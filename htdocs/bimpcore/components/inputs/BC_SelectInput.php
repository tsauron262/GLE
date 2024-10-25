<?php

namespace BC_V2;

class BC_SelectInput extends BC_Input
{

    protected static $definitions = null;
    public static $component_name = 'BC_SelectInput';

    public static function setAttributes(&$params, &$attributes = array())
    {
        parent::setAttributes($params, $attributes);
        self::addClass($attributes, self::$component_name);
    }

    protected static function renderHtml(&$params, $content = '', &$errors = array(), &$debug = array())
    {
        $html = '';
        $value = $params['value'];

        if (is_null($params['options']) || !is_array($params['options'])) {
            $params['options'] = array();
        }

        if (isset($params['select_first']) && (int) $params['select_first']) {
            foreach ($params['options'] as $option_key => $option_val) {
                $value = $option_key;
                break;
            }
        }

        if (count($params['options'])) {
            if (count($params['options']) > 15) {
                $params['input_classes'][] = 'searchable_select';
            }

            $html .= '<select name="' . $params['name'] . '" class="' . implode(' ', $params['input_classes']) . '">';
            foreach ($params['options'] as $option_value => $opt) {
                $html .= self::renderSelectOption($option_value, $opt, $value);
            }
            $html .= '</select>';

            foreach ($params['options'] as $option_value => $opt) {
                if (isset($opt['help'])) {
                    $html .= '<div class="selectOptionHelp ' . $params['input_name'] . '_help" data-option_value="' . htmlentities($option_value) . '">';
                    $html .= \BimpRender::renderAlerts($opt['help'], 'info');
                    $html .= '</div>';
                }
            }
        } else {
            $html .= '<input type="hidden" name="' . $params['input_name'] . '" value="' . $params['value'] . '"/>';
            $html .= '<p class="alert alert-warning">Aucune option disponible</p>';
        }

        return parent::renderHtml($params, $html, $errors, $debug);
    }

    protected static function renderSelectOption($option_value, $option, $value)
    {
        $html = '';
        $color = null;
        $icon = null;
        $disabled = false;
        $data = array();

        if (is_array($option)) {
            if (isset($option['label'])) {
                $label = $option['label'];
            } elseif (isset($option['value'])) {
                $label = $option['value'];
            } else {
                $label = $option_value;
            }

            if (isset($option['value'])) {
                $option_value = $option['value'];
            }

            if (isset($option['disabled']) && (int) $option['disabled']) {
                $disabled = true;
            }

            if (isset($option['data']) && is_array($option['data'])) {
                $data = $option['data'];
            }

            if (isset($option['color'])) {
                $color = $option['color'];
            } elseif (isset($option['classes'])) {
                $color = \BimpTools::getAlertColor($option['classes'][0]);
            }
            if (isset($option['icon'])) {
                $icon = \BimpRender::renderIconClass($option['icon']);
            }
            if (isset($option['group'])) {
                $html .= '<optgroup label="' . (isset($option['group']['label']) ? $option['group']['label'] : '') . '"';
                if (!is_null($color)) {
                    $html .= ' data-color="' . $color . '" style="color: #' . $color . '"';
                }
                if (!is_null($icon)) {
                    $html .= ' data-icon_class="' . $icon . '"';
                }
                $html .= '>';
                if (isset($option['group']['options']) && is_array($option['group']['options'])) {
                    foreach ($option['group']['options'] as $opt_value => $opt) {
                        $html .= self::renderSelectOption($opt_value, $opt, $value);
                    }
                }
                $html .= '</optgroup>';
                return $html;
            }
        } else {
            $label = $option;
        }

        $html .= '<option value="' . $option_value . '"';
        if ((string) $value == (string) $option_value) {
            $html .= ' selected="1"';
        }
        if (!is_null($color)) {
            $html .= ' data-color="' . $color . '" style="color: #' . $color . '"';
        }
        if (!is_null($icon)) {
            $html .= ' data-icon_class="' . $icon . '"';
        }

        $html .= \BimpRender::renderTagData($data);

        if ($disabled) {
            $html .= ' disabled';
        }

        $html .= '>' . $label . '</option>';

        return $html;
    }
}
