<?php

namespace BC_V2;

class BC_Form extends BC_Panel
{

    protected static $definitions = null;
    public static $component_name = 'BC_Form';
    public static $subdir = 'forms';

    public static function setAttributes($params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
        
        
    }

    protected static function renderHtml($params, $content = '', &$errors = array())
    {
        $html = 'JE SUIS UN FORM';
        
        return parent::renderHtml($params, $html, $errors);
    }
}
