<?php

namespace BC_V2;

class BC_DatetimeInput extends BC_Input
{

    protected static $definitions = null;
    public static $component_name = 'BC_DatetimeInput';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array())
    {
        if (!$content) {
            $content = 'Je suis un ' . self::$component_name;
        }
        
        return parent::renderHtml($params, $content, $errors);
    }
}
