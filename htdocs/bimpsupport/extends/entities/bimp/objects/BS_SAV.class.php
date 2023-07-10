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
        $this->updateField('ecologic_data', $datas);
        
        
        return parent::actionToRestitute($data, $success);
    }
}
