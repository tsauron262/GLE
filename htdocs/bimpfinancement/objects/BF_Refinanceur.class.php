<?php

class BF_Refinanceur extends BimpObject
{

    public function getName($withGeneric = true)
    {
        $soc = $this->getChildObject('societe');

        if (BimpObject::objectLoaded($soc)) {
            return $soc->getName();
        }

        return parent::getName($withGeneric);
    }
}
