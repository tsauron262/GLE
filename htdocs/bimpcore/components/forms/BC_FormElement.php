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

        $hidden = (int) self::getParam('hidden', $params);
        if ($hidden) {
            self::addStyle($attributes, 'display', 'none');
        }

        $display_if = self::getParam('display_if', $params, array());
        if (!empty($display_if)) {
            foreach ($display_if as $input_name => $display_if_data) {
                self::addClass($attributes, 'display_if_' . $input_name);
                self::addData($attributes, 'display_if_' . $input_name, json_encode($display_if_data));
            }
        }

        $depends_on = self::getParam('depends_on', $params, array());
        if (!empty($depends_on)) {
            $keep_new_value = (int) self::getParam('keep_new_value', $params);
            self::addData($attributes, 'depends_on', implode(',', $depends_on));
            self::addData($attributes, 'keep_new_value', $keep_new_value);
        }
    }
    
//    protected static function renderHtml(&$params, $content = '', &$errors = array())
//    {
//        return parent::renderHtml($params, $html, $errors);
//    }
}
