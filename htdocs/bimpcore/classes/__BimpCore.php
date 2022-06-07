<?php

class BimpCore
{

    public static $conf_cache = null;
    public static $conf_cache_def_values = array();
    private static $context = '';
    private static $max_execution_time = 0;
    private static $memory_limit = 0;
    private static $logs_extra_data = array();
    public static $files = array(
        'js'  => array(
            'jpicker'           => '/includes/jquery/plugins/jpicker/jpicker-1.1.6.js',
            'SignaturePad'      => '/bimpcore/views/js/SignaturePad.object.js',
            'moment'            => '/bimpcore/views/js/moment.min.js',
            'bootstrap'         => '/bimpcore/views/js/bootstrap.min.js',
            'datetimepicker'    => '/bimpcore/views/js/bootstrap-datetimepicker.js',
            'functions'         => '/bimpcore/views/js/functions.js',
            'scroller'          => '/bimpcore/views/js/scroller.js',
            'ajax'              => '/bimpcore/views/js/ajax.js',
//            '/bimpcore/views/js/component.js',
            'modal'             => '/bimpcore/views/js/modal.js',
            'object'            => '/bimpcore/views/js/object.js',
            'filters'           => '/bimpcore/views/js/filters.js',
            'form'              => '/bimpcore/views/js/form.js',
            'list'              => '/bimpcore/views/js/list.js',
            'view'              => '/bimpcore/views/js/view.js',
            'viewsList'         => '/bimpcore/views/js/viewsList.js',
            'listCustom'        => '/bimpcore/views/js/listCustom.js',
            'statsList'         => '/bimpcore/views/js/statsList.js',
            'page'              => '/bimpcore/views/js/page.js',
            'table2csv'         => '/bimpcore/views/js/table2csv.js',
            'buc'               => '/bimpuserconfig/views/js/buc.js',
            'bimpcore'          => '/bimpcore/views/js/bimpcore.js',
            'bimp_api'          => '/bimpapi/views/js/bimp_api.js',
            'bimpDocumentation' => '/bimpcore/views/js/BimpDocumentation.js'
        ),
        'css' => array(
            'jPicker'    => '/includes/jquery/plugins/jpicker/css/jPicker-1.1.6.css',
            'bimpcore'   => '/bimpcore/views/css/bimpcore.css',
            'userConfig' => '/bimpuserconfig/views/css/userConfig.css',
//            '/theme/BimpTheme/views/dist/css/theme.css' // TODO remove
        )
    );
    public static $filesInit = false;
    public static $config = null;
    public static $dev_mails = array(
        'tommy'   => 't.sauron@bimp.fr',
        'florian' => 'f.martinez@bimp.fr',
        'alexis'  => 'al.bernard@bimp.fr',
        'romain'  => 'r.PELEGRIN@bimp.fr',
        'peter'   => 'p.tkatchenko@bimp.fr'
    );
    public static $html_purifier = null;

    public static function displayHeaderFiles($echo = true)
    {
        global $noBootstrap, $conf;

        if ($noBootstrap) {
//            unset(static::$files['js'][2]);
            self::$files['js'] = BimpTools::unsetArrayValue(self::$files['js'], '/bimpcore/views/js/bootstrap.min.js');
        }

        if (BimpTools::getContext() != 'public') {
            self::$files['js'][] = '/bimpcore/views/js/notification.js';
        } else {
            self::$files['css']['bimpcore'] = '/bimpcore/views/css/bimpcore_public.css';
        }

        $html = '';
        if (!self::$filesInit) {
            foreach (self::$files['css'] as $css_file) {
                $url = self::getFileUrl($css_file);

                if ($url) {
                    $html .= '<link type="text/css" rel="stylesheet" href="' . $url . '"/>';
                }
            }

            global $user;

            $html .= '<script type="text/javascript">';
            $html .= ' var dol_url_root = \'' . DOL_URL_ROOT . '\';';
            $html .= ' var id_user = ' . (BimpObject::objectLoaded($user) ? $user->id : 0) . ';';
            $html .= ' var bimp_context = "' . self::getContext() . '";';

            // Inclusion notifications
            $html .= 'var notificationActive = {';

            if (self::isContextPrivate()) {
                $notification = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNotification');
                $config_notification = $notification->getList();

                foreach ($config_notification as $cn) {
                    $html .= $cn['nom'] . ": {";
                    $html .= "module: '" . $cn['module'] . "',";
                    $html .= "class: '" . $cn['class'] . "' ,";
                    $html .= "method: '" . $cn['method'] . "' ,";
                    $html .= "obj: null},";
                }
            }

            $html .= '};';

            // Fin inclusion notifications
            $html .= 'var theme="' . (isset($user->conf->MAIN_THEME) ? $user->conf->MAIN_THEME : $conf->global->MAIN_THEME) . '";';
            $html .= '</script>';

            foreach (self::$files['js'] as $js_file) {
                $url = self::getFileUrl($js_file);

                if ($url) {
                    $html .= '<script type="text/javascript" src="' . $url . '"></script>';
                }
            }

            self::$filesInit = true;
        }

        if ($echo)
            echo $html;

        return $html;
    }

    public static function getFileUrl($file_path, $use_tms = true)
    {
        $url = '';

        $debug = false;

//        if ($_SERVER['HTTP_X_REAL_IP'] == '10.192.20.148') {
//            $debug = true;
//        }

        if (preg_match('/^\/+(.+)$/', $file_path, $matches)) {
            $file_path = $matches[1];
        }

        if (file_exists(DOL_DOCUMENT_ROOT . '/' . $file_path)) {
            if ($debug) {
                echo 'ICI: ' . DOL_DOCUMENT_ROOT . '/' . $file_path;
            }
            if ($use_tms && (int) BimpCore::getConf('use_files_tms')) {
                $pathinfo = pathinfo($file_path);

                if (strpos($pathinfo['dirname'], '/views/') !== false) {
                    $tms = filemtime(DOL_DOCUMENT_ROOT . '/' . $file_path);

                    if ($tms !== false) {
                        $out_file = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '_tms_' . $tms . '.' . $pathinfo['extension'];

                        if (preg_match('/^\/+(.+)$/', $out_file, $matches)) {
                            $out_file = $matches[1];
                        }

                        if (!file_exists(DOL_DOCUMENT_ROOT . '/' . $out_file)) {
                            // Suppr du fichier existant: 
                            $dir = DOL_DOCUMENT_ROOT . '/' . $pathinfo['dirname'];
                            foreach (scandir($dir) as $f) {
                                if (in_array($f, array('.', '..'))) {
                                    continue;
                                }

                                if (preg_match('/^' . preg_quote($pathinfo['filename']) . '_tms_\d+\.' . preg_quote($pathinfo['extension']) . '$/', $f)) {
                                    unlink($dir . '/' . $f);
                                }
                            }

                            if (!copy(DOL_DOCUMENT_ROOT . '/' . $file_path, DOL_DOCUMENT_ROOT . '/' . $out_file)) {
                                BimpCore::addlog('Echec création du fichier "' . DOL_DOCUMENT_ROOT . '/' . $out_file . '" - Vérifier les droits', Bimp_Log::BIMP_LOG_ALERTE);
                            }
                        }

                        if (file_exists(DOL_DOCUMENT_ROOT . '/' . $out_file)) {
                            $url = $out_file;
                        }
                    }
                }
            }

            if (!$url) {
                $url = $file_path;
            }
        } else {
            if ($debug) {
                echo 'FAIL: ' . DOL_DOCUMENT_ROOT . '/' . $file_path;
            }
        }

        if ($url) {
            $prefixe = DOL_URL_ROOT;
            if ($prefixe == "/")
                $prefixe = "";
            elseif ($prefixe != "")
                $prefixe .= "/";

            return $prefixe . $url;
        }

        BimpCore::addlog('FICHIER ABSENT: "' . DOL_DOCUMENT_ROOT . '/' . $file_path . '"', Bimp_Log::BIMP_LOG_ERREUR);
        return '';
    }

    public static function getConfCache()
    {
        if (is_null(self::$conf_cache)) {
            self::$conf_cache = array();

            $rows = BimpCache::getBdb()->getRows('bimpcore_conf');

            foreach ($rows as $r) {
                $module = $r->module;

                if (!$module) {
                    $module = 'bimpcore';
                }

                if (!isset(self::$conf_cache[$module])) {
                    self::$conf_cache[$module] = array();
                }

                self::$conf_cache[$module][$r->name] = $r->value;
            }
        }

        return self::$conf_cache;
    }

    public static function getConf($name, $default = null, $module = 'bimpcore')
    {
        // Si le paramètre n'est pas enregistré en base, on retourne en priorité la valeur par défaut
        // passée en argument de la fonction. 
        // si cette argument est null on retourne la valeur par défaut définie dans le YML de la config du module (si elle est existe)
        // on retourne null sinon. 
        // Le système de cache permet de vérifier une seule fois la valeur en base et la valeur par défaut du YML. 


        if (!$module) {
            $module = 'bimpcore';
        }

        $cache = self::getConfCache();

        if (isset($cache[$module][$name])) {
//            echo '<br/>OK: ' . $name . ': ' . $cache[$module][$name];
            return $cache[$module][$name];
        } 
//        else {
//            echo '<br/>FAIL: ' . $name;
//        }

        // Check éventuelle erreur sur le module: 
        foreach ($cache as $module_name => $params) {
            if (isset($params[$name])) {
                BimpCore::addlog('BimpCore::getConf() - Erreur module possible', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', null, array(
                    'Paramètre'                                  => $name,
                    'Module demandé'                             => $module,
                    'Module trouvé (tel que enregistré en base)' => $module_name
                        ), true);
                break;
            }
        }

        if (!is_null($default)) {
            return $default;
        }

        if (!isset(self::$conf_cache_def_values[$module][$name])) {
            self::$conf_cache_def_values[$module][$name] = BimpModuleConf::getParamDefaultValue($name, $module, true);
        }

        return self::$conf_cache_def_values[$module][$name];
    }

    public static function setConf($name, $value, $module = 'bimpcore')
    {
        if (!$module) {
            $module = 'bimpcore';
        }

        $errors = array();

        $current_val = (isset(self::$conf_cache[$module][$name]) ? self::$conf_cache[$module][$name] : null);

        $bdb = BimpCache::getBdb();

        if (is_null($current_val)) {
            if ($bdb->insert('bimpcore_conf', array(
                        'name'   => $name,
                        'value'  => $value,
                        'module' => $module
                    )) <= 0) {
                $errors[] = 'Echec de l\'insertion du paramètre "' . $name . '" (Module ' . $module . ') - ' . $bdb->err();
            }
        } else {
            if ($bdb->update('bimpcore_conf', array(
                        'value' => $value
                            ), '`name` = \'' . $name . '\' AND `module` = \'' . $module . '\'') <= 0) {
                $errors[] = 'Echec de la mise à jour du paramètre "' . $name . '" (Module ' . $module . ') - ' . $bdb->err();
            }
        }

        self::$conf_cache[$module][$name] = $value;
    }

    public static function getVersion($dev = '')
    {
        self::getConfCache();
        if (!isset(self::$conf_cache['bimpcore_version']) || ($dev && !isset(self::$conf_cache['bimpcore_version'][$dev]))) {
            global $db;
            $bdb = new BimpDb($db);

            $value = $bdb->getValue('bimpcore_conf', 'value', '`name` = \'bimpcore_version\'');

            $update = false;

            if (preg_match('/^[0-9]+(\.[0-9])*$/', $value)) {
                $versions = array(
                    'florian' => (float) $value
                );
                $update = true;
            } else {
                $versions = json_decode($value, 1);
            }

            if ($dev && !isset($versions[$dev])) {
                $versions[$dev] = 0;
                $update = true;
            }

            if ($update) {
                $bdb->update('bimpcore_conf', array(
                    'value' => json_encode($versions)
                        ), '`name` = \'bimpcore_version\'');
            }


            self::$conf_cache['bimpcore_version'] = $versions;
        }

        if ($dev) {
            return self::$conf_cache['bimpcore_version'][$dev];
        }

        return self::$conf_cache['bimpcore_version'];
    }

    public static function setVersion($dev, $version)
    {
        $versions = self::getVersion();

        if (!isset($versions[$dev])) {
            $versions[$dev] = array();
        }

        $versions[$dev] = $version;

        self::$conf_cache['bimpcore_version'] = $versions;

        global $db;
        $bdb = new BimpDb($db);

        $bdb->update('bimpcore_conf', array(
            'value' => json_encode($versions)
                ), '`name` = \'bimpcore_version\'');
    }

    public static function getParam($full_path, $default_value = '', $type = 'string')
    {
        if (is_null(self::$config)) {
            if (defined('PATH_EXTENDS') && file_exists(PATH_EXTENDS . '/bimpcore/config.yml')) {
                self::$config = new BimpConfig(PATH_EXTENDS . '/bimpcore/', 'config.yml', new BimpObject('', ''));
            } elseif (file_exists(DOL_DOCUMENT_ROOT . '/bimpcore/default_config.yml')) {
                self::$config = new BimpConfig(DOL_DOCUMENT_ROOT . '/bimpcore/', 'default_config.yml', new BimpObject('', ''));
            }
        }

        if (!is_null(self::$config)) {
            return self::$config->get($full_path, $default_value, false, $type);
        }

        return $default_value;
    }

    public static function getModulesUpdates()
    {
        $updates = array();

        $cache = self::getConfCache();

        foreach ($cache as $name => $value) {
            if (preg_match('/^module_version_(.+)$/', $name, $matches)) {
                $module = $matches[1];

                $dir = DOL_DOCUMENT_ROOT . '/' . $module . '/sql';
                if (file_exists($dir) && is_dir($dir)) {
                    $files = scandir($dir);

                    foreach ($files as $f) {
                        if (in_array($f, array('.', '..'))) {
                            continue;
                        }

                        if (preg_match('/^(\d+\.\d)\.sql$/', $f, $matches2)) {
                            if ((float) $matches2[1] > (float) $value) {
                                if (!isset($updates[$module])) {
                                    $updates[$module] = array();
                                }
                                $updates[$module][] = (float) $matches2[1];
                            }
                        }
                    }
                }
            }
        }

        return $updates;
    }

    public static function setModuleVersion($module, $version)
    {
        
    }

    public static function isModuleActive($module)
    {
        return ((string) self::getConf('module_version_' . $module, '') ? 1 : 0);
    }

    public static function isModeDev()
    {
        if (defined('MOD_DEV') && MOD_DEV) {
            return 1;
        }

        return 0;
    }

    public static function isUserDev()
    {
        global $user;

        if (BimpObject::objectLoaded($user)) {
            $bimpUser = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', $user->id);

            if ((int) $bimpUser->getData('is_dev')) {
                return 1;
            }
        }

        return 0;
    }

    // Gestion ini: 

    public static function setMaxExecutionTime($time)
    {
        set_time_limit(0);

        if ($time > self::$max_execution_time) {
            ini_set('max_execution_time', $time);
            self::$max_execution_time = $time;
        }
    }

    public static function setMemoryLimit($limit)
    {
        if ($limit > self::$memory_limit) {
            ini_set('memory_limit', $limit . 'M');
            self::$memory_limit = $limit;
        }
    }

    // Gestion extends: 

    public static function getEntity()
    {
        if (defined('BIMP_ENTITY')) {
            return BIMP_ENTITY;
        }

        return '';
    }

    // Gestion du contexte:

    public static function getContext()
    {
        if (self::$context) {
            return self::$context;
        }

        if (isset($_REQUEST['bimp_context'])) {
            self::setContext($_REQUEST['bimp_context']);
            return self::$context;
        }

//        if (isset($_SESSION['bimp_context'])) {
//            return $_SESSION['bimp_context'];
//        }

        return "";
    }

    public static function setContext($context)
    {
        self::$context = $context;
//        $_SESSION['bimp_context'] = $context;
    }

    public static function isContextPublic()
    {
        return (self::getContext() == 'public' ? 1 : 0);
    }

    public static function isContextPrivate()
    {
        return (self::getContext() != 'public' ? 1 : 0);
    }

    // Gestion des logs:

    public static function addLogs_debug_trace($msg)
    {
        $bt = debug_backtrace(null, 30);
        if (is_array($msg))
            $msg = implode(' - ', $msg);
        static::addLogs_extra_data([$msg => BimpTools::getBacktraceArray($bt)]);
    }

    public static function addLogs_extra_data($array)
    {
        if (!is_array($array)) {
            $array = array($array);
        }

        static::$logs_extra_data = BimpTools::merge_array(static::$logs_extra_data, $array);
    }

    public static function addlog($msg, $level = 1, $type = 'bimpcore', $object = null, $extra_data = array(), $force = false)
    {
        // $bimp_logs_locked: Eviter boucles infinies

        global $bimp_logs_locked, $user;

        if (is_null($bimp_logs_locked)) {
            $bimp_logs_locked = 0;
        }

        if (!$bimp_logs_locked) {
            $bimp_logs_locked = 1;
            $extra_data = BimpTools::merge_array(static::$logs_extra_data, $extra_data);
            if (BimpCore::isModeDev() && (int) self::getConf('print_bimp_logs') && !defined('NO_BIMPLOG_PRINTS')) {
                $bt = debug_backtrace(null, 30);

                $html = 'LOG ' . Bimp_Log::$levels[$level]['label'] . '<br/><br/>';
                $html .= 'Message: ' . $msg . '<br/><br/>';

                if (is_a($object, 'BimpObject') && BimpObject::objectLoaded($object)) {
                    $html .= 'Objet: ' . $object->getLink() . '<br/><br/>';
                }

                if (!empty($extra_data)) {
                    $html .= 'Données: <pre>';
                    $html .= print_r($extra_data, 1);
                    $html .= '</pre>';
                }

                $html .= 'Backtrace: <br/>';
                $html .= BimpRender::renderBacktrace(BimpTools::getBacktraceArray($bt));

                die($html);
            }

            if (!$force && $level < Bimp_Log::BIMP_LOG_ERREUR && (int) BimpCore::getConf('mode_eco')) {
                return array();
            }

            if (!(int) BimpCore::getConf('use_bimp_logs') && !(int) BimpTools::getValue('use_logs', 0)) {
                return array();
            }

            $errors = array();

            $check = true;
            foreach (Bimp_Log::$exclude_msg_prefixes as $prefixe) {
                if (strpos($msg, $prefixe) === 0) {
                    $check = false;
                }
            }

            if ($check) {
                // On vérifie qu'on n'a pas déjà un log similaire:
                $id_current_log = BimpCache::bimpLogExists($type, $level, $msg, $extra_data);

                $mod = '';
                $obj = '';
                $id = 0;

                if (is_a($object, 'BimpObject')) {
                    $mod = $object->module;
                    $obj = $object->object_name;
                    $id = (int) $object->id;
                }

                $data = array(
                    'id_user'    => (BimpObject::objectLoaded($user) ? (int) $user->id : 1),
                    'obj_module' => $mod,
                    'obj_name'   => $obj,
                    'id_object'  => $id,
                    'backtrace'  => BimpTools::getBacktraceArray(debug_backtrace(null, 15))
                );

                if (!$id_current_log) {
                    $data = BimpTools::merge_array($data, array(
                                'type'       => $type,
                                'level'      => $level,
                                'msg'        => $msg,
                                'extra_data' => $extra_data,
                    ));
                    $log = BimpObject::createBimpObject('bimpcore', 'Bimp_Log', $data, true, $errors);

                    if (BimpObject::objectLoaded($log)) {
                        BimpCache::addBimpLog((int) $log->id, $type, $level, $msg, $extra_data);
                        if (BimpDebug::isActive()) {
                            BimpDebug::incCacheInfosCount('logs', true);
                        }
                    }
                } else {
                    if (BimpDebug::isActive()) {
                        BimpDebug::incCacheInfosCount('logs', false);
                    }

                    $log = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Log', $id_current_log);
                    $log->set('last_occurence', date('Y-m-d H:i:d'));
                    $log->set('nb_occurence', $log->getData('nb_occurence') + 1);

                    $warnings = array();
                    $errUpdate = $log->update($warnings, true);

                    $data = array(); // inutile de mettre les data de bases (Type, level, msg, extra_data) qui sont forcéments identiques.

                    if (defined('ID_ERP')) {
                        $data['ID ERP'] = ID_ERP;
                    }

                    if (BimpObject::objectLoaded($user)) {
                        $data['User'] = '#' . $user->id;
                    }

                    if (BimpObject::objectLoaded($object)) {
                        $data['Objet'] = BimpObject::getInstanceNomUrl($object);
                    }

                    if (count($errUpdate)) {
                        $data['Erreurs Màj log'] = $errUpdate;
                    }

                    $data['GET'] = $_GET;
                    $data['POST'] = $_POST;
                    $log->addNote('<pre>' . print_r($data, 1) . '</pre>');
                }
            }

            $bimp_logs_locked = 0;
        }

        return $errors;
    }

    // Chargements librairies:

    public static function loadPhpExcel()
    {
        require_once DOL_DOCUMENT_ROOT . '/bimpcore/libs/PHPExcel-1.8/Classes/PHPExcel.php';
    }

    public static function LoadHtmlPurifier()
    {
        require_once DOL_DOCUMENT_ROOT . '/bimpcore/libs/htmlpurifier-4.13.0/HTMLPurifier.auto.php';
    }

    public static function getHtmlPurifier()
    {
        if (is_null(self::$html_purifier)) {
            self::LoadHtmlPurifier();

            $config = HTMLPurifier_Config::createDefault();

            $root = '';

            if (defined('PATH_TMP') && PATH_TMP) {
                $root = PATH_TMP;
                $path = '/htmlpurifier/serialiser';
            } else {
                $root = DOL_DATA_ROOT;
                $path = '/bimpcore/htmlpurifier/serialiser';
            }

            if (!is_dir($root . $path)) {
                BimpTools::makeDirectories($path, $root);
            }

            $config->set('Cache.SerializerPath', $root . $path);

            self::$html_purifier = new HTMLPurifier($config);
        }

        return self::$html_purifier;
    }

    public static function loadBimpApiLib()
    {
        require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';
    }
}
