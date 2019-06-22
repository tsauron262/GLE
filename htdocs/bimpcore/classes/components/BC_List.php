<?php

class BC_List extends BC_Panel
{

    public $component_name = 'Liste';
    public static $type = 'list';
    protected $item_params = array();
    public $id_parent;
    public $parent = null;
    protected $filters = array();
    protected $bc_filtersPanel = null;
    protected $new_values = array();
    protected $items = null;
    public $nbTotalPages = 1;
    protected $nbItems = null;
    public $userConfig = null;
    public $default_modal_format = 'large';

    public function __construct(BimpObject $object, $path, $list_name = 'default', $level = 1, $id_parent = null, $title = null, $icon = null)
    {
        $this->params_def['configurable'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['pagination'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['n'] = array('data_type' => 'int', 'default' => 10, 'request' => true);
        $this->params_def['p'] = array('data_type' => 'int', 'default' => 1, 'request' => true);

        $this->params_def['sort_field'] = array('data_type' => 'string', 'default' => 'id', 'request' => true);
        $this->params_def['sort_way'] = array('data_type' => 'string', 'default' => 'desc', 'request' => true);
        $this->params_def['sort_option'] = array('data_type' => 'string', 'default' => 'default', 'request' => true);

        $this->params_def['list_filters'] = array('data_type' => 'array', 'default' => array(), 'request' => true, 'json' => true);
        $this->params_def['association_filters'] = array('data_type' => 'array', 'default' => array(), 'request' => true, 'json' => true);
        $this->params_def['joins'] = array('type' => 'definitions', 'defs_type' => 'join', 'multiple' => true, 'request' => true, 'json' => true);

        $this->params_def['add_btn'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['add_form_name'] = array();
        $this->params_def['add_form_values'] = array('data_type' => 'array', 'default' => array());
        $this->params_def['add_form_on_save'] = array('default' => '');
        $this->params_def['add_form_title'] = array();
        $this->params_def['add_btn_label'] = array('default' => '');

        $this->params_def['filters_panel'] = array();
        $this->params_def['filters_panel_values'] = array('data_type' => 'array', 'compile' => true, 'default' => array());
        $this->params_def['filters_panel_open'] = array('data_type' => 'bool', 'default' => 0);

        if (is_null($id_parent)) {
            $parent_id_property = $object->getParentIdProperty();
            if (!is_null($parent_id_property)) {
                if (BimpTools::isSubmit($parent_id_property)) {
                    $this->id_parent = BimpTools::getValue($parent_id_property, null);
                }
            }
        }

        if (!is_null($id_parent) && !is_null($object)) {
            $object->setIdParent($id_parent);
            $parent_module = $object->getParentModule();
            $parent_object_name = $object->getParentObjectName();
            if ($parent_module && $parent_object_name) {
                $this->parent = BimpCache::getBimpObjectInstance($parent_module, $parent_object_name, (int) $id_parent);
            }
        }

        $this->id_parent = $id_parent;

        parent::__construct($object, $list_name, $path, false, $level, $title, $icon);

        if ($this->isOk()) {
            if (is_null($this->params['title']) || !$this->params['title']) {
                $this->params['title'] = 'Listes des ' . $this->object->getLabel('name_plur');
            }
            if (is_null($this->params['icon']) || !$this->params['icon']) {
                $this->params['icon'] = 'fas_list';
            }

            if (!$this->object->can("create")) {
                $this->params['add_btn'] = 0;
                $this->params['add_form_name'] = null;
            }

            if ($this->params['configurable']) {
                global $user;
                $this->userConfig = $this->object->getListConfig(2, $user->id, $this->name);
                if (BimpObject::objectLoaded($this->userConfig)) {
                    if (!BimpTools::isSubmit('param_sort_field')) {
                        $sort_field = $this->userConfig->getData('sort_field');
                        if (!is_null($sort_field)) {
                            $this->params['sort_field'] = $sort_field;
                        }
                    }
                    if (!BimpTools::isSubmit('param_sort_way')) {
                        $sort_way = $this->userConfig->getData('sort_way');
                        if (!is_null($sort_way)) {
                            $this->params['sort_way'] = $sort_way;
                        }
                    }
                    if (!BimpTools::isSubmit('param_sort_option')) {
                        $sort_option = $this->userConfig->getData('sort_option');
                        if (!is_null($sort_option)) {
                            $this->params['sort_option'] = $sort_option;
                        }
                    }
                    if (!BimpTools::isSubmit('param_n')) {
                        $n = $this->userConfig->getData('nb_items');
                        if (!is_null($n)) {
                            $this->params['n'] = $n;
                        }
                    }
                }
            }
        }

        $this->setConfPath();

        $this->filters = $this->object->getSearchFilters($this->params['joins']);
    }

    // Gestion des filtres: 

    protected function mergeFilter($name, $filter)
    {
        if (isset($this->filters[$name])) {
            if (isset($this->filters[$name]['and'])) {
                $this->filters[$name]['and'][] = $filter;
            } else {
                $current_filter = $this->filters[$name];
                $this->filters[$name] = array('and' => array());
                $this->filters[$name]['and'][] = $current_filter;
                $this->filters[$name]['and'][] = $filter;
            }
        } else {
            $this->filters[$name] = $filter;
        }
    }

    public function addFieldFilterValue($field_name, $value)
    {
        $this->params['list_filters'][] = array(
            'name'   => $field_name,
            'filter' => $value
        );

        if ($this->object->field_exists($field_name)) {
            $this->params['add_form_values']['fields'][$field_name] = $value;
        }
    }

    public function addJoin($table, $on, $alias)
    {
        if (!isset($this->params['joins'][$alias])) {
            $this->params['joins'][$alias] = array(
                'table' => $table,
                'on'    => $on,
                'alias' => $alias
            );
        }
    }

    // Gestion des filtres associatifs:

    public function addObjectAssociationFilter($object, $id_object, $association, $type = 'in')
    {
        $bimpAsso = new BimpAssociation($object, $association);
        if (!count($bimpAsso->errors)) {
            if (is_a($bimpAsso->associate, 'BimpObject') &&
                    ($bimpAsso->associate->module === $this->object->module) &&
                    $bimpAsso->associate->object_name === $this->object->object_name) {
                $this->params['association_filters'][] = array(
                    'type'          => $type,
                    'object_module' => $object->module,
                    'object_name'   => $object->object_name,
                    'id_object'     => $id_object,
                    'association'   => $association
                );
            } else {
                $this->addError('Filtre invalide pour l\'association "' . $association . '"');
            }
        } else {
            $this->errors = array_merge($this->errors, $bimpAsso->errors);
        }
    }

    public function addAssociateAssociationFilter($association, $id_associate, $type = 'in')
    {
        $bimpAsso = new BimpAssociation($this->object, $association);

        if (!count($bimpAsso->errors)) {
            $this->params['association_filters'][] = array(
                'type'         => $type,
                'association'  => $association,
                'id_associate' => (int) $id_associate
            );
        } else {
            $this->errors = array_merge($this->errors, $bimpAsso->errors);
        }
    }

    public function addBulkAssociation($association, $id_associate, $label = null)
    {
        if ($this->object->config->isDefined('associations/' . $association)) {
            $associate = $this->object->config->getObject('associations/' . $association . '/object');
            if (!is_null($associate)) {
                if ($associate->fetch($id_associate) > 0) {
                    $this->identifier .= '_associate_to_' . $association;
                    $this->params['title'] = BimpTools::ucfirst($this->object->getLabel('name_plur')) . ' associables ';
                    $this->params['title'] .= BimpObject::getInstanceLabel($associate, 'to') . ' "' . BimpObject::getInstanceNom($associate) . '"';
                    $this->params['association_filters'][] = array(
                        'type'         => 'not_in',
                        'association'  => $association,
                        'id_associate' => $id_associate
                    );
                    if (!is_null($this->params['add_form'])) {
                        if (is_null($this->params['add_form_values'])) {
                            $this->params['add_form_values'] = array();
                        }
                        if (!isset($this->params['add_form_values']['associations'])) {
                            $this->params['add_form_values']['associations'] = array();
                        }
                        $this->params['add_form_values']['associations'][$association] = array($id_associate);
                    }
                    if (is_null($label)) {
                        $label = 'Associer ' . BimpObject::getInstanceLabel($associate, 'to') . ' ' . $id_associate;
                    }
                    $this->bulk_actions[] = array(
                        'label'   => $label,
                        'onclick' => 'toggleSelectedItemsAssociation(\'' . $this->identifier . '\', \'add\', \'' . $association . '\', ' . $id_associate . ')',
                        'icon'    => 'link'
                    );
                }
            }
        }
    }

    public function addBulkDeassociation($association, $id_associate, $label = null)
    {
        if ($this->object->config->isDefined('associations/' . $association)) {
            $associate = $this->object->config->getObject('associations/' . $association . '/object');
            if (!is_null($associate)) {
                if ($associate->fetch($id_associate) > 0) {
                    if (is_null($label)) {
                        $label = 'Désassocier les ' . $this->object->getLabel('name_plur') . ' sélectionnés';
                    }
                    $this->identifier .= '_associated_to_' . $association;
                    $this->params['add_form'] = null;
                    $this->params['title'] = BimpTools::ucfirst($this->object->getLabel('name_plur')) . ' associés ';
                    $this->params['title'] .= BimpObject::getInstanceLabel($associate, 'to') . ' "' . BimpObject::getInstanceNom($associate) . '"';
                    $this->bulk_actions[] = array(
                        'label'   => $label,
                        'onclick' => 'toggleSelectedItemsAssociation(\'' . $this->identifier . '\', \'delete\', \'' . $association . '\', ' . $id_associate . ')',
                        'icon'    => 'unlink'
                    );
                    $this->params['association_filters'][] = array(
                        'type'         => 'in',
                        'association'  => $association,
                        'id_associate' => $id_associate
                    );
                }
            }
        }
    }

    public function setAddFormName($name)
    {
        if (is_null($this->params['add_form'])) {
            $this->params['add_form'] = array();
        }

        $this->params['add_form'] = $name;
    }

    public function setAddFormValues($values)
    {
        if (!isset($this->params['add_form_values'])) {
            $this->params['add_form_values'] = array();
        }

        if (isset($values['fields'])) {
            foreach ($values['fields'] as $field_name => $value) {
                $this->params['add_form_values']['fields'][$field_name] = $value;
            }
        }

        if (isset($values['associations'])) {
            foreach ($values['associations'] as $asso_name => $value) {
                $this->params['add_form_values']['associations'][$asso_name] = $value;
            }
        }

        if (isset($values['objects'])) {
            foreach ($values['objects'] as $object_name => $value) {
                $this->params['add_form_values']['objects'][$object_name] = $value;
            }
        }
    }

    public function setNewValues($new_values)
    {
        foreach ($new_values as $id_object => $fields) {
            $this->new_values[(int) $id_object] = $fields;
        }
    }

    public function fetchFiltersPanelValues()
    {
        if (is_null($this->bc_filtersPanel)) {
            if (is_null($this->params['filters_panel'])) {
                return;
            }
            $this->bc_filtersPanel = new BC_FiltersPanel($this->object, static::$type, $this->name, $this->identifier, $this->params['filters_panel']);

            if (BimpTools::isSubmit('filters_panel_values')) {
                $values = array(
                    'fields'   => array(),
                    'children' => array()
                );

                foreach (BimpTools::getValue('filters_panel_values/fields', array()) as $field_name => $filter) {
                    foreach ($this->bc_filtersPanel->params['filters'] as $key => $params) {
                        if (isset($params['field']) && $params['field'] === $field_name && !$params['child']) {
                            if (isset($filter['open'])) {
                                $this->bc_filtersPanel->params['filters'][$key]['open'] = (int) $filter['open'];
                            }
                            if (isset($filter['values'])) {
                                $values['fields'][$field_name] = $filter['values'];
                            }
                            continue 2;
                        }
                    }
                }

                foreach (BimpTools::getValue('filters_panel_values/children', array()) as $child => $fields) {
                    if (!isset($values['children'][$child])) {
                        $values['children'][$child] = array();
                    }
                    foreach ($fields as $field_name => $filter) {
                        foreach ($this->bc_filtersPanel->params['filters'] as $key => $params) {
                            if (isset($params['field']) && $params['field'] === $field_name && $params['child'] === $child) {
                                if (isset($filter['open'])) {
                                    $this->bc_filtersPanel->params['filters'][$key]['open'] = (int) $filter['open'];
                                }
                                if (isset($filter['values'])) {
                                    $values['children'][$child][$field_name] = $filter['values'];
                                }
                                continue 2;
                            }
                        }
                    }
                }

                $this->bc_filtersPanel->setFiltersValues($values);
            } elseif (!empty($this->params['filters_panel_values'])) {
                $this->bc_filtersPanel->setFiltersValues($this->params['filters_panel_values']);
            }
        }
    }

    protected function fetchItems()
    {
        $this->fetchFiltersPanelValues();

        if (!$this->isOk()) {
            $this->setConfPath();
            $this->items = array();
            return;
        }

        $primary = $this->object->getPrimary();

        // Jointures: 
        $joins = $this->params['joins'];

        // Filtres: 
        if (count($this->params['list_filters'])) {
            foreach ($this->params['list_filters'] as $list_filter) {
                $this->mergeFilter($list_filter['name'], $list_filter['filter']);
            }
        }

        if (!is_null($this->bc_filtersPanel)) {
            $panelFilters = array();
            $this->bc_filtersPanel->getSqlFilters($panelFilters, $joins);
            foreach ($panelFilters as $name => $filter) {
                $this->mergeFilter($name, $filter);
            }
        }

        // Filtres selon objets associés:
        if (count($this->params['association_filters'])) {
            foreach ($this->params['association_filters'] as $asso_filter) {
                $object = null;
                $id_associate = null;
                $id_object = null;
                $return_field = '';

                if (isset($asso_filter['object_module']) && isset($asso_filter['object_name'])) {

                    if (isset($asso_filter['id_object'])) {
                        $id_object = (int) $asso_filter['id_object'];
                        $object = BimpCache::getBimpObjectInstance($asso_filter['object_module'], $asso_filter['object_name'], $id_object);
                    } else {
                        $object = BimpObject::getInstance($asso_filter['object_module'], $asso_filter['object_name']);
                    }
                    $return_field = 'dest_id_object';
                } elseif (isset($asso_filter['id_associate'])) {
                    $object = $this->object;
                    $id_associate = (int) $asso_filter['id_associate'];
                    $return_field = 'src_id_object';
                }

                if (!is_null($object)) {
                    $bimp_asso = new BimpAssociation($object, $asso_filter['association']);
                    if (!count($bimp_asso->errors)) {
                        $alias = 'asso_' . $asso_filter['association'];
                        $sql = BimpTools::getSqlSelect(array($return_field), $alias);
                        $sql .= BimpTools::getSqlFrom(BimpAssociation::$table, null, $alias);
                        $sql .= BimpTools::getSqlWhere($bimp_asso->getSqlFilters($id_object, $id_associate, $alias));
                        $this->mergeFilter($this->object->getPrimary(), array($asso_filter['type'] => $sql));
                    } else {
                        $this->errors[] = array_merge($this->errors, $bimp_asso->errors);
                        $this->filters[$this->object->getPrimary()] = 0;
                    }
                }
            }
        }

        $filters = $this->filters;
        if (!is_null($this->id_parent)) {
            $parent_id_property = $this->object->getParentIdProperty();
            if ($parent_id_property) {
                $filters[$parent_id_property] = $this->id_parent;
            }
        }

        // Trie: 
        $order_by = $primary;
        $order_by_next_field = null;
        $extra_order_by = null;
        $extra_order_way = null;

        if (!is_null($this->params['sort_field'])) {
            if ($this->params['sort_field'] === 'position') {
                $order_by = 'position';
            } else {
                $order_by = '';
                if (!is_null($this->params['sort_option']) && $this->params['sort_option']) {
                    $sort_option_path = 'fields/' . $this->params['sort_field'] . '/sort_options/' . $this->params['sort_option'];
                    if ($this->object->config->isDefined($sort_option_path)) {
                        $join_field = $this->object->getConf($sort_option_path . '/join_field', '');
                        if ($join_field && $this->object->config->isDefined('fields/' . $this->params['sort_field'] . '/object')) {
                            $object = $this->object->config->getObject('fields/' . $this->params['sort_field'] . '/object');
                            if (!is_null($object)) {
                                $table = BimpTools::getObjectTable($this->object, $this->params['sort_field'], $object);
                                $field_on = BimpTools::getObjectPrimary($this->object, $this->params['sort_field'], $object);
                                if (!is_null($table) && !is_null($field_on)) {
                                    $order_by = $table . '.' . $join_field;
                                    $joins[] = array(
                                        'alias' => $table,
                                        'table' => $table,
                                        'on'    => $table . '.' . $field_on . ' = a.' . $this->params['sort_field']
                                    );
                                }
                            }
                        }
                    } elseif ($this->object->getConf('fields/' . $this->params['sort_field'] . '/type', 'string') === 'id_object') {
                        $sort_obj = $this->object->config->getObject('fields/' . $this->params['sort_field'] . '/object');
                        if (!is_null($sort_obj) && is_a($sort_obj, 'BimpObject')) {
                            if (in_array($this->params['sort_option'], $sort_obj->params['fields'])) {
                                if ((int) $sort_obj->getConf('fields/' . $this->params['sort_option'] . '/sortable', 1, false, 'bool')) {
                                    $table = $sort_obj->getTable();
                                    $field_on = $sort_obj->getPrimary();
                                    if (!is_null($table) && !is_null($field_on)) {
                                        $order_by = $table . '.' . $this->params['sort_option'];
                                        $joins[] = array(
                                            'alias' => $table,
                                            'table' => $table,
                                            'on'    => $table . '.' . $field_on . ' = a.' . $this->params['sort_field']
                                        );
                                    }
                                }
                            }
                        }
                    }
                }

                if (!$order_by) {
                    $order_by = $this->params['sort_field'];
                }
                $extra_order_by = $this->object->getConf('fields/' . $this->params['sort_field'] . '/next_sort_field');
                $extra_order_way = $this->object->getConf('fields/' . $this->params['sort_field'] . '/next_sort_way');
            }

            $this->setConfPath();
        }

        $this->nbItems = $this->object->getListCount($filters, $joins);

        if ($this->params['n'] > 0) {
            $this->nbTotalPages = (int) ceil($this->nbItems / $this->params['n']);
            if ($this->params['p'] > $this->nbTotalPages) {
                $this->params['p'] = $this->nbTotalPages;
            }
        } else {
            $this->nbTotalPages = 1;
            $this->params['p'] = 1;
        }

        $this->items = $this->object->getList($filters, $this->params['n'], $this->params['p'], $order_by, $this->params['sort_way'], 'array', array(
            'DISTINCT (a.' . $primary . ')'
                ), $joins, $extra_order_by, $extra_order_way);

        if (method_exists($this->object, 'listItemsOverride')) {
            $this->object->listItemsOverride($this->name, $this->items);
        }
    }

    public function setItems($items = array())
    {
        $this->items = array();

        if ($this->isOk()) {
            $primary = $this->object->getPrimary();
            foreach ($items as $id_item) {
                $this->items[] = array(
                    $primary => (int) $id_item
                );
            }
        }
    }

// rendus HTML:

    public function renderListParamsInputs()
    {
        $html = '';
//        if (count($this->params['list_filters'])) {
//            $html .= '<input type="hidden" name="param_list_filters" value="' . htmlentities(json_encode($this->params['list_filters'])) . '"/>';
//        }
//        if (count($this->params['association_filters'])) {
//            $html .= '<input type="hidden" name="param_associations_filters" value="' . htmlentities(json_encode($this->params['association_filters'])) . '"/>';
//        }
        if (isset($this->params['add_form_values'])) {
            $name = isset($this->params['add_form_name']) ? $this->params['add_form_name'] : '';
            $name .= '_add_form_values';
            $html .= '<input type="hidden" name="' . $name . '" value="' . htmlentities(json_encode($this->params['add_form_values'])) . '">';
        }

//        $html .= '<input type="hidden" name="param_sort_field" value="' . $this->params['sort_field'] . '"/>';
//        $html .= '<input type="hidden" name="param_sort_way" value="' . $this->params['sort_way'] . '"/>';
//        $html .= '<input type="hidden" name="param_sort_option" value="' . $this->params['sort_option'] . '"/>';
//        $html .= '<input type="hidden" name="param_n" value="' . $this->params['n'] . '"/>';
//        $html .= '<input type="hidden" name="param_p" value="' . $this->params['p'] . '"/>';

        if (!is_null($this->id_parent)) {
            $html .= '<input type="hidden" id="' . $this->object->object_name . '_id_parent" value="' . $this->id_parent . '"/>';
        }

        return $html;
    }

    public function renderPagination()
    {
        if (is_null($this->nbItems)) {
            return '';
        }

        $html = '';
        if (($this->params['n'] > 0) && ($this->params['n'] < $this->nbItems)) {
            $first = $this->params['p'] - 4;
            if ($first < 1) {
                $first = 1;
            }
            $last = $first + 9;
            if ($last > $this->nbTotalPages) {
                $last = $this->nbTotalPages;
            }

            $html .= '<span class="navButton prevButton' . (((int) $this->params['p'] === 1) ? ' disabled' : '') . '">Précédent</span>';
            $html .= '<div class="pages">';

            if ($first !== 1) {
                $html .= '<span class="pageBtn' . (((int) $this->params['p'] === 1) ? ' active' : '') . '" data-p="1">1</span>';
            }

            $current = $first;
            while ($current <= $last) {
                if ($current !== 1) {
                    $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;';
                }
                $html .= '<span class="pageBtn' . (((int) $current === (int) $this->params['p']) ? ' active' : '') . '" data-p="' . $current . '">' . $current . '</span>';
                $current++;
            }

            if ($last !== $this->nbTotalPages) {
                $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;<span class="pageBtn' . (((int) $this->params['p'] === (int) $this->nbTotalPages) ? ' active' : '') . '" data-p="' . $this->nbTotalPages . '">' . $this->nbTotalPages . '</span>';
            }

            $html .= '</div>';
            $html .= '<span class="navButton nextButton' . (((int) $this->params['p'] >= $this->nbTotalPages) ? ' disabled' : '') . '">Suivant</span>';
        }

        $this->setConfPath();
        return $html;
    }

    public function renderFiltersPanel()
    {
        if (is_null($this->params['filters_panel'])) {
            return '';
        }

        if (is_null($this->bc_filtersPanel)) {
            $this->fetchFiltersPanelValues();
        }

        return $this->bc_filtersPanel->renderHtml();
    }

    public function getHeaderButtons()
    {
        $buttons = parent::getHeaderButtons();

        if (isset($this->params['add_form_values']['fields'])) {
            foreach ($this->params['add_form_values']['fields'] as $field_name => $value) {
                $this->object->set($field_name, $value);
            }
        }

        if ($this->object->isCreatable(false)) {
            if ((int) $this->params['add_btn'] && !is_null($this->params['add_form_name']) && $this->params['add_form_name']) {
                $label = '';
                $on_save = ($this->params['add_form_on_save'] ? $this->params['add_form_on_save'] : $this->object->getConf('forms/' . $this->params['add_form_name'] . '/on_save', 'close'));

                if ($this->object->config->isDefined('forms/' . $this->params['add_form_name'] . '/label')) {
                    $label = $this->object->getConf('forms/' . $this->params['add_form_name'] . '/label', '');
                } elseif ($this->params['add_btn_label']) {
                    $label = $this->params['add_btn_label'];
                }
                if (!$label) {
                    $label = 'Ajouter ' . $this->object->getLabel('a');
                }
                $title = '';

                if (!is_null($this->params['add_form_title']) && $this->params['add_form_title']) {
                    $title = htmlentities(addslashes($this->params['add_form_title']));
                } elseif ($this->object->config->isDefined('forms/' . $this->params['add_form_name'] . '/title')) {
                    $title = htmlentities(addslashes($this->object->getConf('forms/' . $this->params['add_form_name'] . '/title', '')));
                }

                $onclick = 'loadModalFormFromList(\'' . $this->identifier . '\', \'' . $this->params['add_form_name'] . '\', ';
                $onclick .= '$(this), 0, ' . (!is_null($this->id_parent) ? $this->id_parent : 0) . ', \'' . $title . '\', \'' . $on_save . '\')';

                $buttons[] = array(
                    'classes'     => array('btn', 'btn-default'),
                    'label'       => $label,
                    'icon_before' => 'plus-circle',
                    'attr'        => array(
                        'type'    => 'button',
                        'onclick' => $onclick
                    )
                );
            }
        }

        return $buttons;
    }
}
