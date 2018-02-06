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

    public static function renderButtonFromConfig(BimpConfig $config, $path, $extra_params, $tag = 'span')
    {
        $label = $config->get($path . '/label', '');
        $icon_before = $config->get($path . '/icon_before', '');
        $icon_after = $config->get($path . '/icon_after', '');
        $configTag = $config->get($path . '/tag', '');

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

        $id = $config->get($path . '/id', '');
        if ($id) {
            $params['id'] = $id;
        }
        $classes = $config->get($path . '/classes', array(), false, 'array');
        if (count($classes)) {
            if (isset($params['classes'])) {
                $params['classes'] = array_merge($params['classes'], $classes);
            } else {
                $params['classes'] = $classes;
            }
        }
        $data = $config->get($path . '/data', array(), false, 'array');
        if (count($data)) {
            if (isset($params['data'])) {
                $params['data'] = array_merge($params['data'], $data);
            } else {
                $params['data'] = $data;
            }
        }
        $attr = $config->get($path . '/attr', array(), false, 'array');
        if (count($attr)) {
            if (isset($params['attr'])) {
                $params['attr'] = array_merge($params['attr'], $attr);
            } else {
                $params['attr'] = $attr;
            }
        }
        $styles = $config->get($path . '/styles', array(), false, 'array');
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
        $html = '<div class="btn-group';
        if (isset($params['drop_up']) && $params['drop_up']) {
            $html .= ' dropup';
        }
        $html .= '">';
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

        if (!isset($params['type']) || !$params['type']) {
            $params['type'] = 'default';
        }

        $html .= '<div class="panel panel-' . $params['type'] . (isset($params['foldable']) && $params['foldable'] ? ' foldable open' : '') . '"';
        if (isset($params['panel_id']) && $params['panel_id']) {
            $html .= ' id="' . $params['panel_id'] . '"';
        }
        $html .= '>';
        $html .= '<div class="panel-heading">';

        // Titre:
        $html .= '<div class="panel-title">';
        if (isset($params['icon']) && $params['icon']) {
            $html .= self::renderIcon($params['icon'], 'iconLeft');
        }
        $html .= $title;
        $html .= '</div>';

        // Bouttons en en-tête:

        $html .= '<div class="header_buttons">';
        if (isset($params['header_buttons']) && is_array($params['header_buttons']) && count($params['header_buttons'])) {
            foreach ($params['header_buttons'] as $button) {
                $button['classes'][] = 'headerBtn';
                $html .= self::renderButton($button);
            }
        }

        if (isset($params['foldable']) && $params['foldable']) {
            $html .= '<span class="panel-caret"></span>';
        }
        $html .= '</div>';
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

    public static function renderNavTabs($tabs)
    {
        $html = '';

        $html .= '<ul class="nav nav-tabs" role="tablist">';
        $first = true;
        foreach ($tabs as $tab) {
            $html .= '<li role="presentation"' . ($first ? ' class="active"' : '') . '>';
            $html .= '<a href="#' . $tab['id'] . '" aria-controls="' . $tab['id'] . '" role="tab" data-toggle="tab">';
            $html .= $tab['title'];
            $html .= '</a>';
            $html .= '</li>';

            if ($first) {
                $first = false;
            }
        }
        $html .= '</ul>';

        $first = true;

        $html .= '<div class="tab-content">';
        foreach ($tabs as $tab) {
            $html .= '<div class="tab-pane fade' . ($first ? ' in active' : '') . '" role="tabpanel" id="' . $tab['id'] . '">';
            $html .= $tab['content'];
            $html .= '</div>';
            if ($first) {
                $first = false;
            }
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

        if (is_a($object, 'BimpObject')) {
            $object_name = $object->object_name;
        } else {
            $object_name = get_class($object);
        }

        $card_identifier = $object_name . '_' . $object->id . '_card';

        $title = $config->getFromCurrentPath('title', null, false, 'any');
        $image_url = $config->getFromCurrentPath('image', null);
        $view_btn = $config->getFromCurrentPath('view_btn', false, false, 'bool');

        $fields = array();
        foreach ($config->getFromCurrentPath('fields', array(), false, 'array') as $label => $params) {
            if (isset($params['object_prop'])) {
                if (isset($object->{$params['object_prop']})) {
                    $fields[$label] = $object->{$params['object_prop']};
                } else {
                    $fields[$label] = '<span class="danger">Inconnu</span>';
                }
            } elseif (is_a($object, 'BimpObject')) {
                if (is_string($params)) {
                    $fields[$label] = $object->displayData($params);
                } elseif (is_array($params)) {
                    $field = $config->getFromCurrentPath('fields/' . $label . '/field', '');
                    if ($field) {
                        $display = $config->getFromCurrentPath('fields/' . $label . '/display', 'default');
                        $fields[$label] = $object->displayData($field, $display);
                    }
                }
            }
            if (!isset($fields[$label])) {
                $fields[$label] = BimpRender::renderAlerts('erreur de configuration pour ce champ', 'warning');
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
                if ($title === 'nom') {
                    $title = BimpObject::getInstanceNom($object);
                }
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
            $file = '';
            $params = array();
            if (is_a($object, 'BimpObject')) {
                $file = $object->module . '/index.php';
                $params['fc'] = $object->controller;
                $params['id'] = $object->id;
            } else {
                $file = strtolower(get_class($object)) . '/card.php';
                if (is_a($object, 'Societe')) {
                    $params['socid'] = $object->id;
                } else {
                    $params['id'] = $object->id;
                }
            }
            if (!file_exists(DOL_DOCUMENT_ROOT . '/' . $file)) {
                $file = '';
            }

            if ($url || $file) {
                $html .= '<div style="text-align: right; margin-top: 15px">';
                $html .= '<div class="btn-group">';
                if ($file) {
                    $html .= '<button type="button" class="btn btn-default"';
                    $html .= ' data-toggle="popover"';
                    $html .= ' data-trigger="hover" ';
                    $html .= ' data-content="Afficher dans une popup"';
                    $html .= ' onclick="loadModalObjectPage($(this), \'' . $url . '\', \'page_modal\', \'' . htmlentities(addslashes($title)) . '\')">';
                    $html .= '<i class="fa fa-file-o iconLeft"></i>';
                    $html .= 'Afficher</button>';
                }
                if ($url) {
                    $html .= '<a href="' . $url . '" class="btn btn-default" target="_blank" ';
                    $html .= ' data-toggle="popover"';
                    $html .= ' data-trigger="hover"';
                    $html .= ' data-content="Afficher dans un nouvel onglet"';
                    $html .= '>';
                    $html .= '<i class="fa fa-external-link"></i>';
                    if (!$file) {
                        $html .= '&nbsp;&nbsp;Afficher';
                    }
                    $html .= '</a>';
                }
                $html .= '</div>';
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

    public static function renderObjectFieldHistoryPopoverButton(BimpObject $object, $field)
    {
        $bimpHistory = BimpObject::getInstance('bimpcore', 'BimpHistory');

        $html = '<span class="historyPopoverButton bs-popover"';
        $html .= ' data-toggle="popover"';
        $html .= ' data-trigger="hover" ';
        $html .= ' data-placement="bottom"';
        $html .= ' data-html="true"';
        $html .= ' data-content="' . htmlentities($bimpHistory->renderCard($object, $field, 10, false, false)) . '"';
        $html .= '></span>';

        unset($bimpHistory);

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

    public static function renderPopoverData($content, $placement = 'top', $html = 'false', $container = 'body', $toggle = 'popover', $trigger = 'hover')
    {
        $return = ' data-toggle="' . $toggle . '"';
        $return .= ' data-trigger="' . $trigger . '"';
        $return .= ' data-container="' . $container . '"';
        $return .= ' data-placement="' . $placement . '"';
        $return .= ' data-content="' . htmlentities($content) . '"';
        $return .= ' data-html="'.$html.'"';
        
        return $return;
    }
}
