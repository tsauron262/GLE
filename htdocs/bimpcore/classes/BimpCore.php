<?php

class BimpCore
{

    public static $is_init = false;
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
            'userConfig' => '/bimpuserconfig/views/css/userConfig.css'
        )
    );
    public static $js_vars = array();
    public static $filesInit = false;
    public static $layoutInit = false;
    public static $config = null;
    public static $dev_mails = array(
        'tommy'   => 't.sauron@bimp.fr',
        'florian' => 'f.martinez@bimp.fr',
        'alexis'  => 'al.bernard@bimp.fr',
        'romain'  => 'r.PELEGRIN@bimp.fr',
        'peter'   => 'p.tkatchenko@bimp.fr'
    );
    public static $html_purifier = null;

    // Initialisation: 

    public static function init()
    {
        if (!self::$is_init) {
            self::$is_init = true;

            global $noBootstrap, $conf;

            if (BimpDebug::isActive()) {
                BimpDebug::addDebugTime('Début affichage page');
            }

            if (stripos($_SERVER['PHP_SELF'], 'bimpinterfaceclient') === false) {
                self::setContext("private"); // Todo: trouver meilleure solution (Le contexte privé / public doit être indépendant du module) 
            }

            if ($noBootstrap) {
                self::$files['js'] = BimpTools::unsetArrayValue(self::$files['js'], '/bimpcore/views/js/bootstrap.min.js');
            }

            if (self::isContextPrivate()) {
                self::$files['js'][] = '/bimpcore/views/js/notification.js';
            } else {
                self::$files['css']['bimpcore'] = '/bimpcore/views/css/bimpcore_public.css';
            }

            BimpConfig::initCacheServeur();
            self::checkBimpCoreVersion();
        }
    }

    // Gestion Layout / js / css: 

    public static function initLayout()
    {
        if (!self::$layoutInit) {
            self::$layoutInit = true;
        }

        $layout = BimpLayout::getInstance();

        // Ajout fichiers CSS BimpCore:
        foreach (self::$files['css'] as $css_file) {
            $layout->addCssFile(self::getFileUrl($css_file, true, false));
        }

        // Ajout fichiers JS BimpCore: 
        foreach (self::$files['js'] as $js_file) {
            $layout->addJsFile(self::getFileUrl($js_file, true, false));
        }

        // Ajouts variables JS: 
        $layout->addJsVars(self::getJsVars());

        self::$filesInit = true;
    }

    public static function getJsVars()
    {
        global $user, $conf;
        $vars = array(
            'dol_url_root'    => '\'' . DOL_URL_ROOT . '\'',
            'id_user'         => (BimpObject::objectLoaded($user) ? $user->id : 0),
            'bimp_context'    => '\'' . self::getContext() . '\'',
            'theme'           => '\'' . (isset($user->conf->MAIN_THEME) ? $user->conf->MAIN_THEME : $conf->global->MAIN_THEME) . '\'',
            'sessionHideMenu' => (BimpController::getSessionConf('hideMenu') == "true" ? 1 : 0)
        );

        $notifs = '{';
        if (self::isContextPrivate()) {
            $notification = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNotification');
            $config_notification = $notification->getList();

            foreach ($config_notification as $cn) {
                $notifs .= $cn['nom'] . ": {";
                $notifs .= "module: '" . $cn['module'] . "', ";
                $notifs .= "class: '" . $cn['class'] . "', ";
                $notifs .= "method: '" . $cn['method'] . "', ";
                $notifs .= "obj: null},";
            }
        }
        $notifs .= '}';

        $vars['notificationActive'] = $notifs;

        return $vars;
    }

    public static function displayHeaderFiles($echo = true)
    {
        $html = '';
        if (!self::$filesInit) {
            foreach (self::$files['css'] as $css_file) {
                $url = self::getFileUrl($css_file);
                if ($url) {
                    $html .= '<link type="text/css" rel="stylesheet" href="' . $url . '"/>' . "\n";
                }
            }

            $js_vars = self::getJsVars();

            if (!empty($js_vars)) {
                $html .= "\n" . '<script type="text/javascript">' . "\n";
                foreach ($js_vars as $var_name => $var_value) {
                    $html .= "\t" . 'var ' . $var_name . ' = ';
                    if (BimpTools::isNumericType($var_value)) {
                        $html .= $var_value;
                    } else {
                        $html .= '\'' . $var_value . '\'';
                    }
                    $html .= ';' . "\n";
                }
                $html .= '</script>' . "\n\n";
            }

            foreach (self::$files['js'] as $js_file) {
                $url = self::getFileUrl($js_file);
                if ($url) {
                    $html .= '<script type="text/javascript" src="' . $url . '"></script>' . "\n";
                }
            }

            self::$filesInit = true;
        }

        if ($echo) {
            echo $html;
        }

        return $html;
    }

    public static function getFileUrl($file_path, $use_tms = true, $include_root = true)
    {
        $url = '';

        if (preg_match('/^\/+(.+)$/', $file_path, $matches)) {
            $file_path = $matches[1];
        }

        if (file_exists(DOL_DOCUMENT_ROOT . '/' . $file_path)) {
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
        }

        if ($url) {
            if ($include_root) {
                $prefixe = DOL_URL_ROOT;
                if ($prefixe == "/")
                    $prefixe = "";
                elseif ($prefixe != "")
                    $prefixe .= "/";

                return $prefixe . $url;
            }
            return $url;
        }

        BimpCore::addlog('FICHIER ABSENT: "' . DOL_DOCUMENT_ROOT . '/' . $file_path . '"', Bimp_Log::BIMP_LOG_ERREUR);
        return '';
    }

    // Gestion Versions des modules: 

    public static function checkBimpCoreVersion()
    {
        if (BimpTools::isSubmit('ajax')) {
            return;
        }

        global $user;

        if (!$user->admin) {
            return;
        }

        if ((int) BimpCore::getConf('check_versions_lock', 0)) {
            return;
        }

        $dir = DOL_DOCUMENT_ROOT . '/bimpcore/updates';
        $updates = array();
        foreach (scandir($dir) as $subDir) {
            if (in_array($subDir, array('.', '..'))) {
                continue;
            }

            if (preg_match('/^[a-z]+$/', $subDir) && is_dir($dir . '/' . $subDir)) {
                $current_version = (float) BimpCore::getVersion($subDir);
                foreach (scandir($dir . '/' . $subDir) as $f) {
                    if (in_array($f, array('.', '..'))) {
                        continue;
                    }
                    if (preg_match('/^(\d+(\.\d{1})*)\.sql$/', $f, $matches)) {
                        if ((float) $matches[1] > (float) $current_version) {
                            if (!isset($updates[$subDir])) {
                                $updates[$subDir] = array();
                            }
                            $updates[$subDir][] = (float) $matches[1];
                        }
                    }
                }
                if (isset($updates[$subDir])) {
                    sort($updates[$subDir]);
                }
            }
        }

        $modules_updates = BimpCore::getModulesUpdates();

        if (!empty($updates) || !empty($modules_updates)) {
            if (!BimpTools::isSubmit('bimpcore_update_confirm')) {
                $url = $_SERVER['REQUEST_URI'];
                if (empty($_SERVER['QUERY_STRING'])) {
                    $url .= '?';
                } else {
                    $url .= '&';
                }
                $url .= 'bimpcore_update_confirm=1';
                echo 'Le module BimpCore doit etre mis a jour<br/><br/>';
                echo 'Liste des mise à jour: ';
                if (!empty($updates)) {
                    echo '<pre>';
                    print_r($updates);
                    echo '</pre>';
                } elseif (!empty($modules_updates)) {
                    echo '<pre>';
                    print_r($modules_updates);
                    echo '</pre>';
                }
                echo '<button type="button" onclick="window.location = \'' . $url . '\'">OK</button>';
                exit;
            } else {
                $bdb = BimpCache::getBdb();

                if (!empty($updates) || !empty($modules_updates)) {
                    BimpCore::setConf('check_versions_lock', 1);
                }

                if (!empty($updates)) {
                    foreach ($updates as $dev => $dev_updates) {
                        sort($dev_updates);
                        $new_version = 0;
                        $dev_dir = DOL_DOCUMENT_ROOT . '/bimpcore/updates/' . $dev . '/';
                        foreach ($dev_updates as $version) {
                            $new_version = $version;
                            $version = (string) $version;
                            if (!file_exists($dev_dir . $version . '.sql') && preg_match('/^[0-9]+$/', $version)) {
                                if (file_exists($dev_dir . $version . '.0.sql')) {
                                    $version .= '.0';
                                }
                            }
                            if (!file_exists($dev_dir . $version . '.sql')) {
                                echo 'FICHIER ABSENT: ' . $dev_dir . $version . '.sql <br/>';
                                continue;
                            }
                            echo 'Mise a jour du module bimpcore a la version: ' . $dev . '/' . $version;
                            if ($bdb->executeFile($dev_dir . $version . '.sql')) {
                                echo ' [OK]<br/>';
                            } else {
                                echo ' [ECHEC] - ' . $bdb->db->error() . '<br/>';
                            }
                        }
                        echo '<br/>';

                        BimpCore::setVersion($dev, $new_version);
                    }
                }

                if (!empty($modules_updates)) {
                    foreach ($modules_updates as $module => $module_updates) {
                        $dir = DOL_DOCUMENT_ROOT . '/' . $module . '/sql/';

                        if (!file_exists($dir) || !is_dir($dir)) {
                            echo 'ERREUR. Le dossier de mise à jour du module "' . $module . '" n\'existe pas <br/><br/>';
                            continue;
                        }

                        sort($module_updates);
                        $new_version = 0;

                        foreach ($module_updates as $version) {
                            $new_version = $version;
                            $version = (string) $version;

                            if (!file_exists($dir . $version . '.sql') && preg_match('/^[0-9]+$/', $version)) {
                                if (file_exists($dir . $version . '.0.sql')) {
                                    $version .= '.0';
                                }
                            }
                            if (!file_exists($dir . $version . '.sql')) {
                                echo 'FICHIER ABSENT: ' . $dir . $version . '.sql <br/>';
                                continue;
                            }
                            echo 'Mise a jour du module "' . $module . '" à la version: ' . $version;
                            if ($bdb->executeFile($dir . $version . '.sql')) {
                                echo ' [OK]<br/>';
                            } else {
                                echo ' [ECHEC] - ' . $bdb->db->error() . '<br/>';
                            }
                        }
                        echo '<br/>';

                        if ($new_version) {
                            BimpCore::setConf('module_version_' . $module, $new_version);
                        }
                    }
                }

                BimpCore::setConf('check_versions_lock', 0);

                $url = str_replace('bimpcore_update_confirm=1', '', $_SERVER['REQUEST_URI']);
                echo '<br/><button type="button" onclick="window.location = \'' . $url . '\'">OK</button>';
                exit;
            }
        }
    }

    public static function getModulesUpdates()
    {
        $updates = array();

        $cache = self::getConfCache();

        if (isset($cache['bimpcore'])) {
            foreach ($cache['bimpcore'] as $name => $value) {
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
        }

        return $updates;
    }

    public static function setModuleVersion($module, $version)
    {
        // Todo ?
    }

    public static function getVersion($dev = '')
    {
        $cache = self::getConfCache();

        if (!isset($cache['bimpcore']['bimpcore_version']) || ($dev && !isset($cache['bimpcore']['bimpcore_version'][$dev]))) {
            $bdb = BimpCache::getBdb();

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
                        ), '`name` = \'bimpcore_version\' AND `module` = \'bimpcore\'');
            }


            self::$conf_cache['bimpcore']['bimpcore_version'] = $versions;
        }

        if ($dev) {
            return self::$conf_cache['bimpcore']['bimpcore_version'][$dev];
        }

        return self::$conf_cache['bimpcore']['bimpcore_version'];
    }

    public static function setVersion($dev, $version)
    {
        $versions = self::getVersion();

        if (!isset($versions[$dev])) {
            $versions[$dev] = 0;
        }

        $versions[$dev] = $version;

        self::setConf('bimpcore_version', $versions);
    }

    // Gestion BimpCore Conf: 

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

    // Gestion params yml globaux: 

    public static function getParam($full_path, $default_value = '', $type = 'string')
    {
        if (is_null(self::$config)) {
            self::$config = new BimpConfig('bimpcore', '', 'config');
        }

        if (!is_null(self::$config)) {
            return self::$config->get($full_path, $default_value, false, $type);
        }

        return $default_value;
    }

    // Getters booléens: 

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
                    if (defined('ID_ERP')) {
                        $extra_data['ID ERP'] = ID_ERP;
                    }

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
                    } else {
                        echo '<pre>';
                        print_r($errors);
                        exit;
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

    // Gestion des locks 

    public static function checkObjectLock($object)
    {
        if (!(int) self::getConf('use_objects_locks')) {
            return false;
        }

        if (!is_a($object, 'BimpObject') || !BimpObject::objectLoaded($object)) {
            return false;
        }

        global $user;

        $bdb = BimpCache::getBdb(true);

        $where = 'obj_module = \'' . $object->module . '\'';
        $where .= ' AND obj_name = \'' . $object->object_name . '\'';
        $where .= ' AND id_object = ' . $object->id;

        $row = $bdb->getRow('bimpcore_object_lock', $where, array('tms', 'id_user'), 'array', 'tms', 'DESC');

        if (!is_null($row) && (int) $row['tms'] < time() - 720) {
            // Si locké depuis + de 12 minutes
            $bdb->update('bimpcore_object_lock', array(
                'id_user' => $user->id,
                'tms'     => time()
                    ), $where);
            return false;
        }

        if (is_null($row)) {
            $bdb->insert('bimpcore_object_lock', array(
                'obj_module' => $object->module,
                'obj_name'   => $object->object_name,
                'id_object'  => $object->id,
                'tms'        => time(),
                'id_user'    => $user->id
            ));
            return false;
        }

        global $user;

        $msg = '';

        if ((int) $user->id === (int) $row['id_user']) {
            $msg = 'Vous avez déjà lancé une opération sur ' . $object->getLabel('the') . ' ' . $object->getRef(true) . '<br/>';
            $msg .= 'Veuillez attendre que l\'opération en cours soit terminée avant de relancer l\'enregistrement.<br/>';
            $msg .= 'Si vous êtes <b>sûr</b> de n\'avoir aucune opération en cours sur ' . $object->getLabel('this') . ', ';
            $msg .= 'vous pouvez en forcer le dévérouillage en cliquant sur le bouton ci-dessous: ';
            $msg .= '<div style="margin: 15px 0; text-align: center">';
            $msg .= '<span class="btn btn-default" onclick="forceBimpObjectUnlock($(this), ' . $object->getJsObjectData() . ')">';
            $msg .= 'Forcer le dévérouillage ' . $object->getLabel('of_the') . ' ' . $object->getRef(true);
            $msg .= '</span>';
            $msg .= '</div>';
        } else {
            $lock_user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $row['id_user']);

            $msg = 'Une opération est déjà en cours sur ' . $object->getLabel('the') . ' ' . $object->getRef(true);
            if (BimpObject::objectLoaded($lock_user)) {
                $msg .= ' par l\'utilisateur ' . $lock_user->getLink();
            }
            $msg .= '<br/>';
            $msg .= 'Il est nécessaire d\'attendre que celle-ci soit terminée pour éviter un conflit sur l\'enregistrement des données.<br/>';
            $msg .= 'Merci de réessayer ultérieurement.<br/>';
            $msg .= '<b>Etant donné qu\'il est possible que les données de ' . $object->getLabel('this') . ' aient été modifiées, il est recommandé ';
            $msg .= ' <a href="javascript:bimp_reloadPage()">d\'actualiser la page</a> avant de retenter l\'opération</b>';
        }

        return $msg;
    }

    public static function unlockObject($module, $object_name, $id_object)
    {
        if (!(int) self::getConf('use_objects_locks')) {
            return array();
        }

        $errors = array();

        if (!$module) {
            $errors = 'Module absent';
        }

        if (!$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (!$id_object) {
            $errors[] = 'ID objet absent';
        }

        if (!count($errors)) {
            $bdb = BimpCache::getBdb(true);

            $where = 'obj_module = \'' . $module . '\'';
            $where .= ' AND obj_name = \'' . $object_name . '\'';
            $where .= ' AND id_object = ' . $id_object;

            if ($bdb->delete('bimpcore_object_lock', $where) <= 0) {
                $errors[] = 'Echec de la suppression du vérouillage - ' . $bdb->err();
            }
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
