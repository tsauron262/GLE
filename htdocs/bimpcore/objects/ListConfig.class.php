<?php

class ListConfig extends BimpObject
{

    const TYPE_GROUP = 1;
    const TYPE_USER = 2;

    protected $obj_instance = null;

    // Droits user:

    public function canEditGroupConfigs()
    {
        return 1;
    }
    
    public function canEdit()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('owner_type') === self::TYPE_GROUP) {
                return $this->canEditGroupConfigs();
            }
        }
        
        return (int) parent::canEdit();
    }
    
    public function canEditField($field_name)
    {
        switch ($field_name) {
            case 'owner_type':
            case 'id_group':
            case 'id_owner':
                return $this->canEditGroupConfigs();
        }

        return (int) parent::canEditField($field_name);
    }

    // Getters booléens

    public function hasCols()
    {
        if (in_array($this->getData('list_type'), array('list_table'))) {
            return 1;
        }

        return 0;
    }

    public function hasFiltersPanel()
    {
        $obj = $this->getObjInstance();

        if (is_a($obj, 'BimpObject')) {
            $path = $this->getListObjConfigPath();

            if ($path) {
                $panel = (string) $obj->config->get($path . '/filters_panel', '');
                if ($panel) {
                    return 1;
                }
            }
        }

        return 0;
    }

    public function hasPagination()
    {
        $obj = $this->getObjInstance();

        if (is_a($obj, 'BimpObject')) {
            $path = $this->getListObjConfigPath();

            if ($path) {
                return (int) ((int) $obj->config->get($path . '/pagination', 1, false, 'bool') && (int) $obj->config->get($path . '/n', 10, false, 'int'));
            }
        }

        return 0;
    }

    public function isListSortable()
    {
        $obj = $this->getObjInstance();

        if (is_a($obj, 'BimpObject')) {
            $path = $this->getListObjConfigPath();

            if ($path) {
                return (int) $obj->config->get($path . '/enable_sort', 1, false, 'bool');
            }
        }

        return 1;
    }

    public function isListSearchable()
    {
        $obj = $this->getObjInstance();

        if (is_a($obj, 'BimpObject')) {
            $path = $this->getListObjConfigPath();

            if ($path) {
                return (int) $obj->config->get($path . '/enable_search', 1, false, 'bool');
            }
        }

        return 1;
    }

    // Getters array: 

    public function getOwnerFiltersArray()
    {
        global $user;

        $list_name = $this->getData('list_name');
        $list_type = $this->getData('list_type');
        $object = $this->getObjInstance();

        if ($list_name && $list_type && is_a($object, 'BimpObject')) {
            $list_path = BC_List::getConfigPath($object, $list_name, $list_type);

            if ($list_path) {
                $panel_name = $object->getConf($list_path . '/filters_panel', '');
                if ($panel_name) {
                    switch ((int) $this->getData('owner_type')) {
                        case self::TYPE_USER:
                            return BimpCache::getUserListFiltersArray($object, $user->id, $panel_name, true);

                        case self::TYPE_GROUP:
                            $id_group = (int) BimpTools::getPostFieldValue('id_group', $this->getData('id_owner'));
                            if ($id_group) {
                                return BimpCache::getUsergroupListFiltersArray($object, $id_group, $panel_name, true);
                            }
                            break;
                    }
                }
            }
        }

        return array(
            0 => ''
        );
    }

    public function getObjSortableFieldsArray()
    {
        $obj = $this->getObjInstance();

        if (is_a($obj, 'BimpObject')) {
            return $obj->getSortableFieldsArray();
        }

        return array();
    }

    public function getObjSortOptionsArray($field = null)
    {
        $obj = $this->getObjInstance();

        if (is_a($obj, 'BimpObject')) {
            return $obj->getSortOptionsArray();
        }

        return array();
    }

    public static function getUserConfigsArray($id_user, $object, $list_type, $list_name, $include_empty = false)
    {
        $cache_key = 'user_' . $id_user . '_' . $object->module . '_' . $object->object_name . '_list_' . $list_type . '_' . $list_name . '_configs_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            if ((int) $id_user && is_a($object, 'BimpObject')) {
                $groups = BimpCache::getUserUserGroupsList($id_user);

                $sql = 'SELECT id, name FROM ' . MAIN_DB_PREFIX . 'bimpcore_list_config';
                $sql .= ' WHERE `obj_module` = \'' . $object->module . '\' AND `obj_name` = \'' . $object->object_name . '\'';
                $sql .= ' AND `list_type` = \'' . $list_type . '\'';
                $sql .= ' AND `list_name` = \'' . $list_name . '\'';
                $sql .= ' AND (`owner_type` = 1 AND `id_owner` IN (' . implode(',', $groups) . ')';
                $sql .= ' OR `owner_type` = 2 AND `id_owner` = ' . $id_user . ')';
                $sql .= ' ORDER BY `owner_type` DESC, `id` ASC';

                $rows = self::getBdb()->executeS($sql, 'array');

                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        self::$cache[$cache_key][(int) $r['id']] = $r['name'];
                    }
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    // Getters: 

    public function getListObjConfigPath()
    {
        $path = '';
        $obj = $this->getObjInstance();
        $list_type = (string) $this->getData('list_type');
        $list_name = (string) $this->getData('list_name');

        if (is_a($obj, 'BimpObject') && $list_type && $list_name) {
            $path = BC_List::getConfigPath($this, $list_name, $list_type);
        }

        return $path;
    }

    public function getReloadListJsCallback()
    {
        $obj = $this->getObjInstance();

        if (is_a($obj, 'BimpObject') && (string) $this->getData('list_name')) {
            return '$(\'.' . $obj->object_name . '_list_table_' . $this->getData('list_name') . '\').each(function() {reloadObjectList($(this).attr(\'id\'), null, 1);})';
        }
    }

    public function getCreateJsCallback()
    {
        return $this->getReloadListJsCallback();
    }

    public function getUpdateJsCallback()
    {
        return $this->getReloadListJsCallback();
    }

    public function getObjInstance()
    {
        if (is_null($this->obj_instance)) {
            $module = (string) $this->getData('obj_module');
            $object_name = (string) $this->getData('obj_name');

            if ($module && $object_name) {
                $this->obj_instance = BimpObject::getInstance($module, $object_name);

                if (!is_a($this->obj_instance, $object_name)) {
                    $this->obj_instance = null;
                }
            }
        }

        return $this->obj_instance;
    }

    public function getIdGroup()
    {
        if ((int) $this->getData('owner_type') === self::TYPE_GROUP) {
            return (int) $this->getData('id_owner');
        }

        return 0;
    }

    public function getListTitle()
    {
        global $user;

        return (BimpObject::objectLoaded($user) ? $user->getFullName() . ': c' : 'C') . 'onfigurations de la liste';
    }

    public static function getUserCurrentConfig($id_user, $object, $list_type, $list_name)
    {
        if ((int) $id_user && is_a($object, 'BimpObject') && $list_type && $list_name) {
            // Recherche config courante: 
            $where = '`obj_module` = \'' . $object->module . '\'';
            $where .= ' AND `obj_name` = \'' . $object->object_name . '\'';
            $where .= ' AND `list_type` = \'' . $list_type . '\'';
            $where .= ' AND `list_name` = \'' . $list_name . '\'';
            $where .= ' AND `id_user` = ' . $id_user;

            $id_config = (int) self::getBdb()->getValue('bimpcore_list_current_config', 'id_config', $where);
            if ($id_config) {
                $config = self::getInstance('bimpcore', 'ListConfig', $id_config);
                if (BimpObject::objectLoaded($config)) {
                    return $config;
                }
            }

            // Recherche config par défaut: 
            $groups = BimpCache::getUserUserGroupsList($id_user);

            $sql = 'SELECT id FROM ' . MAIN_DB_PREFIX . 'bimpcore_list_config';
            $sql .= ' WHERE `obj_module` = \'' . $object->module . '\' AND `obj_name` = \'' . $object->object_name . '\'';
            $sql .= ' AND `list_type` = \'' . $list_type . '\'';
            $sql .= ' AND `list_name` = \'' . $list_name . '\'';
            $sql .= ' AND (`owner_type` = 1 AND `id_owner` IN (' . implode(',', $groups) . ')';
            $sql .= ' OR `owner_type` = 2 AND `id_owner` = ' . $id_user . ')';
            $sql .= ' AND is_default = 1';
            $sql .= ' ORDER BY `owner_type` DESC, `id` DESC LIMIT 1';

            $result = self::getBdb()->executeS($sql, 'array');

            if (isset($result[0]['id']) && (int) $result[0]['id']) {
                $config = self::getInstance('bimpcore', 'ListConfig', (int) $result[0]['id']);
                if (BimpObject::objectLoaded($config)) {
                    return $config;
                }
            }

            // On retente sans le 'is_default': 
            $sql = 'SELECT id FROM ' . MAIN_DB_PREFIX . 'bimpcore_list_config';
            $sql .= ' WHERE `obj_module` = \'' . $object->module . '\' AND `obj_name` = \'' . $object->object_name . '\'';
            $sql .= ' AND `list_type` = \'' . $list_type . '\'';
            $sql .= ' AND `list_name` = \'' . $list_name . '\'';
            $sql .= ' AND (`owner_type` = 1 AND `id_owner` IN (' . implode(',', $groups) . ')';
            $sql .= ' OR `owner_type` = 2 AND `id_owner` = ' . $id_user . ')';
            $sql .= ' ORDER BY `owner_type` DESC, `id` DESC LIMIT 1';

            $result = self::getBdb()->executeS($sql, 'array');

            if (isset($result[0]['id']) && (int) $result[0]['id']) {
                $config = self::getInstance('bimpcore', 'ListConfig', (int) $result[0]['id']);
                if (BimpObject::objectLoaded($config)) {
                    return $config;
                }
            }
        }

        return null;
    }

    // Affichage: 

    public function displayOwner()
    {
        if (!$this->isLoaded()) {
            return '';
        }

        switch ($this->getData('owner_type')) {
            case self::TYPE_USER:
                return 'Utilisateur';

            case self::TYPE_GROUP:
                $groupe = (string) $this->db->getValue('usergroup', 'nom', 'rowid = ' . (int) $this->getData('id_owner'));

                if ($groupe) {
                    return 'Groupe "' . $groupe . '"';
                } else {
                    return 'Groupe #' . $this->getData('id_owner');
                }
        }

        return '';
    }

    public function displayUtilisation()
    {
        if (!$this->isLoaded()) {
            return '';
        }

        $html = '';

        if ((int) $this->getData('is_default')) {
            $html .= '<span class="success">';
            $html .= 'Par défaut';
            switch ($this->getData('owner_type')) {
                case self::TYPE_USER:
                    $html .= ' (utilisateur)';
                    break;

                case self::TYPE_GROUP:
                    $html .= ' (groupe)';
                    break;
            }
            $html .= '</span>';
        }

        global $user;

        if (BimpObject::objectLoaded($user)) {
            $config = self::getUserCurrentConfig((int) $user->id, $this->getObjInstance(), $this->getData('list_type'), $this->getData('list_name'));

            if (BimpObject::objectLoaded($config) && $config->id == $this->id) {
                if ($html) {
                    $html .= '<br/>';
                }
                $html .= '<span class="important">';
                $html .= 'Utilisée actuellement';
                $html .= '</span>';
            }
        }

        return $html;
    }

    // Traitements: 

    public function setAsCurrent()
    {
        if ($this->isLoaded()) {
            global $user;

            if (BimpObject::objectLoaded($user)) {
                $obj_module = (string) $this->getData('obj_module');
                $obj_name = (string) $this->getData('obj_name');
                $list_type = (string) $this->getData('list_type');
                $list_name = (string) $this->getData('list_name');

                if ($obj_module && $obj_name && $list_type && $list_name) {
                    $where .= ' `obj_module` = \'' . $obj_module . '\'';
                    $where .= ' AND `obj_name` = \'' . $obj_name . '\'';
                    $where .= ' AND `list_type` = \'' . $list_type . '\'';
                    $where .= ' AND `list_name` = \'' . $list_name . '\'';
                    $where .= ' AND `id_user` = ' . $user->id;
                }

                $id_config = (int) $this->db->getValue('bimpcore_list_current_config', 'id_config', $where);

                if ($id_config && $id_config !== (int) $this->id) {
                    $this->db->update('bimpcore_list_current_config', array(
                        'id_config' => (int) $this->id
                            ), $where);
                } else {
                    $this->db->insert('bimpcore_list_current_config', array(
                        'id_user'    => (int) $user->id,
                        'obj_module' => $obj_module,
                        'obj_name'   => $obj_name,
                        'list_type'  => $list_type,
                        'list_name'  => $list_name,
                        'id_config'  => (int) $this->id
                    ));
                }
            }
        }
    }

    public function onSave(&$errors = array(), &$warnings = array())
    {
        if ($this->isLoaded() && (int) $this->getData('is_default')) {
            $where = '`obj_module` = \'' . $this->getData('obj_module') . '\'';
            $where .= ' AND `obj_name` = \'' . $this->getData('obj_name') . '\'';
            $where .= ' AND `list_type` = \'' . $this->getData('list_type') . '\'';
            $where .= ' AND `list_name` = \'' . $this->getData('list_name') . '\'';
            $where .= ' AND `owner_type` = ' . (int) $this->getData('owner_type');
            $where .= ' AND `id_owner` = ' . (int) $this->getData('id_owner');
            $where .= ' AND `id` != ' . (int) $this->id;

            $this->db->update($this->getTable(), array(
                'is_default' => 0
                    ), $where);
        }

        parent::onSave($errors, $warnings);
    }

    // Renders: 

    public function renderGroupInput()
    {
        $html = '';
        
        $id_group = $this->getIdGroup();

        if ($this->canEditField('id_group')) {
            $html .= BimpInput::renderInput('search_group', 'id_group', $id_group);
        } else {
            $html .= '<input type="hidden" name="id_group" value="' . $id_group . '"/>';
            if ($id_group) {
                $html .= $this->db->getValue('usergroup', 'nom', 'rowid = ' . $id_group);
            } else {
                $html .= '<span class="warning">Aucun</span>';
            }
        }

        return $html;
    }

    public function renderColsInput()
    {
        if (!$this->hasCols()) {
            return '';
        }

        $html = '';

        $list_cols = array();
        $list_name = $this->getData('list_name');
        $object = $this->getObjInstance();

        if ($list_name && is_a($object, 'BimpObject')) {
            $list_cols = $object->getListColsArray($list_name);
        }

        if (count($list_cols)) {
            $values = array();
            if ($this->isLoaded()) {
                $cols = $this->getData('cols');

                if (is_array($cols)) {
                    foreach ($cols as $col_name) {
                        if (isset($list_cols[$col_name])) {
                            $values[$col_name] = $list_cols[$col_name];
                        }
                    }
                }
            } else {
                $bc_list = new BC_ListTable($object, $list_name);
                foreach ($bc_list->cols as $col_name) {
                    if (isset($list_cols[$col_name])) {
                        $values[$col_name] = $list_cols[$col_name];
                    }
                }
            }

            $input = BimpInput::renderInput('select', 'cols_add_value', '', array('options' => $list_cols));
            $content = BimpInput::renderMultipleValuesInput($this, 'cols', $input, $values, '', 0, 1, 1);
            $html .= BimpInput::renderInputContainer('cols', '', $content, '', 0, 1, '', array('values_field' => 'cols'));
        } else {
            $html .= BimpRender::renderAlerts('Aucune option disponible', 'warnings');
        }

        return $html;
    }

    // Overrides: 

    public function reset()
    {
        parent::reset();
        unset($this->obj_instance);
        $this->obj_instance = null;
    }

    public function validatePost()
    {
        switch ((int) BimpTools::getValue('owner_type', 2)) {
            case self::TYPE_USER:
                global $user;
                if (BimpObject::objectLoaded($user)) {
                    $this->set('id_owner', (int) $user->id);
                } else {
                    $this->set('id_owner', 0);
                }
                break;

            case self::TYPE_GROUP:
                $this->set('id_owner', (int) BimpTools::getValue('id_group', 0));
                break;
        }

        return parent::validatePost();
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $id = (int) $this->id;

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            if ($id) {
                $this->db->delete('bimpcore_list_current_config', '`id_config` = ' . $id);
            }
        }

        return $errors;
    }
}
