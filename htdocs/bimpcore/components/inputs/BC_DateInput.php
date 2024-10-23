<?php

namespace BC_V2;

class BC_DateInput extends BC_DatetimeInput
{

    protected static $definitions = null;
    public static $component_name = 'BC_DateInput';

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
