<?php

class BC_List extends BC_Panel
{

    public static $type = 'list';
    protected $item_params = array();
    public $id_parent;
    protected $filters = array();
    protected $new_values = array();
    protected $items = null;
    public $nbTotalPages = 1;
    protected $nbItems = null;

    public function __construct(BimpObject $object, $path, $list_name = 'default', $level = 1, $id_parent = null, $title = null, $icon = null)
    {
        $this->params_def['n'] = array('data_type' => 'int', 'default' => 10, 'request' => true);
        $this->params_def['p'] = array('data_type' => 'int', 'default' => 1, 'request' => true);

        $this->params_def['sort_field'] = array('data_type' => 'string', 'default' => 'id', 'request' => true);
        $this->params_def['sort_way'] = array('data_type' => 'string', 'default' => 'desc', 'request' => true);
        $this->params_def['sort_option'] = array('data_type' => 'string', 'default' => 'default', 'request' => true);

        $this->params_def['list_filters'] = array('data_type' => 'array', 'default' => array(), 'request' => true, 'json' => true);
        $this->params_def['association_filters'] = array('data_type' => 'array', 'default' => array(), 'request' => true, 'json' => true);

        $this->params_def['add_form_name'] = array('data_type' => 'string');
        $this->params_def['add_form_values'] = array('data_type' => 'array', 'default' => array());
        $this->params_def['add_btn_label'] = array('default' => '');

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
        }

        $this->id_parent = $id_parent;

        parent::__construct($object, $list_name, $path, false, $level, $title, $icon);

        if ($this->isOk()) {
            if (is_null($this->params['title']) || !$this->params['title']) {
                $this->params['title'] = 'Listes des ' . $this->object->getLabel('name_plur');
            }
            if (is_null($this->params['icon']) || !$this->params['icon']) {
                $this->params['icon'] = 'bars';
            }
        }

        $this->setConfPath();

        $this->filters = $this->object->getSearchFilters();
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
        $this->params['add_form_values']['fields'][$field_name] = $value;
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

        foreach ($values as $field_name => $value) {
            $this->params['add_form_values'][$field_name] = $value;
        }
    }

    public function setNewValues($new_values)
    {
        foreach ($new_values as $id_object => $fields) {
            $this->new_values[(int) $id_object] = $fields;
        }
    }

    protected function fetchItems()
    {
        if (!$this->isOk()) {
            $this->setConfPath();
            $this->items = array();
            return;
        }

        $primary = $this->object->getPrimary();
        $joins = array();

        // Filtres: 
        if (count($this->params['list_filters'])) {
            foreach ($this->params['list_filters'] as $list_filter) {
                $this->mergeFilter($list_filter['name'], $list_filter['filter']);
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
                    $object = BimpObject::getInstance($asso_filter['object_module'], $asso_filter['object_name']);
                    if (isset($asso_filter['id_object'])) {
                        $id_object = (int) $asso_filter['id_object'];
                        $object->fetch($id_object);
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
                        $sql = BimpTools::getSqlSelect(array('src_id_object'), $alias);
                        $sql .= BimpTools::getSqlFrom(BimpAssociation::$table, null, $alias);
                        $sql .= BimpTools::getSqlWhere($bimp_asso->getSqlFilters($id_object, $id_associate, $alias));
                        $this->mergeFilter('id', array($asso_filter['type'] => $sql));
                    } else {
                        $this->errors[] = array_merge($this->errors, $bimp_asso->errors);
                        $filters['id'] = 0;
                    }
                }
            }
        }

        $filters = $this->filters;
        if (!is_null($this->id_parent)) {
            $parent_id_property = $this->object->getParentIdProperty();
            $filters[$parent_id_property] = $this->id_parent;
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
            $primary
                ), $joins, $extra_order_by, $extra_order_way);

        if (method_exists($this->object, 'listItemsOverride')) {
            $this->object->listItemsOverride($this->name, $this->items);
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

    public function getHeaderButtons()
    {
        $buttons = array();

        if (!is_null($this->params['add_form_name']) && $this->params['add_form_name']) {
            $label = '';
            if ($this->object->config->isDefined('forms/' . $this->params['add_form_name'] . '/label')) {
                $label = $this->object->getConf('forms/' . $this->params['add_form_name'] . '/label', '');
            } elseif ($this->params['add_btn_label']) {
                $label = $this->params['add_btn_label'];
            }
            if (!$label) {
                $label = 'Ajouter ' . $this->object->getLabel('a');
            }
            $buttons[] = array(
                'classes'     => array('btn', 'btn-default'),
                'label'       => $label,
                'icon_before' => 'plus-circle',
                'attr'        => array(
                    'type'    => 'button',
                    'onclick' => 'loadModalFormFromList(\'' . $this->identifier . '\', \'' . $this->params['add_form_name'] . '\', $(this), 0, ' . (!is_null($this->id_parent) ? $this->id_parent : 0) . ')'
                )
            );
        }
        return $buttons;
    }
}
