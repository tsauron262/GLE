<?php

class BC_Field extends BimpComponent
{

    public static $type = 'field';
    public $edit = false;
    public $value = null;
    public $new_value = null;
    public $display_name = 'default';
    public $container_id = null;
    public static $type_params_def = array(
        'id_object' => array(
            'object'      => array('required' => true),
            'create_form' => array('default' => '')
        ),
        'number'    => array(
            'min'      => array('data_type' => 'float'),
            'max'      => array('data_type' => 'float'),
            'unsigned' => array('data_type' => 'bool', 'default' => 1),
            'decimals' => array('data_type' => 'int', 'default' => 2)
        ),
        'money'     => array(
            'currency' => array('default' => 'EUR')
        ),
        'string'    => array(
            'size'            => array('data_type' => 'int', 'default' => 128),
            'forbidden_chars' => array('default' => ''),
            'regexp'          => array('default' => ''),
            'invalid_msg'     => array('default' => ''),
            'uppercase'       => array('data_type' => 'bool', 'default' => 0),
            'lowercase'       => array('data_type' => 'bool', 'default' => 0),
        )
    );

    public function __construct(BimpObject $object, $name, $edit = false, $path = 'fields')
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
        $this->params_def['values'] = array('data_type' => 'array', 'compile' => true);
        $this->params_def['display_if'] = array('data_type' => 'array', 'compile' => true);
        $this->params_def['history'] = array('data_type' => 'bool', 'default' => 0);

        $this->edit = $edit;

        parent::__construct($object, $name, $path);

        $this->value = $this->object->getData($name);

        if (is_null($this->value) && !is_null($this->params['default_value'])) {
            $this->value = $this->params['default_value'];
        }

        $this->params['editable'] = 1; // Pout plus tard : prise en compte des droits user
        $this->params['viewable'] = 1;

        if (in_array($this->params['type'], array('int', 'float', 'money', 'percent'))) {
            $this->params = array_merge($this->params, parent::fetchParams($this->config_path, self::$type_params_def['number']));
        }
    }

    public function renderHtml()
    {
        if (!$this->params['editable'] && !$this->params['viewable'] || !$this->params['show']) {
            return '';
        }

        $html = parent::renderHtml();

        if (count($this->errors)) {
            return $html;
        }

        if ($this->edit && $this->params['editable']) {
            $html .= $this->renderInput();
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

        if (!$this->params['editable']) {
            return BimpRender::renderAlerts('Vous n\'avez pas la permission d\'éditer ce champ', 'warning');
        }

        if (is_null($input_path)) {
            $input_path = $this->config_path . '/input';
        }

        $input = new BC_Input($this->object, $this->params['type'], $this->name, $input_path, $this->value, $this->params);

        if (!is_null($this->new_value)) {
            $input->new_value = $this->new_value;
        }

        $history_html = '';
        if ($this->params['history']) {
            $history_html = BimpRender::renderObjectFieldHistoryPopoverButton($this->object, $this->name);
        }

        if ($history_html) {
            $html .= '<div style="padding-right: 32px;">';
        }

        $html .= $input->renderHtml();

        if ($history_html) {
            $html .= '<div style="float: right; margin-right: -28px; margin-top: 4px">';
            $html .= $history_html;
            $html .= '</div></div>';
        }

        return $html;
    }

    public function renderSearchInput()
    {
        if (!$this->params['show']) {
            return '';
        }

        if (!$this->params['searchable']) {
            return '';
        }

        $html = '';

        $input_id = $this->object->object_name . '_search_' . $this->name;
        $input_name = 'search_' . $this->name;

        $input_type = '';
        $options = array();

        $html .= '<div class="searchInputContainer"';
        $html .= ' data-field_name="' . $input_name . '"';
        $html .= ' data-search_type="' . $this->params['search']['type'] . '"';
        $html .= ' data-search_on_key_up="' . $this->params['search']['search_on_key_up'] . '"';
        $html .= ' data-min_chars="1"';
        $html .= '>';

        $search_type = (isset($this->params['search']['type']) ? $this->params['search']['type'] : 'field_input');

        switch ($search_type) {
            case 'field_input':
            case 'value_part':
                switch ($this->params['type']) {
                    case 'bool':
                        $input_type = 'select';
                        $options['options'] = array(
                            '' => '',
                            1  => $this->object->getConf('fields/' . $this->name . '/input/toggle_on', 'OUI'),
                            0  => $this->object->getConf('fields/' . $this->name . '/input/toggle_off', 'NON')
                        );
                        break;

                    default:
                        if ($this->object->config->isDefined($this->config_path . '/search/input')) {
                            $input_path = $this->config_path . '/search/input';
                        } else {
                            $input_path = $this->config_path . '/input';
                        }
                        $input = new BC_Input($this->object, $this->params['type'], $this->name, $input_path, $this->value, $this->params);
                        $input_type = $input->params['type'];
                        $options = $input->getOptions();
                        unset($input);
                        break;
                }
                break;

            case 'time_range':
            case 'date_range':
            case 'datetime_range':
                $input_type = $this->params['search']['type'];
                $options['display_now'] = 1;
                break;
        }

        if ($input_type === 'search_list') {
            $html .= BimpInput::renderSearchListInput($this->object, $input_path, $input_name, $this->value, $this->params['search']['option']);
        } else {
            $html .= BimpInput::renderInput($input_type, $input_name, null, $options, null, 'default', $input_id);
        }

        $html .= '</div>';

        return $html;
    }

    public function displayValue()
    {
        if (!$this - params['viewable']) {
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
            $html .= '<div style="padding-right: 32px;">';
        }

        $html .= '<input type="hidden" name="' . $this->name . '" value="' . $this->value . '">';

        $display = new BC_Display($this->object, $this->display_name, $this->config_path . '/display', $this->name, $this->params, $this->value);

        $html .= $display->renderHtml();

        if ($history_html) {
            $html .= '<div style="float: right; margin-right: -28px; margin-top: 4px">';
            $html .= $history_html;
            $html .= '</div></div>';
        }

        return $html;
    }

    public function renderDependsOnScript($form_identifier)
    {
        return self::renderDependsOnScriptStatic($this->object, $form_identifier, $this->name, $this->params['depends_on']);
    }

    public static function renderDependsOnScriptStatic(BimpObject $object, $form_identifier, $field_name, $depends_on)
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
                    $script .= 'addInputEvent(\'' . $form_identifier . '\', \'' . $dependance . '\', \'change\', function() {' . "\n";
                    $script .= '  var data = {};' . "\n";
                    $script .= '  var $form = $(\'#' . $form_identifier . '\');';
//                    $script.= ' bimp_msg(\'HERE: ' . $dependance . '\');';
                    foreach ($dependances as $dep) {
                        $script .= '  if ($form.find(\'[name=' . $dep . ']\').length) {' . "\n";
                        $script .= '      data[\'' . $dep . '\'] = getFieldValue($form, \'' . $dep . '\');' . "\n";
                        $script .= '  }' . "\n";
                    }
                    $script .= '  reloadObjectInput(\'' . $form_identifier . '\', \'' . $field_name . '\', data);' . "\n";
                    $script .= '});' . "\n";
                }
                $script .= '</script>' . "\n";
            }
        }
        return $script;
    }

    public function renderDisplayIfData()
    {
        return self::renderDisplayifDataStatic($this->params['display_if']);
    }

    public static function renderDisplayifDataStatic($params)
    {
        $html = '';
        if (isset($params['field_name']) && $params['field_name']) {
            $html .= ' data-input_name="' . $params['field_name'] . '"';

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
        }
        return $html;
    }
}
