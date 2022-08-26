<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSXRequests_v2.php';

class AppleShipmentPart extends BimpObject
{

    // Getters booléens:

    public function isDeletable($force_delete = false, &$errors = array())
    {
        if (!$force_delete) {
            $shipment = $this->getParentInstance();

            if (BimpObject::objectLoaded($shipment)) {
                if ((int) $shipment->getData('status') > 2) {
                    $errors[] = 'Le statut du retour groupé ne permet pas la suppression de ce composant';
                    return 0;
                }
            }
        }

        return 1;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'fetchReturnLabel':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }
                if (!$this->getData('return_order_number')) {
                    $errors[] = 'N° de retour absent';
                    return 0;
                }
                return 1;
        }

        return parent::isActionAllowed($action, $errors);
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        switch ($field) {
            case 'pack_number':
                $shipment = $this->getParentInstance();
                if (BimpObject::objectLoaded($shipment)) {
                    if ((int) $shipment->getData('status') > 1) {
                        return 0;
                    }
                }
                return 1;
        }
        return parent::isFieldEditable($field, $force_edit);
    }

    // Getters params: 

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isLoaded()) {
            if ($this->isActionAllowed('fetchReturnLabel') && $this->canSetAction('fetchReturnLabel')) {
                $filePath = $this->getReturnLabelFilePath();

                if ($filePath && file_exists($filePath)) {
                    $url = $this->getReturnLabelFileUrl();

                    if ($url) {
                        $onclick = 'window.open(\'' . $url . '\')';
                    }
                } else {
                    $onclick = $this->getJsActionOnclick('fetchReturnLabel');
                }
                if ($onclick) {
                    $buttons[] = array(
                        'label'   => 'Etiquette de retour',
                        'icon'    => 'fas_file-pdf',
                        'onclick' => $onclick
                    );
                }
            }
        }

        return $buttons;
    }

    // Getters données: 

    public function getReturnLabelFilePath()
    {
        if ($this->isLoaded() && $this->getData('return_order_number')) {
            $shipment = $this->getParentInstance();

            if (BimpObject::objectLoaded($shipment)) {
                if ((int) $shipment->getData('user_create')) {
                    // Nouvelle version: 
                    $fileName = 'Etiquette_' . $this->getData('part_number') . '_' . $this->getData('return_order_number') . '_colis_' . $this->getData('pack_number') . '.pdf';
                } else {
                    // Ancienne version: 
                    $fileName = 'label_' . $this->getData('return_order_number') . '_' . $this->getData('part_number') . '.pdf';
                }
                $fileName = str_replace("/", "_", $fileName);
                return $shipment->getFilesDir() . $fileName;
            }
        }

        return '';
    }

    public function getReturnLabelFileUrl()
    {
        if ($this->isLoaded() && $this->getData('return_order_number')) {
            $shipment = $this->getParentInstance();

            if (BimpObject::objectLoaded($shipment)) {
                if ((int) $shipment->getData('user_create')) {
                    // Nouvelle version: 
                    $fileName = 'Etiquette_' . $this->getData('part_number') . '_' . $this->getData('return_order_number') . '_colis_' . $this->getData('pack_number') . '.pdf';
                } else {
                    // Ancienne version: 
                    $fileName = 'label_' . $this->getData('return_order_number') . '_' . $this->getData('part_number') . '.pdf';
                }
                $fileName = str_replace("/", "_", $fileName);
                return $shipment->getFileUrl($fileName);
            }
        }

        return '';
    }

    // Affichages: 

    public function displaySav()
    {
        $pon = (string) $this->getData('part_po_number');

        if ($pon && preg_match('/^SAV.+$/', $pon)) {
            $sav = BimpCache::findBimpObjectInstance('bimpsupport', 'BS_SAV', array(
                        'ref' => $pon
            ));

            if (BimpObject::objectLoaded($sav)) {
                return $sav->getLink();
            }
        }

        return $pon;
    }

    public function displayEquipment()
    {
        $serial = (string) $this->getData('serial');

        if ($serial) {
            $eq = BimpCache::findBimpObjectInstance('bimpequipment', 'Equipment', array(
                        'serial' => array(
                            'or_field' => array(
                                $serial,
                                'S' . $serial
                            )
                        )
            ));

            if (BimpObject::objectLoaded($eq)) {
                return $eq->getLink();
            }
        }

        return $serial;
    }

    // Rendus HTML

    public function renderQuickAddFormHtml()
    {
        $shipment = $this->getParentInstance();

        if (!BimpObject::objectLoaded($shipment)) {
            return BimpRender::renderAlerts('ID de l\'expédition absent');
        }

        if ((int) $shipment->getData('status') !== 0) {
            return '';
        }

        $shipto = $shipment->getData('ship_to');
        if (!$shipto) {
            return BimpRender::renderAlerts('N° ShipTo absent pour cette expédition');
        }

        $html = '';

        if (!class_exists('GSX_v2')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';
        }

        $gsx = GSX_v2::getInstance();
        $response = false;

        if ($gsx->logged) {
            $response = $gsx->getPartsPendingReturnsForShipTo($shipto);
        }

        if (is_array($response)) {
            if (isset($response['parts']) && !empty($response['parts'])) {
                $nToAttribute = 0;
                $parts = array();
                $parts_options = array('' => '');
                $rows = array();

                BimpObject::loadClass('bimpapple', 'AppleShipment');
                foreach ($response['parts'] as $part) {
                    $part_data = json_encode(array(
                        'name'            => BimpTools::getArrayValueFromPath($part, 'partDescription', ''),
                        'serial'          => BimpTools::getArrayValueFromPath($part, 'repairDevice/identifiers/serial', ''),
                        'part_number'     => BimpTools::getArrayValueFromPath($part, 'partNumber', ''),
                        'po_number'       => BimpTools::getArrayValueFromPath($part, 'purchaseOrderNumber', ''),
                        'repair_number'   => BimpTools::getArrayValueFromPath($part, 'repairId', ''),
                        'return_number'   => BimpTools::getArrayValueFromPath($part, 'returnOrderNumber', ''),
                        'sequence_number' => BimpTools::getArrayValueFromPath($part, 'sequenceNumber', ''),
                        'return_type'     => BimpTools::getArrayValueFromPath($part, 'returnType', '')
                    ));

                    $id_part_shipment = (int) AppleShipment::getPartAppleIdShipment(BimpTools::getArrayValueFromPath($part, 'partNumber', ''), BimpTools::getArrayValueFromPath($part, 'repairId', ''), BimpTools::getArrayValueFromPath($part, 'returnOrderNumber', ''));
                    $part['id_shipment'] = $id_part_shipment;
                    $parts[] = $part;

                    if (!$id_part_shipment) {
                        $parts_options[$part['partNumber']] = array(
                            'label' => $part['partNumber'] . ' - ' . $part['partDescription'],
                            'data'  => array(
                                'part_data' => htmlentities($part_data)
                            )
                        );
                    }

                    if (isset($part['repairDevice']['identifiers'])) {
                        $ids = $part['repairDevice']['identifiers'];

                        $eq = null;
                        $eq_str = '';

                        if (isset($ids['serial'])) {
                            $eq = BimpCache::findBimpObjectInstance('bimpequipment', 'Equipment', array(
                                        'serial' => array(
                                            'or_field' => array(
                                                $ids['serial'],
                                                'S' . $ids['serial']
                                            )
                                        )
                            ));
                        }

                        if (BimpObject::objectLoaded($eq)) {
                            $eq_str = $eq->getLink();
                        } else {
                            if (isset($ids['serial']) && $ids['serial']) {
                                $eq_str .= 'n/s: ' . $ids['serial'] . '<br/>';
                            }
                            if (isset($ids['imei']) && $ids['imei']) {
                                $eq_str .= 'IMEI: ' . $ids['imei'] . '<br/>';
                            }
                            if (isset($ids['imei2']) && $ids['imei2']) {
                                $eq_str .= 'IMEI2: ' . $ids['imei2'] . '<br/>';
                            }
                            if (isset($ids['meid']) && $ids['meid']) {
                                $eq_str .= 'MEID: ' . $ids['meid'] . '<br/>';
                            }
                        }
                    }

                    $sav_str = '';

                    if (isset($part['purchaseOrderNumber'])) {
                        if (preg_match('/^SAV.+$/', $part['purchaseOrderNumber'])) {
                            $sav = BimpCache::findBimpObjectInstance('bimpsupport', 'BS_SAV', array(
                                        'ref' => $part['purchaseOrderNumber']
                            ));

                            if (BimpObject::objectLoaded($sav)) {
                                $sav_str = $sav->getLink();
                            }
                        }

                        if (!$sav_str) {
                            $sav_str = $part['purchaseOrderNumber'];
                        }
                    }

                    $return_str = '';

                    if ((int) $part['id_shipment']) {
                        if ((int) $part['id_shipment'] == $shipment->id) {
                            $return_str .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Ajouté</span>';
                        } else {
                            $part_shipment = BimpCache::getBimpObjectInstance('bimpapple', 'AppleShipment', (int) $part['id_shipment']);
                            if (BimpObject::objectLoaded($part_shipment)) {
                                $return_str = $part_shipment->getLink();
                            }
                        }
                    }

                    if (!$return_str) {
                        $nToAttribute++;
                        $return_str .= '<span class="btn btn-default add_from_parts_list" onclick="gsx_addAppleShipmentPart($(this), ' . $shipment->id . ');">';
                        $return_str .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . ' Ajouter';
                        $return_str .= '</span>';
                    }

                    $tmp = array(
                        'item_checkbox'       => ((int) $part['id_shipment'] ? false : true),
                        'row_data'            => ((int) $part['id_shipment'] ? false : array(
                    'part_data' => htmlentities($part_data)
                        )),
                        'partNumber'          => BimpTools::getArrayValueFromPath($part, 'partNumber', ''),
                        'partDescription'     => BimpTools::getArrayValueFromPath($part, 'partDescription', ''),
                        'productDescription'  => BimpTools::getArrayValueFromPath($part, 'productDescription', ''),
                        'equipement'          => $eq_str,
                        'purchaseOrderNumber' => $sav_str,
                        'returnOrderNumber'   => BimpTools::getArrayValueFromPath($part, 'returnOrderNumber', ''),
                        'repairId'            => BimpTools::getArrayValueFromPath($part, 'repairId', ''),
                        'repairStatusCode'    => BimpTools::getArrayValueFromPath($part, 'repairStatusCode', ''),
                        'expectedReturnDate'    => BimpTools::printDate(BimpTools::getArrayValueFromPath($part, 'expectedReturnDate', '')),
                        'return'              => $return_str
                    );
                    
                    $tabBatGonfle = array('QPBS5');
                    if(BimpTools::getArrayValueFromPath($part, 'dangerousGoods', '') != ''){
                        $tmp['row_style'] = 'background-color: rgba(231,166,44, .5)!important';
                        if(in_array(BimpTools::getArrayValueFromPath($part, 'issueCode', ''),$tabBatGonfle)){
                            $tmp['row_style'] = 'background-color: rgba(249,54,12, .5)!important';
                        }
                    }      
                    $rows[] = $tmp;
                    
                }
                if (count($parts_options) > 1) {
                    $inputs[] = array(
                        'label'      => 'Composant',
                        'input_name' => 'part_number',
                        'value'      => '',
                        'content'    => BimpInput::renderInput('select', 'part_number', '', array(
                            'options' => $parts_options
                        ))
                    );

                    $after_html = '<span class="btn btn-primary" onclick="gsx_addAppleShipmentPart($(this), ' . $shipment->id . ');">';
                    $after_html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . ' Ajouter';
                    $after_html .= '</span>';

                    $html .= '<div style="margin-bottom: 10px;">';
                    $html .= BimpForm::renderSingleLineForm($inputs, array(
                                'title'      => 'Ajout rapide',
                                'icon'       => 'fas_plus-circle',
                                'after_html' => $after_html
                    ));
                    $html .= '</div>';
                }

                $headers = array(
                    'partNumber'          => array(
                        'label'     => 'N° composant',
                        'align'     => 'center',
                        'col_style' => 'font-weight: bold'
                    ),
                    'partDescription'     => 'Désignation',
                    'productDescription'  => 'Produit',
                    'equipement'          => 'Equipement',
                    'purchaseOrderNumber' => 'N° commande (SAV)',
                    'returnOrderNumber'   => array('label' => 'N° de retour', 'align' => 'center'),
                    'repairId'            => array('label' => 'N° réparation', 'align' => 'center'),
                    'repairStatusCode'    => array('label' => 'Statut réparation', 'align' => 'center'),
                    'expectedReturnDate'    => array('label' => 'Date de retour attendue', 'align' => 'center'),
                    'return'              => 'Retour'
                );

                $parts_html = BimpRender::renderBimpListTable($rows, $headers, array(
                            'main_id'    => 'parts_pending_return_list',
                            'searchable' => true,
                            'sortable'   => true,
                            'checkboxes' => true,
                            'positions'  => true,
                            'search_mode'=> 'show'
                ));

                if ($nToAttribute > 0) {
                    $parts_html .= '<div class="buttonsContainer align-left">';
                    $parts_html .= '<span class="btn btn-default" onclick="gsx_addAppleShipmentSelectedParts($(this), ' . $shipment->id . ');">';
                    $parts_html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter les composants sélectionnés';
                    $parts_html .= '</span>';
                    $parts_html .= '</div>';
                }

                $title = BimpRender::renderIcon('fas_bars', 'iconLeft') . 'Liste des composants en attente de retour ';
                $title .= '<span class="badge badge-' . ($nToAttribute > 0 ? 'warning' : 'success') . '">' . $nToAttribute . ' non attribué' . ($nToAttribute > 1 ? 's' : '') . '</span>';
                $title .= (!$nToAttribute > 0 ? ' <span class="smallInfo"> Cliquer pour afficher</span>' : '');
                $html .= BimpRender::renderPanel($title, $parts_html, '', array(
                            'type'     => 'secondary',
                            'foldable' => true,
                            'open'     => ($nToAttribute > 0)
                ));
            } else {
                $html .= BimpRender::renderAlerts('Aucun composant en attente de retour', 'warning');
            }
        } elseif (!$gsx->logged) {
            $onclick = 'gsx_is_logged = false; gsx_open_login_modal($(\'\'), function() {$(\'body\').find(\'.AppleShipmentPart_list_table\').each(function() {';
            $onclick .= 'reloadObjectList($(this).attr(\'id\'));';
            $onclick .= '});});';

            $msg = 'Veuillez vous connecter à GSX pour obtenir la liste des composants en attente de retour';
            $msg .= '<div style="text-align: center; margin-top: 10px">';
            $msg .= '<span class="btn btn-default" onclick="' . $onclick . '">';
            $msg .= 'Se connecter à GSX' . BimpRender::renderIcon('fas_sign-in-alt', 'iconRight');
            $msg .= '</span>';
            $msg .= '</div>';
            $html .= BimpRender::renderAlerts($msg);
        } else {
            $html .= $gsx->displayErrors();
        }

        return $html;
    }

    // Traitements: 

    public function fetchReturnLabel()
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $shipment = $this->getParentInstance();

            if (!BimpObject::objectLoaded($shipment)) {
                $errors[] = 'ID du retour groupé absent';
            } else {
                $gsx = GSX_v2::getInstance();

                $result = false;

                if ($gsx->logged) {
                    $result = $gsx->getPartReturnLabel($shipment->getData('ship_to'), array(
                        array(
                            'returnOrderNumber' => (string) $this->getData('return_order_number'),
                            'sequenceNumber'    => (string) $this->getData('sequence_number'),
                            'repairId'          => (string) $this->getData('repair_number'),
                            'returnType'        => (string) $this->getData('return_type')
                        )
                    ));
                }

                if (!$result) {
                    if (!$gsx->logged) {
                        $errors[] = $gsx->displayNoLogged();
                    } else {
                        $errors = $gsx->getErrors();
                        if (!count($errors)) {
                            $errors[] = 'Aucun fichier reçu';
                        }
                    }
                } else {
                    $dir = $shipment->getFilesDir();

                    if (!file_exists($dir) || !is_dir($dir)) {
                        $dir_error = BimpTools::makeDirectories($dir);
                        if ($dir_error) {
                            $errors[] = 'Echec de la création du dossier - ' . $dir_error;
                        }
                    }
                    if (!count($errors)) {
                        if (!file_put_contents($this->getReturnLabelFilePath(), $result)) {
                            $errors[] = 'Echec de la création du fichier';
                        }
                    }
                }
            }
        }

        return $errors;
    }

    // Actions: 

    public function actionFetchReturnLabel($data, &$success = '')
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $cb = '';

        $filePath = $this->getReturnLabelFilePath();

        if (!file_exists($filePath)) {
            $errors = $this->fetchReturnLabel();

            if (!count($errors)) {
                if (!file_exists($filePath)) {
                    $errors[] = 'Fichier non trouvé';
                } else {
                    $cb .= 'triggerObjectChange(\'bimpcore\', \'BimpFile\', 0);';
                }
            }
        }

        if (!count($errors)) {
            $file_url = $this->getReturnLabelFileUrl();
            if ($file_url) {
                $cb .= 'window.open(\'' . $file_url . '\');';
            } else {
                $errors[] = 'Fichier non trouvé';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $cb
        );
    }

    // Overrides: 

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $errors = array();

        if (!$force_delete) {
            $shipment = $this->getParentInstance();

            if (BimpObject::objectLoaded($shipment) && (int) $shipment->getData('status') > 1) {
                if ((int) $shipment->getData('status') > 2) {
                    $errors[] = 'Ce composant ne peux pas être supprimé';
                } else {
                    $gsx = GSX_v2::getInstance();
                    if (!$gsx->logged) {
                        $errors[] = $gsx->displayNoLogged();
                    } else {
                        $response = $gsx->updateReturn($shipment->getData('ship_to'), $shipment->getData('gsx_return_id'), array(
                            array(
                                'action'            => 'DELETE',
//                                'overPackId'        => $this->getData('pack_number'),
                                'partNumber'        => $this->getData('part_number'),
                                'repairId'          => $this->getData('repair_number'),
                                'returnOrderNumber' => $this->getData('return_order_number'),
                                'sequenceNumber'    => (int) $this->getData('sequence_number')
                            )
                        ));

                        if (is_array($response)) {
                            $errors = AppleShipment::processReturnRequestOutcome($response, $warnings);
                        } else {
                            $errors = $gsx->getErrors();
                        }
                    }
                }
            }
        }

        if (!count($errors)) {
            $errors = parent::delete($warnings, $force_delete);
        }

        return $errors;
    }
}
