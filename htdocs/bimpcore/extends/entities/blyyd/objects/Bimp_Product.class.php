<?php

// Entitié : bimp

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/Bimp_Product.class.php';

class Bimp_Product_ExtEntity extends Bimp_Product
{
    public static $sousTypes = array(
        -1 => array(
            0 => ''
        ),
        0 => array(
            1 => 'De série',
            2 => 'En option'
        ),
        1 => array(
            101 => 'Récurent',
            102 => 'Ponctuel'
        )
    );
}
