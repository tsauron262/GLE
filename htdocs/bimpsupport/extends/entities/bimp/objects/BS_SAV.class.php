<?php

require_once DOL_DOCUMENT_ROOT . '/bimpsupport/objects/BS_SAV.class.php';

class BS_SAV_ExtEntity extends BS_SAV
{

    public static $status_ecologic_list = array(
        -2   => array('label' => 'Refusée', 'icon' => 'fas_not-equal', 'classes' => array('important')),
        -1   => array('label' => 'Non Applicable', 'icon' => 'fas_not-equal', 'classes' => array('important')),
        0    => array('label' => 'En attente', 'icon' => 'fas_times', 'classes' => array('danger')),
        1    => array('label' => 'Attente déclaration', 'icon' => 'fas_times', 'classes' => array('danger')),
        99   => array('label' => 'Déclarré', 'icon' => 'arrow-right', 'classes' => array('danger')),
        1000 => array('label' => 'Payée', 'icon' => 'arrow-right', 'classes' => array('success'))
    );

    public function actionClose($data, &$success)
    {
        $return = parent::actionClose($data, $success);
        if (!count($return['errors'])) {
            if ($this->asProdEcologic()) {
                $this->updateField('status_ecologic', 1);
            } else {
                $this->updateField('status_ecologic', -1);
            }
        }

        //si cote 45 et montant inférieur 180 - 45 = 135 erreur montant non éligible

        return $return;
    }

    public function asProdEcologic()
    {
        $asProd = false;
        $tabIdProd = json_decode(BimpCore::getConf('prod_ecologic', '', 'bimpsupport'));
        if (is_array($tabIdProd)) {
            foreach ($this->getPropalLines() as $line) {
                $dolLine = $line->getChildObject('line');
                if (in_array($dolLine->fk_product, $tabIdProd) && $dolLine->qty > 0)
                    $asProd = true;
            }
        }
        return $asProd;
    }

    public function findEcologicSupportAmount()
    {
        $tabIdProd = json_decode(BimpCore::getConf('prod_ecologic', '', 'bimpsupport'));
        if (is_array($tabIdProd)) {
            foreach ($this->getPropalLines() as $line) {
                $dolLine = $line->getChildObject('line');
                if (in_array($dolLine->fk_product, $tabIdProd) && $dolLine->qty > 0) {
//                    print_r($dolLine);die;
                    return -$dolLine->total_ttc /** 1.2*/;
                }
            }
        }
        return 0;
    }

    public function getIRISSymtoms($type = null)
    {
        require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';

        $api = BimpAPI::getApiInstance('ecologic');

        $result = $api->executereqWithCache('printproducttypewithlabellist');

        $resultList = array();

        if (isset($result['ResponseData']) && !empty($result['ResponseData'])) {
            foreach ($result['ResponseData'] as $typeMat) {
                if (is_null($type) || $typeMat['ProductId'] == $type) {
                    foreach ($typeMat['IRISSymtoms'] as $val)
                        $resultList[$val['Code']] = $val['Label'];
                }
            }
        }

        return $resultList;
    }

    public function getRepairCodes($type = null)
    {
        require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';

        $api = BimpAPI::getApiInstance('ecologic');

        $result = $api->executereqWithCache('printproducttypewithlabellist');

        $resultList = array();
        if (isset($result['ResponseData']) && !empty($result['ResponseData'])) {
            foreach ($result['ResponseData'] as $typeMat) {
                if (is_null($type) || $typeMat['ProductId'] == $type) {
                    foreach ($typeMat['RepairCodes'] as $val)
                        $resultList[$val['Code']] = $val['Label'];
                }
            }
        }

        return $resultList;
    }

    public function getEcologicProductId()
    {
        $label = $this->getEquipmentData('product_label');

        $equipement = $this->getChildObject('equipment');
        if ((int) $equipement->getData('id_product')) {
            $label .= $equipement->displayProduct('nom') . '<br/>';
        }
        if (stripos($label, 'mac') !== false)
            return 'EEE.M2.044';
        if (stripos($label, 'ipad') !== false)
            return 'EEE.M2.057';
        if (stripos($label, 'iphone') !== false)
            return 'EEE.M6.060';


        return '';
    }

    public function actionToRestitute($data, &$success)
    {
        $datas = $this->getData('ecologic_data');
        $datas['IRISSymtoms'] = $data['IRISSymtoms'];
        $datas['RepairCodes'] = $data['RepairCodes'];
        $this->updateField('ecologic_data', $datas);

        return parent::actionToRestitute($data, $success);
    }

    public function actionCodeEcologic($data, &$success)
    {
        $success = 'Ok';
        $datas = $this->getData('ecologic_data');
        $datas['IRISSymtoms'] = $data['IRISSymtoms'];
        $datas['RepairCodes'] = $data['RepairCodes'];
        $this->updateField('ecologic_data', $datas);

        return array('errors' => array(), 'warnings' => array());
    }

    public function traiteVilleNameEcologic($name)
    {
        $tabName = explode('1', $name);
        $name = trim($tabName[0]);
        $tabName = explode('2', $name);
        $name = trim($tabName[0]);
        $tabName = explode('3', $name);
        $name = trim($tabName[0]);
        $tabName = explode('4', $name);
        $name = trim($tabName[0]);
        $tabName = explode('5', $name);
        $name = trim($tabName[0]);
        $tabName = explode('6', $name);
        $name = trim($tabName[0]);
        $tabName = explode('7', $name);
        $name = trim($tabName[0]);
        $tabName = explode('8', $name);
        $name = trim($tabName[0]);
        $tabName = explode('9', $name);
        $name = trim($tabName[0]);
        $name = str_replace('-', ' ', $name);
        $name = str_replace('\'', ' ', $name);
        $name = str_replace('ç', 'c', $name);
        $name = str_replace('é', 'e', $name);
        $name = str_replace('è', 'e', $name);
        $name = str_replace('É', 'E', $name);
        $name = str_replace('â', 'a', $name);
        $name = str_replace('ê', 'e', $name);
        $name = str_replace('ô', 'o', $name);
        $name = str_replace('Saint', 'St', $name);
//        $name = ucfirst(strtolower($name));
        return $name;
    }
    
    public function renderHeaderExtraLeft() {
        $html = parent::renderHeaderExtraLeft();
        
        $client = $this->getChildObject('client');
        $nomClient = str_replace('  ', ' ', $client->getData('nom'));
        $tabName = explode(' ', $nomClient);
        if((!isset($tabName[1]) || $tabName[1] == '' || $client->getData('fk_typent') != 8) && $this->asProdEcologic()){
            $html .= BimpRender::renderAlerts('! Attention les PROS ne sont pas concernés par le programme QualiRepair !');
        }
        return $html;
    }

    public function actionSendDemandeEcologic($data, &$success)
    {
        $this->useNoTransactionsDb();
        $errors = $warnings = array();
        $success = 'Demande envoyée';
        $data = array();
        $client = $this->getChildObject('client');
        $nomClient = str_replace('  ', ' ', $client->getData('nom'));
        $tabName = explode(' ', $nomClient);
        $data['Consumer'] = array(
            "Title"          => 1,
            "LastName"       => $tabName[0],
            "FirstName"      => $tabName[1],
            "StreetNumber"   => '',
            "Address1"       => $client->getData('address'),
            "Address2"       => "",
            "Address3"       => "",
            "Zip"            => $client->getData('zip'),
            "City"           => $this->traiteVilleNameEcologic($client->getData('town')),
            "Country"        => "250",
            "Phone"          => "",
            "Email"          => "",
            "AutoValidation" => true
        );

        $equipment = $this->getChildObject('equipment');
        $prod = $equipment->getChildObject('product');
        $ecologicData = $this->getData('ecologic_data');
        $facture = $this->getChildObject('facture');
        $ref = '';
        if (BimpObject::objectLoaded($prod))
            $ref = $prod->ref;


        $data['Product'] = array(
            "ProductId"          => $this->getEcologicProductId(),
            "BrandId"            => "243",
            "CommercialRef"      => $prod->ref,
            "SerialNumber"       => $this->getSerial(true),
            "PurchaseDate"       => "",
            "IRISCondition"      => "",
            "IRISConditionEX"    => "",
            "IRISSymptom"        => $ecologicData['IRISSymtoms'],
            "IRISSection"        => $ecologicData['RepairCodes'],
            "IRISDefault"        => "",
            "IRISRepair"         => "",
            "FailureDescription" => $this->getData('symptomes'),
            "DefectCode"         => ""
        );

        $totalSpare = 0;
        $data["SpareParts"] = array();
        foreach ($this->getPropalLines(array(
            'linked_object_name' => 'sav_apple_part'
        )) as $line) {
            if ($line->getTotalHT() > 0) {
                $data["SpareParts"][] = array(
                    "Partref"  => $line->desc,
                    "Quantity" => $line->qty,
                    "Status"   => ""
                );
                $totalSpare += $line->getTotalHT();
            }
//            echo '<pre>';
//////            print_r($line->printData());
//            print_r($line->getTotalHT());
        }

        if ($totalSpare > $facture->getData('total_ht'))
            $totalSpare = $facture->getData('total_ht');

        $prime = $this->findEcologicSupportAmount();

        $data["Quote"] = array(
            "LaborCost"          => array(
                "Amount"   => round($facture->getData('total_ht') - $totalSpare, 2),
                "Currency" => "EUR"
            ),
            "SparePartsCost"     => array(
                "Amount"   => round($totalSpare + ($prime /*/ 1.2*/), 2),
                "Currency" => "EUR"
            ),
            "TravelCost"         => array(
                "Amount"   => 0.00,
                "Currency" => "EUR"
            ),
            "TotalAmountExclVAT" => array(
                "Amount"   => round($facture->getData('total_ht') + ($prime /*/ 1.2*/), 2),
                "Currency" => "EUR"
            ),
            "TotalAmountInclVAT" => array(
                "Amount"   => round($facture->getData('total_ttc') + $prime, 2),
                "Currency" => "EUR"
            ),
            "SupportAmount"      => array(
                "Amount"   => $prime,
                "Currency" => "EUR"
            )
        );

        require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';

        $api = BimpAPI::getApiInstance('ecologic');

        $tabFile = array();

        $tabFile[] = array($facture->getFilesDir(), $facture->getData('ref'), 'pdf', 'INVOICE');
        $tabFile[] = array($this->getFilesDir(), 'Restitution_' . $this->getData('ref') . '_signe', 'pdf', 'CONSUMERVALIDATION');
        $tabFile[] = array($this->getFilesDir(), 'infos_materiel', 'pdf', 'NAMEPLATE');

        $api->traiteReq($errors, $warnings, $data, $ecologicData, $this->getDefaultSiteId(), $this->getData('ref'), $tabFile, date("Y-m-d\TH:i:s", strtotime($this->getData('date_close'))), $facture->getData('ref'), $this);

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

    public function getViewExtraBtn()
    {
        $btn = parent::getViewExtraBtn();
        if ($this->asProdEcologic() && in_array($this->getData('status_ecologic'), array(1, 0))) {
            if ($this->getStatus() == 999)
                $btn[] = array(
                    'label'   => 'Envoyée a Ecologic',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('sendDemandeEcologic', array(), array())
                );
            if (in_array($this->getStatus(), array(999, 9)))
                $btn[] = array(
                    'label'   => 'Codes Ecologic',
                    'icon'    => 'fas_times',
                    'onclick' => $this->getJsActionOnclick('codeEcologic', array(), array(
                        'form_name' => 'ecologic'
                    ))
                );
        }
        return $btn;
    }

    public function getListHeaderExtraBtn()
    {
        $btn[] = array(
            'label'   => 'Ecologic Payée',
            'icon'    => 'fas_times',
            'onclick' => $this->getJsActionOnclick('ecologicPaye', array(), array(
                'form_name' => 'ecologicPaye'
            ))
        );
        return $btn;
    }

    public function actionEcologicPaye($data, &$success)
    {
        global $db;
        $success = 'Ok';
        $errors = $warnings = array();

        $tmp = $data['ecologicPaye'];
        if (strlen($tmp) < 4)
            $errors['Saisir les numéros'];
        else {
            $tmp = str_replace(' ', ',', $tmp);
            $nums = explode(',', $tmp);
            foreach ($nums as $num) {
                $sql = $db->query("SELECT a.id
FROM llx_bs_sav a
WHERE a.ecologic_data LIKE '%\"ClaimId\":".trim($num)."%'");
                if($db->num_rows($sql) < 1){
                    $errors[] = 'Code : ' . $num . ' introuvable';
                }
                elseif($db->num_rows($sql) > 1){
                    $errors[] = 'Code : ' . $num . ' trouvé plusieurs fois';
                }
                else{
                    $ln = $db->fetch_object($sql);
                    $db->query('UPDATE llx_bs_sav SET `status_ecologic` = "1000"
WHERE `id` = '.$ln->id);
                }
//                $sav = BimpCache::findBimpObjectInstance('bimpsupport', 'BS_SAV', array('ecologic_data' => array('operator' => 'LIKE', 'value' => '%"ClaimId":' . trim($num) . '%')));
//                if (!$sav || !$sav->isLoaded())
//                    $errors[] = 'Code : ' . $num . ' introuvable';
//                else {
//                    $sav->updateField('status_ecologic', 1000);
//                }
            }
        }


        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }
}
