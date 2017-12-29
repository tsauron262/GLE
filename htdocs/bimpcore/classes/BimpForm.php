<?php

class BimpForm
{

    protected $object = null;
    public $id_parent = null;
    public $form_name = null;
    public $form_path = null;
    public $form_identifier = null;
    protected $associations_params = array();
    public $errors = array();
    public static $row_types = array(
        'field', 'association', 'custom'
    );

    public function __construct(BimpObject $object, $form_name = 'default', $id_parent = null)
    {
        $this->object = $object;

        $this->id_parent = $id_parent;
        $this->form_name = $form_name;

        if (is_null($this->id_parent)) {
            $parent_id_property = $object->getParentIdProperty();
            if (!is_null($parent_id_property)) {
                if (BimpTools::isSubmit($parent_id_property)) {
                    $this->id_parent = BimpTools::getValue($parent_id_property, $object->getData($parent_id_property));
                }
            }
        }

        if (!is_null($id_parent)) {
            $this->object->setIdParent($id_parent);
        }

        if ($this->object->config->isDefined('forms/' . $form_name)) {
            $this->form_path = 'forms/' . $form_name;
        } elseif (($form_name === 'default')) {
            if ($this->object->config->isDefined('form')) {
                $this->form_path = 'form';
            } else {
                $this->form_path = '';
            }
        } else {
            $this->errors[] = 'Le formulaire "' . $form_name . '" n\'est pas défini dans le fichier de configuration';
        }

        if (!count($this->errors)) {
            $this->form_identifier = $this->object->object_name . '_' . (isset($this->object->id) && $this->object->id ? $this->object->id . '_' : '') . $this->form_name . '_form';
        }
    }

    public function setConfPath($path = '')
    {
        if (!is_null($this->form_path) && $this->form_path) {
            return $this->object->config->setCurrentPath($this->form_path . '/' . $path);
        }
        return $this->object->config->setCurrentPath($path);
    }

    public function addAssociationObjectParams($object_module, $object_name, $id_object, $association)
    {
        $this->associations_params[] = array(
            'object_module' => $object_module,
            'object_name'   => $object_name,
            'id_object'     => $id_object,
            'association'   => $association
        );
    }

    public function addAssociationAssociateParams($id_associate, $association)
    {
        $this->associations_params[] = array(
            'id_associate' => $id_associate,
            'association'  => $association
        );
    }

    public function addAssociationParams($params)
    {
        $this->associations_params[] = $params;
    }

    public function renderPanel($footer = '')
    {
        if (isset($this->object->id) && $this->object->id) {
            $title = 'Edition ' . $this->object->getLabel('of_the') . ' ' . $this->object->id;
            $icon = 'edit';
        } else {
            $title = 'Ajout ' . $this->object->getLabel('of_a');
            $icon = 'plus-square';
        }

        $content = $this->render();

        return BimpRender::renderPanel($title, $content, $footer, array(
                    'type' => 'secondary',
                    'icon' => $icon
        ));
    }

    public function render()
    {
//        $this->object = new BimpObject();

        $html = '';

        if (count($this->errors)) {
            $html .= BimpRender::renderAlerts($this->errors);
            return $html;
        }

        $html .= '<div id="' . $this->form_identifier . '_container" class="section container-fluid formContainer ' . $this->object->object_name . '_formContainer">';
        $html .= '<form id="' . $this->form_identifier . '" class="objectForm"';
        $html .= ' data-form_identifier="' . $this->form_identifier . '"';
        $html .= ' data-form_name="' . $this->form_name . '"';
        $html .= ' data-module_name="' . $this->object->module . '"';
        $html .= ' data-object_name="' . $this->object->object_name . '"';
        $html .= ' data-id_parent="' . (!is_null($this->id_parent) ? $this->id_parent : 0) . '"';
        $html .= ' enctype="multipart/form-data">';

        $html .= '<input type="hidden" name="module_name" value="' . $this->object->module . '"/>';
        $html .= '<input type="hidden" name="object_name" value="' . $this->object->object_name . '"/>';

        if (count($this->associations_params)) {
            $html .= '<input type="hidden" name="associations_params" value="' . htmlentities(json_encode($this->associations_params)) . '"/>';
        }

        if (isset($this->object->id) && $this->object->id) {
            $html .= '<input type="hidden" name="id_object" value="' . $this->object->id . '" data-default_value="' . $this->object->id . '"/>';
        }


        $parent_id_property = $this->object->getParentIdProperty();
        if (!is_null($this->id_parent)) {
            $html .= '<input type="hidden" name="' . $parent_id_property . '" value="' . $this->id_parent . '"/>';
        }

        $this->setConfPath();
        if (!is_null($this->form_path) && $this->form_path) {
            $rows = $this->object->getCurrentConf('rows', array(), true, 'array');
            foreach ($rows as $idx => $row) {
                $html .= $this->renderFormRow($this->form_path . '/rows/' . $idx);
            }
        } else {
            $fields = $this->object->getConf('fields', array(), true, 'array');
            foreach ($fields as $field => $field_params) {
                if ($parent_id_property && ($field === $parent_id_property)) {
                    continue;
                }
                $html .= $this->renderFormRow('fields/' . $field, $field);
            }
        }

        $html .= '</form>';
        $html .= '<div class="ajaxResultContainer" id="' . $this->form_identifier . '_result">';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderFormRow($row_path, $identifier = '', $label_cols = 3)
    {
        $item_path = '';
        $row_type = 'field';

        if (!$identifier) {
            foreach (self::$row_types as $rt) {
                if ($this->object->config->isDefined($row_path . '/' . $rt)) {
                    $row_type = $rt;
                    $identifier = $this->object->getConf($row_path . '/' . $rt, '');
                    break;
                }
            }
        }

        if ($this->object->config->isDefined($row_type . 's/' . $identifier)) {
            $item_path = $row_type . 's/' . $identifier;
        }

        $parent_id_property = $this->object->getParentIdProperty();
        if ($identifier === $parent_id_property) {
            return '';
        }

        $input_path = '';
        if ($this->object->config->isDefined($row_path . '/input')) {
            $input_path = $row_path;
        } else {
            $input_path = $item_path;
        }

        $label = $this->object->getConf($row_path . '/label', $this->object->getConf($item_path . '/label', '', true));
        $input_type = $this->object->getConf($input_path . '/input/type', '');
        $display_if = (bool) $this->object->config->isDefined($input_path . '/input/display_if');
        $depends_on = (bool) $this->object->config->isDefined($item_path . '/depends_on');

        $html = '';

        $html .= '<div class="row formRow' . (($input_type === 'hidden') ? ' hidden' : '') . ($display_if ? ' display_if' : '') . '"';
        if ($display_if) {
            $html .= self::renderDisplayIfData($this->object, $input_path . '/input/display_if');
        }
        $html .= '>';

        $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-' . (int) $label_cols . '">';
        $html .= $label;
        $html .= '</div>';

        $html .= '<div class="formRowInput ' . $row_type . ' col-xs-12 col-sm-6 col-md-' . (12 - (int) $label_cols) . '">';

        switch ($row_type) {
            case 'field':
                $html .= $this->renderFormField($identifier, $input_path);
                break;

            case 'association':
                $html .= $this->renderFormAssociation($identifier, $row_path);
                break;

            case 'custom':
                $html .= $this->renderCustomField($identifier, $row_path);
                break;

            default:
                $html .= BimpRender::renderAlerts('Erreur de configuration - Champ non défini');
                break;
        }

        $html .= '</div>';
        $html .= '</div>';

        if ($depends_on) {
            $html .= $this->renderDependsOnScript($identifier, $item_path . '/depends_on');
        }

        return $html;
    }

    public function renderFormField($field, $input_path)
    {
        $html = '';

        if (!$field) {
            $field = $this->object->getConf($input_path . '/input/name', '', true);
            if (!$field) {
                return BimpRender::renderAlerts('Erreur de configuration - aucun identifiant défini pour ce champ');
            }
            $value = $this->object->getConf($input_path . '/value', null, false, 'any');
        } else {
            $value = $this->object->getData($field);
        }

        $multiple = (bool) $this->object->getConf($input_path . '/input/multiple', false, false, 'bool');

        $html .= '<div id="' . $this->form_identifier . '_' . $field . '" class="inputContainer"';
        $html .= ' data-field_name="' . $field . '" ';
        $html .= ' data-multiple="' . ($multiple ? '1' : '0') . '">';

        $html .= self::renderInput($this->object, $input_path, $field, $value, $this->id_parent);

        $html .= '</div>';

        return $html;
    }

    public function renderCustomField($identifier, $conf_path)
    {
        $html = '';

        $multiple = (bool) $this->object->getConf($conf_path . '/input/multiple', false, false, 'bool');
        $depends_on = (bool) $this->object->getConf($conf_path . '/depends_on', '', false);

        $html .= '<div id="' . $this->form_identifier . '_' . $identifier . '" class="inputContainer customField"';
        $html .= ' data-field_name="' . $identifier . '" ';
        $html .= ' data-content_config_path="' . $conf_path . '/content' . '"';
        $html .= ' data-multiple="' . ($multiple ? '1' : '0') . '">';

        $html .= $this->object->getConf($conf_path . '/content', '');

        if ($depends_on) {
            $html .= $this->renderDependsOnScript($identifier, $conf_path . '/depends_on');
        }
        $html .= '</div>';
        return $html;
    }

    public function renderFormAssociation($association, $conf_path = '')
    {
        $html = '';

        $html .= '<div id="' . $this->form_identifier . '_' . $association . '" class="inputContainer"';
        $html .= ' data-field_name="' . $association . '" data-multiple="1">';

        $input_path = '';
        if ($this->object->config->isDefined($conf_path . '/input')) {
            $input_path = $conf_path;
        } elseif ($this->object->config->isDefined('associations/' . $association . '/input')) {
            $input_path = 'associations/' . $association;
        }

        if ($this->object->config->isDefined('associations/' . $association . '/list')) {
            $bimpAsso = new BimpAssociation($this->object, $association);

            if ($bimpAsso->errors) {
                $html .= BimpRender::renderAlerts($bimpAsso->errors);
            } else {
                $html .= $bimpAsso->renderAssociatesCheckList();
            }

            unset($bimpAsso);
        } elseif ($input_path) {
            $type = $this->object->getConf($input_path . '/input/type', '', true);
            $associates = $this->object->getAssociatesList($association);
            $field_name = $association . '_add_value';

            if ($type === 'search_list') {
                $html .= BimpInput::renderSearchListInput($this->object, 'associations/' . $association, $field_name, '');
            } else {
                $html .= BimpInput::renderInput($type, $field_name, '', array(), null, null, $this->form_identifier . '_' . $association);
            }

            $items = array();

            if (!is_null($associates)) {
                foreach ($associates as $id_associate) {
                    if ($id_associate) {
                        $items[$id_associate] = $this->object->displayAssociate($association, 'default', $id_associate);
                    }
                }
            }
            if ($type === 'search_list') {
                $label_field_name = $field_name . '_search';
            } else {
                $label_field_name = $association;
            }

            $html .= BimpInput::renderMultipleValuesList($this->object, $association, $items, $label_field_name);
        } else {
            $html .= BimpRender::renderAlerts('Erreur de configuration - Champ non défini pour l\'association "' . $association . '"');
        }

        $html .= '</div>';

        return $html;
    }

    public function renderDependsOnScript($identifier, $path)
    {
        $script = '';
        $depends_on = $this->object->getConf($path);
        if (!is_null($depends_on)) {
            if (is_array($depends_on)) {
                $dependances = $depends_on;
            } elseif (is_string($depends_on)) {
                $dependances = explode(',', $depends_on);
            }

            foreach ($dependances as $key => $dependance) {
                if (!$this->object->config->isDefined('fields/' . $dependance) &&
                        !$this->object->config->isDefined('associations/' . $dependance)) {
                    unset($dependances[$key]);
                }
            }

            if (count($dependances)) {
                $script .= '<script type="text/javascript">' . "\n";
                foreach ($dependances as $dependance) {
                    $script .= 'addInputEvent(\'' . $this->form_identifier . '\', \'' . $dependance . '\', \'change\', function() {' . "\n";
                    $script .= '  var data = {};' . "\n";
                    $script .= '  var $form = $(\'#' . $this->form_identifier . '\');';

                    foreach ($dependances as $dep) {
                        $script .= '  if ($form.find(\'[name=' . $dep . ']\').length) {' . "\n";
                        $script .= '      data[\'' . $dep . '\'] = getFieldValue($form, \'' . $dep . '\');' . "\n";
                        $script .= '  }' . "\n";
                    }
                    $script .= '  reloadObjectInput(\'' . $this->form_identifier . '\', \'' . $identifier . '\', data);' . "\n";
                    $script .= '});' . "\n";
                }
                $script .= '</script>' . "\n";
            }
        }
        return $script;
    }

    public static function renderDisplayIfData($object, $path)
    {
        $html = '';
        $input_name = $object->getConf($path . '/input_name', '');
        if ($input_name) {
            $html .= ' data-input_name="' . $input_name . '"';

            $show_values = $object->getConf($path . '/show_values', null, false, 'array');

            if (!is_null($show_values)) {
                if (is_array($show_values)) {
                    $show_values = implode(',', $show_values);
                }
                $html .= ' data-show_values="' . str_replace('"', "'", $show_values) . '"';
            }

            $hide_values = $object->getConf($path . '/hide_values', null, false, 'array');

            if (!is_null($hide_values)) {
                if (is_array($hide_values)) {
                    $hide_values = implode(',', $hide_values);
                }
                $html .= ' data-hide_values="' . str_replace('"', "'", $hide_values) . '"';
            }
        }
        return $html;
    }

    public static function renderInput(BimpObject $object, $config_path, $field_name, $value = null, $id_parent = null, $form = null, $option = null, $input_id = null, $input_name = null)
    {
        $prev_path = $object->config->current_path;

        if (!$object->config->setCurrentPath($config_path)) {
            return '<p class="alert alert-danger">Erreur technique: champ "' . $field_name . '" non défini (' . $config_path . ')</p>';
        }

        if (is_null($value)) {
            $value = $object->getCurrentConf('input/value', $object->getCurrentConf('default_value', '', false, 'any'), false, 'any');
        }

        if (is_null($form)) {
            global $db;
            $form = new Form($db);
        }

        if (is_null($input_name)) {
            $input_name = $field_name;
        }

        if (is_null($input_id)) {
            $input_id = $input_name;
        }

        $html = '';
        $options = array();

        $type = $object->getCurrentConf('input/type', '');

        $addon_right = $object->getCurrentConf('input/addon_right', null, false, 'array');
        $addon_left = $object->getCurrentConf('input/addon_left', null, false, 'array');
        $multiple = $object->getCurrentConf('input/multiple', false, false, 'bool');
        $data_type = $object->getCurrentConf('type', 'string');

        if (!$type) {
            if ($object->config->isDefined($config_path . '/values')) {
                $type = 'select';
            } else {
                switch ($data_type) {
                    case 'int':
                    case 'float':
                    case 'string':
                    case 'percent':
                    case 'money':
                        $type = 'text';
                        break;

                    case 'text':
                        $type = 'textarea';
                        break;

                    case 'bool':
                        $type = 'toggle';
                        break;

                    case 'time':
                    case 'date':
                    case 'datetime':
                        $type = $data_type;
                        break;
                }
            }
        }

        if (!is_null($addon_right)) {
            if (isset($addon_right['text'])) {
                $options['addon_right'] = $object->getCurrentConf('input/addon_right/text', '');
            } elseif (isset($addon_right['icon'])) {
                $options['addon_right'] = '<i class="fa fa-' . $object->getCurrentConf('input/addon_right/icon') . '"></i>';
            }
        } else {
            switch ($data_type) {
                case 'money':
                    $currency = $object->getCurrentConf('currency', 'EUR');
                    $options['addon_right'] = '<i class="fa fa-' . BimpTools::getCurrencyIcon($currency) . '"></i>';
                    break;

                case 'percent':
                    $options['addon_right'] = '<i class="fa fa-percent"></i>';
                    break;
            }
        }

        if (!is_null($addon_left)) {
            if (isset($addon_left['text'])) {
                $options['addon_left'] = $object->getCurrentConf('input/addon_left/text', '');
            } elseif (isset($addon_left['icon'])) {
                $options['addon_left'] = '<i class="fa fa-' . $object->getCurrentConf('input/addon_left/icon') . '"></i>';
            }
        }

        switch ($type) {
            case 'text':
                $options['data'] = array();
                $min = 'none';
                $max = 'none';
                $decimals = 0;
                switch ($data_type) {
                    case 'percent':
                        $min = '0';
                        $max = '100';
                    case 'money':
                    case 'float':
                        $decimals = $object->getCurrentConf('decimals', 2, false, 'int');
                    case 'int':
                        $options['data']['data_type'] = 'number';
                        $options['data']['decimals'] = $decimals;
                        $options['data']['min'] = $object->getCurrentConf('min', $min, false, 'int');
                        $options['data']['max'] = $object->getCurrentConf('max', $max, false, 'int');
                        $options['data']['unsigned'] = $object->getCurrentConf('unsigned', 0, false, 'bool');
                        break;

                    case 'string':
                        $options['data']['data_type'] = 'string';
                        $options['data']['size'] = $object->getCurrentConf('size', 128, false, 'int');
                        $options['data']['forbidden_chars'] = $object->getCurrentConf('forbidden_chars', '');
                        $options['data']['regexp'] = $object->getCurrentConf('regexp', '^.*$');
                        $options['data']['invalid_msg'] = $object->getCurrentConf('invalid_msg', '');
                        $options['data']['uppercase'] = $object->getCurrentConf('uppercase', 0, false, 'bool');
                        $options['data']['lowercase'] = $object->getCurrentConf('lowercase', 0, false, 'bool');
                        break;
                }
                break;

            case 'time':
            case 'date':
            case 'datetime':
                $options['display_now'] = $object->getCurrentConf('input/display_now', 0, false, 'bool');
                break;

            case 'textarea':
                $options['rows'] = $object->getCurrentConf('input/rows', 3, false, 'int');
                $options['auto_expand'] = $object->getCurrentConf('input/auto_expand', 0, false, 'bool');
                $options['note'] = $object->getCurrentConf('input/note', 0, false, 'bool');
                break;

            case 'select':
                if ($object->config->isDefined($config_path . '/input/options')) {
                    $options['options'] = $object->getCurrentConf('input/options', array(), true, 'array');
                } elseif ($object->config->isDefined($config_path . '/values')) {
                    $options['options'] = $object->getCurrentConf('values', array(), true, 'array');
                } else {
                    $options['options'] = array();
                }

                break;

            case 'toggle':
                $options['toggle_on'] = $object->getCurrentConf('input/toggle_on', 'OUI', false, 'string');
                $options['toggle_off'] = $object->getCurrentConf('input/toggle_off', 'NON', false, 'string');
                break;

            case 'search_list':
                $html .= BimpInput::renderSearchListInput($object, $config_path, $field_name . ($multiple ? '_add_value' : ''), ($multiple ? '' : $value), $option);
                break;

            case 'search_societe':
                $options['type'] = $object->getCurrentConf('input/societe_type', '');
                break;

            case 'check_list':
                $options['items'] = $object->getCurrentConf('input/items', array(), true, 'array');
                break;

            case 'custom':
                $content = $object->getCurrentConf('input/content', null, true);
                if (is_null($content)) {
                    $html .= '<p class="alert alert-danger">Erreur technique: Aucun input défini pour ce champ</p>';
                } else {
                    $html .= $content;
                }

            default:
                $method = 'get' . ucfirst($field_name) . 'Input';
                if (method_exists($object, $method)) {
                    $html .= $object->{$method}($multiple ? '' : $value);
                }
                break;
        }

        if (!$html) {
            $html = BimpInput::renderInput($type, ($field_name . ($multiple ? '_add_value' : '')), ($multiple ? '' : $value), $options, $form, $option = null, $input_id);
        }

        $help = $object->getCurrentConf('input/help', '');
        if ($help) {
            $html .= '<p class="inputHelp">' . $help . '</p>';
        }

        if ($multiple) {
            $values = array();
            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item) {
                        $values[$item] = $object->displayData($field_name, 'default', $item);
                    }
                }
            } elseif ($value) {
                $values[] = $value;
            }
            if ($type === 'search_list') {
                $label_field_name = $field_name . '_add_value_search';
            } else {
                $label_field_name = $field_name;
            }
            $html .= BimpInput::renderMultipleValuesList($object, $field_name, $values, $label_field_name);
        }

        $object->config->setCurrentPath($prev_path);
        return $html;
    }
}
