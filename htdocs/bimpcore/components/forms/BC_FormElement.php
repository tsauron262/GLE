<?php

namespace BC_V2;

class BC_FormElement extends BimpComponent
{

    protected static $definitions = null;
    public static $component_name = 'BC_FormElement';
    public static $subdir = 'forms';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);

        if ($params['hidden']) {
            self::addStyle($attributes, 'display', 'none');
        }

        if (count($params['display_if'])) {
            foreach ($params['display_if'] as $input_name => $display_if_data) {
                self::addClass($attributes, 'display_if_' . $input_name);
                self::addData($attributes, 'display_if_' . $input_name, json_encode($display_if_data));
            }
        }

        if (count($params['depends_on'])) {
            self::addData($attributes, 'depends_on', implode(',', $params['depends_on']));
            self::addData($attributes, 'keep_new_value', $params['keep_new_value']);
        }
    }
    
//    protected static function renderHtml(&$params, $content = '', &$errors = array(), &$debug = array())
//    {
//        return parent::renderHtml($params, $html, $errors, $debug);
//    }
}
