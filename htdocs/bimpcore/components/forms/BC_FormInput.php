<?php

namespace BC_V2;

class BC_FormInput extends BC_FormElement
{

    protected static $definitions = null;
    public static $component_name = 'BC_FormInput';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array())
    {
        $input = self::renderComponent('input', $params);
        $required = (isset($params['input']['required']) ? $params['input']['required'] : 0);

        $html = '';
        switch ($params['display_mode']) {
            default:
            case 'row':
                $html .= '<div class="formRow row">';
                $html .= '<div class="inputLabel col-xs-12 col-sm-3">';
                $html .= $params['label'];
                if ($required) {
                    $html .= '&nbsp;*';
                }
                $html .= '</div>';
                
                $html .= '<div class="formRowInput field col-xs-12 col-sm-9">';
                $html .= $input;
                
                if ($params['help']) {
                    $html .= '<div class="inputHelp">';
                    $html .= $params['help'];
                    $html .= '</div>';
                }
                $html .= '</div>';
                $html .= '</div>';
                break;

            case 'inline':
                break;
        }

        return parent::renderHtml($params, $html, $errors);
    }
}
