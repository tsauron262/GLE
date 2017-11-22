<?php

class BimpStruct
{

    public static function renderStruct(BimpConfig $config, $path)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);
        $type = $config->get($path . '/type', '', true);
        switch ($type) {
            case 'view':
                if ($config->isDefined($path . '/view')) {
                    $html = self::renderView($config, $path . '/view');
                } else {
                    // logError
                }
                break;


            case 'list':
                if ($config->isDefined($path . '/list')) {
                    $html = self::renderList($config, $path . '/list');
                } else {
                    // logError
                }
                break;

            case 'form':
                if ($config->isDefined($path . '/form')) {
                    $html = self::renderForm($config, $path . '/form');
                } else {
                    // logError
                }
                break;

            case 'views_list':
                if ($config->isDefined($path . '/views_list')) {
                    $html = self::renderViewsList($config, $path . '/views_list');
                } else {
                    // logError
                }
                break;

            case 'fields_table':
                if ($config->isDefined($path . '/fields_table')) {
                    $html = self::renderFieldsTable($config, $path . '/fields_table');
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
                    $html = self::renderRows($config, $path . '/rows');
                } else {
                    // logError
                }
                break;

            case 'panel':
                if ($config->isDefined($path . '/panel')) {
                    $html = self::renderPanel($config, $path . '/panel');
                } else {
                    // logError
                }
                break;

            case 'media':
                if ($config->isDefined($path . '/media')) {
                    $html = self::renderMedia($config, $path . '/media');
                } else {
                    // logError
                }
                break;

            case 'button':
                if ($config->isDefined($path . '/button')) {
                    $html = self::renderButton($config, $path . '/button');
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

    public static function renderView(BimpConfig $config, $path)
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

    public static function renderList(BimpConfig $config, $path)
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
            $panel = $config->getFromCurrentPath('panel', 0, false, 'bool');
            $children = $config->getFromCurrentPath('children', null);
            if (!is_null($children)) {
                $html = $object->renderChildrenList($children, $name, $panel);
            } else {
                $html = $object->renderList($name, $panel);
            }
        }

        $config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderForm(BimpConfig $config, $path)
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

    public static function renderViewsList(BimpConfig $config, $path)
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

    public static function renderFieldsTable(BimpConfig $config, $path)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);

        $object = $config->getFromCurrentPath('object', $config->instance, true, 'object');

        if (is_null($object) || !is_a($object, 'BimpObject')) {
            return $html;
        }

        $caption = $config->getFromCurrentPath('caption', null, false, 'any');

        $html = '<table class="objectViewtable">';
        $html .= '<thead>';
        if ($caption) {
            if (is_array($caption)) {
                $label = $config->getFromCurrentPath('caption/label', '');
                $icon = $config->getFromCurrentPath('caption/icon', '');
            } elseif (is_string($caption)) {
                $label = $caption;
                $icon = '';
            }
            $html .= '<tr><th colspan="2">';
            if ($icon) {
                $html .= '<i class="fa fa-' . $icon . ' iconLeft"></i>';
            }
            if ($label) {
                $html .= $label;
            }
            $html .= '</th></tr>';
        }
        $html .= '</thead>';
        $html .= '<tbody>';

        $rows = $config->getFromCurrentPath('rows', array(), true, 'array');
        foreach ($rows as $idx => $row) {
            $row_path = $path . '/rows/' . $idx;
            $config->setCurrentPath($row_path);

            $field = $config->getFromCurrentPath('field', '');
            $html .= '<tr>';
            if ($field) {
                $html .= '<th>';
                $html .= $object->getConf('fields/' . $field . '/label', '');
                $html .= '</th>';
                $display = $object->getConf($row_path . '/display', 'default');
                $html .= '<td>';
                $html .= $object->displayData($field, $display);
                $html .= '</td>';
            } else {
                $label = $object->getConf($row_path . '/label', '', true);
                $value = $object->getConf($row_path . '/value', '', true);
                if ($label && $value) {
                    $html .= '<th>' . $label . '</th>';
                    $html .= '<td>' . $value . '</td>';
                }
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderRows(BimpConfig $config, $path)
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
                    $html .= self::renderStruct($config, $col_path . '/struct');
                } else {
                    $html .= self::renderStruct($config, $col_path);
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderPanel(BimpConfig $config, $path)
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

    public static function renderMedia(BimpConfig $config, $path)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);



        $config->setCurrentPath($prev_path);
        return $html;
    }

    public static function renderButton(BimpConfig $config, $path)
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

    public static function renderCustom(BimpConfig $config, $path)
    {
        $html = '';
        $prev_path = $config->current_path;
        $config->setCurrentPath($path);

        $html = $config->getFromCurrentPath('', '', true);

        $config->setCurrentPath($prev_path);
        return $html;
    }
}
