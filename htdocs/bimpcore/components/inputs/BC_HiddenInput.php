<?php

namespace BC_V2;

class BC_HiddenInput extends BC_Input
{

    protected static $definitions = null;
    public static $component_name = 'BC_HiddenInput';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array())
    {
        $html = 'Je suis un ' . self::$component_name;

        return parent::renderHtml($params, $html, $errors);
    }
}
