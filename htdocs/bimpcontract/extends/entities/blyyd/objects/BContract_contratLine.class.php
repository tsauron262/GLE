<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcontract/objects/BContract_contratLine.class.php';

class BContract_contratLine_ExtEntity extends BContract_contratLine
{
    public function getListExtraBulkActions()
    {
        $actions = array();

//        if ($this->canSetAction('sendEmail')) {
            $actions[] = array(
                'label'   => 'Augmentation',
                'icon'    => 'fas_signal',
                'onclick' => $this->getJsBulkActionOnclick('augmentation', array(), array('form_name' => 'augmentation'))
            );
//        }


        return $actions;
    }
    
    public function actionAugmentation($data, &$success): array {
            $errors = $warnings = array();
        foreach($data['id_objects'] as $id){
            $line = BimpCache::getBimpObjectInstance($this->module, $this->object_name, $id);
            $parent = $line->getParentInstance();
            $totCtr = $parent->getTotalContrat();
            if($totCtr > 0){
                $resteAFACT = ($totCtr - $parent->getTotalDejaPayer());
                if($resteAFACT > 0){
                    if($data['annul']){
                        $resteInitital = $resteAFACT / ((100 + $data['percent'])/100);
                        $totInitial = $resteInitital + $parent->getTotalDejaPayer();
                        $newPercent = $totInitial / $totCtr;
                    }
                    else{
                        $newPercent = (100 + ($data['percent'] / $totCtr * $resteAFACT)) / 100;
                    }
                    if($newPercent !== 1){
//                        $warnings[] = 'ok percent final'.$newPercent.' nominal '. (100 + $data['percent'])/100 .' totCtr : '.$totCtr.' restafact '.$resteAFACT .' line ht '. $line->getData('subprice')*$newPercent;
                        $fields = array('total_ht', 'total_ttc', 'total_tva', 'subprice', 'price_ht');
                        foreach($fields as $field){
                            $line->set($field, $line->getData($field) * $newPercent);
                        }
                        $line->update($warnings, true);
//                        $warnings[] = 'update OK';
                    }
                    else{
                        $errors[] = $line->getRef().' contrat '.$parent->getRef().' pas de changement';
                    }
                }
                else{
                    $errors[] = $line->getRef().' contrat '.$parent->getRef().' totalement payé';
                }
            }
            else
                $errors[] = 'Pas de tot';
        }
        return array('errors'=>$errors, 'warnings'=> $warnings);
    }
}
