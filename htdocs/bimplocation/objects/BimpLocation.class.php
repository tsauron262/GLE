<?php

class BimpLocation extends BimpObject
{

    const STATUS_CANCELED = -1;
    const STATUS_BROUILLON = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CLOSED = 10;

    public static $status_list = array(
        self::STATUS_CANCELED  => array('label' => 'Annulée', 'icon' => 'fas_times', 'classes' => array('danger')),
        self::STATUS_BROUILLON => array('label' => 'Brouillon', 'icon' => 'fas_times', 'classes' => array('warning')),
        self::STATUS_VALIDATED => array('label' => 'Validée', 'icon' => 'fas_times', 'classes' => array('info')),
        self::STATUS_CLOSED    => array('label' => 'Terminée', 'icon' => 'fas_check', 'classes' => array('success'))
    );
}
