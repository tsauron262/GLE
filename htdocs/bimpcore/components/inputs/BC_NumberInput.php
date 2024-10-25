<?php

namespace BC_V2;

class BC_NumberInput extends BC_TextInput
{

    protected static $definitions = null;
    public static $component_name = 'BC_NumberInput';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array(), &$debug = array())
    {
        $html = '';

        if ($params['min_label'] && $params['min'] !== 'none') {
            $html .= '<div style="display: inline-block">';
            $html .= '&nbsp;&nbsp;<span class="small min_label">Min: ' . $params['min'] . '</span>';
            $html .= '</div>';
        }

        if ($params['max_label'] && $params['max'] !== 'none') {
            $html .= '<div style="display: inline-block">';
            $html .= '&nbsp;&nbsp;<span class="small max_label">Max :' . $params['max'] . '</span>';
            $html .= '</div>';
        }

        return parent::renderHtml($params, $html, $errors, $debug);
    }
}
