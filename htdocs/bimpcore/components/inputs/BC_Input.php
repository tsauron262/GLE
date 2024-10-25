<?php

namespace BC_V2;

class BC_Input extends BimpComponent
{

    protected static $definitions = null;
    public static $component_name = 'BC_Input';
    public static $subdir = 'inputs';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
        self::addClass($attributes, 'inputContainer');
        
        self::addData($attributes, 'input_name', $params['name']);
        
        if ($params['required']) {
            self::addClass($attributes, 'required');
        }
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array(), &$debug = array())
    {
        if (!$content) {
            $content = 'Je suis un ' . self::$component_name;
        }

        return parent::renderHtml($params, $content, $errors, $debug);
    }
}
