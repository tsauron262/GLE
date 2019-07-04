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
        return '<i class="' . self::renderIconClass($icon) . ($class ? ' ' . $class : '') . '"></i>';
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

    public static function renderPanel($title, $body_content, $footer_content = '', $params = array())
    {
        $html = '';

        if (!isset($params['type']) || !$params['type']) {
            $params['type'] = 'default';
        }

        if (!isset($params['open'])) {
            $params['open'] = true;
        }

        if (!isset($params['foldable'])) {
            $params['foldable'] = true;
        }

        if (!isset($params['panel_class'])) {
            $params['panel_class'] = '';
        }

        $html .= '<div class="panel panel-' . $params['type'] . ($params['foldable'] ? ' foldable ' . ($params['open'] ? 'open' : 'closed') : '') . ($params['panel_class'] ? ' ' . $params['panel_class'] : '') . '"';
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
                $html .= '<a href="#' . $tab['id'] . '" aria-controls="' . $tab['id'] . '" role="tab" data-toggle="tab">';
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
                $html .= $tab['content'];
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

    public static function renderAjaxModal($modal_id)
    {
        $html = '';
        $html .= '<div class="modal ajax-modal fade" tabindex="-1" role="dialog" id="' . $modal_id . '">';
        $html .= '<div class="modal-dialog modal-lg" role="document">';
        $html .= '<div class="modal-content">';

        $html .= '<div class="modal-header">';
        $html .= '<div class="modal-nav-buttons">';

        $html .= '<div class="modal-nav-prev disabled" onclick="bimpModal.displayPrev();"><i class="fa fa-arrow-left"></i></div>';
        $html .= '<div class="modal-nav-next disabled" onclick="bimpModal.displayNext();"><i class="fa fa-arrow-right"></i></div>';

        $html .= '<div class="modal-nav-history btn-group">';
        $html .= '<div class="dropdown-toggle disabled"';
        $html .= ' data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $html .= '</div>';
        $html .= '<ul class="dropdown-menu"></ul>';
        $html .= '</div>';

        $html .= '</div>';
        
        $html .= '<h4 class="modal-titles_container"></h4>';
        $html .= '<button type="button" class="close" onclick="bimpModal.clearCurrentContent();" aria-label="Close">';
        $html .= '<span aria-hidden="true">&times;</span>';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div class="modal-body">';

        $html .= self::rendercontentLoading();

        $html .= '<div class="modal-contents_container"></div>';

        $html .= '</div>';

        $html .= '<div class="modal-footer">';
        $html .= '<button type="button" class="btn btn-secondary" onclick="bimpModal.clearCurrentContent();">';
        $html .= '<i class="fa fa-times iconLeft"></i>Fermer</button>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div id="openModalBtn" onclick="bimpModal.show();" class="closed bs-popover"';
        $html .= BimpRender::renderPopoverData('Afficher la fenêtre popup', 'top');
        $html .= '>';
        $html .= BimpRender::renderIcon('far_window-restore');
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
//                    $title = str_replace('"', '\\\'\\\'', (BimpObject::getInstanceNom($object)));
                    $title = '';
                    $onclick = 'loadModalObjectPage($(this), \'' . $url . '\', \'' . $title . '\')';
                    $html .= '<span class="objectIcon" onclick="' . $onclick . '">';
                    $html .= '<i class="far fa5-eye"></i>';
                    $html .= '</span>';
                }
            }
        }
        if ($modal_view && is_a($object, 'BimpObject')) {
            $title = str_replace('"', '\\\'\\\'', ($object->getInstanceName()));
            $onclick = 'loadModalView(\'' . $object->module . '\', \'' . $object->object_name . '\', ' . $object->id . ', \'' . $modal_view . '\', $(this), \'' . $title . '\')';
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

        if (!empty($info) && $title) {
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
    
    public static function renderCompteurCaisse()
    {
        // Essayes de gérer tes colonnes avec à gauche les billets, à droite les pièces. 
        
        $html .= '<div>';
        
        $html .= '<table class="bimp_list_table">';
        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<td style="font-weight: bold; font-style="15px">';
        
        $html .= '500 &euro;';
        $html .= BimpInput::renderInput('text', 'compteur_caisse_500', 0, array(
            'data' => array(
                'data_type' => 'number',
                'min' => 0,
                'decimals' => 0
            )
        ));
        
        $html .= '</td>';
        
        $html .= '<td>';
        // etc...
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</tbody>';
        $html .= '</table>';
        
        $html .= '</div>';
    }
}
