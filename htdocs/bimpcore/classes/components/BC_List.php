<?php

class BC_List extends BC_Panel
{

    public $component_name = 'Liste';
    public static $type = 'list';
    public static $item_params = array();
    public $id_parent;
    public $parent = null;
    protected $filters = array();
    protected $bc_filtersPanel = null;
    protected $new_values = array();
    protected $items = null;
    public $nbTotalPages = 1;
    protected $nbItems = null;
    public $default_modal_format = 'large';
    public $initial_filters = array();
    public $initial_joins = array();
    public $final_filters = array();
    public $final_joins = array();
    public $final_order_by = '';
    public $final_order_way = '';
    public $final_extra_order_by = '';
    public $final_extra_order_way = '';

    public function __construct(BimpObject $object, $path, $list_name = 'default', $level = 1, $id_parent = null, $title = null, $icon = null, $id_config = null)
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $this->params_def['pagination'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['n'] = array('data_type' => 'int', 'default' => 10, 'request' => true);
        $this->params_def['p'] = array('data_type' => 'int', 'default' => 1, 'request' => true);

        $this->params_def['allow_large_n'] = array('data_type' => 'bool', 'default' => 0);

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
        $this->params_def['display_active_filters'] = array('data_type' => 'bool', 'default' => 1);

        $full_reload = BimpTools::getValue('full_reload', 0);

        if ($full_reload) {
            $this->no_ajax_params = true;
        }

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

        parent::__construct($object, $list_name, $path, false, $level, $title, $icon, $id_config);

        if ($this->isOk()) {
            if (is_null($this->params['title']) || !$this->params['title']) {
                $this->params['title'] = 'Liste des ' . $this->object->getLabel('name_plur');
            }
            if (is_null($this->params['icon']) || !$this->params['icon']) {
                if (is_null($object->params['icon']) || !$object->params['icon']) {
                    $this->params['icon'] = 'fas_list';
                } else {
                    $this->params['icon'] = $object->params['icon'];
                }
            }

            if (!$this->object->can("create")) {
                $this->params['add_btn'] = 0;
                $this->params['add_form_name'] = null;
            }
        }

        $this->setConfPath();

        if (!empty($this->params['list_filters'])) {
            foreach ($this->params['list_filters'] as $filter) {
                $this->initial_filters = BimpTools::mergeSqlFilter($this->initial_filters, $filter['name'], $filter['filter']);
            }
        }

        if (!is_null($this->id_parent) && $this->id_parent != 0) {
            if (is_a($this->object, 'BimpObject')) {
                $parent_id_property = $this->object->getParentIdProperty();
                if ($parent_id_property) {
                    $this->initial_filters[$parent_id_property] = $this->id_parent;
                }
            }
        }

        $this->initial_joins = $this->params['joins'];

        $current_bc = $prev_bc;
    }

    // Gestion des configurations utilisateur: 

    public function fetchUserConfig($id_config)
    {
        parent::fetchUserConfig($id_config);

        if (BimpObject::objectLoaded($this->userConfig)) {
            if ($this->userConfig->isListSortable()) {
                if (!BimpTools::isSubmit('param_sort_field') || $this->newUserConfigSet) {
                    $sort_field = $this->userConfig->getData('sort_field');
                    if (!is_null($sort_field)) {
                        $this->params['sort_field'] = $sort_field;
                    }
                }
                if (!BimpTools::isSubmit('param_sort_way') || $this->newUserConfigSet) {
                    $sort_way = $this->userConfig->getData('sort_way');
                    if (!is_null($sort_way)) {
                        $this->params['sort_way'] = $sort_way;
                    }
                }
                if (!BimpTools::isSubmit('param_sort_option') || $this->newUserConfigSet) {
                    $sort_option = $this->userConfig->getData('sort_option');
                    if (!is_null($sort_option)) {
                        $this->params['sort_option'] = $sort_option;
                    }
                }
            }

            if ($this->userConfig->hasPagination()) {
                if (!BimpTools::isSubmit('param_n') || $this->newUserConfigSet) {
                    $n = $this->userConfig->getData('nb_items');
                    if (!is_null($n)) {
                        $this->params['n'] = $n;
                    }
                }
            }

            if ($this->userConfig->hasFiltersPanel()) {
                $filters_open = $this->userConfig->getData('filters_open');
                if (!is_null($filters_open)) {
                    $this->params['filters_panel_open'] = (int) $filters_open;
                }

                $this->params['display_active_filters'] = (int) $this->userConfig->getData('active_filters');
            }

            if ($this->userConfig->isListSearchable()) {
                if (array_key_exists('search_open', $this->params)) {
                    $search_open = $this->userConfig->getData('search_open');
                    if (!is_null($search_open)) {
                        $this->params['search_open'] = (int) $search_open;
                    }
                }
            }

            if ($this->userConfig->hasTotalRow()) {
                if (isset($this->params['enable_total_row']) && isset($this->params['total_row']) && (int) $this->params['enable_total_row']) {
                    $this->params['total_row'] = (int) $this->userConfig->getData('total_row');
                }
            }
        }
    }

    // Gestion des filtres: 

    protected function mergeFilter($name, $filter, $no_alias = false)
    {
        if (!$no_alias && !preg_match('/^.+\..+$/', $name) && stripos($name, ".") === false) {
            $name = 'a.' . $name;
        }

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

        $this->initial_filters = BimpTools::mergeSqlFilter($this->initial_filters, $field_name, $value);
    }

    public function addJoin($table, $on, $alias)
    {
        if (!isset($this->params['joins'][$alias])) {
            $this->params['joins'][$alias] = array(
                'table' => $table,
                'on'    => $on,
                'alias' => $alias
            );

            $this->initial_joins = $this->params['joins'];
        }
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function getSearchFilters(&$joins = array())
    {
        return array();
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
                $this->params['add_form_values']['associations'][$association] = array($id_object);
            } else {
                $this->addError('Filtre invalide pour l\'association "' . $association . '"');
            }
        } else {
            $this->errors = BimpTools::merge_array($this->errors, $bimpAsso->errors);
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
            $this->params['add_form_values']['associations'][$association] = array($id_associate);
        } else {
            $this->errors = BimpTools::merge_array($this->errors, $bimpAsso->errors);
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

                    if (!is_array($this->params['extra_bulk_actions'])) {
                        $this->params['extra_bulk_actions'] = array();
                    }

                    $this->params['extra_bulk_actions'][] = array(
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

                    if (!is_array($this->params['extra_bulk_actions'])) {
                        $this->params['extra_bulk_actions'] = array();
                    }

                    $this->params['extra_bulk_actions'][] = array(
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
        if (!isset($this->params['add_form']) || is_null($this->params['add_form'])) {
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
        if (!is_null($this->bc_filtersPanel)) {
            return;
        }

        if (is_null($this->params['filters_panel'])) {
            return;
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $id_config = 0;
        $use_default_filters = false;

        // Si une nouvelle config de liste est démandée, on utilise en prio la config de filtres associée: 
//        if ($this->newUserConfigSet && BimpObject::objectLoaded($this->userConfig) && (int) $this->userConfig::$has_filters) {
//            $id_config = (int) $this->userConfig->getData('id_default_filters_config');
//        }

        if (!$this->newUserConfigSet && BimpTools::isSubmit('id_current_filters_panel_config')) {
            $id_config = (int) BimpTools::getValue('id_current_filters_panel_config');
        }

        if (!$id_config && BimpObject::objectLoaded($this->userConfig) && (int) $this->userConfig::$has_filters) {
            $id_config = (int) $this->userConfig->getData('id_default_filters_config');
        }

        $this->bc_filtersPanel = new BC_FiltersPanel($this->object, static::$type, $this->name, $this->identifier, $this->params['filters_panel'], $id_config);

        $id_default_filters = 0;
        if (BimpObject::objectLoaded($this->userConfig) && $this->userConfig->field_exists('id_default_filters')) {
            $id_default_filters = (int) $this->userConfig->getData('id_default_filters');

            if ($id_default_filters && $this->newUserConfigSet) {
                $use_default_filters = true;
            }
        }

        if (BimpTools::isSubmit('filters_panel_values') && !$use_default_filters) {
            $this->bc_filtersPanel->setFilters(BimpTools::getValue('filters_panel_values', array()));
        } elseif ($id_default_filters) {
            $this->bc_filtersPanel->loadSavedValues($id_default_filters);
        } elseif (!empty($this->params['filters_panel_values'])) {
            $this->bc_filtersPanel->setFilters($this->params['filters_panel_values']);
        }

        $current_bc = $prev_bc;
    }

    public function getPointsForGraph($idGraph, $numero_data = 1)
    {
        $return = array();
        foreach ($this->items as $item) {
            $obj = BimpCache::getBimpObjectInstance($this->object->module, $this->object->object_name, $item['id']);
            $return[] = $obj->getGraphDataPoint($idGraph, $numero_data);
        }
        return $return;
    }

    public function initForGraph()
    {
        $this->params['n'] = 1000;
        $this->fetchItems();
    }

    protected function fetchItems()
    {
        $this->filters = $this->getSearchFilters($this->params['joins']);

        if (method_exists($this->object, "beforeListFetchItems")) {
            $this->object->beforeListFetchItems($this);
        }

        $this->fetchFiltersPanelValues();

        if (!$this->isOk()) {
            $this->setConfPath();
            $this->items = array();
            return;
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

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
            $filters_errors = $this->bc_filtersPanel->getSqlFilters($panelFilters, $joins);

            if (count($filters_errors)) {
                $this->errors[] = BimpTools::getMsgFromArray($filters_errors, 'Erreurs sur les filtres');
            }

            foreach ($panelFilters as $name => $filter) {
                $this->mergeFilter($name, $filter);
            }
            if (method_exists($this->object, 'traiteFilters'))
                $this->object->traiteFilters($this->filters);
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
                        $this->errors[] = BimpTools::merge_array($this->errors, $bimp_asso->errors);
                        $this->filters[$this->object->getPrimary()] = 0;
                    }
                }
            }
        }

        $filters = $this->filters;
        if (!is_null($this->id_parent) && $this->id_parent != 0) {
            $parent_id_property = $this->object->getParentIdProperty();
            if ($parent_id_property) {
                $filters[$parent_id_property] = $this->id_parent;
            }
        }

        // Trie: 
        $order_by = '';
        $extra_order_by = null;
        $extra_order_way = null;

        if (!is_null($this->params['sort_field']) && (string) $this->params['sort_field']) {
            $order_by = $this->getOrderBySqlKey($this->params['sort_field'], $this->getParam('sort_option', ''), $filters, $joins);

//            $extra_order_by = $this->object->getConf('fields/' . $this->params['sort_field'] . '/next_sort_field');
//            $extra_order_way = $this->object->getConf('fields/' . $this->params['sort_field'] . '/next_sort_way');
        }

        if (!$order_by) {
            $order_by = 'a.' . $primary;
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

        $this->final_filters = $filters;
        $this->final_joins = $joins;
        $this->final_order_by = $order_by;
        $this->final_order_way = $this->params['sort_way'];
        $this->final_extra_order_by = $extra_order_by;
        $this->final_order_way = $extra_order_way;

        BimpCore::addLogs_extra_data(array('filtre' => $filters, 'joins' => $joins));

        $this->items = $this->object->getList($filters, $this->params['n'], $this->params['p'], $order_by, $this->params['sort_way'], 'array', array(
            'DISTINCT (a.' . $primary . ')'
                ), $joins, $extra_order_by, $extra_order_way);

        if (method_exists($this->object, 'listItemsOverride')) {
            $this->object->listItemsOverride($this->name, $this->items);
        }

        $current_bc = $prev_bc;
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

    public function getItems()
    {
        if (is_null($this->items)) {
            $this->fetchItems();
        }

        return $this->items;
    }

    public function getCsvColOptionsInputs()
    {
        return array();
    }

    public function getOrderBySqlKey($sort_field = '', $sort_option = '', &$filters = array(), &$joins = array())
    {
        // Attention: fonction surchargée par BC_ListTable
        if ($sort_field == $this->object->position_field) {
            return 'a.' . $this->object->position_field;
        }

        if ($this->object->getConf('fields/' . $sort_field . '/type', 'string') === 'id_object') {
            $sort_obj = $this->object->config->getObject('fields/' . $sort_field . '/object');
            if (!is_null($sort_obj) && is_a($sort_obj, 'BimpObject')) {
                if ($sort_option && $sort_option === $sort_obj->getPrimary()) {
                    $sort_option = '';
                }
                if ($sort_obj->field_exists($sort_option)) {
                    if ((int) $sort_obj->getConf('fields/' . $this->params['sort_option'] . '/sortable', 1, false, 'bool')) {
                        $sqlKey = $sort_obj->getFieldSqlKey($sort_option, 'a', null, $filters, $joins);

                        if ($sqlKey) {
                            return $sqlKey;
                        }
                    }
                }
            }
        }

        if ($this->object->field_exists($sort_field)) {
            $sqlKey = $this->object->getFieldSqlKey($sort_field, 'a', null, $filters, $joins);

            if ($sqlKey) {
                return $sqlKey;
            }
        }

        return 'a.' . $this->object->getPrimary();
    }

    // rendus HTML:

    public function renderRowButton($btn_params, $popover_position = 'top')
    {
        $html = '';
        $tag = isset($btn_params['tag']) ? $btn_params['tag'] : 'span';
        $html .= '<' . $tag . ' class="rowButton' . (isset($btn_params['class']) ? ' ' . $btn_params['class'] : '');

        if (isset($btn_params['label'])) {
            $html .= ' bs-popover"';
            $html .= BimpRender::renderPopoverData($btn_params['label'], $popover_position, 'false', '#' . $this->identifier);
        } else {
            $html .= '"';
        }
        if (isset($btn_params['onclick'])) {
            $html .= ' onclick="' . str_replace('<list_id>', $this->identifier, $btn_params['onclick']) . '"';
        }

        if (isset($btn_params['attrs'])) {
            $html .= BimpRender::displayTagAttrs($btn_params['attrs']);
        }

        $html .= '>';
        if (isset($btn_params['icon'])) {
            $html .= '<i class="' . BimpRender::renderIconClass($btn_params['icon']) . '"></i>';
        }
        $html .= '</' . $tag . '>';

        return $html;
    }

    public function renderListParamsInputs()
    {
        $html = '';
//        if (count($this->params['list_filters'])) {
//            $html .= '<input type="hidden" name="param_list_filters" value="' . htmlentities(json_encode($this->params['list_filters'])) . '"/>';
//        }
//        if (count($this->params['association_filters'])) {
//            $html .= '<input type="hidden" name="param_associations_filters" value="' . htmlentities(json_encode($this->params['association_filters'])) . '"/>';
//        }
        if (isset($this->params['add_form_values']) && !empty($this->params['add_form_values'])) {
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

            $nFirst = (((int) $this->params['p'] - 1) * (int) $this->params['n']) + 1;
            $nLast = $nFirst + (int) $this->params['n'] - 1;

            if ($nLast > (int) $this->nbItems) {
                $nLast = $this->nbItems;
            }

            $html .= '<div class="results_count">';
            $html .= 'Résultat' . ($this->nbItems > 1 && $nFirst !== $nLast ? 's' : '');
            $html .= ' <span>' . $nFirst . '</span>' . ($nFirst !== $nLast ? ' à <span>' . $nLast . '</span>' : '');
            $html .= ' sur <span>' . $this->nbItems . '</span>';
            $html .= '</div>';

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

    public function renderActiveFilters($content_only = false)
    {
        $html = '';
        $filters_html = '';

        if ((int) $this->params['display_active_filters']) {
            if (is_object($this->bc_filtersPanel)) {
                $filters_html = $this->bc_filtersPanel->renderActiveFilters();
            }
        }

        if (!$content_only) {
            $html .= '<div class="list_active_filters"' . ($filters_html ? '' : ' style="display: none"') . '>';
            $html .= $filters_html;
            $html .= '</div>';
        } else {
            return $filters_html;
        }

        return $html;
    }

    public function getHeaderButtons()
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $buttons = parent::getHeaderButtons();

        if (isset($this->params['add_form_values']['fields'])) {
            foreach ($this->params['add_form_values']['fields'] as $field_name => $value) {
                $this->object->set($field_name, $value);
            }
        }

        if ($this->object->getConf('in_cache_serveur') && BimpCache::$cache_server) {
            $buttons[] = array(
                'classes'     => array('btn', 'btn-default'),
                'label'       => 'Vider le cache',
                'icon_before' => 'eraser',
                'attr'        => array(
                    'type'    => 'button',
                    'onclick' => $this->object->getJsActionOnclick('eraseCache')
                )
            );
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

        $current_bc = $prev_bc;
        return $buttons;
    }
}
