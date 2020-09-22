<?php

class BimpDebug
{

    public static $active = true;
    public static $config = null;
    public static $debugs = array();
    public static $time_begin = 0;
    public static $curMem = 0;
    public static $types = array(
        'times'       => 'Timers',
        'cache'       => 'Cache',
        'memory'      => 'Mémoire',
        'list_sql'    => 'SQL listes',
        'sql'         => 'Requêtes SQL',
        'bimpdb_sql'  => 'BIMP DB SQL',
        'php'         => 'Erreurs PHP',
        'params'      => 'Paramètres requête',
        'ajax_result' => 'Réponse ajax'
    );
    public static $times = array();
    public static $cache_infos = array(
        'objects' => array(),
        'counts'  => array(
            'objects'     => array(
                'l' => 'Objets Bimp', // Label
                'n' => 0, // New
                's' => 0 // Skipped
            ),
            'dol_objects' => array(
                'l' => 'Objets Dolibarr',
                'n' => 0,
                's' => 0
            ),
            'logs'        => array(
                'l' => 'Logs',
                'n' => 0,
                's' => 0
            )
        )
    );

    protected static function getConfig()
    {
        if (is_null(self::$config)) {
            self::$config = new BimpConfig(DOL_DOCUMENT_ROOT . '/bimpcore/', 'debug.yml', new BimpObject('', ''));
        }

        return self::$config;
    }

    public static function getTime()
    {
        global $bimp_start_time;

        if (is_null($bimp_start_time)) {
            $bimp_start_time = round((float) microtime(1), 4);
        }

        return round((round((float) microtime(1), 4) - $bimp_start_time), 4);
    }

    public static function checkUser()
    {
        global $user;
        if (!BimpObject::objectLoaded($user)) {
            return 0;
        }

        if ((int) $user->admin === 1) {// || in_array((int) $user->id, explode(',', BimpCore::getConf('bimp_debug_users', '')))) {
            return 1;
        }

        return 0;
    }

    public static function isActive($full_path)
    {
        if (!self::$active) {
            return 0;
        }

        if (!self::checkUser()) {
            return 0;
        }

        if (in_array($full_path, array('debug', 'use_debug_modal'))) {
            return ((int) self::getConfig()->get($full_path, 0, false, 'bool') || BimpTools::getValue('bimp_debug_params/' . $full_path, 0));
        }

        if (strpos($full_path, 'debug_modal') === 0) {
            if (self::getConfig()->get('use_debug_modal', 0, false, 'bool') || BimpTools::getValue('bimp_debug_params/use_debug_modal', 0)) {
                return ((int) self::getConfig()->get($full_path, 0, false, 'bool') || BimpTools::getValue('bimp_debug_params/' . $full_path, 0));
            }

            return 0;
        }

        if (self::getConfig()->get('debug', 0, false, 'bool') || BimpTools::getValue('bimp_debug_params/debug', 0)) {
            return ((int) self::getConfig()->get($full_path, 0, false, 'bool') || BimpTools::getValue('bimp_debug_params/' . $full_path, 0));
        }

        return 0;
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

    public static function addParamsDebug()
    {
        if (!empty($_GET)) {
            self::addDebug('params', 'Paramètres GET', '<pre>' . print_r($_GET, 1) . '</pre>', array(
                'foldable' => true,
                'open'     => true
            ));
        }

        if (!empty($_POST)) {
            self::addDebug('params', 'Paramètres POST', '<pre>' . print_r($_POST, 1) . '</pre>', array(
                'foldable' => true,
                'open'     => true
            ));
        }
    }

    public static function renderDebug($identifier = 'main_debug')
    {
        $html = '';

        // Ajout des timers: 
        if (self::isActive('debug_modal/times')) {
            $content = self::renderDebugTimes();

            if ($content) {
                self::addDebug('times', '', $content, array(
                    'foldable' => false
                ));
            }
        }

        // Ajout des infos du cache: 
        if (self::isActive('debug_modal/cache')) {
            $content = self::renderCacheInfosDebug();

            if ($content) {
                self::addDebug('cache', 'Utilisation du cache', $content, array('foldable' => 0));
            }
        }

        // Rendu HTML des infos de débug: 
        if (!empty(self::$debugs)) {
            $tabs = array();
            foreach (self::$types as $type => $type_label) {
                if (!isset(self::$debugs[$type]) || empty(self::$debugs[$type])) {
                    continue;
                }
                $content = '';

                foreach (self::$debugs[$type] as $item) {
                    if ($item['params']['foldable']) {
                        $title = $item['time'] . ' s - ' . $item['title'];
                        $content .= BimpRender::renderFoldableContainer($title, $item['content'], array(
                                    'open'        => $item['params']['open'],
                                    'offset_left' => false
                        ));
                    } else {
                        if ($content) {
                            $content .= '<br/>';
                        }

                        if ($item['title']) {
                            $content .= '<h4>' . $item['title'] . '</h4>';
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

    public static function freeAll($deactivate = true)
    {
        self::$times = array();
        self::$debugs = array();
        self::$cache_infos = array(
            'objects' => array(),
            'counts'  => array(
                'objects'     => array(
                    'l' => 'Objets Bimp',
                    'n' => 0,
                    's' => 0
                ),
                'dol_objects' => array(
                    'l' => 'Objets Dolibarr',
                    'n' => 0,
                    's' => 0
                ),
                'logs'        => array(
                    'l' => 'Logs',
                    'n' => 0,
                    's' => 0
                )
            )
        );

        if (self::isActive('debug_modal/times')) {
            self::addDebugTime('Urgence - Suppression de toutes les infos debug');
        }

        if ($deactivate) {
            self::$active = false;
        }
    }

    public static function freeByTypes($types)
    {
        if (is_array($types)) {
            foreach ($types as $type) {
                if (isset(self::$debugs[$type])) {
                    self::$debugs[$type] = array();
                }
            }
        }
    }

    // Times: 

    public static function addDebugTime($label)
    {
        if (self::isActive('debug_modal/times')) {
            $mem = memory_get_usage();
            self::$times[] = array(
                'l' => $label,
                't' => round(microtime(1), 4),
                'm' => $mem,
                'd' => $mem - self::$curMem
            );
            self::$curMem = $mem;
        }
    }

    public static function renderDebugTimes()
    {
        if (!self::isActive('debug_modal/times')) {
            return '';
        }

        if (empty(self::$times)) {
            return '';
        }

        $html = '';

        global $bimp_start_time;

        $html .= '<div id="bimpControllerDebugTimeInfos">';

        $html .= '<h3>Debug timers</h3>';

        if (!(float) $bimp_start_time) {
            $html .= BimpRender::renderAlerts('Variable bimp_start_time absente du fichier index.php');
        } else {
            $html .= '<table class="bimp_list_table">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Intitulé</th>';
            $html .= '<th>Timer</th>';
            $html .= '<th>Etat de la mémoire</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            $prev_time = $bimp_start_time;
            $prev_mem = 0;

            foreach (self::$times as $time) {
                $memDiff = $time['m'] - $prev_mem;

                $html .= '<tr>';
                $html .= '<td>' . $time['l'] . '</td>';
                $html .= '<td>' . round((float) ($time['t'] - $bimp_start_time), 4) . ' s';
                $html .= ' (+ ' . round((float) ($time['t'] - $prev_time), 4) . ' s)</td>';
                $html .= '<td>' . BimpTools::displayFloatValue($time['m'] / 1000000, 6) . ' Mo (';
                if ($memDiff > 0) {
                    $html .= '<span class="danger">+' . BimpTools::displayFloatValue($memDiff / 1000, 6) . ' Ko</span>';
                } else {
                    $html .= '<span class="success">-' . BimpTools::displayFloatValue(abs($memDiff) / 1000, 6) . ' Ko</span>';
                }
                $html .= ')</td>';
                $html .= '</tr>';

                $prev_time = $time['t'];
                $prev_mem = $time['m'];
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        $html .= '</div>';

        return $html;
    }

    // Cache infos: 

    public static function addCacheObjectInfos($module, $object_name, $is_fetched = true, $obj_type = 'bimp_object')
    {
        if (!$module || !$object_name) {
            return;
        }

        if (!isset(self::$cache_infos['objects'][$module])) {
            self::$cache_infos['objects'][$module] = array();
        }

        if (!isset(self::$cache_infos['objects'][$module][$object_name])) {
            self::$cache_infos['objects'][$module][$object_name] = array(
                'f' => 0, // Fetched
                's' => 0 // Skipped
            );
        }

        if ($is_fetched) {
            self::$cache_infos['objects'][$module][$object_name]['f'] ++;
        } else {
            self::$cache_infos['objects'][$module][$object_name]['s'] ++;
        }

        if ($obj_type === 'bimp_object') {
            self::incCacheInfosCount('objects', $is_fetched);
        } elseif ($obj_type === 'dol_object') {
            self::incCacheInfosCount('dol_objects', $is_fetched);
        }
    }

    public static function incCacheInfosCount($type, $is_new = true)
    {
        if (isset(self::$cache_infos['counts'][$type])) {
            if ($is_new) {
                self::$cache_infos['counts'][$type]['n'] ++;
            } else {
                self::$cache_infos['counts'][$type]['s'] ++;
            }
        }
    }

    public static function renderCacheInfosDebug()
    {
        $html = '';

        $html .= '<strong>Nombre total d\'éléments en cache: </strong>' . count(BimpCache::$cache) . '<br/><br/>';

        $html .= '<div class="row">';
        $html .= '<div class="col-sm-12 col-md-6">';

        $content = '';
        $content .= '<table class="bimp_list_table">';
        $content .= '<thead>';
        $content .= '<tr>';
        $content .= '<th>Type</th>';
        $content .= '<th>Ajoutés</th>';
        $content .= '<th>Récupérés</th>';
        $content .= '</tr>';
        $content .= '</thead>';

        $content .= '<tbody class="headers_col">';

        foreach (self::$cache_infos['counts'] as $type => $data) {
            $content .= '<tr>';
            $content .= '<th>' . $data['l'] . '</th>';
            $content .= '<td>' . $data['n'] . '</td>';
            $content .= '<td>' . $data['s'] . '</td>';
            $content .= '</tr>';
        }

        $content .= '</tbody>';
        $content .= '</table>';

        $html .= BimpRender::renderPanel('Compteurs généraux', $content, '', array(
                    'type' => 'secondary'
        ));

        $html .= '</div>';

        $html .= '<div class="col-sm-12 col-md-6">';

        $content = '';
        $content .= '<table class="bimp_list_table">';
        $content .= '<thead>';
        $content .= '<tr>';
        $content .= '<th>Module</th>';
        $content .= '<th>Objet</th>';
        $content .= '<th>Instanciés</th>';
        $content .= '<th>Récupérés</th>';
        $content .= '</tr>';
        $content .= '</thead>';

        $content .= '<tbody class="headers_col">';

        foreach (self::$cache_infos['objects'] as $module => $objects) {
            foreach ($objects as $obj_name => $data) {
                $content .= '<tr>';
                $content .= '<td>' . $module . '</td>';
                $content .= '<td>' . $obj_name . '</td>';
                $content .= '<td>' . $data['f'] . '</td>';
                $content .= '<td>' . $data['s'] . '</td>';
                $content .= '</tr>';
            }
        }

        $content .= '</tbody>';
        $content .= '</table>';

        $html .= BimpRender::renderPanel('Détails par type d\'objet', $content, '', array(
                    'type' => 'secondary'
        ));

        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="row">';
        $html .= '<div class="col-sm-12 col-md-6">';

        // Liste des clés de cache:

        $content = '';
        $keys = array();
        foreach (BimpCache::$cache as $key => $item) {
            $keys[] = $key;
        }
        sort($keys);

        foreach ($keys as $key) {
            $content .= ' - ' . $key . '<br/>';
        }

        $html .= BimpRender::renderPanel('Liste des clés de cache', $content, '', array(
                    'type' => 'secondary'
        ));

        $html .= '</div>';

        $content = '';

        $html .= '<div class="col-sm-12 col-md-6">';
        if (!empty(BimpCache::$objects_keys)) {
            $content .= '<table class="bimp_list_table">';
            $content .= '<thead>';
            $content .= '<tr>';
            $content .= '<th>Clé objet</th>';
            $content .= '<th>Nb utilisations</th>';
            $content .= '<th>Mémoire objet</th>';
            $content .= '</tr>';
            $content .= '</thead>';

            $content .= '<tbody>';

            foreach (BimpCache::$objects_keys as $idx => $data) {
                $content .= '<tr>';
                $content .= '<td>' . $data['k'] . '</td>';
                $content .= '<td>' . $data['n'] . '</td>';
                $content .= '<td>' . BimpTools::displayFloatValue($data['m'] / 1000, 3) . ' Ko</td>';
                $content .= '</tr>';
            }

            $content .= '</tbody>';
            $content .= '</table>';



            $html .= BimpRender::renderPanel('Détail par clé objet', $content, '', array(
                        'type' => 'secondary',
                        'open' => false
            ));
        }

        if (!empty(BimpCache::$objects_keys_removed)) {
            $content .= '<table class="bimp_list_table">';
            $content .= '<thead>';
            $content .= '<tr>';
            $content .= '<th>Clé objet</th>';
            $content .= '<th>Nb itérations</th>';
            $content .= '<th>Mémoire objet</th>';
            $content .= '<th>Timer</th>';
            $content .= '</tr>';
            $content .= '</thead>';

            $content .= '<tbody>';

            foreach (BimpCache::$objects_keys_removed as $idx => $data) {
                $content .= '<tr>';
                $content .= '<td>' . $data['k'] . '</td>';
                $content .= '<td>' . $data['n'] . '</td>';
                $content .= '<td>' . BimpTools::displayFloatValue($data['m'] / 1000, 3) . ' Ko</td>';
                $content .= '<td>' . $data['t'] . '</td>';
                $content .= '</tr>';
            }

            $content .= '</tbody>';
            $content .= '</table>';

            $html .= BimpRender::renderPanel('Objets retirés du cache', $content, '', array(
                        'type' => 'secondary',
                        'open' => false
            ));
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
