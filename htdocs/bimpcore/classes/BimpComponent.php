<?php

abstract class BimpComponent
{

    public $component_name = 'Composant';
    public $object;
    public $name;
    public $config_path = null;
    public static $type = '';
    public static $config_required = true;
    public $params_def = array(
        'extends' => array('default' => ''),
        'show'    => array('data_type' => 'bool', 'default' => 1)
    );
    public static $type_params_def = array();
    public $params;
    public $errors = array();
    public $infos = array();
    public $warnings = array();

    public function __construct(BimpObject $object, $name = '', $path = '')
    {
        $this->object = $object;
        $this->name = $name;

        if (!$this->isObjectValid()) {
            $this->addError('Objet invalide');
        } else {
            if (!$path && static::$type) {
                if (!$name || $name === 'default') {
                    if ($this->object->config->isDefined(static::$type)) {
                        $path = static::$type;
                    } elseif ($this->object->config->isDefined(static::$type . 's' . '/default')) {
                        $path = static::$type . 's';
                    }
                } else {
                    $path = static::$type . 's';
                }
            }

            if ($path) {
                if ($this->object->config->isDefined($path . ($name ? '/' . $name : ''))) {
                    $this->config_path = $path . ($name ? '/' . $name : '');
                } elseif (!$name && $this->object->config->isDefined($path . '/default')) {
                    $this->config_path = $path . '/default';
                    $this->name = 'default';
                }
            }
        }

        if (is_null($this->config_path) && static::$config_required) {
            $this->addTechnicalError('Configuration non définie');
        }

        $this->params = $this->fetchParams($this->config_path);

        if (isset($this->params['type'])) {
            if (array_key_exists($this->params['type'], static::$type_params_def)) {
                foreach ($this->fetchParams($this->config_path, static::$type_params_def[$this->params['type']]) as $p_name => $value) {
                    $this->params[$p_name] = $value;
                }
            }
        }
    }

    public function isObjectValid()
    {
        return (!is_null($this->object) && is_a($this->object, 'BimpObject'));
    }

    public function isOk()
    {
        if (count($this->errors) || (is_null($this->config_path) && self::$config_required)) {
            return false;
        }

        return true;
    }

    // Gestion des paramètres: 

    public function setConfPath($path = '')
    {
        if (isset($this->object->config)) {
            return $this->object->config->setCurrentPath($this->config_path . ($path ? '/' . $path : ''));
        }

        return false;
    }

    public function setParams($params)
    {
        foreach ($params as $name => $value) {
            $this->setParam($name, $value);
        }
    }

    public function setParam($name, $value)
    {
        if (self::validateParam($name, $value, $this->params_def, $errors)) {
            $this->params[$name] = $value;
        }
    }

    public function fetchParams($path, $definitions = null)
    {
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        if (is_null($definitions)) {
            $definitions = $this->params_def;
        }

        $errors = array();
        $params = self::fetchParamsStatic($this->object->config, $path, $definitions, $errors);

        if (count($errors)) {
            foreach ($errors as $e) {
                $this->addTechnicalError($e);
            }
        }

        $current_bc = $prev_bc;
        return $params;
    }

    public function fetchParam($name, $definitions, $path)
    {
        $errors = array();
        $param = self::fetchParamStatic($this->object->config, $name, $definitions, $path, $errors);
        if (count($errors)) {
            foreach ($errors as $e) {
                $this->addTechnicalError($e);
            }
        }
        return $param;
    }

    public static function fetchParamsStatic(BimpConfig $config, $path, $definitions, &$errors = array())
    {
        $params = array();
        if (is_null($definitions) || is_null($config)) {
            return array();
        }

        foreach ($definitions as $name => $defs) {
            $params[$name] = self::fetchParamStatic($config, $name, $definitions, $path, $errors);
        }
        return $params;
    }

    protected static function fetchParamStatic(BimpConfig $config, $name, $definitions, $path, &$errors = array())
    {
        if (!isset($definitions[$name])) {
            $errors[] = 'Paramètre de configuration invalide: "' . $name . '" (définitions absentes)';
            return null;
        }

        $defs = $definitions[$name];

        $default_value = (isset($defs['default']) ? $defs['default'] : null);
        $required = (isset($defs['required']) ? (bool) $defs['required'] : false);
        $type = (isset($defs['type']) ? $defs['type'] : 'value');

        $param = null;

        $request = isset($defs['request']) ? (bool) $defs['request'] : false;
        if ($request) {
            $json = isset($defs['json']) ? (bool) $defs['json'] : false;
            if (BimpTools::isSubmit('param_' . $name)) {
                $param = BimpTools::getValue('param_' . $name);
                if ($json && !is_null($param) && is_string($param)) {
                    $param = json_decode($param, true);
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
                        if (!is_null($values)) {
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
                                            $param[$key] = self::fetchParamsStatic($config, $path . '/' . $name . '/' . $key, BimpConfigDefinitions::${$defs_type}, $errors);
                                        }
                                    } else {
                                        $param = self::fetchParamsStatic($config, $path . '/' . $name, BimpConfigDefinitions::${$defs_type}, $errors);
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

    protected static function validateParam($name, $value, $definitions, &$errors)
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

        $type = isset($defs['data_type']) ? $defs['data_type'] : 'string';
        if (!BimpTools::checkValueByType($type, $value)) {
            $errors[] = 'Paramètre de configuration invalide: "' . $name . '" (doit être de type "' . $type . '")';
            return false;
        }

        return true;
    }

    public static function getDefaultParams($definitions)
    {
        $params = array();

        foreach ($definitions as $name => $def) {
            $type = (isset($def['type']) ? $def['type'] : 'value');
            switch ($type) {
                case 'value':
                    $params[$name] = (isset($def['default']) ? $def['default'] : null);
                    break;

                case 'object':
                    $params[$name] = null;
                    break;

                case 'keys':
                    $params[$name] = array();
                    break;

                case 'definitions':
                    $defs_type = isset($def['defs_type']) ? $def['defs_type'] : null;
                    $multiple = isset($def['multiple']) ? (bool) $def['multiple'] : false;

                    if ($multiple || !property_exists('BimpConfigDefinitions', $defs_type)) {
                        $params[$name] = array();
                    } else {
                        $params[$name] = self::getDefaultParams(BimpConfigDefinitions::${$defs_type});
                    }
                    break;
            }
        }

        return $params;
    }

    public static function override_params($params, BimpConfig $config, $path, $definitions, &$errors = array())
    {
        $override_params = self::fetchParamsStatic($config, $path, $definitions);

        foreach ($definitions as $name => $defs) {
            $default = isset($defs['default']) ? $defs['default'] : null;
            if (isset($override_params[$name]) &&
                    (!isset($params[$name]) || is_null($default) || ($override_params[$name] != $default))) {
                $params[$name] = $override_params[$name];
            }
        }

        return $params;
    }

    // Rendus:

    public function renderHtml()
    {
        $html = '';

        if (count($this->errors)) {
            $html .= BimpRender::renderAlerts($this->errors);
        }

        if (count($this->warnings)) {
            $html .= BimpRender::renderAlerts($this->warnings, 'warning');
        }

        if (count($this->infos)) {
            $html .= BimpRender::renderAlerts($this->infos, 'info');
        }

        return $html;
    }

    protected function writePDF($pdf)
    {
        if (!class_exists('BimpModelPDF')) {
            require_once DOL_DOCUMENT_ROOT . '/bimpcore/pdf/classes/BimpModelPDF.pho';
        }

        if (is_a($pdf, 'BimpModelPDF')) {
            
        } else {
            $this->addError('Erreur technique - instance de BimpModelPDF invalide');
        }
    }

    // Gestion des erreurs: 

    public function addTechnicalError($msg)
    {
        $label = 'Composant "' . $this->component_name . ' (' . static::$type . ')" - Type: "' . $this->name . '" - Objet: "' . $this->object->getLabel() . '"';
        $this->addError('[ERREUR TECHNIQUE] ' . $label . ' - ' . $msg);
    }

    public function addError($msg)
    {
        $this->errors[] = $msg;

        $label = '';

        if ($this->isObjectValid()) {
            $label .= BimpTools::ucfirst($this->object->getLabel());
        }
        $label .= ' - ' . static::$type . ' - ' . $this->name . ': ';
        dol_syslog($label . $msg, 3);
    }

    // Méthodes statiques: 

    public static function getConfigPath($object, $name)
    {
        if (!is_a($object, 'BimpObject')) {
            return '';
        }

        $path = '';

        if (!$name || $name === 'default') {
            if ($object->config->isDefined(static::$type)) {
                $path = static::$type;
            } elseif ($object->config->isDefined(static::$type . 's' . '/default')) {
                $path = static::$type . 's/default';
            }
        } else {
            $path = static::$type . 's/' . $name;
        }

        return $path;
    }
}
