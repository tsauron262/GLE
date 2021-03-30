<?php

class BC_Filter extends BimpComponent
{

    public $component_name = 'Filtre';
    public static $type = 'filter';
    public static $config_required = false;
    public $identifier = '';
    public $filter_name = '';
    public $bc_field = null;
    public $base_object = null;
    public $values = array();
    public $excluded_values = array();
    public $is_default = false;
    public $data = array();
    public $cur_value_type = '';
    protected $child_instance = null;
    protected $child_filters_panels = array();
    public static $type_params_def = array(
        'value_part' => array(
            'part_type' => array('default' => 'middle')
        ),
        'check_list' => array(
            'items' => array('data_type' => 'array')
        )
    );
    public static $custom_params_def = array(
    );
    public static $periods = array(
        'n' => 'Y',
        'm' => 'M',
        'j' => 'D'
    );
    public static $date_range_options = array(
        'max_today' => '<= Aujourd\'hui',
        'min_today' => '>= Aujourd\'hui',
        'max_yd'    => '< Aujourd\'hui',
        'min_yd'    => '> Aujourd\'hui'
    );

    public function __construct(BimpObject $object, $name, $path, $params = array(), $values = array(), $excluded_values = array())
    {
        $this->params_def['type'] = array();
        $this->params_def['label'] = array();
        $this->params_def['data_type'] = array('default' => 'string');
        $this->params_def['open'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['default_values'] = array('data_type' => 'array', 'compile' => true, 'default' => array());
        $this->params_def['default_excluded_values'] = array('data_type' => 'array', 'compile' => true, 'default' => array());
        $this->params_def['add_btn'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['exclude_btn'] = array('data_type' => 'bool', 'default' => 1);

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $this->values = $values;
        $this->excluded_values = $excluded_values;
        $this->base_object = $object;

        $filter_object = self::getFilterObject($object, $name, $this->filter_name, $this->errors);

        if (count($this->errors)) {
            return;
        }

        parent::__construct($filter_object, $name, 'filters');

        if (count($this->errors)) {
            return;
        }
        $this->identifier .= $this->object->object_name . '_' . str_replace(':', '___', $this->name) . '_filter';

        if ($this->object->field_exists($this->filter_name)) {
            $this->bc_field = new BC_Field($this->object, $this->filter_name);
            $this->bc_field->display_input_value = false;

            // Surcharge des paramètres selon le champ: 
            if (is_null($this->params['label'])) {
                $this->params['label'] = $this->bc_field->getParam('label', $this->filter_name);
                $this->params['data_type'] = $this->bc_field->getParam('type', 'string');
            }
        }

        // Surchage des paramètres si le filtre appartient à un objet enfant:
        if ($this->filter_name !== $this->name) {
            if ($this->base_object->config->isDefined('filters/' . $this->name)) {
                $errors = array();
                $override_params = self::fetchParamsStatic($this->base_object->config, 'filters/' . $this->name, $this->params_def, $errors, true, true);
                if (!empty($override_params)) {
                    $this->params = BimpTools::overrideArray($this->params, $override_params, true, true);
                }
            }

            if ($this->base_object->config->isDefined($path)) {
                $errors = array();
                $override_params = self::fetchParamsStatic($this->base_object->config, $path, $this->params_def, $errors, true, true);
                if (!empty($override_params)) {
                    $this->params = BimpTools::overrideArray($this->params, $override_params, true, true);
                }
            }
        }

        if (empty($values) && empty($this->excluded_values) && (!empty($this->params['default_values']) || !empty($this->params['default_excluded_values']))) {
            $this->values = $this->params['default_values'];
            $this->excluded_values = $this->params['default_excluded_values'];
            $this->is_default = true;
        }

        $items = null;

        if (is_null($this->params['type']) || !(string) $this->params['type']) {
            if (!is_null($this->bc_field)) {
                // Type de filtre selon paramètre du champ: 
                $input_type = $this->object->getConf('fields/' . $this->bc_field->name . '/input/type', '');
                if ($input_type === 'search_user') {
                    $this->params['type'] = 'user';
                } elseif (isset($this->bc_field->params['values']) && !empty($this->bc_field->params['values'])) {
                    if (count($this->bc_field->params['values']) <= 10) {
                        $this->params['type'] = 'check_list';
                        $items = $this->bc_field->params['values'];
                    } else {
                        $this->params['type'] = 'value';
                    }
                } elseif ($this->bc_field->params['type'] === 'bool') {
                    $this->params['type'] = 'check_list';
                    $items = array(
                        0 => 'NON',
                        1 => 'OUI'
                    );
                }
            }

            if (is_null($this->params['type']) || !(string) $this->params['type']) {
                // Type de filtre selon le type de donnée: 
                $this->params['type'] = self::getDefaultTypeFromDataType($this->params['data_type']);
            }
        }

        // paramètres selon le type de filtre: 
        if (array_key_exists($this->params['type'], static::$type_params_def)) {
            foreach ($this->fetchParams($this->config_path, static::$type_params_def[$this->params['type']]) as $p_name => $value) {
                $this->params[$p_name] = $value;
            }

            // Surchage des paramètres selon le type si le filtre appartient à un objet enfant:
            if ($this->filter_name !== $this->name) {
                if ($this->base_object->config->isDefined('filters/' . $this->name)) {
                    $errors = array();
                    $override_params = self::fetchParamsStatic($this->base_object->config, 'filters/' . $this->name, static::$type_params_def[$this->params['type']], $errors, true, true);
                    if (!empty($override_params)) {
                        $this->params = BimpTools::overrideArray($this->params, $override_params, true, true);
                    }
                }

                if ($this->base_object->config->isDefined($path)) {
                    $errors = array();
                    $override_params = self::fetchParamsStatic($this->base_object->config, $path, static::$type_params_def[$this->params['type']], $errors, true, true);
                    if (!empty($override_params)) {
                        $this->params = BimpTools::overrideArray($this->params, $override_params, true, true);
                    }
                }
            }
        }

        if (is_array($params) && !empty($params)) {
            $this->params = BimpTools::overrideArray($this->params, $params, true, true);
        }

        if (empty($this->getParam('items', array())) && !is_null($items)) {
            $this->params['items'] = $items;
        }

        $current_bc = $prev_bc;
    }

    // Getters: 

    public function getFilterValueLabel($value)
    {
        if (!$this->isOk()) {
            return '';
        }

        if (!$this->params['show']) {
            return '';
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $label = '';

        switch ($this->params['type']) {
            case 'value':
                if (!is_null($this->bc_field)) {
                    if (is_array($value)) {
                        if (isset($value['ids_list'])) {
                            $label = $this->getObjectIdsListLabel($value['ids_list']);
                        } elseif (isset($value['id_filters'])) {
                            $label = $this->getObjectChildIdFiltersLabel($value['id_filters']);
                        }
                    } else {
                        if ($this->bc_field->params['type'] === 'id_object') {
                            $this->bc_field->display_name = 'ref_nom';
                        }
                        $this->bc_field->value = $value;
                        $this->bc_field->no_html = true;
                        $label = $this->bc_field->displayValue();
                    }
                } else {
                    $label = $this->object->getCustomFilterValueLabel($this->filter_name, $value);
                }
                if (!(string) $label) {
                    $label = $value;
                }
                break;

            case 'user':
                if ($value === 'current') {
                    $label = 'Utilisateur connecté';
                } else {
                    $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $value);
                    if (BimpObject::objectLoaded($user)) {
                        $label = $user->getName();
                    } elseif ((int) $value) {
                        $label = 'Utilisateur #' . $value;
                    } else {
                        $label = 'Aucun';
                    }
                }
                break;

            case 'value_part':
                $part = (is_string($value) ? $value : (isset($value['value']) ? $value['value'] : ''));
                $part_type = (isset($value['part_type']) ? $value['part_type'] : $this->params['part_type']);
                $label = $this->getValuePartLabel($part, $part_type);
                break;

            case 'range':
            case 'date_range':
                $label = '';
                $is_dates = false;
                if ($this->params['type'] === 'date_range') {
                    $is_dates = true;
                }

                if (is_array($value)) {
                    if (isset($value['period']) && is_array($value['period']) && !empty($value['period'])) {
                        $label = self::getDateRangePeriodLabel($value['period']);
                        $value = self::convertDateRangePeriodValue($value['period']);
                    } elseif (isset($value['option'])) {
                        $label = self::getDateRangeOptionLabel($value['option']);
                        $value = self::convertDateRangeOptionValue($value['option']);
                    }

                    if ($is_dates) {
                        if (isset($value['max']) && $value['max'] !== '') {
                            if (preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $value['max'])) {
                                $value['max'] .= ' 23:59:59';
                            }
                        }
                    }
                }

                if (is_array($value) && (isset($value['min']) || isset($value['max']))) {
                    $label .= ($this->params['type'] == 'date_range' ? 'Du' : 'Min') . ': <strong>';
                    if (!isset($value['min']) || $value['min'] === '') {
                        $label .= '-&infin;';
                    } else {
                        if (!is_null($this->bc_field)) {
                            $this->bc_field->value = $value['min'];
                            $label .= $this->bc_field->displayValue();
                        } else {
                            $label .= BimpTools::displayValueByType($value['min'], $this->params['data_type']);
                        }
                    }

                    $label .= '</strong><br/>' . ($this->params['type'] == 'date_range' ? 'Au' : 'Max') . ': <strong>';

                    if (!isset($value['max']) || $value['max'] === '') {
                        $label .= '&infin;';
                    } else {
                        if (!is_null($this->bc_field)) {
                            $this->bc_field->value = $value['max'];
                            $label .= $this->bc_field->displayValue();
                        } else {
                            $label .= BimpTools::displayValueByType($value['max'], $this->params['data_type']);
                        }
                    }

                    $label .= '</strong>';

                    if (isset($value['min']) && (string) $value['min'] !== '' &&
                            isset($value['max']) && (string) $value['max'] !== '' &&
                            $value['min'] > $value['max']) {
                        $label .= '<br/><span class="danger">Valeurs invalides (' . ($this->params['type'] == 'date_range' ? 'Du' : 'Min') . ' > ' . ($this->params['type'] == 'date_range' ? 'Au' : 'Max') . ')</span>';
                    }
                } else {
                    $label = '<span class="danger">Valeurs invalides</span>';
                }
                break;

            case 'check_list':
                if (isset($this->params['items'][$value])) {
                    if (is_string($this->params['items'][$value])) {
                        $label = $this->params['items'][$value];
                    } elseif (isset($this->params['items'][$value]['label'])) {
                        $label = $this->params['items'][$value]['label'];
                    }
                }
                if (!$label) {
                    $label = 'Valeur: ' . $value;
                }
                break;
        }

        $current_bc = $prev_bc;
        return $label;
    }

    public function getSqlFilters(&$filters = array(), &$joins = array())
    {
        if (!$this->isOk()) {
            return array();
        }

        if (!(int) $this->params['show']) {
            return array();
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $errors = array();

        if (!is_null($this->bc_field)) {
            $errors = $this->getFieldSqlFilters($filters, $joins);
        } else {
            $errors = $this->getCustomFilterSqlFilters($filters, $joins);
        }

        if (!empty($errors)) {
            BimpCore::addlog('Erreur filtres de liste', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', $this->object, array(
                'Nom du filtre' => $this->name,
                'Erreurs'       => $errors,
                'Paramètres'    => $this->params
            ));
        }

        $current_bc = $prev_bc;
        return $errors;
    }

    protected function getFieldSqlFilters(&$filters = array(), &$joins = array())
    {
        if (in_array($this->filter_name, $this->object->params['fields']) && !$this->object->isFieldActivated($this->filter_name)) {
            return array();
        }

        $values = self::getConvertedValues($this->params['type'], $this->values);
        $excluded_values = self::getConvertedValues($this->params['type'], $this->excluded_values);

        if (empty($values) && empty($excluded_values)) {
            return array();
        }

        $errors = array();

        $field_alias = 'a';
        $field_object = $this->base_object;

        if ($this->filter_name !== $this->name) {
            $children = explode(':', $this->name);
            $field_name = array_pop($children);

            if ($field_name !== $this->bc_field->name) {
                $errors[] = 'Erreur de correspondance du nom du champ';
            } else {
                if (!empty($children)) {
                    $errors = $field_object->getRecursiveChildrenJoins($children, $filters, $joins, 'a', $field_alias, $field_object);
                }
            }
        } else {
            $field_name = $this->bc_field->name;
        }

        if (!is_a($field_object, 'BimpObject')) {
            $errors[] = 'Objet propriétaire du champ invalide';
        }

        if (!count($errors)) {
            $filter_key = $field_object->getFieldSqlKey($field_name, $field_alias, null, $filters, $joins, $errors);

            if (!$filter_key || !empty($errors)) {
                return $errors;
            }

            $and_field = array();
            $or_field = array();

            switch ($this->params['type']) {
                case 'value':
                case 'user':
                    foreach ($values as $value) {
                        if (is_array($value)) {
                            if (isset($value['ids_list'])) {
                                // Liste d'IDs objet: 
                                $sep = BimpTools::getArrayValueFromPath($value, 'ids_list/separator', '');
                                $list = BimpTools::getArrayValueFromPath($value, 'ids_list/list', '');

                                if ($list !== '' && $sep) {
                                    $ids = array();
                                    foreach (explode($sep, $list) as $id) {
                                        if (in_array($id, array('0', 'NULL', 'null'))) {
                                            $ids[] = '';
                                            $ids[] = null;
                                            $ids[] = 0;
                                        } elseif (preg_match('/^[0-9]+$/', $id)) {
                                            $ids[] = $id;
                                        }
                                    }
                                    if (!empty($ids)) {
                                        $or_field[] = array(
                                            'in' => $ids
                                        );
                                    }
                                }
                            } elseif (isset($value['id_filters'])) {
                                // Filtres enregistrés: 
                                $child_intance = $this->getChildInstance();

                                if (is_a($child_intance, 'BimpObject')) {
                                    $filtersPanel = $this->getChildFiltersPanel((int) $value['id_filters']);

                                    if (is_a($filtersPanel, 'BC_FiltersPanel')) {
                                        $primary = $child_intance->getPrimary();
                                        $child_filters = array();
                                        $child_joins = array();

                                        $filtersPanel->getSqlFilters($child_filters, $child_joins);
                                        if (!empty($child_filters)) {
                                            $ids = array();
                                            $rows = $child_intance->getList($child_filters, null, null, $primary, 'asc', 'array', array($primary), $child_joins);

                                            if (is_array($rows)) {
                                                foreach ($rows as $r) {
                                                    $ids[] = (int) $r[$primary];
                                                }
                                            }

                                            $or_field[] = array(
                                                'in' => $ids
                                            );
                                        }
                                    }
                                }
                            }
                        } else {
                            // Valeur standard: 
                            if (BimpTools::checkValueByType($this->bc_field->params['type'], $value)) {
                                $or_field[] = $value;
                            } else {
                                $errors[] = 'Valeur invalide: "' . $value . '" (doit être de type: "' . BimpTools::getDataTypeLabel($this->bc_field->params['type']) . '"';
                            }
                        }
                    }

                    foreach ($excluded_values as $value) {
                        if (is_array($value)) {
                            if (isset($value['ids_list'])) {
                                // Liste d'IDs objet: 
                                $sep = BimpTools::getArrayValueFromPath($value, 'ids_list/separator', '');
                                $list = BimpTools::getArrayValueFromPath($value, 'ids_list/list', '');

                                if ($list !== '' && $sep) {
                                    $ids = array();
                                    foreach (explode($sep, $list) as $id) {
                                        if (in_array($id, array('0', 'null', 'NULL'))) {
                                            $ids[] = 0;
                                            $ids[] = '';
                                            $ids[] = null;
                                        } elseif (preg_match('/^[0-9]+$/', $id)) {
                                            $ids[] = $id;
                                        }
                                    }
                                    if (!empty($ids)) {
                                        $and_field[] = array(
                                            'not_in' => $ids
                                        );
                                    }
                                }
                            } elseif (isset($value['id_filters'])) {
                                // Filtres enregistrés: 
                                $child_intance = $this->getChildInstance();

                                if (is_a($child_intance, 'BimpObject')) {
                                    $filtersPanel = $this->getChildFiltersPanel((int) $value['id_filters']);

                                    if (is_a($filtersPanel, 'BC_FiltersPanel')) {
                                        $primary = $child_intance->getPrimary();
                                        $child_filters = array();
                                        $child_joins = array();

                                        $filtersPanel->getSqlFilters($child_filters, $child_joins);
                                        if (!empty($child_filters)) {
                                            $ids = array();
                                            $rows = $child_intance->getList($child_filters, null, null, $primary, 'asc', 'array', array($primary), $child_joins);

                                            if (is_array($rows)) {
                                                foreach ($rows as $r) {
                                                    $ids[] = (int) $r[$primary];
                                                }
                                            }

                                            $or_field[] = array(
                                                'not_in' => $ids
                                            );
                                        }
                                    }
                                }
                            }
                        } else {
                            if (BimpTools::checkValueByType($this->bc_field->params['type'], $value)) {
                                $and_field[] = array(
                                    'operator' => '!=',
                                    'value'    => $value
                                );
                            } else {
                                $errors[] = 'Valeur invalide: "' . $value . '" (doit être de type: "' . BimpTools::getDataTypeLabel($this->bc_field->params['type']) . '"';
                            }
                        }
                    }
                    break;

                case 'value_part':
                    foreach ($values as $value) {
                        $part = (is_string($value) ? $value : (isset($value['value']) ? $value['value'] : ''));
                        $part_type = (isset($value['part_type']) ? $value['part_type'] : $this->params['part_type']);
                        $or_field[] = self::getValuePartSqlFilter($part, $part_type, false);
                    }
                    foreach ($excluded_values as $value) {
                        $part = (is_string($value) ? $value : (isset($value['value']) ? $value['value'] : ''));
                        $part_type = (isset($value['part_type']) ? $value['part_type'] : $this->params['part_type']);
                        $and_field[] = self::getValuePartSqlFilter($part, $part_type, true);
                    }
                    break;

                case 'date_range':
                    foreach ($values as $value) {
                        $or_field[] = $this->getRangeSqlFilter($value, $errors, true, false);
                    }
                    foreach ($excluded_values as $value) {
                        $and_field[] = $this->getRangeSqlFilter($value, $errors, true, true);
                    }
                    break;

                case 'range':
                    foreach ($values as $value) {
                        $or_field[] = $this->getRangeSqlFilter($value, $errors, false, false);
                    }
                    foreach ($excluded_values as $value) {
                        $and_field[] = $this->getRangeSqlFilter($value, $errors, false, true);
                    }
                    break;

                case 'check_list':
                    if (!empty($values)) {
                        $filters[$filter_key] = array(
                            'in' => $this->values
                        );
                    }

                    if (!empty($excluded_values)) {
                        $filters[$filter_key] = array(
                            'not_in' => $this->values
                        );
                    }
                    break;
            }

            if (!empty($or_field)) {
                $and_field[] = array(
                    'or_field' => $or_field
                );
            }

            if (!empty($and_field)) {
                if (count($and_field) > 1) {
                    $filters[$filter_key] = array(
                        'and' => $and_field
                    );
                } else {
                    $filters[$filter_key] = $and_field[0];
                }
            }
        }

        return $errors;
    }

    protected function getCustomFilterSqlFilters(&$filters = array(), &$joins = array())
    {
        $errors = array();

        $values = self::getConvertedValues($this->params['type'], $this->values);
        if (!empty($values)) {
            $this->object->getCustomFilterSqlFilters($this->filter_name, $values, $filters, $joins, $errors, false);
        }

        $excluded_values = self::getConvertedValues($this->params['type'], $this->excluded_values);
        if (!empty($excluded_values)) {
            $this->object->getCustomFilterSqlFilters($this->filter_name, $excluded_values, $filters, $joins, $errors, true);
        }

        return $errors;
    }

    public function getChildInstance()
    {
        if (is_null($this->child_instance)) {
            $child_instance = null;
            if (!is_null($this->bc_field)) {
                if ($this->params['type'] === 'user') {
                    $child_instance = BimpObject::getInstance('bimpcore', 'Bimp_User');
                } elseif (in_array($this->bc_field->params['type'], array('id_object', 'id_parent'))) {
                    if (isset($this->bc_field->params['object']) && (string) $this->bc_field->params['object']) {
                        $child_instance = $this->object->getChildObject($this->bc_field->params['object']);
                    } elseif ($this->bc_field->params['type'] === 'id_parent') {
                        $child_instance = $this->object->getParentInstance();
                    }
                }
            }

            if (!is_null($child_instance) && !is_a($child_instance, 'BimpObject')) {
                unset($child_instance);
                $child_instance = null;
            }

            if (is_null($child_instance)) {
                $this->child_instance = false;
            } else {
                $this->child_instance = $child_instance;
            }

            return $child_instance;
        } elseif ($this->child_instance === false) {
            return null;
        }

        return $this->child_instance;
    }

    public function getObjectIdsListLabel($ids_list)
    {
        $label = '';

        $sep = BimpTools::getArrayValueFromPath($ids_list, 'separator', '');

        if (!$sep) {
            $label .= '<span class="danger">Séparateur absent</span>';
        } else {
            $list = BimpTools::getArrayValueFromPath($ids_list, 'list', '');

            if ($list === '') {
                $label .= '<span class="danger">Aucun ID spécifié</span>';
            } else {
                $ids = array();
                $invalid_ids = array();

                foreach (explode($sep, $list) as $id) {
                    if (in_array($id, array('0', 'null', 'NULL'))) {
                        $ids[] = 0;
                    } elseif (preg_match('/^[0-9]+$/', $id)) {
                        $ids[] = $id;
                    } else {
                        $invalid_ids[] = $id;
                    }
                }

                if (empty($ids)) {
                    $label .= '<span class="danger">Aucun ID valide</span>';
                } else {
                    $child_instance = $this->getChildInstance();
                    $found = array();
                    $unfound = array();

                    if (!is_null($child_instance) && is_a($child_instance, 'BimpObject')) {
                        $ref_prop = $child_instance->getRefProperty();
                        $primary = $child_instance->getPrimary();

                        if ($ref_prop) {
                            $rows = BimpCache::getBdb()->getRows($child_instance->getTable(), $primary . ' IN (' . implode(',', $ids) . ')', null, 'array', array($primary, $ref_prop));

                            if (is_array($rows)) {
                                foreach ($rows as $r) {
                                    $found[(int) $r[$primary]] = '#' . $r[$primary] . ' <b>' . $r[$ref_prop] . '</b>';
                                }
                            }
                        } else {
                            $rows = BimpCache::getBdb()->getRows($child_instance->getTable(), $primary . ' IN (' . implode(',', $ids) . ')', null, 'array', array($primary));
                            if (is_array($rows)) {
                                foreach ($rows as $r) {
                                    $found[(int) $r[$primary]] = '#<b>' . $r[$primary] . '</b>';
                                }
                            }
                        }

                        foreach ($ids as $id) {
                            if ($id === '') {
                                $id = 0;
                            }

                            if (!isset($found[(int) $id])) {
                                $unfound[] = '#' . $id;
                            }
                        }
                    }

                    $str = '';

                    if (!empty($found)) {
                        $str .= '<span class="success">' . count($found) . ' ' . $child_instance->getLabel((count($found) > 1 ? 'name_plur' : '')) . ' trouvé' . (count($found) > 1 ? 's' : '') . ': </span><br/>';
                        $str .= implode(' | ', $found);
                    }

                    if (!empty($unfound)) {
                        $str .= '<br/><br/>';
                        $str .= '<span class="warning">' . count($unfound) . ' non trouvé' . (count($unfound) > 1 ? 's' : '') . ': </span><br/>';
                        $str .= implode(' | ', $unfound);
                    }

                    if (!empty($invalid_ids)) {
                        $str .= '<br/><br/>';
                        $str .= '<span class="danger">' . count($invalid_ids) . ' ID invalide' . (count($invalid_ids) > 1 ? 's' : '') . ': </span><br/>';
                        $str .= implode(' | ', $invalid_ids);
                    }

                    $str_no_tags = strip_tags(str_replace('<br/>', ' ', $str));

                    if (strlen($str_no_tags) > 30) {
                        $label .= '<span class="bs-popover"' . BimpRender::renderPopoverData($str, 'top', 'true') . '>';
                        $label .= substr($str_no_tags, 0, 30) . '[...]';
                        $label .= '</span>';
                    } else {
                        $label .= $str_no_tags;
                    }
                }
            }
        }

        return $label;
    }

    public function getObjectChildIdFiltersLabel($id_filters)
    {
        $label = '';

        $filters_panel = $this->getChildFiltersPanel($id_filters);

        if (is_a($filters_panel, 'BC_FiltersPanel')) {
            $listFilters = BimpCache::getBimpObjectInstance('bimpuserconfig', 'ListFilters', $id_filters);

            $label_str = 'Filtres ';
            if (BimpObject::objectLoaded($listFilters)) {
                $label_str .= '"' . $listFilters->getData('name') . '"';
            } else {
                $label_str .= '#' . $id_filters;
            }

            $popover = '';

            $filters_html = $filters_panel->renderActiveFilters(true, false);

            if ($filters_html) {
                $popover .= '<div class="list_active_filters">';
                $popover .= $filters_html;
                $popover .= '</div>';

                $label .= '<span class="bs-popover"' . BimpRender::renderPopoverData($popover, 'top', true) . '>';
                $label .= $label_str;
                $label .= '</span>';
            } else {
                $label .= $label_str;
            }
        } elseif (isset($filters_panel['errors'])) {
            $label .= '<span class="danger bs-popover"' . BimpRender::renderPopoverData(BimpRender::renderAlerts($filters_panel['errors']), 'top', 'true') . '>';
            $label .= 'Erreurs';
            $label .= '</span>';
        }

        return $label;
    }

    public function getChildFiltersPanel($id_filters)
    {
        if (!isset($this->child_filters_panels[$id_filters])) {
            $child_instance = $this->getChildInstance();

            if (!is_null($child_instance) && is_a($child_instance, 'BimpObject')) {
                $filters_panel = new BC_FiltersPanel($child_instance);

                if (!$filters_panel->isOk() || !$filters_panel->isObjectValid()) {
                    unset($filters_panel);
                    $filters_panel = array(
                        'errors' => (!empty($filters_panel->errors) ? $filters_panel->errors : array('Filtres invalides'))
                    );
                } else {
                    $errors = $filters_panel->loadSavedValues($id_filters);

                    if (!empty($errors)) {
                        unset($filters_panel);
                        $filters_panel = array(
                            'errors' => $errors
                        );
                    }
                }

                $this->child_filters_panels[$id_filters] = $filters_panel;
            } else {
                $this->child_filters_panels[$id_filters] = array(
                    'errors' => array('Objet invalide')
                );
            }
        }

        return $this->child_filters_panels[$id_filters];
    }

    // Rendus HTML:

    public function renderHtml()
    {
        if (!$this->isOk() || !$this->isObjectValid()) {
            return '';
        }

        if (!$this->params['show']) {
            return '';
        }

        if (in_array($this->filter_name, $this->object->params['fields']) && !$this->object->isFieldActivated($this->filter_name)) {
            return '';
        }

        $html = '';
        $html = parent::renderHtml();

        if (count($this->errors)) {
            return $html;
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }

        $prev_bc = $current_bc;
        $current_bc = $this;

        $type = $this->params['type'];
        $data_type = $this->params['data_type'];
        $open = $this->getParam('open', false);

        $html .= '<div id="' . $this->identifier . '" class="bimp_filter_container foldable_container ' . ($open ? 'open' : 'closed') . '" ';
        $html .= ' data-filter_name="' . str_replace(':', '___', $this->name) . '"';
        $html .= ' data-type="' . $type . '"';
        $html .= ' data-data_type="' . $data_type . '"';
        if ($this->params['type'] === 'value_part') {
            $html .= ' data-part_type="' . $this->params['part_type'] . '"';
        }

        foreach ($this->data as $data_name => $data_value) {
            $html .= ' data-' . $data_name . '="' . $data_value . '"';
        }

        if ($this->params['type'] === 'check_list') {
            $html .= ' onopen="hideFiltersValues($(this))"';
            $html .= ' onclose="showFiltersValues($(this))"';
        }
        $html .= '>';

        $html .= '<div class="bimp_filter_caption foldable_caption">';
        $html .= $this->getParam('label', $this->filter_name) . '<br/>';
        $title = self::getFilterTitle($this->base_object, $this->name);
        if (!$title) {
            $title = str_replace(':', ' > ', $this->name);
        }
        $html .= '<span class="smallInfo" style="font-weight: normal">' . $title . '</span>';
        $html .= '<span class="foldable-caret"></span>';
        $html .= '</div>';

        $html .= '<div class="bimp_filter_content foldable_content"' . (!$open ? ' style="display: none"' : '') . '>';

        $html .= '<div class="bimp_filter_input_container">';
        $html .= $this->renderAddInput();
        $html .= '</div>';

        $html .= '</div>';

        $values_hidden = false;

        if ($this->params['type'] === 'check_list' && $this->params['open']) {
            $values_hidden = true;
        }

        $html .= '<div class="bimp_filter_values_container"' . ($values_hidden ? ' style="display: none"' : '') . '>';
        foreach ($this->values as $value) {
            $html .= $this->renderFilterValue($value, false);
        }

        foreach ($this->excluded_values as $value) {
            $html .= $this->renderFilterValue($value, true);
        }
        $html .= '</div>';

        $html .= '</div>';

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderFilterValue($value, $excluded = false)
    {
        if (!$this->params['show']) {
            return '';
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = '';

        $label = $this->getFilterValueLabel($value);
        $type = $this->getValueType($this->params['type'], $value);

        if ($label) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            if ($this->is_default) {
                $html .= '<div class="bimp_filter_value_default' . ($excluded ? ' excluded' : '') . '">';
                $html .= 'Par défaut: <br/>';
                $html .= $label;
                $html .= '</div>';
            } else {
                $html .= '<div class="bimp_filter_value' . ($excluded ? ' excluded' : '') . '" data-value="' . htmlentities($value) . '" onclick="editBimpFilterValue($(this))" data-type="' . $type . '">';
                $html .= '<span class="bimp_filter_value_remove_btn" onclick="removeBimpFilterValue(event, $(this));">';
                $html .= BimpRender::renderIcon('fas_times');
                $html .= '</span>';
                $html .= $label;
                $html .= '</div>';
            }
        }

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderAddInput()
    {
        if (!$this->params['show']) {
            return '';
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = '';

        $input_name = 'add_' . str_replace(':', '___', $this->name) . '_filter';

        $input_path = '';

        $field_params = array();
        if (!is_null($this->bc_field)) {
            $field_params = $this->bc_field->params;

            if ($this->object->config->isDefined('fields/' . $this->bc_field->name . '/search/input')) {
                $input_path = 'fields/' . $this->bc_field->name . '/search/input';
            } elseif ($this->object->config->isDefined('fields/' . $this->bc_field->name . '/filter/input')) {
                $input_path = 'fields/' . $this->bc_field->name . '/filter/input';
            } else {
                $input_path = 'fields/' . $this->bc_field->name . '/input';
            }
        } elseif ($this->object->config->isDefined('filters/' . $this->filter_name . '/input')) {
            $input_path = 'filters/' . $this->filter_name . '/input';
        }

        $add_btn_html = '<div style="text-align: right; margin: 2px 0">';
        if ((int) $this->params['exclude_btn']) {
            $add_btn_html .= '<button type="button" class="btn btn-default-danger btn-small" onclick="addFieldFilterValue($(this), true);">';
            $add_btn_html .= BimpRender::renderIcon('fas_times-circle', 'iconLeft') . 'Exclure';
            $add_btn_html .= '</button>';
        }
        if ((int) $this->params['add_btn']) {
            $add_btn_html .= '<button type="button" class="btn btn-default btn-small" onclick="addFieldFilterValue($(this), false);">';
            $add_btn_html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
            $add_btn_html .= '</button>';
        }
        $add_btn_html .= '</div>';

        switch ($this->params['type']) {
            case 'user':
            case 'value':
                $html .= $this->renderValueInput($input_name, $add_btn_html, $input_path, $field_params);
                break;

            case 'value_part':
                $html .= $this->renderValuePartInput($input_name, $add_btn_html);
                break;

            case 'date_range':
                $input_type = $this->params['data_type'];
                if ($input_type === 'datetime') {
                    $input_type = 'date';
                }
                $html .= $this->renderDateRangeInput($input_type . '_range', $input_name, $add_btn_html);
                break;

            case 'range':
                $input_options = array(
                    'data' => array(
                        'data_type' => 'number',
                        'decimals'  => 8,
                        'min'       => 'none',
                        'max'       => 'none'
                    )
                );

                $html .= '<b>Min:</b><br/>' . BimpInput::renderInput('text', $input_name . '_min', '', $input_options);
                $html .= '<b>Max:</b><br/>' . BimpInput::renderInput('text', $input_name . '_max', '', $input_options);

                $html .= $add_btn_html;
                break;

            case 'check_list':
                if (!isset($this->params['items'])) {
                    $this->params['items'] = array();
                }
                $html .= BimpInput::renderInput('check_list', $input_name, $this->values, array(
                            'items'              => $this->params['items'],
                            'select_all_buttons' => 1
                ));
                break;
        }

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderValueInput($input_name, $add_btn_html, $input_path, $field_params)
    {
        $html = '';
        $child_instance = $this->getChildInstance();

        if (!is_null($child_instance) && is_a($child_instance, 'BimpObject')) {
            global $user;

            // Select du type: 
            $type_options = array(
                ''        => ($this->params['type'] === 'user' ? 'Sélection' : 'Recherche') . ' ' . $child_instance->getLabel('of_a'),
                'ids'     => 'Liste d\'IDs',
                'filters' => 'Filtres enregistrés'
            );

            if ($this->params['type'] === 'user') {
                $type_options['current'] = 'Utilisateur connecté';
            }

            $html .= '<div class="bimp_filter_type_select">';
            $html .= 'Type: <br/>';
            $html .= BimpInput::renderInput('select', $input_name . '_filter_type', ($this->cur_value_type ? $this->cur_value_type : 'search'), array(
                        'options' => $type_options
            ));
            $html .= '</div>';

            // Type recherche d'objet: 
            $html .= '<div class="bimp_filter_type_container bimp_filter_type_">';
            $bc_input = new BC_Input($this->object, $this->params['data_type'], $input_name, $input_path, null, $field_params);
            $html .= $bc_input->renderHtml();
            $html .= $add_btn_html;
            $html .= '</div>';

            // Type liste d'IDs: 
            $html .= '<div class="bimp_filter_type_container bimp_filter_type_ids">';
            $html .= '<div>';
            $html .= '<b>Séparateur:</b> <br/>';
            $html .= '<input type="text" name="' . $input_name . '_ids_serparator" value=";" class="bimp_filter_object_ids_separator"/>';
            $html .= '</div>';
            $html .= '<div style="margin-top: 5px">';
            $html .= '<b>Liste d\'IDs:</b><br/>';
            $html .= '<input type="text" name="' . $input_name . '_ids_list" value="" class="bimp_filter_object_ids_list"/>';
            $html .= '</div>';
            $html .= '<div style="text-align: right; margin-top: 2px">';
            if ((int) $this->params['exclude_btn']) {
                $html .= '<button type="button" class="btn btn-default-danger btn-small" onclick="addFieldFilterObjectIDs($(this), true)">';
                $html .= BimpRender::renderIcon('fas_times-circle', 'iconLeft') . 'Exclure';
                $html .= '</button>';
            }
            if ((int) $this->params['add_btn']) {
                $html .= '<button type="button" class="btn btn-default btn-small" onclick="addFieldFilterObjectIDs($(this), false)">';
                $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
                $html .= '</button>';
            }
            $html .= '</div>';
            $html .= '</div>';

            // Type filtres enregistrés: 
            $html .= '<div class="bimp_filter_type_container bimp_filter_type_filters">';
            $userChildFilters = ListFilters::getUserConfigsArray($user->id, $child_instance, '', true);

            if (count($userChildFilters) == 1) {
                $html .= BimpRender::renderAlerts('Aucuns filtres enregistrés', 'warning');
                $list_page_url = $child_instance->getListPageUrl();
                if ($list_page_url) {
                    $html .= '<a href="' . $list_page_url . '" target="_blank">Définir des filtres pour ' . $child_instance->getLabel('the_plur') . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a><br/>';
                    $html .= '<span class="smallInfo">Actualisez la liste pour rafraîchir</span>';
                }
            } else {
                $html .= BimpInput::renderInput('select', $input_name . '_child_filters', 0, array(
                            'extra_class' => 'bimp_filter_child_id_filters',
                            'options'     => $userChildFilters
                ));
                $html .= '<div style="text-align: right; margin-top: 2px">';
                if ((int) $this->params['exclude_btn']) {
                    $html .= '<button type="button" class="btn btn-default-danger btn-small" onclick="addFieldFilterObjectFilters($(this), true)">';
                    $html .= BimpRender::renderIcon('fas_times-circle', 'iconLeft') . 'Exclure';
                    $html .= '</button>';
                }
                if ((int) $this->params['add_btn']) {
                    $html .= '<button type="button" class="btn btn-default btn-small" onclick="addFieldFilterObjectFilters($(this), false)">';
                    $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
                    $html .= '</button>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';

            // Type user connecté: 
            if ($this->params['type'] === 'user') {
                $html .= '<div class="bimp_filter_type_container bimp_filter_type_current" style="text-align: right">';
                if ((int) $this->params['exclude_btn']) {
                    $html .= '<span class="btn btn-default-danger btn-small" onclick="addFieldFilterCustomValue($(this), \'current\', true)">';
                    $html .= BimpRender::renderIcon('fas_times-circle', 'iconLeft') . 'Exclure';
                    $html .= '</span>';
                }
                if ((int) $this->params['add_btn']) {
                    $html .= '<span class="btn btn-default btn-small" onclick="addFieldFilterCustomValue($(this), \'current\', false)">';
                    $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
                    $html .= '</span>';
                }
                $html .= '</div>';
            }
        } else {
            $bc_input = new BC_Input($this->object, $this->params['data_type'], $input_name, $input_path, null, $field_params);
            $html .= $bc_input->renderHtml();
            $html .= $add_btn_html;
        }

        return $html;
    }

    public function renderValuePartInput($input_name, $add_btn_html)
    {
        $html = '';

        $html .= BimpInput::renderInput('select', $input_name . '_part_type', $this->params['part_type'], array(
                    'options' => array(
                        'middle'    => 'Contient',
                        'full'      => 'Est égal à',
                        'beginning' => 'Commence par',
                        'end'       => 'Fini par'
                    )
        ));

        $html .= BimpInput::renderInput('text', $input_name, '');
        $html .= $add_btn_html;

        return $html;
    }

    public function renderDateRangeInput($input_type, $input_name, $add_btn_html)
    {
        $html = '';

        if ($input_type === 'date_range') {
            $html .= '<div class="bimp_filter_type_select">';
            $html .= 'Type: <br/>';
            $html .= BimpInput::renderInput('select', $input_name . '_filter_type', ($this->cur_value_type ? $this->cur_value_type : 'min_max'), array(
                        'options' => array(
                            'min_max' => 'Min / Max',
                            'period'  => 'Période relative',
                            'option'  => 'Date relative à ajourd\'hui'
                        )
            ));
            $html .= '</div>';

            $html .= '<div class="bimp_filter_type_container bimp_filter_type_period bimp_filter_date_range_period">';
            $html .= '<div>';
            $html .= '<b>Période passée:</b> <br/>';
            $html .= BimpInput::renderInput('qty', $input_name . '_period_qty', 0, array(
                        'extra_class' => 'bimp_filter_date_range_period_qty',
                        'data'        => array(
                            'data_type' => 'number',
                            'min'       => 0,
                            'max'       => 'none',
                            'unsigned'  => 1,
                            'decimals'  => 0
                        )
            ));
            $html .= BimpInput::renderInput('select', $input_name . '_period_unit', 'y', array(
                        'extra_class' => 'bimp_filter_date_range_period_unit ',
                        'options'     => array(
                            'n' => 'Année(s)',
                            'm' => 'Mois',
                            'j' => 'Jour(s)',
                        )
            ));
            $html .= '</div>';
            $html .= '<div style="margin-top: 5px">';
            $html .= '<b>Décalage (-):</b> <br/>';
            $html .= BimpInput::renderInput('qty', $input_name . '_period_offset_qty', 0, array(
                        'extra_class' => 'bimp_filter_date_range_offset_qty',
                        'data'        => array(
                            'data_type' => 'number',
                            'min'       => 'none',
                            'max'       => 'none',
                            'unsigned'  => 0,
                            'decimals'  => 0
                        )
            ));
            $html .= BimpInput::renderInput('select', $input_name . '_period_offset_unit', 'y', array(
                        'extra_class' => 'bimp_filter_date_range_offset_unit ',
                        'options'     => array(
                            'n' => 'Année(s)',
                            'm' => 'Mois',
                            'j' => 'Jour(s)',
                        )
            ));
            $html .= '</div>';
            $html .= '<div style="margin-top: 5px">';
            $html .= '<b>Mode:</b>';
            $html .= BimpInput::renderInput('select', $input_name . '_period_mode', 'abs', array(
                        'extra_class' => 'bimp_filter_date_range_period_mode ',
                        'options'     => array(
                            'abs' => 'Absolu',
                            'rel' => 'Relatif'
                        )
            ));
            $html .= '</div>';
            $html .= '<div class="date_range_limits_container" style="margin-top: 5px">';
            $html .= '<b>Limites:</b><br/>';
            $html .= BimpInput::renderInput($input_type, $input_name . '_period_limit', '', array(
                        'from_label' => 'Min',
                        'to_label'   => 'Max'
            ));
            $html .= '</div>';

            $html .= '<div style="text-align: right; margin-top: 2px">';
            if ((int) $this->params['exclude_btn']) {
                $html .= '<button type="button" class="btn btn-default-danger btn-small" onclick="addFieldFilterDateRangePeriod($(this), true)">';
                $html .= BimpRender::renderIcon('fas_times-circle', 'iconLeft') . 'Exclure';
                $html .= '</button>';
            }
            if ((int) $this->params['add_btn']) {
                $html .= '<button type="button" class="btn btn-default btn-small" onclick="addFieldFilterDateRangePeriod($(this), false)">';
                $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
                $html .= '</button>';
            }
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div class="bimp_filter_type_container bimp_filter_type_option bimp_filter_date_range_option">';
            $html .= BimpInput::renderInput('select', $input_name . 'date_range_option', '', array(
                        'extra_class' => 'bimp_filter_date_range_option',
                        'options'     => array(
                            ''          => '',
                            'max_today' => '<= Aujourd\'hui',
                            'min_today' => '>= Aujourd\'hui',
                            'max_yd'    => '< Aujourd\'hui',
                            'min_yd'    => '> Aujourd\'hui'
                        )
            ));
            $html .= '<div style="text-align: right; margin-top: 2px">';
            if ((int) $this->params['exclude_btn']) {
                $html .= '<button type="button" class="btn btn-default-danger btn-small" onclick="addFieldFilterDateRangeOption($(this), true)">';
                $html .= BimpRender::renderIcon('fas_times-circle', 'iconLeft') . 'Exclure';
                $html .= '</button>';
            }
            if ((int) $this->params['add_btn']) {
                $html .= '<button type="button" class="btn btn-default btn-small" onclick="addFieldFilterDateRangeOption($(this), false)">';
                $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
                $html .= '</button>';
            }
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div class="bimp_filter_type_container bimp_filter_type_min_max">';
            $html .= BimpInput::renderInput($input_type, $input_name);
            $html .= $add_btn_html;
            $html .= '</div>';
        } else {
            $html .= BimpInput::renderInput($input_type, $input_name);
            $html .= $add_btn_html;
        }

        return $html;
    }

    // Getters statics: 

    public static function getFilterObject($base_object, $full_name, &$filter_name = '', &$errors = array())
    {
        $filter_object = null;
        $children = explode(':', $full_name);
        $filter_name = array_pop($children);

        if (is_a($base_object, 'BimpObject')) {
            if ((string) $filter_name) {
                $filter_object = $base_object;

                if (count($children)) {
                    foreach ($children as $child_name) {
                        $child = $filter_object->getChildObject($child_name);

                        if (is_a($child, 'BimpObject')) {
                            $filter_object = $child;
                        } else {
                            $errors[] = 'Instance enfant "' . $child_name . '" invalide pour l\'objet "' . $filter_object->object_name . '"';
                            break;
                        }
                    }
                }
            } else {
                $errors[] = 'Nom du filtre absent';
            }
        } else {
            $errors[] = 'Object associé invalide';
        }

        return $filter_object;
    }

    public static function getFilterDefaultLabel($base_object, $full_name)
    {
        $full_name = str_replace('___', ':', $full_name);

        $label = '';
        if (is_a($base_object, 'BimpObject')) {
            $label = $base_object->getConf('filters/' . $full_name . '/label', '');

            if (!$label) {
                $label = $base_object->getConf('fields/' . $full_name . '/label', '');

                if (!$label) {
                    $filter_name = '';
                    $errors = array();
                    $filter_object = BC_Filter::getFilterObject($base_object, $full_name, $filter_name, $errors);

                    if (!count($errors) && $filter_name !== $full_name) {
                        $label = $filter_object->getConf('filters/' . $filter_name . '/label', '');

                        if (!$label) {
                            $label = $filter_object->getConf('fields/' . $filter_name . '/label', '');

                            if (!$label) {
                                $label = $filter_name;
                            }
                        }
                    }
                }
            }
        }

        if (!$label) {
            $label = $full_name;
        }

        return $label;
    }

    public static function getFilterTitle($base_object, $full_name, &$errors = array())
    {
        $full_name = str_replace('___', ':', $full_name);

        $title = '';

        if (is_a($base_object, 'BimpObject')) {
            $title = BimpTools::ucfirst($base_object->getLabel()) . ' > ';
            $children = explode(':', $full_name);
            $filter_name = array_pop($children);
            $filter_obj = $base_object;

            while (!empty($children)) {
                $child_name = array_shift($children);

                if ($child_name) {
                    $child = $filter_obj->getChildObject($child_name);

                    if (is_a($child, 'BimpObject')) {
                        $child_label = $filter_obj->getChildLabel($child_name);
                        $title .= BimpTools::ucfirst($child_label) . ' > ';
                        $filter_obj = $child;
                    } else {
                        $errors[] = 'L\'objet lié "' . $child_name . '" n\'existe pas pour les ' . $filter_obj->getLabel('name_plur');
                        $title = '';
                        break;
                    }
                }
            }

            if (!count($errors)) {
                $label = '';
                if (is_a($filter_obj, 'BimpObject')) {
                    $label = $filter_obj->getConf('filters/' . $filter_name . '/label', '');

                    if (!$label) {
                        $label = $filter_obj->getConf('fields/' . $filter_name . '/label', '');
                    }
                }

                if (!$label) {
                    $label = $filter_name;
                }

                $title .= $label;
            }
        }

        if (!$title) {
            $title = $full_name;
        }

        return $title;
    }

    public static function getRangeSqlFilter($value, &$errors = array(), $is_dates = false, $excluded = false)
    {
        $filter = array();

        if (is_array($value)) {
            if ($is_dates && isset($value['period']) && is_array($value['period']) && !empty($value['period'])) {
                $value = self::convertDateRangePeriodValue($value['period']);
            }

            if (isset($value['max']) && $value['max'] !== '') {
                if (preg_match('/^\d{4}\-\d{2}\-\d{2}?$/', $value['max'])) {
                    $value['max'] .= ' 23:59:59';
                }
            }

            if (isset($value['min']) || isset($value['max'])) {
                if ($value['min'] !== '' && $value['max'] === '') {
                    if ($excluded) {
                        $filter = array(
                            'operator' => '<=',
                            'value'    => $value['min']
                        );
                    } else {
                        $filter = array(
                            'operator' => '>=',
                            'value'    => $value['min']
                        );
                    }
                } elseif ($value['max'] !== '' && $value['min'] === '') {
                    if ($excluded) {
                        $filter = array(
                            'operator' => '>=',
                            'value'    => $value['max']
                        );
                    } else {
                        $filter = array(
                            'operator' => '<=',
                            'value'    => $value['max']
                        );
                    }
                } else {
                    $filter = array(
                        'min' => $value['min'],
                        'max' => $value['max']
                    );
                    if ($excluded) {
                        $filter['not'] = 1;
                    }
                }
            } else {
                $errors[] = 'Valeurs minimales et maximales absentes';
            }
        } else {
            $errors[] = 'Valeur invalide: "' . $value . '"';
        }

        return $filter;
    }

    public static function getValuePartSqlFilter($value, $part_type, $excluded = false)
    {
        if (is_array($value) && isset($value['value'])) {
            $value = $value['value'];
            $part_type = (isset($value['part_type']) ? $value['part_type'] : $part_type);
        }

        $value = (string) $value;
        $filter = array();

        if (preg_match('/;/', $value)) {
            $values = explode(';', $value);

            $filter = array(
                ($excluded ? 'and' : 'or_field') => array()
            );

            $sub_filter = array(
                'part_type' => $part_type
            );

            if ($excluded) {
                $sub_filter['not'] = 1;
            }

            foreach ($values as $val) {
                if ((string) $val) {
                    $sub_filter['part'] = $val;
                    $filter[($excluded ? 'and' : 'or_field')][] = $sub_filter;
                }
            }
        } else {
            $filter = array(
                'part_type' => $part_type,
                'part'      => $value
            );

            if ($excluded) {
                $filter['not'] = 1;
            }
        }

        return $filter;
    }

    public static function getValuePartLabel($value, $part_type)
    {
        $label = '';

        if (in_array($part_type, array('middle', 'end'))) {
            $label .= '... ';
        }

        if (!(string) $value && $part_type === 'full') {
            $label .= '(vide)';
        } else {
            $value = str_replace(';', ' | ', $value);
            if (strlen($value) > 30) {
                $label .= '<span class="bs-popover"' . BimpRender::renderPopoverData($value) . '>';
                $label .= substr($value, 0, 30) . '[...]';
                $label .= '</span>';
            } else {
                $label .= $value;
            }
        }
        if (in_array($part_type, array('middle', 'beginning'))) {
            $label .= ' ...';
        }
        return $label;
    }

    public static function getDateRangePeriodLabel($value)
    {
        $label = '';
        if (isset($value['qty']) && (int) $value['qty'] && isset($value['unit']) && (string) $value['unit']) {
            if (isset($value['mode']) && $value['mode'] === 'rel') {
                $label .= '-' . $value['qty'] . ' ';
                switch ($value['unit']) {
                    case 'n':
                        $label .= 'an' . ((int) $value['qty'] > 1 ? 's' : '');
                        break;

                    case 'm':
                        $label .= 'mois';
                        break;

                    case 'j':
                        $label .= 'jour' . ((int) $value['qty'] > 1 ? 's' : '');
                        break;
                }
            } else {
                if ((int) $value['qty'] === 1) {
                    switch ($value['unit']) {
                        case 'n':
                            $label .= 'Année en cours';
                            break;

                        case 'm':
                            $label .= 'Mois en cours';
                            break;

                        case 'j':
                            $label .= 'Ajourd\'hui';
                            break;
                    }
                } else {
                    $label = $value['qty'];
                    switch ($value['unit']) {
                        case 'n':
                            $label .= ' dernières années';
                            break;

                        case 'm':
                            $label .= ' derniers mois';
                            break;

                        case 'j':
                            $label .= ' derniers jours';
                            break;
                    }
                }
            }
        }

        if (!$label) {
            $label = '<span class="danger">Période invalide</span>';
        }

        if (isset($value['offset_qty']) && (int) $value['offset_qty'] && isset($value['offset_unit']) && (string) $value['offset_unit']) {
            $label .= ' (' . ($value['offset_qty'] > 0 ? '-' : '+') . abs((int) $value['offset_qty']) . ' ';
            switch ($value['offset_unit']) {
                case 'n':
                    $label .= 'an' . ((int) $value['offset_qty'] > 1 ? 's' : '');
                    break;

                case 'm':
                    $label .= 'mois';
                    break;

                case 'j':
                    $label .= 'jour' . ((int) $value['offset_qty'] > 1 ? 's' : '');
                    break;
            }
            $label .= ')';
        }

        $label .= '<br/>';

        if (isset($value['limit_min']) && (string) $value['limit_min']) {
            $label .= '<span style="font-style: italic">Limite min: ' . date('d / m / Y', strtotime($value['limit_min'])) . '</span><br/>';
        }
        if (isset($value['limit_max']) && (string) $value['limit_max']) {
            $label .= '<span style="font-style: italic">Limite max: ' . date('d / m / Y', strtotime($value['limit_max'])) . '</span><br/>';
        }

        return $label;
    }

    public static function convertDateRangePeriodValue($value)
    {
        $from = '';
        $to = '';

        if (isset($value['qty']) && (int) $value['qty'] && isset($value['unit']) && (string) $value['unit']) {
            if (!isset($value['mode']) || !(string) $value['mode']) {
                $value['mode'] = 'abs';
            }

            $qty = (int) $value['qty'];

            if ($value['mode'] === 'abs') {
                $qty -= 1;
            }

            $interval = '';
            if ($qty > 0) {
                $interval = 'P' . $qty . self::$periods[$value['unit']];
            }

            $offset = '';
            $offset_neg = false;

            if (isset($value['offset_qty']) && (int) $value['offset_qty'] !== 0 && isset($value['offset_unit']) && (string) $value['offset_unit']) {
                if ((int) $value['offset_qty'] < 0) {
                    $offset_neg = true;
                    $value['offset_qty'] = abs((int) $value['offset_qty']);
                }
                $offset = 'P' . $value['offset_qty'] . self::$periods[$value['offset_unit']];
            }

            $dt_from = new DateTime();
            $dt_to = new DateTime();

            if ($interval) {
                $dt_from->sub(new DateInterval($interval));
            }

            if ($offset) {
                if ($offset_neg) {
                    $dt_to->add(new DateInterval($offset));
                    $dt_from->add(new DateInterval($offset));
                } else {
                    $nMonth = 0;

                    if ($value['mode'] === 'abs') {
                        if (preg_match('/^P(\d+)M$/', $offset, $matches)) {
                            $nMonth = (int) $matches[1];
                        }
                    }

                    // Patch pour correction problème avec nombre de jours par mois en mode absolu: 
                    if ($nMonth && $nMonth < 12) {
                        $to_month = (int) $dt_to->format('m') - $nMonth;
                        $to_year = (int) $dt_to->format('Y');
                        $to_day = (int) $dt_to->format('d');
                        if ($to_month <= 0) {
                            $to_month = 12 - (abs($to_month));
                            $to_year--;
                        }
                        $day_max = cal_days_in_month(CAL_GREGORIAN, $to_month, $to_year);
                        if ($to_day > $day_max) {
                            $to_day = $day_max;
                        }
                        $dt_to = new DateTime($to_year . '-' . $to_month . '-' . $to_day);
                        
                        $from_month = (int) $dt_from->format('m') - $nMonth;
                        $from_year = (int) $dt_from->format('Y');
                        $from_day = (int) $dt_from->format('d');
                        if ($from_month <= 0) {
                            $from_month = 12 - (abs($from_month));
                            $from_year--;
                        }
                        $day_max = cal_days_in_month(CAL_GREGORIAN, $from_month, $from_year);
                        if ($from_day > $day_max) {
                            $from_day = $day_max;
                        }
                        $dt_from = new DateTime($from_year . '-' . $from_month . '-' . $from_day);
                    } else {
                        $dt_to->sub(new DateInterval($offset));
                        $dt_from->sub(new DateInterval($offset));
                    }
                }
            }

            $from = $dt_from->format('Y-m-d');
            $to = $dt_to->format('Y-m-d');

            if ($value['mode'] === 'abs') {
                $dt_now = new DateTime();
                switch ($value['unit']) {
                    case 'n':
                        $from = $dt_from->format('Y') . '-01-01';
                        if ($offset_neg) {
                            $to = $dt_to->format('Y') . '-12-31';
                        } else {
                            if ($dt_to->format('Y') < $dt_now->format('Y')) {
                                $to = $dt_to->format('Y') . '-12-31';
                            }
                        }
                        break;
                    case 'm':
                        $from = $dt_from->format('Y-m') . '-01';
                        if ($dt_to->format('Y-m') < $dt_now->format('Y-m')) {
                            $to = $dt_to->format('Y-m-t');
//                            $to = $dt_to->format('Y-m') . '-01';
//                            $dt_to = new DateTime($to);
//                            $dt_to->add(new DateInterval('P1M'));
//                            $dt_to->sub(new DateInterval('P1D'));
//                            $to = $dt_to->format('Y-m-d');
                        }
                        break;
                }
            }

            if (isset($value['limit_min']) && (string) $value['limit_min']) {
                if ($value['limit_min'] > $from) {
                    $from = $value['limit_min'];
                }
            }

            if (isset($value['limit_max']) && (string) $value['limit_max']) {
                if ($value['limit_max'] < $to) {
                    $to = $value['limit_max'];
                }
            }

            $from .= ' 00:00:00';
            $to .= ' 23:59:59';
        }

        return array(
            'min' => $from,
            'max' => $to
        );
    }

    public static function getDateRangeOptionLabel($option)
    {
        if (isset(self::$date_range_options[$option])) {
            return self::$date_range_options[$option] . '<br/>';
        }

        return '<span class="danger">Valeur invalide</span>';
    }

    public static function convertDateRangeOptionValue($option)
    {
        $value = array(
            'min' => '',
            'max' => ''
        );

        switch ($option) {
            case 'max_today':
                $value['max'] = date('Y-m-d') . ' 23:59:59';
                break;

            case 'min_today':
                $value['min'] = date('Y-m-d') . ' 00:00:00';
                break;

            case 'max_yd':
                $value['max'] = date('Y-m-d', strtotime('-1 day')) . ' 23:59:59';
                break;

            case 'min_yd':
                $value['min'] = date('Y-m-d', strtotime('-1 day')) . ' 00:00:00';
                break;
        }

        return $value;
    }

    public static function getConvertedValues($filter_type, $values)
    {
        foreach ($values as $idx => $value) {
            switch ($filter_type) {
                case 'value_part':
                    $part = (is_string($value) ? $value : (isset($value['value']) ? $value['value'] : ''));
                    $part_type = (isset($value['part_type']) ? $value['part_type'] : "middle"); //$this->params['part_type']);
                    $values[$idx] = array(
                        'value'     => $part,
                        'part_type' => $part_type
                    );
                    break;

                case 'date_range':
                    if (isset($value['period']) && is_array($value['period']) && !empty($value['period'])) {
                        $values[$idx] = self::convertDateRangePeriodValue($value['period']);
                    } elseif (isset($value['option'])) {
                        $values[$idx] = self::convertDateRangeOptionValue($value['option']);
                    }
                    break;

                case 'user':
                    if ($value === 'current') {
                        global $user;
                        if (!BimpObject::objectLoaded($user)) {
                            unset($values[$idx]);
                            break;
                        }
                        $values[$idx] = (int) $user->id;
                    }
                    break;
            }
        }

        return $values;
    }

    public static function getDefaultTypeFromDataType($data_type)
    {
        switch ($data_type) {
            case 'color':
            case 'bool':
            case 'id_object':
            case 'id_parent':
                return 'value';

            case 'string':
            case 'text':
            case 'html':
            case 'id':
                return 'value_part';

            case 'int':
            case 'float':
            case 'qty':
            case 'percent':
            case 'money':
                return 'range';

            case 'date':
            case 'time':
            case 'datetime':
                return 'date_range';
        }

        return '';
    }

    public static function getValueType($filter_type, $value)
    {
        switch ($filter_type) {
            case 'value':
            case 'user':
                if (is_array($value)) {
                    if (isset($value['ids_list'])) {
                        return 'ids';
                    }
                    if (isset($value['id_filters'])) {
                        return 'filters';
                    }
                    return '';
                } elseif ($filter_type === 'user' && $value === 'current') {
                    return 'current';
                }
                break;

            case 'date_range':
                if (is_array($value)) {
                    if (isset($value['period'])) {
                        return 'period';
                    }
                    if (isset($value['option'])) {
                        return 'option';
                    }
                    return 'min_max';
                }
                break;
        }

        return '';
    }
}
