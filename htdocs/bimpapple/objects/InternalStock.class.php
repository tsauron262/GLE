<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapple/objects/PartStock.class.php';

class InternalStock extends PartStock
{

    public static $stock_type = 'internal';

    // Getters params: 

    public function getListHeaderButtons()
    {
        return array();
    }

    // Getters statics: 

    public static function getStockInstance($code_centre, $part_number)
    {
        return BimpCache::findBimpObjectInstance('bimpapple', 'InternalStock', array(
                    'code_centre' => $code_centre,
                    'part_number' => $part_number
                        ), true);
    }
}
