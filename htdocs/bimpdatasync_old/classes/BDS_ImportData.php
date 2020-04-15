<?php

class BDS_ImportData
{

    public static $table = 'bds_object_import_data';
    public $db;
    public $id;
    public $id_process;
    public $object_name;
    public $id_object;
    public $import_reference;
    public $status;
    public $date_add;
    public $date_update;
    protected $objects;

    public function __construct()
    {
        global $db;
        $this->db = new BDSDb($db);
    }

    public function fetch($id)
    {
        $row = $this->db->getRow(self::$table, '`id` = ' . (int) $id);
        if (!is_null($row)) {
            foreach ($row as $property => $value) {
                if (property_exists($this, $property)) {
                    $this->{$property} = $value;
                }
            }
            return true;
        }

        return false;
    }

    public function fetchByObjectId($id_process, $object_name, $id_object)
    {
        $this->id_process = $id_process;
        $this->object_name = $object_name;
        $this->id_object = $id_object;

        $row = $this->db->getRow(self::$table, self::whereObjectId($id_process, $object_name, $id_object));
        if (!is_null($row)) {
            foreach ($row as $property => $value) {
                if (property_exists($this, $property)) {
                    $this->{$property} = $value;
                }
            }
            return true;
        }

        return false;
    }

    public function fetchByObjectReference($id_process, $object_name, $import_reference)
    {
        $this->id_process = $id_process;
        $this->object_name = $object_name;
        $this->import_reference = $import_reference;

        $row = $this->db->getRow(self::$table, self::whereObjectReference($id_process, $object_name, $import_reference));
        if (!is_null($row)) {
            foreach ($row as $property => $value) {
                if (property_exists($this, $property)) {
                    $this->{$property} = $value;
                }
            }
            return true;
        }

        return false;
    }

    public function create()
    {
        if (!isset($this->id_process) || !$this->id_process) {
            return false;
        }

        if (!isset($this->object_name) || !$this->object_name) {
            return false;
        }

        if ((!isset($this->id_object) || !$this->id_object) &&
                (!isset($this->import_reference) || !$this->import_reference)) {
            return false;
        }

        $data = array(
            'id_process'  => (int) $this->id_process,
            'object_name' => $this->db->db->escape($this->object_name),
            'date_add'    => date('Y-m-d H:i:s'),
            'date_update' => date('Y-m-d H:i:s')
        );

        if (isset($this->id_object)) {
            $data['id_object'] = (int) $this->id_object;
        }

        if (isset($this->object_name)) {
            $data['import_reference'] = $this->db->db->escape($this->import_reference);
        }

        if (isset($this->status)) {
            $data['status'] = (int) $this->status;
        }

        $result = $this->db->insert(self::$table, $data, true);
        if ($result > 0) {
            $this->id = $result;
            return true;
        }

        return false;
    }

    public function update()
    {
        if (!isset($this->id) || !$this->id) {
            return $this->create();
        }

        if (!isset($this->id_process) || !$this->id_process) {
            return false;
        }

        if (!isset($this->object_name) || !$this->object_name) {
            return false;
        }

        if ((!isset($this->id_object) || !$this->id_object) &&
                (!isset($this->import_reference) || !$this->import_reference)) {
            return false;
        }

        $data = array(
            'id_process'  => (int) $this->id_process,
            'object_name' => $this->db->db->escape($this->object_name),
            'date_update' => date('Y-m-d H:i:s')
        );

        if (isset($this->id_object)) {
            $data['id_object'] = (int) $this->id_object;
        }

        if (isset($this->object_name)) {
            $data['import_reference'] = $this->db->db->escape($this->import_reference);
        }

        if (isset($this->status)) {
            $data['status'] = (int) $this->status;
        }

        $result = $this->db->update(self::$table, $data, '`id` = ' . (int) $this->id);
        if ($result > 0) {
            return true;
        }

        return false;
    }

    public function delete()
    {
        if (!isset($this->id) || !$this->id) {
            return false;
        }

        $result = $this->db->delete(self::$table, '`id` = ' . (int) $this->id);
        if ($result > 0) {
            return true;
        }
        return false;
    }

    public function setObjects($type, $objects)
    {
        if (!isset($this->id) || !$this->id) {
            return false;
        }

        $result = $this->db->delete(self::$table . '_objects', '`id` = ' . (int) $this->id . ' AND `type` = \'' . $type . '\'');

        if ($result <= 0) {
            return false;
        }

        foreach ($objects as $object) {
            
        }
    }

    protected static function whereObjectId($id_process, $object_name, $id_object)
    {
        $where = '`id_process` = ' . (int) $id_process;
        $where .= ' AND `object_name` = \'' . $object_name . '\'';
        $where .= ' AND `id_object` = ' . (int) $id_object;

        return $where;
    }

    protected static function whereObjectReference($id_process, $object_name, $import_reference)
    {
        $where = '`id_process` = ' . (int) $id_process;
        $where .= ' AND `object_name` = \'' . $object_name . '\'';
        $where .= ' AND `import_reference` = \'' . (int) $import_reference . '\'';

        return $where;
    }

    protected function whereId($id)
    {
        return '`id` = ' . (int) $id;
    }

    public static function getObjectIdByImportReference(BDSDb $db, $id_process, $object_name, $import_reference)
    {
        return $db->getValue(self::$table, 'id_object', self::whereObjectReference($id_process, $object_name, $import_reference));
    }

    public static function getObjectImportReferenceById(BDSDb $db, $id_process, $object_name, $id_object)
    {
        return $db->getValue(self::$table, 'import_reference', self::whereObjectId($id_process, $object_name, $id_object));
    }

    public static function getObjectsIds(BDSDb $db, $id_process, $object_name)
    {
        $where = '`id_process` = ' . (int) $id_process;
        $where .= ' AND `object_name` = \'' . $object_name . '\'';

        $rows = $db->getValues(self::$table, 'id_object', $where);
        if (is_null($rows)) {
            return array();
        }

        return $rows;
    }

    public static function getObjectsImportReferences(BDSDb $db, $id_process, $object_name)
    {
        $where = '`id_process` = ' . (int) $id_process;
        $where .= ' AND `object_name` = \'' . $object_name . '\'';

        $rows = $db->getValues(self::$table, 'import_reference', $where);
        if (is_null($rows)) {
            return array();
        }

        return $rows;
    }

    public static function getAllObjectsList(BDSDb $db, $id_process, $order_by = 'date_update', $order_way = 'desc')
    {
        $where = '';
        if (!is_null($id_process)) {
            $where .= '`id_process` = ' . (int) $id_process;
        }

        $rows = $db->getRows(self::$table, $where, null, 'object');

        $objects = array();

        if (!is_null($rows)) {
            foreach ($rows as $obj) {
                if (!array_key_exists($obj->object_name, $objects)) {
                    $objects[$obj->object_name] = array(
                        'name'       => $obj->object_name,
                        'label'      => BDS_Report::getObjectLabel($obj->object_name, false),
                        'label_plur' => BDS_Report::getObjectLabel($obj->object_name, true),
                        'list'       => array()
                    );
                }
                $data = array(
                    'id_import_data'   => $obj->id,
                    'date_add'         => $obj->date_add,
                    'date_update'      => $obj->date_update,
                    'id_object'        => $obj->id_object,
                    'import_reference' => $obj->import_reference,
                    'status'           => $obj->status
                );
                $objects[$obj->object_name]['list'][] = $data;
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
