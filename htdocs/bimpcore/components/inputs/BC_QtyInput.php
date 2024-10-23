<?php

namespace BC_V2;

class BC_QtyInput extends BC_NumberInput
{

    protected static $definitions = null;
    public static $component_name = 'BC_QtyInput';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

//    protected static function renderHtml(&$params, $content = '', &$errors = array(), &$debug = array())
//    {
//        $html = 'Je suis un ' . self::$component_name;
//        
//        return parent::renderHtml($params, $html, $errors, $debug);
//    }
}
