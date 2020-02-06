<?php

class BC_FieldFilter extends BC_Filter
{

    public $base_object = null;
    public $child_name = '';
    public $field = null;
    public $field_params = array();

    public function __construct(BimpObject $object, $params, $panel_path = '', $values = array(), $excluded_values = array())
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

        parent::__construct($object, $params, $values, $path, $excluded_values);

        if ($this->base_object->config->isDefined($panel_path)) {
            $this->params = self::override_params($this->params, $this->base_object->config, $panel_path, $this->params_def);

            if (empty($values) && empty($excluded_values) && (!empty($this->params['default_values']) || !empty($this->params['default_excluded_values']))) {
                $this->values = $this->params['default_values'];
                $this->excluded_values = $this->params['default_excluded_values'];
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
                $input_type = $this->object->getConf('fields/' . $this->field->name . '/input/type', '');
                if ($input_type === 'search_user') {
                    $this->params['type'] = 'user';
                } elseif (isset($this->field->params['values']) && !empty($this->field->params['values'])) {
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
            case 'user':
                if ($this->params['type'] === 'user' && $value === 'current') {
                    $label = 'Utilisateur connecté';
                    break;
                }
                if ($this->field->params['type'] === 'id_object') {
                    $this->field->display_name = 'ref_nom';
                }
                $this->field->value = $value;
                $this->field->no_html = true;
                $label = $this->field->displayValue();
                break;

            case 'value_part':
                $label = self::getValuePartLabel($value, $this->params['part_type']);
                break;

            case 'range':
            case 'date_range':
                $is_dates = false;
                if ($this->params['type'] === 'date_range') {
                    $is_dates = true;
                }
                if (is_array($value)) {
                    $label = '';
                    if (isset($value['period']) && is_array($value['period']) && !empty($value['period'])) {
                        $label = self::getDateRangePeriodLabel($value['period']);
                        $value = self::convertDateRangePeriodValue($value['period']);
                    }
                    if ((isset($value['min']) || isset($value['max']))) {
                        $label .= ($is_dates ? 'Du' : 'Min') . ': <strong>';
                        if (!isset($value['min']) || $value['min'] === '') {
                            $label .= '-&infin;';
                        } else {
                            $this->field->value = $value['min'];
                            $label .= $this->field->displayValue();
                        }

                        $label .= '</strong><br/>';
                        $label .= ($is_dates ? 'Au' : 'Max') . ': <strong>';
                        if (!isset($value['max']) || $value['max'] === '') {
                            $label .= '&infin;';
                        } else {
                            $this->field->value = $value['max'];
                            $label .= $this->field->displayValue();
                        }

                        $label .= '</strong>';
                        break;
                    }
                }
                $label = '<span class="danger">Valeurs invalides</valeur>';
                break;

            default:
                $label = parent::getFilterValueLabel($value);
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

        $filter_key = '';
        $field_name = $this->field->name;
        $filter_key = $this->base_object->getFieldSqlKey($field_name, 'a', $this->child_name, $joins, $errors, $this->object);

        if (!$filter_key) {
            $current_bc = $prev_bc;
            return $errors;
        }

        $values = self::getConvertedValues($this->params['type'], $this->values);
        $excluded_values = self::getConvertedValues($this->params['type'], $this->excluded_values);

        $and_field = array();
        $or_field = array();

        switch ($this->params['type']) {
            case 'value':
            case 'user':
                foreach ($values as $value) {
                    if (BimpTools::checkValueByType($this->field->params['type'], $value)) {
                        $or_field[] = $value;
                    } else {
                        $errors[] = 'Valeur invalide: "' . $value . '" (doit être de type: "' . BimpTools::getDataTypeLabel($this->field->params['type']) . '"';
                    }
                }

                foreach ($excluded_values as $value) {
                    if (BimpTools::checkValueByType($this->field->params['type'], $value)) {
                        $and_field[] = array(
                            'operator' => '!=',
                            'value'    => $value
                        );
                    } else {
                        $errors[] = 'Valeur invalide: "' . $value . '" (doit être de type: "' . BimpTools::getDataTypeLabel($this->field->params['type']) . '"';
                    }
                }
                break;

            case 'value_part':
                foreach ($values as $value) {
                    $or_field[] = self::getValuePartSqlFilter($value, $this->params['part_type'], false);
                }
                foreach ($excluded_values as $value) {
                    $and_field[] = self::getValuePartSqlFilter($value, $this->params['part_type'], true);
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
                $html .= '<div style="Margin-bottom: 5px;padding-bottom: 5px; border-bottom: 1px solid #7D7D7D">';
                $html .= '<span style="font-size: 11px">';
                $html .= BimpRender::renderIcon('fas_user', 'iconLeft') . 'Utilisateur connecté:';
                $html .= '</span>';
                $html .= '<div style="margin-top: 4px; text-align: right">';
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
                $html .= '</div>';

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
                $html .= $this->renderDateRangeInput($type . '_range', $input_name, $add_btn_html);
                break;

            case 'range':
                $bc_input = new BC_Input($this->object, $this->field->params['type'], $input_name, $input_path, null, $this->field->params);
                $input_options = $bc_input->getOptions();

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
                            'select_all_buttons' => 1
                ));
                break;
        }

        $current_bc = $prev_bc;
        return $html;
    }
}
