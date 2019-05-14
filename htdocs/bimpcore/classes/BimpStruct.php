<?php

class BimpStruct
{

    public static function renderStruct(BimpConfig $config, $path, &$parent_component = null)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);
        $type = $config->get($path . '/type', '', true);
        $show = (int) $config->get($path . '/show', 1, false, 'bool');

        if (!$show) {
            return '';
        }

        switch ($type) {
            case 'page':
                $html = self::renderObjectPage($config, $path . '/page', $parent_component);
                break;

            case 'object_header':
//                if ($config->isDefined($path . '/object_header')) {
                $html = self::renderObjectHeader($config, $path . '/object_header', $parent_component);
//                } else {
//                    // logError
//                }
                break;

            case 'view':
                if ($config->isDefined($path . '/view')) {
                    $html = self::renderView($config, $path . '/view', $parent_component);
                } else {
                    // logError
                }
                break;

            case 'list':
                if ($config->isDefined($path . '/list')) {
                    $html = self::renderList($config, $path . '/list', $parent_component);
                } else {
                    // logError
                }
                break;

            case 'list_custom':
                if ($config->isDefined($path . '/list_custom')) {
                    $html = self::renderListCustom($config, $path . '/list_custom', $parent_component);
                } else {
                    // logError
                }
                break;

            case 'stats_list':
                if ($config->isDefined($path . '/stats_list')) {
                    $html = self::renderStatsList($config, $path . '/stats_list', $parent_component);
                } else {
                    // logError
                }
                break;

            case 'form':
                if ($config->isDefined($path . '/form')) {
                    $html = self::renderForm($config, $path . '/form', $parent_component);
                } else {
                    // logError
                }
                break;

            case 'views_list':
                if ($config->isDefined($path . '/views_list')) {
                    $html = self::renderViewsList($config, $path . '/views_list', $parent_component);
                } else {
                    // logError
                }
                break;

            case 'fields_table':
                if ($config->isDefined($path . '/fields_table')) {
                    $html = self::renderFieldsTable($config, $path . '/fields_table', $parent_component);
                } else {
                    // logError
                }
                break;

            case 'object_common_fields':
                $object = $config->getFromCurrentPath('object', $config->instance, false, 'object');
                if (!is_null($object) && is_a($object, 'BimpObject')) {
                    $html .= $object->renderCommonFields();
                }
                break;

            case 'rows':
                if ($config->isDefined($path . '/rows')) {
                    $html = self::renderRows($config, $path . '/rows', $parent_component);
                } else {
                    // logError
                }
                break;

            case 'nav_tabs':
                if ($config->isDefined($path . '/nav_tabs')) {
                    $html = self::renderNavTabs($config, $path . '/nav_tabs', $parent_component);
                } else {
                    // logError
                }
                break;

            case 'panel':
                if ($config->isDefined($path . '/panel')) {
                    $html = self::renderPanel($config, $path . '/panel', $parent_component);
                } else {
                    // logError
                }
                break;

            case 'media':
                if ($config->isDefined($path . '/media')) {
                    $html = self::renderMedia($config, $path . '/media', $parent_component);
                } else {
                    // logError
                }
                break;

            case 'notes':
                if ($config->isDefined($path . '/notes')) {
                    $html = self::renderNotes($config, $path . '/notes', $parent_component);
                }
                break;

            case 'button':
                if ($config->isDefined($path . '/button')) {
                    $html = self::renderButton($config, $path . '/button', $parent_component);
                } else {
                    // logError
                }
                break;

            case 'custom':
                if ($config->isDefined($path . '/custom')) {
                    $html = $config->get($path . '/custom', '', true);
                } else {
                    // logError
                }
                break;
        }
        $config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderObjectPage(BimpConfig $config, $path, &$parent_component = null)
    {
        $html = '';

        if ($config->isDefined($path . '/object')) {
            $object = $config->getObject($path . '/object');
        } else {
            $object = $config->instance;
        }

        if (is_null($object) || !is_a($object, 'BimpObject')) {
            $html .= BimpRender::renderAlerts('Objet non trouvé');
        } else {
            $name = $config->get($path . '/name', 'default');
            $page = new BC_Page($object, $name, false);

            $html .= $page->renderHtml();
        }

        return $html;
    }

    public static function renderObjectHeader(BimpConfig $config, $path, &$parent_component = null)
    {
        $html = '';

        if ($config->isDefined($path . '/object')) {
            $object = $config->getObject($path . '/object');
        } else {
            $object = $config->instance;
        }

        if (!is_null($object) && is_a($object, 'BimpObject')) {
            $html .= $object->renderHeader();
        } else {
            $html .= BimpRender::renderAlerts('Objet non trouvé');
        }

        return $html;
    }

    public static function renderView(BimpConfig $config, $path, &$parent_component = null)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);

        if ($config->isDefined($path . '/object')) {
            $object = $config->getObject($path . '/object');
        } else {
            $object = $config->instance;
        }
        if (!is_null($object)) {
            $name = $config->getFromCurrentPath('name', 'default');
            $panel = $config->getFromCurrentPath('panel', 0, false, 'bool');
            $html = $object->renderView($name, $panel);
        } else {
            $html = BimpRender::renderAlerts('Objet non trouvé');
        }

        $config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderList(BimpConfig $config, $path, &$parent_component = null)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);

        $object = $config->getObject($path . '/object');

        if (is_null($object)) {
            if (is_a($config->instance, 'BimpObject')) {
                $object = $config->instance;
            }
        }

        if (!is_null($object)) {
            $name = $config->getFromCurrentPath('name', 'default');
            $panel = $config->getFromCurrentPath('panel', 1, false, 'bool');
            $title = $config->getFromCurrentPath('title', null);
            $icon = $config->getFromCurrentPath('icon', null);
            $children = $config->getFromCurrentPath('children', null);
            $association = $config->getFromCurrentPath('association', null);

            if (!is_null($children)) {
                $html = $object->renderChildrenList($children, $name, $panel, $title, $icon);
            } elseif (!is_null($association)) {
                $html = $object->renderAssociatesList($association, $name, $panel, $title, $icon);
            } else {
                $filters = $config->getFromCurrentPath('filters', array(), false, 'array');
                $html = $object->renderList($name, $panel, $title, $icon, $filters);
            }
        }

        $config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderListCustom(BimpConfig $config, $path, &$parent_component = null)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);

        $object = $config->getObject($path . '/object');

        if (is_null($object)) {
            if (is_a($config->instance, 'BimpObject')) {
                $object = $config->instance;
            }
        }

        if (!is_null($object)) {
            $name = $config->getFromCurrentPath('name', 'default');
            $title = $config->getFromCurrentPath('title', null);
            $icon = $config->getFromCurrentPath('icon', null);

            $html = $object->renderListCustom($name, $title, $icon);
        }

        $config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderStatsList(BimpConfig $config, $path, &$parent_component = null)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);

        $object = $config->getObject($path . '/object');

        if (is_null($object)) {
            if (is_a($config->instance, 'BimpObject')) {
                $object = $config->instance;
            }
        }

        if (!is_null($object)) {
            $name = $config->getFromCurrentPath('name', 'default');
            $title = $config->getFromCurrentPath('title', null);
            $icon = $config->getFromCurrentPath('icon', null);

            $html = $object->renderListCustom($name, $title, $icon);
        }

        $config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderForm(BimpConfig $config, $path, &$parent_component = null)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);

        $object = $config->getObject($path . '/object');

        if (!is_null($object)) {
            $name = $config->getFromCurrentPath('name', 'default');
            $panel = $config->getFromCurrentPath('panel', 0, false, 'bool');
            $html = $object->renderForm($name, $panel);
        }

        $config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderViewsList(BimpConfig $config, $path, &$parent_component = null)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);

        $object = $config->getObject($path . '/object');

        if (!is_null($object)) {
            $name = $config->getFromCurrentPath('name', 'default');
            $panel = $config->getFromCurrentPath('panel', 0, false, 'bool');
            $html = $object->renderViewsList($name, $panel);
        }

        $config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderFieldsTable(BimpConfig $config, $path, &$parent_component = null)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);

        $object = $config->getObject($path . '/object');

        if (is_null($object)) {
            $object = $config->instance;
        }

        $panel = (int) $config->getFromCurrentPath('panel', 1, false, 'bool');

        $table = new BC_FieldsTable($object, $path, !$panel);

        if (!is_null($parent_component)) {
            if (is_a($parent_component, 'BC_View')) {
                if (count($parent_component->new_values)) {
                    $table->setNewValues($parent_component->new_values);
                }
            }
        }

        $html .= $table->renderHtml();
        unset($table);


        $config->setCurrentPath($prev_path);

        return $html;
    }

    public static function renderRows(BimpConfig $config, $path, &$parent_component = null)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);

        $rows = $config->getFromCurrentPath('', array(), true, 'array');

        foreach ($rows as $idx_row => $row) {
            $config->setCurrentPath($path);
            $html .= '<div class="row">';
            $cols = $config->getFromCurrentPath($idx_row . '/cols', array(), true, 'array');
            foreach ($cols as $idx_col => $col) {
                $col_path = $path . '/' . $idx_row . '/cols/' . $idx_col;
                $config->setCurrentPath($col_path);

                $show = (int) $config->getFromCurrentPath('show', 1, false, 'bool');
                if (!$show) {
                    continue;
                }

                $col_lg = $config->getFromCurrentPath('col_lg', 12, false, 'int');
                $col_md = $config->getFromCurrentPath('col_md', null, false, 'int');
                $col_sm = $config->getFromCurrentPath('col_sm', null, false, 'int');
                $col_xs = $config->getFromCurrentPath('col_xs', null, false, 'int');

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
                $html .= '<div class="col-xs-' . $col_xs . ' col-sm-' . $col_sm . ' col-md-' . $col_md . ' col-lg-' . $col_lg . '">';
                if ($config->isDefinedCurrent('struct')) {
                    $html .= self::renderStruct($config, $col_path . '/struct', $parent_component);
                } else {
                    $html .= self::renderStruct($config, $col_path, $parent_component);
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderNavTabs(BimpConfig $config, $path, &$parent_component = null)
    {
        $tabs = array();

        if ($config->isDefined($path . '/tabs')) {
            $tabs_id = $config->get($path . '/tabs_id', 'maintabs');
            $nav_tabs = $config->getParams($path . '/tabs');
        } else {
            $nav_tabs = $config->getParams($path);
            $tabs_id = 'maintabs';
        }
        foreach ($nav_tabs as $idx => $nav_tab) {
            if (!(int) $config->get($path . '/' . $idx . '/show', 1, false, 'bool')) {
                continue;
            }
            $content = '';
            if ($config->isDefined($path . '/' . $idx . '/struct')) {
                $content = self::renderStruct($config, $path . '/' . $idx . '/struct');
            } elseif ($config->isDefined($path . '/' . $idx . '/content')) {
                $content = $config->get($path . '/' . $idx . '/content');
            }
            $tabs[] = array(
                'id'      => $config->get($path . '/' . $idx . '/id', '', true),
                'title'   => $config->get($path . '/' . $idx . '/title', '', true),
                'content' => $content
            );
        }
        return BimpRender::renderNavTabs($tabs, $tabs_id);
    }

    public static function renderPanel(BimpConfig $config, $path, &$parent_component = null)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);

        $title = $config->getFromCurrentPath('title', '');
        $icon = $config->getFromCurrentPath('icon', '');
        $type = $config->getFromCurrentPath('type', 'default');
        $header_buttons = $config->getFromCurrentPath('header_buttons', array(), false, 'array');
        $footer_buttons = $config->getFromCurrentPath('footer_buttons', array(), false, 'array');

        $footer_html = '';
        foreach ($footer_buttons as $idx => $params) {
            $footer_html = self::renderButton($config, $path . '/footer_buttons/' . $idx);
        }

        $content = self::renderStruct($config, $path . '/content');

        $html .= BimpRender::renderPanel($title, $content, $footer_html, array(
                    'icon'           => $icon,
                    'type'           => $type,
                    'header_buttons' => $header_buttons
        ));

        $config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderMedia(BimpConfig $config, $path, &$parent_component = null)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);



        $config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderNotes(BimpConfig $config, $path, &$parent_component = null)
    {
        $object = $config->getObject($path . '/object');

        $filter_by_user = (int) $config->get($path . '/filter_by_user', 1, false, 'bool');
        if (!is_null($object) && is_a($object, 'BimpObject')) {
            return $object->renderNotesList($filter_by_user);
        }

        return BimpRender::renderAlerts('Erreur de configuration, impossible d\'afficher la liste des notes (objet invalide)');
    }

    public static function renderButton(BimpConfig $config, $path, &$parent_component = null)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);

        $type = $config->getFromCurrentPath('type', 'button');

        switch ($type) {
            case 'button':
                $html .= BimpRender::renderButtonFromConfig($config, $path);
                break;

            case 'dropdown':
                $label = $config->getFromCurrentPath('label', '');
                $params = array(
                    'type'  => $config->getFromCurrentPath('type', 'default'),
                    'icon'  => $config->getFromCurrentPath('icon', ''),
                    'caret' => $config->getFromCurrentPath('caret', 1, false, 'bool')
                );
                $items = $config->getFromCurrentPath('items', array(), true, 'array');
                $items_html = array();
                foreach ($items as $idx => $item) {
                    $items_html[] = self::renderButton($config, $path . '/items/' . $idx);
                }

                $html .= BimpRender::renderDropDownButton($label, $items, $params);
                break;

            case 'separator':
                $html .= 'separator';
                break;
        }

        $config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderCustom(BimpConfig $config, $path, &$parent_component = null)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);

        $html = $config->getFromCurrentPath('', '', true);

        $config->setCurrentPath($prev_path);
        return $html;
    }
}
