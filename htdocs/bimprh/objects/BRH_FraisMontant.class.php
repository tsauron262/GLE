<?php

class BRH_FraisMontant extends BimpObject
{   

    public static $periode_restauration = array(1 => 'Midi',2 => 'Soir');
    
    public static $exclude_taxes = array('19.6','2.1','6','7','10');

    public static $types = array(
        1 => array(
            1 => array('label' => 'Repas invitation', 'classes' => array('')),
            2 => array('label' => 'Repas mission', 'classes' => array('')),
            3 => array('label' => 'Repas organisation', 'classes' => array('')),
        ),
        6 => array(
            1 => array('label' => 'Billet d\'avion', 'classes' => array(''), 'icon' => 'plane'),
            2 => array('label' => 'Billet de train', 'classes' => array(''), 'icon' => 'train'),
            3 => array('label' => 'Billet de metro', 'classes' => array(''), 'icon' => 'train'),
            4 => array('label' => 'Taxi', 'classes' => array(''), 'icon' => 'taxi'),
        ),
        8 => array(
            1 => array('label' => 'Abonnement téléphonique', 'classes' => array(''), 'icon' => 'phone'),
            2 => array('label' => 'Abonnement de train', 'classes' => array(''), 'icon' => 'train'),
            3 => array('label' => 'Abonnement de bus', 'classes' => array(''), 'icon' => 'bus'),
            4 => array('label' => 'Abonnement tcl', 'classes' => array(''), 'icon' => 'bus'),
        ),
        9 => array(
            1 => array('label' => 'Wifi', 'classes' => array(''), 'icon' => 'wifi'),
            2 => array('label' => 'Billeterie', 'classes' => array(''), 'icon' => 'ticket'),
            3 => array('label' => 'Repas', 'classes' => array(''), 'icon' => 'apple'),
        ),
    );
    public static $necessary_types = array(1,2,6,8, 9);

    public function hasNecessaryType() {
        $frais = $this->getParentInstance();
        $type_frais = $frais->getData('type');
        return (in_array((int) $type_frais, self::$necessary_types) ? 1 : 0);
    }

    public function getTaxesArray()
    {
        $taxes = array();
        foreach (BimpCache::getTaxes() as $id_tax => $rate) {
            if(!in_array($rate, self::$exclude_taxes)){
                $taxes[(float) $rate] = $rate . ' %';
            }
        }
        return $taxes;
    }

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

    public function hasParentPeriod() {
        $return = 0;
        $frais = $this->getParentInstance();
          if (!BimpObject::objectLoaded($frais)) {
            $errors[] = 'ID de la note de frais correspondante absent';
        } else {
            $return = $frais->hasPeriod();
        }
        return $return;
    }

    public function displayTypesList() {
        $frais = $this->getParentInstance();
        if(!BimpObject::objectLoaded($frais)) {
            $errors[] = 'Id de la note de frais correspondante absent';
        } else {
            return self::$types[$frais->getData('type')];
        }
    }

   public function getListExtraBtn() {
        $buttons = array();
        return $buttons;
   }

    public function canFraisEdit() {
        if($this->getData('validate') == 1)
            return 0;
        return '1';
    }

    public function getNum() {
        return "<b>#".$this->getData('id')."</b>";
    }

    

}
