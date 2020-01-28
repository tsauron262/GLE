<?php

class BimpStats
{

    public static function getTotal(BimpObject $instance, $field, $filters = array())
    {
        $total = 0;

        if ($instance->field_exists($field)) {
            $type = $instance->getConf('fields/' . $field . '/type', 'string');
            if (in_array($type, BimpObject::$numeric_types)) {
                $primary = $instance->getPrimary();
                $rows = $instance->getList($filters, null, null, 'id', 'asc', 'array', array($primary));
                $list = array();
                foreach ($rows as $r) {
                    $list[] = (int) $r[$primary];
                }

                $values = $instance->getSavedData($field, $list);

                foreach ($values as $id_object => $value) {
                    $total += (float) $value;
                }
            }
        }

        return $total;
    }
}
