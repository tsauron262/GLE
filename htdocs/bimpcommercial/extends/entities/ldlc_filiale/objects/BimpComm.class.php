<?php

// Entité : bimp

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpComm.class.php';

class BimpComm_LdlcFiliale extends BimpComm
{
    public function getActionsButtons()
    {
        $buttons = parent::getActionsButtons();
//        $buttons[] = array(
//            'label'   => 'Ajouter/Maj port BIMP',
//            'icon'    => 'fas_hand-holding-usd',
//            'onclick' => $this->getJsActionOnclick('addPortLdlc', array(), array('form_name'=>'port'))
//        );
        return $buttons;
    }
    
    public function actionAddPortLdlc($dataForm = array(), &$success = ''){
        $errors = array();
        $tabPoids = array(
            'fr' => array(
                'base' => array(
                    1 => 5.95,
                    3 => 6.95,
                    5 => 7.95,
                    7 => 9.95,
                    9 => 11.95,
                    12 => 14.95,
                    15 => 17.95,
                    20 => 21.95,
                    25 => 24.95,
                    30 => 29.95,
                    100 => array(32.95, 10, 5),
                    99999999 => array(79.95, 100, 50)
                ),
                'chrono' => array(
                    3 => 4,
                    5 => 6,
                    10 => 8,
                    20 => 10,
                    99999999 => 15
                ),
                'chronoEx' => array(
                    3 => 7,
                    5 => 10,
                    10 => 12,
                    20 => 15,
                    99999999 => 20
                ),
                'corse' => array(
                    20 => '+5',
                    30 => 69,
                    100 => array(100, 10, 15),
                    9999999999 => array(205, 100, 150)
                )
            )
        );
        
        $success = 'Ajout/maj port LDLC OK';
        $poid = $this->getTotalWeight();
        $price = 0;
        $oldMax = 0;
        $info = '';
        
        foreach($tabPoids['fr']['base'] as $max => $data){
            if($max >= $poid){
                if(is_array($data)){
                    $price = $data[0];
                    for($i = 0; $i<100; $i++){
                        if($data[1] * $i + $oldMax >= $poid){
                            $price += $i * $data[2];
                            $info .= ' ('.$data[0].' € + '.$i.' X '.$data[2].' €) HT';
                            break;
                        }
                    }
                }
                else{
                    $info .= ' '.$data.' € HT';
                    $price += $data;
                }
                break;
            }
            $oldMax = $max;
        }
        
        if($dataForm['option'] != 'base'){
            //exception corse
            $info .= ' + ';
            if($dataForm['option'] == 'corse' && $poid > 20){
                $price = 0;
                $info = ' ';
            }
            foreach($tabPoids['fr'][$dataForm['option']] as $max => $data){
                if($max >= $poid){
                    if(is_array($data)){
                        $price += $data[0];
                        for($i = 0; $i<100; $i++){
                            if($data[1] * $i + $oldMax >= $poid){
                                $price += $i * $data[2];
                                $info .= 'Option '.$dataForm['option'].' ('.$data[0].' € + '.$i.' X '.$data[2].' €) HT';
                                break;
                            }
                        }
                    }
                    else{
                        $info .= 'Option '.$dataForm['option'].' '.$data.' € HT';
                        $price += $data;
                    }
                    break;
                }
                $oldMax = $max;
            }
        }
        
        
        
        if($price < 1)
            $errors[] = 'Calcul impossible';
        if(!count($errors)){
            $errors = $this->createMajLn(array('linked_object_name' => 'portLdlc'), array(
                'qty'=>1, 
                'id_product'=>243901, 
                'pu_ht'=>$price,
                'tva_tx' => 20,
                'desc'   => 'Frais de port BIMP'.$info,
                'pa_ht' => $price*0.5
            ), array(
//                'type'      => ObjectLine::LINE_FREE,
                'editable'  => 0,
                'deletable' => 1,
                'id_parent_line' => 0
            ));
        }
        return array('warnings'=>array(), 'errors'=>$errors);
    }
}
