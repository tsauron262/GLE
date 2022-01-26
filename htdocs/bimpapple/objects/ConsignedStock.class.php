<?php

class ConsignedStock extends BimpObject
{

    // Droits user: 

    public function canSetAction($action)
    {
        switch ($action) {
            case 'send':
                return 0;

            case 'receive':
                return 1;
        }

        return parent::canSetAction($action);
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

//        if ($this->canSetAction('send')) {
//            $buttons[] = array(
//                'label'   => 'Envoi stock consigné',
//                'icon'    => 'fas_sign-out-alt',
//                'onclick' => $this->getJsActionOnclick('send', array(), array(
//                    'form_name' => 'send'
//                ))
//            );
//        }

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

                    $result = $gsx->consignmentOrdersLookup('INCREASE', 'OPEN');

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

                                foreach ($result as $order) {
                                    if (isset($order['deliveries'])) {
                                        foreach ($order['deliveries'] as $delivery) {
                                            $total = 0;
                                            $qty = 0;
                                            $dt = new DateTime($delivery['deliveredDate']);

                                            if (isset($delivery['parts'])) {
                                                foreach ($delivery['parts'] as $part) {
                                                    $total += (int) $part['quantityDelivered'];
                                                    $qty += ((int) $part['quantityDelivered'] - (int) $part['quantityAcknowledged']);
                                                }
                                            }

                                            $deliveries[$delivery['number']] = 'N° ' . $delivery['number'] . ' - ' . $dt->format('d / m / Y') . ' - ' . $qty . ' unité(s) à réceptionner sur ' . $total;
                                        }
                                    }
                                }

                                $html .= BimpInput::renderInput('select', 'deliveryNumber', '', array(
                                            'options' => $deliveries
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
                            if ($qty_to_receive) {
                                if ((bool) $part['serialized']) {
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
                    } else {
//                        $html .= '<pre>';
//                        $html .= print_r($result, 1);
//                        $html .= '</pre>';
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
                                $html .= '<pre>';
                                $html .= print_r($result, 1);
                                $html .= '</pre>';
                                return $html;

//                                $deliveries = array();

//                                foreach ($result as $order) {
//                                    if (isset($order['deliveries'])) {
//                                        foreach ($order['deliveries'] as $delivery) {
//                                            $total = 0;
//                                            $qty = 0;
//                                            $dt = new DateTime($delivery['deliveredDate']);
//
//                                            if (isset($delivery['parts'])) {
//                                                foreach ($delivery['parts'] as $part) {
//                                                    $total += (int) $part['quantityDelivered'];
//                                                    $qty += ((int) $part['quantityDelivered'] - (int) $part['quantityAcknowledged']);
//                                                }
//                                            }
//
//                                            $deliveries[$delivery['number']] = 'N° ' . $delivery['number'] . ' - ' . $dt->format('d / m / Y') . ' - ' . $qty . ' unité(s) à réceptionner sur ' . $total;
//                                        }
//                                    }
//                                }
//
//                                $html .= BimpInput::renderInput('select', 'deliveryNumber', '', array(
//                                            'options' => $deliveries
//                                ));
//
//                                return $html;
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

    // Actions: 

    public function actionReceive($data, &$success)
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

            if (!empty($data_parts)) {
                foreach ($data_parts as $part) {
                    if (isset($part['qty']) && (int) $part['qty'] > 0) {
                        $parts[] = array(
                            'number'   => $part['part_number'],
                            'quantity' => (int) $part['qty']
                        );
                    } elseif (isset($part['serials']) && is_array($part['serials']) && !empty($part['serials'])) {
                        foreach ($part['serials'] as $serial) {
                            $parts[] = array(
                                'number' => $part['part_number'],
                                'device' => array(
                                    'id' => $serial
                                )
                            );
                        }
                    }
                }

                if (empty($parts)) {
                    $errors[] = 'Aucun utité à réceptionner saisie ou sélectionnée';
                } else {
                    // Requête GSX: 
//                    if (BimpCore::isModeDev()) {
//                        $result = array();
//                    } else {
                        require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

                        $gsx = new GSX_v2($shipTo);

                        $result = $gsx->consignmentDeliveryAcknowledge($deliveryNumber, $parts);
                        if (!$gsx->logged) {
                            $errors[] = $gsx->displayNoLogged();
                        } else {
                            $errors = $gsx->getErrors();
                        }
//                    }

                    if (!count($errors)) {
                        $success .= 'Enregistrement des unités reçues sur GSX effectuée avec succès';

                        // Enregistrement parts: 
                        $nOk = 0;
                        $nFails = 0;
                        foreach ($parts as $part) {
                            $cs = BimpCache::findBimpObjectInstance('bimpapple', 'ConsignedStock', array(
                                        'part_number' => $part['number'],
                                        'code_centre' => $code_centre
                                            ), true, false);

                            if (BimpObject::objectLoaded($cs)) {
                                // Mise à jour: 
                                if (isset($part['quantity'])) {
                                    $new_qty = (int) $cs->getData('qty') + (int) $part['quantity'];
                                    $cs->set('qty', $new_qty);

                                    $up_warnings = array();
                                    $up_errors = $cs->update($up_warnings, true);

                                    if (count($up_errors)) {
                                        $warnings[] = BimpTools::getMsgFromArray($up_errors, $part['number'] . ' - Echec mise à jour des quantités');

                                        BimpCore::addlog('Echec mise à jour stock consigné Apple - A corriger manuellement impérativement', Bimp_Log::BIMP_LOG_ERREUR, 'stocks', $cs, array(
                                            'Part Number'        => $part['number'],
                                            'Code Centre'        => $code_centre,
                                            'Nouvelle Quantités' => $new_qty
                                                ), true);
                                        $nFails++;
                                    } else {
                                        $nOk++;
                                    }
                                } elseif (isset($part['device']['id'])) {
                                    $serials = $cs->getData('serials');

                                    if (!in_array($part['device']['id'], $serials)) {
                                        $serials[] = $part['device']['id'];
                                        $cs->set('serials', $serials);
                                        $cs->set('qty', (int) $cs->getData('qty') + 1);

                                        $up_warnings = array();
                                        $up_errors = $cs->update($up_warnings, true);

                                        if (count($up_errors)) {
                                            $warnings[] = BimpTools::getMsgFromArray($up_errors, $part['number'] . ' - Echec mise ajout du n° de série "' . $part['device']['id'] . '"');

                                            BimpCore::addlog('Echec ajout serial stock consigné Apple - A corriger manuellement impérativement', Bimp_Log::BIMP_LOG_ERREUR, 'stocks', $cs, array(
                                                'Part Number'    => $part['number'],
                                                'Code Centre'    => $code_centre,
                                                'Nouveau serial' => $part['device']['id']
                                                    ), true);
                                            $nFails++;
                                        } else {
                                            $nOk++;
                                        }
                                    } else {
                                        $warnings[] = 'N° de série "' . $part['device']['id'] . '" déjà ajouté pour le composant "' . $part['number'] . '"';
                                    }
                                }
                            } else {
                                $cs_data = array(
                                    'part_number' => $part['number'],
                                    'code_centre' => $code_centre
                                );

                                if (isset($part['quantity'])) {
                                    $cs_data['qty'] = (int) $part['quantity'];
                                } elseif (isset($part['device']['id'])) {
                                    $cs_data['serials'] = array($part['device']['id']);
                                    $cs_data['qty'] = 1;
                                } else {
                                    continue;
                                }

                                $create_errors = array();
                                $cs = BimpObject::createBimpObject('bimpapple', 'ConsignedStock', $cs_data, true, $create_errors);

                                if (count($create_errors)) {
                                    $warnings[] = BimpTools::getMsgFromArray($create_errors, $part['number'] . ' - Echec ajout du stock');

                                    BimpCore::addlog('Echec création stock consigné Apple - A ajouter manuellement impérativement', Bimp_Log::BIMP_LOG_ERREUR, 'stocks', null, array(
                                        'Données' => $cs_data
                                            ), true);
                                    $nFails++;
                                } else {
                                    $nOk++;
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
}
