<?php

class BimpCache
{

    public static $bdb = null;
    public static $taxes = array();

    public static function getBdb()
    {
        if (is_null(self::$bdb)) {
            global $db;
            self::$bdb = new BimpDb($db);
        }

        return self::$bdb;
    }

    public static function getTaxes($id_country = 1)
    {
        $id_country = (int) $id_country;
        if (!isset(self::$taxes[$id_country])) {
            $taxes = array();
            $bdb = self::getBdb();
            $rows = $bdb->getRows('c_tva', '`fk_pays` = ' . $id_country . ' AND `active` = 1', null, 'array', array('rowid', 'taux'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $taxes[$r['rowid']] = $r['taux'];
                }
            }
            self::$taxes[$id_country] = $taxes;
        }
        return self::$taxes[$id_country];
    }
}
