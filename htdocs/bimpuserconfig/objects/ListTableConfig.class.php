<?php

require_once DOL_DOCUMENT_ROOT . '/bimpuserconfig/objects/ListConfig.class.php';

class ListTableConfig extends ListConfig
{

    public static $config_object_name = 'ListTableConfig';
    public static $config_table = 'buc_list_table_config';
    public static $component_type = 'list_table';
    public static $has_search = true;
    public static $has_filters = true;
    public static $has_total = true;
    public static $has_pagination = true;
    public static $has_cols = true;

    // Getters booléens: 

    public function isActionAllowed($action, &$errors = array())
    {
        if (in_array($action, array('addCol', 'removeCol', 'removeAllCols', 'useDefaultCols', 'saveColOptions', 'saveColsPositions'))) {
            if (!$this->isLoaded($errors)) {
                return 0;
            }
        }

        return parent::isActionAllowed($action, $errors);
    }

    // Getters params: 

    public function getListExtraButtons()
    {
        $buttons = parent::getListExtraButtons();

        if ($this->can('edit') && $this->isEditable()) {
            $buttons[] = array(
                'label'   => 'Colonnes',
                'icon'    => 'fas_columns',
                'onclick' => $this->getJsLoadModalColsConfig()
            );
        }

        return $buttons;
    }

    // Getters JS: 

    public function getReloadListJsCallback()
    {
        $obj = $this->getObjInstance();

        if (is_a($obj, 'BimpObject')) {
            if ((string) $this->getData('component_name')) {
                return '$(\'.' . $obj->object_name . '_list_table_' . $this->getData('component_name') . '\').each(function() {reloadObjectList($(this).attr(\'id\'), null, 1);})';
            } else {
                return '$(\'.' . $obj->object_name . '_list_table\').each(function() {reloadObjectList($(this).attr(\'id\'), null, 1);})';
            }
        }

        return '';
    }

    public function getJsLoadModalColsConfig()
    {
        $title = 'Configuration des colonnes';
        $success_callback = 'function(result, bimpAjax) {';
        $success_callback .= 'ListTableConfig.setColsConfigEvents(bimpAjax.$resultContainer);';
        $success_callback .= 'ListTableConfig.setColsListItemsEvents(bimpAjax.$resultContainer);';
        $success_callback .= '}';

        return $this->getJsLoadModalCustomContent('renderColsConfig', $title, array(), 'medium', $success_callback);
    }

    // Rendus Html: 

    public function renderColsConfig()
    {
        $html = '';

        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de la configuration absent');
        }

        $obj = $this->getObjInstance();

        if (is_a($obj, 'BimpObject')) {
            $html .= '<div class="list_cols_config_container"';
            $html .= ' data-list_name="' . $this->getData('component_name') . '"';
            $html .= ' data-id_config="' . $this->id . '"';
            $html .= '>';

            // Formulaire d'ajout de colonne: 
            $title = BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajout d\'une colonne';
            $content = '<div class="list_add_col_container">';
            $content .= $this->renderAddColForm();
            $content .= '</div>';

            $html .= BimpRender::renderPanel($title, $content, '', array(
                        'type' => 'secondary'
            ));

            // Liste des colonnes: 
            $title = BimpRender::renderIcon('fas_columns', 'iconLeft') . 'Colonnes';
            $content = '<div class="list_cols_list_container">';
            $content .= $this->renderColsList();
            $content .= '</div>';

            $html .= BimpRender::renderPanel($title, $content, '', array(
                        'type' => 'secondary'
            ));

            $html .= '</div>';
        } else {
            $html .= BimpRender::renderAlerts('Objet associé invalide');
        }

        return $html;
    }

    public function renderAddColForm()
    {
        $html = '';

        $obj = $this->getObjInstance();

        if (is_a($obj, 'BimpObject')) {
            $html .= $obj->renderListColsTypeSelect();
        }

        return $html;
    }

    public function renderColsList()
    {
        $html = '';

        $cols = $this->getData('cols');

        $html .= '<div class="buttonsContainer align-right">';
        $html .= '<span class="btn btn-default loadDefaultColsButton" onclick="ListTableConfig.useDefaultCols($(this))">';
        $html .= BimpRender::renderIcon('fas_undo', 'iconLeft') . 'Colonnes par défaut';
        $html .= '</span>';
        $html .= '<span class="btn btn-default removeAllColsButton" onclick="ListTableConfig.removeAllCols($(this))"' . (count($cols) > 1 ? '' : ' style="display: none"') . '>';
        $html .= BimpRender::renderIcon('fas_times-circle', 'iconLeft') . 'Retirer toutes les colonnes';
        $html .= '</span>';
        $html .= '</div>';

        $html .= '<div class="no_cols"' . (!empty($cols) ? ' style="display: none"' : '') . '>';
        $html .= BimpRender::renderAlerts('Aucune colonne ajoutée à cette configuration', 'warning');
        $html .= '</div>';

        $html .= '<div class="inputMultipleValues"' . (empty($cols) ? ' style="display: none"' : '') . '>';
        $html .= '<table class="cols_list">';
        $html .= '<tbody class="multipleValuesList">';

        foreach ($cols as $col_name => $col_params) {
            $html .= $this->renderColsListItem($col_name);
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public function renderColsListItem($col_name)
    {
        $html = '';

        $errors = array();

        $html .= '<tr class="itemRow listCols_itemRow" data-col_name="' . $col_name . '">';
        $html .= '<td class="positionHandle"><span></span></td>';
        $html .= '<td class="item_label">';

        if ($this->isLoaded($errors)) {
            $obj = $this->getObjInstance();

            if (is_a($obj, 'BimpObject')) {
                $cols = $this->getData('cols');
                $field_prefixe = '';

                if (isset($cols[$col_name])) {
                    $title = BimpTools::ucfirst($obj->getLabel()) . ' > ';
                    $children = explode(':', $col_name);

                    while (count($children) > 1) {
                        $child_name = array_shift($children);

                        if ($child_name) {
                            $field_prefixe .= $child_name . ':';
                            $child = $obj->getChildObject($child_name);

                            if (is_a($child, 'BimpObject')) {
                                $child_label = '';

                                if ($child_name === 'parent') {
                                    $child_id_prop = $obj->getConf('parent_id_property', '');
                                } else {
                                    $child_id_prop = $obj->getConf('objects/' . $child_name . '/instance/id_object/field_value', '');
                                }

                                if ($child_id_prop) {
                                    $child_label = $obj->getConf('fields/' . $child_id_prop . '/label', '');
                                }

                                if (!$child_label) {
                                    $child_label = $child_name;
                                }

                                $title .= BimpTools::ucfirst($child_label) . ' > ';
                                $obj = $child;
                            } else {
                                $errors[] = 'L\'objet lié "' . $child_name . '" n\'existe pas pour les ' . $obj->getLabel('name_plur');
                                break;
                            }
                        }
                    }

                    if (!count($errors)) {
                        $field_name = array_shift($children);

                        if (!is_a($obj, 'BimpObject')) {
                            $errors[] = 'Objet contenant le champ invalide';
                        } elseif (!$field_name) {
                            $errors[] = 'Nom du champ absent';
                        } else {
                            if ($obj->config->isDefined('fields/' . $field_name)) {
                                $title .= $obj->getConf('fields/' . $field_name . '/label', $field_name);
                            } elseif ($obj->config->isDefined('lists_cols/' . $field_name)) {
                                $title .= $obj->getConf('lists_cols/' . $field_name . '/label', $field_name);
                            } else {
                                $title .= $field_name;
                            }

                            $html .= $title;

//                            $html .= '<div style="text-align: right">';
                            $html .= '<span class="openCloseButton open-content" style="float: right">';
                            $html .= BimpRender::renderIcon('fas_cog', 'iconLeft') . 'Options';
                            $html .= '</span>';
//                            $html .= '</div>';

                            $html .= '<div class="item_options openCloseContent" style="display: none">';
                            $html .= $this->renderFieldConfigOptions($obj->module, $obj->object_name, $this->getData('component_name'), $field_name, $field_prefixe, $cols[$col_name], false);
                            $html .= '</div>';
                        }
                    }
                } else {
                    $errors[] = 'Cette colonne n\'est pas enregistrée pour cette config';
                }
            } else {
                $errors[] = 'Objet invalide';
            }
        }

        if (count($errors)) {
            $html .= 'Colonne "' . $col_name . '"';
            $html .= BimpRender::renderAlerts($errors);
        }

        $html .= '</td>';
        $html .= '<td class="removeButton">';
        $html .= '<button type="button" class="btn btn-light-danger iconBtn" onclick="ListTableConfig.removeCol($(this), \'' . $col_name . '\')">';
        $html .= '<i class="fas fa5-trash-alt"></i>';
        $html .= '</button>';
        $html .= '</td>';

        $html .= '</tr>';

        return $html;
    }

    public function renderFieldConfigOptions($module, $object_name, $list_name, $field_name, $field_prefixe = '', $values = array(), $is_new = true)
    {
        $html = '';

        $object = BimpObject::getInstance($module, $object_name);

        if (!is_a($object, 'BimpObject')) {
            $html = BimpRender::renderAlerts('L\'objet "' . $object_name . '" n\'existe pas dans le module "' . $module . '"');
        } else {
            $display_types = array();
            $display_type_options = array();
            $csv_options = array();
            $display_name = BimpTools::getArrayValueFromPath($values, 'display_name');
            $default_csv_option = '';
            $default_label = '';
            $bc_field = null;
            $editable = false;
            $canEdit = false;

            // On ne fourni la liste name que s'il s'agit de l'objet principal (et pas d'un enfant)
            $col_params = BC_ListTable::getObjectConfigColParams($object, $field_name, (!$field_prefixe ? $list_name : ''));

            if ($object->field_exists($field_name)) {
                $bc_field = new BC_Field($object, $field_name);
                if (!empty($bc_field->errors)) {
                    return BimpRender::renderAlerts(BimpTools::getMsgFromArray($bc_field->errors, 'Champ "' . $field_name . '" invalide'));
                }

                if (!$display_name) {
                    if (isset($col_params['display']) && (string) $col_params['display']) {
                        $display_name = $col_params['display'];
                    } else {
                        $display_name = BC_Display::getObjectFieldDefaultDisplayType($object, $field_name, $bc_field);
                    }
                }

                $default_label = $bc_field->params['label'];
                $display_types = $bc_field->getDisplayTypesArray();
                $display_type_options = $bc_field->getDisplayOptionsInputs($display_name, BimpTools::getArrayValueFromPath($values, 'display_options', array()));

                $csv_options = $bc_field->getNoHtmlOptions($default_csv_option);
                $editable = (int) $bc_field->getParam('user_edit', 1);
                $canEdit = (int) $object->canEditField($field_name);
            } elseif ($object->config->isDefined('lists_cols/' . $field_name)) {
                $default_label = $object->getConf('lists_cols/' . $field_name . '/label', $field_name, true);
            } else {
                return BimpRender::renderAlerts('Le champ "' . $field_name . '" n\'existe pas dans l\'objet "' . BimpTools::ucfirst($object->getLabel()) . '"');
            }

            if (!$display_name && !is_null($bc_field)) {
                $display_name = BC_Display::getDefaultTypeByDataType($object, $field_name, $bc_field);
            }

            $html .= '<div class="list_col_options_form"';
            $html .= BimpRender::renderTagData(array(
                        'module'      => $module,
                        'object_name' => $object_name,
                        'field_name'  => $field_name
            ));
            $html .= '>';
            $html .= '<h4>Options de la colonne</h4>';
            $html .= '<form>';

            // Nom du champ: 
            $html .= '<div class="row fieldRow">';
            $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">Nom du champ</div>';
            $html .= '<div class="fieldRowInput field col-xs-12 col-sm-6 col-md-9" data-input_name="">';
            $html .= $bc_field->params['label'];
            $html .= '</div>';
            $html .= '</div>';

            // En-tête: 
            $col_label = BimpTools::getArrayValueFromPath($values, 'label', BimpTools::getArrayValueFromPath($col_params, 'label', $default_label));
            $html .= '<div class="row fieldRow">';
            $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">En-tête de la colonne</div>';
            $html .= '<div class="fieldRowInput field col-xs-12 col-sm-6 col-md-9 colOptionInput" data-input_name="label">';
            $html .= BimpInput::renderInput('text', 'label', $col_label);
            $html .= '</div>';
            $html .= '</div>';

            // Type de donnée: 
            $type = '';
            if (!is_null($bc_field)) {
                $type = $bc_field->displayType();
            } else {
                $type = 'Affichage personnalisé';
            }

            $html .= '<div class="row fieldRow">';
            $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">Type de donnée</div>';
            $html .= '<div class="fieldRowInput field col-xs-12 col-sm-6 col-md-9">';
            $html .= $type;
            $html .= '</div>';
            $html .= '</div>';

            // Largeur min: 
            $col_min_width = (int) BimpTools::getArrayValueFromPath($values, 'min_width', BimpTools::getArrayValueFromPath($col_params, 'min_width', (!is_null($bc_field) ? $bc_field->getDefaultDisplayWidth() : 80)));
            $html .= '<div class="row fieldRow">';
            $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">Largeur minimum</div>';
            $html .= '<div class="fieldRowInput field col-xs-12 col-sm-6 col-md-9 colOptionInput" data-input_name="min_width">';
            $html .= BimpInput::renderInput('qty', 'min_width', $col_min_width, array(
                        'data' => array(
                            'data_type' => 'number',
                            'min'       => 0,
                            'max'       => 'none',
                            'decimals'  => 0
                        )
            ));
            $html .= '</div>';
            $html .= '</div>';


            // Alignement: 
            $col_align = BimpTools::getArrayValueFromPath($values, 'align', BimpTools::getArrayValueFromPath($col_params, 'align', 'left'));
            $html .= '<div class="row fieldRow">';
            $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">Alignement</div>';
            $html .= '<div class="fieldRowInput field col-xs-12 col-sm-6 col-md-9 colOptionInput" data-input_name="align">';
            $html .= BimpInput::renderInput('select', 'align', $col_align, array(
                        'options' => array(
                            'left'   => 'Gauche',
                            'center' => 'Centre',
                            'right'  => 'Droite'
                        )
            ));
            $html .= '</div>';
            $html .= '</div>';

            // Editable: 
            $col_edit = (int) BimpTools::getArrayValueFromPath($values, 'edit', BimpTools::getArrayValueFromPath($col_params, 'edit', 0));
            $html .= '<div class="row fieldRow">';
            $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">Editable</div>';
            $html .= '<div class="fieldRowInput field col-xs-12 col-sm-6 col-md-9 colOptionInput" data-input_name="edit">';
            if ($editable && $canEdit) {
                $html .= BimpInput::renderInput('toggle', 'edit', $col_edit);
            } else {
                $html .= '<input type="hidden" name="edit" value="0"/>';
                if (!$editable) {
                    $html .= '<span class="warning">Edition non permise</span>';
                } elseif (!$canEdit) {
                    $html .= '<span class="danger">Vous n\'avez pas la permission</span>';
                }
            }
            $html .= '</div>';
            $html .= '</div>';

            // Option CSV: 
            if (!is_null($bc_field)) {
                if (!empty($csv_options)) {
                    $col_csv_option = BimpTools::getArrayValueFromPath($values, 'csv_option', $default_csv_option);
                    $html .= '<div class="row fieldRow">';
                    $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">Valeur CSV</div>';
                    $html .= '<div class="fieldRowInput field col-xs-12 col-sm-6 col-md-9 colOptionInput" data-input_name="csv_option">';
                    $html .= BimpInput::renderInput('select', 'csv_option', $col_csv_option, array(
                                'options' => $csv_options
                    ));
                    $html .= '</div>';
                    $html .= '</div>';
                }
            }

            // Lien objet: 
            if ((string) $object->params['controller'] && !is_null($bc_field) && $bc_field->params['type'] === 'string' && !$bc_field->hasValuesArray()) {
                $object_link = (int) BimpTools::getArrayValueFromPath($values, 'object_link', BimpTools::getArrayValueFromPath($col_params, 'object_link', (int) ($field_name === $object->getRefProperty())));
                $html .= '<div class="row fieldRow">';
                $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">Lien vers la fiche ' . $object->getLabel('of_the') . '</div>';
                $html .= '<div class="fieldRowInput field col-xs-12 col-sm-6 col-md-9 colOptionInput" data-input_name="object_link">';
                $html .= BimpInput::renderInput('toggle', 'object_link', $object_link);
                $html .= '</div>';
                $html .= '</div>';
            }

            // Type d'affichage: 
            if (!empty($display_types)) {
                $html .= '<div class="row fieldRow">';
                $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">Type d\'Affichage</div>';
                $html .= '<div class="fieldRowInput field col-xs-12 col-sm-6 col-md-9 colOptionInput" data-input_name="display_name">';
                if (count($display_types) > 1) {
                    $html .= BimpInput::renderInput('select', 'display_name', $display_name, array(
                                'options' => $display_types
                    ));
                } else {
                    foreach ($display_types as $display_type => $display_type_label) {
                        $html .= '<input type="hidden" value="' . $display_type . '" name="display_name"/>';
                        $html .= $display_type_label;
                    }
                }
                $html .= '</div>';
                $html .= '</div>';
            }

            // Options d'affichage:
            if (!empty($display_types || !empty($display_type_options))) {
                $display_values = array();
                if (isset($values['display_name']) && $values['display_name'] === $display_name) {
                    if (isset($values['display_options']) && is_array($values['display_options'])) {
                        $display_values = $values['display_options'];
                    }
                }

                $html .= '<div class="display_type_options" style="margin-top: 15px;' . (empty($display_type_options) ? ' display: none;' : '') . '">';
                if (is_array($display_type_options) && !empty($display_type_options)) {
                    $html .= '<h4>Options d\'affichage du champ</h4>';

                    foreach ($display_type_options as $option) {
                        $html .= '<div class="row fieldRow">';
                        $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">' . $option['label'] . '</div>';
                        $html .= '<div class="fieldRowInput field col-xs-12 col-sm-6 col-md-9 fieldDisplayOptionInput" data-input_name="' . $option['input_name'] . '">' . $option['input'] . '</div>';
                        $html .= '</div>';
                    }
                }
                $html .= '</div>';
            }

            $html .= '</form>';

            $html .= '<div class="buttonsContainer align-center">';
            if ($is_new) {
                $html .= '<span class="btn btn-primary" onclick="ListTableConfig.addFieldCol($(this), \'' . $field_prefixe . $field_name . '\')">';
                $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . ' Ajouter la colonne';
                $html .= '</span>';
            } else {
                $html .= '<span class="btn btn-primary" onclick="ListTableConfig.saveColOptions($(this), ' . (int) $this->id . ', \'' . $field_prefixe . $field_name . '\')">';
                $html .= BimpRender::renderIcon('fas_save', 'iconLeft') . ' Enregistrer les modifications';
                $html .= '</span>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }

        return $html;
    }

    public function renderFieldDisplayOptions($module, $object_name, $field_name, $display_name, $values = array())
    {
        $html = '';

        $object = BimpObject::getInstance($module, $object_name);
        if (is_a($object, 'BimpObject')) {
            if ($object->field_exists($field_name)) {
                $bc_field = new BC_Field($object, $field_name);

                if ($bc_field->isOk()) {
                    $display_options = $bc_field->getDisplayOptionsInputs($display_name, $values);

                    if (is_array($display_options) && !empty($display_options)) {
                        $html .= '<h4>Options d\'affichage du champ</h4>';

                        foreach ($display_options as $option) {
                            $html .= '<div class="row fieldRow">';
                            $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">' . $option['label'] . '</div>';
                            $html .= '<div class="fieldRowInput field col-xs-12 col-sm-6 col-md-9 fieldDisplayOptionInput" data-input_name="' . $option['input_name'] . '">' . $option['input'] . '</div>';
                            $html .= '</div>';
                        }
                    }
                }
            }
        }

        return $html;
    }

    public function renderLinkedObjectOptions($module, $object_name, $child_name, $object_label = '', $fields_prefixe = '')
    {
        $html = '';

        if (!$object_label) {
            $object_label = 'Objet lié "' . $child_name . '"';
        }

        $object = BimpObject::getInstance($module, $object_name);

        if (!is_a($object, 'BimpObject')) {
            $html .= BimpRender::renderAlerts('L\'objet "' . $object_name . '" n\'existe pas dans le module "' . $module . '"');
        } else {
            $child = $object->getChildObject($child_name);

            if (!is_a($child, 'BimpObject')) {
                $html .= BimpRender::renderAlerts('L\'objet lié "' . $object_label . '" n\'existe pas pour les ' . $object->getLabel('name_plur'));
            } else {
                $html .= $child->renderListColsTypeSelect(array(
                    'object_label'   => $object_label,
                    'child_name'     => $child_name,
                    'fields_prefixe' => $fields_prefixe
                ));
            }
        }

        return $html;
    }

    // Traitements:

    public function saveDefaultCols()
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $obj = $this->getObjInstance();
            $list_name = $this->getData('component_name');

            $list_cols = BC_ListTable::getDefaultColsStatic($obj, $list_name, $errors);

            if (!count($errors)) {
                if (empty($list_cols)) {
                    $errors[] = 'Aucune colonne par défaut définie';
                } else {
                    $cols = array();

                    foreach ($list_cols as $col_name => $list_col_params) {
                        $default_csv_option = '';
                        $default_display_name = '';
                        $default_label = '';

                        $field_name = '';
                        $field_errors = array();
                        $field_object = BC_ListTable::getColFieldObject($obj, $col_name, $field_name, $field_errors);

                        if (!empty($field_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($field_errors, 'Colonne "' . $col_name . '"');
                            continue;
                        }
                        if (is_a($field_object, 'BimpObject')) {
                            if ($field_name && $field_object->field_exists($field_name)) {
                                $bc_field = new BC_Field($field_object, $field_name);
                                if (!empty($bc_field->errors)) {
                                    $errors[] = BimpTools::getMsgFromArray($bc_field->errors, 'Champ "' . $field_name . '" - Colonne "' . $col_name . '"');
                                    continue;
                                }

                                $default_display_name = BC_Display::getObjectFieldDefaultDisplayType($field_object, $field_name, $bc_field);
                                $default_label = $bc_field->params['label'];
                                $bc_field->getNoHtmlOptions($default_csv_option);
                            } elseif ($field_object->config->isDefined('lists_cols/' . $field_name)) {
                                $default_label = $field_object->getConf('lists_cols/' . $field_name . '/label', $field_name, true);
                            }
                        }

                        $col_params = array(
                            'label'           => BimpTools::getArrayValueFromPath($list_col_params, 'label', $default_label),
                            'min_width'       => BimpTools::getArrayValueFromPath($list_col_params, 'min_width', 60),
                            'align'           => BimpTools::getArrayValueFromPath($list_col_params, 'align', 'left'),
                            'edit'            => BimpTools::getArrayValueFromPath($list_col_params, 'edit', 0),
                            'display_type'    => BimpTools::getArrayValueFromPath($list_col_params, 'display', ''),
                            'csv_option'      => $default_csv_option,
                            'display_options' => BimpTools::getArrayValueFromPath($list_col_params, 'display_options', array())
                        );

                        if (!$col_params['label']) {
                            $col_params['label'] = $default_label;
                        }

                        if (!$col_params['display']) {
                            $col_params['display'] = $default_display_name;
                        }

                        $cols[$col_name] = $col_params;
                    }

                    if (!count($errors)) {
                        $errors = $this->updateField('cols', $cols);
                    }
                }
            }
        }

        return $errors;
    }

    public function checkConfig($echo = false, $correct = false)
    {
        if ($this->isLoaded()) {
            $base_obj = $this->getObjInstance();

            if ($echo) {
                echo '#' . $this->id . ': ';
            }

            if (!is_a($base_obj, 'BimpObject')) {
                if ($echo) {
                    echo '<span class="danger">Objet invalide</span>';
                }
            } else {
                $errors = array();

                // Check sort field: 
                $sort_field = $this->getData('sort_field');
                if (!$base_obj->field_exists($sort_field)) {
                    $errors[] = 'Champ invalide pour le trie: "' . $sort_field . '"';

                    if ($correct) {
                        $up_err = $this->updateField('sort_field', $base_obj->getPrimary());

                        if ($echo) {
                            if (!empty($up_err)) {
                                echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($up_err, 'Echec màj sort field'));
                            } else {
                                echo '<span class="success">Correction sort field OK</span><br/>';
                            }
                        }
                    }
                }

                // Check cols: 
                $cols = $this->getData('cols');
                $new_cols = array();

                $list_name = $this->getData('component_name');

                foreach ($cols as $col_name => $col_options) {
                    if (BC_ListTable::ObjectColExists($base_obj, $col_name, $list_name, $errors)) {
                        $new_cols[$col_name] = $cols[$col_name];
                    }
                }

                if ($correct && count($new_cols) != count($cols)) {
                    $up_err = $this->updateField('cols', $new_cols);

                    if ($echo) {
                        if (!empty($up_err)) {
                            echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($up_err, 'Echec màj colonne'));
                        } else {
                            echo '<span class="success">Correction colonnes OK</span><br/>';
                        }
                    }
                }

                if ($echo) {
                    if (!empty($errors)) {
                        echo BimpRender::renderAlerts($errors);
                    } else {
                        echo '<span class="success">OK</span>';
                    }
                }
            }

            if ($echo) {
                echo '<br/>';
            }
        }
    }

    // Actions: 

    public function actionAddCol($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $col_html = '';

        $col_name = BimpTools::getArrayValueFromPath($data, 'col_name', '', $errors, true, 'Nom de la colonne absent');
        $options = BimpTools::getArrayValueFromPath($data, 'options', array(), $errors, true, 'Options de la colonne absentes');

        if (!count($errors)) {
            $cols = $this->getData('cols');

            if (isset($cols[$col_name])) {
                $errors[] = 'Cette colonne a déjà été ajoutée';
            } else {
                if (!isset($options['label']) || !(string) $options['label']) {
                    $errors[] = 'Veuillez spécifier l\'en-tête de la colonne';
                }

                if (!count($errors)) {
                    $success = 'Colonne "' . $options['label'] . '" ajoutée';
                    $cols[$col_name] = $options;
                    $errors = $this->updateField('cols', $cols);

                    if (!count($errors)) {
                        $col_html = $this->renderColsListItem($col_name);
                    }
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings,
            'col_html' => $col_html
        );
    }

    public function actionSaveColOptions($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $col_name = BimpTools::getArrayValueFromPath($data, 'col_name', '', $errors, true, 'Nom de la colonne absent');
        $options = BimpTools::getArrayValueFromPath($data, 'options', array(), $errors, true, 'Options de la colonne absentes');

        if (!count($errors)) {
            $cols = $this->getData('cols');

            if (!isset($cols[$col_name])) {
                $errors[] = 'Cette colonne n\'est pas enregistrée pour cette configuration';
            } else {
                if (!isset($options['label']) || !(string) $options['label']) {
                    $errors[] = 'Veuillez spécifier l\'en-tête de la colonne';
                }

                if (!count($errors)) {
                    $success = 'Options de la colonne "' . $options['label'] . '" mises à jour';
                    $cols[$col_name] = $options;
                    $errors = $this->updateField('cols', $cols);
                }
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionSaveColsPositions($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Positions enregistrées';

        $cols = BimpTools::getArrayValueFromPath($data, 'cols', array());

        if (empty($cols)) {
            $errors[] = 'Liste des colonnes absente';
        } else {
            $curCols = $this->getData('cols');
            $newCols = array();

            foreach ($cols as $col_name) {
                if (isset($curCols[$col_name])) {
                    $newCols[$col_name] = $curCols[$col_name];
                }
            }

            $errors = $this->updateField('cols', $newCols);
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRemoveCol($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $col_name = BimpTools::getArrayValueFromPath($data, 'col_name', '');

        if (!$col_name) {
            $errors[] = 'Colonne à retirer absente';
        } else {
            $cols = $this->getData('cols');

            if (isset($cols[$col_name])) {
                $col_label = BimpTools::getArrayValueFromPath($cols[$col_name], 'label', $col_name);
                $success = 'Colonne "' . $col_label . '" retirée';
                unset($cols[$col_name]);

                $errors = $this->updateField('cols', $cols);
            } else {
                $errors[] = 'La colonne "' . $col_name . '" n\'est pas incluse dans cette configuration';
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionRemoveAllCols($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'Toutes les colonnes retirées';

        $errors = $this->updateField('cols', array());

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    public function actionUseDefaultCols($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $cols_html = '';

        $errors = $this->saveDefaultCols();

        if (!count($errors)) {
            $cols = $this->getData('cols');

            foreach ($cols as $col_name => $col_params) {
                $cols_html .= $this->renderColsListItem($col_name);
            }
        }

        return array(
            'errors'    => $errors,
            'warnings'  => $warnings,
            'cols_html' => $cols_html
        );
    }

    public function actionCreateUserDefault($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        global $user;

        if (BimpObject::objectLoaded($user)) {
            $obj_module = BimpTools::getArrayValueFromPath($data, 'obj_module', '', $errors, 1, 'Nom du module absent');
            $obj_name = BimpTools::getArrayValueFromPath($data, 'obj_name', '', $errors, 1, 'Type d\'objet absent');
            $comp_name = BimpTools::getArrayValueFromPath($data, 'component_name', '', $errors, 1, 'Type de liste absent');

            $obj_instance = BimpObject::getInstance($obj_module, $obj_name);
            if (!count($errors)) {
                $values = array(
                    'name'           => 'Configuration utilisateur par défaut',
                    'owner_type'     => UserConfig::OWNER_TYPE_USER,
                    'id_owner'       => (int) $user->id,
                    'id_user_create' => (int) $user->id,
                    'is_default'     => BimpTools::getArrayValueFromPath($data, 'is_default', 1),
                    'obj_module'     => $obj_module,
                    'obj_name'       => $obj_name,
                    'component_name' => $comp_name,
                    'nb_items'       => BimpTools::getArrayValueFromPath($data, 'nb_items', 10),
                    'sort_field'     => BimpTools::getArrayValueFromPath($data, 'sort_field', $obj_instance->getPrimary()),
                    'sort_way'       => BimpTools::getArrayValueFromPath($data, 'sort_way', 'desc'),
                    'sort_option'    => BimpTools::getArrayValueFromPath($data, 'sort_option', 1),
                    'total_row'      => BimpTools::getArrayValueFromPath($data, 'total_row', 0)
                );

                $config = BimpObject::createBimpObject($this->module, $this->object_name, $values, true, $errors, $warnings);

                if (!count($errors)) {
                    if (BimpObject::objectLoaded($config)) {
                        $cols_errors = $config->saveDefaultCols();

                        if (count($cols_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($cols_errors, 'Echec ajout des colonnes par défaut');
                        }

                        if ((int) BimpTools::getArrayValueFromPath($data, 'load_cols_config', 0)) {
                            $success_callback .= $config->getJsLoadModalColsConfig() . ';';
                        }
                        if (BimpTools::getArrayValueFromPath($data, 'list_identifier', '')) {
                            $success_callback .= 'reloadObjectList(\'' . $data['list_identifier'] . '\', null, true, ' . $config->id . ');';
                        }
                    } else {
                        $errors[] = 'Echec de la création de la configuration par défaut';
                    }
                }
            }
        } else {
            $errors[] = 'Aucun utilisateur connecté';
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success_callback' => $success_callback
        );
    }

    // Méthodes statiques: 

    public static function checkAll($echo = false, $correct = false)
    {
        $bdb = self::getBdb();
        $rows = $bdb->getRows('buc_list_table_config', '1', null, 'array', array('id'));

        foreach ($rows as $r) {
            $config = BimpCache::getBimpObjectInstance('bimpuserconfig', 'ListTableConfig', (int) $r['id']);

            if (BimpObject::objectLoaded($config)) {
                $config->checkConfig($echo, $correct);
            }
        }
    }
}
