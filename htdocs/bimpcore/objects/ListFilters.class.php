<?php

class ListFilters extends BimpObject
{

    const TYPE_GROUP = 1;
    const TYPE_USER = 2;

    protected $obj_instance = null;

    // Droits user: 

    public function canEditGroupFilters()
    {
        global $user;
        return (int) $user->admin;
    }

    public function canEdit()
    {
        if ($this->isLoaded()) {
            if ((int) $this->getData('owner_type') === self::TYPE_GROUP) {
                return $this->canEditGroupFilters();
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
                return $this->canEditGroupFilters();
        }

        return (int) parent::canEditField($field_name);
    }

    // Getters: 

    public function getReloadListJsCallback()
    {
        if ($this->isLoaded()) {
            $filters_id = BimpTools::getPostFieldValue('filters_id', '');

            if ($filters_id) {
                return 'loadSavedFilters(\'' . $filters_id . '\', ' . $this->id . ');';
            }
        }

        return '';
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
            $module = $this->getData('obj_module');
            $object_name = $this->getData('obj_name');

            if ($module && $object_name) {
                $this->obj_instance = BimpObject::getInstance($module, $object_name);

                if (!is_a($this->obj_instance, $object_name)) {
                    unset($this->obj_instance);
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

    public function getIdUserOwner()
    {
        if ((int) $this->getData('owner_type') === self::TYPE_USER) {
            return (int) $this->getData('id_owner');
        }

        return 0;
    }

    public static function getOwnerFilterCustomSql($id_user, $alias = 'a')
    {
        if ($alias) {
            $alias .= '.';
        }

        $sql = '(';
        $sql .= '(' . $alias . '`owner_type` = 2 AND ' . $alias . '`id_owner` = ' . $id_user . ')';
        $sql .= ' OR ';
        $sql .= '(' . $alias . '`owner_type` = 1 AND ' . $alias . '`id_owner` IN ';
        $sql .= '(SELECT ugu.fk_usergroup FROM ' . MAIN_DB_PREFIX . 'usergroup_user ugu WHERE ugu.fk_user = ' . $id_user . '))';
        $sql .= ' OR ' . $alias . 'id_user_create = ' . $id_user;
        $sql .= ')';

        return $sql;
    }

    public function getListTitle()
    {
        global $user;

        return (BimpObject::objectLoaded($user) ? $user->getFullName() . ': l' : 'L') . 'iste des filtres enregistrés';
    }

    public function getListExtraButtons()
    {
        $buttons = array();

        if ($this->isActionAllowed('share') && $this->canSetAction('share')) {
            $buttons[] = array(
                'label'   => 'Partager',
                'icon'    => 'fas_share',
                'onclick' => $this->getJsActionOnclick('share', array(), array('form_name' => 'share'))
            );
        }

        return $buttons;
    }

    // Affichage: 

    public function displayOwner()
    {
        if (!$this->isLoaded()) {
            return '';
        }

        switch ($this->getData('owner_type')) {
            case self::TYPE_USER:
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $this->getData('id_owner'));
                if (BimpObject::objectLoaded($user)) {
                    return $user->getLink();
                }
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

    public function renderShareUsersInput()
    {
        $values = BimpCache::getUsersArray();

        $input = BimpInput::renderInput('select', 'users_add_value', '', array('options' => $values));
        $content = BimpInput::renderMultipleValuesInput($this, 'users', $input, array(), '', 0, 0, 0);
        $html .= BimpInput::renderInputContainer('users', '', $content, '', 0, 1, '', array('values_field' => 'users'));

        return $html;
    }

    public function renderShareGroupsInput()
    {
        $values = BimpCache::getUserGroupsArray(false);

        $input = BimpInput::renderInput('select', 'groups_add_value', '', array('options' => $values));
        $content = BimpInput::renderMultipleValuesInput($this, 'groups', $input, array(), '', 0, 0, 0);
        $html .= BimpInput::renderInputContainer('groups', '', $content, '', 0, 1, '', array('values_field' => 'groups'));

        return $html;
    }

    // Actions: 

    public function actionShare($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Filtres enregistrés avec succès';

        if ($this->isLoaded($errors)) {
            $users = isset($data['users']) ? $data['users'] : array();
            $groups = isset($data['groups']) ? $data['groups'] : array();

            if (empty($users) && empty($groups)) {
                $errors[] = 'Aucun utilisateur ou groupe sélectionné';
            } else {
                $values = $this->data;

                if (!empty($users)) {
                    $values['owner_type'] = self::TYPE_USER;

                    foreach ($data['users'] as $id_user) {
                        $values['id_owner'] = (int) $id_user;
                        $values['id_user_create'] = (int) $id_user;
                        self::createBimpObject($this->module, $this->object_name, $values, true, $errors, $warnings);
                    }
                }
                
                if (!empty($groups)) {
                    $values['owner_type'] = self::TYPE_GROUP;
                    $values['id_user_create'] = 0;
                    
                    foreach ($data['groups'] as $id_group) {
                        $values['id_owner'] = (int) $id_group;
                        self::createBimpObject($this->module, $this->object_name, $values, true, $errors, $warnings);
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
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
        $errors = parent::validatePost();

        if (count($errors)) {
            return $errors;
        }

        switch ((int) $this->getData('owner_type')) {
            case self::TYPE_USER:
                global $user;
                if (BimpObject::objectLoaded($user)) {
                    $this->set('id_owner', (int) $user->id);
                } else {
                    $errors[] = 'Aucun utilisateur connecté';
                    $this->set('id_owner', 0);
                }
                break;

            case self::TYPE_GROUP:
                $this->set('id_owner', (int) BimpTools::getValue('id_group', 0));
                break;
        }

        $filters = $this->getData('filters');

        if (empty($filters)) {
            $errors[] = 'Aucun filtre sélectionné';
        } else {
            $values = array(
                'fields'   => array(),
                'children' => array()
            );
            $excluded_values = array(
                'fields'   => array(),
                'children' => array()
            );

            foreach ($filters['fields'] as $field_name => $filter) {
                if (isset($filter['values']) && is_array($filter['values']) && !empty($filter['values'])) {
                    $values['fields'][$field_name] = $filter['values'];
                }
                if (isset($filter['excluded_values']) && is_array($filter['excluded_values']) && !empty($filter['excluded_values'])) {
                    $excluded_values['fields'][$field_name] = $filter['excluded_values'];
                }
            }

            foreach ($filters['children'] as $child => $fields) {
                if (!isset($values['children'][$child])) {
                    $values['children'][$child] = array();
                }
                if (!isset($values['children'][$child])) {
                    $excluded_values['children'][$child] = array();
                }

                foreach ($fields as $field_name => $filter) {
                    if (isset($filter['values']) && is_array($filter['values']) && !empty($filter['values'])) {
                        $values['children'][$child][$field_name] = $filter['values'];
                    }
                    if (isset($filter['excluded_values']) && is_array($filter['excluded_values']) && !empty($filter['excluded_values'])) {
                        $excluded_values['children'][$child][$field_name] = $filter['excluded_values'];
                    }
                }
            }

            $this->set('filters', $values);
            $this->set('excluded', $excluded_values);
        }

        return $errors;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        if (!(int) $this->getData('id_user_create')) {
            global $user;

            if (BimpObject::objectLoaded($user)) {
                $this->set('id_user_create', (int) $user->id);
            }
        }

        return parent::create($warnings, $force_create);
    }
}
