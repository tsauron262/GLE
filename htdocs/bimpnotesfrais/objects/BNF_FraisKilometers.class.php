<?php

class BNF_FraisKilometers extends BimpObject
{

    // Overrides: 
    public static $chevaux_list = array(
        1 => '3 et 4 CV',
        2 => '5 à 7 Cv',
        3 => '8 et 9 CV',
        4 => '10 et 11 CV',
        5 => '12 Cv et +'
    );
    public static $carburants = array(
        1 => 'Gazole',
        2 => 'Super',
        3 => 'GPL'
    );
    public static $coefs = array(
        1 => array(
            1 => 0.068,
            2 => 0.091,
            3 => 0.056
        ),
        2 => array(
            1 => 0.084,
            2 => 0.112,
            3 => 0.068
        ),
        3 => array(
            1 => 0.100,
            2 => 0.133,
            3 => 0.081
        ),
        4 => array(
            1 => 0.113,
            2 => 0.150,
            3 => 0.092
        ),
        5 => array(
            1 => 0.125,
            2 => 0.166,
            3 => 0.102
        )
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
        $cv = (int) $this->getData('chevaux');
        $carb = (int) $this->getData('carburant');
        $km = (float) $this->getData('kilometers');
        if ($cv && $carb && $km) {
            if (isset(self::$coefs[$cv][$carb])) {
                $montant = (float) self::$coefs[$cv][$carb] * $km;
            }
        }

        return $montant;
    }

    public function displayMontant()
    {
        return BimpTools::displayMoneyValue((float) $this->getMontant(), 'EUR');
    }
}
