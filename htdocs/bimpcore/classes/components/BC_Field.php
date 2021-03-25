<?php

class BC_Field extends BimpComponent
{

    public $component_name = 'Champ';
    public static $type = 'field';
    public $edit = false;
    public $value = null;
    public $new_value = null;
    public $display_name = 'default';
    public $display_options = array();
    public $container_id = null;
    public $display_input_value = true;
    public $no_html = false;
    public $name_prefix = '';
    public $display_card_mode = 'none'; // hint / visible
    public $force_edit = false;
    public static $types = array(
        'string'     => 'Chaîne de caractères',
        'text'       => 'Text long',
        'html'       => 'HTML',
        'password'   => 'Mot de passe',
        'int'        => 'Nombre entier',
        'float'      => 'Nombre décimal',
        'bool'       => 'Booléen (valeur OUI/NON)',
        'qty'        => 'Quantité',
        'money'      => 'Valeur monétaire',
        'percent'    => 'Pourcentage',
        'id'         => 'Identifiant numérique',
        'id_parent'  => 'Objet parent',
        'id_object'  => 'Objet lié',
        'items_list' => 'Liste',
        'color'      => 'Couleur',
        'json'       => 'Ensemble de données au format JSON',
        'date'       => 'Date',
        'time'       => 'Heure',
        'datetime'   => 'Date et heure'
    );
    public static $type_params_def = array(
        'id_parent'  => array(
            'object'             => array('default' => ''),
            'create_form'        => array('default' => ''),
            'create_form_values' => array('data_type' => 'array'),
            'create_form_label'  => array('default' => 'Créer'),
            'edit_form'          => array('default' => ''),
            'edit_form_values'   => array('data_type' => 'array'),
            'edit_form_label'    => array('default' => 'Editer')
        ),
        'id_object'  => array(
            'object'             => array('default' => ''),
            'create_form'        => array('default' => ''),
            'create_form_values' => array('data_type' => 'array'),
            'create_form_label'  => array('default' => 'Créer'),
            'edit_form'          => array('default' => ''),
            'edit_form_values'   => array('data_type' => 'array'),
            'edit_form_label'    => array('default' => 'Editer')
        ),
        'items_list' => array(
            'items_data_type' => array('default' => 'string'),
            'items_sortable'  => array('data_type' => 'bool', 'default' => 0),
            'items_delimiter' => array('default' => ','),
            'items_braces'    => array('data_type' => 'bool', 'default' => 0)
        ),
        'number'     => array(
            'min'      => array('data_type' => 'string'),
            'max'      => array('data_type' => 'string'),
            'unsigned' => array('data_type' => 'bool', 'default' => 0),
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
    public static $has_total_types = array('qty', 'money', 'timer');

    public function __construct(BimpObject $object, $name, $edit = false, $path = 'fields', $force_edit = false)
    {
        $this->params_def['label'] = array('required' => true);
        $this->params_def['type'] = array('default' => 'string');
        $this->params_def['required'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['unused'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['required_if'] = array();
        $this->params_def['default_value'] = array('data_type' => 'any', 'default' => null);
        $this->params_def['sortable'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['searchable'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['editable'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['viewable'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['filterable'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['user_edit'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['search'] = array('type' => 'definitions', 'defs_type' => 'search');
        $this->params_def['sort_options'] = array('type' => 'definitions', 'defs_type' => 'sort_option', 'multiple' => 1);
        $this->params_def['next_sort_field'] = array();
        $this->params_def['next_sort_way'] = array('default' => 'asc');
        $this->params_def['depends_on'] = array('data_type' => 'array', 'compile' => true);
        $this->params_def['keep_new_value'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['values'] = array('data_type' => 'array', 'compile' => true);
        $this->params_def['display_if'] = array('data_type' => 'array', 'compile' => true);
        $this->params_def['history'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['extra'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['has_total'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['no_dol_prop'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['nl2br'] = array('data_type' => 'bool', 'default' => 0);

        $this->edit = $edit;
        $this->force_edit = $force_edit;

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        parent::__construct($object, $name, $path);

        $this->value = $this->object->getData($name);

        if (is_null($this->value) && !is_null($this->params['default_value'])) {
            $this->value = $this->params['default_value'];
        }

        if (in_array($this->params['type'], array('qty', 'int', 'float', 'money', 'percent'))) {
            $this->params = BimpTools::merge_array($this->params, parent::fetchParams($this->config_path, self::$type_params_def['number']));
        } elseif ($this->params['type'] === 'items_list') {
            if (isset($this->params['items_data_type']) && $this->params['items_data_type'] === 'id_object') {
                $this->params = BimpTools::merge_array($this->params, parent::fetchParams($this->config_path, self::$type_params_def['id_object']));
            }
        }

        // Le paramètre "has_total" est défini à 1 par défaut pour les types présents dans self::$has_total_types
        if (!(int) $this->params['has_total'] && in_array($this->params['type'], self::$has_total_types) &&
                !$this->object->config->isDefined($this->config_path . '/has_total')) {
            $this->params['has_total'] = 1;
        }

        $current_bc = $prev_bc;
    }

    public function isEditable()
    {
        if (!$this->isObjectValid()) {
            return 0;
        }

        return (int) ((int) $this->params['editable'] && $this->object->canEditField($this->name) && $this->object->isFieldEditable($this->name, $this->force_edit));
    }

    public function isUsed()
    {
        return (!(int) $this->params['unused']);
    }

    // Rendus HTML principaux: 

    public function renderHtml()
    {
        if (!$this->params['show'] || !$this->isUsed()) {
            return '';
        }

        if ($this->object->isDolObject()) {
            if (!$this->object->dol_field_exists($this->name)) {
                return '';
            }
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = parent::renderHtml();

        if (count($this->errors)) {
            $current_bc = $prev_bc;
            return $html;
        }

        if ($this->edit) {
            if ($this->isEditable()) {
                $html .= $this->renderInput();
            } else {
                $content = $this->displayValue();

                $help = $this->object->getConf('fields/' . $this->name . '/input/help', '');

                if ($help) {
                    $content .= '<p class="inputHelp">' . $help . '</p>';
                }

                $html .= BimpInput::renderInputContainer($this->name, $this->value, $content, $this->name_prefix);
            }
        } else {
            $html .= $this->displayValue();

            if ($this->params['nl2br']) {
                $html = nl2br($html);
            }
        }

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderInput($input_path = null)
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        if (is_null($input_path)) {
            $input_path = $this->config_path . '/input';
        }

        if ($this->params['type'] === 'items_list' && is_array($this->value)) {
            if (!isset($this->params['values']) || empty($this->params['values'])) {
                $field_params = $this->params;
                $field_params['type'] = $this->getParam('items_data_type', 'string');
                $bc_display = new BC_Display($this->object, $this->display_name, $this->config_path . '/display', $this->name, $field_params);
                $bc_display->no_html = $this->no_html;
                $bc_display->setDisplayOptions($this->display_options);

                foreach ($this->value as $value) {
                    $bc_display->value = $value;
                    $this->params['values'][$value] = $bc_display->renderHtml();
                }
            }
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

        $current_bc = $prev_bc;
        return $html;
    }

    // Affichages: 

    public function displayValue()
    {
        if (!$this->params['viewable'] || !$this->object->canViewField($this->name)) {
            return BimpRender::renderAlerts('Vous n\'avez pas la permission de voir ce champ', 'warning');
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

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
        $display = new BC_Display($this->object, $this->display_name, $this->config_path . '/displays/' . $this->display_name, $this->name, $this->params, $this->value);
        $display->no_html = $this->no_html;
        $display->setDisplayOptions($this->display_options);
        $html .= $display->renderHtml();

        if ($history_html) {
            $html .= '</div>';
        }

        $current_bc = $prev_bc;
        return $html;
    }

    public function displayType()
    {
        if (isset($this->params['values']) && is_array($this->params['values']) && !empty($this->params['values'])) {
            return 'Identifiant d\'une liste de valeurs prédéfinies';
        }

        $type = $this->params['type'];

        if (!$type) {
            return 'Non défini';
        }

        if (isset(self::$types[$type])) {
            $label = self::$types[$type];

            if (($type === 'id_objet') || ($type === 'items_list' && $this->params['item_data_type'] === 'id_object')) {
                if ($this->isObjectValid()) {
                    $object = $this->object->getChildObject($this->params['object']);

                    if (is_a($object, 'BimpObject')) {
                        $label .= ' (' . BimpTools::ucfirst($object->getLabel()) . ')';
                    }
                }
            } elseif ($type === 'id_parent') {
                if ($this->isObjectValid()) {
                    $object = $this->object->getParentInstance();

                    if (is_a($object, 'BimpObject')) {
                        $label .= ' (' . BimpTools::ucfirst($object->getLabel()) . ')';
                    }
                }
            }

            return $label;
        }

        return 'Inconnu';
    }

    // Getters: 

    public function getLinkedObject()
    {
        if ($this->isObjectValid()) {
            if ($this->params['object']) {
                return $this->object->getChildObject($this->params['object']);
            }
        }

        return null;
    }

    public function hasValuesArray()
    {
        return (isset($this->params['values']) && is_array($this->params['values']) && !empty($this->params['values']));
    }

    public function getValuesArrayData()
    {
        $data = array(
            'has_values'  => 0,
            'has_icon'    => 0,
            'has_classes' => 0
        );

        if (isset($this->params['values']) && is_array($this->params['values']) && !empty($this->params['values'])) {
            $data['has_values'] = 1;
            foreach ($this->params['values'] as $key => $value) {
                if (is_array($value)) {
                    if (isset($value['icon'])) {
                        $data['has_icon'] = 1;
                    }
                    if (isset($value['classes'])) {
                        $data['has_classes'] = 1;
                    }

                    if ($data['has_icon'] && $data['has_classes']) {
                        break;
                    }
                }
            }
        }

        return $data;
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

    public function getDefaultDisplayWidth()
    {
        return self::getDefaultDisplayWidthFromType($this->params['type']);
    }

    public static function getDefaultDisplayWidthFromType($type)
    {
        switch ($type) {
            case 'string':
            case 'datetime':
                return 120;

            case 'text':
            case 'html':
            case 'json':
            case 'items_list':
                return 300;

            case 'id':
            case 'int':
            case 'float':
            case 'qty':
            case 'money':
            case 'percent':
            case 'color':
                return 60;

            case 'date':
            case 'time':
                return 100;

            case 'id_object':
            case 'id_parent':
                return 120;
        }
    }

    // Display_if / Depends_on: 

    public function renderDependsOnScript($form_identifier, $force_keep_new_value = false)
    {
        return self::renderDependsOnScriptStatic($this->object, $form_identifier, $this->name, $this->params['depends_on'], $this->name_prefix, ($force_keep_new_value ? 1 : (int) $this->params['keep_new_value']));
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

            if (count($dependances)) {
                $script .= '<script type="text/javascript">' . "\n";
                foreach ($dependances as $dependance) {
                    $script .= 'addInputEvent(\'' . $form_identifier . '\', \'' . $name_prefix . $dependance . '\', \'change\', function() {' . "\n";
                    $script .= '  var data = {};' . "\n";
                    $script .= '  var $form = $(\'#' . $form_identifier . '\');';
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

    public function renderSearchInput($extra_data = array(), $input_name = null)
    {
        if (!$this->params['show']) {
            return '';
        }

        if (!$this->params['searchable']) {
            return '';
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $input_id = $this->object->object_name . '_search_' . $this->name;

        if (is_null($input_name)) {
            $input_name = 'search_' . $this->name;
        }

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

        $current_bc = $prev_bc;
        return BimpInput::renderSearchInputContainer($input_name, $search_data['search_type'], $search_data['search_on_key_up'], 1, $content, $extra_data);
    }

    // Options d'affichage: 

    public function getDisplayTypesArray()
    {
        if (!$this->isOk()) {
            return array();
        }

        return BC_Display::getObjectFieldDisplayTypesArray($this->object, $this->name, $this);
    }

    public function getDisplayOptionsInputs($display_name = '', $values = array())
    {
        if (!$this->isOk()) {
            return array();
        }

        return BC_Display::getObjectFieldDisplayOptionsInputs($this->object, $this->name, $display_name, $values, $this);
    }

    public function renderCsvOptionsInput($input_name, $value = '')
    {
        if (count($this->errors)) {
            return BimpRender::renderAlerts($this->errors);
        }

        $html = '';

        $def_val = '';

        $options = $this->getNoHtmlOptions($def_val);

        if (!$value) {
            $value = $def_val;
        }

        if (!empty($options)) {
            $html .= BimpInput::renderInput('select', $input_name, $value, array(
                        'options'     => $options,
                        'extra_class' => 'col_option'
            ));
        } else {
            $html .= 'Valeur';
        }

        return $html;
    }

    // No-HTML: 

    public function getNoHtmlOptions(&$default_value = '')
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $options = array();

        if (isset($this->params['values']) && !empty($this->params['values'])) {
            $default_value = 'label';
            $options = array(
                'key'       => 'Identifiant',
                'label'     => 'Valeur affichée',
                'key_label' => 'Identifiant et valeur affichée'
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
                        'd / m / Y H:i'   => 'JJ / MM / AAAA H:min',
                        'Y-m-d'           => 'AAAA-MM-JJ',
                        'd / m / Y'       => 'JJ / MM / AAAA'
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
                    } elseif (isset($this->params['object'])) {
                        if (is_string($this->params['object']) && $this->params['object']) {
                            $instance = $this->object->config->getObject('', $this->params['object']);
                        } elseif (is_object($this->params['object']) && is_a($this->params['object'], 'BimpObject')) {
                            $instance = $this->params['object'];
                        }
                    }

                    if (is_a($instance, 'BimpObject')) {
                        $ref_prop = $instance->getRefProperty();
                        $name_props = $instance->getNameProperties();

                        if ($ref_prop && count($name_props)) {
                            $options['ref_nom'] = $instance->getConf('fields/' . $ref_prop . '/label', $ref_prop) . ' - Nom complet';
                        }

                        foreach ($instance->params['fields'] as $field_name) {
                            if ($instance->field_exists($field_name)) {
                                $options[$field_name] = $instance->getConf('fields/' . $field_name . '/label', $field_name);
                            }
                        }
                    }

                    if (!$default_value) {
                        if (isset($options['ref_nom'])) {
                            $default_value = 'ref_nom';
                        } else {
                            foreach (BimpObject::$name_properties as $name_prop) {
                                if (isset($options[$name_prop])) {
                                    $default_value = $name_prop;
                                    break;
                                }
                            }

                            if (!$default_value) {
                                $default_value = 'fullname';
                            }
                        }
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

        $current_bc = $prev_bc;
        return $options;
    }

    public function getNoHtmlValue($option)
    {
        global $modeCSV;
        $modeCSV = true;

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $value = '';

        if (isset($this->value)) {
            if (isset($this->params['values']) && !empty($this->params['values'])) {
                switch ($option) {
                    case 'key_label':
                        $value = $this->value;

                    default:
                    case 'label':
                        if (is_array($this->value)) {
                            foreach ($this->value as $valTmp) {
                                if (isset($this->params['values'][$valTmp])) {
                                    if (isset($this->params['values'][$valTmp]['label'])) {
                                        $value .= ($value ? ' - ' : '') . $this->params['values'][$valTmp]['label'];
                                    } elseif (is_string($this->params['values'][$valTmp])) {
                                        $value .= ($value ? ' - ' : '') . $this->params['values'][$valTmp];
                                    }
                                }
                            }
                        } else {
                            if (isset($this->params['values'][$this->value])) {
                                if (isset($this->params['values'][$this->value]['label'])) {
                                    $value .= ($value ? ' - ' : '') . $this->params['values'][$this->value]['label'];
                                } elseif (is_string($this->params['values'][$this->value])) {
                                    $value .= ($value ? ' - ' : '') . $this->params['values'][$this->value];
                                }
                            }
                        }
                        break;
                }
                if (!$value) {
                    if (is_array($this->value))
                        $value = implode(' - ', $this->value);
                    else
                        $value = $this->value;
                }
            } else {
                switch ($this->params['type']) {
                    case 'date':
                    case 'time':
                    case 'datetime':
                        if (!(string) $this->value) {
                            break;
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
                        $value = $dt->format($option);
                        break;

                    case 'bool':
                        if ($option === 'number') {
                            $value = (int) $this->value;
                        } else {
                            $value = ((int) $this->value ? 'OUI' : 'NON');
                        }
                        break;

                    case 'id_object':
                    case 'id_parent':
                        if (!$option) {
                            $option = 'id';
                        }
                        switch ($option) {
                            case 'id':
                                $value = $this->value;
                                break;

                            case 'ref_nom':
                            case 'fullname';
                            default:
                                switch ($this->params['type']) {
                                    case 'id_parent':
                                        $obj = $this->object->getParentInstance();
                                        break;

                                    case 'id_object':
                                        if (is_string($this->params['object']) && $this->params['object']) {
                                            $obj = $this->object->getChildObject($this->params['object']);
                                        } elseif (is_object($this->params['object']) && is_a($this->params['object'], 'BimpObject')) {
                                            $obj = $this->params['object'];
                                        } else {
                                            $obj = null;
                                        }
                                        break;
                                }

                                if (!BimpObject::objectLoaded($obj)) {
                                    $value = ($this->value ? $this->value : '');
                                } else {
                                    switch ($option) {
                                        case 'ref_nom':
                                            $ref = $obj->getRef();
                                            if ($ref) {
                                                $value .= $ref;
                                            }

                                            $name = $obj->getName();
                                            if ($name) {
                                                $value .= ($value ? ' - ' : '') . $name;
                                            }
                                            break;

                                        case 'fullname':
                                            if (method_exists($obj, 'getName')) {
                                                $value = $obj->getName();
                                            } elseif (isset($obj->ref)) {
                                                $value = $obj->ref;
                                            } else {
                                                $value = "N/C";
                                            }
                                            break;

                                        default:
                                            if ($obj->field_exists($option)) {
                                                $value = $obj->getData($option);
                                            } else {
                                                $value = $this->value;
                                            }
                                            break;
                                    }
                                }
                                break;
                        }
                        break;

                    case 'money':
                    case 'percent':
                    case 'float':
                    case 'qty':
                        if ($option === 'string') {
                            switch ($this->params['type']) {
                                case 'money':
                                    $value = BimpTools::displayMoneyValue($this->value);
                                    break;

                                case 'percent':
                                    $value = BimpTools::displayFloatValue($this->value) . ' %';
                                    break;

                                case 'float':
                                case 'qty':
                                    $value = BimpTools::displayFloatValue($this->value);
                                    break;
                            }
                        } else {
                            $value = str_replace(".", ",", $this->value);
                        }
                        break;

                    default:
                        $value = $this->value;
                }
            }
        }

        $current_bc = $prev_bc;
        return $value;
    }
}
