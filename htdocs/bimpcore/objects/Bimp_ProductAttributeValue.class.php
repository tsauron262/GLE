<?php

class Bimp_ProductAttributeValue extends BimpObject
{

    public $no_dol_right_check = true;

    // Getters array : 

    public static function getAttributeValuesArray($id_attribute, $include_empty = true)
    {
        $cache_key = 'product_attribute_' . $id_attribute . '_values_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $rows = self::getBdb()->getRows('product_attribute_value', 'fk_product_attribute = ' . $id_attribute, null, 'array', array('rowid', 'ref', 'value'), 'position', 'asc');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['rowid']] = $r['ref'] . ' : ' . $r['value'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    // Surcharges : 

    public function hydrateFromDolObject(&$bimpObjectFields = array())
    {
        $result = parent::hydrateFromDolObject($bimpObjectFields);

        if (!in_array('position', $bimpObjectFields)) {
            $bimpObjectFields[] = 'position';
        }

        return $result;
    }
}
