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
        $this->db = new BimpDb($db);
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

    public static function getObjectIdByImportReference(BimpDb $db, $id_process, $object_name, $import_reference)
    {
        return $db->getValue(self::$table, 'id_object', self::whereObjectReference($id_process, $object_name, $import_reference));
    }

    public static function getObjectImportReferenceById(BimpDb $db, $id_process, $object_name, $id_object)
    {
        return $db->getValue(self::$table, 'import_reference', self::whereObjectId($id_process, $object_name, $id_object));
    }

    public static function getObjectsIds(BimpDb $db, $id_process, $object_name)
    {
        $where = '`id_process` = ' . (int) $id_process;
        $where .= ' AND `object_name` = \'' . $object_name . '\'';

        $rows = $db->getValues(self::$table, 'id_object', $where);
        if (is_null($rows)) {
            return array();
        }

        return $rows;
    }

    public static function getObjectsImportReferences(BimpDb $db, $id_process, $object_name)
    {
        $where = '`id_process` = ' . (int) $id_process;
        $where .= ' AND `object_name` = \'' . $object_name . '\'';

        $rows = $db->getValues(self::$table, 'import_reference', $where);
        if (is_null($rows)) {
            return array();
        }

        return $rows;
    }
}
