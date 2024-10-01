<?php

class Bimp_ProductattributeValue extends BimpObject
{

    public $no_dol_right_check = true;

    // Surcharges : 

    public function hydrateFromDolObject(&$bimpObjectFields = array())
    {
        $result = parent::hydrateFromDolObject($bimpObjectFields);

        $bimpObjectFields[] = 'position';

        return $result;
    }
}
