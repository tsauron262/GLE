<?php

namespace BC_V2;

class BC_Form extends BC_Panel
{
    protected static $definitions = null;
    public static $component_name = 'BC_Form';
    public static $subdir = 'forms';
    
    public static function renderHtml(BC_Data $data, $content = '')
    {
        $html = '';
        
        $errors = array();
        
        return $html;
    }
}
