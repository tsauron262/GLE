<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX_v2.php';

class GSX_Repair extends BimpObject
{

    public $partsPending = array();
    public $repairLookUp = array();
    public $lookupErrors = array();
    public $isIphone = false;
    public $totalFromOrderChanged = false;
    public $gsx = null;
    public $gsx_v2 = null;
    public $use_gsx_v2 = false;
    public $findInGsx = false;
    public static $lookupNumbers = array(
        'serialNumber' => 'Numéro de série',
        'repairNumber' => 'Numéro de réparation (repair #)',
//        'repairConfirmationNumber' => 'Numéro de confirmation (Dispatch ID)',
//        'purchaseOrderNumber'      => 'Numéro de bon de commande (Purchase Order)',
        'imeiNumber'   => 'Numéro IMEI (IPhone)'
    );
    public static $repairStatusCodes = array(
        'RFPU' => 'Ready For Pick Up',
        'AWTP' => 'Awaiting Parts',
        'AWTR' => 'Parts allocated',
        'BEGR' => 'In Repair',
        'SPCM' => 'Réparation marquée complète'
    );
    public static $repairTypes = array(
        'carry_in', 'repair_or_replace'
    );
    public static $readyForPickupCodes = array('RFPU');
    public static $cancelCodes = array('GX02', 'GX08', 'SCNC', 'CCAR', 'CCCR', 'CCNR');
    public static $closeCodes = array('SACM', 'SCOM', 'CFPH', 'CRCN', 'CRCP', 'CUNR', 'CRDE', 'SPCM');

    public function __construct($module, $object_name)
    {
        $this->use_gsx_v2 = BimpCore::getConf('use_gsx_v2');

        parent::__construct($module, $object_name);
    }

    public function initGsx(&$errors = array(), $force = false)
    {
        if ($this->use_gsx_v2) {
            if (is_null($this->gsx_v2) || !$this->gsx_v2->logged) {
                $this->gsx_v2 = GSX_v2::getInstance($force);
            }
            $this->gsx_v2->resetErrors();
            if (!$this->gsx_v2->logged) {
                $errors[] = 'Non authentifié sur GSX : ' . $this->gsx_v2->appleId;
                return false;
            }
        } else {
            if (is_null($this->gsx) || $this->isIphone != $this->gsx->isIphone) {
                $this->gsx = new GSX($this->isIphone);
            }

            if (!$this->gsx->connect) {
                $errors[] = 'Echec de la connexion à GSX (Version 1)';
                return false;
            }
        }

        return true;
    }

    public function setGSX($gsx)
    {
        if (is_a($gsx, 'GSX')) {
            $this->gsx = $gsx;
        } elseif (is_a($gsx, 'GSX_v2')) {
            $this->gsx_v2 = $gsx;
            if ($this->isLoaded()) {
                $this->lookup();
            }
        }
    }

    public function setSerial($serial)
    {
        if (preg_match('/^[0-9]{15,16}$/', $serial))
            $this->isIphone = true;
        else
            $this->isIphone = false;

        $this->set('serial', $serial);

        if ($this->isLoaded() && $serial !== $this->getInitData('serial')) {
            $this->update();
        }
    }

    public function is_v1()
    {
        return !$this->use_gsx_v2;
    }

    public function isActionAllowed($action, &$errors = array())
    {
        switch ($action) {
            case 'reviewPropal':
                if (!$this->isLoaded($errors)) {
                    return 0;
                }

                $sav = $this->getChildObject('sav');
                if (BimpObject::objectLoaded($sav)) {
                    $propal = $sav->getChildObject('propal');
                    if (BimpObject::objectLoaded($propal)) {
                        $propal_status = (int) $propal->getData('fk_statut');
                        if ($propal_status) {
                            if (!$sav->isActionAllowed('reviewPropal', $errors)) {
                                return 0;
                            }
                        }
                        return 1;
                    }
                }

                $errors[] = 'Devis non trouvé pour cette réparation';
                return 0;
        }
        return parent::isActionAllowed($action, $errors);
    }

    public function getRef($withGeneric = true)
    {
        return $this->getData('repair_number');
    }

    // Méthodes V2: 

    public static function processRepairRequestOutcome($result, &$warnings = array(), $excluded_msgs_types = array())
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

                        //Patch pour faire une erreur des probléme de localisatioin
                        if (stripos($msg, "La fonctionnalité Localiser mon appareil est activée") !== false) {
                            $action = "ERROR";
                            $msg .= "<script>alert('La fonctionnalité Localiser mon appareil est activée');</script>";
                        }

                        if ($reason['type'] === 'REPAIR_TYPE' && isset($reason['repairOptions'])) {
                            $msg .= 'Types de réparation éligibles: <br/>';
                            if (empty($reason['repairOptions'])) {
                                $msg .= 'Aucun';
                            } else {
                                foreach ($reason['repairOptions'] as $option) {
                                    $msg .= $option['priority'] . ': ' . (isset(GSX_Const::$repair_types[$option['option']]) ? GSX_Const::$repair_types[$option['option']] : $option['option']);
                                    if (isset($option['subOption'])) {
                                        $msg .= ' (type de service: ' . (isset(GSX_Const::$service_types[$option['subOption']]) ? GSX_Const::$service_types[$option['subOption']] : $option['subOption']) . ')';
                                    }
                                    $msg .= '<br/>';
                                }
                            }
                        }
                        $msgs[] = $msg;
                    }
                    if (in_array($action, array('WARNING', 'MESSAGE', 'HOLD', 'REPAIR_TYPE'))) {
                        $warnings[] = BimpTools::getMsgFromArray($msgs, $action);
                    } else {
                        $errors[] = BimpTools::getMsgFromArray($msgs, $action);
                    }
                }
            }
        }

        return $errors;
    }

    public function getActionsButtons()
    {
        $buttons = array();

        if ($this->isLoaded() && $this->use_gsx_v2) {
            if (!(int) $this->findInGsx && !(int) $this->getData('canceled')) {
                $confirm = 'Attention, la réparation va être considérée comme annulée. Veuillez confirmer';
                $onclick = $this->getJsActionOnclick('cancelRepair');
                $buttons[] = array(
                    'label'   => 'Classé Annulée',
                    'icon'    => 'fas_check',
                    'type'    => 'danger',
                    'onclick' => $onclick
                );
            } elseif (!(int) $this->getData('ready_for_pick_up') && !(int) $this->getData('canceled')) {
                $confirm = 'Attention, la réparation va être marquée &quote;Ready For Pick up&quote; (prête pour enlèvement) auprès du service GSX d\\\'Apple. Veuillez confirmer';
                $onclick = $this->getJsGsxAjaxOnClick('gsxRepairAction', array(
                    'id_repair' => (int) $this->id,
                    'action'    => 'readyForPickup'
                        ), array(
                    'confirm_msg' => $confirm
                        ), '$(\'#repair_' . $this->id . '_result\')');
                $buttons[] = array(
                    'label'   => 'Prête pour enlèvement',
                    'icon'    => 'fas_check',
                    'type'    => 'danger',
                    'onclick' => $onclick
                );
            } elseif (!(int) $this->getData('repair_complete') && !(int) $this->getData('canceled')) {
                $confirm = 'Attention, la réparation va être indiquée comme complète auprès du service GSX d\\\'Apple. Veuillez confirmer';
                $onclick = $this->getJsGsxAjaxOnClick('gsxRepairAction', array(
                    'id_repair' => (int) $this->id,
                    'action'    => 'closeRepair'
                        ), array(
                    'confirm_msg' => $confirm
                        ), '$(\'#repair_' . $this->id . '_result\')');
                $buttons[] = array(
                    'label'   => 'Restituer',
                    'icon'    => 'fas_undo',
                    'type'    => 'danger',
                    'onclick' => $onclick
                );
            } elseif (!(int) $this->getData('reimbursed') && !(int) $this->getData('canceled')) {
                $confirm = 'Veuillez confirmer';
                $onclick = '';
                $buttons[] = array(
                    'label'   => 'Marquer comme remboursée',
                    'icon'    => 'fas_check',
                    'onclick' => $onclick
                );
            }
        }

        return $buttons;
    }

    public function updatePartNumber($part_number, $kgb_number, $kbb_number, $sequence_number = 0, &$warnings = array())
    {
        $errors = array();

        if (!$this->isLoaded($errors)) {
            return $errors;
        }

        $this->initGsx($errors);

        if (empty($errors)) {
            $params = array(
                'repairId' => $this->getData('repair_number'),
                'parts'    => array(
                    array(
                        'number'          => $part_number,
                        'sequenceNumber'  => (int) $sequence_number,
                        'kgbDeviceDetail' => array(
                            'id' => $kgb_number
                        ),
//                        'kbbDeviceDetail' => array(
//                            'id' => $kbb_number
//                        )
                    )
                )
            );

            if ($kbb_number) {
                $params['parts'][0]['kbbDeviceDetail'] = array(
                    'id' => $kbb_number
                );
            }

            $result = $this->gsx_v2->exec('repairUpdate', $params);

            if (!is_array($result) || empty($result)) {
                $errors = $this->gsx_v2->getErrors();
            } else {
                $sav = $this->getChildObject('sav');

                if (BimpObject::objectLoaded($sav)) {
                    $serial = $sav->getSerial();
                    if ($serial == $kbb_number || !$kbb_number) {
                        $this->updateField('new_serial', $kgb_number);

                        $equipment = $sav->getChildObject('equipment');
                        if (BimpObject::objectLoaded($equipment)) {
                            $note = $equipment->changeSerial($kgb_number);
                            $sav->updateField('resolution', $sav->getData('resolution')."\n".'Ancien : '.$note);
                        }
                    }
                }

                $errors = $this->processRepairRequestOutcome($result, $warnings, array('REPAIR_TYPE'));
            }
        }

        return $errors;
    }

    public function getPartReturnLabelFileUrl($part, &$errors = array())
    {
        $fileName = '';
        $filePath = '';

        if ($this->isLoaded($errors)) {
            if (isset($part['returnOrderNumber']) && $part['returnOrderNumber'] && isset($part['number']) && $part['number']) {
                $sav = $this->getChildObject('sav');
                if (BimpObject::objectLoaded($sav)) {
                    $dir = '/bimpcore/sav/' . $sav->id . '';
                    $fileName = 'label_' . $part['number'] . '_' . $part['returnOrderNumber'] . '.pdf';
                    $filePath = $dir . '/' . $fileName;
                    $fileUrl = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . 'sav/' . $sav->id . '/' . $fileName;

                    if (!file_exists(DOL_DATA_ROOT . $filePath)) {
                        if (!is_dir(DOL_DATA_ROOT . $dir)) {
                            BimpTools::makeDirectories('bimpcore/sav/' . $sav->id, DOL_DATA_ROOT);
                        }

                        $serial = $sav->getSerial();
                        if ($serial) {
                            $this->setSerial($serial);
                        }

                        if ($this->isIphone) {
                            $client = 'IPhoneReturnLabel';
                        } else {
                            $client = 'ReturnLabel';
                        }
                        $requestName = $client . 'Request';

                        $use_gsx_v2 = $this->use_gsx_v2;
                        $this->use_gsx_v2 = 0;

                        if (is_null($this->gsx)) {
                            $this->initGsx($errors);
                        }

                        if (!count($errors)) {
                            $request = $this->gsx->_requestBuilder($requestName, '', array(
                                'returnOrderNumber' => $part['returnOrderNumber'],
                                'partNumber'        => $part['number']
                            ));

                            $labelResponse = $this->gsx->request($request, $client);
                            if (isset($labelResponse[$client . 'Response']['returnLabelData']['returnLabelFileName'])) {
                                if (!file_put_contents(DOL_DATA_ROOT . $filePath, $labelResponse[$client . 'Response']['returnLabelData']['returnLabelFileData'])) {
                                    $errors[] = 'Echec de la récupération de l\'étiquette de retour pour le composant "' . $part['number'] . '"';
                                }
                            } else {
                                $errors = $this->gsx->errors['soap'];
                            }
                        }
                        $this->use_gsx_v2 = $use_gsx_v2;
                    }
                }
            }
        }

        if ($filePath && file_exists(DOL_DATA_ROOT . $filePath)) {
            return $fileUrl;
        }

        return '';
    }

    // Méthodes V1 / V2: 

    public function load()
    {
        $filters = array();

        $fields = array('id_sav', 'serial', 'repair_number', 'repair_confirm_number');

        foreach ($fields as $field_name) {
            $value = $this->getData($field_name);
            if (!is_null($value) && $value) {
                $filters[$field_name] = $value;
            }
        }

        if (!count($filters)) {
            return array('Impossible de charger les données de la réparation (aucun identifiant disponible)');
        }

        if (!$this->find($filters, false)) {
            return array('Réparation non trouvée');
        }

        return array();
    }

    public function lookup($number = null, $number_type = null)
    {
        if ($this->use_gsx_v2) {
            $errors = array();
            $repId = $this->getData('repair_number');

            if ($repId) {
                $this->initGsx($errors);
                if (!count($errors)) {
                    $data = $this->gsx_v2->repairDetails($repId);
                    if (is_array($data) && !empty($data)) {
                        $this->findInGsx = true;
                        $this->repairLookUp = $data;

                        // Check type: 
                        if (!(string) $this->getData('repair_type') && isset($this->repairLookUp['repairTypeCode']) && (string) $this->repairLookUp['repairTypeCode']) {
                            $this->updateField('repair_type', $this->repairLookUp['repairTypeCode']);
                        }

                        // Check prix:
                        if (!(float) $this->getData('total_from_order') && isset($this->repairLookUp['price']['totalAmount']) && (string) $this->repairLookUp['price']['totalAmount']) {
                            $this->updateField('total_from_order', (float) $this->repairLookUp['price']['totalAmount']);
                        }

                        // Check du statut: 
                        if (isset($this->repairLookUp['repairStatusCode']) && ($this->repairLookUp['repairStatusCode'] != '')) {
                            $ready_for_pick_up = 0;
                            $complete = 0;
                            $cancelled = 0;

                            if (in_array($this->repairLookUp['repairStatusCode'], self::$cancelCodes)) {
                                $cancelled = 1;
                                $ready_for_pick_up = 1;
                            } elseif (in_array($this->repairLookUp['repairStatusCode'], self::$closeCodes)) {
                                $complete = 1;
                                $ready_for_pick_up = 1;
                            } elseif (in_array($this->repairLookUp['repairStatusCode'], self::$readyForPickupCodes)) {
                                $ready_for_pick_up = 1;
                            }

                            if ($ready_for_pick_up !== (int) $this->getInitData('ready_for_pick_up')) {
                                $this->updateField('ready_for_pick_up', $ready_for_pick_up);
                            }
                            if ($cancelled !== (int) $this->getInitData('canceled')) {
                                $this->updateField('canceled', $cancelled);
                            }
                            if ($complete !== (int) $this->getInitData('repair_complete')) {
                                $this->updateField('repair_complete', $complete);
                            }
                        }
                    } else {
                        $errors = $this->gsx_v2->getErrors();
                    }
                }
            } else {
                $errors[] = 'Numéro de réparation absent';
            }
            $this->lookupErrors = $errors;
            return $errors;
        } else {
            if (is_null($this->gsx) || $this->isIphone != $this->gsx->isIphone) {
                $this->gsx = new GSX($this->isIphone);
            }

            if (!$this->gsx->connect) {
                return array('Echec de la connexion à GSX (4)' . print_r($this->gsx->errors, 1));
            }

            $n_soap_errors = count($this->gsx->errors['soap']);

            $look_up_data = array(
                'repairConfirmationNumber' => '',
                'customerEmailAddress'     => '',
                'customerFirstName'        => '',
                'customerLastName'         => '',
                'fromDate'                 => '',
                'toDate'                   => '',
                'incompleteRepair'         => '',
                'pendingShipment'          => '',
                'purchaseOrderNumber'      => '',
                'repairNumber'             => '',
                'repairStatus'             => '',
                'repairType'               => '',
                'serialNumber'             => '',
                'shipToCode'               => '',
                'soldToReferenceNumber'    => '',
                'technicianFirstName'      => '',
                'technicianLastName'       => '',
                'unreceivedModules'        => '',
            );

            if (isset($number) && $number && isset($number_type) && $number_type && isset($look_up_data[$number_type])) {
                $look_up_data[$number_type] = $number;
            } else {
                $repairNumber = $this->getData('repair_number');
                $repairConfirmNumber = $this->getData('repair_confirm_number');
                if (!is_null($repairConfirmNumber) && $repairConfirmNumber) {
                    $look_up_data['repairConfirmationNumber'] = $repairConfirmNumber;
                } elseif (!is_null($repairNumber) && $repairNumber) {
                    $look_up_data['repairNumber'] = $repairNumber;
                } else
                    return array("Aucune info pour le repairLookup " . print_r($look_up_data, 1));
            }

            if ($this->isIphone) {
                $look_up_data['imeiNumber'] = '';
            }


            $client = '';
            $requestName = '';
            if ($this->isIphone) {
                $client = 'IPhoneRepairLookup';
                $requestName = 'IPhoneRepairLookupRequest';
            } else {
                $client = 'RepairLookup';
                $requestName = 'RepairLookupRequest';
            }
            $request = $this->gsx->_requestBuilder($requestName, 'lookupRequestData', $look_up_data);
            $response = $this->gsx->request($request, $client);

            $canceled = $this->getData('canceled');

            if (count($this->gsx->errors['soap']) > $n_soap_errors) {
                if (!$canceled && stripos($this->gsx->errors['soap'][$n_soap_errors], "SOAP Error:  (Code: RPR.LKP.01)") !== false) {
                    $this->set('canceled', 1);
                    $this->update();
                }
                return $this->gsx->errors['soap'];
            } else if (!isset($response[$client . 'Response']['lookupResponseData'])) {
                return array('Echec de la requête "lookup" pour une raison inconnue');
            }
            if ($canceled) {
                $this->set('canceled', 0);
                $update = true;
            }

            $this->repairLookUp = $response[$client . 'Response']['lookupResponseData']['repairLookup'];

            $update = false;



            if (is_array($this->repairLookUp) && !isset($this->repairLookUp['repairConfirmationNumber'])) {
                $this->repairLookUp = $this->repairLookUp[0];
            }

            if (isset($this->repairLookUp['repairNumber']) && ($this->repairLookUp['repairNumber'] != '')) {
                $repairNumber = $this->getData('repair_number');
                if (!is_null($repairNumber) || ($repairNumber != $this->repairLookUp['repairNumber'])) {
                    $this->set('repair_number', $this->repairLookUp['repairNumber']);
                    $update = true;
                }
            }

            if (isset($this->repairLookUp['repairConfirmationNumber']) && ($this->repairLookUp['repairConfirmationNumber'] != '')) {
                $repairConirmNumber = $this->getData('repair_confirm_number');
                if (is_null($repairConirmNumber) || ($repairConirmNumber != $this->repairLookUp['repairConfirmationNumber'])) {
                    $this->set('repair_confirm_number', $this->repairLookUp['repairConfirmationNumber']);
                    $update = true;
                }
            }

            if (isset($this->repairLookUp['repairStatus']) && ($this->repairLookUp['repairStatus'] != '')) {
                $repairComplete = 0;
                $ready_for_pick_up = 0;


                if ($this->repairLookUp['repairStatus'] == "Prêt pour enlèvement") {
                    $ready_for_pick_up = 1;
                }

                if (in_array($this->repairLookUp['repairStatus'], array(
                            'Closed',
                            'Fermée et complétée',
                            'Fermé et terminé',
                            'Réparation marquée comme complète',
                            'Refusée - réparation annulée',
                            'Refusé - Refusé par Apple',
                            'Fermée et complétée par le système',
                            'Nouveau devis refusé'
                        ))) {
                    $repairComplete = 1;
                    $ready_for_pick_up = 1;
                }
                if ((int) $this->getData('repair_complete') !== $repairComplete) {
                    $this->set('repair_complete', $repairComplete);
                    $update = true;
                }

                if ((int) $this->getData('ready_for_pick_up') !== $ready_for_pick_up) {
                    $this->set('ready_for_pick_up', $ready_for_pick_up);
                    $update = true;
                }
            }

            if (isset($this->repairLookUp['repairType'])) {
                $repair_type = 'repair_or_replace';
                if ($this->repairLookUp['repairType'] === 'CA' || $this->repairLookUp['repairType'] === 'Carry-in') {
                    $repair_type = 'carry_in';
                }
                if ($this->getData('repair_type') !== $repair_type) {
                    $this->set('repair_type', $repair_type);
                    $update = true;
                }
            }

//        $total_from_order = $this->getData('total_from_order');
            if ($this->isLoaded()) {
                $this->repairDetails($update);
            }

            if ($update && $this->isLoaded()) {
                $this->update();
            }

            return array();
        }
    }

    public function updateStatus($status = 'RFPU', &$warnings = array())
    {
        $errors = array();

        if (!$this->isLoaded()) {
            $errors = $this->load();
            if (count($errors)) {
                return $errors;
            }
        }

        if ((int) $this->getData('repair_complete')) {
            return array('Cette réparation a déjà été fermée.');
        }

        switch ($status) {
            case 'RFPU':
                if ((int) $this->getData('ready_for_pick_up')) {
                    BimpTools::mailGrouper('tommy@bimp.fr, jc.cannet@bimp.fr', null, 'Passage a RFPU deja RFPU<br/>Attention SAV : ' . $this->getData('id_sav'));
                    return array();
                }
                break;

            case 'SPCM':
                if ((int) $this->getData('repair_complete')) {
                    BimpTools::mailGrouper('tommy@bimp.fr, jc.cannet@bimp.fr', null, 'Passage a Fermé deja Fermé<br/>Attention SAV : ' . $this->getData('id_sav'));
                    return array();
                }
                break;

            default:
                if (!array_key_exists($status, self::$repairStatusCodes)) {
                    return array('Statut de la réparation à mettre à jour invalide (' . $status . ')');
                }
        }

        if ($this->use_gsx_v2) {
            $repId = $this->getData('repair_number');
            if (!$repId) {
                $errors[] = 'Numéro de réparation absent';
            } else {
                $this->initGsx($errors);
                if (empty($errors)) {
                    $result = $this->gsx_v2->repairUpdateStatus($repId, $status);

                    if (!is_array($result) || empty($result)) {
                        $errors = $this->gsx_v2->getErrors();
                    } else {
                        $errors = $this->processRepairRequestOutcome($result, $warnings);
                    }
                }
            }
        } else {
            $repair_confirm_number = $this->getData('repair_confirm_number');
            if (is_null($repair_confirm_number) || !$repair_confirm_number) {
                return array('Erreur: n° de confirmation de la réparation absent');
            }

            if (is_null($this->gsx) || $this->isIphone != $this->gsx->isIphone) {
                $this->gsx = new GSX($this->isIphone);
            }

            if (!$this->gsx->connect) {
                return array('Echec de la connexion à GSX (5)');
            }

            $repair_type = $this->getData('repair_type');
            if (is_null($repair_type) || !$repair_type) {
                $this->lookup();
                $repair_type = $this->getData('repair_type');
                if (!is_null($repair_type) && $repair_type) {
                    $this->update();
                } else {
                    return array('Echec de la récupération du type de réparation. Mise à jour du statut impossible');
                }
            }

            $n_soap_errors = count($this->gsx->errors['soap']);

            $client = '';
            $requestName = '';

            $data = array(
                'repairConfirmationNumber' => $repair_confirm_number
            );

            switch ($repair_type) {
                case 'carry_in':
                    $data['statusCode'] = $status;
                    if (0 != $this->gsx->isIphone)
                        $this->gsx = new GSX(false); //force not iphone
                    $client = 'CarryInRepairUpdate';
                    $requestName = 'UpdateCarryInRequest';
                    $data['statusCode'] = $status;
                    $clientRep = 'UpdateCarryIn' . 'Response';
                    break;
                case 'repair_or_replace':
                    $data['repairStatusCode'] = $status;
                    /* if ($this->isIphone) {
                      $client = 'UpdateIPhoneRepairOrReplaceRequest';
                      $requestName = 'UpdateIPhoneRepairOrReplaceRequest';
                      } else { */

                    if (0 != $this->gsx->isIphone)
                        $this->gsx = new GSX(false); //force not iphone
                    $client = 'UpdateRepairOrReplace';
                    $requestName = 'UpdateRepairOrReplaceRequest';
//                }
                    $data['repairStatusCode'] = $status;
                    $clientRep = $client . 'Response';
                    break;
            }

            $errors = array();

            $request = $this->gsx->_requestBuilder($requestName, 'repairData', $data);
            $response = $this->gsx->request($request, $client);

            if (count($this->gsx->errors['soap']) > $n_soap_errors) {
                $errors[] = 'Echec de la requête "' . $requestName . '" WSDL : ' . $this->gsx->wsdlUrl;
                $errors = BimpTools::merge_array($errors, $this->gsx->errors['soap']);
            }

            if (!isset($response[$clientRep]['repairConfirmation'])) {
                $errors[] = 'Echec de la requête "' . $requestName . '"';
            }
        }

        if (!count($errors)) {
            if ($status === 'RFPU') {
                $this->updateField('ready_for_pick_up', 1);
            }
        } else
            BimpTools::mailGrouper('tommy@bimp.fr, jc.cannet@bimp.fr', null, 'Changement de statut erreur<br/>Attention SAV : ' . $this->getData('id_sav') . ' changement de statut erreur ' . print_r($errors, 1));


        return $errors;
    }

    // Méthodes V1: 

    public function import($id_sav, $number, $numberType)
    {
        // Obsolète pour la v2

        if ($this->use_gsx_v2) {
            return;
        }

        $this->reset();

        switch ($numberType) {
            case 'repairConfirmationNumber':
                $this->set('repair_confirm_number', $number);
                break;

            case 'repairNumber':
                $this->set('repair_number', $number);
                break;

            case 'serialNumber':
                $this->setSerial($number);
                break;

            case 'imeiNumber':
                $this->set('serial', $number);
                $this->isIphone = true;
                break;
        }

        $this->set('id_sav', (int) $id_sav);

        $this->load();

        if (!$this->isLoaded()) {
            $this->set('id_sav', (int) $id_sav);
            $errors = $this->lookup($number, $numberType);

            if (count($errors)) {
                return $errors;
            }

            return $this->create();
        }
        $errors[] = 'Cette réparation est déjà enregistrée';
        $this->lookup($number, $numberType);
        return $errors;
    }

    public function loadPartsPending()
    {
        if ($this->use_gsx_v2) {
            return '';
        }
        if ($this->getData('canceled'))
            return array("Réparation annulée");
        if ($this->getData('closed') || $this->getData('repair_complete'))
            return array("Réparation fermée");

        $this->partsPending = array();

        if ((int) BimpCore::getConf('use_gsx_v2')) {
            
        } else {
            if (is_null($this->gsx) || $this->isIphone != $this->gsx->isIphone) {
                $this->gsx = new GSX($this->isIphone);
            }
            if (!$this->gsx->connect) {
                return array('Echec de la connexion à GSX (3)');
            }

            $this->gsx->resetSoapErrors();

            $repairNumber = $this->getData('repair_number');
            $repairConfirmNumber = $this->getData('repair_confirm_number');

            $data = array(
                'repairType'               => '',
                'repairStatus'             => '',
                'purchaseOrderNumber'      => '',
                'sroNumber'                => '',
                'repairConfirmationNumber' => '',
                'serialNumbers'            => array(
                    'serialNumber' => ''
                ),
                'shipToCode'               => '',
                'customerFirstName'        => '',
                'customerLastName'         => '',
                'customerEmailAddress'     => '',
                'createdFromDate'          => '',
                'createdToDate'            => '',
                'warrantyType'             => '',
                'kbbSerialNumberFlag'      => '',
                'comptiaCode'              => '',
            );

            if (!is_null($repairConfirmNumber) && $repairConfirmNumber) {
                $data['repairConfirmationNumber'] = $repairConfirmNumber;
            } elseif (!is_null($repairNumber) && $repairNumber) {
                $data['sroNumber'] = $repairNumber;
            }

            if ($this->isIphone) {
                $client = 'IPhonePartsPendingReturn';
                $requestName = 'IPhonePartsPendingReturnRequest';
            } else {
                $client = 'PartsPendingReturn';
                $requestName = 'PartsPendingReturnRequest';
            }

            $request = $this->gsx->_requestBuilder($requestName, 'repairData', $data);
            $response = $this->gsx->request($request, $client);

            if (count($this->gsx->errors['soap'])) {
                return $this->gsx->errors['soap'];
            }
            if (isset($response[$client . 'Response']['partsPendingResponse'])) {
                $partsPending = $response[$client . 'Response']['partsPendingResponse'];
                if (isset($partsPending['returnOrderNumber'])) {
                    $partsPending = array($partsPending);
                }
                $id_sav = (int) $this->getData('id_sav');
                $i = 0;
                foreach ($partsPending as $part) {
                    $i++;
                    $fileName = null;
                    $labelDir = '/bimpcore/sav/' . $id_sav . '';
                    if (!is_dir(DOL_DATA_ROOT . $labelDir)) {
                        mkdir(DOL_DATA_ROOT . $labelDir);
                    }
                    $fileUrl = "";
                    if (isset($part['returnOrderNumber']) && $part['returnOrderNumber'] != "" && isset($part['partNumber'])) {
                        $fileName = "label_" . $part['returnOrderNumber'] . "-" . $i . ".pdf";
                        $fileNamePath = $labelDir . "/" . $fileName;
                        $fileUrl = "/document.php?modulepart=bimpcore&file=" . 'sav/' . $id_sav . "/" . $fileName;
                        if (!file_exists(DOL_DATA_ROOT . $fileNamePath)) {
                            if ($this->isIphone) {
                                $client2 = 'IPhoneReturnLabel';
                            } else {
                                $client2 = 'ReturnLabel';
                            }
                            $requestName2 = $client2 . 'Request';

                            $request = $this->gsx->_requestBuilder($requestName2, '', array(
                                'returnOrderNumber' => $part['returnOrderNumber'],
                                'partNumber'        => $part['partNumber']
                            ));

                            $labelResponse = $this->gsx->request($request, $client2);

                            if (isset($labelResponse[$client2 . 'Response']['returnLabelData']['returnLabelFileName'])) {
                                if (!file_put_contents(DOL_DATA_ROOT . $fileNamePath, $labelResponse[$client2 . 'Response']['returnLabelData']['returnLabelFileData']))
                                    $fileUrl = "";
                            }
                        }
                    }
                    $part['fileName'] = $fileUrl;
                    $this->partsPending[] = $part;
                }
                return array();
            }
            return array('Echec de la requête "' . $requestName . '" (aucune réponse reçue');
        }
    }

    public function repairDetails($force_repair_update = false)
    {
        // Obsolète pour la v2

        if ($this->use_gsx_v2) {
            return;
        }

        if (!$this->isLoaded()) {
            $errors = $this->load();
            if (count($errors)) {
                return $errors;
            }
        }

        if (is_null($this->gsx) || $this->isIphone != $this->gsx->isIphone) {
            $this->gsx = new GSX($this->isIphone);
        }

        if (!$this->gsx->connect) {
            return array('Echec de la connexion à GSX (6)');
        }

        $repair_confirm_number = $this->getData('repair_confirm_number');
        if (is_null($repair_confirm_number) || !$repair_confirm_number) {
            return array('Erreur: n° de confirmation de la réparation absent');
        }

        if ($this->isIphone) {
            $client = 'IPhoneRepairDetailsLookup';
            $requestName = 'IPhoneRepairDetailsLookupRequest';
        } else {
            $client = 'RepairDetailsLookup';
            $requestName = 'RepairDetailsLookupRequest';
        }

        $n_soap_errors = count($this->gsx->errors['soap']);

        $request = $this->gsx->_requestBuilder($requestName, 'repairConfirmationNumber', $this->getData('repair_confirm_number'));
        $response = $this->gsx->request($request, $client);

        if (count($this->gsx->errors['soap']) > $n_soap_errors) {
            $errors[] = 'Echec de la requête "' . $requestName . '"';
            $errors = BimpTools::merge_array($errors, $this->gsx->errors['soap']);
        }

        $parts = null;
        if ($this->isIphone) {
            if (isset($response['IPhoneRepairDetailsLookupResponse']['lookupResponseData'])) {
                $response = $response['IPhoneRepairDetailsLookupResponse']['lookupResponseData'];
            }
        } else {
            if (isset($response['RepairDetailsLookupResponse']['repairDetails'])) {
                $response = $response['RepairDetailsLookupResponse']['repairDetails'];
            }
        }
        if (isset($response['repairPartDetailsInfo'])) {
            $parts = $response['repairPartDetailsInfo'];
        } else {
            return array('Echec de la récupération du montant total: Aucune réponse reçue');
        }

        if (isset($parts['partNumber'])) {
            $parts = array($parts);
        }

        $totalFromOrder = 0;
        foreach ($parts as $part) {
            if (isset($part['netPrice']) && $part['netPrice']) {
                $totalFromOrder += (float) $part['netPrice'];
            }
            if ($totalFromOrder < 1 && $part['partAbused'] == "Y")
                $totalFromOrder = 1.00;
        }

        if (!$totalFromOrder) {
            if (isset($response['isInvoiced']) && $response['isInvoiced'] === 'Y') {
                $totalFromOrder = 1.00;
            }
        }

        if ((float) $totalFromOrder !== (float) $this->getData('total_from_order')) {
            $this->set('total_from_order', (float) $totalFromOrder);
            $this->set('total_from_order_changed', 1);
            $sav = $this->getChildObject('sav');
            $sav->setAllStatutWarranty((float) $totalFromOrder == 0);
            $force_repair_update = true;
        }

        if (isset($response['newSerialNumber']) && $response['newSerialNumber'] !== '') {
            if ($response['newSerialNumber'] !== (string) $this->getData('serial') &&
                    $response['newSerialNumber'] !== (string) $this->getData('new_serial')) {
                $this->set('new_serial', $response['newSerialNumber']);
                $sav = $this->getChildObject('sav');
                if (BimpObject::objectLoaded($sav)) {
                    $equipment = $sav->getChildObject('equipment');
                    if (BimpObject::objectLoaded($equipment)) {
                        $equipment->set('serial', $response['newSerialNumber']);
                        $equipment->update();
                        $sav->addNote('Mise à jour du numéro de série de l\'équipement effectué le ' . date('d / m / Y à H:i'));
                    }
                }
                $force_repair_update = true;
            }
        }
        if ($force_repair_update && $this->isLoaded()) {
            return $this->update();
        }

        return array();
    }

    public function close($sendRequest = true, $checkRepair = 1, &$warnings = array(), &$modal_html = '')
    {
        if ($this->use_gsx_v2) {
            $errors = array();
            $warnings = array();
            $modal_html = '';
            if ($this->initGsx($errors)) {
                if ((int) $this->getData('repair_complete')) {
                    $errors[] = 'Cette réparation a déjà été fermée';
                } elseif ((int) $this->getData('canceled')) {
                    $errors[] = 'Cette réparation est annulée';
                } else {
                    $check = true;
                    if ($checkRepair) {
                        $this->loadPartsPending();
                        $callback = 'function() {reloadRepairsViews(' . $this->getData('id_sav') . ');}';
                        $onclick = $this->getJsGsxAjaxOnClick('gsxRepairAction', array(
                                    'id_repair'    => (int) $this->id,
                                    'action'       => 'closeRepair',
                                    'check_repair' => 0
                                        ), array(
                                    'success_callback' => $callback
                                        ), '$(\'#repair_' . $this->id . '_result\')') . ';';
                        $onclick .= 'bimpModal.clearCurrentContent()';
                        if (count($this->partsPending) && !(string) $this->getData('new_serial')) {
                            $msg = 'La réparation ne peut pas être fermée, les numéros de série de certains composants semblent ne pas avoir été mis à jour';
                            $modal_html .= BimpRender::renderAlerts($msg, 'warning');
                            $modal_html .= '<p style="text-align: center; padding: 30px">';
                            $modal_html .= '<span class="btn btn-default closeRepair" onclick="' . $onclick . '">Forcer la fermeture</span></p>';
                            $check = false;
                        } elseif (!(string) $this->getData('new_serial')) {
                            $msg = 'Veuillez confirmer que plus aucune opération n\'est à effectuer sur cette réparation.';
                            $modal_html .= BimpRender::renderAlerts($msg, 'warning');
                            $modal_html .= '<p style="text-align: center; padding: 30px">';
                            $modal_html .= '<span class="btn btn-default closeRepair" onclick="' . $onclick . '">Confirmer la fermeture</span></p>';
                            $check = false;
                        }
                    }

                    if ($check) {
                        if ($sendRequest) {
                            $errors = $this->updateStatus('SPCM', $warnings);
                        }

                        if (!count($errors)) {
                            $this->set('repair_complete', 1);
                            $this->set('closed', 1);
                            $this->set('date_closed', date('Y-m-d'));

                            $update_errors = $this->update();
                            if (count($update_errors)) {
                                $errors[] = BimpTools::getMsgFromArray($update_errors, 'Echec de l\'enregistrement de la fermeture de la réparation en base de données');
                            }
                        }
                    }
                }
            }
            return array(
                'errors'     => $errors,
                'warnings'   => $warnings,
                'modal_html' => $modal_html
            );
        } else {
            if (!$this->isLoaded()) {
                $errors = $this->load();
                if (count($errors)) {
                    return $errors;
                }
            }

            if (is_null($this->gsx) || $this->isIphone != $this->gsx->isIphone) {
                $this->gsx = new GSX($this->isIphone);
            }

            if (!$this->gsx->connect) {
                return array('Echec de la connexion à GSX (7)');
            }

            $repair_confirm_number = $this->getData('repair_confirm_number');
            if (is_null($repair_confirm_number) || !$repair_confirm_number) {
                return array('Erreur: n° de confirmation de la réparation absent');
            }

            if ((int) $this->getData('repair_complete')) {
                return array('Cette réparation a déjà été fermée.');
            }

            if ($sendRequest) {
                $confirm_number = $this->getData('repair_confirm_number');
                if (is_null($confirm_number) || $confirm_number == '') {
                    $this->addError('Erreur: aucun numéro de confirmation enregistré pour cette réparation.');
                    return false;
                }

                if ($checkRepair) {
                    $this->loadPartsPending();
                    $callback = 'function() {reloadRepairsViews(' . $this->getData('id_sav') . ');}';
                    $onclick = $this->getJsActionOnclick('closeRepair', array('check_repair' => 0), array(
                        'result_container' => '$(\'#repair_' . $this->id . '_result\')',
                        'success_callback' => $callback,
                    ));
                    if (count($this->partsPending) && !(string) $this->getData('new_serial')) {
                        $html = 'La réparation ne peut pas être fermée, les numéros de série de certains composants semblent ne pas avoir été mis à jour';
                        $html .= '<p style="text-align: center; padding: 30px">';
                        $html .= '<span class="btn btn-default closeRepair" onclick="' . $onclick . '">Forcer la fermeture</span></p>';
                        return array($html);
                    } elseif (!(string) $this->getData('new_serial')) {
                        $html = 'Veuillez confirmer que plus aucune opération n\'est à effectuer sur cette réparation.';
                        $html .= '<p style="text-align: center; padding: 30px">';
                        $html .= '<span class="button redHover closeRepair" onclick="' . $onclick . '">Confirmer la fermeture</span></p>';
                        $this->gsx->resetSoapErrors();
                        return array($html);
                    }
                }

                $client = '';
                $requestName = '';

                if ($this->isIphone) {
                    $client = 'IPhoneMarkRepairComplete';
                    $requestName = 'IPhoneMarkRepairCompleteRequest';
                } else {
                    $client = 'MarkRepairComplete';
                    $requestName = 'MarkRepairCompleteRequest';
                }
                $request = $this->gsx->_requestBuilder($requestName, '', array('repairConfirmationNumbers' => $this->getData('repair_confirm_number')));
                $response = $this->gsx->request($request, $client);

                if (!isset($response[$client . 'Response']['repairConfirmationNumbers'])) {
                    return BimpTools::merge_array($this->gsx->errors['soap'], array('Echec de la requête de fermeture de la réparation'));
                }
            }

            $this->set('repair_complete', 1);
            $this->set('closed', 1);
            $this->set('date_closed', date('Y-m-d'));

            $update_errors = $this->update();
            if (count($update_errors)) {
                $errors[] = 'Echec de l\'enregistrement de la fermeture de la réparation en base de données';
                return BimpTools::merge_array($errors, $update_errors);
            }

            return $this->repairDetails();
        }
    }

    // Affichages:

    public function displayGsxStatus()
    {
        if ($this->use_gsx_v2) {
            if (isset($this->repairLookUp['repairStatusCode'])) {
                $status .= $this->repairLookUp['repairStatusCode'];
            }

            if (isset($this->repairLookUp['repairStatusDescription'])) {
                $status .= ($status ? ' - ' : '') . $this->repairLookUp['repairStatusDescription'];
            }

            if ($status) {
                return $status;
            }
        } else {
            if (isset($this->repairLookUp['repairStatus'])) {
                return $this->repairLookUp['repairStatus'];
            }
        }

        return '<span class="danger">Inconnu</span>';
    }

    public function displayGarantie()
    {
        if (!$this->isLoaded()) {
            return '';
        }
        $html = '';

        $total = $this->getData('total_from_order');
        if (!is_null($total)) {
            $msg = '';

            if ((float) $total > 0) {
                $html .= '<span class="danger">NON</span>';
                $msg = 'La réparation ' . $this->id . ' - ' . $this->getData('repair_confirm_number') . ' n\'est pas sous garantie. Montant: ';
                if ((float) $total != 1) {
                    $html .= ' - Montant : ' . BimpTools::displayMoneyValue($total, 'EUR');
                    $msg .= $total . ' €';
                }
            } else {
                $html .= '<span class="success">OUI</span>';
                $msg .= 'Réparation ' . $this->id . ' - ' . $this->getData('repair_confirm_number') . ' sous garantie';
            }

            if ((int) $this->getData('total_from_order_changed')) {
                $html .= '<script type="text/javascript">';
                $html .= 'alert("' . $msg . ' verifiez bien les paniers")';
                $html .= '</script>';

                $this->updateField('total_from_order_changed', 0);
            }
        } else {
            $html .= '<span class="danger">Inconnu</span>';
        }
        return $html;
    }

    public function displayViewMsgs()
    {
        $html = '';
        if (!empty($this->lookupErrors)) {
            $html .= BimpRender::renderAlerts(BimpTools::getMsgFromArray($this->lookupErrors, 'Echec de la récupération des informations depuis GSX'));
        } elseif (!empty($this->repairLookUp) && $this->use_gsx_v2) {
            if (isset($this->repairLookUp['messages']) && !empty($this->repairLookUp['messages'])) {
                foreach ($this->repairLookUp['messages'] as $message) {
                    $class = 'info';
                    $html .= BimpRender::renderAlerts($message['type'] . '<br/>' . $message['message'], $class);
                }
            }
        }
        return $html;
    }

    public function displayRepairType()
    {
        $html = '';

        $type = $this->getData('repair_type');

        if ($type) {
            $html .= $type;
            if ($this->use_gsx_v2 && array_key_exists($type, GSX_Const::$repair_types)) {
                $html .= ' - ' . GSX_Const::$repair_types[$type];
            }
        }

        return $html;
    }

    // Rendus HTML V2:

    public function renderLookupInfos()
    {
        if (!$this->isLoaded()) {
            return '';
        }

        $html = '';

        $html .= $this->displayViewMsgs();

        if (!empty($this->repairLookUp)) {
            $errors = array();

            $html .= '<table class="bimp_list_table">';
            $html .= '<tbody class="headers_col">';

            foreach (array(
        'referenceNumber'           => 'Référence',
        'purchaseOrderNumber'       => 'N° de commande',
        'serviceNotificationNumber' => 'N° de notification du service',
        'repairStatusCode'          => 'Code statut réparation',
        'repairStatusDescription'   => 'Statut réparation',
        'slaGroupDescription'       => 'Groupe SLA',
        'coverageOption'            => 'Option de couverture',
        'coverageStatusCode'        => 'Code statut couverture',
        'coverageStatusDescription' => 'Statut couverture',
        'acPlusIncidentConsumed'    => 'AppleCare+ consommé',
        'shipment/deviceShipped'    => 'Matériel expédié',
        'shipment/trackingNumber'   => 'N° de suivi de l\'expédition',
        'invoiceAvailable'          => 'Facture disponible',
        'csCode'                    => 'Code satisfaction client'
            ) as $path => $label) {
                $value = BimpTools::getArrayValueFromPath($this->repairLookUp, $path, '', $errors, false, '', array(
                            'value2String' => true
                ));
                if ($value) {
                    $html .= '<tr>';
                    $html .= '<th>' . $label . '</th>';
                    $html .= '<td>' . $value . '</td>';
                    $html .= '</tr>';
                }
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        $title = BimpRender::renderIcon('fab_apple') . ' Infos GSX';
        return BimpRender::renderPanel($title, $html, '', array(
                    'foldable' => 1,
                    'type'     => 'secondary'
        ));
    }

    public function renderRepairParts()
    {
        if ($this->isLoaded()) {
            if (isset($this->repairLookUp['parts']) && !empty($this->repairLookUp['parts'])) {
                $errors = array();

                foreach ($this->repairLookUp['parts'] as $part) {
                    $part_html = '';
                    $html = '<table class="bimp_list_table">';
                    $html .= '<tbody class="headers_col">';
                    foreach (array(
                'description'                        => 'Désignation',
                'type'                               => 'Type',
                'kbbDeviceDetail/identifiers/serial' => 'Numéro de série',
                'kbbDeviceDetail/identifiers/imei'   => 'IMEI',
                'kbbDeviceDetail/identifiers/imei2'  => 'IMEI2',
                'kbbDeviceDetail/identifiers/meid'   => 'MEID',
                'kgbDeviceDetail/identifiers/serial' => 'Nouveau Numéro de série',
                'kgbDeviceDetail/identifiers/imei'   => 'Nouveau IMEI',
                'kgbDeviceDetail/identifiers/imei2'  => 'Nouveau IMEI2',
                'kgbDeviceDetail/identifiers/meid'   => 'Nouveau MEID',
                'partUsed'                           => 'Réf. composant utilisé',
                'coverageOption'                     => 'Option de couverture',
                'coverageCode'                       => 'Code couverture',
                'coverageDescription'                => 'Couverture',
                'fromConsignedStock'                 => 'Provient du stock consigné',
                'netPrice'                           => 'Prix net',
                'currency'                           => 'Devise',
                'pricingOption'                      => 'Prix spécial appliqué',
                'billable'                           => 'Facturable'
                    ) as $path => $label) {
                        $value = BimpTools::getArrayValueFromPath($part, $path, '', $errors, false, '', array(
                                    'value2String' => true
                        ));
                        if ($value) {
                            $html .= '<tr>';
                            $html .= '<th>' . $label . '</th>';
                            $html .= '<td>' . $value . '</td>';
                            $html .= '</tr>';
                        }
                    }

                    $html .= '</tbody class="headers_col">';
                    $html .= '</table>';

                    $part_html .= '<div class="col-xs-12 col-sm-6 sol-md-6 col-lg-6">';
                    $title = BimpRender::renderIcon('fas_info-circle', 'iconLeft') . 'Infos';
                    $part_html .= BimpRender::renderPanel($title, $html, '', array(
                                'foldable' => 1,
                                'type'     => 'default'
                    ));
                    $part_html .= '</div>';


                    $part_html .= '<div class="col-xs-12 col-sm-6 sol-md-6 col-lg-6">';
                    // Infos commande:
                    $has_lines = false;
                    $html = '<table class="bimp_list_table">';
                    $html .= '<tbody class="headers_col">';
                    foreach (array(
                'orderId'                => 'ID Commande',
                'orderStatusCode'        => 'Code statut commande',
                'orderStatusDescription' => 'Statut commande',
                'orderStatusDate'        => 'Date du statut commande'
                    ) as $path => $label) {
                        $value = BimpTools::getArrayValueFromPath($part, $path, '', $errors, false, '', array(
                                    'value2String' => true
                        ));
                        if ($value) {
                            $has_lines = true;
                            $html .= '<tr>';
                            $html .= '<th>' . $label . '</th>';
                            $html .= '<td>' . $value . '</td>';
                            $html .= '</tr>';
                        }
                    }

                    $html .= '</tbody>';
                    $html .= '</table>';

                    if ($has_lines) {
                        $title = BimpRender::renderIcon('fas_dolly', 'iconLeft') . 'Infos commande';
                        $part_html .= BimpRender::renderPanel($title, $html, '', array(
                                    'foldable' => 1,
                                    'type'     => 'default'
                        ));
                    }

                    // Infos livraison:
                    $has_lines = false;
                    $html = '<table class="bimp_list_table">';
                    $html .= '<tbody class="headers_col">';
                    foreach (array(
                'carrierName'            => 'Transporteur',
                'carrierCode'            => 'Code transporteur',
                'carrierUrl'             => 'URL Transporteur',
                'deliveryTrackingNumber' => 'N° de suivi du transport',
                'deliveryNumber'         => 'N° de livraison',
                'deliveryDate'           => 'Date de livraison'
                    ) as $path => $label) {
                        $value = BimpTools::getArrayValueFromPath($part, $path, '', $errors, false, '', array(
                                    'value2String' => true
                        ));
                        if ($value) {
                            $has_lines = true;
                            $html .= '<tr>';
                            $html .= '<th>' . $label . '</th>';
                            $html .= '<td>' . $value . '</td>';
                            $html .= '</tr>';
                        }
                    }

                    $html .= '</tbody>';
                    $html .= '</table>';

                    if ($has_lines) {
                        $title = BimpRender::renderIcon('fas_shipping-fast', 'iconLeft') . 'Infos livraison';
                        $part_html .= BimpRender::renderPanel($title, $html, '', array(
                                    'foldable' => 1,
                                    'type'     => 'default'
                        ));
                    }

                    // Infos retour:
                    $has_lines = false;
                    $html .= '</div>';
                    $html = '<table class="bimp_list_table">';
                    $html .= '<tbody class="headers_col">';
                    foreach (array(
                'returnStatusCode'       => 'Code raison du retour',
                'returnOrderNumber'      => 'N° de retour',
                'returnTrackingNumber'   => 'N° de suivi du retour',
                'kgbDeviceDetail'        => 'KGB',
                'kbbDeviceDetail'        => 'KBB',
                'returnPartReceivedDate' => 'Date de réception du retour'
                    ) as $path => $label) {
                        $value = BimpTools::getArrayValueFromPath($part, $path, '', $errors, false, '', array(
                                    'value2String' => true
                        ));
                        if ($value) {
                            $has_lines = true;
                            $html .= '<tr>';
                            $html .= '<th>' . $label . '</th>';
                            if (is_array($value))
                                $html .= '<td>' . print_r($value, 1) . '</td>';
                            else
                                $html .= '<td>' . $value . '</td>';
                            $html .= '</tr>';
                        }
                    }

                    if (isset($part['returnOrderNumber']) && $part['returnOrderNumber']) {
                        $file_errors = array();
                        $file_url = $this->getPartReturnLabelFileUrl($part, $file_errors);

                        $html .= '<tr>';
                        $html .= '<th>Etiquette de retour</th>';
                        $html .= '<td>';
                        if (count($file_errors)) {
                            $html .= BimpRender::renderAlerts($file_errors);
                        }
                        if ($file_url) {
                            $html .= '<a class="btn btn-default" href="' . $file_url . '" target="_blank">';
                            $html .= BimpRender::renderIcon('fas_file-pdf', 'iconLeft') . 'Fichier PDF';
                            $html .= '</a>';
                        }
                        $html .= '</td>';
                        $html .= '</tr>';
                    }

                    $html .= '</tbody>';
                    $html .= '</table>';

                    if ($has_lines) {
                        $title = BimpRender::renderIcon('fas_arrow-circle-left', 'iconLeft') . 'Infos retour';
                        $part_html .= BimpRender::renderPanel($title, $html, '', array(
                                    'foldable' => 1,
                                    'type'     => 'default'
                        ));
                    }

                    $part_html .= '</div>';

                    $parts_html .= '<div class="row">';
                    $title = BimpRender::renderIcon('fas_box', 'iconLeft') . 'Composant ' . $part['number'];
                    $buttons = array();

                    $codeReturnAttendKBB = array('KBB', 'ABU', 'NRET');
                    if (isset($part['returnStatusCode']) && in_array($part['returnStatusCode'], $codeReturnAttendKBB) &&
                            (!isset($part['kgbDeviceDetail']) || empty($part['kgbDeviceDetail']))) {

                        $values = array(
                            'fields' => array(
                                'kbb_number'      => '',
                                'kgb_number'      => '',
                                'sequence_number' => isset($part['sequenceNumber']) ? (int) $part['sequenceNumber'] : 0,
                            )
                        );

                        foreach (array(
                    'kbb_number' => 'kbbDeviceDetail',
                    'kgb_number' => 'kgbDeviceDetail'
                        ) as $number_type => $numberDataName) {
                            foreach (GSX_Const::$deviceIdentifiers as $id_type => $id_label) {
                                if (isset($part[$numberDataName]['identifiers'][$id_type]) && (string) $part[$numberDataName]['identifiers'][$id_type]) {
                                    $values['fields'][$number_type] = $part[$numberDataName]['identifiers'][$id_type];
                                    break;
                                }
                            }
                        }

                        $values_str = htmlentities(json_encode($values));

                        $buttons[] = array(
                            'label'       => 'Mettre à jour le numéro de série',
                            'icon_before' => 'fas_pen',
                            'classes'     => array('btn', 'btn-default'),
                            'attr'        => array(
                                'onclick' => 'gsx_loadUpdatePartKgbForm($(this), ' . (int) $this->getData('id_sav') . ', ' . (int) $this->id . ', \'' . $part['number'] . '\', \'' . $values_str . '\')'
                            )
                        );
                    }

                    $parts_html .= BimpRender::renderPanel($title, $part_html, '', array(
                                'foldable'       => 1,
                                'type'           => 'secondary',
                                'header_buttons' => $buttons
                    ));
                    $parts_html .= '</div>';
                }

                return $parts_html;
            }
        }

        return '';
    }

    public function renderPropalReviewInfos()
    {
        if ($this->isLoaded()) {
            if (empty($this->repairLookUp)) {
                $this->lookup();
            }

            if (isset($this->repairLookUp['parts']) && !empty($this->repairLookUp['parts'])) {
                $sav = $this->getChildObject('sav');
                if (BimpObject::objectLoaded($sav)) {
                    $propal = $sav->getChildObject('propal');

                    if (BimpObject::objectLoaded($propal)) {
                        $propal_status = (int) $propal->getData('fk_statut');
                        if (!$propal_status || $sav->isActionAllowed('reviewPropal')) {
                            $parts = array();

                            foreach ($this->repairLookUp['parts'] as $repairPart) {
                                $savPart = null;

                                if (isset($repairPart['number']) && $repairPart['number']) {
                                    $savPart = BimpCache::findBimpObjectInstance('bimpsupport', 'BS_ApplePart', array(
                                                'id_sav'      => (int) $sav->id,
                                                'part_number' => $repairPart['number']
                                    ));
                                }

                                if (BimpObject::objectLoaded($savPart)) {
                                    $savPartPrice = (float) $savPart->getPrice();
                                    if (((float) $repairPart['netPrice'] > 0 && $savPartPrice !== (float) $repairPart['netPrice']) ||
                                            !(float) $repairPart['netPrice'] && $savPartPrice !== 0 && (int) $savPart->getData('out_of_warranty')) {
                                        $parts[] = array(
                                            'number'        => $repairPart['number'],
                                            'desc'          => (isset($repairPart['description']) ? $repairPart['description'] : ''),
                                            'current_price' => $savPartPrice,
                                            'new_price'     => (float) $repairPart['netPrice']
                                        );
                                    }
                                } else {
                                    $line_desc = (isset($repairPart['number']) ? $repairPart['number'] : '');
                                    if (isset($repairPart['description']) && $repairPart['description']) {
                                        $line_desc .= ($line_desc ? ' - ' : '') . $repairPart['description'];
                                    }
                                    $id_line = (int) $this->db->getValue('propaldet', 'rowid', '`fk_propal` = ' . (int) $propal->id . ' AND `description` LIKE \'' . $line_desc . '\'');
                                    if (!$id_line) {
                                        $parts[] = array(
                                            'number'        => (isset($repairPart['number']) ? $repairPart['number'] : ''),
                                            'desc'          => (isset($repairPart['description']) ? $repairPart['description'] : ''),
                                            'current_price' => 'none',
                                            'new_price'     => (float) $repairPart['netPrice']
                                        );
                                    }
                                }
                            }

                            if (!empty($parts)) {
                                $html = '';

                                $msg = 'Un écart de prix a été constaté entre le devis et la réparation pour ';
                                if (count($parts) > 1) {
                                    $msg .= count($parts) . ' composants';
                                } else {
                                    $msg .= '1 composant';
                                }
                                $html .= BimpRender::renderAlerts($msg, 'warning');

                                $html .= '<table class="bimp_list_table">';
                                $html .= '<thead>';
                                $html .= '<tr>';
                                $html .= '<th>Réf. composant</th>';
                                $html .= '<th>Désignation</th>';
                                $html .= '<th>Prix actuel du devis</th>';
                                $html .= '<th>Prix réparation</th>';
                                $html .= '<th></th>';
                                $html .= '</tr>';
                                $html .= '</thead>';
                                $html .= '<tbody>';

                                foreach ($parts as $part) {
                                    $html .= '<tr>';
                                    $html .= '<td>' . $part['number'] . '</td>';
                                    $html .= '<td>' . $part['desc'] . '</td>';
                                    $html .= '<td>';
                                    if ($part['current_price'] === 'none') {
                                        $html .= '<span class="warning">Absent du panier</span>';
                                    } else {
                                        $html .= BimpTools::displayMoneyValue($part['current_price']);
                                    }
                                    $html .= '</td>';
                                    $html .= '<td>' . BimpTools::displayMoneyValue($part['new_price']) . '</td>';
                                    $html .= '<td>';
                                    $html .= '<span class="btn btn-default" onclick="';
                                    $html .= $this->getJsActionOnclick('reviewPropal', array(
                                        'parts' => htmlentities(json_encode(array(
                                            array(
                                                'number'    => $part['number'],
                                                'desc'      => $part['desc'],
                                                'new_price' => (float) $part['new_price']
                                            )
                                        )))
                                            ), array(
                                        'confirm_msg' => 'Veuillez confirmer la ' . ($propal_status ? 'révision' : 'mise à jour') . ' du devis pour le composant ' . $part['number']
                                    ));
                                    $html .= '">';
                                    if ($part['current_price'] === 'none') {
                                        $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . ' Ajouter au devis';
                                    } else {
                                        $html .= BimpRender::renderIcon('fas_pen', 'iconLeft') . ($propal_status ? 'Réviser' : 'Mettre à jour') . ' le devis';
                                    }
                                    $html .= '</span>';
                                    $html .= '</td>';
                                    $html .= '</tr>';
                                }

                                $html .= '</tbody>';
                                $html .= '</table>';

                                if (count($parts) > 1) {
                                    $parts_data = array();
                                    foreach ($parts as $part) {
                                        $parts_data[] = array(
                                            'number'    => $part['number'],
                                            'desc'      => $part['desc'],
                                            'new_price' => (float) $part['new_price']
                                        );
                                    }

                                    $html .= '<div class="buttonsContainer" style="text-align: right; margin-top: 10px">';
                                    $html .= '<span class="btn btn-default" onclick="' . $this->getJsActionOnclick('reviewPropal', array(
                                                'parts' => htmlentities(json_encode($parts_data))
                                                    ), array(
                                                'confirm_msg' => 'Veuillez confirmer la ' . ($propal_status ? 'révision' : 'mise à jour') . ' du devis pour tous les composants'
                                            )) . '">';
                                    $html .= BimpRender::renderIcon('fas_pen', 'iconLeft') . ($propal_status ? 'Réviser' : 'Mettre à jour') . ' le devis pour tous les composants';
                                    $html .= '</span>';
                                    $html .= '</div>';
                                }

                                return $html;
                            }
                        }
                    }
                }
            }
        }

        return '';
    }

    // Rendus HTML V1: 

    public function renderActions()
    {
        if ($this->use_gsx_v2) {
            return '';
        }

        $buttons = array();

        $id_sav = (int) $this->getData('id_sav');
        $callback = 'function() {reloadRepairsViews(' . $id_sav . ');}';

        if (!(int) $this->getData('ready_for_pick_up')) {
            $confirm = 'Attention, la réparation va être marquée &quote;Ready For Pick up&quote; (prête pour enlèvement) auprès du service GSX d\\\'Apple. Veuillez confirmer';
            $onclick = $this->getJsActionOnclick('endRepair', array(), array(
                'result_container' => '$(\'#repair_' . $this->id . '_result\')',
                'success_callback' => $callback,
                'confirm_msg'      => $confirm
            ));
            $buttons[] = array(
                'label'   => 'Prête pour enlèvement',
                'classes' => array('btn', 'btn-danger'),
                'attr'    => array(
                    'onclick' => $onclick
                )
            );
        } elseif (!(int) $this->getData('repair_complete')) {
            $confirm = 'Attention, la réparation va être indiquée comme complète auprès du service GSX d\\\'Apple. Veuillez confirmer';
            $onclick = $this->getJsActionOnclick('closeRepair', array('check_repair' => 1), array(
                'result_container' => '$(\'#repair_' . $this->id . '_result\')',
                'success_callback' => $callback,
                'confirm_msg'      => $confirm
            ));
            $buttons[] = array(
                'label'   => 'Restituer',
                'classes' => array('btn', 'btn-danger'),
                'attr'    => array(
                    'onclick' => $onclick
                )
            );
        } elseif (!(int) $this->getData('reimbursed')) {
            $confirm = 'Veuillez confirmer';
            $onclick = $this->getJsActionOnclick('markRepairAsReimbursed', array(), array(
                'result_container' => '$(\'#repair_' . $this->id . '_result\')',
                'success_callback' => $callback,
                'confirm_msg'      => $confirm
            ));
            $buttons[] = array(
                'label'   => 'Marquer comme remboursée',
                'classes' => array('btn', 'btn-default'),
                'attr'    => array(
                    'onclick' => $onclick
                )
            );
        }

        if (count($buttons)) {
            $html .= '<div class="buttonsContainer align-right">';
            foreach ($buttons as $button) {
                $html .= BimpRender::renderButton($button);
            }
            $html .= '</div>';
            $html .= '<div id="repair_' . $this->id . '_result" class="ajaxResultContainer" style="display: none"></div>';
        }

        return $html;
    }

    public function renderPartsPendingReturn()
    {
        if (!$this->isLoaded()) {
            return '';
        }

        if ($this->use_gsx_v2) {
            return '';
        }

        $html = '';
        if (!count($this->partsPending)) {
            $errors = $this->loadPartsPending();
            if (count($errors)) {
                $html .= BimpRender::renderAlerts($errors);
            } else {
                if ($this->use_gsx_v2) {
                    
                } elseif (count($this->gsx->errors['soap'])) {
                    $html .= '<p class="alert alert-info">Aucun composant en attente de retour n\'a été trouvé. <span class="displaySoapMsg" onclick="$(\'#partsPendingSoapMessages\').slideDown(250);">Voir le message soap</span></p>';
                    $html .= '<div id="partsPendingSoapMessages" style="margin: 15px 0; padding: 10px; display: none">';
                    $html .= $this->gsx->getGSXErrorsHtml();
                    $html .= '</div>';
                }
            }
        }

        if (!count($this->partsPending)) {
            $html .= BimpRender::renderAlerts('Aucun composant en attente de retour', 'info');
        } else {
            $html .= '<table id="repair_' . $this->id . '_partsPendingTable" class="bimp_list_table">';
            $html .= '<thead>';
            $html .= '<th>Nom</th>';
            $html .= '<th>Réf.</th>';
            $html .= '<th>N° de retour</th>';
            $html .= '<th>Inscrit pour le Retour</th>';
            $html .= '<th>Adresse retour</th>';
            $html .= '<th>KBB</th>';
            $html .= '<th>Etiquette</th>';
            $html .= '</thead>';

            $html .= '<tbody>';
            foreach ($this->partsPending as $part) {
                $html .= '<tr>';
                $html .= '<td>' . $part['partDescription'] . '</td>';
                $html .= '<td>' . $part['partNumber'] . '</td>';
                $html .= '<td>' . $part['returnOrderNumber'] . '</td>';
                $html .= '<td>' . $part['registeredForReturn'] . '</td>';
                $html .= '<td><span title="' . $part['vendorName'] . " " . $part['vendorAddress'] . " " . $part['vendorState'] . " " . $part['vendorCity'] . '">' . $part['vendorAddress'] . '</span></td>';
                $html .= '<td><span title="' . $part['kbbSerialNumber'] . '">' . dol_trunc($part['kbbSerialNumber'], 6) . '</span></td>';
                $html .= '<td>' . ($part['fileName'] != "" ? '<a class="btn btn-default" target="_blank" href="' . DOL_URL_ROOT . $part['fileName'] . '"><i class="fas fa5-file-alt iconLeft"></i>Etiquette</a>' : '') . '</td>';
                if (file_exists(DOL_DATA_ROOT . '/bimpcore/bimpsupport/sav/' . (int) $this->getData('id_sav') . '/' . $part['fileName'])) {
                    $html .= '<a target="_blank" href="' . DOL_URL_ROOT . $part['fileName'] . '" class="btn btn-default">';
                    $html .= '<i class="fa fa-file-o iconLeft"></i>Etiquette de retour</a>';
                }
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            $html .= '</table>';
        }

        $buttons = array();

        $footer = '';
        if (count($this->partsPending)) {
            $title = 'Mise à jour des numéros de série des composants';
            $buttons[] = BimpRender::renderButton(array(
                        'label'   => 'Numéros de série des composant',
                        'classes' => array('btn btn-light-default'),
                        'attr'    => array(
                            'onclick' => 'loadSerialUpdateForm($(this), \'' . (string) $this->getData('serial') . '\', ' . (int) $this->getData('id_sav') . ', ' . (int) $this->id . ', \'UpdateSerialNumber\', \'' . $title . '\')'
                        )
            ));
        }

        if (!(string) $this->getData('new_serial')) {
            $title = 'Mise à jour des numéros de série de l\\\'unité';
            $buttons[] = BimpRender::renderButton(array(
                        'label'   => 'Numéro de série de l\'unité',
                        'classes' => array('btn btn-light-default'),
                        'attr'    => array(
                            'onclick' => 'loadSerialUpdateForm($(this), \'' . (string) $this->getData('serial') . '\', ' . (int) $this->getData('id_sav') . ', ' . (int) $this->id . ', \'KGBSerialNumberUpdate\', \'' . $title . '\')'
                        )
            ));
        }

        if (count($buttons)) {
            $footer = BimpRender::renderDropDownButton('Mettre à jour', $buttons, array(
                        'icon' => 'edit'
            ));
        }

        return BimpRender::renderPanel('Composants en attente de retour', $html, $footer, array(
                    'type'     => 'secondary',
                    'foldable' => true,
                    'icon'     => 'fas_box'
        ));
    }

    // Rendus JS: 

    public function getJsGsxAjaxOnClick($method, $data = array(), $params = array(), $resultContainer = 'null')
    {
        $js = '';

        if ($this->isLoaded()) {
            $data['id_repair'] = (int) $this->id;
        }

        $js .= 'GsxAjax(';
        $js .= '\'' . $method . '\', ';
        $js .= '{';
        $fl = true;
        foreach ($data as $key => $value) {
            if (!$fl) {
                $js .= ',';
            } else {
                $fl = false;
            }
            $js .= $key . ': \'' . $value . '\'';
        }
        $js .= '}, ';

        $js .= $resultContainer . ', ';

        $js .= '{';
        $fl = true;
        foreach ($params as $key => $value) {
            if (!$fl) {
                $js .= ',';
            } else {
                $fl = false;
            }
            $js .= $key . ': \'' . $value . '\'';
        }
        $js .= '})';

        return $js;
    }

    // Actions V2 

    public function actionReviewPropal($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        $parts = isset($data['parts']) ? json_decode($data['parts'], 1) : array();

        if (empty($parts)) {
            $errors[] = 'Aucun composant à mettre à jour dans le devis indiqué';
        } else {
            $sav = $this->getChildObject('sav');
            if (BimpObject::objectLoaded($sav)) {
                $propal = $sav->getChildObject('propal');
                if (BimpObject::objectLoaded($propal)) {
                    $propal_status = (int) $propal->getData('fk_statut');
                    if ($propal_status > 0) {
                        // révision de la propale: 
                        $prop_warnings = array();
                        $prop_errors = $sav->reviewPropal($prop_warnings);

                        if (count($prop_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($prop_errors, 'Echec de la révision de la propale');
                        } else {
                            $success .= 'Mise en révision du devis effectuée avec succès<br/>';
                            $propal = $sav->getChildObject('propal');
                            $success_callback = 'triggerObjectChange(\'bimpsupport\', \'BS_SAV\', ' . $sav->id . ');';
                        }

                        if (count($prop_warnings)) {
                            $warnings[] = BimpTools::getMsgFromArray($prop_errors, 'Erreurs lors de la révision de la propale');
                        }
                    }

                    if (!count($errors)) {
                        // Mise à jour des pièces Apple: 
                        foreach ($parts as $part) {
                            $savPart = null;

                            if (isset($part['number']) && $part['number']) {
                                $savPart = BimpCache::findBimpObjectInstance('bimpsupport', 'BS_ApplePart', array(
                                            'id_sav'      => (int) $sav->id,
                                            'part_number' => $part['number']
                                ));
                            }

                            if (BimpObject::objectLoaded($savPart)) {
                                if (!(float) $part['new_price']) {
                                    $savPart->set('out_of_warranty', 0);
                                } else {
                                    $savPart->set('out_of_warranty', 1);
                                    $savPart->setPrice((float) $part['new_price']);
                                }

                                $part_warnings = array();
                                $part_errors = $savPart->update($part_warnings, true);

                                if (count($part_errors)) {
                                    $warnings[] = BimpTools::getMsgFromArray($part_errors, 'Echec de la mise à jour du prix pour le composant "' . $part['number'] . '"');
                                } else {
                                    $success .= 'Mise à jour du composant "' . $part['number'] . '" effectuée avec succès<br/>';
                                }

                                if (count($part_warnings)) {
                                    $warnings[] = BimpTools::getMsgFromArray($part_errors, 'Erreurs lors de la mise à jour du prix pour le composant "' . $part['number'] . '"');
                                }
                            } else {
                                BimpObject::loadClass('bimpsupport', 'BS_ApplePart');
                                $part_type = BS_ApplePart::getCategProdApple(isset($part['number']) ? $part['number'] : '', isset($part['desc']) ? $part['desc'] : '');
                                $pu_ht = (float) BS_ApplePart::convertPrixStatic($part_type, (float) $part['new_price'], $part['number']);

                                $desc = (isset($part['number']) && $part['number'] ? $part['number'] : '');
                                if (isset($part['desc']) && $part['desc']) {
                                    $desc .= ($desc ? ' - ' : '') . $part['desc'];
                                }

                                $id_line = (int) $this->db->getValue('propaldet', 'rowid', '`fk_propal` = ' . (int) $propal->id . ' AND `description` LIKE \'' . $desc . '\'');
                                if ($id_line > 0) {
                                    $propalLine = BimpCache::findBimpObjectInstance('bimpsupport', 'BS_SavPropalLine', array(
                                                'id_line' => (int) $id_line
                                    ));
                                    if ($propalLine->isLoaded()) {
                                        $propalLine->validateArray(array(
                                            'type'            => BS_SavPropalLine::LINE_FREE,
                                            'deletable'       => 1,
                                            'editable'        => 0,
                                            'out_of_warranty' => ($pu_ht > 0) ? 1 : 0,
                                            'remisable'       => 0
                                        ));


                                        $propalLine->pa_ht = $part['new_price'];
                                        $propalLine->pu_ht = $pu_ht;
                                        $propalLine->tva_tx = 20;
                                        $propalLine->qty = 1;
                                        $propalLine->update();
                                    }
                                } else {
                                    // Ajout au devis (pièce absente du panier) : 

                                    $line = BimpObject::getInstance('bimpsupport', 'BS_SavPropalLine');

                                    $line->validateArray(array(
                                        'id_obj'          => (int) $propal->id,
                                        'type'            => BS_SavPropalLine::LINE_FREE,
                                        'deletable'       => 1,
                                        'editable'        => 0,
                                        'out_of_warranty' => ($part['new_price'] > 0) ? 1 : 0,
                                        'remisable'       => 0
                                    ));


                                    $line->desc = $desc;
                                    $line->qty = 1;
                                    $line->pu_ht = $pu_ht;
                                    $line->pa_ht = (float) $part['new_price'];
                                    $line->tva_tx = 20;

                                    $line_warnings = array();
                                    $line_errors = $line->create($line_warnings, true);

                                    if (count($line_errors)) {
                                        $warnings[] = BimpTools::getMsgFromArray($line_errors, 'Echec de l\'ajout du composant "' . $desc . '"');
                                    } else {
                                        $success .= 'Ajout du composant "' . $desc . '" effectué avec succès<br/>';
                                    }

                                    if (count($line_warnings)) {
                                        $warnings[] = BimpTools::getMsgFromArray($line_warnings, 'Erreurs lors de l\'ajout du composant "' . $desc . '"');
                                    }
                                }
                            }
                        }
                    }
                }
            }
//            $errors = BimpTools::merge_array($errors , $sav->processPropalGarantie());
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    // Actions V1 (obsolètes pour la V2 : ces actions sont traitées dans gsxController): 

    public function actionImportRepair($data, &$success = '')
    {
        $errors = array();
        $success_callback = '';

        $id_sav = (isset($data['id_sav']) ? (int) $data['id_sav'] : 0);
        $number = (isset($data['import_number']) ? $data['import_number'] : '');
        $number_type = (isset($data['import_number_type']) ? $data['import_number_type'] : '');

        if (!$id_sav) {
            $errors[] = 'ID du SAV absent';
        }

        if (!$number) {
            $errors[] = 'Aucun identifiant renseigné';
        }

        if (!$number_type) {
            $errors[] = 'Type de l\'identifiant absent';
        }

        if (!count($errors)) {
            $success = 'Réparation importée avec succès';
            $errors = $this->import($id_sav, $number, $number_type);
        }

        return array(
            'errors'           => $errors,
            'success_callback' => $success_callback
        );
    }

    public function actionEndRepair($data, &$success)
    {
        if ((int) $this->getData('canceled')) {
            $success = 'Cette réparation est annulée';

            return 1;
        } else {
            $success = 'Le statut de la réparation a été mis à jour avec succès';

            return $this->updateStatus();
        }
    }

    public function actionMarkRepairAsReimbursed($data, &$success)
    {
        $success = 'La réparation a bien été marquée comme remboursée';

        if ((int) $this->getData('reimbursed')) {
            return array('Cette réparation est déjà marquée comme remboursée');
        }

        $this->set('reimbursed', 1);
        return $this->update();
    }

    public function actionCloseRepair($data, &$success)
    {
        $checkRepair = isset($data['check_repair']) ? (int) $data['check_repair'] : 0;
        $success = 'Réparation fermée avec succès';

        return $this->close(true, $checkRepair);
    }

    public function actionCancelRepair($data, &$success)
    {
        if ($this->isLoaded()) {
            $success_callback = '';
            $success = 'Réparation marqué annulée';
            $errors = $this->updateField('canceled', "1");

            return array(
                'errors'           => $errors,
                'success_callback' => $success_callback
            );
        }
        return 0;
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $serial = (string) $this->getData('serial');
        if (!$serial) {
            $sav = $this->getChildObject('sav');
            if (BimpObject::objectLoaded($sav)) {
                $serial = $sav->getSerial();
            }
        }

        if (!$serial) {
            return array('N° de série absent');
        }

        $this->setSerial($serial);

        if (!$this->use_gsx_v2) {
            $this->set('total_from_order', '1,2'); //Pour qu'il y est forcément un changement aprés
        }

        $errors = parent::create($warnings, $force_create);

        if (!count($errors)) {
            $errors = $this->lookup();
        }

        return $errors;
    }

    public function fetch($id, $parent = null)
    {
        //$this->gsx->errors['soap'] = array();
        if (parent::fetch($id, $parent)) {
            $this->setSerial($this->getData('serial'));
            $this->lookup();
            return true;
        }
        return false;
    }
}
