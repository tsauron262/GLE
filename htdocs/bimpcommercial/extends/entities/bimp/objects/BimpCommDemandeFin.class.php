<?php

// Entitié : bimp

require_once DOL_DOCUMENT_ROOT . '/bimpcommercial/objects/BimpCommDemandeFin.class.php';

class BimpCommDemandeFin_ExtEntity extends BimpCommDemandeFin
{

    public static $targets = array(
        'prolease' => 'LDLC Pro Lease'
    );
    public static $def_target = 'prolease';
    public static $targets_defaults = array(
        'prolease' => array(
            'duration'    => 36,
            'periodicity' => 3,
            'mode_calcul'   => 1
        )
    );
}
