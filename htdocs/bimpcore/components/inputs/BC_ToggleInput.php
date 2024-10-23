<?php

namespace BC_V2;

class BC_ToggleInput extends BC_Input
{

    protected static $definitions = null;
    public static $component_name = 'BC_ToggleInput';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array())
    {
        $html = '';

        $input_id = 'toggle_' . rand(0, 9999999);
        $extra_classes = (count($params['input_classes']) ? ' ' . implode(' ', $params['input_classes']) : '');

        $html .= '<div class="toggleContainer">';

        $html .= '<input type="hidden" class="toggle_value' . $extra_classes . '" value="' . ($params['value'] ? '1' : '0') . '" name="' . $params['name'] . '" id="' . $input_id . '"/>';
        $html .= '<input type="checkbox" class="toggle' . $extra_classes . '" id="' . $input_id . '_checkbox" ' . ($params['value'] ? ' checked' : '') . '/>';

        $html .= '<span class="toggle-label-off">' . ($params['display_labels'] ? $params['toggle_off'] : '') . '</span>';
        $html .= '<label class="toggle-slider' . ($params['disabled'] ? ' disabled' : '') . '" for="' . $input_id . '_checkbox"></label>';

        if ($params['display_labels']) {
            $html .= '<span class="toggle-label-on">' . ($params['display_labels'] ? $params['toggle_on'] : '') . '</span>';
        }

        $html .= '</div>';

        return parent::renderHtml($params, $html, $errors);
    }
}
