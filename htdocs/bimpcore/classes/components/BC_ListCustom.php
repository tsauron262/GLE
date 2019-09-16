<?php

class BC_ListCustom extends BC_List
{

    public $component_name = 'Liste personnalisée';
    public static $type = 'list_custom';

    public function __construct(BimpObject $object, $name = 'default', $id_parent = null, $title = null, $icon = null)
    {
        $this->params_def['content_callback'] = array('default' => '');

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $path = null;

        if (!is_null($object)) {
            if (!$name || $name === 'default') {
                if ($object->config->isDefined('list_custom')) {
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

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderListContent()
    {
        $html = '';

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        if (is_null($this->items)) {
            $this->fetchItems();
        }

        if (method_exists($this->object, $this->params['content_callback'])) {
            $html .= $this->object->{$this->params['content_callback']}($this->items);
        } else {
            $html .= BimpRender::renderAlerts('Erreur de configuration: aucun contenu défini pour cette section');
        }

        $current_bc = $prev_bc;
        return $html;
    }
}
