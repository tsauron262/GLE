<?php

require_once DOL_DOCUMENT_ROOT . '/bimpuserconfig/objects/BCUserConfig.class.php';

class FiltersConfig extends BCUserConfig
{

    public static $config_object_name = 'FiltersConfig';
    public static $config_table = 'buc_filters_config';
    public static $component_type = 'filters_panel';
    public static $use_component_name = false;

    // Getters JS: 

    public function getCreateJsCallback()
    {
        return $this->getJsLoadFiltersConfig();
    }

    public function getUpdateJsCallback()
    {
        return $this->getJsLoadFiltersConfig();
    }

    public function getJsLoadFiltersConfig()
    {
        $js = '';

        if ($this->isLoaded()) {
            $obj = $this->getObjInstance();

            if (is_a($obj, 'BimpObject')) {
                $js .= '$(\'body\').find(\'.' . $obj->object_name . '_filters_panel\').each(function() {';
                $js .= 'loadFiltersConfig($(this).attr(\'id\'), ' . $this->id . ');';
                $js .= '});';
            }
        }

        return $js;
    }

    // Rendus HTML: 

    public function renderFiltersInput($with_default = true)
    {
        $html = '';

        $obj = $this->getObjInstance();

        if (!is_a($obj, 'BimpObject')) {
            return BimpRender::renderAlerts('Objet associé invalide');
        }

        $html .= '<div class="filters_config_container"';
        $html .= ' data-module="' . $obj->module . '"';
        $html .= ' data-object_name="' . $obj->object_name . '"';
        $html .= '>';

        // Formulaire d'ajout d'un filtre:
        $title = BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajout d\'un filtre';
        $content = '<div class="add_filter_container">';
        $content .= $obj->renderFiltersSelect();
        $content .= '</div>';

        $html .= BimpRender::renderPanel($title, $content, '', array(
                    'type' => 'secondary'
        ));

        // Liste des filtres:
        $title = BimpRender::renderIcon('fas_bars', 'iconLeft') . 'Liste des filtres';

        $content = '<div class="filters_list_container">';
        $content .= $this->renderFiltersList($with_default);
        $content .= '</div>';

        $html .= BimpRender::renderPanel($title, $content, '', array(
                    'type' => 'secondary'
        ));

        $html .= '</div>';

        return $html;
    }

    public function renderFilterOptions($module, $object_name, $filter_name, $filter_prefixe = '', $values = array(), $is_new = true)
    {
        $html = '';

        $object = BimpObject::getInstance($module, $object_name);

        if (!is_a($object, 'BimpObject')) {
            $html = BimpRender::renderAlerts('L\'objet "' . $object_name . '" n\'existe pas dans le module "' . $module . '"');
        } else {
            $default_label = $object->getConf('filters/' . $filter_name . '/label', '');

            if (!$default_label) {
                $default_label = $object->getConf('fields/' . $filter_name . '/label', $filter_name);
            }

            $html .= '<div class="filter_options_form"';
            $html .= BimpRender::renderTagData(array(
                        'module'      => $module,
                        'object_name' => $object_name,
                        'field_name'  => $filter_name
            ));
            $html .= '>';
            $html .= '<h4>Options du filtre</h4>';
//            $html .= '<form>';
            // Nom du champ: 
            $html .= '<div class="row fieldRow">';
            $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">Nom du champ</div>';
            $html .= '<div class="fieldRowInput field col-xs-12 col-sm-6 col-md-9" data-input_name="">';
            $html .= $default_label;
            $html .= '</div>';
            $html .= '</div>';

            // label: 
            $filter_label = BimpTools::getArrayValueFromPath($values, 'label', $default_label);
            $input_name = ($is_new ? 'new_filter_label' : str_replace(':', '___', $filter_prefixe) . $filter_name . '_label');
            $html .= '<div class="row fieldRow">';
            $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">Intitulé</div>';
            $html .= '<div class="fieldRowInput field col-xs-12 col-sm-6 col-md-9 colOptionInput" data-input_name="label">';
            $html .= BimpInput::renderInput('text', $input_name, $filter_label);
            $html .= '</div>';
            $html .= '</div>';

            // Ouvert par défaut: 
            $open = (int) BimpTools::getArrayValueFromPath($values, 'open', 0);
            $input_name = ($is_new ? 'new_filter_open' : str_replace(':', '___', $filter_prefixe) . $filter_name . '_open');
            $html .= '<div class="row fieldRow">';
            $html .= '<div class="inputLabel col-xs-12 col-sm-4 col-md-3">Ouvert par défaut</div>';
            $html .= '<div class="fieldRowInput field col-xs-12 col-sm-6 col-md-9 colOptionInput" data-input_name="open">';
            $html .= BimpInput::renderInput('toggle', $input_name, $open);
            $html .= '</div>';
            $html .= '</div>';

//            $html .= '</form>';

            if ($is_new) {
                $html .= '<div class="buttonsContainer align-center">';
                $html .= '<span class="btn btn-primary" onclick="FiltersConfig.addFilter($(this), \'' . $filter_prefixe . $filter_name . '\')">';
                $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . ' Ajouter le filtre';
                $html .= '</span>';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        return $html;
    }

    public function renderLinkedObjectOptions($module, $object_name, $child_name, $object_label = '', $filters_prefixe = '')
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
                $html .= $child->renderFiltersSelect(array(
                    'object_label'   => $object_label,
                    'child_name'     => $child_name,
                    'fields_prefixe' => $filters_prefixe
                ));
            }
        }

        return $html;
    }

    public function renderFiltersList($with_default = true)
    {
        $html = '';

        $filters = $this->getData('filters');
        $object = $this->getObjInstance();

        $html .= '<div class="buttonsContainer align-right">';
        $html .= '<span class="btn btn-default loadDefaultFiltersButton" onclick="FiltersConfig.userDefaultFilters($(this))">';
        $html .= BimpRender::renderIcon('fas_undo', 'iconLeft') . 'Filtres par défaut';
        $html .= '</span>';
        $html .= '<span class="btn btn-default removeAllFiltersButton" onclick="FiltersConfig.removeAllFilters($(this))"' . (count($filters) > 1 ? '' : ' style="display: none"') . '>';
        $html .= BimpRender::renderIcon('fas_times-circle', 'iconLeft') . 'Retirer tous les filtres';
        $html .= '</span>';
        $html .= '</div>';

        $html .= '<div class="no_cols"' . (!empty($filters) ? ' style="display: none"' : '') . '>';
        $html .= BimpRender::renderAlerts('Aucun filtre ajouté à cette configuration', 'warning');
        $html .= '</div>';

        $html .= '<div class="inputMultipleValues"' . (empty($filters) ? ' style="display: none"' : '') . '>';
        $html .= '<table class="filters_list">';
        $html .= '<tbody class="multipleValuesList">';

        if (!empty($filters)) {
            foreach ($filters as $filter_name => $filter_options) {
                $label = BimpTools::getArrayValueFromPath($filter_options, 'label', '');
                $open = BimpTools::getArrayValueFromPath($filter_options, 'open', 0);
                $html .= $this->renderFilterItem($object, $filter_name, $label, $open);
            }
        } elseif ($with_default) {
            $html .= $this->renderDefaultFiltersItems($object->module, $object->object_name);
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public function renderFilterItem($object, $filter_name, $label = '', $open = 0)
    {
        $html = '';

        if (is_array($object) && isset($object['module']) && isset($object['object_name'])) {
            $object = BimpObject::getInstance($object['module'], $object['object_name']);
        }

        if (!is_a($object, 'BimpObject')) {
            $html = BimpRender::renderAlerts('Objet associé invalide');
        } else {
            $html .= '<tr class="itemRow filters_itemRow" data-filter_name="' . $filter_name . '">';
            $html .= '<td class="positionHandle"><span></span></td>';
            $html .= '<td class="item_label">';

            $title = BimpTools::ucfirst($object->getLabel()) . ' > ';
            $children = explode(':', $filter_name);

            $obj = $object;
            $filter_prefixe = '';
            $errors = array();

            while (count($children) > 1) {
                $child_name = array_shift($children);

                if ($child_name) {
                    $filter_prefixe .= $child_name . ':';
                    $child = $obj->getChildObject($child_name);

                    if (is_a($child, 'BimpObject')) {
                        $child_label = '';

                        $child_id_prop = $obj->getConf('objects/' . $child_name . '/instance/id_object/field_value', '');
                        if ($child_id_prop) {
                            $child_label = $obj->getConf('fields/' . $child_id_prop . '/label', '');
                        }

                        if (!$child_label) {
                            $child_label = BimpTools::ucfirst($child->getLabel());
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
                $final_name = array_shift($children);

                if (!is_a($obj, 'BimpObject')) {
                    $errors[] = 'Objet contenant le filtre invalide';
                } elseif (!$final_name) {
                    $errors[] = 'Nom du filtre absent';
                } else {
                    $html .= '<input type="hidden" value="' . str_replace(':', '___', $filter_name) . '" name="filters_items[]">';
                    $filter_label = $obj->getConf('filters/' . $final_name . '/label', '');
                    if (!$filter_label) {
                        $filter_label = $obj->getConf('fields/' . $final_name . '/label', $final_name);
                    }

                    if (!$label) {
                        $label = $filter_label;
                    }

                    $title .= $filter_label;
                    $html .= '<span class="bold">' . $label . '</span>';
                    $html .= '&nbsp;&nbsp;&nbsp;<span class="smallInfo">(' . $title . ')</span>';

//                            $html .= '<div style="text-align: right">';
                    $html .= '<span class="openCloseButton open-content" style="float: right">';
                    $html .= BimpRender::renderIcon('fas_cog', 'iconLeft') . 'Options';
                    $html .= '</span>';
//                            $html .= '</div>';

                    $html .= '<div class="item_options openCloseContent" style="display: none">';
                    $html .= $this->renderFilterOptions($obj->module, $obj->object_name, $final_name, $filter_prefixe, array(
                        'label' => $label,
                        'open'  => $open
                            ), false);
                    $html .= '</div>';
                }
            }

            if (count($errors)) {
                $html .= 'Filtre "' . $filter_name . '"';
                $html .= BimpRender::renderAlerts($errors);
            }

            $html .= '</td>';
            $html .= '<td class="removeButton">';
            $html .= '<button type="button" class="btn btn-light-danger iconBtn" onclick="FiltersConfig.removeFilter($(this))">';
            $html .= '<i class="fas fa5-trash-alt"></i>';
            $html .= '</button>';
            $html .= '</td>';

            $html .= '</tr>';
        }

        return $html;
    }

    public function renderDefaultFiltersItems($module, $object_name)
    {
        $html = '';

        $obj = BimpObject::getInstance($module, $object_name);

        $panels = $obj->config->getCompiledParams('filters_panels');
        $filters = array();

        if (isset($panels['default']['filters'])) {
            $filters = $panels['default']['filters'];
        } else {
            if (is_array($panels)) {
                foreach ($panels as $panel_name => $params) {
                    if (isset($params['filters'])) {
                        $filters = $params['filters'];
                    }
                }
            }
        }

        if (!empty($filters)) {
            foreach ($filters as $filter_name => $filter_params) {
                $label = BimpTools::getArrayValueFromPath($filter_params, 'label', '');
                $open = BimpTools::getArrayValueFromPath($filter_params, 'open', 0);
                $html .= $this->renderFilterItem($obj, $filter_name, $label, $open);
            }
        }
        
        return $html;
    }

    // Overrides: 

    public function validatePost()
    {
        $errors = array();

        if (BimpTools::isSubmit('filters_items')) {
            $items = BimpTools::getPostFieldValue('filters_items', array());

            if (empty($items)) {
                $errors[] = 'Aucun filtre ajouté à cette configuration';
            } else {
                $filters = array();

                $module = BimpTools::getPostFieldValue('obj_module', $this->getData('obj_module'));
                $object_name = BimpTools::getPostFieldValue('obj_name', $this->getData('obj_name'));

                if (!$module || !$object_name) {
                    $errors[] = 'Objet associé invalide';
                } else {
                    $base_obj = BimpObject::getInstance($module, $object_name);

                    foreach ($items as $filter_name) {
                        $label = BimpTools::getPostFieldValue($filter_name . '_label', '');
                        $open = BimpTools::getPostFieldValue($filter_name . '_open', 0);

                        $filter_name = str_replace('___', ':', $filter_name);

                        if (!$label) {
                            $label = BC_Filter::getFilterDefaultLabel($base_obj, $filter_name);
                        }

                        $filters[$filter_name] = array(
                            'label' => $label,
                            'open'  => $open
                        );
                    }
                }

                $this->set('filters', $filters);
            }
        }

        if (!count($errors)) {
            $errors = parent::validatePost();
        }

        return $errors;
    }

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $id = (int) $this->id;
        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            $this->db->update('buc_list_table_config', array(
                'id_default_filters_config' => 0,
                    ), 'id_default_filters_config = ' . $id
            );
            $this->db->update('buc_stats_list_config', array(
                'id_default_filters_config' => 0,
                    ), 'id_default_filters_config = ' . $id
            );
        }
    }

    // Actions: 

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

            if (!count($errors)) {
                $values = array(
                    'name'           => 'Configuration utilisateur par défaut',
                    'owner_type'     => UserConfig::OWNER_TYPE_USER,
                    'id_owner'       => (int) $user->id,
                    'id_user_create' => (int) $user->id,
                    'is_default'     => BimpTools::getArrayValueFromPath($data, 'is_default', 1),
                    'obj_module'     => $obj_module,
                    'obj_name'       => $obj_name
                );

                $config = BimpObject::createBimpObject($this->module, $this->object_name, $values, true, $errors, $warnings);

                if (!count($errors)) {
                    if (BimpObject::objectLoaded($config)) {
                        $success_callback .= $config->getCreateJsCallback();
                        if ((int) BimpTools::getArrayValueFromPath($data, 'load_filters_config', 0)) {
                            $success_callback .= html_entity_decode($config->getJsLoadModalForm('edit_filters', 'Edition de la configuration de filtres', array(), '', '', 0, '$()'));
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
}
