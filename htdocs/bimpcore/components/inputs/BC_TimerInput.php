<?php

namespace BC_V2;

class BC_TimerInput extends BC_Input
{

    protected static $definitions = null;
    public static $component_name = 'BC_TimerInput';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array(), &$debug = array())
    {
        $html = '';
        $timer = \BimpTools::getTimeDataFromSeconds((int) $params['value']);

        $html .= '<input type="hidden" name="' . $params['name'] . '"' . ' value="' . (int) $params['value'] . '"/>';

        // Jours : 
        if ($params['with_days']) {
            $html .= BC_NumberInput::render(array(
                        'name'          => $params['name'] . '_days',
                        'value'         => $timer['days'],
                        'inline'        => 1,
                        'min'           => 0,
                        'input_classes' => array('timer_input_value'),
                        'addon_right'   => 'J'
            ));
        } else {
            $timer['hours'] += ($timer['days'] * 24);
        }

        // Heures :
        $html .= BC_NumberInput::render(array(
                    'name'          => $params['name'] . '_hours',
                    'value'         => $timer['hours'],
                    'inline'        => 1,
                    'min'           => 0,
                    'max'           => ($params['with_days'] ? 23 : 'none'),
                    'input_classes' => array('timer_input_value'),
                    'addon_right'   => 'H'
        ));

        // Minutes :
        $html .= BC_NumberInput::render(array(
                    'name'          => $params['name'] . '_minutes',
                    'value'         => $timer['minutes'],
                    'inline'        => 1,
                    'min'           => 0,
                    'max'           => 59,
                    'input_classes' => array('timer_input_value'),
                    'addon_right'   => 'Min'
        ));

        // Secondes : 
        if ($params['with_secondes']) {
            $html .= BC_NumberInput::render(array(
                        'name'          => $params['name'] . '_secondes',
                        'value'         => $timer['secondes'],
                        'inline'        => 1,
                        'min'           => 0,
                        'max'           => 59,
                        'input_classes' => array('timer_input_value'),
                        'addon_right'   => 'Sec'
            ));
        } else {
            $html .= '<input type="hidden" name="' . $params['name'] . '_secondes" value="' . (int) $timer['secondes'] . '"/>';
        }

        // todo : gérer dans fichier js
        $html .= '<script ' . \BimpTools::getScriptAttribut() . '>';
        $html .= '$(\'.' . $params['name'] . '_time_value\').each(function() {';
        $html .= '$(this).change(function() {';
        $html .= 'updateTimerInput($(this), \'' . $params['name'] . '\');';
        $html .= '});';
        $html .= '});';
        $html .= '</script>';

        return parent::renderHtml($params, $html, $errors, $debug);
    }
}
