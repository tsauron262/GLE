<?php

class Bimp_ApporteurFilter extends BimpObject{
    function getNbRsult(){
        $list = $this->getProductIds();
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
}