<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/Bimp_Propal.class.php';

class Bimp_Propal_ExtEntity extends Bimp_Propal
{

    public static $df_status_list = array(
        0  => '',
        1  => array('label' => 'En attente d\'acceptation', 'icon' => 'fas_hourglass-start', 'classes' => array('warning')),
        10 => array('label' => 'Acceptée', 'icon' => 'fas_check', 'classes' => array('success')),
        20 => array('label' => 'Refusée', 'icon' => 'fas_times', 'classes' => array('danger')),
        21 => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger'))
    );
}
