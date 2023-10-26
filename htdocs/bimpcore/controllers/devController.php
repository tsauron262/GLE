<?php

class devController extends BimpController
{

    public static $dev_links = array(
        array('Admin Bimp', 'fas_wrench', 'bimpcore/index.php?fc=admin'),
        array('Admin Dolibarr', 'fas_wrench', 'admin/tools/index.php?leftmenu=admintools'),
        array('Admin utilisateurs', 'fas_users', 'bimpcore/index.php?fc=users'),
        array('Admin caisse', 'fas_cash-register', 'bimpcaisse/index.php?fc=admin'),
        array('Config modules Dolibarr', 'fas_cog', 'admin/modules.php'),
        array('Logs Dolibarr', 'fas_book-medical', 'synopsistools/fichierLog.php'),
        array('PHP MyAdmin', 'fas_database', 'synopsistools/Synopsis_MyAdmin/index.php'),
        array('Quick scripts', 'fas_code', 'bimpcore/scripts/__quick_scripts.php'),
        array('Tâches crons', 'fas_clock', 'cron/list.php?status=-2&leftmenu=admintools')
    );

    public function can($right)
    {
        return BimpCore::isUserDev();
    }

    // Rendus HTML: 

    public function renderDashboardTab()
    {
        if (!$this->can('view')) {
            return BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à ce contenu', 'danger');
        }

        $html = '';

        $html .= '<div class="container-fluid">';

        // ToolsBar:
        $html .= '<div class="buttonsContainer align-left" style="padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px solid #000000">';
        if (BimpCore::isModuleActive('bimpapple')) {
            $html .= '<a class="btn btn-default" href="' . DOL_URL_ROOT . '/synopsistools/phantomApple.php" target="_blank">';
            $html .= 'Connect Apple' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
            $html .= '</a>';
        }

        if (file_exists(DOL_DOCUMENT_ROOT . '/bimpcore/test.php')) {
            $html .= '<a class="btn btn-default" href="' . DOL_URL_ROOT . '/bimpcore/test.php" target="_blank">';
            $html .= 'TESTS' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
            $html .= '</a>';
        }

        $html .= '<a class="btn btn-default" href="' . DOL_URL_ROOT . '/bimpcore/bimptests.php?type=sms" target="_blank">';
        $html .= 'TEST SMS' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
        $html .= '</a>';

        if (BimpCore::isModuleActive('bimpinterfaceclient')) {
            $html .= '<a class="btn btn-default" href="' . BimpObject::getPublicBaseUrl(true) . '" target="_blank">';
            $html .= 'Espace client' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
            $html .= '</a>';
        }

        if (!BimpCore::isModeDev()) {
            $html .= '<a class="btn btn-default" href="' . DOL_URL_ROOT . '/bimpcore/cron_log.php" target="_blank">';
            $html .= 'Logs CRONS client' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
            $html .= '</a>';
        }

        if (!BimpCore::isModeDev()) {
            $html .= '<a class="btn btn-default" href="' . DOL_URL_ROOT . '/synopsistools/git_pull.php" target="_blank">';
            $html .= 'GIT PULL' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
            $html .= '</a>';

            $html .= '<a class="btn btn-info" href="' . DOL_URL_ROOT . '/synopsistools/git_pull_all.php?go=1&branche=master" target="_blank">';
            $html .= 'GIT PULL ALL' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
            $html .= '</a>';
        }
        $html .= '</div>';

        // Récap Paramètres ERP: 
        $html .= '<div class="row" style="margin-bottom: 30px">';
        $html .= '<div class="col-sm-12">';
        $html .= 'Version:';
        if (defined('BIMP_EXTENDS_VERSION')) {
            $html .= '<b>' . BIMP_EXTENDS_VERSION . '</b>';
        } else {
            $html .= '<span class="danger">Aucune</span>';
        }
        $html .= ' - Entité: ';
        if (BimpCore::getExtendsEntity() != '') {
            $html .= '<b>' . BimpCore::getExtendsEntity() . '</b>';
        } else {
            $html .= '<span class="danger">Aucune</span>';
        }
        $html .= '</div>';
        $html .= '</div>';

        // Vérif des versions vérouillée: 
        if ((int) BimpCore::getConf('check_versions_lock')) {
            $html .= '<h4 class="danger">';
            $html .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . ' Vérification des versions vérouillée';
            $html .= '</h4>';

            $onclick = 'saveBimpcoreConf(\'bimpcore\', \'check_versions_lock\', \'0\', null, function() {bimp_reloadPage();})';

            $html .= '<div style="margin: 15px">';
            $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
            $html .= 'Dévérouiller immédiatement';
            $html .= '</span>';
            $html .= '</div>';
        }

        // vérif des pull vérouillés: 

        $lock_msg = BimpCore::getConf('git_pull_lock_msg');
        if ($lock_msg) {
            $html .= '<h4 class="danger">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . 'GIT PULL vérouillés</h4>';
            $html .= 'Message : <b>' . $lock_msg . '</b>';

            $onclick = 'saveBimpcoreConf(\'bimpcore\', \'git_pull_lock_msg\', \'\', null, function() {bimp_reloadPage();})';

            $html .= '<div style="margin: 15px">';
            $html .= '<span class="btn btn-default" onclick="' . $onclick . '">';
            $html .= 'Dévérouiller immédiatement';
            $html .= '</span>';
            $html .= '</div>';
        }

        // Crons en erreur: 
        $rows = BimpCache::getBdb()->getRows('cronjob', '`datenextrun` < DATE_ADD(now(), INTERVAL -1 HOUR) AND status = 1', null, 'array', array('rowid', 'label'));
        if (!empty($rows)) {
            $html .= '<div class="row" style="margin-bottom: 30px">';
            $html .= '<h4 class="danger">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . count($rows) . ' tâche(s) cron en erreur</h4>';
            $html .= '<ul>';
            foreach ($rows as $r) {
                $html .= '<li>';
                $html .= '<a href="' . DOL_URL_ROOT . '/cron/card.php?id=' . $r['rowid'] . '" target="_blank">' . $r['label'] . BimpRender::renderIcon('fas_external-link-alt', 'iconRight') . '</a>';
                $html .= '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }

        // Paramètres obligatoires non définis: 
        $missings_params = BimpModuleConf::getMissingRequiredParams();
        if (!empty($missings_params)) {
            $html .= '<div class="row" style="margin-bottom: 30px">';
            $html .= '<h4 class="danger">' . BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft') . count($missings_params) . ' paramètre(s) obligatoire(s) non défini(s)</h4>';
            $html .= '<ul>';
            foreach ($missings_params as $p) {
                $html .= '<li>';
                $html .= $p;
                $html .= '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }

        // Récap logs: 
        $html .= '<div class="row">';
        $html .= '<div class="col-sm-12 col-md-8">';
        $html .= Bimp_Log::renderBeforeListContent();
        $html .= '</div>';

//        // Liens: 
//        $html .= '<div class="col-sm-12 col-md-4">';
//        $content = '';
//        foreach (self::$dev_links as $link) {
//            $content .= ($content ? '<br/><br/>' : '');
//            $content .= '<a href="' . DOL_URL_ROOT . '/' . $link[2] . '" target="_blank">';
//            $content .= BimpRender::renderIcon($link[1], 'iconLeft');
//            $content .= $link[0] . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
//            $content .= '</a>';
//        }
//        $html .= BimpRender::renderPanel('Liens utiles', $content);
//        $html .= '</div>';
        $html .= '</div>';

        if (BimpTools::isModuleDoliActif('BIMPTASK')) {
            $html .= '<div class="row">';
            $html .= '<div class="col-sm-12 col-md-6">';
            $html .= '<h3>' . BimpRender::renderIcon('fas_tasks', 'iconLeft') . 'Tâches</h3>';
            BimpObject::loadClass('bimptask', 'BIMP_Task');
            $html .= BIMP_Task::renderCounts('dev');
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div class="row">';
            $html .= '<div class="col-lg-12">';
            $list = new BC_ListTable(BimpObject::getInstance('bimptask', 'BIMP_Task'), 'main', 1, null, 'Tâches dév');
            $list->addFieldFilterValue('id_task', 0);
            //        $list->addFieldFilterValue('type_manuel', 'dev');
            $html .= $list->renderHtml();
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    public function renderModulesConfTab()
    {
        if (!$this->can('view')) {
            return BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à ce contenu', 'danger');
        }

        $html = '';

        $html .= '<div id="moduleConf">';
        $modules = BimpCache::getBimpModulesArray(true, true, 'bimpcore');

        if (empty($modules)) {
            $html .= BimpRender::renderAlerts('Aucun module installé');
        } else {
            $html .= '<div class="module_select_container">';

            if (BimpTools::isModuleDoliActif('MULTICOMPANY')) {
                $html .= '<div style="display: inline-block; vertical-align: middle">';
                $html .= '<b>Entité : </b>';
                $html .= BimpInput::renderInput('select', 'entity_select', 'bimpcore', array(
                            'options' => array(
                                'all'     => 'Toutes',
                                'current' => 'Courante',
                                'global'  => 'Globale'
                            )
                ));
                $html .= '</div>';
            }

            $html .= '<div style="display: inline-block; vertical-align: middle; margin-left: 15px">';
            $html .= '<b>Module : </b>';
            $html .= BimpInput::renderInput('select', 'module_select', 'bimpcore', array(
                        'options' => $modules
            ));
            $html .= '</div>';
            $html .= '<div style="display: inline-block; margin-left: 30px; vertical-align: middle">';
            $html .= BimpInput::renderInput('text', 'all_modules_search', '', array(
                        'extra_class' => 'all_modules_search_input',
                        'addon_left'  => BimpRender::renderIcon('fas_search')
            ));
            $html .= '<span id="all_modules_search_submit" class="btn btn-primary" onclick="BimpModuleConf.searchInAllModules();">';
            $html .= 'Rechercher';
            $html .= '</span>';
            $html .= BimpRender::renderInfoIcon('Rechercher dans tous les fichiers YML de configuration des modules <br/> ainsi que dans les paramètres enregistrés en base ne figurant dans aucun fichier de configuration YML');
            $html .= '</div>';

            $html .= '<div style="display: inline-block; float: right">';
            $html .= '<a class="btn btn-default" href="' . DOL_URL_ROOT . '/admin/modules.php' . '" target="_blank">';
            $html .= 'Config modules Dolibarr' . BimpRender::renderIcon('fas_external-link-alt', 'iconRight');
            $html .= '</a>';
            $html .= '</div>';

            $html .= '</div>';

            $html .= '<div class="all_modules_search_result_container">';
            $html .= '<div style="text-align: right">';
            $html .= '<span class="btn btn-default" onclick="BimpModuleConf.closeModulesSearchResult()">';
            $html .= BimpRender::renderIcon('fas_times', 'iconLeft') . 'Fermer';
            $html .= '</span>';
            $html .= '</div>';
            $html .= '<div class="all_modules_search_result_content"></div>';
            $html .= '</div>';

            $html .= '<div class="module_params_container">';

            $bimpModuleConf = new BimpModuleConf('bimpcore');
            $html .= $bimpModuleConf->renderFormHtml();

            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    public function renderYmlTab()
    {
        require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpYml.php';
        $html = '';

        $html .= '<div id="bimpYmlManager">';

        $html .= '<div style="margin-bottom: 10px;font-size: 14px">';
        $html .= 'Version: ';
        if (defined('BIMP_EXTENDS_VERSION')) {
            $html .= '<b>' . BIMP_EXTENDS_VERSION . '</b>';
        } else {
            $html .= '<span class="danger">Aucune</span>';
        }
        $html .= ' - Entité: ';
        if (BimpCore::getExtendsEntity() != '') {
            $html .= '<b>' . BimpCore::getExtendsEntity() . '</b>';
        } else {
            $html .= '<span class="danger">Aucune</span>';
        }
        $html .= '</div>';

        $html .= '<div class="mainToolsBar">';
        $html .= '<div class="toolsBarInput typeSelectContainer">';
        $html .= '<label>Type</label>';
        $html .= BimpInput::renderInput('select', 'yml_type_select', 'all', array(
                    'options' => array(
                        'all'         => 'Tous',
                        'objects'     => 'Objets',
                        'controllers' => 'Controllers',
                        'configs'     => 'Config modules',
                    )
        ));
        $html .= '</div>';

        $html .= '<div class="toolsBarInput moduleSelectContainer">';
        $html .= '<label>Module</label>';
        $html .= BimpInput::renderInput('select', 'yml_module_select', 'all', array(
                    'options' => BimpCache::getBimpModulesArray(false, true, 'all', 'Tous')
        ));
        $html .= '</div>';

        $html .= '<div class="toolsBarInput fileSelectContainer">';
        $html .= '<label>Fichier: </label>';
        $html .= BimpInput::renderInput('select', 'yml_file_select', '', array(
                    'options' => BimpYml::getYmlFilesArray()
        ));
        $html .= '</div>';
        $html .= '<span class="btn btn-default" onclick="BimpYMLManager.loadYmlFileManagerContent();" style="float: right">';
        $html .= BimpRender::renderIcon('fas_redo', 'iconLeft') . 'Actualiser';
        $html .= '</span>';
        $html .= '</div>';

        $html .= '<div class="fileYmlManagerContent" style="display: none"></div>';

        $html .= '</div>';

        return $html;
    }

    public function renderScriptsTabContent()
    {
        if (!$this->can('view')) {
            return BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à ce contenu', 'danger');
        }

        $files = scandir(DOL_DOCUMENT_ROOT . '/bimpcore/scripts/');

        $html = '';

        foreach ($files as $f) {
            if (in_array($f, array('.', '..'))) {
                continue;
            }

            if (preg_match('/^(.*)\.php$/', $f, $matches)) {
                $html .= '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $f . '" target="_blank">';
                $html .= $matches[1];
                $html .= '</a><br/>';
            }
        }

        return $html;
    }

    public function renderBimpThemeMenuList()
    {
        if (!$this->can('view')) {
            return BimpRender::renderAlerts('Vous n\'avez pas la permission d\'accéder à ce contenu', 'danger');
        }

        $menu = BimpObject::getInstance('bimptheme', 'Bimp_Menu');
        return $menu->renderItemsList();
    }

    // Ajax processes - Config modules: 

    public function ajaxProcessLoadModuleConfForm()
    {
        $errors = array();
        $warnings = array();
        $html = '';

        $module_name = BimpTools::getValue('module_name', '');
        $entity_type = BimpTools::getValue('entity_type', 'all');

        if (!$module_name) {
            $errors[] = 'Nom du module non spécifié';
        } else {
            $bimpModuleConf = BimpModuleConf::getInstance($module_name);
            $html .= $bimpModuleConf->renderFormHtml($entity_type);
        }

        return array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    public function ajaxProcessSaveModuleConfParam()
    {
        $errors = array();
        $warnings = array();
        $param_row = '';
        $n_errors = 0;

        $module = BimpTools::getValue('module', '');
        $param_name = BimpTools::getValue('param_name', '');
        $value = BimpTools::getValue('value', '');
        $id_entity = (int) BimpTools::getValue('id_entity', null);
        $entity_type = BimpTools::getValue('entity_type', 'all');

        if (is_null($id_entity)) {
            $errors[] = 'Entité absente ou invalide';
        }

        if (!$module) {
            $errors[] = 'Nom du module absent';
        }

        if (!$param_name) {
            $errors[] = 'Nom du paramètre absent';
        }

        if (!count($errors)) {
            $errors = BimpCore::setConf($param_name, $value, $module, $id_entity);

            if (!count($errors)) {
                $param_row = BimpModuleConf::renderParamRow($entity_type, $module, $param_name, null, $n_errors, true);
            }
        }

        return array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'param_row'  => $param_row,
            'has_errors' => ($n_errors > 0),
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessRemoveModuleConfParam()
    {
        $errors = array();
        $param_row = '';
        $n_errors = 0;

        $module = BimpTools::getValue('module', '');
        $param_name = BimpTools::getValue('param_name', '');
        $id_entity = (int) BimpTools::getValue('id_entity', null);
        $entity_type = BimpTools::getValue('entity_type', 'all');

        if (is_null($id_entity)) {
            $errors[] = 'Entité absente ou invalide';
        }

        if (!$module) {
            $errors[] = 'Nom du module absent';
        }

        if (!$param_name) {
            $errors[] = 'Nom du paramètre absent';
        }

        if (!count($errors)) {
            $errors = BimpCore::RemoveConf($param_name, $module, $id_entity);

            if (!count($errors)) {
                $param_row = BimpModuleConf::renderParamRow($entity_type, $module, $param_name, null, $n_errors, true);
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'param_row'  => $param_row,
            'has_errors' => ($n_errors > 0),
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    public function ajaxProcessSearchModulesConfParams()
    {
        return array(
            'errors'     => array(),
            'warnings'   => array(),
            'html'       => BimpModuleConf::renderSearchParamsResults(BimpTools::getValue('search', ''), BimpTools::getValue('entity_type', 'all')),
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    // Ajax processes - Gestionnaire YML: 

    public function ajaxProcessLoadYmlFilesSelect()
    {
        $errors = array();
        $html = '';

        $type = BimpTools::getValue('type', '');

        if (!$type) {
            $errors[] = 'Type absent';
        }

        $module = BimpTools::getValue('module', '');

        if (!$module) {
            $errors[] = 'Module absent';
        }

        if (!count($errors)) {
            require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpYml.php';

            $html .= '<label>Fichier: </label>';
            $html .= BimpInput::renderInput('select', 'yml_file_select', '', array(
                        'options' => BimpYml::getYmlFilesArray($type, $module, true)
            ));
        }

        return array(
            'errors'     => $errors,
            'warnings'   => array(),
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    public function ajaxProcessLoadYmlFileManagerContent()
    {
        $errors = array();
        $warnings = array();
        $html = '';

        $file_data = BimpTools::getValue('file_data', '');

        if (!$file_data) {
            $errors[] = 'Données du fichier à afficher absentes';
        } else {
            $file_data = BimpTools::json_decode_array($file_data, $errors);

            $type = BimpTools::getArrayValueFromPath($file_data, 'type', '');
            if (!$type) {
                $errors[] = 'Type de fichier absent';
            }

            $module = BimpTools::getArrayValueFromPath($file_data, 'module', '');
            if (!$module) {
                $errors[] = 'Module non spécifié';
            }

            $name = BimpTools::getArrayValueFromPath($file_data, 'name', '');
            if (!$name) {
                $errors[] = 'Nom du fichier absent';
            }
        }

        if (!count($errors)) {
            require_once DOL_DOCUMENT_ROOT . '/bimpcore/classes/BimpYml.php';
            $html = BimpYml::renderYmlFileAnalyser($type, $module, $name);
        }

        return array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }
}
