<?php

class BimpList
{

    protected $object = null;
    public $id_parent = null;
    public $list_name = null;
    public $list_path = null;
    public $listIdentifier = null;
    public $title = null;
    public $icon = null;
    protected $user_config = null;
    protected $cols = null;
    protected $filters = array();
    protected $association_filters = array();
    protected $items = null;
    protected $rows = null;
    protected $colspan = 0;
    protected $bulk_actions = array();
    protected $checkboxes = false;
    protected $search = false;
    protected $addobjectRow = false;
    protected $addForm = null;
    protected $use_positions = false;
    protected $sort_col = null;
    protected $sort_way = 'desc';
    protected $sort_option = '';
    protected $n = 1;
    protected $p = 1;
    protected $nbTotalPages = 1;
    protected $nbItems = null;
    public $errors = array();

    public function __construct(BimpObject $object, $list_name = 'default', $id_parent = null, $title = null, $icon = null)
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
            if (!is_null($title)) {
                $this->title = $title;
            } else {
                $this->title = $this->object->getCurrentConf('title', 'Liste des ' . $object->getLabel('name_plur'));
            }
            if (!is_null($icon)) {
                $this->icon = $icon;
            } else {
                $this->icon = $this->object->getCurrentConf('icon', 'bars');
            }
            $this->checkboxes = (int) $this->object->getCurrentConf('checkboxes', 0, false, 'bool');
            $this->addobjectRow = (int) $this->object->getCurrentConf('add_object_row', 0, false, 'bool');
            $this->use_positions = (int) $this->object->getConf('positions', false, false, 'bool');
            $this->fetchCols();
            $this->colspan = 2 + count($this->cols);
            $this->fetchlistParams();
            $this->bulk_actions = $this->object->getCurrentConf('bulk_actions', array(), false, 'array');
            $this->filters = $this->object->getSearchFilters();
        }
    }

    public function setConfPath($path = '')
    {
        return $this->object->config->setCurrentPath($this->list_path . '/' . $path);
    }

    public function addBulkAssociation($association, $id_associate, $label = null)
    {
        if ($this->object->config->isDefined('associations/' . $association)) {
            $associate = $this->object->config->getObject('associations/' . $association . '/object');
            if (!is_null($associate)) {
                if ($associate->fetch($id_associate) > 0) {
                    $this->listIdentifier .= 'associate_to_' . $association;
                    $this->title = BimpTools::ucfirst($this->object->getLabel('name_plur')) . ' associables ';
                    $this->title .= BimpObject::getInstanceLabel($associate, 'to') . ' "' . BimpObject::getInstanceNom($associate) . '"';
                    $this->association_filters[] = array(
                        'type'         => 'not_in',
                        'association'  => $association,
                        'id_associate' => $id_associate
                    );
                    if (!is_null($this->addForm)) {
                        if (!isset($this->addForm['values'])) {
                            $this->addForm['values'] = array();
                        }
                        if (!isset($this->addForm['values']['associations'])) {
                            $this->addForm['values']['associations'] = array();
                        }
                        $this->addForm['values']['associations'][$association] = array($id_associate);
                    }
                    if (is_null($label)) {
                        $label = 'Associer ' . BimpObject::getInstanceLabel($associate, 'to') . ' ' . $id_associate;
                    }
                    $this->bulk_actions[] = array(
                        'label'   => $label,
                        'onclick' => 'toggleSelectedItemsAssociation(\'' . $this->listIdentifier . '\', \'add\', \'' . $association . '\', ' . $id_associate . ')',
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
                    $this->listIdentifier .= 'associated_to_' . $association;
                    $this->addForm = null;
                    $this->title = BimpTools::ucfirst($this->object->getLabel('name_plur')) . ' associés ';
                    $this->title .= BimpObject::getInstanceLabel($associate, 'to') . ' "' . BimpObject::getInstanceNom($associate) . '"';
                    $this->bulk_actions[] = array(
                        'label'   => $label,
                        'onclick' => 'toggleSelectedItemsAssociation(\'' . $this->listIdentifier . '\', \'delete\', \'' . $association . '\', ' . $id_associate . ')',
                        'icon'    => 'unlink'
                    );
                    $this->association_filters[] = array(
                        'type'          => 'in',
                        'association'   => $association,
                        'id_associate ' => $id_associate
                    );
                }
            }
        }
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
        $this->setConfPath();

        $this->n = BimpTools::getValue('n', $this->object->getCurrentConf('n', 0, false, 'int'));
        $this->p = BimpTools::getValue('p', 1);
        $this->sort_col = BimpTools::getValue('sort_col', $this->object->getCurrentConf('sort_col', null));
        $this->sort_way = BimpTools::getValue('sort_way', $this->object->getCurrentConf('sort_way', 'DESC'));
        $this->sort_option = BimpTools::getValue('sort_option', 'default');

        if (BimpTools::isSubmit('associations_filters')) {
            $this->association_filters = json_decode(BimpTools::getValue('associations_filters'), true);
        }

        $addForm = $this->object->getConf($this->list_path . '/add_form');

        if ($addForm) {
            $this->addForm = array(
                'name' => $addForm
            );

            if ($this->object->config->isDefined($this->list_path . '/add_form_values')) {
                $this->addForm['values'] = $this->object->config->getCompiledParams($this->list_path . '/add_form_values');
            }
        }
    }

    protected function fetchItems()
    {
        if (is_null($this->list_path) || count($this->errors) || !count($this->cols)) {
            $this->setConfPath();
            $this->items = array();
            return;
        }

        $primary = $this->object->getPrimary();
        $joins = array();

        // Filtres: 
        $filters = $this->filters;
        if (!is_null($this->id_parent)) {
            $parent_id_property = $this->object->getParentIdProperty();
            $filters[$parent_id_property] = $this->id_parent;
        }

        // Filtres selon objets associés:
        if (count($this->association_filters)) {
            foreach ($this->association_filters as $asso_filter) {
                $bimp_asso = new BimpAssociation($this->object, $asso_filter['association']);
                if (!count($bimp_asso->errors)) {
                    $alias = 'asso_' . $asso_filter['association'];
                    $sql = BimpTools::getSqlSelect(array('src_id_object'), $alias);
                    $sql .= BimpTools::getSqlFrom(BimpAssociation::$table, null, $alias);
                    $sql .= BimpTools::getSqlWhere($bimp_asso->getSqlFilters(null, $asso_filter['id_associate'], $alias));
                    $id_filter = '';
                    if (isset($filters['id'])) {
                        $id_filter = $filters['id'];
                    }
                    $filters['id'] = array('and' => array());
                    if ($id_filter) {
                        $filters['id']['and'][] = $id_filter;
                    }
                    $filters['id']['and'][] = array(
                        $asso_filter['type'] => $sql
                    );
                } else {
                    $this->errors[] = array_merge($this->errors, $bimp_asso->errors);
                    $filters['id'] = 0;
                }
            }
        }

        // Trie: 
        $order_by = $primary;

        if (!is_null($this->sort_col)) {
            if ($this->sort_col === 'position') {
                $order_by = 'position';
            } elseif ($this->setConfPath('cols/' . $this->sort_col)) {
                $field = $this->object->getCurrentConf('field', '');
                if ($field) {
                    $order_by = '';
                    if (!is_null($this->sort_option) && $this->sort_option) {
                        $sort_option_path = 'fields/' . $field . '/sort_options/' . $this->sort_option;
                        if ($this->object->config->isDefined($sort_option_path)) {
                            $join_field = $this->object->getConf($sort_option_path . '/join_field', '');
                            if ($join_field && $this->object->config->isDefined('fields/' . $field . '/object')) {
                                $object = $this->object->config->getObject('fields/' . $field . '/object');
                                if (!is_null($object)) {
                                    $table = BimpTools::getObjectTable($this->object, $field, $object);
                                    $field_on = BimpTools::getObjectPrimary($this->object, $field, $object);
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
                if ($this->use_positions) {
                    $row['position'] = $this->object->getData('position');
                    if (is_null($row['position'])) {
                        $row['position'] = 0;
                    }
                }
                foreach ($this->cols as $col_name => $params) {
                    if ($this->setConfPath('cols/' . $col_name)) {
                        $field = $this->object->getCurrentConf('field', '');
                        $edit = (int) $this->object->getCurrentConf('edit', 0, false, 'bool');

                        if ($edit && $field) {
                            $value = $this->object->getData($field);
                            $input_id = $this->object->object_name . '_' . $item[$primary] . '_' . $field;
                            $input = '<div class="editInputContainer" data-field_name="' . $field . '">';
                            $input .= BimpForm::renderInput($this->object, 'fields/' . $field, $field, $value, $this->id_parent, null, null, $input_id);
                            $input .= '</div>';
                            $row[$col_name] = $input;
                            continue;
                        }

                        $content = '';
                        if ($field) {
                            $display_name = $this->object->getCurrentConf('display', '');
                            $display = $this->object->displayData($field, $display_name);
                            $value = $this->object->getData($field);
                            $content .= '<input type="hidden" name="' . $field . '" value="' . $value . '"/>';
                            $content .= $display;
                        } else {
                            $value = $this->object->getCurrentConf('value', '', true);
                            if ($value) {
                                if ($this->object->config->isDefined('cols/' . $col_name . '/display')) {
                                    $content .= $this->object->displayValue($value, 'cols/' . $col_name . '/display');
                                } else {
                                    $content .= $value;
                                }
                            }
                        }
                        $row[$col_name] = $content;
                    }
                }
                $rows[$item[$primary]] = $row;
                $this->object->reset();
            }
        }

        $this->setConfPath();
        $this->rows = $rows;
    }

    public function render($panel = true)
    {
        if (count($this->errors)) {
            return BimpRender::renderAlerts($this->errors);
        }

        if (is_null($this->list_path)) {
            $this->errors[] = 'Erreur d\'initialisation de la liste';
            return BimpRender::renderAlerts($this->errors);
        }

        if (is_null($this->items)) {
            $this->fetchRows();
        }

        $html = '';
        $this->setConfPath();

        $labels = $this->object->getLabels();

        $html = '<script type="text/javascript">';
        $html .= 'object_labels[\'' . $this->object->object_name . '\'] = ' . json_encode($labels);
        $html .= '</script>';

        $html .= '<div id="' . $this->listIdentifier . '_container"';
        $html .= ' class="section listContainer ' . $this->object->object_name . '_listContainer' . '">';

        $content = '<div id="' . $this->listIdentifier . '" class="objectList ' . $this->object->object_name . '_list"';
        $content .= ' data-list_identifier="' . $this->listIdentifier . '"';
        $content .= ' data-list_name="' . $this->list_name . '"';
        $content .= ' data-module_name="' . $this->object->module . '"';
        $content .= ' data-object_name="' . $this->object->object_name . '"';
        $content .= '>';

        $content .= '<div class="objectViewContainer" style="display: none"></div>';
        $content .= '<div class="objectFormContainer" style="display: none"></div>';

        if (count($this->association_filters)) {
            $content .= '<input type="hidden" name="associations_filters" value="' . htmlentities(json_encode($this->association_filters)) . '"/>';
        }

        if (isset($this->addForm['values'])) {
            $content .= '<input type="hidden" name="' . $this->addForm['name'] . '_add_form_values" value="' . htmlentities(json_encode($this->addForm['values'])) . '">';
        }

        $content .= '<input type="hidden" name="sort_col" value="' . $this->sort_col . '"/>';
        $content .= '<input type="hidden" name="sort_way" value="' . $this->sort_way . '"/>';
        $content .= '<input type="hidden" name="sort_option" value="' . $this->sort_option . '"/>';
        $content .= '<input type="hidden" name="p" value=""/>';

        if (!is_null($this->id_parent)) {
            $content .= '<input type="hidden" id="' . $this->object->object_name . '_id_parent" value="' . $this->id_parent . '"/>';
        }

        $content .= '<table class="noborder objectlistTable" style="border: none" width="100%">';
        $content .= '<thead>';

        $content .= $this->renderHeaderRow();
        $content .= $this->renderSearchRow();
        $content .= $this->renderAddObjectRow();

        $content .= '</thead>';
        $content .= '<tbody class="listRows">';

        $content .= $this->renderRows();

        $content .= '</tbody>';

        $content .= '</table>';
        $content .= '<div class="ajaxResultContainer" id="' . $this->listIdentifier . '_result"></div>';
        $content .= '</div>';

        $content .= $this->renderPaginationRow();

        if ($panel) {
            $footer = $this->renderBulkActions();
        } else {
            $footer = '';
        }

        $params = array(
            'type'     => 'secondary',
            'icon'     => $this->icon,
            'foldable' => true
        );

        if (!is_null($this->addForm)) {
            $form_name = (isset($this->addForm['name']) ? $this->addForm['name'] : 'default');
            $params['header_buttons'] = array(
                array(
                    'classes'     => array('btn', 'btn-default'),
                    'label'       => 'Ajouter ' . $this->object->getLabel('a'),
                    'icon_before' => 'plus-circle',
                    'attr'        => array(
                        'type'    => 'button',
                        'onclick' => 'loadModalFormFromList(\'' . $this->listIdentifier . '\', \'' . $form_name . '\', $(this), 0, ' . (!is_null($this->id_parent) ? $this->id_parent : 0) . ')'
                    )
                )
            );
        }

        $html .= BimpRender::renderPanel($this->title, $content, $footer, $params);
        $html .= '</div>';

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

            if ($this->use_positions) {
                $html .= '<th class="positionHandle"></th>';
            }

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
                        $html .= '" onclick="if (!$(this).hasClass(\'deactivated\')) { sortList(\'' . $this->listIdentifier . '\', \'' . $col_name . '\'); }">';

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
                $html .= '<span class="headerButton openSearchRowButton open-close action-open"></span>';
            }
            if ($this->addobjectRow) {
                $html .= '<span class="headerButton openAddObjectRowButton open-close action-open"></span>';
            }
            if ($this->use_positions) {
                $html .= '<span class="headerButton activatePositionsButton open-close action-open"></span>';
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
        $html .= ' data-object_name="' . $this->object->object_name . '">';

        $html .= '<td style="text-align: center"><i class="fa fa-search"></i></td>';

        if ($this->use_positions) {
            $html .= '<td class="positionHandle"></td>';
        }

        foreach ($this->cols as $col_name => $col_params) {
            if ($this->setConfPath('cols/' . $col_name)) {
                $field = $this->object->getCurrentConf('field', '');
                $html .= '<td>';
                if ($field && $this->object->config->isDefined('fields/' . $field . '/search')) {
                    $search = $this->object->getConf('fields/' . $field . '/search', true, false, 'any');
                    if ($search != '0') {
                        $search_type = $this->object->getConf('fields/' . $field . '/search/type', 'field_input');
                        $searchOnKeyUp = $this->object->getConf('fields/' . $field . '/search/search_on_key_up', 0);
                        $minChars = $this->object->getConf('fields/' . $field . '/search/min_chars', 1);

                        $html .= '<div class="searchInputContainer"';
                        $html .= ' data-field_name="search_' . $field . '"';
                        $html .= ' data-search_type="' . $search_type . '"';
                        $html .= ' data-search_on_key_up="' . $searchOnKeyUp . '"';
                        $html .= ' data-min_chars="' . $minChars . '"';
                        $html .= '>';

                        $input_id = $this->object->object_name . '_search_' . $field;


                        switch ($search_type) {
                            case 'field_input':
                            case 'value_part':
                                if ($this->object->config->isDefined('fields/' . $field . '/search/input')) {
                                    $html .= BimpForm::renderInput($this->object, 'fields/' . $field . '/search', 'search_' . $field, null, $this->id_parent, null, 'default', $input_id, 'search_' . $field);
                                } elseif ($this->object->config->isDefined('fields/' . $field)) {
                                    $html .= BimpForm::renderInput($this->object, 'fields/' . $field, 'search_' . $field, null, $this->id_parent, null, 'default', $input_id, 'search_' . $field);
                                }
                                break;

                            case 'time_range':
                            case 'date_range':
                            case 'datetime_range':
                                $html .= BimpInput::renderInput($search_type, 'search_' . $field, null, array('display_now' => 1), null, 'default', $input_id, 'search_' . $field);
                                break;
                        }
                        $html .= '</div>';
                    }
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

        if ((int) $this->addobjectRow && !is_null($this->list_path)) {
            $html .= '<tr id="' . $this->listIdentifier . '_addObjectRow" class="addObjectRow inputsRow">';
            $html .= '<td><i class="fa fa-plus-circle"></i></td>';

            if ($this->use_positions) {
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

            foreach ($this->cols as $col_name => $col_params) {
                $this->setConfPath('cols/' . $col_name);
                $field = $this->object->getCurrentConf('field', '');

                if (!$field || in_array($field, BimpObject::$common_fields)) {
                    $html .= '<td></td>';
                } else {
                    $input_type = $this->object->getConf('fields/' . $field . '/input/type', '');
                    $input_id = $this->object->object_name . '_' . $field . '_add';
                    $default_value = $this->object->getConf('fields/' . $field . '/default_value', '', false);
                    $html .= '<td' . (($input_type === 'hidden') ? ' style="display: none"' : '') . '>';
                    $html .= '<div class="inputContainer" id="' . $this->object->object_name . '_' . $field . '_addInputContainer"';
                    $html .= ' data-field_name="' . $field . '"';
                    $html .= ' data-default_value="' . $default_value . '">';
                    $html .= BimpForm::renderInput($this->object, 'fields/' . $field, $field, $default_value, $this->id_parent, null, null, $input_id);
                    $html .= '</div>';
                    $html .= '</td>';
                }
            }

            $html .= '<td class="buttons">';
            $html .= '<button class="btn btn-default" onclick="addObjectFromList(\'' . $this->listIdentifier . '\', $(this))">';
            $html .= '<i class="fa fa-plus-circle iconLeft"></i>Ajouter</span>';
            $html .= '</td>';
            $html .= '</tr>';
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
                $button = null;
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
                } elseif (is_array($action_params)) {
                    $label = isset($action_params['label']) ? $action_params['label'] : '';
                    $onclick = isset($action_params['onclick']) ? $action_params['onclick'] : '';
                    $icon = isset($action_params['icon']) ? $action_params['icon'] : '';
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
                }
                if (!is_null($button)) {
                    $buttons[] = BimpRender::renderButton($button, 'button');
                }
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
            $onclick = '';
            $label = '';
            $icon = '';
            $btn_class = '';
            if ($this->setConfPath('bulk_actions/' . $idx)) {
                $onclick = $this->object->getCurrentConf('onclick', '', true);
                $label = $this->object->getCurrentConf('label', '', true);
                $icon = $this->object->getCurrentConf('icon', '', false);
                $btn_class = $this->object->getCurrentConf('btn_class', '', true);
            } elseif (is_array($action_params)) {
                $label = isset($action_params['label']) ? $action_params['label'] : '';
                $onclick = isset($action_params['onclick']) ? $action_params['onclick'] : '';
                $icon = isset($action_params['icon']) ? $action_params['icon'] : '';
            }

            if ($label && $onclick) {
                $html .= '<div><span class="btn';
                if ($btn_class) {
                    $html .= ' ' . $btn_class;
                }
                $onclick = str_replace('list_id', $this->listIdentifier, $onclick);
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
                $html .= '<tr class="' . $this->object->object_name . '_row objectListItemRow" id="' . $this->object->object_name . '_row_' . $id_object . '"';
                $html .= ' data-id_object="' . $id_object . '"';
                if ($this->use_positions) {
                    $html .= ' data-position="' . $row['position'] . '"';
                }
                $html .= '>';

                $html .= '<td style="text-align: center">';
                if ($this->checkboxes) {
                    $html .= '<input type="checkbox" id_="' . $this->object->object_name . '_check_' . $id_object . '"';
                    $html .= ' name="' . $this->object->object_name . '_check"';
                    $html .= ' class="item_check"';
                    $html .= ' data-id_object="' . $id_object . '"/>';
                }
                $html .= '</td>';

                if ($this->use_positions) {
                    $html .= '<td class="positionHandle"><span></span></td>';
                }

                $this->setConfPath('cols');
                foreach ($this->cols as $col_name => $col_params) {
                    $hidden = (int) $this->object->getCurrentConf($col_name . '/hidden', 0, false, 'bool');
                    $min_width = (int) $this->object->getCurrentConf($col_name . '/min_width', 0);
                    $max_width = (int) $this->object->getCurrentConf($col_name . '/max_width', 0);

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
                        $html .= '\'' . $this->object->module . '\', \'' . $this->object->object_name . '\', \'default\', ' . $id_object.', \'default\'';
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
