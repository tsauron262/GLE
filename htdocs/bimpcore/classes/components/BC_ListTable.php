<?php

class BC_ListTable extends BC_List
{

    public static $type = 'list_table';
    public $search = false;
    protected $rows = null;
    protected $colspan = 0;
    protected $cols = null;
    public $item_params = array(
        'update_btn'  => array('data_type' => 'bool', 'default' => 0),
        'edit_btn'    => array('data_type' => 'bool', 'default' => 0),
        'delete_btn'  => array('data_type' => 'bool', 'default' => 0),
        'page_btn'    => array('data_type' => 'bool', 'default' => 0),
        'inline_view' => array(),
        'modal_view'  => array(),
        'edit_form'   => array('default' => 'default'),
        'extra_btn'   => array('data_type' => 'array', 'compile' => true),
        'row_style'   => array('default' => '')
    );
    public $col_params = array(
        'show'      => array('data_type' => 'bool', 'default' => 1),
        'field'     => array('default' => ''),
        'edit'      => array('data_type' => 'bool', 'default' => 0),
        'history'   => array('data_type' => 'bool', 'default' => 0),
        'display'   => array('default' => ''),
        'label'     => array('default' => ''),
        'value'     => array('default' => ''),
        'width'     => array('default' => null),
        'min_width' => array('default' => null),
        'max_width' => array('default' => null),
        'hidden'    => array('data_type' => 'bool', 'default' => 0)
    );

    public function __construct(BimpObject $object, $name = 'default', $level = 1, $id_parent = null, $title = null, $icon = null)
    {
        $this->params_def['checkboxes'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['add_object_row'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['positions'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['bulk_actions'] = array('type' => 'definitions', 'defs_type' => 'button', 'multiple' => true);
        $this->params_def['cols'] = array('type' => 'keys');
        $this->params_def['extra_cols'] = array('data_type' => 'array');
        $this->params_def['enable_search'] = array('data_type' => 'bool', 'default' => 1);

        $path = null;

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

        parent::__construct($object, $path, $name, $level, $id_parent, $title, $icon);

        $this->fetchCols();
        $this->colspan = 2 + count($this->cols);
    }

    protected function fetchCols()
    {
        $prev_path = $this->object->config->current_path;
        $this->setConfPath();

        //        $listConfig = BimpObject::getInstance('BimpCore', 'ListConfig');
//
//        global $user;
//
//        if (!is_null($listConfig) && isset($user->id) && $user->id) {
//            if ($listConfig->find(array(
//                        'module_name' => $object->module,
//                        'object_name' => $object->object_name,
//                        'list_name'   => $list_name,
//                        'id_user'     => $user->id
//                    ))) {
//                $this->user_config = null; // Todo
//            }
//        }

        $this->cols = array();

        foreach ($this->params['cols'] as $col_name) {
            $show = (int) $this->object->getCurrentConf('cols/' . $col_name . '/show', 1, false, 'bool');
            if ($show) {
                $this->cols[] = $col_name;
            }
        }

        if (count($this->params['extra_cols'])) {
            foreach ($this->params['extra_cols'] as $col_name => $col_params) {
                if (isset($col_params['show']) && !(bool) $col_params['show']) {
                    continue;
                }
                $this->cols[] = $col_name;
            }
            $this->object->config->addParams($this->config_path . '/cols', $this->params['extra_cols']);
        }

        $this->object->config->setCurrentPath($prev_path);
    }

    protected function fetchRows()
    {
        if (is_null($this->items)) {
            $this->fetchItems();
        }

//        if ($this->object->object_name === 'BMP_TypeMontant') {
//            echo '<pre>';
//            print_r($this->items);
//            exit;
//        }

        $rows = array();

        if (is_null($this->items) || !count($this->items)) {
            $this->rows = array();
        }

        $primary = $this->object->getPrimary();

        foreach ($this->items as $item) {
            $this->object->reset();
            $row = array();
            if ($this->object->fetch((int) $item[$primary])) {
                $new_values = isset($this->new_values[(int) $item[$primary]]) ? $this->new_values[(int) $item[$primary]] : array();
                if ($this->params['positions']) {
                    $row['position'] = $this->object->getData('position');
                    if (is_null($row['position'])) {
                        $row['position'] = 0;
                    }
                }
                foreach ($this->cols as $col_name) {
                    $content = '';
                    if ($this->setConfPath('cols/' . $col_name)) {
                        $show = (bool) $this->object->getCurrentConf('show', true, false, 'bool');
                        if (!$show) {
                            continue;
                        }
                        $col_params = $this->fetchParams($this->config_path . '/cols/' . $col_name, $this->col_params);


                        if ($col_params['field']) {
                            $field = new BC_Field($this->object, $col_params['field'], $col_params['edit']);
                            $field->display_name = $col_params['display'];

                            if (isset($new_values[$col_params['field']])) {
                                $field->new_value = $new_values[$col_params['field']];
                            }

                            $content = $field->renderHtml();
                        } else if (isset($col_params['value']) && $col_params['value']) {
                            $content .= $col_params['value'];
                        }
                    }

                    $row[$col_name] = $content;
                }
                $rows[$item[$primary]] = $row;
                $this->object->reset();
            }
        }

        if (!is_null($this->id_parent)) {
            $this->object->setIdParent($this->id_parent);
        }

        $this->setConfPath();

        $this->rows = $rows;
        if (method_exists($this->object, 'listRowsOverride')) {
            $this->object->listRowsOverride($this->name, $this->rows);
        }
    }

    // Rendus HTML:

    public function renderHtmlContent()
    {
        $html = '';

        $html .= '<div class="objectViewContainer" style="display: none"></div>';
        $html .= '<div class="objectFormContainer" style="display: none"></div>';

        $html .= $this->renderListParamsInputs();

        $html .= '<table class="noborder objectlistTable" style="border: none; min-width: ' . ($this->colspan * 80) . 'px" width="100%">';
        $html .= '<thead>';

        $html .= $this->renderHeaderRow();
        $html .= $this->renderSearchRow();
        $html .= $this->renderAddObjectRow();

        $html .= '</thead>';
        $html .= '<tbody class="listRows">';

        $html .= $this->renderRows();

        $html .= '</tbody>';

        $html .= '<tfoot>';
        $html .= $this->renderPaginationRow();
        $html .= '</tfoot>';

        $html .= '</table>';
        $html .= '<div class="ajaxResultContainer" id="' . $this->identifier . '_result"></div>';

        return $html;
    }

    public function renderHtmlFooter()
    {
        return $this->renderBulkActions();
    }

    public function renderHeaderRow()
    {
        $html = '';

        $this->setConfPath();
        if ($this->isOk() && count($this->cols)) {
            $this->search = false;
            $default_sort_way = $this->params['sort_way'];

            $html .= '<tr class="headerRow">';

            $html .= '<th class="th_checkboxes" width="40px" style="text-align: center">';
            if ($this->params['checkboxes']) {
                $html .= '<input type="checkbox" id="' . $this->identifier . '_checkall" onchange="toggleCheckAll(\'' . $this->identifier . '\', $(this));"/>';
            }
            $html .= '</th>';

            if ($this->params['positions']) {
                $html .= '<th class="positionHandle"></th>';
            }

            foreach ($this->cols as $col_name) {
                if ($this->setConfPath('cols/' . $col_name)) {
                    $col_params = $this->fetchParams($this->config_path . '/cols/' . $col_name, $this->col_params);

                    if (!$col_params['label']) {
                        if ($col_params['field']) {
                            $col_params['label'] = $this->object->config->get('fields/' . $col_params['field'] . '/label', ucfirst($col_name));
                        } else {
                            $col_params['label'] = ucfirst($col_name);
                        }
                    }
                    $sortable = ($col_params['field'] ? $this->object->getConf('fields/' . $col_params['field'] . '/sortable', 1, false, 'bool') : false);

                    $html .= '<th';
                    if (!is_null($col_params['width'])) {
                        $html .= ' width="' . $col_params['width'] . '"';
                    }
                    if (!is_null($col_params['max_width']) || !is_null($col_params['min_width'])) {
                        $html .= ' style="';
                        if (!is_null($col_params['min_width'])) {
                            $html .= 'min-width: ' . $col_params['min_width'] . ';';
                        }
                        if (!is_null($col_params['max_width'])) {
                            $html .= 'max-width: ' . $col_params['max_width'] . ';';
                        }
                        $html .= '"';
                    }

                    $html .= ' data-col_name="' . $col_name . '"';
                    $html .= ' data-field_name="' . ($col_params['field'] ? $col_params['field'] : '') . '"';
                    $html .= '>';

                    if ($sortable) {
                        $html .= '<span id="' . $col_name . '_sortTitle" class="sortTitle sorted-';

                        if ($col_params['field'] && $this->params['sort_field'] === $col_params['field']) {
                            $html .= strtolower($this->params['sort_way']);
                            $html .= ' active';
                        } else {
                            $html .= strtolower($default_sort_way);
                        }
                        $html .= '" onclick="if (!$(this).hasClass(\'deactivated\')) { sortList(\'' . $this->identifier . '\', \'' . $col_name . '\'); }">';

                        $html .= $col_params['label'] . '</span>';
                    } else {
                        $html .= $col_params['label'];
                    }
                    $html .= '</th>';

                    if (!$this->search && $col_params['field']) {
                        $search = $this->object->getConf('fields/' . $col_params['field'] . '/search', 1, false, 'any');
                        if (is_array($search) || (int) $search) {
                            $this->search = true;
                        }
                    }
                }
            }

            $this->setConfPath();

            $html .= '<th class="th_tools">';
            $html .= '<div class="headerTools">';
            if ($this->search && $this->params['enable_search']) {
                $html .= '<span class="headerButton openSearchRowButton open-close action-open"></span>';
            }
            if ($this->params['add_object_row']) {
                $html .= '<span class="headerButton openAddObjectRowButton open-close action-open"></span>';
            }
            if ($this->params['positions']) {
                $html .= '<span class="headerButton activatePositionsButton open-close action-open"></span>';
            }
            if ($this->params['checkboxes']) {
                $html .= '<span class="headerButton displayPopupButton openBulkActionsPopupButton"';
                $html .= ' data-popup_id="' . $this->identifier . '_bulkActionsPopup"></span>';
                $html .= $this->renderBulkActionsPopup();
            }
            $html .= '<span class="headerButton displayPopupButton openParametersPopupButton"';
            $html .= ' data-popup_id="' . $this->identifier . '_parametersPopup"></span>';
            $html .= $this->renderParametersPopup();
            $html .= '</div>';
            $html .= '</th>';

            $html .= '</tr>';
        }

        $this->setConfPath();
        return $html;
    }

    public function renderSearchRow()
    {
        if (!$this->search || !$this->params['enable_search'] || !$this->isOk() || !count($this->cols)) {
            return '';
        }

        if (!is_null($this->id_parent)) {
            $this->object->setIdParent($this->id_parent);
        }

        $html = '';

        $html .= '<tr id="' . $this->identifier . '_searchRow" class="listSearchRow"';
        $html .= ' data-list_name="' . $this->name . '"';
        $html .= ' data-module_name="' . $this->object->module . '"';
        $html .= ' data-object_name="' . $this->object->object_name . '">';

        $html .= '<td style="text-align: center"><i class="fa fa-search"></i></td>';

        if ($this->params['positions']) {
            $html .= '<td class="positionHandle"></td>';
        }

        foreach ($this->cols as $col_name) {
            if ($this->setConfPath('cols/' . $col_name)) {
                $col_params = $this->fetchParams($this->config_path . '/cols/' . $col_name, $this->col_params);
                $html .= '<td>';
                if (in_array($col_params['field'], BimpObject::$common_fields)) {
                    $html .= $this->object->getCommonFieldSearchInput($col_params['field']);
                } elseif ($col_params['field']) {
                    $field = new BC_Field($this->object, $col_params['field'], true);
                    $html .= $field->renderSearchInput();
                    unset($field);
                }
                $html .= '</td>';
            }
        }

        $html .= '<td class="searchTools">';
        $html .= '<button type="button" class="btn btn-default" onclick="resetListSearchInputs(\'' . $this->identifier . '\')">';
        $html .= '<i class="fa fa-eraser iconLeft"></i>Réinitialiser</span>';
        $html .= '</td>';
        $html .= '</tr>';

        $this->setConfPath();
        $this->object->reset();

        return $html;
    }

    public function renderAddObjectRow()
    {
        $html = '';

        $this->object->reset();

        if (!is_null($this->id_parent)) {
            $this->object->setIdParent($this->id_parent);
        }

        if ((int) $this->params['add_object_row'] && !is_null($this->config_path)) {
            $html .= '<tr id="' . $this->identifier . '_addObjectRow" class="addObjectRow inputsRow">';
            $html .= '<td><i class="fa fa-plus-circle"></i></td>';

            if ($this->params['positions']) {
                $html .= '<td class="positionHandle"></td>';
            }

            if (!is_null($this->id_parent)) {
                $parent_id_property = $this->object->getParentIdProperty();
                if (!is_null($parent_id_property)) {
                    $html .= '<td style="display: none">';
                    $html .= '<div class="inputContainer" data-field_name="' . $parent_id_property . '"';
                    $html .= ' data-default_value="' . $this->id_parent . '"';
                    $html .= ' id="' . $this->object->object_name . '_' . $parent_id_property . '_addInputContainer">';
                    $html .= '<input type="hidden" name="' . $parent_id_property . '" ';
                    $html .= 'value="' . $this->id_parent . '"/>';
                    $html .= '</div>';
                    $html .= '</td>';
                }
            }

            foreach ($this->cols as $col_name) {
                $this->setConfPath('cols/' . $col_name);
                $field = $this->fetchParam('field', $this->col_params, $this->config_path . '/cols/' . $col_name);

                $html .= '<td>';
                if ($field && !in_array($field, BimpObject::$common_fields)) {
                    $field = new BC_Field($this->object, $field, true);
                    $default_value = $field->params['default_value'];
                    $field->value = $default_value;
                    if ($field->params['editable']) {
                        $html .= $field->renderHtml();
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
        $this->setConfPath();

        return $html;
    }

    public function renderPaginationRow()
    {
        $hide = (is_null($this->nbItems) || ($this->params['n'] <= 0) || ($this->params['n'] >= $this->nbItems));

        $html = '<tr class="paginationContainer"' . ($hide ? ' style="display: none"' : '') . '>';
        $html .= '<td colspan="' . $this->colspan . '" style="padding: 15px 10px;">';
        $html .= '<div id="' . $this->identifier . '_pagination" class="listPagination">';
        $html .= $this->renderPagination();
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';

        return $html;
    }

    public function renderBulkActions()
    {
        $html = '';

        if (count($this->params['bulk_actions']) && (int) $this->params['checkboxes']) {
            $buttons = array();

            foreach ($this->params['bulk_actions'] as $idx => $action_params) {
                $button = null;
                $label = isset($action_params['label']) ? $action_params['label'] : '';
                $onclick = isset($action_params['onclick']) ? $action_params['onclick'] : '';
                $icon = isset($action_params['icon']) ? $action_params['icon'] : '';
                $onclick = str_replace('list_id', $this->identifier, $onclick);
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

            $title = BimpTools::ucfirst($this->object->getLabel('name_plur')) . ' sélectionné' . ($this->object->isLabelFemale() ? 'e' : '') . 's';
            $html .= BimpRender::renderDropDownButton($title, $buttons, array(
                        'icon' => 'check-square-o'
            ));
        }

        $buttons = array();
        $buttons[] = BimpRender::renderButton(array(
                    'classes'     => array('btn', 'btn-light-default'),
                    'label'       => 'Enregistrer toutes les modifications',
                    'attr'        => array(
                        'type'    => 'button',
                        'onclick' => 'saveAllRowsModifications(\'' . $this->identifier . '\', $(this))'
                    ),
                    'icon_before' => 'save'
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
                    'icon' => 'edit'
        ));
        $html .= '</span>';
        $this->setConfPath();
        return $html;
    }

    public function renderBulkActionsPopup()
    {
        $html = '';

        $html .= '<div id="' . $this->identifier . '_bulkActionsPopup" class="tinyPopup listPopup">';
        $html .= '<div class="title">';
        $html .= BimpTools::ucfirst($this->object->getLabel('name_plur')) . ' sélectionné' . ($this->object->isLabelFemale() ? 'e' : '') . 's';
        $html .= '</div>';

        foreach ($this->params['bulk_actions'] as $idx => $action_params) {
            $label = isset($action_params['label']) ? $action_params['label'] : '';
            $onclick = isset($action_params['onclick']) ? $action_params['onclick'] : '';
            $icon = isset($action_params['icon']) ? $action_params['icon'] : '';

            if ($label && $onclick) {
                $html .= '<div><span class="btn';
                $onclick = str_replace('list_id', $this->identifier, $onclick);
                $html .= '" onclick="' . $onclick . '">';
                if ($icon) {
                    $html .= '<i class="fa fa-' . $icon . ' left"></i>';
                }
                $html .= $label . '</span></div>';
            }
        }
        $html .= '</div>';

        $this->setConfPath();
        return $html;
    }

    public function renderParametersPopup()
    {
        $html = '';
        $html .= '<div id="' . $this->identifier . '_parametersPopup" class="tinyPopup listPopup">';

        $html .= '<div class="title">';
        $html .= 'Nombre d\'items par page';
        $html .= '</div>';

        $html .= BimpInput::renderSwitchOptionsInput('select_n', array(
                    10  => '10', 25  => '25', 50  => '50', 100 => '100', 250 => '250', 500 => '500', 0   => 'Tout'), $this->params['n'], $this->identifier . '_n');

        $html .= '</div>';

        $this->setConfPath();
        return $html;
    }

    public function renderRowButton($btn_params)
    {
        $html = '';
        $tag = isset($btn_params['tag']) ? $btn_params['tag'] : 'span';
        $html .= '<' . $tag . ' class="rowButton' . (isset($btn_params['class']) ? ' ' . $btn_params['class'] : '');

        if (isset($btn_params['label'])) {
            $html .= ' bs-popover"';
            $html .= ' data-toggle="popover"';
            $html .= ' data-trigger="hover"';
            $html .= ' data-placement="top"';
            $html .= ' data-content="' . $btn_params['label'];
        }
        if (isset($btn_params['onclick'])) {
            $html .= '" onclick="' . $btn_params['onclick'];
        }

        $html .= '"';

        if (isset($btn_params['attrs'])) {
            $html .= BimpRender::displayTagAttrs($btn_params['attrs']);
        }

        $html .= '>';
        if (isset($btn_params['icon'])) {
            $html .= '<i class="fa fa-' . $btn_params['icon'] . '"></i>';
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

        if (is_null($this->rows)) {
            $this->fetchRows();
        }

        if (count($this->rows)) {
            foreach ($this->rows as $id_object => $row) {
                $this->object->reset();

                if (is_numeric($id_object)) {
                    $this->object->fetch((int) $id_object);
                }

                $item_params = $this->fetchParams($this->config_path, $this->item_params);

                if (isset($row['row_style'])) {
                    $row_style = $row['row_style'];
                } else {
                    $row_style = $item_params['row_style'];
                }

                $html .= '<tr class="' . $this->object->object_name . '_row objectListItemRow';
                if (isset($this->new_values[(int) $id_object]) && count($this->new_values[(int) $id_object])) {
                    $html .= ' modified';
                }
                $html .= '" id="' . $this->object->object_name . '_row_' . $id_object . '"';
                $html .= ' data-id_object="' . $id_object . '"';
                if ($this->params['positions']) {
                    $html .= ' data-position="' . $row['position'] . '"';
                }
                if (!is_null($row_style) && $row_style) {
                    $html .= ' style="' . $row_style . '"';
                }
                $html .= '>';

                $html .= '<td style="text-align: center">';
                if ($this->params['checkboxes']) {
                    if ($this->object->getCurrentConf('item_checkbox', true, false, 'bool')) {
                        $html .= '<input type="checkbox" id_="' . $this->object->object_name . '_check_' . $id_object . '"';
                        $html .= ' name="' . $this->object->object_name . '_check"';
                        $html .= ' class="item_check"';
                        $html .= ' data-id_object="' . $id_object . '"/>';
                    }
                }
                $html .= '</td>';

                if ($this->params['positions']) {
                    $html .= '<td class="positionHandle"><span></span></td>';
                }

                $this->setConfPath('cols');
                foreach ($this->cols as $col_name) {
                    $hidden = (int) $this->object->getCurrentConf($col_name . '/hidden', 0, false, 'bool');
                    $min_width = $this->object->getCurrentConf($col_name . '/min_width', 0);
                    $max_width = $this->object->getCurrentConf($col_name . '/max_width', 0);

                    $html .= '<td style="';
                    if ($hidden) {
                        $html .= 'display: none;';
                    }
                    if ($min_width) {
                        $html .= 'min-width: ' . $min_width . ';';
                    }
                    if ($max_width) {
                        $html .= 'max-width: ' . $max_width . ';';
                    }
                    $html .= '">';
                    $html .= (isset($row[$col_name]) ? $row[$col_name] : '');
                    $html .= '</td>';
                }
                $html .= '<td class="buttons">';

                $this->setConfPath();

                if (count($item_params['extra_btn'])) {
                    foreach ($item_params['extra_btn'] as $btn_params) {
                        $html .= $this->renderRowButton($btn_params);
                    }
                }
                if ($item_params['update_btn']) {
                    $html .= $this->renderRowButton(array(
                        'class'   => 'cancelModificationsButton hidden',
                        'icon'    => 'undo',
                        'label'   => 'Annuler les modifications',
                        'onclick' => 'cancelObjectRowModifications(\'' . $this->identifier . '\', ' . $id_object . ', $(this))'
                    ));
                    $html .= $this->renderRowButton(array(
                        'class'   => 'updateButton hidden',
                        'label'   => 'Enregistrer',
                        'onclick' => 'updateObjectFromRow(\'' . $this->identifier . '\', ' . $id_object . ', $(this))'
                    ));
                }
                if ($item_params['edit_btn']) {
                    $onclick = 'loadModalFormFromList(';
                    $onclick .= '\'' . $this->identifier . '\', \'' . $item_params['edit_form'] . '\'';
                    $onclick .= ', $(this), ' . $id_object . ', ' . (!is_null($this->id_parent) ? $this->id_parent : 0) . ')';
                    $html .= $this->renderRowButton(array(
                        'class'   => 'editButton',
                        'label'   => 'Editer',
                        'onclick' => $onclick
                    ));
                }
                if ($item_params['page_btn']) {
                    $controller = $this->object->getController();
                    if ($controller && !is_null($id_object) && $id_object) {
                        $url = DOL_URL_ROOT . '/' . $this->object->module . '/index.php?fc=' . $controller . '&id=' . $id_object;
                        $html .= $this->renderRowButton(array(
                            'label'   => 'Afficher la page',
                            'onclick' => 'window.location = \'' . $url . '\';',
                            'icon'    => 'file-o'
                        ));
                        $html .= $this->renderRowButton(array(
                            'label'   => 'Afficher la page dans un nouvel onglet',
                            'onclick' => 'window.open(\'' . $url . '\');',
                            'icon'    => 'external-link'
                        ));
                    }
                }
                if (!is_null($item_params['inline_view'])) {
                    $onclick = 'displayObjectView($(\'#' . $this->identifier . '_container\').find(\'.objectViewContainer\'), ';
                    $onclick .= '\'' . $this->object->module . '\', \'' . $this->object->object_name . '\', \'' . $item_params['inline_view'] . '\', ' . $id_object . ', \'default\'';
                    $onclick .= ');';
                    $html .= $this->renderRowButton(array(
                        'label'   => 'Afficher',
                        'icon'    => 'eye',
                        'onclick' => $onclick
                    ));
                }
                if (!is_null($item_params['modal_view'])) {
                    $onclick = 'loadModalView(\'' . $this->object->module . '\', \'' . $this->object->object_name . '\', ' . $this->object->id . ', \'' . $item_params['modal_view'] . '\', $(this))';
                    $html .= $this->renderRowButton(array(
                        'label'   => 'Vue rapide',
                        'icon'    => 'eye',
                        'onclick' => $onclick
                    ));
                }
                if ($item_params['delete_btn']) {
                    $html .= $this->renderRowButton(array(
                        'class'   => 'deleteButton',
                        'label'   => 'Supprimer',
                        'onclick' => 'deleteObjects(\'' . $this->identifier . '\', [' . $id_object . '], $(this))'
                    ));
                }
                $html .= '</td>';
                $html .= '</tr>';
            }
        } else {
            $label = $this->object->getLabel('name');
            $isFemale = $this->object->isLabelFemale();
            $html .= '<tr>';
            $html .= '<td  colspan="' . $this->colspan . '" style="text-align: center">';
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

        return $html;
    }
}
