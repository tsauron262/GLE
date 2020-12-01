<?php

class BC_FiltersPanel extends BC_Panel
{

    public $component_name = 'Filtres de liste';
    public static $type = 'filters_panel';
    public static $hasUserConfig = true;
    public $list_type = '';
    public $list_name = '';
    public $list_identifier = '';
    public $id_list_filters = 0;
    protected $filters = array();
    public $bc_filters = array();
    public $id_filters_config = 0;

    public function __construct(BimpObject $object, $list_type, $list_name, $list_identifier, $name = 'default', $id_config = null)
    {
        $this->params_def['configurable']['default'] = 1;
        $this->params_def['default_values'] = array('data_type' => 'array', 'default' => array(), 'compile' => true);

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $this->list_type = $list_type;
        $this->list_name = $list_name;
        $this->list_identifier = $list_identifier;

        parent::__construct($object, $name, '', false, 1, null, null, $id_config);

        $this->params['title'] = 'Filtres';
        $this->params['icon'] = 'fas_filter';

        $this->data['list_identifier'] = $this->list_identifier;
        $this->data['list_type'] = $this->list_type;
        $this->data['list_name'] = $this->list_name;

        $this->values = $this->params['default_values'];

        if (BimpTools::isSubmit('id_current_list_filters')) {
            $this->id_list_filters = (int) BimpTools::getValue('id_current_list_filters', 0);
        }

        $this->addIdentifierSuffix($list_type . '_' . $list_name);

        $this->fetchFilters();
        $current_bc = $prev_bc;
    }

    public function fetchFilters()
    {
        $this->bc_Filters = array();
        $filters = array();

        if (BimpObject::objectLoaded($this->userConfig)) {
            $filters = $this->userConfig->getData('filters');
        }

        if (empty($filters) && $this->isObjectValid()) {
            if ($this->object->config->isDefined($this->config_path . '/filters')) {
                $filters = $this->object->config->getCompiledParams($this->config_path . '/filters');
            }
        }

        foreach ($filters as $filter_name => $params) {
            if (!isset($this->bc_filters[$filter_name])) {
                $this->bc_filters[$filter_name] = new BC_Filter($this->object, $filter_name, $this->config_path . '/filters/' . $filter_name, $params);
            }
        }
    }

    public function setFilters($filters)
    {
        $this->filters = array();

        foreach ($filters as $filter_name => $filter) {
            $filter_name = str_replace('___', ':', $filter_name);
            $this->filters[$filter_name] = $filter;
        }

        foreach ($this->filters as $filter_name => $filter_data) {
            $values = BimpTools::getArrayValueFromPath($filter_data, 'values', array());
            $excluded_values = BimpTools::getArrayValueFromPath($filter_data, 'excluded_values', array());

            if (!empty($values) || !empty($excluded_values)) {
                if (!isset($this->bc_filters[$filter_name])) {
                    $this->bc_filters[$filter_name] = new BC_Filter($this->object, $filter_name, $this->config_path . '/filters/' . $filter_name);
                }
            }

            if (isset($this->bc_filters[$filter_name])) {
                if (isset($filter_data['open'])) {
                    $this->bc_filters[$filter_name]->params['open'] = (int) $filter_data['open'];
                }

                $this->bc_filters[$filter_name]->values = $values;
                $this->bc_filters[$filter_name]->excluded_values = $excluded_values;
            }
        }
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function loadSavedValues($id_list_filters)
    {
        $errors = array();
        $this->filters = array();

        $listFilters = BimpCache::getBimpObjectInstance('bimpuserconfig', 'ListFilters', (int) $id_list_filters);

        if (!BimpObject::objectLoaded($listFilters)) {
            $errors[] = 'L\'enregistrement de filtres d\'ID ' . $id_list_filters . ' n\'existe pas';
        } else {
            $this->setFilters($listFilters->getData('filters'));
            $this->id_list_filters = $id_list_filters;
        }

        return $errors;
    }

    public function getSqlFilters(&$filters = array(), &$joins = array())
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }

        $prev_bc = $current_bc;
        $current_bc = $this;

        $errors = array();

        foreach ($this->bc_filters as $filter_name => $bc_filter) {
            $filter_errors = $bc_filter->getSqlFilters($filters, $joins);
            if (count($filter_errors)) {
                $errors[] = BimpTools::getMsgFromArray($filter_errors, 'Filtre "' . $bc_filter->params['label'] . '"');
            }
        }

        $current_bc = $prev_bc;
        return $errors;
    }

    public function renderHtmlContent()
    {
        $html = parent::renderHtmlContent();

        if (count($this->errors)) {
            return $html;
        }

        global $user, $current_bc;

        if (!is_object($current_bc)) {
            $current_bc = null;
        }

        $prev_bc = $current_bc;
        $current_bc = $this;

        $html .= '<div class="filters_toolbar align-right">';
        $html .= BimpRender::renderRowButton('Effacer tous les filtres', 'fas_eraser', 'removeAllListFilters(\'' . $this->identifier . '\')');
        $html .= BimpRender::renderRowButton('Enregistrer les filtres actuels', 'fas_save', 'saveListFilters($(this), \'' . $this->identifier . '\')');
        if (BimpObject::objectLoaded($user)) {
            $list_title = 'Gestion des filtres de liste';
            $html .= BimpRender::renderRowButton('Liste des filtres enregistrés', 'fas_bars', 'loadBCUserConfigsModalList($(this), ' . $user->id . ',\'' . $this->identifier . '\', \'ListFilters\', \'' . $list_title . '\')');
        }
        if ($this->params['configurable']) {
            $list_title = 'Gestion des configrations de  fitres';
            $html .= BimpRender::renderRowButton('Configurations des filtres', 'fas_cog', 'loadBCUserConfigsModalList($(this), ' . $user->id . ',\'' . $this->identifier . '\', \'FiltersConfig\', \'' . $list_title . '\')');
        }
        $html .= BimpRender::renderRowButton('Replier tous les filtres', 'fas_minus-square', 'hideAllFilters(\'' . $this->identifier . '\')');
        $html .= BimpRender::renderRowButton('Déplier tous les filtres', 'fas_plus-square', 'showAllFilters(\'' . $this->identifier . '\')');
        $html .= '</div>';

        if ($this->params['configurable']) {
            $html .= '<div class="filters_panel_configurations_container">';
            $html .= $this->renderFiltersConfigs();
            $html .= '</div>';
        }


        $html .= '<div class="load_saved_filters_container">';
        $html .= $this->renderSavedFilters();
        $html .= '</div>';

        foreach ($this->bc_filters as $filter_name => $bc_filter) {
            $html .= $bc_filter->renderHtml();
        }

        if ($this->params['configurable']) {
            if (BimpObject::objectLoaded($this->userConfig)) {
                $onclick = $this->userConfig->getJsLoadModalForm('edit_filters', 'Edition de la configuration de filtres "' . $this->userConfig->getData('name') . '"');
            } else {
                BimpObject::loadClass('bimpuserconfig', 'FiltersConfig');
                $userConfig = FiltersConfig::getUserDefaultConfig($user->id, array(
                            'obj_module' => $this->object->module,
                            'obj_name'   => $this->object->object_name
                ));

                if (BimpObject::objectLoaded($userConfig)) {
                    $onclick = $userConfig->getJsLoadModalForm('edit_filters', 'Edition de la configuration de filtres "' . $userConfig->getData('name') . '"');
                } else {
                    $config_instance = BimpObject::getInstance('bimpuserconfig', 'FiltersConfig');
                    $onclick = $config_instance->getJsActionOnclick('createUserDefault', array(
                        'load_filters_config' => 1,
                        'obj_module'          => $this->object->module,
                        'obj_name'            => $this->object->object_name,
                        'is_default'          => 1
                            ), array());
                }
            }

            $html .= '<div class="buttonsContainer align-center">';
            $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
            $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Ajouter un filtre';
            $html .= '</span>';
            $html .= '</div>';
        }

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderFiltersConfigs()
    {
        $html = '';

        if ($this->isObjectValid()) {
            global $user;

            $html .= '<span style="font-size: 12px;color: #8C8C8C;">' . BimpRender::renderIcon('fas_cog', 'iconLeft') . 'Configuration des filtres: </span>';

            BimpObject::loadClass('bimpuserconfig', 'FiltersConfig');
            $userConfigs = FiltersConfig::getUserConfigsArray($user->id, $this->object, '', true);
            $id_config = (BimpObject::objectLoaded($this->userConfig) ? $this->userConfig->id : 0);

            if (count($userConfigs) > 1) {
                $html .= BimpInput::renderInput('select', 'id_filters_config_to_load', (int) $id_config, array(
                            'options' => $userConfigs
                ));
            }

            $html .= '<div style="text-align: right">';

            $instance = BimpObject::getInstance('bimpuserconfig', 'FiltersConfig');
            $onclick = $instance->getJsLoadModalForm('default', 'Nouvelle configuration de filtres', array(
                'fields' => array(
                    'obj_module' => $this->object->module,
                    'obj_name'   => $this->object->object_name
                )
            ));

            $html .= '<button type="button" class="btn btn-default btn-small" onclick="' . $onclick . '">';
            $html .= BimpRender::renderIcon('fas_plus-circle', 'iconLeft') . 'Nouvelle configuration';
            $html .= '</button>';

            if ($id_config && $this->userConfig->can('edit')) {
                $onclick = $this->userConfig->getJsLoadModalForm('default', 'Edition de la configuration de filtres "' . $this->userConfig->getData('name') . '"');
                $html .= '<button type="button" class="btn btn-default btn-small" onclick="' . $onclick . '">';
                $html .= BimpRender::renderIcon('fas_edit', 'iconLeft') . 'Editer';
                $html .= '</button>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    public function renderSavedFilters()
    {
        $html = '';

        global $user;
        if (BimpObject::objectLoaded($user)) {
            BimpObject::loadClass('bimpuserconfig', 'ListFilters');
            $userListFilters = ListFilters::getUserConfigsArray($user->id, $this->object, '', true);
            if (count($userListFilters) > 1) {
                $html .= '<span style="font-size: 12px;color: #8C8C8C;">' . BimpRender::renderIcon('fas_save', 'iconLeft') . 'Filtres enregistrés: </span>';
                $html .= BimpInput::renderInput('select', 'id_filters_to_load', (int) $this->id_list_filters, array(
                            'options' => $userListFilters
                ));

                if ((int) $this->id_list_filters) {
                    $listFilters = BimpCache::getBimpObjectInstance('bimpuserconfig', 'ListFilters', (int) $this->id_list_filters);
                    if (BimpObject::objectLoaded($listFilters)) {
                        $html .= '<div style="text-align: right">';
                        if ($listFilters->can('edit')) {
                            $html .= '<button type="button" class="btn btn-default btn-small" onclick="saveListFilters($(this), \'' . $this->identifier . '\', ' . $this->id_list_filters . ');">';
                            $html .= BimpRender::renderIcon('fas_save', 'iconLeft') . 'Mettre à jour';
                            $html .= '</button>';
                        }
                        $html .= '<button type="button" class="btn btn-default btn-small" onclick="loadSavedFilters(\'' . $this->identifier . '\', ' . $this->id_list_filters . ');">';
                        $html .= BimpRender::renderIcon('fas_download', 'iconLeft') . 'Recharger';
                        $html .= '</button>';
                        $html .= '</div>';
                    }
                }
            }
        }

        return $html;
    }

    public function renderHtmlFooter()
    {
        $items = array();

        $items[] = BimpRender::renderButton(array(
                    'classes'     => array('btn', 'btn-light-default'),
                    'label'       => 'Effacer tous les filtres',
                    'icon_before' => 'fas_eraser',
                    'attr'        => array(
                        'onclick' => 'removeAllListFilters(\'' . $this->identifier . '\')'
                    )
        ));

        $items[] = BimpRender::renderButton(array(
                    'classes'     => array('btn', 'btn-light-default'),
                    'label'       => 'Enregistrer les filtres actuels',
                    'icon_before' => 'fas_save',
                    'attr'        => array(
                        'onclick' => 'saveListFilters($(this), \'' . $this->identifier . '\')'
                    )
        ));

        $items[] = BimpRender::renderButton(array(
                    'classes'     => array('btn', 'btn-light-default'),
                    'label'       => 'Replier tous les filtres',
                    'icon_before' => 'fas_minus-square',
                    'attr'        => array(
                        'onclick' => 'hideAllFilters(\'' . $this->identifier . '\')'
                    )
        ));

        $items[] = BimpRender::renderButton(array(
                    'classes'     => array('btn', 'btn-light-default'),
                    'label'       => 'Déplier tous les filtres',
                    'icon_before' => 'fas_plus-square',
                    'attr'        => array(
                        'onclick' => 'showAllFilters(\'' . $this->identifier . '\')'
                    )
        ));

        $html .= '<div class="panelFooterButtons" style="text-align: right">';
        $html .= BimpRender::renderDropDownButton('Action', $items, array(
                    'type' => 'default',
                    'icon' => 'fas_cogs'
        ));
        $html .= '</div>';

        return $html;
    }

    public function renderActiveFilters()
    {
        $html = '';

        foreach ($this->bc_filters as $filter_name => $bc_filter) {
            if (!empty($bc_filter->values) || !empty($bc_filter->excluded_values)) {
                $filters_html = '';
                $values_html = '';
                if (!empty($bc_filter->values)) {
                    foreach ($bc_filter->values as $value) {
                        $label = $bc_filter->getFilterValueLabel($value);
                        if (!$label) {
                            $label = $value;
                        }

                        $values_html .= '<div class="filter_value">';
                        if (is_array($value)) {
                            $value_str = htmlentities(json_encode($value));
                        } else {
                            $value_str = htmlentities($value);
                        }
                        $onclick = 'removeBimpFilterValueFromActiveFilters($(this), \'' . $this->identifier . '\', \'' . $filter_name . '\', \'' . $value_str . '\', false)';
                        $values_html .= '<span class="remove_btn" onclick="' . $onclick . '">';
                        $values_html .= BimpRender::renderIcon('fas_times');
                        $values_html .= '</span>';
                        $values_html .= $label;
                        $values_html .= '</div>';
                    }

                    $filters_html .= '<div class="included_values">';
                    $filters_html .= $values_html;
                    $filters_html .= '</div>';
                }

                if (!empty($bc_filter->excluded_values)) {
                    $excluded_values_html = '';
                    foreach ($bc_filter->excluded_values as $value) {
                        $label = $bc_filter->getFilterValueLabel($value);
                        if (!$label) {
                            $label = $value;
                        }

                        $excluded_values_html .= '<div class="filter_value">';
                        if (is_array($value)) {
                            $value_str = htmlentities(json_encode($value));
                        } else {
                            $value_str = htmlentities($value);
                        }
                        $onclick = 'removeBimpFilterValueFromActiveFilters($(this), \'' . $this->identifier . '\', \'' . $filter_name . '\', \'' . $value_str . '\', true)';
                        $excluded_values_html .= '<span class="remove_btn" onclick="' . $onclick . '">';
                        $excluded_values_html .= BimpRender::renderIcon('fas_times');
                        $excluded_values_html .= '</span>';
                        $excluded_values_html .= $label;
                        $excluded_values_html .= '</div>';
                    }

                    $filters_html .= '<div class="excluded_values">';
                    $filters_html .= $excluded_values_html;
                    $filters_html .= '</div>';
                }

                if ($filters_html) {
                    $html .= '<div class="filter_active_values">';
                    $html .= '<div class="filter_label">';
                    $html .= $bc_filter->params['label'];
                    $html .= '</div>';

                    $html .= $filters_html;
                    $html .= '</div>';
                }
            }
        }

        if ($html) {
            $html = '<div class="list_active_filters_title">' . BimpRender::renderIcon('fas_filter', 'iconLeft') . 'Filtres actifs: </div>' . $html;
        }

        return $html;
    }
}
