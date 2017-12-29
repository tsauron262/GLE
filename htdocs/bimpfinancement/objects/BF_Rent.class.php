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

}
