<?php

class BC_FieldFilter extends BC_Filter
{

    public $base_object = null;
    public $child_name = '';
    public $field = null;
    public $field_params = array();

    public function __construct(BimpObject $object, $params, $values = array())
    {
        $this->base_object = $object;

        if (isset($params['field']) && (string) $params['field']) {
            if (isset($params['child']) && (string) $params['child']) {
                $field_object = $object->getChildObject($params['child']);

                if (is_a($field_object, 'BimpObject')) {
                    $object = $field_object;
                    $this->child_name = $params['child'];
                } else {
                    $this->errors[] = 'Objet enfant "' . $params['child'] . '" invalide';
                }
            }

            if (!count($this->errors)) {
                $field = new BC_Field($object, $params['field']);

                $this->field = $field;
                $this->field->params['editable'] = 1;
                $this->field->params['viewable'] = 1;
            }
        }

        if (!isset($params['name'])) {
            $params['name'] = 'default';
        }

        $path = 'fields/' . $this->field->name . '/filters';

        if (is_a($object, 'BimpObject') && (!$params['name'] || $params['name'] === 'default')) {
            if (!is_null($this->field)) {
                if ($this->field->object->config->isDefined('fields/' . $this->field->name . '/filter')) {
                    $params['name'] = '';
                    $path = 'fields/' . $this->field->name . '/filter';
                }
            }
        }

        parent::__construct($object, $params, $values, $path);

        if (!is_null($this->field)) {
            if (is_null($this->params['label'])) {
                $this->params['label'] = (isset($this->field->params['label']) ? $this->field->params['label'] : $this->field->name);
                if ($this->child_name) {
                    $this->params['label'] .= ' (' . $this->object->getLabel() . ')';
                }
            }

            if (is_null($this->params['type'])) {
                $items = null;
                if (isset($this->field->params['values']) && !empty($this->field->params['values'])) {
                    if (count($this->field->params['values']) <= 10) {
                        $this->params['type'] = 'check_list';
                        $items = $this->field->params['values'];
                    } else {
                        $this->params['type'] = 'value';
                    }
                } elseif ($this->field->params['type'] === 'bool') {
                    $this->params['type'] = 'check_list';
                    $items = array(
                        0 => 'NON',
                        1 => 'OUI'
                    );
                } else {
                    $this->params['type'] = self::getDefaultTypeFromDataType(isset($this->field->params['type']) ? $this->field->params['type'] : 'string');
                }

                if (array_key_exists($this->params['type'], static::$type_params_def)) {
                    foreach ($this->fetchParams($this->config_path, static::$type_params_def[$this->params['type']]) as $p_name => $value) {
                        $this->params[$p_name] = $value;
                    }
                }

                if (empty($this->params['items']) && !is_null($items)) {
                    $this->params['items'] = $items;
                }
            }

            $this->data['field_name'] = $this->field->name;
            $this->data['child_name'] = $this->child_name;

            if ($this->child_name) {
                $this->identifier .= '_child_' . $this->child_name;
            }
            $this->identifier . '_field_' . $this->field->name;
        } else {
            $this->errors[] = 'Champ associé invalide';
        }
    }

    public function getFilterValueLabel($value)
    {
        $label = '';

        switch ($this->params['type']) {
            case 'value':
                if ($this->field->params['type'] === 'id_object') {
                    $this->field->display_name = 'ref_nom';
                }
                $this->field->value = $value;
                $this->field->no_html = true;
                $label = $this->field->displayValue();
                break;

            case 'value_part':
                if (in_array($this->params['part_type'], array('beginning', 'middle'))) {
                    $label .= '... ';
                }
                $label .= $value;
                if (in_array($this->params['part_type'], array('middle', 'end'))) {
                    $label .= ' ...';
                }
                break;

            case 'range':
            case 'date_range':
                if (is_array($value) && (isset($value['min']) || isset($value['max']))) {
                    $label .= 'Min: <strong>';
                    if (!isset($value['min']) || $value['min'] === '') {
                        $label .= '-&infin;';
                    } else {
                        $this->field->value = $value['min'];
                        $label .= $this->field->displayValue();
                    }

                    $label .= '</strong><br/>Max: <strong>';

                    if (!isset($value['max']) || $value['max'] === '') {
                        $label .= '&infin;';
                    } else {
                        $this->field->value = $value['max'];
                        $label .= $this->field->displayValue();
                    }

                    $label .= '</strong>';
                } else {
                    $label = '<span class="danger">Valeurs invalides</valeur>';
                }
                break;
        }

        return $label;
    }

    public function getSqlFilters(&$filters = array(), &$joins = array())
    {
        $errors = array();
        $or_field = array();

        $filter_key = '';

        if ($this->field->params['extra']) {
            $filter_key = $this->object->getExtraFieldFilterKey($this->field->name, $joins, $this->child_name);
        } else {
            $field_name = $this->field->name;

            if ($this->child_name) {
                if ($this->object->isDolExtraField($field_name)) {
                    $alias = $this->child_name . '_ef';
                    $filter_key = $alias . '.';
                    if (!isset($joins[$alias])) {
                        $joins[$alias] = array(
                            'table' => $this->object->getTable() . '_extrafields',
                            'on'    => $alias . '.fk_object = a.' . $this->base_object->getChildIdProperty($this->child_name),
                            'alias' => $alias
                        );
                    }
                } else {
                    $filter_key = $this->child_name . '.';
                    if (!isset($joins[$this->child_name])) {
                        $joins[$this->child_name] = array(
                            'table' => $this->object->getTable(),
                            'on'    => $this->child_name . '.' . $this->object->getPrimary() . ' = a.' . $this->base_object->getChildIdProperty($this->child_name),
                            'alias' => $this->child_name
                        );
                    }
                }
            }
            $filter_key .= $field_name;
        }

        switch ($this->params['type']) {
            case 'value':
                foreach ($this->values as $value) {
                    if (BimpTools::checkValueByType($this->field->params['type'], $value)) {
                        $or_field[] = $value;
                    } else {
                        $errors[] = 'Valeur invalide: "' . $value . '" (doit être de type: "' . BimpTools::getDataTypeLabel($this->field->params['type']) . '"';
                    }
                }
                break;

            case 'value_part':
                foreach ($this->values as $value) {
                    $value = (string) $value;
                    if ($value !== '') {
                        $or_field[] = array(
                            'part_type' => $this->params['part_type'],
                            'part'      => $value
                        );
                    }
                }
                break;

            case 'date_range':
            case 'range':
                foreach ($this->values as $value) {
                    if (is_array($value)) {
                        if (isset($value['min']) || isset($value['max'])) {
                            if ($value['min'] !== '' && $value['max'] === '') {
                                $or_field[] = array(
                                    'operator' => '>=',
                                    'value'    => $value['min']
                                );
                            } elseif ($value['max'] !== '' && $value['min'] === '') {
                                $or_field[] = array(
                                    'operator' => '<=',
                                    'value'    => $value['max']
                                );
                            } else {
                                $or_field[] = array(
                                    'min' => $value['min'],
                                    'max' => $value['max']
                                );
                            }
                        } else {
                            $errors[] = 'Valeurs minimales et maximales absentes';
                        }
                    } else {
                        $errors[] = 'Valeur invalide: "' . $value . '"';
                    }
                }
                break;

            case 'check_list':
                $filters[$filter_key] = array(
                    'in' => $this->values
                );
                break;
        }

        if (!empty($or_field)) {
            $filters[$filter_key] = array(
                'or_field' => $or_field
            );
        }

        return $errors;
    }

    public function renderAddInput()
    {
        $html = '';

        $input_name = 'add_' . $this->field->name . '_filter';

        if ($this->object->config->isDefined('fields/' . $this->field->name . '/filter/input')) {
            $input_path = 'fields/' . $this->field->name . '/filter/input';
        } elseif ($this->object->config->isDefined('fields/' . $this->field->name . '/search/input')) {
            $input_path = 'fields/' . $this->field->name . '/search/input';
        } else {
            $input_path = 'fields/' . $this->field->name . '/input';
        }

        $onclick = 'addFieldFilterValue($(this), \'' . $this->field->name . '\');';

//        $add_btn_html .= BimpRender::renderRowButton('Ajouter', 'fas_plus-circle', $onclick);
        $add_btn_html = '<div style="text-align: right; margin: 2px 0">';
        $add_btn_html .= '<button type="button" class="btn btn-default btn-small" onclick="' . $onclick . '">';
        $add_btn_html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
        $add_btn_html .= '</button>';
        $add_btn_html .= '</div>';

        switch ($this->params['type']) {
            case 'value':
                $bc_input = new BC_Input($this->object, $this->field->params['type'], $input_name, $input_path, null, $this->field->params);
                $html .= $bc_input->renderHtml();
                $html .= $add_btn_html;
                break;

            case 'value_part':
                $html .= BimpInput::renderInput('text', $input_name, '');
                $html .= $add_btn_html;
                break;

            case 'date_range':
                $type = $this->field->params['type'];
                if ($type === 'datetime') {
                    $type = 'date';
                }
                $html .= BimpInput::renderInput($type . '_range', $input_name);
                $html .= $add_btn_html;
                break;

            case 'range':
                $bc_input = new BC_Input($this->object, $this->field->params['type'], $input_name, $input_path, null, $this->field->params);
//                $input_type = $bc_input->params['type'];
                $input_options = $bc_input->getOptions();
//
//                $html .= '<span class="range_input_label">Min: </span>' . BimpInput::renderInput($input_type, $input_name . '_min', '', $input_options);
//                $html .= '<span class="range_input_label">Max: </span>' . BimpInput::renderInput($input_type, $input_name . '_max', '', $input_options);

                $input_options = array(
                    'data' => array(
                        'data_type' => 'number',
                        'decimals'  => 8,
                        'min'       => 'none',
                        'max'       => 'none'
                    )
                );

                $html .= '<span class="range_input_label">Min: </span>' . BimpInput::renderInput('text', $input_name . '_min', '', $input_options);
                $html .= '<span class="range_input_label">Max: </span>' . BimpInput::renderInput('text', $input_name . '_max', '', $input_options);

                $html .= $add_btn_html;
                break;

            case 'check_list':
                if (!isset($this->params['items'])) {
                    $this->params['items'] = array();
                }
                $html .= BimpInput::renderInput('check_list', $input_name, $this->values, array(
                            'items'              => $this->params['items'],
                            'select_all_buttons' => 0
                ));
                break;
        }

        return $html;
    }
}
