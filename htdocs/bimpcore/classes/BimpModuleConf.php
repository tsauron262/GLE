<?php

class BimpModuleConf
{

    public $module = '';
    public $config = null;
    public static $category_def = array(
        'label' => array('label' => 'Nom', 'required' => 1),
        'desc'  => array('label' => 'Description', 'default' => ''),
    );
    public static $param_def = array(
        'label'    => array('label' => 'Nom', 'required' => 1),
        'info'     => array('label' => 'Information', 'default' => ''),
        'warning'  => array('label' => 'Alerte', 'default' => ''),
        'help'     => array('label' => 'Note input', 'default' => ''),
        'cat'      => array('label' => 'Catégorie', 'default' => ''),
        'type'     => array('label' => 'Type', 'default' => 'string'),
        'values'   => array('label' => 'Valeurs', 'default' => array()),
        'def'      => array('label' => 'Valeur par défaut', 'default' => null),
        'required' => array('label' => 'Obligatoire (en base)', 'default' => 0)
    );
    public $full_params = null;
    protected static $entities = array();

    public static function getInstance($module)
    {
        $cache_key = 'bimp_module_conf_' . $module;

        if (!BimpCache::cacheExists($cache_key)) {
            BimpCache::setCache($cache_key, new BimpModuleConf($module));
        }

        return BimpCache::getCache($cache_key);
    }

    public function __construct($module)
    {
        $this->module = $module;
        $this->config = BimpConfig::getModuleConfigInstance($module, $this);
    }

    public function canView()
    {
        global $user;
        return (int) $user->admin;
    }

    public function canEdit()
    {
        return BimpCore::isUserDev();
    }

    public function getParamsByCategories()
    {
        $categories = array();

        if (!is_null($this->config)) {
            $params = $this->config->getCompiledParams('params');

            if (is_array($params) && !empty($params)) {
                foreach ($params as $name => $param_def) {
                    $category = BimpTools::getArrayValueFromPath($param_def, 'cat', '');

                    if (!isset($categories[$category])) {
                        $categories[$category] = array();
                    }

                    $categories[$category][$name] = $param_def;
                }
            }
        }

        return $categories;
    }

    public function getFullParamsData()
    {
        if (is_null($this->full_params)) {
            $full_params = array();

            $params = $this->config->getCompiledParams('params');

            if (is_array($params)) {
                foreach ($params as $name => $data) {
                    $full_params[$name] = $data;

                    if (isset($data['sub_params'])) {
                        unset($full_params[$name]['sub_params']);

                        foreach ($this->getFullSubParamsData($data) as $sub_param_name => $sub_param_data) {
                            $full_params[$sub_param_name] = $sub_param_data;
                        }
                    }
                }
            }

            $this->full_params = $full_params;
        }

        return $this->full_params;
    }

    protected function getFullSubParamsData($parent_param)
    {
        $full_params = array();

        if (isset($parent_param['sub_params'])) {
            foreach ($parent_param['sub_params'] as $idx => $if_data) {
                if (isset($if_data['params'])) {
                    foreach ($if_data['params'] as $name => $data) {
                        $full_params[$name] = $data;

                        if (isset($data['sub_params'])) {
                            unset($full_params[$name]['sub_params']);

                            foreach ($this->getFullSubParamsData($data) as $sub_param_name => $sub_param_data) {
                                $full_params[$sub_param_name] = $sub_param_data;
                            }
                        }
                    }
                }
            }
        }

        return $full_params;
    }

    public function getParamDefs($param_name)
    {
        return $this->config->getCompiledParams('params/' . $param_name);
    }

    public static function getEntities($entity_type)
    {
        if (!isset(self::$entities[$entity_type])) {
            self::$entities[$entity_type] = array();

            if (BimpTools::isModuleDoliActif('MULTICOMPANY')) {
                switch ($entity_type) {
                    case 'current':
                        if (BimpCore::isModeDev()) {
                            self::$entities[$entity_type][1] = 'Entité 1';
                        } else {
                            $entity = getEntity('bimp_conf', 0);
                            if ($entity) {
                                self::$entities[$entity_type][$entity] = 'Courante'; // todo: remplacer par nom
                            } else {
                                return BimpRender::renderAlerts('Aucune entité courante');
                            }
                        }

                        break;

                    case 'global':
                        self::$entities[$entity_type][$entity] = 'Globale';
                        break;

                    case 'all':
                    default:
                        if (BimpCore::isModeDev()) {
                            self::$entities[$entity_type][0] = 'Globale';
                            self::$entities[$entity_type][1] = 'Entité 1';
                            self::$entities[$entity_type][2] = 'Entité 2';
                        } else {
                            self::$entities[$entity_type] = BimpCache::getEntitiesCacheArray(true, 'Globale');
                        }
                        break;
                }
            } else {
                self::$entities[$entity_type][0] = '';
            }
        }

        return self::$entities[$entity_type];
    }

    public function renderFormHtml($entity_type = 'all')
    {
        $html = '';

        if ($this->module === 'bimpcore') {
            $module_version = BimpCore::getConf('bimpcore_version', '');
        } else {
            $module_version = BimpCore::getConf('module_version_' . $this->module);
        }

        $html .= '<div class="module_params_form" data-module_name="' . $this->module . '">';

        $html .= '<div class="module_params_header">';
        $html .= '<div style="display: inline-block; width: 49%">';
        $html .= '<h3>Module ' . $this->module . '</h3>';
        if ($module_version) {
            $html .= '<h4>Version <b>' . $module_version . '</b></h4>';
        }
        $html .= '</div>';

        $html .= '<div style="display: inline-block; width: 50%; text-align: right;">';
        if ($module_version) {
            $html .= BimpInput::renderInput('text', 'cur_module_search', '', array(
                        'extra_class' => 'cur_module_search_input',
                        'addon_left'  => BimpRender::renderIcon('fas_search')
            ));
            $html .= '<span class="btn btn-default bs-popover"' . BimpRender::renderPopoverData('Effacer la recherche');
            $html .= ' onclick="BimpModuleConf.eraseCurModuleSearch()"';
            $html .= '>';
            $html .= BimpRender::renderIcon('fas_eraser', 'iconLeft');
            $html .= '</span>';
        }

        $html .= '<span class="btn btn-default" onclick="BimpModuleConf.reloadModuleConfForm();" style="margin-left: 4px">';
        $html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser';
        $html .= '</span>';
        $html .= '</div>';
        $html .= '</div>';

        if (!BimpCore::isModuleActive($this->module)) {
            $html .= BimpRender::renderAlerts('Ce module n\'est pas installé');
        } else {
            if (!$this->canView()) {
                return BimpRender::renderAlerts('Vous n\'avez pas la permission de voir ce contenu');
            }

            if (!is_null($this->config)) {
                if (count($this->config->errors)) {
                    $html .= BimpRender::renderAlerts($this->config->errors);
                }

                $categories_params = $this->getParamsByCategories();
                $categories = $this->config->getCompiledParams('categories');
                $tabs = array();

                foreach ($categories_params as $categ_name => $categ_params) {
                    if (empty($categ_params)) {
                        continue;
                    }

                    $content = '';
                    $n_errors = 0;

                    if ($categ_name && isset($categories[$categ_name])) {
                        $content .= '<h3>' . BimpTools::getArrayValueFromPath($categories, $categ_name . '/label', $categ_name) . '</h3>';

                        $desc = BimpTools::getArrayValueFromPath($categories, $categ_name . '/desc', '');
                        if ($desc) {
                            $content .= '<p style="font-style: italic; color: #737373">' . $desc . '</p>';
                        }
                    } elseif ($categ_name === '' || $categ_name === 'default_category') {
                        $content .= '<h3>Paramètres principaux</h3>';
                    }

                    $content .= '<div class="category_params_container">';
                    $content .= self::renderParamsTable($this->module, $categ_params, $n_errors, $entity_type);
                    $content .= '</div>';

                    $title = ($categ_name ? BimpTools::getArrayValueFromPath($categories, $categ_name . '/label', $categ_name) : 'Paramètres principaux');

                    if ($n_errors) {
                        $title .= '&nbsp;&nbsp;<span class="badge badge-danger n_errors">' . $n_errors . '</span>';
                    }

                    $tabs[] = array(
                        'id'      => ($categ_name ? $categ_name : 'default_category'),
                        'title'   => $title . '&nbsp;&nbsp;',
                        'content' => $content
                    );
                }

                $html .= BimpRender::renderNavTabs($tabs, 'module_' . $this->module . '_conf_categories', array(
                            'li_extra_class' => 'param_category_title'
                ));
            } else {
                $html .= BimpRender::renderAlerts('Echec du chargement de la configuration pour un une raison inconnue');
            }
        }

        $html .= '</div>';
        return $html;
    }

    // Méthodes statiques: 

    public static function renderParamsTable($module, $params, &$n_errors = 0, $entity_type = 'all')
    {
        $html = '';

        if (!empty($params)) {
            $html .= '<table class="bimp_list_table module_conf_params" data-module="' . $module . '">';
            $html .= '<tbody>';

            foreach ($params as $name => $param_defs) {
                $html .= self::renderParamRow($entity_type, $module, $name, $param_defs, $n_errors);

                $type = BimpTools::getArrayValueFromPath($param_defs, 'type', 'string');
                $sub_params_defs = BimpTools::getArrayValueFromPath($param_defs, 'sub_params', array());

                if (!empty($sub_params_defs)) {
                    foreach ($sub_params_defs as $sub_params_def) {
                        $if = BimpTools::getArrayValueFromPath($sub_params_def, 'if', null);
                        if (!is_null($if)) {
                            $if = explode(',', $if);
                        }
                        $sub_params = BimpTools::getArrayValueFromPath($sub_params_def, 'params', array());

                        if (!is_null($sub_params)) {
                            $html .= '<tr class="sub_params_row" data-parent_param="' . $name . '" data-if="' . (!is_null($if) ? htmlentities(implode(',', $if)) : '') . '">';
                            $html .= '<td colspan="3">';
                            $html .= '<div class="sub_params_container">';
                            if (is_null($if)) {
                                $html .= BimpRender::renderAlerts('Erreur: condition absente (paramètre "if")');
                            }

                            $html .= self::renderParamsTable($module, $sub_params, $n_errors, $entity_type);
                            $html .= '</div>';

                            $html .= '</td>';
                            $html .= '</tr>';
                        }
                    }
                }
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        return $html;
    }

    public static function renderParamRow($entity_type, $module, $name, $param_defs = null, &$n_errors = 0, $content_only = false)
    {
        $html = '';

        if (empty($param_defs)) {
            $conf = new BimpModuleConf($module);
            $param_defs = $conf->getParamDefs($name);
        }

        $entities = self::getEntities($entity_type);
        $can_edit = BimpCore::isUserDev();
//            $can_edit = false;
        $required = BimpTools::getArrayValueFromPath($param_defs, 'required', 0);
        $values = array();

        $missing = false;
        foreach ($entities as $id_entity => $entity_label) {
            $source = '';
            $value = BimpCore::getConf($name, null, $module, $source, $id_entity);

            if (is_null($value)) {
                $value = BimpTools::getArrayValueFromPath($param_defs, 'def');
            }

            if ($required && (string) $value === '') {
                $missing = true;
                $n_errors++;
            }

            $values[$id_entity] = array(
                'val' => $value,
                'src' => $source
            );
        }

        if (!$content_only) {
            $html .= '<tr class="param_row' . ($missing ? ' has_errors' : '') . '" data-module="' . $module . '" data-param_name="' . $name . '">';
        }

        $html .= '<td class="param_label">';
        $label = BimpTools::getArrayValueFromPath($param_defs, 'label', '');

        if ($label) {
            $html .= '<span class="bold param_label">' . $label . '</span>';
        } else {
            $html .= '<span class="danger">Nom Absent</span>';
        }

        if ($required) {
            $html .= '&nbsp;<span style="color: #FF0000;"><b>*</b></span>';
        }

        $html .= '<br/>';

        $displayed_name = BimpTools::getArrayValueFromPath($param_defs, 'displayed_name', $name);

        $html .= '<span class="small param_name" style="color: #807F7F">' . $displayed_name . '</span>';
        $html .= '</td>';

        $html .= '<td>';

        $html .= '<table>';

        foreach ($values as $id_entity => $val_data) {
            $is_saved = ($val_data['src'] !== 'val_def' && ($val_data['src'] !== 'global' || $id_entity == 0));
            $html .= '<tr class="entity_param_row" data-id_entity="' . $id_entity . '">';

//            $html .= '<td>';
//            $html .= '<pre>' . print_r($val_data, 1) . '</pre>';
//            $html .= '</td>';

            if (BimpTools::isModuleDoliActif('MULTICOMPANY')) {
                $html .= '<td' . ($is_saved ? ' style="font-weight: bold"' : '') . '>' . $entities[$id_entity] . '</td>';
            }

            $html .= '<td>';
            if ($can_edit) {
                $html .= self::renderParamInput($module, $name, $param_defs, $val_data['val'], $id_entity, $val_data['src']);

                if ($is_saved) {
                    $html .= '</td>';
                    $html .= '<td>';

                    $html .= '<span class="btn btn-light-danger iconBtn" onclick="BimpModuleConf.removeParam(\'' . $module . '\', \'' . $name . '\', ' . $id_entity . ')">';
                    $html .= BimpRender::renderIcon('fas_trash-alt');
                    $html .= '</span>';
                }
            } else {
                $html .= '<td>';
                $html .= self::displayParamValue($param_defs, $val_data['val'], $val_data['src']);

                if ($is_saved) {
                    $html .= '&nbsp&nbsp;<span style="color: #CCCCCC">' . BimpRender::renderIcon('fas_save') . '</span>';
                }

                $html .= '</td>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        $html .= '</td>';

        $html .= '<td>';
        if ($missing) {
            $html .= '<div><span class="danger">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'Valeur obligatoire manquante</span></div>';
        }
        $info = BimpTools::getArrayValueFromPath($param_defs, 'info', '');
        if ($info) {
            $html .= BimpRender::renderAlerts($info, 'info');
        }
        $warning = BimpTools::getArrayValueFromPath($param_defs, 'warning', '');
        if ($warning) {
            $html .= BimpRender::renderAlerts($warning, 'warning');
        }
        $html .= '</td>';

        if (!$content_only) {
            $html .= '</tr>';
        }

        return $html;
    }

    public static function renderParamInput($module, $param_name, $param_defs, $value, $id_entity = 0, $src = '')
    {
        $html = '<div>';

        $data = array(
            'module'    => $module,
            'id_entity' => $id_entity,
            'src'       => $src
        );

        switch (BimpTools::getArrayValueFromPath($param_defs, 'type', 'string')) {
            case 'string':
                $html .= BimpInput::renderInput('text', $param_name, (string) $value, array(
                            'data'        => $data,
                            'extra_class' => 'module_conf_input',
                            'addon_left'  => BimpRender::renderIcon('fas_pen')
                ));
                break;

            case 'text':
                $html .= BimpInput::renderInput('textarea', $param_name, (string) $value, array(
                            'data'        => $data,
                            'extra_class' => 'module_conf_input',
                            'rows'        => 3,
                            'auto_expand' => true
                ));
                break;

            case 'bool':
                $html .= BimpInput::renderInput('toggle', $param_name, (int) $value, array(
                            'data'        => $data,
                            'extra_class' => 'module_conf_input'
                ));
                break;

            case 'select':
                $values = BimpTools::getArrayValueFromPath($param_defs, 'values', array());
                $html .= BimpInput::renderInput('select', $param_name, $value, array(
                            'data'        => $data,
                            'extra_class' => 'module_conf_input',
                            'options'     => $values
                ));
                break;
        }
        $html .= '</div>';

        $help = BimpTools::getArrayValueFromPath($param_defs, 'help', '');
        if ($help) {
            $html .= '<p class="inputHelp" style="display: block">' . $help . '</p>';
        }

        return $html;
    }

    public static function displayParamValue($param_defs, $value)
    {
        $html = '';

        if (is_null($value)) {
            $html .= '<span class="warning">Non défini</span>';
        } else {
            switch (BimpTools::getArrayValueFromPath($param_defs, 'type', 'string')) {
                case 'string':
                case 'text':
                    $html .= $value;
                    break;

                case 'bool':
                    $html .= '<span class="' . ((int) $value ? 'success' : 'danger') . '">' . ((int) $value ? 'OUI' : 'NON') . '</span>';
                    break;

                case 'select':
                    $values = BimpTools::getArrayValueFromPath($param_defs, 'values', array());
                    if (isset($values[$value])) {
                        $html .= $values[$value];
                    } elseif ((string) $value) {
                        $html .= BimpRender::renderAlerts('Aucune valeur définie pour l\'identifiant "' . $value . '"');
                    }
                    break;
            }
        }

        return $html;
    }

    public static function getParamDefaultValue($name, $module = 'bimpcore', $log_param_unfound = false)
    {
        $conf = self::getInstance($module);
        $params = $conf->getFullParamsData();

        if ($log_param_unfound && !isset($params[$name])) {
            BimpCore::addlog('Paramètre de conf non trouvé: ' . $name . ' (' . $module . ')', Bimp_Log::BIMP_LOG_URGENT, 'bimpcore', null, array(
                'Module'    => $module,
                'Paramètre' => $name
                    ), 1);
            return null;
        }

        return BimpTools::getArrayValueFromPath($params, $name . '/def', null);
    }

    public static function getAllModulesParams($modules_actives_only = true)
    {
        $params = array();
        $modules = BimpCache::getBimpModulesArray($modules_actives_only, false);

        foreach ($modules as $module_name => $module) {
            $conf = self::getInstance($module_name);
            $mod_params = $conf->getFullParamsData();

            if (!empty($mod_params)) {
                $params[$module_name] = $mod_params;
            }
        }

        return $params;
    }

    public static function renderSearchParamsResults($search, $entity_type = 'all')
    {
        $html = '';

        if (!(string) $search) {
            $html .= BimpRender::renderAlerts('Veuillez saisir un terme de recherche');
        } else {
            $n_errors = 0;
            $bdb = BimpCache::getBdb();
            $search_words = explode(' ', str_replace(array('-', '_'), ' ', $search));
            $where = '';

            foreach ($search_words as $word) {
                $where .= ($where ? ' AND ' : '') . 'name LIKE  \'%' . $word . '%\'';
            }

            $rows = $bdb->getRows('bimpcore_conf', $where, null, 'array');

            $saved_params = array();
            foreach ($rows as $r) {
                $saved_params[$r['module']][$r['name']] = $r['value'];
            }

            $all_config_params = self::getAllModulesParams(true);
            $config_params = array();

            foreach ($all_config_params as $module => $params) {
                foreach ($params as $param_name => $param_data) {
                    $check = true;
                    foreach ($search_words as $word) {
                        if (strpos($param_data['label'], $word) === false) {
                            $check = false;
                            break;
                        }
                    }

                    if (!$check) {
                        $check = true;
                        foreach ($search_words as $word) {
                            if (strpos($param_name, $word) === false) {
                                $check = false;
                                break;
                            }
                        }
                    }

                    if ($check) {
                        if (!isset($config_params[$module])) {
                            $config_params[$module] = array();
                        }

                        $config_params[$module][$param_name] = $param_data;
                    }
                }
            }

            foreach ($saved_params as $module => $params) {
                foreach ($params as $name => $value) {
                    if (isset($config_params[$module][$name])) {
                        unset($saved_params[$module][$name]);
                    }
                }
            }

            $has_params = false;
            foreach ($config_params as $module => $module_params) {
                $params = array();
                foreach ($module_params as $param_name => $param_data) {
                    $label = BimpTools::getArrayValueFromPath($param_data, 'label', '');
                    if ($label) {
                        foreach ($search_words as $word) {
                            $label = preg_replace('/' . preg_quote($word, '/') . '/i', '<span style="color: #178BCF">' . $word . '</span>', $label);
                        }
                    }
                    $param_data['label'] = $label;

                    $name = $param_name;

                    foreach ($search_words as $word) {
                        $name = preg_replace('/' . preg_quote($word, '/') . '/i', '<span style="color: #178BCF">' . $word . '</span>', $name);
                    }

                    $param_data['displayed_name'] = $name;
                    $params[$param_name] = $param_data;
                }

                if (isset($saved_params[$module])) {
                    foreach ($saved_params[$module] as $param_name => $param_value) {
                        $name = $param_name;

                        foreach ($search_words as $word) {
                            $name = preg_replace('/' . preg_quote($word, '/') . '/i', '<span style="color: #178BCF">' . $word . '</span>', $name);
                        }

                        $params[$param_name] = array(
                            'label'          => '',
                            'displayed_name' => $name,
                            'warning'        => 'Paramètre non défini dans le fichier de configuration YML',
                            'type'           => 'string'
                        );
                    }
                }

                if (!empty($params)) {
                    $has_params = true;
                    $html .= '<div class="module_params_form" style="margin-bottom: 30px; padding-bottom: 15px; border-bottom: 1px solid #000" data-module_name="' . $module . '">';
                    $html .= '<h3>Module ' . $module . '</h3>';

                    $html .= self::renderParamsTable($module, $params, $n_errors, $entity_type);
                    $html .= '</div>';
                }
            }

            foreach ($saved_params as $module => $params) {
                if (!isset($config_params[$module])) {
                    $params = array();

                    if (isset($saved_params[$module])) {
                        foreach ($saved_params[$module] as $param_name => $param_value) {
                            $name = $param_name;

                            foreach ($search_words as $word) {
                                $name = preg_replace('/' . preg_quote($word, '/') . '/i', '<span style="color: #178BCF">' . $word . '</span>', $name);
                            }

                            $params[$param_name] = array(
                                'label'          => '',
                                'displayed_name' => $name,
                                'warning'        => 'Paramètre non défini dans le fichier de configuration YML',
                                'type'           => 'string'
                            );
                        }
                    }

                    if (!empty($params)) {
                        $has_params = true;
                        $html .= '<div class="module_params_form" style="margin-bottom: 30px; padding-bottom: 15px; border-bottom: 1px solid #000" data-module_name="' . $module . '">';
                        $html .= '<h3>Module ' . $module . '</h3>';

                        $html .= self::renderParamsTable($module, $params, $n_errors, $entity_type);
                        $html .= '</div>';
                    }
                }
            }

            if (!$has_params) {
                $html .= BimpRender::renderAlerts('Aucun paramètre trouvé pour la recherche "' . $search . '"', 'warning');
            }
        }

        return $html;
    }

    public static function getMissingRequiredParams()
    {
        $missings = array();

        foreach (scandir(DOL_DOCUMENT_ROOT) as $dir) {
            if (in_array($dir, array('.', '..')) || !is_dir(DOL_DOCUMENT_ROOT . '/' . $dir) || strpos($dir, 'bimp') !== 0) {
                continue;
            }

            if (BimpCore::isModuleActive($dir) && file_exists(DOL_DOCUMENT_ROOT . '/' . $dir . '/' . $dir . '.yml')) {
                $module_conf = self::getInstance($dir);

                $params = $module_conf->getFullParamsData();

                foreach ($params as $name => $param) {
                    if (isset($param['required']) && (int) $param['required']) {
                        if (!BimpCore::getConf($name, null, $dir)) {
                            $missings[] = $dir . ' : <b>' . $name . '</b>';
                        }
                    }
                }
            }
        }

        return $missings;
    }
}
