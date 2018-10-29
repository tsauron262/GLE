<?php

class BF_Refinanceur extends BimpObject
{

    const BF_REFINANCEUR_RIEN = 0;
    const BF_REFINANCEUR_ETUDE = 1;
    const BF_REFINANCEUR_ACCORD = 2;
    const BF_REFINANCEUR_REFUS = 3;
    const BF_REFINANCEUR_SOUS_CONDITION = 4;

    public static $status_list = array(
        // Oblkigatoirement une constante pour self::
        self::BF_REFINANCEUR_RIEN => array('label' => '-', 'classes' => array('important')),
        self::BF_REFINANCEUR_ACCORD => array('label' => 'Accord', 'classes' => array('success')),
        self::BF_REFINANCEUR_REFUS => array('label' => 'Refus', 'classes' => array('danger')),
        self::BF_REFINANCEUR_ETUDE => array('label' => '&Eacute;tude', 'classes' => array('warning')),
        self::BF_REFINANCEUR_SOUS_CONDITION => array('label' => 'Sous-condition', 'classes' => array('warning')),
    );

    public static $names = array(
        0 => '-',
        228225 => 'BNP',
        233883 => 'FRANFINANCE',
        231492 => 'GE - CM-CIC BAIL',
        234057 => 'GRENKE',
        5 => 'LIXXBAIL',
        230634 => 'LOCAM'
    );
    // public static $status_list = array(
    //     0 => '-',
    //     1 => 'Etude',
    //     2 => 'Accord',
    //     3 => 'Refus',
    //     4 => 'Sous-condition'
    // );

}
