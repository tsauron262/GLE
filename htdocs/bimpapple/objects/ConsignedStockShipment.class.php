<?php

class ConsignedStockShipment extends BimpObject
{

    const STATUT_ANNULE = -1;
    const STATUT_BROUILLON = 0;
    const STATUT_ATTENTE_ENVOI = 1;
    const STATUT_ENVOYE = 2;

    public static $status_list = array(
        self::STATUT_ANNULE        => array('label' => 'Annulé', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::STATUT_BROUILLON     => array('label' => 'Brouillon', 'icon' => 'far_file-alt', 'classes' => array('warning')),
        self::STATUT_ATTENTE_ENVOI => array('label' => 'En attente d\'envoi', 'icon' => 'fas_hourglass-start', 'classes' => array('info')),
        self::STATUT_ENVOYE        => array('label' => 'Expédié', 'icon' => 'fas_check', 'classes' => array('success')),
    );
    public static $carriers = array(
        'UPSWW065' => 'UPS'
    );

    // Droits Users: 

    public function canSetAction($action)
    {
        switch ($action) {
            case 'submit':
            case 'ship':
                return 1;
        }

        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'saveParts':
            case 'submit':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                if ((int) $this->getData('status')) {
                    $errors[] = 'Ce renvoi n\'est plus au statut brouillon';
                    return 0;
                }
                return 1;

            case 'ship':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                if ((int) $this->getData('status') !== self::STATUT_ATTENTE_ENVOI) {
                    $errors[] = 'Ce renvoi n\'est pas en attente d\'envoi';
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isEditable($force_edit = false, &$errors = array())
    {
        if ($force_edit) {
            return 1;
        }

        if ((int) $this->getData('status') === self::STATUT_ENVOYE) {
            return 0;
        }

        return 1;
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if ($force_delete) {
            return 1;
        }

        if ((int) $this->getData('status') <= 0) {
            return 1;
        }

        return 0;
    }

    // Getters params: 

    public function getListHeaderButtons()
    {
        $buttons = array();

        $stock = BimpObject::getInstance('bimpapple', 'ConsignedStock');
        if ($stock->canSetAction('createShipment')) {
            $buttons[] = array(
                'label'   => 'Renvoi stock consigné',
                'icon'    => 'fas_sign-out-alt',
                'onclick' => $stock->getJsActionOnclick('createShipment', array(), array(
                    'form_name' => 'create_shipment'
                ))
            );
        }

        return $buttons;
    }

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('submit') && $this->canSetAction('submit')) {
            $buttons[] = array(
                'label'   => 'Valider et soumettre à Apple',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('submit', array(), array(
                    'confirm_msg' => 'La liste des composants du stock consigné à renvoyer va être soumise à Apple. Cette action est irreversible. Veuillez confirmer'
                ))
            );
        }

        if ($this->isActionAllowed('ship') && $this->canSetAction('ship')) {
            $buttons[] = array(
                'label'   => 'Expédition effectuée',
                'icon'    => 'fas_check',
                'onclick' => $this->getJsActionOnclick('ship', array(), array(
                    'form_name' => 'ship'
                ))
            );
        }


        return $buttons;
    }

    // Getters données: 

    public function getShipTo(&$errors = array())
    {
        $shipTo = '';
        $centre = $this->getData('code_centre');

        if (!$centre) {
            $errors[] = 'Centre absent';
        } else {
            $centres = BimpCache::getCentres();

            if (!isset($centres[$centre])) {
                $errors[] = 'Aucun centre pour le code "' . $centre . '"';
            } else {
                $shipTo = BimpTools::getArrayValueFromPath($centres[$centre], 'shipTo', '');

                if (!$shipTo) {
                    $errors[] = 'Aucun n° shipTo pour le centre ' . $centres[$centre]['label'];
                }
            }
        }

        return $shipTo;
    }

    // Rendus HTML: 

    public function renderHeaderExtraLeft()
    {
        $html = '';

        $status = (int) $this->getData('status');

        if ($status > 0) {
            $html .= '<div class="object_header_infos">';
            $html .= 'Validé le ' . $this->displayData('date_submitted', 'default', false);
            $html .= '</div>';
        }
        if ($status > 1) {
            $html .= '<div class="object_header_infos">';
            $html .= 'Expédié le ' . $this->displayData('date_shipped', 'default', false);
            $html .= '</div>';
        }
        return $html;
    }

    public function renderPartsList($content_only = false)
    {
        $html = '';
        $errors = array();

        $parts = $this->getData('parts');

        if ((int) $this->getData('status') === 0) {
            $shipTo = $this->getShipTo($errors);
            $orderId = $this->getData('order_id');

            if (!$orderId) {
                $errors[] = 'N° de commande absent';
            }

            if (!count($errors)) {
                require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
                $gsx = new GSX_v2($shipTo);

                $result = $gsx->consignmentOrderLookup($orderId);

                if (!$gsx->logged) {
                    $html .= BimpRender::renderAlerts('Non connecté à GSX. Veillez vous connecter puis cliquer sur "Actualiser"');
                    $html .= '<div class="buttonsContainer">';
                    $html .= '<span class="btn btn-default" onclick="gsx_open_login_modal($(this));">';
                    $html .= 'Se connecter' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
                    $html .= '</span>';
                    $html .= '</div>';
                } else {
                    $errors = $gsx->getErrors();

                    if (!count($errors)) {
                        $code_centre = $this->getData('code_centre');
                        $rows = array();

                        foreach ($result as $order) {
                            if ($order['orderId'] == $orderId) {
                                if (isset($order['parts'])) {
                                    $i = 1;
                                    foreach ($order['parts'] as $orderPart) {
                                        if ((int) $orderPart['partQuantity'] != (int) $orderPart['quantitySubmitted']) {
                                            $qtyPending = (int) ((int) $orderPart['partQuantity'] - (int) $orderPart['quantitySubmitted']);

                                            $row = array(
                                                'row_data'   => array(
                                                    'part_number' => $orderPart['number']
                                                ),
                                                'number'     => $orderPart['number'],
                                                'desc'       => $orderPart['description'],
                                                'qtyPending' => $qtyPending,
                                                'qtyStock'   => '',
                                                'qty'        => ''
                                            );

                                            $max = $qtyPending;
                                            $qty = 0;
                                            $selected_serials = array();
                                            $serials = array();

                                            $qtyStock = 0;
                                            $stock = BimpCache::findBimpObjectInstance('bimpapple', 'ConsignedStock', array(
                                                        'code_centre' => $code_centre,
                                                        'part_number' => $orderPart['number']
                                            ));

                                            if (BimpObject::objectLoaded($stock)) {
                                                if ((int) $orderPart['serialized']) {
                                                    $serials = $stock->getData('serials');
                                                    $qtyStock = count($serials);
                                                } else {
                                                    $qtyStock = (int) $stock->getData('qty');
                                                }
                                            }

                                            if ($max > $qtyStock) {
                                                $max = $qtyStock;

                                                if ($qty > $max) {
                                                    $qty = $max;
                                                }
                                            }

                                            $row['qtyStock'] = '<span class="badge badge-' . ($qtyStock >= $qtyPending ? 'success' : ($qtyStock > 0 ? 'warning' : 'danger')) . '">' . $qtyStock . '</span>';

                                            if (isset($parts[$orderPart['number']])) {
                                                $part = $parts[$orderPart['number']];

                                                if ((int) $orderPart['serialized']) {
                                                    $selected_serials = BimpTools::getArrayValueFromPath($part, 'serials', array());
                                                    $qty = count($selected_serials);
                                                } else {
                                                    $qty = (int) BimpTools::getArrayValueFromPath($part, 'qty', 0);
                                                }
                                            }

                                            if ($qtyStock > 0) {
                                                if ((int) $orderPart['serialized']) {
                                                    $items = array();
                                                    $values = array();

                                                    foreach ($selected_serials as $serial) {
                                                        $values[$serial] = $serial;
                                                    }

                                                    foreach ($serials as $serial) {
                                                        $items[$serial] = $serial;
                                                    }

                                                    $row['qty'] .= BimpInput::renderInput('check_list', 'part_' . $i . '_serials', $values, array(
                                                                'items'        => $items,
                                                                'search_input' => (count($items) > 10 ? 1 : 0),
                                                                'extra_class'  => 'part_serials_check_list',
                                                                'max'          => $max
                                                    ));
                                                } else {
                                                    $row['qty'] = BimpInput::renderInput('qty', 'part_' . $i . '_qty', $qty, array(
                                                                'extra_class' => 'part_qty_input',
                                                                'data'        => array(
                                                                    'data_type' => 'number',
                                                                    'min'       => 0,
                                                                    'max'       => $max
                                                                )
                                                    ));
                                                }
                                            }

                                            $rows[] = $row;
                                            $i++;
                                        }
                                    }
                                }
                            }
                        }

                        if (empty($rows)) {
                            $html .= BimpRender::renderAlerts('Aucun stock consigné à renvoyer trouver pour ce n° de commande', 'warning');
                        } else {
                            $headers = array(
                                'number'     => 'Ref.',
                                'desc'       => 'Libellé',
                                'qtyPending' => array('label' => 'Qté attendue', 'align' => 'center'),
                                'qtyStock'   => array('label' => 'Qté en stock', 'align' => 'center'),
                                'qty'        => 'Qté / N° de série à renvoyer'
                            );

                            $html .= '<div class="parts_form">';
                            $html .= BimpRender::renderBimpListTable($rows, $headers, array(
                                        'searchable'  => true,
                                        'search_mode' => 'lighten'
                            ));

                            $html .= '<div class="ajaxResultContainer" style="display: none"></div>';

                            $html .= '<div class="buttonsContainer align-right">';
                            $html .= '<span class="btn btn-primary" onclick="ConsignedStocksShipment.saveParts($(this), ' . $this->id . ');">';
                            $html .= BimpRender::renderIcon('fas_save', 'iconLeft') . 'Enregistrer les modifications';
                            $html .= '</span>';
                            $html .= '</div>';
                            $html .= '</div>';
                        }
                    }
                }
            }
        } elseif ((int) $this->getData('status') > 0) {
            $headers = array(
                'ref'     => 'Réf.',
                'qty'     => 'Qté renvoyés',
                'serials' => 'N° de serie'
            );

            $rows = array();

            foreach ($parts as $part_number => $part) {
                $row = array(
                    'ref' => $part_number
                );

                if (isset($part['serials'])) {
                    $row['qty'] = count($part['serials']);
                    $row['serials'] = '';

                    foreach ($part['serials'] as $serial) {
                        $row['serials'] .= ($row['serials'] ? '<br/>' : '') . $serial;
                    }
                } else {
                    $row['qty'] = BimpTools::getArrayValueFromPath($part, 'qty', 0);
                }

                $rows[] = $row;
            }

            $html .= '<div class="col-xs-12 col-md-6 col-lg-6">';
            $html .= BimpRender::renderBimpListTable($rows, $headers);
            $html .= '</div>';
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        if ($content_only) {
            return $html;
        }

        $title = BimpRender::renderIcon('fas_bars', 'iconLeft') . 'Liste des composants';
        return BimpRender::renderPanel($title, $html, '', array(
                    'foldable'       => true,
                    'type'           => 'secondary',
                    'header_buttons' => array(
                        array(
                            'label'   => 'Actualiser',
                            'icon'    => 'fas_undo',
                            'onclick' => $this->getJsLoadCustomContent('renderPartsList', '$(this).findParentByClass(\'panel\').children(\'.panel-body\')', array(true))
                        )
                    )
        ));
    }

    // Traitements: 

    public function checkParts($new_parts, &$errors = array(), &$warnings = array())
    {
        if ($this->isLoaded($errors)) {
            if ((int) $this->getData('status') === 0) {
                if (!empty($new_parts)) {
                    $shipTo = $this->getShipTo($errors);
                    $orderId = $this->getData('order_id');

                    if (!$orderId) {
                        $errors[] = 'N° de commande absent';
                    }

                    if (!count($errors)) {
                        require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
                        $gsx = new GSX_v2($shipTo);

                        $result = $gsx->consignmentOrderLookup($orderId);

                        if (!$gsx->logged) {
                            $errors[] = $gsx->displayNoLogged();
                        } else {
                            $errors = $gsx->getErrors();

                            if (!count($errors)) {
                                $parts = array();
                                $code_centre = $this->getData('code_centre');

                                foreach ($result as $order) {
                                    if ($order['orderId'] == $orderId) {
                                        if (isset($order['parts'])) {
                                            foreach ($order['parts'] as $orderPart) {
                                                $partNumber = $orderPart['number'];

                                                if (isset($new_parts[$partNumber])) {
                                                    $qtyPending = (int) ((int) $orderPart['partQuantity'] - (int) $orderPart['quantitySubmitted']);
                                                    $stock = BimpCache::findBimpObjectInstance('bimpapple', 'ConsignedStock', array(
                                                                'code_centre' => $code_centre,
                                                                'part_number' => $partNumber
                                                                    ), true);

                                                    if ($qtyPending > 0) {
                                                        if (BimpObject::objectLoaded($stock)) {
                                                            if ((int) $orderPart['serialized']) {
                                                                $serials = array();
                                                                $stocks_serials = $stock->getData('serials');

                                                                if (isset($new_parts[$partNumber]['serials']) && !empty($new_parts[$partNumber]['serials'])) {
                                                                    foreach ($new_parts[$partNumber]['serials'] as $serial) {
                                                                        if (!in_array($serial, $stocks_serials)) {
                                                                            $warnings[] = $partNumber . ': le n° de série "' . $serial . '" n\'est plus présent en stock';
                                                                        } else {
                                                                            $serials[] = $serial;
                                                                        }
                                                                    }

                                                                    if (count($serials) > $qtyPending) {
                                                                        $errors[] = $partNumber . ': nombre de n° de série sélectionné supérieur au nombre attendu. Veuillez retirer ' . (count($serials) - $qtyPending) . ' éléments';
                                                                    } elseif (!empty($serials)) {
                                                                        $parts[$partNumber] = array('serials' => $serials);
                                                                    }
                                                                }
                                                            } else {
                                                                $new_qty = (int) BimpTools::getArrayValueFromPath($new_parts, $partNumber . '/qty', 0);

                                                                if ($new_qty > 0) {
                                                                    $stock_qty = (int) $stock->getData('qty');

                                                                    if (!$stock_qty) {
                                                                        $warnings[] = $partNumber . ': aucune unité disponible en stock';
                                                                        $new_qty = 0;
                                                                    } elseif ($new_qty > $stock_qty) {
                                                                        if ($stock_qty > 1) {
                                                                            $warnings[] = $partNumber . ': seules ' . $stock_qty . ' unités sont disponibles en stock';
                                                                        } else {
                                                                            $warnings[] = $partNumber . ': seule ' . $stock_qty . ' unité est disponible en stock';
                                                                        }

                                                                        $new_qty = $stock_qty;
                                                                    }

                                                                    if ($new_qty > 0) {
                                                                        if ($new_qty > $qtyPending) {
                                                                            if ($qtyPending > 1) {
                                                                                $warnings[] = $partNumber . ': seules ' . $qtyPending . ' unités sont attendues';
                                                                            } else {
                                                                                $warnings[] = $partNumber . ': seule ' . $qtyPending . ' unité est attendue';
                                                                            }

                                                                            $new_qty = $qtyPending;
                                                                        }

                                                                        if ($new_qty > 0) {
                                                                            $parts[$partNumber] = array('qty' => $new_qty);
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        } else {
                                                            $warnings[] = $partNumber . ': aucune unité en stock';
                                                        }
                                                    } else {
                                                        $warnings[] = $partNumber . ': aucune unité attendue';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                return $parts;
                            }
                        }
                    }
                }
            } else {
                $errors[] = 'Ce renvoi n\'est plus au statut brouillon';
            }
        }

        return $new_parts;
    }

    // Actions: 

    public function actionSaveParts($data, &$success = '')
    {
        $errors = array();
        $warnings = array();
        $success = 'Enregistrement effectué avec succès';

        if (!count($errors)) {
            $new_parts = array();

            foreach (BimpTools::getArrayValueFromPath($data, 'parts', array()) as $new_part) {
                $new_parts[$new_part['number']] = array();

                if (isset($new_part['qty'])) {
                    $new_parts[$new_part['number']]['qty'] = $new_part['qty'];
                } elseif (isset($new_part['serials'])) {
                    $new_parts[$new_part['number']]['serials'] = $new_part['serials'];
                } else {
                    $new_parts[$new_part['number']]['serials'] = array();
                }
            }

            $parts = $this->checkParts($new_parts, $errors, $warnings);

            if (!count($errors)) {
                $errors = $this->updateField('parts', $parts);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSubmit($data, &$success = '')
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $parts = $this->checkParts($this->getData('parts'), $errors, $warnings);

        if (!count($errors)) {
            $this->updateField('parts', $parts);

            if (count($warnings)) {
                $errors[] = 'Des corrections ont été faites. Veuillez vérifier les quantités et les numéros de série avant de reétirer la validation';
            }

            if (empty($parts)) {
                $errors[] = 'Aucun composant à renvoyer';
            }

            if (!count($errors)) {
                $shipTo = $this->getShipTo();
                require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
                $gsx = new GSX_v2($shipTo);
                $orderId = $this->getData('order_id');

                if (BimpCore::isModeDev()) {
                    $warnings[] = 'Mode DEV: aucune requête GSX effectuée';
                    $result = array(
                        'orderId' => $orderId,
                        'parts'   => array()
                    );

                    foreach ($parts as $partNumber => $data) {
                        $resPart = array(
                            'number'            => $partNumber,
                            'statusCode'        => 'SUBMIT_SUCCESS',
                            'statusDescription' => 'Submission Successful'
                        );

                        if (isset($data['serials']) && is_array($data['serials'])) {
                            foreach ($data['serials'] as $serial) {
                                $resPart['quantity'] = 1;
                                $resPart['device']['identifiers']['serial'] = $serial;

                                $result['parts'][] = $resPart;
                            }
                        } elseif (isset($data['qty'])) {
                            $resPart['quantity'] = (int) $data['qty'];
                            $result['parts'][] = $resPart;
                        }
                    }
                } else {
                    $orderParts = array();

                    foreach ($parts as $partNumber => $data) {
                        if (isset($data['serials']) && is_array($data['serials']) && !empty($data['serials'])) {
                            foreach ($data['serials'] as $serial) {
                                $orderParts[] = array(
                                    'number' => $partNumber,
                                    'device' => array(
                                        'id' => $serial
                                    )
                                );
                            }
                        } elseif (isset($data['qty']) && (int) $data['qty'] > 0) {
                            $orderParts[] = array(
                                'number'   => $partNumber,
                                'quantity' => (int) $data['qty']
                            );
                        }
                    }

                    $result = $gsx->consignmentOrderSubmit($this->getData('order_id'), $orderParts);
                }

                if (empty($result) || !isset($result['parts']) || empty($result['parts'])) {
                    if (!$gsx->logged) {
                        $errors[] = $gsx->displayNoLogged();
                    } else {
                        $errors = $gsx->getErrors();

                        if (empty($errors)) {
                            $errors[] = 'Echec de la requête pour une raison inconnue';
                        }
                    }
                } else {
                    $success .= 'Soumission du renvoi à Apple effectuée avec succès';

//                    $this->useNoTransactionsDb();//Pourquoi ? En plein millieu de script, ca fait un locktimeout.(l'instance transaction a deja bossé dessus.L'autre instance arrive pas a bossé. Au pire faire un commit juste avant.
                    $up_errors = $this->updateField('status', self::STATUT_ATTENTE_ENVOI);
                    $this->updateField('date_submitted', date('Y-m-d H:i:s'));

                    if (count($up_errors)) {
                        // Le statut doit absolument être changé. On tente en sql direct:
                        if ($this->db->update($this->getTable(), array(
                                    'status' => self::STATUT_ATTENTE_ENVOI
                                        ), 'id = ' . (int) $this->id) <= 0) {
                            $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour du statut de ce renvoi - Veuillez impérativement éviter toute nouvelle opération jusqu\'à correction manuelle');

                            BimpCore::addlog('A CORRIGER MANUELLEMENT - Echec du passage du renvoi de stock consigné au statut "En attente d\'envoi', Bimp_Log::BIMP_LOG_URGENT, 'gsx', $this, array(
                                'Erreurs'    => $up_errors,
                                'Erreur SQL' => $this->db->err()
                                    ), true);
                        }
                    }

                    $code_centre = $this->getData('code_centre');
                    $nSerialsOk = 0;
                    $nQtiesOk = 0;

                    BimpObject::loadClass('bimpapple', 'ConsignedStock');

                    $hasFails = false;
                    foreach ($result['parts'] as $resPart) {
                        $partNumber = BimpTools::getArrayValueFromPath($resPart, 'number', '');

                        if ($partNumber && isset($parts[$partNumber])) {
                            $status = BimpTools::getArrayValueFromPath($resPart, 'statusCode', '');

                            if ($status == 'SUBMIT_SUCCESS') {
                                $stock = ConsignedStock::getStockInstance($code_centre, $partNumber);

                                if (BimpObject::objectLoaded($stock)) {
                                    $stock->useNoTransactionsDb();

                                    if (isset($resPart['device']['identifiers'])) {
                                        $serial = '';
                                        foreach (array('serial', 'imei', 'imei2', 'meid') as $identifier) {
                                            if (isset($resPart['device']['identifiers'][$identifier])) {
                                                $serial = $resPart['device']['identifiers'][$identifier];
                                                break;
                                            }
                                        }

                                        if ($serial && isset($parts[$partNumber]['serials'])) {
                                            if (in_array($serial, $parts[$partNumber]['serials'])) {

                                                $part_warnings = array();
                                                $part_errors = $stock->correctStock(-1, $serial, 'SHIPMENT_' . $this->id, 'Renvoi #' . $this->id . ' - Commande n° ' . $orderId, $part_warnings, false, true);

                                                if (count($part_errors)) {
                                                    $warnings[] = BimpTools::getMsgFromArray($part_errors, $partNumber . ': échec du retrait du stock du n° de serie "' . $serial . '"');
                                                    $hasFails = true;
                                                } else {
                                                    $nSerialsOk++;
                                                }
                                            }
                                        }
                                    } else {
                                        $qty = (int) BimpTools::getArrayValueFromPath($parts, $partNumber . '/qty', 0);

                                        if ($qty) {
                                            $part_warnings = array();
                                            $part_errors = $stock->correctStock($qty * -1, '', 'SHIPMENT_' . $this->id, 'Renvoi #' . $this->id . ' - Commande n° ' . $orderId, $part_warnings, false, true);

                                            if (count($part_errors)) {
                                                $warnings[] = BimpTools::getMsgFromArray($part_errors, $partNumber . ': échec du retrait de ' . $qty . ' unité(s)');
                                                $hasFails = true;
                                            } else {
                                                $nQtiesOk++;
                                            }
                                        }
                                    }
                                } else {
                                    $warnings[] = $partNumber . ': aucun stock consigné enregistré pour ce composant';
                                }
                            } elseif ($status) {
                                $warnings[] = $partNumber . ': échec de la soumission' . (isset($resPart['statusDescription']) ? ' - ' . $resPart['statusDescription'] : '');
                            }
                        }
                    }

                    if ($hasFails) {
                        BimpCore::addlog('Erreurs de correction des stocks lors d\'un renvoi de pièces consignées Apple - Utiliser la fonction de vérification', Bimp_Log::BIMP_LOG_URGENT, 'stocks', $this, array(
                            'N° de commande' => $orderId
                                ), true);
                    }

                    if ($nSerialsOk > 0) {
                        $success .= '<br/>' . $nSerialsOk . ' numéro(s) de serie retiré(s) du stock avec succès';
                    }

                    if ($nQtiesOk > 0) {
                        $success .= '<br/>' . $nQtiesOk . ' mise(s) à jour du stock de composant(s) non sérialisé(s) effectuée(s) avec succès';
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionShip($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Envoi confirmé aupèrs d\'Apple avec succès';

        $carrier_code = BimpTools::getArrayValueFromPath($data, 'carrier_code', '', $errors, true, 'Veuillez sélectionner un transporteur');
        $tracking_number = BimpTools::getArrayValueFromPath($data, 'tracking_number', '', $errors, true, 'Veuillez saisir le numéro de suivi');

        $parts = $this->getData('parts');

        if (empty($parts)) {
            $errors[] = 'Aucun composant ajouté à ce retour';
        }

        $orderId = $this->getData('order_id');
        if (!$orderId) {
            $errors[] = 'N° de commande absent';
        }

        $shipTo = $this->getShipTo();
        require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
        $gsx = new GSX_v2($shipTo);

        if (!count($errors)) {
            $shipment_parts = array();

            foreach ($parts as $part_number => $part_data) {
                if (isset($part_data['serials'])) {
                    foreach ($part_data['serials'] as $serial) {
                        $shipment_parts[] = array(
                            'number' => $part_number,
                            'device' => array(
                                'id' => $serial
                            )
                        );
                    }
                } elseif (isset($part_data['qty'])) {
                    $shipment_parts[] = array(
                        'number' => $part_number,
                        'quantity'    => (int) $part_data['qty'],
                    );
                } else {
                    $errors[] = 'Quantités absentes pour le composant "' . $part_number . '"';
                }
            }

            if (!count($errors)) {
                if (!empty($shipment_parts)) {
                    $result = $gsx->consignmentOrderShipment($orderId, $carrier_code, $tracking_number, $shipment_parts);

                    if (!$result || !isset($result['parts']) || empty($result['parts'])) {
                        if (!$gsx->logged) {
                            $errors[] = $gsx->displayNoLogged();
                        } else {
                            $errors = $gsx->getErrors();

                            if (empty($errors)) {
                                $errors[] = 'Echec de la requête pour une raison inconnue';
                            }
                        }
                    } else {
                        if (isset($result['shipmentNumber'])) {
                            $this->set('shipment_number', $result['shipmentNumber']);
                        } else {
                            $warnings[] = 'N° d\'envoi non reçu';
                        }

                        $this->set('carrier_code', $carrier_code);
                        $this->set('tracking_number', $tracking_number);
                        $this->set('date_shipped', date('Y-m-d H:i:s'));
                        $this->set('status', self::STATUT_ENVOYE);

                        $up_errors = $this->update($warnings, true);
                        if (count($up_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Echec de la mise à jour des données de ce retour');
                        }
                    }
                } else {
                    $errors[] = 'Aucun composant valide à expédier';
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}
