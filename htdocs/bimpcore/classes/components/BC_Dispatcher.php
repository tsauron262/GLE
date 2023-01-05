<?php

class BC_Dispatcher extends BC_List
{

    public $component_name = 'Répartisseur';
    public static $type = 'dispatcher';
    public static $config_required = false;

    public function __construct(BimpObject $object, $path, $dispatcher_name = 'default', $id_parent = null, $title = null, $icon = null)
    {
        $this->params_def['item_display'] = array('default' => 'nom');
        $this->params_def['field_value'] = array('default' => 'primary');

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        if (is_a($object, 'BimpObject')) {
            if (is_null($title)) {
                $title = BimpTools::ucfirst($object->getLabel('name_plur')) . ' disponibles';
            }

            if (is_null($icon)) {
                $icon = $object->params['icon'];
            }
        }
        parent::__construct($object, $path, $dispatcher_name, 1, $id_parent, $title, $icon);

        $current_bc = $prev_bc;
    }

    public function renderHtmlContent()
    {
        $html = '';

        if (count($this->errors)) {
            return parent::renderHtml();
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html .= $this->renderListParamsInputs();

        // todo: implémenter l'utilisation des filtres (reload en js, etc.): 
//        if (!is_null($this->params['filters_panel'])) {
//            $html .= '<div class="row">';
//            $html .= '<div class="listFiltersPanelContainer col-xs-12 col-sm-12 col-md-2 col-lg-2"' . (!(int) $this->params['filters_panel_open'] ? ' style="display: none"' : '') . '>';
//            $html .= $this->renderFiltersPanel();
//            $html .= '</div>';
//            $html .= '<div class="objectlistCustomContainer col-xs-12 col-sm-12 col-md-10 col-lg-10">';
//        }

        $html .= '<div id="' . $this->identifier . '_ajax_content" class="list_custom_ajax_content">';
        $html .= $this->renderListContent();
        $html .= '</div>';

//        if (!is_null($this->params['filters_panel'])) {
//            $html .= '</div></div>';
//        }

        $html .= '<div class="ajaxResultContainer" id="' . $this->identifier . '_result"></div>';

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderListContent()
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        if (is_null($this->items)) {
            $this->fetchItems();
        }

        $html = '';

        $html .= '<div class="no_items_msg" style="' . ((is_array($this->items) && count($this->items)) ? 'display: none' : '') . '">';
        $html .= BimpRender::renderAlerts('Aucun ' . $this->object->getLabel() . 'disponible', 'warning');
        $html .= '</div>';

        $primary = $this->object->getPrimary();

        foreach ($this->items as $item) {
            $object = BimpCache::getBimpObjectInstance($this->object->module, $this->object->object_name, (int) $item[$primary]);
            if (BimpObject::objectLoaded($object)) {
                $value = '';

                if ($this->params['field_value'] === 'primary') {
                    if (isset($object->id)) {
                        $value = (int) $object->id;
                    } elseif ($object->field_exists($primary)) {
                        $value = (int) $object->getData($primary);
                    }
                } elseif ($object->field_exists($this->params['field_value'])) {
                    $value = $object->getData($this->params['field_value']);
                }

                $html .= '<div class="dispatcher_item available"';
                $html .= ' data-dispatcher_id="' . $this->identifier . '"';
                $html .= ' value="' . $value . '"';
                $html .= '>';

                $html .= '</div>';
            }
        }

        $current_bc = $prev_bc;
        return $html;
    }
}
