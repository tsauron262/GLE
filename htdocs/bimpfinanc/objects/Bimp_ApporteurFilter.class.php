<?php

class Bimp_ApporteurFilter extends BimpObject{
    function getNbRsult(){
        $list = $this->getProductIds();
        if($list == 'all')
            return 'Tous';
        return count($list);
    }
    
    public function getProductIds(){
        $filters_array = BimpTools::json_decode_array($this->getData('filter'));
        
        $filtersProd = array();
        $prefProd = 'dol_line___product___';
        foreach($filters_array as $filter => $dataFilter){
            if(stripos($filter, $prefProd) !== false){
                $filtersProd[str_replace($prefProd, '', $filter)] = $dataFilter;
            }
        }
        
        if(is_array($filtersProd) && count($filtersProd)){
            $object = BimpObject::getInstance('bimpcore', 'Bimp_Product');
            $list = BC_FiltersPanel::getObjectListIdsFromFilters($object, $filtersProd);
            return $list;
        }
        else
            return 'all';
    }
    
    
    public function getLabel($param= '', $ucfirst = false) {
        if($this->id > 0){
            $libelle = $this->getData('libelle');
            if($libelle != '') {

                // Contient un pourcentage
                if(preg_match("/\d+\s?%/", $libelle) != 0)
                    $return = $libelle;
                else// Ne contient pas de pourcentage => on le rajoute
                    $return = $libelle . " (" . $this->getData('commition') . "%)";

            } else
                $return = "Commissionnée à " . $this->getData('commition') .  "%";
            if($ucfirst)
                return ucfirst ($return);
            return $return;
        }
        return parent::getLabel($type, $ucfirst);
    }
    
    public function isFieldEditable($field, $force_edit = false) {
                
        return parent::isFieldEditable($field, $force_edit);
    }
    
    
    public function updateField($field, $value, $id_object = null, $force_update = true, $do_not_validate = false) {
        
        $errors = parent::updateField($field, $value, $id_object, $force_update, $do_not_validate);
        
//        if(empty($errors)) {
//            
//        }
        
    }
}
