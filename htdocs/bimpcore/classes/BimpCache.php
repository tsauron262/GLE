<?php

require_once(DOL_DOCUMENT_ROOT . "/bimpcore/classes/BimpCacheRedis.php");

class BimpCache extends BimpCacheRedis
{

//    RÈGLES POUR LES NOMS DES MÉTHODES DE BIMPCACHE: 
//    (Afin de connaître le return d'une méthode sans avoir à rentrer dedans) 
//    
//    geXXXArray : renvoie un tableau sous la forme id => label
//    getXXXList : renvoie un tableau d'IDs.
//    getXXXData : renvoie un tableau de données
//    getXXXObjects : renvoie un tableau d'objets fetchés.                     
//    
//    /!\ Attention, il est ultra-important de faire en sorte que la cache_key soit unique!

    public static $bdb = null;
    public static $cache = array();
    public static $nextBimpObjectCacheId = 1;
    public static $currentMem = 0;
    protected static $memoryLimit = null;
    protected static $memoryMax = null;
    public static $objects_keys = array();
    public static $objects_keys_removed = array();

    public static function getBdb()
    {
        if (is_null(self::$bdb)) {
            global $db;
            self::$bdb = new BimpDb($db);
        }

        return self::$bdb;
    }

    public static function getCacheArray($cache_key, $include_empty = false, $empty_value = 0, $empty_label = '')
    {
        if ($include_empty) {
            $return = array(
                $empty_value => $empty_label
            );

            if ($cache_key && isset(self::$cache[$cache_key])) {
                foreach (self::$cache[$cache_key] as $value => $label) {
                    $return[$value] = $label;
                }
            }
            return $return;
        }

        if ($cache_key && isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        return array();
    }

    public static function cacheExists($cache_key)
    {
        if ((string) $cache_key) {
            return (int) isset(self::$cache[$cache_key]);
        }

        return 0;
    }

    // Objets BIMP:

    public static function isBimpObjectInCache($module, $object_name, $id_object)
    {
        return self::cacheExists('bimp_object_' . $module . '_' . $object_name . '_' . $id_object);
    }

    public static function getBimpObjectInstance($module, $object_name, $id_object = null, $parent = null)
    {
        self::checkMemory();

        // Pas très propre mais seule solution trouvée: 
        global $conf;
        if (isset($conf->global->MAIN_MODULE_BIMPSUPPORT) && $conf->global->MAIN_MODULE_BIMPSUPPORT && $object_name === 'Bimp_Propal' && (int) $id_object) {
            $id_sav = (int) self::getBdb()->getValue('bs_sav', 'id', '`id_propal` = ' . (int) $id_object);
            if ($id_sav) {
                $module = 'bimpsupport';
                $object_name = 'BS_SavPropal';
            }
        }
//        elseif ($object_name === 'Bimp_PropalLine' && (int) $id_object) {
//            $result = self::$bdb->executeS('SELECT s.id FROM ' . MAIN_DB_PREFIX . 'bs_sav s LEFT JOIN ' . MAIN_DB_PREFIX . 'bs_sav_propal_line l ON l.id_obj = s.id_propal WHERE l.id = ' . (int) $id_object);
//            if (isset($result[0]['id']) && (int) $result[0]['id']) {
//                $module = 'bimpsupport';
//                $object_name = 'BS_SavPropalLine';
//            }
//        }

        if (!is_int($id_object)) {
            if (preg_match('/^[0-9]+$/', $id_object)) {
                $id_object = (int) $id_object;
            } else {
                $id_object = 0;
            }
        }

        // retourne une nouvelle instance si $id_object non défini
        if (!(int) $id_object) {
            return BimpObject::getInstance($module, $object_name, null, $parent);
        }

        $cache_key = 'bimp_object_' . $module . '_' . $object_name . '_' . $id_object;
        $is_fetched = true;

        $obj_memory = 0;

        if (isset(self::$cache[$cache_key])) {
            // Instance déjà présente en cache. 
            if (!is_a(self::$cache[$cache_key], $object_name) || !self::$cache[$cache_key]->isLoaded() ||
                    (int) self::$cache[$cache_key]->id !== (int) $id_object) {
                // L'instance ne correspond pas à celle attendue, on la supprime du cache.
                self::$cache[$cache_key] = null;
                unset(self::$cache[$cache_key]);
            } else {
                if (!is_null($parent)) {
                    self::$cache[$cache_key]->parent = $parent;
                }
                $is_fetched = false;
            }
        }

        if (!isset(self::$cache[$cache_key])) {
            $curMem = memory_get_usage();
            $instance = BimpObject::getInstance($module, $object_name, $id_object, $parent);
            $newMem = memory_get_usage();
            $obj_memory = $newMem - $curMem;

            // Ajout au cache
            self::$cache[$cache_key] = $instance;
            if (BimpObject::objectLoaded(self::$cache[$cache_key])) {
                self::$cache[$cache_key]->cache_id = self::$nextBimpObjectCacheId;
                self::$nextBimpObjectCacheId++;
                self::$cache[$cache_key]->checkObject('fetch');
            }
        }

        if (is_a(self::$cache[$cache_key], 'BimpObject')) {
            self::addObjectKey($cache_key, $obj_memory);

            if (BimpDebug::isActive()) {
                BimpDebug::addCacheObjectInfos($module, $object_name, $is_fetched);
            }
        }

        return self::$cache[$cache_key];
    }

    public static function findBimpObjectInstance($module, $object_name, $filters, $return_first = false, $delete_if_multiple = false, $force_delete = false)
    {
        $instance = BimpObject::getInstance($module, $object_name);

        if (is_a($instance, 'BimpObject')) {
            $id_object = null;

            $joins = array();
            $table = $instance->getTable();
            $primary = $instance->getPrimary();

            if ($instance->isDolObject()) {
                $has_extrafields = false;
                $filters = $instance->checkSqlFilters($filters, $has_extrafields, $joins);

                if ($has_extrafields && !isset($joins['ef'])) {
                    $joins['ef'] = array(
                        'alias' => 'ef',
                        'table' => $table . '_extrafields',
                        'on'    => 'ef.fk_object = a.' . $primary
                    );
                }
            }

            $sql = BimpTools::getSqlSelect('a.' . $primary);
            $sql .= BimpTools::getSqlFrom($table, $joins);
            $sql .= BimpTools::getSqlWhere($filters);

            $rows = self::getBdb()->executeS($sql, 'array');

            if (!is_null($rows) && count($rows)) {
                if (count($rows) > 1) {
                    if (!empty($filters) && $delete_if_multiple) {
                        $fl = true;
                        foreach ($rows as $r) {
                            if ($fl) {
                                $fl = false;
                                if ($return_first) {
                                    continue;
                                }
                            }
                            $obj = self::getBimpObjectInstance($module, $object_name, (int) $r[$primary]);
                            if ($obj->isLoaded()) {
                                $warnings = array();
                                $obj->delete($warnings, $force_delete);
                            }
                            self::unsetBimpObjectInstance($module, $object_name, (int) $r[$primary]);
                        }
                    }
                    if (!$return_first) {
                        return null;
                    }
                }
                $id_object = (int) $rows[0][$primary];
            }

            if (is_null($id_object) || !$id_object) {
                return null;
            }

            return self::getBimpObjectInstance($module, $object_name, $id_object);
        }

        return null;
    }

    public static function unsetBimpObjectInstance($module, $object_name, $id_object)
    {
        $cache_key = 'bimp_object_' . $module . '_' . $object_name . '_' . $id_object;
        if (isset(self::$cache[$cache_key])) {
            unset(self::$cache[$cache_key]);
        }

        foreach (self::$objects_keys as $idx => $data) {
            if ($data['k'] == $cache_key) {
                unset(self::$objects_keys[$cache_key]);
                break;
            }
        }
    }

    public static function setBimpObjectInstance($object)
    {
        if (is_a($object, 'BimpObject') && $object->isLoaded()) {
            $cache_key = 'bimp_object_' . $object->module . '_' . $object->object_name . '_' . $object->id;

            self::$cache[$cache_key] = $object;
            self::$cache[$cache_key]->cache_id = self::$nextBimpObjectCacheId;
            self::$nextBimpObjectCacheId++;

            if (BimpDebug::isActive()) {
                BimpDebug::addCacheObjectInfos($object->module, $object->object_name, true);
            }
            self::addObjectKey($cache_key);
        }
    }

    public static function getObjectFilesArray($module, $object_name, $id_object, $with_deleted = false, $with_icons = false)
    {
        if ((int) $id_object) {
            $file = BimpObject::getInstance("bimpcore", "BimpFile");
            $file->checkObjectFiles($module, $object_name, $id_object);

            $cache_key = $module . '_' . $object_name . '_' . $id_object . '_files';
            if ($with_deleted) {
                $cache_key .= '_with_deleted';
            }
            if ($with_icons) {
                $cache_key .= '_with_icons';
            }

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();

                $where = '`parent_module` = \'' . $module . '\'';
                $where .= ' AND `parent_object_name` = \'' . $object_name . '\'';
                $where .= ' AND `id_parent` = ' . (int) $id_object;

                if (!$with_deleted) {
                    $where .= ' AND `deleted` = 0';
                }

                $rows = self::getBdb()->getRows('bimpcore_file', $where, null, 'array', array('id', 'file_name', 'file_ext'), 'id', 'asc');

                if (!is_null($rows)) {
                    foreach ($rows as $r) {
                        $file_name = $r['file_name'] . '.' . $r['file_ext'];

                        if ($with_icons) {
                            self::$cache[$cache_key][(int) $r['id']] = array(
                                'label' => $file_name,
                                'icon'  => BimpTools::getFileIcon($file_name)
                            );
                        } else {
                            self::$cache[$cache_key][(int) $r['id']] = BimpTools::getFileType($file_name) . ' - ' . $file_name;
                        }
                    }
                }
            }

            return self::$cache[$cache_key];
        }
    }

    public static function getExtraFieldsArray($element)
    {
        $cache_key = $element . '_extrafields_array';
        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $where = '`elementtype` = \'' . $element . '\'';
            $rows = self::getBdb()->getRows('extrafields', $where, null, 'array', array('name', 'label'));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][$r['name']] = $r['label'];
                }
            }
        }

        return self::$cache[$cache_key];
    }

    public static function getObjectLinkedObjectsArray(BimpObject $object, $include_empty = false)
    {
        if (!is_null($object) && is_a($object, 'BimpObject')) {
            $cache_key = $object->module . '_' . $object->object_name . '_linked_objects_array';
            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();

                // Objet parent: 
                $parent_object_name = $object->getConf('parent_object', '');
                $parent_id_property = $object->getConf('parent_id_property', '');

                if ($parent_object_name && $parent_id_property && $object->field_exists($parent_id_property)) {
                    $parent_module = $object->getConf('parent_module', $object->module);

                    $parent = BimpObject::getInstance($parent_module, $parent_object_name);
                    $field_label = $object->getConf('fields/' . $parent_id_property . '/label', '');
                    if ($field_label) {
                        $field_label .= ' (Objet "' . BimpTools::ucfirst($parent->getLabel()) . '")';
                    } else {
                        $field_label = BimpTools::ucfirst($parent->getLabel());
                    }
                    self::$cache[$cache_key]['parent'] = $field_label;
                }

                // Objets liés:
                $objects = $object->getConf('objects', array(), false, 'array');
                if (is_array($objects)) {
                    foreach ($objects as $child_name => $params) {
                        $path = 'objects/' . $child_name . '/';
                        $relation = $object->getConf($path . 'relation', '');
                        if ($relation === 'hasOne') {
                            $field_name = $object->getConf('objects/' . $child_name . '/instance/id_object/field_value', '');
                            if ($field_name && $object->field_exists($field_name)) {
                                $instance = $object->getChildObject($child_name);
                                if (is_a($instance, 'BimpObject')) {
                                    $field_label = $object->getConf('fields/' . $field_name . '/label', '', true);
                                    if (!$field_label) {
                                        $field_label = BimpTools::ucfirst($instance->getLabel());
                                    } else {
                                        $field_label .= ' (Objet "' . BimpTools::ucfirst($instance->getLabel()) . '")';
                                    }
                                    self::$cache[$cache_key][$child_name] = $field_label;
                                }
                            }
                        }
                    }
                }
            }

            return self::getCacheArray($cache_key, $include_empty, '', '');
        }

        return array();
    }

    public static function getObjectListChildrenArray(BimpObject $object, $include_empty = false)
    {
        if (!is_null($object) && is_a($object, 'BimpObject')) {
            $cache_key = $object->module . '_' . $object->object_name . '_children_list_array';
            if (!isset(self::$cache[$cache_key])) {
                $objects = $object->getConf('objects', array(), false, 'array');

                if (is_array($objects)) {
                    foreach ($objects as $child_name => $params) {
                        $path = 'objects/' . $child_name . '/';
                        $relation = $object->getConf($path . 'relation', '');
                        if ($relation === 'hasMany') {
                            $instance = $object->getChildObject($child_name);
                            if (is_a($instance, 'BimpObject')) {
                                $children_label = $object->getConf($path . '/label', $object->getConf($path . 'list/title', BimpTools::ucfirst($instance->getLabel('name_plur'))));
                                self::$cache[$cache_key][$child_name] = $children_label;
                            }
                        }
                    }
                }
            }

            return self::getCacheArray($cache_key, $include_empty, '', '');
        }

        return array();
    }

    public static function getObjectListColsArray(BimpObject $object, $include_empty = false)
    {
        if (!is_null($object) && is_a($object, 'BimpObject')) {
            $cache_key = $object->module . '_' . $object->object_name . '_list_cols_array';
            if (!isset(self::$cache[$cache_key])) {
                // Fields: 
                self::$cache[$cache_key] = array();

                if (isset($object->params['fields'])) {
                    foreach ($object->params['fields'] as $field_name) {
                        if ($object->isFieldActivated($field_name)) {
                            if ($object->getConf('fields/' . $field_name . '/viewable', 1, false, 'bool')) {
                                self::$cache[$cache_key][$field_name] = $object->getConf('fields/' . $field_name . '/label', $field_name, true);
                            }
                        }
                    }
                }

                // lists_col: 
                $lists_cols = $object->config->getCompiledParams('lists_cols');

                if (is_array($lists_cols)) {
                    foreach ($lists_cols as $col_name => $params) {
                        $label = BimpTools::getArrayValueFromPath($params, 'label', '');
                        if ($label) {
                            if (isset($object->params['fields'][$col_name])) {
                                if (!$object->isFieldActivated($col_name)) {
                                    continue;
                                }

                                $obj_field_label = $object->getConf('fields/' . $field_name . '/label', '');
                                if ($obj_field_label && $label !== $obj_field_label) {
                                    $label .= ' (Champ "' . $obj_field_label . '")';
                                }
                            }
                            self::$cache[$cache_key][$col_name] = $label;
                        }
                    }
                }
            }

            foreach (self::$cache[$cache_key] as $col_name => $col_label) {
                $info = $object->getConf('lists_cols/' . $col_name . '/info', '');
                if ($info) {
                    self::$cache[$cache_key][$col_name] .= ' - ' . $info;
                }
            }

            return self::getCacheArray($cache_key, $include_empty, '', '');
        }

        return array();
    }

    public static function getObjectStatsListColsArray(BimpObject $object, $list_name)
    {
        if (!is_null($object) && is_a($object, 'BimpObject')) {
            $cache_key = $object->module . '_' . $object->object_name . '_' . $list_name . '_stats_list_cols_array';
            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();

                $bc_list = new BC_StatsList($object, $list_name);

                foreach ($bc_list->params['cols'] as $col_name) {
                    $col_params = $bc_list->fetchParams($bc_list->config_path . '/cols/' . $col_name, $bc_list->col_params);
                    $label = '';
                    if (isset($col_params['label']) && $col_params['label']) {
                        $label = $col_params['label'];
                    }
                    if (isset($col_params['field']) && $col_params['field']) {
                        if (isset($col_params['child']) && $col_params['child']) {
                            $sub_object = $object->getChildObject($col_params['child']);
                            if (!is_null($sub_object) && is_a($sub_object, 'BimpObject')) {
                                if ($label) {
                                    $label .= ' (Champ: ' . $sub_object->getConf('fields/' . $col_params['field'] . '/label', $col_name);
                                    $label .= ' - Objet: ' . BimpTools::ucfirst($sub_object->getLabel()) . ')';
                                } else {
                                    $label = $sub_object->getConf('fields/' . $col_params['field'] . '/label', $col_name);
                                    $label .= ' (Objet: ' . BimpTools::ucfirst($sub_object->getLabel()) . ')';
                                }
                            }
                        } elseif ($object->config->isDefined('fields/' . $col_params['field'] . '/label')) {
                            if ($label) {
                                $label .= ' (Champ: ' . $object->getConf('fields/' . $col_params['field'] . '/label', $col_name) . ')';
                            } else {
                                $label = $object->getConf('fields/' . $col_params['field'] . '/label', $col_name);
                            }
                        }
                    }
                    if (!$label) {
                        $label = $col_name;
                    }
                    self::$cache[$cache_key][$col_name] = $label;
                }

                if ((int) $bc_list->params['configurable'] &&
                        $object->config->isDefined('stats_lists_cols')) {
                    foreach ($object->config->getCompiledParams('stats_lists_cols') as $col_name => $col_params) {
                        if (!isset(self::$cache[$cache_key][$col_name]) || self::$cache[$cache_key][$col_name] === $col_name) {
                            $label = '';
                            if (isset($col_params['label']) && $col_params['label']) {
                                $label = $col_params['label'];
                            }
                            if (isset($col_params['field']) && $col_params['field']) {
                                if (isset($col_params['child']) && $col_params['child']) {
                                    $sub_object = $object->getChildObject($col_params['child']);
                                    if (!is_null($sub_object) && is_a($sub_object, 'BimpObject')) {
                                        if ($label) {
                                            $label .= ' (Champ: ' . $sub_object->getConf('fields/' . $col_params['field'] . '/label', $col_name);
                                            $label .= ' - Objet: ' . BimpTools::ucfirst($sub_object->getLabel()) . ')';
                                        } else {
                                            $label = $sub_object->getConf('fields/' . $col_params['field'] . '/label', $col_name);
                                            $label .= ' (objet: ' . BimpTools::ucfirst($sub_object->getLabel()) . ')';
                                        }
                                    }
                                } elseif ($object->config->isDefined('fields/' . $col_params['field'] . '/label')) {
                                    if ($label) {
                                        $label .= ' (Champ: ' . $object->getConf('fields/' . $col_params['field'] . '/label', $col_name) . ')';
                                    } else {
                                        $label = $object->getConf('fields/' . $col_params['field'] . '/label', $col_name);
                                    }
                                }
                            }
                            if (!$label) {
                                $label = $col_name;
                            }
                            self::$cache[$cache_key][$col_name] = $label;
                        }
                    }
                }
            }

            foreach (self::$cache[$cache_key] as $col_name => $col_label) {
                $info = $object->getConf('stats_lists_cols/' . $col_name . '/info', '');
                if ($info) {
                    self::$cache[$cache_key][$col_name] .= ' - ' . $info;
                }
            }

            return self::$cache[$cache_key];
        }

        return array();
    }

    public static function getObjectNotes(BimpObject $object)
    {
        if (!BimpObject::objectLoaded($object)) {
            return array();
        }

        $cache_key = 'object_' . $object->module . '_' . $object->object_name . '_' . $object->id;

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $instance = BimpObject::getInstance('bimpcore', 'BimpNote');

            $filters = array(
                'obj_type'   => 'bimp_object',
                'obj_module' => $object->module,
                'obj_name'   => $object->object_name,
                'id_obj'     => $object->id
            );

            $filters = BimpTools::merge_array($filters, BimpNote::getFiltersByUser());

            $list = $instance->getList($filters, null, null, 'date_create', 'desc', 'array', array('id'));

            if (!is_null($list)) {
                foreach ($list as $item) {
                    self::$cache[$cache_key][] = BimpObject::getInstance('bimpcore', 'BimpNote', (int) $item['id']);
                }
            }
        }

        return self::$cache[$cache_key];
    }

    public static function getObjectFiltersArray(BimpObject $object, $include_empty = false)
    {
        if (!is_null($object) && is_a($object, 'BimpObject')) {
            $cache_key = $object->module . '_' . $object->object_name . '_filters_array';
            if (!isset(self::$cache[$cache_key])) {
                // Fields: 
                self::$cache[$cache_key] = array();

                if (isset($object->params['fields'])) {
                    foreach ($object->params['fields'] as $field_name) {
                        if (!$object->isFieldActivated($field_name)) {
                            continue;
                        }

                        if ($object->getConf('fields/' . $field_name . '/filterable', 1, false, 'bool')) {
                            self::$cache[$cache_key][$field_name] = $object->getConf('fields/' . $field_name . '/label', $field_name, true);
                        }
                    }
                }

                // custom_filters: 
                $filters = $object->config->getCompiledParams('filters');

                if (is_array($filters)) {
                    foreach ($filters as $filter_name => $params) {
                        if (strpos($filter_name, ':') !== false) {
                            continue;
                        }
                        $label = BimpTools::getArrayValueFromPath($params, 'label', '');
                        if ($label) {
                            if (isset($object->params['fields'][$filter_name])) {
                                if (!$object->isFieldActivated($filter_name)) {
                                    continue;
                                }

                                $obj_field_label = $object->getConf('fields/' . $field_name . '/label', '');
                                if ($obj_field_label && $label !== $obj_field_label) {
                                    $label .= ' (Champ "' . $obj_field_label . '")';
                                }
                            }
                            self::$cache[$cache_key][$filter_name] = $label;
                        }
                    }
                }
            }

            return self::getCacheArray($cache_key, $include_empty, '', '');
        }

        return array();
    }

    public static function getBimpObjectFullListArray($module, $object_name, $include_empty = 0)
    {
        $cache_key = $module . '_' . $object_name . '_full_list_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $instance = BimpObject::getInstance($module, $object_name);
            if (is_a($instance, 'BimpObject')) {
                $name_prop = $instance->getNameProperty();
                $primary = $instance->getPrimary();
                if ($name_prop) {
                    foreach ($instance->getList(array(), null, null, 'id', 'desc', 'array', array($primary, $name_prop)) as $item) {
                        self::$cache[$cache_key][(int) $item[$primary]] = $item[$name_prop];
                    }
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getFilesParentsArray($include_empty = false)
    {
        $cache_key = 'files_parents_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $bimpObject = BimpObject::getInstance('bimpcore', 'BimpObject');

            foreach ($bimpObject->params['objects'] as $name) {
                $has_files = $bimpObject->getConf('objects/' . $name . '/has_files', 0, false, 'bool');
                if ($has_files) {
                    if ($bimpObject->config->isDefined('objects/' . $name . '/instance/bimp_object')) {
                        $module = $bimpObject->getConf('objects/' . $name . '/instance/bimp_object/module', '');
                        $obj_name = $bimpObject->getConf('objects/' . $name . '/instance/bimp_object/name', '');
                        if ($module && $obj_name) {
                            $instance = BimpObject::getInstance($module, $obj_name);
                            $icon = $instance->params['icon'];
                            self::$cache[$cache_key][$name] = array('label' => BimpTools::ucfirst($instance->getLabel()), 'icon' => $icon);
                        }
                    } elseif ($bimpObject->config->isDefined('objects/' . $name . '/instance/dol_object')) {
                        self::$cache[$cache_key][$name] = BimpTools::ucfirst($bimpObject->getConf('objects/' . $name . '/instance/dol_object/label', $name));
                    }
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty, '', '');
    }

    public static function getBimpObjectsArray($with_icons = true, $by_modules = true, $include_empty = false, $with_object_names = true)
    {
        $cache_key = 'bimp_objects_array';
        if ($by_modules) {
            $cache_key .= 'by_modules';
        }
        if ($with_icons) {
            $cache_key .= 'with_icons';
        }

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $files = scandir(DOL_DOCUMENT_ROOT);

            foreach ($files as $f) {
                if (in_array($f, array('.', '..')) || !is_dir(DOL_DOCUMENT_ROOT . '/' . $f) ||
                        !preg_match('/^bimp(.+)$/', $f)) {
                    continue;
                }


                if (file_exists(DOL_DOCUMENT_ROOT . '/' . $f . '/objects') && is_dir(DOL_DOCUMENT_ROOT . '/' . $f . '/objects')) {
                    if ($by_modules) {
                        self::$cache[$cache_key][$f] = array(
                            'group' => array(
                                'label'   => $f,
                                'options' => array()
                            )
                        );
                    }

                    $objects = scandir(DOL_DOCUMENT_ROOT . '/' . $f . '/objects');

                    foreach ($objects as $objFile) {
                        if (in_array($objFile, array('.', '..'))) {
                            continue;
                        }

                        if (preg_match('/^(.+)\.yml$/', $objFile, $matches)) {
                            $object_name = $matches[1];
                            $instance = BimpObject::getInstance($f, $object_name);
                            if (is_a($instance, 'BimpObject') && is_a($instance, $object_name)) {
                                $option = array();
                                if ($with_icons && (string) $instance->params['icon']) {
                                    $option['icon'] = $instance->params['icon'];
                                }
                                $option['label'] = BimpTools::ucfirst($instance->getLabel()) . ($with_object_names ? ' (' . $object_name . ')' : '');

                                if ($by_modules) {
                                    self::$cache[$cache_key][$f]['group']['options'][$f . '-' . $object_name] = $option;
                                } else {
                                    self::$cache[$cache_key][$f . '-' . $object_name] = $option;
                                }
                            }
                        }
                    }
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty, '', '');
    }

    public static function getBimpObjectsList()
    {
        $cache_key = 'bimp_objects_list';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $files = scandir(DOL_DOCUMENT_ROOT);

            foreach ($files as $module) {
                if (in_array($module, array('.', '..')) || !is_dir(DOL_DOCUMENT_ROOT . '/' . $module) ||
                        !preg_match('/^bimp(.+)$/', $module)) {
                    continue;
                }


                if (file_exists(DOL_DOCUMENT_ROOT . '/' . $module . '/objects') && is_dir(DOL_DOCUMENT_ROOT . '/' . $module . '/objects')) {
                    self::$cache[$cache_key][$module] = array();

                    $objects = scandir(DOL_DOCUMENT_ROOT . '/' . $module . '/objects');

                    foreach ($objects as $objFile) {
                        if (in_array($objFile, array('.', '..'))) {
                            continue;
                        }

                        if (preg_match('/^(.+)\.yml$/', $objFile, $matches)) {
                            $object_name = $matches[1];
                            self::$cache[$cache_key][$module][] = $object_name;
                        }
                    }
                }
            }
        }

        return self::$cache[$cache_key];
    }

    public static function getBimpObjectList($module, $object_name, $filters = array())
    {
        $instance = BimpObject::getInstance($module, $object_name);

        if (!is_a($instance, $object_name)) {
            return array();
        }

        $primary = $instance->getPrimary();
        $rows = $instance->getList($filters, null, null, 'id', 'asc', 'array', array($primary));

        $list = array();

        foreach ($rows as $r) {
            $list[] = (int) $r[$primary];
        }

        return $list;
    }

    public static function getBimpObjectObjects($module, $object_name, $filters = array(), $order_by = 'id', $sortorder = 'asc', $joins = array())
    {
        $instance = BimpObject::getInstance($module, $object_name);

        if (!is_a($instance, 'BimpObject')) {
            return array();
        }

        $rows = $instance->getList($filters, null, null, $order_by, $sortorder, 'array', array($instance->getPrimary()), $joins);
        $items = array();

        foreach ($rows as $r) {
            $item = self::getBimpObjectInstance($module, $object_name, (int) $r[$instance->getPrimary()]);
            if (BimpObject::objectLoaded($item)) {
                $items[$item->id] = $item;
            }
        }

        return $items;
    }

    // Objets Dolibarr: 

    public static function getDolObjectInstance($id_object, $module, $file = null, $class = null)
    {
        self::checkMemory();

        if (is_null($file)) {
            $file = $module;
        }

        if (is_null($class)) {
            $class = ucfirst($file);
        }

        BimpTools::loadDolClass($module, $file, $class);

        if (class_exists($class)) {
            global $db;

            if (!(int) $id_object) {
                return new $class($db);
            }

            $cache_key = 'dol_object_' . $module . '_' . $class . '_' . $id_object;

            $is_fetched = false;
            $obj_memory = 0;
            if (!isset(self::$cache[$cache_key])) {
                $curMem = memory_get_usage();
                $instance = new $class($db);
                $newMem = memory_get_usage();
                $obj_memory = $newMem - $curMem;

                if (method_exists($instance, 'fetch')) {
                    $instance->fetch($id_object);
                }

                $is_fetched = true;

                self::$cache[$cache_key] = $instance;
            }

            self::addObjectKey($cache_key, $obj_memory);

            if (BimpDebug::isActive()) {
                BimpDebug::addCacheObjectInfos($module, $class, $is_fetched, 'dol_object');
            }

            return self::$cache[$cache_key];
        }

        BimpCore::addlog('BimpCache: tentative d\'instanciation d\'un objet dolibarr non existant', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', null, array(
            'Module'  => $module,
            'Fichier' => $file,
            'Classe'  => $class
        ));

        return null;
    }

    public static function unsetDolObjectInstance($id_object, $module, $file = null, $class = null)
    {
        if (is_null($file)) {
            $file = $module;
        }

        if (is_null($class)) {
            $class = ucfirst($file);
        }

        $cache_key = 'dol_object_' . $module . '_' . $class . '_' . $id_object;

        if (isset(self::$cache[$cache_key])) {
            unset(self::$cache[$cache_key]);

            foreach (self::$objects_keys as $idx => $data) {
                if ($data['k'] == $cache_key) {
                    unset(self::$objects_keys[$idx]);
                    break;
                }
            }
        }
    }

    // Listes génériques: 

    public static function getDolListArray($id_list, $include_empty = false)
    {
        if (!class_exists('listform')) {
            require_once(DOL_DOCUMENT_ROOT . '/Synopsis_Process/class/process.class.php');
        }

        if (!(int) $id_list) {
            return array();
        }

        $cache_key = 'dol_list_' . $id_list;

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            global $db;
            $list = new listform($db);
            $list->fetch($id_list);

            foreach ($list->lignes as $ligne) {
                self::$cache[$cache_key][$ligne->valeur] = $ligne->label;
            }
        }

        return self::getCacheArray($cache_key, $include_empty, '', '');
    }

    public static function getDbListArray($table, $value_field = 'rowid', $label_field = 'label', $include_empty = false, $empty_value = 0, $empty_label = '')
    {
        $cache_key = 'db_list_from_' . $table . '_by_' . $value_field . '_and_' . $label_field;

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $rows = self::getBdb()->getRows($table, 1, null, 'array', array($value_field, $label_field), $label_field, 'DESC');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][$r[$value_field]] = $r[$label_field];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty, $empty_value, $empty_label);
    }

    // Sociétés: 

    public static function getSocieteContactsArray($id_societe, $include_empty = false)
    {
        $cache_key = '';

        if ((int) $id_societe) {
            $cache_key = 'societe_' . $id_societe . '_contacts_array';
            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array(0 => ""); // => Normalement on ne doit jamais inclure de valeurs vides dans le cache : c'est à ça que sert la variale $include_empty.
                $where = '`fk_soc` = ' . (int) $id_societe;
                $rows = self::getBdb()->getRows('socpeople', $where, null, 'array', array('rowid', 'firstname', 'lastname'));
                if (!is_null($rows)) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][(int) $r['rowid']] = BimpTools::ucfirst($r['firstname']) . ' ' . strtoupper($r['lastname']);
                    }
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getSocieteContratsArray($id_societe)
    {
        if ((int) $id_societe) {
            $cache_key = 'societe_' . $id_societe . '_contrats_array';
            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array(
                    0 => ''
                );

                $where = '`fk_soc` = ' . $id_societe;
                $rows = self::getBdb()->getRows('contrat', $where, null, 'array', array(
                    'rowid', 'ref'
                ));

                if (!is_null($rows)) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][(int) $r['rowid']] = $r['ref'];
                    }
                }
            }

            return self::$cache[$cache_key];
        }

        return array(
            0 => ''
        );
    }

    public static function getSocietePropalsArray($id_societe)
    {
        if ((int) $id_societe) {
            $cache_key = 'societe_' . $id_societe . '_propals_array';

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array(
                    0 => ''
                );
                $where = '`fk_soc` = ' . $id_societe;
                $rows = self::getBdb()->getRows('propal', $where, null, 'array', array(
                    'rowid', 'ref'
                ));

                if (!is_null($rows)) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][(int) $r['rowid']] = $r['ref'];
                    }
                }
            }

            return self::$cache[$cache_key];
        }

        return array();
    }

    public static function getSocieteProductEquipmentsArray($id_societe, $id_product, $with_current_sav = false)
    {
        if ((int) $id_societe) {
            $cache_key = 'societe_' . $id_societe . '_product_' . (int) $id_product . '_equipments_array';

            if ($with_current_sav) {
                $cache_key .= '_sav_incl';
            }

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array(
                    0 => ''
                );
                BimpObject::loadClass('bimpequipment', 'BE_Place');
                $sql = BimpTools::getSqlSelect(array('a.id', 'a.serial'));
                $sql .= BimpTools::getSqlFrom('be_equipment', array(
                            'p' => array(
                                'table' => 'be_equipment_place',
                                'alias' => 'p',
                                'on'    => 'p.id_equipment = a.id'
                            )
                ));
                $sql .= BimpTools::getSqlWhere(array(
                            'a.id_product' => (int) $id_product,
                            'p.position'   => 1,
                            'p.type'       => BE_Place::BE_PLACE_CLIENT,
                            'p.id_client'  => (int) $id_societe
                ));

                $rows = self::getBdb()->executeS($sql, 'array');

                if (!is_null($rows)) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][(int) $r['id']] = $r['serial'];
                    }
                }

                if ($with_current_sav) {
                    $rows = self::getBdb()->getRows('bs_sav', 'id_client = ' . (int) $id_societe . ' AND status < 9', null, 'array', array('id', 'id_equipment'));

                    if (!is_null($rows)) {
                        foreach ($rows as $r) {
                            if ((int) $r['id_equipment'] && !isset(self::$cache[$cache_key][(int) $r['id_equipment']])) {
                                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $r['id_equipment']);

                                if (BimpObject::objectLoaded($equipment)) {
                                    $errors = array();
                                    if ($equipment->isAvailable(0, $errors, array('id_sav' => (int) $r['id']))) {
                                        self::$cache[$cache_key][(int) $r['id_equipment']] = $equipment->getData('serial');
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return self::$cache[$cache_key];
        }

        return array();
    }

    public static function getSocieteEmails(Societe $societe, $with_contacts = true, $with_societe = true)
    {
        if (!BimpObject::objectLoaded($societe)) {
            return array();
        }

        $cache_key = 'societe_emails';
        if ($with_contacts) {
            $cache_key .= '_with_contacts';
        }
        if ($with_societe) {
            $cache_key .= 'with_societe';
        }

        if (!isset(self::$cache[$cache_key])) {
            global $langs;

            if ($with_contacts) {
                self::$cache[$cache_key] = $societe->thirdparty_and_contact_email_array((int) $with_societe);
            } else {
                self::$cache[$cache_key] = array(
                    'thirdparty' => $langs->trans("ThirdParty") . ': ' . dol_trunc($societe->name, 16) . " &lt;" . $societe->email . "&gt;"
                );
            }
        }
        foreach (self::$cache[$cache_key] as $idT => $valT)
            self::$cache[$cache_key][$idT] = str_replace("<", "(", str_replace(">", ")", $valT));

        return self::$cache[$cache_key];
    }

    public static function getSocieteCommerciauxArray($id_societe, $include_empty = false, $with_default = true)
    {
        $cache_key = 'societe_' . $id_societe . '_commerciaux_array';
        if ($with_default)
            $cache_key .= 'with_default';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $sql = 'SELECT u.rowid as id_user, u.firstname,u.lastname FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux sc';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user u ON u.rowid = sc.fk_user';
            $sql .= ' WHERE sc.fk_soc = ' . (int) $id_societe;

            $rows = self::getBdb()->executeS($sql, 'array');
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['id_user']] = $r['firstname'] . ' ' . $r['lastname'];
                }
            }

            if (empty(self::$cache[$cache_key]) && $with_default) {
                $default_id_commercial = (int) BimpCore::getConf('default_id_commercial');
                if ($default_id_commercial) {
                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $default_id_commercial);
                    if (BimpObject::objectLoaded($user)) {
                        self::$cache[$cache_key][$default_id_commercial] = $user->getName();
                    }
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getSocieteCommerciauxObjectsList($id_societe)
    {
        if (!(int) $id_societe) {
            return array();
        }

        $cache_key = 'societe_' . $id_societe . '_commerciaux_list';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            global $db;
            $rows = self::getBdb()->getRows('societe_commerciaux', 'fk_soc = ' . $id_societe);

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $instance = new User($db);
                    if ($instance->fetch($r->fk_user) > 0) {
                        if ($instance->statut == 1) {
                            self::$cache[$cache_key][$comm->fk_user] = $instance;
                        }
                    }
                }
            }

            if (empty(self::$cache[$cache_key])) {
                $default_id_commercial = (int) BimpCore::getConf('default_id_commercial');
                if ($default_id_commercial) {
                    $instance = new User($db);
                    if ($instance->fetch($default_id_commercial) > 0) {
                        self::$cache[$cache_key][$default_id_commercial] = $instance;
                    }
                }
            }
        }

        return self::$cache[$cache_key];
    }

    public static function getTypesSocietesArray($include_empty = false, $active_only = false)
    {
        $cache_key = 'types_socs_array';

        if ($active_only) {
            $cache_key .= '_active';
        }

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $rows = self::getBdb()->getRows('c_typent', ($active_only ? '`active` = 1' : '1'), null, 'array', array('id', 'libelle'));

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['id']] = $r['libelle'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getTypesSocietesCodesArray($include_empty = false, $active_only = false)
    {
        $cache_key = 'types_socs_codes_array';

        if ($active_only) {
            $cache_key .= '_active';
        }

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $rows = self::getBdb()->getRows('c_typent', ($active_only ? '`active` = 1' : '1'), null, 'array', array('id', 'code'));

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['id']] = $r['code'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    // User: 

    public static function getUsersArray($include_empty = 0, $empty_label = '')
    {
        global $conf, $langs;

        if ($conf->global->USER_HIDE_INACTIVE_IN_COMBOBOX) {
            $active_only = true;
        } else {
            $active_only = false;
        }

        $cache_key = 'users';
        if ($active_only) {
            $cache_key .= '_active_only';
        }
        if (!isset(self::$cache[$cache_key])) {
            if ($active_only) {
                $where = '`statut` != 0';
            } else {
                $where = '1';
            }
            if (empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)) {
                $order_by = 'firstname';
            } else {
                $order_by = 'lastname';
            }
            $rows = self::getBdb()->getRows('user', $where, null, 'object', array('rowid', 'firstname', 'lastname'), $order_by, 'asc');
            if (!is_null($rows)) {
                $userstatic = new User(self::getBdb()->db);
                foreach ($rows as $r) {
                    $userstatic->id = $r->rowid;
                    $userstatic->lastname = $r->lastname;
                    $userstatic->firstname = $r->firstname;

                    if (empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)) {
                        $fullNameMode = 1;
                    } else {
                        $fullNameMode = 0;
                    }
                    self::$cache[$cache_key][$r->rowid] = $userstatic->getFullName($langs, $fullNameMode, -1);
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty, 0, $empty_label);
    }

    public static function getUserGroupsArray($include_empty = 1, $nom_url = 0)
    {
        $cache_key = 'users_groups';

        if ($nom_url) {
            $cache_key .= '_nom_url';
        }

        $cache_key .= '_array';

        if (!isset(self::$cache[$cache_key])) {

            // Ne pas faire ça, c'est géré via getCacheArray(): 
//            if ($include_empty)
//                self::$cache[$cache_key] = array("" => "");
//            else
//                self::$cache[$cache_key] = array();


            $rows = self::getBdb()->getRows('usergroup', '1', null, 'object', array('rowid', 'nom'), 'nom', 'asc');
            if (!is_null($rows)) {
                $icon = BimpRender::renderIcon('fas_users', 'iconLeft');
                foreach ($rows as $r) {
                    if ($nom_url) {
                        $url = BimpTools::getDolObjectUrl('UserGroup', $r->rowid);
                        self::$cache[$cache_key][$r->rowid] = '<a href="' . $url . '" target="_blank">' . $icon . $r->nom . '</a>';
                    } else {
                        self::$cache[$cache_key][$r->rowid] = $r->nom;
                    }
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getUserUserGroupsArray($id_user, $include_empty = 0, $nom_url = 0)
    {
        $cache_key = 'user_' . $id_user . '_usergroups';

        if ($nom_url) {
            $cache_key .= '_nom_url';
        }

        $cache_key .= '_array';

        if (!isset(self::$cache[$cache_key])) {

            $groups = self::getUserGroupsArray($include_empty, $nom_url);
            $rows = self::getBdb()->getRows('usergroup_user', 'fk_user = ' . (int) $id_user, null, 'array', array('fk_usergroup'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    if (isset($groups[(int) $r['fk_usergroup']])) {
                        self::$cache[$cache_key][(int) $r['fk_usergroup']] = $groups[(int) $r['fk_usergroup']];
                    }
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getUserUserGroupsList($id_user)
    {
        $cache_key = 'user_' . $id_user . '_usergroups_list';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();
            $rows = self::getBdb()->getRows('usergroup_user', 'fk_user = ' . (int) $id_user, null, 'array', array('fk_usergroup'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][] = (int) $r['fk_usergroup'];
                }
            }
        }

        return self::$cache[$cache_key];
    }

    public static function getUserCentresArray()
    {

        $centres = array(
            '' => ''
        );

        global $user;
        if (BimpObject::objectLoaded($user)) {
            $cache_key = 'user_' . $user->id . '_centres_array';
            if (!isset(self::$cache[$cache_key])) {
                $userCentres = explode(' ', $user->array_options['options_apple_centre']);
                $centres = self::getCentres();

                if (count($userCentres)) {
                    foreach ($userCentres as $code) {
                        if (preg_match('/^ ?([A-Z]+) ?$/', $code, $matches)) {
                            if (isset($centres[$matches[1]])) {
                                self::$cache[$cache_key][$matches[1]] = $centres[$matches[1]]['label'];
                            }
                        }
                    }
                }

                if (count($centres) <= 1) {
                    foreach ($centres as $code => $centre) {
                        self::$cache[$cache_key][$code] = $centre['label'];
                    }
                }
            }

            return self::$cache[$cache_key];
        }

        return array();
    }

    // User Groups: 

    public static function getGroupIds($idUser)
    {
        $cache_key = 'groupsIduser' . $idUser;
        if (!isset(self::$cache[$cache_key])) {
            require_once(DOL_DOCUMENT_ROOT . "/user/class/usergroup.class.php");
            $userGroup = new UserGroup(self::getBdb()->db);

            foreach ($userGroup->listGroupsForUser($idUser, false) as $obj) {
                self::$cache[$cache_key][] = $obj->id;
            }
        }
        return self::getCacheArray($cache_key);
    }

    public static function getGroupUsersList($id_group)
    {
        $cache_key = 'user_group_' . $id_group . '_users_list';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $rows = self::getBdb()->getRows('usergroup_user', '`fk_usergroup` = ' . (int) $id_group, null, 'array', array('fk_user'));
            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][] = (int) $r['fk_user'];
                }
            }
        }

        return self::$cache[$cache_key];
    }

    // MySoc: 

    public static function getBankAccountsArray($include_empty = false)
    {
        if (!isset(self::$cache['comptes_bancaires'])) {
            self::$cache['comptes_bancaires'] = array();

            $rows = self::getBdb()->getRows('bank_account');
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache['comptes_bancaires'][(int) $r->rowid] = $r->label;
                }
            }
        }

        return self::getCacheArray('comptes_bancaires', $include_empty);
    }

    // Product: 

    public static function getProductEquipmentsArray($id_product = 0, $include_empty = false, $empty_label = '')
    {
        if ((int) $id_product) {
            $cache_key = 'product_' . $id_product . '_equipments_array';

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();

                $rows = self::getBdb()->getRows('be_equipment', '`id_product` = ' . (int) $id_product, null, 'array', array('id', 'serial'));

                if (!is_null($rows) && count($rows)) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][(int) $r['id']] = $r['serial'];
                    }
                }
            }
        } else {
            $cache_key = '';
        }

        return self::getCacheArray($cache_key, $include_empty, 0, $empty_label);
    }

    public static function getProductFournPricesArray($id_product, $include_empty = false, $empty_label = '')
    {
        if (((int) $id_product)) {
            $cache_key = 'product_' . $id_product . '_fourn_prices_array';

            if (!isset(self::$cache[$cache_key])) {
                BimpObject::loadClass('bimpcore', 'Bimp_Product');

                self::$cache[$cache_key] = Bimp_Product::getFournisseursPriceArray((int) $id_product, 0, 0, false);
            }
        } else {
            $cache_key = '';
        }

        return self::getCacheArray($cache_key, $include_empty, 0, $empty_label);
    }

    public static function getProductFournisseursArray($id_product, $include_empty = false, $empty_label = '')
    {
        $cache_key = 'product_' . $id_product . '_fournisseurs_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $product = self::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);

            if (BimpObject::objectLoaded($product)) {
                $list = $product->dol_object->list_suppliers();
                if (!empty($list)) {
                    $rows = self::getBdb()->getRows('societe', '`rowid` IN (' . implode(',', $list) . ')', null, 'array', array('rowid', 'nom', 'code_fournisseur'));
                    if (!is_null($rows)) {
                        foreach ($rows as $r) {
                            if (!isset(self::$cache[$cache_key][(int) $r['rowid']])) {
                                self::$cache[$cache_key][(int) $r['rowid']] = $r['code_fournisseur'] . ' - ' . $r['nom'];
                            }
                        }
                    }
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty, 0, $empty_label);
    }

    public static function getProductCategoriesArray($id_product, $include_empty = false, $empty_label = '')
    {
        $cache_key = 'product_' . $id_product . '_categories_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $sql = BimpTools::getSqlSelect(array(
                        'a.fk_categorie',
                        'c.label'
            ));
            $sql .= BimpTools::getSqlFrom('categorie_product', array(
                        'c' => array(
                            'table' => 'categorie',
                            'alias' => 'c',
                            'on'    => 'a.fk_categorie = c.rowid'
                        )
            ));
            $sql .= BimpTools::getSqlWhere(array(
                        'a.fk_product' => (int) $id_product
            ));

            $rows = self::getBdb()->executeS($sql, 'array');

            if (!is_null($rows) && count($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['fk_categorie']] = $r['label'];
                }
            }
        }



        return self::getCacheArray($cache_key, $include_empty, 0, $empty_label);
    }

    // Catégories: 

    public static function getMarquesArray($include_empty = true, $empty_label = '')
    {
        $cache_key = 'marques_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $where = 'fk_parent IN (' . BimpCore::getConf('marques_parent_categories') . ')';
            $rows = self::getBdb()->getRows('categorie', $where, null, 'array', array('rowid', 'label'));
            foreach ($rows as $r) {
                self::$cache[$cache_key][(int) $r['rowid']] = $r['label'];
            }
        }

        return self::getCacheArray($cache_key, $include_empty, 0, $empty_label);
    }

    public static function getMarquesList()
    {
        $cache_key = 'marques_list';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $where = 'fk_parent IN (' . BimpCore::getConf('marques_parent_categories') . ')';
            $rows = self::getBdb()->getRows('categorie', $where, null, 'array', array('rowid'));
            foreach ($rows as $r) {
                self::$cache[$cache_key][] = (int) $r['rowid'];
            }
        }

        return self::$cache[$cache_key];
    }

    public static function getGammesMaterielArray($include_empty = true, $empty_label = '')
    {
        $cache_key = 'gamme_materiel_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();
            BimpObject::loadClass('bimpcore', 'Bimp_Categorie');
            $parent_categories = explode(',', BimpCore::getConf('gamme_materiel_parent_categories'));

            if (!empty($parent_categories)) {
                foreach ($parent_categories as $id_parent) {
                    foreach (Bimp_Categorie::getCategoriesArrayByParent($id_parent) as $id_cat => $label) {
                        self::$cache[$cache_key][(int) $id_cat] = $label;
                    }
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty, 0, $empty_label);
    }

    public static function getGammesMaterielList()
    {
        $cache_key = 'gamme_materiel_list';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();
            BimpObject::loadClass('bimpcore', 'Bimp_Categorie');
            $parent_categories = explode(',', BimpCore::getConf('gamme_materiel_parent_categories'));

            if (!empty($parent_categories)) {
                foreach ($parent_categories as $id_parent) {
                    self::$cache[$cache_key] = BimpTools::merge_array(self::$cache[$cache_key], Bimp_Categorie::getCategoriesListByParent($id_parent));
                }
            }
        }

        return self::$cache[$cache_key];
    }

    public static function getCategoriesFullTree($type = 'product')
    {
        $cache_key = $type . '_categories_full_tree';

        if (!isset(self::$cache[$cache_key])) {
            BimpTools::loadDolClass('categories', 'categorie');

            $categorie = new Categorie(self::getBdb()->db);

            $tree = $categorie->get_full_arbo($type);

            self::$cache[$cache_key] = BimpTools::makeTreeFromArray($tree, 0, 'fk_parent');
        }

        return self::$cache[$cache_key];
    }

    public static function getCategoriesArray($type = 'product', $full_label = true)
    {
        $cache_key = $type . '_categories_array';

        if ($full_label) {
            $cache_key .= '_full_label';
        }

        if (!isset(self::$cache[$cache_key])) {
            BimpTools::loadDolClass('categories', 'categorie');

            $categorie = new Categorie(self::getBdb()->db);

            $rows = $categorie->get_full_arbo($type);

            foreach ($rows as $r) {
                if ($full_label) {
                    self::$cache[$cache_key][(int) $r['id']] = $r['fulllabel'];
                } else {
                    self::$cache[$cache_key][(int) $r['id']] = $r['label'];
                }
            }
        }

        return self::$cache[$cache_key];
    }

    public static function getProductsTagsByTypeArray($type, $include_empty = true, $key = 'id')
    {
        $cache_key = 'products_tags_' . $type . '_by_' . $key;

        if (!isset(self::$cache[$cache_key])) {
            if (in_array($key, array('id', 'label'))) {
                $rows = self::getBdb()->getRows('bimp_c_values8sens', '`type` = \'' . $type . '\'', null, 'array', array('id', 'label'), 'label', 'ASC');
                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        switch ($key) {
                            case 'id':
                                self::$cache[$cache_key][$r['id']] = $r['label'];
                                break;

                            case 'label':
                                self::$cache[$cache_key][$r['label']] = $r['id'];
                                break;
                        }
                    }
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    // Emails: 

    public static function getEmailTemplatesArray($email_type, $include_empty = false)
    {
        $cache_key = 'email_templates_' . $email_type;
        if (is_null(self::$cache[$cache_key])) {
            global $user;

            self::$cache[$cache_key] = array();
            if ($include_empty) {
                self::$cache[$cache_key][0] = '';
            }

            $where = '`type_template` = \'' . $email_type . '\'';
            $where .= ' AND (`fk_user` IS NULL OR `fk_user` = 0';
            if (BimpObject::objectLoaded($user)) {
                $where .= ' OR `fk_user` = ' . (int) $user->id;
            }
            $where .= ') AND `active` = 1';

            $rows = self::getBdb()->getRows('c_email_templates', $where, null, 'array', array(
                'rowid', 'label'
                    ), 'position', 'asc');

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['rowid']] = $r['label'];
                }
            }
        }

        return self::$cache[$cache_key];
    }

    public static function getEmailTemplateData($id_model)
    {
        if ((int) $id_model) {
            $cache_key = 'email_template_' . $id_model;

            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = self::getBdb()->getRow('c_email_templates', '`rowid` = ' . (int) $id_model, array('label', 'topic', 'content', 'content_lines', 'joinfiles'), 'array');
            }

            return self::$cache[$cache_key];
        }

        return null;
    }

    // Divers: 

    public static function getTaxes($id_country = 1, $active_only = true, $include_empty = false, $key_field = 'rowid')
    {
        $id_country = (int) $id_country;
        $cache_key = 'taxes_' . $id_country . '_by_' . $key_field;

        if ($active_only) {
            $cache_key .= '_active_only';
        }

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();
            $rows = self::getBdb()->getRows('c_tva', '`fk_pays` = ' . $id_country . ($active_only ? ' AND `active` = 1' : ''), null, 'array', array($key_field, 'taux'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][$r[$key_field]] = $r['taux'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getTaxesByRates($id_country = 1, $active_only = true, $include_empty = false)
    {
        $id_country = (int) $id_country;
        $cache_key = 'taxes_' . $id_country . '_by_rates';

        if ($active_only) {
            $cache_key .= '_active_only';
        }

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();
            $rows = self::getBdb()->getRows('c_tva', '`fk_pays` = ' . $id_country . ($active_only ? ' AND `active` = 1' : ''), null, 'array', array('rowid', 'taux', 'note'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][$r['taux']] = BimpTools::displayFloatValue((float) $r['taux'], 1) . '% - ' . $r['note'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getCentres()
    {
        if (!isset(self::$cache['centres'])) {
            global $tabCentre;

            if (!is_array($tabCentre)) {
                require_once DOL_DOCUMENT_ROOT . '/bimpsupport/centre.inc.php';
            }

            self::$cache['centres'] = array();

            foreach ($tabCentre as $code => $centre) {
                self::$cache['centres'][$code] = array(
                    'code'        => $code,
                    'label'       => $centre[2],
                    'tel'         => $centre[0],
                    'mail'        => $centre[1],
                    'address'     => $centre[7],
                    'zip'         => $centre[5],
                    'town'        => $centre[6],
                    'id_entrepot' => $centre[8]
                );
            }
        }

        return self::$cache['centres'];
    }

    public static function getCentresArray()
    {
        if (!isset(self::$cache['centres_array'])) {
            self::$cache['centres_array'] = array();

            foreach (self::getCentres() as $code => $centre) {
                self::$cache['centres_array'][$code] = $centre['label'];
            }
        }

        return self::getCacheArray('centres_array', true, '', '');
    }

    public static function getEntrepotsArray($include_empty = false, $has_commissions_only = false)
    {
        $cache_key = 'entrepots';
        if ($has_commissions_only) {
            $cache_key .= '_has_commissions_only';
        }
        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $where = '';

            if ($has_commissions_only) {
                $where .= '`has_entrepot_commissions` = 1';
            } else {
                $where .= '1';
            }

            $rows = self::getBdb()->getRows('entrepot', $where, null, 'object', array('rowid', 'ref', 'lieu'), 'ref', 'asc');
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r->rowid] = $r->ref . ' - ' . $r->lieu;
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getEntrepotsShipTos($include_empty = false)
    {
        if (!isset(self::$cache['entrepots_ship_tos'])) {
            self::$cache['entrepots_ship_tos'] = array();

            $rows = self::getBdb()->getRows('entrepot', '`ship_to` != \'\' AND `ship_to` IS NOT NULL', null, 'object', array('rowid', 'ship_to'), 'ref', 'asc');
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache['entrepots_ship_tos'][(int) $r->rowid] = $r->ship_to;
                }
            }
        }

        return self::getCacheArray('entrepots_ship_tos', $include_empty);
    }

    public static function getCondReglementsArray()
    {
        if (!isset(self::$cache['cond_reglements_array'])) {
            $rows = self::getBdb()->getRows('c_payment_term', '`active` > 0', null, 'array', array('rowid', 'libelle'), 'sortorder');

            self::$cache['cond_reglements_array'] = array();
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache['cond_reglements_array'][(int) $r['rowid']] = $r['libelle'];
                }
            }
        }

        return self::getCacheArray('cond_reglements_array', 1, '', '');
    }

    public static function getModeReglementsArray($key = 'id', $active_only = false)
    {
        $cache_key = 'mode_reglements_by_' . $key;
        if ($active_only) {
            $cache_key .= '_active_only';
        }

        if (!isset(self::$cache[$cache_key])) {
            if (!class_exists('Form')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
            }

            $form = new Form(self::getBdb()->db);
            $form->load_cache_types_paiements();

            self::$cache[$cache_key] = array();

            foreach ($form->cache_types_paiements as $id_payment => $payment_data) {
                if (!$active_only || ($active_only && (int) $payment_data['active'])) {
                    switch ($key) {
                        case 'id':
                            self::$cache[$cache_key][(int) $payment_data['id']] = $payment_data['label'];
                            break;

                        case 'code':
                            self::$cache[$cache_key][$payment_data['code']] = $payment_data['label'];
                            break;
                    }
                }
            }
        }

        $empty_value = '';
        if ($key === 'id') {
            $empty_value = 0;
        }

        return self::getCacheArray($cache_key, 1, $empty_value, '');
    }

    public static function getAvailabilitiesArray()
    {
        if (!isset(self::$cache['availabilities_array'])) {
            if (!class_exists('Form')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
            }

            $form = new Form(self::getBdb()->db);
            $form->load_cache_availability();

            self::$cache['availabilities_array'] = array();

            foreach ($form->cache_availability as $id => $availability) {
                self::$cache['availabilities_array'][(int) $id] = $availability['label'];
            }
        }

        return self::getCacheArray('availabilities_array', 1);
    }

    public static function getDemandReasonsArray()
    {
        if (!isset(self::$cache['demand_reasons_array'])) {
            if (!class_exists('Form')) {
                require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
            }

            $form = new Form(self::getBdb()->db);
            $form->loadCacheInputReason();

            self::$cache['demand_reasons_array'] = array(
                0 => ''
            );

            foreach ($form->cache_demand_reason as $id => $dr) {
                self::$cache['demand_reasons_array'][(int) $id] = $dr['label'];
            }
        }

        return self::$cache['demand_reasons_array'];
    }

    public static function getCommandeMethodsArray($include_empty = true)
    {
        $cache_key = 'commande_methods_array';

        if (!isset(self::$cache[$cache_key])) {
            $rows = self::getBdb()->getRows('c_input_method', '`active` = 1', null, 'array', array('rowid', 'libelle'));

            self::$cache[$cache_key] = array();

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['rowid']] = $r['libelle'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getCountriesArray($active_only = false, $key_field = 'rowid', $include_empty = false)
    {
        $cache_key = 'countries_array_by' . $key_field;
        if ($include_empty) {
            $cache_key .= '_active_only';
        }

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            if ($active_only) {
                $where = '`active` > 0';
            } else {
                $where = '1';
            }
            $rows = self::getBdb()->getRows('c_country', $where, null, 'array', array($key_field, 'label'));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][$r[$key_field]] = $r['label'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getCountriesCodesArray($active_only = false, $include_empty = false)
    {
        $cache_key = 'countries_codes_array';
        if ($include_empty) {
            $cache_key .= '_active_only';
        }

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            if ($active_only) {
                $where = '`active` > 0';
            } else {
                $where = '1';
            }
            $rows = self::getBdb()->getRows('c_country', $where, null, 'array', array('rowid', 'code'));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][$r['rowid']] = $r['code'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getStatesArray($country = 0, $country_key_field = 'rowid', $active_only = false, $include_empty = false)
    {
        $cache_key = 'states_array';
        if ($country) {
            $cache_key .= '_country_' . $country;
        }
        if ($active_only) {
            $cache_key .= 'active_only';
        }

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $filters = array();

            if ($country) {
                $filters['c.' . $country_key_field] = $country;
            }
            if ($active_only) {
                $filters['a.active'] = 1;
                $filters['r.active'] = 1;
                $filters['c.active'] = 1;
            }

            $sql = BimpTools::getSqlSelect(array('rowid', 'nom'));
            $sql .= BimpTools::getSqlFrom('c_departements', array(
                        array(
                            'table' => 'c_regions',
                            'alias' => 'r',
                            'on'    => 'a.fk_region = r.code_region',
                        ), array(
                            'table' => 'c_country',
                            'alias' => 'c',
                            'on'    => 'r.fk_pays = c.rowid'
                        )
            ));
            $sql .= BimpTools::getSqlWhere($filters);
            $sql .= BimpTools::getSqlOrderBy('c.code', 'asc', 'a', 'code_departement', 'asc');

            $rows = self::getBdb()->executeS($sql, 'array');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['rowid']] = $r['nom'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getJuridicalstatusArray($country = 0, $country_key_field = 'code', $active_only = false, $include_empty = false)
    {
        $cache_key = 'juridicalstatus_array';
        if ($country) {
            $cache_key .= '_country_' . $country;
        }
        if ($active_only) {
            $cache_key .= 'active_only';
        }

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $filters = array();

            if ($country) {
                $filters['c.' . $country_key_field] = $country;
            }
            if ($active_only) {
                $filters['a.active'] = 1;
                $filters['c.active'] = 1;
            }

            $sql = BimpTools::getSqlSelect(array('code', 'libelle'));
            $sql .= BimpTools::getSqlFrom('c_forme_juridique', array(
                        array(
                            'table' => 'c_country',
                            'alias' => 'c',
                            'on'    => 'a.fk_pays = c.rowid'
                        )
            ));
            $sql .= BimpTools::getSqlWhere($filters);
            $sql .= BimpTools::getSqlOrderBy('c.code', 'asc');

            $rows = self::getBdb()->executeS($sql, 'array');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['code']] = $r['libelle'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getSecteursArray()
    {
        if (!BimpCore::getConf("USE_SECTEUR")) {
            return array();
        }

        if (!isset(self::$cache['secteurs_array'])) {
            self::$cache['secteurs_array'] = array(
                '' => ''
            );

            $rows = self::getBdb()->getRows('bimp_c_secteur');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache['secteurs_array'][$r->clef] = $r->valeur;
                }
            }
        }

        return self::$cache['secteurs_array'];
    }

    public static function getSystemsArray()
    {
        return array(
            300  => "iOs",
            1120 => "MAC OS 11.2 (Big Sur)",
            1015 => "MAC OS 10.15 (Catalina)",
            1014 => "MAC OS 10.14 (Mojave)",
            1013 => "MAC OS 10.13 (High Sierra)",
            1012 => "MAC OS 10.12 (Sierra)",
            1011 => "MAC OS 10.11 (El Capitan)",
            1010 => "MAC OS 10.10 (Yosemite)",
            109  => "MAC OS 10.9 (Mavericks)",
            108  => "MAC OS 10.8 (Mountain Lion)",
            1075 => "MAC OS 10.7.5 (Lion)",
            107  => "MAC OS 10.7",
            106  => "MAC OS 10.6 (Snow Leopard)",
            9911 => "Windows 10",
            203  => "Windows 8",
            204  => "Windows 7",
            202  => "Windows Vista",
            201  => "Windows XP",
            8801 => "Linux",
            2    => "Indéterminé",
            1    => "Autre"
        );
    }

    public static function getCivilitiesArray($include_empty = false, $active_only = false, $include_codes_keys = false)
    {
        if (!$active_only && !$include_codes_keys) {
            return self::getDbListArray('c_civility', 'code', 'label', $include_empty, 0, '');
        }

        $cache_key = 'civilities_array';

        if ($active_only) {
            $cache_key .= '_active_only';
        }

        if ($include_codes_keys) {
            $cache_key .= '_with_codes_keys';
        }

        if (!isset(self::$cache[$cache_key])) {
            $rows = self::getBdb()->getRows('c_civility', '`active` = 1', null, 'array', array('code', 'label', 'code'), 'label', 'DESC');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][$r['code']] = $r['label'];
                }

                if ($include_codes_keys) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][$r['code']] = $r['label'];
                    }
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getDevsNamesArray($include_empty = false)
    {
        $names = array();

        if ($include_empty) {
            $names[''] = '';
        }

        foreach (BimpCore::$dev_mails as $name => $email) {
            $names[$name] = ucfirst($name);
        }

        return $names;
    }

    // Logs: 

    public static function getBimpLogsData()
    {
        $cache_key = 'bimp_logs_data';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();
            $rows = self::getBdb()->getRows('bimpcore_log', 'processed = 0', null, 'array', array('id', 'type', 'level', 'msg', 'extra_data'));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::addBimpLog((int) $r['id'], $r['type'], $r['level'], $r['msg'], $r['extra_data']);
                }

                // Check du nombre de logs: 
                if (!BimpCore::isModeDev()) {
                    $mail_send = BimpCore::getConf('bimpcore_to_much_logs_email_send', 0);
                    if (count($rows) > 500) {
                        if (!$mail_send) {
                            $message = 'Il y a plus de 500 entrées à traiter dans les logs.' . "\n\n";
                            $message .= DOL_URL_ROOT . '/bimpcore/index.php?fc=admin&tab=logs' . "\n\n";

                            mailSyn2("TROP DE LOGS", "dev@bimp.fr", "admin@bimp.fr", $message);
                            BimpCore::setConf('bimpcore_to_much_logs_email_send', 1);
                        }
                    } elseif ($mail_send) {
                        BimpCore::setConf('bimpcore_to_much_logs_email_send', 0);
                    }
                }
            }
        }

        return self::$cache[$cache_key];
    }

    public static function addBimpLog($id_log, $type, $level, $msg, $extra_data)
    {
        $cache_key = 'bimp_logs_data';

        if (isset(self::$cache[$cache_key]) && !isset(self::$cache[$cache_key][$type][$level][$id_log])) {
            if (!isset(self::$cache[$cache_key][$type])) {
                self::$cache[$cache_key][$type] = array();
            }

            if (!isset(self::$cache[$cache_key][$type][$level])) {
                self::$cache[$cache_key][$type][$level] = array();
            }

            self::$cache[$cache_key][$type][$level][$id_log] = array(
                'msg'        => $msg,
                'extra_data' => (is_array($extra_data) ? json_encode($extra_data) : (string) $extra_data)
            );
        }
    }

    public static function bimpLogExists($type, $level, $msg, $extra_data)
    {
        $logs = self::getBimpLogsData();

        if (isset($logs[$type][$level])) {
            foreach ($logs[$type][$level] as $id_log => $log_data) {
                if (isset($log_data['msg']) && $log_data['msg'] === (string) $msg) {
                    if (isset($log_data['extra_data']) && $log_data['extra_data'] === (is_array($extra_data) ? json_encode($extra_data) : (string) $extra_data)) {
                        return (int) $id_log;
                    }
                }
            }
        }

        return 0;
    }

    // Gestion de la mémoire: 

    public static function getMemoryLimits()
    {
        if (is_null(self::$memoryLimit)) {
            $memory_limit = ini_get('memory_limit');
            if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
                if ($matches[2] == 'M') {
                    $memory_limit = $matches[1] * 1000000;
                } else if ($matches[2] == 'K') {
                    $memory_limit = $matches[1] * 1000;
                }
            } else {
                $memory_limit = 24000000;
            }

            self::$memoryLimit = array(
                'max' => $memory_limit,
                '85'  => floor((int) $memory_limit * 0.85),
                '75'  => floor((int) $memory_limit * 0.75),
                '50'  => floor((int) $memory_limit * 0.50),
            );
        }

        return self::$memoryLimit;
    }

    public static function checkMemory()
    {
        if (!BimpCore::getConf('bimpcache_check_memory_enabled', 1)) {
            return;
        }

        if (!gc_enabled()) {
            gc_enable();
        }

        if (gc_enabled()) {
            $memLims = self::getMemoryLimits();

            $cur_mem = memory_get_usage();

            if ($cur_mem > $memLims['50']) {
//                echo 'nInCache: ' . count(self::$cache) . ' - MEM ' . $cur_mem . '<br/>';
                if ($cur_mem > $memLims['85']) {
                    // Urgence absolue, on vide la totalité du cache (Et du debug)
                    self::$cache = array();
                    self::$objects_keys = array();
                    self::$objects_keys_removed = array();
                    BimpDebug::freeAll(true);
                    gc_collect_cycles();
                } elseif ($cur_mem > $memLims['75']) {
                    // Urgence, on suppr. tous les objets du cache + une partie du debug
                    foreach (self::$objects_keys as $idx => $data) {
                        if (isset(self::$cache[$data['k']])) {
                            unset(self::$cache[$data['k']]);
                        }

                        $data['time'] = microtime();
                        self::$objects_keys_removed[] = $data;
                        unset(self::$objects_keys[$idx]);
                    }

                    if (BimpDebug::isActive()) {
                        BimpDebug::addDebugTime('Dépassement 75% mémoire limite');
                    }
                    gc_collect_cycles();
                } else {
                    // on suppr. du debug: 
                    BimpDebug::freeByTypes(array('php', 'sql', 'bimpdb_sql'));
                    gc_collect_cycles();

                    $cur_mem = memory_get_usage();

                    if ($cur_mem > $memLims['50']) {
                        // on libère des objets du cache: 
                        self::freeObjectsCache($cur_mem - $memLims['50']);
                        gc_collect_cycles();
                    }
                }
            }
        }
    }

    public static function addObjectKey($obj_key, $memory = 0)
    {
        $n = 1;
        foreach (self::$objects_keys as $idx => $data) {
            if ($data['k'] == $obj_key) {
                $n += (int) $data['n'];
                if (!$memory) {
                    $memory = $data['m'];
                }
                unset(self::$objects_keys[$idx]);
            }
        }

        self::$objects_keys[] = array(
            'k' => $obj_key, // Key
            'n' => $n, // Nb used
            'm' => $memory // memory
        );
    }

    public static function freeObjectsCache($min_memory = 0)
    {
        $min_used = 1;
        $min_found = 0;
        $max_found = 0;

        $n = 0;
        $memFree = 0;
        for ($i = 0; $i < 10; $i++) { // Par précaution, on ne parcours que 10 fois la boucle. 
            foreach (self::$objects_keys as $idx => $data) {
                if ($data['n'] <= $min_used) {
                    // On retire l'objet du cache: 
                    if (isset(self::$cache[$data['k']])) {
                        unset(self::$cache[$data['k']]);
                    }

                    $min_memory -= $data['m'];
                    $memFree += $data['m'];
                    unset(self::$objects_keys[$idx]);

                    $data['t'] = microtime(true);
                    self::$objects_keys_removed[] = $data;

                    $n++;

                    if ($min_memory <= 0) {
                        break 2; // La quantité de mémoire demandée a été libérée. 
                    }
                }

                if ($data['n'] < $min_found) {
                    $min_found = $data['n'];
                }

                if ($data['n'] > $max_found) {
                    $max_found = $data['n'];
                }
            }

            if ($min_found == $max_found) {
                break;
            }

            $min_used = $min_found;
        }

        if ($n > 0) {
            if (BimpDebug::isActive()) {
                BimpDebug::addDebugTime('Retrait de ' . $n . ' objet(s) du cache');
            }
        }
    }
}
