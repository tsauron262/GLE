<?php

// Entité : bimp

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';

class BimpComm_LdlcFiliale extends BimpComm
{
    public function getActionsButtons()
    {
        $buttons = parent::getActionsButtons();
//        $buttons[] = array(
//            'label'   => 'Ajouter/Maj port LDLC',
//            'icon'    => 'fas_hand-holding-usd',
//            'onclick' => $this->getJsActionOnclick('addPortLdlc', array(), array())
//        );
        return $buttons;
    }
    
    public function actionAddPortLdlc($data = array(), &$success = ''){
        $success = 'Ajout/maj port LDLC OK';
        $price = $this->getTotalWeight() * 3;
        $errors = $this->createMajLn(array('linked_object_name' => 'portLdlc'), array(
            'qty'=>1, 
            'id_product'=>0, 
            'pu_ht'=>$price,
            'tva_tx' => 20,
            'desc'   => 'Frais de port LDLC',
            'pa_ht' => $price
        ), array(
            'type'      => ObjectLine::LINE_FREE,
            'editable'  => 0,
            'deletable' => 1,
            'id_parent_line' => 0
        ));
        return array('warnings'=>array(), 'errors'=>$errors);
    }
}
