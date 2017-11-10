<?php

class BimpRender
{

    public static function displayTagData($data)
    {
        $html = '';
        foreach ($data as $name => $value) {
            $html .= ' data-' . $name . '="' . $value . '"';
        }
    }

    public static function renderIcon($icon, $class = '')
    {
        return '<i class="fa fa-' . $icon . ($class ? ' ' . $class : '') . '"></i>';
    }

    public static function displayTagAttrs($params)
    {
        $html = '';

        if (isset($params['id'])) {
            $html .= ' id="' . $params['id'] . '"';
        }

        if (isset($params['classes'])) {
            $html .= ' class="';
            $first_loop = true;
            foreach ($params['classes'] as $class) {
                if (!$first_loop) {
                    $html .= ' ';
                } else {
                    $first_loop = false;
                }
                $html .= $class;
            }
            $html .= '"';
        }

        if (isset($params['data'])) {
            foreach ($params['data'] as $name => $value) {
                $html .= ' data-' . $name . '="' . $value . '"';
            }
        }

        if (isset($params['styles'])) {
            $html .= ' style="';
            foreach ($params['styles'] as $name => $value) {
                $html .= $name . ': ' . $value . '; ';
            }
            $html .= '"';
        }

        if (isset($params['attr'])) {
            foreach ($params['attr'] as $name => $value) {
                $html .= ' ' . $name . '="' . $value . '"';
            }
        }

        return $html;
    }

    public static function renderButton($params, $tag = 'span')
    {
        $html = '<' . $tag . self::displayTagAttrs($params) . '>';
        $html .= (isset($params['icon_before']) ? self::renderIcon($params['icon_before'], 'iconLeft') : '');
        $html .= (isset($params['label']) ? $params['label'] : '');
        $html .= (isset($params['icon_after']) ? self::renderIcon($params['icon_after'], 'iconRight') : '');
        $html .= '</' . $tag . '>';
        return $html;
    }

    public static function renderButtonFromConfig(BimpConfig $config, $path, $instance, $extra_params, $tag = 'span')
    {
        $label = $config->get($path . '/label', $instance, '');
        $icon_before = $config->get($path . '/icon_before', $instance, '');
        $icon_after = $config->get($path . '/icon_after', $instance, '');
        $configTag = $config->get($path . '/tag', $instance, '');

        $params = $extra_params;
        if ($label) {
            $params['label'] = $label;
        }
        if ($icon_before) {
            $params['icon_before'] = $icon_before;
        }
        if ($icon_after) {
            $params['icon_after'] = $icon_after;
        }
        if ($configTag) {
            $tag = $configTag;
        }

        $id = $config->get($path . '/id', $instance, '');
        if ($id) {
            $params['id'] = $id;
        }
        $classes = $config->get($path . '/classes', $instance, array(), false, 'array');
        if (count($classes)) {
            if (isset($params['classes'])) {
                $params['classes'] = array_merge($params['classes'], $classes);
            } else {
                $params['classes'] = $classes;
            }
        }
        $data = $config->get($path . '/data', $instance, array(), false, 'array');
        if (count($data)) {
            if (isset($params['data'])) {
                $params['data'] = array_merge($params['data'], $data);
            } else {
                $params['data'] = $data;
            }
        }
        $attr = $config->get($path . '/attr', $instance, array(), false, 'array');
        if (count($attr)) {
            if (isset($params['attr'])) {
                $params['attr'] = array_merge($params['attr'], $attr);
            } else {
                $params['attr'] = $attr;
            }
        }
        $styles = $config->get($path . '/styles', $instance, array(), false, 'array');
        if (count($styles)) {
            if (isset($params['styles'])) {
                $params['styles'] = array_merge($params['styles'], $styles);
            } else {
                $params['styles'] = $styles;
            }
        }
        
        return self::renderButton($params, $tag);
    }

    public static function renderDropDownButton($label, $items, $params)
    {
        if (!isset($params['type'])) {
            $params['type'] = 'default';
        }
        $html = '<div class="btn-group">';
        $html .= '<button type="button" class="btn btn-' . $params['type'] . ' dropdown-toggle"';
        $html .= ' data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        if (isset($params['icon'])) {
            $html .= self::renderIcon($params['icon'], 'iconLeft');
        }
        $html .= $label;
        if (!isset($params['caret']) || (isset($params['caret']) && $params['caret'])) {
            $html .= '<span class="caret"></span>';
        }
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu">';
        foreach ($items as $item) {
            if ($item === 'separator') {
                $html .= '<li role="separator" class="divider"></li>';
            } else {
                $html .= '<li>' . $item . '</li>';
            }
        }
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    public static function renderPanel($title, $body_content, $footer_content = '', $params = array())
    {
        $html = '';

        if (!isset($params['type'])) {
            $params['type'] = 'default';
        }

        $html .= '<div class="panel panel-' . $params['type'] . '"';
        if (isset($params['panel_id'])) {
            $html .= ' id="' . $params['panel_id'] . '"';
        }
        $html .= '>';
        $html .= '<div class="panel-heading">';

        // Titre:
        $html .= '<div class="panel-title">';
        if (isset($params['icon'])) {
            $html .= self::renderIcon($params['icon'], 'iconLeft');
        }
        $html .= $title;
        $html .= '</div>';

        // Bouttons en en-tête:
        if (isset($params['header_buttons'])) {
            $html .= '<div class="header_buttons">';
            foreach ($params['header_buttons'] as $button) {
                $button['classes'][] = 'headerBtn';
                $html .= self::renderButton($button);
            }
            $html .= '</div>';
        }

        $html .= '</div>';

        // Corps:
        $html .= '<div class="panel-body">';
        $html .= $body_content;
        $html .= '</div>';

        // Footer:
        if ($footer_content) {
            $html .= '<div class="panel-footer">';
            $html .= $footer_content;
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    public static function renderObjectCard($object, BimpConfig $config)
    {
        if (!isset($object->id) || !$object->id) {
            if (is_a($object, 'BimpObject')) {
                $msg = 'Aucun' . ($object->isLabelFemale() ? 'e' : '') . ' ' . $object->getLabel();
            } else {
                $msg = 'Aucun';
            }
            return self::renderAlerts($msg, 'warning');
        }
        $title = $config->getFromCurrentPath('title', $object, null, false, 'any');
        $image_url = $config->getFromCurrentPath('image', $object, null);
        $view_btn = $config->getFromCurrentPath('view_btn', $object, false, false, 'bool');

        $fields = array();
        foreach ($config->getFromCurrentPath('fields', $object, array(), false, 'array') as $label => $params) {
            if (isset($params['object_prop'])) {
                if (isset($object->{$params['object_prop']})) {
                    $fields[$label] = $object->{$params['object_prop']};
                } else {
                    $fields[$label] = '<span class="danger">Inconnu</span>';
                }
            }
        }

        $html = '';
        $html .= '<div class="media object_card">';
        if (!is_null($image_url) && $image_url) {
            $html .= '<div class="media-left">';
            $html .= '<img src="' . $image_url . '" alt=""/>';
            $html .= '</div>';
        }
        $html .= '<div class="media-body">';
        if (!is_null($title)) {
            if (is_array($title)) {
                if (isset($title['object_prop'])) {
                    if (property_exists($object, $title['object_prop'])) {
                        $title = $object->{$title['object_prop']};
                    }
                }
            }
            if (is_string($title)) {
                $html .= '<h4 class="media-heading">' . $title . '</h4>';
            }
        }
        $html .= '<table class="object_card_table">';
        $html .= '<thead></thead>';
        $html .= '<tbody>';
        foreach ($fields as $label => $value) {
            $html .= '<tr>';
            $html .= '<th>' . $label . ':</th>';
            $html .= '<td>' . $value . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        if ($view_btn) {
            $url = BimpObject::getInstanceUrl($object);
            if ($url) {
                $html .= '<div style="text-align: right; margin-top: 15px">';
                $html .= '<a href="' . $url . '" class="btn btn-default" target="_blanck">';
                $html .= '<i class="fa fa-file-o iconLeft"></i>';
                $html .= 'Afficher';
                $html .= '</a>';
                $html .= '</div>';
            }
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public static function renderAjaxModal($modal_id)
    {
        $html = '';
        $html .= '<div class="modal ajax-modal fade" tabindex="-1" role="dialog" id="' . $modal_id . '">';
        $html .= '<div class="modal-dialog modal-lg" role="document">';
        $html .= '<div class="modal-content">';

        $html .= '<div class="modal-header">';
        $html .= '<h4 class="modal-title"></h4>';
        $html .= '<button type="button" class="close" data-dismiss="modal" aria-label="Close">';
        $html .= '<span aria-hidden="true">&times;</span>';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div class="modal-body">';

        $html .= '<div class="content-loading">';
        $html .= '<div class="loading-spin"><i class="fa fa-spinner fa-spin"></i></div>';
        $html .= '<p class="loading-text"></p>';
        $html .= '</div>';

        $html .= '<div class="modal-ajax-content"></div>';

        $html .= '</div>';

        $html .= '<div class="modal-footer">';
        $html .= '<button type="button" class="btn btn-secondary" data-dismiss="modal">';
        $html .= '<i class="fa fa-times iconLeft"></i>Fermer</button>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public static function renderBimpTimer($object, $object_time_field)
    {
        if (!isset($object->id) || !$object->id) {
            return '';
        }

        $time_sec = $object->getData($object_time_field);
        if (is_null($time_sec)) {
            $time_sec = 0;
        }

        $timer = BimpTools::getTimeDataFromSeconds($time_sec);

        $timer_id = $object->object_name . '_' . $object->id . '_timer';

        $html = '';
        $html .= '<div id="' . $timer_id . '" class="bimp_timer">';

        $html .= '<div class="bimp_timer_header">';
        $html .= '<h4 class="title">Chrono intervention</h4>';
        $html .= '</div>';

        $html .= '<div class="bimp_timer_total_time bimp_timer_time">';
        $html .= '<div class="title">Durée totale intervention:</div>';
        $html .= '<span class="bimp_timer_days bimp_timer_value">' . $timer['days'] . '</span>';
        $html .= '<span class="bimp_timer_label">j</span>';
        $html .= '<span class="bimp_timer_hours bimp_timer_value">' . $timer['hours'] . '</span>';
        $html .= '<span class="bimp_timer_label">h</span>';
        $html .= '<span class="bimp_timer_minutes bimp_timer_value">' . $timer['minutes'] . '</span>';
        $html .= '<span class="bimp_timer_label">min</span>';
        $html .= '<span class="bimp_timer_secondes bimp_timer_value">' . $timer['secondes'] . '</span>';
        $html .= '<span class="bimp_timer_label">sec</span>';
        $html .= '</div>';

        $html .= '<div class="bimp_timer_current_time bimp_timer_time">';
        $html .= '<div class="title">Durée session:</div>';
        $html .= '<span class="bimp_timer_days bimp_timer_value">0</span>';
        $html .= '<span class="bimp_timer_label">j</span>';
        $html .= '<span class="bimp_timer_hours bimp_timer_value">0</span>';
        $html .= '<span class="bimp_timer_label">h</span>';
        $html .= '<span class="bimp_timer_minutes bimp_timer_value">0</span>';
        $html .= '<span class="bimp_timer_label">min</span>';
        $html .= '<span class="bimp_timer_secondes bimp_timer_value">0</span>';
        $html .= '<span class="bimp_timer_label">sec</span>';
        $html .= '</div>';

        $html .= '<div class="bimp_timer_footer">';

        $buttons = array();
        $button = '<button type="button" class="btn btn-light-default bimp_timer_save_btn" onclick="$' . $timer_id . '.save();">';
        $button .= '<i class="fa fa-save iconLeft"></i>Enregistrer la durée courante</button>';
        $buttons[] = $button;
        $button = '<button type="button" class="btn btn-light-default bimp_timer_reset_current_btn" onclick="$' . $timer_id . '.resetCurrent();">';
        $button .= '<i class="fa fa-history iconLeft"></i>Réinitialiser la durée de la session</button>';
        $buttons[] = $button;
        $button = '<button type="button" class="btn btn-light-default bimp_timer_reset_total_btn" onclick="$' . $timer_id . '.resetTotal();">';
        $button .= '<i class="fa fa-history iconLeft"></i>Réinitialiser la durée totale de l\'intervention</button>';
        $buttons[] = $button;
        $button = '<button type="button" class="btn btn-light-default bimp_timer_cancel_reset_btn"';
        $button .= ' style="display: none" onclick="$' . $timer_id . '.cancelLastReset();">';
        $button .= '<i class="fa fa-times iconLeft"></i>Réinitialiser la durée totale de l\'intervention</button>';
        $buttons[] = $button;

        $html .= BimpRender::renderDropDownButton('Actions', $buttons, array('icon' => 'cogs'));

        $html .= '<button type="button" class="btn btn-success bimp_timer_start_btn" onclick="$' . $timer_id . '.start();"><i class="fa fa-play iconLeft"></i>Démarrer</button>';
        $html .= '<button type="button" class="btn btn-warning bimp_timer_pause_btn" style="display: none" onclick="$' . $timer_id . '.pause();"><i class="fa fa-pause iconLeft"></i>Suspendre</button>';

        $html .= '</div>';
        $html .= '</div>';

        $html .= '<script type="text/javascript">';
        $html .= 'var $' . $timer_id . ' = new BimpTimer(\'' . $timer_id . '\', \'' . $object->module . '\', \'' . $object->object_name . '\', ' . $object->id . ', \'' . $object_time_field . '\', ' . $time_sec . ', 0);';
        $html .= '</script>';

        return $html;
    }

    public static function renderAlerts($msgs, $alert_type = 'danger', $closable = true)
    {
        $html = '<div class="alert alert-' . $alert_type . ($closable ? ' alert-dismissible' : '') . '">';
        if ($closable) {
            $html .= '<button type="button" class="close" data-dismiss="alert" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>';
        }
        if (is_string($msgs)) {
            $html .= '<p>' . $msgs . '</p>';
        } elseif (is_array($msgs)) {
            $html .= '<ul>';
            foreach ($msgs as $msg) {
                $html .= '<li>' . $msg . '</li>';
            }
            $html .= '</ul>';
        }
        $html .= '</div>';

        return $html;
    }
}
