<?php

class BC_Field extends BimpComponent
{

    public $component_name = 'Champ';
    public static $type = 'field';
    public $edit = false;
    public $value = null;
    public $new_value = null;
    public $display_name = 'default';
    public $container_id = null;
    public $display_input_value = true;
    public $no_html = false;
    public $name_prefix = '';
    public $display_card_mode = 'none'; // hint / visible
    public $force_edit = false;
    public static $type_params_def = array(
        'id_parent'  => array(
            'object'             => array('default' => ''),
            'create_form'        => array('default' => ''),
            'create_form_values' => array('data_type' => 'array'),
            'create_form_label'  => array('default' => 'Créer')
        ),
        'id_object'  => array(
            'object'             => array('default' => ''),
            'create_form'        => array('default' => ''),
            'create_form_values' => array('data_type' => 'array'),
            'create_form_label'  => array('default' => 'Créer'),
        ),
        'items_list' => array(
            'items_data_type' => array('default' => 'string'),
            'items_sortable'  => array('data_type' => 'bool', 'default' => 0),
            'items_delimiter' => array('default' => ',')
        ),
        'number'     => array(
            'min'      => array('data_type' => 'float'),
            'max'      => array('data_type' => 'float'),
            'unsigned' => array('data_type' => 'bool', 'default' => 1),
            'decimals' => array('data_type' => 'int', 'default' => 2)
        ),
        'money'      => array(
            'currency' => array('default' => 'EUR')
        ),
        'string'     => array(
            'size'            => array('data_type' => 'int', 'default' => 128),
            'forbidden_chars' => array('default' => ''),
            'regexp'          => array('default' => ''),
            'invalid_msg'     => array('default' => ''),
            'uppercase'       => array('data_type' => 'bool', 'default' => 0),
            'lowercase'       => array('data_type' => 'bool', 'default' => 0),
        )
    );
    public static $missing_if_empty_types = array(
        'string', 'text', 'password', 'html', 'id', 'id_object', 'id_parent', 'time', 'date', 'datetime', 'color'
    );

    public function __construct(BimpObject $object, $name, $edit = false, $path = 'fields', $force_edit = false)
    {
        $this->params_def['label'] = array('required' => true);
        $this->params_def['type'] = array('default' => 'string');
        $this->params_def['required'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['required_if'] = array();
        $this->params_def['default_value'] = array('data_type' => 'any', 'default' => null);
        $this->params_def['sortable'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['searchable'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['editable'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['viewable'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['search'] = array('type' => 'definitions', 'defs_type' => 'search');
        $this->params_def['sort_options'] = array('type' => 'definitions', 'defs_type' => 'sort_option', 'multiple' => 1);
        $this->params_def['next_sort_field'] = array();
        $this->params_def['next_sort_way'] = array('default' => 'asc');
//        $this->params_def['display'] = array('type' => 'definitions', 'defs_type' => 'display', 'multiple' => 1);
        $this->params_def['depends_on'] = array('data_type' => 'array', 'compile' => true);
        $this->params_def['keep_new_value'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['values'] = array('data_type' => 'array', 'compile' => true);
        $this->params_def['display_if'] = array('data_type' => 'array', 'compile' => true);
        $this->params_def['history'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['extra'] = array('data_type' => 'bool', 'default' => 0);

        $this->edit = $edit;
        $this->force_edit = $force_edit;

        parent::__construct($object, $name, $path);

        $this->value = $this->object->getData($name);

        if (is_null($this->value) && !is_null($this->params['default_value'])) {
            $this->value = $this->params['default_value'];
        }

        // Ces paramètres ne sont plus définis ici, car dans certains cas, la variable $this->force_edit peut être ajustée après le __construc(). 
//        if ($this->isObjectValid()) {
//            if (!$this->force_edit) {
//                $this->params['editable'] = (int) ($this->object->canEditField($name) && $this->object->isFieldEditable($name, $this->force_edit));
//            }
//            $this->params['viewable'] = (int) $this->object->canViewField($name);
//        }

        if (in_array($this->params['type'], array('qty', 'int', 'float', 'money', 'percent'))) {
            $this->params = array_merge($this->params, parent::fetchParams($this->config_path, self::$type_params_def['number']));
        } elseif ($this->params['type'] === 'items_list') {
            if (isset($this->params['items_data_type']) && $this->params['items_data_type'] === 'id_object') {
                $this->params = array_merge($this->params, parent::fetchParams($this->config_path, self::$type_params_def['id_object']));
            }
        }
    }

    public function renderHtml()
    {
        if ((!$this->params['editable'] && !$this->params['viewable']) || !$this->params['show']) {
            return '';
        }

        if ($this->object->isDolObject()) {
            if (!$this->object->dol_field_exists($this->name)) {
                return '';
            }
        }

        $html = parent::renderHtml();

        if (count($this->errors)) {
            return $html;
        }

        if ($this->edit) {
            if ($this->params['editable'] && $this->object->canEditField($this->name) && $this->object->isFieldEditable($this->name, $this->force_edit)) {
                $html .= $this->renderInput();
            } else {
                $content = $this->displayValue();
                $html .= BimpInput::renderInputContainer($this->name, $this->value, $content, $this->name_prefix);
            }
        } else {
            $html .= $this->displayValue();
        }

        return $html;
    }

    public function renderInput($input_path = null)
    {
        if (!$this->params['show']) {
            return '';
        }

        if (is_null($input_path)) {
            $input_path = $this->config_path . '/input';
        }

        $input = new BC_Input($this->object, $this->params['type'], $this->name, $input_path, $this->value, $this->params);
        $input->setNamePrefix($this->name_prefix);
        $input->display_card_mode = $this->display_card_mode;

        if (!is_null($this->new_value)) {
            $input->new_value = $this->new_value;
        }

        $history_html = '';
        if ($this->params['history'] && BimpObject::objectLoaded($this->object)) {
            $history_html = BimpRender::renderObjectFieldHistoryPopoverButton($this->object, $this->name_prefix . $this->name);
        }

        if ($history_html) {
            $html .= '<div style="padding-left: 32px;">';
            $html .= '<div style="float: left; margin-left: -28px; margin-top: 4px">';
            $html .= $history_html;
            $html .= '</div>';
        }

        $html .= $input->renderHtml();

        if ($history_html) {
            $html .= '</div>';
        }

        return $html;
    }

    public function displayValue()
    {
        if (!$this->params['viewable'] || !$this->object->canViewField($this->name)) {
            return BimpRender::renderAlerts('Vous n\'avez pas la permission de voir ce champ', 'warning');
        }

        $html = '';

        if (is_null($this->value)) {
            $this->value = '';
        }

        $history_html = '';
        if ($this->params['history']) {
            $history_html = BimpRender::renderObjectFieldHistoryPopoverButton($this->object, $this->name);
        }

        if ($history_html) {
            $html .= '<div style="padding-right: 45px;">';
            $html .= '<div style="float: left; margin-left: -40px; margin-top: 4px">';
            $html .= $history_html;
            $html .= '</div>';
        }

        if ($this->display_input_value) {
            $value = $this->value;
            if (is_array($value)) {
                if ($this->params['type'] === 'json') {
                    $value = json_encode($value);
                } else {
                    $value = implode($this->params['items_delimiter'], $this->value);
                }
            }
            $html .= '<input type="hidden" name="' . $this->name_prefix . $this->name . '" value="' . htmlentities($value) . '">';
        }

        $display = new BC_Display($this->object, $this->display_name, $this->config_path . '/display', $this->name, $this->params, $this->value);
        $display->no_html = $this->no_html;

        $html .= $display->renderHtml();

        if ($history_html) {
            $html .= '</div>';
        }

        return $html;
    }

    public function renderDependsOnScript($form_identifier)
    {
        return self::renderDependsOnScriptStatic($this->object, $form_identifier, $this->name, $this->params['depends_on'], $this->name_prefix, $this->params['keep_new_value']);
    }

    public static function renderDependsOnScriptStatic(BimpObject $object, $form_identifier, $field_name, $depends_on, $name_prefix = '', $keep_new_value = 1)
    {
        $script = '';
        if (!is_null($depends_on) && $depends_on) {
            if (is_array($depends_on)) {
                $dependances = $depends_on;
            } elseif (is_string($depends_on)) {
                $dependances = explode(',', $depends_on);
            }

//            foreach ($dependances as $key => $dependance) {
//                if (!$object->config->isDefined('fields/' . $dependance) &&
//                        !$object->config->isDefined('associations/' . $dependance)) {
//                    unset($dependances[$key]);
//                }
//            }

            if (count($dependances)) {
                $script .= '<script type="text/javascript">' . "\n";
                foreach ($dependances as $dependance) {
                    $script .= 'addInputEvent(\'' . $form_identifier . '\', \'' . $name_prefix . $dependance . '\', \'change\', function() {' . "\n";
                    $script .= '  var data = {};' . "\n";
                    $script .= '  var $form = $(\'#' . $form_identifier . '\');';
//                    $script.= ' bimp_msg(\'HERE: ' . $dependance . '\');';
                    foreach ($dependances as $dep) {
                        $script .= '  if ($form.find(\'[name=' . $name_prefix . $dep . ']\').length) {' . "\n";
                        $script .= '      data[\'' . $dep . '\'] = getFieldValue($form, \'' . $name_prefix . $dep . '\');' . "\n";
                        $script .= '  }' . "\n";
                    }
                    $script .= '  reloadObjectInput(\'' . $form_identifier . '\', \'' . $name_prefix . $field_name . '\', data, ' . $keep_new_value . ');' . "\n";
                    $script .= '});' . "\n";
                }
                $script .= '</script>' . "\n";
            }
        }
        return $script;
    }

    public function renderDisplayIfData()
    {
        return self::renderDisplayifDataStatic($this->params['display_if'], $this->name_prefix);
    }

    public function checkDisplayIf()
    {
        if (isset($this->params['display_if']['field_name'])) {
            $field = $this->params['display_if']['field_name'];
            if ($field && $this->object->field_exists($field)) {
                $field_value = $this->object->getData($field);

                if (isset($this->params['display_if']['show_values'])) {
                    $show_values = $this->params['display_if']['show_values'];
                    if (!is_array($show_values)) {
                        $show_values = explode(',', $show_values);
                    }

                    if (!in_array($field_value, $show_values)) {
                        return 0;
                    }
                }

                if (isset($this->params['display_if']['hide_values'])) {
                    $hide_values = $this->params['display_if']['hide_values'];
                    if (!is_array($hide_values)) {
                        $hide_values = explode(',', $hide_values);
                    }

                    if (in_array($field_value, $hide_values)) {
                        return 0;
                    }
                }
            }
        }

        // todo : ajouter display_if/fields_names

        return 1;
    }

    public static function renderDisplayifDataStatic($params, $name_prefix = '')
    {
        $html = '';
        if (isset($params['field_name']) && $params['field_name']) {
            $html .= ' data-input_name="' . $name_prefix . $params['field_name'] . '"';

            if (isset($params['show_values']) && !is_null($params['show_values'])) {
                $show_values = $params['show_values'];
                if (is_array($show_values)) {
                    $show_values = implode(',', $show_values);
                }
                $html .= ' data-show_values="' . str_replace('"', "'", $show_values) . '"';
            }

            if (isset($params['hide_values']) && !is_null($params['hide_values'])) {
                $hide_values = $params['hide_values'];

                if (is_array($hide_values)) {
                    $hide_values = implode(',', $hide_values);
                }
                $html .= ' data-hide_values="' . str_replace('"', "'", $hide_values) . '"';
            }
        } elseif (isset($params['fields_names'])) {
            $fields_names = $params['fields_names'];
            if (!is_array($fields_names)) {
                $fields_names = explode(',', $fields_names);
            }
            $fields = array();
            foreach ($fields_names as $field) {
                if (isset($params[$field])) {
                    $fields[] = $name_prefix . $field;
                    if (isset($params[$field]['show_values']) && !is_null($params[$field]['show_values'])) {
                        $show_values = $params[$field]['show_values'];
                        if (is_array($show_values)) {
                            $show_values = implode(',', $show_values);
                        }
                        $html .= ' data-show_values_' . $name_prefix . $field . '="' . str_replace('"', "'", $show_values) . '"';
                    }

                    if (isset($params[$field]['hide_values']) && !is_null($params[$field]['hide_values'])) {
                        $hide_values = $params[$field]['hide_values'];
                        if (is_array($hide_values)) {
                            $hide_values = implode(',', $hide_values);
                        }
                        $html .= ' data-hide_values_' . $name_prefix . $field . '="' . str_replace('"', "'", $hide_values) . '"';
                    }
                }
            }

            $html .= ' data-inputs_names="' . implode(',', $fields) . '"';
        }
        return $html;
    }

    public static function getInputType(BimpObject $object, $field)
    {
        $path = 'fields/' . $field . '/';
        if ($object->config->isDefined($path . 'input/type')) {
            return $object->getConf($path . 'input/type');
        }

        if ($object->config->isDefined($path . 'values')) {
            return 'select';
        }

        $data_type = $object->getConf($path . 'type', 'string');

        switch ($data_type) {
            case 'int':
            case 'float':
            case 'string':
            case 'percent':
            case 'money':
            case 'color':
                return 'text';

            case 'text':
                return 'textarea';

            case 'bool':
                return 'toggle';

            case 'qty':
            case 'html':
            case 'time':
            case 'date':
            case 'datetime':
            case 'password':
                return $data_type;
        }

        return '';
    }

    // Recherches: 

    public function getSearchData()
    {
        $input_options = array();
        $input_type = '';
        $search_type = (isset($this->params['search']['type']) ? $this->params['search']['type'] : 'field_input');

        if ($search_type === 'field_input' && !empty($this->params['values'])) {
            $input_type = 'select';
            $input_options = array(
                'options' => $this->params['values']
            );
        } else {
            switch ($search_type) {
                case 'field_input':
                case 'value_part':
                    switch ($this->params['type']) {
//                        case 'id_object':
//                        case 'id_parent':
//                            
//                            $search_type = 'search_object';
//                            $input_type = 'text';
//                            break;

                        case 'html':
                        case 'text':
                        case 'password':
                        case 'string':
                            $search_type = 'value_part';
                            $input_type = 'text';
                            break;

                        case 'qty':
                        case 'money':
                        case 'percent':
                            $search_type = 'values_range';
                            $input_type = 'text';
                            $input = new BC_Input($this->object, $this->params['type'], $this->name_prefix . $this->name, $this->config_path . '/input', $this->value, $this->params);
                            $input_options = $input->getOptions();
                            unset($input);
                            break;

                        case 'date':
                        case 'datetime':
                            $search_type = $input_type = 'date_range';
                            break;

                        case 'time':
                            $search_type = $input_type = 'time_range';
                            break;

//                    case 'datetime':
//                        $search_type = $input_type = 'datetime_range';
//                        break;

                        case 'bool':
                            $input_type = 'select';
                            $input_options['options'] = array(
                                '' => '',
                                1  => $this->object->getConf('fields/' . $this->name . '/input/toggle_on', 'OUI'),
                                0  => $this->object->getConf('fields/' . $this->name . '/input/toggle_off', 'NON')
                            );
                            break;

                        case 'list':
                            $input_type = 'text';
                            $search_type = 'value_part';
                            break;

                        default:
                            if ($this->object->config->isDefined($this->config_path . '/search/input')) {
                                $input_path = $this->config_path . '/search/input';
                            } else {
                                $input_path = $this->config_path . '/input';
                            }
                            $input = new BC_Input($this->object, $this->params['type'], $this->name_prefix . $this->name, $input_path, $this->value, $this->params);
                            $input_type = $input->params['type'];
                            $input_options = $input->getOptions();
                            unset($input);
                            break;
                    }
                    break;

                case 'time_range':
                case 'date_range':
                case 'datetime_range':
                    $input_type = $this->params['search']['type'];
                    break;
            }
        }



        return array(
            'input_type'       => $input_type,
            'search_type'      => $search_type,
            'part_type'        => $this->params['search']['part_type'],
            'search_on_key_up' => $this->params['search']['search_on_key_up'],
            'search_option'    => $this->params['search']['option'],
            'input_options'    => $input_options
        );
    }

    public function renderSearchInput($extra_data = array())
    {
        if (!$this->params['show']) {
            return '';
        }

        if (!$this->params['searchable']) {
            return '';
        }

        $input_id = $this->object->object_name . '_search_' . $this->name;
        $input_name = 'search_' . $this->name;

        $search_data = $this->getSearchData();

        if ($search_data['input_type'] === 'search_list') {
            if ($this->object->config->isDefined($this->config_path . '/search/input')) {
                $input_path = $this->config_path . '/search/input';
            } else {
                $input_path = $this->config_path . '/input';
            }
            $content = BimpInput::renderSearchListInputFromConfig($this->object, $input_path, $input_name, $this->value, $this->params['search']['option']);
        } elseif ($search_data['search_type'] === 'values_range') {
            $content .= '<div>';
            $input_options = $search_data['input_options'];
            $input_options['addon_left'] = 'Min';
            $content .= BimpInput::renderInput($search_data['input_type'], $input_name . '_min', null, $input_options);
            $content .= '</div>';
            $content .= '<div>';
            $input_options['addon_left'] = 'Max';
            $content .= BimpInput::renderInput($search_data['input_type'], $input_name . '_max', null, $input_options);
            $content .= '</div>';
        } else {
            $content = BimpInput::renderInput($search_data['input_type'], $input_name, null, $search_data['input_options'], null, 'default', $input_id);
        }

        return BimpInput::renderSearchInputContainer($input_name, $search_data['search_type'], $search_data['search_on_key_up'], 1, $content, $extra_data);
    }

    //No-HTML: 

    public function getNoHtmlOptions(&$default_value = '')
    {
        $options = array();

        if (isset($this->params['values']) && !empty($this->params['values'])) {
            $default_value = 'label';
            $options = array(
                'key'   => 'Identifiant',
                'label' => 'Valeur affichée'
            );
        } else {
            switch ($this->params['type']) {
                case 'date':
                    $default_value = 'd / m / Y';
                    $options = array(
                        'Y-m-d'     => 'AAAA-MM-JJ',
                        'd / m / Y' => 'JJ / MM / AAAA'
                    );
                    break;

                case 'time':
                    $default_value = 'H:i';
                    $options = array(
                        'H:i:s' => 'H:min:sec',
                        'H:i'   => 'H:min'
                    );
                    break;

                case 'datetime':
                    $default_value = 'd / m / Y H:i';
                    $options = array(
                        'Y-m-d H:i:s'     => 'AAAA-MM-JJ H:min:sec',
                        'Y-m-d H:i'       => 'AAAA-MM-JJ H:min',
                        'd / m / Y H:i:s' => 'JJ / MM / AAAA H:min:sec',
                        'd / m / Y H:i'   => 'JJ / MM / AAAA H:min'
                    );
                    break;

                case 'bool':
                    $default_value = 'string';
                    $options = array(
                        'number' => '1/0',
                        'string' => 'OUI/NON'
                    );
                    break;

                case 'id_object':
                case 'id_parent':
                    $default_value = '';
                    $options = array(
                        'id'       => 'ID',
                        'fullname' => 'Nom complet'
                    );
                    $instance = null;
                    if ($this->params['type'] === 'id_parent') {
                        $instance = $this->object->getParentInstance();
                    } elseif (isset($this->params['object']) && (string) $this->params['object']) {
                        $instance = $this->object->config->getObject('', $this->params['object']);
                    }
                    if (is_a($instance, 'BimpObject')) {
                        foreach ($instance->params['fields'] as $field_name) {
                            $options[$field_name] = $instance->getConf('fields/' . $field_name . '/label', $field_name);
                        }
                    }
                    foreach (BimpObject::$ref_properties as $ref_prop) {
                        if (isset($options[$ref_prop])) {
                            $default_value = $ref_prop;
                            break;
                        }
                    }
                    if (!$default_value) {
                        foreach (BimpObject::$name_properties as $name_prop) {
                            if (isset($options[$name_prop])) {
                                $default_value = $name_prop;
                            }
                        }
                    }
                    if (!$default_value) {
                        $default_value = 'fullname';
                    }
                    break;

                case 'money':
                case 'percent':
                case 'float':
                case 'qty':
                    $default_value = 'number';
                    $options = array(
                        'number' => 'Valeur numérique',
                        'string' => 'Valeur affichée'
                    );
                    break;
            }
        }

        return $options;
    }

    public function getNoHtmlValue($option)
    {
        $options = array();

        if (isset($this->params['values']) && !empty($this->params['values'])) {
            if ($option === 'label') {
                if (isset($this->params['values'][$this->value])) {
                    $value = $this->params['values'][$this->value];
                    if(is_array($value)){
                        if(isset($value['label']))
                            return $value['label'];
                        else
                            return 'Pas de label (' . $this->value . ')';
                    }
                    return $this->params['values'][$this->value];
                } else {
                    return 'Non défini (' . $this->value . ')';
                }
            }
            return $this->value;
        } else {
            switch ($this->params['type']) {
                case 'date':
                case 'time':
                case 'datetime':
                    if (!(string) $this->value) {
                        return '';
                    }
                    $dt = new DateTime($this->value);
                    if (!(string) $option) {
                        switch ($this->params['type']) {
                            case 'date':
                                $option = 'd / m / Y';
                                break;
                            case 'time':
                                $option = 'H:i';
                                break;
                            case 'datetime':
                                $option = 'd / m / Y H:i';
                                break;
                        }
                    }
                    return $dt->format($option);

                case 'bool':
                    if ($option === 'number') {
                        return (int) $this->value;
                    }
                    return ((int) $this->value ? 'OUI' : 'NON');

                case 'id_object':
                case 'id_parent':
                    if (!$option) {
                        $option = 'id';
                    }
                    switch ($option) {
                        case 'id':
                            return $this->value;

                        case 'fullname';
                        default:
                            switch ($this->params['type']) {
                                case 'id_parent':
                                    $obj = $this->object->getParentInstance();
                                    break;

                                case 'id_object':
                                    $obj = $this->object->getChildObject($this->params['object']);
                                    break;
                            }

                            if (!BimpObject::objectLoaded($obj)) {
                                return $this->value;
                            }

                            if ($option === 'fullname') {
                                if(method_exists($obj, 'getName'))
                                    return $obj->getName();
                                elseif(isset($obj->ref))
                                    return $obj->ref;
                                else
                                    return "N/C";
                            }

                            if ($obj->field_exists($option)) {
                                return $obj->getData($option);
                            }

                            return $this->value;
                    }

                case 'money':
                case 'percent':
                case 'float':
                case 'qty':
                    if ($option === 'string') {
                        switch ($this->params['type']) {
                            case 'money':
                                return BimpTools::displayMoneyValue($this->value);

                            case 'percent':
                                return BimpTools::displayFloatValue($this->value) . ' %';

                            case 'float':
                            case 'qty':
                                return BimpTools::displayFloatValue($this->value);
                        }
                    }
                    return $this->value;

                default:
                    return $this->Value;
            }
        }
    }
}
