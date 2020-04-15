<?php

class BDS_SyncData
{

    public $id = 0;
    public $ext_id = 0;
    public $loc_id_process = 0;
    public $loc_object_name = '';
    public $loc_id_object = 0;
    public $ext_id_process = 0;
    public $ext_object_name = '';
    public $ext_id_object = 0;
    public $status = 0;
    public $date_add = '';
    public $date_update = '';
    protected $objects = array();
    protected $db;
    public static $table = 'bds_object_sync_data';

    public function __construct($id = null)
    {
        global $db;
        $this->db = new BDSDb($db);

        if (!is_null($id)) {
            $this->id = $id;
        }
    }

    public function setLocValues($id_process, $object_name, $id_object)
    {
        $this->loc_id_process = (int) $id_process;
        $this->loc_object_name = $object_name;
        $this->loc_id_object = (int) $id_object;
    }

    public function setExtValues($id_process, $object_name, $id_object)
    {
        $this->ext_id_process = (int) $id_process;
        $this->ext_object_name = $object_name;
        $this->ext_id_object = $id_object;
    }

    public function loadOrCreate($load_only = false)
    {
        $id = $this->id;
        if (!$id) {
            $id = $this->getIdBy('ext_id');
        }
        if (!$id) {
            $id = $this->getIdBy('loc_values');
        }
        if (!$id) {
            $id = $this->getIdBy('ext_values');
        }

        if ($id) {
            $data = $this->db->getRow(self::$table, '`id` = ' . (int) $id);
            if ($data) {
                foreach ($data as $key => $value) {
                    // On évite d'écraser d'éventuelles données existantes:
                    if (is_null($this->{$key}) || !$this->{$key} || ($this->{$key} === '')) {
                        $this->{$key} = $value;
                    }
                }
            }
            $rows = $this->db->getRows(self::$table . '_object', '`id_sync_data` = ' . (int) $this->id);
            if (!is_null($rows) && count($rows)) {
                foreach ($rows as $r) {
                    if (!isset($this->objects[$r->type])) {
                        $this->objects[$r->type] = array();
                    }

                    $this->objects[$r->type][$r->loc_value] = $r->ext_value;
                }
            }
            if ($data !== false) {
                return true;
            }
        } elseif (!$load_only) {
            $id = $this->db->insert(self::$table, array(
                'ext_id'          => (int) $this->id,
                'loc_id_process'  => (int) $this->loc_id_process,
                'loc_object_name' => $this->db->db->escape($this->loc_object_name),
                'loc_id_object'   => (int) $this->loc_id_object,
                'ext_id_process'  => (int) $this->ext_id_process,
                'ext_object_name' => $this->db->db->escape($this->ext_object_name),
                'ext_id_object'   => (int) $this->ext_id_object,
                'status'          => (int) $this->status
                    ), true);
            if ($id) {
                $this->id = $id;
                return true;
            }
        }
        return false;
    }

    public function save()
    {
        $errors = array();

        if (!$this->id) {
            $errors[] = 'ID des données de synchronisation absent';
        } else {
            if (!$this->db->update(self::$table, array(
                        'ext_id'          => (int) $this->ext_id,
                        'loc_id_process'  => (int) $this->loc_id_process,
                        'loc_object_name' => $this->db->db->escape($this->loc_object_name),
                        'loc_id_object'   => (int) $this->loc_id_object,
                        'ext_id_process'  => (int) $this->ext_id_process,
                        'ext_object_name' => $this->db->db->escape($this->ext_object_name),
                        'ext_id_object'   => (int) $this->ext_id_object,
                        'status'          => (int) $this->status
                            ), '`id` = ' . (int) $this->id)) {
                $msg = 'Echec de l\'enregistrement des données de synchronisation';
                $msg .= ' - Erreur SQL: ' . $this->db->getMsgError();
                $errors[] = $msg;
            }

            if (count($this->objects)) {
                foreach ($this->objects as $type => $rows) {
                    $delWhere = '`id_sync_data` = ' . (int) $this->id;
                    $delWhere .= ' AND `type` = \'' . $type . '\'';
                    if (!$this->db->delete(self::$table . '_object', $delWhere)) {
                        $msg = 'Echec du nettoyage des valeurs des objets de type "' . $type . '"';
                        $errors[] = $msg;
                    } elseif (count($rows)) {
                        foreach ($rows as $loc_value => $ext_value) {
                            if (!$this->db->insert(self::$table . '_object', array(
                                        'id_sync_data' => (int) $this->id,
                                        'type'         => $this->db->db->escape($type),
                                        'loc_value'    => $this->db->db->escape($loc_value),
                                        'ext_value'    => $this->db->db->escape($ext_value)
                                    ))) {
                                $msg = 'Echec de l\'enregistrement des valeurs d\'un objet de type "' . $type . '"';
                                $msg .= ' (' . $loc_value . ' => ' . $ext_value . ')';
                                $errors[] = $msg;
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    public function delete()
    {
        $errors = array();
        $id = $this->id;
        if (is_null($id) || !$id) {
            $id = $this->getIdBy('ext_id');
            if (!$id) {
                $id = $this->getIdBy('loc_values');
            }
            if (!$id) {
                $id = $this->getIdBy('ext_values');
            }
            if (!$id) {
                $errors[] = 'Aucun ID de synchronisation trouvé';
                return $errors;
            }
        }

        if (!$this->db->delete(self::$table . '_object', '`id_sync_data` = ' . (int) $id)) {
            $msg = 'Echec de la suppression des objets associées aux données de synchronisation d\'ID ' . $id;
            $msg .= ' - Erreur SQL : ' . $this->db->db->error();
            $errors[] = $msg;
        }
        if (!$this->db->delete(self::$table, '`id` = ' . (int) $id)) {
            $msg = 'Echec de la suppression des données de synchronisation d\'ID ' . $id;
            $msg .= ' - Erreur SQL: ' . $this->db->db->error();
            $errors[] = $msg;
        } else {
            $this->id = null;
        }
        return $errors;
    }

    public function updateStatus($new_status)
    {
        if (is_null($this->id) || !$this->id) {
            return false;
        }

        $this->status = $new_status;
        return $this->db->update(self::$table, array(
                    'status' => $new_status
                        ), '`id` = ' . (int) $this->id);
    }

    public function areLocValuesValid()
    {
        return (!is_null($this->loc_id_process) && $this->loc_id_process &&
                !is_null($this->loc_object_name) && $this->loc_object_name &&
                !is_null($this->loc_id_object) && $this->loc_id_object);
    }

    public function areExtValuesValid()
    {
        return (!is_null($this->ext_id_process) && $this->ext_id_process &&
                !is_null($this->ext_object_name) && $this->ext_object_name &&
                !is_null($this->ext_id_object) && $this->ext_id_object);
    }

    public function getIdBy($values_type)
    {
        if (!in_array($values_type, array('ext_id', 'loc_values', 'ext_values'))) {
            return 0;
        }

        $where = '';
        switch ($values_type) {
            case 'ext_id':
                if (is_null($this->ext_id) || !$this->ext_id) {
                    return 0;
                }
                $where .= '`ext_id` = ' . (int) $this->ext_id;
                break;

            case 'loc_values':
                if (!$this->areLocValuesValid()) {
                    return 0;
                }
                $where .= '`loc_id_process` = ' . (int) $this->loc_id_process;
                $where .= ' AND `loc_object_name` = \'' . $this->loc_object_name . '\'';
                $where .= ' AND `loc_id_object` = ' . (int) $this->loc_id_object;
                break;

            case 'ext_values':
                if (!$this->areExtValuesValid()) {
                    return 0;
                }
                $where .= '`ext_id_process` = ' . (int) $this->ext_id_process;
                $where .= ' AND `ext_object_name` = \'' . $this->ext_object_name . '\'';
                $where .= ' AND `ext_id_object` = ' . (int) $this->ext_id_object;
                break;
        }

        if ($where !== '') {
            $id = $this->db->getValue(self::$table, 'id', $where);
            if ($id === false) {
                return 0;
            }

            $this->id = $id;
            return $id;
        }

        return 0;
    }

    public function getObjects($type)
    {
        if (!is_null($type) && $type && isset($this->objects[$type])) {
            return $this->objects[$type];
        }

        return array();
    }

    public function getObjectsForExport()
    {
        $objects = array();
        foreach ($this->objects as $type => $rows) {
            $objects[$type] = array();

            foreach ($rows as $loc_value => $ext_value) {
                $objects[$type][] = array(
                    'loc_value' => $ext_value,
                    'ext_value' => $loc_value
                );
            }
        }

        return $objects;
    }

    public function setObjects($type, $objects)
    {
        $this->objects[$type] = $objects;
    }

    public static function updateStatusById(BDSDb $db, $id_sync_data, $new_status)
    {
        if (!is_null($id_sync_data) && $id_sync_data) {
            return $result = $db->update(self::$table, array(
                'status' => (int) $new_status
                    ), '`id` = ' . (int) $id_sync_data);
        }
        return false;
    }

    public static function updateStatusBylocIdObject(BDSDb $db, $id_process, $object_name, $id_object, $new_status)
    {
        $where = '`loc_id_process` = ' . (int) $id_process;
        $where .= ' AND `loc_object_name` = \'' . $object_name . '\'';
        $where .= ' AND `loc_id_object` = ' . (int) $id_object;

        return $db->update(self::$table, array(
                    'status' => (int) $new_status
                        ), $where);
    }

    public static function resetAllStatus(BDSDb $db, $id_process, $object_name)
    {
        $where = '`loc_id_process` = ' . (int) $id_process;
        $where .= ' AND `loc_object_name` = \'' . $object_name . '\'';

        return $result = $db->update(self::$table, array(
            'status' => 0
                ), $where);
    }

    public static function getObjectValue(BDSDb $db, $value_name, $loc_id_process, $loc_object_name, $key_value, $key_property)
    {
        if (!property_exists('BDS_SyncData', $value_name)) {
            return null;
        }
        if (!in_array($key_property, array('id', 'ext_id', 'ext_id_object', 'loc_id_object'))) {
            return null;
        }

        $where = '`loc_id_process` = ' . (int) $loc_id_process;
        $where .= ' AND `loc_object_name` = \'' . $loc_object_name . '\'';
        $where .= ' AND `' . $key_property . '` = ';
        if (is_string($key_value)) {
            $where .= '\'' . $key_value . '\'';
        } else {
            $where .= $key_value;
        }
        return $db->getValue(self::$table, $value_name, $where);
    }

    public static function getObjectObjects(BDSDb $db, $type, $loc_id_process, $loc_object_name, $loc_id_object)
    {
        $sql = 'SELECT o.loc_value, o.ext_value FROM ' . MAIN_DB_PREFIX . self::$table . '_object o';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . self::$table . ' d ON o.id_sync_data = d.id';
        $sql .= ' WHERE d.loc_id_process = ' . (int) $loc_id_process;
        $sql .= ' AND d.loc_object_name = \'' . $loc_object_name . '\'';
        $sql .= ' AND d.loc_id_object = ' . (int) $loc_id_object;
        $sql .= ' AND o.type = \'' . $type . '\'';

        $rows = $db->executeS($sql);

        $objects = array();
        if (!is_null($rows) && count($rows)) {
            foreach ($rows as $r) {
                $objects[$r->loc_value] = $r->ext_value;
            }
        }
        return $objects;
    }

    public static function deleteByLocObject($id_process, $object_name, $id_object, &$errors = null)
    {
        $sync_data = new BDS_SyncData();
        $sync_data->setLocValues($id_process, $object_name, $id_object);
        $id = $sync_data->getIdBy('loc_values');
        if (!is_null($id) && $id) {
            $sync_data_errors = $sync_data->delete();
            if (!is_null($errors) && count($sync_data_errors)) {
                $errors = BimpTools::merge_array($errors, $sync_data_errors);
            }
        } else {
            if (!is_null($errors)) {
                $errors[] = 'ID de synchronisation non trouvé';
            }
        }
    }

    public static function getObjectsList(BDSDb $db, $id_process = null, $object_name = null)
    {
        $where = '';

        $first = true;
        if (!is_null($id_process)) {
            $where .= '`loc_id_process` = ' . (int) $id_process;
            $first = false;
        }
        if (!is_null($object_name)) {
            if (!$first) {
                $where .= ' AND ';
            }
            $where .= '`loc_object_name` = \'' . $object_name . '\'';
        }

        $rows = $db->getRows(self::$table, $where, null, 'array');

        $objects = array();
        if (!is_null($rows)) {
            foreach ($rows as $r) {
                $objects[(int) $r['loc_id_object']] = array(
                    'status'        => (int) $r['status'],
                    'ext_id_object' => (int) $r['ext_id_object'],
                    'id_sync_data'  => (int) $r['id']
                );
            }
        }
        return $objects;
    }

    public static function getAllObjectsList(BDSDb $db, $id_process = null, $order_by = 'date_update', $order_way = 'desc')
    {
        $where = '';
        if (!is_null($id_process)) {
            $where .= '`loc_id_process` = ' . (int) $id_process;
        }

        $rows = $db->getRows(self::$table, $where, null, 'object');

        $objects = array();

        if (!is_null($rows)) {
            foreach ($rows as $obj) {
                if (!array_key_exists($obj->loc_object_name, $objects)) {
                    $objects[$obj->loc_object_name] = array(
                        'name'       => $obj->loc_object_name,
                        'label'      => BDS_Report::getObjectLabel($obj->loc_object_name, false),
                        'label_plur' => BDS_Report::getObjectLabel($obj->loc_object_name, true),
                        'list'       => array()
                    );
                }
                $data = array(
                    'id_sync_data'  => $obj->id,
                    'date_add'      => $obj->date_add,
                    'date_update'   => $obj->date_update,
                    'id_object'     => $obj->loc_id_object,
                    'ext_id_object' => $obj->ext_id_object,
                    'ext_object_name'    => $obj->ext_object_name,
                    'status'        => $obj->status
                );
                $objects[$obj->loc_object_name]['list'][] = $data;
            }
        }

        global $bds_array_sort;
        $bds_array_sort = array(
            'order_by'  => $order_by,
            'order_way' => $order_way
        );

        function bds_compare($a, $b)
        {
            global $bds_array_sort;

            if (!array_key_exists($bds_array_sort['order_by'], $a)) {
                return 0;
            }
            if (!in_array($bds_array_sort['order_way'], array('asc', 'ASC', 'desc', 'DESC'))) {
                return 0;
            }

            $mult = 1;
            if (strtolower($bds_array_sort['order_way']) === 'desc') {
                $mult = -1;
            }
            if ($a[$bds_array_sort['order_by']] < $b[$bds_array_sort['order_by']]) {
                return -1 * $mult;
            } elseif ($a[$bds_array_sort['order_by']] > $b[$bds_array_sort['order_by']]) {
                return 1 * $mult;
            }
            return 0;
        }
        foreach ($objects as $object_name => &$array) {
            usort($array['list'], 'bds_compare');
        }
        return $objects;
    }
}
