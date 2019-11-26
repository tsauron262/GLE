<?php

class BC_FieldFilter extends BC_Filter
{

    public $base_object = null;
    public $child_name = '';
    public $field = null;
    public $field_params = array();

    public function __construct(BimpObject $object, $params, $panel_path = '', $values = array())
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

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

        $path = '';

        if (is_a($object, 'BimpObject') && (!$params['name'] || $params['name'] === 'default')) {
            if (!is_null($this->field)) {
                if ($this->field->object->config->isDefined('fields/' . $this->field->name . '/filter')) {
                    $params['name'] = '';
                    $path = 'fields/' . $this->field->name . '/filter';
                }
            }
        }

        parent::__construct($object, $params, $values, $path);

        if ($this->base_object->config->isDefined($panel_path)) {
            $this->params = self::override_params($this->params, $this->base_object->config, $panel_path, $this->params_def);

            if (empty($values) && !empty($this->params['default_values'])) {
                $this->values = $this->params['default_values'];
                $this->is_default = true;
            }

            if (isset($params['open'])) {
                $this->params['open'] = (int) $params['open'];
            }
        }

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

        $current_bc = $prev_bc;
    }

    public function getFilterValueLabel($value)
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

        $current_bc = $prev_bc;
        return $label;
    }

    public function getSqlFilters(&$filters = array(), &$joins = array())
    {
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
        $or_field = array();

        $filter_key = '';
        $field_name = $this->field->name;
        $filter_key = $this->base_object->getFieldSqlKey($field_name, 'a', $this->child_name, $joins, $errors, $this->object);

        if (!$filter_key) {
            $current_bc = $prev_bc;
            return $errors;
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
                    $or_field[] = $this->getDateRangeSqlFilter($value, $errors);
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

        $current_bc = $prev_bc;
        return $errors;
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

        $current_bc = $prev_bc;
        return $html;
    }
}
