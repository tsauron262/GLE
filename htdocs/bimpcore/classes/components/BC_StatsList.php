<?php

class BC_StatsList extends BC_List
{

    public $component_name = 'Liste statistique';
    public static $type = 'stats_list';
    public static $hasUserConfig = true;
    public static $col_params = array(
        'label'         => array(),
        'type'          => array('default' => 'sum'),
        'data_type'     => array('default' => 'int'),
        'field'         => array('default' => ''),
        'child'         => array(),
        'display'       => array('default' => ''),
        'filters'       => array('data_type' => 'array', 'compile' => 1),
        'sortable'      => array('data_type' => 'bool', 'default' => 1),
        'available_csv' => array('data_type' => 'bool', 'default' => 1),
        'width'         => array('default' => null),
        'min_width'     => array('default' => null),
        'max_width'     => array('default' => null),
        'search'        => array('type' => 'definitions', 'defs_type' => 'search', 'default' => null),
    );
    public static $col_data_type_params = array(
        'float'   => array(
            'decimals' => array('data_type' => 'int', 'default' => 2)
        ),
        'percent' => array(
            'decimals' => array('data_type' => 'int', 'default' => 2)
        ),
        'money'   => array(
            'decimals'    => array('data_type' => 'int', 'default' => 2),
            'with_styles' => array('data_type' => 'bool', 'default' => 0),
            'truncate'    => array('data_type' => 'bool', 'default' => 0)
        ),
    );
    public static $cols_types = array('sum', 'count', 'avg', 'count_distinct');
    public $cols = null;
    public $colspan = 0;
    public $groupBy = null;
    public $groupByIndex = null;
    public $subListFilters = array();
    public $subListJoins = array();
    public $section_filters = array();
    public $nextGroupBy = null;
    public $search = false;
    public $data = array();
    public $totals = array();
    public $statFieldsFilters = array();
    public $base_list_id = '';

    public function __construct(BimpObject $object, $name = 'default', $id_parent = null, $title = null, $icon = null, $id_config = null, $groupByIndex = null, $subListFilters = array(), $subListJoins = array())
    {
        $this->params_def['group_by'] = array('data_type' => 'array', 'default' => array(), 'request' => true, 'json' => true);
        $this->params_def['group_by_options'] = array('data_type' => 'array', 'compile' => true, 'default' => array());
        $this->params_def['enable_total_row'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['total_row'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['enable_csv'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['cols'] = array('type' => 'keys');

        $this->groupByIndex = $groupByIndex;
        $this->subListFilters = $subListFilters;
        $this->subListJoins = $subListJoins;

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $path = null;

        if (!is_null($object)) {
            if (!$name || $name === 'default') {
                if ($object->config->isDefined('stats_list')) {
                    $path = 'stats_list';
                    $name = '';
                } elseif ($object->config->isDefined('list_customs/default')) {
                    $path = 'stats_lists';
                    $name = 'default';
                }
            } else {
                $path = 'stats_lists';
            }
        }

        if (is_null($title)) {
            $title = 'Statistiques des ' . $object->getLabel('name_plur');
        }

        if (is_null($icon)) {
            $icon = 'fas_chart-bar';
        }


        parent::__construct($object, '', $name, 1, $id_parent, $title, $icon, $id_config);

        if (!empty($subListFilters)) {
            foreach ($subListFilters as $field => $value) {
                $this->addIdentifierSuffix(str_replace('.', '_', $field) . '_' . $value);
            }

            $this->params['configurable'] = 0;
        }

        $this->params['filters_panel_open'] = 1;

        if (!$this->params['pagination']) {
            $this->params['n'] = 0;
            $this->params['p'] = 1;
        }

        if (!count($this->errors)) {

            if (!(int) $this->params['enable_total_row']) {
                $this->params['total_row'] = 0;
            }

            $this->fetchGroupBy();
            $this->fetchCols();
        }

        $this->base_list_id = $this->identifier;
        $current_bc = $prev_bc;
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
        $data_type_params = array();

        if ($this->object->config->isDefined('stats_lists_cols/' . $col_name)) {
            $col_params = $this->fetchParams('stats_lists_cols/' . $col_name, static::$col_params);
            $col_overriden_params = $this->object->config->getCompiledParams($this->config_path . '/cols/' . $col_name);
            if (is_array($col_overriden_params) && !empty($col_overriden_params)) {
                $col_params = $this->object->config->mergeParams($col_params, $col_overriden_params);
            }

            if (array_key_exists($col_params['data_type'], self::$col_data_type_params)) {
                $data_type_params = $this->fetchParams('stats_lists_cols/' . $col_name, static::$col_data_type_params[$col_params['data_type']]);
                $data_type_overriden_params = $this->object->config->getCompiledParams($this->config_path . '/cols/' . $col_name);

                if (is_array($data_type_overriden_params) && !empty($data_type_overriden_params)) {
                    $data_type_params = $this->object->config->mergeParams($data_type_params, $data_type_overriden_params);
                }
            }
        } else {
            $col_params = $this->fetchParams($this->config_path . '/cols/' . $col_name, static::$col_params);

            if (array_key_exists($col_params['data_type'], self::$col_data_type_params)) {
                $data_type_params = $this->fetchParams($this->config_path . '/cols/' . $col_name, static::$col_data_type_params[$col_params['data_type']]);
            }
        }

        if (!empty($data_type_params)) {
            foreach ($data_type_params as $name => $params) {
                $col_params[$name] = $params;
            }
        }

        $current_bc = $prev_bc;
        return $col_params;
    }

    protected function fetchCols()
    {
        if (is_null($this->cols)) {
            $this->cols = array();
            $this->colspan = 1; // Tools col

            if ($this->params['total_row']) {
                $this->colspan++;
            }

            // Main Cols:            
            $section = false;
            if (!empty($this->groupBy)) {
                foreach ($this->groupBy as $gb) {
                    $label = $this->object->getConf('fields/' . $gb['value'] . '/label', '');
                    $this->cols[$gb['value']] = array(
                        'label' => $label,
                        'field' => $gb['value']
                    );

                    if ((int) $gb['section']) {
                        $section = true;
                        break;
                    }
                }
            }

            if (empty($this->cols) || !$section) {
                $primary = $this->object->getPrimary();
                $this->cols[$primary] = array(
                    'label' => BimpTools::ucfirst($this->object->getLabel()),
                    'field' => $primary
                );
            }

            $cols = array();

            if ($this->params['configurable']) {
                if ($this->object->config->isDefined('stats_lists_cols')) {
                    $lists_cols = $this->object->config->getCompiledParams('stats_lists_cols');
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

            $list_cols = array();

            if ($this->params['configurable'] && BimpObject::objectLoaded($this->userConfig)) {
                $list_cols = $this->userConfig->getData('cols');
            }

            if (!is_array($list_cols) || empty($list_cols)) {
                $list_cols = $this->params['cols'];
            }

            foreach ($list_cols as $col_name) {
                if (!in_array($col_name, $cols)) {
                    continue;
                }

                $show = (int) $this->object->getConf('stats_lists_cols/' . $col_name . '/show', 1, false, 'bool');
                $show = (int) $this->object->getConf($this->config_path . '/cols/' . $col_name . '/show', $show, false, 'bool');

                if ($show) {
                    $field = $this->object->getConf('stats_lists_cols/' . $col_name . '/field', '');
                    $field = $this->object->getConf($this->config_path . '/cols/' . $col_name . '/field', $field);
                    if ($field && $this->object->isDolObject()) {
                        if (!$this->object->dol_field_exists($field)) {
                            continue;
                        }
                    }
                    $this->cols[$col_name] = $this->getColParams($col_name);
                }
            }

            $this->colspan += count($this->cols);
        }
    }

    public function fetchGroupBy()
    {
        if (is_null($this->groupBy)) {
            $this->groupBy = array();

            $groupsBy = BimpTools::getValue('group_by', array());
            $idx = (int) $this->groupByIndex;
            $next_gb_idx = null;

            foreach ($groupsBy as $gb_idx => $gb) {
                if ((int) $gb_idx >= $idx) {
                    $this->groupBy[] = $gb;

                    if ((int) $gb['section']) {
                        $next_gb_idx = $gb_idx + 1;
                        break;
                    }
                }
            }

            if (!is_null($next_gb_idx)) {
                $this->nextGroupBy = array(
                    'idx'     => $next_gb_idx,
                    'filters' => array(),
                    'fields'  => array()
                );
                $next_gb_section = false;
                foreach ($groupsBy as $gb_idx => $gb) {
                    if ($gb_idx < $next_gb_idx) {
                        $this->nextGroupBy['filters'][] = $gb['value'];
                    } else {
                        $this->nextGroupBy['fields'][] = $gb;
                        $this->nextGroupBy['idx'] = $gb_idx;

                        if ((int) $gb['section']) {
                            $next_gb_section = true;
                            break;
                        }
                    }
                }

                if (!$next_gb_section) {
                    $this->nextGroupBy['fields'][] = array(
                        'value'   => $this->object->getPrimary(),
                        'section' => 0
                    );
                }
            }

            if (empty($this->groupBy) || is_null($next_gb_section)) {
                $this->groupBy[] = array(
                    'value'   => $this->object->getPrimary(),
                    'section' => 0
                );
            }
        }
    }

    protected function fetchItems()
    {
        $this->items = array();
    }

    protected function fetchSearchFilters()
    {
        $init_post = $_POST;
        $search_fields = BimpTools::getValue('search_fields', array());
        unset($_POST['search_fields']);

        $obj_fields = array();
        $stats_fields = array();

        // On sépare les champs calculés des champs ordinaires: 
        foreach ($search_fields as $field_name => $value) {
            if ($this->object->field_exists($field_name)) {
                $obj_fields[$field_name] = $value;
            } else {
                $stats_fields[$field_name] = $value;
            }
        }

        // Champs de base de l'objet: 
        $filters = $this->object->getSearchFilters($this->params['joins'], $obj_fields, 'a');
        foreach ($filters as $filter_key => $filter) {
            $this->mergeFilter($filter_key, $filter);
        }

        // Champs des objets enfants: 
        $filters = $this->object->getSearchFilters($this->params['joins']);
        foreach ($filters as $filter_key => $filter) {
            $this->mergeFilter($filter_key, $filter);
        }

        // Champs calculés: 
        foreach ($stats_fields as $name => $value) {
            if ((isset($value['min']) && (string) $value['min'] !== '') || (isset($value['max']) && (string) $value['max'] !== '')) {
                $this->statFieldsFilters = BimpTools::mergeSqlFilter($this->statFieldsFilters, $name, $value);
            }
        }

        $_POST = $init_post;
    }

    protected function getStatsItems()
    {
        $this->fetchSearchFilters();
        $this->fetchFiltersPanelValues();

        if (!$this->isOk()) {
            return array();
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $table = $this->object->getTable();
        $primary = $this->object->getPrimary();

        // Jointures: 
        $joins = $this->params['joins'];

        // Filtres:
        // Filtres de base: 
        if (count($this->params['list_filters'])) {
            foreach ($this->params['list_filters'] as $list_filter) {
                $this->mergeFilter($list_filter['name'], $list_filter['filter']);
            }
        }

        // Panneau filtres: 
        if (!is_null($this->bc_filtersPanel)) {
            $panelFilters = array();
            $filters_errors = $this->bc_filtersPanel->getSqlFilters($panelFilters, $joins);

            if (count($filters_errors)) {
                $this->errors[] = BimpTools::getMsgFromArray($filters_errors, 'Erreurs sur les filtres');
            }

            if (method_exists($this->object, 'traiteFilters')) {
                $this->object->traiteFilters($panelFilters);
            }

            foreach ($panelFilters as $name => $filter) {
                $this->mergeFilter($name, $filter);
            }
        }

        // Filtres sous-liste: 
        foreach ($this->subListFilters as $field => $filter) {
            $this->mergeFilter($field, $filter);
        }

        // Joins sous-liste: 
        foreach ($this->subListJoins as $join_alias => $join) {
            if (!isset($joins[$join_alias])) {
                $joins[$join_alias] = $join;
            }
        }

        // ID Parent: 
        if (!is_null($this->id_parent) && $this->id_parent != 0) {
            $parent_id_property = $this->object->getParentIdProperty();
            if ($parent_id_property) {
                $this->mergeFilter($parent_id_property, $this->id_parent);
            }
        }

        $filters = $this->filters;

        if (count($this->errors)) {
            return array();
        }

        // Groups By: 

        $groups_by = array();

        foreach ($this->groupBy as $gb) {
            $group_by = $gb['value'];

            if ($group_by) {
                if ($group_by === $primary) {
                    $group_by_key = 'a.' . $primary;
                } else {
                    $group_by_key = $this->object->getFieldSqlKey($group_by, 'a', null, $filters, $joins, $this->errors);
                }

                if ($group_by_key) {
                    $groups_by[] = array(
                        'field' => $group_by,
                        'key'   => $group_by_key
                    );
                }
            }
        }

        if (count($this->errors)) {
            return array();
        }

        // Trie: 
        $order_by = $primary;
        $order_way = 'desc';
        $extra_order_by = null;
        $extra_order_way = null;

        if (!is_null($this->params['sort_field'])) {
            $order_by = '';
            if (!is_null($this->params['sort_option']) && $this->params['sort_option']) {
                $sort_option_path = 'fields/' . $this->params['sort_field'] . '/sort_options/' . $this->params['sort_option'];
                if ($this->object->config->isDefined($sort_option_path)) {
                    $join_field = $this->object->getConf($sort_option_path . '/join_field', '');
                    if ($join_field && $this->object->config->isDefined('fields/' . $this->params['sort_field'] . '/object')) {
                        $sort_child = $this->object->config->get('fields/' . $this->params['sort_field'] . '/object', '');
                        if ($sort_child) {
                            $order_by = $this->object->getFieldSqlKey($join_field, 'a', $sort_child, $filters, $joins, $this->errors);
                        } else {
                            $this->errors[] = 'Nom de l\'objet enfant absent pour le trie via le champ "' . $this->params['sort_field'] . '"';
                        }
                    } else {
                        $this->errors[] = 'Echec de l\'obtention de la clé SQL pour le trie via le champ "' . $this->params['sort_field'] . '"';
                    }
                } elseif ($this->object->getConf('fields/' . $this->params['sort_field'] . '/type', 'string') === 'id_object') {
                    $sort_child = $this->object->config->get('fields/' . $this->params['sort_field'] . '/object', '');
                    if ($sort_child) {
                        $order_by = $this->object->getFieldSqlKey($this->params['sort_option'], 'a', $sort_child, $filters, $joins, $this->errors);
                    } else {
                        $this->errors[] = 'Nom de l\'objet enfant absent pour le trie via le champ "' . $this->params['sort_field'] . '"';
                    }
                }
            }

            if (!$order_by) {
                if ($this->params['sort_field'] === 'id' || $this->params['sort_field'] === $primary) {
                    $order_by = 'a.' . $primary;
                } elseif ($this->object->field_exists($this->params['sort_field'])) {
                    $order_by = $this->object->getFieldSqlKey($this->params['sort_field'], 'a', null, $filters, $joins, $this->errors);
                } else {
                    $order_by = $this->params['sort_field'];
                }
            }

            $extra_order_by = $this->object->getConf('fields/' . $this->params['sort_field'] . '/next_sort_field');
            $extra_order_way = $this->object->getConf('fields/' . $this->params['sort_field'] . '/next_sort_way');

            $this->setConfPath();
        }

        if (isset($this->params['sort_way']) && $this->params['sort_way']) {
            $order_way = $this->params['sort_way'];
        }

        $n = $this->params['n'];
        $p = $this->params['p'];

        $this->final_filters = $filters;
        $this->final_joins = $joins;

        // Requête principale:         
        $sql = '';

        $request_fields = array();
        $total_fields = array();

        foreach ($groups_by as $group_by) {
            $request_fields[] = $group_by['key'] . ' as ' . $group_by['field'];
        }

        // SQL Filtres sur les champs calculés: 
//        $having_sql = '';
//        if (!empty($this->statFieldsFilters)) {
//            foreach ($this->statFieldsFilters as $col_name => $filter) {
//                if (isset($this->cols[$col_name])) {
//                    $having_sql .= ($having_sql ? ' AND ' : '') . '(';
//                    $filter_sql = '';
//                    if (isset($filter['min']) && (string) $filter['min'] !== '') {
//                        $filter_sql .= $col_name . ' >= ' . $filter['min'];
//                    }
//                    if (isset($filter['max']) && (string) $filter['max'] !== '') {
//                        $filter_sql .= ($filter_sql ? ' AND ' : '') . $col_name . ' <= ' . $filter['min'];
//                    }
//                    $having_sql .= $filter_sql;
//                    $having_sql .= ')';
//                }
//            }
//        }

        foreach ($this->cols as $col_name => $col_params) {
            $type = BimpTools::getArrayValueFromPath($col_params, 'type', '');
            $field = BimpTools::getArrayValueFromPath($col_params, 'field', '');
            $child = BimpTools::getArrayValueFromPath($col_params, 'child', null);
            $col_filters = BimpTools::getArrayValueFromPath($col_params, 'filters', array());

            switch ($type) {
                case 'sum':
                case 'avg':
                    if ($field) {
                        $key = $this->object->getFieldSqlKey($field, 'a', $child, $filters, $joins, $this->errors);
                        if ($key) {
                            $col_filters = BimpTools::getArrayValueFromPath($col_params, 'filters', array());
                            $request_field = strtoupper($type) . '(';
                            $request_field .= BimpTools::getSqlCase($col_filters, $key, 0, 'a');
                            $request_field .= ') as ' . $col_name;
                            $request_fields[] = $request_field;
                            $total_fields[] = $request_field;
                        }
                    }
                    break;

                case 'count_distinct':
                    if ($field) {
                        $key = $this->object->getFieldSqlKey($field, 'a', $child, $filters, $joins, $this->errors);
                        if ($key) {
                            $request_fields[] = 'COUNT(DISTINCT ' . $key . ') as ' . $col_name;
                        }
                    }
                    break;

                case 'count':
                    if (empty($col_filters)) {
                        $request_fields[] = 'COUNT(a.' . $primary . ') as ' . $col_name;
                    } else {
                        $request_field = 'SUM(';
                        $request_field .= BimpTools::getSqlCase($col_filters, 1, 0, 'a');
                        $request_field .= ') as ' . $col_name;
                        $request_fields[] = $request_field;
                        $total_fields[] = $request_field;
                    }
                    break;
            }
        }

        if (count($this->errors)) {
            return array();
        }

        $data = array();
        $bdb = BimpCache::getBdb();

        if (!empty($request_fields)) {
            $sql = BimpTools::getSqlSelect($request_fields, 'a');
            $sql .= BimpTools::getSqlFrom($table, $joins, 'a');
            $sql .= BimpTools::getSqlWhere($filters, 'a');
            $sql .= ' GROUP BY ';

            $fl = true;
            foreach ($groups_by as $group_by) {
                if (!$fl) {
                    $sql .= ', ';
                } else {
                    $fl = false;
                }

                $sql .= $group_by['field'];
            }

//            if ($having_sql) {
//                $sql .= ' HAVING ' . $having_sql;
//            }

            $sql .= BimpTools::getSqlOrderBy($order_by, $order_way, '', $extra_order_by, $extra_order_way);

            if (empty($this->statFieldsFilters)) { // On récupère la totalité des lignes pour appliquer les filtres sur les champs calculés après coup. 
                $sql .= BimpTools::getSqlLimit($n, $p);
            }

            $rows = $bdb->executeS($sql, 'array');

            if (is_array($rows)) {
                if (BimpDebug::isActive()) {
                    $content = BimpRender::renderSql($sql);
                    $content .= BimpRender::renderFoldableContainer('Liste params', '<pre>' . print_r($this->params, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                    $content .= BimpRender::renderFoldableContainer('Filters', '<pre>' . print_r($filters, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                    $content .= BimpRender::renderFoldableContainer('Joins', '<pre>' . print_r($joins, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                    $content .= BimpRender::renderFoldableContainer('GroupBy', '<pre>' . print_r($this->groupBy, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                    $content .= BimpRender::renderFoldableContainer('Next GroupBy', '<pre>' . print_r($this->nextGroupBy, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                    $title = 'SQL Stats Liste - Module: "' . $this->object->module . '" Objet: "' . $this->object->object_name . '" - StatsList: ' . $this->name;
                    BimpDebug::addDebug('list_sql', $title, $content);
                }

                foreach ($rows as $r) {
                    $key = '';
                    $fl = true;
                    foreach ($groups_by as $gb) {
                        if (!$fl) {
                            $key .= '-';
                        } else {
                            $fl = false;
                        }
                        $key .= $r[$gb['field']];
                    }

                    $data[$key] = $r;
                }
            } else {
                $this->errors[] = $bdb->db->lasterror();
                if (BimpDebug::isActive()) {
                    $content = BimpRender::renderSql($sql);
                    $content .= BimpRender::renderDebugInfo($bdb->db->lasterror(), 'ERREUR SQL', 'fas_exclamation-circle');
                    $content .= BimpRender::renderFoldableContainer('Liste params', '<pre>' . print_r($this->params, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                    $content .= BimpRender::renderFoldableContainer('Filters', '<pre>' . print_r($filters, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                    $content .= BimpRender::renderFoldableContainer('Joins', '<pre>' . print_r($joins, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                    $content .= BimpRender::renderFoldableContainer('GroupBy', '<pre>' . print_r($this->groupBy, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                    $content .= BimpRender::renderFoldableContainer('Next GroupBy', '<pre>' . print_r($this->nextGroupBy, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                    $title = 'SQL Stats Liste - Module: "' . $this->object->module . '" Objet: "' . $this->object->object_name . '" - StatsList: ' . $this->name;
                    BimpDebug::addDebug('list_sql', $title, $content);
                }
            }
        }

        // Requête nombre total d'éléments et totaux
        $this->nbItems = 0;
        $sql = 'SELECT COUNT(DISTINCT ';

        $fl = true;
        foreach ($groups_by as $gb) {
            if (!$fl) {
                $sql .= ', ';
            } else {
                $fl = false;
            }

            $sql .= $gb['key'];
        }

        $sql .= ') as nbItems';

        if (empty($this->statFieldsFilters) && $this->params['total_row'] && !empty($total_fields)) {
            foreach ($total_fields as $total_field) {
                $sql .= ', ' . $total_field;
            }
        } else {
            $joins = $this->final_joins;
            $filters = $this->final_filters;
        }

        $sql .= BimpTools::getSqlFrom($table, $joins);
        $sql .= BimpTools::getSqlWhere($filters, 'a');

        $rows = $bdb->executeS($sql, 'array');

        if (isset($rows[0])) {
            $this->nbItems = (int) $rows[0]['nbItems'];

            if (empty($this->statFieldsFilters)) {
                if ($this->params['total_row']) {
                    foreach ($this->cols as $col_name => $col_params) {
                        if (isset($rows[0][$col_name])) {
                            $this->totals[$col_name] = $rows[0][$col_name];
                        }
                    }
                }
            }

            if (BimpDebug::isActive()) {
                $content = BimpRender::renderSql($sql);
                $content .= BimpRender::renderFoldableContainer('Filters', '<pre>' . print_r($filters, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                $content .= BimpRender::renderFoldableContainer('Joins', '<pre>' . print_r($joins, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                $title = 'SQL Stats Liste (Nb Total items' . ($this->params['total_row'] ? ' + totaux' : '') . ') - Module: "' . $this->object->module . '" Objet: "' . $this->object->object_name . '" - StatsList: ' . $this->name;
                BimpDebug::addDebug('list_sql', $title, $content);
            }
        } else {
            if (BimpDebug::isActive()) {
                $content = BimpRender::renderSql($sql);
                $content .= BimpRender::renderDebugInfo($bdb->db->lasterror(), 'ERREUR SQL', 'fas_exclamation-circle');
                $content .= BimpRender::renderFoldableContainer('Filters', '<pre>' . print_r($filters, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                $content .= BimpRender::renderFoldableContainer('Joins', '<pre>' . print_r($joins, 1) . '</pre>', array('open' => false, 'offset_left' => true));
                $title = 'SQL Stats Liste (Nb Total items) - Module: "' . $this->object->module . '" Objet: "' . $this->object->object_name . '" - StatsList: ' . $this->name;
                BimpDebug::addDebug('list_sql', $title, $content);
            }
        }

        if ($n <= 0) {
            $p = 1;
        }


        // Application des filtres sur les champs calculés: 
        if (!empty($this->statFieldsFilters)) {
            $this->totals = array();
            if ($this->params['total_row'] && !empty($total_fields)) {
                foreach ($this->cols as $col_name => $col_params) {
                    $type = BimpTools::getArrayValueFromPath($col_params, 'type', '');
                    if ($type) {
                        $this->totals[$col_name] = 0;
                    }
                }
            }

            $rows = $data;
            $data = array();

            $curN = 0;
            $curP = 1;

            foreach ($rows as $key => $r) {
                foreach ($this->statFieldsFilters as $name => $filter) {
                    if (!isset($r[$name])) {
                        $r[$name] = 0;
                    }

                    $check = true;
                    if (isset($filter['min']) && $filter['min'] !== '') {
                        if ((float) $r[$name] < (float) $filter['min']) {
                            $check = false;
                            break;
                        }
                    }
                    if (isset($filter['max']) && $filter['max'] !== '') {
                        if ((float) $r[$name] > (float) $filter['max']) {
                            $check = false;
                            break;
                        }
                    }
                }

                if (!$check) {
                    $this->nbItems--;
                } else {
                    $curN++;
                    if ($n && $curN > $n) {
                        $curN = 0;
                        $curP++;
                    }

                    if ((int) $curP === (int) $p) {
                        $data[$key] = $r;
                    }

                    if ($this->params['total_row'] && !empty($total_fields)) {
                        foreach ($this->cols as $col_name => $col_params) {
                            if (isset($this->totals[$col_name]) && isset($r[$col_name])) {
                                $this->totals[$col_name] += (float) $r[$col_name];
                            }
                        }
                    }
                }
            }
        }

        // Calcul du nombre de pages: 
        $this->nbTotalPages = 1;

        if ($n > 0) {
            $this->nbTotalPages = (int) ceil($this->nbItems / $n);
        } else {
            $this->nbTotalPages = 1;
        }

        $this->params['n'] = $n;
        $this->params['p'] = $p;

        $current_bc = $prev_bc;

        $this->data = $data;

        return $data;
    }

    public function getSortableColsArray()
    {
        return array();
    }

    public function getCsvColOptionsInputs($light_export = false)
    {
        $rows = array();

        $this->groupBy = null;
        $this->cols = null;

        $this->groupBy = json_decode(BimpTools::getPostFieldValue('group_by', ''), 1);
        $this->fetchCols();

        $user_config_cols_options = array();
        if (BimpObject::objectLoaded($this->userConfig)) {
            $user_config_cols_options = $this->userConfig->getData('cols_options');
        }

        if (count($this->errors)) {
            return array();
        }

        foreach ($this->cols as $col_name => $col_params) {
            $label = BimpTools::getArrayValueFromPath($col_params, 'label', '');
            $input_name = 'col_' . $col_name . '_option';
            $content = '';

            $type = BimpTools::getArrayValueFromPath($col_params, 'type', '');

            if ($type) {
                $content = BimpInput::renderInput('select', $input_name, 'default', array(
                            'options'     => array(
                                'number' => 'Valeur numérique',
                                'string' => 'Valeur affichée'
                            ),
                            'extra_class' => 'col_option'
                ));
            } else {
                $field = BimpTools::getArrayValueFromPath($col_params, 'field', '');
                $child = BimpTools::getArrayValueFromPath($col_params, 'child', '');
                $instance = null;

                if ($child) {
                    if ($child === 'parent') {
                        $instance = $this->object->getParentInstance();
                    } else {
                        $instance = $this->object->config->getObject('', $child);
                    }
                } else {
                    $instance = $this->object;
                }

                if (is_a($instance, 'BimpObject')) {
                    if ($field == $instance->getPrimary()) {
                        $bc_field = new BC_Field($instance, $field);
                        $bc_field->errors = array();
                        $bc_field->params['type'] = 'id_object';
                        $bc_field->params['object'] = $instance;

                        $content = $bc_field->renderCsvOptionsInput($input_name, (isset($user_config_cols_options[$col_name]['csv_display']) ? $user_config_cols_options[$col_name]['csv_display'] : ''));
                    } elseif ($instance->field_exists($field)) {
                        $bc_field = new BC_Field($instance, $field);

                        if (!$label) {
                            $label = $bc_field->params['label'];
                        }

                        $content = $bc_field->renderCsvOptionsInput($input_name, (isset($user_config_cols_options[$col_name]['csv_display']) ? $user_config_cols_options[$col_name]['csv_display'] : ''));
                    } else {
                        $content = BimpRender::renderAlerts('Le champ "' . $col_params['field'] . '" n\'existe pas dans l\'objet "' . $instance->getLabel() . '"');
                    }
                } else {
                    $content = BimpRender::renderAlerts('Instance invalide');
                }
            }

            if (!$label) {
                $label = $col_name;
            }

            if (!$content) {
                $content = 'Valeur affichée';
            }

            $rows[] = array(
                'label'   => $label,
                'content' => $content
            );
        }

        return $rows;
    }

    // Rendus HTML: 

    public function renderSubStatsListParams()
    {
        $html = '';

        $html .= '<input type="hidden" name="param_sort_field" value="' . $this->params['sort_field'] . '"/>';
        $html .= '<input type="hidden" name="param_sort_way" value="' . $this->params['sort_way'] . '"/>';
        $html .= '<input type="hidden" name="param_sort_option" value="' . $this->params['sort_option'] . '"/>';
        $html .= '<input type="hidden" name="param_n" value="' . $this->params['n'] . '"/>';
        $html .= '<input type="hidden" name="param_p" value="' . $this->params['p'] . '"/>';

        return $html;
    }

    public function renderHtmlContent()
    {
        $html = '';

        if (count($this->errors)) {
            return parent::renderHtml();
        }

        if (!$this->object->can("view")) {
            return BimpRender::renderAlerts('Vous n\'avez pas la permission de voir ' . $this->object->getLabel('the_plur'));
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html .= $this->renderListParamsInputs();

        $left_content = $this->renderGroupByOptions();

        if (!is_null($this->params['filters_panel'])) {
            $left_content .= '<div class="listFiltersPanelContainer">';
            $left_content .= $this->renderFiltersPanel();
            $left_content .= '</div>';
        }

        if ($left_content) {
            $html .= '<div class="row">';
            $html .= '<div class="col-xs-12 col-sm-12 col-md-3 col-lg-3">';
            $html .= $left_content;
            $html .= '</div>';

            $html .= '<div class="col-xs-12 col-sm-12 col-md-9 col-lg-9">';
        }

        $html .= $this->renderActiveFilters();

        $html .= '<div id="' . $this->identifier . '_ajax_content" class="stats_list_ajax_content"></div>';

        if ($left_content) {
            $html .= '</div></div>';
        }

//        $html .= '<div class="ajaxResultContainer" id="' . $this->identifier . '_result"></div>';

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderListContent($rows_only = false)
    {
        $html = '';

        $rows = $this->getStatsItems();

        if (count($this->errors)) {
            return '';
        }

        if (!$rows_only) {
            $html .= '<div class="statsListTableContainer" data-sub_list_id="' . $this->identifier . '"';

            if ((int) $this->groupByIndex > 0) {
                $html .= ' data-sub_list_filters="' . htmlentities(json_encode($this->subListFilters)) . '"';
                $html .= ' data-sub_list_joins="' . htmlentities(json_encode($this->subListJoins)) . '"';
                $html .= ' data-group_by_index="' . htmlentities(json_encode($this->groupByIndex)) . '"';
            }

            $html .= '>';

            if ((int) $this->groupByIndex > 0) {
                $html .= '<div class="subStatsListParams">';
                $html .= $this->renderSubStatsListParams();
                $html .= '</div>';
            }
            $html .= '<table class="noborder objectlistTable statsListTable" style="border: none; min-width: ' . ($this->colspan * 80) . 'px" width="100%">';
            $html .= '<thead class="listTableHead">';

            $html .= $this->renderHeaderRow();
            $html .= $this->renderSearchRow();

            $html .= '</thead>';
            $html .= '<tbody class="listRows">';
        }

        if (!empty($rows)) {
            $html .= $this->renderRows($rows);
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="' . $this->colspan . '">';
            $html .= BimpRender::renderAlerts('Aucun résultat trouvé', 'warning');
            $html .= '</td>';
            $html .= '</tr>';
        }

        if (!$rows_only) {
            $html .= '</tbody>';
            $html .= '<tfoot>';

            $html .= $this->renderPaginationRow();

            $html .= '</tfoot>';
            $html .= '</table>';

            $html .= '</div>';
        }

        return $html;
    }

    public function renderGroupByOptions()
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = '';

        if (!empty($this->params['group_by_options'])) {
            $group_by_options = array();

            $data = array(
                'item_options' => 'section'
            );

            foreach ($this->params['group_by_options'] as $groupByOption) {
                if (is_string($groupByOption)) {
                    if ($this->object->field_exists($groupByOption)) {
                        $label = $this->object->getConf('fields/' . $groupByOption . '/label', $groupByOption);
                        $group_by_options[$groupByOption] = array(
                            'label' => $label,
                            'data'  => $data
                        );
                    }
                }
            }

            if (count($group_by_options)) {
                $panel_content = '<div id="' . $this->identifier . '_group_by_optons" class="stats_list_group_by_options">';

                $input = BimpInput::renderInput('select', 'group_by_add_value', $this->params['group_by'], array(
                            'options' => $group_by_options
                ));

                $items_options = array(
                    'section' => array('label' => 'Sections', 'input' => BimpInput::renderInput('toggle', 'item_option_input_name', 1))
                );

                $content = BimpInput::renderMultipleValuesInput($this->object, 'group_by', $input, $this->params['group_by'], '', 0, 1, 1, 'none', $items_options);
                $panel_content .= BimpInput::renderInputContainer('group_by', '', $content, '', 0, 1, '', array('values_field' => 'group_by'));

                $panel_content .= '</div>';

                $panel_content .= '<div class="buttonsContainer align-right">';
                $panel_content .= '<span class="btn btn-default" onclick="reloadObjectStatsList(\'' . $this->identifier . '\')">';
                $panel_content .= 'Appliquer' . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
                $panel_content .= '</span>';
                $panel_content .= '</div>';

                $html = BimpRender::renderPanel('Grouper les résulats par: ', $panel_content, '', array(
                            'type' => 'secondary',
                            'icon' => 'fas_object-group'
                ));
            }
        }

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderHeaderRow()
    {
        $html = '';

        if ($this->isOk() && count($this->cols)) {
            $html .= '<tr class="headerRow" data-sub_list_id="' . $this->identifier . '">';

            if ($this->params['total_row']) {
                $html .= '<th style="width: 45px; min-width: 45px"></th>';
            }

            $this->search = false;
            $default_sort_way = $this->params['sort_way'];
            $user_config_cols_options = array();

            if (BimpObject::objectLoaded($this->userConfig)) {
                $user_config_cols_options = $this->userConfig->getData('cols_options');
            }

            foreach ($this->cols as $col_name => $col_params) {
                $field_object = $this->object;

                if ($col_params['field'] && $col_params['child']) {
                    $field_object = $this->object->getChildObject($col_params['child']);
                    if (!is_a($field_object, 'BimpObject')) {
                        $field_object = null;
                    }
                }
                if (isset($user_config_cols_options[$col_name]['label'])) {
                    $col_params['label'] = $user_config_cols_options[$col_name]['label'];
                }
                if (!$col_params['label']) {
                    if ($col_params['field'] && !is_null($field_object)) {
                        $col_params['label'] = $field_object->config->get('fields/' . $col_params['field'] . '/label', ucfirst($col_name));
                    } else {
                        $col_params['label'] = ucfirst($col_name);
                    }
                }

                $sortable = BimpTools::getArrayValueFromPath($col_params, 'sortable', null);

                if (is_null($sortable)) {
                    $sortable = 0;
                    if ($col_params['field']) {
                        if (is_a($field_object, 'BimpObject')) {
                            if ($field_object->field_exists($col_params['field'])) {
                                $sortable = $field_object->getConf('fields/' . $col_params['field'] . '/sortable', 1, false, 'bool');
                            }
                        }
                    }
                }

                $html .= '<th';
                if (!is_null($col_params['width'])) {
                    $html .= ' width="' . $col_params['width'] . '"';
                }

                $html .= ' style="';
                if (!is_null($col_params['min_width'])) {
                    $html .= 'min-width: ' . $col_params['min_width'] . ';';
                }
                if (!is_null($col_params['max_width'])) {
                    $html .= 'max-width: ' . $col_params['max_width'] . ';';
                }
                $html .= '"';

                $html .= ' data-col_name="' . $col_name . '"';
                $html .= ' data-field_name="' . ($col_params['field'] ? $col_params['field'] : '') . '"';
                if ($col_params['child']) {
                    $html .= ' data-child="' . $col_params['child'] . '"';
                }
                $html .= '>';

                if ($sortable) {
                    $html .= '<span id="' . $col_name . '_sortTitle" class="sortTitle sorted-';

                    if (($col_params['field'] && $this->params['sort_field'] === $col_params['field']) || $this->params['sort_field'] === $col_name) {
                        $html .= strtolower($this->params['sort_way']);
                        if (!$this->params['positions_open']) {
                            $html .= ' active';
                        }
                    } else {
                        $html .= strtolower($default_sort_way);
                    }
                    $onclick_params = '';

                    if ($this->groupByIndex > 0) {
                        $onclick_params = '\'' . $this->base_list_id . '\', \'' . $col_name . '\', \'' . $this->identifier . '\'';
                    } else {
                        $onclick_params = '\'' . $this->identifier . '\', \'' . $col_name . '\'';
                    }

                    $html .= '" onclick="if (!$(this).hasClass(\'deactivated\')) { sortStatsList(' . $onclick_params . '); }">';

                    $html .= $col_params['label'] . '</span>';
                } else {
                    $html .= $col_params['label'];
                }
                $html .= '</th>';

                if (!$this->search && $col_params['field'] && !$col_params['child']) {
                    $search = $this->object->getConf('fields/' . $col_params['field'] . '/search', 1, false, 'any');
                    if (is_array($search) || (int) $search) {
                        $this->search = true;
                    }
                }
            }

            $tools_width = 64;
            $tools_html = '<div class="headerTools">';
            $tools_html .= '<span class="fa-spin loadingIcon"></span>';
            if ($this->search) {
                $tools_html .= '<span class="headerButton openSearchRowButton open-close action-' . ($this->params['search_open'] ? 'close' : 'open') . '"></span>';
                $tools_width += 44;
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

            $tools_html .= '<span class="headerButton refreshListButton bs-popover" data-list_identifier="' . $this->identifier . '"';
            $tools_html .= BimpRender::renderPopoverData('Actualiser la liste', 'left', false, '#' . $this->identifier);
            $tools_html .= '></span>';
            $tools_width += 32;

            $tools_html .= '</div>';

            $html .= '<th class="th_tools" style="min-width: ' . $tools_width . 'px">';
            $html .= $tools_html;
            $html .= '</th>';
        }

        $html .= '</tr>';

        return $html;
    }

    public function renderSearchRow()
    {
        if (!$this->search || !$this->isOk() || !count($this->cols)) {
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

//        $html .= '<td style="text-align: center"><i class="fa fa-search"></i></td>';

        if ($this->params['total_row']) {
            $html .= '<td style="width: 45px; min-width: 45px"></td>';
        }

        foreach ($this->cols as $col_name => $col_params) {
            $html .= '<td>';

            $type = BimpTools::getArrayValueFromPath($col_params, 'type', '');

            if ($type) {
                $html .= $this->renderStatFieldSearchInput($col_name, $col_params);
            } else {
                $extra_data = array();
                $field_object = $this->object;

                if ($col_params['child']) {
                    $extra_data['child'] = $col_params['child'];
                    $field_object = $this->object->getChildObject($col_params['child']);
                    if (!is_a($field_object, 'BimpObject')) {
                        $field_object = null;
                    }
                }

                if ($col_params['field'] && in_array($col_params['field'], BimpObject::$common_fields)) {
                    if (!is_null($field_object)) {
                        $html .= $field_object->getCommonFieldSearchInput($col_params['field']);
                    }
                } elseif ($col_params['field'] && !is_null($field_object)) {
                    $field = new BC_Field($field_object, $col_params['field'], true);
                    $html .= $field->renderSearchInput($extra_data);
                    unset($field);
                } elseif (!is_null($col_params['search']) && method_exists($this->object, 'get' . ucfirst($col_name) . 'SearchFilters')) {
                    $search_type = $col_params['search']['type'];
                    $content = BimpInput::renderInput($col_params['search']['input']['type'], 'search_' . $col_name, '', $col_params['search']['input']['options']);
                    $html .= BimpInput::renderSearchInputContainer('search_' . $col_name, $search_type, $col_params['search']['search_on_key_up'], 1, $content, $extra_data);
                }
            }
            $html .= '</td>';
        }

        $html .= '<td class="searchTools">';
        $html .= '<button type="button" class="btn btn-default statsListSearchResetButton">';
        $html .= BimpRender::renderIcon('fas_eraser', 'iconLeft') . 'Réinitialiser</button>';
        $html .= '</td>';
        $html .= '</tr>';

        $this->object->reset();

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderPaginationRow()
    {
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

        if ($this->params['pagination']) {
            $content .= '<div class="title">';
            $content .= 'Nombre d\'items par page';
            $content .= '</div>';

            $content .= '<div style="margin-bottom: 15px">';
            $content .= BimpInput::renderSwitchOptionsInput('select_n', array(
                        10  => '10', 25  => '25', 50  => '50', 100 => '100', 200 => '200'), $this->params['n'], $this->identifier . '_n');
            $content .= '</div>';
        }

        global $user;
        if (BimpObject::objectLoaded($user) && (int) $this->params['configurable']) {
            $content .= '<div class="title">';
            $content .= 'Paramètres utilisateur';
            $content .= '</div>';

            $values = array(
                'owner_type' => 2,
                'id_owner'   => $user->id,
                'list_name'  => $this->name,
            );

            BimpObject::loadClass('bimpuserconfig', 'StatsListConfig');
            $configs = StatsListConfig::getUserConfigsArray($user->id, $this->object, $this->name);

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
                    $content .= '<div style="margin-top: 5px;">';
                    $content .= '<button class="btn btn-default btn-small" onclick="' . $this->userConfig->getJsLoadModalForm('default', 'Edition de la configuration #' . $this->userConfig->id) . '" style="margin-right: 4px">';
                    $content .= BimpRender::renderIcon('fas_edit', 'iconLeft') . 'Editer';
                    $content .= '</button>';
//                    $content .= '<button class="btn btn-default btn-small" onclick="' . $this->userConfig->getJsLoadModalForm('cols_options', 'Configuration #' . $this->userConfig->id . ' - Options des colonnes') . '">';
//                    $content .= BimpRender::renderIcon('fas_columns', 'iconLeft') . 'Options des colonnes';
//                    $content .= '</button>';
                    $content .= '</div>';
                }

                $content .= '</div>';

                $content .= 'Nombre d\'éléments par page: <span class="bold">' . ((int) $values['nb_items'] ? $values['nb_items'] : BimpRender::renderIcon('fas_infinity')) . '</span><br/>';

//                $sortable_fields = $this->getSortableColsArray();
//                if (array_key_exists($values['sort_field'], $sortable_fields)) {
//                    $content .= 'Tri par défaut: <span class="bold">' . $sortable_fields[$values['sort_field']] . '</span><br/>';
//                    if ((string) $values['sort_option']) {
//                        $sort_options = $this->object->getSortOptionsArray($values['sort_field']);
//                        if (array_key_exists($values['sort_option'], $sort_options)) {
//                            $content .= 'Option de tri: <span class="bold">' . $sort_options[$values['sort_option']] . '</span><br/>';
//                        }
//                    }
//                }
//                $content .= 'Ordre de tri: <span class="bold">' . $this->userConfig->displayData('sort_way') . '</span><br/>';
//                $content .= 'Afficher les totaux: <span class="bold">' . ((int) $values['total_row'] ? 'OUI' : 'NON') . '</span>';
                $content .= '</div>';

                $userConfig = BimpObject::getInstance('bimpuserconfig', 'StatsListConfig');
                $onclick = 'loadBCUserConfigsModalList($(this), ' . $user->id . ', \'' . $this->identifier . '\', \'StatsListConfig\', \'Gestion des configurations de la liste\')';
            } else {
                $values['sort_field'] = $this->params['sort_field'];
                $values['sort_way'] = $this->params['sort_way'];
                $values['sort_option'] = $this->params['sort_option'];
                $values['nb_items'] = $this->params['n'];
                $values['total_row'] = (int) $this->params['total_row'];

                $userConfig = BimpObject::getInstance('bimpuserconfig', 'StatsListConfig');
                $onclick = $userConfig->getJsLoadModalForm('default', 'Nouvelle configuration de liste', array(
                    'fields' => array(
                        'name'           => '',
                        'obj_module'     => $this->object->module,
                        'obj_name'       => $this->object->object_name,
                        'component_name' => $this->name,
                        'owner_type'     => UserConfig::OWNER_TYPE_USER,
                        'id_owner'       => (int) $user->id,
                        'nb_items'       => $this->params['n'],
                        'total_row'      => $this->params['total_row'],
                        'is_default'     => 1
                    )
                ));
            }

            $content .= '<div style="margin-bottom: 15px; text-align: center">';
            if (count($configs) > 1) {
                $items = array();

                foreach ($configs as $id_config => $config_label) {
                    if ((int) $id_config === (int) $this->userConfig->id) {
                        continue;
                    }
                    $items[] = '<span class="btn btn-light-default" onclick="loadStatsListConfig($(this), ' . $id_config . ');">' . $config_label . '</span>';
                }

                $content .= BimpRender::renderDropDownButton('Charger', $items, array(
                            'icon'       => 'fas_user-cog',
                            'menu_right' => 1
                        )) . '<br/>';
            }

            $content .= BimpRender::renderButton(array(
                        'classes'     => array('btn', 'btn-default'),
                        'label'       => 'Editer les configurations',
                        'icon_before' => 'fas_pen',
                        'attr'        => array(
                            'onclick' => $onclick
                        )
            ));
            $content .= '</div>';
        }

        if ($this->params['enable_csv']) {
            $content .= '<div class="title">';
            $content .= 'Outils';
            $content .= '</div>';

            $content .= '<div style="text-align: center">';
            $content .= BimpRender::renderButton(array(
                        'classes'     => array('btn', 'btn-default'),
                        'label'       => 'Générer fichier CSV',
                        'icon_before' => 'fas_file-excel',
                        'attr'        => array(
                            'onclick' => $this->object->getJsActionOnclick('generateListCsv', array(
                                'list_id'   => $this->identifier,
                                'list_name' => $this->name,
                                'list_type' => static::$type,
                                'group_by'  => htmlentities(json_encode($this->groupBy)),
                                'file_name' => BimpTools::cleanStringForUrl($this->object->getLabel() . '_' . date('d-m-Y')),
                                    ), array(
                                'form_name'      => 'list_csv',
                                'on_form_submit' => 'function($form, extra_data) {return onGenerateStatsListCsvFormSubmit($form, extra_data);}'
                            ))
                        )
            ));
            $content .= '</div>';
        }

        if ($content) {
            $html .= '<div id="' . $this->identifier . '_parametersPopup" class="tinyPopup listPopup">';
            $html .= $content;
            $html .= '</div>';
        }

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderRows($rows)
    {
        $html = '';

        $nextGroupByLabel = '';
        $nextGroupByFiltersLabels = array();

        if (!is_null($this->nextGroupBy)) {
            $primary = $this->object->getPrimary();

            foreach ($this->nextGroupBy['fields'] as $idx => $gb) {
                if ($nextGroupByLabel) {
                    if ($idx >= (count($this->nextGroupBy['fields']) - 1)) {
                        $nextGroupByLabel .= ' et ';
                    } else {
                        $nextGroupByLabel .= ', ';
                    }
                }

                if ($gb['value'] === $primary) {
                    $nextGroupByLabel .= $this->object->getLabel();
                } else {
                    $nextGroupByLabel .= lcfirst($this->object->getConf('fields/' . $gb['value'] . '/label', 'champ: ' . $gb['value']));
                }
            }

            foreach ($this->nextGroupBy['filters'] as $filter_field) {
                $nextGroupByFiltersLabels[$filter_field] = $this->object->getConf('fields/' . $filter_field . '/label', $filter_field);
            }
        }

        foreach ($rows as $row) {
            $html .= '<tr class="statListItemRow">';

            if ((int) $this->params['total_row']) {
                $html .= '<td style="width: 45px; min-width: 45px;"></td>';
            }

            foreach ($this->cols as $col_name => $col_params) {
                $html .= '<td style="';
                if ($col_params['min_width']) {
                    $html .= 'min-width: ' . $col_params['min_width'] . ';';
                }
                if ($col_params['max_width']) {
                    $html .= 'max-width: ' . $col_params['max_width'] . ';';
                }
                $html .= '">';

                if (isset($row[$col_name])) {
                    $html .= $this->renderValue($row[$col_name], $col_params);
                }

                $html .= '</td>';
            }

            $html .= '<td>';

            $next_row_html = '';

            if (!is_null($this->nextGroupBy) && isset($this->nextGroupBy['fields']) && !empty($this->nextGroupBy['fields'])) {
                $filters = $this->subListFilters;
                $joins = $this->subListJoins;

                $nextGroupByTitle = $nextGroupByLabel;
                $nextGroupByTitleFiltersLabels = '';

                foreach ($this->nextGroupBy['filters'] as $filter_field) {
                    $filterDisplayedValue = '';
                    $filter_key = $this->object->getFieldSqlKey($filter_field, 'a', null, $filters, $joins);

                    if (isset($filters[$filter_key])) {
                        $this->object->set($filter_field, $filters[$filter_key]);
                        $filterDisplayedValue = $this->object->displayData($filter_field, 'default', false, true);
                        if (!$filterDisplayedValue) {
                            $filterDisplayedValue = $filters[$filter_key];
                        }
                    } elseif (isset($row[$filter_field]) && !is_null($row[$filter_field])) {
                        $filters[$filter_key] = $row[$filter_field];
                        $this->object->set($filter_field, $row[$filter_field]);
                        $filterDisplayedValue = $this->object->displayData($filter_field, 'default', false, true);
                        if (!$filterDisplayedValue) {
                            $filterDisplayedValue = $row[$filter_field];
                        }
                    } else {
                        $filters[$filter_key] = 'IS_NULL';
                    }

                    $nextGroupByTitleFiltersLabels .= ($nextGroupByTitleFiltersLabels ? ' - ' : '') . BimpTools::ucfirst($nextGroupByFiltersLabels[$filter_field]) . ' "' . $filterDisplayedValue . '"';
                }

                if ($nextGroupByTitleFiltersLabels) {
                    $nextGroupByTitle .= ' pour: ' . $nextGroupByTitleFiltersLabels;
                }

                $html .= $this->renderRowButton(array(
                    'label'   => 'Détails par ' . $nextGroupByLabel,
                    'icon'    => 'fas_bars',
                    'onclick' => 'loadObjectSubStatsList($(this), \'' . $this->identifier . '\', \'' . htmlentities(addslashes($nextGroupByTitle)) . '\', ' . htmlentities(json_encode($filters)) . ', ' . htmlentities(json_encode($joins)) . ', ' . $this->nextGroupBy['idx'] . ');'
                ));

                $next_row_html .= '<tr class="statList_subListRow" style="display: none">';
                $next_row_html .= '<td colspan="' . $this->colspan . '" class="subStatsListContainer" style="background-color: #EAEAEA!important;">';
                $next_row_html .= '</td>';
                $next_row_html .= '</tr>';
            }
            $html .= '</td>';

            $html .= '</tr>';

            if ($next_row_html) {
                $html .= $next_row_html;
            }
        }

        $html .= $this->renderTotalRow();

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
            $html .= '<th>Total</th>';

            foreach ($this->cols as $col_name => $col_params) {
                $html .= '<td>';
                if (isset($this->totals[$col_name])) {
                    $data_type = BimpTools::getArrayValueFromPath($col_params, 'data_type', '');
                    if ($data_type === 'money') {
                        $col_params['truncate'] = 1;
                    }

                    $html .= $this->renderValue($this->totals[$col_name], $col_params);
                }
                $html .= '</td>';
            }
            $html .= '<td></td>';
            $html .= '</tr>';
        }

        return $html;
    }

    public function renderValue($value, $col_params)
    {
        if (is_a($this->object, 'BimpObject')) {
            $primary = $this->object->getPrimary();
            if ($col_params['field'] === $primary && !BimpTools::getArrayValueFromPath($col_params, 'child', '')) {
                $instance = BimpCache::getBimpObjectInstance($this->object->module, $this->object->object_name, (int) $value);
                if (BimpObject::objectLoaded($instance)) {
                    return $instance->getLink();
                }
                return $value;
            }
        }

        $type = BimpTools::getArrayValueFromPath($col_params, 'type', '');

        if ($type) {
            $data_type = BimpTools::getArrayValueFromPath($col_params, 'data_type', '');

            switch ($data_type) {
                case 'int':
                case 'qty':
                    return $value;

                case 'float':
                case 'percent':
                    $decimals = BimpTools::getArrayValueFromPath($col_params, 'decimals', 2);
                    return BimpTools::displayFloatValue($value, $decimals) . ($data_type === 'percent' ? ' %' : '');

                case 'money':
                    $decimals = BimpTools::getArrayValueFromPath($col_params, 'decimals', 2);
                    $with_styles = BimpTools::getArrayValueFromPath($col_params, 'with_styles', false);
                    $truncate = BimpTools::getArrayValueFromPath($col_params, 'truncate', 0);
                    return BimpTools::displayMoneyValue($value, 'EUR', $with_styles, $truncate);

                default:
                    $field = BimpTools::getArrayValueFromPath($col_params, 'field', '');
                    if ($field && $this->object->field_exists($field)) {
                        $display_name = BimpTools::getArrayValueFromPath($col_params, 'display', 'default');
                        $this->object->set($field, $value);
                        return $this->object->displayData($field, $display_name, false);
                    }
                    break;
            }
        } elseif (is_a($this->object, 'BimpObject')) {
            $field = BimpTools::getArrayValueFromPath($col_params, 'field', '');

            if ($field) {
                $child = BimpTools::getArrayValueFromPath($col_params, 'child', '');
                $display_name = BimpTools::getArrayValueFromPath($col_params, 'display', 'default');

                if ($child) {
                    $obj = $this->object->getChildObject($child);
                } else {
                    $obj = $this->object;
                }

                $obj->set($field, $value);
                return $obj->displayData($field, $display_name, false);
            }
        }

        return $value;
    }

    public function renderStatFieldSearchInput($col_name, $col_params)
    {
        $data_type = BimpTools::getArrayValueFromPath($col_params, 'data_type', '');

        $input_options = array(
            'data' => array(
                'data_type' => 'number',
                'decimals'  => ($data_type === 'int' ? 0 : 2),
                'min'       => 'none',
                'max'       => 'none',
                'unsigned'  => 0
            )
        );

        // On récup. les params du champ: 
        $field = BimpTools::getArrayValueFromPath($col_params, 'field', '');
        if ($field && $this->object->field_exists($field)) {
            $input_options['data']['decimals'] = $this->object->getConf('fields/' . $field . '/decimals', ($data_type === 'int' ? 0 : 2));
            $input_options['data']['min'] = $this->object->getConf('fields/' . $field . '/min', 'none');
            $input_options['data']['max'] = $this->object->getConf('fields/' . $field . '/max', 'none');
            $input_options['data']['unsigned'] = $this->object->getConf('fields/' . $field . '/unsigned', 0);
        }

        // On surchage avec les paramètres de la colonne: 
        if (isset($col_params['decimals'])) {
            $input_options['data']['decimals'] = (int) $col_params['decimals'];
        }

        $input_name = 'search_' . $col_name;

        $content = '<div>';
        $input_options['addon_left'] = 'Min';
        $content .= BimpInput::renderInput('text', $input_name . '_min', null, $input_options);
        $content .= '</div>';

        $content .= '<div>';
        $input_options['addon_left'] = 'Max';
        $content .= BimpInput::renderInput('text', $input_name . '_max', null, $input_options);
        $content .= '</div>';

        return BimpInput::renderSearchInputContainer($input_name, 'values_range', 0, 1, $content, array());
    }

    public function renderCsvContent($separator, $col_options, $headers = true, $light_export = false, &$errors = array())
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $this->params['n'] = 0;
        $this->params['p'] = 1;

        $items = $this->getStatsItems();

        $this->setConfPath();

        $rows = '';

        if ($headers) {
            $line = '';
            $fl = true;
            foreach ($this->cols as $col_name => $col_params) {
                if (!(int) BimpTools::getArrayValueFromPath($col_params, 'available_csv', 1)) {
                    continue;
                }

                $label = $col_params['label'];
                if (!$label && $col_params['field']) {
                    $field_object = $this->object;
                    if ($col_params['child']) {
                        $field_object = $this->object->getChildObject($col_params['child']);
                        if (!is_a($field_object, 'BimpObject')) {
                            $field_object = null;
                        }
                    }
                    if (!is_null($field_object)) {
                        $label = $field_object->config->get('fields/' . $col_params['field'] . '/label', ucfirst($col_name));
                    }
                }
                if (!$label) {
                    $label = $col_name;
                }
                $line .= (!$fl ? $separator : '') . $label;
                $fl = false;
            }
            $rows .= $line . "\n";
        }

        if (empty($items)) {
            $current_bc = $prev_bc;
            return $rows;
        }

        $nb = 0;
        foreach ($items as $item) {
            $nb++;
            if ($nb == 2) {
                $cache_mem = BimpCache::$cache;
            } elseif ($nb > 2) {
                BimpCache::$cache = $cache_mem;
            }

            $line = '';
            $fl = true;
            foreach ($this->cols as $col_name => $col_params) {
                if (!(int) BimpTools::getArrayValueFromPath($col_params, 'available_csv', 1)) {
                    continue;
                }

                $content = '';

                if (isset($item[$col_name])) {
                    $option = BimpTools::getArrayValueFromPath($col_options, $col_name, '');
                    $type = BimpTools::getArrayValueFromPath($col_params, 'type', '');

                    if ($type) {
                        switch ($option) {
                            case 'string':
                                $content .= $this->renderValue($item[$col_name], $col_params);
                                break;

                            case 'number':
                            default:
                                $content .= $item[$col_name];
                                break;
                        }
                    } else {
                        $field = BimpTools::getArrayValueFromPath($col_params, 'field', '');
                        $child = BimpTools::getArrayValueFromPath($col_params, 'child', '');

                        if ($field) {
                            if ($child) {
                                if ($child === 'parent') {
                                    $obj = $this->object->getParentInstance();
                                } else {
                                    $obj = $this->object->getChildObject($child);
                                }
                            } else {
                                $obj = $this->object;
                            }

                            if (is_a($obj, 'BimpObject')) {
                                $obj->set($field, $item[$col_name]);

                                if ($field === $obj->getPrimary()) {
                                    $field->params['type'] = 'id_object';
                                    $field->params['object'] = $obj;
                                }

                                $field = new BC_Field($obj, $field);
                                $content = $field->getNoHtmlValue($option);
                            }
                        }
                    }

                    $content = str_replace(array('<br>', '<br/>', '<br />'), ' ', $content);
                    $content = strip_tags($content);
                    $content = html_entity_decode($content);
                    $content = str_replace($separator, '', $content);
                    $content = str_replace('"', '""', $content);
                }

                $line .= (!$fl ? $separator : '' ) . '"' . $content . '"';

                $fl = false;
            }

            $rows .= $line . "\n";
        }

        BimpCache::$cache = $cache_mem;

        $this->setConfPath();
        $current_bc = $prev_bc;
        return $rows;
    }
}
