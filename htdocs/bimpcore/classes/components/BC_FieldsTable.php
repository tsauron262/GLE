<?php

class BC_FieldsTable extends BC_Panel
{

    public $component_name = 'Tableau de données';
    public static $type = 'fields_table';
    protected $new_values = array();
    public $row_params = array(
        'show'        => array('data_type' => 'bool', 'default' => 1),
        'field'       => array('default' => ''),
        'association' => array('default' => ''),
        'label'       => array('default' => ''),
        'value'       => array('data_type' => 'any'),
        'display'     => array('default' => 'default'),
        'edit'        => array('data_type' => 'bool', 'default' => 0),
        'history'     => array('data_type' => 'bool', 'default' => 0)
    );

    public function __construct(BimpObject $object, $path, $content_only = false, $level = 1, $title = null, $icon = null)
    {
        $this->params_def['rows'] = array('type' => 'keys');

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $name = $object->getConf($path . '/name', 'default');

        if (!$object->config->isDefined($path . '/rows')) {
            if (!$name || $name === 'default') {
                if ($object->config->isDefined('fields_table')) {
                    $path = 'fields_table';
                } elseif ($object->config->isDefined('fields_tables/default')) {
                    $path = 'fields_tables';
                    $name = 'default';
                }
            } else {
                $path = 'fields_tables';
            }
        }

        parent::__construct($object, $name, $path, $content_only, $level, $title, $icon);

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
            $row_params = $this->fetchParams($this->config_path . '/rows/' . $row, $this->row_params);

            if (!(int) $row_params['show']) {
                continue;
            }

            $label = $row_params['label'];
            $content = '';
            if ($row_params['field']) {
                $field_errors = array();
                $field_name = $row_params['field'];
                $field_name = $row_params['field'];
                $field_object = BC_Field::getFieldObject($this->object, $field_name, $field_errors);

                if (count($field_errors)) {
                    $content .= BimpRender::renderAlerts($field_errors);
                } else {
                    if (!$field_object->isFieldActivated($field_name)) {
                        continue;
                    }

                    if ($field_object->isDolObject()) {
                        if (!$field_object->dol_field_exists($field_name)) {
                            continue;
                        }
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
                        $field->new_value = $this->new_values[$field_name];
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

            if (!$label) {
                $label = BimpTools::ucfirst($row);
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
