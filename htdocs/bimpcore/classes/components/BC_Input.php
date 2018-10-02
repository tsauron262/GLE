<?php

class BC_Input extends BimpComponent
{

    public $component_name = 'Champ de saisi';
    public static $type = 'input';
    public static $config_required = false;
    public $data_type = '';
    public $input_name = '';
    public $input_id = '';
    public $value = null;
    public $new_value = null;
    public $field_params = array();
    public $option = null;
    public $extraClasses = array();
    public $extraData = array();
    public $name_prefix = '';
    public static $type_params_def = array(
        'text'                        => array(
            'values'       => array('data_type' => 'array', 'compile' => true, 'default' => array()),
            'allow_custom' => array('data_type' => 'bool', 'default' => 1)
        ),
        'qty'                         => array(
            'step' => array('data_type' => 'float', 'default' => 1)
        ),
        'time'                        => array(
            'display_now' => array('data_type' => 'bool', 'default' => 0)
        ),
        'date'                        => array(
            'display_now' => array('data_type' => 'bool', 'default' => 0)
        ),
        'datetime'                    => array(
            'display_now' => array('data_type' => 'bool', 'default' => 0)
        ),
        'textarea'                    => array(
            'rows'        => array('data_type' => 'int', 'default' => 3),
            'auto_expand' => array('data_type' => 'bool', 'default' => 0),
            'note'        => array('data_type' => 'bool', 'default' => 0),
            'values'      => array('data_type' => 'array', 'default' => array()),
        ),
        'select'                      => array(
            'options'      => array('data_type' => 'array', 'compile' => true, 'default' => array()),
            'select_first' => array('data_type' => 'bool', 'default_value' => 0)
        ),
        'switch_options'              => array(
            'options'  => array('data_type' => 'array', 'compile' => true, 'default' => array()),
            'vertical' => array('data_type' => 'bool', 'default_value' => 0)
        ),
        'toggle'                      => array(
            'toggle_on'  => array('default' => 'OUI'),
            'toggle_off' => array('default' => 'NON')
        ),
        'check_list'                  => array(
            'items' => array('data_type' => 'array', 'default' => array(), 'compile' => true)
        ),
        'custom'                      => array(
            'content' => array('default' => '')
        ),
        'search_product'              => array(
            'filter_type' => array('data_type' => 'any', 'default' => 0)
        ),
        'search_societe'              => array(
            'societe_type' => array('default' => '')
        ),
        'search_state'                => array(
            'id_country' => array('data_type' => 'int', 'default' => 0)
        ),
        'search_juridicalstatus'      => array(
            'country_code' => array('default' => '0')
        ),
        'search_commande_client'      => array(
            'id_client' => array('data_type' => 'int', 'default' => 0)
        ),
        'search_commande_fournisseur' => array(
            'id_fournisseur' => array('data_type' => 'int', 'default' => 0)
        ),
        'select_payment'              => array(
            'value_type'  => array('default' => 'id'),
            'active_only' => array('data_type' => 'bool', 'default' => 1)
        ),
        'search_ziptown'              => array(
            'field_type'    => array('default' => ''),
            'town_field'    => array('default' => ''),
            'zip_field'     => array('default' => ''),
            'state_field'   => array('default' => ''),
            'country_field' => array('default' => '')
        ),
        'select_remises'              => array(
            'id_client'     => array('data_type' => 'int', 'required' => 1),
            'extra_filters' => array('default' => '')
        )
    );

    public function __construct(BimpObject $object, $data_type, $input_name, $path, $value = null, &$field_params = array(), $option = null)
    {
        $this->data_type = $data_type;
        $this->input_name = $input_name;
        $this->value = $value;
        $this->field_params = $field_params;
        $this->option = $option;

        $this->params_def['type'] = array();
        $this->params_def['addon_right'] = array('data_type' => 'array');
        $this->params_def['addon_left'] = array('data_type' => 'array');
        $this->params_def['multiple'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['help'] = array('default' => '');

        parent::__construct($object, '', $path);

        if (is_null($this->params['type'])) {
            if (isset($this->field_params['values']) && !is_null($this->field_params['values'])) {
                $this->params['type'] = 'select';
            } else {
                switch ($this->data_type) {
                    case 'int':
                    case 'float':
                    case 'string':
                    case 'percent':
                    case 'money':
                    case 'color':
                        $this->params['type'] = 'text';
                        break;

                    case 'text':
                        $this->params['type'] = 'textarea';
                        break;

                    case 'bool':
                        $this->params['type'] = 'toggle';
                        break;

                    case 'qty':
                    case 'html':
                    case 'time':
                    case 'date':
                    case 'datetime':
                        $this->params['type'] = $this->data_type;
                        break;
                }
            }
            if (!is_null($this->params['type'])) {
                if (array_key_exists($this->params['type'], self::$type_params_def)) {
                    foreach ($this->fetchParams($this->config_path, static::$type_params_def[$this->params['type']]) as $p_name => $value) {
                        $this->params[$p_name] = $value;
                    }
                }
            }
        }

        switch ($this->params['type']) {
            case 'select':
            case 'switch_options':
                if (is_null($this->params['options']) || !count($this->params['options'])) {
                    if (isset($this->field_params['values']) && !is_null($this->field_params['values'])) {
                        $this->params['options'] = $this->field_params['values'];
                    } else {
                        $this->params['options'] = array();
                    }
                }
                break;
        }

        $this->input_id = $this->object->object_name;
        if ($this->object->isLoaded()) {
            $this->input_id .= '_' . $this->object->id;
        }
        $this->input_id .= '_' . $input_name;
    }

    public function setNamePrefix($prefix)
    {
        $this->name_prefix = $prefix;
        $this->input_id = $this->object->object_name;
        if ($this->object->isLoaded()) {
            $this->input_id .= '_' . $this->object->id;
        }
        $this->input_id .= '_' . $prefix . $this->input_name;
    }

    public function getOptions()
    {
        $options = array();

        switch ($this->params['type']) {
            case 'text':
                $options['values'] = isset($this->params['values']) ? $this->params['values'] : array();
                $options['allow_custom'] = (int) (isset($this->params['allow_custom']) ? $this->params['allow_custom'] : 1);
            case 'qty':
                $options['data'] = array();
                if ($this->params['type'] === 'qty') {
                    $options['step'] = $this->params['step'];
                }
                $min = 'none';
                $max = 'none';
                $decimals = 0;
                switch ($this->data_type) {
                    case 'percent':
                        $min = '0';
                        $max = '100';

                    case 'money':
                    case 'float':
                        $decimals = isset($this->field_params['decimals']) ? $this->field_params['decimals'] : 2;

                    case 'qty':
                    case 'int':
                        $options['data']['data_type'] = 'number';
                        $options['data']['decimals'] = $decimals;
                        $options['data']['min'] = isset($this->field_params['min']) ? $this->field_params['min'] : $min;
                        $options['data']['max'] = isset($this->field_params['max']) ? $this->field_params['max'] : $max;
                        $options['data']['unsigned'] = isset($this->field_params['unsigned']) ? $this->field_params['unsigned'] : 0;
                        break;

                    case 'string':
                        $options['data']['data_type'] = 'string';
                        $options['data']['size'] = isset($this->field_params['size']) ? $this->field_params['size'] : 128;
                        $options['data']['forbidden_chars'] = isset($this->field_params['forbidden_chars']) ? $this->field_params['forbidden_chars'] : '';
                        $options['data']['regexp'] = isset($this->field_params['regexp']) ? $this->field_params['regexp'] : '';
                        $options['data']['invalid_msg'] = isset($this->field_params['invalid_msg']) ? $this->field_params['invalid_msg'] : '';
                        $options['data']['uppercase'] = isset($this->field_params['uppercase']) ? $this->field_params['uppercase'] : 0;
                        $options['data']['lowercase'] = isset($this->field_params['lowercase']) ? $this->field_params['lowercase'] : 0;
                        break;
                }
                break;

            case 'time':
            case 'date':
            case 'datetime':
                $options['display_now'] = isset($this->params['display_now']) ? $this->params['display_now'] : 0;
                break;

            case 'textarea':
                $options['rows'] = isset($this->params['rows']) ? $this->params['rows'] : 3;
                $options['auto_expand'] = isset($this->params['auto_expand']) ? $this->params['auto_expand'] : 0;
                $options['note'] = isset($this->params['note']) ? $this->params['note'] : 0;
                $options['values'] = isset($this->params['values']) ? $this->params['values'] : array();
                break;

            case 'select':
                $options['options'] = isset($this->params['options']) ? $this->params['options'] : array();
                $options['select_first'] = isset($this->params['select_first']) ? $this->params['select_first'] : 0;
                break;

            case 'switch_options':
                $options['options'] = isset($this->params['options']) ? $this->params['options'] : array();
                $options['vertical'] = isset($this->params['vertical']) ? $this->params['vertical'] : 0;
                break;

            case 'toggle':
                $options['toggle_on'] = isset($this->params['toggle_on']) ? $this->params['toggle_on'] : 'OUI';
                $options['toggle_off'] = isset($this->params['toggle_off']) ? $this->params['toggle_off'] : 'NON';
                break;

            case 'check_list':
                $options['items'] = isset($this->params['items']) ? $this->params['items'] : array();
                break;

            case 'search_product':
                $options['filter_type'] = isset($this->params['filter_type']) ? $this->params['filter_type'] : 0;
                break;

            case 'search_societe':
                $options['type'] = isset($this->params['societe_type']) ? $this->params['societe_type'] : '';
                break;

            case 'search_state':
                $options['id_country'] = isset($this->params['id_country']) ? $this->params['id_country'] : '';
                break;

            case 'search_juridicalstatus':
                $options['country_code'] = isset($this->params['country_code']) ? $this->params['country_code'] : '';
                break;

            case 'search_commande_client':
                $options['id_client'] = isset($this->params['id_client']) ? $this->params['id_client'] : '';
                break;

            case 'search_commande_fournisseur':
                $options['id_fournisseur'] = isset($this->params['id_fournisseur']) ? $this->params['id_fournisseur'] : '';
                break;

            case 'select_payment':
                $options['value_type'] = isset($this->params['value_type']) ? $this->params['value_type'] : 'id';
                $options['active_only'] = isset($this->params['active_only']) ? $this->params['active_only'] : 1;
                break;

            case 'search_ziptown':
                $options['field_type'] = isset($this->params['field_type']) ? $this->params['field_type'] : '';
                $options['town_field'] = isset($this->params['town_field']) ? $this->params['town_field'] : '';
                $options['zip_field'] = isset($this->params['zip_field']) ? $this->params['zip_field'] : '';
                $options['state_field'] = isset($this->params['state_field']) ? $this->params['state_field'] : '';
                $options['country_field'] = isset($this->params['country_field']) ? $this->params['country_field'] : '';
                break;

            case 'select_remises':
                $options['id_client'] = isset($this->params['id_client']) ? $this->params['id_client'] : 0;
                $options['extra_filters'] = isset($this->params['extra_filters']) ? $this->params['extra_filters'] : '';
                break;
        }

        return $options;
    }

    public function renderHtml()
    {
        $options = $this->getOptions();
        $option = '';

        if (!is_null($this->params['addon_right'])) {
            if (isset($this->params['addon_right']['text'])) {
                $options['addon_right'] = $this->params['addon_right']['text'];
            } elseif (isset($this->params['addon_right']['icon'])) {
                $options['addon_right'] = '<i class="fa fa-' . $this->params['addon_right']['icon'] . '"></i>';
            }
        } else {
            switch ($this->data_type) {
                case 'money':
                    $currency = isset($this->params['currency']) ? $this->params['currency'] : 'EUR';
                    $options['addon_right'] = '<i class="fa fa-' . BimpTools::getCurrencyIcon($currency) . '"></i>';
                    break;

                case 'percent':
                    $options['addon_right'] = '<i class="fa fa-percent"></i>';
                    break;
            }
        }

        if (!is_null($this->params['addon_left'])) {
            if (isset($this->params['addon_left']['text'])) {
                $options['addon_left'] = $this->params['addon_left']['text'];
            } elseif (isset($this->params['addon_left']['icon'])) {
                $options['addon_left'] = '<i class="fa fa-' . $this->params['addon_left']['icon'] . '"></i>';
            }
        }

        $html = parent::renderHtml();

        if (count($this->errors)) {
            return $html;
        }

        if (is_null($this->value)) {
            $this->value = '';
        }

        if (is_null($this->new_value)) {
            $this->new_value = $this->value;
        }

        $html .= '<div class="inputContainer ' . $this->name_prefix . $this->input_name . '_inputContainer';
        if (count($this->extraClasses)) {
            foreach ($this->extraClasses as $extraClass) {
                $html .= ' ' . $extraClass;
            }
        }

        $required = isset($this->field_params['required']) ? (int) $this->field_params['required'] : 0;

        $input_name = $this->name_prefix . $this->input_name;
        $input_id = $this->input_id;

        if ($this->params['multiple']) {
            $this->extraData['values_field'] = $input_name;
            $input_name .= '_add_value';
            $input_id .= '_add_value';
        }

        $html .= '"';
        $html .= ' data-field_name="' . $input_name . '"';
        $html .= ' data-initial_value="' . htmlentities($this->value) . '"';
        $html .= ' data-multiple="' . ((int) $this->params['multiple'] ? 1 : 0) . '"';
        $html .= ' data-required="' . $required . '"';
        $html .= ' data-data_type="' . $this->data_type . '"';
        if (count($this->extraData)) {
            foreach ($this->extraData as $data_name => $data_value) {
                $html .= ' data-' . $data_name . '="' . $data_value . '"';
            }
        }
        $html .= ' data-field_prefix="' . $this->name_prefix . '"';
        $html .= '>';

        switch ($this->params['type']) {
            case 'search_list':
                $html .= $this->renderSearchListInput();
                break;

            case 'custom':
                $html .= isset($this->params['content']) ? $this->params['content'] : '';
                break;

            default:
                $html .= BimpInput::renderInput($this->params['type'], $input_name, $this->new_value, $options, null, $option, $input_id);
                break;
        }

        if ($this->params['help']) {
            $html .= '<p class="inputHelp">' . $this->params['help'] . '</p>';
        }

        $html .= '</div>';

        if ((int) $this->params['multiple']) {
            if ($this->params['type'] === 'search_list') {
                $label_field_name = $input_name . '_search';
            } else {
                $label_field_name = $input_name;
            }
            $html .= BimpInput::renderMultipleValuesList($this->object, $this->name_prefix . $this->input_name, $this->value, $label_field_name, false, $required);
        }

        return $html;
    }

    protected function renderSearchListInput()
    {
        if (is_null($this->value)) {
            $this->value = '';
        }

        return BimpInput::renderSearchListInputFromConfig($this->object, $this->config_path, $this->name_prefix . $this->input_name, $this->value, $this->option);
    }
}
