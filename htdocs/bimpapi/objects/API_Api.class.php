<?php

require_once DOL_DOCUMENT_ROOT . '/bimpapi/BimpApi_Lib.php';

class API_Api extends BimpObject
{

    public static $modes = array(
        'test' => array('label' => 'Test', 'icon' => 'fas_exclamation-circle', 'classes' => array('info')),
        'prod' => array('label' => 'Production', 'icon' => 'fas_check', 'classes' => array('success')),
    );

    // Droits users: 

    public function canView()
    {
        if ($this->isLoaded()) {
            global $user;
            if ($this->getData('name') === 'piste') {
                if (!empty($user->rights->bimpcommercial->chorus_exports)) {
                    return 1;
                }
            }

            return (int) $user->admin;
        }

        return 1;
    }

    public function canCreate()
    {
        global $user;
        return (int) $user->admin;
    }

    public function canSetAction($action)
    {
        return $this->canCreate();
    }

    // Getters params:

    public function getActionsButtons()
    {
        $buttons = array();

        $userAccount = $this->getDefaultUserAccount();
        if (BimpObject::objectLoaded($userAccount)) {
            if ($userAccount->isActionAllowed('connect') && $userAccount->canSetAction('connect')) {
                $label = ($userAccount->isLogged() ? 'Réinitialiser la connexion du' : 'Connecter le') . ' compte par défaut';
                $buttons[] = array(
                    'label'   => $label,
                    'icon'    => 'fas_power-off',
                    'onclick' => $userAccount->getJsActionOnclick('connect')
                );
            }
        }

        if (BimpCore::isModeDev() || 1) {
            $api = $this->getApiInstance();

            if (is_a($api, 'BimpAPI') && $api->isOk()) {
                $buttons[] = array(
                    'label'   => 'Test dev',
                    'icon'    => 'fas_cogs',
                    'onclick' => $api->getJsApiRequestOnClick('testRequest')
                );
            }
        }

        return $buttons;
    }

    public function getListExtraHeadersButtons()
    {
        $buttons = array();

        $buttons[] = array(
            'label'   => 'Installer une API',
            'icon'    => 'fas_folder-plus',
            'onclick' => $this->getJsActionOnclick('installApi', array(), array(
                'form_name' => 'install'
            ))
        );

        return $buttons;
    }

    public function getNameProperties()
    {
        return array('title');
    }

    public function getRefProperty()
    {
        return 'name';
    }

    public function getApiToInstallDefaultTitle()
    {
        $api_name = BimpTools::getPostFieldValue('api_name');
        $class_name = ucfirst($api_name) . 'API';

        if (!class_exists($class_name)) {
            $file = DOL_DOCUMENT_ROOT . '/bimpapi/classes/apis/' . $class_name . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }

        if (class_exists($class_name)) {
            return $class_name::getDefaultApiTitle();
        }

        return '';
    }

    // Getters Array: 

    public function getApisToInstallArray()
    {
        $apis = array();

        $apis_classes = BimpAPI::getApisClassesArray();
        $installed_apis = self::getApisArray(false, false);

        foreach ($apis_classes as $api_name => $api_class_name) {
            if (!class_exists($api_class_name)) {
                continue;
            }

            if (!$api_class_name::$allow_multiple_instances && array_key_exists($api_class_name::$name . '_0', $installed_apis)) {
                continue;
            }

            if (class_exists($api_class_name)) {
                if (method_exists($api_class_name, 'install')) {
                    $apis[$api_name] = $api_class_name;
                }
            }
        }

        return $apis;
    }

    public static function getApisArray($include_empty = false, $active_only = true, $key = 'name')
    {
        $cache_key = 'bimp_apis_array_by_' . $key;

        if ($active_only) {
            $cache_key .= '_active_only';
        }

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $rows = self::getBdb()->getRows('bimpapi_api', ($active_only ? 'active = 1' : '1'), null, 'array', array(
                $key,
                'api_idx',
                'title'
            ));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][$r[$key] . ($key === 'name' ? '_' . (int) $r['api_idx'] : '')] = $r['libelle'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty, '');
    }

    public function getUsersAccountsArray($include_empty = false, $mode = 'prod')
    {
        if (!$this->isLoaded()) {
            return array();
        }

        $cache_key = 'bimpapi_' . $this->id . '_' . $mode . '_users_accounts_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $rows = $this->db->getRows('bimpapi_user_account', 'id_api = \'' . $this->id . '\' AND mode = \'' . $mode . '\'', null, 'array', array('id', 'name'));

            if (is_array($rows)) {
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['id']] = $r['name'];
                }
            }
        }

        return self::getCacheArray($cache_key, $include_empty);
    }

    public function getAvailableProcessesArray()
    {
        $cache_key = 'api_available_bds_processes_array';

        if (!isset(self::$cache[$cache_key])) {
            self::$cache[$cache_key] = array();

            $processes = $this->getData('processes');

            $where = 'active = 1';

            if (!empty($processes)) {
                $where .= ' AND id NOT IN (' . implode(',', $processes) . ')';
            }
            $rows = $this->db->getRows('bds_process', $where, null, 'array', array('id', 'title', 'type'));

            if (is_array($rows)) {
                BimpObject::loadClass('bimpdatasync', 'BDS_Process');
                foreach ($rows as $r) {
                    self::$cache[$cache_key][(int) $r['id']] = $r['title'] . ($r['type'] && isset(BDS_Process::$types[$r['type']]) ? ' (' . BDS_Process::$types[$r['type']]['label'] . ')' : '');
                }
            }
        }

        return self::$cache[$cache_key];
    }

    // Getters données: 

    public function getApiInstance()
    {
        $api_name = $this->getData('name');

        if ($api_name) {
            return BimpAPI::getApiInstance($api_name, (int) $this->getData('api_idx'));
        }

        return null;
    }

    public function getDefaultUserAccountId($mode = null)
    {
        if (is_null($mode)) {
            $mode = $this->getData('mode');
        }

        switch ($mode) {
            case 'test':
                return (int) $this->getData('id_default_user_account_test');

            case 'prod':
                return (int) $this->getData('id_default_user_account_prod');
        }

        return 0;
    }

    public function getDefaultUserAccount($mode = null)
    {
        $id_user_account = (int) $this->getDefaultUserAccountId($mode);

        if ($id_user_account) {
            return BimpCache::getBimpObjectInstance('bimpapi', 'API_UserAccount', $id_user_account);
        }

        return null;
    }

    public function getPageTitle()
    {
        return 'API ' . $this->getData('title');
    }

    // Getters Statiques: 

    public static function getApiInstanceByID($id_api, &$errors = array(), $check_validity = false)
    {
        $api_obj = BimpCache::getBimpObjectInstance('bimpapi', 'API_Api', $id_api);
        if (!BimpObject::objectLoaded($api_obj)) {
            $errors[] = 'ID de l\'API invalide';
        } else {
            $api = $api_obj->getApiInstance();
            if (is_a($api, 'BimpAPI')) {
                if ($check_validity) {
                    $api->isOk($errors);
                }

                return $api;
            } else {
                $errors[] = 'Erreur de configuration : API invalide (' . $api_obj->getName() . ')';
            }
        }

        return null;
    }

    // Affichages: 

    public function displayDefaultUserAccountLogged()
    {
        $html = '';

        $id_default_user_account = (int) $this->getDefaultUserAccountId();
        if ($id_default_user_account) {
            $userAccount = $this->getDefaultUserAccount();

            if (BimpObject::objectLoaded($userAccount)) {
                if ($userAccount->isLogged()) {
                    $html .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Connecté</span>';
                } else {
                    $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Déconnecté</span>';
                }
            } else {
                $html .= '<span class="important">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'Le compte #' . $id_default_user_account . ' n\'existe plus</span>';
            }
        } else {
            $html .= '<span class="warning">' . BimpRender::renderIcon('fas_exclamation-circle', 'iconLeft') . 'Pas de compte par défaut dans le mode actuel</span>';
        }

        return $html;
    }

    // Rendus HTML: 

    public function renderHeaderStatusExtra()
    {
        $html = '';

        if ((int) $this->getData('active')) {
            $html .= '<span class="success">' . BimpRender::renderIcon('fas_check', 'iconLeft') . 'Active</span>';

            $html .= '<br/>';
            $html .= 'Mode: ' . $this->displayInitData('mode', 'default', false);
        } else {
            $html .= '<span class="danger">' . BimpRender::renderIcon('fas_times', 'iconLeft') . 'Désactivée</span>';
        }

        return $html;
    }

    public function renderDefaultViewNavtabs()
    {
        $html = '';

        $tabs = array();

        $tabs[] = array(
            'id'            => 'users_accounts_list_tab',
            'title'         => BimpRender::renderIcon('fas_user-tag', 'iconLeft') . 'Comptes utilisateurs',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#users_accounts_list_tab .nav_tab_ajax_result\')', array('users_accounts'), array('button' => ''))
        );

        $tabs[] = array(
            'id'            => 'params_list_tab',
            'title'         => BimpRender::renderIcon('fas_cog', 'iconLeft') . 'Paramètres API',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#params_list_tab .nav_tab_ajax_result\')', array('params'), array('button' => ''))
        );

        $tabs[] = array(
            'id'            => 'logs_list_tab',
            'title'         => BimpRender::renderIcon('fas_book-medical', 'iconLeft') . 'Logs',
            'ajax'          => 1,
            'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#logs_list_tab .nav_tab_ajax_result\')', array('logs'), array('button' => ''))
        );

        if (!empty($this->getData('processes'))) {
            $tabs[] = array(
                'id'            => 'reports_navtabs',
                'title'         => BimpRender::renderIcon('fas_file-alt', 'iconLeft') . 'Rapports des processus',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderReportsNavTabs', '$(\'#reports_navtabs .nav_tab_ajax_result\')', array(), array('button' => ''))
            );
        }

        $html .= BimpRender::renderNavTabs($tabs, 'children');

        return $html;
    }

    public function renderReportsNavTabs()
    {
        $html = '';

        $processes = $this->getData('processes');

        if (empty($processes)) {
            $html .= BimpRender::renderAlerts('Il n\'y a aucun processus lié à cette API', 'warning');
        } else {
            $tabs = array();

            $tabs[] = array(
                'id'            => 'reports_tab',
                'title'         => BimpRender::renderIcon('fas_file-alt', 'iconLeft') . 'Rapports',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#reports_tab .nav_tab_ajax_result\')', array('reports'), array('button' => ''))
            );

            $tabs[] = array(
                'id'            => 'reports_lines_tab',
                'title'         => BimpRender::renderIcon('fas_list', 'iconLeft') . 'Toutes les lignes de rapport',
                'ajax'          => 1,
                'ajax_callback' => $this->getJsLoadCustomContent('renderLinkedObjectList', '$(\'#reports_lines_tab .nav_tab_ajax_result\')', array('reports_lines'), array('button' => ''))
            );

            $html .= BimpRender::renderNavTabs($tabs, 'children');
        }

        return $html;
    }

    public function renderLinkedObjectList($type_children)
    {
        $html = '';

        switch ($type_children) {
            case 'users_accounts':
                $html .= $this->renderChildrenList('users_accounts', 'api', 1, 'Comptes utilisateurs de l\'API "' . $this->getData('title') . '"');
                break;

            case 'logs':
                if ($this->isLoaded()) {
                    $list = new BC_ListTable(BimpObject::getInstance('bimpcore', 'Bimp_Log'), 'object', 1, null, 'Logs');
                    $list->addFieldFilterValue('obj_module', $this->module);
                    $list->addFieldFilterValue('obj_name', $this->object_name);
                    $list->addFieldFilterValue('id_object', $this->id);
                    $html .= $list->renderHtml();
                }
                break;

            case 'params':
                if ($this->isLoaded()) {
                    $html .= $this->renderChildrenList('params', 'default', 1, 'Paramètre de l\'API "' . $this->getData('title') . '"');
                }
                break;

            case 'reports':
                $processes = $this->getData('processes');

                if (empty($processes)) {
                    $html .= BimpRender::renderAlerts('Il n\'y a aucun processus lié à cette API', 'warning');
                } else {
                    $list = new BC_ListTable(BimpObject::getInstance('bimpdatasync', 'BDS_Report'), 'default', 1, null, 'Rapports des processus liés à cette API');
                    $list->addFieldFilterValue('id_process', array(
                        'in' => $processes
                    ));
                    $html .= $list->renderHtml();
                }
                break;

            case 'reports_lines':
                $processes = $this->getData('processes');

                if (empty($processes)) {
                    $html .= BimpRender::renderAlerts('Il n\'y a aucun processus lié à cette API', 'warning');
                } else {
                    $list = new BC_ListTable(BimpObject::getInstance('bimpdatasync', 'BDS_ReportLine'), 'default', 1, null, 'Toutes les lignes de rapport liées à cette API');
                    $list->addJoin('bds_report', 'a.id_report = report.id', 'report');
                    $list->addFieldFilterValue('report.id_process', array(
                        'in' => $processes
                    ));
                    $html .= $list->renderHtml();
                }
                break;
        }

        return $html;
    }

    // Actions: 

    public function actionInstallApi($data, &$success)
    {
        $errors = array();
        $warnings = array();
        $success = 'API installée avec succès';

        $api_name = BimpTools::getArrayValueFromPath($data, 'api_name', '');
        $api_title = BimpTools::getArrayValueFromPath($data, 'api_title', '');

        if (!$api_name) {
            $errors[] = 'Aucune API à installer sélectionnée';
        } else {
            $api = BimpAPI::getApiInstance($api_name);

            if (!is_a($api, 'BimpAPI') || !method_exists($api, 'install')) {
                $errors[] = 'L\'API sélectionnée est invalide';
            } else {
                $errors = $api->install($api_title, $warnings);
            }
        }

        return array(
            'errors'   => $errors,
            'warnings' => $warnings
        );
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $errors = array();

        $api_name = $this->getData('name');
        $api_class_name = ucfirst($api_name) . 'API';

        if (!class_exists($api_class_name)) {
            $file = DOL_DOCUMENT_ROOT . '/bimpapi/classes/apis/' . $api_class_name . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }

        if (class_exists($api_class_name)) {
            $idx = $this->db->getMax('bimpapi_api', 'api_idx', 'name = \'' . $api_name . '\'');

            if ($api_class_name::$allow_multiple_instances) {
                $idx = (int) $idx;
                $idx += 1;
                $this->set('api_idx', $idx);
            } elseif (!is_null($idx)) {
                $errors[] = 'Une instance existe déjà pour l\'API "' . $api_name . '" (création d\'instances multiples non permise pour cette API)';
            }
        } else {
            $errors[] = 'La classe API "' . $api_class_name . '" n\'existe pas';
        }

        if (!count($errors)) {
            $errors = parent::create($warnings, $force_create);
        }

        return $errors;
    }
}
