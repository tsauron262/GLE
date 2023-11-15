<?php

class BC_FieldsTable extends BC_Panel
{

    public $component_name = 'Tableau de données';
    public static $type = 'fields_table';
    protected $new_values = array();
    public $row_params = array(
        'show'          => array('data_type' => 'bool', 'default' => 1),
        'field'         => array('default' => ''),
        'association'   => array('default' => ''),
        'label'         => array('default' => ''),
        'value'         => array('data_type' => 'any'),
        'display'       => array('default' => 'default'),
        'edit'          => array('data_type' => 'bool', 'default' => 0),
        'history'       => array('data_type' => 'bool', 'default' => 0),
        'extra_content' => array()
    );

    public function __construct(BimpObject $object, $name, $content_only = false, $title = null, $icon = null)
    {
        $this->params_def['rows'] = array('type' => 'keys', 'default' => array());
        $this->params_def['all_fields'] = array('data_type' => 'bool', 'default' => 0);

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        parent::__construct($object, $name, '', $content_only, 1, $title, $icon);

        if (empty($this->params['rows']) && $this->params['all_fields']) {
            $fields = $this->object->getFieldsList(true, true, false);

            $this->params['rows'] = array();

            foreach ($fields as $field_name) {
                $row = array(
                    'field' => $field_name
                );
                foreach ($this->row_params as $param_name => $defs) {
                    if ($param_name == 'field') {
                        continue;
                    }

                    $row[$param_name] = BimpTools::getArrayValueFromPath($defs, 'default', null);
                }
                $this->params['rows'][] = $row;
            }
        }

        if (!count($this->errors)) {
            if (!$this->object->can("view")) {
                $this->errors[] = 'Vous n\'avez pas la permission de voir ' . $this->object->getLabel('this');
            }
        }

        $current_bc = $prev_bc;
    }

    public function setNewValues($new_values)
    {
        foreach ($new_values as $field => $value) {
            $this->new_values[$field] = $value;
        }
    }

    public function renderHtmlContent()
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = '';

        $html .= '<table class="objectFieldsTable ' . $this->object->object_name . '_fieldsTable">';
        $html .= '<tbody>';

        $has_content = false;

        foreach ($this->params['rows'] as $row) {
            $row_params = array();

            if (isset($row['field'])) {
                $row_params = $row;
            } else {
                $row_params = $this->fetchParams($this->config_path . '/rows/' . $row, $this->row_params);
            }

            if (empty($row_params)) {
                continue;
            }

            if (!(int) $row_params['show']) {
                continue;
            }

            $label = $row_params['label'];
            $content = '';

            if ($row_params['field']) {
                $field_errors = array();
                $field_name = $row_params['field'];
                $field_object = BC_Field::getFieldObject($this->object, $field_name, $field_errors);

                if (count($field_errors)) {
                    $content .= BimpRender::renderAlerts($field_errors);
                } else {
                    if (!$field_object->field_exists($field_name)) {
                        continue;
                    }

                    $isBaseObjectField = ($field_name == $row_params['field']);
                    $edit = 0;

                    if ($isBaseObjectField && (int) $row_params['edit']) {
                        $edit = 1;
                    }

                    $field = new BC_Field($field_object, $field_name, $edit);
                    $field->display_name = $row_params['display'];

                    if (!$field->params['show']) {
                        continue;
                    }

                    if (!$field->checkDisplayIf()) {
                        continue;
                    }

                    if (isset($this->new_values[$row_params['field']])) {
                        $field->new_value = $this->new_values[$row_params['field']];
                    }

                    if (!$label) {
                        $label = $field->params['label'];
                    }

                    $content = $field->renderHtml();

                    if ($edit && $field->isEditable()) {
                        $content .= $field->displayCreateObjectButton(true, true);
                    }

                    unset($field);
                }
            } elseif ($row_params['association']) {
                $asso = new BimpAssociation($this->object, $row_params['association']);
                if (count($asso->errors)) {
                    $content .= BimpRender::renderAlerts($asso->errors);
                } else {
                    $config_path = 'associations/' . $row_params['association'] . '/';
                    if (!$label) {
                        $label = $this->object->getConf($config_path . 'label', '');
                    }
                    if ((int) $row_params['edit']) {
                        if ($this->object->config->isDefined($config_path . 'input')) {
                            $content = $asso->renderAddAssociateInput($row_params['display'], true);
                        } elseif ($this->object->config->isDefined($config_path . 'list')) {
                            $content .= '<div class="inputContainer ' . $this->object->object_name . '_inputContainer"';
                            $content .= ' data-field_name="' . $row_params['association'] . '"';
                            $content .= ' data-multiple="1"';
                            $content .= '>';

                            $content .= $asso->renderAssociatesCheckList();

                            $content .= '</div>';
                        }
                    } else {
                        $items = $asso->getAssociatesList();
                        $content .= '<div class="multiple_values_items_list">';
                        if (count($items)) {
                            foreach ($items as $id_item) {
                                $content .= '<div class="multiple_values_item">';
                                $content .= $this->object->displayAssociate($row_params['association'], $row_params['display'], $id_item);
                                $content .= '</div>';
                            }
                        } else {
                            if (is_a($asso->associate, 'BimpObject')) {
                                $msg = 'Aucun' . ($asso->associate->isLabelFemale() ? 'e' : '');
                                $msg .= ' ' . $asso->associate->getLabel('') . ' associé' . ($asso->associate->isLabelFemale() ? 'e' : '');
                            } else {
                                $msg = 'Aucun élément associé';
                            }
                            $content .= BimpRender::renderAlerts($msg, 'warning');
                        }
                        $content .= '</div>';
                    }
                }
            } elseif (!is_null($row_params['value'])) {
                $content = $row_params['value'];
            } else {
                continue;
            }

            if (isset($row_params['extra_content'])) {
                $content .= $row_params['extra_content'];
            }

            if (!$label) {
                $label = BimpTools::ucfirst($row);
            }

            if ($content === '') {
                continue;
            }

            $has_content = true;

            $html .= '<tr>';
            $html .= '<th>' . $label . '</th>';
            $html .= '<td>' . $content . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        if (!$has_content) {
            $this->params['show'] = 0;
            return '';
        }

        $current_bc = $prev_bc;

        return $html;
    }

    public function renderHtmlFooter()
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = '<div class="fieldsTableFooter" style="text-align: right">';

        $html .= BimpRender::renderButton(array(
                    'classes'     => array('btn', 'btn-default', 'cancelmodificationsButton'),
                    'label'       => 'Annuler les modifications',
                    'attr'        => array(
                        'style'   => 'display: none',
                        'type'    => 'button',
                        'onclick' => 'cancelFieldsTableModifications(\'' . $this->identifier . '\', $(this))'
                    ),
                    'icon_before' => 'times'
                        ), 'button');

        $html .= BimpRender::renderButton(array(
                    'classes'     => array('btn', 'btn-primary', 'saveButton'),
                    'label'       => 'Enregistrer',
                    'attr'        => array(
                        'style'   => 'display: none',
                        'type'    => 'button',
                        'onclick' => 'saveObjectfromFieldsTable(\'' . $this->identifier . '\', $(this))'
                    ),
                    'icon_before' => 'fas_save'
                        ), 'button');

        $html .= parent::renderFooterExtraBtn();

        $html .= '</div>';

        $current_bc = $prev_bc;
        return $html;
    }
}
