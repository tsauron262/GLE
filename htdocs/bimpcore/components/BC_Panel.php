<?php

namespace BC_V2;

class BC_Panel extends BimpComponent
{

    protected static $definitions = null;
    public static $component_name = 'BC_Panel';

    public static function setAttributes($params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);

        self::addClass($attributes, self::$component_name);
    }

    public static function render($params, $content = '')
    {
        $html = 'JE SUIS UN PANEL <br/>';
        $html .= 'MON CONTENU : <br/><br/>';
        $html .= $content;

        return parent::render($params, $html);
    }
}
