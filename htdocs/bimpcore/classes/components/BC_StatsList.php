<?php

class BC_StatsList extends BC_List
{

    public $component_name = 'Liste statitique';
    public static $type = 'stats_list';
    public static $col_params = array(
        'label'   => array(),
        'type'    => array('default' => 'sum'),
        'field'   => array('required' => 1),
        'child'   => array(),
        'filters' => array('data_type' => 'array', 'compile' => 1)
    );
    public static $cols_types = array('sum', 'count', 'average');
    public $groupBy = null;

    public function __construct(BimpObject $object, $name = 'default', $id_parent = null, $title = null, $icon = null)
    {
        $this->params_def['group_by'] = array('data_type' => 'array', 'default' => array(), 'request' => true, 'json' => true);
        $this->params_def['group_by_options'] = array('data_type' => 'array', 'compile' => true, 'default' => array());
        $this->params_def['cols'] = array('type' => 'keys');

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

        parent::__construct($object, '', $name, 1, $id_parent, $title, $icon);

        $this->params['filters_panel_open'] = 1;

        $this->fetchCols();
        $this->fetchGroupBy();

        $current_bc = $prev_bc;
    }

    protected function fetchCols()
    {
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

        $this->cols = array();

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
                $this->cols[] = $col_name;
            }
        }
    }

    public function fetchGroupBy()
    {
        if (is_null($this->groupBy)) {
            $this->groupBy = array();
            if (BimpTools::isSubmit('group_by')) {
                $this->groupBy = BimpTools::getValue('group_by', array());
            }
        }
    }

    protected function fetchItems()
    {
        $this->items = array();
    }

    protected function getStatsItems($group_by = '', $extra_filters = array(), $override_params = array())
    {
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

        $group_by_key = '';
        if ($group_by) {
            $group_by_key = $this->object->getFieldSqlKey($group_by, 'a', null, $joins, $this->errors);

            if (count($this->errors)) {
                return array();
            }
        }

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

            if (method_exists($this->object, 'traiteFilters'))
                $this->object->traiteFilters($panelFilters);

            foreach ($panelFilters as $name => $filter) {
                $this->mergeFilter($name, $filter);
            }
        }

        $filters = $this->filters;
        if (!is_null($this->id_parent) && $this->id_parent != 0) {
            $parent_id_property = $this->object->getParentIdProperty();
            if ($parent_id_property) {
                $filters[$parent_id_property] = $this->id_parent;
            }
        }

        foreach ($extra_filters as $name => $filter) {
            $this->mergeFilter($name, $filter);
        }

        // Trie: 
        $order_by = $primary;
        $order_way = 'desc';
        $extra_order_by = null;
        $extra_order_way = null;

        if (isset($override_params['sort_field'])) {
            $order_by = $override_params['sort_field'];
        } elseif (!is_null($this->params['sort_field'])) {
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
                                $joins[$table] = array(
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
                                    $joins[$table] = array(
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

            $this->setConfPath();
        }

        if ($order_by === 'id') {
            $order_by = $primary;
        }

        if (isset($override_params['sort_way'])) {
            $order_way = $override_params['sort_way'];
        } elseif (isset($this->params['sort_way']) && $this->params['sort_way']) {
            $order_way = $this->params['sort_way'];
        }

        $n = $this->params['n'];
        if (isset($override_params['n'])) {
            $n = $override_params['n'];
        }

        $p = $this->params['p'];
        if (isset($override_params['p'])) {
            $p = $override_params['p'];
        }

        $nbItems = 0; //$this->object->getListCount($filters, $joins);
        $nbTotalPages = 1;

        if ($n > 0) {
            $nbTotalPages = (int) ceil($nbItems / $n);
        } else {
            $p = 1;
        }

        $this->final_filters = $filters;
        $this->final_joins = $joins;

        // Requête de base:         
        $sql = '';

        $fields = array();

        if ($group_by_key) {
            $fields[] = $group_by_key . ' as ' . $group_by;
        } else {
            $fields[] = 'a.' . $primary . ' as ' . $primary;
        }

        foreach ($this->cols as $col_name) {
            $col_params = $this->getColParams($col_name);
            $type = BimpTools::getArrayValueFromPath($col_params, 'type', '');
            $field = BimpTools::getArrayValueFromPath($col_params, 'field', '');
            $child = BimpTools::getArrayValueFromPath($col_params, 'child', null);

            if ($field && $this->object->field_exists($field)) {
                if (!isset($col_params['filters']) || empty($col_params['filters'])) {
                    $key = $this->object->getFieldSqlKey($field, 'a', $child, $joins, $this->errors);
                    if ($key) {
                        switch ($type) {
                            case 'sum':
                            case 'avg':
                                $fields[] = strtoupper($type) . '(' . $key . ') as ' . $col_name;
                                break;
                        }
                    }
                }
            }
        }

        if (count($this->errors)) {
            return array();
        }

        $data = array();
        $items = array();

        if (!empty($fields)) {
            $sql = BimpTools::getSqlSelect($fields, 'a');
            $sql .= BimpTools::getSqlFrom($table, $joins, 'a');
            $sql .= BimpTools::getSqlWhere($filters, 'a');

            if ($group_by_key) {
                $sql .= ' GROUP BY ' . $group_by_key;
            }

            $sql .= BimpTools::getSqlOrderBy($order_by, $order_way, 'a', $extra_order_by, $extra_order_way);
            $sql .= BimpTools::getSqlLimit($n, $p);

            if (BimpDebug::isActive('bimpcore/objects/print_list_sql') || BimpTools::isSubmit('list_sql')) {
                $plus = "";
                if (class_exists('synopsisHook'))
                    $plus = ' ' . synopsisHook::getTime();
                echo BimpRender::renderDebugInfo($sql, 'SQL Stats Liste Requête de base - Module: "' . $this->object->module . '" Objet: "' . $this->object->object_name . '" - ' . $plus);
            }

            $bdb = BimpCache::getBdb();
            $rows = $bdb->executeS($sql, 'array');

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    if ($group_by_key) {
                        $items[] = $r[$group_by];
                        $data[$r[$group_by]] = $r;
                    } else {
                        $items[] = $r[$primary];
                        $data[$r[$primary]] = $r;
                    }
                }
            } else {
                $this->errors[] = $bdb->db->lasterror();
            }
        }

        if (!empty($items)) {
            // Requêtes pour les champs avec filtres supplémentaires:

            foreach ($this->cols as $col_name) {
                $col_params = $this->getColParams($col_name);
                $type = BimpTools::getArrayValueFromPath($col_params, 'type', '');
                $field = BimpTools::getArrayValueFromPath($col_params, 'field', '');
                $child = BimpTools::getArrayValueFromPath($col_params, 'child', null);

                if ($field && $this->object->field_exists($field)) {
                    if (isset($col_params['filters']) && !empty($col_params['filters'])) {
                        $filters = $this->final_filters;
                        $joins = $this->final_joins;
                        $fields = array();

                        if ($group_by_key) {
                            $filters[$group_by_key] = array(
                                'in' => $items
                            );
                            $fields[] = $group_by_key . ' as ' . $group_by;
                        } else {
                            $filters['a.' . $primary] = array(
                                'in' => $items
                            );
                            $fields[] = 'a.' . $primary . ' as ' . $primary;
                        }

                        $key = $this->object->getFieldSqlKey($field, 'a', $child, $joins, $this->errors);
                        if ($key) {
                            switch ($type) {
                                case 'sum':
                                case 'avg':
                                    $fields[] = strtoupper($type) . '(' . $key . ') as ' . $col_name;
                                    break;
                            }

                            foreach ($col_params['filters'] as $name => $filter) {
                                $filters = BimpTools::mergeSqlFilter($filters, $name, $filter);
                            }

                            // Todo: req SQL... Ajouter résultat dans $data... 
                            $sql = BimpTools::getSqlSelect($fields, 'a');
                            $sql .= BimpTools::getSqlFrom($table, $joins, 'a');
                            $sql .= BimpTools::getSqlWhere($filters, 'a');

                            if ($group_by_key) {
                                $sql .= ' GROUP BY ' . $group_by_key;
                            }

                            $sql .= BimpTools::getSqlOrderBy($order_by, $order_way, 'a', $extra_order_by, $extra_order_way);
                            $sql .= BimpTools::getSqlLimit($n, $p);

                            $bdb = BimpCache::getBdb();
                            $rows = $bdb->executeS($sql, 'array');

                            if (is_array($rows)) {
                                foreach ($rows as $r) {
                                    if ($group_by_key) {
                                        if (isset($data[$r[$group_by]])) {
                                            $data[$r[$group_by]][$col_name] = $r[$col_name];
                                        }
                                    } else {
                                        if (isset($data[$r[$primary]])) {
                                            $data[$r[$primary]][$col_name] = $r[$col_name];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $current_bc = $prev_bc;

        return $data;
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

        if ($this->object->config->isDefined('stats_lists_cols/' . $col_name)) {
            $col_params = $this->fetchParams('stats_lists_cols/' . $col_name, static::$col_params);
            $col_overriden_params = $this->object->config->getCompiledParams($this->config_path . '/cols/' . $col_name);
            if (is_array($col_overriden_params)) {
                $col_params = $this->object->config->mergeParams($col_params, $col_overriden_params);
            }
        } else {
            $col_params = $this->fetchParams($this->config_path . '/cols/' . $col_name, static::$col_params);
        }

        $current_bc = $prev_bc;
        return $col_params;
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

        $html .= '<div id="' . $this->identifier . '_ajax_content" class="stats_list_ajax_content">';
//        $html .= $this->renderListContent();
        $html .= '</div>';

        if ($left_content) {
            $html .= '</div></div>';
        }

        $html .= '<div class="ajaxResultContainer" id="' . $this->identifier . '_result"></div>';

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderListContent()
    {
        $html = '';

        $group_by = '';
        if (!empty($this->groupBy)) {
            $group_by = $this->groupBy[0];
        }

        $html .= $this->getStatsItems($group_by['value']);

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
}
