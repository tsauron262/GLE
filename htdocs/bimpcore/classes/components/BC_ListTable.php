<?php

class BC_ListTable extends BC_List
{

    public $component_name = 'Tableau';
    public static $type = 'list_table';
    public static $hasUserConfig = true;
    public $search = false;
    public $rows = null;
    public $colspan = 0;
    public $cols = null;
    public static $item_params = array(
        'edit_btn'        => array('data_type' => 'bool', 'default' => 0),
        'delete_btn'      => array('data_type' => 'bool', 'default' => 0),
        'page_btn'        => array('data_type' => 'bool', 'default' => 0),
        'inline_view'     => array(),
        'modal_view'      => array(),
        'edit_form'       => array('default' => 'default'),
        'edit_form_title' => array(),
        'extra_btn'       => array('data_type' => 'array', 'compile' => true),
        'row_style'       => array('default' => ''),
        'td_style'        => array('default' => ''),
        'item_checkbox'   => array('data_type' => 'bool', 'default' => 1)
    );
    public static $col_params = array(
        'label'           => array('default' => ''),
        'show'            => array('data_type' => 'bool', 'default' => 1),
        'display'         => array('default' => ''),
        'display_options' => array('data_type' => 'array', 'default' => array(), 'compile' => true),
        'edit'            => array('data_type' => 'bool', 'default' => 0),
        'history'         => array('data_type' => 'bool', 'default' => 0),
        'available_csv'   => array('data_type' => 'bool', 'default' => 1),
        'min_width'       => array('default' => null),
        'search_list'     => array('data_type' => 'array', 'compile' => true, 'default' => null),
        'search'          => array('type' => 'definitions', 'defs_type' => 'search', 'default' => null),
        'col_style'       => array('default' => ''),
        'has_total'       => array('data_type' => 'bool', 'default' => 0),
        'total_type'      => array('default' => null),
        'align'           => array('default' => 'left'),
        'object_link'     => array('data_type' => 'bool', 'default' => 0)
    );
    public static $item_col_params = array(
        'value'      => array('default' => null),
        'true_value' => array('default' => null),
        'hidden'     => array('data_type' => 'bool', 'default' => 0),
        'td_style'   => array('default' => ''),
    );
    protected $selected_rows = array();
    protected $totals = array();

    public function __construct(BimpObject $object, $name = 'default', $level = 1, $id_parent = null, $title = null, $icon = null, $id_config = null)
    {
        $this->params_def['checkboxes'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['enable_total_row'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['total_row'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['add_object_row'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['add_object_row_open'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['positions'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['positions_open'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['bulk_actions'] = array('data_type' => 'array', 'default' => array(), 'compile' => true);
        $this->params_def['extra_bulk_actions'] = array('data_type' => 'array', 'default' => array(), 'compile' => true);
        $this->params_def['list_actions'] = array('data_type' => 'array', 'default' => array(), 'compile' => true);
        $this->params_def['cols'] = array('type' => 'keys');
        $this->params_def['extra_cols'] = array('data_type' => 'array', 'default' => array());
        $this->params_def['enable_search'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['enable_sort'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['enable_refresh'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['enable_edit'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['single_cell'] = array('type' => 'definitions', 'defs_type' => 'single_cell', 'default' => null);
        $this->params_def['inline_view_item'] = array('data_type' => 'int', 'default' => 0);
        $this->params_def['before_list_callback'] = array('default' => '');
        $this->params_def['after_list_callback'] = array('default' => '');
        $this->params_def['refresh_before_content'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['refresh_after_content'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['enable_csv'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['search_open'] = array('data_type' => 'bool', 'default' => 0);

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $path = null;

        if (!is_null($object)) {
            if (!$name || $name === 'default') {
                if ($object->config->isDefined('list')) {
                    $path = 'list';
                    $name = '';
                } elseif ($object->config->isDefined('lists/default')) {
                    $path = 'lists';
                    $name = 'default';
                }
            } else {
                $path = 'lists';
            }
        }

        parent::__construct($object, $path, $name, $level, $id_parent, $title, $icon, $id_config);

        if ($this->isObjectValid()) {
            if (!(int) $this->object->can("create")) {
                $this->params['add_object_row'] = 0;
            }
            if (!(int) $this->object->can("view")) {
                $this->errors[] = 'Vous n\'avez pas la permission de voir ' . $this->object->getLabel('the_plur');
            }
            if (!(int) $this->object->can("edit")) {
                $this->params['enable_edit'] = 0;
                $this->params['positions'] = 0;
                $this->params['add_object_row'] = 0;
            }

            if (!(int) $this->object->can("delete")) {
                foreach ($this->params['bulk_actions'] as $idx => $bulk_action) {
                    $onclick = isset($bulk_action['onclick']) ? $bulk_action['onclick'] : '';
                    if (preg_match('/^deleteSelectedObjects\(/', $onclick)) {
                        unset($this->params['bulk_actions'][$idx]);
                    }
                }
                foreach ($this->params['extra_bulk_actions'] as $idx => $bulk_action) {
                    $onclick = isset($bulk_action['onclick']) ? $bulk_action['onclick'] : '';
                    if (preg_match('/^deleteSelectedObjects\(/', $onclick)) {
                        unset($this->params['extra_bulk_actions'][$idx]);
                    }
                }
            }
        }

        if (!$this->params['pagination']) {
            $this->params['n'] = 0;
            $this->params['p'] = 1;
        }

        if (!count($this->errors)) {
            $this->fetchCols();
            $this->colspan = 2 + count($this->cols);

            if ($this->params['positions_open']) {
                $this->params['sort_field'] = 'position';
                $this->params['sort_way'] = 'asc';
                $this->colspan++;
            }

            if (!(int) $this->params['enable_total_row']) {
                $this->params['total_row'] = 0;
            }

            if ($this->params['total_row']) {
                $this->colspan++;
            }
        }

        $current_bc = $prev_bc;
    }

    // Gestion des colonnes: 

    protected function fetchCols_old()
    {
        $cols = array();

        if ($this->params['configurable']) {
            if ($this->object->config->isDefined('lists_cols')) {
                $lists_cols = $this->object->config->getCompiledParams('lists_cols');
                if (is_array($lists_cols)) {
                    foreach ($lists_cols as $col_name => $col_params) {
                        $cols[] = $col_name;
                    }
                }
            }
        }

        foreach ($this->params['cols'] as $col_name) {
            if (!in_array($col_name, $cols)) {
                $cols[] = $col_name;
            }
        }

        if (count($this->params['extra_cols'])) {
            foreach ($this->params['extra_cols'] as $col_name => $col_params) {
                if (isset($col_params['show']) && !(bool) $col_params['show']) {
                    continue;
                }
                if (!in_array($col_name, $cols)) {
                    $cols[] = $col_name;
                    $this->params['cols'][] = $col_name;
                }
            }
            $this->object->config->addParams($this->config_path . '/cols', $this->params['extra_cols']);
        }

        $list_cols = array();

        if ($this->params['configurable'] && BimpObject::objectLoaded($this->userConfig)) {
            $list_cols = $this->userConfig->getData('cols');
        }

        if (!is_array($list_cols) || !count($list_cols)) {
            $list_cols = $this->params['cols'];
        }

        $this->cols = array();

        foreach ($list_cols as $col_name) {
            if (!in_array($col_name, $cols)) {
                continue;
            }
            $show = (int) $this->object->getConf('lists_cols/' . $col_name . '/show', 1, false, 'bool');
            $show = (int) $this->object->getConf($this->config_path . '/cols/' . $col_name . '/show', $show, false, 'bool');

            if ($show) {
                $field = $this->object->getConf('lists_cols/' . $col_name . '/field', '');
                $field = $this->object->getConf($this->config_path . '/cols/' . $col_name . '/field', $field);
                if ($field && $this->object->isDolObject()) {
                    if (!$this->object->dol_field_exists($field)) {
                        continue;
                    }
                }
                $this->cols[] = $col_name;
            }
        }
    }

    protected function fetchCols()
    {
        $this->cols = array();
        $cols = array();

        // Colonnes config user: 
        if ($this->params['configurable'] && BimpObject::objectLoaded($this->userConfig)) {
            $user_cols = $this->userConfig->getData('cols');

            foreach ($user_cols as $col_name => $col_params) {
                $cols[] = $col_name;
            }
        }

        // Colonnes par défaut de la liste: 
        if (empty($cols)) {
            foreach ($this->params['cols'] as $col_name) {
                $cols[] = $col_name;
            }

            if (isset($this->params['extra_cols']) && is_array($this->params['extra_cols'])) {
                foreach ($this->params['extra_cols'] as $extra_col_name => $extra_col_params) {
                    if (!in_array($extra_col_name, $cols)) {
                        $cols[] = $col_name;
                    }
                }
            }
        }

        foreach ($cols as $col_name) {
            $col_errors = array();
            $field_name = '';
            $field_object = self::getColFieldObject($this->object, $col_name, $field_name, $col_errors);

            if (empty($col_errors) && is_a($field_object, 'BimpObject')) {
                if (in_array($field_name, $this->object->params['fields']) && !$this->object->isFieldActivated($field_name)) {
                    continue;
                }
            }

            $col_params = $this->getColParams($col_name);

            if ((int) BimpTools::getArrayValueFromPath($col_params, 'show', 1)) {
                $this->cols[$col_name] = $this->getColParams($col_name);
            }
        }
    }

    public function getColParams($col_name)
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $col_params = array();

        if (isset($this->params['extra_cols'][$col_name])) {
            $col_params = $this->params['extra_cols'][$col_name];
        } else {
            $col_params = self::getObjectConfigColParams($this->object, $col_name, $this->name);
        }

        if ($this->params['configurable'] && BimpObject::objectLoaded($this->userConfig)) {
            $user_cols = $this->userConfig->getData('cols');

            if (isset($user_cols[$col_name])) {
                $user_params = $user_cols[$col_name];

                if (isset($user_params['label']) && (string) $user_params['label']) {
                    $col_params['label'] = $user_params['label'];
                }

                if (isset($user_params['min_width']) && (int) $user_params['min_width']) {
                    $col_params['min_width'] = (int) $user_params['min_width'];
                }

                if (isset($user_params['align']) && $user_params['align']) {
                    $col_params['align'] = $user_params['align'];
                }

                if (isset($user_params['edit'])) {
                    $col_params['edit'] = (int) $user_params['edit'];
                }

                if (isset($user_params['display_name']) && (string) $user_params['display_name']) {
                    $col_params['display'] = $user_params['display_name'];
                }

                if (isset($user_params['display_options']) && is_array($user_params['display_options'])) {
                    $col_params['display_options'] = $user_params['display_options'];
                }

                if (isset($user_params['object_link'])) {
                    $col_params['object_link'] = (int) $user_params['object_link'];
                }
            }
        }

        $current_bc = $prev_bc;
        return $col_params;
    }

    public function getDefaultCols(&$errors = array())
    {
        if (!$this->isOk()) {
            $errors = BimpTools::merge_array($errors, $this->errors);
            return array();
        }

        $cols = array();

        foreach ($this->params['cols'] as $col_name) {
            $cols[$col_name] = $this->getColParams($col_name);
        }

        return $cols;
    }

    public function getItemColsParams($base_object, $col_name, $field_object, $field_name, &$errors = array())
    {
        $item_col_params = $this->getDefaultParams(self::$item_col_params);

        if (is_a($field_object, 'BimpObject') && $field_object->config->isDefined('lists_cols/' . $field_name)) {
            $override_params = self::fetchParamsStatic($field_object->config, 'lists_cols/' . $field_name, self::$item_col_params, $errors, true, true);
            $item_col_params = BimpTools::overrideArray($item_col_params, $override_params, true, true);
        }

        if (is_a($base_object, 'bimpObject')) {
            if ($field_name !== $col_name && $base_object->config->isDefined('lists_cols/' . $col_name)) {
                $override_params = self::fetchParamsStatic($base_object->config, 'lists_cols/' . $col_name, self::$item_col_params, $errors, true, true);
                $item_col_params = BimpTools::overrideArray($item_col_params, $override_params, true, true);
            }

            if ($base_object->config->isDefined('lists/' . $this->name . '/cols/' . $col_name)) {
                $override_params = self::fetchParamsStatic($base_object->config, 'lists/' . $this->name . '/cols/' . $col_name, self::$item_col_params, $errors, true, true);
                $item_col_params = BimpTools::overrideArray($item_col_params, $override_params, true, true);
            }
        }

        return $item_col_params;
    }

    // Getters statiques: 

    public static function getObjectConfigColParams(BimpObject $object, $col_name, $list_name = '')
    {
        $params = array();
        $errors = array();

        if (is_a($object, 'BimpObject')) {
            $field_name = '';
            $field_object = self::getColFieldObject($object, $col_name, $field_name, $errors);

            if (!count($errors)) {
                $params = self::getDefaultParams(self::$col_params);

                // Label du champ: 
                if ($field_object->field_exists($field_name)) {
                    $params['label'] = $field_object->getConf('fields/' . $field_name . '/label', '', true);
                } elseif ($field_object->config->isDefined('objects/' . $field_name)) {
                    // todo: le champ final correspond à une sous-liste d'enfants. 
                }

                // Lists cols de l'objet propriétaire du champ: 
                if ($field_object->config->isDefined('lists_cols/' . $field_name)) {
                    $override_params = self::fetchParamsStatic($field_object->config, 'lists_cols/' . $field_name, self::$col_params, $errors, true, true);
                    if (!empty($override_params)) {
                        $params = BimpTools::overrideArray($params, $override_params, true, true);
                    }
                }

                // Lists cols de l'objet courant: 
                if ($field_name !== $col_name && $object->config->isDefined('lists_cols/' . $col_name)) {
                    $override_params = self::fetchParamsStatic($object->config, 'lists_cols/' . $col_name, self::$col_params, $errors, true, true);
                    if (!empty($override_params)) {
                        $params = BimpTools::overrideArray($params, $override_params, true, true);
                    }
                }

                // Cols de l'objet courant: 
                if ($list_name && $object->config->isDefined('lists/' . $list_name . '/cols/' . $col_name)) {
                    $override_params = self::fetchParamsStatic($object->config, 'lists/' . $list_name . '/cols/' . $col_name, self::$col_params, $errors, true, true);
                    if (!empty($override_params)) {
                        $params = BimpTools::overrideArray($params, $override_params, true, true);
                    }
                }
            }
        } else {
            $errors[] = 'Object associé invalide';
        }

        if (count($errors)) {
            BimpCore::addlog('Erreur(s) lors de la récupération des paramètres d\'une colonne de liste', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', $object, array(
                'Nom colonne' => $col_name,
                'Nom Liste'   => $list_name,
                'Erreurs'     => $errors
            ));
        }

        return $params;
    }

    public static function getColFieldObject($object, $col_name, &$field_name = '', &$errors = array(), &$field_prefixe = '')
    {
        $field_object = null;
        $children = explode(':', $col_name);
        $field_name = array_pop($children);
        $field_prefixe = '';

        if (is_a($object, 'BimpObject')) {
            if ((string) $field_name) {
                $field_object = $object;

                if (count($children)) {
                    foreach ($children as $child_name) {
                        $child = $field_object->getChildObject($child_name);

                        if (is_a($child, 'BimpObject')) {
                            $field_prefixe .= $child_name . '___';
                            $field_object = $child;
                        } else {
                            $errors[] = 'Instance enfant "' . $child_name . '" invalide pour l\'objet "' . $field_object->object_name . '"';
                            break;
                        }
                    }
                }
            } else {
                $errors[] = 'Nom du champ absent';
            }
        } else {
            $errors[] = 'Object associé invalide';
        }

        return $field_object;
    }

    public static function getDefaultColsStatic($object, $list_name = 'default', &$errors = array())
    {
        if (is_a($object, 'BimpObject')) {
            if (!$list_name) {
                $list_name = 'default';
            }

            if ($object->config->isDefined('lists/' . $list_name)) {
                $bc_list = new BC_ListTable($object, $list_name);
                return $bc_list->getDefaultCols($errors);
            }
        } else {
            $errors[] = 'Objet invalide';
        }

        return array();
    }

    public static function getColFullTitle($base_object, $full_name, &$errors = array())
    {
        $full_name = str_replace('___', ':', $full_name);

        $title = '';

        if (is_a($base_object, 'BimpObject')) {
            $title = BimpTools::ucfirst($base_object->getLabel()) . ' > ';
            $children = explode(':', $full_name);
            $field_name = array_pop($children);
            $field_object = $base_object;

            while (!empty($children)) {
                $child_name = array_shift($children);

                if ($child_name) {
                    $child = $field_object->getChildObject($child_name);

                    if (is_a($child, 'BimpObject')) {
                        $child_label = $field_object->getChildLabel($child_name);
                        $title .= BimpTools::ucfirst($child_label) . ' > ';
                        $field_object = $child;
                    } else {
                        $errors[] = 'L\'objet lié "' . $child_name . '" n\'existe pas pour les ' . $filter_obj->getLabel('name_plur');
                        $title = '';
                        break;
                    }
                }
            }

            if (!count($errors)) {
                $label = '';
                if (is_a($field_object, 'BimpObject')) {
                    $label = $field_object->getConf('lists_cols/' . $field_name . '/label', $field_object->getConf('fields/' . $field_name . '/label', ''));
                }
                if (!$label) {
                    $label = $field_name;
                }
                $title .= $label;
            }
        }

        if (!$title) {
            $title = str_replace(':', ' > ', $full_name);
        }

        return $title;
    }

    public static function ObjectColExists($base_object, $col_name, $list_name = '', &$errors = array())
    {
        $col_errors = array();

        if (is_a($base_object, 'bimpObject')) {
            $field_name = '';
            $field_object = self::getColFieldObject($base_object, $col_name, $field_name, $col_errors);

            if (empty($col_errors) && is_a($field_object, 'BimpObject') && $field_name) {
                if ($field_object->field_exists($field_name)) {
                    return 1;
                }
                if ($field_object->config->isDefined('lists_cols/' . $field_name)) {
                    return 1;
                }

                if ($list_name && $field_name === $col_name) {
                    if ($base_object->config->isDefined('lists/' . $list_name . '/cols/' . $col_name)) {
                        return 1;
                    }
                }

                $col_errors[] = 'Cette colonne n\'existe pas';
            }
        } else {
            $col_errors[] = 'Objet de base invalide';
        }

        if (!empty($col_errors)) {
            $errors[] = BimpTools::getMsgFromArray($col_errors, 'Colonne "' . $col_name . '"');
        }

        return 0;
    }

    // Gestion des filtres: 

    public function getSearchFilters(&$joins = array())
    {
        if (!$this->isOk()) {
            return array();
        }

        $filters = array();

        if (BimpTools::isSubmit('search_fields')) {
            $fields = array();

            $searches = BimpTools::getValue('search_fields', array());

            foreach ($searches as $col_name => $search_filter) {
                $field_object = $this->object;
                $children = explode('___', $col_name);
                $field_name = array_pop($children);

                $field_alias = 'a';
                $field_object = $this->object;
                $errors = array();

                if (!empty($children)) {
                    $errors = $this->object->getRecursiveChildrenJoins($children, $filters, $joins, 'a', $field_alias, $field_object);
                }

                if (empty($errors)) {
                    if (!isset($fields[$field_alias])) {
                        $fields[$field_alias] = array(
                            'object' => $field_object,
                            'fields' => array()
                        );
                    }

                    $fields[$field_alias]['fields'][$field_name] = $search_filter;
                }
            }

            if (!empty($fields)) {
                foreach ($fields as $parent_alias => $fields_data) {
                    if (is_a($fields_data['object'], 'BimpObject')) {
                        $new_filters = $fields_data['object']->getSearchFilters($joins, $fields_data['fields'], $parent_alias);

                        if (is_array($new_filters)) {
                            $filters = array_merge($filters, $new_filters);
                        }
                    }
                }
            }
        }

        return $filters;
    }

    // Gestion des lignes: 

    protected function fetchRows()
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        if (is_null($this->items)) {
            $this->fetchItems();
        }

        $this->rows = array();
        $rows = array();

        $primary = $this->object->getPrimary();

        $this->setConfPath();

        $object_instance = $this->object;

        foreach ($this->items as $item) {
            $object = BimpCache::getBimpObjectInstance($this->object->module, $this->object->object_name, (int) $item[$primary], $this->parent);
            if (BimpObject::objectLoaded($object)) {
                $this->object = $object;
                $item_errors = array();
                $item_params = self::fetchParamsStatic($object->config, $this->config_path, self::$item_params, $item_errors, true);

                $row = array(
                    'params' => array(
                        'single_cell'    => false,
                        'item_params'    => $item_params,
                        'canEdit'        => (int) ($object->can("edit") && $object->isEditable()),
                        'canView'        => (int) $object->can("view"),
                        'canDelete'      => (int) ($object->can("delete") && $object->isDeletable()),
                        'instance_name'  => $object->getInstanceName(),
                        'url'            => '',
                        'page_btn_label' => '',
                    ),
                    'cols'   => array()
                );

                if ((int) $item_params['page_btn']) {
                    $url = $object->getUrl();
                    if ($url) {
                        $row['params']['url'] = $url;
                        $row['params']['page_btn_label'] = 'Afficher la page';
                    }
                }

                if (($this->params['single_cell']['col'])) {
                    if ($object->doMatchFilters($this->params['single_cell']['filters'])) {
                        $row['params']['single_cell'] = true;
                    }
                }
                $new_values = isset($this->new_values[(int) $item[$primary]]) ? $this->new_values[(int) $item[$primary]] : array();
                if ($this->params['positions']) {
                    $row['params']['position'] = (int) $object->getData('position');
                }
                foreach ($this->cols as $col_name => $col_params) {
                    if ($row['params']['single_cell'] && $col_name !== $this->params['single_cell']['col']) {
                        continue;
                    }

                    $row['cols'][$col_name] = array(
                        'content'  => '',
                        'hidden'   => 0,
                        'td_style' => ''
                    );

                    if (!(int) $col_params['show']) {
                        continue;
                    }

                    $item_col_errors = array();
                    $item_col_params = array();
                    $bc_field = null;
                    $field_name = '';
                    $field_name_prefixe = '';
                    $has_total = false;
                    $field_object = self::getColFieldObject($object, $col_name, $field_name, $item_col_errors, $field_name_prefixe);

                    if (empty($item_col_errors)) {
                        $item_col_params = $this->getItemColsParams($object, $col_name, $field_object, $field_name, $item_col_errors);

                        if ($field_object->field_exists($field_name)) {
                            $bc_field = new BC_Field($field_object, $field_name, (int) $col_params['edit']);
                            $bc_field->name_prefix = $field_name_prefixe;
                            $bc_field->display_name = $col_params['display'];
                            $bc_field->display_options = $col_params['display_options'];

                            if (isset($new_values[$col_params['field']])) {
                                $bc_field->new_value = $new_values[$col_params['field']];
                            }

                            $row_content = $bc_field->renderHtml();

                            if ((int) $col_params['object_link']) {
                                $url = $object->getUrl();

                                if ($url) {
                                    $row_content = '<a href="' . $url . '">' . $row_content . '</a>';
                                }
                            }

                            $row['cols'][$col_name]['content'] = $row_content;

                            $has_total = (int) $col_params['has_total'];

                            if (!$has_total) {
                                $has_total = (int) $bc_field->params['has_total'];
                            }
                        } elseif (isset($item_col_params['value'])) {
                            $row['cols'][$col_name]['content'] .= $item_col_params['value'];
                        }

                        if (isset($item_col_params['td_style'])) {
                            $row['cols'][$col_name]['td_style'] = $item_col_params['td_style'];
                        }

                        if (isset($item_col_params['hidden'])) {
                            $row['cols'][$col_name]['hidden'] = (int) $item_col_params['hidden'];
                        }

                        if (!isset($this->totals[$col_name])) {
                            if ((int) $this->params['total_row'] && $has_total) {
                                $this->totals[$col_name] = array(
                                    'data_type' => '',
                                    'sql_key'   => '',
                                    'value'     => 0
                                );
                                if (is_a($bc_field, 'BC_Field')) {
                                    $this->totals[$col_name]['data_type'] = $bc_field->params['type'];
                                } elseif (!is_null($col_params['total_type'])) {
                                    $this->totals[$col_name]['data_type'] = $col_params['total_type'];
                                }
                            }
                        }
                    } else {
                        $row['cols'][$col_name]['content'] = BimpRender::renderAlerts(BimpTools::getMsgFromArray($item_col_errors));
                    }
                }
                $rows[$item[$primary]] = $row;
            }
        }

        $this->object = $object_instance;

        if (!is_null($this->parent)) {
            $this->object->parent = $this->parent;
        }

        $this->setConfPath();

        $this->rows = $rows;
        
        if (method_exists($this->object, 'listRowsOverride')) {
            $this->object->listRowsOverride($this->name, $this->rows);
        }

        if ((int) $this->params['total_row']) {
            $this->fetchTotals();
        }

        $current_bc = $prev_bc;
    }

    protected function fetchTotals()
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $fields = array();

        $filters = $this->final_filters;
        $joins = $this->final_joins;

        $errors = array();

        foreach ($this->totals as $col_name => $params) {
            $method_name = 'get' . ucfirst($col_name) . 'ListTotal';

            if (method_exists($this->object, $method_name)) {
                $this->totals[$col_name] = $this->object->{$method_name}($filters, $joins, $this->items);
                continue;
            }

            $col_name = str_replace(':', '___', $col_name);

            if (method_exists($this->object, 'get' . ucfirst($col_name) . 'SqlKey')) {
                $sqlKey = $this->object->{'get' . ucfirst($col_name) . 'SqlKey'}($joins);
                if ($sqlKey) {
                    $fields[$sqlKey] = $col_name;
                }
            } else {
                $children = explode('___', $col_name);
                $field_name = array_pop($children);
                $field_alias = 'a';
                $field_object = $this->object;
                $col_errors = array();

                if (!empty($children)) {
                    $col_errors = $this->object->getRecursiveChildrenJoins($children, $filters, $joins, 'a', $field_alias, $field_object);
                }

                if (empty($col_errors) && $field_name && is_a($field_object, 'BimpObject')) {
                    $sqlKey = $field_object->getFieldSqlKey($field_name, $field_alias, null, $filters, $joins, $col_errors);
                    if ($sqlKey) {
                        $fields[$sqlKey] = $col_name;
                    }
                }
            }

            if (!empty($col_errors)) {
                $errors[] = BimpTools::getMsgFromArray($col_errors, 'Colonne "' . str_replace('___', ' > ', $col_name) . '"');
            }
        }

        if (!empty($fields)) {
            $result = $this->object->getListTotals($fields, $filters, $joins);

            if (!empty($result)) {
                foreach ($fields as $key => $col_name) {
                    if (isset($result[0][$col_name])) {
                        $this->totals[str_replace('___', ':', $col_name)]['value'] = $result[0][$col_name];
                    }
                }
            }
        }

        if (!empty($errors)) {
            BimpCore::addlog('Erreur génération requête SQL pour total de liste', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', null, array(
                'Erreurs' => $errors
            ));
        }

        $current_bc = $prev_bc;
    }

    public function setSelectedRows($selected_rows)
    {
        $this->selected_rows = $selected_rows;
    }

    public function getBeforeListContent()
    {
        if ($this->params['before_list_callback']) {
            if (method_exists($this->object, $this->params['before_list_callback'])) {
                return $this->object->{$this->params['before_list_callback']}($this);
            }
        }

        return '';
    }

    public function getAfterListContent()
    {
        if ($this->params['after_list_callback']) {
            if (method_exists($this->object, $this->params['after_list_callback'])) {
                return $this->object->{$this->params['after_list_callback']}($this);
            }
        }

        return '';
    }

    public function getCsvColOptionsInputs()
    {
        $rows = array();

        if (count($this->errors)) {
            return BimpRender::renderAlerts($this->errors);
        }

        $user_config_cols_options = array();

        if (BimpObject::objectLoaded($this->userConfig)) {
            $user_config_cols_options = $this->userConfig->getData('cols');
        }

        $cols = $this->cols;
        $rows = array();
        foreach ($cols as $col_name => $col_params) {
            if (!(int) $col_params['show'] || (int) $col_params['hidden'] || !(int) $col_params['available_csv']) {
                continue;
            }

            $user_options = BimpTools::getArrayValueFromPath($user_config_cols_options, $col_name, array());

            $label = BimpTools::getArrayValueFromPath($user_options, 'label', BimpTools::getArrayValueFromPath($col_params, 'label', ''));
            $content = '';
            $field_name = '';
            $col_errors = array();
            $field_object = self::getColFieldObject($this->object, $col_name, $field_name, $col_errors);

            if (count($col_errors)) {
                if (!$label) {
                    $label = $col_name;
                }
                $content = BimpRender::renderAlerts($col_errors);
            } else {
                if ($field_object->field_exists($field_name)) {
                    $bc_field = new BC_Field($field_object, $field_name);
                    if (!$label) {
                        $label = $bc_field->getParam('label', $field_name);
                    }
                    $content = $bc_field->renderCsvOptionsInput('col_' . $col_name . '_option', (isset($user_config_cols_options[$col_name]['csv_option']) ? $user_config_cols_options[$col_name]['csv_option'] : ''));
                }

                if (!$label) {
                    $label = $col_name;
                }

                if (!$content) {
                    $content = 'Valeur affichée';
                }
            }

            $title = self::getColFullTitle($this->object, $col_name);

            $rows[] = array(
                'label'   => $label . '<br/><span class="small" style="color: #e6dccf">' . $title . '</span>',
                'content' => $content
            );
        }

        return $rows;
    }

    // Rendus HTML:

    public function renderBeforePanelHtml()
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = '';
        $html .= '<div id="' . $this->identifier . '_objectViewContainer" class="objectViewContainer"';

        $view_check = false;
        if ((int) $this->params['inline_view_item']) {
            if ($this->object->fetch((int) $this->params['inline_view_item'])) {
                if ($this->object->can("view")) {
                    $view_name = $this->object->getConf($this->config_path . '/inline_view', '');
                    if ($view_name) {
                        $view = new BC_View($this->object, $view_name, false);
                        $onclick = 'closeObjectView(\'' . $this->identifier . '_objectViewContainer\');';
                        $view->params['footer_extra_btn'][] = array(
                            'label'   => 'Fermer',
                            'icon'    => 'times',
                            'onclick' => $onclick
                        );
                        $html .= '>' . $view->renderHtml();
                        unset($view);
                        $view_check = true;
                    }
                }
                $this->object->reset();
            }
        }

        if (!$view_check) {
            $html .= ' style="display: none">';
        }

        $html .= '</div>';

        $html .= '<div id="' . $this->identifier . '_objectFormContainer" class="objectFormContainer" style="display: none"></div>';

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderHtmlContent()
    {
        $html = '';

        if (count($this->errors)) {
            return parent::renderHtml();
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html .= $this->renderListParamsInputs();

        if (!is_null($this->params['filters_panel'])) {
            $html .= '<div class="row">';
            $html .= '<div class="listFiltersPanelContainer col-xs-12 col-sm-12 col-md-3 col-lg-2"' . (!(int) $this->params['filters_panel_open'] ? ' style="display: none"' : '') . '>';
            $html .= $this->renderFiltersPanel();
            $html .= '</div>';
            $html .= '<div class="objectlistTableContainer ' . ((int) $this->params['filters_panel_open'] ? 'col-xs-12 col-sm-12 col-md-9 col-lg-10' : 'col-xs-12') . '">';
        }

        $html .= '<div class="before_list_content" data-refresh="' . (int) $this->params['refresh_before_content'] . '">';
        $html .= $this->getBeforeListContent();
        $html .= '</div>';

        $html .= $this->renderActiveFilters();

        if ($this->params['configurable']) {
            $msg = BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Les colonnes ont été modifiées  ';
            $msg .= '<span class="btn btn-default" onclick="reloadObjectList(\'' . $this->identifier . '\', null, true)">';
            $msg .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Recharger la liste';
            $msg .= '</span>';

            $html .= '<div style="display: none; margin: 10px 0; text-align: right;" class="list_table_cols_change_notification">';
            $html .= '<div style="text-align: left; display: inline-block; width: 420px">';
            $html .= BimpRender::renderAlerts($msg, 'warning');
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '<table class="objectlistTable" style="border: none; min-width: ' . ($this->colspan * 80) . 'px" width="100%">';
        $html .= '<thead class="listTableHead">';

        $html .= $this->renderHeaderRow();
        $html .= $this->renderSearchRow();
        $html .= $this->renderAddObjectRow();

        $html .= '</thead>';
        $html .= '<tbody class="listRows">';

        $html .= $this->renderRows();

        $html .= '</tbody>';

        $html .= '<tfoot>';

        $html .= '<tr class="listFooterButtons">';
        $html .= '<td colspan="' . $this->colspan . '" class="fullrow">';
        $html .= '<div style="text-align: right">';
        foreach ($this->getHeaderButtons() as $button) {
            $button['classes'][] = 'headerBtn';
            $html .= BimpRender::renderButton($button);
        }
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';

        if ($this->params['pagination']) {
            $html .= $this->renderPaginationRow();
        }
        $html .= '</tfoot>';

        $html .= '</table>';

        $html .= '<div class="after_list_content" data-refresh="' . (int) $this->params['refresh_after_content'] . '">';
        $html .= $this->getAfterListContent();
        $html .= '</div>';

        if (!is_null($this->params['filters_panel'])) {
            $html .= '</div>';
            $html .= '</div>';
        }

        if ($this->object->params['has_graph']) {
            $html .= '<div id="' . $this->identifier . '_chartContainer" style="height: 300px; width: 100%;"></div>';
            $html .= '<script src="https://canvasjs.com/assets/script/jquery.canvasjs.min.js"></script><script>' . "updateGraph('" . $this->identifier . "', '" . $this->name . "');" . '</script>';
        }

        $html .= '<div class="ajaxResultContainer" id="' . $this->identifier . '_result"></div>';

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderHtmlFooter()
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = $this->renderBulkActions();

        $html .= $this->renderFooterExtraBtn();

        if ($this->params['footer_extra_content']) {
            $html .= $this->params['footer_extra_content'];
        }

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderHeaderRow()
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = '';

        if ($this->isOk() && count($this->cols)) {
            $this->search = false;
            $default_sort_way = $this->params['sort_way'];

            $html .= '<tr class="headerRow">';

            $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">';
            if ($this->params['checkboxes']) {
                $html .= '<input type="checkbox" id="' . $this->identifier . '_checkall" onchange="toggleCheckAll(\'' . $this->identifier . '\', $(this));"/>';
            }
            $html .= '</th>';

            if ($this->params['total_row']) {
                $html .= '<th style="width: 45px; min-width: 45px"></th>';
            }

            if ($this->params['positions']) {
                $html .= '<th class="positionHandle"' . (!$this->params['positions_open'] ? ' style="display: none"' : '') . '></th>';
            }

            $cols_errors = array();

            foreach ($this->cols as $col_name => $col_params) {
                $col_errors = array();
                $field_name = '';
                $field_object = self::getColFieldObject($this->object, $col_name, $field_name, $col_errors);

                if (!empty($col_errors)) {
                    $cols_errors[] = BimpTools::getMsgFromArray($col_errors, 'Colonne "' . $col_name . '"');
                    continue;
                }

                $col_label = BimpTools::getArrayValueFromPath($col_params, 'label', '');
                $sortable = false;

                if ($this->params['enable_sort']) {
                    if ($field_object->field_exists($field_name)) {
                        if ($field_object->getConf('fields/' . $field_name . '/sortable', 1, false, 'bool')) {
                            $sortable = true;
                        }
                    }
                }

                $html .= '<th';
                if (isset($col_params['width']) && !is_null($col_params['width'])) {
                    $html .= ' width="' . $col_params['width'] . '"';
                }

                $html .= ' style="';
                if (isset($col_params['min_width']) && !is_null($col_params['min_width'])) {
                    $html .= 'min-width: ' . $col_params['min_width'] . ';';
                }
                if (isset($col_params['max_width']) && !is_null($col_params['max_width'])) {
                    $html .= 'max-width: ' . $col_params['max_width'] . ';';
                }
                $html .= '"';

                $html .= ' data-col_name="' . $col_name . '"';
                $html .= ' data-field_name="' . $field_name . '"';
                $html .= '>';

                if ($sortable) {
                    $html .= '<span id="' . $col_name . '_sortTitle" class="sortTitle sorted-';

                    if ($this->params['sort_field'] === $col_name) {
                        $html .= strtolower($this->params['sort_way']);
                        if (!$this->params['positions_open']) {
                            $html .= ' active';
                        }
                    } else {
                        $html .= strtolower($default_sort_way);
                    }
                    if ($this->params['positions_open']) {
                        $html .= ' deactivated';
                    }
                    $html .= '" onclick="if (!$(this).hasClass(\'deactivated\')) { sortList(\'' . $this->identifier . '\', \'' . $col_name . '\'); }">';

                    $html .= $col_label . '</span>';
                } else {
                    $html .= $col_label;
                }
                $html .= '</th>';

                if (!$this->search) {
                    if ($this->object->getConf('fields/' . $field_name . '/searchable', 1, false, 'any')) {
                        $this->search = true;
                    }
                }
            }

            $tools_width = 64;
            $tools_html = '<div class="headerTools">';
            $tools_html .= '<span class="fa-spin loadingIcon"></span>';

            if (!is_null($this->params['filters_panel'])) {
                $tools_html .= '<span class="headerButton openFiltersPanelButton open-close action-' . ($this->params['filters_panel_open'] ? 'close' : 'open') . '"></span>';
                $tools_width += 44;
            }
            if ($this->search && $this->params['enable_search']) {
                $tools_html .= '<span class="headerButton openSearchRowButton open-close action-' . ($this->params['search_open'] ? 'close' : 'open') . '"></span>';
                $tools_width += 44;
            }
            if ($this->params['add_object_row']) {
                $tools_html .= '<span class="headerButton openAddObjectRowButton open-close action-' . ($this->params['add_object_row_open'] ? 'close' : 'open') . '"></span>';
                $tools_width += 44;
            }
            if ($this->params['positions']) {
                $tools_html .= '<span class="headerButton activatePositionsButton bs-popover open-close action-' . ($this->params['positions_open'] ? 'close' : 'open') . '"';
                $tools_html .= '></span>';
                $tools_width += 44;
            }
            if ($this->params['checkboxes'] && count($this->params['bulk_actions'])) {
                $tools_html .= '<span class="headerButton displayPopupButton openBulkActionsPopupButton"';
                $tools_html .= ' data-popup_id="' . $this->identifier . '_bulkActionsPopup"></span>';
                $tools_html .= $this->renderBulkActionsPopup();
                $tools_width += 32;
            }

            $parametersPopUpHtml = $this->renderParametersPopup();
            if ($parametersPopUpHtml) {
                $tools_html .= '<div style="display: inline-block">';
                $tools_html .= '<span class="headerButton displayPopupButton openParametersPopupButton"';
                $tools_html .= ' data-popup_id="' . $this->identifier . '_parametersPopup"></span>';
                $tools_html .= $parametersPopUpHtml;
                $tools_html .= '</div>';
                $tools_width += 32;
            }

            if ($this->params['enable_refresh']) {
                $tools_html .= '<span class="headerButton refreshListButton bs-popover"';
                $tools_html .= BimpRender::renderPopoverData('Actualiser les lignes', 'left', false, '#' . $this->identifier);
                $tools_html .= '></span>';
                $tools_width += 32;
            }

            $tools_html .= '</div>';

            $html .= '<th class="th_tools" style="min-width: ' . $tools_width . 'px">';
            $html .= $tools_html;
            $html .= '</th>';

            $html .= '</tr>';
        }

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderSearchRow()
    {
        if (!$this->search || !$this->params['enable_search'] || !$this->isOk() || !count($this->cols)) {
            return '';
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        if (!is_null($this->id_parent)) {
            $this->object->setIdParent($this->id_parent);
        }

        $html = '';

        $html .= '<tr id="' . $this->identifier . '_searchRow" class="listSearchRow"';
        $html .= ' data-list_name="' . $this->name . '"';
        $html .= ' data-module_name="' . $this->object->module . '"';
        $html .= ' data-object_name="' . $this->object->object_name . '"';
        if (!$this->params['search_open']) {
            $html .= ' style="display: none"';
        }
        $html .= '>';

        $html .= '<td style="text-align: center"><i class="fa fa-search"></i></td>';

        if ($this->params['total_row']) {
            $html .= '<td style="width: 45px; min-width: 45px"></td>';
        }

        if ($this->params['positions']) {
            $html .= '<td class="positionHandle"' . (!$this->params['positions_open'] ? ' style="display: none"' : '') . '></td>';
        }

        foreach ($this->cols as $col_name => $col_params) {
            $extra_data = array();
            $col_errors = array();
            $field_name = '';
            $field_object = self::getColFieldObject($this->object, $col_name, $field_name, $col_errors);

            $html .= '<td>';

            if (empty($col_errors)) {
                $input_name = 'search_' . str_replace(':', '___', $col_name);
                if (in_array($field_name, BimpObject::$common_fields)) {
                    if (!is_null($field_object)) {
                        $html .= $field_object->getCommonFieldSearchInput($field_name, $input_name);
                    }
                } elseif ($field_object->field_exists($field_name)) {
                    $field = new BC_Field($field_object, $field_name, true);
                    $html .= $field->renderSearchInput($extra_data, $input_name);
                    unset($field);
                } elseif (!is_null($col_params['search']) && method_exists($field_object, 'get' . ucfirst($field_name) . 'SearchFilters')) {
                    $search_type = $col_params['search']['type'];
                    $content = BimpInput::renderInput($col_params['search']['input']['type'], $input_name, '', $col_params['search']['input']['options']);
                    $html .= BimpInput::renderSearchInputContainer($input_name, $search_type, $col_params['search']['search_on_key_up'], 1, $content, $extra_data);
                } elseif (!is_null($col_params['search_list'])) {
                    $content = BimpInput::renderSearchListInputFromConfig($this->object, $this->config_path . '/cols/' . $col_name, $input_name, '', null);
                    $html .= BimpInput::renderSearchInputContainer($input_name, 'field_input', 0, 1, $content, $extra_data);
                }
            }

            $html .= '</td>';
        }

        $html .= '<td class="searchTools">';
        $html .= '<button type="button" class="btn btn-default" onclick="resetListSearchInputs(\'' . $this->identifier . '\')">';
        $html .= BimpRender::renderIcon('fas_eraser', 'iconLeft') . 'Réinitialiser</button>';
        $html .= '</td>';
        $html .= '</tr>';

        $this->object->reset();

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderTotalRow()
    {
        $html = '';

        if ((int) $this->params['total_row'] && !empty($this->totals)) {
            $html .= '<tr class="margin_row">';
            $html .= '<td colspan="' . $this->colspan . '" class="fullrow"></td>';
            $html .= '</tr>';

            $html .= '<tr class="total_row">';

            // Checkboxes: 
            $html .= '<th></th>';
            $html .= '<th>Total</th>';

            // Positions: 
            if ($this->params['positions']) {
                $html .= '<td class="positionHandle"' . (!$this->params['positions_open'] ? ' style="display: none"' : '') . '></td>';
            }

            foreach ($this->cols as $col_name => $col_params) {
                $html .= '<td>';
                if (isset($this->totals[$col_name])) {
                    switch ($this->totals[$col_name]['data_type']) {
                        case 'money':
                            $html .= BimpTools::displayMoneyValue($this->totals[$col_name]['value'], 'EUR', false, true);
                            break;

                        case 'percent':
                            $html .= BimpTools::displayFloatValue($this->totals[$col_name]['value'], 4) . '%';
                            break;

                        case 'float':
                            $html .= BimpTools::displayFloatValue($this->totals[$col_name]['value'], 4);
                            break;

                        default:
                            $html .= round($this->totals[$col_name]['value'], 4);
                            break;
                    }
                }
                $html .= '</td>';
            }
            $html .= '<td></td>';
            $html .= '</tr>';
        }

        return $html;
    }

    public function renderAddObjectRow()
    {
        if (!$this->object->can("create") || !$this->object->can("edit")) {
            return '';
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = '';

        $this->object->reset();

        if (!is_null($this->id_parent)) {
            $this->object->setIdParent($this->id_parent);
            $this->object->parent = $this->parent;
        }

        if ((int) $this->params['add_object_row'] && !is_null($this->config_path)) {
            $html .= '<tr id="' . $this->identifier . '_addObjectRow" class="addObjectRow inputsRow" style="' . ($this->params['add_object_row_open'] ? '' : 'display: none;') . '">';
            $html .= '<td><i class="fa fa-plus-circle"></i></td>';

            if ($this->params['total_row']) {
                $html .= '<td style="width: 45px; min-width: 45px"></td>';
            }

            if ($this->params['positions']) {
                $html .= '<td class="positionHandle"' . (!$this->params['positions_open'] ? ' style="display: none"' : '') . '></td>';
            }

            if (!is_null($this->id_parent)) {
                $parent_id_property = $this->object->getParentIdProperty();
                if (!is_null($parent_id_property)) {
                    $html .= '<td style="display: none">';
                    $html .= '<div class="inputContainer" data-field_name="' . $parent_id_property . '"';
                    $html .= ' data-initial_value="' . $this->id_parent . '"';
                    $html .= ' id="' . $this->object->object_name . '_' . $parent_id_property . '_addInputContainer">';
                    $html .= '<input type="hidden" name="' . $parent_id_property . '" ';
                    $html .= 'value="' . $this->id_parent . '"/>';
                    $html .= '</div>';
                    $html .= '</td>';
                }
            }

            foreach ($this->cols as $col_name => $col_params) {
                $field_name = '';
                $field_object = self::getColFieldObject($this->object, $col_name, $field_name);

                $html .= '<td>';
                if (isset($field_name) && $field_name && !in_array($field_name, BimpObject::$common_fields)) {
                    $bc_field = new BC_Field($field_object, $field_name, true);
                    $default_value = $bc_field->params['default_value'];
                    $bc_field->value = $default_value;
                    if ($bc_field->isEditable()) {
                        $html .= $bc_field->renderHtml();
                    }
                }
                $html .= '</td>';
            }

            $html .= '<td class="buttons">';
            $html .= '<button class="btn btn-default" onclick="addObjectFromList(\'' . $this->identifier . '\', $(this))">';
            $html .= '<i class="fa fa-plus-circle iconLeft"></i>Ajouter</span>';
            $html .= '</td>';
            $html .= '</tr>';
        }

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderPaginationRow()
    {
        if (!$this->params['pagination']) {
            return '';
        }

        $hide = (is_null($this->nbItems) || ($this->params['n'] <= 0) || ($this->params['n'] >= $this->nbItems));

        $html = '<tr class="paginationContainer"' . ($hide ? ' style="display: none"' : '') . '>';
        $html .= '<td colspan="' . $this->colspan . '" style="padding: 5px 10px 15px;" class="fullrow">';
        $html .= '<div id="' . $this->identifier . '_pagination" class="listPagination">';
        $html .= $this->renderPagination();
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';

        return $html;
    }

    public function renderBulkActions()
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = '';

        // Lignes sélectionnées: 
        $bulk_actions = array_merge($this->params['bulk_actions'], $this->params['extra_bulk_actions']);

        if (count($bulk_actions) && (int) $this->params['checkboxes']) {
            $buttons = array();

            foreach ($bulk_actions as $idx => $action_params) {
                $button = null;
                $label = isset($action_params['label']) ? $action_params['label'] : '';
                $onclick = isset($action_params['onclick']) ? $action_params['onclick'] : '';
                $icon = isset($action_params['icon']) ? $action_params['icon'] : '';
                $onclick = str_replace('list_id', $this->identifier, $onclick);
                $onclick = str_replace('id_parent', $this->id_parent, $onclick);
                foreach ($this->params['list_filters'] as $filter) {
                    if (BimpTools::isNumericType($filter['filter']) || is_string($filter['filter'])) {
                        $onclick = str_replace('list_filter_' . $filter['name'], $filter['filter'], $onclick);
                    }
                }
                if ($label && $onclick) {
                    $button = array(
                        'classes' => array('btn', 'btn-light-default'),
                        'label'   => $label,
                        'attr'    => array(
                            'type'    => 'button',
                            'onclick' => $onclick
                        )
                    );
                }
                if ($icon) {
                    $button['icon_before'] = $icon;
                }
                if (!is_null($button)) {
                    $buttons[] = BimpRender::renderButton($button, 'button');
                }
            }

            if (count($buttons)) {
                $title = BimpTools::ucfirst($this->object->getLabel('name_plur')) . ' sélectionné' . ($this->object->isLabelFemale() ? 'e' : '') . 's';
                $html .= BimpRender::renderDropDownButton($title, $buttons, array(
                            'icon' => 'fas_check-square'
                ));
            }
        }

        // Actions sur la liste filtrée complète: 

        $actions = $this->params['list_actions'];

        if (count($actions)) {
            $buttons = array();

            foreach ($actions as $idx => $action_params) {
                $action_name = isset($action_params['action']) ? $action_params['action'] : '';

                if (!$action_name || !$this->object->canSetAction($action_name)) {
                    continue;
                }

                $button = null;
                $label = isset($action_params['label']) ? $action_params['label'] : '';
                $icon = isset($action_params['icon']) ? $action_params['icon'] : '';

                $extra_data = BimpTools::getArrayValueFromPath($action_params, 'extra_data', array());
                $form_name = BimpTools::getArrayValueFromPath($action_params, 'form_name', '');
                $confirm_msg = BimpTools::getArrayValueFromPath($action_params, 'confirm_msg', '');
                $on_form_submit = BimpTools::getArrayValueFromPath($action_params, 'on_form_submit', '');
                $success_callback = BimpTools::getArrayValueFromPath($action_params, 'success_callback', '');
                $resultContainer = BimpTools::getArrayValueFromPath($action_params, 'resultContainer', '');

                $onclick = 'setFilteredListObjectsAction($(this), \'' . $this->identifier . '\', \'' . $action_name . '\', {';

                if (is_array($extra_data) && !empty($extra_data)) {
                    $fl = true;
                    foreach ($extra_data as $key => $value) {
                        if (!$fl) {
                            $onclick .= ', ';
                        } else {
                            $fl = false;
                        }
                        $onclick .= $key . ': ' . (BimpTools::isNumericType($value) ? $value : '\'' . $value . '\'');
                    }
                }
                $onclick .= '}, ' . ($form_name ? '\'' . $form_name . '\'' : 'null') . ', ';
                $onclick .= ($confirm_msg ? '\'' . $confirm_msg . '\'' : 'null') . ', ';
                $onclick .= ($on_form_submit ? '\'' . $on_form_submit . '\'' : 'null') . ', ';
                $onclick .= ($success_callback ? '\'' . $on_form_submit . '\'' : 'null') . ', ';
                $onclick .= ($resultContainer ? '\'' . $resultContainer . '\'' : 'null');
                $onclick .= ')';

                if ($label && $onclick) {
                    $button = array(
                        'classes' => array('btn', 'btn-light-default'),
                        'label'   => $label,
                        'attr'    => array(
                            'type'    => 'button',
                            'onclick' => $onclick
                        )
                    );
                }
                if ($icon) {
                    $button['icon_before'] = $icon;
                }
                if (!is_null($button)) {
                    $buttons[] = BimpRender::renderButton($button, 'button');
                }
            }

            if (count($buttons)) {
                $title = 'Liste filtrée';
                $html .= BimpRender::renderDropDownButton($title, $buttons, array(
                            'icon' => 'fas_bars'
                ));
            }
        }

        $buttons = array();

        if ($this->object->can("edit")) {
            $buttons[] = BimpRender::renderButton(array(
                        'classes'     => array('btn', 'btn-light-default'),
                        'label'       => 'Enregistrer toutes les modifications',
                        'attr'        => array(
                            'type'    => 'button',
                            'onclick' => 'saveAllRowsModifications(\'' . $this->identifier . '\', $(this))'
                        ),
                        'icon_before' => 'fas_save'
                            ), 'button');
            $buttons[] = BimpRender::renderButton(array(
                        'classes'     => array('btn', 'btn-light-default'),
                        'label'       => 'Annuler toutes les modifications',
                        'attr'        => array(
                            'type'    => 'button',
                            'onclick' => 'cancelAllRowsModifications(\'' . $this->identifier . '\', $(this))'
                        ),
                        'icon_before' => 'undo'
                            ), 'button');
            $title = BimpTools::ucfirst($this->object->getLabel('name_plur')) . ' modifié' . ($this->object->isLabelFemale() ? 'e' : '') . 's';
            $html .= '<span class="modifiedRowsActions" style="display: none">';
            $html .= BimpRender::renderDropDownButton($title, $buttons, array(
                        'icon' => 'fas_edit'
            ));
            $html .= '</span>';
        }

        $this->setConfPath();
        $current_bc = $prev_bc;
        return $html;
    }

    public function renderBulkActionsPopup()
    {
        $html = '';

        $bulk_actions = array_merge($this->params['bulk_actions'], $this->params['extra_bulk_actions']);

        if (!count($bulk_actions)) {
            return $html;
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html .= '<div id="' . $this->identifier . '_bulkActionsPopup" class="tinyPopup listPopup">';
        $html .= '<div class="title">';
        $html .= BimpTools::ucfirst($this->object->getLabel('name_plur')) . ' sélectionné' . ($this->object->isLabelFemale() ? 'e' : '') . 's';
        $html .= '</div>';

        foreach ($bulk_actions as $idx => $action_params) {
            $label = isset($action_params['label']) ? $action_params['label'] : '';
            $onclick = isset($action_params['onclick']) ? $action_params['onclick'] : '';
            $icon = isset($action_params['icon']) ? $action_params['icon'] : '';

            if ($label && $onclick) {
                if (preg_match('/^deleteSelectedObjects\(/', $onclick)) {
                    if (!$this->object->can("delete")) {
                        continue;
                    }
                }
                $html .= '<div><span class="btn';
                $onclick = str_replace('list_id', $this->identifier, $onclick);
                $onclick = str_replace('id_parent', $this->id_parent, $onclick);
                foreach ($this->params['list_filters'] as $filter) {
                    if (BimpTools::isNumericType($filter['filter']) || is_string($filter['filter'])) {
                        $onclick = str_replace('list_filter_' . $filter['name'], $filter['filter'], $onclick);
                    }
                }
                $html .= '" onclick="' . $onclick . '">';
                if ($icon) {
                    $html .= '<i class="' . BimpRender::renderIconClass($icon) . ' iconLeft"></i>';
                }
                $html .= $label . '</span></div>';
            }
        }
        $html .= '</div>';

        $this->setConfPath();
        $current_bc = $prev_bc;
        return $html;
    }

    public function renderParametersPopup()
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = '';
        $content = '';

        // Rechargement liste: 
        if ($this->params['configurable']) {
            $content .= '<div style="text-align: center; margin-bottom: 15px;">';
            $content .= BimpRender::renderButton(array(
                        'classes'     => array('btn', 'btn-default'),
                        'label'       => 'Recharger la liste',
                        'icon_before' => 'fas_redo',
                        'attr'        => array(
                            'onclick' => "reloadObjectList('" . $this->identifier . "', null, 1, " . (BimpObject::objectLoaded($this->userConfig) ? (int) $this->userConfig->id : 0) . ");"
                        )
            ));
            $content .= '</div>';
        }

        // Pagination: 
        if ($this->params['pagination']) {
            $content .= '<div class="title">';
            $content .= 'Nombre d\'items par page';
            $content .= '</div>';

            $content .= '<div style="margin-bottom: 15px">';
            $content .= BimpInput::renderSwitchOptionsInput('select_n', array(
                        10 => '10', 20 => '20', 30 => '30', 40 => '40', 50 => '50'), $this->params['n'], $this->identifier . '_n');
            $content .= '</div>';
        }

        // Config utilisateur: 
        global $user;
        if (BimpCore::isModuleActive('bimpuserconfig') && BimpObject::objectLoaded($user) && (int) $this->params['configurable']) {
            $content .= '<div class="title">';
            $content .= 'Paramètres utilisateur';
            $content .= '</div>';

            $values = array(
                'owner_type' => 2,
                'id_owner'   => $user->id,
                'list_name'  => $this->name,
            );

            BimpObject::loadClass('bimpuserconfig', 'ListTableConfig');
            $configs = ListTableConfig::getUserConfigsArray($user->id, $this->object, $this->name, false);

            if (BimpObject::objectLoaded($this->userConfig)) {
                $values['sort_field'] = $this->userConfig->getData('sort_field');
                $values['sort_way'] = $this->userConfig->getData('sort_way');
                $values['sort_option'] = $this->userConfig->getData('sort_option');
                $values['nb_items'] = $this->userConfig->getData('nb_items');
                $values['total_row'] = $this->userConfig->getData('total_row');

                $content .= '<div style="font-weight: normal; font-size: 11px">';

                $content .= 'Configuration actuelle:<br/>';
                $content .= '<div style="margin: 5px 0; font-weight: bold">';
                $content .= $this->userConfig->getData('name');

                if ($this->userConfig->can('edit')) {
                    $content .= '<div style="margin-top: 5px; text-align: center">';
                    $content .= '<button class="btn btn-default btn-small" onclick="' . $this->userConfig->getJsLoadModalForm('default', 'Edition de la configuration #' . $this->userConfig->id) . '" style="margin-right: 4px">';
                    $content .= BimpRender::renderIcon('fas_edit', 'iconLeft') . 'Editer';
                    $content .= '</button>';

                    $content .= '<button class="btn btn-default btn-small" onclick="' . $this->userConfig->getJsLoadModalColsConfig() . '">';
                    $content .= BimpRender::renderIcon('fas_columns', 'iconLeft') . 'Colonnes';
                    $content .= '</button>';
                    $content .= '</div>';
                }

                $content .= '</div>';

                $content .= 'Nombre d\'éléments par page: <span class="bold">' . ((int) $values['nb_items'] ? $values['nb_items'] : BimpRender::renderIcon('fas_infinity')) . '</span><br/>';

                $sortable_fields = $this->object->getSortableFieldsArray();

                if (array_key_exists($values['sort_field'], $sortable_fields)) {
                    $content .= 'Tri par défaut: <span class="bold">' . $sortable_fields[$values['sort_field']] . '</span><br/>';
                    if ((string) $values['sort_option']) {
                        $sort_options = $this->object->getSortOptionsArray($values['sort_field']);
                        if (array_key_exists($values['sort_option'], $sort_options)) {
                            $content .= 'Option de tri: <span class="bold">' . $sort_options[$values['sort_option']] . '</span><br/>';
                        }
                    }
                }
                $content .= 'Ordre de tri: <span class="bold">' . $this->userConfig->displayData('sort_way') . '</span><br/>';
                $content .= 'Afficher les totaux: <span class="bold">' . ((int) $values['total_row'] ? 'OUI' : 'NON') . '</span>';
                $content .= '</div>';
            } else {
                $userConfig = BimpObject::getInstance('bimpuserconfig', 'ListTableConfig');
                
                $onclick = $userConfig->getJsLoadModalForm('default', 'Nouvelle configuration de liste', array(
                    'fields' => array(
                        'name'           => '',
                        'obj_module'     => $this->object->module,
                        'obj_name'       => $this->object->object_name,
                        'component_name' => $this->name,
                        'owner_type'     => UserConfig::OWNER_TYPE_USER,
                        'id_owner'       => (int) $user->id,
                        'nb_items'       => $this->params['n'],
                        'sort_field'     => $this->params['sort_field'],
                        'sort_way'       => $this->params['sort_way'],
                        'sort_option'    => $this->params['sort_option'],
                        'total_row'      => $this->params['total_row'],
                        'is_default'     => 1
                    )
                ));

                $content .= '<div style="margin: 5px 0 15px 0; text-align: center">';
                $content .= '<button class="btn btn-default btn-small" onclick="' . $onclick . '" style="margin-right: 4px">';
                $content .= BimpRender::renderIcon('fas_edit', 'iconLeft') . 'Editer';
                $content .= '</button>';

                $onclick = $userConfig->getJsActionOnclick('createUserDefault', array(
                    'load_cols_config' => 1,
                    'list_identifier'  => $this->identifier,
                    'obj_module'       => $this->object->module,
                    'obj_name'         => $this->object->object_name,
                    'component_name'   => $this->name,
                    'nb_items'         => $this->params['n'],
                    'sort_field'       => $this->params['sort_field'],
                    'sort_way'         => $this->params['sort_way'],
                    'sort_option'      => $this->params['sort_option'],
                    'total_row'        => $this->params['total_row'],
                    'is_default'       => 1
                ));
                $content .= '<button class="btn btn-default btn-small" onclick="' . $onclick . '">';
                $content .= BimpRender::renderIcon('fas_columns', 'iconLeft') . 'Colonnes';
                $content .= '</button>';
                $content .= '</div>';
            }

            if (count($configs) > 0) {
                $content .= '<div style="margin-bottom: 15px; text-align: center">';

                if (count($configs) > 1) {
                    $items = array();

                    foreach ($configs as $id_config => $config_label) {
                        if ((int) $id_config === (int) $this->userConfig->id) {
                            continue;
                        }
                        $items[] = '<span class="btn btn-light-default" onclick="loadListConfig($(this), ' . $id_config . ');">' . $config_label . '</span>';
                    }

                    $content .= BimpRender::renderDropDownButton('Charger une configuration', $items, array(
                                'icon'       => 'fas_user-cog',
                                'menu_right' => 1
                            )) . '<br/>';
                }

                $userConfig = BimpObject::getInstance('bimpuserconfig', 'ListTableConfig');
                $onclick = 'loadBCUserConfigsModalList($(this), ' . $user->id . ', \'' . $this->identifier . '\', \'ListTableConfig\', \'Configurations\')';

                $content .= BimpRender::renderButton(array(
                            'classes'     => array('btn', 'btn-default'),
                            'label'       => 'Gérer les configurations',
                            'icon_before' => 'fas_pen',
                            'attr'        => array(
                                'onclick' => $onclick
                            )
                ));
                $content .= '</div>';
            }
        }

        // Génération fichier CSV:
        $tools_html = '';
        if ($this->params['enable_csv']) {
            $tools_html .= '<div style="text-align: center;">';
            $tools_html .= BimpRender::renderButton(array(
                        'classes'     => array('btn', 'btn-default'),
                        'label'       => 'Générer fichier CSV',
                        'icon_before' => 'fas_file-excel',
                        'attr'        => array(
                            'onclick' => $this->object->getJsActionOnclick('generateListCsv', array(
                                'list_id'   => $this->identifier,
                                'list_name' => $this->name,
                                'list_type' => static::$type,
                                'file_name' => BimpTools::cleanStringForUrl($this->object->getLabel() . '_' . date('d-m-Y')),
                                    ), array(
                                'form_name'      => 'list_csv',
                                'on_form_submit' => 'function($form, extra_data) {return onGenerateCsvFormSubmit($form, extra_data);}'
                            ))
                        )
            ));
            $tools_html .= '</div>';
        }

        if ($this->object->params['has_graph']) {
            $tools_html .= '<div style="text-align: center;">';
            $tools_html .= BimpRender::renderButton(array(
                        'classes'     => array('btn', 'btn-default'),
                        'label'       => 'Actualiser le graphique',
                        'icon_before' => 'fas_chart-pie',
                        'attr'        => array(
                            'onclick' => "updateGraph('" . $this->identifier . "', '" . $this->name . "');"
                        )
            ));
            $tools_html .= '</div>';
        }

        if ($tools_html) {
            $content .= '<div class="title">';
            $content .= 'Outils';
            $content .= '</div>';
            $content .= $tools_html;
        }

        if ($content) {
            $html .= '<div id="' . $this->identifier . '_parametersPopup" class="tinyPopup listPopup">';
            $html .= $content;
            $html .= '</div>';
        }

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderRows()
    {
        $html = '';

        $this->setConfPath();

        if (!$this->isOk() || !count($this->cols)) {
            return '';
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        if (is_null($this->rows)) {
            $this->fetchRows();
        }

        if (!empty($this->rows)) {
            foreach ($this->rows as $id_object => $row) {
                $id_object = (int) $id_object;
                $item_params = $row['params']['item_params'];

                $selected = in_array((int) $id_object, $this->selected_rows);
                $modified = (isset($this->new_values[$id_object]) && !empty($this->new_values[$id_object]));

                $html .= '<tr class="' . $this->object->object_name . '_row objectListItemRow' . ($modified ? ' modified' : '') . ($selected ? ' selected' : '');
                $html .= '" id="' . $this->object->object_name . '_row_' . $id_object . '"';
                $html .= ' data-id_object="' . $id_object . '"';
                if ($this->params['positions']) {
                    $html .= ' data-position="' . $row['params']['position'] . '"';
                }
                if (!is_null($item_params['row_style']) && $item_params['row_style']) {
                    $html .= ' style="' . $item_params['row_style'] . '"';
                }
                $html .= '>';

                $html .= '<td style="text-align: center; ' . $item_params['td_style'] . '">';
                if ($this->params['checkboxes']) {
                    if ((int) $item_params['item_checkbox']) {
                        $html .= '<input type="checkbox" id_="' . $this->object->object_name . '_check_' . $id_object . '"';
                        $html .= ' name="' . $this->object->object_name . '_check"';
                        $html .= ' class="item_check"';
                        if ($selected) {
                            $html .= ' checked="1"';
                        }
                        $html .= ' data-id_object="' . $id_object . '"/>';
                    }
                }
                $html .= '</td>';

                if ((int) $this->params['total_row']) {
                    $html .= '<td style="width: 45px; min-width: 45px; ' . $item_params['td_style'] . '"></td>';
                }

                if ($this->params['positions']) {
                    $html .= '<td class="positionHandle" style="' . (!$this->params['position'] ? 'display: none;' : '') . $item_params['td_style'] . '"><span></span></td>';
                }

                foreach ($this->cols as $col_name => $col_params) {
                    if ($row['params']['single_cell'] && $col_name !== $this->params['single_cell']['col']) {
                        continue;
                    }

                    $html .= '<td style="';
                    if (BimpTools::getArrayValueFromPath($col_params, 'hidden', false)) {
                        $html .= 'display: none;';
                    }

                    if (isset($col_params['min_width']) && $col_params['min_width']) {
                        $html .= 'min-width: ' . $col_params['min_width'] . 'px;';
                    }

                    if ($item_params['td_style']) {
                        $html .= $item_params['td_style'] . ';';
                    }

                    if (isset($col_params['col_style']) && (string) $col_params['col_style']) {
                        $html .= $col_params['col_style'] . ';';
                    }

                    if (isset($col_params['align']) && in_array($col_params['align'], array('center', 'right'))) { // pas left puisque par défaut. 
                        $html .= 'text-align: ' . $col_params['align'] . ';';
                    }

                    $html .= '"' . ($row['params']['single_cell'] ? ' colspan="' . count($this->cols) . '"' : '') . '>';
                    $html .= (isset($row['cols'][$col_name]['content']) ? $row['cols'][$col_name]['content'] : '');
                    $html .= '</td>';
                }

                $rowButtons = array();

                if ((int) $row['params']['canEdit']) {
                    if ($this->params['enable_edit']) {
                        $rowButtons[] = array(
                            'class'   => 'cancelModificationsButton hidden',
                            'icon'    => 'fas_undo',
                            'label'   => 'Annuler les modifications',
                            'onclick' => 'cancelObjectRowModifications(\'' . $this->identifier . '\', ' . $id_object . ', $(this))'
                        );
                        $rowButtons[] = array(
                            'class'   => 'updateButton hidden',
                            'label'   => 'Enregistrer',
                            'onclick' => 'updateObjectFromRow(\'' . $this->identifier . '\', ' . $id_object . ', $(this))'
                        );
                    }
                }

                if (is_array($item_params['extra_btn']) && count($item_params['extra_btn'])) {
                    foreach ($item_params['extra_btn'] as $btn_params) {
                        $rowButtons[] = $btn_params;
                    }
                }

                if ((int) $row['params']['canEdit']) {
                    if ((int) $item_params['edit_btn']) {
                        $title = '';
                        if (!is_null($item_params['edit_form_title']) && $item_params['edit_form_title']) {
                            $title = htmlentities(addslashes($item_params['edit_form_title']));
                        }
                        $onclick = 'loadModalFormFromList(';
                        $onclick .= '\'' . $this->identifier . '\', \'' . $item_params['edit_form'] . '\'';
                        $onclick .= ', $(this), ' . $id_object . ', ' . (!is_null($this->id_parent) ? $this->id_parent : 0) . ', \'' . $title . '\')';
                        $rowButtons[] = array(
                            'class'   => 'editButton',
                            'label'   => 'Editer',
                            'onclick' => $onclick
                        );
                    }
                }

                if ((int) $row['params']['canView']) {
                    if (!is_null($item_params['modal_view'])) {
                        $title = '';
                        if ($this->object->config->isDefined('views/' . $item_params['modal_view'] . '/title')) {
                            $title = htmlentities(addslashes($this->object->getConf('views/' . $item_params['modal_view'] . '/title')));
                        } else {
                            $title = htmlentities(addslashes($row['params']['instance_name']));
                        }
                        $onclick = 'loadModalView(\'' . $this->object->module . '\', \'' . $this->object->object_name . '\', ' . $id_object . ', \'' . $item_params['modal_view'] . '\', $(this), \'' . $title . '\')';
                        $rowButtons[] = array(
                            'label'   => 'Vue rapide',
                            'icon'    => 'far_eye',
                            'onclick' => $onclick
                        );
                    }
                    if (!is_null($item_params['inline_view']) && $item_params['inline_view']) {
                        $onclick = 'displayObjectView($(\'#' . $this->identifier . '_container\').find(\'.objectViewContainer\'), ';
                        $onclick .= '\'' . $this->object->module . '\', \'' . $this->object->object_name . '\', \'' . $item_params['inline_view'] . '\', ' . $id_object . ', \'default\'';
                        $onclick .= ');';
                        $rowButtons[] = array(
                            'label'   => 'Afficher',
                            'icon'    => 'far_eye',
                            'onclick' => $onclick
                        );
                    }
                    if ((int) $item_params['page_btn'] && $row['params']['url']) {
                        $rowButtons[] = array(
                            'label'   => $row['params']['page_btn_label'],
                            'onclick' => 'window.location = \'' . $row['params']['url'] . '\';',
                            'icon'    => 'far_file'
                        );
                        $rowButtons[] = array(
                            'label'   => $row['params']['page_btn_label'] . ' dans un nouvel onglet',
                            'onclick' => 'window.open(\'' . $row['params']['url'] . '\');',
                            'icon'    => 'fas_external-link-alt'
                        );
                    }
                }

                if ((int) $row['params']['canDelete']) {
                    if ($item_params['delete_btn']) {
                        $rowButtons[] = array(
                            'class'   => 'deleteButton',
                            'label'   => 'Supprimer',
                            'onclick' => 'deleteObjects(\'' . $this->identifier . '\', [' . $id_object . '], $(this))'
                        );
                    }
                }


                $min_width = ((count($rowButtons) * 36) + 12) . 'px';
                $html .= '<td class="buttons" style="min-width: ' . $min_width . '; ' . $item_params['td_style'] . '">';

                $i = 1;
                foreach ($rowButtons as $btn_params) {
//                    echo $i . '(' . count($rowButtons) . '): ' . $btn_params['label'] . ': ' . strlen($btn_params['label']) . '<br/>';
                    $position = ($i === count($rowButtons) || ($i === (count($rowButtons) - 1) && strlen($btn_params['label']) > 18) || strlen($btn_params['label']) > 28 ? 'left' : 'top');
                    $html .= $this->renderRowButton($btn_params, $position);
                    $i++;
                }

                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= $this->renderTotalRow();
        } else {
            $label = $this->object->getLabel('name');
            $isFemale = $this->object->isLabelFemale();
            $html .= '<tr>';
            $html .= '<td  colspan="' . $this->colspan . '" style="text-align: center" class="fullrow">';
            if (count($this->filters)) {
                $html .= '<p class="alert alert-warning">';
                $html .= 'Aucun' . ($isFemale ? 'e' : '') . ' ' . $label;
                $html .= ' trouvé' . ($isFemale ? 'e' : '') . ' avec ces critères de recherche';
                $html .= '</p>';
            } else {
                $html .= '<p class="alert alert-info">';
                $html .= 'Aucun' . ($isFemale ? 'e' : '') . ' ' . $label;
                $html .= ' enregistré' . ($isFemale ? 'e' : '') . ' pour le moment';
                $html .= '</p>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderCsvContent($separator, $col_options, $headers = true, &$errors = array())
    {
        set_time_limit(0);
        ini_set('max_execution_time', 12000);
        ini_set('memory_limit', '4096M');

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $this->params['n'] = 0;
        $this->params['p'] = 1;

        if (is_null($this->items)) {
            $this->fetchItems();
        }

        $this->setConfPath();

        $object_instance = $this->object;
        $primary = $this->object->getPrimary();

        $rows = '';

        if ($headers) {
            $line = '';
            $fl = true;
            foreach ($this->cols as $col_name => $col_params) {
                if (!(int) $col_params['show'] || (int) $col_params['hidden'] || !(int) $col_params['available_csv']) {
                    continue;
                }

                $label = $col_params['label'];
                if (!$label && $col_params['field']) {
                    $field_name = '';
                    $field_object = self::getColFieldObject($this->object, $col_name, $field_name);

                    if (is_a($field_object, 'BimpObject') && $field_object->field_exists($field_name)) {
                        $label = $field_object->getConf('fields/' . $field_name . '/label', '');
                    }
                }
                if (!$label) {
                    $label = $col_name;
                }

                $label = BimpTools::replaceBr($label, ' ');
                $label = strip_tags($label);
                $label = html_entity_decode($label);
                $label = str_replace($separator, ' ', $label);

                $line .= (!$fl ? $separator : '') . '"' . $label . '"';
                $fl = false;
            }
            $rows .= $line . "\n";
        }

        if (is_null($this->items) || !count($this->items)) {
            $current_bc = $prev_bc;
            return $rows;
        }

        global $modeCSV;
        $modeCSV = true;

        $nb = 0;
        foreach ($this->items as $item) {
            $nb++;

            $line = '';
            $object = BimpCache::getBimpObjectInstance($this->object->module, $this->object->object_name, (int) $item[$primary], $this->parent);
            if (BimpObject::objectLoaded($object)) {
                $this->object = $object;

                $fl = true;
                foreach ($this->cols as $col_name => $col_params) {
                    if (!(int) $col_params['show'] || (int) $col_params['hidden'] || !(int) $col_params['available_csv']) {
                        continue;
                    }

                    $content = '';

                    $field_name = '';
                    $field_object = self::getColFieldObject($this->object, $col_name, $field_name);

                    $item_params = $this->getItemColsParams($object, $col_name, $field_object, $field_name);

                    if (is_a($field_object, 'BimpObject') && BimpObject::objectLoaded($field_object) && $field_name && $field_object->field_exists($field_name)) {
                        $field = new BC_Field($field_object, $field_name);
                        $content = $field->getNoHtmlValue(isset($col_options[$col_name]) ? $col_options[$col_name] : '');
                    } elseif (isset($item_params['true_value']) && !is_null($item_params['true_value'])) {
                        $content = $item_params['true_value'];
                    } elseif (isset($item_params['value'])) {
                        $content = $item_params['value'];
                    }

                    $content = BimpTools::replaceBr($content);
                    $content = strip_tags($content);
                    $content = html_entity_decode($content);
                    $content = str_replace($separator, '', $content);
                    $content = str_replace('"', '""', $content);

                    $line .= (!$fl ? $separator : '' ) . '"' . $content . '"';

                    $fl = false;
                }

                $rows .= $line . "\n";
            }
        }

        $this->object = $object_instance;

        if (!is_null($this->parent)) {
            $this->object->parent = $this->parent;
        }

        $this->setConfPath();
        $current_bc = $prev_bc;
        return $rows;
    }
}
