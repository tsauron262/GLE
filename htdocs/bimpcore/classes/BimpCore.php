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

        return $errors;
    }

    public static function getVersion($dev = '')
    {
        $versions = self::getConf('bimpcore_version');

        if (!is_array($versions)) {
            if ((string) $versions) {
                $versions = json_decode($versions, 1);
            }

            $update = false;

            if (empty($versions)) {
                // On vérifie valeur en base:
                $bdb = BimpCache::getBdb();
                $value = $bdb->getValue('bimpcore_conf', 'value', '`name` = \'bimpcore_version\'');

                if (!(string) $value) {
                    $bdb->insert('bimpcore_conf', array(
                        'value' => json_encode(array(
                            'florian' => 0,
                            'tommy'   => 0,
                            'romain'  => 0,
                            'alexis'  => 0
                        )),
                    ));
                } else {
                    if (preg_match('/^[0-9]+(\.[0-9])*$/', $value)) {
                        // Pour compat:
                        $versions = array(
                            'florian' => (float) $value
                        );
                        $update = true;
                    } else {
                        $versions = json_decode($value, 1);
                    }
                }
            }
        }

        if ($dev && !isset($versions[$dev])) {
            $versions[$dev] = 0;
            $update = true;
        }

        if ($update) {
            $bdb->update('bimpcore_conf', array(
                'value' => json_encode($versions)
                    ), '`name` = \'bimpcore_version\' AND `module` = \'bimpcore\'');
            self::$conf_cache['bimpcore']['bimpcore_version'] = json_encode($versions);
        }

        if ($dev) {
            return (float) BimpTools::getArrayValueFromPath($versions, $dev, 0);
        }

        return $versions;
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

    public static function getModulesExtendsUpdates()
    {
        if (!defined('BIMP_EXTENDS_VERSION') && !defined('BIMP_EXTENDS_ENTITY')) {
            return array();
        }
        $updates = array();

        $cache = self::getConfCache();

        if (isset($cache['bimpcore'])) {
            foreach ($cache['bimpcore'] as $name => $value) {
                if (preg_match('/^module_version_(.+)$/', $name, $matches)) {
                    $module = $matches[1];

                    if (defined('BIMP_EXTENDS_VERSION')) {
                        $dir = DOL_DOCUMENT_ROOT . '/' . $module . '/extends/versions/' . BIMP_EXTENDS_VERSION . '/sql';
                        if (file_exists($dir) && is_dir($dir)) {
                            $current_version = (float) BimpCore::getConf('module_sql_version_' . $module . '_version_' . BIMP_EXTENDS_VERSION, 0);
                            $files = scandir($dir);

                            foreach ($files as $f) {
                                if (in_array($f, array('.', '..'))) {
                                    continue;
                                }

                                if (preg_match('/^(\d+\.\d)\.sql$/', $f, $matches2)) {
                                    if ((float) $matches2[1] > $current_version) {
                                        if (!isset($updates[$module])) {
                                            $updates[$module] = array();
                                        }
                                        if (!isset($updates[$module]['version'])) {
                                            $updates[$module]['version'] = array();
                                        }

                                        $updates[$module]['version'][] = (float) $matches2[1];
                                    }
                                }
                            }
                        }
                    }

                    if (defined('BIMP_EXTENDS_ENTITY')) {
                        $dir = DOL_DOCUMENT_ROOT . '/' . $module . '/extends/entities/' . BIMP_EXTENDS_ENTITY . '/sql';
                        if (file_exists($dir) && is_dir($dir)) {
                            $current_version = (float) BimpCore::getConf('module_sql_version_' . $module . '_entity_' . BIMP_EXTENDS_ENTITY, 0);
                            $files = scandir($dir);

                            foreach ($files as $f) {
                                if (in_array($f, array('.', '..'))) {
                                    continue;
                                }

                                if (preg_match('/^(\d+\.\d)\.sql$/', $f, $matches2)) {
                                    if ((float) $matches2[1] > $current_version) {
                                        if (!isset($updates[$module])) {
                                            $updates[$module] = array();
                                        }
                                        if (!isset($updates[$module]['entity'])) {
                                            $updates[$module]['entity'] = array();
                                        }

                                        $updates[$module]['entity'][] = (float) $matches2[1];
                                    }
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

        for ($i = 0; $i < 10; $i++) {
            if ($i > 0) {
                sleep(1);
            }
            $row = $bdb->getRow('bimpcore_object_lock', $where, array('tms', 'id_user'), 'array', 'tms', 'DESC');

            if (!is_null($row) && (int) $row['tms'] < time() - 600) {
                // Si locké depuis + de 12 minutes
                $bdb->update('bimpcore_object_lock', array(
                    'id_user' => $user->id,
                    'tms'     => time()
                        ), $where);
                return false;
            }

            if (is_null($row)) {
                global $bimp_object_locked_id;
                $bimp_object_locked_id = $bdb->insert('bimpcore_object_lock', array(
                    'obj_module' => $object->module,
                    'obj_name'   => $object->object_name,
                    'id_object'  => $object->id,
                    'tms'        => time(),
                    'id_user'    => $user->id
                        ), true);
                return false;
            }
        }

        global $user;

        $msg = '';

        if ((int) $user->id === (int) $row['id_user']) {
            $msg = 'Vous avez déjà lancé une opération sur ' . $object->getLabel('the') . ' ' . $object->getRef(true) . '<br/>';
            $msg .= 'Veuillez attendre que l\'opération en cours soit terminée avant de relancer l\'enregistrement.<br/>';
            $msg .= '<b>Note: ceci est une protection volontaire pour éviter un écrasement de données. Il ne s\'agit pas d\'un bug</b>';

            $diff = ((int) $row['tms'] + 720) - time();
            $min = floor($diff / 60);
            $secs = $diff - ($min * 60);

            $msg .= '<div style="margin-top: 15px; font-weight: bold;">';
            $msg .= 'Si l\'opération en cours sur ' . $object->getLabel('this') . ' a échoué (erreur fatale, problème réseau, etc.), ';
            $msg .= 'le vérouillage sera automatiquement désactivé dans ';
            $msg .= ($min ? $min . ' min. ' . ($secs ? 'et ' : '') : '') . ($secs ? $secs . ' sec.' : '');
            $msg .= '</div>';

            if ($user->admin || (isset($object->allow_force_unlock) && $object->allow_force_unlock)) {
                $msg .= '<div style="margin: 15px 0; text-align: center">';
                if (!$user->admin) {
                    $msg .= 'Si vous êtes sûr de n\'avoir aucune opération en cours, vous pouvez cliquer sur le bouton ci-dessous.<br/>';
                    $msg .= 'Une fois fois le dévéroullage effectué, relancez l\'opération qui a été bloquée (en cliquant à nouveau sur le bouton "Enregistrer" ou "Valider")';
                    $msg .= '<br/>';
                }

                $msg .= '<span class="btn btn-default" onclick="forceBimpObjectUnlock($(this), ' . $object->getJsObjectData() . ')">';

                if ($user->admin) {
                    $msg .= 'Forcer le dévérouillage (Admins seulement)';
                } else {
                    $msg .= 'Dévérouiller';
                }
                $msg .= '</span>';
                $msg .= '</div>';
            }
        } else {
            $lock_user = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $row['id_user']);

            $msg = 'Une opération est déjà en cours sur ' . $object->getLabel('the') . ' ' . $object->getRef(true);
            if (BimpObject::objectLoaded($lock_user)) {
                $msg .= ' par l\'utilisateur ' . $lock_user->getLink();
            }
            $msg .= '<br/>';
            $msg .= 'Il est nécessaire d\'attendre que celle-ci soit terminée pour éviter un conflit sur l\'enregistrement des données.<br/>';
            $msg .= 'Merci d\'attendre une dizaine de secondes et de réessayer.<br/>';
            $msg .= '<b>Etant donné qu\'il est possible que les données de ' . $object->getLabel('this') . ' aient été modifiées, il est recommandé ';
            $msg .= ' <a href="javascript:bimp_reloadPage()">d\'actualiser la page</a> avant de retenter l\'opération</b><br/><br/>';
            $msg .= '<b>Note: ceci est une protection volontaire pour éviter un écrasement de données. Il ne s\'agit pas d\'un bug</b>';
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

    public static function forceUnlockCurrentObject()
    {
        global $bimp_object_locked_id;

        if ((int) $bimp_object_locked_id) {
            $bdb = BimpCache::getBdb(true, -1, true);
            $bdb->delete('bimpcore_object_lock', 'id = ' . $bimp_object_locked_id);
        }
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
