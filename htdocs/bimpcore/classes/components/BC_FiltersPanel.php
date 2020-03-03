<?php

class BC_FiltersPanel extends BC_Panel
{

    public $component_name = 'Filtres de liste';
    public static $type = 'filters_panel';
    public $list_type = '';
    public $list_name = '';
    public $list_identifier = '';
    public $id_list_filters = 0;
    protected $values = array(
        'fields'   => array(),
        'children' => array()
    );
    protected $excluded_values = array(
        'fields'   => array(),
        'children' => array()
    );

    public function __construct(BimpObject $object, $list_type, $list_name, $list_identifier, $name = 'default')
    {
        $this->params_def['filters'] = array('type' => 'definitions', 'defs_type' => 'list_filter', 'multiple' => true);
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

        parent::__construct($object, $name, '');

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
        $current_bc = $prev_bc;
    }

    public function setFiltersValues($values, $exclude_values = array())
    {
        if (!isset($values['fields']) && !isset($values['children']) && !empty($values)) {
            $this->values['fields'] = $values;
        }

        if (isset($values['fields'])) {
            $this->values['fields'] = $values['fields'];
        }

        if (isset($values['children'])) {
            $this->values['children'] = $values['children'];
        }

        if (!isset($exclude_values['fields']) && !isset($exclude_values['children']) && !empty($exclude_values)) {
            $this->excluded_values['fields'] = $exclude_values;
        }

        if (isset($exclude_values['fields'])) {
            $this->excluded_values['fields'] = $exclude_values['fields'];
        }

        if (isset($exclude_values['children'])) {
            $this->excluded_values['children'] = $exclude_values['children'];
        }
    }

    public function getValues($field, $child = '')
    {
        $values = array();
        if ($child) {
            if (isset($this->values['children'][$child][$field])) {
                $values = $this->values['children'][$child][$field];
            }
        } elseif (isset($this->values['fields'][$field])) {
            $values = $this->values['fields'][$field];
        }

        return $values;
    }

    public function getExcludedValues($field, $child = '')
    {
        $values = array();
        if ($child) {
            if (isset($this->excluded_values['children'][$child][$field])) {
                $values = $this->excluded_values['children'][$child][$field];
            }
        } elseif (isset($this->excluded_values['fields'][$field])) {
            $values = $this->excluded_values['fields'][$field];
        }

        return $values;
    }

    public function loadSavedValues($id_list_filters)
    {
        $errors = array();

        $this->values = array(
            'fields'   => array(),
            'children' => array()
        );

        $listFilters = BimpCache::getBimpObjectInstance('bimpcore', 'ListFilters', (int) $id_list_filters);
        if (!BimpObject::objectLoaded($listFilters)) {
            $errors[] = 'L\'enregistrement de filtres d\'ID ' . $id_list_filters . ' n\'existe pas';
        } else {
            $this->id_list_filters = $id_list_filters;
            $values = $listFilters->getData('filters');
            $excluded = $listFilters->getData('excluded');

            if (is_array($values) && !empty($values)) {
                $this->setFiltersValues($values, $excluded);
            } else {
                $errors[] = 'Aucun filtre trouvé pour cet enregistrement';
            }
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

        foreach ($this->params['filters'] as $key => $filter) {
            if (isset($filter['show']) && !(int) $filter['show']) {
                continue;
            }
            if (isset($filter['field']) && $filter['field']) {
                $values = $this->getValues($filter['field'], isset($filter['child']) ? $filter['child'] : '');
                $excluded = $this->getExcludedValues($filter['field'], isset($filter['child']) ? $filter['child'] : '');
                $path = $this->config_path . '/filters/' . $key;
                if ((int) $filter['custom']) {
                    $bc_filter = new BC_CustomFilter($this->object, $filter, $path, $values, $excluded);
                } else {
                    $bc_filter = new BC_FieldFilter($this->object, $filter, $path, $values, $excluded);
                }
                if (!empty($bc_filter->values) || !empty($bc_filter->excluded_values)) {
                    $filter_errors = $bc_filter->getSqlFilters($filters, $joins);
                    if (count($filter_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($filter_errors, 'Filtre "' . $bc_filter->params['label'] . '"');
                    }
                }
            }
        }

//        echo '<pre>';
//        print_r($filters);
//        echo '</pre>';

        $current_bc = $prev_bc;
        return $errors;
    }

    public function addFieldFilterValues($field_name, $values, $child = '')
    {
        if (!is_array($values)) {
            $values = array($values);
        }

        if ($child) {
            if (!isset($this->values['children'])) {
                $this->values['children'] = array();
            }
            if (!isset($this->values['children'][$child])) {
                $this->values['children'][$child] = array();
            }

            $array = &$this->values['children'][$child];
        } else {
            if (!isset($this->values['fields'])) {
                $this->values['fields'] = array();
            }
            $array = &$this->values['fields'];
        }
        if (!isset($array[$field_name])) {
            $array[$field_name] = $values;
            return;
        }

        if (!is_array($array[$field_name])) {
            $array[$field_name] = array($array[$field_name]);
        }

        foreach ($values as $value) {
            if (!is_array($value)) {
                if (!in_array($value, $array[$field_name])) {
                    $array[$field_name][] = $value;
                }
            } else {
                $check = true;
                foreach ($array[$field_name] as $val) {
                    if (is_array($val)) {
                        $check = false;
                        foreach ($value as $key => $subVal) {
                            if (!isset($val[$key]) || $val[$key] !== $subVal) {
                                $check = true;
                                break;
                            }
                        }
                        if (!$check) {
                            break;
                        }
                    }
                }
                if ($check) {
                    $array[$field_name][] = $value;
                }
            }
        }
    }

    public function addFieldFilterExcludedValues($field_name, $values, $child = '')
    {
        if (!is_array($values)) {
            $values = array($values);
        }

        if ($child) {
            if (!isset($this->excluded_values['children'])) {
                $this->excluded_values['children'] = array();
            }
            if (!isset($this->excluded_values['children'][$child])) {
                $this->excluded_values['children'][$child] = array();
            }

            $array = &$this->excluded_values['children'][$child];
        } else {
            if (!isset($this->excluded_values['fields'])) {
                $this->excluded_values['fields'] = array();
            }
            $array = &$this->excluded_values['fields'];
        }
        if (!isset($array[$field_name])) {
            $array[$field_name] = $values;
            return;
        }

        if (!is_array($array[$field_name])) {
            $array[$field_name] = array($array[$field_name]);
        }

        foreach ($values as $value) {
            if (!is_array($value)) {
                if (!in_array($value, $array[$field_name])) {
                    $array[$field_name][] = $value;
                }
            } else {
                $check = true;
                foreach ($array[$field_name] as $val) {
                    if (is_array($val)) {
                        $check = false;
                        foreach ($value as $key => $subVal) {
                            if (!isset($val[$key]) || $val[$key] !== $subVal) {
                                $check = true;
                                break;
                            }
                        }
                        if (!$check) {
                            break;
                        }
                    }
                }
                if ($check) {
                    $array[$field_name][] = $value;
                }
            }
        }
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
            $html .= BimpRender::renderRowButton('Liste des filtres enregistrés', 'fas_bars', 'loadUserListFiltersModalList($(this), \'' . $this->identifier . '\', ' . $user->id . ')');
        }
        $html .= BimpRender::renderRowButton('Replier tous les filtres', 'fas_minus-square', 'hideAllFilters(\'' . $this->identifier . '\')');
        $html .= BimpRender::renderRowButton('Déplier tous les filtres', 'fas_plus-square', 'showAllFilters(\'' . $this->identifier . '\')');
        $html .= '</div>';

        global $user;

        if (BimpObject::objectLoaded($user)) {
            $saves = BimpCache::getUserListFiltersArray($this->object, $user->id, $this->name, true);

            if (count($saves)) {
                $html .= '<div class="load_saved_filters_container">';
                $html .= '<span style="font-size: 12px;color: #8C8C8C;">Filtres enregistrés: </span>';
                $html .= BimpInput::renderInput('select', 'id_filters_to_load', (int) $this->id_list_filters, array(
                            'options' => $saves
                ));

                if ((int) $this->id_list_filters) {
                    $listFilters = BimpCache::getBimpObjectInstance('bimpcore', 'ListFilters', (int) $this->id_list_filters);
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
                $html .= '</div>';
            }
        }

        foreach ($this->params['filters'] as $key => $filter) {
            if (isset($filter['show']) && !(int) $filter['show']) {
                continue;
            }

            if (isset($filter['field']) && (string) $filter['field']) {
                $values = $this->getValues($filter['field'], isset($filter['child']) ? $filter['child'] : '');
                $excluded_values = $this->getExcludedValues($filter['field'], isset($filter['child']) ? $filter['child'] : '');
                $path = $this->config_path . '/filters/' . $key;
                if ((int) $filter['custom']) {
                    $bc_filter = new BC_CustomFilter($this->object, $filter, $path, $values, $excluded_values);
                } else {
                    $bc_filter = new BC_FieldFilter($this->object, $filter, $path, $values, $excluded_values);
                }

                $html .= $bc_filter->renderHtml();
            }
        }

        $current_bc = $prev_bc;
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

        foreach ($this->params['filters'] as $key => $filter) {
            $field_name = $filter['field'];
            $child_name = (isset($filter['child']) ? $filter['child'] : '');
            $values = $this->getValues($field_name, $child_name);
            $excluded_values = $this->getExcludedValues($field_name, $child_name);

            if (!empty($values) || !empty($excluded_values)) {
                $path = $this->config_path . '/filters/' . $key;
                if ((int) $filter['custom']) {
                    $bc_filter = new BC_CustomFilter($this->object, $filter, $path, $values, $excluded_values);
                } else {
                    $bc_filter = new BC_FieldFilter($this->object, $filter, $path, $values, $excluded_values);
                }

                $filters_html = '';
                $values_html = '';
                if (!empty($values)) {
                    foreach ($bc_filter->values as $value) {
                        $label = $bc_filter->getFilterValueLabel($value);
                        if (!$label) {
                            $label = $value;
                        }

//                        if ($values_html) {
//                            $values_html .= '<span class="relation">OU</span>';
//                        }

                        $values_html .= '<div class="filter_value">';
                        if (is_array($value)) {
                            $value_str = htmlentities(json_encode($value));
                        } else {
                            $value_str = htmlentities($value);
                        }
                        $onclick = 'removeBimpFilterValueFromActiveFilters($(this), \'' . $this->identifier . '\', \'' . $field_name . '\', \'' . $child_name . '\', \'' . $value_str . '\', false)';
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

                if (!empty($excluded_values)) {
                    $excluded_values_html = '';
                    foreach ($bc_filter->excluded_values as $value) {
                        $label = $bc_filter->getFilterValueLabel($value);
                        if (!$label) {
                            $label = $value;
                        }

//                        if ($excluded_values_html) {
//                            $excluded_values_html .= '<span class="relation">ET</span>';
//                        }

                        $excluded_values_html .= '<div class="filter_value">';
                        if (is_array($value)) {
                            $value_str = htmlentities(json_encode($value));
                        } else {
                            $value_str = htmlentities($value);
                        }
                        $onclick = 'removeBimpFilterValueFromActiveFilters($(this), \'' . $this->identifier . '\', \'' . $field_name . '\', \'' . $child_name . '\', \'' . $value_str . '\', true)';
                        $excluded_values_html .= '<span class="remove_btn" onclick="' . $onclick . '">';
                        $excluded_values_html .= BimpRender::renderIcon('fas_times');
                        $excluded_values_html .= '</span>';
                        $excluded_values_html .= $label;
                        $excluded_values_html .= '</div>';
                    }

//                    if ($values_html) {
//                        $filters_html .= '<span class="relation">ET</span>';
//                    }

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
