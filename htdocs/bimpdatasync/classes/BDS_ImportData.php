<?php

class BDS_ImportData
{

    public $db;
    public $id;
    public $id_process;
    public $object_name;
    public $id_object;
    public $import_reference;
    protected $objects;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    protected function whereObject($id_process, $obejct_name, $id_object)
    {
        $where = '`id_process` = ' . (int) $id_process;
        $where .= ' AND `object_name` = \'' . $obejct_name . '\'';
        $where .= ' AND `id_object` = ' . (int) $id_object;

        return $where;
    }

    protected function whereId($id)
    {
        return '`id` = ' . (int) $id;
    }
}
