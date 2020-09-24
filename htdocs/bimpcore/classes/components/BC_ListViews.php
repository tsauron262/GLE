<?php

class BC_ListViews extends BC_List
{

    public $component_name = 'Liste';
    public static $type = 'list_view';

    public function __construct(BimpObject $object, $name = 'default', $level = 1, $id_parent = null, $title = null, $icon = null)
    {
        $this->params_def['component_type'] = array('default' => 'view', 'allowed' => array('view', 'card'));
        $this->params_def['component_name'] = array('default' => 'default');
//        $this->params_def['item_view'] = array('default' => 'default');
        $this->params_def['view_btn'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['edit_form'] = array('default' => '');
        $this->params_def['item_modal_view'] = array('default' => '');
        $this->params_def['item_inline_view'] = array('default' => '');

        $this->params_def['item_col_lg'] = array('data_type' => 'int', 'default' => 12);
        $this->params_def['item_col_md'] = array('data_type' => 'int');
        $this->params_def['item_col_sm'] = array('data_type' => 'int');
        $this->params_def['item_col_xs'] = array('data_type' => 'int');

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $path = null;

        if (!$name || $name === 'default') {
            if ($object->config->isDefined('views_list')) {
                $path = 'views_list';
            } elseif ($object->config->isDefined('views_lists/default')) {
                $path = 'views_lists';
                $name = 'default';
            }
        } else {
            $path = 'views_lists';
        }

        parent::__construct($object, $path, $name, $level, $id_parent, $title, $icon);

        if ($this->isOk()) {
            if (!$this->object->can("view")) {
                $this->errors[] = 'Vous n\'avez pas la permission de voir ' . $this->object->getLabel('the_plur');
            }

            if (is_null($this->params['icon']) || !$this->params['icon'] || $this->params['icon'] === 'fas_list') {
                $this->params['icon'] = 'fas_th-large';
            }

            if (is_null($this->params['item_col_lg'])) {
                $this->params['item_col_lg'] = 12;
            }
            if ($this->params['item_col_lg'] > 12) {
                $this->params['item_col_lg'] = 12;
            }

            if (is_null($this->params['item_col_md'])) {
                $this->params['item_col_md'] = $this->params['item_col_lg'];
            }

            if (is_null($this->params['item_col_sm'])) {
                $this->params['item_col_sm'] = ($this->params['item_col_md'] * 2);
                if ($this->params['item_col_sm'] > 12) {
                    $this->params['item_col_sm'] = 12;
                }
            }
            if (is_null($this->params['item_col_xs'])) {
                $this->params['item_col_xs'] = $this->params['item_col_sm'] * 2;
                if ($this->params['item_col_xs'] > 12) {
                    $this->params['item_col_xs'] = 12;
                }
            }
        }

        $current_bc = $prev_bc;
    }

    public function renderHtml()
    {
        if ((int) $this->params['panel']) {
            if (!isset($this->params['header_icons']) || !is_array($this->params['header_icons'])) {
                $this->params['header_icons'] = array();
            }

            $this->params['header_icons'][] = array(
                'label'   => 'Actualiser la liste',
                'icon'    => 'fas_redo',
                'onclick' => 'reloadObjectViewsList(\'' . $this->identifier . '\');'
            );
        }

        return parent::renderHtml();
    }

    public function renderHtmlContent()
    {
        $html = parent::renderHtmlContent();

        if (!$this->isOk()) {
            return $html;
        }

        if (is_null($this->items)) {
            $this->fetchItems();
        }

        if (!count($this->items)) {
            return BimpRender::renderAlerts('Aucun' . $this->object->e() . ' ' . $this->object->getLabel() . ' trouvé' . $this->object->e(), 'info');
        }

        $html .= $this->renderItemViews();

        if ((int) $this->params['pagination']) {
            $html .= '<div id="' . $this->identifier . '_pagination" class="listPagination" data-views_list_id="' . $this->identifier . '">';
            $html .= $this->renderPagination();
            $html .= '</div>';
        }

        return $html;
    }

    public function renderItemViews()
    {
        if (count($this->errors)) {
            return BimpRender::renderAlerts($this->errors);
        }

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        if (is_null($this->items)) {
            $this->fetchItems();
        }

        $primary = $this->object->getPrimary();
        $controller = $this->object->getController();
        $object = null;

        $html = '';

        $html .= '<div class="objectViewContainer" style="display: none"></div>';

        if ((int) $this->params['pagination']) {
            $html .= '<div id="' . $this->identifier . '_pagination" class="listPagination" data-views_list_id="' . $this->identifier . '">';
            $html .= $this->renderPagination();
            $html .= '</div>';
        }

        $html .= '<div class="row">';

        foreach ($this->items as $item) {
            $object = BimpCache::getBimpObjectInstance($this->object->module, $this->object->object_name, (int) $item[$primary]);
            if (BimpObject::objectLoaded($object)) {

                $html .= '<div class="' . $this->identifier . '_item';
                $html .= ' col-xs-' . $this->params['item_col_xs'];
                $html .= ' col-sm-' . $this->params['item_col_sm'];
                $html .= ' col-md-' . $this->params['item_col_md'];
                $html .= ' col-lg-' . $this->params['item_col_lg'] . '">';

                $item_footer = '';
                if ($this->params['view_btn'] || $this->params['edit_form']) {
                    $item_footer .= '<div style="text-align: right;">';
                    if ($this->params['edit_form']) {
                        $onclick = $object->getJsLoadModalForm($this->params['edit_form'], 'Edition ' . $object->display());
                        $item_footer .= '<button type="button" class="btn btn-primary" onclick="' . $onclick . '">';
                        $item_footer .= BimpRender::renderIcon('fas_edit', 'iconLeft') . 'Editer';
                        $item_footer .= '</button>';
                    }
                    if ($this->params['view_btn']) {
                        $item_footer .= '<div class="btn-group">';
                        if ($this->params['item_inline_view']) {
                            $item_footer .= '<button type="button" class="btn btn-default bs-popover" onclick="';
                            $item_footer .= 'displayObjectView($(\'#' . $this->identifier . '\').find(\'.objectViewContainer\'), ';
                            $item_footer .= '\'' . $object->module . '\', \'' . $object->object_name . '\', \'' . $this->params['item_inline_view'] . '\', ' . $object->id;
                            $item_footer .= ');';
                            $item_footer .= '"';
                            $item_footer .= BimpRender::renderPopoverData('Afficher les données') . '>';
                            $item_footer .= BimpRender::renderIcon('fas_file-alt', 'iconLeft') . 'Afficher';
                            $item_footer .= '</button>';
                        }
                        if ($this->params['item_modal_view']) {
                            $onclick = $object->getJsLoadModalView($this->params['item_modal_view'], $object->display());
                            $item_footer .= '<button type="button" class="btn btn-default bs-popover" onclick="' . $onclick . '"';
                            $item_footer .= BimpRender::renderPopoverData('Vue rapide') . '>';
                            $item_footer .= BimpRender::renderIcon('fas_eye');
                            $item_footer .= '</button>';
                        }
                        if ($controller) {
                            $url = $object->getUrl();
                            if ($url) {
                                $item_footer .= '<a class="btn btn-default" href="' . $url . '" target="_blank" class="bs-popover"';
                                $item_footer .= BimpRender::renderPopoverData('Afficher dans une nouvel onglet') . '>';
                                $item_footer .= BimpRender::renderIcon('fas_external-link-alt');
                                $item_footer .= '</a>';
                            }
                        }
                        $item_footer .= '</div>';
                    }
                    $item_footer .= '</div>';
                }

                switch ($this->params['component_type']) {
                    case 'card':
                        $component = new BC_Card($object, null, $this->params['component_name']);
                        break;

                    case 'view':
                    default:
                        $component = new BC_View($object, $this->params['component_name'], true, $this->level + 1);
                        break;
                }

                $html .= BimpRender::renderPanel($object->display(), $component->renderHtml(), $item_footer);
                $html .= '</div>';
                unset($component);
                $component = null;
            }
        }

        $html .= '</div>';

        if ((int) $this->params['pagination']) {
            $html .= '<div id="' . $this->identifier . '_pagination" class="listPagination" data-views_list_id="' . $this->identifier . '">';
            $html .= $this->renderPagination();
            $html .= '</div>';
        }

        $current_bc = $prev_bc;
        return $html;
    }
}
