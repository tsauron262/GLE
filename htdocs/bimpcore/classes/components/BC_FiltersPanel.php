<?php

class BC_FiltersPanel extends BC_Panel
{

    public $component_name = 'Filtres de liste';
    public static $type = 'filters_panel';
    public $list_type = '';
    public $list_name = '';
    public $list_identifier = '';
    protected $values = array(
        'fields'   => array(),
        'children' => array()
    );

    public function __construct(BimpObject $object, $list_type, $list_name, $list_identifier, $name = 'default')
    {
        $this->params_def['filters'] = array('type' => 'definitions', 'defs_type' => 'list_filter', 'multiple' => true);
        $this->params_def['default_values'] = array('data_type' => 'array', 'default' => array(), 'compile' => true);

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
    }

    public function setFiltersValues($values)
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
            $values = $listFilters->getData('filters');

            if (is_array($values) && !empty($values)) {
                $this->setFiltersValues($values);
            } else {
                $errors[] = 'Aucun filtre trouvé pour cet enregistrement';
            }
        }

        return $errors;
    }

    public function getSqlFilters(&$filters = array(), &$joins = array())
    {
        $errors = array();

        foreach ($this->params['filters'] as $key => $filter) {
            if (isset($filter['show']) && !(int) $filter['show']) {
                continue;
            }
            
            if (isset($filter['field']) && $filter['field']) {
                $values = $this->getValues($filter['field'], isset($filter['child']) ? $filter['child'] : '');
                if (!empty($values)) {
                    if ((int) $filter['custom']) {
                        $path = $this->config_path . '/filters/' . $key;
                        $bc_filter = new BC_CustomFilter($this->object, $filter, $path, $values);
                    } else {
                        $bc_filter = new BC_FieldFilter($this->object, $filter, $values);
                    }
                    $filter_errors = $bc_filter->getSqlFilters($filters, $joins);
                    if (count($filter_errors)) {
                        $errors[] = BimpTools::getMsgFromArray($filter_errors, 'Filtre "' . $bc_filter->params['label'] . '"');
                    }
                }
            }
        }

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

    public function renderHtmlContent()
    {
        $html = parent::renderHtmlContent();

        if (count($this->errors)) {
            return $html;
        }

        $html .= '<div class="filters_toolbar align-right">';
        $html .= BimpRender::renderRowButton('Effacer tous les filtres', 'fas_eraser', 'removeAllListFilters(\'' . $this->identifier . '\')');
        $html .= BimpRender::renderRowButton('Enregistrer les filtres actuels', 'fas_save', 'saveListFilters($(this), \'' . $this->identifier . '\')');
        $html .= BimpRender::renderRowButton('Replier tous les filtres', 'fas_minus-square', 'hideAllFilters(\'' . $this->identifier . '\')');
        $html .= BimpRender::renderRowButton('Déplier tous les filtres', 'fas_plus-square', 'showAllFilters(\'' . $this->identifier . '\')');
        $html .= '</div>';

        global $user;

        if (BimpObject::objectLoaded($user)) {
            $saves = BimpCache::getUserListFiltersArray($this->object, $user->id, $this->list_type, $this->list_name, $this->name);

            if (count($saves)) {
                $html .= '<div class="load_saved_filters_container">';
                $html .= '<span style="font-size: 12px;color: #8C8C8C;">Filtres enregistrés: </span>';
                $html .= BimpInput::renderInput('select', 'id_filters_to_load', '', array(
                            'options' => $saves
                ));
                $html .= '<div style="text-align: right">';
                $html .= '<button type="button" class="btn btn-default btn-small" onclick="deleteSavedFilters($(this), \'' . $this->identifier . '\');">';
                $html .= BimpRender::renderIcon('fas_trash', 'iconLeft') . 'Supprimer';
                $html .= '</button>';
                $html .= '<button type="button" class="btn btn-default btn-small" onclick="loadSavedFilters($(this), \'' . $this->identifier . '\');">';
                $html .= BimpRender::renderIcon('fas_download', 'iconLeft') . 'Charger';
                $html .= '</button>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }

        foreach ($this->params['filters'] as $key => $filter) {
            if (isset($filter['show']) && !(int) $filter['show']) {
                continue;
            }
            
            if (isset($filter['field']) && (string) $filter['field']) {
                $values = $this->getValues($filter['field'], isset($filter['child']) ? $filter['child'] : '');
                
                if ((int) $filter['custom']) {
                    $path = $this->config_path . '/filters/' . $key;
                    $bc_filter = new BC_CustomFilter($this->object, $filter, $path, $values);
                } else {
                    $bc_filter = new BC_FieldFilter($this->object, $filter, $values);
                }
                
                $html .= $bc_filter->renderHtml();
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
}
