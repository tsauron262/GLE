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
            '/bimpcore/views/js/table2csv.js'
        ),
        'css' => array(
            '/includes/jquery/plugins/jpicker/css/jPicker-1.1.6.css',
            '/bimpcore/views/css/bimpcore.css'
        )
    );
    public static $filesInit = false;
    public static $config = null;

    public static function displayHeaderFiles($echo = true)
    {
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
                                dol_syslog('Echec création du fichier "' . DOL_DOCUMENT_ROOT . '/' . $out_file . '" - Vérifier les droits', E_ERROR);
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

        dol_syslog('FICHIER ABSENT: "' . DOL_DOCUMENT_ROOT . '/' . $file_path . '"', E_ERROR);
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

    public static function getConf($name)
    {
        $cache = self::getConfCache();

        if (isset(self::$conf_cache_not_exist[$name]))
            return null;

        if (!isset($cache[$name])) {
            $value = BimpCache::getBdb()->getValue('bimpcore_conf', 'value', '`name` = \'' . $name . '\'');
            if (!is_null($value)) {
                self::$conf_cache[$name] = $value;
                return $value;
            } else
                self::$conf_cache_not_exist[$name] = $name;

            return null;
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
            if (!file_exists(DOL_DATA_ROOT . '/bimpcore/config.yml')) {
                copy(DOL_DOCUMENT_ROOT . '/bimpcore/default_config.yml', DOL_DATA_ROOT . '/bimpcore/config.yml');
            }
            if (file_exists(DOL_DATA_ROOT . '/bimpcore/config.yml')) {
                self::$config = new BimpConfig(DOL_DATA_ROOT . '/bimpcore/', 'config.yml', new BimpObject('', ''));
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
}
