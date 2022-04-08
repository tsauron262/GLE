<?php

class BimpObjectLog extends BimpObject
{

    // Getters statiques: 

    public static function getLastObjectLogByCodes($object, $codes = '')
    {
        if (is_a($object, 'BimpObject') && $object->isLoaded()) {
            if (!is_array($codes)) {
                $codes = array($codes);
            }

            $sql = BimpTools::getSqlSelectFullQuery('bimpcore_object_log', array('id'), array(
                        'obj_module' => $object->module,
                        'obj_name'   => $object->object_name,
                        'id_object'  => $object->id,
                        'code'       => array(
                            'in' => $codes
                        )
                            ), array(), array(
                        'order_by'  => 'date',
                        'order_way' => 'DESC',
                        'n'         => 1
            ));

            $rows = BimpCache::getBdb()->executeS($sql, 'array');

            if (isset($rows[0]['id'])) {
                return BimpCache::getBimpObjectInstance('bimpcore', 'BimpObjectLog', (int) $rows[0]['id']);
            }
        }

        return null;
    }
}
