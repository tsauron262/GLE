<?php

class BimpViewsList
{

    public $object = null;
    public $id_parent = null;
    public $view_name = null;
    public $views_list_name = null;
    public $views_list_identifier = null;
    public $views_list_path = null;
    protected $items = null;
    protected $filters = array();
    protected $sort_field = null;
    protected $sort_way = null;
    protected $sort_option = '';
    protected $n = 1;
    protected $p = 1;
    protected $nbTotalPages = 1;
    protected $nbItems = null;
    public $errors = array();

    public function __construct(BimpObject $object, $views_list_name = 'default', $id_parent = null)
    {
        $this->object = $object;
        $this->id_parent = $id_parent;
        $this->views_list_name = $views_list_name;

        if (is_null($this->id_parent)) {
            $parent_id_property = $object->getParentIdProperty();
            if (!is_null($parent_id_property)) {
                if (BimpTools::isSubmit($parent_id_property)) {
                    $this->id_parent = BimpTools::getValue($parent_id_property, null);
                }
            }
        }

        if ($this->object->config->isDefined('views_lists/' . $views_list_name)) {
            $this->views_list_path = 'views_lists/' . $views_list_name;
            $this->object->config->setCurrentPath($this->views_list_path);
        } elseif (($views_list_name === 'default') && $this->object->config->isDefined('views_list')) {
            $this->views_list_path = 'views_list';
            $this->object->config->setCurrentPath($this->views_list_path);
        } else {
            $this->errors[] = 'Liste de vues "' . $views_list_name . '" non définie dans le fichier de configuration';
        }

        if (!count($this->errors) && !is_null($this->views_list_path)) {
            $this->views_list_identifier = $this->object->object_name . '_' . $views_list_name . '_views_list';
            $this->fetchlistParams();
            $this->fetchFilters();
            $this->fetchItems();
        }
    }

    public function setConfPath($path = '')
    {
        return $this->object->config->setCurrentPath($this->views_list_path . '/' . $path);
    }

    protected function fetchlistParams()
    {
        $this->view_name = $this->object->getCurrentConf('view', 'default');
        $this->n = BimpTools::getValue('n', $this->object->getCurrentConf('n', 0, false, 'int'));
        $this->p = BimpTools::getValue('p', 1);
        $this->sort_field = $this->object->getCurrentConf('sort_field', 'date_creata');
        $this->sort_way = $this->object->getCurrentConf('sort_way', 'DESC');
        $this->sort_option = $this->object->getCurrentConf('sort_option', '');
    }

    protected function fetchFilters()
    {
        $this->filters = array();
    }

    protected function fetchItems()
    {
        if (is_null($this->views_list_path) || count($this->errors)) {
            $this->setConfPath();
            $this->items = array();
            return;
        }

        $primary = $this->object->getPrimary();

        // Filtres: 
        $filters = $this->filters;
        if (!is_null($this->id_parent)) {
            $parent_id_property = $this->object->getParentIdProperty();
            $filters[$parent_id_property] = $this->id_parent;
        }

        // Jointures: 
        $joins = array();

        // Trie: 
        $order_by = $primary;

        if (!is_null($this->sort_field)) {
            if ($this->setConfPath('fields/' . $this->sort_field)) {
                $order_by = '';
                if ($this->sort_option) {
                    $sort_option_path = 'fields/' . $this->sort_field . '/sort_options/' . $this->sort_option;
                    if ($this->object->config->isDefined($sort_option_path)) {
                        $join_field = $this->object->getConf($sort_option_path . '/join_field', '');
                        if ($join_field && $this->object->config->isDefined('fields/' . $field . '/object')) {
                            $object = $this->object->config->getObject('fields/' . $field . '/object');
                            if (!is_null($object)) {
                                $table = BimpTools::getObjectTable($this->object, $field, $object);
                                $field_on = BimpTools::getObjectPrimary($this->object, $field, $object);
                                if (!is_null($table) && !is_null($field_on)) {
                                    $order_by = $table . '.' . $join_field;
                                    $joins[] = array(
                                        'alias' => $table,
                                        'table' => $table,
                                        'on'    => $table . '.' . $field_on . ' = a.' . $field
                                    );
                                }
                            }
                        }
                    }
                }

                if (!$order_by) {
                    $order_by = $this->sort_field;
                }
            }

            $this->setConfPath();
        }

        $this->nbItems = $this->object->getListCount($filters, $joins);

        if ($this->n > 0) {
            $this->nbTotalPages = (int) ceil($this->nbItems / $this->n);
            if ($this->p > $this->nbTotalPages) {
                $this->p = $this->nbTotalPages;
            }
        } else {
            $this->nbTotalPages = 1;
            $this->p = 1;
        }

        $this->items = $this->object->getList($filters, $this->n, $this->p, $order_by, $this->sort_way, 'array', array(
            $primary
                ), $joins);
    }

    public function render($panel)
    {
        if (count($this->errors)) {
            return BimpRender::renderAlerts($this->errors);
        }

        if (is_null($this->views_list_path)) {
            $this->errors[] = 'Erreur d\'initialisation de la liste';
            return BimpRender::renderAlerts($this->errors);
        }

        if (!count($this->items)) {
            return '';
        }

        $html = '';
        $this->setConfPath();

        $labels = $this->object->getLabels();

        $html = '<script type="text/javascript">';
        $html .= 'object_labels[\'' . $this->object->object_name . '\'] = ' . json_encode($labels);
        $html .= '</script>';

        $html .= '<div id="' . $this->views_list_identifier . '_container" class="' . ($panel ? 'section ' : '') . 'viewsListContainer ' . $this->object->object_name . '_viewsListContainer">';

        $content = '';
        $content .= '<div id="' . $this->views_list_identifier . '" class="row objectViewslist ' . $this->object->object_name . '_views_list objectList ' . $this->object->object_name . '_list"';
        $content .= ' data-module_name="' . $this->object->module . '"';
        $content .= ' data-object_name="' . $this->object->object_name . '"';
        $content .= ' data-views_list_name="' . $this->views_list_name . '"';
        $content .= ' data-item_view_name="' . $this->view_name . '"';
        $content .= '>';
        $content .= '<div class="objectViewContainer" style="display: none"></div>';
        $content .= $this->renderItemViews();
        $content .= '</div>';

        if ($panel) {
            $this->setConfPath();
            $title = $this->object->getCurrentConf('title', '');
            $icon = $this->object->getCurrentConf('icon', 'bars');
            $add_btn = $this->object->getCurrentConf('add_btn', 0, false, 'bool');

            $params = array(
                'type' => 'secondary',
                'icon' => $icon
            );

            if ($add_btn) {
                $params['header_buttons'] = array(
                    array(
                        'classes'     => array('btn', 'btn-default'),
                        'label'       => 'Ajouter ' . $labels['a'],
                        'icon_before' => 'plus-circle',
                        'attr'        => array(
                            'type'    => 'button',
                            'onclick' => 'loadModalFormFromList(\'' . $this->views_list_identifier . '\', \'default\', $(this), 0, ' . (!is_null($this->id_parent) ? $this->id_parent : 0) . ')'
                        )
                    )
                );
            }

            $html .= BimpRender::renderPanel($title, $content, '', $params);
        } else {
            $html .= $content;
        }

        $html .= '</div>';

        return $html;
    }

    public function renderItemViews()
    {
        if (count($this->errors)) {
            return BimpRender::renderAlerts($this->errors);
        }

        if (is_null($this->views_list_path)) {
            $this->errors[] = 'Erreur d\'initialisation de la liste';
            return BimpRender::renderAlerts($this->errors);
        }

        if (!count($this->items)) {
            return '';
        }

        $html = '';
        $this->setConfPath();

        $primary = $this->object->getPrimary();
        $controller = $this->object->getController();

        $view_btn = $this->object->getCurrentConf('view_btn', 0, false, 'bool');
        $edit_form = $this->object->getCurrentConf('edit_form', '');
        $item_modal_view = $this->object->getCurrentConf('item_modal_view', '');
        $item_inline_view = $this->object->getCurrentConf('item_inline_view', '');

        $col_lg = $this->object->getCurrentConf('item_col_lg', 12, false, 'int');
        $col_md = $this->object->getCurrentConf('item_col_md', null, false, 'int');
        $col_sm = $this->object->getCurrentConf('item_col_sm', null, false, 'int');
        $col_xs = $this->object->getCurrentConf('item_col_xs', null, false, 'int');

        if (!is_int($col_lg)) {
            $col_lg = 12;
        }
        if ($col_lg > 12) {
            $col_lg = 12;
        }

        if (is_null($col_md)) {
            $col_md = $col_lg;
        }

        if (is_null($col_sm)) {
            $col_sm = ($col_md * 2);
            if ($col_sm > 12) {
                $col_sm = 12;
            }
        }
        if (is_null($col_xs)) {
            $col_xs = $col_sm * 2;
            if ($col_xs > 12) {
                $col_xs = 12;
            }
        }

        foreach ($this->items as $item) {
            $this->object->reset();
            if ($this->object->fetch((int) $item[$primary])) {
                $view = new BimpView($this->object, $this->view_name);
                $html .= '<div class="' . $this->views_list_identifier . '_item col-xs-' . $col_xs . ' col-sm-' . $col_sm . ' col-md-' . $col_md . ' col-lg-' . $col_lg . '">';
                $item_footer = '';
                if ($view_btn || $edit_form) {
                    $item_footer .= '<div style="text-align: right;">';
                    if ($edit_form) {
                        $item_footer .= '<button type="button" class="btn btn-primary"';
                        $item_footer .= ' onclick="loadModalFormFromView(\'' . $view->view_identifier . '\', \'' . $edit_form . '\', $(this));"';
                        $item_footer .= '><i class="fa fa-edit iconLeft"></i>Editer</button>';
                    }
                    if ($view_btn) {
                        $item_footer .= '<div class="btn-group">';
                        if ($item_inline_view) {
                            $item_footer .= '<button type="button" class="btn btn-default" onclick="';
                            $item_footer .= 'displayObjectView($(\'#' . $this->views_list_identifier . '\').find(\'.objectViewContainer\'), ';
                            $item_footer .= '\'' . $this->object->module . '\', \'' . $this->object->object_name . '\', \'' . $item_inline_view . '\', ' . $this->object->id;
                            $item_footer .= ');';
                            $item_footer .= '"';
                            $item_footer .= ' data-toggle="popover"';
                            $item_footer .= ' data-trigger="hover"';
                            $item_footer .= ' data-container="body"';
                            $item_footer .= ' data-content="Afficher les données"';
                            $item_footer .= '><i class="fa fa-file-o iconLeft"></i>Afficher</button>';
                        }
                        if ($item_modal_view) {
                            $item_footer .= '<button type="button" class="btn btn-default" onclick="';
                            $item_footer .= 'loadModalView(\'' . $view->view_identifier . '\', \'' . $item_modal_view . '\', $(this));';
                            $item_footer .= '"><i class="fa fa-eye iconleft"></i>';
                            if (!$item_inline_view) {
                                $html .= 'Afficher';
                            }
                            $html .= '</button>';
                        }
                        if ($controller) {
                            $url = DOL_URL_ROOT . '/' . $this->object->module . '/index.php?fc=' . $controller . '&id=' . $this->object->id;
                            $item_footer .= '<a class="btn btn-default" href="' . $url . '" target="_blank" title="Afficher dans une nouvel onglet">';
                            $item_footer .= '<i class="fa fa-external-link"></i>';
                            if (!$item_inline_view && !$item_modal_view) {
                                $html .= 'Afficher';
                            }
                            $html .= '</a>';
                        }
                        $item_footer .= '</div>';
                    }
                    $item_footer .= '</div>';
                }
                $html .= BimpRender::renderPanel($this->object->getInstanceName(), $view->render(), $item_footer);
                $html .= '</div>';
                unset($view);
                $view = null;
            }
        }

        return $html;
    }

    public function renderPagination()
    {
        $hide = (is_null($this->nbItems) || ($this->n <= 0) || ($this->n >= $this->nbItems));

        $html = '<div class="paginationContainer"' . ($hide ? ' style="display: none"' : '') . '>';
        $html .= '<div id="' . $this->views_list_identifier . '_pagination" class="listPagination">';

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

        $html .= '</div>';
        $html .= '</div>';

        $this->setConfPath();
        return $html;
    }
}
