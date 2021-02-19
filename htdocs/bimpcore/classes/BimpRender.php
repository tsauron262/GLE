<?php

class BimpRender
{

    public static function displayTagData($data)
    {
        // Obsolète / A suppr... 
        return self::renderTagData($data);
    }

    public static function renderTagData($data)
    {
        $html = '';

        if (is_array($data) && !empty($data)) {
            foreach ($data as $name => $value) {
                $html .= ' data-' . $name . '="' . $value . '"';
            }
        }

        return $html;
    }

    public static function renderIcon($icon, $class = '')
    {
        return '<i class="' . self::renderIconClass($icon) . ($class ? ' ' . $class : '') . '"></i>';
    }

    public static function displayTagAttrs($params)
    {
        // Obsolète / A suppr... 
        return self::renderTagAttrs($params);
    }

    public static function renderTagAttrs($params)
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
        if (isset($params['icon'])) {
            $params['icon_before'] = $params['icon'];
            unset($params['icon']);
        }
        
        if (isset($params['onclick'])) {
            if (!isset($params['attr'])) {
                $params['attr'] = array();
            }
            
            $params['attr']['onclick'] = $params['onclick'];
            unset($params['onclick']);
        }
        
        if (!isset($params['classes'])) {
            $params['classes'] = array();
        }
        
        if (!in_array('btn', $params['classes'])) {
            $params['classes'][] = 'btn';
            
            if (isset($params['type'])) {
                $params['classes'][] = 'btn-' . $params['type'];
            } else {
                $params['classes'][] = 'btn-default';
            }
        }
        
        $html = '<' . $tag . self::displayTagAttrs($params) . '>';
        $html .= (isset($params['icon_before']) ? self::renderIcon($params['icon_before'], isset($params['label']) ? 'iconLeft' : '') : '');
        $html .= (isset($params['label']) ? $params['label'] : '');
        $html .= (isset($params['icon_after']) ? self::renderIcon($params['icon_after'], isset($params['label']) ? 'iconRight' : '') : '');
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
                $params['classes'] = BimpTools::merge_array($params['classes'], $classes);
            } else {
                $params['classes'] = $classes;
            }
        }
        $data = $config->get($path . '/data', array(), false, 'array');
        if (count($data)) {
            if (isset($params['data'])) {
                $params['data'] = BimpTools::merge_array($params['data'], $data);
            } else {
                $params['data'] = $data;
            }
        }
        $attr = $config->get($path . '/attr', array(), false, 'array');
        if (count($attr)) {
            if (isset($params['attr'])) {
                $params['attr'] = BimpTools::merge_array($params['attr'], $attr);
            } else {
                $params['attr'] = $attr;
            }
        }
        $styles = $config->get($path . '/styles', array(), false, 'array');
        if (count($styles)) {
            if (isset($params['styles'])) {
                $params['styles'] = BimpTools::merge_array($params['styles'], $styles);
            } else {
                $params['styles'] = $styles;
            }
        }

        return self::renderButton($params, $tag);
    }

    public static function renderRowButton($label, $icon, $onclick, $class = '', $attrs = array(), $tag = 'span')
    {
        $html = '';
        $html .= '<' . $tag . ' class="rowButton' . ((string) $class ? ' ' . $class : '');

        $html .= ' bs-popover"';
        $html .= ' data-container="body"';
        $html .= ' data-toggle="popover"';
        $html .= ' data-trigger="hover"';
        $html .= ' data-placement="top"';
        $html .= ' data-content="' . $label;

        $html .= '" onclick="' . $onclick . '"';

        if (!empty($attrs)) {
            $html .= BimpRender::displayTagAttrs($attrs);
        }

        $html .= '>';
        $html .= '<i class="' . BimpRender::renderIconClass($icon) . '"></i>';
        $html .= '</' . $tag . '>';

        return $html;
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
            $html .= self::renderIcon($params['icon'], $label ? 'iconLeft' : '');
        }
        $html .= $label;
        if (!isset($params['caret']) || (isset($params['caret']) && $params['caret'])) {
            $html .= '<span class="caret"></span>';
        }
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu';
        if (isset($params['menu_right']) && (int) $params['menu_right']) {
            $html .= ' dropdown-menu-right';
        }
        $html .= '">';
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

    public static function renderButtonsGroup($buttons, $params)
    {
        $html = '';
        $max = (isset($params['max']) ? (int) $params['max'] : 0);

        $buttons_html = array();
        foreach ($buttons as $btn) {
            $label = isset($btn['label']) ? $btn['label'] : '';
            $icon = isset($btn['icon']) ? $btn['icon'] : '';

            if ($label || $icon) {
                $onclick = isset($btn['onclick']) ? $btn['onclick'] : '';
                $disabled = isset($btn['disabled']) ? (int) $btn['disabled'] : 0;
                $popover = isset($btn['popover']) ? (string) $btn['popover'] : '';
                $classes = array('btn');

                if ($max && count($buttons) > $max) {
                    $classes[] = 'btn-light-default';
                } else {
                    $classes[] = (isset($btn['type']) ? 'btn-' . $btn['type'] : 'btn-default');
                }

                if ($disabled) {
                    $classes[] = 'disabled';
                }

                if ($popover) {
                    $classes[] = 'bs-popover';
                }

                $button = array(
                    'classes' => $classes,
                    'label'   => $label,
                    'attr'    => array(
                        'type'    => 'button',
                        'onclick' => $onclick
                    )
                );
                if ($icon) {
                    $button['icon_before'] = $icon;
                }
                if ($popover) {
                    $button['data']['toggle'] = 'popover';
                    $button['data']['trigger'] = 'hover';
                    $button['data']['container'] = 'body';
                    $button['data']['placement'] = 'top';
                    $button['data']['html'] = 'true';
                    $button['data']['content'] = $popover;
                }

                $buttons_html[] = BimpRender::renderButton($button, 'button');
            }
        }

        if (count($buttons_html)) {
            if ($max && count($buttons) > $max) {
                $dp_label = (isset($params['dropdown_label']) ? $params['dropdown_label'] : 'Actions');
                $dp_icon = (isset($params['dropdown_icon']) ? $params['dropdown_icon'] : 'fas_cogs');
                $html .= BimpRender::renderDropDownButton($dp_label, $buttons_html, array(
                            'icon'       => $dp_icon,
                            'menu_right' => (isset($params['dropdown_menu_right']) ? (int) $params['dropdown_menu_right'] : 0)
                ));
            } else {
                foreach ($buttons_html as $btn_html) {
                    $html .= $btn_html;
                }
            }
        }

        return $html;
    }

    public static function renderPanel($title, $body_content, $footer_content = '', $params = array())
    {
        $html = '';

        $params = BimpTools::overrideArray(array(
                    'type'        => 'default',
                    'foldable'    => true,
                    'open'        => true,
                    'panel_class' => '',
                    'no_borders'  => 0
                        ), $params);

        $html .= '<div class="panel panel-' . $params['type'];
        $html .= ($params['foldable'] ? ' foldable ' . ($params['open'] ? 'open' : 'closed') : '');
        $html .= ($params['panel_class'] ? ' ' . $params['panel_class'] : '');
        $html .= ($params['no_borders'] ? ' no-borders' : '');
        $html .= '"';
        if (isset($params['panel_id']) && $params['panel_id']) {
            $html .= ' id="' . $params['panel_id'] . '"';
        }
        $html .= '>';

        if (!isset($params['no_header']) || !(int) $params['no_header']) {
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

//                    if (isset($button['dropdown']) && (int) $button['dropdown']) {
//                        $html .= self::renderDropDownButton($button['label'], $button['items'], $button['params']);
//                    } else {
                    $html .= self::renderButton($button);
//                    }
                }
            }

            if (isset($params['header_icons']) && is_array($params['header_icons']) && count($params['header_icons'])) {
                foreach ($params['header_icons'] as $icon_data) {
                    if (isset($icon_data['icon'])) {
                        
                    }
                }
                $html .= '<span class="panel_header_icon bs-popover"';
                if (isset($icon_data['label']) && $icon_data['label']) {
                    $html .= BimpRender::renderPopoverData($icon_data['label']);
                }
                $html .= ' onclick="' . (isset($icon_data['onclick']) ? $icon_data['onclick'] : '') . '">';
                $html .= BimpRender::renderIcon($icon_data['icon']);
                $html .= '</span>';
            }

            if ($params['foldable']) {
                $html .= '<span class="panel-caret"></span>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }

        // Corps:
        $html .= '<div class="panel-body"' . ($params['foldable'] && !$params['open'] ? ' style="display: none"' : '') . '>';
        $html .= $body_content;
        $html .= '</div>';

        // Footer:
        if ($footer_content && (!isset($params['no_footer']) || !(int) $params['no_footer'])) {
            $html .= '<div class="panel-footer"' . ($params['foldable'] && !$params['open'] ? ' style="display: none"' : '') . '>';
            $html .= $footer_content;
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    public static function renderFreeForm($rows, $buttons = array(), $title = '', $icon = '', $infos = array())
    {
        $html = '<div class="freeForm">';
        if ($title) {
            $html .= '<div class="freeFormTitle">';
            if ($icon) {
                $html .= BimpRender::renderIcon($icon, 'iconLeft');
            }
            $html .= $title;
            $html .= '</div>';
        }
        $html .= '<div class="freeFormContent">';

        if (count($infos)) {
            foreach ($infos as $info) {
                $html .= '<p class="alert alert-info">';
                $html .= $info;
                $html .= '</p>';
            }
        }

        foreach ($rows as $row) {
            $html .= '<div class="freeFormRow">';
            if (isset($row['label'])) {
                $html .= '<div class="freeFormLabel">' . $row['label'] . ': </div>';
            }
            if (isset($row['input'])) {
                $html .= '<div class="freeFormInput">';
                $html .= $row['input'];
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $html .= '<div class="freeFormAjaxResult">';
        $html .= '</div>';

        if (!empty($buttons)) {
            $html .= '<div class="freeFormSubmit rightAlign">';
            foreach ($buttons as $button) {
                $html .= $button;
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public static function renderNavTabs($tabs, $tabs_id = 'maintabs', $params = array())
    {
        $html = '';

        if (is_array($tabs) && count($tabs)) {
            if ($tabs_id === 'maintabs' && BimpTools::isSubmit('navtab')) {
                $active = BimpTools::getValue('navtab', $tabs[0]['id']);
            } else {
                $active = BimpTools::getValue('navtab-' . $tabs_id, $tabs[0]['id']);
            }
        }

        if (!isset($params['content_only']) || !(int) $params['content_only']) {
            $html .= '<ul id="navtabs_' . $tabs_id . '" class="nav nav-tabs" role="tablist" data-navtabs_id="' . $tabs_id . '">';

            foreach ($tabs as $tab) {
                $html .= '<li role="presentation"' . ($tab['id'] === $active ? ' class="active"' : '') . ' data-navtab_id="' . $tab['id'] . '">';
                $paramsUrl2 = array();
                if ($tabs_id === 'maintabs' && !BimpTools::isSubmit('ajax')) {
                    $paramsUrl = $_GET;
                    $paramsUrl['navtab'] = $tab['id'];
                    foreach ($paramsUrl as $clef => $val)
                        $paramsUrl2[] = $clef . '=' . $val;
                }
                $html .= '<a href="' . (count($paramsUrl2) > 0 ? '?' . implode('&', $paramsUrl2) : '') . '#' . $tab['id'] . '" aria-controls="' . $tab['id'] . '" role="tab" data-toggle="tab"';
                if (isset($tab['ajax']) && (int) $tab['ajax']) {
                    $html .= ' data-ajax="1"';
                }
                if (isset($tab['ajax_callback']) && $tab['ajax_callback']) {
                    $html .= ' data-ajax_callback="' . htmlentities($tab['ajax_callback']) . '"';
                }
                $html .= '>';
                $html .= $tab['title'];
                $html .= '</a>';
                $html .= '</li>';
            }
            $html .= '</ul>';
        }

        if (!isset($params['nav_only']) || !(int) $params['nav_only']) {
            $html .= '<div id="navtabs_content_' . $tabs_id . '" class="tab-content">';
            foreach ($tabs as $tab) {
                $html .= '<div class="tab-pane fade' . ($tab['id'] === $active ? ' in active' : '') . '" role="tabpanel" id="' . $tab['id'] . '">';
                if (!isset($tab['ajax']) || !(int) $tab['ajax']) {
                    if (isset($tab['content'])) {
                        $html .= $tab['content'];
                    }
                } else {
                    $html .= '<div class="nav_tab_ajax_result">';
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }

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
                    $html .= ' onclick="loadModalObjectPage($(this), \'' . $url . '\', \'' . htmlentities(addslashes($title)) . '\')">';
                    $html .= '<i class="far fa5-file iconLeft"></i>';
                    $html .= 'Afficher</button>';
                }
                if ($url) {
                    $html .= '<a href="' . $url . '" class="btn btn-default" target="_blank" ';
                    $html .= ' data-toggle="popover"';
                    $html .= ' data-trigger="hover"';
                    $html .= ' data-content="Afficher dans un nouvel onglet"';
                    $html .= '>';
                    $html .= '<i class="' . BimpRender::renderIconClass('fas_external-link-alt') . '"></i>';
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

    public static function renderAjaxModal($modal_id, $ajaxName = 'bimpModal')
    {
        $html = '';
        $html .= '<div class="modal ajax-modal fade" tabindex="-1" role="dialog" id="' . $modal_id . '">';
        $html .= '<div class="modal-dialog modal-lg" role="document">';
        $html .= '<div class="modal-content">';

        $html .= '<div class="modal-header">';
        $html .= '<div class="modal-nav-buttons">';

        $html .= '<div class="modal-nav-prev disabled" onclick="' . $ajaxName . '.displayPrev();"><i class="fa fa-arrow-left"></i></div>';
        $html .= '<div class="modal-nav-next disabled" onclick="' . $ajaxName . '.displayNext();"><i class="fa fa-arrow-right"></i></div>';

        $html .= '<div class="modal-nav-history btn-group">';
        $html .= '<div class="dropdown-toggle disabled"';
        $html .= ' data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= '</div>';
        $html .= '<ul class="dropdown-menu"></ul>';
        $html .= '</div>';

        $html .= '</div>';

        $html .= '<h4 class="modal-titles_container"></h4>';
        $html .= '<button type="button" class="close" onclick="' . $ajaxName . '.clearCurrentContent();" aria-label="Close">';
        $html .= '<span aria-hidden="true">&times;</span>';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div class="modal-body">';

        $html .= self::rendercontentLoading();

        $html .= '<div class="modal-contents_container">';

        $html .= '</div>';

        $html .= '</div>';

        $html .= '<div class="modal-footer">';
        $html .= '<button type="button" class="btn btn-secondary" onclick="' . $ajaxName . '.clearCurrentContent();">';
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
        $html .= ' data-trigger="hover"';
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
        $return .= ' data-html="' . (is_string($html) ? $html : ($html ? 'true' : 'false')) . '"';
        $return .= ' data-viewport="' . htmlentities('{"selector": "body", "padding": 0}') . '"';

        return $return;
    }

    public static function rendercontentLoading($loading_text = '')
    {
        $html = '<div class="content-loading">';
        $html .= '<div class="loading-spin"><i class="fa fa-spinner fa-spin"></i></div>';
        $html .= '<p class="loading-text">' . $loading_text . '</p>';
        $html .= '</div>';
        return $html;
    }

    public static function renderIconClass($icon)
    {
        if (preg_match('/^(.+)_(.+)$/', $icon, $matches)) {
            return $matches[1] . ' fa5-' . $matches[2];
        } else {
            return 'fa fa-' . $icon;
        }
    }

    public static function renderObjectIcons($object, $page_link = true, $modal_view = null, $url = null)
    {
        if (!BimpObject::objectLoaded($object)) {
            return '';
        }

        if (is_null($modal_view)) {
            $modal_view = '';
        }
        $html = '';
        if ($page_link) {
            if (is_null($url)) {
                $url = BimpObject::getInstanceUrl($object);
            }
            if ($url) {
                $html .= '<span class="objectIcon" onclick="window.open(\'' . $url . '\')">';
                $html .= '<i class="fas fa5-external-link-alt"></i>';
                $html .= '</span>';
                if (!$modal_view) {
                    $title = BimpObject::getInstanceNom($object);
                    $title = str_replace('\'', '\\\'', $title);
                    $title = str_replace('"', '\\\'\\\'', $title);
                    $onclick = 'loadModalObjectPage($(this), \'' . $url . '\', \'' . htmlentities($title) . '\')';
                    $html .= '<span class="objectIcon" onclick="' . $onclick . '">';
                    $html .= '<i class="far fa5-eye"></i>';
                    $html .= '</span>';
                }
            }
        }
        if ($modal_view && is_a($object, 'BimpObject')) {
            $title = $object->getInstanceName();
            $title = str_replace('\'', '\\\'', $title);
            $title = str_replace('"', '\\\'\\\'', $title);
            $onclick = 'loadModalView(\'' . $object->module . '\', \'' . $object->object_name . '\', ' . $object->id . ', \'' . $modal_view . '\', $(this), \'' . htmlentities($title) . '\')';
            $html .= '<span class="objectIcon" onclick="' . $onclick . '">';
            $html .= '<i class="far fa5-eye"></i>';
            $html .= '</span>';
        }

        if (method_exists($object, 'getExtraObjectIcons')) {
            $html .= $object->getExtraObjectIcons();
        }

        return $html;
    }

    public static function renderDebugInfo($info, $title = '', $icon = 'fas_info-circle')
    {
        $html = '';

        if (!empty($info)) {
            $html .= '<div class="debug_info">';
            if ($icon) {
                $html .= '<i class="' . BimpRender::renderIconClass($icon) . ' debug_icon"></i>';
            }

            if ($title) {
                $html .= '<div class="debug_info_title">';
                $html .= $title;
                $html .= '</div>';
            }

            if (is_array($info)) {
                $html .= '<ul>';
                foreach ($info as $msg) {
                    $html .= '<li>' . $msg . '</li>';
                }
                $html .= '</ul>';
            } else {
                $html .= '<div>' . $info . ' </div>';
            }

            $html .= '</div>';
        }

        return $html;
    }

    public static function renderCompteurCaisse($input_name)
    {
        $rows = array(
            array(
                '500' => '500 €',
                '2'   => '2 €',
            ),
            array(
                '200' => '200 €',
                '1'   => '1 €'
            ),
            array(
                '100' => '100 €',
                '0.5' => '50 cts'
            ),
            array(
                '50'  => '50 €',
                '0.2' => '20 cts'
            ),
            array(
                '20'  => '20 €',
                '0.1' => '10 cts'
            ),
            array(
                '10'   => '10 €',
                '0.05' => '5 cts'
            ),
            array(
                '5'    => '5 €',
                '0.02' => '2 cts'
            ),
            array(
                '0'    => '',
                '0.01' => '1 ct'
            )
        );

        $html .= '<div class="compteur_caisse">';
        $html .= '<p class="small">';
        $html .= 'Appuyez sur "Entrée" pour passer au champ suivant';
        $html .= '</p>';
        $html .= '<table class="bimp_list_table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th colspan="2" style="text-align: center">Billets</th>';
        $html .= '<th colspan="2" style="text-align: center">Pièces</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';

        $i = 0;
        foreach ($rows as $r) {
            $html .= '<tr>';
            foreach ($r as $value => $label) {
                if (!(float) $value) {
                    $html .= '<td colspan="2"></td>';
                } else {
                    $html .= '<th>';
                    $html .= $label;
                    $html .= '</th>';
                    $html .= '<td>';
                    $html .= BimpInput::renderInput('text', 'compteur_caisse_' . $value, 0, array(
                                'data'        => array(
                                    'data_type' => 'number',
                                    'min'       => 0,
                                    'decimals'  => 0,
                                    'value'     => $value,
                                    'label'     => $label,
                                    'idx'       => $i
                                ),
                                'extra_class' => 'compteur_caisse_input idx_' . $i
                    ));
                    $html .= '</td>';
                }
                $i += 7;
            }
            $html .= '</tr>';
            $i -= 13;
        }
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '<input class="compteur_caisse_total_input" type="hidden" name="' . $input_name . '" value="0"/>';

        $html .= '<div style="margin-top: 10px; padding: 5px 0;font-size: 16px;background-color: #DCDCDC;text-align: center">';
        $html .= 'Total: ';
        $html .= '<span class="compteur_caisse_total">0</span> &euro;';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public static function renderRecursiveArrayContent($array, $params)
    {
        $html = '';

        $no_html = BimpTools::getArrayValueFromPath($params, 'no_html', false);
        $title = BimpTools::getArrayValueFromPath($params, 'title', '');

        if (!$no_html) {
            $foldable = (int) BimpTools::getArrayValueFromPath($params, 'foldable', 0);
            $open = (int) BimpTools::getArrayValueFromPath($params, 'open', 1);

            $html .= '<div class="array_content_container' . (($foldable && $title) ? ' foldable ' . ($open ? 'open' : 'closed') : '') . '">';

            if ($foldable && $title) {
                $html .= '<div class="folding_buttons">';
                $html .= '<span class="open_all">' . BimpRender::renderIcon('fas_plus', 'iconLeft') . 'tout déplier</span>';
                $html .= '<span class="close_all">' . BimpRender::renderIcon('fas_minus', 'iconLeft') . 'tout replier</span>';
                $html .= '</div>';
            }

            if ($title) {
                $html .= '<div class="array_content_caption">';
                $html .= '<span class="title">' . $title . '</span>';
                $html .= '</div>';
            }

            $html .= '<div class="array_content">';
            if (is_array($array)) {
                foreach ($array as $label => $value) {
                    if (is_array($value)) {
                        $html .= self::renderRecursiveArrayContent($value, array(
                                    'foldable' => $foldable,
                                    'title'    => $label,
                                    'open'     => $open
                        ));
                    } else {
                        $html .= '<div class="array_content_row">';
                        $html .= '<span class="array_content_label">' . $label . ': </span>';
                        $html .= '<span class="array_content_value">' . $value . '</span>';
                        $html .= '</div>';
                    }
                }
            } elseif (is_string($array)) {
                $html .= $array;
            }

            $html .= '</div>';
            $html .= '</div>';
        } else {
            $nTabs = BimpTools::getArrayValueFromPath($params, 'nTabs', 0);

            if ($title) {
                if ($nTabs) {
                    for ($i = 0; $i < $nTabs; $i++) {
                        $html .= "\t";
                    }
                }
                $html .= $title . "\n";
                $nTabs++;
            }

            if (is_array($array)) {
                foreach ($array as $label => $value) {
                    if ($nTabs) {
                        for ($i = 0; $i < $nTabs; $i++) {
                            $html .= "\t";
                        }
                    }
                    if (is_array($value)) {
                        $html .= self::renderRecursiveArrayContent($value, array(
                                    'no_html' => true,
                                    'title'   => $label,
                                    'nTabs'   => $nTabs
                        ));
                    } else {
                        $html .= $label . ': ' . $value . "\n";
                    }
                }
            } else {
                if ($nTabs) {
                    for ($i = 0; $i < $nTabs; $i++) {
                        $html .= "\t";
                    }
                }
                $html .= (string) $array;
            }
        }

        return $html;
    }

    public static function renderImage($module_part, $file_name, $max_width = 'none', $max_height = 'none', $with_preview = true)
    {
        
    }

    public static function renderInfoCard($title, $value, $params)
    {
        $params = BimpTools::overrideArray(array(
                    'icon'      => '',
                    'class'     => 'default',
                    'color'     => '',
                    'data_type' => 'string',
                    'format'    => 'medium'
                        ), $params);

        if (!$params['color']) {
            $params['color'] = (string) BimpCore::getParam('colors/' . $params['class'], BimpCore::getParam('colors/default', '000000'));
        }

        $html .= '<div class="bimp_info_card" style="border-color: #' . $params['color'] . '">';

        if ($params['icon']) {
            $html .= '<div class="bimp_info_card_icon" style="color: #' . $params['color'] . '">';
            $html .= BimpRender::renderIcon($params['icon']);
            $html .= '</div>';
        }
        $html .= '<div class="bimp_info_card_content">';
        $html .= '<div class="bimp_info_card_title" style="color: #' . $params['color'] . '">';
        $html .= $title;
        $html .= '</div>';

        $html .= '<div class="bimp_info_card_value">';
        switch ($params['data_type']) {
            case 'money':
                $html .= BimpTools::displayMoneyValue($value);
                break;

            case 'percent':
                $html .= BimpTools::displayFloatValue($value, (isset($params['decimals']) ? (int) $params['decimals'] : 2)) . '%';
                // todo: ajouter une barre de progression.
                break;

            case 'string':
            default:
                $html .= $value;
                break;
        }
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    public static function renderFoldableContainer($title, $content, $params = array())
    {
        $params = BimpTools::overrideArray(array(
                    'id'          => '',
                    'open'        => true,
                    'offset_left' => false
                        ), $params);

        $html = '';

        $html .= '<div ' . ($params['id'] ? 'id="' . $params['id'] . '" ' : '') . 'class="foldable_container ' . ($params['open'] ? 'open' : 'closed') . ($params['offset_left'] ? ' offset_left' : '') . '">';
        $html .= '<div class="foldable_caption">';
        $html .= $title;
        $html .= '<span class="foldable-caret"></span>';
        $html .= '</div>';
        $html .= '<div class="foldable_content"' . ($params['open'] ? '' : ' style="display: none"') . '>';
        $html .= $content;
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public static function renderFormRow($label, $input, $label_col = 3)
    {
        $html = '';

        $html .= '<div class="row formRow operationOptionRow">';
        $html .= '<div class="inputLabel col-xs-12 col-sm-6 col-md-' . $label_col . '">';
        $html .= $label;
        $html .= '</div>';

        $html .= '<div class="formRowInput operationOptionInput field col-xs-12 col-sm-6 col-md-' . (12 - $label_col) . '">';
        $html .= $input;
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public static function renderBacktrace($bt_files)
    {
        $html = '';

        if (!empty($bt_files)) {
            $html .= '<div class="BimpBacktraceContainer">';

            foreach ($bt_files as $file) {
                if (isset($file['file'])) {
                    $html .= '<div class="bt_file">';
                    $html .= $file['file'];
                    $html .= '</div>';
                }

                if (isset($file['lines']) && !empty($file['lines'])) {
                    $html .= '<ul class="bt_file_lines">';
                    foreach ($file['lines'] as $line) {
                        $html .= '<li>';
                        if (preg_match('/^(\d+:)(.+)$/', $line, $matches)) {
                            $html .= '<b>' . $matches[1] . '</b>' . $matches[2];
                        } else {
                            $html .= $line;
                        }

                        $html .= '</li>';
                    }
                    $html .= '</ul>';
                }
            }

            $html .= '</div>';
        }

        return $html;
    }

    public static function renderSql($sql)
    {
        $main_kw = array('SELECT ', 'UPDATE ', 'INSERT INTO ', 'DELETE ');
        $sec_kw = array(' FROM ', 'LEFT JOIN', 'RIGHT JOIN', 'INNER JOIN', ' WHERE ', ' HAVING ', ' ORDER BY ', ' GROUP BY ', ' LIMIT ');
        $kw = array(' AND ', ' OR ', ' ON ', ' AS ');

        foreach ($main_kw as $word) {
            $sql = str_replace($word, '<span class="danger">' . $word . '</span>', $sql);
            $sql = str_replace(strtolower($word), '<span class="danger">' . strtolower($word) . '</span>', $sql);
        }

        foreach ($sec_kw as $word) {
            $sql = str_replace($word, '<br/><span class="info">' . $word . '</span>', $sql);
            $sql = str_replace(strtolower($word), '<br/><span class="info">' . strtolower($word) . '</span>', $sql);
        }

        foreach ($kw as $word) {
            $sql = str_replace($word, '<b>' . $word . '</b>', $sql);
            $sql = str_replace(strtolower($word), '<b>' . strtolower($word) . '</b>', $sql);
        }

        return $sql;
    }
    
    public static function renderBimpListTable($rows, $headers = array(), $params = array())
    {
        // Paramètres par défaut:
        $params = BimpTools::overrideArray(array(
                    'main_id'     => '',
                    'main_class'  => '',
                    'data'        => array(),
                    'searchable'  => false,
                    'search_mode' => 'lighten', // lighten (surbrillance des éléments trouvés) / show (affichage uniquement des élements trouvés)  
                    'sortable'    => false,
                    'sort_col'    => '',
                    'sort_way'    => 'asc', // asc / desc
                    'checkboxes'  => false
                        ), $params);

        $params['data']['searchable'] = (int) $params['searchable'];
        $params['data']['sortable'] = (int) $params['sortable'];
        $params['data']['checkboxes'] = (int) $params['checkboxes'];
        $params['data']['search_mode'] = $params['search_mode'];

        // Params par défaut des headers:
        $headers_temp = $headers;
        $headers = array();

        foreach ($headers_temp as $col_name => $header_params) {
            if (!is_array($header_params)) {
                $header_params = array(
                    'label' => $header_params
                );
            }

            $headers[$col_name] = BimpTools::overrideArray(array(
                        'label'      => '',
                        'cel_tag'    => 'td', // td / th
                        'align'      => 'left', // left / right / center
                        'col_style'  => '',
                        'searchable' => true,
                        'sortable'   => true,
                            ), $header_params);
        }

        $empty_first_col = ($params['checkboxes'] || $params['searchable']);
        $html = '';

        $html .= '<table' . ($params['main_id'] ? ' id="' . $params['main_id'] . '"' : '');
        $html .= ' class="bimp_list_table' . ($params['main_class'] ? ' ' . $params['main_class'] : '') . '"';
        $html .= BimpRender::renderTagData($params['data']);
        $html .= '>';

        if (!empty($headers)) {
            $html .= '<thead>';

            // En-têtes: 
            $html .= '<tr class="header_row">';

            if ($empty_first_col) {
                $html .= '<th class="th_checkboxes">';
                if ($params['checkboxes']) {
                    $html .= '<input type="checkbox" name="check_all" class="bimp_list_table_check_all"/>';
                }
                $html .= '</th>';
            }

            foreach ($headers as $col_name => $header) {
                $html .= '<th class="col_header" data-col_name="' . $col_name . '">';
                if ($params['sortable'] && $header['sortable']) {
                    $html .= '<span id="' . $col_name . '_sortTitle" class="sortTitle sorted-';
                    if ($params['sort_col'] && $params['sort_col'] === $col_name) {
                        $html .= strtolower($params['sort_way']) . ' active';
                    } else {
                        $html .= 'desc';
                    }
                    $html .= '">';
                    $html .= $header['label'] . '</span>';
                } else {
                    $html .= $header['label'];
                }
                $html .= '</th>';
            }
            $html .= '</tr>';

            // Ligne de recherche: 
            if ($params['searchable']) {
                $html .= '<tr class="listSearchRow">';
                $html .= '<td style="text-align: center"><i class="fa fa-search"></i></td>';

                foreach ($headers as $col_name => $header) {
                    $html .= '<td class="col_search">';
                    $html .= '<div class="searchInputContainer">';
                    if ($header['searchable']) {
                        $html .= '<input type="text" class="bimp_list_table_search_input" value="" name="search_col_' . $col_name . '" data-col="' . $col_name . '"/>';
                    }
                    $html .= '</div>';
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</thead>';
        }

        $html .= '<tbody class="headers_col">';

        // Lignes: 
        foreach ($rows as $row) {
            $html .= '<tr class="bimp_list_table_row"' . (isset($row['row_data']) ? BimpRender::renderTagData($row['row_data']) : '') . '>';
            if ($empty_first_col) {
                $html .= '<td>';
                if ($params['checkboxes'] && (!isset($row['item_checkbox']) || (int) $row['item_checkbox'])) {
                    $html .= '<input type="checkbox" class="bimp_list_table_row_check" name="row_check"/>';
                }
                $html .= '</td>';
            }

            foreach ($headers as $col_name => $header) {
                $html .= '<' . $header['cel_tag'];
                $html .= ' class="col_' . $col_name . '"';
                $html .= ' data-col="' . $col_name . '"';
                if (is_array($row[$col_name]) && isset($row[$col_name]['value'])) {
                    $html .= ' data-value="' . htmlentities($row[$col_name]['value']) . '"';
                }
                $html .= ' style="' . ($header['align'] !== 'left' ? 'text-align: ' . $header['align'] . ';' : '');
                if ($header['col_style']) {
                    $html .= ' ' . $header['col_style'] . ';';
                }
                $html .= '">';
                if (isset($row[$col_name])) {
                    if (is_array($row[$col_name])) {
                        if (isset($row[$col_name]['content'])) {
                            $html .= $row[$col_name]['content'];
                        } elseif (isset($row[$col_name]['value'])) {
                            $html .= $row[$col_name]['value'];
                        }
                    } else {
                        $html .= $row[$col_name];
                    }
                }
                $html .= '</' . $header['cel_tag'] . '>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    // Form elements: 

    public static function renderFormGroupMultiple($items_contents, $inputName, $title, $params = array())
    {
        // params: 
        // item_label (string, fac.)
        // max_items (int, fac.)
        // required (bool, fac.)
        // display_if (array, fac.) 

        $html = '';

        $html .= '<div class="formInputGroup' . (isset($params['display_if']) ? ' display_if' : '') . '" data-field_name="' . $inputName . '" data-multiple="1"';
        if (isset($params['max_items'])) {
            $html .= ' data-max_items="' . $params['max_items'] . '"';
        }
        if (isset($params['display_if'])) {
            $html .= BC_Field::renderDisplayifDataStatic($params['display_if']);
        }
        $html .= '>';

        // En-tête: 
        $html .= '<div class="formGroupHeading">';
        $html .= '<div class="formGroupTitle">';
        $html .= '<h3>' . $title . ((isset($params['required']) && (int) $params['required']) ? '<sup>*</sup>' : '') . '</h3>';
        if (isset($params['max_items'])) {
            $html .= '<span class="small">&nbsp;&nbsp;' . $params['max_items'] . ' élément(s) max</span>';
        }
        $html .= '</div>';
        $html .= '</div>';

        // Template: 
        if (isset($items_contents['tpl'])) {
            $html .= '<div class="dataInputTemplate">';
            $html .= '<div class="formGroupMultipleItem">';
            $html .= '<div class="formGroupHeading">';
            $html .= '<div class="formGroupTitle">';
            $html .= '<h4>' . (isset($params['item_label']) ? $params['item_label'] . ' ' : '') . '#idx</h4>';
            $html .= '</div>';
            $html .= '<div class="formGroupButtons">';
            $html .= '<button class="btn btn-danger" onclick="removeFormGroupMultipleItem($(this))">';
            $html .= BimpRender::renderIcon('fas_trash-alt');
            $html .= '</button>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= $items_contents['tpl'];
            $html .= '</div>';
            $html .= '</div>';
        }

        // Liste des items: 
        $html .= '<div class="formGroupMultipleItems">';
        $i = 0;
        foreach ($items_contents as $idx => $item_content) {
            if ($idx === 'tpl') {
                continue;
            }

            $i++;
            $html .= '<div class="formGroupMultipleItem">';
            $html .= '<div class="formGroupHeading">';
            $html .= '<div class="formGroupTitle">';
            $html .= '<h4>' . (isset($params['item_label']) ? $params['item_label'] . ' ' : '') . '#' . $i . '</h4>';
            $html .= '</div>';
            $html .= '<div class="formGroupButtons">';
            $html .= '<button class="btn btn-danger" onclick="removeFormGroupMultipleItem($(this))">';
            $html .= BimpRender::renderIcon('fas_trash-alt');
            $html .= '</button>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= str_replace('idx', $i, $item_content);

            $html .= '</div>';
        }

        // Bouton ajout: 
        $html .= '</div>';
        $html .= '<div class="buttonsContainer align-right">';
        $html .= '<span class="btn btn-default" onclick="addFormGroupMultipleItem($(this), \'' . $inputName . '\')"><i class="fa fa-plus-circle iconLeft"></i>Ajouter</span>';
        $html .= '</div>';

        $html .= '<input type="hidden" name="' . $inputName . '_nextIdx" value="' . ($i + 1) . '"/>';

        $html .= '</div>';
        return $html;
    }

    public static function renderFormInputsGroup($title, $inputName, $inputs_content, $params = array())
    {
        // params: 
        // required (bool, fac.)
        // display_if (array, fac.) 

        $html = '';

        $html .= '<div class="formInputGroup' . (isset($params['display_if']) ? ' display_if' : '') . '" data-multiple="0" data-field_name="' . $inputName . '"';
        if (isset($params['display_if'])) {
            $html .= BC_Field::renderDisplayifDataStatic($params['display_if']);
        }
        $html .= '>';

        $html .= '<div class="formGroupHeading">';
        $html .= '<div class="formGroupTitle"><h3>' . $title . (isset($params['required']) && (int) $params['required'] ? '<sup>*</sup>' : '') . '</h3></div>';
        $html .= '</div>';

        $html .= $inputs_content;

        $html .= '</div>';


        return $html;
    }
}
