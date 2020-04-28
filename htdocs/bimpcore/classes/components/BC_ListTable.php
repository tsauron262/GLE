<?php

class BC_ListTable extends BC_List
{

    public $component_name = 'Tableau';
    public static $type = 'list_table';
    public $search = false;
    public $rows = null;
    public $colspan = 0;
    public $cols = null;
    public $item_params = array(
        'update_btn'      => array('data_type' => 'bool', 'default' => 0),
        'edit_btn'        => array('data_type' => 'bool', 'default' => 0),
        'delete_btn'      => array('data_type' => 'bool', 'default' => 0),
        'page_btn'        => array('data_type' => 'bool', 'default' => 0),
        'inline_view'     => array(),
        'modal_view'      => array(),
        'edit_form'       => array('default' => 'default'),
        'edit_form_title' => array(),
        'extra_btn'       => array('data_type' => 'array', 'compile' => true),
        'row_style'       => array('default' => ''),
        'td_style'        => array('default' => '')
    );
    public $col_params = array(
        'show'          => array('data_type' => 'bool', 'default' => 1),
        'field'         => array('default' => ''),
        'child'         => array('default' => ''),
        'edit'          => array('data_type' => 'bool', 'default' => 0),
        'history'       => array('data_type' => 'bool', 'default' => 0),
        'available_csv' => array('data_type' => 'bool', 'default' => 1),
        'display'       => array('default' => ''),
        'label'         => array('default' => ''),
        'value'         => array('default' => ''),
        'true_value'    => array('default' => null),
        'width'         => array('default' => null),
        'min_width'     => array('default' => null),
        'max_width'     => array('default' => null),
        'hidden'        => array('data_type' => 'bool', 'default' => 0),
        'search_list'   => array('data_type' => 'array', 'compile' => true, 'default' => null),
        'field_name'    => array(),
        'search'        => array('type' => 'definitions', 'defs_type' => 'search', 'default' => null),
        'col_style'     => array('default' => ''),
        'has_total'     => array('data_type' => 'bool', 'default' => 0),
        'total_type'    => array('default' => null),
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
        $this->params_def['before_list_content'] = array('default' => '');
        $this->params_def['after_list_content'] = array('default' => '');
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

    protected function fetchCols()
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

        $rows = array();

        if (is_null($this->items) || !count($this->items)) {
            $this->rows = array();
        }

        $primary = $this->object->getPrimary();

        $this->setConfPath();

        $object_instance = $this->object;

        foreach ($this->items as $item) {
            $object = BimpCache::getBimpObjectInstance($this->object->module, $this->object->object_name, (int) $item[$primary], $this->parent);
            if (BimpObject::objectLoaded($object)) {
                $this->object = $object;
                $item_params = $this->fetchParams($this->config_path, $this->item_params);

                $row = array(
                    'params' => array(
                        'checkbox'       => (int) $object->getConf($this->config_path . '/item_checkbox', true, false, 'bool'),
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
                    } elseif ($object->isDolObject()) {
//                        $row['params']['url'] = BimpTools::getDolObjectUrl($object->dol_object, (int) $item[$primary]);
//                        $row['params']['page_btn_label'] = 'Afficher la fiche ' . $object->getLabel();
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
                foreach ($this->cols as $col_name) {
                    if ($row['params']['single_cell'] && $col_name !== $this->params['single_cell']['col']) {
                        continue;
                    }

                    $col_params = $this->getColParams($col_name);

                    $row['cols'][$col_name] = array(
                        'content'   => '',
                        'show'      => (int) $col_params['show'],
                        'hidden'    => (int) $col_params['hidden'],
                        'min_width' => $col_params['min_width'],
                        'max_width' => $col_params['max_width'],
                        'col_style' => $col_params['col_style'],
                    );

                    if (!(int) $row['cols'][$col_name]['show']) {
                        continue;
                    }

                    $field = null;

                    $has_total = (int) $col_params['has_total'];

                    if ($col_params['field']) {
                        if ($col_params['child']) {
                            $obj = $object->getChildObject($col_params['child']);
                            if (is_null($obj) || !is_a($obj, 'BimpObject')) {
                                $row['cols'][$col_name]['content'] = BimpRender::renderAlerts('Objet "' . $col_params['child'] . '" invalide');
                                continue;
                            }
                            if (!$obj->isLoaded()) {
                                $row['cols'][$col_name]['content'] = '';
                                continue;
                            }
                        } else {
                            $obj = $object;
                        }
                        $field = new BC_Field($obj, $col_params['field'], ($this->params['enable_edit'] && (int) $col_params['edit']));
                        $field->display_name = $col_params['display'];

                        if (isset($new_values[$col_params['field']])) {
                            $field->new_value = $new_values[$col_params['field']];
                        }

                        $row['cols'][$col_name]['content'] = $field->renderHtml();

                        if (!$has_total) {
                            $has_total = (int) $field->params['has_total'];
                        }
                    } elseif (isset($col_params['value'])) {
                        $row['cols'][$col_name]['content'] .= $col_params['value'];
                    }

                    if (!isset($this->totals[$col_name])) {
                        if ((int) $this->params['total_row'] && $has_total) {
                            $this->totals[$col_name] = array(
                                'data_type' => '',
                                'sql_key'   => '',
                                'value'     => 0
                            );
                            if (is_a($field, 'BC_Field')) {
                                $this->totals[$col_name]['data_type'] = $field->params['type'];
                            } elseif (!is_null($col_params['total_type'])) {
                                $this->totals[$col_name]['data_type'] = $col_params['total_type'];
                            }
                        }
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

        foreach ($this->totals as $col_name => $params) {
            $method_name = 'get' . ucfirst($col_name) . 'ListTotal';

            if (method_exists($this->object, $method_name)) {
                $this->totals[$col_name] = $this->object->{$method_name}($this->final_filters, $this->final_joins);
                continue;
            }

            $col_params = $this->getColParams($col_name);
            if (isset($col_params['field']) && $col_params['field']) {
                $child_name = ((isset($col_params['child']) && $col_params['child']) ? $col_params['child'] : null);
                $sqlKey = $this->object->getFieldSqlKey($col_params['field'], 'a', $child_name, $this->final_joins);
                if ($sqlKey) {
                    $fields[$sqlKey] = $col_name;
                }
            } elseif (method_exists($this->object, 'get' . ucfirst($col_name) . 'SqlKey')) {
                $sqlKey = $this->object->{'get' . ucfirst($col_name) . 'SqlKey'}($this->final_joins);
                if ($sqlKey) {
                    $fields[$sqlKey] = $col_name;
                }
            }
        }

        if (!empty($fields)) {
            $result = $this->object->getListTotals($fields, $this->final_filters, $this->final_joins);

            if (!empty($result)) {
                foreach ($fields as $key => $col_name) {
                    if (isset($result[0][$col_name])) {
                        $this->totals[$col_name]['value'] = $result[0][$col_name];
                    }
                }
            }
        }

        $current_bc = $prev_bc;
    }

    public function setSelectedRows($selected_rows)
    {
        $this->selected_rows = $selected_rows;
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
            $this->object->config->params['lists_cols'][$col_name] = $this->params['extra_cols'][$col_name];
        }

        if ($this->object->config->isDefined('lists_cols/' . $col_name)) {
            $col_params = $this->fetchParams('lists_cols/' . $col_name, $this->col_params);
            $col_overriden_params = $this->object->config->getCompiledParams($this->config_path . '/cols/' . $col_name);
            if (is_array($col_overriden_params)) {
                $col_params = $this->object->config->mergeParams($col_params, $col_overriden_params);
            }
        } else {
            $col_params = $this->fetchParams($this->config_path . '/cols/' . $col_name, $this->col_params);
        }

        $current_bc = $prev_bc;
        return $col_params;
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

        $html .= '<div class="before_list_content">';
        if ($this->params['before_list_content']) {
            $html .= $this->params['before_list_content'];
        }
        $html .= '</div>';

        $html .= $this->renderActiveFilters();

        $html .= '<table class="noborder objectlistTable" style="border: none; min-width: ' . ($this->colspan * 80) . 'px" width="100%">';
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

        $html .= '<div class="after_list_content">';
        if ($this->params['after_list_content']) {
            $html .= $this->params['after_list_content'];
        }
        $html .= '</div>';

        if (!is_null($this->params['filters_panel'])) {
            $html .= '</div>';
            $html .= '</div>';
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

            $user_config_cols_options = array();

            if (BimpObject::objectLoaded($this->userConfig)) {
                $user_config_cols_options = $this->userConfig->getData('cols_options');
            }

            foreach ($this->cols as $col_name) {
                $col_params = $this->getColParams($col_name);

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
                $sortable = ($this->params['enable_sort'] && ($col_params['field'] && !$col_params['child'] ? $this->object->getConf('fields/' . $col_params['field'] . '/sortable', 1, false, 'bool') : false));

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

                    if ($col_params['field'] && $this->params['sort_field'] === $col_params['field']) {
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
                $tools_html .= BimpRender::renderPopoverData('Actualiser la liste', 'left', false, '#' . $this->identifier);
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

        foreach ($this->cols as $col_name) {
            $html .= '<td>';
            $col_params = $this->getColParams($col_name);

            $extra_data = array();
            $field_object = $this->object;

            if ($col_params['child']) {
                $extra_data['child'] = $col_params['child'];
                $field_object = $this->object->getChildObject($col_params['child']);
                if (!is_a($field_object, 'BimpObject')) {
                    $field_object = null;
                }
            }

            if (in_array($col_params['field'], BimpObject::$common_fields)) {
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
            } elseif (!is_null($col_params['search_list']) && !is_null($col_params['field_name'])) {
                $input_name = 'search_' . $col_params['field_name'];
                $content = BimpInput::renderSearchListInputFromConfig($this->object, $this->config_path . '/cols/' . $col_name, $input_name, '', null);
                $html .= BimpInput::renderSearchInputContainer($input_name, 'field_input', 0, 1, $content, $extra_data);
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

            foreach ($this->cols as $col_name) {
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
        if (!$this->object->can("create")) {
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

            foreach ($this->cols as $col_name) {
                $col_params = $this->getColParams($col_name);

                $html .= '<td>';
                if (isset($col_params['field']) && $col_params['field'] && !in_array($col_params['field'], BimpObject::$common_fields)) {
                    $bc_field = new BC_Field($this->object, $col_params['field'], true);
                    $default_value = $bc_field->params['default_value'];
                    $bc_field->value = $default_value;
                    if ($bc_field->params['editable']) {
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

        // Liste complète: 

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
                            $onclick = false;
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
                $title = 'List filtrée';
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

        if ($this->params['pagination']) {
            $content .= '<div class="title">';
            $content .= 'Nombre d\'items par page';
            $content .= '</div>';

            $content .= '<div style="margin-bottom: 15px">';
            $content .= BimpInput::renderSwitchOptionsInput('select_n', array(
                        10 => '10', 20 => '20', 30 => '30', 40 => '40', 50 => '50'), $this->params['n'], $this->identifier . '_n');
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

            $configs = ListConfig::getUserConfigsArray($user->id, $this->object, static::$type, $this->name);

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
                    $content .= '<div style="margin-top: 5px; text-align: right">';
                    $content .= '<button class="btn btn-default btn-small" onclick="' . $this->userConfig->getJsLoadModalForm('default', 'Edition de la configuration #' . $this->userConfig->id) . '" style="margin-right: 4px">';
                    $content .= BimpRender::renderIcon('fas_edit', 'iconLeft') . 'Editer';
                    $content .= '</button>';

                    $content .= '<button class="btn btn-default btn-small" onclick="' . $this->userConfig->getJsLoadModalForm('cols_options', 'Configuration #' . $this->userConfig->id . ' - Options des colonnes') . '">';
                    $content .= BimpRender::renderIcon('fas_columns', 'iconLeft') . 'Options des colonnes';
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

                $userConfig = BimpObject::getInstance('bimpcore', 'ListConfig');
                $onclick = 'loadListUserConfigsModalList($(this), \'' . $this->identifier . '\', ' . $user->id . ')';
            } else {
                $values['sort_field'] = $this->params['sort_field'];
                $values['sort_way'] = $this->params['sort_way'];
                $values['sort_option'] = $this->params['sort_option'];
                $values['nb_items'] = $this->params['n'];
                $values['total_row'] = (int) $this->params['total_row'];

                $userConfig = BimpObject::getInstance('bimpcore', 'ListConfig');
                $onclick = $userConfig->getJsLoadModalForm('default', 'Nouvelle configuration de liste', array(
                    'fields' => array(
                        'name'        => '',
                        'obj_module'  => $this->object->module,
                        'obj_name'    => $this->object->object_name,
                        'list_type'   => static::$type,
                        'list_name'   => $this->name,
                        'owner_type'  => ListConfig::TYPE_USER,
                        'nb_items'    => $this->params['n'],
                        'sort_field'  => $this->params['sort_field'],
                        'sort_way'    => $this->params['sort_way'],
                        'sort_option' => $this->params['sort_option'],
                        'total_row'   => $this->params['total_row'],
                        'is_default'  => 1
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
                    $items[] = '<span class="btn btn-light-default" onclick="loadListConfig($(this), ' . $id_config . ');">' . $config_label . '</span>';
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
                                'file_name' => BimpTools::cleanStringForUrl($this->object->getLabel() . '_' . date('d-m-Y')),
                                    ), array(
                                'form_name'      => 'list_csv',
                                'on_form_submit' => 'function($form, extra_data) {return onGenerateCsvFormSubmit($form, extra_data);}'
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

        if (count($this->rows)) {
            foreach ($this->rows as $id_object => $row) {
                $id_object = (int) $id_object;
                $item_params = $row['params']['item_params'];

                if (in_array((int) $id_object, $this->selected_rows)) {
                    $selected = true;
                } else {
                    $selected = false;
                }

                $html .= '<tr class="' . $this->object->object_name . '_row objectListItemRow';
                if (isset($this->new_values[$id_object]) && count($this->new_values[$id_object])) {
                    $html .= ' modified';
                }
                if ($selected) {
                    $html .= ' selected';
                }
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
                    if ((int) $row['params']['checkbox']) {
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

                $this->setConfPath('cols');
                foreach ($this->cols as $col_name) {
                    if ($row['params']['single_cell'] && $col_name !== $this->params['single_cell']['col']) {
                        continue;
                    }

                    $html .= '<td style="';
                    if ($row['cols'][$col_name]['hidden']) {
                        $html .= 'display: none;';
                    }
                    if ($row['cols'][$col_name]['min_width']) {
                        $html .= 'min-width: ' . $row['cols'][$col_name]['min_width'] . ';';
                    }
                    if ($row['cols'][$col_name]['max_width']) {
                        $html .= 'max-width: ' . $row['cols'][$col_name]['max_width'] . ';';
                    }
                    if ($item_params['td_style']) {
                        $html .= $item_params['td_style'];
                    }
                    if ($row['cols'][$col_name]['col_style']) {
                        $html .= $row['cols'][$col_name]['col_style'];
                    }
                    $html .= '"' . ($row['params']['single_cell'] ? ' colspan="' . count($this->cols) . '"' : '') . '>';
                    if ((int) $row['cols'][$col_name]['show']) {
                        $html .= (isset($row['cols'][$col_name]['content']) ? $row['cols'][$col_name]['content'] : '');
                    }
                    $html .= '</td>';
                }

                $rowButtons = array();
                $this->setConfPath();

                if ((int) $row['params']['canEdit']) {
                    if ($this->params['enable_edit'] && (int) $item_params['update_btn']) {
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

                if (count($item_params['extra_btn'])) {
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
            foreach ($this->cols as $col_name) {
                $col_params = $this->getColParams($col_name);

                if (!(int) $col_params['show'] || (int) $col_params['hidden'] || !(int) $col_params['available_csv']) {
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

        if (is_null($this->items) || !count($this->items)) {
            $current_bc = $prev_bc;
            return $rows;
        }

        $nb = 0;
        foreach ($this->items as $item) {
            $nb++;
            if ($nb == 2) {
                $cache_mem = BimpCache::$cache;
            } elseif ($nb > 2) {
                BimpCache::$cache = $cache_mem;
            }

            $line = '';
            $object = BimpCache::getBimpObjectInstance($this->object->module, $this->object->object_name, (int) $item[$primary], $this->parent);
            if (BimpObject::objectLoaded($object)) {
                $this->object = $object;

                $fl = true;
                foreach ($this->cols as $col_name) {
                    $col_params = $this->getColParams($col_name);

                    if (!(int) $col_params['show'] || (int) $col_params['hidden'] || !(int) $col_params['available_csv']) {
                        continue;
                    }

                    $content = '';

                    if ($col_params['field']) {
                        if ($col_params['child']) {
                            if ($col_params['child'] === 'parent') {
                                $obj = $object->getParentInstance();
                            } else {
                                $obj = $object->getChildObject($col_params['child']);
                            }
                        } else {
                            $obj = $object;
                        }

                        if (is_a($obj, 'BimpObject') && BimpObject::objectLoaded($obj)) {
                            $field = new BC_Field($obj, $col_params['field']);
                            $content = $field->getNoHtmlValue(isset($col_options[$col_name]) ? $col_options[$col_name] : '');
                        }
                    } elseif (isset($col_params['true_value']) && !is_null($col_params['true_value'])) {
                        $content = $col_params['true_value'];
                    } elseif (isset($col_params['value'])) {
                        $content = $col_params['value'];
                    }

                    $content = str_replace(array('<br>', '<br/>', '<br />'), ' ', $content);
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

        BimpCache::$cache = $cache_mem;

        $this->object = $object_instance;

        if (!is_null($this->parent)) {
            $this->object->parent = $this->parent;
        }

        $this->setConfPath();
        $current_bc = $prev_bc;
        return $rows;
    }
}
