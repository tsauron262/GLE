<?php

require_once DOL_DOCUMENT_ROOT . '/bimpsupport/objects/BS_SAV.class.php';

class BS_SAV_ExtEntity extends BS_SAV{
    public static $status_ecologic_list = array(
        -1          => array('label' => 'Non Applicable', 'icon' => 'fas_not-equal', 'classes' => array('important')),
        0           => array('label' => 'En attente', 'icon' => 'fas_times', 'classes' => array('danger')),
        1           => array('label' => 'Attente déclaration', 'icon' => 'fas_times', 'classes' => array('danger')),
        99          => array('label' => 'Déclarré', 'icon' => 'arrow-right', 'classes' => array('danger'))
    );
    
    public function actionClose($data, &$success){
        $return = parent::actionClose($data, $success);
        if(!count($return['errors'])){
            if($this->asProdEcologic()){
                $this->updateField('status_ecologic',1);
            }
            else{
                $this->updateField('status_ecologic',-1);
            }
        }
        return $return;
    }
    
    public function asProdEcologic(){
        $asProd = false;
        $tabIdProd = json_decode(BimpCore::getConf('prod_ecologic', '', 'bimpsupport'));
        if(is_array($tabIdProd)){
            foreach ($this->getPropalLines() as $line) {
                $dolLine = $line->getChildObject('line');
                if(in_array($dolLine->fk_product, $tabIdProd))
                        $asProd = true;
            }
        }
        return $asProd;
    }
    
    public function getIRISSymtoms($type = null){
        require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';

        $api = BimpAPI::getApiInstance('ecologic');
        
        $result = $api->executereqWithCache('printproducttypewithlabellist');
        
        $resultList = array();
        foreach($result['ResponseData'] as $typeMat){
            if(is_null($type) || $typeMat['ProductId'] == $type){
                foreach($typeMat['IRISSymtoms'] as $val)
                    $resultList[$val['Code']] = $val['Label'];
            }
        }
        return $resultList;
    }
    
    public function getRepairCodes($type = null){
        require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';

        $api = BimpAPI::getApiInstance('ecologic');
        
        $result = $api->executereqWithCache('printproducttypewithlabellist');
        
        $resultList = array();
        foreach($result['ResponseData'] as $typeMat){
            if(is_null($type) || $typeMat['ProductId'] == $type){
                foreach($typeMat['RepairCodes'] as $val)
                    $resultList[$val['Code']] = $val['Label'];
            }
        }
        return $resultList;
    }
    
    public function getEcologicProductId(){
        $label = $this->getEquipmentData('product_label');
        if(stripos($label, 'mac') !== false)
            return 'EEE.M2.044';
        if(stripos($label, 'ipad') !== false)
            return 'EEE.M2.057';
        if(stripos($label, 'iphone') !== false)
            return 'EEE.M6.060';
        
        
        return '';
    }
    public function actionToRestitute($data, &$success) {
        $datas = $this->getData('ecologic_data');
        $datas['IRISSymtoms'] = $data['IRISSymtoms'];
        $datas['RepairCodes'] = $data['RepairCodes'];
        $this->updateField('ecologic_data', 99);
        
        
        return parent::actionToRestitute($data, $success);
    }
    
    
    public function actionSendDemandeEcologic($data, &$success){
        $this->useNoTransactionsDb();
        $errors = $warnings = array();
        $success = 'Demande envoyée';
        $data = array();
        $client = $this->getChildObject('client');
        $nomClient = $client->getData('nom');
        $tabName = explode(' ', $nomClient);
        $data['Consumer'] = array(
            "Title"=> 1,
            "LastName"=> $tabName[0],
            "FirstName"=> $tabName[1],
            "StreetNumber"=> '',
            "Address1"=> $client->getData('address'),
            "Address2"=> "",
            "Address3"=> "",
            "Zip"=> $client->getData('zip'),
            "City"=> $client->getData('town'),
            "Country"=> "250",
            "Phone"=> "",
            "Email"=> "",
            "AutoValidation"=> true
        );
        
        $equipment = $this->getChildObject('equipment');
        $prod = $equipment->getChildObject('product');
        $ecologicData = $this->getData('ecologic_data');
        $facture = $this->getChildObject('facture');
        $ref = '';
        if (BimpObject::objectLoaded($prod)) 
            $ref = $prod->ref;
         
        
        $data['Product'] = array(
            "ProductId"=> $this->getEcologicProductId(),
            "BrandId"=> "243",
            "CommercialRef"=> $prod->ref ,
            "SerialNumber"=> $this->getSerial(),
            "PurchaseDate"=> "",
            "IRISCondition"=> "",
            "IRISConditionEX"=> "",
            "IRISSymptom"=> $ecologicData['IRISSymtoms'],
            "IRISSection"=> $ecologicData['RepairCodes'],
            "IRISDefault"=> "",
            "IRISRepair"=> "",
            "FailureDescription"=> $this->getData('symptomes'),
            "DefectCode"=> ""
        );
        
        $totalSpare = 0;
        $data["SpareParts"] = array();
        foreach ($this->getPropalLines(array(
            'linked_object_name' => 'sav_apple_part'
        )) as $line) {
            if($line->getTotalHT() > 0){
                $data["SpareParts"][] = array(
                    "Partref"=> $line->desc,
                    "Quantity"=> $line->qty,
                    "Status"=> ""
                ); 
                $totalSpare+= $line->getTotalHT();
            }
//            echo '<pre>';
//////            print_r($line->printData());
//            print_r($line->getTotalHT());
        }
        
        if($totalSpare > $facture->getData('total_ht'))
            $totalSpare = $facture->getData('total_ht');
        
        $data["Quote"] = array(
            "LaborCost"=> array(
              "Amount"=> round($facture->getData('total_ht') - $totalSpare,2),
              "Currency"=> "EUR"
            ),
            "SparePartsCost"=> array(
              "Amount"=> round($totalSpare,2),
              "Currency"=> "EUR"
            ),
            "TravelCost"=> array(
              "Amount"=> 0.00,
              "Currency"=> "EUR"
            ),
            "TotalAmountExclVAT"=> array(
              "Amount"=> round($facture->getData('total_ht'),2),
              "Currency"=> "EUR"
            ),
            "TotalAmountInclVAT"=> array(
              "Amount"=> round($facture->getData('total_ttc'),2),
              "Currency"=> "EUR"
            ),
            "SupportAmount"=> array(
              "Amount"=> 25.00,
              "Currency"=> "EUR"
            )
        );
        
        
        require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';

        $api = BimpAPI::getApiInstance('ecologic');
        $params = array();
        $params['fields'] = $data;
        
        
        if(!isset($ecologicData['RequestId'])){//on cré la demande
            $params['url_params'] = array('callDate'=> date("Y-m-d\TH:i:s"), 'repairSiteId'=> $this->getDefaultSiteId(), 'quoteNumber'=> $this->getData('ref'));
            $return = $api->execCurl('createsupportrequest', $params, $errors);
            
            if(isset($return['ResponseData']) && isset($return['ResponseData']['RequestId'])){
                $warnings = BimpTools::merge_array($warnings, $errors);
                $errors = array();
                $ecologicData['RequestId'] = $return['ResponseData']['RequestId'];
                if($return['ResponseData']['IsValid']){
                    $ecologicData['RequestOk'] = true;
                }
            }
        }
        elseif(isset($ecologicData['RequestId'])  && !isset($ecologicData['ClaimId']) && !isset($ecologicData['RequestOk'])){//on update la demande
            $params['url_params'] = array('claimId'/*attention erreur API, ca devrait être RequestId*/ => $ecologicData['RequestId'],'callDate'=> date("Y-m-d\TH:i:s"), 'repairSiteId'=> $this->getDefaultSiteId(), 'quoteNumber'=> $this->getData('ref'));
            $return = $api->execCurl('updatesupportrequest', $params, $errors);
            
            if(isset($return['ResponseData']) && isset($return['ResponseData']['RequestId']) && $return['ResponseData']['IsValid']){
                $ecologicData['RequestOk'] = true;
            }
            
            
        }
        
        if(isset($ecologicData['RequestId']) && isset($ecologicData['RequestOk']) && $ecologicData['RequestOk'] && !isset($ecologicData['ClaimId'])){//on créer le claim
            $params['url_params'] = array('RequestId' => $ecologicData['RequestId'], 'RepairEndDate' => date("Y-m-d\TH:i:s", strtotime($this->getData('date_close'))), 'ConsumerInvoiceNumber'=>$facture->getData('ref'), 'repairSiteId'=> $this->getDefaultSiteId(), 'quoteNumber'=> $this->getData('ref'));
            $return = $api->execCurl('createclaim', $params, $errors);
            if(isset($return['ResponseData']) && isset($return['ResponseData']['ClaimId'])){
                $warnings = BimpTools::merge_array($warnings, $errors);
                $errors = array();
                $ecologicData['ClaimId'] = $return['ResponseData']['ClaimId'];
            }
        }
        
        //enregistrement avant les fichiers au cas ou....
        $this->updateField('ecologic_data', $ecologicData);
        
        if(isset($ecologicData['RequestId']) && isset($ecologicData['ClaimId'])){
            $tabFile = array();
            
            $tabFile[] = array($facture->getFilesDir(), $facture->getData('ref'), 'pdf', 'INVOICE');
            $tabFile[] = array($this->getFilesDir(), 'Restitution_'.$this->getData('ref').'_signe', 'pdf', 'CONSUMERVALIDATION');
            $tabFile[] = array($this->getFilesDir(), 'Plaque', 'pdf', 'NAMEPLATE');
            
            $filesOk = true;
            foreach($tabFile as $fileT){
                if(!is_file($fileT[0] . $fileT[1].'.'.$fileT[2])){
                    $errors[] = 'Fichier : '.$fileT[0] . $fileT[1].'.'.$fileT[2].' introuvable';
                    BimpCore::addlog ('Fichier : '.$fileT[0] . $fileT[1].'.'.$fileT[2].' introuvable');
                    $filesOk = false;
                }
            }
            
            if($filesOk){
                foreach($tabFile as $fileT){
                    if(!isset($ecologicData['files']) || !in_array($fileT[1], $ecologicData['files'])){
                        $paramsFile = array();
                        $paramsFile['fields']['FileContent'] = base64_encode(file_get_contents($fileT[0] . $fileT[1].'.'.$fileT[2]));
                        $paramsFile['url_params'] = array('ClaimId' => $ecologicData['ClaimId'], 'FileName' => $fileT[1].'.'.$fileT[2], 'FileExtension' => $fileT[2], 'DocumentType' => $fileT[3]);
                        $return = $api->execCurl('AttachFile', $paramsFile, $errors);
                        if(stripos($return, 'Code 200') !== false){
//                        if(isset($return['ResponseData']) && $return['ResponseData']['IsValid']){
                            $ecologicData['files'][] = $fileT[1];
                            //enregistrement pendant les fichiers, au cas ou...
                            $this->updateField('ecologic_data', $ecologicData);
                            $warnings = BimpTools::merge_array($warnings, $errors);
                            $errors = array();
                        }
                        else{
                            $filesOk = false;
                        }
                    }
                }
            }
        }
        
        
        
        if(isset($ecologicData['RequestId']) && isset($ecologicData['ClaimId']) && $filesOk){
            $warnings = array();//Tout semble ok, on vire les ancinne erreur de fichier qui sont résolu entre temps
            $params['url_params'] = array('ClaimId' => $ecologicData['ClaimId'], 'RepairEndDate' => date("Y-m-d\TH:i:s", strtotime($this->getData('date_close'))), 'ConsumerInvoiceNumber'=>$facture->getData('ref'), 'repairSiteId'=> $this->getDefaultSiteId(), 'quoteNumber'=> $this->getData('ref'), 'Submit' => 'true');
            $return = $api->execCurl('updateclaim', $params, $errors);
            
            if(isset($return['ResponseStatus']) && $return['ResponseStatus'] == "S" && isset($return['ResponseData']) && $return['ResponseData']['IsValid'])
                $this->updateField('status_ecologic', 99);
        }
        else{
            if(!isset($ecologicData['ClaimId']))
                $errors[] = 'Demande non créer';
            elseif(!$filesOk)
                $errors[] = 'Les fichiers ne sont pas ou partielement envoyées';
            else
                $errors[] = 'Erreur inconnue';
        }
        
        
        
//            $ecologicData['EcoOrganizationId'] = $return['ResponseData']['EcoOrganizationId'];
        $this->updateField('ecologic_data', $ecologicData);
        
        
        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
    
    public function getDefaultSiteId()
    {
        global $tabCentre;
        if (isset($tabCentre[$this->getData('code_centre')]) && isset($tabCentre[$this->getData('code_centre')][11]))
            return $tabCentre[$this->getData('code_centre')][11];
    }
    
    
    public function getViewExtraBtn() {
        $btn = parent::getViewExtraBtn();
        if($this->asProdEcologic() && $this->getStatus() == 999 && $this->getData('status_ecologic') == 1){
            $btn[] = array(
                    'label'   => 'Envoyée a Ecologic',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('sendDemandeEcologic', array(), array(
//                        'form_name' => 'cancel_rdv'
                    ))
                );
        }
        return $btn;
    }
}
