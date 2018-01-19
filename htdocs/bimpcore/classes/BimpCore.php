<?php

class BimpCore
{

    public static $files = array(
        'js'  => array(
            '/includes/jquery/plugins/jpicker/jpicker-1.1.6.js',
            '/bimpcore/views/js/moment.min.js',
            '/bimpcore/views/js/bootstrap.min.js',
            '/bimpcore/views/js/bootstrap-datetimepicker.js',
            '/bimpcore/views/js/functions.js',
            '/bimpcore/views/js/ajax.js',
            '/bimpcore/views/js/list.js',
            '/bimpcore/views/js/form.js',
            '/bimpcore/views/js/view.js',
            '/bimpcore/views/js/viewsList.js'
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
            foreach (self::$files['js'] as $js_file) {
                echo '<script type="text/javascript" src="' . DOL_URL_ROOT . '/' . $js_file . '"></script>';
            }
            self::$filesInit = true;
        }
    }
}
