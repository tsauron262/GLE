<?php

class BF_Demande extends BimpObject
{

    public static $status_list = array(
        0  => array('label' => 'Brouillon', 'icon' => 'far_file', 'classes' => array('info')),
        1  => array('label' => 'Acceptation refinanceur en attente', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        10 => array('label' => 'Accepté', 'icon' => 'fas_check', 'classes' => array('success')),
        20 => array('label' => 'Refusée', 'icon' => 'fas_times', 'classes' => array('danger')),
        21 => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger'))
    );
    public static $durations = array(
        24 => '24 mois',
        36 => '36 mois',
        48 => '48 mois',
        60 => '60 mois',
        72 => '72 mois',
        84 => '84 mois'
    );
    public static $periodicities = array(
        1  => 'Mensuelle',
        3  => 'Trimestrielle',
        6  => 'Semestrielle',
        12 => 'Annuelle'
    );
    public static $calc_modes = array(
        0 => '-',
        1 => 'A terme échu',
        2 => 'A terme à échoir'
    );

    // Getters booléens: 

    public function areLinesEditable()
    {
        return 1;
    }

    // Getters array: 

    public function getClientContactsArray($include_empty = true, $active_only = true)
    {
        $id_client = (int) $this->getData('id_client');

        if ($id_client) {
            return self::getSocieteContactsArray($id_client, $include_empty, '', $active_only);
        }

        return array();
    }
}
