<?php

class BimpCore
{

    public static $conf_cache = array();
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
            '/bimpcore/views/js/form.js',
            '/bimpcore/views/js/list.js',
            '/bimpcore/views/js/view.js',
            '/bimpcore/views/js/viewsList.js',
            '/bimpcore/views/js/page.js'
        ),
        'css' => array(
            '/includes/jquery/plugins/jpicker/css/jPicker-1.1.6.css',
            '/bimpcore/views/css/bimpcore_bootstrap.css'
        )
    );
    public static $filesInit = false;

    public static function displayHeaderFiles()
    {
        if (!self::$filesInit) {
            foreach (self::$files['css'] as $css_file) {
                echo '<link type="text/css" rel="stylesheet" href="' . DOL_URL_ROOT . '/' . $css_file . '"/>';
            }

            global $user;

            echo '<script type="text/javascript">';
            echo ' var dol_url_root = \'' . DOL_URL_ROOT . '\';';
            echo ' var id_user = ' . (BimpObject::objectLoaded($user) ? $user->id : 0) . ';';
            echo '</script>';

            foreach (self::$files['js'] as $js_file) {
                echo '<script type="text/javascript" src="' . DOL_URL_ROOT . '/' . $js_file . '"></script>';
            }

            self::$filesInit = true;
        }
    }

    public static function getConf($name)
    {
//        if ($name === 'bimpcore_version') {
//            return self::getVersion();
//        }

        if (!isset(self::$conf_cache[$name])) {
            global $db;
            $bdb = new BimpDb($db);
            self::$conf_cache[$name] = $bdb->getValue('bimpcore_conf', 'value', '`name` = \'' . $name . '\'');
        }

        return self::$conf_cache[$name];
    }

    public static function getVersion($dev = '')
    {
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
}
