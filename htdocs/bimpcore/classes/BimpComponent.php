<?php

abstract class BimpComponent
{

    public $component_name = 'Composant';
    public static $type = '';
    public static $config_required = true;
    public static $type_params_def = array();
    public static $hasUserConfig = false;
    public $object;
    public $name;
    public $config_path = null;
    public $params_def = array(
        'extends'      => array('default' => ''),
        'show'         => array('data_type' => 'bool', 'default' => 1),
        'configurable' => array('data_type' => 'bool', 'default' => 0)
    );
    public $params;
    public $errors = array();
    public $infos = array();
    public $warnings = array();
    public $no_ajax_params = false;
    public $userConfig = null;
    public $newUserConfigSet = false;

    public function __construct(BimpObject $object, $name = '', $path = '', $id_config = null)
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
            if (isset(static::$type_params_def[$this->params['type']])) {
                foreach ($this->fetchParams($this->config_path, static::$type_params_def[$this->params['type']]) as $p_name => $value) {
                    $this->params[$p_name] = $value;
                }
            }
        }

        $this->fetchUserConfig($id_config);
    }

    public function isObjectValid()
    {
        return (!is_null($this->object) && is_a($this->object, 'BimpObject'));
    }

    public function isOk()
    {
        if (!empty($this->errors) || (!(string) $this->config_path && static::$config_required)) {
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
        $param = self::fetchParamStatic($this->object->config, $name, $definitions, $path, $errors, $this->no_ajax_params);
        if (count($errors)) {
            foreach ($errors as $e) {
                $this->addTechnicalError($e);
            }
        }
        return $param;
    }

    public static function fetchParamsStatic(BimpConfig $config, $path, $definitions, &$errors = array(), $no_ajax_params = false, $no_default_values = false)
    {
        $params = array();
        if (is_null($definitions) || is_null($config)) {
            return array();
        }

        foreach ($definitions as $name => $defs) {
            $params[$name] = self::fetchParamStatic($config, $name, $definitions, $path, $errors, $no_ajax_params, $no_default_values);
        }
        return $params;
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
                    $param = BimpTools::getValue('param_' . $name);
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

    protected static function validateParam($name, $value, $definitions, &$errors = array())
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

        if (MOD_DEV) {
            $type = (isset($defs['data_type']) ? $defs['data_type'] : 'string');
            if (!BimpTools::checkValueByType($type, $value)) {
                $errors[] = 'Paramètre de configuration invalide: "' . $name . '" (doit être de type "' . $type . '")';
                return false;
            }
        }

        if (isset($defs['allowed']) && is_array($defs['allowed'])) {
            if (!in_array($value, $defs['allowed'])) {
                $errors[] = 'Valeur du paramètre "' . $name . '" non autorisée (' . $value . ')';
                return false;
            }
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

    public function getParam($path, $default_value = null)
    {
        $keys = explode('/', $path);
        $current_value = null;

        foreach ($keys as $key) {
            if (is_null($current_value)) {
                if (isset($this->params[$key])) {
                    $current_value = $this->params[$key];
                } else {
                    $current_value = $default_value;
                    break;
                }
            } elseif (is_array($current_value) && isset($current_value[$key])) {
                $current_value = $current_value[$key];
            } else {
                $current_value = $default_value;
                break;
            }
        }

        return $current_value;
    }

    // Gestion des congigurations utilisateur

    public function fetchUserConfig($id_config)
    {
        // Priorités: $id_config > $_POST['id_..._config] > current_config > default_config
        // $id_config : fourni en PHP dans le constructeur du composant
        // $_POST[id_..._config] : sélection d'une nouvelle config par le user
        // $_POST[id_current_..._config] : Configuration en cours d'utilisaltion
        // current_config : dernière config utilisée par le user
        // default_config : config par défaut (si plusieurs configs, renvoyée par UserConfig::getUserCurrentConfig() si pas de current config)

        if (BimpCore::isModuleActive('bimpuserconfig') && static::$hasUserConfig && $this->params['configurable']) {
            BimpObject::loadClass('bimpuserconfig', 'BCUserConfig');

            $set_as_current = false;

            if ($id_config) {
                $this->newUserConfigSet = true;
            }

            // Si nouvelle config demandée: 
            if (!$id_config && BimpTools::isSubmit('id_' . static::$type . '_config')) {
                $id_config = BimpTools::getValue('id_' . static::$type . '_config', 0);

                if (!$id_config) {
                    // Choix de ne pas utiliser de config par l'utilisateur: 
                    $config_instance = BCUserConfig::getInstanceFromComponentType(static::$type);
                    $key = $config_instance::getCurrentConfigKeyStatic($this->object, $this->name);
                    if ($key) {
                        $config_instance->setNoCurrent($key);
                    }
                    if (is_object($this->userConfig)) {
                        unset($this->userConfig);
                        $this->userConfig = null;
                    }
                    return;
                }

                $this->newUserConfigSet = true;
                $set_as_current = true;
            }

            // Si config en cours d'utilisation: 
            if (!$id_config && BimpTools::isSubmit('id_current_' . static::$type . '_config')) {
                $id_config = (int) BimpTools::getValue('id_current_' . static::$type . '_config', 0);
                $this->newUserConfigSet = false;

                if (!$id_config) {
                    if (is_object($this->userConfig)) {
                        unset($this->userConfig);
                        $this->userConfig = null;
                    }
                    return;
                }
            }

            // Chargement de la config: 
            if ($id_config) {
                $this->userConfig = BCUserConfig::getInstanceFromComponentType(static::$type, (int) $id_config);

                if (BimpObject::objectLoaded($this->userConfig)) {
                    if ($set_as_current) {
                        $this->userConfig->setAsCurrent();
                    }
                } else {
                    unset($this->userConfig);
                    $this->userConfig = null;
                }
            }

            // Chargement de la configuration courante ou par défaut: 
            if (!BimpObject::objectLoaded($this->userConfig)) {
                global $user;

                if (BimpObject::objectLoaded($user)) {
                    $config_instance = BCUserConfig::getInstanceFromComponentType(static::$type);

                    if (is_a($config_instance, 'BCUserConfig')) {
                        $this->userConfig = $config_instance::getUserCurrentConfig((int) $user->id, $this->object, $this->name);
                        $this->newUserConfigSet = true;
                    }
                }
            }
        }
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

        $this->errors[] = '[ERREUR TECHNIQUE] ' . $label . ' - ' . $msg;
        BimpCore::addlog('Erreur Composant', Bimp_Log::BIMP_LOG_ALERTE, 'bimpcore', $this->object, array(
            'Composant'      => $this->component_name,
            'Type composant' => static::$type,
            'Nom'            => $this->name,
            'Msg'            => $msg
        ));
    }

    public function addError($msg)
    {
        $this->errors[] = $msg;

        BimpCore::addlog('Erreur Composant', Bimp_Log::BIMP_LOG_ALERTE, 'bimpcore', $this->object, array(
            'Composant'      => $this->component_name,
            'Type composant' => static::$type,
            'Nom'            => $this->name,
            'Msg'            => $msg
        ));
    }

    // Méthodes statiques: 

    public static function getConfigPath($object, $name, $type = null)
    {
        if (!is_a($object, 'BimpObject')) {
            return '';
        }

        if (is_null($type)) {
            $type = static::$type;
        }

        if ($type === 'list_table') {
            $type = 'list';
        }

        $path = '';

        if (!$name || $name === 'default') {
            if ($object->config->isDefined($type)) {
                $path = $type;
            } elseif ($object->config->isDefined($type . 's' . '/default')) {
                $path = $type . 's/default';
            }
        } else {
            $path = $type . 's/' . $name;
        }

        return $path;
    }
}
