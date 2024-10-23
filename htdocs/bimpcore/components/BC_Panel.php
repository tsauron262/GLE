<?php

namespace BC_V2;

class BC_Panel extends BimpComponent
{

    protected static $definitions = null;
    public static $component_name = 'BC_Panel';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array(), &$debug = array())
    {
        $html = '';

        $panel_content = '';
        $panel_content .= '<div class="container-fluid">';

        if (!empty($params['msgs'])) {
            foreach ($params['msgs'] as $msg) {
                if ($msg['content']) {
                    $panel_content .= \BimpRender::renderAlerts($msg['content'], $msg['type']);
                }
            }
        }

        $panel_content .= static::renderBeforePanelContentHtml($params, $errors);
        $panel_content .= $content;
        $panel_content .= static::renderAfterPanelContentHtml($params, $errors);
        $panel_content .= '</div>';

        $footer = static::renderPanelHtmlFooter($params, $errors);

        $html .= \BimpRender::renderPanel($params['title'], $panel_content, $footer, array(
                    'type'           => 'secondary',
                    'foldable'       => true,
                    'icon'           => $params['icon'],
                    'header_buttons' => static::getPanelHeaderButtons($params, $errors),
                    'header_icons'   => static::getPanelHeaderIcons($params, $errors),
                    'no_header'      => !$params['panel_header'],
                    'no_footer'      => !$params['panel_footer'],
                    'no_borders'     => $params['no_borders'],
                    'open'           => $params['open']
        ));

        return parent::renderHtml($params, $html, $errors, $debug);
    }

    protected static function renderBeforePanelContentHtml(&$params, &$errors = array())
    {
        return '';
    }

    protected static function renderAfterPanelContentHtml(&$params, &$errors = array())
    {
        return '';
    }

    protected static function renderPanelHtmlFooter(&$params, &$errors = array())
    {
        $html = '<div class="panelFooterButtons" style="text-align: right">';
        $html .= self::renderPanelFooterExtraBtn($params, $errors);
        if ($params['footer_extra_content']) {
            $html .= $params['footer_extra_content'];
        }
        $html .= '</div>';

        return $html;
    }

    protected static function renderPanelFooterExtraBtn(&$params, &$errors = array())
    {
        if (!empty($params['footer_extra_btn'])) {
            $items = array();

            foreach ($params['footer_extra_btn'] as $btn_params) {
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

    protected static function getPanelHeaderButtons(&$params, &$errors = array())
    {
        return $params['header_buttons'];
    }

    protected static function getPanelHeaderIcons(&$params, &$errors = array())
    {
        return $params['header_icons'];
    }
}
