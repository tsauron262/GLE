<?php

class BimpList
{

    protected $object = null;
    protected $id_parent = null;
    protected $list_name = null;
    protected $user_config = null;
    protected $list_path = null;
    protected $listIdentifier = null;
    protected $cols = null;
    protected $filters = array();
    protected $items = null;
    protected $rows = null;
    protected $colspan = 0;
    protected $bulk_actions = array();
    protected $checkboxes = false;
    protected $search = false;
    protected $sort_col = null;
    protected $sort_way = 'desc';
    protected $sort_option = '';
    protected $n = 1;
    protected $p = 1;
    protected $nbTotalPages = 1;
    protected $nbItems = null;
    public $errors = array();

    public function __construct(BimpObject $object, $list_name = 'default', $id_parent = null)
    {
        $this->object = $object;
        $this->id_parent = $id_parent;
        $this->list_name = $list_name;

        if (is_null($this->id_parent)) {
            $parent_id_property = $object->getParentIdProperty();
            if (!is_null($parent_id_property)) {
                if (BimpTools::isSubmit($parent_id_property)) {
                    $this->id_parent = BimpTools::getValue($parent_id_property, null);
                }
            }
        }

        if ($this->object->config->isDefined('lists/' . $list_name)) {
            $this->list_path = 'lists/' . $list_name;
            $this->object->config->setCurrentPath($this->list_path);
        } elseif (($list_name === 'default') && $this->object->config->isDefined('list')) {
            $this->list_path = 'list';
            $this->object->config->setCurrentPath($this->list_path);
        } else {
            $this->errors[] = 'Liste "' . $list_name . '" non définie dans le fichier de configuration';
        }

        if (!count($this->errors) && !is_null($this->list_path)) {
            $this->listIdentifier = $this->object->object_name . '_' . $list_name . '_list';
            $this->checkboxes = (int) $this->object->getCurrentConf('checkboxes', 0, false, 'bool');
            $this->fetchCols();
            $this->colspan = 2 + count($this->cols);
            $this->fetchlistParams();
            $this->bulk_actions = $this->object->getCurrentConf('bulk_actions', array(), false, 'array');
            $this->filters = $this->object->getSearchFilters();
            $this->fetchRows();
        }
    }

    public function setConfPath($path = '')
    {
        return $this->object->config->setCurrentPath($this->list_path . '/' . $path);
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

        $this->cols = $this->object->getCurrentConf('cols', array(), true, 'array');
        $this->object->config->setCurrentPath($prev_path);
    }

    protected function fetchlistParams()
    {
        $this->n = BimpTools::getValue('n', $this->object->getCurrentConf('n', 0, false, 'int'));
        $this->p = BimpTools::getValue('p', 1);
        $this->sort_col = BimpTools::getValue('sort_col', $this->object->getCurrentConf('sort_col', null));
        $this->sort_way = BimpTools::getValue('sort_way', $this->object->getCurrentConf('sort_way', 'DESC'));
        $this->sort_option = BimpTools::getValue('sort_option', 'default');
    }

    protected function fetchItems()
    {
        if (is_null($this->list_path) || count($this->errors) || !count($this->cols)) {
            $this->setConfPath();
            $this->items = array();
            return;
        }

        $primary = $this->object->getPrimary();

        // Filtres: 
        $filters = $this->filters;
        if (!is_null($this->id_parent)) {
            $parent_id_property = $this->object->getParentIdProperty();
            $filters[$parent_id_property] = $this->id_parent;
        }

        // Jointures: 
        $joins = array();

        // Trie: 
        $order_by = $primary;

        if (!is_null($this->sort_col)) {
            if ($this->setConfPath('cols/' . $this->sort_col)) {
                $field = $this->object->getCurrentConf('field', '');
                if ($field) {
                    $order_by = '';
                    $sort_option_path = 'fields/' . $field . '/sort_options/' . $this->sort_option;
                    if ($this->object->config->isDefined($sort_option_path)) {
                        $join_field = $this->object->getConf($sort_option_path . '/join_field', '');

                        if ($join_field) {
                            $table = $this->object->getConf('fields/' . $field . '/object/join/table', null, true);
                            $field_on = $this->object->getConf('fields/' . $field . '/object/join/field_on', null, true);
                            if (!is_null($table) && !is_null($field_on)) {
                                $order_by = $table . '.' . $join_field;
                                $joins[] = array(
                                    'alias' => $table,
                                    'table' => $table,
                                    'on'    => $table . '.' . $field_on . ' = a.' . $field
                                );
                            }
                        }
                    }

                    if (!$order_by) {
                        $order_by = $field;
                    }
                }
            }

            $this->setConfPath();
        }

        $this->nbItems = $this->object->getListCount($filters, $joins);

        if ($this->n > 0) {
            $this->nbTotalPages = (int) ceil($this->nbItems / $this->n);
            if ($this->p > $this->nbTotalPages) {
                $this->p = $this->nbTotalPages;
            }
        } else {
            $this->nbTotalPages = 1;
            $this->p = 1;
        }

        $this->items = $this->object->getList($filters, $this->n, $this->p, $order_by, $this->sort_way, 'array', array(
            $primary
                ), $joins);
    }

    protected function fetchRows()
    {
        if (is_null($this->items)) {
            $this->fetchItems();
        }

        $rows = array();

        if (is_null($this->items) || !count($this->items)) {
            $this->rows = array();
        }

        $primary = $this->object->getPrimary();

        foreach ($this->items as $item) {
            $this->object->reset();
            $row = array();
            if ($this->object->fetch((int) $item[$primary])) {
                foreach ($this->cols as $col_name => $params) {
                    if ($this->setConfPath('cols/' . $col_name)) {
                        $field = $this->object->getCurrentConf('field', '');
                        $edit = (int) $this->object->getCurrentConf('edit', 0, false, 'bool');

                        if ($edit) {
                            if ($field) {
                                $value = $this->object->getData($field);
                                $input = '<div class="editInputContainer" data-field_name="' . $field . '">';
                                $input .= BimpForm::renderInput($this->object, 'fields/' . $field, $field, $value, $this->id_parent);
                                $input .= '</div>';
                                $row[$col_name] = $input;
                                continue;
                            }
                        }

                        $value = '';
                        if ($field) {
                            $display_name = $this->object->getCurrentConf('display', '');
                            $value = $this->object->displayData($field, $display_name);
                        } else {
                            $value = $this->object->getCurrentConf('value', '', true);
                            if ($value) {
                                if ($this->object->config->isDefined('cols/' . $col_name . '/display')) {
                                    $value = $this->object->displayValue($value, 'cols/' . $col_name . '/display');
                                }
                            }
                        }
                        $row[$col_name] = $value;
                    }
                }
                $rows[$item[$primary]] = $row;
                $this->object->reset();
            }
        }

        $this->setConfPath();
        $this->rows = $rows;
    }

    public function render()
    {
        $html = '';

        if (count($this->errors)) {
            $html .= BimpRender::renderAlerts($this->errors);
            return $html;
        }

        if (is_null($this->list_path)) {
            $this->errors[] = 'Erreur d\'initialisation de la liste';
            $html .= BimpRender::renderAlerts($this->errors);
            return $html;
        }

        $labels = $this->object->getLabels();

        $html = '<script type="text/javascript">';
        $html .= 'object_labels[\'' . $this->object->object_name . '\'] = ' . json_encode($labels);
        $html .= '</script>';

        $edit_btn = (int) $this->object->getCurrentConf('edit_btn', 0, false, 'bool');
        $add_btn = (int) $this->object->getCurrentConf('add_btn', 0, false, 'bool');

        $html .= '<div id="' . $this->listIdentifier . '_container"';
        $html .= ' class="listContainer ' . $this->object->object_name . '_listContainer' . '">';

        $title = $this->object->getCurrentConf('title', 'Liste des ' . $labels['name_plur']);

        $content = '<div id="' . $this->listIdentifier . '" class="objectList ' . $this->object->object_name . '_list"';
        $content .= ' data-list_identifier="' . $this->listIdentifier . '"';
        $content .= ' data-list_name="' . $this->list_name . '"';
        $content .= ' data-module_name="' . $this->object->module . '"';
        $content .= ' data-object_name="' . $this->object->object_name . '"';
        $content .= '>';

        $content .= '<div class="objectViewContainer" style="display: none"></div>';
        $content .= '<div class="objectFormContainer" style="display: none"></div>';

        $content .= '<input type="hidden" name="sort_col" value="' . $this->sort_col . '"/>';
        $content .= '<input type="hidden" name="sort_way" value="' . $this->sort_way . '"/>';
        $content .= '<input type="hidden" name="sort_option" value="' . $this->sort_option . '"/>';
        $content .= '<input type="hidden" name="p" value=""/>';

        if (!is_null($this->id_parent)) {
            $content .= '<input type="hidden" id="' . $this->object->object_name . '_id_parent" value="' . $this->id_parent . '"/>';
        }

        $content .= '<table class="noborder" style="border: none" width="100%">';
        $content .= '<thead>';

        $content .= $this->renderHeaderRow();
        $content .= $this->renderSearchRow();

        $content .= '</thead>';
        $content .= '<tbody class="listRows">';

        $content .= $this->renderRows();
        $content .= $this->renderAddObjectRow();

        $content .= '</tbody>';

        $content .= '</table>';
        $content .= '<div class="ajaxResultContainer" id="' . $this->listIdentifier . '_result"></div>';
        $content .= '</div>';

        $content .= $this->renderPaginationRow();
        $footer = $this->renderBulkActions();

        $params = array(
            'type' => 'secondary',
            'icon' => 'bars'
        );

        if ($add_btn) {
            $params['header_buttons'] = array(
                array(
                    'classes'     => array('btn', 'btn-default'),
                    'label'       => 'Ajouter ' . $this->object->getLabel('a'),
                    'icon_before' => 'plus-circle',
                    'attr'        => array(
                        'type'    => 'button',
                        'onclick' => 'loadModalFormFromList(\'' . $this->listIdentifier . '\', \'default\', $(this), 0, ' . (!is_null($this->id_parent) ? $this->id_parent : 0) . ')'
                    )
                )
            );
        }

        $html .= BimpRender::renderPanel($title, $content, $footer, $params);
        $html .= '</div>';

        if ($edit_btn || $add_btn) {
            $html .= BimpRender::renderAjaxModal($this->listIdentifier . '_modal');
        }

        return $html;
    }

    public function renderHeaderRow()
    {
        $html = '';

        $this->setConfPath();
        if (!is_null($this->list_path) && count($this->cols)) {
            $this->search = false;
            $default_sort_way = $this->object->getCurrentConf('sort_way', 'DESC');

            $html .= '<tr class="headerRow">';

            $html .= '<th width="5%" style="text-align: center">';
            if ($this->checkboxes) {
                $html .= '<input type="checkbox" id="' . $this->listIdentifier . '_checkall" onchange="toggleCheckAll(\'' . $this->listIdentifier . '\', $(this));"/>';
            }
            $html .= '</th>';

            foreach ($this->cols as $col_name => $col_params) {
                if ($this->setConfPath('cols/' . $col_name)) {
                    $width = $this->object->getCurrentConf('width', null);
                    $min_width = $this->object->getCurrentConf('min_width', null);
                    $max_width = $this->object->getCurrentConf('max_width', null);

                    $field = $this->object->getCurrentConf('field', '');
                    $label = $this->object->getCurrentConf('label', $field ? $this->object->config->get('fields/' . $field . '/label', ucfirst($col_name)) : ucfirst($col_name));
                    $sortable = ($field ? $this->object->getConf('fields/' . $field . '/sortable', in_array($field, BimpObject::$common_fields), false, 'bool') : false);

                    $html .= '<th';
                    if (!is_null($width)) {
                        $html .= ' width="' . $width . '"';
                    }
                    if (!is_null($max_width) || !is_nan($min_width)) {
                        $html .= ' style="';
                        if (!is_null($min_width)) {
                            $html .= 'min-width: ' . $min_width . ';';
                        }
                        if (!is_null($max_width)) {
                            $html .= 'max-width: ' . $max_width . ';';
                        }
                        $html .= '"';
                    }

                    $html .= ' data-col_name="' . $col_name . '">';
                    if ($sortable) {
                        $html .= '<span id="' . $col_name . '_sortTitle" class="sortTitle sorted-';

                        if ($this->sort_col === $col_name) {
                            $html .= strtolower($this->sort_way);
                            $html .= ' active';
                        } else {
                            $html .= strtolower($default_sort_way);
                        }
                        $html .= '" onclick="sortList(\'' . $this->listIdentifier . '\', \'' . $col_name . '\');">';

                        $html .= $label . '</span>';
                    } else {
                        $html .= $label;
                    }
                    $html .= '</th>';

                    if ($field && $this->object->config->isDefined('fields/' . $field . '/search')) {
                        $this->search = true;
                    }
                }
            }

            $this->setConfPath();

            $html .= '<th class="th_tools">';
            $html .= '<div class="headerTools">';
            if ($this->search) {
                $html .= '<span class="headerButton openSearchRowButton open-close action-close"></span>';
            }
            $html .= '<span class="headerButton displayPopupButton openBulkActionsPopupButton"';
            $html .= ' data-popup_id="' . $this->listIdentifier . '_bulkActionsPopup"></span>';
            $html .= '<span class="headerButton displayPopupButton openParametersPopupButton"';
            $html .= ' data-popup_id="' . $this->listIdentifier . '_parametersPopup"></span>';
            $html .= $this->renderBulkActionsPopup();
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
        if (!$this->search || is_null($this->list_path) || !count($this->cols)) {
            return '';
        }

        if (!is_null($this->id_parent)) {
            $this->object->setIdParent($this->id_parent);
        }

        $html = '';

        $html .= '<tr id="' . $this->listIdentifier . '_searchRow" class="listSearchRow"';
        $html .= ' data-list_name="' . $this->list_name . '"';
        $html .= ' data-module_name="' . $this->object->module . '"';
        $html .= ' data-object_name="' . $this->object->object_name . '"';
        $html .= '>';

        $html .= '<td style="text-align: center"><i class="fa fa-search"></i></td>';

        foreach ($this->cols as $col_name => $col_params) {
            if ($this->setConfPath('cols/' . $col_name)) {
                $field = $this->object->getCurrentConf('field', '');
                $html .= '<td>';
                if ($field && $this->object->config->isDefined('fields/' . $field . '/search')) {
                    $search_type = $this->object->getConf('fields/' . $field . '/search/type', 'field_input');
                    $searchOnKeyUp = $this->object->getConf('fields/' . $field . '/search/search_on_key_up', 0);
                    $minChars = $this->object->getConf('fields/' . $field . '/search/min_chars', 1);

                    $html .= '<div class="searchInputContainer"';
                    $html .= ' data-field_name="search_' . $field . '"';
                    $html .= ' data-search_type="' . $search_type . '"';
                    $html .= ' data-search_on_key_up="' . $searchOnKeyUp . '"';
                    $html .= ' data-min_chars="' . $minChars . '"';
                    $html .= '>';

                    switch ($search_type) {
                        case 'field_input':
                        case 'value_part':
                            if ($field) {
                                $input_id = $this->object->object_name . '_search_' . $field;
                                if ($this->object->config->isDefined('fields/' . $field . '/search/input')) {
                                    $html .= BimpForm::renderInput($this->object, 'fields/' . $field . '/search', 'search_' . $field, null, $this->id_parent, null, 'default', $input_id);
                                } elseif ($this->object->config->isDefined('fields/' . $field)) {
                                    $html .= BimpForm::renderInput($this->object, 'fields/' . $field, 'search_' . $field, null, $this->id_parent, null, 'default', $input_id);
                                }
                            }
                            break;
                    }
                    $html .= '</div>';
                } elseif (in_array($field, BimpObject::$common_fields)) {
                    $html .= $this->object->getCommonFieldSearchInput($field);
                }
                $html .= '</td>';
            }
        }

        $html .= '<td class="searchTools">';
        $html .= '<button type="button" class="btn btn-default" onclick="resetListSearchInputs(\'' . $this->listIdentifier . '\')">';
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

        if (!is_null($this->list_path)) {
            if ((int) $this->object->getCurrentConf('add_object_row', 0, false, 'bool')) {
                $html .= '<tr id="' . $this->listIdentifier . '_addObjectRow" class="inputsRow">';
                $html .= '<td></td>';

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

                foreach ($this->cols as $col_name => $col_params) {
                    $this->setConfPath('cols/' . $col_name);
                    $field = $this->object->getCurrentConf('field', '');

                    if (!$field) {
                        $html .= '<td></td>';
                    } else {
                        $input_type = $this->object->getConf('fields/' . $field . '/input/type', '');
                        $default_value = $this->object->getConf('fields/' . $field . '/default_value', '', false);
                        $html .= '<td' . (($input_type === 'hidden') ? ' style="display: none"' : '') . '>';
                        $html .= '<div class="inputContainer" id="' . $this->object->object_name . '_' . $field . '_addInputContainer"';
                        $html .= ' data-field_name="' . $field . '">';
                        $html .= ' data-default_value="' . $default_value . '"';
                        $html .= BimpForm::renderInput($this->object, 'fields/' . $field, $field, $default_value, $this->id_parent);
                        $html .= '</div>';
                        $html .= '</td>';
                    }
                }

                $html .= '<td>';
                $html .= '<span class="butAction" onclick="addObjectFromList(\'' . $this->listIdentifier . '\', $(this))">Ajouter</span>';
                $html .= '</td>';
                $html .= '</tr>';
            }
        }
        $this->setConfPath();
        return $html;
    }

    public function renderPaginationRow()
    {
        $hide = (is_null($this->nbItems) || ($this->n <= 0) || ($this->n >= $this->nbItems));

        $html = '<tr class="paginationContainer"' . ($hide ? ' style="display: none"' : '') . '>';
        $html .= '<td colspan="' . $this->colspan . '" style="padding: 15px 10px;">';
        $html .= '<div id="' . $this->listIdentifier . '_pagination" class="listPagination">';
        $html .= $this->renderPagination();
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';

        return $html;
    }

    public function renderPagination()
    {
        if (is_null($this->nbItems)) {
            return '';
        }

        $html = '';
        if (($this->n > 0) && ($this->n < $this->nbItems)) {
            $first = $this->p - 4;
            if ($first < 1) {
                $first = 1;
            }
            $last = $first + 9;
            if ($last > $this->nbTotalPages) {
                $last = $this->nbTotalPages;
            }

            $html .= '<span class="navButton prevButton' . (((int) $this->p === 1) ? ' disabled' : '') . '">Précédent</span>';
            $html .= '<div class="pages">';

            if ($first !== 1) {
                $html .= '<span class="pageBtn' . (((int) $this->p === 1) ? ' active' : '') . '" data-p="1">1</span>';
            }

            $current = $first;
            while ($current <= $last) {
                if ($current !== 1) {
                    $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;';
                }
                $html .= '<span class="pageBtn' . (((int) $current === (int) $this->p) ? ' active' : '') . '" data-p="' . $current . '">' . $current . '</span>';
                $current++;
            }

            if ($last !== $this->nbTotalPages) {
                $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;<span class="pageBtn' . (((int) $this->p === (int) $this->nbTotalPages) ? ' active' : '') . '" data-p="' . $this->nbTotalPages . '">' . $this->nbTotalPages . '</span>';
            }

            $html .= '</div>';
            $html .= '<span class="navButton nextButton' . (((int) $this->p >= $this->nbTotalPages) ? ' disabled' : '') . '">Suivant</span>';
        }

        $this->setConfPath();
        return $html;
    }

    public function renderBulkActions()
    {
        if (count($this->bulk_actions)) {
            $buttons = array();

            foreach ($this->bulk_actions as $idx => $action_params) {
                if ($this->setConfPath('bulk_actions/' . $idx)) {
                    $onclick = $this->object->getCurrentConf('onclick', '', true);
                    $onclick = str_replace('list_id', $this->listIdentifier, $onclick);
                    $label = $this->object->getCurrentConf('label', '', true);
                    $icon = $this->object->getCurrentConf('icon', '', false);


                    $button = array(
                        'classes' => array('btn', 'btn-light-default'),
                        'label'   => $label,
                        'attr'    => array(
                            'type'    => 'button',
                            'onclick' => $onclick
                        )
                    );
                    if ($icon) {
                        $button['icon_before'] = $icon;
                    }
                }
                $buttons[] = BimpRender::renderButton($button, 'button');
            }

            $title = BimpTools::ucfirst($this->object->getLabel('name_plur')) . ' sélectionné' . ($this->object->isLabelFemale() ? 'e' : '') . 's';
            $html = BimpRender::renderDropDownButton($title, $buttons, array(
                        'icon' => 'check-square-o'
            ));
        }

        $this->setConfPath();
        return $html;
    }

    public function renderBulkActionsPopup()
    {
        $html = '';

        $html .= '<div id="' . $this->listIdentifier . '_bulkActionsPopup" class="tinyPopup listPopup">';
        $html .= '<div class="title">';
        $html .= BimpTools::ucfirst($this->object->getLabel('name_plur')) . ' sélectionné' . ($this->object->isLabelFemale() ? 'e' : '') . 's';
        $html .= '</div>';

        foreach ($this->bulk_actions as $idx => $action_params) {
            if ($this->setConfPath('bulk_actions/' . $idx)) {
                $html .= '<span class="btn';
                $btn_class = $this->object->getCurrentConf('btn_class', '', true);
                $onclick = $this->object->getCurrentConf('onclick', '', true);
                $label = $this->object->getCurrentConf('label', '', true);
                $icon = $this->object->getCurrentConf('icon', '', false);
                if ($btn_class) {
                    $html .= ' ' . $btn_class;
                }
                $onclick = str_replace('list_id', $this->listIdentifier, $onclick);
                $html .= '" onclick="' . $onclick . '">';
                if ($icon) {
                    $html .= '<i class="fa fa-' . $icon . ' left"></i>';
                }
                $html .= $label . '</span>';
            }
        }

        $html .= '</div>';

        $this->setConfPath();
        return $html;
    }

    public function renderParametersPopup()
    {
        $html = '';
        $html .= '<div id="' . $this->listIdentifier . '_parametersPopup" class="tinyPopup listPopup">';

        $html .= '<div class="title">';
        $html .= 'Nombre d\'items par page';
        $html .= '</div>';

        $html .= BimpInput::renderSwitchOptionsInput('n', array(
                    10  => '10', 25  => '25', 50  => '50', 100 => '100', 250 => '250', 500 => '500', 0   => 'Tout'), $this->n, $this->listIdentifier . '_n');

        $html .= '</div>';

        $this->setConfPath();
        return $html;
    }

    public function renderRows()
    {
        $html = '';

        $this->setConfPath();
        if (count($this->errors)) {
            $html .= '<tr>';
            $html .= '<td colspan="' . $this->colspan . '">';
            $html .= BimpRender::renderAlerts($this->errors);
            $html .= '</td>';
            $html .= '</tr>';
            return $html;
        }

        if (is_null($this->list_path) || !count($this->cols)) {
            return '';
        }

        if (is_null($this->rows)) {
            $this->fetchRows();
        }

        if (count($this->rows)) {
            $update_btn = (int) $this->object->getCurrentConf('update_btn', 0);
            $edit_btn = (int) $this->object->getCurrentConf('edit_btn', 0);
            $delete_btn = (int) $this->object->getCurrentConf('delete_btn', 0);
            $view_btn = (int) $this->object->getCurrentConf('view_btn', 0);

            foreach ($this->rows as $id_object => $row) {
                $html .= '<tr class="' . $this->object->object_name . '_row" id="' . $this->object->object_name . '_row_' . $id_object . '">';

                $html .= '<td style="text-align: center">';
                if ($this->checkboxes) {
                    $html .= '<input type="checkbox" id_="' . $this->object->object_name . '_check_' . $id_object . '"';
                    $html .= ' name="' . $this->object->object_name . '_check"';
                    $html .= ' class="item_check"';
                    $html .= ' data-id_object="' . $id_object . '"/>';
                }
                $html .= '</td>';

                $this->setConfPath('cols');
                foreach ($this->cols as $col_name => $col_params) {
                    $hidden = (int) $this->object->getCurrentConf($col_name . '/hidden', 0, false, 'bool');
                    $html .= '<td' . ($hidden ? ' style="display: none"' : '') . '>';
                    $html .= (isset($row[$col_name]) ? $row[$col_name] : '');
                    $html .= '</td>';
                }
                $html .= '<td class="buttons">';

                $this->setConfPath();

                if ($update_btn) {
                    $html .= '<span class="rowButton updateButton" title="Mettre à jour"';
                    $html .= ' onclick="updateObjectFromRow(\'' . $this->listIdentifier . '\', ' . $id_object . ', $(this))"></span>';
                }
                if ($edit_btn) {
                    $html .= '<span class="rowButton editButton" title="Editer"';
                    $html .= ' onclick="loadModalFormFromList(\'' . $this->listIdentifier . '\', \'default\', $(this), ' . $id_object . ')">';
                    $html .= '</span>';
                }
                if ($view_btn) {
                    $html .= '<span class="rowButton viewButton" title="Afficher" onclick="';
                    $controller = $this->object->getController();
                    if ($controller && !is_null($id_object) && $id_object) {
                        $url = DOL_URL_ROOT . '/' . $this->object->module . '/index.php?fc=' . $controller . '&id=' . $id_object;
                        $html .= 'window.location = \'' . $url . '\';';
                    } else {
                        $html .= 'displayObjectView($(\'#' . $this->listIdentifier . '_container\').find(\'.objectViewContainer\'), ';
                        $html .= '\'' . $this->object->module . '\', \'' . $this->object->object_name . '\', \'default\', ' . $id_object;
                        $html .= ');';
                    }
                    $html .= '"></span>';
                }
                if ($delete_btn) {
                    $html .= '<span class="rowButton deleteButton" title="Supprimer"';
                    $html .= ' onclick="deleteObjects(\'' . $this->listIdentifier . '\', [' . $id_object . '], $(this))">';
                    $html .= '</span>';
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
