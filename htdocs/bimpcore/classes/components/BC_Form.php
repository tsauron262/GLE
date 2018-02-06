<?php

class BC_Form extends BC_Panel
{

    public static $type = 'form';
    public static $config_required = false;
    public $id_parent = null;
    public static $row_types = array(
        'field', 'association', 'custom'
    );
    public static $row_params = array(
        'show'        => array('data_type' => 'bool', 'default' => 1),
        'field'       => array('default' => ''),
        'association' => array('default'),
        'custom'      => array('data_type' => 'bool', 'default' => 0),
        'label'       => array('default' => ''),
    );
    public static $custom_row_params = array(
        'input_name' => array('required' => true, 'default' => ''),
        'display_if' => array('data_type' => 'array'),
        'depends_on' => array(),
        'data_type'  => array('default' => 'string'),
        'hidden'     => array('data_type' => 'bool', 'default' => 0),
        'value'      => array('data_type' => 'any', 'default' => '')
    );

    public function __construct(BimpObject $object, $id_parent = null, $name = '', $level = 1, $content_only = false)
    {
        $this->params_def['rows'] = array('type' => 'keys');
        $this->params_def['values'] = array('data_type' => 'array', 'request' => true, 'json' => true);
        $this->params_def['associations_params'] = array('data_type' => 'array', 'request' => true, 'json' => true);

        $this->id_parent = $id_parent;

        $path = null;

        if (!$name || $name === 'default') {
            if ($object->config->isDefined('form')) {
                $path = 'form';
            } elseif ($object->config->isDefined('forms/default')) {
                $path = 'forms';
                $name = 'default';
            }
        } else {
            $path = 'forms';
        }

        if ($object->isLoaded()) {
            $title = 'Edition ' . $object->getLabel('of_the') . ' ' . $object->id;
        } else {
            $title = 'Ajout ' . $object->getLabel('of_a');
        }

        if (is_null($id_parent) && !is_null($object)) {
            $parent_id_property = $object->getParentIdProperty();
            if (!is_null($parent_id_property)) {
                if (BimpTools::isSubmit($parent_id_property)) {
                    $this->id_parent = BimpTools::getValue($parent_id_property, null);
                }
            }
        }

        if (!is_null($id_parent) && !is_null($object)) {
            $object->setIdParent($id_parent);
        }

        parent::__construct($object, $name, $path, $content_only, $level, $title, 'edit');

        if (!is_null($this->id_parent)) {
            $this->data['id_parent'] = $this->id_parent;
        }

        if (isset($this->params['values']) && !is_null($this->params['values'])) {
            if (isset($this->params['values']['fields'])) {
                foreach ($this->params['values']['fields'] as $field_name => $value) {
                    $object->set($field_name, $value);
                }
            }

            if (isset($this->params['values']['associations'])) {
                foreach ($this->params['values']['associations'] as $association => $associates) {
                    $object->setAssociatesList($association, $associates);
                }
            }
        }
    }

    public function addAssociationObjectParams($object_module, $object_name, $id_object, $association)
    {
        $this->params['associations_params'][] = array(
            'object_module' => $object_module,
            'object_name'   => $object_name,
            'id_object'     => $id_object,
            'association'   => $association
        );
    }

    public function addAssociationAssociateParams($id_associate, $association)
    {
        $this->params['associations_params'][] = array(
            'id_associate' => $id_associate,
            'association'  => $association
        );
    }

    public function addAssociationParams($params)
    {
        $this->params['associations_params'][] = $params;
    }

    public function renderHtmlContent()
    {
        $html = '';

        $html .= '<form enctype="multipart/form-data" class="' . $this->object->object_name . '_form">';

        $parent_id_property = $this->object->getParentIdProperty();

        $html .= '<input type="hidden" name="module" value="' . $this->object->module . '"/>';
        $html .= '<input type="hidden" name="object_name" value="' . $this->object->object_name . '"/>';
        $html .= '<input type="hidden" name="id_object" value="' . ((isset($this->object->id) && $this->object->id) ? $this->object->id : 0) . '"/>';

        if (!is_null($parent_id_property) && !is_null($this->id_parent)) {
            $html .= '<input type="hidden" name="' . $parent_id_property . '" value="' . $this->id_parent . '"/>';
        }

        if (is_null($this->config_path)) {
            $fields = $this->object->getConf('fields', array(), true, 'array');
            foreach ($fields as $field_name => $field_params) {
                if ($field_name === $parent_id_property) {
                    continue;
                }
                $html .= $this->renderFieldRow($field_name);
            }
        } else {
            foreach ($this->params['rows'] as $row) {
                $row_params = parent::fetchParams($this->config_path . '/rows/' . $row, self::$row_params);
                if (!(int) $row_params['show']) {
                    continue;
                }
                if ($row_params['field']) {
                    $html .= $this->renderFieldRow($row_params['field'], $row_params);
                } elseif ($row_params['association']) {
                    $html .= $this->renderAssociationRow($row_params);
                } elseif ((int) $row_params['custom']) {
                    $html .= $this->renderCustomRow($row, $row_params);
                }
            }
        }

        $html .= '</form>';

        return $html;
    }

    public function renderHtmlFooter()
    {
        $html = '';
        $html .= BimpRender::renderButton(array(
                    'label'       => 'Annuler',
                    'icon_before' => 'times',
                    'classes'     => array('btn', 'btn-default'),
                    'attr'        => array(
                        'onclick' => 'closeObjectForm(\'' . $this->object->object_name . '\')'
                    )
                        ), 'button');
        $html .= BimpRender::renderButton(array(
                    'label'       => 'Enregistrer',
                    'icon_before' => 'save',
                    'classes'     => array('btn', 'btn-primary', 'pull-right'),
                    'attr'        => array(
                        'onclick' => 'saveObjectFromForm(\'' . $this->identifier . '\')'
                    )
                        ), 'button');
        return $html;
    }

    public function renderFieldRow($field_name, $params = array(), $label_cols = 3)
    {
        $field = new BC_Field($this->object, $field_name, true);
        if (!$field->params['editable'] || !$field->params['show']) {
            return '';
        }

        $label = (isset($params['label']) && $params['label']) ? $params['label'] : $field->params['label'];
        $input_type = $this->object->getConf('fields/' . $field_name . '/input/type', 'text', false);
        $display_if = (bool) $this->object->config->isDefined('fields/' . $field_name . '/display_if');
        $depends_on = (bool) $this->object->config->isDefined('fields/' . $field_name . '/depends_on');

        $html = '';

        $html .= '<div class="row formRow' . (($input_type === 'hidden') ? ' hidden' : '') . ($display_if ? ' display_if' : '') . '"';
        if ($display_if) {
            $html .= $field->renderDisplayIfData();
        }
        $html .= '>';

        $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-' . (int) $label_cols . '">';
        $html .= $label;
        $html .= '</div>';

        $html .= '<div class="formRowInput field col-xs-12 col-sm-6 col-md-' . (12 - (int) $label_cols) . '">';
        $html .= $field->renderHtml();
        $html .= '</div>';

        $html .= '</div>';

        if ($depends_on) {
            $html .= $field->renderDependsOnScript($this->identifier);
        }

        return $html;
    }

    public function renderAssociationRow($params = array(), $label_cols = 3)
    {
        if (!isset($params['association'])) {
            return '';
        }

        $html = '';

        $asso = new BimpAssociation($this->object, $params['association']);
        $associate = $asso->associate;
        $items = array();

        if ($this->object->isLoaded()) {
            $items = $asso->getAssociatesList();
        }

        $html .= '<div class="row formRow">';

        $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-' . (int) $label_cols . '">';
        if (isset($params['label'])) {
            $html .= $params['label'];
        } elseif (!is_null($associate)) {
            $html .= BimpTools::ucfirst(BimpObject::getInstanceLabel($associate, 'name_plur')) . ' associés';
        } else {
            $html .= 'Association "' . $params['association'] . '"';
        }
        $html .= '</div>';

        $html .= '<div class="formRowInput field col-xs-12 col-sm-6 col-md-' . (12 - (int) $label_cols) . '">';

        if (count($asso->errors)) {
            $html .= BimpRender::renderAlerts($asso->errors);
        } else {
            if ($this->object->config->isDefined('associations/' . $params['association'] . '/list')) {
                $html .= '<div class="inputContainer ' . $params['association'] . '_inputContainer"';
                $html .= ' data-field_name="' . $params['association'] . '"';
                $html .= ' data-initial_values="' . implode(',', $items) . '"';
                $html .= ' data-multiple="1"';
                $html .= '>';

                $html .= $asso->renderAssociatesCheckList();

                $html .= '</div>';
            } elseif ($this->object->config->isDefined('associations/' . $params['association'] . '/input')) {
                $input_name = $params['association'] . '_add_value';
                $input = new BC_Input($this->object, 'int', $input_name, 'associations/' . $params['association'] . '/input');
                $input->extraData['values_field'] = $params['association'];
                $html .= $input->renderHtml();

                if ($input->params['type'] === 'search_list') {
                    $label_input_name = $input_name . '_search';
                } else {
                    $label_input_name = $params['association'];
                }

                $values = array();

                foreach ($items as $id_item) {
                    if ($id_item) {
                        $values[$id_item] = $this->object->displayAssociate($params['association'], 'default', $id_item);
                    }
                }

                $html .= BimpInput::renderMultipleValuesList($this->object, $params['association'], $values, $label_input_name);
            } else {
                $html .= BimpRender::renderAlerts('Erreur de configuration - Champ non défini pour l\'association "' . $params['association'] . '"');
            }
        }
        $html .= '</div>';
        $html .= '</div>';

        unset($asso);


        return $html;
    }

    public function renderCustomRow($row, $params = array(), $label_cols = 3)
    {
        $html = '';

        $params = array_merge($params, parent::fetchParams($this->config_path . '/rows/' . $row, self::$custom_row_params));
        if (is_array($params['value'])) {
            $params['value'] = implode(',', $params['value']);
        }

        $html .= '<div class="row formRow' . ($params['hidden'] ? ' hidden' : '') . (!is_null($params['display_if']) ? ' display_if' : '') . '"';
        if (!is_null($params['display_if'])) {
            $html .= BC_Field::renderDisplayifDataStatic($params['display_if']);
        }
        $html .= '>';

        $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-' . (int) $label_cols . '">';
        $html .= $params['label'];
        $html .= '</div>';

        $html .= '<div class="formRowInput field col-xs-12 col-sm-6 col-md-' . (12 - (int) $label_cols) . '">';

        $html .= $this->renderCustomInput($row, $params);

        $html .= '</div>';
        $html .= '</div>';

        if (!is_null($params['depends_on'])) {
            $html .= BC_Field::renderDependsOnScriptStatic($this->object, $this->identifier, $params['input_name'], $params['depends_on']);
        }

        return $html;
    }

    public function renderCustomInput($row, $params = null)
    {
        if (is_null($params)) {
            if (!$this->object->config->isDefined($this->config_path . '/rows/' . $row)) {
                return BimpRender::renderAlerts('Erreur de configuration: ligne ' . $row . ' non définie');
            }

            $params = $this->fetchParams($this->config_path . '/rows/' . $row, self::$row_params);
            $params = array_merge($params, $this->fetchParams($this->config_path . '/rows/' . $row, self::$custom_row_params));
        }

        $html = '';
        if ($this->object->config->isDefined($this->config_path . '/rows/' . $row . '/input')) {
            $input = new BC_Input($this->object, $params['data_type'], $params['input_name'], $this->config_path . '/rows/' . $row . '/input', $params['value']);
            $input->extraClasses[] = 'customField';
            $input->extraData['form_row'] = $row;
            $html .= $input->renderHtml();
            unset($input);
        } elseif ($this->object->config->isDefined($this->config_path . '/rows/' . $row . '/content')) {
            $html .= '<div class="inputContainer ' . $params['input_name'] . '_inputContainer customField"';
            $html .= ' data-field_name="' . $params['input_name'] . '"';
            $html .= ' data-initial_values="' . $params['value'] . '"';
            $html .= ' data-multiple="1"';
            $html .= ' form_row="' . $row . '"';
            $html .= '>';

            $html .= $this->object->getConf($this->config_path . '/rows/' . $row . '/content', '', true);

            $html .= '</div>';
        } else {
            $html .= BimpRender::renderAlerts('Erreur de configuration: aucun contenu défini pour ce champ');
        }

        return $html;
    }
}
