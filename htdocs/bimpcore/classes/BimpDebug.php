<?php

class BimpDebug
{

    public static $config = null;
    public static $debugs = array();
    public static $time_begin = 0;
    public static $types = array(
        'times'      => 'Timers',
        'list_sql'   => 'SQL listes',
        'sql'        => 'Requêtes SQL',
        'bimpdb_sql' => 'BIMP DB SQL',
    );

    public static function init()
    {
        self::$time_begin = microtime();
    }

    protected static function getConfig()
    {
        if (is_null(self::$config)) {
            self::$config = new BimpConfig(DOL_DOCUMENT_ROOT . '/bimpcore/', 'debug.yml', new BimpObject('', ''));
        }

        return self::$config;
    }

    public static function getTime()
    {
        return microtime() - self::$time_begin;
    }

    public static function checkUser()
    {
        global $user;
        if (!BimpObject::objectLoaded($user)) {
            return 0;
        }

        if ((int) $user->id === 1) {// || in_array((int) $user->id, explode(',', BimpCore::getConf('bimp_debug_users', '')))) {
            return 1;
        }

        return 0;
    }

    public static function isActive($full_path)
    {
        if (!self::checkUser()) {
            return 0;
        }

        if (self::getConfig()->get('debug', 0, false, 'bool') || BimpTools::getValue('bimp_debug_params/debug', 0)) {
            return ((int) self::getConfig()->get($full_path, 0, false, 'bool') || BimpTools::getValue('bimp_debug_params/' . $full_path, 0));
        }
    }

    public static function getParam($full_path, $default_value = '', $type = 'string')
    {
        return (int) self::getConfig()->get($full_path, $default_value, false, $type);
    }

    public static function addDebug($type, $title, $content, $params = array())
    {
        $params = BimpTools::overrideArray(array(
                    'foldable' => true,
                    'open'     => false
                        ), $params);

        if (!isset(self::$debugs[$type])) {
            self::$debugs[$type] = array();
        }

        self::$debugs[$type][] = array(
            'title'   => $title,
            'content' => $content,
            'time'    => self::getTime(),
            'params'  => $params
        );
    }

    public static function renderDebug($identifier = 'main_debug')
    {
        $html = '';

        if (!empty(self::$debugs)) {
            $tabs = array();
            foreach (self::$types as $type => $type_label) {
                if (!isset(self::$debugs[$type]) || empty(self::$debugs[$type])) {
                    continue;
                }
                $content = '';

                foreach (self::$debugs[$type] as $item) {
                    if ($item['params']['foldable']) {
                        $title = ($item['time'] / 1000) . ' - ' . $item['title'];
                        $content .= BimpRender::renderFoldableContainer($title, $item['content'], array(
                                    'open'        => $item['params']['open'],
                                    'offset_left' => false
                        ));
                    } else {
                        if ($content) {
                            $content .= '<br/>';
                        }

                        if ($title) {
                            $content .= '<h4>' . $title . '</h4>';
                        }

                        $content .= $item['content'];
                    }
                }

                if ($content) {
                    $tabs[] = array(
                        'id'      => $identifier . '_tab_' . $type,
                        'title'   => $type_label,
                        'content' => $content
                    );
                }
            }
            if (!empty($tabs)) {
                $html .= BimpRender::renderNavTabs($tabs, $identifier . '_tabs');
            }
        }

        if (!$html) {
            $html .= BimpRender::renderAlerts('Aucune info de débug', 'warning');
        }

        return $html;
    }
}
