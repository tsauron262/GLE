<?php

class BC_Form extends BC_Panel
{

    public $component_name = 'Formulaire';
    public static $type = 'form';
    public static $config_required = false;
    public $id_parent = null;
    public $fields_prefix = '';
    public $sub_objects = array();
    public static $row_types = array(
        'field', 'association', 'custom', 'object'
    );
    public static $row_params = array(
        'show'               => array('data_type' => 'bool', 'default' => 1),
        'field'              => array('default' => ''),
        'association'        => array('default' => ''),
        'object'             => array('default' => ''),
        'custom'             => array('data_type' => 'bool', 'default' => 0),
        'label'              => array('default' => ''),
        'create_form'        => array('default' => ''),
        'create_form_values' => array('data_type' => 'array'),
        'create_form_label'  => array('default' => 'Créer'),
        'display'            => array('default' => 'default'),
        'hidden'             => array('data_type' => 'bool', 'default' => 0),
        'required'           => array('data_type' => 'bool', 'default' => null),
        'edit'               => array('data_type' => 'bool', 'default' => 1),
        'display_if'         => array('data_type' => 'array', 'compile' => 1, 'default' => null)
    );
    public static $association_params = array(
        'display_if' => array('data_type' => 'array'),
        'depends_on' => array(),
    );
    public static $custom_row_params = array(
        'input_name'     => array('required' => true, 'default' => ''),
        'display_if'     => array('data_type' => 'array'),
        'depends_on'     => array(),
        'data_type'      => array('default' => 'string'),
        'value'          => array('data_type' => 'any', 'default' => ''),
        'no_container'   => array('data_type' => 'bool', 'default' => 0),
        'multiple'       => array('data_type' => 'bool', 'default' => 0),
        'keep_new_value' => array('data_type' => 'bool', 'default' => 0),
        'items_data_type' => array()
    );
    public static $object_params = array(
        'form_name'   => array('default' => 'default'),
        'form_values' => array('data_type' => 'array', 'default' => array()),
        'multiple'    => array('data_type' => 'bool', 'default' => 0),
        'display_if'  => array('data_type' => 'array'),
        'depends_on'  => array(),
        'on_create'   => array('data_type' => 'bool', 'default' => 1),
        'on_edit'     => array('data_type' => 'bool', 'default' => 0)
    );

    public function __construct(BimpObject $object, $id_parent = null, $name = '', $level = 1, $content_only = false, $on_save = null)
    {
        $this->params_def['rows'] = array('type' => 'keys');
        $this->params_def['values'] = array('data_type' => 'array', 'request' => true, 'json' => true);
        $this->params_def['associations_params'] = array('data_type' => 'array', 'request' => true, 'json' => true);
        $this->params_def['on_save'] = array('default' => 'close');
        $this->params_def['sub_objects'] = array('type' => 'keys');
        $this->params_def['no_auto_submit'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['force_edit'] = array('data_type' => 'bool', 'default' => 0);

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
            $title = 'Edition ' . $object->getLabel('of_the') . ' ' . $object->getInstanceName();
        } else {
            $title = 'Ajout ' . $object->getLabel('of_a');
        }

        if ((is_null($id_parent) || !$id_parent) && !is_null($object)) {
            $parent_id_property = $object->getParentIdProperty();
            if (!is_null($parent_id_property)) {
                if (BimpTools::isSubmit($parent_id_property)) {
                    $this->id_parent = BimpTools::getValue($parent_id_property, null);
                } else {
                    $id_parent = $object->getParentId();
                    if (!is_null($id_parent) && $id_parent) {
                        $this->id_parent = $id_parent;
                    }
                }
            }
        }

        if (!is_null($this->id_parent) && $this->id_parent && !is_null($object)) {
            $object->setIdParent($this->id_parent);
        }

        if (!is_null($on_save)) {
            $this->params['on_save'] = $on_save;
        }

        parent::__construct($object, $name, $path, $content_only, $level, $title, 'edit');

        if (isset($this->params['values']) && !is_null($this->params['values'])) {
            if (isset($this->params['values']['fields'])) {
                foreach ($this->params['values']['fields'] as $field_name => $value) {
                    if (!is_null($value)) {
                        $object->set($field_name, $value);
                    }
                }
            }

            if (isset($this->params['values']['associations'])) {
                foreach ($this->params['values']['associations'] as $association => $associates) {
                    $object->setAssociatesList($association, $associates);
                }
            }
        }

        // $id_parent a pu être fourni via params['values']. 

        if (!is_null($object)) {
            $this->id_parent = (int) $object->getParentId();

            $form_errors = array();
            if (!$object->isFormAllowed($this->name, $form_errors)) {
                $this->errors[] = BimpTools::getMsgFromArray($form_errors, 'Cette édition n\'est pas permise');
            }
        }

        if (!is_null($this->id_parent)) {
            $this->data['id_parent'] = (int) $this->id_parent;
        }

        $this->data['on_save'] = $this->params['on_save'];
        $this->data['no_auto_submit'] = $this->params['no_auto_submit'];
    }

    public function setValues($values)
    {
        if (isset($values['fields'])) {
            if (!isset($this->params['values']['fields'])) {
                $this->params['values']['fields'] = array();
            }
            foreach ($values['fields'] as $field_name => $value) {
                $this->params['values']['fields'][$field_name] = $value;
                $this->object->set($field_name, $value);
            }
        }

        if (isset($values['associations'])) {
            if (!isset($this->params['values']['associations'])) {
                $this->params['values']['associations'] = array();
            }

            foreach ($values['associations'] as $association => $associates) {
                $this->params['values']['associations'][$association] = $associates;
                $this->object->setAssociatesList($association, $associates);
            }
        }

        if (isset($values['objects'])) {
            if (!isset($this->params['values']['objects'])) {
                $this->params['values']['objects'] = array();
            }

            foreach ($values['objects'] as $object_name => $objects) {
                if (!isset($this->params['values']['objects'][$object_name])) {
                    $this->params['values']['objects'][$object_name] = array();
                }

                foreach ($objects as $object_values) {
                    $this->params['values']['objects'][$object_name][] = $object_values;
                }
            }
        }
    }

    public function setFieldsPrefix($prefix)
    {
        $this->fields_prefix = $prefix;
        $this->data['fields_prefix'] = $prefix;
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

    public function renderHtmlContent($form_tag = true)
    {
        $html = parent::renderHtmlContent();

        if ($form_tag) {
            $html .= '<form enctype="multipart/form-data" class="' . $this->object->object_name . '_form">';
        }

        $parent_id_property = $this->object->getParentIdProperty();

        $html .= '<input type="hidden" name="' . $this->fields_prefix . 'module" value="' . $this->object->module . '"/>';
        $html .= '<input type="hidden" name="' . $this->fields_prefix . 'object_name" value="' . $this->object->object_name . '"/>';
        $html .= '<input type="hidden" name="' . $this->fields_prefix . 'id_object" value="' . ((isset($this->object->id) && $this->object->id) ? $this->object->id : 0) . '"/>';
        $html .= '<input type="hidden" name="' . $this->fields_prefix . 'force_edit" value="' . $this->params['force_edit'] . '"/>';

        if (!is_null($parent_id_property)) {
            $html .= '<input type="hidden" name="' . $this->fields_prefix . $parent_id_property . '" value="' . $this->id_parent . '"/>';
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
                    $row_params = array_merge($row_params, parent::fetchParams($this->config_path . '/rows/' . $row, self::$association_params));
                    $html .= $this->renderAssociationRow($row_params);
                } elseif ((int) $row_params['custom']) {
                    $html .= $this->renderCustomRow($row, $row_params);
                } elseif ($row_params['object']) {
                    $row_params = array_merge($row_params, parent::fetchParams($this->config_path . '/rows/' . $row, self::$object_params));
                    if (!(int) $row_params['on_edit'] && $this->object->isLoaded()) {
                        continue;
                    }
                    if (!(int) $row_params['on_create'] && !$this->object->isLoaded()) {
                        continue;
                    }
                    $html .= '<div>';
                    $html .= $this->renderObjectRow($row_params['object'], $row_params);
                    $html .= '</div>';
                }
            }
        }

        $html .= '<input type="hidden" name="' . $this->fields_prefix . 'sub_objects" value="' . implode(',', $this->sub_objects) . '"/>';

        if ($form_tag) {
            $html .= '</form>';
        }

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
                    'icon_before' => 'fas_save',
                    'classes'     => array('btn', 'btn-primary', 'pull-right', 'save_object_button'),
                    'attr'        => array(
                        'onclick' => 'saveObjectFromForm(\'' . $this->identifier . '\')'
                    )
                        ), 'button');
        return $html;
    }

    public function renderFieldRow($field_name, $params = array(), $label_cols = 3)
    {
        $field = new BC_Field($this->object, $field_name, true, 'fields', true);
        $field->name_prefix = $this->fields_prefix;
        $field->display_card_mode = 'visible';

        if (isset($params['edit']) && !(int) $params['edit']) {
            $field->params['editable'] = 0;
        }

        if (!$field->params['show']) {
            return '';
        }

        if ((int) $this->params['force_edit']) {
            $field->force_edit = true;
        }

        if ($this->object->isDolObject()) {
            if (!$this->object->dol_field_exists($field_name)) {
                return '';
            }
        }

        $label = (isset($params['label']) && $params['label']) ? $params['label'] : $field->params['label'];
        $required = (!is_null($params['required']) ? (int) $params['required'] : (int) $field->params['required']);
        $input_type = $this->object->getConf('fields/' . $field_name . '/input/type', 'text', false);
        $display_if = (bool) (!is_null($params['display_if']));
        if (!$display_if) {
            $display_if = (bool) $this->object->config->isDefined('fields/' . $field_name . '/display_if');
        }
        $depends_on = (bool) $this->object->config->isDefined('fields/' . $field_name . '/depends_on');

        $html = '';

        $html .= '<div class="row formRow' . (($input_type === 'hidden' || (int) $params['hidden']) ? ' hidden' : '') . ($display_if ? ' display_if' : '') . '"';
        if ($display_if) {
            if (!is_null($params['display_if'])) {
                $html .= BC_Field::renderDisplayifDataStatic($params['display_if'], $this->fields_prefix);
            } else {
                $html .= $field->renderDisplayIfData();
            }
        }
        $html .= '>';

        $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-' . (int) $label_cols . '">';
        $html .= $label;
        if ($required) {
            $html .= '&nbsp;*';
        }
        $html .= '</div>';

        $html .= '<div class="formRowInput field col-xs-12 col-sm-6 col-md-' . (12 - (int) $label_cols) . '">';

        if ($field->params['editable']) {
            if ($field->params['type'] === 'id_object' ||
                    ($field->params['type'] === 'items_list' && $field->params['items_data_type'] === 'id_object')) {
                $form_name = ($params['create_form'] ? $params['create_form'] : ($field->params['create_form'] ? $field->params['create_form'] : ''));
                $form_values = ($params['create_form_values'] ? $params['create_form_values'] : ($field->params['create_form_values'] ? $field->params['create_form_values'] : ''));
                $btn_label = ($params['create_form_label'] ? $params['create_form_label'] : ($field->params['create_form_label'] ? $field->params['create_form_label'] : 'Créer'));

                if ($form_name) {
                    $html .= self::renderCreateObjectButton($this->object, $this->identifier, $field->params['object'], $this->fields_prefix . $field->name, $form_name, $form_values, $btn_label);
                }
            }
        }
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

        $html .= '<div class="row formRow' . ($params['hidden'] ? ' hidden' : '') . (!is_null($params['display_if']) ? ' display_if' : '') . '"';
        if (!is_null($params['display_if'])) {
            $html .= BC_Field::renderDisplayifDataStatic($params['display_if'], $this->fields_prefix);
        }
        $html .= '>';

        $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-' . (int) $label_cols . '">';
        if ($params['label']) {
            $html .= $params['label'];
        } elseif ($this->object->config->isDefined('associations/' . $params['association'] . '/label')) {
            $html .= $this->object->getConf('associations/' . $params['association'] . '/label');
        } elseif (!is_null($associate)) {
            $html .= BimpTools::ucfirst(BimpObject::getInstanceLabel($associate, 'name_plur')) . ' associés';
        } else {
            $html .= 'Association "' . $params['association'] . '"';
        }
        if ((int) $params['required']) {
            $html .= ' *';
        }
        $html .= '</div>';

        $html .= '<div class="formRowInput field col-xs-12 col-sm-6 col-md-' . (12 - (int) $label_cols) . '">';

        if (count($asso->errors)) {
            $html .= BimpRender::renderAlerts($asso->errors);
        } else {
            if ($this->object->config->isDefined('associations/' . $params['association'] . '/list')) {
                $content = $asso->renderAssociatesCheckList($this->fields_prefix);
                $html .= BimpInput::renderInputContainer($params['association'], implode(',', $items), $content, $this->fields_prefix, $params['required'], 1);
            } elseif ($this->object->config->isDefined('associations/' . $params['association'] . '/input')) {
                if (is_a($associate, 'BimpObject')) {
                    $form_name = ($params['create_form'] ? $params['create_form'] : $this->object->getConf('associations/' . $params['association'] . '/create_form', ''));
                    if ($form_name) {
                        $html .= $this->renderCreateObjectButton($this->object, $this->identifier, '', '', $form_name, null, 'Créer', false, $associate);
                    }
                }
                $html .= $asso->renderAddAssociateInput($params['display'], false, $this->fields_prefix, (int) $params['required']);
            } else {
                $html .= BimpRender::renderAlerts('Erreur de configuration - Champ non défini pour l\'association "' . $params['association'] . '"');
            }
        }
        $html .= '</div>';
        $html .= '</div>';

        if (!is_null($params['depends_on'])) {
            $html .= BC_Field::renderDependsOnScriptStatic($this->object, $this->identifier, $params['association'], $params['depends_on'], $this->fields_prefix, 0);
        }

        unset($asso);


        return $html;
    }

    public function renderCustomRow($row, $params = array(), $label_cols = 3)
    {
        $html = '';

        $params = array_merge($params, parent::fetchParams($this->config_path . '/rows/' . $row, self::$custom_row_params));
        if (is_array($params['value'])) {
            if ($params['data_type'] === 'json') {
                $params['value'] = json_encode($params['value']);
            } else {
                $params['value'] = implode(',', $params['value']);
            }
        }

        if ($params['data_type'] === 'items_list') {
            $params['items_data_type'] = $this->object->getConf($this->config_path . '/rows/' . $row . '/items_data_type', 'string');
        }

        $html .= '<div class="row formRow' . ($params['hidden'] ? ' hidden' : '') . (!is_null($params['display_if']) ? ' display_if' : '') . '"';
        if (!is_null($params['display_if'])) {
            $html .= BC_Field::renderDisplayifDataStatic($params['display_if'], $this->fields_prefix);
        }
        $html .= '>';

        $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-' . (int) $label_cols . '">';
        $html .= $params['label'];
        if ((int) $params['required']) {
            $html .= '&nbsp;*';
        }
        $html .= '</div>';

        $html .= '<div class="formRowInput field col-xs-12 col-sm-6 col-md-' . (12 - (int) $label_cols) . '">';

        $html .= $this->renderCustomInput($row, $params);

        $html .= '</div>';
        $html .= '</div>';

        if (!is_null($params['depends_on'])) {
            $html .= BC_Field::renderDependsOnScriptStatic($this->object, $this->identifier, $params['input_name'], $params['depends_on'], $this->fields_prefix, (int) $params['keep_new_value']);
        }

        return $html;
    }

    public function renderObjectRow($object_name, $params = array())
    {
        $html = '';

        if (!$this->object->object_exists($object_name)) {
            return BimpRender::renderAlerts('Erreur de configuration: sous-object "' . $object_name . '" non défini');
        }

        $object = $this->object->config->getObject('', $object_name);

        if (!is_a($object, 'BimpObject')) {
            return BimpRender::renderAlerts('Erreur de configuration: sous-object "' . $object_name . '" invalide');
        }

        $object_id_parent = 0;

        if ($this->object->isChild($object)) {
            $object->parent = $this->object;
            if (BimpObject::objectLoaded($this->object)) {
                $object_id_parent = $this->object->id;
            }
        }

        $this->sub_objects[] = $this->fields_prefix . $object_name;

        $html .= '<div id="' . $this->fields_prefix . $object_name . '_subObjectsContainer" class="subObjects row formInputGroup' . (!is_null($params['display_if']) ? ' display_if' : '') . '"';
        if (!is_null($params['display_if'])) {
            $html .= BC_Field::renderDisplayifDataStatic($params['display_if']);
        }
        $html .= ' data-field_prefix="' . $this->fields_prefix . '"';
        $html .= '>';

        $html .= '<div class="formGroupHeading">';

        $html .= '<div class="formGroupTitle">';
        $title = '';
        if (isset($params['title'])) {
            $title = $params['title'];
        } elseif ((int) $params['multiple']) {
            $title = BimpTools::ucfirst(BimpObject::getInstanceLabel($object, 'name_plur'));
        } else {
            $title = BimpTools::ucfirst(BimpObject::getInstanceLabel($object));
        }
        $html .= '<h3>' . $title . '</h3>';
        $html .= '</div>';
        $html .= '</div>';

        $form = new BC_Form($object, null, $params['form_name'], 1, true);
        $form->setValues($params['form_values']);
        $form->setFieldsPrefix($this->fields_prefix . $object_name . '_');

        $subFormIdentifier = '';
        $nb_items = 0;
        if ((int) $params['multiple']) {
            $subFormIdentifier = $this->fields_prefix . $object_name . '_sub_object_idx_sub_object_form';
            $form->identifier = $subFormIdentifier;
            $html .= '<div class="subObjectFormTemplate">';
            $html .= '<div id="' . $subFormIdentifier . '" class="formInputGroup subObjectForm"';
            $html .= ' data-module="' . $object->module . '"';
            $html .= ' data-object_name="' . $object->object_name . '"';
            $html .= ' data-name="' . $params['form_name'] . '"';
            $html .= ' data-id_object="' . (BimpObject::objectLoaded($object) ? $object->id : 0) . '"';
            $html .= ' data-id_parent="' . $object_id_parent . '"';
            $html .= ' data-idx="sub_object_idx"';
            $html .= '>';
            $html .= '<div class="formGroupHeading">';
            $html .= '<div class="formGroupTitle">';
            $html .= '<h4>' . BimpTools::ucfirst(BimpObject::getInstanceLabel($object)) . ' #sub_object_idx</h4>';
            $html .= '</div>';
            $html .= '<div class="formGroupButtons">';
            $html .= '<span class="btn btn-default" onclick="removeSubObjectForm($(this))">';
            $html .= '<i class="fas fa5-trash-alt iconLeft"></i>Supprimer</span>';
            $html .= '</div>';
            $html .= '</div>';
            $form->fields_prefix .= 'sub_object_idx_';
            $html .= $form->renderHtmlContent(false);
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div class="subObjectsMultipleForms">';

            if (isset($this->params['values']['objects'][$object_name])) {
                foreach ($this->params['values']['objects'][$object_name] as $object_values) {
                    $nb_items++;

                    $form->object->reset();
                    $form->params['values'] = array();
                    $form->setValues($params['form_values']);
                    $form->setValues($object_values);

                    $html .= '<div id="' . $this->fields_prefix . $object_name . '_' . $nb_items . '_sub_object_form" class="formInputGroup subObjectForm"';
                    $html .= ' data-module="' . $object->module . '"';
                    $html .= ' data-object_name="' . $object->object_name . '"';
                    $html .= ' data-name="' . $params['form_name'] . '"';
                    $html .= ' data-id_object="' . (BimpObject::objectLoaded($object) ? $object->id : 0) . '"';
                    $html .= ' data-id_parent="' . $object_id_parent . '"';
                    $html .= ' data-idx="' . $nb_items . '"';
                    $html .= '>';
                    $html .= '<div class="formGroupHeading">';
                    $html .= '<div class="formGroupTitle">';
                    $html .= '<h4>' . BimpTools::ucfirst(BimpObject::getInstanceLabel($object)) . ' #' . $nb_items . '</h4>';
                    $html .= '</div>';
                    $html .= '<div class="formGroupButtons">';
                    $html .= '<span class="btn btn-default" onclick="removeSubObjectForm($(this))">';
                    $html .= '<i class="fas fa5-trash-alt iconLeft"></i>Supprimer</span>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $form->setFieldsPrefix($this->fields_prefix . $object_name . '_' . $nb_items . '_');
                    $html .= $form->renderHtmlContent(false);
                    $html .= '</div>';
                }
            }

            $html .= '</div>';
            $html .= '<div class="formGroupButtons">';
            $html .= '<span class="btn btn-default" onclick="addSubObjectForm($(this), \'' . $this->fields_prefix . $object_name . '\')">';
            $html .= '<i class="fa fa-plus-circle iconLeft"></i>Ajouter ' . BimpObject::getInstanceLabel($object, 'a') . '</span>';
            $html .= '</div>';
        } else {
            $nb_items = 1;
            $subFormIdentifier = $this->fields_prefix . $object_name . '_sub_object_form';
            $form->identifier = $subFormIdentifier;

            if (isset($this->params['values']['objects'][$object_name])) {
                $form->setValues($this->params['values']['objects'][$object_name]);
            }

            $html .= '<div id="' . $subFormIdentifier . '" class="subObjectForm"';
            $html .= ' data-module="' . $object->module . '"';
            $html .= ' data-object_name="' . $object->object_name . '"';
            $html .= ' data-name="' . $params['form_name'] . '"';
            $html .= ' data-id_object="' . (BimpObject::objectLoaded($object) ? $object->id : 0) . '"';
            $html .= ' data-id_parent="' . $object_id_parent . '"';
            $html .= '>';
            $html .= $form->renderHtmlContent(false);
            $html .= '</div>';
        }

        $html .= '<input type="hidden" name="' . $this->fields_prefix . $object_name . '_multiple" value="' . (int) $params['multiple'] . '"/>';
        $html .= '<input type="hidden" name="' . $this->fields_prefix . $object_name . '_count" value="' . $nb_items . '"/>';
        $html .= '</div>';

        if (!is_null($params['depends_on'])) {
            $html .= BC_Field::renderDependsOnScriptStatic($this->object, $this->identifier, $object_name, $params['depends_on'], $this->fields_prefix);
        }

        return $html;
    }

    public function renderCustomInput($row, $params = null)
    {
        $row_path = $this->config_path . '/rows/' . $row;
        if (is_null($params)) {
            if (!$this->object->config->isDefined($row_path)) {
                return BimpRender::renderAlerts('Erreur de configuration: ligne ' . $row . ' non définie');
            }

            $params = $this->fetchParams($row_path, self::$row_params);
            $params = array_merge($params, $this->fetchParams($row_path, self::$custom_row_params));
        }

//        if ((is_null($params['value']) || $params['value'] === '') && isset($this->params['values']['fields'][$params['input_name']])) {
        if (isset($this->params['values']['fields'][$params['input_name']])) {
            $params['value'] = $this->params['values']['fields'][$params['input_name']];
        }

        $html = '';

        if (($params['data_type'] === 'id_object' || (($params['data_type'] === 'items_list') && isset($params['items_data_type']) && $params['items_data_type'] === 'id_object')) && $params['object'] && $params['create_form']) {
            $html .= self::renderCreateObjectButton($this->object, $this->identifier, $params['object'], $this->fields_prefix . $params['input_name'], $params['create_form'], $params['create_form_values'], $params['create_form_label']);
        } else {
            $html .= '<pre>';
            $html .= print_r($params, 1);
            $html .= '</pre>';
        }

        if ($this->object->config->isDefined($row_path . '/input')) {
            $field_params = array();
            if (in_array($params['data_type'], array('qty', 'int', 'float', 'money', 'percent'))) {
                $field_params = BimpComponent::fetchParams($row_path, BC_Field::$type_params_def['number']);
            } elseif (array_key_exists($params['data_type'], BC_Field::$type_params_def)) {
                $field_params = BimpComponent::fetchParams($row_path, BC_Field::$type_params_def[$params['data_type']]);
            }
            $field_params['required'] = (int) $params['required'];
            $input = new BC_Input($this->object, $params['data_type'], $params['input_name'], $row_path . '/input', $params['value'], $field_params);
            $input->display_card_mode = 'visible';
            $input->setNamePrefix($this->fields_prefix);
            $input->extraClasses[] = 'customField';
            $input->extraData['form_row'] = $row;
            $html .= $input->renderHtml();
            unset($input);
        } elseif ($this->object->config->isDefined($this->config_path . '/rows/' . $row . '/content')) {
            $content = str_replace('name="' . $params['input_name'] . '"', 'name="' . $this->fields_prefix . $params['input_name'] . '"', $this->object->getConf($row_path . '/content', '', true));
            if (!(int) $params['no_container']) {
                $extra_data = array(
                    'form_row'  => $row,
                    'data_type' => $params['data_type']
                );
                if ($params['data_type'] === 'id_object') {
                    $card = $this->object->getConf($row_path . '/card', '');
                    if ($card) {
                        $object = $this->object->config->getObject($row_path . '/object');
                        if (is_a($object, 'BimpObject')) {
                            $extra_data['card'] = $card;
                            $extra_data['object_module'] = $object->module;
                            $extra_data['object_name'] = $object->object_name;
                            $extra_data['display_card_mode'] = $this->object->getConf($row_path . '/display_card_mode', 'visible');
                        }
                    }
                }
                if ($params['multiple']) {
                    $extra_data['values_field'] = $params['input_name'];
                }
                $html .= BimpInput::renderInputContainer($params['input_name'], $params['value'], $content, $this->fields_prefix, $params['required'], $params['multiple'], 'customField', $extra_data);
            } else {
                $html .= $content;
            }
        } else {
            $html .= BimpRender::renderAlerts('Erreur de configuration: aucun contenu défini pour ce champ');
        }

        return $html;
    }

    public static function renderCreateObjectButton(BimpObject $parent, $parent_form_id, $object_name, $result_input_name, $form_name, $form_values = null, $btn_label = 'Créer', $reload_input = true, $object = null, $successcallBack = '')
    {
        if (!$form_name) {
            return '';
        }

        if (is_null($object) && $object_name) {
            $object = $parent->config->getObject('', $object_name);
        }

        if (is_null($object) || !is_a($object, 'BimpObject')) {
            return '';
        }

        $label = $btn_label . ' ' . $object->getLabel('a');
        $title = 'Ajout ' . addslashes($object->getLabel('of_a'));

        if ($parent->isLoaded() && $object->getParentObjectName() === $parent->object_name) {
            $id_parent = $parent->id;
        } else {
            $id_parent = 0;
        }

        $html = '';

        $html .= '<div style="text-align: right">';

        $onclick = '\'' . $title . '\', \'' . $result_input_name . '\', \'' . $parent_form_id . '\'';
        $onclick .= ', \'' . $object->module . '\', \'' . $object->object_name . '\'';
        $onclick .= ', \'' . $form_name . '\', ' . $id_parent;
        $onclick .= ', ' . ($reload_input ? 'true' : 'false');
        $onclick .= ', $(this)';
        if (!is_null($form_values) && is_array($form_values)) {
            $onclick .= ', \'' . htmlentities(json_encode($form_values)) . '\'';
        }
        $html .= BimpRender::renderButton(array(
                    'icon_before' => 'plus-circle',
                    'label'       => $label,
                    'classes'     => array('btn', 'btn-light-default'),
                    'attr'        => array(
                        'onclick' => 'loadObjectFormFromForm(' . $onclick . ')'
                    )
        ));

        $html .= '</div>';

        return $html;
    }
}
