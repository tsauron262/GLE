<?php

class BC_ListViews extends BC_List
{

    public $component_name = 'Liste';
    public static $type = 'list_view';

    public function __construct(BimpObject $object, $name = 'default', $level = 1, $id_parent = null, $title = null, $icon = null)
    {
        $this->params_def['item_view'] = array('default' => 'default');
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

        if (is_null($this->params['icon']) || !$this->params['icon'] || $this->params['icon'] === 'fas_list') {
            $this->params['icon'] = 'fas_th-large';
        }

        $this->data['item_view_name'] = $this->params['item_view'];

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

        if (!count($this->errors)) {
            if (!$this->object->can("view")) {
                $this->errors[] = 'Vous n\'avez pas la permission de voir ' . $this->object->getLabel('the_plur');
            }
        }

        $current_bc = $prev_bc;
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
            return BimpRender::renderAlerts('Aucun élément trouvé', 'info');
        }

        $html .= $this->renderItemViews();

        return $html;
    }

    public function renderItemViews()
    {
        if (count($this->errors)) {
            return BimpRender::renderAlerts($this->errors);
        }

        if (is_null($this->config_path)) {
            $this->errors[] = 'Erreur d\'initialisation de la liste';
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

        $html = '';

        $html .= '<div class="objectViewContainer" style="display: none"></div>';

        $primary = $this->object->getPrimary();
        $controller = $this->object->getController();

        foreach ($this->items as $item) {
            $this->object->reset();
            if ($this->object->fetch((int) $item[$primary])) {
                $view = new BC_View($this->object, $this->params['item_view'], true, $this->level + 1);

                $html .= '<div class="' . $this->identifier . '_item';
                $html .= ' col-xs-' . $this->params['item_col_xs'];
                $html .= ' col-sm-' . $this->params['item_col_sm'];
                $html .= ' col-md-' . $this->params['item_col_md'];
                $html .= ' col-lg-' . $this->params['item_col_lg'] . '">';

                $item_footer = '';
                if ($this->params['view_btn'] || $this->params['edit_form']) {
                    $item_footer .= '<div style="text-align: right;">';
                    if ($this->params['edit_form']) {
                        $item_footer .= '<button type="button" class="btn btn-primary"';
                        $item_footer .= ' onclick="loadModalFormFromView(\'' . $view->identifier . '\', \'' . $this->params['edit_form'] . '\', $(this));"';
                        $item_footer .= '><i class="fas fa5-edit iconLeft"></i>Editer</button>';
                    }
                    if ($this->params['view_btn']) {
                        $item_footer .= '<div class="btn-group">';
                        if ($this->params['item_inline_view']) {
                            $item_footer .= '<button type="button" class="btn btn-default bs-popover" onclick="';
                            $item_footer .= 'displayObjectView($(\'#' . $this->identifier . '\').find(\'.objectViewContainer\'), ';
                            $item_footer .= '\'' . $this->object->module . '\', \'' . $this->object->object_name . '\', \'' . $this->params['item_inline_view'] . '\', ' . $this->object->id;
                            $item_footer .= ');';
                            $item_footer .= '"';
                            $item_footer .= ' data-toggle="popover"';
                            $item_footer .= ' data-trigger="hover"';
                            $item_footer .= ' data-container="body"';
                            $item_footer .= ' data-content="Afficher les données"';
                            $item_footer .= ' data-placement="top"';
                            $item_footer .= '><i class="far fa5-file iconLeft"></i>Afficher</button>';
                        }
                        if ($this->params['item_modal_view']) {
                            $item_footer .= '<button type="button" class="btn btn-default bs-popover" onclick="';
                            $item_footer .= 'loadModalView(\'' . $this->object->module . '\', \'' . $this->object->object_name . '\', ' . $this->object->id . ',\'' . $this->params['item_modal_view'] . '\', $(this));"';
                            $item_footer .= ' data-toggle="popover"';
                            $item_footer .= ' data-trigger="hover"';
                            $item_footer .= ' data-container="body"';
                            $item_footer .= ' data-content="Vue rapide"';
                            $item_footer .= ' data-placement="top"';
                            $item_footer .= '><i class="far fa5-eye"></i>';
                            $item_footer .= '</button>';
                        }
                        if ($controller) {
                            $url = DOL_URL_ROOT . '/' . $this->object->module . '/index.php?fc=' . $controller . '&id=' . $this->object->id;
                            $item_footer .= '<a class="btn btn-default" href="' . $url . '" target="_blank" title="Afficher dans une nouvel onglet">';
                            $item_footer .= '<i class="fas fa5-external-link-alt"></i>';
                            $item_footer .= '</a>';
                        }
                        $item_footer .= '</div>';
                    }
                    $item_footer .= '</div>';
                }
                $html .= BimpRender::renderPanel($this->object->getInstanceName(), $view->renderHtml(), $item_footer);
                $html .= '</div>';
                unset($view);
                $view = null;
            }
        }

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderPagination()
    {
        $html = '';
        if (!is_null($this->nbItems)) {
            if (($this->n > 0) && ($this->n < $this->nbItems)) {
                $first = $this->p - 4;
                if ($first < 1) {
                    $first = 1;
                }
                $last = $first + 9;
                if ($last > $this->nbTotalPages) {
                    $last = $this->nbTotalPages;
                }

                $html .= '<span class="navButton prevButton' . (((int) $this->p === 1) ? ' disabled' : '') . '">Précédent</span>';
                $html .= '<div class="pages">';

                if ($first !== 1) {
                    $html .= '<span class="pageBtn' . (((int) $this->p === 1) ? ' active' : '') . '" data-p="1">1</span>';
                }

                $current = $first;
                while ($current <= $last) {
                    if ($current !== 1) {
                        $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;';
                    }
                    $html .= '<span class="pageBtn' . (((int) $current === (int) $this->p) ? ' active' : '') . '" data-p="' . $current . '">' . $current . '</span>';
                    $current++;
                }

                if ($last !== $this->nbTotalPages) {
                    $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;<span class="pageBtn' . (((int) $this->p === (int) $this->nbTotalPages) ? ' active' : '') . '" data-p="' . $this->nbTotalPages . '">' . $this->nbTotalPages . '</span>';
                }

                $html .= '</div>';
                $html .= '<span class="navButton nextButton' . (((int) $this->p >= $this->nbTotalPages) ? ' disabled' : '') . '">Suivant</span>';
            }
        }

        return $html;
    }
}
