<?php

namespace BC_V2;

class BimpComponent
{

    protected static $definitions = null;
    public static $debug = false;
    public static $component_name = 'BimpComponent';
    public static $subdir = '';
    public static $is_grid_element = false;

    // Gestion définitions : 

    protected static function areDefinitionsInit()
    {
        return (!is_null(static::$definitions));
    }

    public static function initDefinitions()
    {
        if (is_null(static::$definitions)) {
            static::$definitions = BC_Definitions::getComponentDefinitions((static::$subdir ? static::$subdir . '/' : '') . static::$component_name);
        }
    }

    public static function getParamDefinitions($param_name)
    {
        self::initDefinitions();

        return (isset(static::$definitions['params'][$param_name]) ? static::$definitions['params'][$param_name] : array());
    }

    public static function printDefinitions()
    {
        echo static::$component_name . ' defs: <pre>';
        print_r(static::$definitions);
        echo '</pre>';
    }

    // Gestion paramètres : 

    protected static function getParam($param_name, &$params, $default_value = null, $defs = null)
    {
        if (is_null($defs)) {
            $defs = self::getParamDefinitions($param_name);
        }

        $type = (isset($defs['type']) ? $defs['type'] : '');

        switch ($type) {
            default:
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
                    if (\BimpTools::checkValueByType((isset($defs['data_type']) ? $defs['data_type'] : 'string'), $value, $errors)) {
                        if (isset($defs['values'])) {
                            if (!isset($defs['values'][$value])) {
                                $errors[] = 'Valeur non autorisée pour ce paramètre';
                            } elseif (isset($defs['array_value']) && (int) $defs['array_value']) {
                                $value = $defs['values'][$value];
                            }
                        }
                    }

                    if (count($errors)) {
                        \BimpCore::addlog('Paramètre composant : valeur invalide', 3, 'bimpcore', null, array(
                            'Composant' => static::$component_name,
                            'Param'     => $param_name,
                            'Erreurs'   => $errors,
                            'valeur'    => $value
                        ));
                    }
                }


                return $value;

            case 'params':
                $params_defs = BC_Definitions::getComponentDefinitions($defs['params_name'], array(), true);
                if (isset($defs['multiple']) && (int) $defs['multiple']) {
                    $return = array();
                    if (isset($params[$param_name])) {
                        foreach ($params[$param_name] as $item_params) {
                            $item = array();
                            foreach ($params_defs as $sub_param_name => $sub_param_defs) {
                                $item[$sub_param_name] = self::getParam($sub_param_name, $item_params, null, $sub_param_defs);
                            }
                            $return[] = $item;
                        }

                        return $return;
                    }
                } elseif (isset($params[$param_name])) {
                    $return = array();
                    foreach ($params_defs as $sub_param_name => $sub_param_defs) {
                        $return[$sub_param_name] = self::getParam($sub_param_name, $params[$param_name], null, $sub_param_defs);
                    }
                    return $return;
                }

                if (!is_null($default_value)) {
                    return $default_value;
                }

                return array();
        }
    }

    protected static function getGridColsValues(&$params)
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

    public static function setAttributes(&$params, &$attributes = array())
    {
        self::initDefinitions();

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
        if (isset(static::$definitions['params'])) {
            foreach (static::$definitions['params'] as $param_name => $param_defs) {
                if (isset($param_defs['in_data']) && (int) $param_defs['in_data']) {
                    $val = self::getParam($param_name, $params);
                    if (!is_null($val) && $val !== '') {
                        self::addData($attributes, $param_name, $val);
                    }
                }
            }
        }

        $on_loaded = self::getParam('on_loaded', $params);
        if ($on_loaded) {
            self::addData($attributes, 'on_loaded', $on_loaded, true);
        }

        $objects_change_reload = self::getParam('objects_change_reload', $params, array());
        if (!empty($objects_change_reload)) {
            if (is_array($objects_change_reload)) {
                $objects_change_reload = implode(',', $objects_change_reload);
            }
            self::addData($attributes, 'objects_change_reload', $objects_change_reload, true, ',');
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

        if (is_array($value)) {
            $value = htmlentities(json_encode($value));
        }

        if ($extends && isset($attributes['data'][$data_name]) && !empty($attributes['data'][$data_name])) {
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

    protected static function renderHtml(&$params, $content = '', &$errors = array())
    {
        $html = '';

        $show = self::getParam('show', $params);

        if (!self::$debug && !$show) {
            return '';
        }

        $content_only = self::getParam('content_only', $params);

        if (!$content_only) {
            $attributes = array();
            static::setAttributes($params, $attributes);
            $html .= '<div' . \BimpRender::renderTagAttrs($attributes) . '>';
        }

        if (self::$debug) {
            $html .= \BimpRender::renderFoldableContainer('Définitions', '<pre>' . print_r(static::$definitions, 1) . '</pre>', array('open' => false));
            $html .= \BimpRender::renderFoldableContainer('Paramètres', '<pre>' . print_r($params, 1) . '</pre>', array('open' => false));

            if (!$show) {
                $html .= '<span class="danger">Non affiché</span>';
            }
        }

        if ($show) {
            if (count($errors)) {
                $html .= \BimpRender::renderAlerts($errors);
            }

            $html .= self::getParam('before_content', $params);
            $html .= $content;
            $html .= self::getParam('after_content', $params);
        }

        if (!$content_only) {
            $html .= '</div>';
        }

        return $html;
    }

    public static function render(&$params)
    {
//        self::$debug = (\BimpCore::isUserDev() && (int) \BimpCore::getConf('components_debug'));

        self::initDefinitions();

        if (isset(static::$definitions['component_name_param']) && static::$definitions['component_name_param']) {
            $component_name = self::getParam(static::$definitions['component_name_param'], $params);
            if ($component_name) {
                $component_name = 'BC_V2\\' . $component_name;
                if (class_exists($component_name)) {
                    return $component_name::render($params);
                }
            }
        }

        $errors = array();
        return static::renderHtml($params, '', $errors);
    }

    protected static function renderComponent($param_name, &$params, &$errors = array())
    {
        $html = '';

//        if (isset($params[$param_name])) {
        $defs = self::getParamDefinitions($param_name);

        if (isset($defs['type']) && $defs['type'] === 'component') {
            $component_name = (isset($defs['component']) ? $defs['component'] : '');
            $multiple = (int) (isset($defs['multiple']) ? $defs['multiple'] : '');

            if ($component_name) {
                $component_name = 'BC_V2\\' . $component_name;
                if (class_exists($component_name)) {
                    $elements = array();

                    if ($multiple) {
                        $elements = (isset($params[$param_name]) ? $params[$param_name] : array());
                    } else {
                        if (isset($params[$param_name])) {
                            $elements = array($params[$param_name]);
                        } else {
                            $elements[] = array();
                        }
                    }

                    foreach ($elements as $element_key => &$element_params) {
                        $html .= $component_name::render($element_params);
                    }
                }
            }
        }
//        }


        return $html;
    }

    // Divers : 

    public static function getLabel($type = '', $ucfirst = false)
    {
        $label = '';

        self::initDefinitions();

        if (isset(static::$definitions['labels']['name'])) {
            $labels = static::$definitions['labels'];

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
