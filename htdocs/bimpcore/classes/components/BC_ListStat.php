<?php

class BC_StatList extends BC_List
{

    public $component_name = 'Liste statitique';
    public static $type = 'list_stats';

    public function __construct(BimpObject $object, $name = 'default', $id_parent = null, $title = null, $icon = null)
    {
        $this->params_def['group_by'] = array('data_type' => 'array', 'default' => array(), 'request' => true, 'json' => true);
        $this->params_def['group_by_options'] = array('type' => 'definitions', 'defs_type' => 'group_by_option', 'default' => array(), 'multiple' => 1);

        $path = null;

        if (!is_null($object)) {
            if (!$name || $name === 'default') {
                if ($object->config->isDefined('list_stats')) {
                    $path = 'list_custom';
                    $name = '';
                } elseif ($object->config->isDefined('list_customs/default')) {
                    $path = 'list_customs';
                    $name = 'default';
                }
            } else {
                $path = 'list_customs';
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

        if (is_null($this->items)) {
            $this->fetchItems();
        }

        if (method_exists($this->object, $this->params['content_callback'])) {
            $html .= $this->object->{$this->params['content_callback']}($this->items);
        } else {
            $html .= BimpRender::renderAlerts('Erreur de configuration: aucun contenu défini pour cette section');
        }

        return $html;
    }
}
