<?php

require_once DOL_DOCUMENT_ROOT . '/bimpsupport/objects/BS_SAV.class.php';

class BS_SAV_ExtEntity extends BS_SAV{
    public static $status_ecologic_list = array(
        -1          => array('label' => 'Non Applicable', 'icon' => 'fas_not-equal', 'classes' => array('important')),
        0           => array('label' => 'En attente', 'icon' => 'fas_times', 'classes' => array('danger')),
        1           => array('label' => 'Attente déclaration', 'icon' => 'fas_times', 'classes' => array('danger')),
        99          => array('label' => 'Déclarré', 'icon' => 'arrow-right', 'classes' => array('danger'))
    );
    
    public function actionToRestitute($data, &$success){
        $return = parent::actionToRestitute($data, $success);
        if(!count($return['errors'])){
            $this->verifCologic();
        }
        return $return;
    }
    
    public function verifCologic(){
        $asProd = false;
        $tabIdProd = json_decode(BimpCore::getConf('prod_ecologic', '', 'bimpsupport'));
        if(is_array($tabIdProd)){
            foreach ($this->getPropalLines() as $line) {
                $dolLine = $line->getChildObject('line');
                if(in_array($dolLine->fk_product, $tabIdProd))
                        $asProd = true;
            }
        }
        if($asProd){
            $this->updateField('status_ecologic',1);
        }
        else{
            $this->updateField('status_ecologic',-1);
        }
    }
}
