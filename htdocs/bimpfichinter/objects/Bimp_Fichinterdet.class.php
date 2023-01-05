<?php

require_once DOL_DOCUMENT_ROOT.'/bimpfichinter/objects/objectInterDet.class.php';

class Bimp_Fichinterdet extends ObjectInterDet
{
    
    
    public function __construct($module, $object_name) {
        return parent::__construct($module, $object_name);
    }
    
    
    public function getInstanceName()
    {
        if ($this->isLoaded()) {
            $parent = $this->getParentInstance();
            if($parent->isLoaded())
                return $parent->getData('ref')." ligne ".dol_trunc ($this->getData("description"));
        }

        return ' ';
    }
    

     
    public function create(&$warnings = array(), $force_create = false) {
        $this->dol_object->fk_typeinterv = $this->getData('fk_typeinterv');//bizarre mais necessaire
        $errors  = parent::create($warnings, $force_create);
        if(!count($errors))
            $errors = $this->update($warnings, $force_create);
        
        return $errors;
    }
    

}

