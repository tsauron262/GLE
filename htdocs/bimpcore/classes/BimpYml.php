<?php

require_once __DIR__ . '/BimpYmlGenerator.php';

class BimpYml
{

    public static $hues = array(200, 280, 0, 30, 90, 150, 175);
    public static $configs = array(
        'objects'     => array(
            'sections'        => array(
                'root'             => 'Paramètres objet',
                'objects'          => 'Objets',
                'associations'     => 'Associations',
                'fields'           => 'Champs',
                'forms'            => 'Formulaires',
                'fields_tables'    => 'Tableaux de champ',
                'views'            => 'Vues',
                'cards'            => 'Mini-fiches',
                'filters'          => 'Filtres',
                'filters_panels'   => 'Panneaux de filtres',
                'lists_cols'       => 'Colonnes de liste',
                'lists'            => 'Listes',
                'stats_lists_cols' => 'Colonnes de tableaux statistiques',
                'stats_lists'      => 'Tableaux statistiques',
                'views_lists'      => 'Listes de vues',
                'searches'         => 'Recherches'
            ),
            'default_section' => 'fields'
        ),
        'controllers' => array(
            'sections'        => array(
                'root'     => 'Paramètres Controller',
                'js'       => 'Fichiers JS',
                'css'      => 'Fichiers CSS',
                'objects'  => 'Objets',
                'sections' => 'Sections'
            ),
            'default_section' => 'fields'
        ),
        'configs'     => array(
            'sections'        => array(
                'categories' => 'Catégories',
                'params'     => 'Paramètres'
            ),
            'default_section' => 'params'
        )
    );
    public static $types = array(
        'objects'     => 'Objets',
        'controllers' => 'Controllers',
        'configs'     => 'Config modules'
    );

    public static function getYmlFilesArray($type = 'all', $module = 'all', $include_empty = true)
    {
        $cache_key = 'bimp_yml_' . $type . '_' . $module . '_files_array';

        if (!isset(BimpCache::$cache[$cache_key])) {
            $files = array();

            if ($module === 'all') {
                $modules = BimpCache::getBimpModulesArray();
            } else {
                $modules = array($module => $module);
            }

            foreach ($modules as $m) {
                if (!is_dir(DOL_DOCUMENT_ROOT . '/' . $m)) {
                    continue;
                }

                if ($type === 'all' || $type === 'objects') {
                    if (is_dir(DOL_DOCUMENT_ROOT . '/' . $m . '/objects'))
                        foreach (scandir(DOL_DOCUMENT_ROOT . '/' . $m . '/objects') as $f) {
                            if (in_array($f, array('.', '..'))) {
                                continue;
                            }

                            if (preg_match('/^(.+)\.yml$/', $f, $matches)) {
                                $value = htmlentities(json_encode(array(
                                    'type'   => 'object',
                                    'module' => $m,
                                    'name'   => $matches[1]
                                )));

                                $files[$value] = $m . ' - Objet: [bold]' . $matches[1] . '[/bold]';
                            }
                        }
                }

                if ($type === 'all' || $type === 'controllers') {
                    if (is_dir(DOL_DOCUMENT_ROOT . '/' . $m . '/controllers'))
                        foreach (scandir(DOL_DOCUMENT_ROOT . '/' . $m . '/controllers') as $f) {
                            if (in_array($f, array('.', '..'))) {
                                continue;
                            }

                            if (preg_match('/^(.+)\.yml$/', $f, $matches)) {
                                $value = htmlentities(json_encode(array(
                                    'type'   => 'controller',
                                    'module' => $m,
                                    'name'   => $matches[1]
                                )));

                                $files[$value] = $m . ' - Controller: [bold]' . $matches[1] . '[/bold]';
                            }
                        }
                }

                if ($type === 'all' || $type === 'configs') {
                    if (file_exists(DOL_DOCUMENT_ROOT . '/' . $m . '/' . $m . '.yml')) {
                        $value = htmlentities(json_encode(array(
                            'type'   => 'config',
                            'module' => $m,
                            'name'   => $m
                        )));

                        $files[$value] = 'Config module: [bold]' . $m . '[/bold]';
                    }
                }
            }

            BimpCache::$cache[$cache_key] = $files;
        }

        return BimpCache::getCacheArray($cache_key, $include_empty, '', '');
    }

    public static function mergeFileParams($params, $file_params, $file_idx)
    {
        if (!is_array($params)) {
            $params = array();
        }

        foreach ($file_params as $param_name => $param_value) {
            if (!isset($params[$param_name])) {
                $params[$param_name] = array(
                    'file_idx'   => '',
                    'values'     => array(),
                    'sub_params' => array()
                );
            }

            if ($param_value === 'unset') {
                $params[$param_name]['unset_by'] = $file_idx;
            } elseif (isset($param_value['unused']) && (int) $param_value['unused']) {
                $params[$param_name]['unused_by'] = $file_idx;
            } else {
                $params[$param_name]['file_idx'] = $file_idx;

                if (is_array($param_value)) {
                    $params[$param_name]['sub_params'] = self::mergeFileParams($params[$param_name]['sub_params'], $param_value, $file_idx);
                } else {
                    $params[$param_name]['values'][$file_idx] = (string) $param_value;
                }
            }
        }

        return $params;
    }

    public static function renderYmlFileAnalyser($type, $module, $name)
    {
        $html = '';

        switch ($type) {
            case 'object':
                $html .= self::renderObjectYmlFileAnalyser($module, $name);
                break;

            case 'controller':
                $html .= self::renderControllerYmlFileAnalyser($module, $name);
                break;

            case 'config':
                $html .= self::renderConfigModuleYmlFileAnalyser($module);
                break;

            default:
                $html .= BimpRender::renderAlerts('Type de fichier invalide (' . $type . ')');
                break;
        }

        return $html;
    }

    public static function renderObjectYmlFileAnalyser($module, $name)
    {
        $html = '';
        $html .= '<h3 style="font-weight: normal">Module "<b>' . $module . '</b>" - Objet "<b>' . $name . '</b>"</h3>';

        $tabs = array();
        $params = array();
        $errors = array();

        $cur_module = $module;
        $cur_name = $name;

        $i = 0;
        while ($i < 10) {// protection boucles infinies
            $i++;
            $idx = ($i < 10 ? '0' : '') . $i;

            $params[$idx] = array(
                'active'  => ($cur_name === 'BimpObject' ? 0 : 1),
                'module'  => $cur_module,
                'name'    => $cur_name,
                'base'    => array(),
                'version' => array(),
                'entity'  => array()
            );

            // Base: 
            $file = DOL_DOCUMENT_ROOT . '/' . $cur_module . '/objects/' . $cur_name . '.yml';
            if (file_exists($file)) {
                $params[$idx]['base'] = spyc_load_file($file);
            }

            // Version: 
            if (defined('BIMP_EXTENDS_VERSION')) {
                $file = DOL_DOCUMENT_ROOT . '/' . $cur_module . '/extends/versions/' . BIMP_EXTENDS_VERSION . '/objects/' . $cur_name . '.yml';
                if (file_exists($file)) {
                    $params[$idx]['version'] = spyc_load_file($file);
                }
            }

            // Entité
            if (defined('BIMP_EXTENDS_ENTITY')) {
                $file = DOL_DOCUMENT_ROOT . '/' . $cur_module . '/extends/entities/' . BIMP_EXTENDS_ENTITY . '/objects/' . $cur_name . '.yml';
                if (file_exists($file)) {
                    $params[$idx]['entity'] = spyc_load_file($file);
                }
            }

            if (empty($params[$idx]['base']) && empty($params[$idx]['version']) && empty($params[$idx]['entity'])) {
                $errors[] = 'Aucun fichier YML trouvé pour l\'objet "' . $cur_name . '" (Module: ' . $cur_module . ')';
                break;
            }

            if ($cur_name === 'BimpObject') {
                break;
            }

            $next_module = '';
            $next_name = '';
            $sub_errors = array();

            if (isset($params[$idx]['entity']['extends'])) {
                $next_module = BimpTools::getArrayValueFromPath($params[$idx], 'entity/extends/module', '');
                $next_name = BimpTools::getArrayValueFromPath($params[$idx], 'entity/extends/object_name', '');

                if (!$next_module) {
                    $sub_errors[] = 'Nom du module étendu absent dans le fichier entité';
                }
                if (!$next_module) {
                    $sub_errors[] = 'Nom de l\'objet étendu absent dans le fichier entité';
                }
            } elseif (isset($params[$idx]['version']['extends'])) {
                $next_module = BimpTools::getArrayValueFromPath($params[$idx], 'version/extends/module', '');
                $next_name = BimpTools::getArrayValueFromPath($params[$idx], 'version/extends/object_name', '');

                if (!$next_module) {
                    $sub_errors[] = 'Nom du module étendu absent dans le fichier version';
                }
                if (!$next_module) {
                    $sub_errors[] = 'Nom de l\'objet étendu absent dans le fichier version';
                }
            } elseif (isset($params[$idx]['base']['extends'])) {
                $next_module = BimpTools::getArrayValueFromPath($params[$idx], 'base/extends/module', '');
                $next_name = BimpTools::getArrayValueFromPath($params[$idx], 'base/extends/object_name', '');

                if (!$next_module) {
                    $sub_errors[] = 'Nom du module étendu absent dans le fichier de base';
                }
                if (!$next_module) {
                    $sub_errors[] = 'Nom de l\'objet étendu absent dans le fichier de base';
                }
            }

            if ($next_module && $next_name && !($cur_module === $next_module && $cur_name === $next_name)) {
                $cur_module = $next_module;
                $cur_name = $next_name;
                continue;
            }

            if (!empty($sub_errors)) {
                $errors[] = BimpTools::getMsgFromArray($sub_errors, $cur_module . '/' . $cur_name);
                break;
            }

            $cur_module = 'bimpcore';
            $cur_name = 'BimpObject';
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        $instance = BimpObject::getInstance($module, $name);
        $tabs[] = array(
            'id'      => 'analyser',
            'title'   => 'Analyseur YML',
            'content' => self::renderAnalyserContent($params, 'objects', $instance)
        );

        $instance = BimpObject::getInstance($module, $name);
        $full_params = $instance->config->getParams('');

        $tabs[] = array(
            'id'      => 'full_params_formates',
            'title'   => 'Tous les paramètres (formaté)',
            'content' => BimpRender::renderRecursiveArrayContent($full_params, array(
                'title'    => 'Tous les paramètres après fusion',
                'foldable' => 1,
                'open'     => 0
            )),
        );

        $tabs[] = array(
            'id'      => 'full_params_bruts',
            'title'   => 'Tous les paramètres (brut)',
            'content' => '<div><pre>' . print_r($full_params, 1) . '</pre></div>'
        );

        $generator_content = BimpYmlGenerator::renderObjectYmlGeneratorView($module, $name);

        if ($generator_content) {
            $tabs[] = array(
                'id'      => 'generator',
                'title'   => 'Générateur',
                'content' => $generator_content
            );
        }

        $html .= BimpRender::renderNavTabs($tabs, 'yml_analyser');

        return $html;
    }

    public static function renderControllerYmlFileAnalyser($module, $name)
    {
        $html = '';
        $html .= '<h3 style="font-weight: normal">Module "<b>' . $module . '</b>" - Controller "<b>' . $name . '</b>"</h3>';

        $tabs = array();
        $params = array();
        $errors = array();

        $cur_module = $module;
        $cur_name = $name;

        $i = 0;
        while (1) {
            $i++;
            $idx = ($i < 10 ? '0' : '') . $i;

            $params[$idx] = array(
                'module'  => $cur_module,
                'name'    => $cur_name,
                'base'    => array(),
                'version' => array(),
                'entity'  => array()
            );

            // Base: 
            $file = DOL_DOCUMENT_ROOT . '/' . $cur_module . '/controllers/' . $cur_name . '.yml';
            if (file_exists($file)) {
                $params[$idx]['base'] = spyc_load_file($file);
            }

            // Version: 
            if (defined('BIMP_EXTENDS_VERSION')) {
                $file = DOL_DOCUMENT_ROOT . '/' . $cur_module . '/extends/versions/' . BIMP_EXTENDS_VERSION . '/controllers/' . $cur_name . '.yml';
                if (file_exists($file)) {
                    $params[$idx]['version'] = spyc_load_file($file);
                }
            }

            // Entité
            if (defined('BIMP_EXTENDS_ENTITY')) {
                $file = DOL_DOCUMENT_ROOT . '/' . $cur_module . '/extends/entities/' . BIMP_EXTENDS_ENTITY . '/controllers/' . $cur_name . '.yml';
                if (file_exists($file)) {
                    $params[$idx]['entity'] = spyc_load_file($file);
                }
            }

            if (empty($params[$idx]['base']) && empty($params[$idx]['version']) && empty($params[$idx]['entity'])) {
                $errors[] = 'Aucun fichier YML trouvé pour le controller "' . $cur_name . '" (Module: ' . $cur_module . ')';
                break;
            }

            $next_module = '';
            $next_name = '';
            $sub_errors = array();

            if (isset($params[$idx]['entity']['extends'])) {
                $next_module = BimpTools::getArrayValueFromPath($params[$idx], 'entity/extends/module', '');
                $next_name = BimpTools::getArrayValueFromPath($params[$idx], 'entity/extends/object_name', '');

                if (!$next_module) {
                    $sub_errors[] = 'Nom du module étendu absent dans le fichier entité';
                }
                if (!$next_module) {
                    $sub_errors[] = 'Nom de l\'objet étendu absent dans le fichier entité';
                }
            } elseif (isset($params[$idx]['version']['extends'])) {
                $next_module = BimpTools::getArrayValueFromPath($params[$idx], 'version/extends/module', '');
                $next_name = BimpTools::getArrayValueFromPath($params[$idx], 'version/extends/object_name', '');

                if (!$next_module) {
                    $sub_errors[] = 'Nom du module étendu absent dans le fichier version';
                }
                if (!$next_module) {
                    $sub_errors[] = 'Nom de l\'objet étendu absent dans le fichier version';
                }
            } elseif (isset($params[$idx]['base']['extends'])) {
                $next_module = BimpTools::getArrayValueFromPath($params[$idx], 'base/extends/module', '');
                $next_name = BimpTools::getArrayValueFromPath($params[$idx], 'base/extends/object_name', '');

                if (!$next_module) {
                    $sub_errors[] = 'Nom du module étendu absent dans le fichier de base';
                }
                if (!$next_module) {
                    $sub_errors[] = 'Nom de l\'objet étendu absent dans le fichier de base';
                }
            }

            if ($next_module && $next_name) {
                $cur_module = $next_module;
                $cur_name = $next_name;
                continue;
            }

            if (!empty($sub_errors)) {
                $errors[] = BimpTools::getMsgFromArray($sub_errors, $cur_module . '/' . $cur_name);
            }

            break;
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        $tabs[] = array(
            'id'      => 'default',
            'title'   => 'Analyseur YML',
            'content' => self::renderAnalyserContent($params, 'controllers')
        );

        $controller = BimpController::getInstance($module, $name);
        $full_params = $controller->config->getParams('');

        $tabs[] = array(
            'id'      => 'full_params',
            'title'   => 'Tous les paramètres',
            'content' => '<pre>' . print_r($full_params, 1) . '</pre>'
//            'content' => BimpRender::renderRecursiveArrayContent($full_params, array(
//                'title'    => 'Tous les paramètres finaux',
//                'foldable' => 1,
//                'open'     => 1
//            ))
        );

        $html .= BimpRender::renderNavTabs($tabs, 'yml_analyser');

        return $html;
    }

    public static function renderConfigModuleYmlFileAnalyser($module)
    {
        $html = '';
        $html .= '<h3 style="font-weight: normal">Module "<b>' . $module . '</b>"</h3>';

        $params = array();
        $errors = array();

        $idx = '01';
        $params[$idx] = array(
            'module'  => $module,
            'name'    => $module,
            'base'    => array(),
            'version' => array(),
            'entity'  => array()
        );

        // Base: 
        $file = DOL_DOCUMENT_ROOT . '/' . $module . '/' . $module . '.yml';
        if (file_exists($file)) {
            $params[$idx]['base'] = spyc_load_file($file);
        }

        // Version: 
        if (defined('BIMP_EXTENDS_VERSION')) {
            $file = DOL_DOCUMENT_ROOT . '/' . $module . '/extends/versions/' . BIMP_EXTENDS_VERSION . '/' . $module . '.yml';
            if (file_exists($file)) {
                $params[$idx]['version'] = spyc_load_file($file);
            }
        }

        // Entité
        if (defined('BIMP_EXTENDS_ENTITY')) {
            $file = DOL_DOCUMENT_ROOT . '/' . $module . '/extends/entities/' . BIMP_EXTENDS_ENTITY . '/' . $module . '.yml';
            if (file_exists($file)) {
                $params[$idx]['entity'] = spyc_load_file($file);
            }
        }

        if (empty($params[$idx]['base']) && empty($params[$idx]['version']) && empty($params[$idx]['entity'])) {
            $errors[] = 'Aucun fichier YML trouvé pour le  fichier de configuration du module "' . $module . '"';
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        $html .= self::renderAnalyserContent($params, 'configs');

        return $html;
    }

    public static function renderAnalyserContent($params_files, $yml_type, $instance = null)
    {
        $html = '';

        if (!is_array($params_files) || empty($params_files)) {
            $html .= BimpRender::renderAlerts('Aucun paramètre trouvé', 'warning');
        } else {
            krsort($params_files);
            $max_idx = 0;
            $files = array();

            foreach ($params_files as $idx => $file_data) {
                if (!$max_idx) {
                    $max_idx = (int) $idx;
                }
                $file_idx = ((int) $idx < 10 ? '0' : '') . $idx;
                $files[$idx . '_base'] = $file_data['name'];
                if (isset($file_data['entity']) && !empty($file_data['entity'])) {
                    $files[$idx . '_entity'] = $file_data['name'] . ' (Entité)';
                }
                if (isset($file_data['version']) && !empty($file_data['version'])) {
                    $files[$idx . '_version'] = $file_data['name'] . ' (Version)';
                }

                $next_idx = '';

                if ((int) $idx > 1) {
                    $next_idx = (string) ((int) $idx < 10 ? '0' . (string) ((int) $idx - 1) : (int) $idx - 1);
                    if (!isset($params_files[$next_idx])) {
                        $next_idx = '';
                    }
                }
                $params_files[$idx]['next_idx'] = $next_idx;
            }

            $html .= self::renderYmlAnalyserColorsStyles($params_files);
            $html .= '<div class="yml_analyser_content">';

            // Arborescence des fichiers: 
            $html .= '<div class="yml_analyser_files_tree">';
            $html .= '<table>';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>Module</th>';

            foreach ($params_files as $idx => $file_data) {
                $html .= '<td>' . $file_data['module'] . '</td>';
                if ($file_data['next_idx']) {
                    $html .= '<td class="chevron"></td>';
                }
            }

            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';

            $entities_cells = '';
            $has_entities = false;
            foreach ($params_files as $idx => $file_data) {
                $entities_cells .= '<td>';
                if (isset($file_data['entity']) && !empty($file_data['entity'])) {
                    $has_entities = true;
                    $entities_cells .= '<span class="yml_file_label yml_entity_file_label idx_' . $idx . '_entity" data-file_idx="' . $idx . '" data-file_level="entity">';
                    $entities_cells .= $file_data['name'];
                    $entities_cells .= '</span>';
                }
                $entities_cells .= '</td>';

                if ($file_data['next_idx']) {
                    $entities_cells .= '<td class="chevron"></td>';
                }
            }

            $versions_cells = '';
            $has_versions = false;

            foreach ($params_files as $idx => $file_data) {
                $versions_cells .= '<td>';
                if (isset($file_data['version']) && !empty($file_data['version'])) {
                    $has_versions = true;
                    $versions_cells .= '<span class="yml_file_label yml_version_file_label idx_' . $idx . '_version" data-file_idx="' . $idx . '" data-file_level="version">';
                    $versions_cells .= $file_data['name'];
                    $versions_cells .= '</span>';
                } elseif ($has_entities && isset($file_data['entity']) && !empty($file_data['entity'])) {
                    $versions_cells .= BimpRender::renderIcon('fas_long-arrow-alt-down');
                }
                $versions_cells .= '</td>';

                if ($file_data['next_idx']) {
                    $versions_cells .= '<td class="chevron"></td>';
                }
            }

            if ($has_entities) {
                $html .= '<tr>';
                $html .= '<th>Entités</th>';
                $html .= $entities_cells;
                $html .= '</tr>';

                $html .= '<tr class="chevrons">';
                $html .= '<th></th>';
                foreach ($params_files as $idx => $file_data) {
                    $html .= '<td>';
                    if (isset($file_data['entity']) && !empty($file_data['entity'])) {
                        if (!$has_versions || (isset($file_data['version']) && !empty($file_data['version']))) {
                            $html .= BimpRender::renderIcon('fas_chevron-down');
                        }
                    }
                    $html .= '</td>';

                    if ($file_data['next_idx']) {
                        $html .= '<td class="chevron"></td>';
                    }
                }
                $html .= '</tr>';
            }

            if ($has_versions) {
                $html .= '<tr>';
                $html .= '<th>Versions</th>';
                $html .= $versions_cells;
                $html .= '</tr>';

                $html .= '<tr class="chevrons">';
                $html .= '<th></th>';
                foreach ($params_files as $idx => $file_data) {
                    $html .= '<td>';
                    if (isset($file_data['version']) && !empty($file_data['version'])) {
                        $html .= BimpRender::renderIcon('fas_chevron-down');
                    }
                    $html .= '</td>';

                    if ($file_data['next_idx']) {
                        $html .= '<td class="chevron"></td>';
                    }
                }
                $html .= '</tr>';
            }

            $html .= '<tr>';
            $html .= '<th>Bases</th>';

            foreach ($params_files as $idx => $file_data) {
                $html .= '<td>';
                if (isset($file_data['base']) && !empty($file_data['base'])) {
                    $html .= '<span class="yml_file_label yml_base_file_label idx_' . $idx . '" data-file_idx="' . $idx . '" data-file_level="base">';
                    $html .= $file_data['name'];
                    $html .= '</span>';
                }
                $html .= '</td>';

                if ($file_data['next_idx']) {
                    $html .= '<td class="chevron">';
                    $html .= BimpRender::renderIcon('fas_chevron-left');
                    $html .= '</td>';
                }
            }

            $html .= '</tr>';

            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';

            // Trie des paramètres: 
            $params = array('root' => array());

            foreach ($params_files as $idx => $file_data) {
                foreach (array(
            'base', 'version', 'entity'
                ) as $extends_type) {
                    if (isset($file_data[$extends_type]) && !empty($file_data[$extends_type])) {
                        foreach ($file_data[$extends_type] as $param_name => $param_value) {
                            $file_idx = (string) $idx . '_' . $extends_type;
                            if (array_key_exists($param_name, self::$configs[$yml_type]['sections'])) {
                                if (!isset($params[$param_name])) {
                                    $params[$param_name] = array();
                                }

                                $params[$param_name] = self::mergeFileParams($params[$param_name], $param_value, $file_idx);
                            } else {
                                if (!isset($params['root'][$param_name])) {
                                    $params['root'][$param_name] = array(
                                        'values'     => array(),
                                        'sub_params' => array()
                                    );
                                }

                                if ($param_value === 'unset') {
                                    $params['root'][$param_name]['unset_by'] = $file_idx;
                                } elseif (isset($param_value['unused']) && (int) $param_value['unused']) {
                                    $params['root'][$param_name]['unused_by'] = $file_idx;
                                } else {
                                    $params['root'][$param_name]['file_idx'] = $file_idx;

                                    if (is_array($param_value)) {
                                        $params['root'][$param_name]['sub_params'] = self::mergeFileParams($params['root'][$param_name]['sub_params'], $param_value, $file_idx);
                                    } else {
                                        $params['root'][$param_name]['values'][$file_idx] = $param_value;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $sections = array();

            if (isset(self::$configs[$yml_type]['sections'])) {
                $sections = self::$configs[$yml_type]['sections'];
            } else {
                $sections = array(
                    'root' => 'Paramètres'
                );
            }

            $default_section = BimpTools::getArrayValueFromPath(self::$configs, $yml_type . '/default_section', 'root');
            if (!isset($params[$default_section]) || empty($params[$default_section])) {
                $default_section = 'root';
            }

            $html .= '<div class="yml_analyser_params_container row">';
            $html .= '<div class="yml_analyser_sections col-sm-12 col-md-3 col-lg-2">';
            foreach ($sections as $section => $section_label) {
                if (isset($params[$section]) && !empty($params[$section])) {
                    $html .= '<div class="yml_analyser_section' . ($default_section === $section ? ' active' : '') . '"';
                    $html .= ' data-section="' . $section . '">';
                    $html .= $section_label . ($section !== 'root' ? ' - <span class="section_name">' . $section . '</span>' : '');
                    $html .= '</div>';
                }
            }
            $html .= '</div>';

            $html .= '<div class="yml_analyser_params col-sm-12 col-md-9 col-lg-10">';

            foreach ($params as $section => $section_params) {
                $is_obj_field = ($yml_type === 'objects' && $section === 'fields');

                $html .= '<div class="yml_analyser_section_params yml_analyser_section_' . $section . '_params"' . ($default_section !== $section ? ' style="display: none"' : '');
                $html .= ' data-section="' . $section . '">';
                $section_title = BimpTools::getArrayValueFromPath($sections, $section, 'Section "' . $section . '"');

                $html .= '<h4>' . $section_title . ($section !== 'root' ? ' - <span class="section_name">' . $section . '</span>' : '') . '</h4>';

                foreach ($section_params as $param_name => $param_data) {
                    $param_title = ''; //BimpRender::renderCopyTextIcon($param_name);
                    if (isset($param_data['file_idx'])) {
                        $param_title .= '<span class="name idx_' . str_replace('_base', '', $param_data['file_idx']);
                        if (isset($param_data['unset_by'])) {
                            $param_title .= ' unset';
                        }
                        $param_title .= '">' . $param_name . '</span>';
                    } else {
                        $param_title .= $param_name;
                    }

                    if (isset($param_data['unset_by'])) {
                        $html .= '<div class="yml_analyser_param">';
                        $html .= $param_title;
                        $html .= ' : <span class="idx_' . str_replace('_base', '', $param_data['unset_by']) . '">';
                        $html .= BimpRender::renderIcon('fas_times-circle') . ' UNSET';
                        $html .= '</span>';
                        if (isset($param_data['title_extra']) && $param_data['title_extra']) {
                            $html .= ' ' . $param_data['title_extra'];
                        }
                        $html .= '</div>';
                    } elseif (isset($param_data['unused_by'])) {
                        $html .= '<div class="yml_analyser_param">';
                        $html .= $param_title;
                        $html .= ' : <span class="idx_' . str_replace('_base', '', $param_data['unused_by']) . '">';
                        $html .= BimpRender::renderIcon('fas_exclamation-circle') . ' UNUSED';
                        $html .= '</span>';
                        if (isset($param_data['title_extra']) && $param_data['title_extra']) {
                            $html .= ' ' . $param_data['title_extra'];
                        }
                        $html .= '</div>';
                    } elseif (isset($param_data['sub_params']) && !empty($param_data['sub_params'])) {
                        if ($is_obj_field && is_a($instance, 'BimpObject')) {
                            if (isset($param_data['sub_params']['dol_extra_field']) && (int) $param_data['sub_params']['dol_extra_field']) {
                                if (!$instance->dol_field_exists($param_name)) {
                                    $param_title .= '<span class="danger" style="margin-left: 12px">';
                                    $param_title .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Extrafield absent';
                                    $param_title .= '</span>';
                                }
                            }
                        }

                        $param_content = self::renderYmlAnalyserSubParamsContent($param_data['sub_params'], $max_idx, $files);
                        $html .= BimpRender::renderFoldableContainer($param_title, $param_content, array(
                                    'open'        => false,
                                    'offset_left' => true,
                                    'grey_bk'     => 0
                        ));
                    } else {
                        $html .= '<div class="yml_analyser_param">';
                        $html .= $param_title;
                        if (isset($param_data['values']) && !empty($param_data['values'])) {
                            if (count($param_data['values']) > 1) {
                                $html .= '<span class="bold">*</span>';
                            }
                            $html .= ' : ' . self::renderYmlAnalyserParamValues($param_data['values'], $max_idx, $files);
                        }
                        $html .= '</div>';
                    }
                }
                $html .= '</div>';
            }

            $html .= '</div>';
            $html .= '</div>';

            $html .= '</div>';
        }

        return $html;
    }

    public static function renderYmlAnalyserSubParamsContent($params, $max_idx, $files)
    {
        if (!is_array($params) || empty($params)) {
            return '';
        }

        $html = '';

        $html .= '<div class="yml_anayser_sub_params">';

        foreach ($params as $param_name => $param_data) {
            if (isset($param_data['file_idx'])) {
                $param_title = '<span class="name idx_' . str_replace('_base', '', $param_data['file_idx']);
                if (isset($param_data['unset_by'])) {
                    $param_title .= ' unset';
                }
                $param_title .= '">' . $param_name . '</span>';
            } else {
                $param_title = $param_name;
            }

            if (isset($param_data['unset_by'])) {
                $html .= '<div class="yml_analyser_param">';
                $html .= $param_title;
                $html .= ' : <span class="idx_' . str_replace('_base', '', $param_data['unset_by']) . '">';
                $html .= BimpRender::renderIcon('fas_times-circle') . ' UNSET';
                $html .= '</span>';
                if (isset($param_data['title_extra']) && $param_data['title_extra']) {
                    $html .= ' ' . $param_data['title_extra'];
                }
                $html .= '</div>';
            } elseif (isset($param_data['unused_by'])) {
                $html .= '<div class="yml_analyser_param">';
                $html .= $param_title;
                $html .= ' : <span class="idx_' . str_replace('_base', '', $param_data['unused_by']) . '">';
                $html .= BimpRender::renderIcon('fas_exclamation-circle') . ' UNUSED';
                $html .= '</span>';
                if (isset($param_data['title_extra']) && $param_data['title_extra']) {
                    $html .= ' ' . $param_data['title_extra'];
                }
                $html .= '</div>';
            } elseif (isset($param_data['sub_params']) && !empty($param_data['sub_params'])) {
                $html .= '<div class="yml_analyser_param">';
                $html .= $param_title . ' : ';
                if (isset($param_data['title_extra']) && $param_data['title_extra']) {
                    $html .= ' ' . $param_data['title_extra'];
                }
                $html .= '</div>';
                $html .= self::renderYmlAnalyserSubParamsContent($param_data['sub_params'], $max_idx, $files);
            } else {
                $html .= '<div class="yml_analyser_param">';
                $html .= $param_title;
                if (isset($param_data['values']) && !empty($param_data['values'])) {
                    if (count($param_data['values']) > 1) {
                        $html .= '<span class="bold">*</span>';
                    }
                    $html .= ' : ';
                    if (isset($param_data['title_extra']) && $param_data['title_extra']) {
                        $html .= ' ' . $param_data['title_extra'];
                    }
                    $html .= self::renderYmlAnalyserParamValues($param_data['values'], $max_idx, $files);
                }
                $html .= '</div>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    public static function renderYmlAnalyserParamValues($values, $max_idx, $files, &$param_file_idx = '')
    {
        $param_file_idx = '';

        if (!is_array($values)) {
            return (string) $values;
        }

        if (empty($values)) {
            return '';
        }

        if (count($values) > 1) {
            $html = '';
            $popover = '';
            $value = '';

            for ($i = 0; $i <= $max_idx; $i++) {
                foreach (array('entity', 'version', 'base') as $extend_type) {
                    $idx = ($i < 10 ? '0' : '') . $i . '_' . $extend_type;

                    if (isset($values[$idx])) {
                        if (!$param_file_idx) {
                            $param_file_idx = (string) $idx;
                            $value = (string) $values[$idx];
                        } else {
                            $popover .= ($popover ? '<br/>' : '');
                            $popover .= '<span class="bold idx_' . str_replace('_base', '', $idx) . '">';
                            $popover .= BimpTools::getArrayValueFromPath($files, $idx, $idx);
                            $popover .= '</span>' . ' : ' . $values[$idx];
                        }
                    }
                }
            }

            $html .= '<span class="bs-popover"' . ($popover ? BimpRender::renderPopoverData($popover, 'bottom', true) : '') . '>';
            $html .= htmlentities($value);
            $html .= '</span>';
            return $html;
        }

        foreach ($values as $idx => $value) {
            $param_file_idx = $idx;
            return htmlentities($value);
        }
    }

    public static function renderYmlAnalyserColorsStyles($params)
    {
        $html = '<style>';

        $hue_idx = 0;
        $lum_base = 30;
        $sat_base = 100;
        $sat_modifier = 0;
        $hue_modifier = 0;
        $lum_extends_modifier = 20;

        foreach ($params as $idx => $data) {
            if ($data['name'] === 'BimpObject') {
                $color_base = '333333';
                $color_version = '666666';
                $color_entity = '999999';
            } else {
                $hue = self::$hues[$hue_idx] + $hue_modifier;
                if ($hue > 360) {
                    $hue -= 360;
                }
                $sat = $sat_base - $sat_modifier;
                if ($sat < 0) {
                    $sat = 0;
                }
                $lum = $lum_base;
                $color_base = BimpTools::hslToHex(array($hue / 360, $sat / 100, $lum / 100));
                $color_version = BimpTools::hslToHex(array($hue / 360, $sat / 100, ($lum + $lum_extends_modifier) / 100));
                $color_entity = BimpTools::hslToHex(array($hue / 360, ($sat - 50) / 100, ($lum + ($lum_extends_modifier * 1.5)) / 100));
            }

            $html .= '.idx_' . $idx . ' {';
            $html .= 'color: #' . $color_base;
            $html .= '}' . "\n";

            $html .= '.idx_' . $idx . '_version {';
            $html .= 'color: #' . $color_version;
            $html .= '}' . "\n";

            $html .= '.idx_' . $idx . '_entity {';
            $html .= 'color: #' . $color_entity;
            $html .= '}' . "\n";

            if ($data['name'] !== 'BimpObject') {
                $hue_idx++;
                if (!isset(self::$hues[$hue_idx])) {
                    $hue_idx = 0;
                    $sat_modifier += 20;
                    $hue_modifier += 20;
                }
            }
        }
        $html .= '</style>';

        return $html;
    }
}
