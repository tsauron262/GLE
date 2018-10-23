<?php

class BF_Rent extends BimpObject
{

    public static $payments = array(
        0 => '-',
        1 => 'Prélévement auto',
        2 => 'Virement',
        3 => 'Mandat administratif'
    );
    public static $periodicities = array(
        1  => 'Mensuelle',
        3  => 'Trimestrielle',
        6  => 'Semestrielle',
        12 => 'Annuelle'
    );
    public static $periodicities_masc = array(
        1  => 'mensuel',
        3  => 'trimestriel',
        6  => 'semestriel',
        12 => 'annuel'
    );
    public static $period_label = array(
        1  => 'mois',
        3  => 'trimestre',
        6  => 'semestre',
        12 => 'an'
    );
    public static $period_label_plur = array(
        1  => 'mois',
        3  => 'trimestres',
        6  => 'semestres',
        12 => 'ans'
    );

}
