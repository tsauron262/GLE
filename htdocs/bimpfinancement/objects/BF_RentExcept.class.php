<?php

require_once __DIR__.'/BF_Frais.class.php';

class BF_rentExcept extends BF_Frais
{

    public static $payments = array(
        0 => '-',
        1 => 'Prélévement auto',
        2 => 'Virement',
        3 => 'Mandat administratif'
    );
}
