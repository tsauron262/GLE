<?php

class BC_StatsList extends BC_List
{

    public $component_name = 'Liste statitique';
    public static $type = 'stats_list';

    public function __construct(BimpObject $object, $name = 'default', $id_parent = null, $title = null, $icon = null)
    {
        $this->params_def['group_by'] = array('data_type' => 'array', 'default' => array(), 'request' => true, 'json' => true);
        $this->params_def['group_by_options'] = array('data_type' => 'array', 'compile' => true, 'default' => array());

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

        parent::__construct($object, '', $name, $id_parent, $title, $icon);
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

        $html .= $this->renderListParamsInputs();

        if (!is_null($this->params['filters_panel'])) {
            $html .= '<div class="row">';
            $html .= '<div class="listFiltersPanelContainer col-xs-12 col-sm-12 col-md-2 col-lg-2"' . (!(int) $this->params['filters_panel_open'] ? ' style="display: none"' : '') . '>';
            $html .= $this->renderFiltersPanel();
            $html .= '</div>';
            $html .= '<div class="objectlistCustomContainer col-xs-12 col-sm-12 col-md-10 col-lg-10">';
        }

        $html .= $this->renderGroupByOptions();

        $html .= '<div id="' . $this->identifier . '_ajax_content" class="list_custom_ajax_content">';
        $html .= $this->renderListContent();
        $html .= '</div>';

        if (!is_null($this->params['filters_panel'])) {
            $html .= '</div></div>';
        }

        $html .= '<div class="ajaxResultContainer" id="' . $this->identifier . '_result"></div>';

        return $html;
    }

    public function renderListContent()
    {
        $html = '';

//        if (is_null($this->items)) {
//            $this->fetchItems();
//        }

        $html .= 'ICI LIST';
        
        return $html;
    }

    public function renderGroupByOptions()
    {
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

            $html .= '<div id="' . $this->identifier . '_group_by_optons" class="stats_list_group_by_options">';

            $html .= '<h3>Grouper les résultats par: </h3>';

            $input = BimpInput::renderInput('', 'group_by_add_value', $this->params['group_by'], array(
                        'options' => $group_by_options
            ));
            $content = BimpInput::renderMultipleValuesInput($this, 'group_by', $input, $this->params['group_by'], '', 0, 1, 1);
            $html .= BimpInput::renderInputContainer('cols', '', $content, '', 0, 1, '', array('values_field' => 'group_by'));

            $html .= '</div>';
        }

        return $html;
    }
}
