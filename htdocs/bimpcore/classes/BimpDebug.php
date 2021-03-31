<?php

class BimpDebug
{

    public static $active = true;
    protected static $user_checked = null;
    public static $maxDebugPerType = 1000;
    public static $debugs = array();
    public static $time_begin = 0;
    public static $curMem = 0;
    public static $types = array(
        'times'       => 'Timers',
        'cache'       => 'Cache',
        'collections' => 'Collections',
        'memory'      => 'Mémoire',
        'list_sql'    => 'SQL listes',
        'sql'         => 'Requêtes SQL',
        'sql_count'   => 'Compteur SQL',
        'php'         => 'Erreurs PHP',
        'params'      => 'Paramètres requête',
        'ajax_result' => 'Réponse ajax'
    );
    public static $times = array();
    public static $sql_count = array();
    public static $sql_reqs = array();
    public static $cur_sql_transaction = 0;
    public static $cache_infos = array(
        'objects'     => array(),
        'server'      => array(),
        'counts'      => array(
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
            ),
            'yml'         => array(
                'l' => 'Paramètres YML',
                'n' => 0,
                's' => 0
            )
        ),
        'collections' => array()
    );
    public static $collections_infos = array();

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
        if (is_null(self::$user_checked)) {
            global $user;
            if (BimpObject::objectLoaded($user)) {
                if ($user->admin) {// || in_array((int) $user->id, explode(',', BimpCore::getConf('bimp_debug_users', '')))) {
                    self::$user_checked = 1;
                } else {
                    self::$user_checked = 0;
                }
            } else {
                return 0;
            }
        }

        return self::$user_checked;
    }

    public static function isActive()
    {
        if (!self::$active) {
            return 0;
        }

        if (!self::checkUser()) {
            return 0;
        }

        return 1;
    }

    public static function addDebug($type, $title, $content, $params = array())
    {
        if (!self::isActive()) {
            return;
        }

        $params = BimpTools::overrideArray(array(
                    'foldable' => true,
                    'open'     => false
                        ), $params);

        if (!isset(self::$debugs[$type])) {
            self::$debugs[$type] = array();
        } elseif (count(self::$debugs[$type]) > self::$maxDebugPerType) {
            return;
        }

        self::$debugs[$type][] = array(
            'title'   => $title,
            'content' => $content,
            'time'    => self::getTime(),
            'params'  => $params
        );

        if (count(self::$debugs[$type]) == self::$maxDebugPerType) {
            self::$debugs[$type][] = array(
                'title'   => '',
                'content' => BimpRender::renderAlerts('Trop d\'entrées - arrêt des ajouts'),
                'time'    => self::getTime(),
                'params'  => array(
                    'foldable' => false
                )
            );
        }
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
        if (!self::isActive()) {
            return '';
        }

        $html = '';

        // Ajout des timers: 
        $content = self::renderDebugTimes();

        if ($content) {
            self::addDebug('times', '', $content, array(
                'foldable' => false
            ));
        }

        // Ajout des infos du cache: 
        $content = self::renderCacheInfosDebug();

        if ($content) {
            self::addDebug('cache', 'Utilisation du cache', $content, array('foldable' => 0));
        }

        // Ajout des infos collections: 

        $content = self::renderCollectionsDebug();
        if ($content) {
            self::addDebug('collections', '', $content, array('foldable' => 0));
        }

        // Ajout des requêtes SQL: 
        $content = self::renderSqlDebug();

        if ($content) {
            self::addDebug('sql_count', '', $content, array('foldable' => 0));
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

        if (self::isActive()) {
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
        $mem = memory_get_usage();
        self::$times[] = array(
            'l' => $label,
            't' => round(microtime(1), 4),
            'm' => $mem,
            'd' => $mem - self::$curMem
        );
        self::$curMem = $mem;
    }

    public static function renderDebugTimes()
    {
        if (!self::isActive()) {
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
        if (!self::isActive()) {
            return '';
        }

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
                    'type' => 'secondary',
                    'open' => false
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

        if (is_object(BimpCache::$cache_server)) {
            $html .= '<div class="row">';
            $html .= '<div class="col-sm-12 col-md-6">';

            $title = 'Détails cache serveur - Objet "' . get_class(BimpCache::$cache_server) . '"';

            $content = '';

            $content .= '<table class="bimp_list_table">';
            $content .= '<thead>';
            $content .= '<tr>';
            $content .= '<th>Clé</th>';
            $content .= '<th>Nb accès</th>';
            $content .= '</tr>';
            $content .= '</thead>';

            $content .= '<tbody>';

            if (empty(self::$cache_infos['server'])) {
                $content .= '<tr>';
                $content .= '<td colspan="2" style="text-align: center">' . BimpRender::renderAlerts('Aucun accès au cache serveur', 'info') . '</td>';
                $content .= '</tr>';
            } else {
                foreach (self::$cache_infos['server'] as $key => $nb) {
                    $content .= '<tr>';
                    $content .= '<td>' . $key . '</td>';
                    $content .= '<td>' . $nb . '</td>';
                    $content .= '</tr>';
                }
            }

            $content .= '</tbody>';
            $content .= '</table>';

            $html .= BimpRender::renderPanel($title, $content, '', array(
                        'type' => 'secondary',
                        'open' => false
            ));

            $html .= '</div>';
            $html .= '</div>';
        }


        return $html;
    }

    public static function incCacheServerKeyCount($key)
    {
        if (!isset(self::$cache_infos['server'][$key])) {
            self::$cache_infos['server'][$key] = 0;
        }

        self::$cache_infos['server'][$key] ++;
    }

    // Collections infos: 

    public static function incCollectionInfo($object_name, $type, $number, $ok = true)
    {
        if (!isset(self::$collections_infos[$object_name])) {
            self::$collections_infos[$object_name] = array(
                'items' => 0,
                'reqs'  => array(
                    'ok' => 0,
                    'ko' => 0
                ),
                'name'  => array(
                    'ok' => 0,
                    'ko' => 0
                ),
                'ref'   => array(
                    'ok' => 0,
                    'ko' => 0
                ),
                'link'  => array(
                    'ok' => 0,
                    'ko' => 0
                ),
                'card'  => array(
                    'ok' => 0,
                    'ko' => 0
                )
            );
        }

        if (isset(self::$collections_infos[$object_name][$type])) {
            if ($type === 'items') {
                self::$collections_infos[$object_name][$type] += $number;
            } elseif ($ok) {
                self::$collections_infos[$object_name][$type]['ok'] += $number;
            } else {
                self::$collections_infos[$object_name][$type]['ko'] += $number;
            }
        }
    }

    public static function renderCollectionsDebug()
    {
        $html = '';

        $html .= '<div class="row">';
        $html .= '<div class="col-sm-12 col-md-12 col-lg-12">';

        $content = '';
        $content .= '<table class="bimp_list_table">';
        $content .= '<thead>';
        $content .= '<tr>';
        $content .= '<th>Objet</th>';
        $content .= '<th style="text-align: center">Nb items</th>';
        $content .= '<th style="text-align: center">Nb requêtes</th>';
        $content .= '<th style="text-align: center">Noms</th>';
        $content .= '<th style="text-align: center">Refs</th>';
        $content .= '<th style="text-align: center">Liens</th>';
        $content .= '<th style="text-align: center">Cards</th>';
        $content .= '</tr>';
        $content .= '</thead>';

        $content .= '<tbody class="headers_col">';

        foreach (self::$collections_infos as $object_name => $data) {
            $content .= '<tr>';
            $content .= '<th>' . $object_name . '</th>';
            $content .= '<td style="text-align: center">' . $data['items'] . '</td>';

            $content .= '<td style="text-align: center">';
            if ((int) $data['reqs']['ok'] > 0) {
                $content .= '<span class="badge badge-success">' . (int) $data['reqs']['ok'] . '</span>&nbsp;';
            }
            if ((int) $data['reqs']['ko'] > 0) {
                $content .= '<span class="badge badge-danger">' . (int) $data['reqs']['ko'] . '</span>';
            }
            $content .= '</td>';

            $content .= '<td style="text-align: center">';
            if ((int) $data['name']['ok'] > 0) {
                $content .= '<span class="badge badge-success">' . (int) $data['name']['ok'] . '</span>&nbsp;';
            }
            if ((int) $data['name']['ko'] > 0) {
                $content .= '<span class="badge badge-danger">' . (int) $data['name']['ko'] . '</span>';
            }
            $content .= '</td>';

            $content .= '<td style="text-align: center">';
            if ((int) $data['ref']['ok'] > 0) {
                $content .= '<span class="badge badge-success">' . (int) $data['ref']['ok'] . '</span>&nbsp;';
            }
            if ((int) $data['ref']['ko'] > 0) {
                $content .= '<span class="badge badge-danger">' . (int) $data['ref']['ko'] . '</span>';
            }
            $content .= '</td>';

            $content .= '<td style="text-align: center">';
            if ((int) $data['link']['ok'] > 0) {
                $content .= '<span class="badge badge-success">' . (int) $data['link']['ok'] . '</span>&nbsp;';
            }
            if ((int) $data['link']['ko'] > 0) {
                $content .= '<span class="badge badge-danger">' . (int) $data['link']['ko'] . '</span>';
            }
            $content .= '</td>';

            $content .= '<td style="text-align: center">';
            if ((int) $data['card']['ok'] > 0) {
                $content .= '<span class="badge badge-success">' . (int) $data['card']['ok'] . '</span>&nbsp;';
            }
            if ((int) $data['card']['ko'] > 0) {
                $content .= '<span class="badge badge-danger">' . (int) $data['card']['ko'] . '</span>';
            }
            $content .= '</td>';

            $content .= '</tr>';
        }

        $content .= '</tbody>';
        $content .= '</table>';

        $html .= BimpRender::renderPanel('Détail utilisation des collections', $content, '', array(
                    'type' => 'secondary'
        ));

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    // SQL: 

    public static function addSqlDebug($sql)
    {
        $time = self::getTime();

        foreach (self::$sql_count as $idx => $data) {
            if ($data['sql'] === $sql) {
                self::$sql_count[$idx]['times'][] = $time;
                return;
            }
        }

        self::$sql_count[] = array(
            'sql'   => $sql,
            'times' => array(
                $time
            )
        );
    }

    public static function renderSqlDebug()
    {
        if (!self::isActive()) {
            return '';
        }

        $html = '';

        $nSql = 0;
        $nReq = 0;
        $max = 0;

        $sql_sorted = array();
        foreach (self::$sql_count as $idx => $data) {
            $nSql++;
            $n = count($data['times']);

            if ($n > $max) {
                $max = $n;
            }

            $nReq += $n;
            $sql_sorted[$idx] = $n;
        }

        arsort($sql_sorted);

        $html .= '<strong>Nombre total de requêtes: </strong>' . $nReq . '<br/>';
        $html .= '<strong>Nombre de requêtes uniques: </strong>' . $nSql . '<br/><br/>';

        $content = '';
        $content .= '<table class="bimp_list_table">';
        $content .= '<thead>';
        $content .= '<tr>';
        $content .= '<th>Reqête</th>';
        $content .= '<th>Nombre</th>';
        $content .= '<th>Timers</th>';
        $content .= '</tr>';
        $content .= '</thead>';

        $content .= '<tbody class="headers_col">';

        foreach ($sql_sorted as $idx => $n) {
            if (isset(self::$sql_count[$idx])) {
                $data = self::$sql_count[$idx];

                $class = '';
                if ($n > 10) {
                    $class = 'important';
                } elseif ($n > 5) {
                    $class = 'danger';
                } elseif ($n > 1) {
                    $class = 'warning';
                } else {
                    $class = 'success';
                }

                $content .= '<tr>';
                $content .= '<td style="width: 600px; padding: 10px;">' . BimpRender::renderSql($data['sql']) . '</td>';
                $content .= '<td><span class="badge badge-' . $class . '">' . $n . '</span></td>';
                $content .= '<td>';

                foreach ($data['times'] as $time) {
                    $content .= ' - ' . $time . '<br/>';
                }

                $content .= '</td>';
                $content .= '</tr>';
            }
        }

        $content .= '</tbody>';
        $content .= '</table>';

        $html .= BimpRender::renderPanel('Compteurs généraux', $content, '', array(
                    'type' => 'secondary'
        ));

        return $html;
    }
}
