<?php

class BC_Caisse extends BimpObject
{

    public static $states = array(
        0 => array('label' => 'Fermée', 'icon' => 'times', 'classes' => array('danger')),
        1 => array('label' => 'Ouverte', 'icon' => 'check', 'classes' => array('success'))
    );
}
