<?php

class BMP_MontantDetailValue extends BimpObject
{

    public function getTypes_montantsArray()
    {
        $cache_key = 'bmp_types_montants_with_details';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $instance = BimpObject::getInstance($this->module, 'BMP_TypeMontant');
            $list = $instance->getList(array(
                'has_details' => 1
            ));

            foreach ($list as $item) {
                self::$cache[$cache_key][(int) $item['id']] = $item['name'];
            }
        }

        return self::$cache[$cache_key];
    }
}
