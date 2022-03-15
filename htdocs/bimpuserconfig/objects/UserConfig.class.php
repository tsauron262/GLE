<?php

class UserConfig extends BimpObject
{

    const OWNER_TYPE_GROUP = 1;
    const OWNER_TYPE_USER = 2;

    public static $config_object_name = 'UserConfig';
    public static $config_table = 'buc_list_table_config';
    public static $config_primary = 'id';
    public static $user_current_config_table = 'buc_user_current_config';
    public static $owner_types = array(
        self::OWNER_TYPE_GROUP => array('label' => 'Groupe', 'icon' => 'fas_users'),
        self::OWNER_TYPE_USER  => array('label' => 'Utilisateur', 'icon' => 'fas_user')
    );

    // Droits user:

    public function canEditGroupConfigs()
    {
        global $user;
        return (int) $user->admin;
    }

    public function canCreate()
    {
        return 1;
    }

    public function canEdit()
    {
        global $user;

        if ((int) $user->id === (int) $this->getData('id_user_create')) {
            return 1;
        }

        if ((int) $user->admin) {
            return 1;
        }

        if ($this->isLoaded()) {
            if ((int) $this->getData('owner_type') === self::OWNER_TYPE_GROUP) {
                return $this->canEditGroupConfigs();
            } else {
                if ((int) $user->id === (int) $this->getData('id_owner')) {
                    return 1;
                }
            }

            return 0;
        }

        return 1;
    }

    public function canEditField($field_name)
    {
        switch ($field_name) {
            case 'owner_type':
            case 'id_group':
            case 'id_owner':
                return $this->canEditGroupConfigs();

            case 'shared_users':
            case 'shared_groups':
                return $this->canEdit();
        }

        return parent::canEditField($field_name);
    }

    public function canDelete()
    {
        return $this->canEdit();
    }

    // Getters données: 

    public function getIdGroup()
    {
        if ((int) $this->getData('owner_type') === self::OWNER_TYPE_GROUP) {
            return (int) $this->getData('id_owner');
        }

        return 0;
    }

    public function getIdUserOwner()
    {
        if ((int) $this->getData('owner_type') === self::OWNER_TYPE_USER) {
            return (int) $this->getData('id_owner');
        }

        return 0;
    }

    public function getConfigFilters()
    {
        BimpCore::addlog('Méthode "getConfigFilters" non défini dans l\'objet "' . get_class($this) . '"', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore');
        return null;
    }

    public function getCurrentConfigKey()
    {
        BimpCore::addlog('Méthode "getCurrentConfigKey" non défini dans l\'objet "' . get_class($this) . '"', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore');
        return '';
    }

    public function getConfigDefaultValues()
    {
        global $user;

        $values = array(
            'owner_type' => self::OWNER_TYPE_USER
        );

        if (BimpObject::objectLoaded($user)) {
            $values['id_owner'] = (int) $user->id;
        }

        return $values;
    }

    // Getters Array:

    public static function getUserConfigsArrayCore($id_user, $include_empty = false, $params = array())
    {
        if (!(int) $id_user) {
            return array();
        }

        $params = BimpTools::overrideArray(array(
                    'cache_key' => '',
                    'filters'   => array(),
                    'joins'     => array()
                        ), $params);

        $cache_key = $params['cache_key'];

        if (!$cache_key || !static::$config_table || !static::$config_primary) {
            return array();
        }

        $cache_key = 'buc_' . $cache_key . '_user_' . $id_user . '_configs_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $filters = BimpTools::getArrayValueFromPath($params, 'filters', array());
            $filters['owner_custom'] = array('custom' => static::getOwnerSqlFilter($id_user));

            $sql = BimpTools::getSqlSelect(array(static::$config_primary, 'name'));
            $sql .= BimpTools::getSqlFrom(static::$config_table, $params['joins']);
            $sql .= BimpTools::getSqlWhere($filters);
            $sql .= BimpTools::getSqlOrderBy('owner_type', 'desc', '', static::$config_primary, 'asc');

            $rows = self::getBdb()->executeS($sql, 'array');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r[static::$config_primary]] = $r['name'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public static function getGroupConfigsArrayCore($id_group, $include_empty = false, $params = array())
    {
        if (!(int) $id_group) {
            return array();
        }

        $params = BimpTools::overrideArray(array(
                    'cache_key' => '',
                    'filters'   => array(),
                    'joins'     => array()
                        ), $params);

        $cache_key = $params['cache_key'];

        if (!$cache_key || !static::$config_table || !static::$config_primary) {
            return array();
        }

        $cache_key = 'buc_' . $cache_key . '_group_' . $id_group . '_configs_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $filters = BimpTools::getArrayValueFromPath($params, 'filters', array());
            $filters['owner_custom'] = array('custom' => static::getGroupOwnerSqlFilter($id_group));

            $sql = BimpTools::getSqlSelect(array(static::$config_primary, 'name'));
            $sql .= BimpTools::getSqlFrom(static::$config_table, $params['joins']);
            $sql .= BimpTools::getSqlWhere($filters);
            $sql .= BimpTools::getSqlOrderBy('owner_type', 'desc', '', static::$config_primary, 'asc');

            $rows = self::getBdb()->executeS($sql, 'array');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r[static::$config_primary]] = $r['name'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    // Getters params: 

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->canEdit()) {
            $buttons[] = array(
                'label'   => 'Partages',
                'icon'    => 'fas_share-alt',
                'onclick' => $this->getJsLoadModalForm('shares', 'Partages de la configuration')
            );
        }

        return $buttons;
    }

    // Getters statics: 

    public static function getCurrentConfigKeyFromConfigFilters($filters)
    {
        return '';
    }

    public static function getUserCurrentConfigCore($id_user, $config_type_key, $default_config_filters = array())
    {
        if ((int) $id_user) {

            // Recherche config courante: 
            $where = '`config_object_name` = \'' . static::$config_object_name . '\'';
            $where .= ' AND `config_type_key` = \'' . $config_type_key . '\'';
            $where .= ' AND `id_user` = ' . $id_user;

            $id_config = self::getBdb()->getValue(self::$user_current_config_table, 'id_config', $where);
            if (is_null($id_config)) {
                if (is_array($default_config_filters) && !empty($default_config_filters)) {
                    return static::getUserDefaultConfig($id_user, $default_config_filters);
                }
            } elseif ((int) $id_config) {
                $config = self::getInstance('bimpuserconfig', static::$config_object_name, (int) $id_config);
                if (BimpObject::objectLoaded($config)) {
                    return $config;
                }
            }
        }

        return null;
    }

    public static function getUserDefaultConfig($id_user, $filters = array())
    {
        if ((int) $id_user) {
            // Recherche config par défaut: 

            $filters['is_default'] = 1;
            $filters['owner_custom'] = array(
                'custom' => static::getOwnerSqlFilter($id_user, '')
            );

            $sql = BimpTools::getSqlSelect(static::$config_primary, '');
            $sql .= BimpTools::getSqlFrom(static::$config_table, array(), '');
            $sql .= BimpTools::getSqlWhere($filters, '');
            $sql .= BimpTools::getSqlOrderBy('owner_type', 'desc', '', static::$config_primary, 'desc');

            $result = self::getBdb()->executeS($sql, 'array');

            if (isset($result[0][static::$config_primary]) && (int) $result[0][static::$config_primary]) {
                $config = self::getInstance('bimpuserconfig', static::$config_object_name, (int) $result[0][static::$config_primary]);
                if (BimpObject::objectLoaded($config)) {
                    return $config;
                }
            }

            // On retente sans le 'is_default': 
            unset($filters['is_default']);
            $sql = BimpTools::getSqlSelect(static::$config_primary, '');
            $sql .= BimpTools::getSqlFrom(static::$config_table, array(), '');
            $sql .= BimpTools::getSqlWhere($filters, '');
            $sql .= BimpTools::getSqlOrderBy('owner_type', 'desc', '', static::$config_primary, 'desc');

            $result = self::getBdb()->executeS($sql, 'array');

            if (isset($result[0][static::$config_primary]) && (int) $result[0][static::$config_primary]) {
                $config = self::getInstance('bimpuserconfig', static::$config_object_name, (int) $result[0][static::$config_primary]);
                if (BimpObject::objectLoaded($config)) {
                    return $config;
                }
            }
        }

        return null;
    }

    public static function getOwnerSqlFilter($id_user, $alias = 'a', $with_user_create = false)
    {
        if ($alias) {
            $alias .= '.';
        }
        $sql = '';
        if ((int) $id_user) {
            $sql .= '(';
            $groups = BimpCache::getUserUserGroupsList($id_user);

            $sql .= '(' . $alias . 'owner_type = 2 AND ' . $alias . 'id_owner = ' . $id_user . ')';
            $sql .= ' OR (' . $alias . 'shared_users LIKE \'%[' . $id_user . ']%\')';

            if (count($groups)) {
                $sql .= ' OR (' . $alias . 'owner_type = 1 AND ' . $alias . 'id_owner IN (' . implode(',', $groups) . '))';

                foreach ($groups as $id_group) {
                    $sql .= ' OR (' . $alias . 'shared_groups LIKE \'%[' . $id_group . ']%\')';
                }
            }

            if ($with_user_create) {
                $sql .= ' OR (' . $alias . 'id_user_create = ' . $id_user . ')';
            }
            $sql .= ')';
        }

        return $sql;
    }

    public static function getGroupOwnerSqlFilter($id_group, $alias = 'a')
    {
        if ($alias) {
            $alias .= '.';
        }
        $sql = '';
        if ((int) $id_group) {
            $sql .= '(';
            $sql .= '(' . $alias . 'owner_type = 1 AND ' . $alias . 'id_owner = ' . $id_group . ')';
            $sql .= ' OR (' . $alias . 'shared_groups LIKE \'%[' . $id_group . ']%\')';
            $sql .= ')';
        }

        return $sql;
    }

    // Affichages: 

    public function displayOwner($nom_url = false)
    {
        if (!$this->isLoaded()) {
            return '';
        }

        switch ($this->getData('owner_type')) {
            case self::OWNER_TYPE_USER:
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->getData('id_owner'));
                if (BimpObject::objectLoaded($user)) {
                    if ($nom_url) {
                        return $user->getLink();
                    }
                    return BimpRender::renderIcon('fas_user', 'iconLeft') . $user->getName();
                } else {
                    return BimpRender::renderIcon('fas_user', 'iconLeft') . 'Utilisateur #' . $this->getData('id_owner');
                }

            case self::OWNER_TYPE_GROUP:
                BimpObject::loadClass('bimpcore', 'Bimp_User');
                $id_group = (int) $this->getData('id_owner');
                if ($id_group) {
                    return Bimp_User::displayUserGroup($id_group, ($nom_url ? 'nom_url' : 'nom'), true);
                }
                return '';
        }

        return '';
    }

    public function displaySharedUsers($nom_url = false)
    {
        $html = '';

        $users = $this->getData('shared_users');

        if (is_array($users) && !empty($users)) {
            foreach ($users as $id_user) {
                if (!(int) $id_user) {
                    continue;
                }

                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $id_user);
                if (BimpObject::objectLoaded($user)) {
                    if ($html) {
                        $html .= '<br/>';
                    }
                    if ($nom_url) {
                        $html .= $user->getLink();
                    } else {
                        $html .= $user->getName();
                    }
                }
            }
        }

        return $html;
    }

    public function displaySharedGroups($nom_url = false)
    {
        $html = '';

        $groups = $this->getData('shared_groups');

        if (is_array($groups) && !empty($groups)) {
            BimpObject::loadClass('bimpcore', 'Bimp_User');

            foreach ($groups as $id_group) {
                if (!(int) $id_group) {
                    continue;
                }

                $group_label = Bimp_User::displayUserGroup($id_group, ($nom_url ? 'nom_url' : 'nom'), true);

                if ($group_label) {
                    if ($html) {
                        $html .= '<br/>';
                    }
                    $html .= $group_label;
                }
            }
        }

        return $html;
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
                case self::OWNER_TYPE_USER:
                    $html .= ' (utilisateur)';
                    break;

                case self::OWNER_TYPE_GROUP:
                    $html .= ' (groupe)';
                    break;
            }
            $html .= '</span>';
        }

        global $user;

        if (BimpObject::objectLoaded($user)) {
            $key = $this->getCurrentConfigKey();
            $config = static::getUserCurrentConfigCore($user->id, $key, null);

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

    // Rendus HTML: 

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

    // Traitements: 

    public function setAsCurrent()
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            global $user;

            if (BimpObject::objectLoaded($user)) {
                $config_type_key = $this->getCurrentConfigKey();

                if ($config_type_key) {
                    $where = '`config_object_name` = \'' . static::$config_object_name . '\'';
                    $where .= ' AND `config_type_key` = \'' . $config_type_key . '\'';
                    $where .= ' AND `id_user` = ' . $user->id;

                    $id_config = $this->db->getValue(self::$user_current_config_table, 'id_config', $where);

                    if (!is_null($id_config) && (int) $id_config !== (int) $this->id) {
                        $this->db->update(static::$user_current_config_table, array(
                            'id_config' => (int) $this->id
                                ), $where);
                    } else {
                        $this->db->insert(static::$user_current_config_table, array(
                            'id_user'            => (int) $user->id,
                            'id_config'          => (int) $this->id,
                            'config_object_name' => static::$config_object_name,
                            'config_type_key'    => $config_type_key
                        ));
                    }
                }
            }
        }

        return $errors;
    }

    public function unsetCurrent($config_type_key)
    {
        $errors = array();

        if (!$config_type_key) {
            $errors[] = 'Clé de configuration courante absente';
        } else {
            global $user;
            if (BimpObject::objectLoaded($user)) {
                $where = '`config_object_name` = \'' . static::$config_object_name . '\'';
                $where .= ' AND `config_type_key` = \'' . $config_type_key . '\'';
                $where .= ' AND `id_user` = ' . $user->id;

                if ($this->db->delete(static::$user_current_config_table, $where) <= 0) {
                    $errors[] = 'Echec suppression coufig courante - ' . $this->db->err();
                }
            }
        }

        return $errors;
    }

    public function setNoCurrent($config_type_key)
    {
        $errors = array();

        if (!$config_type_key) {
            $errors[] = 'Clé de configuration courante absente';
        } else {
            global $user;

            if (BimpObject::objectLoaded($user)) {
                $where = '`config_object_name` = \'' . static::$config_object_name . '\'';
                $where .= ' AND `config_type_key` = \'' . $config_type_key . '\'';
                $where .= ' AND `id_user` = ' . $user->id;

                $id_current_config = (int) $this->db->getValue(self::$user_current_config_table, 'id', $where);

                if ($id_current_config) {
                    $this->db->update(static::$user_current_config_table, array(
                        'id_config' => 0
                            ), 'id = ' . $id_current_config);
                } else {
                    $this->db->insert(static::$user_current_config_table, array(
                        'id_user'            => (int) $user->id,
                        'id_config'          => 0,
                        'config_object_name' => static::$config_object_name,
                        'config_type_key'    => $config_type_key
                    ));
                }
            }
        }



        return $errors;
    }

    public function onSave(&$errors = array(), &$warnings = array())
    {
        if ($this->isLoaded() && (int) $this->getData('is_default')) {
            $filters = $this->getConfigFilters();

            if (is_array($filters) && !empty($filters)) {
                $filters['owner_type'] = $this->getData('owner_type');
                $filters['id_owner'] = (int) $this->getData('id_owner');
                $filters[static::$config_primary] = array(
                    'operator' => '!=',
                    'value'    => (int) $this->id
                );

                $where = BimpTools::getSqlWhere($filters, '', '');

                $this->db->update(static::$config_table, array(
                    'is_default' => 0
                        ), $where);
            }
        }

        parent::onSave($errors, $warnings);
    }

    // Overrides: 

    public function validatePost()
    {
        if (!(int) $this->getData('id_owner')) {
            switch ((int) BimpTools::getValue('owner_type', self::OWNER_TYPE_USER)) {
                case self::OWNER_TYPE_USER:
                    global $user;
                    if (BimpObject::objectLoaded($user)) {
                        $this->set('id_owner', (int) $user->id);
                    } else {
                        $this->set('id_owner', 0);
                    }
                    break;

                case self::OWNER_TYPE_GROUP:
                    $this->set('id_owner', (int) BimpTools::getValue('id_group', 0));
                    break;
            }
        }

        return parent::validatePost();
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        global $user;

        if (BimpObject::objectLoaded($user)) {
            $this->set('id_user_create', $user->id);
        }

        return parent::create($warnings, $force_create);
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $id = (int) $this->id;

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            if ($id) {
                $this->db->delete(static::$user_current_config_table, '`config_object_name` = \'' . static::$config_object_name . '\' AND `id_config` = ' . $id);
            }
        }

        return $errors;
    }
}
