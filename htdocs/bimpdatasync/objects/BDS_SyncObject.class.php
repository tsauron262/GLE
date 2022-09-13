<?php

class BDS_SyncObject extends BimpObject
{

    public static $status_list = array();

    // Méthodes statiques: 

    public static function syncExists($module, $object_name, $id_loc = 0, $id_ext = 0)
    {
        if ($id_loc || $id_ext) {
            $where = 'obj_module = \'' . $module . '\'';
            $where .= ' AND obj_name = \'' . $object_name . '\'';

            if ($id_loc) {
                $where .= ' AND id_loc = ' . $id_loc;
            }
            if ($id_ext) {
                $where .= ' AND id_ext = ' . $id_ext;
            }

            return (int) self::getBdb()->getValue('bds_sync_object', 'id', $where);
        }

        return 0;
    }
    
    public static function setObjectToSynchronize($module, $object_name, $id_ext, $data, $id_process)
    {
        
    }
}
