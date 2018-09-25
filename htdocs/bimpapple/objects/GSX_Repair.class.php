<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/classes/GSX.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpObject.php';

class GSX_Repair extends BimpObject
{

    public $partsPending = array();
    public $repairLookUp = array();
    public $isIphone = false;
    public $totalFromOrderChanged = false;
    public $gsx = null;
    public static $lookupNumbers = array(
        'serialNumber'             => 'Numéro de série',
        'repairNumber'             => 'Numéro de réparation (repair #)',
        'repairConfirmationNumber' => 'Numéro de confirmation (Dispatch ID)',
        'purchaseOrderNumber'      => 'Numéro de bon de commande (Purchase Order)',
        'imeiNumber'               => 'Numéro IMEI (IPhone)'
    );
    public static $repairStatusCodes = array(
        'RFPU' => 'Ready For Pick Up',
        'AWTP' => 'Awaiting Parts',
        'AWTR' => 'Parts allocated',
        'BEGR' => 'In Repair',
    );
    public static $repairTypes = array(
        'carry_in', 'repair_or_replace'
    );

    public function setGSX($gsx)
    {
        $this->gsx = $gsx;
    }

    public function setSerial($serial)
    {
        if (preg_match('/^[0-9]{15,16}$/', $serial))
            $this->isIphone = true;
        else
            $this->isIphone = false;

        $this->set('serial', $serial);

        if ($this->isLoaded()) {
            $this->update();
        }
    }

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

    public function import($id_sav, $number, $numberType)
    {
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

        if (is_null($this->gsx)) {
            $this->gsx = new GSX($this->isIphone);
        }

        if (!$this->gsx->connect) {
            return array('Echec de la connexion à GSX');
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
        if (is_null($this->gsx)) {
            $this->gsx = new GSX($this->isIphone);
        }

        if (!$this->gsx->connect) {
            return array('Echec de la connexion à GSX');
        }

        $this->partsPending = array();

        $this->gsx->resetSoapErrors();

        $repairNumber = $this->getData('repair_number');
        $repairConfirmNumber = $this->getData('repair_confirm_number');

        $data = array(
            'repairType'               => '',
            'repairStatus'             => '',
            'purchaseOrderNumber'      => '',
            'sroNumber'                => isset($repairNumber) ? $repairNumber : '',
            'repairConfirmationNumber' => isset($repairConfirmNumber) ? $repairConfirmNumber : '',
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
                    $fileName = "label_" . $part['returnOrderNumber'] . "-". $i . ".pdf";
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

    public function lookup($number = null, $number_type = null)
    {
        if (is_null($this->gsx)) {
            $this->gsx = new GSX($this->isIphone);
        }

        if (!$this->gsx->connect) {
            return array('Echec de la connexion à GSX');
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

        if (is_null($number)) {
            $repairNumber = $this->getData('repair_number');
            $repairConfirmNumber = $this->getData('repair_confirm_number');
            if (!is_null($repairNumber) && $repairNumber) {
                $look_up_data['repairNumber'] = $repairNumber;
            }
            if (!is_null($repairConfirmNumber) && $repairConfirmNumber) {
                $look_up_data['repairConfirmationNumber'] = $repairConfirmNumber;
            }
        }

        if ($this->isIphone) {
            $look_up_data['imeiNumber'] = '';
        }

        if (isset($number) && $number && isset($number_type) && $number_type) {
            if (isset($look_up_data[$number_type])) {
                $look_up_data[$number_type] = $number;
            }
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

        if (count($this->gsx->errors['soap']) > $n_soap_errors) {
            return $this->gsx->errors['soap'];
        } else if (!isset($response[$client . 'Response']['lookupResponseData'])) {
            return array('Echec de la requête "lookup" pour une raison inconnue');
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

    public function updateStatus($status = 'RFPU')
    {
        if (!$this->isLoaded()) {
            $errors = $this->load();
            if (count($errors)) {
                return $errors;
            }
        }

        if ((int) $this->getData('repair_complete')) {
            return array('Cette réparation a déjà été fermée.');
        }

        $repair_confirm_number = $this->getData('repair_confirm_number');
        if (is_null($repair_confirm_number) || !$repair_confirm_number) {
            return array('Erreur: n° de confirmation de la réparation absent');
        }

        if ($status === 'RFPU') {
            if ((int) $this->getData('ready_for_pick_up')) {
                return array();
            }
        } else if (!array_key_exists($status, self::$repairStatusCodes)) {
            return array('Statut de la réparation à mettre à jour invalide (' . $status . ')');
        }

        if (is_null($this->gsx)) {
            $this->gsx = new GSX($this->isIphone);
        }

        if (!$this->gsx->connect) {
            return array('Echec de la connexion à GSX');
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
                if ($this->isIphone) 
                    $this->gsx = new GSX(false);//force not iphone
                $client = 'CarryInRepairUpdate';
                $requestName = 'UpdateCarryInRequest';
                $data['statusCode'] = $status;
                $clientRep = 'UpdateCarryIn' . 'Response';
                break;
            case 'repair_or_replace':
                $data['repairStatusCode'] = $status;
                if ($this->isIphone) {
                    $client = 'IPhoneUpdateRepairOrReplaceRequest';
                    $requestName = 'IPhoneUpdateRepairOrReplaceRequest';
                } else {
                    $client = 'UpdateRepairOrReplace';
                    $requestName = 'UpdateRepairOrReplaceRequest';
                }
                $data['repairStatusCode'] = $status;
                $clientRep = $client . 'Response';
                break;
        }

        $errors = array();

        $request = $this->gsx->_requestBuilder($requestName, 'repairData', $data);
        $response = $this->gsx->request($request, $client);

        if (count($this->gsx->errors['soap']) > $n_soap_errors) {
            $errors[] = 'Echec de la requête "' . $requestName . '"';
            $errors = array_merge($errors, $this->gsx->errors['soap']);
        }

        if (!isset($response[$clientRep]['repairConfirmation'])) {
            $errors[] = 'Echec de la requête "' . $requestName . '"';
        }

        if (!count($errors)) {
            if ($status === 'RFPU') {
                $this->set('ready_for_pick_up', 1);
                $this->update();
            }
        }

        return $errors;
    }

    public function repairDetails($force_repair_update = false)
    {
        if (!$this->isLoaded()) {
            $errors = $this->load();
            if (count($errors)) {
                return $errors;
            }
        }

        if (is_null($this->gsx)) {
            $this->gsx = new GSX($this->isIphone);
        }

        if (!$this->gsx->connect) {
            return array('Echec de la connexion à GSX');
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
            $errors = array_merge($errors, $this->gsx->errors['soap']);
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
                        $sav->addNote('Mise à jour du numéro de série de l\'équipement effectué le '.date('d / m / Y à H:i'));
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

    public function close($sendRequest = true, $checkRepair = 1)
    {
        if (!$this->isLoaded()) {
            $errors = $this->load();
            if (count($errors)) {
                return $errors;
            }
        }

        if (is_null($this->gsx)) {
            $this->gsx = new GSX($this->isIphone);
        }

        if (!$this->gsx->connect) {
            return array('Echec de la connexion à GSX');
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
                return array('Echec de la requête de fermeture de la réparation');
            }
        }

        $this->set('repair_complete', 1);
        $this->set('closed', 1);
        $this->set('data_closed', date('Y-m-d'));

        $update_errors = $this->update();
        if (count($update_errors)) {
            $errors[] = 'Echec de l\'enregistrement de la fermeture de la réparation en base de données';
            return array_merge($errors, $update_errors);
        }

        return $this->repairDetails();
    }

    // Affichages:

    public function displayGsxStatus()
    {
        if (isset($this->repairLookUp['repairStatus'])) {
            return $this->repairLookUp['repairStatus'];
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

    // Rendus HTML: 

    public function renderActions()
    {
        $buttons = array();

        $id_sav = (int) $this->getData('id_sav');
        $callback = 'function() {reloadRepairsViews(' . $id_sav . ');}';

        if (!(int) $this->getData('ready_for_pick_up')) {
            $confirm = 'Attention, la réparation va être marquée &quote;Ready For Pick up&quote; (prête pour enlèvement) auprès du service GSX d\\\'Apple. Veuillez confirmer';
            $buttons[] = array(
                'label'   => 'Terminer la réparation',
                'classes' => array('btn', 'btn-danger'),
                'attr'    => array(
                    'onclick' => $this->getJsActionOnclick('endRepair', array(), array(
                        'result_container' => '$(\'#repair_' . $this->id . '_result\')',
                        'success_callback' => $callback,
                        'confirm_msg'      => $confirm
                    ))
                )
            );
        } elseif (!(int) $this->getData('repair_complete')) {
            $confirm = 'Attention, la réparation va être indiquée comme complète auprès du service GSX d\\\'Apple. Veuillez confirmer';
            $buttons[] = array(
                'label'   => 'Restituer',
                'classes' => array('btn', 'btn-danger'),
                'attr'    => array(
                    'onclick' => $this->getJsActionOnclick('closeRepair', array('check_repair' => 1), array(
                        'result_container' => '$(\'#repair_' . $this->id . '_result\')',
                        'success_callback' => $callback,
                        'confirm_msg'      => $confirm
                    ))
                )
            );
        } elseif (!(int) $this->getData('reimbursed')) {
            $confirm = 'Veuillez confirmer';
            $buttons[] = array(
                'label'   => 'Marquer comme remboursée',
                'classes' => array('btn', 'btn-default'),
                'attr'    => array(
                    'onclick' => $this->getJsActionOnclick('markRepairAsReimbursed', array(), array(
                        'result_container' => '$(\'#repair_' . $this->id . '_result\')',
                        'success_callback' => $callback,
                        'confirm_msg'      => $confirm
                    ))
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

        $html = '';
        if (!count($this->partsPending)) {
            $errors = $this->loadPartsPending();
            if (count($errors)) {
                $html .= BimpRender::renderAlerts($errors);
            } else {
                if (count($this->gsx->errors['soap'])) {
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
                $html .= '<td>' . ($part['fileName'] != "" ? '<a class="btn btn-default" target="_blank" href="' . DOL_URL_ROOT . $part['fileName'] . '"><i class="fa fa-file-text iconLeft"></i>Etiquette</a>' : '') . '</td>';
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

    // Actions: 

    public function actionImportRepair($data, &$success)
    {
        $success = 'Réparation importée avec succès';

        return $this->import($data['id_sav'], $data['import_number'], $data['import_number_type']);
    }

    public function actionEndRepair($data, &$success)
    {
        $success = 'Le statut de la réparation a été mis à jour avec succès';

        return $this->updateStatus();
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

    // Overrides: 

    public function create()
    {
        $serial = (string) $this->getData('serial');
        if (!$serial) {
            $sav = $this->getChildObject('sav');
            if (!is_null($sav) && $sav->isLoaded()) {
                $equipment = $sav->getChildObject('equipment');
                if (!is_null($equipment) && $equipment->isLoaded()) {
                    $serial = $equipment->getData('serial');
                }
            }
        }

        if (!$serial) {
            return array('N° de série absent');
        }

        $this->setSerial($serial);
        $this->set('total_from_order', '1,2');//Pour qu'il y est forcément un changement aprés

        $errors = parent::create();

        if (!count($errors)) {
            $errors = $this->lookup();
        }

        return $errors;
    }

    public function fetch($id, $parent = null)
    {
        if (parent::fetch($id, $parent)) {
            $this->setSerial($this->getData('serial'));
            $this->lookup();
            return true;
        }
        return false;
    }
}
