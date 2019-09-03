<?php

class BimpCache
{

//    RÈGLES POUR LES NOMS DES MÉTHODES DE BIMPCACHE: 
//    (Afin de connaître le return d'une méthode sans avoir à rentrer dedans) 
//    
//    getObjectsArray : renvoie un tableau sous la forme id => label
//    getObjectList : renvoie un tableau d'IDs.
//    getObjectData : renvoie un tableau de données
//    getObjects : renvoie un tableau d'objets fetchés.                     
//    
//    /!\ Attention, il est ultra-important de faire en sorte que la cache_key soit unique!

    public static $bdb = null;
    public static $cache = array();
    public static $nextBimpObjectCacheId = 1;

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

    // Objets:

    public static function isBimpObjectInCache($module, $object_name, $id_object)
    {
        return self::cacheExists('bimp_object_' . $module . '_' . $object_name . '_' . $id_object);
    }

    public static function getBimpObjectInstance($module, $object_name, $id_object, $parent = null)
    {
        // Pas très propre mais seule solution trouvée: 
        if ($object_name === 'Bimp_Propal' && (int) $id_object) {
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

        if (isset(self::$cache[$cache_key])) {
            if (!is_a(self::$cache[$cache_key], $object_name) || !self::$cache[$cache_key]->isLoaded() ||
                    (int) self::$cache[$cache_key]->id !== (int) $id_object) {
                self::$cache[$cache_key] = null;
            } else {
                if (!is_null($parent)) {
                    self::$cache[$cache_key]->parent = $parent;
                }
            }
        }

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = BimpObject::getInstance($module, $object_name, $id_object, $parent);
            if (BimpObject::objectLoaded(self::$cache[$cache_key])) {
                self::$cache[$cache_key]->cache_id = self::$nextBimpObjectCacheId;
                self::$nextBimpObjectCacheId++;
                self::$cache[$cache_key]->checkObject('fetch');
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
            self::$cache[$cache_key] = null;
            unset(self::$cache[$cache_key]);
        }
    }

    public static function setBimpObjectInstance($object)
    {
        if (is_a($object, 'BimpObject') && $object->isLoaded()) {
            $cache_key = 'bimp_object_' . $object->module . '_' . $object->object_name . '_' . $object->id;
            self::$cache[$cache_key] = $object;
            self::$cache[$cache_key]->cache_id = self::$nextBimpObjectCacheId;
            self::$nextBimpObjectCacheId++;
        }
    }

    public static function unsetDolObjectInstance($id_object, $module, $file = null, $class = null)
    {
        if (is_null($file)) {
            $file = $module;
        }

        if (is_null($class)) {
            $class = ucfirst($file);
        }

        $cache_key = 'dol_object_' . $class . '_' . $id_object;

        if (isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = null;
            unset(self::$cache[$cache_key]);
        }
    }

    public static function getDolObjectInstance($id_object, $module, $file = null, $class = null)
    {
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

            $cache_key = 'dol_object_' . $class . '_' . $id_object;

            if (!isset(self::$cache[$cache_key])) {
                $instance = new $class($db);
                if (method_exists($instance, 'fetch')) {
                    $instance->fetch($id_object);
                }
                self::$cache[$cache_key] = $instance;
            }

            return self::$cache[$cache_key];
        }

        return null;
    }

    public static function getObjectFilesArray($object, $with_deleted = false)
    {
        if (BimpObject::objectLoaded($object)) {
            if (is_a($object, 'BimpObject')) {
                $cache_key = $object->module . '_' . $object->object_name . '_' . $object->id . '_files';
                if ($with_deleted) {
                    $cache_key .= '_with_deleted';
                }
                if (!isset(self::$cache[$cache_key])) {
                    self::$cache[$cache_key] = array();

                    $where = '`parent_module` = \'' . $object->module . '\'';
                    $where .= ' AND `parent_object_name` = \'' . $object->object_name . '\'';
                    $where .= ' AND `id_parent` = ' . (int) $object->id;

                    if (!$with_deleted) {
                        $where .= ' AND `deleted` = 0';
                    }

                    $rows = self::getBdb()->getRows('bimpcore_file', $where, null, 'array', array('id', 'file_name', 'file_ext'), 'id', 'asc');

                    if (!is_null($rows)) {
                        foreach ($rows as $r) {
                            $file_name = $r['file_name'] . '.' . $r['file_ext'];
                            self::$cache[$cache_key][(int) $r['id']] = BimpRender::renderIcon(BimpTools::getFileIcon($file_name), 'iconLeft') . BimpTools::getFileType($file_name) . ' - ' . $file_name;
                        }
                    }
                }

                return self::$cache[$cache_key];
            }
        }

        return array();
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

    public static function getObjectListColsArray(BimpObject $object, $list_name)
    {
        if (!is_null($object) && is_a($object, 'BimpObject')) {
            $cache_key = $object->module . '_' . $object->object_name . '_' . $list_name . '_list_cols_array';
            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array();

                $bc_list = new BC_ListTable($object, $list_name);

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
                        $object->config->isDefined('lists_cols')) {
                    foreach ($object->config->getCompiledParams('lists_cols') as $col_name => $col_params) {
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

//                                        $info = $object->getConf('lists_cols/' . $col_name . '/info', '');
//                                        if ($info) {
//                                            $label .= ' - ' . $info;
//                                        }
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
                $info = $object->getConf('lists_cols/' . $col_name . '/info', '');
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

            $filters = array_merge($filters, BimpNote::getFiltersByUser());

            $list = $instance->getList($filters, null, null, 'date_create', 'desc', 'array', array('id'));

            if (!is_null($list)) {
                foreach ($list as $item) {
                    self::$cache[$cache_key][] = BimpObject::getInstance('bimpcore', 'BimpNote', (int) $item['id']);
                }
            }
        }

        return self::$cache[$cache_key];
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

            foreach ($bimpObject->params['objects'] as $name => $params) {
                if (isset($params['has_files']) && (int) $params['has_files']) {
                    if (isset($params['instance']['bimp_object'])) {
                        $instance = BimpObject::getInstance($params['instance']['bimp_object']['module'], $params['instance']['bimp_object']['name']);
                        $icon = $instance->params['icon'];
                        self::$cache[$cache_key][$name] = array('label' => BimpTools::ucfirst($instance->getLabel()), 'icon' => $icon);
                    } elseif (isset($params['instance']['dol_object'])) {
                        if (isset($params['instance']['dol_object']['label'])) {
                            self::$cache[$cache_key][$name] = BimpTools::ucfirst($params['instance']['dol_object']['label']);
                        } else {
                            self::$cache[$cache_key][$name] = BimpTools::ucfirst($name);
                        }
                    }
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty, '', '');
    }

    public static function getBimpObjectsArray($with_icons = true, $by_modules = true, $include_empty = false)
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
                                $option['label'] = BimpTools::ucfirst($instance->getLabel()) . ' (' . $object_name . ')';

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

        return self::getCacheArray($cache_key, $include_empty);
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

    public static function getBimpObjectList($module, $object_name, $filters)
    {
        $instance = BimpObject::getInstance($module, $object_name);

        if (!is_a($instance, $object_name)) {
            return array();
        }

        $rows = $instance->getList($filters, null, null, 'id', 'asc', 'array', array($instance->getPrimary()));

        $list = array();

        foreach ($rows as $r) {
            $list[] = (int) $r[$instance->getPrimary()];
        }

        return $list;
    }

    public static function getBimpObjectObjects($module, $object_name, $filters)
    {
        $instance = BimpObject::getInstance($module, $object_name);

        if (!is_a($instance, $object_name)) {
            return array();
        }

        $rows = $instance->getList($filters, null, null, 'id', 'asc', 'array', array($instance->getPrimary()));
        $items = array();

        foreach ($rows as $r) {
            $item = self::getBimpObjectInstance($module, $object_name, (int) $r[$instance->getPrimary()]);
            if (BimpObject::objectLoaded($item)) {
                $items[$item->id] = $item;
            }
        }

        return $items;
    }

    // Sociétés: 

    public static function getSocieteContactsArray($id_societe, $include_empty = false)
    {
        $cache_key = '';

        if ((int) $id_societe) {
            $cache_key = 'societe_' . $id_societe . '_contacts_array';
            if (!isset(self::$cache[$cache_key])) {
                self::$cache[$cache_key] = array("" => "");
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

    public static function getSocieteProductEquipmentsArray($id_societe, $id_product)
    {
        if ((int) $id_societe) {
            $cache_key = 'societe_' . $id_societe . '_product_' . (int) $id_product . '_equipments_array';

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

    public static function getSocieteCommerciauxObjectsList($id_societe)
    {
        $cache_key = 'societe_' . $id_societe . '_commerciaux_array';

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

    // User: 

    public static function getUsersArray($include_empty = 0)
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
            if ($include_empty)
                self::$cache[$cache_key] = array("" => "");
            else
                self::$cache[$cache_key] = array();

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

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getUserGroupsArray($include_empty = 1)
    {
        $cache_key = 'users_groups_array';

        if (!isset(self::$cache[$cache_key])) {
            if ($include_empty)
                self::$cache[$cache_key] = array("" => "");
            else
                self::$cache[$cache_key] = array();


            $rows = self::getBdb()->getRows('usergroup', '1', null, 'object', array('rowid', 'nom'), 'nom', 'asc');
            if (!is_null($rows)) {
                foreach ($rows as $r) {

                    self::$cache[$cache_key][$r->rowid] = $r->nom;
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
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

    public static function getUserListFiltersArray(BimpObject $object, $id_user, $list_type, $list_name, $panel_name, $include_empty = false)
    {
        $cache_key = $object->module . '_' . $object->object_name . '_' . $list_name . '_' . $list_type . '_' . $panel_name . '_filters_panel_user_' . $id_user;

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $instance = BimpObject::getInstance('bimpcore', 'ListFilters');

            $rows = $instance->getList(array(
                'owner_type' => 2,
                'id_owner'   => (int) $id_user,
                'obj_module' => $object->module,
                'obj_name'   => $object->object_name,
                'list_type'  => $list_type,
                'list_name'  => $list_name,
                'panel_name' => $panel_name
                    ), null, null, 'id', 'asc', 'array', array('id', 'name'));

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['id']] = $r['name'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    // User Groups: 

    public static function getGroupIds($idUser)
    {
        $cache_key = 'groupsIduser' . $idUser;
        if (!isset(self::$cache[$cache_key])) {
            require_once(DOL_DOCUMENT_ROOT . "/user/class/usergroup.class.php");
            $userGroup = new UserGroup(self::getBdb()->db);
            $listIdGr = array();
            foreach ($userGroup->listGroupsForUser($idUser, false) as $obj)
                self::$cache[$cache_key][] = $obj->id;
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

    public static function getComptesArray()
    {
        if (!isset(self::$cache['comptes'])) {
            self::$cache['comptes'] = array();

            $rows = self::getBdb()->getRows('bank_account');
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache['comptes'][(int) $r->rowid] = $r->label;
                }
            }
        }

        return self::$cache['comptes'];
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
                    self::$cache[$cache_key] = array_merge(self::$cache[$cache_key], Bimp_Categorie::getCategoriesListByParent($id_parent));
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
                self::$cache[$cache_key] = self::getBdb()->getRow('c_email_templates', '`rowid` = ' . (int) $id_model, array('label', 'topic', 'content', 'content_lines'), 'array');
            }

            return self::$cache[$cache_key];
        }

        return null;
    }

    // Divers: 

    public static function getTaxes($id_country = 1)
    {
        $id_country = (int) $id_country;
        $cache_key = 'taxes_' . $id_country;

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();
            $rows = self::getBdb()->getRows('c_tva', '`fk_pays` = ' . $id_country . ' AND `active` = 1', null, 'array', array('rowid', 'taux'));
            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['rowid']] = $r['taux'];
                }
            }
        }

        return self::$cache[$cache_key];
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

        return self::getCacheArray('cond_reglements_array', 1);
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

        return self::getCacheArray($cache_key, 1, $empty_value);
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
            1014 => "MAC OS 10.14",
            1013 => "MAC OS 10.13",
            1012 => "MAC OS 10.12",
            1011 => "MAC OS 10.11",
            1010 => "MAC OS 10.10",
            1075 => "MAC OS 10.7.5",
            106  => "MAC OS 10.6",
            107  => "MAC OS 10.7",
            109  => "MAC OS 10.9",
            108  => "MAC OS 10.8",
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

    public static function getObjectListConfig($module, $object_name, $owner_type, $id_owner, $list_name)
    {
        $cache_key = $module . '_' . $object_name . '_' . $owner_type . '_' . $id_owner . '_' . $list_name . '_list_config';
        if (!isset(self::$cache[$cache_key])) {
            $config = BimpObject::getInstance('bimpcore', 'ListConfig');
            if ($config->find(array(
                        'owner_type' => $owner_type,
                        'id_owner'   => (int) $id_owner,
                        'obj_module' => $module,
                        'obj_name'   => $object_name,
                        'list_name'  => $list_name
                            ), true)) {
                self::$cache[$cache_key] = $config;
            } else {
                self::$cache[$cache_key] = null;
            }
        }

        return self::$cache[$cache_key];
    }

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

        return self::$cache[$cache_key];
    }
}
