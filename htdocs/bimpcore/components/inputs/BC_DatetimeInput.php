<?php

namespace BC_V2;

class BC_DatetimeInput extends BC_Input
{

    protected static $definitions = null;
    public static $component_name = 'BC_DatetimeInput';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array(), &$debug = array())
    {
        $html = '';

        if ($params['range']) {
            $format = ($params['subtype'] == 'date' ? 'Y-m-d' : ($params['subtype'] == 'time' ? 'H:i:s' : 'Y-m-d H:i:s'));
            $from_label = ($params['subtype'] == 'time' ? 'De' : 'Du');
            $to_label = ($params['subtype'] == 'time' ? 'À' : 'Au');
            $value = $params['value'];

            if (is_null($value)) {
                $value = '';
            }

            if (!is_array($value)) {
                $value = array(
                    'from' => '',
                    'to'   => $value
                );
            }

            if (!isset($value['to']) || !$value['to']) {
                if (isset($value['from']) && $value['from']) {
                    $DT = new \DateTime($value['from']);
                    if (isset($value['to_interval'])) {
                        $DT->add($value['to_interval']);
                    } else {
                        switch ($params['subtype']) {
                            case 'time':
                                $DT->add(new \DateInterval('PT1H'));
                                break;

                            case 'date':
                            case 'datetime':
                                $DT->add(new \DateInterval('P1D'));
                                break;
                        }
                    }
                    $value['to'] = $DT->format($format);
                    unset($DT);
                }
            }

            if (!isset($value['from'])) {
                $value['from'] = '';
            }

            if (!$value['from'] && isset($value['from_interval'])) {
                $DT = new \DateTime($value['to']);
                $DT->sub(new \DateInterval($value['from_interval']));
                $value['from'] = $DT->format($format);
                unset($DT);
            }

            $html .= '<div class="input-group">';
            $html .= '<span class="input-group-addon">' . $from_label . '</span>';
            $classes = $params['input_classes'];
            $classes[] = 'date_range_from';
            $html .= self::renderPicker($params, $params['name'] . '_from', $value['from'], 'date_range_from');
            $html .= '</div>';

            $html .= '<div class="input-group">';
            $html .= '<span class="input-group-addon">' . $to_label . '</span>';
            $html .= self::renderPicker($params, $params['name'] . '_to', $value['to'], 'date_range_to');
            $html .= '</div>';
        } else {
            $html .= self::renderPicker($params, $params['name'], $params['value']);
        }

        return parent::renderHtml($params, $html, $errors, $debug);
    }

    protected static function renderPicker(&$params, $input_name, $value, $extra_class = '')
    {
        $html = '';
        $input_id .= 'dt_' . rand(111111, 999999);

        if (is_null($value)) {
            $value = '';
        }

        $display_js_format = '';
        $js_format = '';
        $php_format = '';
        $dt_value = null;
        switch ($params['subtype']) {
            case 'time':
                if ($params['with_seconds']) {
                    $display_js_format = 'HH:mm:ss';
                    $js_format = 'HH:mm:ss';
                } else {
                    $display_js_format = 'HH:mm';
                    $js_format = 'HH:mm';
                }

                $php_format = 'H:i:s';
                if ($value) {
                    if (preg_match('/^(\d{2}):(\d{2}):?(\d{2})?$/', $value)) {
                        $dt_value = new \DateTime($value);
                    }
                }
                break;

            case 'date':
                $display_js_format = 'Do MMMM YYYY';
                $js_format = 'YYYY-MM-DD';
                $php_format = 'Y-m-d';
                if ($value) {
                    if (preg_match('/^(\d{4})\-(\d{2})\-(\d{2})$/', $value)) {
                        $dt_value = new \DateTime($value);
                    }
                }
                break;

            default:
            case 'datetime':
                if ($params['with_seconds']) {
                    $display_js_format = 'Do MMMM YYYY HH:mm';
                    $js_format = 'YYYY-MM-DD HH:mm';
                } else {
                    $display_js_format = 'Do MMMM YYYY HH:mm:ss';
                    $js_format = 'YYYY-MM-DD HH:mm:ss';
                }

                $php_format = 'Y-m-d H:i:s';
                if ($value) {
                    if (preg_match('/^(\d{4})\-(\d{2})\-(\d{2})( (\d{2}):(\d{2}):?(\d{2})?)?$/', $value)) {
                        $dt_value = new \DateTime($value);
                    }
                }
                break;
        }

        if (!$value && $params['display_now']) {
            $value = date($php_format);
            $dt_value = new \DateTime($value);
        }

        $classes = $params['input_classes'];
        $classes[] = 'datepicker_value';
        if ($extra_class) {
            $classes[] = $extra_class;
        }

        $html .= '<div class="dropdown">';
        $html .= '<input' . \BimpRender::renderTagAttrs(array(
                    'attr'    => array(
                        'type'  => 'hidden',
                        'id'    => $input_id,
                        'name'  => $input_name,
                        'value' => (!is_null($dt_value) ? $dt_value->format($php_format) : '')
                    ),
                    'classes' => $classes
                )) . '/>';

        $html .= '<input type="text" class="form-control bs_datetimepicker" id="' . $input_id . '_bs_dt_picker" name="' . $input_name . '_picker"/>';

        $html .= '<script ' . \BimpTools::getScriptAttribut() . '>';
        $html .= "$('#" . $input_id . "_bs_dt_picker').datetimepicker({";
        $html .= "locale: 'fr',";
        $html .= "format: '" . $display_js_format . "',";
        if (!is_null($dt_value)) {
            $html .= "defaultDate: moment('" . $dt_value->format($php_format) . "', '" . $js_format . "'),";
        }
        $html .= "showTodayButton: " . ($params['display_now'] ? "true" : "false");
        $html .= "}); ";

        $html .= "$('#" . $input_id . "_bs_dt_picker').on('dp.change', function(e) {";
        $html .= "if (e.date) {";
        $html .= "$('#" . $input_id . "').val(e.date.format('" . $js_format . "')).change();";
        $html .= "} else {";
        $html .= "$('#" . $input_id . "').val('').change();";
        $html .= "}";
        $html .= "})";
        $html .= '</script>';
        $html .= '</div>';

        return $html;
    }
}
