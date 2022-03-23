<?php

class ConsignedStock extends BimpObject
{

    // Droits user:

    public function canCreate()
    {
        return 1;
    }

    public function canEdit()
    {
        return $this->canCreate();
    }

    public function canDelete()
    {
        global $user;

        return (int) $user->admin;
    }

    public function canEditField($field_name)
    {
        if (!$this->isLoaded()) {
            return 1;
        }

        global $user;

        if ($user->admin) {
            return 1;
        }

        if (in_array($field_name, array('serials', 'qty'))) {
            return 1;
        }

        return 0;
    }

    public function canSetAction($action)
    {
        switch ($action) {
            case 'correct':
                return $this->isUserAdmin();

            case 'createShipment':
                return 1;

            case 'receive':
                return 1;
        }

        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'correct':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    // Getters données: 

    public function getCentreData($code_centre, &$errors = array())
    {
        $centres = BimpCache::getCentres();

        if (!isset($centres[$code_centre])) {
            $errors[] = 'Aucun centre pour le code "' . $code_centre . '"';
            return array();
        }

        return $centres[$code_centre];
    }

    // Getters params: 

    public function getListHeaderButtons()
    {
        $buttons = array();

        if ($this->canSetAction('receive')) {
            $buttons[] = array(
                'label'   => 'Réception stock consigné',
                'icon'    => 'fas_sign-in-alt',
                'onclick' => $this->getJsActionOnclick('receive', array(), array(
                    'form_name'      => 'receive',
                    'on_form_submit' => 'function($form, extra_data){return ConsignedStocks.onReceiveFormSubmit($form, extra_data);}'
                ))
            );
        }

        return $buttons;
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('correct') && $this->canSetAction('correct')) {
            $buttons[] = array(
                'label'   => 'Corriger le stock',
                'icon'    => 'fas_pen',
                'onclick' => $this->getJsActionOnclick('correct', array(), array(
                    'form_name' => 'correct' . ((int) $this->getData('serialized') ? '_serialized' : '')
                ))
            );
        }

        return $buttons;
    }

    // Getters statics: 

    public static function getStockInstance($code_centre, $part_number)
    {
        return BimpCache::findBimpObjectInstance('bimpapple', 'ConsignedStock', array(
                    'code_centre' => $code_centre,
                    'part_number' => $part_number
                        ), true);
    }

    // Rendus HTML: 

    public function renderReceptionDeliveryNumberSelect()
    {
        $html = '';
        $errors = array();

        $code_centre = BimpTools::getPostFieldValue('code_centre', '');

        if (!$code_centre) {
            $errors[] = 'Veuillez sélectionner un centre';
        } else {
            $centre = $this->getCentreData($code_centre, $errors);

            if (!count($errors)) {
                $shipTo = BimpTools::getArrayValueFromPath($centre, 'shipTo', '');
                if (!$shipTo) {
                    $errors[] = 'Aucun n° shipTo pour le centre ' . $centre['label'];
                } else {
                    require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

                    $gsx = new GSX_v2($shipTo);

                    $statusId = BimpTools::getPostFieldValue('status', 0);
                    $result = $gsx->consignmentOrdersLookup('INCREASE', ($statusId == 1 ? 'ALL' : 'OPEN'));

                    if (!$gsx->logged) {
                        $html .= BimpRender::renderAlerts($gsx->displayNoLogged());
                        $html .= '<input type="hidden" value="" name="deliveryNumber"/>';
                    } else {
                        $errors = $gsx->getErrors();

                        if (!count($errors)) {
                            if (empty($result)) {
                                return BimpRender::renderAlerts('Aucun stock consigné à réceptionner pour ce n° ShipTo', 'warning');
                            } else {
//                                $html .= '<pre>';
//                                $html .= print_r($result, 1);
//                                $html .= '</pre>';
//                                return $html;

                                $deliveries = array();
                                $options = array();

                                foreach ($result as $order) {
                                    if (isset($order['deliveries'])) {
                                        foreach ($order['deliveries'] as $delivery) {
                                            if (!isset($deliveries[$delivery['number']])) {
                                                $dt = new DateTime($delivery['deliveredDate']);

                                                $deliveries[$delivery['number']] = array(
                                                    'number' => $delivery['number'],
                                                    'total'  => 0,
                                                    'qty'    => 0,
                                                    'date'   => $dt->format('d / m / Y')
                                                );
                                            }

                                            if (isset($delivery['parts'])) {
                                                foreach ($delivery['parts'] as $part) {
                                                    $deliveries[$delivery['number']]['total'] += (int) $part['quantityDelivered'];
                                                    $deliveries[$delivery['number']]['qty'] += ((int) $part['quantityDelivered'] - (int) $part['quantityAcknowledged']);
                                                }
                                            }
                                        }
                                    }
                                }

                                foreach ($deliveries as $deliveryNumber => $delivery) {
                                    $options[$delivery['number']] = 'N° ' . $delivery['number'] . ' - ' . $delivery['date'] . ' - ' . $delivery['qty'] . ' unité(s) à réceptionner sur ' . $delivery['total'];
                                }

                                $html .= BimpInput::renderInput('select', 'deliveryNumber', '', array(
                                            'options' => $options
                                ));

                                return $html;
                            }
                        }
                    }
                }
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }
        $html .= '<input type="hidden" value="" name="deliveryNumber"/>';

        return $html;
    }

    public function renderDeliveryPartsQtyInputs()
    {
        $html = '';
        $errors = array();

        $delivery_number = BimpTools::getPostFieldValue('deliveryNumber', '');

        if (!$delivery_number) {
            $errors[] = 'Veuillez sélectionner une livraison';
        }

        $code_centre = BimpTools::getPostFieldValue('code_centre', '');

        if (!$code_centre) {
            $errors[] = 'Veuillez sélectionner un centre';
        } else {
            $centre = $this->getCentreData($code_centre, $errors);

            if (!count($errors)) {
                $shipTo = BimpTools::getArrayValueFromPath($centre, 'shipTo', '');

                if (!$shipTo) {
                    $errors[] = 'Aucun n° shipTo pour le centre ' . $centre['label'];
                }
            }
        }

        if (!count($errors)) {
            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

            $gsx = new GSX_v2($shipTo);

            $result = $gsx->consignmentDeliveryLookup($delivery_number);

            if (!$gsx->logged) {
                $html .= BimpRender::renderAlerts($gsx->displayNoLogged());
            } else {
                $errors = $gsx->getErrors();

                if (!count($errors)) {
                    if (empty($result)) {
                        return BimpRender::renderAlerts('Aucun stock consigné à réceptionner pour ce n° ShipTo', 'warning');
                    } elseif (isset($result[0]['parts']) && !empty($result[0]['parts'])) {
                        $stocks_errors = array();

                        $html .= '<div class="buttonsContainer align-right" style="margin: 5px 0;">';
                        $html .= '<span class="btn btn-default" onclick="ConsignedStocks.receiveNone($(this))">';
                        $html .= BimpRender::renderIcon('fas_times', 'iconLeft') . 'Tout mettre à zéro';
                        $html .= '</span>';
                        $html .= '<span class="btn btn-default" onclick="ConsignedStocks.receiveAll($(this))">';
                        $html .= BimpRender::renderIcon('fas_check', 'iconLeft') . 'Tout réceptionner';
                        $html .= '</span>';
                        $html .= '</div>';

                        $html .= '<table class="bimp_list_table delivery_parts_list">';
                        $html .= '<thead>';
                        $html .= '<tr>';
                        $html .= '<th>Réf</th>';
                        $html .= '<th>Libéllé</th>';
                        $html .= '<th style="text-align: center">Qté à réceptionner</th>';
                        $html .= '<th>Qté reçue / N° de série</th>';
                        $html .= '</tr>';
                        $html .= '</thead>';

                        $html .= '<tbody>';

                        $i = 1;
                        foreach ($result[0]['parts']as $part) {
//                            $html .= '<tr>';
//                            $html .= '<td colspan="4">';
//                            $html .= '<pre>';
//                            $html .= print_r($part, 1);
//                            $html .= '</pre>';
//                            $html .= '</td>';
//                            $html .= '</tr>';

                            $qty_to_receive = (int) $part['quantityDelivered'] - (int) $part['quantityAcknowledged'];
                            $html .= '<tr class="part_row" data-part_number="' . $part['number'] . '">';
                            $html .= '<td>' . $part['number'] . '</td>';
                            $html .= '<td>' . $part['description'] . '</td>';
                            $html .= '<td style="text-align: center"><span class="badge badge-' . ($qty_to_receive ? 'success' : 'danger') . ' part_qty_to_receive">' . $qty_to_receive . '</span></td>';

                            $html .= '<td>';
                            if ($qty_to_receive > 0) {
                                if ((bool) $part['serialized']) {
                                    // Check stock existant: 

                                    $cur_stock = self::getStockInstance($code_centre, $part['number']);
                                    if (BimpObject::objectLoaded($cur_stock) && !((int) $cur_stock->getData('serialized')) && (int) $cur_stock->getData('qty')) {
                                        $stocks_errors[] = 'Stock actuel non sérialisé pour le composant "' . $part['number'] . '"';
                                        continue;
                                    }

                                    $serials = array();
                                    $values = array();

                                    if (isset($part['devices'])) {
                                        foreach ($part['devices'] as $device) {
                                            foreach (array('serial', 'imei', 'imei2', 'meid') as $identifier) {
                                                if (isset($device['identifiers'][$identifier])) {
                                                    $serials[$device['identifiers'][$identifier]] = $device['identifiers'][$identifier];
                                                    $values[] = $device['identifiers'][$identifier];
                                                    break;
                                                }
                                            }
                                        }
                                    }

                                    if (!empty($serials)) {
                                        $html .= BimpInput::renderInput('check_list', 'part_' . $i . '_serials', $values, array(
                                                    'items'        => $serials,
                                                    'search_input' => (count($serials) > 10 ? 1 : 0),
                                                    'extra_class'  => 'part_serials_check_list'
                                        ));
                                    }

                                    $remaining_serials = $qty_to_receive - count($serials);
                                    if ($remaining_serials > 0) {
                                        $html .= '<span class="danger">N° de série à saisir manuellement: </span><br/>';

                                        for ($j = 1; $j <= $remaining_serials; $j++) {
                                            $html .= BimpInput::renderInput('text', 'part_' . $i . '_serial_' . $j, '', array(
                                                        'extra_class' => 'part_serials_text_input'
                                            ));
                                        }
                                    }
                                } else {
                                    $html .= BimpInput::renderInput('qty', 'part_' . $i . '_qty', $qty_to_receive, array(
                                                'extra_class' => 'part_qty_input',
                                                'data'        => array(
                                                    'data_type' => 'number',
                                                    'min'       => 0,
                                                    'max'       => $qty_to_receive
                                                )
                                    ));
                                }
                            }

                            $html .= '</td>';
                            $html .= '</tr>';
                        }

                        $html .= '</tbody>';

                        $html .= '</table>';

                        if (!empty($stocks_errors)) {
                            $html = '';
                            $html .= '<span class="danger">';
                            $html .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                            $html .= 'Des erreurs ont été détectées. Veuillez les corriger pour pouvoir effectuer cette réception';
                            $html .= '</span>';
                            $html .= BimpRender::renderAlerts($stocks_errors);

                            BimpCore::addlog('Erreurs stock(s) consigné(s) à corriger', Bimp_Log::BIMP_LOG_URGENT, 'stock', $this, array(
                                'Centre'       => $centre['label'],
                                'N° réception' => $delivery_number,
                                'Erreurs'      => $stocks_errors
                            ));
                        }
                    } else {
                        $errors[] = 'Aucune unité à réceptionner pour cette livraison';
                    }
                }
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        return $html;
    }

    public function renderShipmentOrderIdSelect()
    {
        $html = '';
        $errors = array();

        $code_centre = BimpTools::getPostFieldValue('code_centre', '');

        if (!$code_centre) {
            $errors[] = 'Veuillez sélectionner un centre';
        } else {
            $centre = $this->getCentreData($code_centre, $errors);

            if (!count($errors)) {
                $shipTo = BimpTools::getArrayValueFromPath($centre, 'shipTo', '');

                if (!$shipTo) {
                    $errors[] = 'Aucun n° shipTo pour le centre ' . $centre['label'];
                } else {
                    require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

                    $gsx = new GSX_v2($shipTo);

                    $result = $gsx->consignmentOrdersLookup('DECREASE', 'OPEN');

                    if (!$gsx->logged) {
                        $html .= BimpRender::renderAlerts($gsx->displayNoLogged());
                        $html .= '<input type="hidden" value="" name="deliveryNumber"/>';
                    } else {
                        $errors = $gsx->getErrors();

                        if (!count($errors)) {
                            if (empty($result)) {
                                return BimpRender::renderAlerts('Aucun stock consigné à renvoyer à Apple pour ce n° ShipTo', 'warning');
                            } else {
//                                $html .= '<pre>';
//                                $html .= print_r($result, 1);
//                                $html .= '</pre>';
//                                return $html;

                                $orders = array();
                                foreach ($result as $order) {
                                    if (isset($order['parts'])) {
                                        $total = 0;
                                        $qty = 0;

                                        foreach ($order['parts'] as $part) {
                                            $total += (int) $part['partQuantity'];
                                            $qty += ((int) $part['partQuantity'] - (int) $part['quantitySubmitted']);
                                        }

                                        $orders[$order['orderId']] = 'N° ' . $order['orderId'] . ' - ' . $qty . ' unité(s) à renvoyer sur ' . $total;
                                    }
                                }

                                $html .= BimpInput::renderInput('select', 'orderId', '', array(
                                            'options' => $orders
                                ));

                                return $html;
                            }
                        }
                    }
                }
            }
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }
        $html .= '<input type="hidden" value="" name="deliveryNumber"/>';

        return $html;
    }

    // Traitements:

    public function correctStock($qty_modif, $serial = '', $code_mvt = '', $desc = '', &$warnings = array(), $log_error = false, $no_db_transactions = false)
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $serials = $this->getData('serials');
            $qty = (int) $this->getData('qty');

            if ((int) $this->getData('serialized')) {
                if (!$serial) {
                    $errors[] = 'Composant sérialisé - n° de série obligatoire';
                } else {
                    if ($qty_modif < 0) {
                        if (!in_array($serial, $serials)) {
                            $errors[] = 'Le n° de série "' . $serial . '" n\'est pas en stock';
                        } else {
                            foreach ($serials as $idx => $s) {
                                if ($s == $serial) {
                                    unset($serials[$idx]);
                                    break;
                                }
                            }
                            $qty_modif = -1;
                        }
                    } else if ($qty_modif > 0) {
                        if (in_array($serial, $serials)) {
                            $errors[] = 'Le n° de série "' . $serial . '" est déjà en stock';
                        } else {
                            $serials[] = $serial;
                            $qty_modif = 1;
                        }
                    }

                    $qty = count($serials);
                }
            } else {
                $serials = array();
                $qty += $qty_modif;
            }

            if (!count($errors)) {
                $prev_db = $this->db;
                if ($no_db_transactions) {
                    $this->useNoTransactionsDb();
                }

                $this->set('serials', $serials);
                $this->set('qty', $qty);
                $errors = $this->update($warnings, true);

                if (!count($errors)) {
                    global $user;

                    $mvt_errors = array();
                    $mvt_warnings = array();
                    BimpObject::createBimpObject('bimpapple', 'ConsignedStockMvt', array(
                        'id_stock'    => (int) $this->id,
                        'id_user'     => (int) $user->id,
                        'date'        => date('Y-m-d H:i:s'),
                        'qty'         => $qty_modif,
                        'serial'      => $serial,
                        'code_mvt'    => $code_mvt,
                        'description' => $desc
                            ), true, $mvt_errors, $mvt_warnings, $no_db_transactions);

                    if (count($mvt_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($mvt_errors, $this->getData('part_number'));
                    }
                } elseif ($log_error) {
                    BimpCore::addlog('Echec mise à jour stock consigné Apple - A corriger', Bimp_Log::BIMP_LOG_ERREUR, 'stocks', $this, array(
                        'Modificateur qté' => $qty_modif,
                        'Serial'           => $serial,
                        'Code mvt'         => $code_mvt,
                        'Description mvt'  => $desc
                            ), true);
                }

                $this->db = $prev_db;
            }
        }

        return $errors;
    }

    // Actions: 

    public function actionCorrect($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $sc = 'triggerObjectChange(\'bimpapple\', \'ConsignedStockMvt\');';

        $code_mvt = 'MANUAL';
        $desc = BimpTools::getArrayValueFromPath($data, 'infos', '');

        if ((int) $this->getData('serialized')) {
            $nAdded = 0;
            $nRemoved = 0;

            $serials = $this->getData('serials');
            $new_serials = BimpTools::getArrayValueFromPath($data, 'serials', array());

            foreach ($serials as $serial) {
                if (!in_array($serial, $new_serials)) {
                    $stock_errors = $this->correctStock(-1, $serial, $code_mvt, $desc, $warnings);

                    if (count($stock_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Echec retrait du n° de série "' . $serial . '"');
                    } else {
                        $nRemoved++;
                    }
                }
            }

            foreach ($new_serials as $serial) {
                if (!in_array($serial, $serials)) {
                    $stock_errors = $this->correctStock(1, $serial, $code_mvt, $desc, $warnings);

                    if (count($stock_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($stock_errors, 'Echec ajout du n° de série "' . $serial . '"');
                    } else {
                        $nAdded++;
                    }
                }
            }

            if (!$nAdded && !$nRemoved) {
                $warnings[] = 'Aucun changement n\'a été éffectué';
            } else {
                if ($nAdded) {
                    $success .= ($success ? '<br/>' : '') . $nAdded . ' n° de série ajouté(s) avec succès';
                }
                if ($nRemoved) {
                    $success .= ($success ? '<br/>' : '') . $nRemoved . ' n° de série retiré(s) avec succès';
                }
            }
        } else {
            $mode = BimpTools::getArrayValueFromPath($data, 'mode', '');

            if (!$mode) {
                $errors[] = 'Mode de saisie des quantités absent';
            } else {
                $qty_modif = 0;

                switch ($mode) {
                    case 'total':
                        $new_qty = (int) BimpTools::getArrayValueFromPath($data, 'qty', $this->getData('qty'));
                        $qty = (int) $this->getData('qty');
                        $qty_modif = $new_qty - $qty;
                        break;

                    case 'add':
                        $qty_modif = (int) BimpTools::getArrayValueFromPath($data, 'qty_to_add', 0);
                        break;

                    case 'remove':
                        $qty_modif = (int) BimpTools::getArrayValueFromPath($data, 'qty_to_remove', 0) * -1;
                        break;
                }

                if (!$qty_modif) {
                    $errors[] = 'Nouvelle quantité identique à la quantité actuelle';
                } else {
                    $errors = $this->correctStock($qty_modif, '', $code_mvt, $desc, $warnings);

                    if (!count($errors)) {
                        $success = 'Correction du stock effectuée avec succès.<br/>Nouvelle quantité <b>' . $this->getData('qty') . '</b>';
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $sc
        );
    }

    public function actionReceive($data, &$success = '')
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $shipTo = '';
        $code_centre = BimpTools::getArrayValueFromPath($data, 'code_centre', '');

        if (!$code_centre) {
            $errors[] = 'Centre absent';
        } else {
            $centre = $this->getCentreData($code_centre, $errors);

            if (!count($errors)) {
                $shipTo = BimpTools::getArrayValueFromPath($centre, 'shipTo', '');

                if (!$shipTo) {
                    $errors[] = 'Aucun n° shipTo pour le centre ' . $centre['label'];
                }
            }
        }

        $deliveryNumber = BimpTools::getArrayValueFromPath($data, 'deliveryNumber', '');

        if (!$deliveryNumber) {
            $errors[] = 'N° de livraison absent';
        }

        if (!count($errors)) {
            $parts = array();

            $data_parts = BimpTools::getArrayValueFromPath($data, 'parts', array());
            $extra_serials = array();

            if (!empty($data_parts)) {
                foreach ($data_parts as $part) {
                    if (isset($part['qty']) && (int) $part['qty'] > 0) {
                        $parts[] = array(
                            'number'   => $part['part_number'],
                            'quantity' => (int) $part['qty']
                        );
                    } elseif (isset($part['serials']) && is_array($part['serials']) && !empty($part['serials'])) {
                        foreach ($part['serials'] as $serial_data) {
                            $new_part = array(
                                'number' => $part['part_number']
                            );

//                            if ((int) $serial_data['manual']) {
//                                $new_part['quantity'] = 1;
//                            if (!isset($extra_serials[$part['part_number']])) {
//                                $extra_serials[$part['part_number']] = array();
//                            }
//                            $extra_serials[$part['part_number']][] = $serial_data['serial'];
//                            } else {
                            $new_part['device']['id'] = $serial_data['serial'];
//                            }

                            $parts[] = $new_part;
                        }
                    }
                }

                if (empty($parts)) {
                    $errors[] = 'Aucun utité à réceptionner saisie ou sélectionnée';
                } else {
                    // Requête GSX: 
                    require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

                    $gsx = new GSX_v2($shipTo);

                    $result = $gsx->consignmentDeliveryAcknowledge($deliveryNumber, $parts);
                    if (!$gsx->logged) {
                        $errors[] = $gsx->displayNoLogged();
                    } else {
                        $errors = $gsx->getErrors();

                        if (!count($errors) && !is_array($result) || empty($result)) {
                            $errors[] = 'Aucune réponse reçue';
                        }
                    }

                    if (!count($errors)) {
                        $success .= 'Enregistrement des unités reçues sur GSX effectuée avec succès';

                        // Enregistrement parts: 
                        $nOk = 0;
                        $nFails = 0;

                        if (isset($result['parts']) && is_array($result['parts'])) {
                            foreach ($result['parts'] as $part) {
                                if ($part['statusCode'] == 'ACKNOWLEDGEMENT_SUCCESSFUL') {
                                    $qty = 0;
                                    $serial = '';

                                    if (isset($part['device']['identifiers'])) {
                                        foreach (array('serial', 'imei', 'imei2', 'meid') as $identifier) {
                                            if (isset($part['device']['identifiers'][$identifier])) {
                                                $serial = $part['device']['identifiers'][$identifier];
                                                break;
                                            }
                                        }

                                        if (!$serial) {
                                            BimpCore::addlog('Stock consigné - identifiant device inconnu', Bimp_Log::BIMP_LOG_URGENT, 'gsx', null, array(
                                                'Part Number'  => $part['number'],
                                                'Identifiants' => $part['device']['identifiers']
                                                    ), true);
                                            $nFails++;
                                            continue;
                                        }
                                    } elseif (isset($part['quantity'])) {
                                        $qty = (int) $part['quantity'];
                                    }

                                    if (!$serial && !$qty) {
                                        continue;
                                    }

                                    // Recherche entrée existante: 
                                    $stock = self::getStockInstance($code_centre, $part['number']);

                                    if (!BimpObject::objectLoaded($stock)) {
                                        // Création: 
                                        $cs_data = array(
                                            'part_number' => $part['number'],
                                            'code_centre' => $code_centre,
                                            'serialized'  => ($serial ? 1 : 0)
                                        );

                                        $create_errors = array();
                                        $create_warnings = array();
                                        $stock = BimpObject::createBimpObject('bimpapple', 'ConsignedStock', $cs_data, true, $create_errors, $create_warnings, true);

                                        if (count($create_errors)) {
                                            $warnings[] = BimpTools::getMsgFromArray($create_errors, $part['number']);

                                            BimpCore::addlog('Echec création stock consigné Apple - A ajouter manuellement impérativement', Bimp_Log::BIMP_LOG_ERREUR, 'stocks', null, array(
                                                'Données'            => $cs_data,
                                                'Quantité réception' => (int) BimpTools::getArrayValueFromPath($part, 'quantity', 0),
                                                'Serial'             => $serial
                                                    ), true);
                                            $nFails++;
                                        }
                                    }

                                    if (BimpObject::objectLoaded($stock)) {
                                        // mise à jour stock: 
                                        $qty_modif = (int) BimpTools::getArrayValueFromPath($part, 'quantity', 0);
                                        if ($serial) {
                                            $qty_modif = 1;
                                        }

                                        $mvt_warnings = array();
                                        $desc = 'Réception n° ' . $deliveryNumber;
                                        $mvt_errors = $stock->correctStock($qty_modif, $serial, 'DELIVERY_' . $deliveryNumber, $desc, $mvt_warnings, true, true);

                                        if (count($mvt_errors)) {
                                            if ($serial) {
                                                $warnings[] = BimpTools::getMsgFromArray($mvt_errors, $part['number'] . ' - Echec ajout du n° de série "' . $serial . '"');
                                            } else {
                                                $warnings[] = BimpTools::getMsgFromArray($mvt_errors, $part['number'] . ' - Echec mise à jour du stock');
                                            }

                                            $nFails++;
                                        } else {
                                            $nOk++;
                                        }
                                    }
                                } else {
                                    $warnings[] = 'Erreur - Composant "' . $part['number'] . '" : ' . $part['statusDescription'];
                                }
                            }
                        }

                        // Traitement des extra_serials: 
                        if (!empty($extra_serials)) {
                            foreach ($extra_serials as $part_number => $serials) {
                                $stock = self::getStockInstance($code_centre, $part_number);

                                if (!BimpObject::objectLoaded($stock)) {
                                    // Création du stock: 
                                    $create_errors = array();
                                    $create_warnings = array();
                                    $stock = BimpObject::createBimpObject('bimpapple', 'ConsignedStock', array(
                                                'part_number' => $part_number,
                                                'code_centre' => $code_centre,
                                                'serialized'  => 1
                                                    ), true, $create_errors, $create_warnings, true);

                                    if (count($create_errors)) {
                                        $warnings[] = BimpTools::getMsgFromArray($create_errors, $part_number);

                                        BimpCore::addlog('Echec création stock consigné Apple - A ajouter manuellement impérativement', Bimp_Log::BIMP_LOG_ERREUR, 'stocks', null, array(
                                            'Part number' => $part_number,
                                            'code_centre' => $code_centre,
                                            'Serials'     => $serial
                                                ), true);
                                        $nFails++;
                                    }
                                }

                                if (BimpObject::objectLoaded($stock)) {
                                    foreach ($serials as $serial) {
                                        $mvt_errors = $this->correctStock(1, $serial, 'DELIVERY_' . $deliveryNumber, $desc, $warnings, true, true);

                                        if (count($mvt_errors)) {
                                            $errors[] = BimpTools::getMsgFromArray($mvt_errors, 'Echec ajout du n° de série "' . $serial . '"');
                                            $nFails++;
                                        } else {
                                            $nOk++;
                                        }
                                    }
                                }
                            }
                        }
                        if ($nOk) {
                            $success .= '<br/>' . $nOk . ' composant(s) du stock consigné mis à jour';
                        }

                        if ($nFails) {
                            mailSyn2('ERREURS STOCKS CONSIGNES APPLE', 'dev@bimp.fr', '', $nFails . ' erreur(s) à corriger manuellement  - Voir les logs');
                        }
                    }
                }
            }
        }


        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCreateShipment($data, &$success = '')
    {
        $errors = array();
        $warnings = array();
        $success = 'Renvoi de stock consigné ajouté avec succès';
        $success_callback = '';

        $code_centre = BimpTools::getArrayValueFromPath($data, 'code_centre', '');
        $order_id = BimpTools::getArrayValueFromPath($data, 'orderId', '');

        if (!$code_centre) {
            $errors[] = 'Veuillez sélectionner un centre';
        }

        if (!$order_id) {
            $errors[] = 'Veuillez sélectionner un n° de commande';
        }

        if (!count($errors)) {
            $shipment = BimpCache::findBimpObjectInstance('bimpapple', 'ConsignedStockShipment', array(
                        'code_centre' => $code_centre,
                        'order_id'    => $order_id,
                        'status'      => 0
                            ), true);

            if (BimpObject::objectLoaded($shipment)) {
                $errors[] = 'Un renvoi au statut brouillon existe déjà pour ce numéro de commande: ' . $shipment->getLink();
            } else {
                $shipment = BimpObject::createBimpObject('bimpapple', 'ConsignedStockShipment', array(
                            'code_centre' => $code_centre,
                            'order_id'    => $order_id
                                ), true, $errors, $warnings);

                if (BimpObject::objectLoaded($shipment)) {
                    $url = $shipment->getUrl();

                    if ($url) {
                        $success_callback .= 'window.open(\'' . $url . '\');';
                    }
                }
            }
        }

        $success_callback .= 'triggerObjectChange(\'bimpapple\', \'ConsignedStockShipment\');';

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    // Overrides: 

    public function validate()
    {
        $errors = array();

        if ((int) $this->getData('serialized')) {
            $this->set('qty', count($this->getData('serials')));
        } else {
            $this->set('serials', array());
        }

        if (!count($errors)) {
            $errors = parent::validate();
        }

        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        $stock = self::getStockInstance($this->getData('code_centre'), $this->getData('part_number'));

        if (BimpObject::objectLoaded($stock)) {
            $errors[] = 'Cette référence existe déja pour ce centre';
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors) && !$this->getData('serialized')) {
            $qty = BimpTools::getValue('qty_tot', 0);
            if ($qty > 0) {
                $errors = $this->correctStock($qty, '', 'CREATION');
            }
        }

        return $errors;
    }
}
