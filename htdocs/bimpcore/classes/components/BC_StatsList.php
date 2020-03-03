<?php

class BC_StatsList extends BC_List
{

    public $component_name = 'Liste statitique';
    public static $type = 'stats_list';

    public function __construct(BimpObject $object, $name = 'default', $id_parent = null, $title = null, $icon = null)
    {
        $this->params_def['group_by'] = array('data_type' => 'array', 'default' => array(), 'request' => true, 'json' => true);
        $this->params_def['group_by_options'] = array('data_type' => 'array', 'compile' => true, 'default' => array());

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $path = null;

        if (!is_null($object)) {
            if (!$name || $name === 'default') {
                if ($object->config->isDefined('stats_list')) {
                    $path = 'stats_list';
                    $name = '';
                } elseif ($object->config->isDefined('list_customs/default')) {
                    $path = 'stats_lists';
                    $name = 'default';
                }
            } else {
                $path = 'stats_lists';
            }
        }

        if (is_null($title)) {
            $title = 'Statistiques des ' . $object->getLabel('name_plur');
        }

        if (is_null($icon)) {
            $icon = 'fas_chart-bar';
        }

        parent::__construct($object, '', $name, 1, $id_parent, $title, $icon);

        $this->params['filters_panel_open'] = 1;
        $this->params['n'] = 0;

        $current_bc = $prev_bc;
    }

    public function renderHtmlContent()
    {
        $html = '';

        if (count($this->errors)) {
            return parent::renderHtml();
        }

        if (!$this->object->can("view")) {
            return BimpRender::renderAlerts('Vous n\'avez pas la permission de voir ' . $this->object->getLabel('the_plur'));
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html .= $this->renderListParamsInputs();

        $left_content = $this->renderGroupByOptions();

        if (!is_null($this->params['filters_panel'])) {
            $left_content .= '<div class="listFiltersPanelContainer">';
            $left_content .= $this->renderFiltersPanel();
            $left_content .= '</div>';
        }

        if ($left_content) {
            $html .= '<div class="row">';
            $html .= '<div class="col-xs-12 col-sm-12 col-md-2 col-lg-2">';
            $html .= $left_content;
            $html .= '</div>';

            $html .= '<div class="col-xs-12 col-sm-12 col-md-10 col-lg-10">';
        }

        $html .= '<div id="' . $this->identifier . '_ajax_content" class="stats_list_ajax_content">';
//        $html .= $this->renderListContent();
        $html .= '</div>';

        if ($left_content) {
            $html .= '</div></div>';
        }

        $html .= '<div class="ajaxResultContainer" id="' . $this->identifier . '_result"></div>';

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderListContent()
    {
        $html = '';

        if (is_null($this->items)) {
            $this->fetchItems();
        }

        $html .= 'Nb lignes factures: ' . count($this->items);

        return $html;
    }

    public function renderGroupByOptions()
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = '';

        if (!empty($this->params['group_by_options'])) {
            $group_by_options = array();

            foreach ($this->params['group_by_options'] as $groupByOption) {
                if (is_string($groupByOption)) {
                    if ($this->object->field_exists($groupByOption)) {
                        $label = $this->object->getConf('fields/' . $groupByOption . '/label', $groupByOption);
                        $group_by_options[$groupByOption] = $label;
                    }
                }
            }

            if (count($group_by_options)) {

                $panel_content = '<div id="' . $this->identifier . '_group_by_optons" class="stats_list_group_by_options">';

                $input = BimpInput::renderInput('select', 'group_by_add_value', $this->params['group_by'], array(
                            'options' => $group_by_options
                ));
                $content = BimpInput::renderMultipleValuesInput($this->object, 'group_by', $input, $this->params['group_by'], '', 0, 1, 1);
                $panel_content .= BimpInput::renderInputContainer('group_by', '', $content, '', 0, 1, '', array('values_field' => 'group_by'));

                $panel_content .= '</div>';

                $html = BimpRender::renderPanel('Grouper les résulats par: ', $panel_content, '', array(
                            'type' => 'secondary',
                            'icon' => 'fas_object-group'
                ));
            }
        }

        $current_bc = $prev_bc;
        return $html;
    }
}
