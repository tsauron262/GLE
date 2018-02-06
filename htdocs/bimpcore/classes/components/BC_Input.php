<?php

class BC_Input extends BimpComponent
{

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
    public static $type_params_def = array(
        'time'           => array(
            'display_now' => array('data_type' => 'bool', 'default' => 0)
        ),
        'date'           => array(
            'display_now' => array('data_type' => 'bool', 'default' => 0)
        ),
        'datetime'       => array(
            'display_now' => array('data_type' => 'bool', 'default' => 0)
        ),
        'textarea'       => array(
            'rows'        => array('data_type' => 'int', 'default' => 3),
            'auto_expand' => array('data_type' => 'bool', 'default' => 0),
            'note'        => array('data_type' => 'bool', 'default' => 0),
        ),
        'select'         => array(
            'options' => array('data_type' => 'array', 'compile' => true)
        ),
        'toggle'         => array(
            'toggle_on'  => array('default' => 'OUI'),
            'toggle_off' => array('default' => 'NON')
        ),
        'search_societe' => array(
            'societe_type' => array('default' => '')
        ),
        'check_list'     => array(
            'items' => array('data_type' => 'array', 'default' => array(), 'compile' => 0)
        ),
        'custom'         => array(
            'content' => array('default' => '')
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
            $this->input_id .= '_' . $this->object->id . '_';
        }
        $this->input_id .= $input_name;
    }

    public function getOptions()
    {
        $options = array();

        switch ($this->params['type']) {
            case 'text':
                $options['data'] = array();
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
                break;

            case 'select':
                $options['options'] = isset($this->params['options']) ? $this->params['options'] : array();
                break;

            case 'toggle':
                $options['toggle_on'] = isset($this->params['toggle_on']) ? $this->params['toggle_on'] : 'OUI';
                $options['toggle_off'] = isset($this->params['toggle_off']) ? $this->params['toggle_off'] : 'NON';
                break;

            case 'search_societe':
                $options['type'] = isset($this->params['societe_type']) ? $this->params['societe_type'] : '';
                break;

            case 'check_list':
                $options['items'] = isset($this->params['items']) ? $this->params['items'] : array();
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

        $html .= '<div class="inputContainer ' . $this->input_name . '_inputContainer';
        if (count($this->extraClasses)) {
            foreach ($this->extraClasses as $extraClass) {
                $html .= ' ' . $extraClass;
            }
        }
        $html .= '"';
        $html .= ' data-field_name="' . $this->input_name . '"';
        $html .= ' data-initial_value="' . $this->value . '"';
        $html .= ' data-multiple="' . ((int) $this->params['multiple'] ? 1 : 0) . '"';
        if (count($this->extraData)) {
            foreach ($this->extraData as $data_name => $data_value) {
                $html .= ' data-' . $data_name . '="' . $data_value . '"';
            }
        }
        $html .= '>';

        switch ($this->params['type']) {
            case 'search_list':
                $html .= $this->renderSearchListInput();
                break;

            case 'custom':
                $html .= isset($this->params['content']) ? $this->params['content'] : '';
                break;

            default:
                $html .= BimpInput::renderInput($this->params['type'], $this->input_name, $this->new_value, $options, null, $option, $this->input_id);
                break;
        }

        if ($this->params['help']) {
            $html .= '<p class="inputHelp">' . $this->params['help'] . '</p>';
        }

        if ((int) $this->params['multiple']) {
            if ($this->params['type'] === 'search_list') {
                $label_field_name = $this->input_name . '_add_value_search';
            } else {
                $label_field_name = $this->input_name;
            }
            $html .= BimpInput::renderMultipleValuesList($this->object, $this->input_name, $this->value, $label_field_name);
        }

        $html .= '</div>';

        return $html;
    }

    protected function renderSearchListInput()
    {
        // todo: implémenter la déf des params search_list

        if (is_null($this->value)) {
            $this->value = '';
        }

        if (!$this->object->config->isDefined($this->config_path . '/search_list')) {
            $html .= BimpRender::renderAlerts('Paramètres de recherche non définis pour le champ "' . $this->input_name . '"');
        } else {
            $path = $this->config_path . '/search_list/';
            $table = $this->object->getConf($path . 'table', null, true);
            $join = $this->object->getConf($path . 'join', '');
            $join_on = $this->object->getConf($path . 'join_on', '');
            $field_return_label = $this->object->getConf($path . 'field_return_label', null, true);
            $field_return_value = $this->object->getConf($path . 'field_return_value', null, true);
            $join_return_label = $this->object->getConf($path . 'join_return_label', '');
            $label_syntaxe = $this->object->getConf($path . 'label_syntaxe', '<label_1>');

            if (is_null($table) || is_null($field_return_label) || is_null($field_return_value)) {
                $html .= BimpRender::renderAlerts('Configuration invalide pour le champ  "' . $this->input_name . '"');
            } else {
                $fields_search = array();
                if ($this->object->config->isDefined($path . 'options')) {
                    if (!is_null($this->option) && $this->object->config->isDefined($path . '/options/' . $this->option)) {
                        $fields_search = $this->object->getConf($path . 'options/' . $this->option . '/fields_search', null, true);
                        $join = $this->object->getConf($path . 'options/' . $this->option . '/join', '');
                        $join_on = $this->object->getConf($path . 'options/' . $this->option . '/join_on', '');
                        $join_return_label = $this->object->getConf($path . 'options/' . $this->option . '/join_return_label', '');
                    } else {
                        $options = $this->object->getConf($path . 'options', array(), true, 'array');
                        $html .= '<div class="searchListOptions optionsContainer">';
                        $html .= '<span class="displayPopupButton optionsButton" data-popup_id="' . $this->input_name . '_searchListOptionsPopup">Options de recherche</span>';
                        $html .= '<div id="' . $this->input_name . '_searchListOptionsPopup" class="tinyPopup searchListOptionsPopup">';
                        $switchOptions = array();
                        foreach ($options as $opt_name => $opt_params) {
                            $opt_label = $this->object->getConf($path . 'options/' . $opt_name . '/label', '', true);
                            $opt_fields_search = $this->object->getConf($path . 'options/' . $opt_name . '/fields_search', null, true);
                            $opt_join = $this->object->getConf($path . 'options/' . $opt_name . '/join', '');
                            $opt_join_on = $this->object->getConf($path . 'options/' . $opt_name . '/join_on', '');
                            $opt_join_return_label = $this->object->getConf($path . 'options/' . $opt_name . '/join_return_label', '');
                            $opt_help = $this->object->getConf($path . 'options/' . $opt_name . '/help', '');
                            if (!is_null($opt_fields_search) && !is_null($opt_label)) {
                                $html .= '<input type="hidden" id="searchList_' . $opt_name . '_fields_search" value="' . $opt_fields_search . '"/>';
                                if ($opt_join) {
                                    $html .= '<input type="hidden" id="searchList_' . $opt_name . '_join" value="' . $opt_join . '"/>';
                                }
                                if ($opt_join_on) {
                                    $html .= '<input type="hidden" id="searchList_' . $opt_name . '_join_on" value="' . $opt_join_on . '"/>';
                                }
                                if ($opt_join_on) {
                                    $html .= '<input type="hidden" id="searchList_' . $opt_name . '_join_return_label" value="' . $opt_join_return_label . '"/>';
                                }
                                if ($opt_help) {
                                    $html .= '<input type="hidden" id="searchList_' . $opt_name . '_help" value="' . htmlentities($opt_help) . '"/>';
                                }
                                $switchOptions[$opt_name] = $opt_label;
                            }
                            if ($opt_name === 'default') {
                                $fields_search = $opt_fields_search;
                                $join = $opt_join;
                                $join_on = $opt_join_on;
                                $join_return_label = $opt_join_return_label;
                            }
                        }
                        $html .= '<div class="title">Options de recherche</div>';
                        $html .= BimpInput::renderSwitchOptionsInput($this->input_name . '_search_list_option', $switchOptions, 'default', null, true);
                        $html .= '</div>';
                        $html .= '</div>';
                    }
                } else {
                    $fields_search = $this->object->getConf($path . 'fields_search', array(), true);
                }

                $search = '';
                if ($this->value) {
                    global $db;
                    $bdb = new BimpDb($db);
                    if (!is_array($fields_search)) {
                        $fields_search = explode(',', $fields_search);
                    }
                    $where = '`' . preg_replace('/^.*\.(.*)$/', '$1', $field_return_value) . '` = ' . (is_string($this->value) ? '\'' . $this->value . '\'' : $this->value);
                    $search = $bdb->getValue(preg_replace('/^.*\.(.*)$/', '$1', $table), preg_replace('/^.*\.(.*)$/', '$1', $fields_search[0]), $where);
                    if (is_null($search)) {
                        $search = '';
                    }
                    unset($bdb);
                }

                if (is_array($fields_search)) {
                    $fields_search = implode(',', $fields_search);
                }

                $html .= '<input type="hidden" name="' . $this->input_name . '" value="' . $this->value . '"/>';
                $html .= '<input type="text" name="' . $this->input_name . '_search" class="search_list_input" value="' . $search . '" onkeyup="searchObjectList($(this));"';
                $html .= ' data-table="' . $table . '"';
                $html .= ' data-join="' . $join . '"';
                $html .= ' data-join_on="' . $join_on . '"';
                $html .= ' data-fields_search="' . $fields_search . '"';
                $html .= ' data-field_return_label="' . $field_return_label . '"';
                $html .= ' data-field_return_value="' . $field_return_value . '"';
                $html .= ' data-join_return_label="' . $join_return_label . '"';
                $html .= ' data-label_syntaxe="' . htmlentities($label_syntaxe) . '"';
                $html .= '/>';
                $html .= '<i class="loading fa fa-spinner fa-spin"></i>';
                $html .= '<div class="search_input_results"></div>';
            }
        }
        return $html;
    }
}
