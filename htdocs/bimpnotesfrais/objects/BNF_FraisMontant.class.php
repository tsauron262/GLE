<?php

class BNF_FraisMontant extends BimpObject
{
    
    public function getTaxesArray()
    {
        $taxes = array();
        foreach (BimpCache::getTaxes() as $id_tax => $rate) {
            $taxes[(float) $rate] = $rate . ' %';
        }
        return $taxes;
    }

    // Overrides: 

    public function create()
    {
        $errors = array();

        $frais = $this->getParentInstance();
        if (!BimpObject::objectLoaded($frais)) {
            $errors[] = 'ID de la note de frais correspondante absent';
        } else {
            if (!$frais->hasAmounts()) {
                $errors[] = 'Il n\'est pas possible d\'ajouter un montant pour ce type de note de frais';
            } else {
                $errors = parent::create();
            }
        }

        return $errors;
    }
}
