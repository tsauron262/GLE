<?php

class BC_CustomFilter extends BC_Filter
{

    public $field_name = '';
    public static $config_required = true;

    public function __construct(BimpObject $object, $params, $path, $values = array())
    {
        $this->params_def['data_type'] = array('default' => 'string');

        if (isset($params['field']) && $params['field']) {
            $this->field_name = $params['field'];
        } else {
            $this->errors[] = 'Nom du champ absent';
        }

        $params['name'] = '';

        parent::__construct($object, $params, $values, $path);

        $this->data['field_name'] = $this->field_name;
        $this->identifier . '_field_' . $this->field_name;
    }

    public function getFilterValueLabel($value)
    {
        $label = '';

        switch ($this->params['type']) {
            case 'value':
                $label = $this->object->getCustomFilterValueLabel($this->field_name, $value);
                break;

            case 'value_part':
                if (in_array($this->params['part_type'], array('beginning', 'middle'))) {
                    $label .= '... ';
                }
                $label .= $this->object->getCustomFilterValueLabel($this->field_name, $value);
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
                        $label = BimpTools::displayValueByType($value['min'], $this->params['data_type']);
                    }

                    $label .= '</strong><br/>Max: <strong>';

                    if (!isset($value['max']) || $value['max'] === '') {
                        $label .= '&infin;';
                    } else {
                        $label = BimpTools::displayValueByType($value['max'], $this->params['data_type']);
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

        $this->object->getCustomFilterSqlFilters($this->field_name, $this->values, $filters, $joins, $errors);

        return $errors;
    }

    public function renderAddInput()
    {
        $html = '';

        $input_name = 'add_' . $this->field_name . '_filter';

        $input_path = $this->config_path . '/input';

        $onclick = 'addFieldFilterValue($(this), \'' . $this->field_name . '\');';

        $add_btn_html = '<div style="text-align: right; margin: 2px 0">';
        $add_btn_html .= '<button type="button" class="btn btn-default btn-small" onclick="' . $onclick . '">';
        $add_btn_html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter';
        $add_btn_html .= '</button>';
        $add_btn_html .= '</div>';

        switch ($this->params['type']) {
            case 'value':
                $bc_input = new BC_Input($this->object, $this->params['data_type'], $input_name, $input_path);
                $html .= $bc_input->renderHtml();
                $html .= $add_btn_html;
                break;

            case 'value_part':
                $html .= BimpInput::renderInput('text', $input_name, '');
                $html .= $add_btn_html;
                break;

            case 'date_range':
                $type = $this->params['data_type'];
                if ($type === 'datetime') {
                    $type = 'date';
                }
                $html .= BimpInput::renderInput($type . '_range', $input_name);
                $html .= $add_btn_html;
                break;

            case 'range':
                $bc_input = new BC_Input($this->object, $this->params['data_type'], $input_name, $input_path, null);
                $input_type = $bc_input->params['type'];
                $input_options = $bc_input->getOptions();

                $html .= '<span class="range_input_label">Min: </span>' . BimpInput::renderInput($input_type, $input_name . '_min', '', $input_options);
                $html .= '<span class="range_input_label">Max: </span>' . BimpInput::renderInput($input_type, $input_name . '_max', '', $input_options);
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
