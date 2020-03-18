<?php

class BRH_FraisKilometers extends BimpObject
{

    // Overrides: 
    public static $chevaux_list = array(
        3 => '3 CV',
        4 => '4 CV',
        5 => '5 CV',
        6 => '6 CV',
        7 => '7 CV'
    );
    public static $carburants = array(
        1 => 'Gazole',
        2 => 'Super',
        3 => 'GPL'
    );
    public static $coefs = array(
        3 => 0.54,
        4 => 0.60,
        5 => 0.63,
        6 => 0.66,
        7 => 0.70
    );

    public function create()
    {
        $errors = array();

        $frais = $this->getParentInstance();
        if (!BimpObject::objectLoaded($frais)) {
            $errors[] = 'ID de la note de frais correspondante absent';
        } else {
            if (!$frais->hasKilometers()) {
                $errors[] = 'Il n\'est pas possible d\'ajouter un montant pour ce type de note de frais';
            } else {
                $errors = parent::create();
            }
        }

        return $errors;
    }

    public function getMontant()
    {
        
        $montant = 0;
        $km = (float) $this->getData('kilometers');
        $cv = (int) $this->getData('chevaux');
        $montant = (float) self::$coefs[$cv] * $km;

        return $montant;
    }
   

    public function displayMontant()
    {
        return BimpTools::displayMoneyValue((float) $this->getMontant(), 'EUR');
    }
}
