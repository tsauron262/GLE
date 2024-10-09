<?php
namespace BC_V2;

class BimpComponent_old
{

    public static $subdir = '';
    public static $object_config_path = '';

    public function getDefinitions($path = '')
    {
        $key = 'bimpcore/components/' . (static::$subdir ? static::$subdir . '/' : '');
        if (BimpCache::cacheServerExists($key)) {
            return BimpTools::getArrayValueFromPath(BimpCache::getCacheServeur($key), $path, array());
        }

        return BimpConfig::getComponentConfigInstance($this)->getParams($path);
    }

    public function setObject(BimpObject $object, $object_config_name = null)
    {
        $errors = array();

        $this->object = $object;

        if (!is_null($object_config_name) && $this->isObjectValid()) {
            $this->object_config_name = $object_config_name;
            $errors = $this->loadParamsFromYml($this->object->config, static::$object_config_path . '/' . $object_config_name);
        } else {
            $errors[] = 'Objet invalide';
        }

        if (!empty($errors)) {
            $this->addErrors($errors);
        }

        return 0;
    }

    public function setParams($params)
    {
        
    }

    public function fetchParamsFromYmlConfig(BimpConfig $config, $path, &$errors = array(), $no_ajax_params = false, $no_default_values = false)
    {
        $this->params = array();
        $definitions = $this->getDefinitions('params');
        
        
        
        $this->params = static::fetchParamsFromYmlConfigStatic($config, $path, $this->getDefinitions('params'), $errors, $no_ajax_params, $no_default_values);
    }

    public static function fetchParamsFromYmlConfigStatic(BimpConfig $yml_config, $path, $definitions, &$errors = array(), $no_default_values = false)
    {
        $params = array();
        if (is_null($definitions) || is_null($yml_config)) {
            return array();
        }

        foreach ($definitions as $param_name => $params_def) {
            $params[$param_name] = self::fetchParamFromYmlConfigStatic($yml_config, $param_name, $definitions, $path, $errors, $no_default_values);
        }

        return $params;
    }

    protected function fetchParamFromYmlConfigStatic(BimpConfig $yml_config, $param_name, $definitions, $path, &$errors = array(), $no_default_values = false)
    {
        // $no_default_values = true: à utiliser pour surcharger des paramètres existants. 

        if (!isset($definitions[$param_name])) {
            $errors[] = 'Paramètre de configuration invalide: "' . $param_name . '" (définitions absentes)';
            return null;
        }

        $defs = $definitions[$param_name];

        if ($no_default_values) {
            $default_value = null;
            $required = false;
        } else {
            $default_value = (isset($defs['default']) ? $defs['default'] : null);
            $required = (isset($defs['required']) ? (bool) $defs['required'] : false);
        }

        $type = (isset($defs['type']) ? $defs['type'] : 'value');

        $param = null;

        if (is_null($param)) {
            switch ($type) {
                case 'value':
                    $value = null;
                    $data_type = isset($defs['data_type']) ? $defs['data_type'] : 'string';

                    if (!is_null($path)) {
                        if ($data_type === 'array') {
                            $compile = isset($defs['compile']) ? (bool) $defs['compile'] : true;
                            if ($compile) {
                                $value = $yml_config->getCompiledParams($path . '/' . $param_name);
                                if (is_null($value)) {
                                    $value = $default_value;
                                }
                            }
                        } else {
                            $value = $yml_config->get($path . '/' . $param_name, $default_value, $required, $data_type);
                        }
                    } else {
                        $value = $default_value;
                    }
                    if (!is_null($value)) {
                        if (self::validateParam($param_name, $value, $definitions, $errors)) {
                            $param = $value;
                        }
                    } elseif ($required) {
                        $errors[] = 'Paramètre obligatoire "' . $path . '/' . $param_name . '" absent du fichier de configuration';
                    }
                    break;

                case 'definitions':
                    if (!is_null($path)) {
                        $defs_type = isset($defs['defs_type']) ? $defs['defs_type'] : null;
                        $multiple = isset($defs['multiple']) ? (bool) $defs['multiple'] : false;

                        if (!is_null($defs_type)) {
                            if (property_exists('BimpConfigDefinitions', $defs_type)) {
                                if (!$yml_config->isDefined($path . '/' . $param_name) && $required) {
                                    $errors[] = 'Paramètre obligatoire "' . $path . '/' . $param_name . '" absent du fichier de configuration';
                                } else {
                                    if ($multiple) {
                                        $param = array();
                                        foreach ($yml_config->get($path . '/' . $param_name, array(), false, 'array') as $key => $values) {
                                            $param[$key] = self::fetchParamsFromYmlConfigStatic($yml_config, $path . '/' . $param_name . '/' . $key, BimpConfigDefinitions::${$defs_type}, $errors, $no_default_values);
                                        }
                                    } else {
                                        $param = self::fetchParamFromYmlConfigStatic($yml_config, $path . '/' . $param_name, BimpConfigDefinitions::${$defs_type}, $errors, $no_default_values);
                                    }
                                }
                            } else {
                                $errors[] = 'Type de définitions invalide pour le paramètre "' . $param_name . '" (' . $defs_type . ')';
                            }
                        } else {
                            $errors[] = 'Type de définitions absent pour le paramètre "' . $param_name . '"';
                        }
                    }
                    break;

                case 'component':
                    $component_name = $defs['component'];
                    if (!class_exists($component_name)) {
                        $errors[] = 'Type de composant inexistant : "' . $component_name . '"';
                        $component = new $component_name($this->object);
                    }
                    break;
            }
        }

        return $param;
    }

    public function fetchParams($params, &$errors = array(), $no_ajax_params = false, $no_default_values = false)
    {
        $this->params = static::fetchParamsStatic($this->getConfigParams('params'), $params, $errors, $no_ajax_params, $no_default_values);
    }

    public static function fetchParamsStatic($definitions, $params, &$errors = array(), $no_ajax_params = false, $no_default_values = false)
    {
        $return = array();

        foreach ($definitions as $param_name => $params_def) {
            if ($no_default_values) {
                $default_value = null;
                $required = false;
            } else {
                $default_value = (isset($params_def['default']) ? $params_def['default'] : null);
                $required = (isset($params_def['required']) ? (bool) $params_def['required'] : false);
            }

            $type = (isset($params_def['type']) ? $params_def['type'] : 'value');

            $param = null;

            if (!$no_ajax_params) {
                $request = isset($params_def['request']) ? (bool) $params_def['request'] : false;
                if ($request) {
                    $json = isset($params_def['json']) ? (bool) $params_def['json'] : false;
                    if (BimpTools::isSubmit('param_' . $name)) {
                        $param = BimpTools::getValue('param_' . $name, null, 'json');
                        if ($json && !is_null($param) && is_string($param)) {
                            $param = json_decode($param, true);
                        }
                    }
                }
            }

            if (is_null($param)) {
                switch ($type) {
                    case 'value':
                        $value = null;
                        $data_type = isset($params_def['data_type']) ? $params_def['data_type'] : 'string';

                        if (!is_null($path)) {
                            if ($data_type === 'array') {
                                $compile = isset($params_def['compile']) ? (bool) $params_def['compile'] : true;
                                if ($compile) {
                                    $value = $config->getCompiledParams($path . '/' . $name);
                                    if (is_null($value)) {
                                        $value = $default_value;
                                    }
                                }
                            } else {
                                $value = $config->get($path . '/' . $name, $default_value, $required, $data_type);
                            }
                        } else {
                            $value = $default_value;
                        }
                        if (!is_null($value)) {
                            if (self::validateParam($name, $value, $definitions, $errors)) {
                                $param = $value;
                            }
                        } elseif ($required) {
                            $errors[] = 'Paramètre obligatoire "' . $path . '/' . $name . '" absent du fichier de configuration';
                        }
                        break;

                    case 'object':
                        if (!is_null($path)) {
                            $param = $config->getObject($path . '/' . $name);
                        }
                        break;

                    case 'keys':
                        if (!is_null($path)) {
                            $values = $config->get($path . '/' . $name, array(), $required, 'array');
                            $param = array();
                            if (is_array($values) && !empty($values)) {
                                foreach ($values as $key => $val) {
                                    $param[] = $key;
                                }
                            }
                        }
                        break;

                    case 'definitions':
                        if (!is_null($path)) {
                            $defs_type = isset($params_def['defs_type']) ? $params_def['defs_type'] : null;
                            $multiple = isset($params_def['multiple']) ? (bool) $params_def['multiple'] : false;

                            if (!is_null($defs_type)) {
                                if (property_exists('BimpConfigDefinitions', $defs_type)) {
                                    if (!$config->isDefined($path . '/' . $name) && $required) {
                                        $errors[] = 'Paramètre obligatoire "' . $path . '/' . $name . '" absent du fichier de configuration';
                                    } else {
                                        if ($multiple) {
                                            $param = array();
                                            foreach ($config->get($path . '/' . $name, array(), false, 'array') as $key => $values) {
                                                $param[$key] = self::fetchParamsStatic($config, $path . '/' . $name . '/' . $key, BimpConfigDefinitions::${$defs_type}, $errors, $no_ajax_params, $no_default_values);
                                            }
                                        } else {
                                            $param = self::fetchParamsStatic($config, $path . '/' . $name, BimpConfigDefinitions::${$defs_type}, $errors, $no_ajax_params, $no_default_values);
                                        }
                                    }
                                } else {
                                    $errors[] = 'Type de définitions invalide pour le paramètre "' . $name . '" (' . $defs_type . ')';
                                }
                            } else {
                                $errors[] = 'Type de définitions absent pour le paramètre "' . $name . '"';
                            }
                        }
                        break;
                }
            }
        }
    }

    protected static function fetchParamStatic(BimpConfig $config, $name, $definitions, $path, &$errors = array(), $no_ajax_params = false, $no_default_values = false)
    {
        // $no_default_values = true: à utiliser pour surcharger des paramètres existants. 

        if (!isset($definitions[$name])) {
            $errors[] = 'Paramètre de configuration invalide: "' . $name . '" (définitions absentes)';
            return null;
        }

        $defs = $definitions[$name];

        if ($no_default_values) {
            $default_value = null;
            $required = false;
        } else {
            $default_value = (isset($defs['default']) ? $defs['default'] : null);
            $required = (isset($defs['required']) ? (bool) $defs['required'] : false);
        }

        $type = (isset($defs['type']) ? $defs['type'] : 'value');

        $param = null;

        if (!$no_ajax_params) {
            $request = isset($defs['request']) ? (bool) $defs['request'] : false;
            if ($request) {
                $json = isset($defs['json']) ? (bool) $defs['json'] : false;
                if (BimpTools::isSubmit('param_' . $name)) {
                    $param = BimpTools::getValue('param_' . $name, null, 'json');
                    if ($json && !is_null($param) && is_string($param)) {
                        $param = json_decode($param, true);
                    }
                }
            }
        }

        if (is_null($param)) {
            switch ($type) {
                case 'value':
                    $value = null;
                    $data_type = isset($defs['data_type']) ? $defs['data_type'] : 'string';

                    if (!is_null($path)) {
                        if ($data_type === 'array') {
                            $compile = isset($defs['compile']) ? (bool) $defs['compile'] : true;
                            if ($compile) {
                                $value = $config->getCompiledParams($path . '/' . $name);
                                if (is_null($value)) {
                                    $value = $default_value;
                                }
                            }
                        } else {
                            $value = $config->get($path . '/' . $name, $default_value, $required, $data_type);
                        }
                    } else {
                        $value = $default_value;
                    }
                    if (!is_null($value)) {
                        if (self::validateParam($name, $value, $definitions, $errors)) {
                            $param = $value;
                        }
                    } elseif ($required) {
                        $errors[] = 'Paramètre obligatoire "' . $path . '/' . $name . '" absent du fichier de configuration';
                    }
                    break;

                case 'object':
                    if (!is_null($path)) {
                        $param = $config->getObject($path . '/' . $name);
                    }
                    break;

                case 'keys':
                    if (!is_null($path)) {
                        $values = $config->get($path . '/' . $name, array(), $required, 'array');
                        $param = array();
                        if (is_array($values) && !empty($values)) {
                            foreach ($values as $key => $val) {
                                $param[] = $key;
                            }
                        }
                    }
                    break;

                case 'definitions':
                    if (!is_null($path)) {
                        $defs_type = isset($defs['defs_type']) ? $defs['defs_type'] : null;
                        $multiple = isset($defs['multiple']) ? (bool) $defs['multiple'] : false;

                        if (!is_null($defs_type)) {
                            if (property_exists('BimpConfigDefinitions', $defs_type)) {
                                if (!$config->isDefined($path . '/' . $name) && $required) {
                                    $errors[] = 'Paramètre obligatoire "' . $path . '/' . $name . '" absent du fichier de configuration';
                                } else {
                                    if ($multiple) {
                                        $param = array();
                                        foreach ($config->get($path . '/' . $name, array(), false, 'array') as $key => $values) {
                                            $param[$key] = self::fetchParamsStatic($config, $path . '/' . $name . '/' . $key, BimpConfigDefinitions::${$defs_type}, $errors, $no_ajax_params, $no_default_values);
                                        }
                                    } else {
                                        $param = self::fetchParamsStatic($config, $path . '/' . $name, BimpConfigDefinitions::${$defs_type}, $errors, $no_ajax_params, $no_default_values);
                                    }
                                }
                            } else {
                                $errors[] = 'Type de définitions invalide pour le paramètre "' . $name . '" (' . $defs_type . ')';
                            }
                        } else {
                            $errors[] = 'Type de définitions absent pour le paramètre "' . $name . '"';
                        }
                    }
                    break;
            }
        }

        return $param;
    }

    protected static function validateParam($name, &$value, $definitions, &$errors = array())
    {
        if (is_null($definitions) || !is_array($definitions) || !array_key_exists($name, $definitions)) {
            $errors[] = 'Paramètre de configuration invalide: "' . $name . '" (définitions absentes)';
            return false;
        }

        $defs = $definitions[$name];

        if (isset($defs['type']) && $defs['type'] !== 'value') {
            return true;
        }

        if (is_null($value) || ($value === '')) {
            if (isset($defs['required']) && (bool) $defs['required']) {
                $errors[] = 'Paramètre de configuration obligatoire absent: "' . $name . '"';
                return false;
            }
            $value = '';
        }

        if (isset($defs['values']) && !array_key_exists($value, $defs['values'])) {
            if (!in_array($value, $defs['allowed'])) {
                $errors[] = 'Valeur du paramètre "' . $name . '" non autorisée (' . $value . ')';
                $value = '';
                return false;
            }
        }

        if (isset($defs['allowed']) && is_array($defs['allowed'])) {
            if (!in_array($value, $defs['allowed'])) {
                $errors[] = 'Valeur du paramètre "' . $name . '" non autorisée (' . $value . ')';
                $value = '';
                return false;
            }
        }

        if (BimpCore::isModeDev()) {
            $type = (isset($defs['data_type']) ? $defs['data_type'] : 'string');
            if (!BimpTools::checkValueByType($type, $value)) {
                $errors[] = 'Paramètre de configuration invalide: "' . $name . '" (doit être de type "' . $type . '")';
                return false;
            }
        }

        return true;
    }

    public function addErrors($errors)
    {
        
    }
}
