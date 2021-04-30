<?php

class Bimp_ApporteurFilter extends BimpObject{
    function getNbRsult(){
        $list = $this->getProductIds();
        return count($list);
    }
    
    public function getProductIds(){
        $filters_array = json_decode($this->getData('filter'), true);
        $object = BimpObject::getInstance('bimpcore', 'Bimp_Product');
        $list = BC_FiltersPanel::getObjectListIdsFromFilters($object, $filters_array);
        return $list;
    }
}