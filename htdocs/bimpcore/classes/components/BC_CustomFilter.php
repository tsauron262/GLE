<?php

class BC_CustomFilter extends BC_Filter
{

    public $field_name = '';
    public static $config_required = true;

    public function __construct(BimpObject $object, $params, $path, $values = array(), $excluded_values = array())
    {
        $this->params_def['data_type'] = array('default' => 'string');

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        if (isset($params['field']) && $params['field']) {
            $this->field_name = $params['field'];
        } else {
            $this->errors[] = 'Nom du champ absent';
        }

        $params['name'] = '';

        parent::__construct($object, $params, $values, $path, $excluded_values);

        $this->data['field_name'] = $this->field_name;
        $this->identifier .= '_field_' . $this->field_name;

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
            case 'user':
                if ($value === 'current') {
                    $label = 'Utilisateur connecté';
                    break;
                }
                $user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $value);
                global $langs;
                if (BimpObject::objectLoaded($user)) {
                    return $user->dol_object->getFullName($langs);
                }

            case 'value':
                $input_type = $this->object->getConf($this->config_path . '/input/type', '');
                if ($input_type === 'select') {
                    if ($this->object->config->isDefined($this->config_path . '/input/options')) {
                        $options = $this->object->getConf($this->config_path . '/input/options', array(), false, 'array');
                        if (isset($options[$value])) {
                            $current_bc = $prev_bc;
                            return $options[$value];
                        }
                    }
                }

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
                    if (isset($value['min']) || isset($value['max'])) {
                        $label .= ($is_dates ? 'Du' : 'Min') . ': <strong>';
                        if (!isset($value['min']) || $value['min'] === '') {
                            $label .= '-&infin;';
                        } else {
                            $label .= BimpTools::displayValueByType($value['min'], $this->params['data_type']);
                        }

                        $label .= '</strong><br/>';
                        $label .= ($is_dates ? 'Au' : 'Max') . ': <strong>';

                        if (!isset($value['max']) || $value['max'] === '') {
                            $label .= '&infin;';
                        } else {
                            $label .= BimpTools::displayValueByType($value['max'], $this->params['data_type']);
                        }
                        $label .= '</strong>';
                        break;
                    }
                }
                $label = '<span class="danger">Valeurs invalides</span>';
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
        if (!$this->params['show']) {
            return array();
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }

        $prev_bc = $current_bc;
        $current_bc = $this;

        $errors = array();

        $values = self::getConvertedValues($this->params['type'], $this->values);
        if (!empty($values)) {
            $this->object->getCustomFilterSqlFilters($this->field_name, $values, $filters, $joins, $errors, false);
        }

        $excluded_values = self::getConvertedValues($this->params['type'], $this->excluded_values);
        if (!empty($excluded_values)) {
            $this->object->getCustomFilterSqlFilters($this->field_name, $excluded_values, $filters, $joins, $errors, true);
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

        $input_name = 'add_' . $this->field_name . '_filter';

        $input_path = $this->config_path . '/input';

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

                if (!$this->object->config->isDefined($input_path)) {
                    $html .= BimpInput::renderInput('search_user', $input_name, 0);
                    $html .= $add_btn_html;
                    break;
                }

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
                $html .= $this->renderDateRangeInput($type . '_range', $input_name, $add_btn_html);
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

        $current_bc = $prev_bc;
        return $html;
    }
}
