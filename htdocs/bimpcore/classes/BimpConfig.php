<?php

class BimpConfig
{

    public $dir;
    public $file;
    public $params = array();
    public $current_path = '';
    public $current = null;
    public $errors = array();

    public function __construct($dir, $file_name)
    {
        $this->params = array();
        $this->errors = array();

        if (!preg_match('/^.+\.yml$/', $file_name)) {
            $file_name .= '.yml';
        }

        $this->file = $file_name;

        if (!preg_match('/^.+\/$/', $dir)) {
            $dir .= '/';
        }
        $this->dir = $dir;

        if (!file_exists($dir . $file_name)) {
            $this->logConfigError('Erreur technique: le fichier de configuration "' . $file_name . '" n\'existe pas');
            return false;
        }

        $this->params = spyc_load_file($dir . $file_name);
        if (is_array($this->params) && count($this->params)) {
            return true;
        }

        $this->params = array();
        $this->logConfigError('Echec du chargement de la configuration depuis le fichier YAML "' . $file_name . '"');
        return false;
    }

    public function isDefined($path)
    {
        $path = explode('/', $path);

        $currentPath = '';
        $current = $this->params;
        foreach ($path as $key) {
            if ($key === '') {
                continue;
            }
            $currentPath .= $key . '/';
            if (isset($current[$key])) {
                $current = $current[$key];
            } else {
                return false;
            }
        }
        return true;
    }

    public function isDefinedCurrent($path_from_current)
    {
        return $this->isDefined($this->current_path . $path_from_current);
    }

    public function setCurrentPath($path = '')
    {
        $this->current = $this->params;
        $this->current_path = '';

        if ($path === '') {
            return true;
        }

        $path = explode('/', $path);

        $currentPath = '';
        $current = $this->params;
        foreach ($path as $key) {
            if ($key === '') {
                continue;
            }
            $currentPath .= $key . '/';
            if (isset($current[$key])) {
                $current = $current[$key];
            } else {
                return false;
            }
        }
        $this->current_path = $currentPath;
        $this->current = $current;
        return true;
    }

    public function getPathPrevLevel($path)
    {
        $path = explode('/', $path);
        array_pop($path);
        return implode('/', $path);
    }

    public function get($full_path, $instance = null, $default_value = null, $required = false, $data_type = 'string')
    {
        if (is_null($full_path) || !$full_path) {
            return $default_value;
        }

        $path = explode('/', $full_path);

        $current = $this->params;

        foreach ($path as $key) {
            if ($key === '') {
                continue;
            }
            if (isset($current[$key])) {
                $current = $current[$key];
            } else {
                if ($required) {
                    $this->logConfigUndefinedValue($full_path);
                }
                return $default_value;
            }
        }

        $value = $this->getvalue($current, $instance, $full_path . '/');

        if (is_null($value)) {
            $value = $default_value;
        }

        if (!$this->checkValueDataType($value, $data_type)) {
            $this->logInvalideDataType($full_path, $data_type);
            $value = null;
        }
        
        return $value;
    }

    public function getFromCurrentPath($path_from_current, $instance = null, $default_value = null, $required = true, $data_type = 'string')
    {
        return $this->get($this->current_path . $path_from_current, $instance, $default_value, $required, $data_type);
    }

    protected function getvalue($value, $instance = null, $path = '')
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            if (array_key_exists('prop', $value)) {
                return $this->getProp($value['prop'], $instance, $path . 'prop/');
            }
            if (array_key_exists('field_value', $value)) {
                return $this->getFieldValue($value['field_value'], $instance, $path . 'field_value/');
            }
            if (array_key_exists('array', $value)) {
                return $this->getArray($value['array'], $instance, $path . 'array/');
            }
            if (array_key_exists('array_value', $value)) {
                return $this->getArrayValue($value['array_value'], $instance, $path . 'array_value/');
            }
            if (array_key_exists('instance', $value)) {
                return $this->getInstance($value['instance'], $instance, $path . 'instance/');
            }
            if (array_key_exists('callback', $value)) {
                return $this->processCallback($value['callback'], $instance, $path . 'callback/');
            }
            if (array_key_exists('global', $value)) {
                global ${$value['global']};
                if (isset(${$value['global']}) && ${$value['global']}) {
                    return ${$value['global']};
                }
            }
            if (array_key_exists('request', $value)) {
                $request = $this->getvalue($value['request'], $instance, $path . 'request/');
                return BimpTools::getValue($request);
            }
        }
        return $value;
    }

    protected function getBoolValue($value, $instance = null, $path = '')
    {
        $value = $this->getvalue($value, $instance, $path);
        if (!$this->checkValueDataType($value, 'bool')) {
            return null;
        }
        return $value;
    }

    protected function getIntValue($value, $instance = null, $path = '')
    {
        $value = $this->getvalue($value, $instance, $path);
        if (!$this->checkValueDataType($value, 'int')) {
            return null;
        }
        return $value;
    }

    protected function getFloatValue($value, $instance = null, $path = '')
    {
        $value = $this->getvalue($value, $instance, $path);
        if (!$this->checkValueDataType($value, 'float')) {
            return null;
        }
        return $value;
    }

    protected function getInstance($params, $associate_instance = null, $path = '')
    {
        $instance = null;

        if (is_string($params)) {
            if ($params === 'object') {
                $up_path = $path;
                while ($up_path) {
                    $up_path = $this->getPathPrevLevel($up_path);
                    if ($this->isDefined($up_path . '/object')) {
                        return $this->get($up_path . '/object', $associate_instance, null, false, 'object');
                    }
                }
                return null;
            }
            if (class_exists($params)) {
                return new $params();
            }
            return null;
        }

        if (is_array($params)) {
            if (isset($params['bimp_object'])) {
                $module_name = null;
                $object_name = null;
                if (is_string($params['bimp_object'])) {
                    if (is_a($associate_instance, 'BimpObject')) {
                        $module_name = $associate_instance->module;
                    } elseif (is_a($associate_instance, 'BimpController')) {
                        $module_name = $associate_instance->module;
                    }
                    $object_name = $params['bimp_object'];
                } elseif (is_array($params['bimp_object'])) {
                    if (isset($params['bimp_object']['module'])) {
                        $module_name = $this->getvalue($params['bimp_object']['module'], $associate_instance, $path . 'bimp_object/module/');
                    }
                    if (isset($params['bimp_object']['object'])) {
                        $object_name = $this->getvalue($params['bimp_object']['object'], $associate_instance, $path . 'bimp_object/object/');
                    }
                }

                if (is_null($module_name)) {
                    $this->logConfigUndefinedValue($path . 'bimp_object/module/');
                    return null;
                }

                if (is_null($object_name)) {
                    $this->logConfigUndefinedValue($path . 'bimp_object/object/');
                    return null;
                }

                $instance = BimpObject::getInstance($module_name, $object_name);
            } elseif (isset($params['dol_object'])) {
                $module = null;
                $file = null;
                $className = null;

                if (is_string($params['dol_object'])) {
                    $module = $file = $params['dol_object'];
                    $className = ucfirst($file);
                } elseif (is_array($params['dol_object'])) {
                    if (isset($params['dol_object']['module'])) {
                        $module = $this->getvalue($params['dol_object']['module'], $associate_instance, $path . 'dol_object/module/');
                    }
                    if (is_null($module)) {
                        $this->logConfigUndefinedValue($path . 'dol_object/module/');
                        return null;
                    }

                    if (isset($params['dol_object']['file'])) {
                        $file = $this->getvalue($params['dol_object']['file'], $associate_instance, $path . 'dol_object/file/');
                        if (is_null($file)) {
                            $this->logConfigUndefinedValue($path . 'dol_object/file/');
                            return null;
                        }
                    } else {
                        $file = $module;
                    }

                    if (isset($params['dol_object']['class'])) {
                        $className = $this->getvalue($params['dol_object']['class'], $associate_instance, $path . 'dol_object/class/');
                        if (is_null($className)) {
                            $this->logConfigUndefinedValue($path . 'dol_object/class/');
                            return null;
                        }
                    } else {
                        $className = ucfirst($file);
                    }
                }
                if (!is_nan($module) && !is_null($file) && !is_null($className)) {
                    if (!class_exists($className)) {
                        $file_path = DOL_DOCUMENT_ROOT . $module . '/class/' . $file . '.class.php';
                        if (file_exists($file_path)) {
                            require_once $file_path;
                        }

                        if (!class_exists($className)) {
                            $this->logConfigError('Class "' . $className . '" inexistante');
                            return null;
                        }
                    }


                    global $db;
                    $instance = new $className($db);
                }
            } elseif (isset($params['custom_object'])) {
                $class_name = null;

                if (isset($params['custom_object']['class_name'])) {
                    $class_name = $this->getvalue($params['custom_object']['class_name'], $associate_instance, $path . 'custom_object/class_name/');
                }

                if (is_null($class_name)) {
                    $this->logConfigUndefinedValue($path . 'custom_object/class_name/');
                    return null;
                }

                if (!class_exists($class_name)) {
                    $class_path = null;
                    if (isset($params['custom_object']['class_path'])) {
                        $class_path = $this->getvalue($params['custom_object']['class_path'], $associate_instance, $path . 'custom_object/class_path/');
                    }

                    if (is_null($class_path)) {
                        $this->logConfigUndefinedValue($path . 'custom_object/class_path/');
                        return null;
                    }

                    if (file_exists($class_path)) {
                        require_once DOL_DOCUMENT_ROOT . $class_path;
                    }

                    if (!class_exists($class_name)) {
                        $this->logConfigError('Classe "' . $class_name . '" inexistante');
                        return null;
                    }
                }

                $construct_params = array();
                if (isset($params['construct_params'])) {
                    $construct_params = $this->getArray($params['construct_params'], $associate_instance, $path . 'construct_params/');
                    if (is_null($construct_params)) {
                        $this->logConfigUndefinedValue($path . 'construct_params/');
                        return null;
                    }
                }

                $args = '';
                $first = true;
                foreach ($construct_params as $key => $value) {
                    if (!$first) {
                        $args .= ', ';
                    } else {
                        $first = false;
                    }
                    $args .= '$construct_params[' . $key . ']';
                }

                eval('$instance = new $class_name(' . $args . ');');
            } else {
                $instance = null;
                $this->logConfigUndefinedValue($path);
            }

            if (!is_null($instance)) {
                if (isset($params['fetch'])) {
                    $fetch_params = $this->getArray($params['fetch'], $associate_instance, $path . 'fetch/');
                    if (!is_null($fetch_params) && is_array($fetch_params)) {
                        if ($result = call_user_func_array(array(
                                    $instance, 'fetch'
                                        ), $fetch_params) <= 0) {
                            $this->logConfigError('Echec de fetch() sur l\'objet "' . get_class($instance) . '" - Paramètres: <pre>' . print_r($fetch_params, 1) . '</pre>');
                            return null;
                        }
                    }
                } elseif (isset($params['id_object'])) {
                    $id_object = $this->getvalue($params['id_object'], $associate_instance, $path . 'id_object/');
                    if (is_null($id_object)) {
                        $this->logConfigUndefinedValue($path . 'id_object');
                        unset($instance);
                        return null;
                    }
                    if (method_exists($instance, 'fetch')) {
                        if ($result = $instance->fetch($id_object) <= 0) {
                            $this->logConfigError('Echec de fetch() sur l\'objet "' . get_class($instance) . '" - ID: ' . $id_object);
                            unset($instance);
                            return null;
                        }
                    } else {
                        $this->logConfigError('La méthode "fetch()" n\'existe pas pour l\'objet "' . get_class($instance) . '"');
                        unset($instance);
                        return null;
                    }
                }
            }
        }

        return $instance;
    }

    protected function getFieldValue($field_value, $instance = null, $path = '')
    {
        if (is_string($field_value)) {
            if (!is_null($instance) && is_a($instance, 'BimpObject')) {
                return $instance->getData($field_value);
            }
        } elseif (is_array($field_value)) {
            $field_name = $this->getvalue($field_value['field_name'], $instance, $path . 'field_name/');
            if (!is_null($field_name)) {
                if (isset($field_value['instance'])) {
                    $instance = $this->getInstance($field_value['instance'], $instance, $path . 'instance/');
                    if (!is_null($instance) && is_a($instance, 'BimpObject')) {
                        return $instance->getData[$field_name];
                    }
                }
            }
        }

        return null;
    }

    protected function getProp($prop, $instance = null, $path = 'prop/')
    {
        $prop_name = null;
        $is_static = 0;

        if (is_string($prop)) {
            $prop_name = $prop;
        } elseif (is_array($prop)) {
            if (!isset($prop['prop_name'])) {
                $this->logConfigUndefinedValue($path . 'prop_name/');
            } else {
                $prop_name = $this->getvalue($prop['prop_name'], $instance, $path . 'prop_name/');
            }
            if (isset($prop['instance'])) {
                $instance = $this->getInstance($prop['instance'], $instance, $path . 'instance/');
            }
            if (isset($prop['is_static'])) {
                $is_static = $this->getBoolValue($prop['is_static'], $instance, $path . 'is_static/');
                if (is_null($is_static)) {
                    $is_static = false;
                }
            }
        }

        if (is_null($instance)) {
            if (!is_null($prop_name)) {
                $msg = 'Impossible d\'obtenir la propriété "' . $prop_name . '" - Instance invalide';
                $this->logConfigError($msg);
            }
            return null;
        }

        if (!is_null($prop_name)) {
            if (property_exists($instance, $prop_name)) {
                if ($is_static) {
                    return $instance::${$prop_name};
                } else {
                    return $instance->{$prop_name};
                }
            } else {
                $msg = 'La propriété "' . $prop_name . '" n\existe pas dans la classe "' . get_class($instance) . '"';
                $this->logConfigError($msg);
            }
        }

        return null;
    }

    protected function getArray($array, $instance = null, $path = 'array/')
    {
        if (is_array($array)) {
            if (isset($array['callback'])) {
                return $this->processCallback($array['callback'], $instance, $path . 'callback/');
            }
            if (isset($array['prop'])) {
                return $this->getProp($array['prop'], $instance, $path . 'prop/');
            }

            $return = array();
            foreach ($array as $key => $value) {
                $return[$key] = $this->getvalue($value, $instance, $path . $key . '/');
            }
            return $return;
        }

        if (is_string($array)) {
            if (!is_null($instance)) {
                if (property_exists($instance, $array)) {
                    if (isset($instance->{$array}) && is_array($instance->{$array})) {
                        return $instance->{$array};
                    } elseif (isset($instance::${$array}) && is_array($instance::${$array})) {
                        return $instance::${$array};
                    }
                    return array();
                }

                $method = 'get' . ucfirst($array) . 'Array';
                if (method_exists($instance, $method)) {
                    $result = $instance->{$method}();
                    if (is_array($result)) {
                        return $result;
                    }

                    return array();
                }
            }

            $rows = explode(',', $array);
            $return = array();
            foreach ($rows as $row) {
                if (preg_match('/^(.*)=>(.*)$/', $row, $matches)) {
                    $value = isset($matches[1]) ? $matches[1] : '';

                    if (!isset($matches[0]) || $matches[0] === '') {
                        $return[] = $value;
                    } else {
                        $return[$matches[1]] = $value;
                    }
                } else {
                    $return[] = $row;
                }
            }
            return $return;
        }

        return array();
    }

    protected function getArrayValue($params, $instance = null, $path = 'array_value/')
    {
        $key = null;
        if (!isset($params['key'])) {
            $this->logConfigUndefinedValue($path . 'key/');
        } else {
            $key = $this->getvalue($params['key'], $instance, $path . 'key/');
        }

        $array = null;
        if (!isset($params['array'])) {
            $this->logConfigUndefinedValue($path . 'array/');
        } else {
            $array = $this->getArray($params['array'], $instance, $path . 'array/');
        }

        if (is_null($key) || is_null($array) || !is_array($array)) {
            return null;
        }

        if (!array_key_exists($key, $array)) {
            return null;
        }

        if (is_array($array[$key])) {
            if (isset($array[$key]['label'])) {
                return $array[$key]['label'];
            } elseif (isset($array[$key]['value'])) {
                return $array[$key]['value'];
            } else {
                return null;
            }
        }
        return $array[$key];
    }

    protected function processCallback($callback, $instance = null, $path = 'callback/')
    {
        if (is_string($callback)) {
            if (!is_null($instance)) {
                if (method_exists($instance, $callback)) {
                    return $instance->{$callback}();
                } else {
                    $this->logConfigError('Méthode "' . $callback . '" inexistante dans la classe ' . get_class($instance));
                }
            } else {
                $this->logConfigError('Impossible d\'appeller la méthode "' . $callback . '" - Instance invalide');
            }
        } elseif (is_array($callback)) {
            $method = null;
            $is_static = false;

            if (!isset($callback['method'])) {
                $this->logConfigUndefinedValue($path . 'method/');
            } else {
                $method = $this->getvalue($callback['method'], $instance, $path . 'method/');
            }
            if (is_null($method)) {
                return null;
            }

            if (isset($callback['instance'])) {
                $instance = $this->getInstance($callback['instance'], $instance, $path . 'instance/');
            }
            if (is_null($instance)) {
                return null;
            }

            if (isset($callback['is_static'])) {
                $is_static = $this->getBoolValue($callback['is_static'], $instance, $path . 'is_static/');
            }

            $params = array();

            if (isset($callback['params'])) {
                $params = $this->getArray($callback['params'], $instance, $path . 'params/');
                if (is_null($params)) {
                    return null;
                }
            }

            if ($is_static) {
                return forward_static_call_array(array(
                    $instance, $method
                        ), $params);
            } else {
                return call_user_func_array(array(
                    $instance, $method
                        ), $params);
            }
        }

        return null;
    }

    protected function checkValueDataType(&$value, $data_type)
    {
        return BimpTools::checkValueByType($data_type, $value);
    }

    protected function logInvalideDataType($param_path, $data_type)
    {
        $msg = 'Type de valeur invalide pour le paramètre "' . $param_path . '"';
        $msg .= '. Type attendu: "' . $data_type . '"';
        $this->logConfigError($msg);
    }

    protected function logConfigUndefinedValue($param_path)
    {
        self::logConfigError('Paramètre "' . $param_path . '" non défini');
    }

    protected function logConfigError($msg)
    {
        $message = 'Erreur de configuration pour le fichier "' . $this->dir . $this->file . '" - ' . $msg;
        $this->errors[] = $msg;
        dol_syslog($message, LOG_ERR);
    }
}
