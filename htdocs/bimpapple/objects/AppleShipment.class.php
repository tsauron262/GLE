<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSXRequests_v2.php';

class AppleShipment extends BimpObject
{

    public static $status_list = array(
        0  => array('label' => 'Brouillon', 'icon' => 'far_file-alt', 'classes' => array('warning')),
        1  => array('label' => 'En attente d\'enregistrement GSX', 'icon' => 'fas_hourglass-start', 'classes' => array('info')),
        2  => array('label' => 'En attente de confirmation GSX', 'icon' => 'fas_hourglass-start', 'classes' => array('info')),
        3  => array('label' => 'En attente d\'envoi', 'icon' => 'fas_hourglass-start', 'classes' => array('info')),
        4  => array('label' => 'Expédié', 'icon' => 'fas_check', 'classes' => array('success')),
        -1 => array('label' => 'Annulé', 'icon' => 'fas_times', 'classes' => array('danger')),
    );
    public static $carriers = array(
        'UPSWW065' => 'UPS'
    );

    public function canSetAction($action)
    {
        switch ($action) {
//            case 'fetchFullDoc':
//                global $user;
//                return (int) $user->admin;
        }

        return parent::canSetAction($action);
    }

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('addParts', 'validate', 'cancel', 'reopen', 'createGsxReturn', 'confirmGsxReturn', 'fetchBulkReturnLabel', 'fetchFullDoc', 'fetchPackingList', 'fetchPartsReturnLabel', 'uploadUpsFile')) &&
                !$this->isLoaded($errors)) {
            return 0;
        }

        switch ($action) {
            case 'addParts':
                if ((int) $this->getData('status') !== 0) {
                    $errors[] = 'Statut invalide pour cette opération';
                    return 0;
                }
                return 1;

            case 'validate':
                if ((int) $this->getData('status') !== 0) {
                    $errors[] = 'Statut invalide pour cette opération';
                    return 0;
                }
                $parts = $this->getChildrenList('parts');
                if (empty($parts)) {
                    $errors[] = 'Aucun composant ajouté';
                    return 0;
                }
                return 1;

            case 'cancel':
                if ((int) $this->getData('status') !== 0) {
                    $errors[] = 'Statut invalide pour cette opération';
                    return 0;
                }
                return 1;

            case 'reopen':
                if (!in_array((int) $this->getData('status'), array(-1, 1))) {
                    $errors[] = 'Statut invalide pour cette opération';
                    return 0;
                }
                return 1;

            case 'createGsxReturn':
                if ((int) $this->getData('status') !== 1) {
                    $errors[] = 'Statut invalide pour cette opération';
                    return 0;
                }
                return 1;

            case 'updateGsxReturn':
            case 'confirmGsxReturn':
                if ((int) $this->getData('status') !== 2) {
                    $errors[] = 'Statut invalide pour cette opération';
                    return 0;
                }
                return 1;

            case 'fetchBulkReturnLabel':
                return 0;

            case 'fetchFullDoc':
            case 'fetchPackingList':
                if ((int) $this->getData('status') < 3) {
                    $errors[] = 'Statut invalide pour cette opération';
                    return 0;
                }

                if (!(string) $this->getData('gsx_return_id')) {
                    $errors[] = 'N° de retour GSX absent';
                    return 0;
                }
                return 1;

            case 'fetchPartsReturnLabel':
                if ((int) $this->getData('status') < 3) {
                    $errors[] = 'Statut invalide pour cette opération';
                    return 0;
                }
                return 1;

            case 'uploadUpsFile':
                if (file_exists($this->getUpsFilePath())) {
                    $errors[] = 'Le fichier ups existe déjà';
                    return 0;
                }
                return 1;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function isNewStatusAllowed($new_status, &$errors = array())
    {
        if ($new_status == 4) {
            if ($this->getData('status') != 3) {
                $errors[] = 'Ce retour n\'est pas au statut "' . self::$status_list[3]['label'] . '"';
                return 0;
            }

            return 1;
        }
        return parent::isNewStatusAllowed($new_status, $errors);
    }

    public function isDeletable($force_delete = false, &$errors = array())
    {
        $status = (int) $this->getData('status');

        if ($status > 0) {
            $errors[] = 'Ce retour ne peut pas être supprimé car il n\'est plus au statut brouillon';
            return 0;
        }

        return 1;
    }

    public function isFieldEditable($field, $force_edit = false)
    {
        $status = (int) $this->getData('status');

        if (!$force_edit && ($status < 0 || $status > 1) && in_array($field, array('carrier_code', 'gsx_note', 'length', 'width', 'height', 'weight', 'tracking_number', 'identification_number', 'gsx_tracking_url'))) {
            return 0;
        }

        return parent::isFieldEditable($field, $force_edit);
    }

    // Getters params: 

    public function getActionsButtons()
    {
        $buttons = $this->getListButtons();

        if ($this->isLoaded()) {
            // Validation: 
            if ($this->isActionAllowed('validate') && $this->canSetAction('validate')) {
                $buttons[] = array(
                    'label'   => 'Valider',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsNewStatusOnclick(1, array(), array(
                        'confirm_msg' => 'Veuillez confirmer la validation de ce retour'
                    ))
                );
            }

            // Annulation: 
            if ($this->isActionAllowed('cancel') && $this->canSetAction('cancel')) {
                $buttons[] = array(
                    'label'   => 'Annuler',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsNewStatusOnclick(-1, array(), array(
                        'confirm_msg' => 'Veuillez confirmer l\\\'annulation de ce retour'
                    ))
                );
            }

            // Réouverture: 
            if ($this->isActionAllowed('reopen') && $this->canSetAction('reopen')) {
                $buttons[] = array(
                    'label'   => ($this->getData('status') > 0 ? 'Modifier' : 'Réouvrir'),
                    'icon'    => 'fas_redo',
                    'onclick' => $this->getJsNewStatusOnclick(0, array(), array(
                        'confirm_msg' => 'Veuillez confirmer la remise en brouillon de ce retour'
                    ))
                );
            }

            // Création retour GSX: 
            if ($this->isActionAllowed('createGsxReturn') && $this->canSetAction('createGsxReturn')) {
                $buttons[] = array(
                    'label'   => 'Enregistrer sur GSX',
                    'icon'    => 'fas_sign-out-alt',
                    'onclick' => $this->getJsActionOnclick('createGsxReturn', array(
                        'confirmGsxReturn' => 0
                            ), array(
                        'form_name' => 'create_gsx_return'
                    ))
                );
            }

            // Mise à jour retour GSX: 
            if ($this->isActionAllowed('updateGsxReturn') && $this->canSetAction('updateGsxReturn')) {
                $buttons[] = array(
                    'label'   => 'Mettre à jour les infos sur GSX',
                    'icon'    => 'fas_edit',
                    'onclick' => $this->getJsActionOnclick('updateGsxReturn', array(), array(
                        'form_name' => 'update_gsx_return'
                    ))
                );
            }

            // Création et confirmation retour GSX: 
            if ($this->isActionAllowed('createGsxReturn') && $this->canSetAction('createGsxReturn')) {
                $buttons[] = array(
                    'label'   => 'Enregistrer et confirmer sur GSX',
                    'icon'    => 'fas_sign-out-alt',
                    'onclick' => $this->getJsActionOnclick('createGsxReturn', array(
                        'confirmGsxReturn' => 1
                            ), array(
                        'form_name' => 'create_gsx_return'
                    ))
                );
            }

            // Confirmation sur GSX: 
            if ($this->isActionAllowed('confirmGsxReturn') && $this->canSetAction('confirmGsxReturn')) {
                $buttons[] = array(
                    'label'   => 'Confirmer sur GSX',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsActionOnclick('confirmGsxReturn', array(), array(
                        'confirm_msg' => 'Veuillez confirmer cette opération'
                    ))
                );
            }

            // Validation expédition: 
            if ($this->isNewStatusAllowed(4)) {
                $buttons[] = array(
                    'label'   => 'Expédié',
                    'icon'    => 'fas_check',
                    'onclick' => $this->getJsNewStatusOnclick(4, array(), array(
                        'confirm_msg' => 'Veuillez confirmer'
                    ))
                );
            }

            // Fichier UPS:
            if ($this->isActionAllowed('uploadUpsFile') && $this->canSetAction('uploadUpsFile')) {
                $buttons[] = array(
                    'label'   => 'Enregistrer fichier UPS',
                    'icon'    => 'fas_file-import',
                    'onclick' => $this->getJsLoadModalForm('ups_file', 'Enregistrer fichier UPS')
                );
            }


            // Document complet: 
            if ($this->isActionAllowed('fetchFullDoc') && $this->canSetAction('fetchFullDoc')) {
                $buttons[] = array(
                    'label'   => 'Document complet',
                    'icon'    => 'fas_file-pdf',
                    'onclick' => $this->getJsActionOnclick('fetchFullDoc', array(), array())
                );
            }

            // Packing liste: 
            $onclick = '';
            $filePath = $this->getPackingListFilePath();
            if (file_exists($filePath)) {
                $fileUrl = $this->getPackingListFileUrl();
                if ($fileUrl) {
                    $onclick = 'window.open(\'' . $fileUrl . '\')';
                }
            }

            if (!$onclick && $this->isActionAllowed('fetchPackingList') && $this->canSetAction('fetchPackingList')) {
                $onclick = $this->getJsActionOnclick('fetchPackingList');
            }

            if ($onclick) {
                $buttons[] = array(
                    'label'   => 'Bordereau d\'expédition',
                    'icon'    => 'fas_file-pdf',
                    'onclick' => $onclick
                );
            }

            // Etiquette de retour: 
            $onclick = '';
            $filePath = $this->getBulkReturnLabelFilePath();
            if (file_exists($filePath)) {
                $fileUrl = $this->getBulkReturnLabelFileUrl();
                if ($fileUrl) {
                    $onclick = 'window.open(\'' . $fileUrl . '\')';
                }
            }

            if (!$onclick && $this->isActionAllowed('fetchBulkReturnLabel') && $this->canSetAction('fetchBulkReturnLabel')) {
                $onclick = $this->getJsActionOnclick('fetchBulkReturnLabel');
            }

            if ($onclick) {
                $buttons[] = array(
                    'label'   => 'Etiquette retour groupé',
                    'icon'    => 'fas_file-pdf',
                    'onclick' => $onclick
                );
            }

            // Etiquettes composants: 
            if ($this->isActionAllowed('fetchPartsReturnLabel') && $this->canSetAction('fetchPartsReturnLabel')) {
                $buttons[] = array(
                    'label'   => 'Etiquettes composants',
                    'icon'    => 'fas_file-pdf',
                    'onclick' => $this->getJsActionOnclick('fetchPartsReturnLabel', array(), array(
                        'form_name' => 'parts_labels'
                    ))
                );
            }
        }
        return $buttons;
    }

    public function getListButtons()
    {
        $buttons = array();

        $url = $this->getData('gsx_tracking_url');
        if ($url) {
            $buttons[] = array(
                'label'   => 'Page de suivi',
                'icon'    => 'fas_map-marker-alt',
                'onclick' => 'window.open(\'' . $url . '\')'
            );
        }

        return $buttons;
    }

    public function getFilesDir()
    {
        if ((int) $this->getData('user_create')) {
            // Nouvelle version
            return parent::getFilesDir();
        } else {
            // Ancienne version
            $ref = (string) $this->getData('tracking_number');
            if ($ref) {
                global $conf;
                $dir = $conf->synopsisapple->dir_output;

                if ($dir && !file_exists($dir)) {
                    if (!mkdir($dir)) {
                        return null;
                    }
                }

                $dir .= '/' . str_replace(" ", "_", $ref);

                if ($dir && !file_exists($dir)) {
                    if (!mkdir($dir)) {
                        return null;
                    }
                }

                return $dir;
            }
        }

        return null;
    }

    public function getFileUrl($file_name, $page = 'document')
    {
        if ((int) $this->getData('user_create')) {
            // Nouvelle version: 
            return parent::getFileUrl($file_name, $page);
        }

        // Ancienne version: 
        if (!$file_name) {
            return '';
        }

        if (!$this->isLoaded()) {
            return '';
        }

        $ref = (string) $this->getData('tracking_number');

        if (!$ref) {
            return '';
        }

        $file = $ref . '/' . $file_name;

        return DOL_URL_ROOT . '/' . $page . '.php?modulepart=synopsisapple&file=' . urlencode($file);
    }

    public function getBulkReturnLabelFilePath()
    {
        if ($this->isLoaded()) {
            $dir = $this->getFilesDir();
            if ($dir) {
                if (!preg_match('/^.+\/$/', $dir)) {
                    $dir .= '/';
                }
            }
            return $dir . 'Etiquette_retour_groupe_' . $this->id . '.pdf';
        }

        return '';
    }

    public function getBulkReturnLabelFileUrl()
    {
        if ($this->isLoaded()) {
            return $this->getFileUrl('Etiquette_retour_groupe_' . $this->id . '.pdf');
        }

        return '';
    }

    public function getPackingListFilePath()
    {
        if ($this->isLoaded()) {
            $dir = $this->getFilesDir();
            if ($dir) {
                if (!preg_match('/^.+\/$/', $dir)) {
                    $dir .= '/';
                }
            }
            return $dir . 'Liste_packs_retour_groupe_' . $this->id . '.pdf';
        }

        return '';
    }

    public function getPackingListFileUrl()
    {
        if ($this->isLoaded()) {
            return $this->getFileUrl('Liste_packs_retour_groupe_' . $this->id . '.pdf');
        }

        return '';
    }

    public function getUpsFilePath()
    {
        if ($this->isLoaded()) {
            $dir = $this->getFilesDir();
            if ($dir) {
                if (!preg_match('/^.+\/$/', $dir)) {
                    $dir .= '/';
                }
            }
            return $dir . 'Etiquette_ups_retour_groupe_' . $this->id . '.pdf';
        }

        return '';
    }

    public function getUpsFileUrl()
    {
        if ($this->isLoaded()) {
            return $this->getFileUrl('Etiquette_ups_retour_groupe_' . $this->id . '.pdf');
        }

        return '';
    }

    // Getters données: 

    public static function getShiptosData()
    {
        $cache_key = 'apple_shipment_shiptos_data';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();
            global $tabCentre;

            if (is_array($tabCentre)) {
                foreach ($tabCentre as $centre) {
                    if (isset($centre['7'])) {
                        self::$cache[$cache_key][$centre['4']] = array(
                            'Name'          => 'BIMP',
                            'AttentionName' => 'SAV',
                            'ShipperNumber' => 'R8X411',
                            'Address'       => array(
                                'AddressLine'       => $centre['7'],
                                'City'              => $centre['6'],
                                'StateProvinceCode' => substr($centre['5'], 0, 2),
                                'PostalCode'        => $centre['5'],
                                'CountryCode'       => 'FR',
                            ),
                            'Phone'         => array(
                                'Number' => $centre['0']
                        ));
                    }
                }
            }
        }

        return self::getCacheArray($cache_key);
    }

    public static function getPartAppleIdShipment($partNumber, $repairId, $returnOrderNumber)
    {
        $where = '(part_number = \'' . $partNumber . '\' OR part_new_number = \'' . $partNumber . '\')';
        $where .= ' AND repair_number = \'' . $repairId . '\' AND return_order_number = \'' . $returnOrderNumber . '\'';
        return (int) self::getBdb()->getValue('bimp_gsx_shipment_part', 'shipment_id', $where);
    }

    // Getters array: 

    public static function getShiptosArray($include_empty = false)
    {
        $cache_key = 'apple_shipment_shiptos_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $shiptos = self::getShiptosData();
            foreach ($shiptos as $shipto => $data) {
                self::$cache[$cache_key][$shipto] = $shipto . ': ' . $data['Name'] . ' - ' . $data['Address']['PostalCode'] . ' ' . $data['Address']['City'];
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public function getPacksNumbersArray()
    {
        if ($this->isLoaded()) {
            $cache_key = 'shipment_' . $this->id . '_packs_numbers_array';

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();

                $rows = $this->db->executeS('SELECT DISTINCT pack_number FROM ' . MAIN_DB_PREFIX . 'bimp_gsx_shipment_part WHERE shipment_id = ' . (int) $this->id, 'array');

                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][(int) $r['pack_number']] = 'Colis n° ' . $r['pack_number'];
                    }
                }
            }

            return self::getCacheArray($cache_key, true, 0, 'Tous les colis');
        }

        return array();
    }

    // Traitements:

    public function createGsxReturn(&$warnings = array())
    {
        $errors = array();

        $gsx = GSX_v2::getInstance();
        if (!$gsx->logged) {
            $errors[] = $gsx->displayNoLogged();
        } else {
            $parts = array();

            $i = 0;
            foreach ($this->getChildrenObjects('parts') as $part) {
                $parts[] = array(
                    'action'            => 'CREATE',
                    'overPackId'        => 'Individual',
                    'partNumber'        => $part->getData('part_number'),
                    'repairId'          => $part->getData('repair_number'),
                    'returnOrderNumber' => $part->getData('return_order_number'),
                    'sequenceNumber'    => (int) $part->getData('sequence_number')
                );
                $i++;
            }

            if (empty($parts)) {
                $errors[] = 'Aucun composant';
            } else {
                $details = array(
                    'carrierCode'    => (string) $this->getData('carrier_code'),
                    'trackingNumber' => (string) $this->getData('tracking_number'),
                    'notes'          => (string) $this->getData('gsx_note'),
                    'length'         => (string) $this->getData('length'),
                    'width'          => (string) $this->getData('width'),
                    'height'         => (string) $this->getData('height'),
                    'weight'         => (string) $this->getData('weight') . ' kg'
                );

                $response = $gsx->createReturn($this->getData('ship_to'), $details, $parts);
                if (is_array($response)) {
                    $errors = self::processReturnRequestOutcome($response, $warnings);

                    if (empty($errors)) {
                        if (isset($response['bulkReturn']) && (string) $response['bulkReturn']) {
                            $this->set('gsx_return_id', $response['bulkReturn']);

                            if (isset($response['trackingUrl'])) {
                                $this->set('gsx_tracking_url', $response['trackingUrl']);
                            }
                            $this->set('status', 2);

                            $up_warnings = array();
                            $up_errors = $this->update($up_warnings, true);

                            if (count($up_errors)) {
                                $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Création sur GSX effectuée avec succès mais échec de la mise à jour du retour groupé');
                            }

                            if (count($up_warnings)) {
                                $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Erreurs suite à la mise à jour du retour groupé');
                            }
                        } else {
                            $errors[] = 'N° de retour groupé non obtenu';
                        }
                    }
                } else {
                    $errors = $gsx->getErrors();
                }
            }
        }

        return $errors;
    }

    public function confirmOnGsx(&$warnings = array())
    {
        $errors = array();

        if (!$this->getData('gsx_return_id')) {
            $errors[] = 'ID du retour groupé absent';
        } else {
            $gsx = GSX_v2::getInstance();
            if (!$gsx->logged) {
                $errors[] = $gsx->displayNoLogged();
            } else {
                $response = $gsx->exec('returnsConfirmshipment', array(
                    'shipTo'     => BimpTools::addZeros(GSX_v2::$mode == 'test' ? GSX_v2::$test_ids['ship_to'] : $this->getData('ship_to'), 10),
                    'bulkReturn' => $this->getData('gsx_return_id')
                ));

                if ($response) {
                    $errors = self::processReturnRequestOutcome($response, $warnings);

                    if (empty($errors)) {
                        $this->set('status', 3);
                        $up_errors = $this->update($warnings, true);

                        if (count($up_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($up_errors, 'Confirmation GSX effectuée avec succès mais échec de la mise à jour du statut du retour groupé');
                        }
                    }
                } else {
                    $errors = $gsx->getErrors();
                }
            }
        }

        return $errors;
    }

    public static function processReturnRequestOutcome($result, &$warnings = array(), $excluded_msgs_types = array())
    {
        $errors = array();
        if (isset($result['outcome'])) {
            if (isset($result['outcome']['reasons']))
                $result['outcome'][] = $result['outcome'];

            foreach ($result['outcome'] as $outcome) {
                if (isset($outcome['reasons'])) {
                    $action = (isset($outcome['action'])) ? $outcome['action'] : null;
                    $msgs = array();
                    foreach ($outcome['reasons'] as $reason) {
                        if (isset($reason['type'])) {
                            if (in_array($reason['type'], $excluded_msgs_types)) {
                                continue;
                            }
                            $msg = $reason['type'] . '<br/>';
                            if (!isset($action))
                                $action = $reason['type'];
                        }

                        if (isset($reason['messages']) && is_array($reason['messages'])) {
                            foreach ($reason['messages'] as $message) {
                                $msg .= ' - ' . $message . '<br/>';
                            }
                        }

                        $msgs[] = $msg;
                    }
                    if (in_array($action, array('MSG', 'NOTA_FISCAL_IGNORED_WARNING'))) {
                        $warnings[] = BimpTools::getMsgFromArray($msgs, $action);
                    } else {
                        $errors[] = BimpTools::getMsgFromArray($msgs, $action);
                    }
                }
            }
        }

        return $errors;
    }

    public function onNewStatus($new_status, $current_status, $extra_data = array(), &$warnings = array())
    {
        $errors = array();
        if ($this->isLoaded($errors)) {
            switch ($new_status) {
                case -1:
                    $part_instance = BimpObject::getInstance('bimpapple', 'AppleShipmentPart');
                    $part_instance->deleteByParent((int) $this->id, $errors);
                    break;
            }
        }

        return $errors;
    }

    public function fetchBulkReturnLabel()
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            if (!(string) $this->getData('gsx_return_id')) {
                $errors[] = 'N° de retour GSX absent';
            } else {
                $gsx = GSX_v2::getInstance();

                $result = false;

                if ($gsx->logged) {
                    $result = $gsx->getBulkReturnLabel($this->getData('ship_to'), $this->getData('gsx_return_id'));
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
                    $dir = $this->getFilesDir();

                    if (!file_exists($dir) || !is_dir($dir)) {
                        $dir_error = BimpTools::makeDirectories($dir);
                        if ($dir_error) {
                            $errors[] = 'Echec de la création du dossier - ' . $dir_error;
                        }
                    }
                    if (!count($errors)) {
                        if (!file_put_contents($this->getBulkReturnLabelFilePath(), $result)) {
                            $errors[] = 'Echec de la création du fichier';
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function fetchPackingList()
    {
        $errors = array();

        if ($this->isLoaded($errors)) {

            if (!(string) $this->getData('gsx_return_id')) {
                $errors[] = 'N° de retour GSX absent';
            } else {
                $gsx = GSX_v2::getInstance();

                $result = false;

                if ($gsx->logged) {
                    $result = $gsx->getReturnPackingList($this->getData('ship_to'), $this->getData('gsx_return_id'));
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
                    $dir = $this->getFilesDir();

                    if (!file_exists($dir) || !is_dir($dir)) {
                        $dir_error = BimpTools::makeDirectories($dir);
                        if ($dir_error) {
                            $errors[] = 'Echec de la création du dossier - ' . $dir_error;
                        }
                    }
                    if (!count($errors)) {
                        if (!file_put_contents($this->getPackingListFilePath(), $result)) {
                            $errors[] = 'Echec de la création du fichier';
                        }
                    }
                }
            }
        }

        return $errors;
    }

    // Actions:

    public function actionAddParts($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $parts = BimpTools::getArrayValueFromPath($data, 'parts', array());

        if (empty($parts)) {
            $errors[] = 'Aucun composant sélectionné';
        } else {
            $nOk = 0;

            $i = 0;
            foreach ($parts as $part) {
                $i++;
                $part_errors = array();

                $part_number = BimpTools::getArrayValueFromPath($part, 'part_number', '');
                $repair_number = BimpTools::getArrayValueFromPath($part, 'repair_number', '');
                $return_number = BimpTools::getArrayValueFromPath($part, 'return_number', '');

                if (!$part_number) {
                    $part_errors[] = 'Part Number absent';
                }
                if (!$repair_number) {
                    $part_errors[] = 'N° de réparation absent';
                }
                if (!$return_number) {
                    $part_errors[] = 'N° de retour absent';
                }

                if (count($part_errors)) {
                    $warnings[] = BimpTools::getMsgFromArray($part_errors, 'Composant n°' . $i . ($part_number ? ' (' . $part_number . ')' : ''));
                } else {
                    $id_apple_shipment = self::getPartAppleIdShipment($part['part_number'], $part['repair_number'], $part['return_number']);

                    if ($id_apple_shipment) {
                        $shipment = BimpCache::getBimpObjectInstance('bimpapple', 'AppleShipment', $id_apple_shipment);
                        $warnings[] = 'Le composant "' . $part['part_number'] . '" a déjà été ajouté au retour ' . (BimpObject::objectLoaded($shipment) ? $shipment->getLink() : '#' . $id_apple_shipment);
                        continue;
                    }

                    $appleShipmentPart = BimpObject::createBimpObject('bimpapple', 'AppleShipmentPart', array(
                                'shipment_id'         => (int) $this->id,
                                'name'                => $part['name'],
                                'serial'              => $part['serial'],
                                'part_number'         => $part['part_number'],
                                'part_po_number'      => $part['po_number'],
                                'repair_number'       => $part['repair_number'],
                                'return_order_number' => $part['return_number'],
                                'return_type'         => $part['return_type'],
                                'sequence_number'     => (int) $part['sequence_number'],
                                    ), true, $errors, $warnings);

                    if (BimpObject::objectLoaded($appleShipmentPart)) {
                        $nOk++;
                    }
                }
            }

            if ($nOk) {
                $success = $nOk . ' composant' . ($nOk > 1 ? 's' : '') . ' ajouté' . ($nOk > 1 ? 's' : '') . ' avec succès';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionCreateGsxReturn($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Enregistrement du retour sur GSX effectué avec succès';

        $this->set('carrier_code', BimpTools::getArrayValueFromPath($data, 'carrier_code', $this->getData('carrier_code')));
        $this->set('length', BimpTools::getArrayValueFromPath($data, 'length', $this->getData('length')));
        $this->set('width', BimpTools::getArrayValueFromPath($data, 'width', $this->getData('width')));
        $this->set('height', BimpTools::getArrayValueFromPath($data, 'height', $this->getData('height')));
        $this->set('weight', BimpTools::getArrayValueFromPath($data, 'weight', $this->getData('weight')));
        $this->set('gsx_note', BimpTools::getArrayValueFromPath($data, 'gsx_note', $this->getData('gsx_note')));
        $this->set('tracking_number', BimpTools::getArrayValueFromPath($data, 'tracking_number', $this->getData('tracking_number')));
        $this->set('gsx_tracking_url', BimpTools::getArrayValueFromPath($data, 'gsx_tracking_url', $this->getData('gsx_tracking_url')));

        $errors = $this->update($warnings, true);

        if (!count($errors)) {
            $errors = $this->createGsxReturn($warnings);

            if (!count($errors)) {
                if ((int) BimpTools::getArrayValueFromPath($data, 'confirmGsxReturn', 0)) {
                    $confirm_errors = $this->confirmOnGsx($warnings);

                    if (count($confirm_errors)) {
                        $warnings[] = BimpTools::getMsgFromArray($confirm_errors, 'Echec de la confirmation sur GSX');
                    } else {
                        $success .= '<br/>Confirmation sur GSX effectuée avec succès';
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionUpdateGsxReturn($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Mise à jour des informations du retour sur GSX effectuée avec succès';

        $gsx = GSX_v2::getInstance();
        if (!$gsx->logged) {
            $errors[] = $gsx->displayNoLogged();
        } else {
            $this->set('carrier_code', BimpTools::getArrayValueFromPath($data, 'carrier_code', $this->getData('carrier_code')));
            $this->set('length', BimpTools::getArrayValueFromPath($data, 'length', $this->getData('length')));
            $this->set('width', BimpTools::getArrayValueFromPath($data, 'width', $this->getData('width')));
            $this->set('height', BimpTools::getArrayValueFromPath($data, 'height', $this->getData('height')));
            $this->set('weight', BimpTools::getArrayValueFromPath($data, 'weight', $this->getData('weight')));
            $this->set('gsx_note', BimpTools::getArrayValueFromPath($data, 'gsx_note', $this->getData('gsx_note')));
            $this->set('tracking_number', BimpTools::getArrayValueFromPath($data, 'tracking_number', $this->getData('tracking_number')));
            $this->set('gsx_tracking_url', BimpTools::getArrayValueFromPath($data, 'gsx_tracking_url', $this->getData('gsx_tracking_url')));

            $details = array(
                'carrierCode'    => (string) $this->getData('carrier_code'),
                'trackingNumber' => (string) $this->getData('tracking_number'),
                'notes'          => (string) $this->getData('gsx_note'),
                'length'         => (string) $this->getData('length'),
                'width'          => (string) $this->getData('width'),
                'height'         => (string) $this->getData('height'),
                'weight'         => (string) $this->getData('weight') . ' kg'
            );

            $response = $gsx->updateReturn($this->getData('ship_to'), $this->getData('gsx_return_id'), array(), $details);
            if (is_array($response)) {
                $errors = self::processReturnRequestOutcome($response, $warnings);
            } else {
                $errors = $gsx->getErrors();
            }

            if (!count($errors)) {
                $up_errors = $this->update($warnings, true);
                $warnings = BimpTools::merge_array($warnings, $up_errors);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionConfirmGsxReturn($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Confirmation sur GSX effectuée avec succès';

        $errors = $this->confirmOnGsx($warnings);

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionFetchBulkReturnLabel($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $cb = '';

        $filePath = $this->getBulkReturnLabelFilePath();

        if (!file_exists($filePath)) {
            $errors = $this->fetchBulkReturnLabel();

            if (!count($errors)) {
                if (!file_exists($filePath)) {
                    $errors[] = 'Fichier non trouvé';
                } else {
                    $cb .= 'triggerObjectChange(\'bimpcore\', \'BimpFile\', 0);';
                }
            }
        }

        if (!count($errors)) {
            $file_url = $this->getBulkReturnLabelFileUrl();
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

    public function actionFetchPackingList($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $cb = '';

        $filePath = $this->getPackingListFilePath();

        if (!file_exists($filePath)) {
            $errors = $this->fetchPackingList();

            if (!count($errors)) {
                if (!file_exists($filePath)) {
                    $errors[] = 'Fichier non trouvé';
                } else {
                    $cb .= 'triggerObjectChange(\'bimpcore\', \'BimpFile\', 0);';
                }
            }
        }

        if (!count($errors)) {
            $file_url = $this->getPackingListFileUrl();
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

    public function actionFetchPartsReturnLabel($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $cb = '';

        $num_pack = (int) BimpTools::getArrayValueFromPath($data, 'pack_number', 0);

        $filters = array();

        if ($num_pack) {
            $filters['pack_number'] = $num_pack;
        }

        $parts = $this->getChildrenObjects('parts', $filters);

        if (!count($parts)) {
            $errors[] = 'Aucun composant';
        } else {
            foreach ($parts as $part) {
                $part_errors = array();
                $filePath = $part->getReturnLabelFilePath();

                if ($filePath) {
                    if (!file_exists($filePath)) {
                        $gsx = GSX_v2::getInstance();

                        if ($gsx->logged) {
                            $part_errors = $part->fetchReturnLabel();

                            if (!count($part_errors)) {
                                if (!$cb) {
                                    $cb .= 'triggerObjectChange(\'bimpcore\', \'BimpFile\', 0);';
                                }
                            }
                        }

                        if (!$gsx->logged) {
                            $errors[] = $gsx->displayNoLogged();
                            break;
                        }
                    }
                } else {
                    $part_errors[] = 'N° de retour absent';
                }

                if (count($part_errors)) {
                    $errors[] = BimpTools::getMsgFromArray($errors, 'Composant #' . $part->id . ' - ' . $part->getData('part_number'), true);
                }
            }

            if (!count($errors)) {
                $cb .= 'window.open(\'' . DOL_URL_ROOT . '/bimpapple/parts_labels.php?id=' . $this->id . ($num_pack ? '&pack_number=' . $num_pack : '') . '\');';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $cb
        );
    }

    public function actionFetchFullDoc($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $cb = '';

        $parts = $this->getChildrenObjects('parts');

        if (!count($parts)) {
            $errors[] = 'Aucun composant';
        } else {
            $gsx = GSX_v2::getInstance();

            if ($gsx->logged) {
                // Etiquette retour: 
//                $filePath = $this->getBulkReturnLabelFilePath();
//
//                if (!file_exists($filePath) && $gsx->logged) {
//                    $file_errors = $this->fetchBulkReturnLabel();
//                    if (count($file_errors)) {
//                        $errors[] = BimpTools::getMsgFromArray($file_errors, 'Echec obtention de l\'étiquette de retour');
//                    }
//                }
                // Packing List:
                $filePath = $this->getPackingListFilePath();

                if (!file_exists($filePath) && $gsx->logged) {
                    $file_errors = $this->fetchPackingList();
                    if (count($file_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($file_errors, 'Echec obtention du bordereau d\'expédition');
                    }
                }

                // Etiquettes parts: 
                foreach ($parts as $part) {
                    if (!$gsx->logged) {
                        break;
                    }

                    $part_errors = array();
                    $filePath = $part->getReturnLabelFilePath();

                    if ($filePath) {
                        if (!file_exists($filePath)) {
                            if ($gsx->logged) {
                                $part_errors = $part->fetchReturnLabel();

                                if (!count($part_errors)) {
                                    if (!$cb) {
                                        $cb .= 'triggerObjectChange(\'bimpcore\', \'BimpFile\', 0);';
                                    }
                                }
                            }
                        }
                    } else {
                        $part_errors[] = 'N° de retour absent';
                    }

                    if (count($part_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($errors, 'Composant #' . $part->id . ' - ' . $part->getData('part_number'), true);
                    }
                }
            }

            if (!$gsx->logged) {
                $errors = array($gsx->displayNoLogged());
            }

            if (!count($errors)) {
                $cb .= 'window.open(\'' . DOL_URL_ROOT . '/bimpapple/full_doc.php?id=' . $this->id . '\');';
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $cb
        );
    }

    public function actionUploadUpsFile($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Fichier enregistré avec succès';

        if (!isset($_FILES['file'])) {
            $errors[] = 'Aucun fichier sélectionné';
        } else {
            $file = BimpObject::getInstance('bimpcore', 'BimpFile');

            $errors = $file->validateArray(array(
                'parent_module'      => $this->module,
                'parent_object_name' => $this->object_name,
                'id_parent'          => $this->id,
                'file_name'          => 'Etiquette_ups_retour_groupe_' . $this->id,
                'file_ext'           => 'pdf',
                'visibility'         => 2
            ));

            if (!count($errors)) {
                $errors = $file->create($warnings, true);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides:

    public function update(&$warnings = array(), $force_update = false)
    {
        if ((int) BimpTools::getPostFieldValue('ups_file_upload', 0)) {
            $result = $this->setObjectAction('uploadUpsFile');
            $warnings = $result['warnings'];
            return $result['errors'];
        }

        return parent::update($warnings, $force_update);
    }
}
