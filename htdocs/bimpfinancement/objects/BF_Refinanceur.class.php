<?php

class BF_Refinanceur extends BimpObject {
    // Overrides: 
    
    public function getName()
    {
        $soc = $this->getChildObject('societe');
        
        if (BimpObject::objectLoaded($soc)) {
            return $soc->getName();
        }
        
        return parent::getName();
    }
}