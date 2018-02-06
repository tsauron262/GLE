<?php

class BimpConfig
{

    public $dir;
    public $file;
    public $instance = null;
    public $objects = array();
    public $params = array();
    public $current_path = '';
    public $current = null;
    public $errors = array();
    public static $keywords = array(
        'prop', 'field_value', 'array', 'array_value', 'instance', 'callback', 'global', 'request', 'dol_list', 'conf'
    );

    public function __construct($dir, $file_name, $instance)
    {
        $this->params = array();
        $this->errors = array();

        $this->instance = $instance;

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

    // Gestion des chemins de configuration: 

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

    // récupération des données de configuration: 

    public function get($full_path, $default_value = null, $required = false, $data_type = 'string')
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

        $value = $this->getvalue($current, $full_path);

        if (is_null($value)) {
            $value = $default_value;
        }

        if (!$this->checkValueDataType($value, $data_type)) {
            $this->logInvalideDataType($full_path, $data_type);
            $value = null;
        }

        return $value;
    }

    public function getCompiledParams($full_path)
    {
        $params = $this->getParams($full_path);
        return $this->compileParams($params, $full_path);
    }

    public function getCompiledParamsfromCurrentPath($path_from_current)
    {
        return $this->getCompiledParams($this->current_path . $path_from_current);
    }

    public function getParams($full_path)
    {
        if (is_null($full_path) || !$full_path) {
            return null;
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
                return null;
            }
        }

        return $current;
    }

    public function getFromCurrentPath($path_from_current = '', $default_value = null, $required = true, $data_type = 'string')
    {
        return $this->get($this->current_path . $path_from_current, $default_value, $required, $data_type);
    }

    public function getParamsfromCurrentPath($path_from_current = '')
    {
        return $this->getParams($this->current_path . $path_from_current);
    }

    protected function compileParams($params, $path)
    {
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                if (in_array($key, self::$keywords)) {
                    $params = $this->getvalue($params, $path);
                    break;
                } elseif ($key === 'object') {
                    $params[$key] = $this->getObject($path . '/object');
                } elseif (is_array($value)) {
                    $params[$key] = $this->compileParams($params[$key], $path . '/' . $key);
                }
            }
        }

        return $params;
    }

    public function addParams($path, $params)
    {
        if (is_null($path) || !$path) {
            return false;
        }

        $path = explode('/', $path);

        $current = &$this->params;

        foreach ($path as $key) {
            if ($key === '') {
                continue;
            }
            if (isset($current[$key])) {
                $current = &$current[$key];
            } else {
                return false;
            }
        }

        if (isset($current)) {
            $current = array_merge($current, $params);
            return true;
        }

        return false;
    }

    protected function getvalue($value, $path)
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            if (array_key_exists('prop', $value)) {
                return $this->getProp($value['prop'], $path . '/prop');
            }
            if (array_key_exists('field_value', $value)) {
                return $this->getFieldValue($value['field_value'], $path . '/field_value');
            }
            if (array_key_exists('array', $value)) {
                return $this->getArray($value['array'], $path . '/array');
            }
            if (array_key_exists('dol_list', $value)) {
                return $this->getDolList($value['dol_list'], $path . '/dol_list');
            }
            if (array_key_exists('array_value', $value)) {
                return $this->getArrayValue($path . '/array_value');
            }
            if (array_key_exists('instance', $value)) {
                return $this->getInstance($value['instance'], $path . '/instance');
            }
            if (array_key_exists('callback', $value)) {
                return $this->processCallback($value['callback'], $path . '/callback');
            }
            if (array_key_exists('conf', $value)) {
                return $this->getConfValue($value['conf'], $path . '/conf');
            }
            if (array_key_exists('global', $value)) {
                $global_var = $this->getvalue($value['global'], $path . '/global');
                global ${$global_var};
                if (isset(${$global_var}) && ${$global_var}) {
                    return ${$global_var};
                }
            }
            if (array_key_exists('request', $value)) {
                $request = $this->getvalue($value['request'], $path . '/request');
                return BimpTools::getValue($request);
            }
        }
        return $value;
    }

    protected function getInstance($params, $path)
    {
        $instance = null;

        if (is_array($params)) {
            if (isset($params['object'])) {
                $instance = $this->getObject($path . '/object');
            } elseif (isset($params['bimp_object'])) {
                $module_name = null;
                $object_name = null;
                if (is_string($params['bimp_object'])) {
                    if (is_a($this->instance, 'BimpObject')) {
                        $module_name = $this->instance->module;
                    } elseif (is_a($this->instance, 'BimpController')) {
                        $module_name = $this->instance->module;
                    }
                    $object_name = $params['bimp_object'];
                } elseif (is_array($params['bimp_object'])) {
                    $module_name = $this->get($path . '/bimp_object/module', null, true);
                    $object_name = $this->get($path . '/bimp_object/name', null, true);
                }

                if (is_null($module_name) || is_null($object_name)) {
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
                    $module = $this->get($path . '/dol_object/module', null, true);
                    if (is_null($module)) {
                        return null;
                    }
                    $file = $this->get($path . '/dol_object/file', $module, false);
                    $className = $this->get($path . '/dol_object/class', ucfirst($file), false);
                }
                if (!is_null($module) && !is_null($file) && !is_null($className)) {
                    if (!class_exists($className)) {
                        $file_path = DOL_DOCUMENT_ROOT . '/' . $module . '/class/' . $file . '.class.php';

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
                $class_name = $this->get($path . '/class_name', null, true);
                if (is_null($class_name)) {
                    return null;
                }

                if (!class_exists($class_name)) {
                    $class_path = $this->get($path . '/class_path', null, true);

                    if (is_null($class_path)) {
                        return null;
                    }

                    if (file_exists($class_path)) {
                        require_once DOL_DOCUMENT_ROOT . '/' . $class_path;
                    }

                    if (!class_exists($class_name)) {
                        $this->logConfigError('Classe "' . $class_name . '" inexistante');
                        return null;
                    }
                }

                $construct_params = $this->get($path . '/construct_params', array(), false);

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

                eval('$instance = new $class_name(' . $args . ');
                        ');
            } else {
                $instance = null;
                $this->logConfigUndefinedValue($path);
            }

            if (!is_null($instance)) {
                if (isset($params['fetch'])) {
                    $fetch_params = $this->get($path . '/fetch', array());
                    if (!is_null($fetch_params) && is_array($fetch_params)) {
                        if ($result = call_user_func_array(array(
                                    $instance, 'fetch'
                                        ), $fetch_params) <= 0) {
                            $this->logConfigError('Echec de fetch() sur l\'objet "' . get_class($instance) . '" - Paramètres: <pre>' . print_r($fetch_params, 1) . '</pre>');
                        }
                    }
                } elseif (isset($params['id_object'])) {
                    $id_object = $this->get($path . '/id_object', null, true, 'int');
                    if (!is_null($id_object)) {
                        if (method_exists($instance, 'fetch')) {
                            if ($result = $instance->fetch((int) $id_object) <= 0) {
                                $this->logConfigError('Echec de fetch() sur l\'objet "' . get_class($instance) . '" - ID: ' . $id_object);
                            }
                        } else {
                            $this->logConfigError('La méthode "fetch()" n\'existe pas pour l\'objet "' . get_class($instance) . '"');
                        }
                    }
                }
            }
        }

        return $instance;
    }

    protected function getFieldValue($field_value, $path)
    {
        if (is_string($field_value)) {
            if (!is_null($this->instance) && is_a($this->instance, 'BimpObject')) {
                return $this->instance->getData($field_value);
            }
        } elseif (is_array($field_value)) {
            $field_name = $this->get($path . '/field_name', null, true);
            $object = $this->getObject($path . '/object');
            if (!is_null($field_name) && !is_null($object) && is_a($object, 'BimpObject')) {
                return $object->getData($field_name);
            }
        }

        return null;
    }

    protected function getProp($prop, $path)
    {
        $prop_name = null;
        $is_static = 0;

        if (is_string($prop)) {
            $prop_name = $prop;
        } elseif (is_array($prop)) {
            $prop_name = $this->get($path . '/name', null, true);
            if ($this->isDefined($path . '/object')) {
                $object = $this->getObject($path . '/object');
            } else {
                $object = $this->instance;
            }
            $is_static = (int) $this->get($path . '/is_static', false, false, 'bool');
        }
        
        if (is_null($object)) {
            if (!is_null($prop_name)) {
                $msg = 'Impossible d\'obtenir la propriété "' . $prop_name . '" - Instance invalide';
                $this->logConfigError($msg);
            }
            return null;
        }

        if (!is_null($prop_name)) {
            if (property_exists($object, $prop_name)) {
                if ($is_static) {
                    return $object::${$prop_name};
                } else {
                    return $object->{$prop_name};
                }
            } else {
                $msg = 'La propriété "' . $prop_name . '" n\existe pas dans la classe "' . get_class($object) . '"';
                $this->logConfigError($msg);
            }
        }

        return null;
    }

    protected function getArray($array, $path)
    {
        if (is_array($array)) {
            $array = $this->getvalue($array, $path);
            $return = array();
            if (!is_null($array) && is_array($array))
                foreach ($array as $key => $value) {
                    $return[$key] = $this->getvalue($value, $path . '/' . $key);
                }
            return $return;
        }

        if (is_string($array)) {
            if (!is_null($this->instance)) {
                if (property_exists($this->instance, $array)) {
                    $instance = $this->instance;
                    if (isset($instance->{$array}) && is_array($instance->{$array})) {
                        return $instance->{$array};
                    } elseif (isset($instance::${$array}) && is_array($instance::${$array})) {
                        return $instance::${$array};
                    }
                    return array();
                }

                $method = 'get' . ucfirst($array) . 'Array';
                if (method_exists($this->instance, $method)) {
                    $result = $this->instance->{$method}();
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

    protected function getArrayValue($path)
    {
        $key = $this->get($path . '/key', null, true);
        $array = $this->get($path . '/array', null, true, 'array');

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

    protected function getDolList($dol_list, $path)
    {
        $id_list = null;
        if (is_int($dol_list) || preg_match('/^\d+$/', $dol_list)) {
            $id_list = (int) $dol_list;
        } elseif (is_array($dol_list)) {
            $id_list = (int) $this->getvalue($dol_list, $path);
        }

        if (is_null($id_list) || !($id_list) || !is_int($id_list)) {
            return array();
        }
        
        return BimpTools::getDolListArray($id_list);
    }

    protected function getConfValue($conf, $path)
    {
        if (is_array($conf)) {
            $conf = $this->getvalue($conf, $path);
        }

        if (is_string($conf)) {
            global $db;
            $bdb = new BimpDb($db);
            return $bdb->getValue('bimpcore_conf', 'value', '`name` = \'' . $conf . '\'');
        }

        return null;
    }

    protected function processCallback($callback, $path)
    {
        if (is_string($callback)) {
            if (!is_null($this->instance)) {
                if (method_exists($this->instance, $callback)) {
                    return $this->instance->{$callback}();
                } else {
                    $this->logConfigError('Méthode "' . $callback . '" inexistante dans la classe ' . get_class($this->instance));
                }
            } else {
                $this->logConfigError('Impossible d\'appeller la méthode "' . $callback . '" - Instance invalide');
            }
        } elseif (is_array($callback)) {
            $is_static = $this->get($path . '/is_static', false, false, 'bool');
            $method = $this->get($path . '/method', null, true);
            if ($this->isDefined($path . '/object')) {
                $instance = $this->getObject($path . '/object', null, false, 'object');
            } elseif ($is_static && $this->isDefined($path . '/class_name')) {
                $instance = $this->get($path . '/class_name', '', true);
            } else {
                $instance = $this->instance;
            }

            if (is_null($method) || is_null($instance)) {
                return null;
            }

            $params = $this->getCompiledParams($path . '/params');

            if (is_null($params)) {
                $params = array();
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

    // Gestion des objets: 

    public function getObject($path = '', $object_name = null)
    {
        if (is_null($object_name)) {
            if (!$path) {
                return null;
            }

            $object = $this->get($path, null, true, 'any');
            if (is_object($object)) {
                return $object;
            } elseif (is_string($object)) {
                $object_name = $object;
            }
        }

        if ($object_name === 'default') {
            return $this->instance;
        }
        if (!isset($this->objects[$object_name])) {
            $up_path = $path;
            $params = null;
            $instance_path = '';
            if (!$up_path) {
                $up_path = '/';
            }
            while ($up_path) {
                $up_path = $this->getPathPrevLevel($up_path);
                if ($this->isDefined($up_path . '/object/name')) {
                    $name = $this->get($up_path . '/object/name', '');
                    if ($name && ($name === $object_name)) {
                        $params = $this->get($up_path . '/instance', null, true, 'array');
                        $instance_path = $up_path . '/instance';
                        break;
                    }
                }

                if ($this->isDefined($up_path . '/objects/' . $object_name)) {
                    $params = $this->get($up_path . '/objects/' . $object_name . '/instance', null, true, 'array');
                    $instance_path = $up_path . '/objects/' . $object_name . '/instance';
                    break;
                }
            }
            if (!is_null($params)) {
                $instance = $this->getInstance($params, $instance_path);
                if (!is_null($instance)) {
                    $this->objects[$object_name] = $instance;
                }
            }
        }
        if (isset($this->objects[$object_name])) {
            return $this->objects[$object_name];
        }
        $this->logConfigUndefinedValue($path);
        return null;
    }

    public function getObjectModuleFromInstancePath($instance_path)
    {
        $params = $this->getParams($instance_path);
        if (isset($params['bimp_object'])) {
            if (isset($params['bimp_object']['module'])) {
                return $this->getvalue($params['bimp_object']['module'], $instance_path . '/bimp_object/module');
            }
            return $this->instance->module;
        } elseif (isset($params['dol_object'])) {
            if (is_string($params['dol_object'])) {
                return $params['dol_object'];
            }
            if (isset($params['dol_object']['module'])) {
                return $this->getvalue($params['dol_object']['module'], $instance_path . '/dol_object/module');
            }
        }
        return '';
    }

    public function getObjectNameFromInstancePath($instance_path)
    {
        $params = $this->getParams($instance_path);
        if (isset($params['bimp_object'])) {
            if (is_string($params['bimp_object'])) {
                return $params['bimp_object'];
            }
            if (isset($params['bimp_object']['name'])) {
                return $this->getvalue($params['bimp_object']['name'], $instance_path . '/bimp_object/module');
            }
        } elseif (isset($params['dol_object'])) {
            if (is_string($params['dol_object'])) {
                return ucfirst($params['dol_object']);
            }
            if (isset($params['dol_object']['class'])) {
                return $this->getvalue($params['dol_object']['class'], $instance_path . '/dol_object/class');
            }
            if (isset($params['dol_object']['file'])) {
                return ucfirst($this->getvalue($params['dol_object']['file'], $instance_path . '/dol_object/file'));
            }
            if (isset($params['dol_object']['module'])) {
                return ucfirst($this->getvalue($params['dol_object']['module'], $instance_path . '/dol_object/module'));
            }
        }
        return '';
    }

    public function getObjectTypeFromInstancePath($instance_path)
    {
        $params = $this->getParams($instance_path);
        if (isset($params['bimp_object'])) {
            return 'bimp_object';
        } elseif (isset($params['dol_object'])) {
            return 'dol_object';
        } elseif (isset($params['custom_object'])) {
            return 'custom_object';
        }
        return '';
    }

    public function getObjectModule($path = '', $object_name = null)
    {
        if (is_null($object_name)) {
            if (!$path) {
                return '';
            }

            if ($this->isDefined($path . '/instance')) {
                return $this->getObjectModuleFromInstancePath($path . '/instance');
            } else {
                $object_name = $this->get($path, '', true);
                if (!$object_name) {
                    return '';
                }
            }
        }

        if ($object_name === 'default') {
            return $this->instance->module;
        }

        if ($this->isDefined('objects/' . $object_name . '/instance')) {
            return $this->getObjectModuleFromInstancePath('objects/' . $object_name . '/instance');
        }

        return '';
    }

    public function getObjectName($path = '', $object_name = null)
    {
        if (is_null($object_name)) {
            if (!$path) {
                return '';
            }

            if ($this->isDefined($path . '/instance')) {
                return $this->getObjectNameFromInstancePath($path . '/instance');
            } else {
                $object_name = $this->get($path, '', true);
                if (!$object_name) {
                    return '';
                }
            }
        }

        if ($object_name === 'default') {
            if (is_a($this->instance, 'BimpObject')) {
                return $this->instance->object_name;
            }
            return '';
        }

        if ($this->isDefined('objects/' . $object_name . '/instance')) {
            return $this->getObjectNameFromInstancePath('objects/' . $object_name . '/instance');
        }

        return '';
    }

    public function getObjectType($path = '', $object_name = null)
    {
        if (is_null($object_name)) {
            if (!$path) {
                return '';
            }

            if ($this->isDefined($path . '/instance')) {
                return $this->getObjectTypeFromInstancePath($path . '/instance');
            } else {
                $object_name = $this->get($path, '', true);
                if (!$object_name) {
                    return '';
                }
            }
        }

        if ($object_name === 'default') {
            return 'bimp_object';
        }

        if ($this->isDefined('objects/' . $object_name . '/instance')) {
            return $this->getObjectTypeFromInstancePath('objects/' . $object_name . '/instance');
        }

        return '';
    }

    public function resetObjects()
    {
        foreach ($this->objects as $name => &$object) {
            unset($object);
        }
        $this->objects = array();
    }

    // Logs: 

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
