<?php

class BimpCore
{

    public static $conf_cache = null;
    public static $conf_cache_not_exist = array();
    public static $files = array(
        'js'  => array(
            '/includes/jquery/plugins/jpicker/jpicker-1.1.6.js',
            '/bimpcore/views/js/moment.min.js',
            '/bimpcore/views/js/bootstrap.min.js',
            '/bimpcore/views/js/bootstrap-datetimepicker.js',
            '/bimpcore/views/js/functions.js',
            '/bimpcore/views/js/scroller.js',
            '/bimpcore/views/js/ajax.js',
//            '/bimpcore/views/js/component.js',
            '/bimpcore/views/js/modal.js',
            '/bimpcore/views/js/object.js',
            '/bimpcore/views/js/filters.js',
            '/bimpcore/views/js/form.js',
            '/bimpcore/views/js/list.js',
            '/bimpcore/views/js/view.js',
            '/bimpcore/views/js/viewsList.js',
            '/bimpcore/views/js/listCustom.js',
            '/bimpcore/views/js/statsList.js',
            '/bimpcore/views/js/page.js',
            '/bimpcore/views/js/table2csv.js',
            '/bimpuserconfig/views/js/buc.js',
            '/bimpcore/views/js/notification.js',
            '/bimpcore/views/js/bimpcore.js'
        ),
        'css' => array(
            '/includes/jquery/plugins/jpicker/css/jPicker-1.1.6.css',
            '/bimpcore/views/css/bimpcore.css',
            '/bimpuserconfig/views/css/userConfig.css',
//            '/theme/BimpTheme/views/dist/css/theme.css' // TODO remove
        )
    );
    public static $filesInit = false;
    public static $config = null;
    public static $dev_mails = array(
        'tommy'   => 'tommy@bimp.fr',
        'florian' => 'f.martinez@bimp.fr',
        'alexis'  => 'al.bernard@bimp.fr',
        'romain'  => 'r.PELEGRIN@bimp.fr'
    );

    public static function displayHeaderFiles($echo = true)
    {
        global $noBootstrap;
        if ($noBootstrap)
            unset(static::$files['js'][2]);


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
            $html .= ' var context = "' . BimpTools::getContext() . '";';
                    
                    // Inclusion notifications
                    $notification = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNotification');
                    $config_notification = $notification->getList();
                    
                    $html .= 'var notificationActive = {';

                    foreach($config_notification as $cn) {
                        $html .= $cn['nom'] . ": {";
                        $html .= "module: '" . $cn['module'] . "',";
                        $html .= "class: '" . $cn['class'] . "' ,";
                        $html .= "method: '" . $cn['method'] . "' ,";
                        $html .= "obj: null},";
                    }
                    $html .= '};';
                    // Fin inclusion notifications

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
//                                if (!self::isModeDev()) {
                                BimpCore::addlog('Echec création du fichier "' . DOL_DOCUMENT_ROOT . '/' . $out_file . '" - Vérifier les droits', Bimp_Log::BIMP_LOG_ALERTE);
//                                }
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
                self::$conf_cache[$r->name] = $r->value;
            }
        }

        return self::$conf_cache;
    }

    public static function getConf($name, $default = null)
    {
        $cache = self::getConfCache();

        if (isset(self::$conf_cache_not_exist[$name])) {
            return $default;
        }

        if (!isset($cache[$name])) {
            $value = BimpCache::getBdb()->getValue('bimpcore_conf', 'value', '`name` = \'' . $name . '\'');
            if (!is_null($value)) {
                self::$conf_cache[$name] = $value;
                return $value;
            } else {
                self::$conf_cache_not_exist[$name] = $name;
            }

            return $default;
        }

        return self::$conf_cache[$name];
    }

    public static function setConf($name, $value)
    {
        $current_val = self::getConf($name);

        $bdb = BimpCache::getBdb();

        if (is_null($current_val)) {
            $bdb->insert('bimpcore_conf', array(
                'name'  => $name,
                'value' => $value
            ));
        } else {
            $bdb->update('bimpcore_conf', array(
                'value' => $value
                    ), '`name` = \'' . $name . '\'');
        }

        self::$conf_cache[$name] = $value;
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
            if (defined('PATH_EXTENDS') && file_exists(PATH_EXTENDS.'/bimpcore/config.yml')) {
                echo 'ici'; exit;
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

    // Gestion des logs: 

    public static function addlog($msg, $level = 1, $type = 'bimpcore', $object = null, $extra_data = array())
    {
        $errors = array();

        // $bimp_logs_locked: Eviter boucles infinies 
        global $bimp_logs_locked;

        if (is_null($bimp_logs_locked)) {
            $bimp_logs_locked = 0;
        }

        if (!$bimp_logs_locked) {
            $bimp_logs_locked = 1;

            $check = true;
            foreach (Bimp_Log::$exclude_msg_prefixes as $prefixe) {
                if (strpos($msg, $prefixe) === 0) {
                    $check = false;
                }
            }

            if ($check) {
                // On vérifie qu'on n'a pas déjà un log similaire:
                $id_current_log = BimpCache::bimpLogExists($type, $level, $msg, $extra_data);

                if (!$id_current_log) {
                    if ((int) BimpCore::getConf('bimpcore_use_logs', 0) || (int) BimpTools::getValue('use_logs', 0)) {
                        global $user;

                        $mod = '';
                        $obj = '';
                        $id = 0;

                        if (is_a($object, 'BimpObject')) {
                            $mod = $object->module;
                            $obj = $object->object_name;
                            $id = (int) $object->id;
                        }

                        $bt = debug_backtrace(null, 15);

                        $log = BimpObject::createBimpObject('bimpcore', 'Bimp_Log', array(
                                    'id_user'    => (BimpObject::objectLoaded($user) ? (int) $user->id : 1),
                                    'type'       => $type,
                                    'level'      => $level,
                                    'msg'        => $msg,
                                    'obj_module' => $mod,
                                    'obj_name'   => $obj,
                                    'id_object'  => $id,
                                    'extra_data' => $extra_data,
                                    'backtrace'  => BimpTools::getBacktraceArray($bt)
                                        ), true, $errors);

                        if (BimpObject::objectLoaded($log)) {
                            BimpCache::addBimpLog((int) $log->id, $type, $level, $msg, $extra_data);
                            if (BimpDebug::isActive()) {
                                BimpDebug::incCacheInfosCount('logs', true);
                            }
                        }
                    }
                } else {
                    if (BimpDebug::isActive()) {
                        BimpDebug::incCacheInfosCount('logs', false);
                    }
//                    $log = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Log', $id_current_log);
//                    $log->set('last_occurence', date('Y-m-d H:i:s'));
//                    $log->set('nb_occurence', (int) $log->getData('nb_occurence') + 1);
//                    $errors = BimpTools::merge_array($errors, $log->update());
                    $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'bimpcore_log SET';
                    $sql .= ' nb_occurence = (nb_occurence + 1)';
                    $sql .= ', last_occurence = \'' . date('Y-m-d H:i:d').'\'';
                    $sql .= ' WHERE id = ' . $id_current_log;
                    BimpCache::getBdb()->execute($sql);
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
}
