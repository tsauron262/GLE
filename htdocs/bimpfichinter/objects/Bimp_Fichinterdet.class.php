<?php

require_once DOL_DOCUMENT_ROOT.'/bimpfichinter/objects/objectInterDet.class.php';

class Bimp_Fichinterdet extends ObjectInterDet
{
    
    
    public function __construct($module, $object_name) {
        parent::__construct($module, $object_name);
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
        parent::create($warnings, $force_create);
        
        $this->update($warnings, $force_create);
    }
    

}

