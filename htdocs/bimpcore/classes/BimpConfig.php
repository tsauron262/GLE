<?php

class BimpConfig
{

    public static $debug = false;
    public $dir = '';
    public $file = '';
    public $module = '';
    public $module_dir = '';
    public $file_name = '';
    public $instance = null;
    public $objects = array();
    public $current_path = '';
    public $current = null;
    public $errors = array();
    public static $keywords = array(
        'prop', 'field_value', 'array', 'array_value', 'instance', 'callback', 'global', 'request', 'request_field', 'dol_list', 'conf', 'bimpcore_conf', 'dol_conf', 'is_module_active'
    );
    public $cache_key = '';
    protected static $params = array();
    public static $cache_mode = 'per_file'; // per_file / full
    public static $params_cache = array();
    public static $values_cache = array();
    public static $files_cache = array();
    public static $params_cache_changes = array();
    public static $values_cache_changes = array();
    public static $files_cache_changes = array();

    public static function getObjectConfigInstance($module, $object_name, $instance = null)
    {
        return new BimpConfig($module, 'objects', $object_name, $instance);
    }

    public static function getControllerConfigInstance($module, $controller, $instance = null)
    {
        return new BimpConfig($module, 'controllers', $controller, $instance);
    }

    public static function getModuleConfigInstance($module, $instance = null)
    {
        // Note: $instance est mis ici en prévision d'une possible création de classes modules. 

        return new BimpConfig($module, '', $module, $instance);
    }

    public function __construct($module, $module_dir, $file_name, $instance = null)
    {
        $this->errors = array();

        $this->module = $module;
        $this->module_dir = $module_dir;
        $this->file_name = $file_name;
        $this->instance = $instance;

        if (!$module) {
            $this->errors[] = 'Module non spécifié';
        }

        if (!$file_name) {
            $this->errors[] = 'Fichier YML spécifié';
        }

        if (count($this->errors)) {
            BimpCore::addlog('Erreur paramètre de construction BimpConfig', Bimp_Log::BIMP_LOG_URGENT, 'yml', $instance, array(
                'Erreurs' => $this->errors
                    ), 1);
            return false;
        }

        if (preg_match('/^(.+)\.yml$/', $this->file_name, $matches)) {
            $this->file_name = $matches[1];
        }

        $this->dir = DOL_DOCUMENT_ROOT . '/' . $module . ($module_dir ? '/' . $module_dir : '') . '/';
        $this->file = $this->dir . $file_name . '.yml';
        $this->cache_key = $this->module . ($this->module_dir ? '/' . $this->module_dir : '') . '/' . $this->file_name;

        // Chargements depuis le cache serveur: 
        if (self::$cache_mode === 'per_file') {
            if (!isset(self::$values_cache[$this->cache_key])) {
                self::$values_cache[$this->cache_key] = BimpCache::getCacheServeur('bimp_config_values_' . $this->cache_key);

                if (!is_array(self::$values_cache[$this->cache_key]) || empty(self::$values_cache[$this->cache_key])) {
                    unset(self::$values_cache[$this->cache_key]);
                }
            }

            if (!isset(self::$params_cache[$this->cache_key])) {
                self::$params_cache[$this->cache_key] = BimpCache::getCacheServeur('bimp_config_params_' . $this->cache_key);

                if (!is_array(self::$params_cache[$this->cache_key]) || empty(self::$params_cache[$this->cache_key])) {
                    unset(self::$params_cache[$this->cache_key]);
                }
            }
        }

        if (!isset(self::$values_cache[$this->cache_key])) {
            self::$values_cache[$this->cache_key] = array();
        }

        if (isset(self::$params[$this->cache_key])) {
            return true;
        }

        if (isset(self::$params_cache[$this->cache_key])) {
            self::$params[$this->cache_key] = self::$params_cache[$this->cache_key];
            return true;
        }

        // Chargement des paramètres depuis le fichier: 
        if (!file_exists($this->file)) {
            $this->errors[] = 'Fichier de configuration "' . $this->file_name . '" absent';
            $this->logConfigError('Erreur technique: le fichier de configuration "' . $this->file_name . '.yml" n\'existe pas');
            return false;
        }

        self::$params[$this->cache_key] = array();

        if (!is_null($this->instance) && is_a($this->instance, 'BimpObject')) {
            if (!isset(self::$params_cache['BimpObject'])) {
                self::$params_cache['BimpObject'] = $this->getParamsFromFile('bimpcore', 'objects', 'BimpObject', $this->errors, false);
            }
            self::$params[$this->cache_key] = self::$params_cache['BimpObject'];
        }
        self::$params[$this->cache_key] = $this->mergeParams(self::$params[$this->cache_key], $this->getParamsFromFile($this->module, $this->module_dir, $this->file_name, $this->errors, true));

        if (is_array(self::$params[$this->cache_key]) && count(self::$params[$this->cache_key])) {
            foreach (self::$params[$this->cache_key] as $param_name => $param) {
                $this->checkParamsExtensions($param, $param_name);
            }

            // Création du cache pour ce fichier:
            if ($this->cache_key) {
                self::$params_cache[$this->cache_key] = self::$params[$this->cache_key];
                self::$params_cache_changes[$this->cache_key] = 1;
            }

            return true;
        }

        self::$params[$this->cache_key] = array();
        $this->logConfigError('Echec du chargement de la configuration depuis le fichier YML "' . $file_name . '"');

        return false;
    }

    public function getParamsFromFile($module, $module_dir, $file_name, &$errors = array(), $check_extends = true)
    {
        // $check_extensions: true pour les fichiers de base (core) / false pour les fichiers versions et entité. 

        if (!$module) {
            $errors[] = 'Module non spécifié';
        }

        if (!$file_name) {
            $errors[] = 'Nom du fichier non psécifié';
        }

        if (count($errors)) {
            return array();
        }

        if (preg_match('/^(.+)\.yml$/', $file_name, $matches)) {
            $file_name = $matches[1];
        }

        $cache_key = $module . ($module_dir ? '/' . $module_dir : '') . '/' . $file_name;

        if (!isset(self::$files_cache[$cache_key])) {
            $title = 'GET PARAMS - ' . $module . ' - ' . $module_dir . ' - ' . $file_name;
            $html = '';
            $params = array();

            $file = DOL_DOCUMENT_ROOT . '/' . $module . ($module_dir ? '/' . $module_dir : '') . '/' . $file_name . '.yml';

            if (!file_exists($file)) {
                $errors[] = 'Le fichier de configuration "' . $file . '" n\'existe pas';
            } else {
                $params = spyc_load_file($file);

                if (self::$debug) {
                    $html .= BimpRender::renderFoldableContainer('BASE PARAMS', '<pre>' . print_r($params, 1) . '</pre>', array(
                                'open'        => false,
                                'offset_left' => true
                    ));
                }

                // Surcharges version:
                $override_params = array();
                if (defined('BIMP_EXTENDS_VERSION')) {
                    $version_module_dir = 'extends/versions/' . BIMP_EXTENDS_VERSION . ($module_dir ? '/' . $module_dir : '');
                    $version_file = DOL_DOCUMENT_ROOT . '/' . $module . '/' . $version_module_dir . '/' . $file_name . '.yml';
                    if (file_exists($version_file)) {
                        $version_params = $this->getParamsFromFile($module, $version_module_dir, $file_name, $errors, false);

                        if (!empty($version_params)) {
                            if (self::$debug) {
                                $html .= BimpRender::renderFoldableContainer('VERSION PARAMS', '<pre>' . print_r($version_params, 1) . '</pre>', array(
                                            'open'        => false,
                                            'offset_left' => true
                                ));
                            }

                            $override_params = $this->mergeParams($override_params, $version_params, false);
                        }
                    }
                }

                // Surcharge entité: 
                if (defined('BIMP_EXTENDS_ENTITY')) {
                    $entity_module_dir = 'extends/entities/' . BIMP_EXTENDS_ENTITY . ($module_dir ? '/' . $module_dir : '');
                    $entity_file = DOL_DOCUMENT_ROOT . '/' . $module . '/' . $entity_module_dir . '/' . $file_name . '.yml';
                    if (file_exists($entity_file)) {
                        $entity_params = $this->getParamsFromFile($module, $entity_module_dir, $file_name, $errors, false);

                        if (!empty($entity_params)) {
                            if (self::$debug) {
                                $html .= BimpRender::renderFoldableContainer('ENTITY PARAMS', '<pre>' . print_r($entity_params, 1) . '</pre>', array(
                                            'open'        => false,
                                            'offset_left' => true
                                ));
                            }

                            $override_params = $this->mergeParams($override_params, $entity_params, false);
                        }
                    }
                }

                if (!empty($override_params)) {
                    if (self::$debug) {
                        $html .= BimpRender::renderFoldableContainer('ALL OVERRIDE PARAMS', '<pre>' . print_r($override_params, 1) . '</pre>', array(
                                    'open'        => false,
                                    'offset_left' => true
                        ));
                    }

                    $params = $this->mergeParams($params, $override_params, true);

                    if (self::$debug) {
                        $html .= BimpRender::renderFoldableContainer('PARAMS AFTER OVERRIDE', '<pre>' . print_r($params, 1) . '</pre>', array(
                                    'open'        => false,
                                    'offset_left' => true
                        ));
                    }
                }

                // Traitement des fichiers étendus: 
                if ($check_extends && isset($params['extends'])) {
                    $sub_dir = '';
                    if (!is_null($this->instance)) {
                        if (is_a($this->instance, 'BimpObject')) {
                            $sub_dir = 'objects';
                        } elseif (is_a($this->instance, 'BimpController')) {
                            $sub_dir = 'controllers';
                        }
                    }
                    $parent_file = DOL_DOCUMENT_ROOT . '/';
                    $extends_module = '';
                    $extends_object = '';

                    if (isset($params['extends']['module'])) {
                        $extends_module = $params['extends']['module'];
                        if (isset($params['extends']['object_name']) && $params['extends']['object_name']) {
                            $extends_object = $params['extends']['object_name'];
                        } else {
                            $errors[] = 'Nom du fichier d\'extension absent dans le fichier "' . $file . '"';
                        }
                    } elseif (is_string($params['extends']) && isset($this->instance->module)) {
                        $extends_module = $this->instance->module;
                        $extends_object = $params['extends'];
                    } else {
                        $errors[] = 'Nom du module absent du fichier de configuration "' . $file . '"';
                    }

                    if ($extends_module && $extends_object) {
                        $parent_file .= $extends_module . '/' . $sub_dir . '/' . $extends_object . '.yml';
                        if (is_file($parent_file)) {
                            $parent_params = $this->getParamsFromFile($extends_module, $sub_dir, $extends_object, $errors, true);

                            if (!empty($parent_params)) {
                                if (self::$debug) {
                                    $html .= BimpRender::renderFoldableContainer('PARENT PARAMS', '<pre>' . print_r($parent_params, 1) . '</pre>', array(
                                                'open'        => false,
                                                'offset_left' => true
                                    ));
                                }

                                $params = $this->mergeParams($parent_params, $params, false);

                                if (self::$debug) {
                                    $html .= BimpRender::renderFoldableContainer('PARAMS AFTER PARENT EXTENDS', '<pre>' . print_r($entity_params, 1) . '</pre>', array(
                                                'open'        => false,
                                                'offset_left' => true
                                    ));
                                }
                            }

                            if (is_object($this->instance) && property_exists($this->instance, 'extends')) {
                                $this->instance->extends[] = array(
                                    'module'      => $extends_module,
                                    'object_name' => $extends_object
                                );
                            }
                        } else {
                            $errors[] = 'Le fichier étendu "' . $parent_file . '" n\'existe pas';
                        }
                    }
                }
            }

            if (self::$debug) {
                $html .= BimpRender::renderFoldableContainer('FINAL PARAMS', '<pre>' . print_r($params, 1) . '</pre>', array(
                            'open'        => false,
                            'offset_left' => true
                ));

                echo BimpRender::renderFoldableContainer($title, $html, array(
                    'open'        => false,
                    'offset_left' => true
                ));
            }

            self::$files_cache[$cache_key] = $params;
            self::$files_cache_changes[$cache_key] = 1;
        }

        return self::$files_cache[$cache_key];
    }

    public static function mergeParams(Array $parent_params, Array $child_params, $keep_unset = false)
    {
        foreach ($child_params as $key => $values) {
            if (isset($parent_params[$key]) && is_array($values) && is_array($parent_params[$key])) {
                if (isset($values['unextends']) && (int) $values['unextends']) {
                    unset($values['unextends']);
                    $parent_params[$key] = $values;
                } else {
                    $parent_params[$key] = self::mergeParams($parent_params[$key], $values, $keep_unset);
                }
            } else {
                if (is_string($values) && $values === 'unset') {
                    if ($keep_unset) {
                        $parent_params[$key] = 'unset';
                    } elseif (isset($parent_params[$key])) {
                        unset($parent_params[$key]);
                    }
                } else {
                    $parent_params[$key] = $values;
                }
            }
        }

        return $parent_params;
    }

    public function checkParamsExtensions($params, $path)
    {
        if (is_array($params)) {
            if (isset($params['extends']) && $params['extends']) {
                $prev_path = $this->getPathPrevLevel($path);
                if ($prev_path && $this->isDefined($prev_path . '/' . $params['extends'])) {
                    $extended_params = $this->getParams($prev_path . '/' . $params['extends']);
                    unset($params['extends']);
                    $params = $this->mergeParams($extended_params, $params);
                    $this->setParams($path, $params);
                }
            }
            foreach ($params as $param_name => $param) {
                $this->checkParamsExtensions($param, $path . '/' . $param_name);
            }
        }
    }

    public static function initCacheServeur()
    {
        if (self::$cache_mode === 'full') {
            $cacheVal = BimpCache::getCacheServeur('bimpconfig');
            if (!is_null($cacheVal)) {
                self::$params_cache = $cacheVal[0];
                self::$values_cache = $cacheVal[1];
                self::$files_cache = $cacheVal[2];
            }
        }
    }

    public static function saveCacheServeur()
    {
        if (BimpCache::getCacheServerType() != 'server') {
            return;
        }

        switch (self::$cache_mode) {
            case 'per_file':
                foreach (self::$params_cache_changes as $cache_key => $value) {
                    if ($value && isset(self::$params_cache[$cache_key])) {
                        BimpCache::setCacheServeur('bimp_config_params_' . $cache_key, self::$params_cache[$cache_key]);
                    }
                }
                foreach (self::$values_cache_changes as $cache_key => $value) {
                    if ($value && isset(self::$values_cache[$cache_key])) {
                        BimpCache::setCacheServeur('bimp_config_values_' . $cache_key, self::$values_cache[$cache_key]);
                    }
                }
                foreach (self::$files_cache_changes as $cache_key => $value) {
                    if ($value && isset(self::$files_cache[$cache_key])) {
                        BimpCache::setCacheServeur('bimp_config_files_' . $cache_key, self::$files_cache[$cache_key]);
                    }
                }
                return;

            case 'full':
                BimpCache::setCacheServeur('bimpconfig', array(self::$params_cache, self::$values_cache, self::$files_cache));
                return;
        }
    }

    // Gestion des chemins de configuration: 

    public function isDefined($path)
    {
        $path = explode('/', $path);

        $current = self::$params[$this->cache_key];
        foreach ($path as $key) {
            if ($key === '') {
                continue;
            }
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
        $this->current = self::$params[$this->cache_key];
        $this->current_path = '';

        if ($path === '') {
            return true;
        }

        $path = explode('/', $path);

        $currentPath = '';
        $current = self::$params[$this->cache_key];
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

        // Récup depuis le cache s'il existe: 
        if (isset(self::$values_cache[$this->cache_key][$full_path])) {
            if (BimpDebug::isActive()) {
                BimpDebug::$cache_infos['counts']['yml']['s']++;
            }

            if (self::$values_cache[$this->cache_key][$full_path] === 'NOT_DEF') {
                return $default_value;
            } else {
                return self::$values_cache[$this->cache_key][$full_path];
            }
        }

        $path = explode('/', $full_path);

        $current = self::$params[$this->cache_key];

        foreach ($path as $key) {
            if ($key === '') {
                continue;
            }
            if (isset($current[$key])) {
                $current = $current[$key];
            } else {
                if ($required && MOD_DEV) {
                    $this->logConfigUndefinedValue($full_path);
                }
                $current = null;
                break;
            }
        }

        if (is_array($current)) {
            $value = $this->getvalue($current, $full_path);

            if (!is_null($value) && MOD_DEV) {
                if (!$this->checkValueDataType($value, $data_type)) {
                    $this->logInvalideDataType($full_path, $data_type, $value);
                    $value = null;
                }
            }

            if (is_null($value)) {
                return $default_value;
            }
            return $value;
        }

        // On ne met en cache que si le paramètre n'est pas dynamique
        if (!is_null($current) && MOD_DEV) {
            if (!$this->checkValueDataType($current, $data_type)) {
                $this->logInvalideDataType($full_path, $data_type, $current);
                $current = null;
            }
        }

        // On ne met pas $default_value en cache car elle peut être potentiellement variable pour un même $full_path selon le context d'appel à get(). 
        self::$values_cache[$this->cache_key][$full_path] = (is_null($current) ? 'NOT_DEF' : $current);
        self::$values_cache_changes[$this->cache_key] = 1;

        if (BimpDebug::isActive()) {
            BimpDebug::$cache_infos['counts']['yml']['n']++;
        }

        if (is_null($current)) {
            return $default_value;
        }

        return $current;
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

    public function getParams($full_path = '')
    {
        if (is_null($full_path) || !$full_path) {
            return self::$params[$this->cache_key];
        }

        $path = explode('/', $full_path);

        $current = self::$params[$this->cache_key];

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

    public function getFromCurrentPath($path_from_current = '', $default_value = null, $required = false, $data_type = 'string')
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
                if (in_array((string) $key, self::$keywords)) {
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

    public function addParams($path, $params, $mode = 'overrides')
    {
        // Modes: 
        // - overrides: surcharge les (éventuels) paramètres actuels. 
        // - replace: remplace les (éventuels) paramètres actuels. 
        // - initial: paramètres initiaux devant être surchargés par les paramètres actuels. 

        if (is_null($path) || !$path) {
            return false;
        }

        $path = explode('/', $path);

        $current = &self::$params[$this->cache_key];

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
            switch ($mode) {
                case 'overrides':
                default:
                    $current = BimpTools::overrideArray($current, $params, false, true);
                    break;

                case 'replace':
                    $current = BimpTools::merge_array($current, $params);
                    break;

                case 'initial':
                    $current = BimpTools::overrideArray($params, $current, false, true);
                    break;
            }
            return true;
        }

        return false;
    }

    public function setParams($path, $params)
    {
        if (is_null($path) || !$path) {
            return false;
        }

        $path = explode('/', $path);

        $current = &self::$params[$this->cache_key];

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
            $current = $params;
            return true;
        }

        return false;
    }

    public function print_params($full_path)
    {
        echo '<pre>';
        print_r($this->getParams($full_path));
        echo '</pre>';
    }

    // Getters interne: 

    protected function getvalue($value, $path)
    {
        if (is_array($value)) {
            if (isset($value['callback'])) {
                return $this->processCallback($value['callback'], $path . '/callback');
            }
            if (isset($value['array'])) {
                return $this->getArray($value['array'], $path . '/array');
            }
            if (isset($value['prop'])) {
                return $this->getProp($value['prop'], $path . '/prop');
            }
            if (isset($value['field_value'])) {
                return $this->getFieldValue($value['field_value'], $path . '/field_value');
            }
            if (isset($value['conf'])) {
                return $this->getConfValue($value['conf'], $path . '/conf');
            }
            if (isset($value['bimpcore_conf'])) {
                return $this->getBimpcoreConfValue($value['bimpcore_conf'], $path . '/bimpcore_conf');
            }
            if (isset($value['dol_conf'])) {
                return $this->getDolConfValue($value['dol_conf'], $path . '/dol_conf');
            }
            if (isset($value['instance'])) {
                return $this->getInstance($value['instance'], $path . '/instance');
            }
            if (isset($value['array_value'])) {
                return $this->getArrayValue($path . '/array_value');
            }
            if (isset($value['request'])) {
                return $this->getRequestValue($value['request'], $path . '/request', false);
            }
            if (isset($value['request_field'])) {
                return $this->getRequestValue($value['request_field'], $path . '/request_field', true);
            }
            if (isset($value['global'])) {
                $global_var = $this->getvalue($value['global'], $path . '/global');
                if (is_string($global_var)) {
                    global ${$global_var};
                    if (isset(${$global_var}) && ${$global_var}) {
                        return ${$global_var};
                    }
                }
            }
            if (isset($value['dol_list'])) {
                return $this->getDolList($value['dol_list'], $path . '/dol_list');
            }
            if (isset($value['is_module_active'])) {
                return $this->getIsModuleActive($value['is_module_active'], $path . '/is_module_active');
            }
        }
        return $value;
    }

    protected function getInstanceOld($params, $path)
    {
        $instance = null;

        if (is_array($params)) {
            if (isset($params['object'])) {
                $instance = $this->getObject($path . '/object');
            } elseif (isset($params['bimp_object'])) {
                $module_name = null;
                $object_name = null;
                if (is_string($params['bimp_object'])) {
                    if (!is_null($this->instance)) {
                        if (is_a($this->instance, 'BimpObject')) {
                            $module_name = $this->instance->module;
                        } elseif (is_a($this->instance, 'BimpController')) {
                            $module_name = $this->instance->module;
                        }
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
                    if (!is_null($id_object) && $id_object) {
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

    protected function getInstance($params, $path, $id_object = null)
    {
        if (is_array($params)) {
            if (isset($params['object'])) {
                return $this->getObject($path . '/object');
            } else {
                if (is_null($id_object) || !(int) $id_object) {
                    $id_object = $this->get($path . '/id_object', 0, false, 'int');
                }

                if (!is_int($id_object)) {
                    if (preg_match('/^[0-9]+$/', $id_object)) {
                        $id_object = (int) $id_object;
                    } else {
                        $id_object = null;
                    }
                }

                if (isset($params['bimp_object'])) {
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
                    return BimpCache::getBimpObjectInstance($module_name, $object_name, $id_object);
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
                        BimpTools::loadDolClass($module, $file, $className);
                        if (!class_exists($className)) {
                            $this->logConfigError('Class "' . $className . '" inexistante');
                            return null;
                        }
                        return BimpCache::getDolObjectInstance($id_object, $module, $file, $className);
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

                    $instance = null;
                    eval('$instance = new $class_name(' . $args . ');
                        ');
                    return $instance;
                } else {
                    $this->logConfigUndefinedValue($path);
                }
            }
        }

        return null;
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
        $object = null;
        $prop_name = null;
        $is_static = 0;

        if (is_string($prop)) {
            $prop_name = $prop;
            $object = $this->instance;
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
            if (!is_null($this->instance) && is_object($this->instance)) {
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
        $include_empty = false;

        $params = $this->getvalue($dol_list, $path);

        if (is_int($params) || preg_match('/^\d+$/', $params)) {
            $id_list = (int) $dol_list;
        } elseif (is_array($params)) {
            if (isset($params['id_list'])) {
                $id_list = (int) $this->getvalue($params['id_list'], $path . '/id_list');
            }
            if (isset($params['include_empty'])) {
                $include_empty = (int) $this->getvalue($params['include_empty'], $path . '/include_empty');
            }
        }

        if (is_null($id_list) || !($id_list) || !is_int($id_list)) {
            return array();
        }

        return BimpCache::getDolListArray($id_list, $include_empty);
    }

    protected function getConfValue($conf, $path)
    {
        return $this->getBimpcoreConfValue($conf, $path);
    }

    protected function getBimpcoreConfValue($bimpcoreConf, $path)
    {
        $name = '';
        $module = 'bimpcore';
        $default = null;
        $is_int = 1;

        if (is_string($bimpcoreConf)) {
            $name = $bimpcoreConf;
        } elseif (is_array($bimpcoreConf)) {
            $module = $this->get($path . '/module', 'bimpcore');
            $name = $this->get($path . '/name', '', true);
            $default = $this->get($path . '/def', null);
            $is_int = $this->get($path . 'is_int', 1);
        }

        $val = null;
        if ($name) {
            $val = BimpCore::getConf($name, $default, $module);
        }

        if ($is_int) {
            $val = (int) $val;
        }

        return $val;
    }

    protected function getDolConfValue($dol_conf, $path)
    {
        if ((string) $dol_conf) {
            if (verifCond('$conf->' . $dol_conf)) {
                return 1;
            }
        }
        return 0;
    }

    protected function getRequestValue($request, $path, $is_field = false)
    {
        $params = $this->getvalue($request, $path);

        $request_name = null;
        $default_value = null;

        if (is_string($params)) {
            $request_name = $params;
        } elseif (is_array($params)) {
            if (isset($params['name'])) {
                $request_name = $this->getvalue($params['name'], $path . '/name');
            }
            if (isset($params['default_value'])) {
                $default_value = $this->getvalue($params['default_value'], $path . '/default_value');
            }
        }


        if (!is_null($request_name) && is_string($request_name)) {
            if ($is_field) {
                $value = BimpTools::getPostFieldValue($request_name, $default_value);
            } else {
                $value = BimpTools::getValue($request_name, $default_value);
            }
            return $value;
        }

        return null;
    }

    protected function processCallback($callback, $path)
    {
        if (is_string($callback)) {
            if (!is_null($this->instance) && is_object($this->instance)) {
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
                $instance = $this->getObject($path . '/object');
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

            if (!is_object($instance)) {
                $this->logConfigError('Impossible d\'appeller la méthode "' . $method . '" - Instance invalide');
            } elseif (!method_exists($instance, $method)) {
                $this->logConfigError('Méthode "' . $method . '" inexistante dans la classe ' . get_class($instance));
            } else {
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
        }

        return null;
    }

    protected function checkValueDataType(&$value, $data_type)
    {
        return BimpTools::checkValueByType($data_type, $value);
    }

    protected function getIsModuleActive($module, $path)
    {
        if (strpos($module, 'bimp') === 0) {
            return (int) BimpCore::isModuleActive($module);
        } else {
            // todo
        }

        return 0;
    }

    // Gestion des objets: 

    public function getObjectOld($path = '', $object_name = null)
    {
//        if (is_null($object_name)) {
//            if (!$path) {
//                return null;
//            }
//
//            $object = $this->get($path, null, true, 'any');
//
//            if (is_object($object)) {
//                return $object;
//            } elseif (is_string($object)) {
//                $object_name = $object;
//            }
//        }
//
//        if ($object_name === 'default') {
//            return $this->instance;
//        }
//        if (!isset($this->objects[$object_name])) {
//            $up_path = $path;
//            $params = null;
//            $instance_path = '';
//            if (!$up_path) {
//                $up_path = '/';
//            }
//            while ($up_path) {
//                $up_path = $this->getPathPrevLevel($up_path);
//                if ($this->isDefined($up_path . '/object/name')) {
//                    $name = $this->get($up_path . '/object/name', '');
//                    if ($name && ($name === $object_name)) {
//                        $params = $this->get($up_path . '/instance', null, true, 'array');
//                        $instance_path = $up_path . '/instance';
//                        break;
//                    }
//                }
//
//                if ($this->isDefined($up_path . '/objects/' . $object_name)) {
//                    $params = $this->get($up_path . '/objects/' . $object_name . '/instance', null, true, 'array');
//                    $instance_path = $up_path . '/objects/' . $object_name . '/instance';
//                    break;
//                }
//            }
//            if (!is_null($params)) {
//                $instance = $this->getInstance($params, $instance_path);
//                if (!is_null($instance)) {
//                    $this->objects[$object_name] = $instance;
//                }
//            }
//        }
//        if (isset($this->objects[$object_name])) {
//            return $this->objects[$object_name];
//        }
//        $this->logConfigUndefinedValue($path);
//        return null;
    }

    public function getObject($path = '', $object_name = null, $id_object = null)
    {
        if (is_null($object_name)) {
            $object_name = '';

            if (!$path) {
                return null;
            }

            $object = $this->get($path, null, false, 'any');

            if (is_object($object)) {
                return $object;
            } elseif (is_string($object)) {
                $object_name = $object;
            }
        }

        if ($object_name === 'default') {
            return $this->instance;
        } elseif ($object_name === 'parent') {
            if (is_a($this->instance, 'BimpObject')) {
                return $this->instance->getParentInstance();
            }
        }

        if ($object_name) {
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
                return $this->getInstance($params, $instance_path, $id_object);
            }
        }

//        $this->logConfigUndefinedValue($path); // (Peut éventuellement être null) 
        return null;
    }

    public function getObjectModuleFromInstancePath($instance_path)
    {
        $params = $this->getParams($instance_path);
        if (isset($params['bimp_object'])) {
            if (isset($params['bimp_object']['module'])) {
                return $this->getvalue($params['bimp_object']['module'], $instance_path . '/bimp_object/module');
            }
            if (!is_null($this->instance) && is_a($this->instance, 'BimpObject') || is_a($this->instance, 'BimpController')) {
                return $this->instance->module;
            }
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

    public function getObjectInstanceParamsFromInstancePath($instance_path)
    {
        $object_type = $this->getObjectTypeFromInstancePath($instance_path);

        switch ($object_type) {
            case 'bimp_object':
                return array(
                    'object_type' => $object_type,
                    'module'      => $this->getObjectModuleFromInstancePath($instance_path),
                    'object_name' => $this->getObjectNameFromInstancePath($instance_path)
                );

            case 'dol_object':
                $module = $this->getObjectModuleFromInstancePath($instance_path);
                $file = $this->get($instance_path . '/dol_object/file', $module);
                $class = $this->get($instance_path . '/dol_object/class', ucfirst($file));

                return array(
                    'object_type' => $object_type,
                    'module'      => $module,
                    'file'        => $file,
                    'class'       => $class
                );
        }

        return null;
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
            if (!is_null($this->instance) && is_a($this->instance, 'BimpObject') || is_a($this->instance, 'BimpController')) {
                return $this->instance->module;
            }
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
            if (!is_null($this->instance) && is_a($this->instance, 'BimpObject')) {
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

    public function getObjectInstanceParams($path = '', $object_name = null)
    {
        $instance_path = '';
        if (is_null($object_name)) {
            if (!$path) {
                return null;
            }

            if ($this->isDefined($path . '/instance')) {
                $instance_path = $path . '/instance';
            } else {
                $object_name = $this->get($path, '', true);
                if (!$object_name) {
                    return null;
                }
            }
        }

        if ($object_name === 'default') {
            return array(
                'object_type' => 'bimp_object',
                'module'      => $this->instance->module,
                'object_name' => $this->instance->object_name
            );
        }

        if ($object_name && !$instance_path && $this->isDefined('objects/' . $object_name . '/instance')) {
            $instance_path = 'objects/' . $object_name . '/instance';
        }

        if ($instance_path) {
            return $this->getObjectInstanceParamsFromInstancePath($instance_path);
        }

        return null;
    }

    // Logs: 

    protected function logInvalideDataType($param_path, $data_type, $value = null)
    {
        $msg = 'Type de valeur invalide pour le paramètre "' . $param_path . '"';
        $msg .= '. Type attendu: "' . $data_type . '" - Obtenu: ' . gettype($value);

        if (!in_array($data_type, array('text', 'html'))) {
            $msg .= ' - Valeur: "' . $value . '"';
        }

        $this->logConfigError($msg);
    }

    protected function logConfigUndefinedValue($param_path)
    {
        if (isset(self::$params[$this->cache_key]['abstract']) && (int) self::$params[$this->cache_key]['abstract']) {
            return;
        }

        self::logConfigError('Paramètre "' . $param_path . '" non défini');
    }

    protected function logConfigError($msg)
    {
        if (!$this->file || !empty($this->errors)) {
            // Eviter les logs intempestifs
            return;
        }

        global $user;

        if (BimpCore::isModeDev() || $user->id == 1) { // Pour éviter trop de logs... 
            BimpCore::addlog('Erreur config YML: ' . $msg, Bimp_Log::BIMP_LOG_ALERTE, 'yml', (is_a($this->instance, 'BimpObject') ? $this->instance : null), array(
                'Fichier' => $this->file
            ));
        }
    }
}
