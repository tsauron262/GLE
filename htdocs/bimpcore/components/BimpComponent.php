<?php

namespace BC_V2;

class BimpComponent
{

    protected static $definitions = null;
    public static $component_name = 'BimpComponent';
    public static $subdir = '';
    public static $is_grid_element = false;

    // Gestion définitions : 

    public static function areDefinitionsInit()
    {
        return (!is_null(static::$definitions));
    }

    public static function getDefinitions()
    {
        if (is_null(static::$definitions)) {
            static::$definitions = BC_Definitions::getComponentDefinitions((static::$subdir ? static::$subdir . '/' : '') . static::$component_name);
        }

        return static::$definitions;
    }

    public static function getParamsDefinitions()
    {
        $defs = self::getDefinitions();
        if (isset($defs['params'])) {
            return $defs['params'];
        }

        return array();
    }

    public static function getParamDefinitions($param_name)
    {
        $defs = self::getDefinitions();
        return (isset($defs['params'][$param_name]) ? $defs['params'][$param_name] : array());
    }

    public static function printDefinitions()
    {
        echo static::$component_name . ' defs: <pre>';
        print_r(static::$definitions);
        echo '</pre>';
    }

    // Gestion paramètres : 

    public static function getParam($param_name, $params, $default_value = null)
    {
        $defs = self::getParamDefinitions($param_name);

        if (isset($params[$param_name])) {
            $value = $params[$param_name];
        } elseif (!is_null($default_value)) {
            $value = $default_value;
        } else {
            if (isset($defs['required']) && $defs['required']) {
                \BimpCore::addlog('Paramètre composant obligatoire absent', 3, 'bimpcore', null, array(
                    'Composant' => static::$component_name,
                    'Param'     => $param_name
                ));
            }
            if (isset($defs['default'])) {
                $value = $defs['default'];
            }
        }

        if (!is_null($value)) {
            $errors = array();
            if (!\BimpTools::checkValueByType((isset($defs['data_type']) ? $defs['data_type'] : 'string'), $value, $errors)) {
                if (count($errors)) {
                    \BimpCore::addlog('Paramètre composant : valeur invalide', 3, 'bimpcore', null, array(
                        'Composant' => static::$component_name,
                        'Param'     => $param_name,
                        'valeur'    => $value
                    ));
                }
            }
        }

        return $value;
    }

    public static function getGridColsValues($params)
    {
        if (static::$is_grid_element) {
            return array(
                'col_sm' => self::getParam('col_sm', $params),
                'col_md' => self::getParam('col_md', $params),
                'col_lg' => self::getParam('col_lg', $params)
            );
        }

        return array();
    }

    // Gestion Attributs HTML : 

    public static function setAttributes($params, &$attributes = array())
    {
        // Classes : 

        self::addClass($attributes, self::$component_name);

        $cols = self::getGridColsValues($params);
        if (!empty($cols)) {
            self::addClass($attributes, 'col-sm-' . $cols['col_sm']);
            self::addClass($attributes, 'col-md-' . $cols['col_md']);
            self::addClass($attributes, 'col-lg-' . $cols['col_lg']);
        }

        if ((int) self::getParam('no_reload', $params)) {
            self::addClass($attributes, 'no_reload');
        }

        $extra_classes = self::getParam('extra_classes', $params);
        if (!empty($extra_classes)) {
            foreach ($extra_classes as $class_name) {
                self::addClass($attributes, $class_name);
            }
        }

        // Data :
        $defs = self::getDefinitions();
        foreach ($defs['params'] as $param_name => $param_defs) {
            if (isset($param_defs['in_data']) && (int) $param_defs['in_data']) {
                $val = self::getParam($param_name, $params);
                if (!is_null($val) && $val !== '') {
                    self::addData($attributes, $param_name, $val);
                }
            }
        }

        $on_loaded = self::getParam('on_loaded', $params);
        if ($on_loaded) {
            self::addData($attributes, 'on_loaded', $on_loaded, true);
        }

        $objects_change_reload = self::getParam('objects_change_reload', $params, array());
        if (!empty($objects_change_reload)) {
            self::addData($attributes, 'objects_change_reload', implode(',', $objects_change_reload), true, ',');
        }

        $extra_data = self::getParam('extra_data', $params);
        if (!empty($extra_data)) {
            foreach ($extra_data as $data_name => $value) {
                self::addData($attributes, $data_name, $value, false);
            }
        }

        // Styles: 
        $extra_styles = self::getParam('extra_styles', $params);
        if (!empty($extra_styles)) {
            foreach ($extra_styles as $style_name => $value) {
                self::addStyle($attributes, $style_name, $value);
            }
        }
    }

    public static function addAttribute(&$attributes, $attr_name, $value, $extends = true, $extend_separator = ';')
    {
        if (!isset($attributes['attr'])) {
            $attributes['attr'] = array();
        }

        if ($extends && isset($attributes['attr'][$attr_name]) && $attributes['attr'][$attr_name]) {
            $attributes['attr'][$attr_name] .= $extend_separator . $value;
        } else {
            $attributes['attr'][$attr_name] = $value;
        }
    }

    public static function addClass(&$attributes, $class_name)
    {
        if (!isset($attributes['classes'])) {
            $attributes['classes'] = array();
        }

        if (!in_array($class_name, $attributes['classes'])) {
            $attributes['classes'][] = $class_name;
        }
    }

    public static function addData(&$attributes, $data_name, $value, $extends = true, $extend_separator = ';')
    {
        if (!isset($attributes['data'])) {
            $attributes['data'] = array();
        }

        if ($extends && isset($attributes['data'][$data_name]) && $attributes['data'][$data_name]) {
            $attributes['data'][$data_name] .= $extend_separator . $value;
        } else {
            $attributes['data'][$data_name] = $value;
        }
    }

    public static function addStyle(&$attributes, $name, $value)
    {
        if (!isset($attributes['styles'])) {
            $attributes['styles'] = array();
        }

        $attributes['styles'][$name] = $value;
    }

    // Rendu HTML : 

    public static function render($params, $content = '')
    {
        if (!self::getParam('show', $params)) {
            return '';
        }

        $html = '';

        $content_only = self::getParam('content_only', $params);

        if (!$content_only) {
            $attributes = array();
            static::setAttributes($params, $attributes);
            $html .= '<div' . \BimpRender::renderTagAttrs($attributes) . '>';
        }

        $html .= self::getParam('before_content', $params);
        $html .= $content;
        $html .= self::getParam('after_content', $params);

        if (!$content_only) {
            $html .= '</div>';
        }

        return $html;
    }

    // Divers : 

    public static function getLabel($type = '', $ucfirst = false)
    {
        $label = '';

        $defs = self::getDefinitions();

        if (isset($defs['labels']['name'])) {
            $labels = $defs['labels'];

            $component_name = $labels['name'];

            $vowel_first = false;
            if (preg_match('/^[aàâäeéèêëiîïoôöuùûüyŷÿ](.*)$/', strtolower($component_name))) {
                $vowel_first = true;
            }

            $name_plur = '';

            if (!isset($labels['name_plur'])) {
                if (preg_match('/^.*[ao]u$/', $component_name)) {
                    $name_plur = $component_name . 'x';
                } elseif (preg_match('/^.*ou$/', $component_name)) {
                    $name_plur = $component_name . 'x';
                } elseif (!preg_match('/^.*s$/', $component_name)) {
                    $name_plur = $component_name . 's';
                }
            } else {
                $name_plur = $labels['name_plur'];
            }

            if (isset($labels['is_female'])) {
                $isFemale = $labels['is_female'];
            } else {
                $isFemale = false;
            }
        } else {
            $component_name = 'composant ' . static::$component_name;
            $name_plur = 'composants ' . static::$component_name;
            $isFemale = false;
            $vowel_first = false;
        }

        switch ($type) {
            case '':
            default:
                $label = $component_name;
                break;

            case 'name_plur':
                $label = $name_plur;
                break;

            case 'the':
                if ($vowel_first) {
                    $label = 'l\'' . $component_name;
                } elseif ($isFemale) {
                    $label = 'la ' . $component_name;
                } else {
                    $label = 'le ' . $component_name;
                }
                break;

            case 'a':
                if ($isFemale) {
                    $label = 'une ' . $component_name;
                } else {
                    $label = 'un ' . $component_name;
                }
                break;

            case 'to':
                if ($vowel_first) {
                    $label = 'à l\'' . $component_name;
                } elseif ($isFemale) {
                    $label = 'à la ' . $component_name;
                } else {
                    $label = 'au ' . $component_name;
                }
                break;

            case 'this':
                if ($isFemale) {
                    $label = 'cette ' . $component_name;
                } elseif ($vowel_first) {
                    $label = 'cet ' . $component_name;
                } else {
                    $label = 'ce ' . $component_name;
                }
                break;

            case 'of_a':
                if ($isFemale) {
                    $label = 'd\'une ' . $component_name;
                } else {
                    $label = 'd\'un ' . $component_name;
                }
                break;

            case 'of_the':
                if ($vowel_first) {
                    $label = 'de l\'' . $component_name;
                } elseif ($isFemale) {
                    $label = 'de la ' . $component_name;
                } else {
                    $label = 'du ' . $component_name;
                }
                break;

            case 'of_this':
                if ($isFemale) {
                    $label = 'de cette ' . $component_name;
                } elseif ($vowel_first) {
                    $label = 'de cet ' . $component_name;
                } else {
                    $label = 'de ce ' . $component_name;
                }
                break;

            case 'the_plur':
                $label = 'les ' . $name_plur;
                break;

            case 'of_those':
                $label = 'de ces ' . $name_plur;
                break;

            case 'of_plur':
                if ($vowel_first) {
                    $label = 'd\'' . $name_plur;
                } else {
                    $label = 'de ' . $name_plur;
                }
                break;

            case 'all_the':
                if ($isFemale) {
                    $label = 'toutes les ' . $name_plur;
                } else {
                    $label = 'tous les ' . $name_plur;
                }
                break;
        }

        return ($ucfirst ? ucfirst($label) : $label);
    }
}
