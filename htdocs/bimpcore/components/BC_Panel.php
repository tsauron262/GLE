<?php

namespace BC_V2;

class BC_Panel extends BimpComponent
{

    protected static $definitions = null;
    public static $component_name = 'BC_Panel';

    public static function setAttributes($params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

    protected static function renderHtml($params, $content = '', &$errors = array())
    {
        $html = '';

        $panel_content = '';
        $panel_content .= '<div class="container-fluid">';

        $msgs = self::getParam('msgs', $params);

        if (!empty($msgs)) {
            foreach ($msgs as $msg) {
                if ($msg['content']) {
                    $panel_content .= \BimpRender::renderAlerts($msg['content'], $msg['type']);
                }
            }
        }

        $panel_content .= static::renderBeforePanelContentHtml($params, $errors);
        $panel_content .= $content;
        $panel_content .= static::renderAfterPanelContentHtml($params, $errors);
        $panel_content .= '</div>';

        $title = self::getParam('title', $params);
        $footer = static::renderPanelHtmlFooter($params, $errors);

        $html .= \BimpRender::renderPanel($title, $panel_content, $footer, array(
                    'type'           => 'secondary',
                    'foldable'       => true,
                    'icon'           => self::getParam('icon', $params),
                    'header_buttons' => static::getPanelHeaderButtons($params, $errors),
                    'header_icons'   => static::getPanelHeaderIcons($params, $errors),
                    'no_header'      => (int) !self::getParam('panel_header', $params),
                    'no_footer'      => (int) !self::getParam('panel_footer', $params),
                    'no_borders'     => (int) self::getParam('no_borders', $params),
                    'open'           => (int) self::getParam('open', $params)
        ));

        return parent::renderHtml($params, $html, $errors);
    }

    protected static function renderBeforePanelContentHtml($params, &$errors = array())
    {
        return '';
    }

    protected static function renderAfterPanelContentHtml($params, &$errors = array())
    {
        return '';
    }

    protected static function renderPanelHtmlFooter($params, &$errors = array())
    {
        $html = '<div class="panelFooterButtons" style="text-align: right">';
        $html .= self::renderPanelFooterExtraBtn($params, $errors);
        $html .= self::getParam('footer_extra_content', $params, '');
        $html .= '</div>';

        return $html;
    }

    protected static function renderPanelFooterExtraBtn($params, &$errors = array())
    {
        $buttons = self::getParam('footer_extra_btn', $params);
        if (!empty($buttons)) {
            $items = array();

            foreach ($buttons as $btn_params) {
                $btn_params['type'] = 'light-default';
                $items[] = \BimpRender::renderButton($btn_params, 'button');
            }

            if (count($items)) {
                if (count($items) > 1) {
                    return \BimpRender::renderDropDownButton('Actions', $items, array(
                                'icon'       => 'fas_cogs',
                                'menu_right' => 1
                    ));
                }
                return str_replace('btn-light-default', 'btn-default', $items[0]);
            }
        }

        return '';
    }

    protected static function getPanelHeaderButtons($params, &$errors = array())
    {
        return self::getParam('header_buttons', $params, array());
    }

    protected static function getPanelHeaderIcons($params, &$errors = array())
    {
        return self::getParam('header_icons', $params, array());
    }
}
