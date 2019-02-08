<?php

class BC_Filter extends BimpComponent
{

    public $component_name = 'Filtres de liste';
    public static $type = 'filter';
    public static $config_required = false;
    public $field = null;
    public $field_params = array();
    public $identifier = '';
    public $values = array();
    public static $type_params_def = array(
        'value'      => array(),
        'value_part' => array(
            'part_type' => array('default' => 'middle')
        ),
        'range'      => array(),
        'check_list' => array(
            'items' => array('data_type' => 'array')
        )
    );

    public function __construct(BimpObject $object, $params, $values = array())
    {
        $this->params_def['type'] = array();
        $this->params_def['label'] = array();
        $this->params_def['open'] = array('data_type' => 'bool', 'default' => 1);

        if (isset($params['field']) && (string) $params['field']) {
            $field = new BC_Field($object, $params['field']);
            $this->field = $field;
        } else {
            // todo: custom... 
        }

        if (!isset($params['name'])) {
            $params['name'] = 'default';
        }

        $this->values = $values;

        $path = 'fields/' . $this->field->name . '/filters';
        if (is_a($object, 'BimpObject') && (!$params['name'] || $params['name'] === 'default')) {
            if (!is_null($this->field)) {
                if ($object->config->isDefined('fields/' . $this->field->name . '/filter')) {
                    $params['name'] = '';
                    $path = 'fields/' . $this->field->name . '/filter';
                }
            }
        }

        parent::__construct($object, $params['name'], $path);

        if (isset($params['open'])) {
            $this->params['open'] = (int) $params['open'];
        }

        if (is_null($this->field)) {
            $this->errors[] = 'Définitions du champ absentes';
        } else {
            if (is_null($this->params['label'])) {
                $this->params['label'] = (isset($this->field->params['label']) ? $this->field->params['label'] : $this->field->name);
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

            if (!is_null($this->object)) {
                $this->identifier .= $this->object->object_name;
            }

            $this->identifier .= '_' . $this->field->name . '_' . ($this->name ? $this->name . '_' : '') . 'filter';
        }

//        if ($params['field'] === 'code_centre') {
//            echo '<pre>';
//            print_r($this->params);
//            exit;
//        }
    }

    public function getFilterValueLabel($value)
    {
        $label = '';

        switch ($this->params['type']) {
            case 'value':
                $this->field->value = $value;
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
                    if (!isset($value['min'])) {
                        $label .= '&infin;';
                    } else {
                        $this->field->value = $value['min'];
                        $label .= $this->field->displayValue();
                    }

                    $label .= '</strong><br/>Max: <strong>';

                    if (!isset($value['max'])) {
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
                            if ((string) $value['min'] && !(string) $value['max']) {
                                $or_field[] = array(
                                    'operator' => '>=',
                                    'value'    => $value['min']
                                );
                            } elseif ((string) $value['max'] && !(string) $value['min']) {
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
                $filters[$this->field->name] = array(
                    'in' => $this->values
                );
                break;
        }

        if (!empty($or_field)) {
            $filters[$this->field->name] = array(
                'or_field' => $or_field
            );
        }

        return $errors;
    }

    public function renderHtml()
    {
        $html = '';
        $html = parent::renderHtml();

        if (count($this->errors)) {
            return $html;
        }

        $html .= '<div id="' . $this->identifier . '" class="bimp_filter_container foldable_container ' . ($this->params['open'] ? 'open' : 'closed') . '" data-field_name="' . $this->field->name . '" data-type="' . $this->params['type'] . '"';
        if ($this->params['type'] === 'value_part') {
            $html .= ' data-part_type="' . $this->params['part_type'] . '"';
        }
        $html .= '>';

        $html .= '<div class="bimp_filter_caption foldable_caption">';
        $html .= $this->params['label'];
        $html .= '<span class="foldable-caret"></span>';
        $html .= '</div>';

        $html .= '<div class="bimp_filter_content foldable_content"' . (!$this->params['open'] ? ' style="display: none"' : '') . '>';

        $html .= '<div class="bimp_filter_input_container">';
        $html .= $this->renderAddInput();
        $html .= '</div>';

        $html .= '</div>';

        $html .= '<div class="bimp_filter_values_container">';
        if (in_array($this->params['type'], array('value', 'value_part', 'range', 'date_range'))) {
            foreach ($this->values as $value) {
                $html .= $this->renderFilterValue($value);
            }
        }
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    public function renderFilterValue($value)
    {
        $html = '';

        $label = $this->getFilterValueLabel($value);

        if (!$label) {
            return '';
        }

        if (is_array($value)) {
            $value = json_encode($value);
        }

        $html .= '<div class="bimp_filter_value" data-value="' . htmlentities($value) . '">';
        $html .= '<span class="bimp_filter_value_remove_btn" onclick="removeBimpFilterValue($(this));">';
        $html .= BimpRender::renderIcon('fas_trash-alt');
        $html .= '</span>';
        $html .= $label;
        $html .= '</div>';

        return $html;
    }

    public function renderAddInput()
    {
        $html = '';

        $input_name = 'add_' . $this->field->name . '_filter';
        $input_path = 'fields/' . $this->field->name . '/input';

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
//                $html .= '<div style="padding-right: 42px">';
                $html .= BimpInput::renderInput('text', $input_name, '');
//                $html .= '<span style="float: right; margin-right: -40px;">';
                $html .= $add_btn_html;
//                $html .= '</span>';
//                $html .= '</div>';
                break;

            case 'date_range':
                $html .= BimpInput::renderInput($this->field->params['type'] . '_range', $input_name);
//                $html .= '<span style="float: right">';
                $html .= $add_btn_html;
//                $html .= '</span>';
                break;

            case 'range':
                $bc_input = new BC_Input($this->object, $this->field->params['type'], $input_name, $input_path, null, $this->field->params);
                $input_type = $bc_input->params['type'];
                $input_options = $bc_input->getOptions();

                $html .= '<span class="range_input_label">Min: </span>' . BimpInput::renderInput($input_type, $input_name . '_min', '', $input_options);
                $html .= '<span class="range_input_label">Max: </span>' . BimpInput::renderInput($input_type, $input_name . '_max', '', $input_options);
//                $html .= '<span style="float: right">';
                $html .= $add_btn_html;
//                $html .= '</span>';
                break;

            case 'check_list':
                if (!isset($this->params['items'])) {
                    $this->params['items'] = array();
                }
                $html .= BimpInput::renderInput('check_list', $input_name, $this->values, array(
                            'items' => $this->params['items']
                ));
                break;
        }

        return $html;
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
    }
}
