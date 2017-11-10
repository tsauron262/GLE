<?php

class BimpForm
{

    protected $object = null;
    protected $id_parent = null;
    protected $form_name = null;
    protected $form_path = null;
    public $form_identifier = null;
    public $errors = array();

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

        $html .= '<div id="' . $this->form_identifier . '_container" class="container-fluid formContainer ' . $this->object->object_name . '_formContainer">';
        $html .= '<form id="' . $this->form_identifier . '" class="objectForm"';
        $html .= ' data-form_identifier="' . $this->form_identifier . '"';
        $html .= ' data-form_name="' . $this->form_name . '"';
        $html .= ' data-module_name="' . $this->object->module . '"';
        $html .= ' data-object_name="' . $this->object->object_name . '"';
        $html .= '>';

        $html .= '<input type="hidden" name="module_name" value="' . $this->object->module . '"/>';
        $html .= '<input type="hidden" name="object_name" value="' . $this->object->object_name . '"/>';

        if (isset($this->object->id) && $this->object->id) {
            $html .= '<input type="hidden" name="id_object" value="' . $this->object->id . '" data-default_value="' . $this->object->id . '"/>';
        }

        $parent_id_property = '';
        if (!is_null($this->id_parent)) {
            $parent_id_property = $this->object->getParentIdProperty();
            $html .= '<input type="hidden" name="' . $parent_id_property . '" value="' . $this->id_parent . '"/>';
        }

        if (!is_null($this->form_path) && $this->form_path) {
            
        } else {
            $fields = $this->object->getConf('fields', array(), true, 'array');
            foreach ($fields as $field => $field_params) {
                if ($parent_id_property && ($field === $parent_id_property)) {
                    continue;
                }
                if ($this->setConfPath('fields/' . $field)) {
                    $html .= $this->renderField($field);
                }
            }
        }

        $html .= '</form>';
        $html .= '<div class="ajaxResultContainer" id="' . $this->form_identifier . '_result">';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderField($field, $label_cols = 3)
    {
        $parent_id_property = $this->object->getParentIdProperty();
        if ($field === $parent_id_property) {
            return '';
        }

        $html = '';

        $type = $this->object->getCurrentConf('input/type', '', true);
        $label = $this->object->getCurrentConf('label', '', true);
        $value = $this->object->getData($field);
        if (is_null($value)) {
            $value = $this->object->getCurrentConf('default_value');
        }

        $display_if = $this->object->config->isDefinedCurrent('input/display_if');

        $html .= '<div class="row fieldRow' . (($type === 'hidden') ? ' hidden' : '') . ($display_if ? ' display_if' : '') . '"';
        if ($display_if) {
            $html .= $this->renderDisplayIfData();
        }
        $html .= '>';

        $html .= '<div class="inputLabel col-xs-12 col-sm-6 col-md-' . (int) $label_cols . '">';
        $html .= $label;
        $html .= '</div>';
        $html .= '<div id="' . $this->form_identifier . '_' . $field . '" class="inputContainer col-xs-12 col-sm-6 col-md-' . (12 - (int) $label_cols) . '"';
        $html .= ' data-field_name="' . $field . '">';
        $html .= self::renderInput($this->object, $this->object->config->current_path, $field, $value);
        $html .= '</div>';
        if ($this->object->config->isDefinedCurrent('depends_on')) {
            $html .= $this->renderDependsOnScript($field);
        }
        $html .= '</div>';

        return $html;
    }

    public function renderDisplayIfData()
    {
        $html = '';
        $input_name = $this->object->getCurrentConf('input/display_if/input_name', '');
        if ($input_name) {
            $html .= ' data-input_name="' . $input_name . '"';

            $show_values = $this->object->getCurrentConf('input/display_if/show_values', null, false, 'array');

            if (!is_null($show_values)) {
                if (is_array($show_values)) {
                    $show_values = implode(',', $show_values);
                }
                $html .= ' data-show_values="' . str_replace('"', "'", $show_values) . '"';
            }

            $hide_values = $this->object->getCurrentConf('input/display_if/hide_values', null, false, 'array');

            if (!is_null($hide_values)) {
                if (is_array($hide_values)) {
                    $hide_values = implode(',', $hide_values);
                }
                $html .= ' data-hide_values="' . str_replace('"', "'", $hide_values) . '"';
            }
        }
        return $html;
    }

    public function renderDependsOnScript($field)
    {
        $script = '';
        $depends_on = $this->object->getCurrentConf('depends_on');
        if (!is_null($depends_on)) {
            if (is_array($depends_on)) {
                $dependances = $depends_on;
            } elseif (is_string($depends_on)) {
                $dependances = explode(',', $depends_on);
            }

            foreach ($dependances as $key => $dependance) {
                if (!$this->object->config->isDefined('fields/' . $dependance . '/input')) {
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
                        $script .= '  if ($form.find(\'#' . $dep . '\').length) {' . "\n";
                        $script .= '      data[\'' . $dep . '\'] = getFieldValue($form, \'' . $dep . '\');' . "\n";
                        $script .= '  }' . "\n";
                    }
                    $script .= '  reloadObjectInput(\'' . $this->form_identifier . '\', \'' . $field . '\', data);' . "\n";
                    $script .= '});' . "\n";
                }
                $script .= '</script>' . "\n";
            }
        }
        return $script;
    }

    public static function renderObjectAssociationForm(BimpObject $object, $association)
    {
        $prev_path = $object->config->current_path;

        if (!$object->config->setCurrentPath('associations/' . $association)) {
            return '<p class="alert alert-danger">Configuration non définie pour le type d\'association "' . $association . '"</p>';
        }

        $associate = $object->getCurrentConf('associate_object', null, true, 'object');

        if (is_null($associate)) {
            return '<p class="alert alert-danger">Erreur technique: Paramètres d\'instanciation des objets associés invalides</p>';
        }

        $list = array();
        $method = 'get' . ucfirst($association) . 'AssociationList';

        if (method_exists($object, $method)) {
            $list = $object->{$method}();
        } else {
            $list = $object->getAssociationList($association);
        }

        $currents = $object->getAssociatedObjectsIds($association);

        if (is_a($associate, 'BimpObject')) {
            $label = $associate->getLabel('name_plur') . ' associé' . ($associate->isLabelFemale() ? 'e' : '') . 's';
            $is_label_female = $associate->isLabelFemale();
        } else {
            $label = 'objets "' . get_class($associate) . '" associés';
            $is_label_female = false;
        }

        $html .= '<div>';

        $html .= '<table class="noborder" width="100%">';

        $html .= '<tr class="liste_titre">';
        $html .= '<td>';
        $html .= ucfirst($label);
        $html .= '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<td>';
        if (count($list)) {
            $html .= '<div id="' . $object->object_name . '_' . $association . '_associations_list">';
            foreach ($list as $item) {
                $html .= '<div class="formRow">';
                $html .= '<input type="checkbox" value="' . $item['id'] . '" id="' . $association . '_' . $item['id'] . '" name="' . $association . '[]"';
                if (in_array($item['id'], $currents)) {
                    $html .= ' checked';
                }
                $html .= '/>';
                $html .= '<label for="' . $association . '_' . $item['id'] . '">';
                if (isset($item['label'])) {
                    $html .= $item['label'];
                } elseif (isset($item['title'])) {
                    $html .= $item['title'];
                } elseif (isset($item['name'])) {
                    $html .= $item['name'];
                } else {
                    $html .= ucfirst(BimpObject::getInstanceLabel($associate)) . ' n°' . $item['id'];
                }
                $html .= '</label>';
                $html .= '</div>';
            }
            $html .= '</div>';
        } else {
            $html .= '<div class="alert alert-warning">';
            $html .= 'Il n\'y a aucun' . ($is_label_female ? 'e' : '') . ' ' . BimpObject::getInstanceLabel($associate) . ' à associer';
            $html .= '</div>';
        }

        $html .= '<div id="' . $object->object_name . '_' . $association . '_associatonsAjaxResult"></div>';

        $html .= '<div class="formSubmit">';
        $html .= '<span class="butAction" onclick="saveObjectAssociations(' . $object->id . ', \'' . $object->object_name . '\', \'' . $association . '\', $(this));">';
        $html .= 'Enregistrer les ' . $label;
        $html .= '</span>';
        $html .= '</div>';
        $html .= '</td/>';
        $html .= '</tr/>';

        $html .= '</table/>';

        $html .= '</div>';

        $object->config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderInput(BimpObject $object, $config_path, $field_name, $value = null, $id_parent = null, $form = null, $option = null, $input_id = null)
    {
        $prev_path = $object->config->current_path;

        if (!$object->config->setCurrentPath($config_path)) {
            return '<p class="alert alert-danger">Erreur technique: champ "' . $field_name . '" non défini (' . $config_path . ')</p>';
        }

        if (is_null($value)) {
            $value = $object->getCurrentConf('default_value', '');
        }

        if (is_null($form)) {
            global $db;
            $form = new Form($db);
        }

        $html = '';
        $type = $object->getCurrentConf('input/type', '');
        $options = array();
        switch ($type) {
            case 'datetime':
                if (!$value) {
                    $value = date('Y-m-d H:i:s');
                }
                $options['display_now'] = $object->getCurrentConf('input/display_now', 0, false, 'bool');
                break;

            case 'textarea':
                $options['rows'] = $object->getCurrentConf('input/rows', 3, false, 'int');
                break;

            case 'select':
                $options['options'] = $object->getCurrentConf('input/options', array(), true, 'array');
                break;

            case 'search_list':
                $html .= BimpInput::renderSearchListInput($object, $config_path, $field_name, $value, $option);
                break;

            case 'custom':
                $content = $object->getCurrentConf('input/html', null, true);
                if (is_null($content)) {
                    $html .= '<p class="alert alert-danger">Erreur technique: Aucun input défini pour ce champ</p>';
                } else {
                    $html .= $content;
                }

            default:
                $method = 'get' . ucfirst($field_name) . 'Input';
                if (method_exists($object, $method)) {
                    $html .= $object->{$method}($value);
                }
                break;
        }

        if (!$html) {
            $html = BimpInput::renderInput($type, $field_name, $value, $options, $form, $option = null, $input_id);
        }
        $help = $object->getCurrentConf('input/help', '');
        if ($help) {
            $html .= '<p class="inputHelp">' . $help . '</p>';
        }
        $object->config->setCurrentPath($prev_path);
        return $html;
    }
}
