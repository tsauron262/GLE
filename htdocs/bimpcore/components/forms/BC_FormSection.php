<?php

namespace BC_V2;

class BC_FormSection extends BC_FormElement
{

    protected static $definitions = null;
    public static $component_name = 'BC_FormSection';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array())
    {
        $html = 'Je suis une section';
        return parent::renderHtml($params, $html, $errors);
    }
}
