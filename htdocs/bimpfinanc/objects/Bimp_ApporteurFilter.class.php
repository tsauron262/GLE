<?php

class Bimp_ApporteurFilter extends BimpObject{
    function getNbRsult(){
        $list = $this->getProductIds();
        if($list == 'all')
            return 'All';
        return count($list);
    }
    
    public function getProductIds(){
        $filters_array = json_decode($this->getData('filter'), true);
        if(is_array($filters_array)){
            $object = BimpObject::getInstance('bimpcore', 'Bimp_Product');
            $list = BC_FiltersPanel::getObjectListIdsFromFilters($object, $filters_array);
            return $list;
        }
        else
            return 'all';
    }
    
    
    public function getLabel($param= '') {
        $libelle = $this->getData('libelle');
        if($libelle != '') {
            
            // Contient un pourcentage
            if(preg_match("/\d+\s?%/", $libelle) != 0)
                return $libelle;

            // Ne contient pas de pourcentage => on le rajoute
            return $libelle . " (" . $this->getData('commition') . "%)";

        } else
            return "Commissionnée à " . $this->getData('commition') .  "%";
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
