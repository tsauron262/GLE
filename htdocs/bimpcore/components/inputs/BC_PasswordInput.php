<?php

namespace BC_V2;

class BC_PasswordInput extends BC_Input
{

    protected static $definitions = null;
    public static $component_name = 'BC_PasswordInput';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array())
    {
        $html = '<input ' . \Bimprender::renderTagAttrs(array(
                    'attr'    => array(
                        'type'  => 'password',
                        'name'  => $params['name'],
                        'value' => $params['value']
                    ),
                    'classes' => $params['input_classes'],
                    'styles'  => $params['input_styles']
                )) . '/>';

        $html .= '<p class="inputHelp">';
        $html .= $params['min_length'] . ' caractères minimum';
        if ($params['special_required'] || $params['maj_required'] || $params['num_required']) {
            $html .= '<br/>Veuillez utiliser au moins : ';

            if ($params['special_required']) {
                $html .= '<br/> - Un caractère spécial';
            }
            if ($params['maj_required']) {
                $html .= '<br/> - Un caractère majuscule';
            }
            if ($params['num_required']) {
                $html .= '<br/> - Un caractère numérique';
            }
        }
        $html .= '</p>';

        return parent::renderHtml($params, $html, $errors);
    }
}
