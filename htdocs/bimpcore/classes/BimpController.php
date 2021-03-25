<?php

class BimpController
{

    public $times = array();
    public $module = '';
    public $controller = '';
    public $current_tab = '';
    public $config = null;
    public $errors = array();
    public $msgs = array();
    protected $jsFiles = array();
    protected $cssFiles = array();
    public $extends = array();
    private $nbBouclePush = 2;
//    private $maxBouclePush = 40;
    private $maxBouclePush = 1;

    public static function getInstance($module, $controller = null)
    {
        $dir = DOL_DOCUMENT_ROOT . '/' . $module . '/';
        if (is_null($controller)) {
            $controller = BimpTools::getValue('fc', 'index');
        }
        if (BimpTools::getContext() == "public")
            $controller = "public_" . $controller;

        $controllerClass = $controller . 'Controller';

        if (file_exists($dir . '/controllers/' . $controllerClass . '.php')) {
            if (!class_exists($controllerClass)) {
                require_once $dir . '/controllers/' . $controllerClass . '.php';
            }
            return new $controllerClass($module, $controller);
        }
        if (BimpTools::getContext() == "public")
            return new Bimp_user_client_controller($module, $controller);
        return new BimpController($module, $controller);
    }

    public function __construct($module, $controller = 'index')
    {
        ini_set('display_errors', 0);
        error_reporting(E_ERROR);

        $this->initErrorsHandler();

        global $main_controller;

        if (is_null($main_controller)) {
            $main_controller = $this;
        }

        if (BimpDebug::isActive()) {
            BimpDebug::addDebugTime('Début controller');
        }

        $this->module = $module;
        $this->controller = $controller;

        global $user, $bimpUser;

        if (BimpObject::objectLoaded($user)) {
            $bimpUser = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_User', (int) $user->id);
        }

        $dir = DOL_DOCUMENT_ROOT . '/' . $module . '/controllers/';

        $this->current_tab = BimpTools::getValue('tab', 'default');

        $this->config = new BimpConfig($dir, $this->controller, $this);

        if ($this->config->errors) {
            $this->errors = BimpTools::merge_array($this->errors, $this->config->errors);
        }

        $this->addJsFile('/bimpcore/views/js/controller.js');

        if (file_exists(DOL_DOCUMENT_ROOT . '/' . $module . '/views/js/' . $controller . '.js')) {
            $this->addJsFile('/' . $module . '/views/js/' . $controller . '.js');
        }

        $jsFiles = $this->getConf('js', array(), false, 'array');
        foreach ($jsFiles as $jsFile) {
            $this->addJsFile($jsFile);
        }

        if (file_exists(DOL_DOCUMENT_ROOT . '/' . $module . '/views/css/' . $controller . '.css')) {
            $this->addCssFile('/' . $module . '/views/css/' . $controller . '.css');
        }

        $cssFiles = $this->getConf('css', array(), false, 'array');
        foreach ($cssFiles as $cssFile) {
            $this->addCssFile($cssFile);
        }

        $this->init();
    }

    public function init()
    {
        
    }

    public function initErrorsHandler()
    {
        if (!defined('BIMP_CONTOLLER_ERRORS_HANDLER_INIT')) {
            define('BIMP_CONTOLLER_ERRORS_HANDLER_INIT', 1);

            register_shutdown_function(array($this, 'onExit'));
            set_error_handler(array($this, 'handleError'), E_ALL);
        }
    }

    public function handleError($level, $msg, $file, $line)
    {
        ini_set('display_errors', 0); // Par précaution. 

        switch ($level) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
                if (!BimpCore::isModeDev()) {
                    global $user, $langs;
                    $txt = '';
                    $txt .= '<strong>ERP:</strong> ' . DOL_URL_ROOT . "\n";
                    $txt .= '<strong>URL:</strong> ' . $_SERVER['REQUEST_URI'] . "\n";

                    if (isset($_SERVER['HTTP_REFERER'])) {
                        $txt .= '<strong>Page:</strong> ' . $_SERVER['HTTP_REFERER'] . "\n";
                    }

                    if (is_a($user, 'User') && (int) $user->id) {
                        $txt .= '<strong>Utilisateur:</strong> ' . $user->getFullName($langs) . "\n";
                    }

                    $txt .= "\n";

                    $txt .= 'Le <strong>' . date('d / m / Y') . ' à ' . date('H:i:s') . "\n\n";
                    $txt .= $file . ' - Ligne ' . $line . "\n\n";
                    $txt .= $msg;

                    if (isset($_POST) && !empty($_POST)) {
                        $txt .= "\n\n";
                        $txt .= 'POST: ' . "\n";
                        $txt .= '<pre>' . print_r($_POST, 1) . '</pre>';
                    }

                    mailSyn2('ERREUR FATALE', "dev@bimp.fr", null, $txt);
                }

                if (strpos($msg, 'Allowed memory size') !== false) {
                    $msg = 'Mémoire dépassée (Opération trop lourde). Les administrateurs ont été alertés par e-mail';
                }

                $html = '';
                $html .= '<h2 class="danger">Erreur Fatale</h2>';
                $html .= '<strong>' . $file . '</strong> - Ligne <strong>' . $line . '</strong><br/>';

                $html .= BimpRender::renderAlerts(str_replace("\n", '<br/>', $msg));

                $html .= '<br/><br/>';

                $html .= '<div class="warning" style="font-size: 15px; text-align: center;">';
                $html .= BimpRender::renderIcon('fas_exclamation-triangle', 'iconLeft');
                $html .= 'ATTENTION: VEUILLEZ NE PAS REITERER L\'OPERATION AVANT RESOLUTION DU PROBLEME';
                $html .= '</div>';

                echo $html;

                return true;

            case E_RECOVERABLE_ERROR:
            case E_USER_ERROR:
                BimpCore::addlog($msg, Bimp_Log::BIMP_LOG_ERREUR, 'php', null, array(
                    'Fichier' => $file,
                    'Ligne'   => $line
                ));

                if (BimpDebug::isActive()) {
                    $content .= '<strong>' . $file . ' - Ligne ' . $line . '</strong>';
                    $content .= BimpRender::renderAlerts($msg, 'danger');
                    BimpDebug::addDebug('php', 'Erreur', $content, array('open' => true));
                }
                break;

            case E_WARNING:
            case E_USER_WARNING:
                global $bimpLogPhpWarnings;

                if (is_null($bimpLogPhpWarnings) || $bimpLogPhpWarnings) {
                    BimpCore::addlog($msg, Bimp_Log::BIMP_LOG_ALERTE, 'php', null, array(
                        'Fichier' => $file,
                        'Ligne'   => $line
                    ));
                }
                if (BimpDebug::isActive()) {
                    $content .= '<strong>' . $file . ' - Ligne ' . $line . '</strong>';
                    $content .= BimpRender::renderAlerts($msg, 'warning');
                    BimpDebug::addDebug('php', 'Alerte', $content, array('open' => true));
                }

                break;

            case E_NOTICE:
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                if (BimpDebug::isActive()) {
                    $content .= '<strong>' . $file . ' - Ligne ' . $line . '</strong>';
                    $content .= BimpRender::renderAlerts($msg, 'info');
                    BimpDebug::addDebug('php', 'Info', $content, array('open' => true));
                }
                break;

            default:
                return false;
        }

        return true;
    }

    public function onExit()
    {
        $error = error_get_last();

        if (isset($error['type']) && in_array($error['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR))) {
            $this->handleError(E_ERROR, $error['message'], $error['file'], $error['line']);
        } else {
            BimpConfig::saveCacheServeur();
        }
    }

    public function getConf($path, $default_value = null, $required = false, $data_type = 'string')
    {
        return $this->config->get($path, $default_value, $required, $data_type);
    }

    public function getCurrentConf($path, $default_value = null, $required = false, $data_type = 'string')
    {
        return $this->config->getFromCurrentPath($path, $default_value, $required, $data_type);
    }

    public function addJsFile($file)
    {
        if (!in_array($file, $this->jsFiles)) {
            $this->jsFiles[] = $file;
        }
    }

    public function addCssFile($file)
    {
        if (!in_array($file, $this->cssFiles)) {
            $this->cssFiles[] = $file;
        }
    }

    public function addMsg($text, $type = 'info')
    {
        $this->msgs[] = array(
            'text' => $text,
            'type' => $type
        );
    }

    public function can($right)
    {
        return 1;
    }

    // Affichages:

    public function displayHeaderFiles($echo = true)
    {
        $html = '';
        $id_object = BimpTools::getValue('id');

        $prefixe = DOL_URL_ROOT;
        if ($prefixe == "/")
            $prefixe = "";
        elseif ($prefixe != "")
            $prefixe .= "/";

        $html .= '<script type="text/javascript">';
        $html .= 'ajaxRequestsUrl = \'' . $prefixe . "/" . $this->module . '/index.php?fc=' . $this->controller . (!is_null($id_object) ? '&id=' . $id_object : '') . '\';';
        $html .= '</script>';

        $html .= BimpCore::displayHeaderFiles(false);

        foreach ($this->cssFiles as $css_file) {
            $html .= '<link type="text/css" rel="stylesheet" href="' . BimpCore::getFileUrl($css_file) . '"/>';
        }

        foreach ($this->jsFiles as $js_file) {
            $html .= '<script type="text/javascript" src="' . BimpCore::getFileUrl($js_file) . '"></script>';
        }

        $html .= '<script type="text/javascript">';
        $html .= '$(document).ready(function() {$(\'body\').trigger($.Event(\'bimp_ready\'));});';
        $html .= '</script>';
        if ($echo)
            echo $html;
        return $html;
    }

    public function display()
    {
        global $user;

        if (BimpTools::isSubmit('ajax')) {
            $this->ajaxProcess();
            return;
        }

        if ($user->id < 1) {
            die("Pas de User <a href='" . DOL_URL_ROOT . "'> Allez à la page de login</a>");
        }

        global $main_controller;

        $display_footer = false;

        if (!defined('BIMP_CONTROLLER_INIT')) {
            define('BIMP_CONTROLLER_INIT', 1);
            if (BimpDebug::isActive()) {
                BimpDebug::addDebugTime('Début affichage page');
            }

            if (!(int) $this->config->get('content_only', 0, false, 'bool')) {
                $title = '';
                if ((int) $user->id === 1) {
                    $prefix = BimpCore::getConf('pages_titles_prefix', '');
                    if ($prefix) {
                        $title = '[' . $prefix . '] ';
                    }
                }
                $title .= $this->getConf('title', '');
                llxHeader('', $title, '', false, false, false);
            }
            $display_footer = true;
        } else {
            $cssFiles = $this->getConf('css', array(), false, 'array');
            foreach ($cssFiles as $cssFile) {
                if (!is_null($main_controller) && is_a($main_controller, 'BimpController')) {
                    if (in_array($cssFile, $main_controller->cssFiles)) {
                        continue;
                    }
                }
                echo '<link type="text/css" rel="stylesheet" href="' . BimpCore::getFileUrl($cssFile) . '"/>';
            }

            $jsFiles = $this->getConf('js', array(), false, 'array');
            foreach ($jsFiles as $jsFile) {
                if (!is_null($main_controller) && is_a($main_controller, 'BimpController')) {
                    if (in_array($jsFile, $main_controller->jsFiles)) {
                        continue;
                    }
                }
                echo '<script type="text/javascript" src="' . BimpCore::getFileUrl($jsFile) . '"></script>';
            }
        }

        echo '<div class="bimp_controller_content">';
        if (count($this->errors)) {
            echo BimpRender::renderAlerts($this->errors);
            if (count($this->msgs)) {
                foreach ($this->msgs as $msg) {
                    echo BimpRender::renderAlerts($msg['text'], $msg['type']);
                }
            }
        } else {
            if (method_exists($this, 'displayHead')) {
                $this->displayHead();
            }

            if (count($this->msgs)) {
                foreach ($this->msgs as $msg) {
                    echo BimpRender::renderAlerts($msg['text'], $msg['type']);
                }
            }

            if (!$this->can("view")) {
                echo BimpRender::renderAlerts('Vous n\'avez pas la permission de voir ce contenu');
            } elseif (BimpTools::isSubmit('search')) {
                echo $this->renderSearchResults();
            } elseif (method_exists($this, 'renderHtml')) {
                echo $this->renderHtml();
            } else {
                echo $this->renderSections('sections');
            }
        }
        echo '</div>';

        if ($display_footer) {
            echo BimpRender::renderAjaxModal('page_modal');

            $html = '<div id="openModalBtn" onclick="bimpModal.show();" class="closed bs-popover"';
            $html .= BimpRender::renderPopoverData('Afficher la fenêtre popup', 'left');
            $html .= ' data-modal_id="page_modal">';
            $html .= BimpRender::renderIcon('far_window-restore');
            $html .= '</div>';

            echo $html;

            if (BimpDebug::isActive()) {
                BimpDebug::addDebugTime('Fin affichage page');

                echo BimpRender::renderAjaxModal('debug_modal', 'BimpDebugModal');

                $html = '<div id="openDebugModalBtn" onclick="BimpDebugModal.show();" class="closed bs-popover"';
                $html .= BimpRender::renderPopoverData('Afficher la fenêtre debug', 'right');
                $html .= ' data-modal_id="debug_modal">';
                $html .= BimpRender::renderIcon('fas_info-circle');
                $html .= '</div>';

                echo $html;

                echo '<div id="bimp_page_debug_content" style="display: none">';
                echo BimpDebug::renderDebug();
                echo '</div>';
            }

            llxFooter();
        }

//        echo '<pre>';
//        print_r(BimpConfig::$values_cache);
//        exit;
    }

    protected function renderSections($sections_path)
    {
        $html = '';
        $sections = $this->getConf($sections_path, null, false, 'array');

        if (!is_null($sections)) {
            $prev_path = $this->config->current_path;

            foreach ($sections as $idx => $section) {
                if ($this->config->setCurrentPath($sections_path . '/' . $idx)) {
                    $html .= $this->renderCurrentSection();
                }
            }

            $this->config->setCurrentPath($prev_path);
        }

        return $html;
    }

    protected function renderCurrentSection()
    {
        $html = '';

        $type = $this->getCurrentConf('type', '');

        switch ($type) {
            case 'tabs':
                $html .= $this->renderTabsSection();
                break;

            case 'buttons':
                $html .= $this->renderButtonsSection();
                break;

            default:
                $html .= BimpStruct::renderStruct($this->config, $this->config->current_path);
                break;
        }

        return $html;
    }

    protected function renderTabsSection()
    {
        $html = '';
        $section_path = $this->config->current_path;
        $tabs = $this->config->get($section_path . '/tabs', array(), false, 'array');

        if (empty($tabs)) {
            return $html;
        }

        if (!$this->current_tab) {
            $this->current_tab = 'default';
        }

        $h = 0;
        $head = array();

        $prev_path = $this->config->current_path;

        foreach ($tabs as $tab_name => $params) {
            $this->config->setCurrentPath($section_path . '/tabs/' . $tab_name);
            $show = $this->config->getFromCurrentPath('show', 1, false, 'bool');
            if (!$show) {
                if ($this->current_tab === $tab_name) {
                    $this->current_tab = 'default';
                }
                continue;
            }

            $url = $this->config->getFromCurrentPath('url', '');
            $href = '';
            $module = $this->config->getFromCurrentPath('module', $this->module);
            $controller = $this->config->getFromCurrentPath('controller', $this->controller);

            if (!$url) {
                $href = DOL_URL_ROOT . '/' . $module . '/index.php?fc=' . $controller;
                if ($module === $this->module && $controller === $this->controller) {
                    if (BimpTools::isSubmit('id')) {
                        $href .= '&id=' . BimpTools::getValue('id');
                    }

                    if ($tab_name && $tab_name !== 'default') {
                        $href .= '&tab=' . $tab_name;
                    }
                }
            } else {
                $href = $url;
            }

            $url_params = $this->config->getCompiledParamsfromCurrentPath('url_params');

            if (is_array($url_params)) {
                foreach ($url_params as $name => $value) {
                    $href .= '&' . $name . '=' . $value;
                }
            }

//            if (!$url && $controller === $this->controller && $module === $this->module) {
//                $href .= '#' . $tab_name;  //javascript:loadTabContent(\'' . $url . '\', \'' . $tab_name . '\')';
//            }

            $label = '';
            $icon = $this->config->getFromCurrentPath('icon', '');

            if ($icon) {
                $label .= BimpRender::renderIcon($icon, 'iconLeft');
            }

            $label .= $this->config->getFromCurrentPath('label', $tab_name, true);

            $head[$h][0] = $href;
            $head[$h][1] = $label;
            $head[$h][2] = $tab_name;
            $h++;
        }

        $this->config->setCurrentPath($prev_path);

        $tab_title = $this->config->get($section_path . 'tabs/' . $this->current_tab . '/title', '');

        dol_fiche_head($head, $this->current_tab, $tab_title);

        $html .= '<div id="controllerTabContentContainer" data-controller="' . $this->controller . '">';
        $html .= '<div class="content-loading">';
        $html .= '<div class="loading-spin"><i class="fa fa-spinner fa-spin"></i></div>';
        $html .= '<p class="loading-text">Chargement</p>';
        $html .= '</div>';

        $html .= '<div id="controllerTabContent">';
        if ($this->config->isDefined($section_path . 'tabs/' . $this->current_tab . '/sections')) {
            $html .= $this->renderSections($section_path . 'tabs/' . $this->current_tab . '/sections');
        }
        $html .= '</div>';
        $html .= '</div>';

        echo $html;
    }

    protected function renderButtonsSection()
    {
        $section_path = $this->config->current_path;

        $html = '';
        $align = $this->getCurrentConf('align', 'left');

        $html = '<div class="buttonsContainer align-' . $align . '">';
        $buttons = $this->getConf($section_path . '/buttons', array(), false, 'array');

        foreach ($buttons as $idx => $button) {
            $this->config->setCurrentPath($section_path . '/buttons/' . $idx);

            $params = array(
                'label'   => $this->getCurrentConf('label', ''),
                'classes' => $this->getCurrentConf('classes', array('btn', 'btn-default'), false, 'array'),
                'data'    => $this->getCurrentConf('data', array(), false, 'array'),
                'attr'    => array()
            );

            $icon_before = $this->getCurrentConf('icon_before');
            $icon_after = $this->getCurrentConf('icon_after');

            if (!is_null($icon_before)) {
                $params['icon_before'] = $icon_before;
            }
            if (!is_null($icon_after)) {
                $params['icon_after'] = $icon_after;
            }

            $tag = 'span';

            if (isset($button['url'])) {
                $tag = 'a';
                $params['attr']['href'] = BimpTools::makeUrlFromConfig($this->config, $section_path . '/buttons/' . $idx . '/url', $this->module, $this->controller);
            } elseif (isset($button['onclick'])) {
                $params['attr']['onclick'] = $this->getCurrentConf('onclick', '');
                $tag = 'button';
                $params['attr']['type'] = 'button';
            }

            $html .= BimpRender::renderButton($params, $tag);
        }

        $html .= '</div>';

        $this->config->setCurrentPath($section_path);

        return $html;
    }

    protected function renderSearchResults()
    {
        $errors = array();

        $object_name = BimpTools::getValue('object');
        $search_value = BimpTools::getValue('sall');
        $search_name = BimpTools::getValue('search_name', 'default');

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet non spécifié';
        }

        if (is_null($search_value) || !$search_value) {
            $errors[] = 'Aucun terme de recherche spécifié';
        }

        if (!count($errors)) {
            $object = $this->config->getObject('', $object_name);
            if (is_null($object)) {
                $errors[] = 'Type d\'objet à rechercher invalide';
            } else {
                return $object->renderSearchResults($search_value, $search_name);
            }
        }

        if (count($errors)) {
            return BimpRender::renderAlerts($errors);
        }

        return '';
    }

    public function renderTabs($fonction, $nomTabs, $params1 = null, $params2 = null)
    {
        //pour patch le chargement auto des onglet

        if (!BimpTools::isSubmit('ajax')) {
            if ($nomTabs == '' || $nomTabs == "default") {
                if (BimpTools::isSubmit('tab') && BimpTools::getValue('tab') != 'default')
                    return 'ne devrais jamais etre visible';
            }
            elseif (BimpTools::getValue('tab') != $nomTabs)
                return 'ne devrais jamais etre visible2';
        }

        if (method_exists($this, $fonction)) {
            if (isset($params2))
                return $this->$fonction($params1, $params2);
            elseif (isset($params1))
                return $this->$fonction($params1);
            else
                return $this->$fonction();
        }
        
        return 'fonction : "' . $fonction . '" inexistante';
    }

    // Traitements Ajax:

    protected function ajaxProcess()
    {
        if (BimpDebug::isActive()) {
            BimpDebug::addDebugTime('Début affichage page');
            BimpDebug::addParamsDebug();
        }

        $req_id = (int) BimpTools::getValue('request_id', 0);
        $debug_content = '';

        $errors = array();
        if (BimpTools::isSubmit('action')) {
            $action = BimpTools::getvalue('action');
            $method = 'ajaxProcess' . ucfirst($action);
            if (method_exists($this, $method)) {
                $result = $this->{$method}();

                if (!is_array($result)) {
                    BimpCore::addlog('', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', null, array(
                        'Module'     => $this->module,
                        'Controller' => $this->controller,
                        'Action'     => $action,
                        'Méthode'    => $method,
                    ));
                    $result = array();
                }

                if (!isset($result['request_id'])) {
                    $result['request_id'] = $req_id;
                }

                if (BimpDebug::isActive()) {
                    BimpDebug::addDebug('ajax_result', '', '<pre>' . htmlentities(print_r($result, 1)) . '</pre>', array('foldable' => false));
                    BimpDebug::addDebugTime('Fin affichage page');
                    $result['debug_content'] = BimpDebug::renderDebug('ajax_' . $req_id);
                }

                $json = json_encode($result);

                if ($json === false) {
                    $json_err = json_last_error_msg();
                    $json_err_code = json_last_error();

                    if ($json_err_code == JSON_ERROR_UTF8) {
                        // On tente un encodage utf-8. 
                        $result = BimpTools::utf8_encode($result);

                        $json = json_encode($result);

                        if ($json !== false) {
                            die($json);
                        }
                    }

                    BimpCore::addlog('Retour ajax: échec encodage JSON', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', null, array(
                        'Module'      => $this->module,
                        'Controller'  => $this->controller,
                        'Action'      => $action,
                        'Erreur JSON' => $json_err,
                        'Code erreur' => $json_err_code,
                        'Result'      => '<pre>' . print_r($result, 1) . '</pre>'
                    ));

                    die(json_encode(array(
                        'errors'     => array('Echec de l\'encodage JSON - ' . $json_err),
                        'request_id' => $req_id
                    )));
                }

                die($json);
            } else {
                $errors[] = 'Requête inconnue: "' . $action . '"';
            }
        } else {
            $errors[] = 'Requête invalide: Aucune action spécifiée';
        }

        $debug_content = '';

        if (BimpDebug::isActive()) {
            BimpDebug::addDebugTime('Fin affichage page');
            BimpDebug::addDebug('ajax_result', 'Erreurs', '<pre>' . htmlentities(print_r($errors, 1)) . '</pre>', array('foldable' => false));
            $debug_content = BimpDebug::renderDebug('ajax_' . $req_id);
        }

        die(json_encode(array(
            'errors'        => $errors,
            'request_id'    => $req_id,
            'debug_content' => $debug_content
        )));
    }

    // Controller:

    protected function ajaxProcessSetSessionConf()
    {
        static::setSessionConf($_REQUEST['name'], $_REQUEST['value']);
        return array('sucess' => 'lll');
    }

    public static function setSessionConf($name, $value)
    {
        $_SESSION['js_data'][$name] = $value;
    }

    public static function getSessionConf($name)
    {
        if (isset($_SESSION['js_data'][$name]))
            return $_SESSION['js_data'][$name];
        return false;
    }

    protected function ajaxProcessLoadControllerTab()
    {
        $errors = $this->errors;
        $html = '';

        if (!count($errors)) {
            if (is_null($this->current_tab) || !$this->current_tab) {
                $errors[] = 'Impossible de charger le contenu demandé : aucun onglet spécifié';
            }
            $sections = $this->getConf('sections', array(), false, 'array');

            foreach ($sections as $idx => $section) {
                if ($this->config->isDefined('sections/' . $idx . '/tabs/' . $this->current_tab . '/sections')) {
                    $html .= $this->renderSections('sections/' . $idx . '/tabs/' . $this->current_tab . '/sections');
                }
            }

            if (!$html) {
                $errors[] = 'Aucun contenu trouvé pour cet onglet';
            }
        }

        return array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessLoadFixeTabs($i = 0)
    {
        $i++;

        ignore_user_abort(0);

        $bimp_fixe_tabs = new FixeTabs();
        $bimp_fixe_tabs->init();

        $html = $bimp_fixe_tabs->render(true);

        $errors = array_merge($bimp_fixe_tabs->errors, array(/* ici recup erreur global ou message genre application ferme dans 10min */));
        $returnHtml = "";
        $hashCash = 'fixeTabsHtml' . $_POST['randomId']; //Pour ne regardé que sur l'ongelt actuel
//        session_start();
        if (!isset($_SESSION[$hashCash]) || !is_array($_SESSION[$hashCash]))
            $_SESSION[$hashCash] = array('nbBouclePush' => $this->nbBouclePush, 'html' => '');

        if (count($errors) > 0 || $i > $_SESSION[$hashCash]['nbBouclePush'] || $i > $this->maxBouclePush || $_SESSION[$hashCash]['html'] != $html) {
            if ($_SESSION[$hashCash]['html'] != $html)//On ne renvoie rien, pas de refeesh
                $returnHtml = $html;
            $_SESSION[$hashCash]['html'] = $html;
            $_SESSION[$hashCash]['nbBouclePush'] = $_SESSION[$hashCash]['nbBouclePush'] * 1.1; //Pour ne pas surchargé quand navigateur resté ouvert, mais ne pas avoir des boucle morte quand navigation rapide

            return array(
                'errors'     => $errors,
                'html'       => $returnHtml,
                'request_id' => BimpTools::getValue('request_id', 0)
            );
        }
        else {
            session_write_close(); //Pour eviter les blockages navigateur
            usleep(930000 * 2); //un tous petit peu moins d'une seconde + temps d'execution = 1s
            return $this->ajaxProcessLoadFixeTabs($i);
        }
    }

    // Enregistrements BimpObjects: 

    protected function ajaxProcessSaveObject()
    {
        $errors = array();
        $url = '';

        $id_object = BimpTools::getValue('id_object');
        $object_name = BimpTools::getValue('object_name', '');
        $object_module = BimpTools::getValue('module', $this->module);

        if (!$object_name) {
            if (empty($_POST)) {
                $errors[] = 'Echec de la reqête (aucune donnée reçue par le serveur). Si vous tentez d\'envoyer un fichier, veuillez vérifier qu\'il n\'est pas trop volumineux (> 8 Mo)';
//                BimpCore::addlog('Echec Save Object ($_POST vide)', Bimp_Log::BIMP_LOG_ALERTE, 'bimpcore');
            } else {
                $errors[] = 'Type de l\'objet à enregistrer absent';
                BimpCore::addlog('Echec Save Object (Type objet absent)', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', null, array(
                    'POST' => $_POST
                ));
            }
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($object_module, $object_name);

            if (!is_null($id_object) && (int) $id_object) {
                $object->fetch($id_object);
            }

            $result = $object->saveFromPost();

            if (!count($result['errors'])) {
                $id_object = $object->id;
                $url = BimpObject::getInstanceUrl($object);
            } else {
                $errors = $result['errors'];
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $result['warnings'],
            'object_view_url'  => $url,
            'success'          => $result['success'],
            'module'           => $object_module,
            'object_name'      => $object_name,
            'id_object'        => $id_object,
            'success_callback' => $result['success_callback'],
            'request_id'       => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessSaveObjectField()
    {
        $errors = array();
        $success = '';

        $id_object = BimpTools::getValue('id_object', null);
        $object_name = BimpTools::getValue('object_name');
        $module = BimpTools::getValue('module', $this->module);
        $field = BimpTools::getValue('field', null);
        $value = BimpTools::getValue('value', null);

        if (is_null($id_object) || !$id_object) {
            $errors[] = 'ID Absent';
        }

        if (is_null($object_name)) {
            $errors[] = 'Type d\'objet absent';
        }

        if (is_null($field) || !$field) {
            $errors[] = 'Nom du champ absent';
        }

        if (is_null($value)) {
            $errors[] = 'Valeur absente';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            if (!$object->fetch($id_object)) {
                $errors[] = BimpTools::ucfirst($object->getLabel()) . ' d\'ID ' . $id_object . ' non trouvé' . ($object->isLabelFemale() ? 'e' : '');
            } else {
                $errors = $object->set($field, $value);

                if (!count($errors)) {
                    $errors = $object->update();
                    if (!count($errors)) {
                        $field_name = $object->config->get('fields/' . $field . '/label', $field);
                        $success = 'Mise à jour du champ "' . $field_name . '" pour ' . $object->getLabel('the') . ' ' . $object->getRef() . ' effectuée avec succès';
                    }
                }
            }
        }

        return array(
            'errors'      => $errors,
            'success'     => $success,
            'module'      => $module,
            'object_name' => $object_name,
            'id_object'   => $id_object,
            'field'       => $field,
            'request_id'  => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessSaveObjectAssociations()
    {
        $errors = array();
        $success = '';

        $id_object = BimpTools::getValue('id_object');
        $object_name = BimpTools::getValue('object_name');
        $module = BimpTools::getValue('module', $this->module);
        $association = BimpTools::getValue('association');
        $list = BimpTools::getValue('list', array());

        if (is_null($id_object) || !$id_object) {
            $errors[] = 'ID Absent';
        }
        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet Absent';
        }
        if (is_null($association) || !$association) {
            $errors[] = 'Type d\'association absent';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);

            if (!is_null($id_object)) {
                $object->fetch($id_object);
            }

            if (!$object->config->setCurrentPath('associations/' . $association)) {
                $errors[] = 'Type d\'association invalide';
            } else {
                $errors = $object->saveAssociations($association, $list);
                if (!count($errors)) {
                    $asso_instance = $object->getCurrentConf('associate_object', null, true, 'object');
                    $success = 'Enregistrement de la liste des ';
                    $success .= BimpObject::getInstanceLabel($asso_instance, 'name_plur') . ' associé';

                    if (is_a($asso_instance, 'BimpObject')) {
                        if ($asso_instance->isLabelFemale()) {
                            $success .= 'e';
                        }
                    }

                    $success .= 's effectué avec succès';
                }
            }
        }

        return array(
            'errors'     => $errors,
            'success'    => $success,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessSaveObjectPosition()
    {
        $errors = array();
        $success = '';

        $list_id = BimpTools::getValue('list_id', '');
        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $id_object = BimpTools::getValue('id_object');
        $position = BimpTools::getValue('position');

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type de l\'objet absent';
        }
        if (is_null($id_object) || !$id_object) {
            $errors[] = 'ID de l\'objet absent';
        }
        if (is_null($position) || !$position) {
            $errors[] = 'Position absente ou invalide';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            if ($object->fetch((int) $id_object)) {
                if ($object->setPosition($position)) {
                    $success = 'Position ' . $object->getLabel('of_the') . ' ' . $id_object . ' mise à jour avec succès';
                } else {
                    $errors[] = 'Echec de la mise à jour de la position ' . $object->getLabel('of_the') . ' ' . $id_object;
                }
            } else {
                $errors[] = BimpTools::ucfirst($object->getLabel()) . ' d\'ID ' . $id_object . ' non trouvé' . ($object->isLabelFemale() ? 'e' : '');
            }
        }

        return array(
            'errors'     => $errors,
            'success'    => $success,
            'list_id'    => $list_id,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessAddObjectMultipleValuesItem()
    {
        $errors = array();
        $success = '';

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $id_object = BimpTools::getValue('id_object');
        $field = BimpTools::getValue('field');
        $item_value = BimpTools::getValue('item_value');

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (is_null($id_object) || !$id_object) {
            $errors[] = 'ID de l\'objet absent';
        }

        if (is_null($field) || !$field) {
            $errors[] = 'Nom du champ absent';
        }

        if (is_null($item_value) || !$item_value) {
            $errors[] = 'Valeur à enregistrer absente';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            if (!$object->fetch($id_object)) {
                $errors[] = BimpTools::ucfirst($object->getLabel()) . ' d\'ID ' . $id_object . ' non trouvé' . ($object->isLabelFemale() ? 'e' : '');
            } else {
                $result = $object->addMultipleValuesItem($field, $item_value);
                if (is_array($result)) {
                    $errors = $result;
                } elseif (is_string($result)) {
                    $success = $result;
                }
            }
        }

        return array(
            'errors'      => $errors,
            'success'     => $success,
            'module'      => $module,
            'object_name' => $object_name,
            'id_object'   => $id_object,
            'request_id'  => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessDeleteObjectMultipleValuesItem()
    {
        $errors = array();
        $success = '';

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $id_object = BimpTools::getValue('id_object');
        $field = BimpTools::getValue('field');
        $item_value = BimpTools::getValue('item_value');

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (is_null($id_object) || !$id_object) {
            $errors[] = 'ID de l\'objet absent';
        }

        if (is_null($field) || !$field) {
            $errors[] = 'Nom du champ absent';
        }

        if (is_null($item_value) || !$item_value) {
            $errors[] = 'Valeur à enregistrer absente';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            if (!$object->fetch($id_object)) {
                $errors[] = BimpTools::ucfirst($object->getLabel()) . ' d\'ID ' . $id_object . ' non trouvé' . ($object->isLabelFemale() ? 'e' : '');
            } else {
                $result = $object->deleteMultipleValuesItem($field, $item_value);
                if (is_array($result)) {
                    $errors = $result;
                } elseif (is_string($result)) {
                    $success = $result;
                }
            }
        }

        return array(
            'errors'      => $errors,
            'success'     => $success,
            'module'      => $module,
            'object_name' => $object_name,
            'id_object'   => $id_object,
            'request_id'  => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessDeleteObjects()
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $objects = BimpTools::getValue('objects', array());
        $object_name = BimpTools::getValue('object_name', null);
        $module = BimpTools::getValue('module', $this->module);

        if (!count($objects)) {
            $errors[] = 'Liste des objets à supprimer vide ou absente';
        }
        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (!count($errors)) {
            $instance = BimpObject::getInstance($module, $object_name);
            if (is_null($instance)) {
                $errors[] = 'Classe "' . $object_name . '" inexistante';
            } else {
                foreach ($objects as $id_object) {
                    $instance->reset();
                    if ($instance->fetch($id_object)) {
                        $del_warnings = array();
                        $del_errors = $instance->delete($del_warnings);
                        if (count($del_errors)) {
                            $errors[] = BimpTools::getMsgFromArray($del_errors, 'Echec de la suppression ' . $instance->getLabel('of_the') . ' d\'ID ' . $id_object);
                        }
                        if (count($del_warnings)) {
                            $warnings[] = BimpTools::getMsgFromArray($del_warnings, BimpTools::ucfirst($instance->getLabel()) . ' ' . $id_object);
                        }
                    }
                }
                if (!count($errors)) {
                    if (count($objects) > 1) {
                        $success = BimpTools::ucfirst($instance->getLabel('name_plur')) . ' supprimé';
                        if ($instance->isLabelFemale()) {
                            $success .= 'e';
                        }
                        $success .= 's';
                    } else {
                        $success = BimpTools::ucfirst($instance->getLabel('name')) . ' supprimé';
                        if ($instance->isLabelFemale()) {
                            $success .= 'e';
                        }
                    }
                    $success .= ' avec succès';
                }
            }
        }
        return array(
            'errors'       => $errors,
            'warnings'     => $warnings,
            'success'      => $success,
            'module'       => $module,
            'object_name'  => $object_name,
            'objects_list' => $objects,
            'request_id'   => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessSaveAssociations()
    {
        $errors = array();
        $done = array();

        $associations = BimpTools::getValue('associations', array());

        $operation = BimpTools::getValue('operation');
        if (is_null($operation) || !$operation) {
            $errors[] = 'Type d\'opération absent';
        } elseif (!in_array($operation, array('add', 'delete'))) {
            $errors[] = 'Type d\'opération invalide';
        }

        if (!count($associations)) {
            $errors[] = 'Aucune association indiquée';
        } else {
            foreach ($associations as $i => $association) {
                $asso_errors = array();
                $module = isset($association['module']) ? $association['module'] : '';
                $object_name = isset($association['object_name']) ? $association['object_name'] : '';
                $association_name = isset($association['association']) ? $association['association'] : '';
                $id_object = isset($association['id_object']) ? $association['id_object'] : 0;
                $id_associate = isset($association['id_associate']) ? $association['id_associate'] : 0;

                $base_msg = 'Erreur pour l\'association ' . ($i + 1) . ' - ';
                if (!$module) {
                    $asso_errors[] = $base_msg . 'module absent';
                }
                if (!$object_name) {
                    $asso_errors[] = $base_msg . 'type d\'objet absent';
                }
                if (!$association_name) {
                    $asso_errors[] = $base_msg . 'type d\'association absent';
                }
                if (!$id_object) {
                    $asso_errors[] = $base_msg . 'id de l\'objet absent';
                }
                if (!$id_associate) {
                    $asso_errors[] = $base_msg . 'id de l\'objet associé absent';
                }

                if (!count($asso_errors)) {
                    $object = BimpObject::getInstance($module, $object_name);
                    $bimpAsso = new BimpAssociation($object, $association_name);

                    switch ($operation) {
                        case 'add':
                            $asso_errors = $bimpAsso->addObjectAssociation($id_associate, $id_object);
                            break;

                        case 'delete':
                            $asso_errors = $bimpAsso->deleteAssociation($id_object, $id_associate);
                            break;
                    }

                    unset($bimpAsso);
                    unset($object);
                }

                if (count($asso_errors)) {
                    $errors = array_merge($errors, $asso_errors);
                } else {
                    $done[] = $i;
                }
            }
        }

        return array(
            'errors'     => $errors,
            'success'    => 'Associations correctement enregistrées',
            'done'       => $done,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    // Chargements BimpObjects
    // Views / Fields: 

    protected function ajaxProcessLoadObjectView()
    {
        $errors = array();
        $html = '';
        $header_html = '';
        $view_id = '';

        $id_parent = BimpTools::getValue('id_parent', null);
        if (!$id_parent) {
            $id_parent = null;
        }
        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $id_object = BimpTools::getValue('id_object', 0);
        $view_name = BimpTools::getValue('view_name', 'default');
        $content_only = BimpTools::getValue('content_only', false);
        $new_values = BimpTools::getValue('new_values', array());
        $panel = BimpTools::getValue('panel');
        $modal_idx = BimpTools::getValue('modal_idx', 0);
        $panel_header = BimpTools::getValue('panel_header');
        $modal_format = 'large';

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }
        if (is_null($id_object) || !$id_object) {
            $errors[] = 'ID de l\'objet absent';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            $object->fetch($id_object);
            $view = new BC_View($object, $view_name, $content_only, 1);
            $modal_format = $view->params['modal_format'];
            if ($modal_idx) {
                $view->addIdentifierSuffix('modal_' . $modal_idx);
            }
            $view->content_only = $content_only;
            $view->setNewValues($new_values);

            if (!is_null($panel)) {
                $view->params['panel'] = (int) $panel;
            }

            if (!is_null($panel_header)) {
                $view->params['panel_header'] = (int) $panel_header;
            }

            if ($content_only) {
                $html = $view->renderHtmlContent();
            } else {
                $html = $view->renderHtml();
            }

            $header_html = $object->renderHeader(true);
            $view_id = $view->identifier;
        }

        return array(
            'errors'       => $errors,
            'html'         => $html,
            'header_html'  => $header_html,
            'view_id'      => $view_id,
            'modal_format' => $modal_format,
            'request_id'   => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessLoadObjectFieldValue()
    {
        $errors = array();
        $value = null;

        $object_name = BimpTools::getValue('object_name');
        $id_object = BimpTools::getValue('id_object', 0);
        $module = BimpTools::getValue('module', $this->module);
        $field = BimpTools::getValue('field', $this->module);

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type de l\'objet absent';
        }
        if (is_null($id_object) || !$id_object) {
            $errors[] = 'ID de l\'objet absent';
        }
        if (is_null($field) || !$field) {
            $errors[] = 'Nom du champ absent';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            if (!$object->fetch($id_object)) {
                $errors[] = ucfirst($object->getLabel('')) . ' d\'ID ' . $id_object . ' non trouvé' . ($object->isLabelFemale() ? 'e' : '');
            }

            if (!count($errors)) {
                $value = $object->getData($field);
                if (is_null($value)) {
                    $errors[] = 'Echec de la récupération de la valeur "' . $field . '" pour ' . $object->getLabel('this');
                }
            }
        }

        return array(
            'errors'     => $errors,
            'value'      => $value,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessReloadObjectHeader()
    {
        $errors = array();
        $sucess = '';
        $html = '';

        $module = BimpTools::getValue('module', '');
        $object_name = BimpTools::getValue('object_name', '');
        $id_object = BimpTools::getValue('id_object', 0);

        if (!$module) {
            $errors[] = 'Nom du module absent';
        }
        if (!$object_name) {
            $errors[] = 'Type d\'object absent';
        }
        if (!$id_object) {
            $errors[] = 'ID de l\'objet absent';
        }

        if (!count($errors)) {
            $object = BimpCache::getBimpObjectInstance($module, $object_name, $id_object);
            if (!BimpObject::objectLoaded($object)) {
                if (!is_a($object, 'BimpObject')) {
                    $errors[] = BimpTools::ucfirst($object->getLabel('the')) . ' d\'ID ' . $id_object . ' n\'existe pas';
                } else {
                    $errors[] = 'l\'Objet de type "' . $object_name . '" d\'ID ' . $id_object . ' n\'existe pas';
                }
            } else {
                $html = $object->renderHeader(true);
            }
        }

        return array(
            'errors'     => $errors,
            'success'    => $sucess,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessLoadObjectCard()
    {
        $errors = array();
        $html = '';

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $id_object = (int) BimpTools::getValue('id_object', 0);
        $card_name = BimpTools::getValue('card_name', 'default');

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type de l\'objet absent';
        }

        if (!$id_object) {
            $errors[] = 'ID de l\'objet absent';
        }

        if (!count($errors)) {
            $object = BimpCache::getBimpObjectInstance($module, $object_name, $id_object);
            if (is_null($object)) {
                $errors[] = 'Objet non trouvé';
            } elseif (!$object->isLoaded()) {
                $errors[] = BimpTools::ucfirst($object->getLabel('the')) . ' d\'ID ' . $id_object . ' n\'existe pas';
            } else {
                $card = new BC_Card($object, null, $card_name);
                $html = $card->renderHtml();
            }
        }

        return array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessLoadObjectCustomContent()
    {
        $errors = array();
        $html = '';

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $id_object = (int) BimpTools::getValue('id_object', 0);
        $method = BimpTools::getValue('method', '');
        $params = BimpTools::getValue('params', array());

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type de l\'objet absent';
        }

        if (!$method) {
            $errors[] = 'Nom de la méthode absent';
        }

        if (!count($errors)) {
            $object = BimpCache::getBimpObjectInstance($module, $object_name, $id_object);
            if (is_null($object)) {
                $errors[] = 'Objet non trouvé';
            } elseif ($id_object && !$object->isLoaded()) {
                $errors[] = BimpTools::ucfirst($object->getLabel('the')) . ' d\'ID ' . $id_object . ' n\'existe pas';
            } elseif (!method_exists($object, $method)) {
                $errors[] = 'La méthode "' . $method . '" n\'existe pas dans l\'objet "' . BimpTools::ucfirst($object->getLabel()) . '"';
            } else {
                if (empty($params)) {
                    $html = $object->{$method}();
                } else {
                    $html = call_user_func_array(array(
                        $object, $method
                            ), $params);
                }
            }
        }

        return array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    // Forms:

    protected function ajaxProcessLoadObjectForm()
    {
        $errors = array();
        $html = '';
        $form_id = '';

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $id_object = BimpTools::getValue('id_object', 0);
        $id_parent = BimpTools::getValue('id_parent', 0);
        $form_name = BimpTools::getValue('form_name', 'default');
        $form_id = BimpTools::getValue('form_id', null);
        $full_panel = BimpTools::getValue('full_panel', false);
        $force_edit = BimpTools::getValue('force_edit', 0);
        $modal_format = 'medium';

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type de l\'objet absent';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);

            if (!is_null($id_parent)) {
                $object->setIdParent($id_parent);
            }
            if ($id_object) {
                if (!$object->fetch($id_object)) {
                    $errors[] = ucfirst($object->getLabel('')) . ' d\'ID ' . $id_object . ' non trouvé';
                }
            }

            if (!count($errors)) {
                $form = new BC_Form($object, $id_parent, $form_name, 1, !$full_panel);
                $modal_format = $form->params['modal_format'];
                if ($force_edit) {
                    $form->force_edit = true;
                }
                if (!is_null($form_id)) {
                    $form->identifier = $form_id;
                } else {
                    $form_id = $form->identifier;
                }
                if (count($form->errors)) {
                    $errors = $form->errors;
                } else {
                    $html = $form->renderHtml();
                }
            }
        }

        return array(
            'errors'       => $errors,
            'html'         => $html,
            'module'       => $module,
            'object_name'  => $object_name,
            'id_object'    => $id_object,
            'id_parent'    => $id_parent,
            'form_name'    => $form_name,
            'form_id'      => $form_id,
            'modal_format' => $modal_format,
            'request_id'   => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessLoadObjectInput()
    {
        $errors = array();
        $html = '';

        $form_id = BimpTools::getValue('form_id', '');
        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $form_name = BimpTools::getValue('form_name');
        $id_parent = BimpTools::getValue('id_parent', null);
        $field_name = BimpTools::getValue('field_name');
        $fields = BimpTools::getValue('fields', array());
        $custom_field = BimpTools::getValue('custom_field', false);
        $id_object = BimpTools::getValue('id_object', 0);
        $value = BimpTools::getValue('value', null);
        $field_prefix = BimpTools::getValue('field_prefix', '');
        $is_object = (int) BimpTools::getValue('is_object', 0);

        if ($field_prefix) {
            if (preg_match('/^' . $field_prefix . '(.*)$/', $field_name, $matches)) {
                $field_name = $matches[1];
            }
        }

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type de l\'objet absent';
        }

        if (is_null($form_id) || !$form_name) {
            $errors[] = 'Nom du formulaire absent';
        }

        if (is_null($field_name) || !$field_name) {
            $errors[] = 'Nom du champ absent';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);

            if ($id_object) {
                if (!$object->fetch($id_object)) {
                    $errors[] = ucfirst($object->getLabel('')) . ' d\'ID ' . $id_object . ' non trouvé';
                }
            } elseif (!is_null($id_parent) && $id_parent) {
                $object->setIdParent($id_parent);
            }

            if (!count($errors)) {
                if (!is_null($value) && $object->field_exists($field_name)) {
                    $object->set($field_name, $value);
                }

                foreach ($fields as $field => $field_value) {
                    $object->set($field, $field_value);
                }

                if (!empty($object->errors)) {
                    $errors = $object->errors;
                } else {
                    if ($is_object) {
                        $form = new BC_Form($object, $id_parent, $form_name, 1, true);
                        $form->fields_prefix = $field_prefix;
                        if (!is_null($form->config_path)) {
                            foreach ($form->params['rows'] as $row) {
                                if ($object->config->isDefined($form->config_path . '/rows/' . $row . '/object')) {
                                    $sub_object_name = $object->getConf($form->config_path . '/rows/' . $row . '/object', '');
                                    if ($sub_object_name && $sub_object_name === $field_name) {
                                        $row_params = BimpComponent::fetchParamsStatic($object->config, $form->config_path . '/rows/' . $row, BC_Form::$object_params);
                                        $html = $form->renderObjectRow($field_name, $row_params);
                                    }
                                }
                            }
                        }
                    } elseif ($custom_field) {
                        $form_row = BimpTools::getValue('form_row');
                        if (!is_null($form_row)) {
                            $form = new BC_Form($object, $id_parent, $form_name, 1, true);
                            $form->fields_prefix = $field_prefix;
                            if (!is_null($value)) {
                                $form->setValues(array(
                                    'fields' => array(
                                        $field_name => $value
                                    )
                                ));
                            }
                            $html = $form->renderCustomInput($form_row);
                        } else {
                            $html = BimpRender::renderAlerts('Erreur de configuration - contenu du champ personnalisé non défini');
                        }
                    } elseif ($object->config->isDefined('fields/' . $field_name)) {
                        $field = new BC_Field($object, $field_name, true);
                        $field->name_prefix = $field_prefix;
                        $field->display_card_mode = 'visible';
                        if ($field->params['type'] === 'id_object' || ($field->params['type'] === 'items_list' && $field->params['items_data_type'] === 'id_object')) {
                            if ($field->params['create_form'])
                                $html .= BC_Form::renderLoadFormObjectButton($object, $form_id, $field->params['object'], $field_prefix . $field_name, $field->params['create_form'], $field->params['create_form_values'], $field->params['create_form_label'], true);
                            if ($field->params['edit_form'])
                                $html .= BC_Form::renderLoadFormObjectButton($object, $form_id, $field->params['object'], $field_prefix . $field_name, $field->params['edit_form'], $field->params['edit_form_values'], $field->params['edit_form_label'], true, null, '', -1);
                        }
                        $html .= $field->renderInput();
                        unset($field);
                    } elseif ($object->config->isDefined('associations/' . $field_name)) {
                        $form = new BC_Form($object, $id_parent, $form_name, 1, true);
                        $bimpAsso = new BimpAssociation($object, $field_name);
                        if (count($bimpAsso->errors)) {
                            $html = BimpRender::renderAlerts($bimpAsso->errors);
                        } else {
                            $html = $bimpAsso->renderAssociatesCheckList($field_prefix);
                            // todo: remplacer 'default' par param correspondant (dans form/rows/...) 
                            $html = $bimpAsso->renderAddAssociateInput('default', false, $field_prefix, 0);
                        }
                    }
                }
            }
        }

        return array(
            'errors'      => $errors,
            'html'        => $html,
            'form_id'     => $form_id,
            'module'      => $module,
            'object_name' => $object_name,
            'id_object'   => $id_object,
            'field_name'  => $field_name,
            'request_id'  => BimpTools::getValue('request_id', 0)
        );
    }

    // Lists:

    protected function ajaxProcessLoadObjectList()
    {
        $errors = array();
        $rows_html = '';
        $pagination_html = '';
        $filters_panel_html = '';
        $active_filters_html = '';
        $thead_html = '';
        $colspan = 0;
        $id_config = 0;

        $id_parent = BimpTools::getValue('id_parent', null);
        if (!$id_parent) {
            $id_parent = null;
        }
        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $list_name = BimpTools::getValue('list_name', 'default');
        $list_id = BimpTools::getValue('list_id', null);
        $full_reload = (int) BimpTools::getValue('full_reload', 0);

        $modal_format = 'large';

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (!count($errors)) {
//            if ($full_reload) {
//                if (isset($_POST['filters_panel_values'])) {
//                    unset($_POST['filters_panel_values']);
//                }
//            }
            $object = BimpObject::getInstance($module, $object_name);
            $list = new BC_ListTable($object, $list_name, 1, $id_parent);
            $modal_format = $list->params['modal_format'];
            if (!is_null($list_id)) {
                $list->identifier = $list_id;
            }
            if (BimpTools::isSubmit('new_values')) {
                $list->setNewValues(BimpTools::getValue('new_values', array()));
            }
            if (BimpTools::isSubmit('selected_rows')) {
                $list->setSelectedRows(BimpTools::getValue('selected_rows', array()));
            }
            $rows_html = $list->renderRows();
            $pagination_html = $list->renderPagination();
            $filters_panel_html = $list->renderFiltersPanel();
            $active_filters_html = $list->renderActiveFilters(true);
            $before_html = $list->getBeforeListContent();
            $after_html = $list->getAfterListContent();
            if ($full_reload) {
                $thead_html .= $list->renderHeaderRow();
                $thead_html .= $list->renderSearchRow();
                $thead_html .= $list->renderAddObjectRow();
            }

            if (BimpObject::objectLoaded($list->userConfig)) {
                $id_config = (int) $list->userConfig->id;
            }

            $colspan = $list->colspan;

            if (count($list->errors)) {
                BimpCore::addlog('Erreur génération liste', Bimp_Log::BIMP_LOG_ERREUR, 'bimpcore', $object, array(
                    'Nom liste' => $list_name,
                    'Erreurs'   => $errors
                ));
            }
        }

        return array(
            'errors'              => $errors,
            'rows_html'           => $rows_html,
            'pagination_html'     => $pagination_html,
            'filters_panel_html'  => $filters_panel_html,
            'active_filters_html' => $active_filters_html,
            'before_html'         => $before_html,
            'after_html'          => $after_html,
            'thead_html'          => $thead_html,
            'list_id'             => $list_id,
            'colspan'             => $colspan,
            'id_config'           => $id_config,
            'request_id'          => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessLoadObjectListFullPanel()
    {
        $errors = array();
        $html = '';
        $list_id = '';

        $id_parent = BimpTools::getValue('id_parent', null);
        if (!$id_parent) {
            $id_parent = null;
        }
        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $list_name = BimpTools::getValue('list_name', 'default');
        $extra_filters = BimpTools::getValue('extra_filters', array());
        $extra_joins = BimpTools::getValue('extra_joins', array());

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            $list = new BC_ListTable($object, $list_name, 1, $id_parent);

            if ($extra_filters) {
                foreach ($extra_filters as $name => $filter) {
                    $list->addFieldFilterValue($name, $filter);
                }
            }

            if ($extra_joins) {
                foreach ($extra_joins as $join) {
                    $list->addJoin($join['table'], $join['on'], $join['alias']);
                }
            }

            $html = $list->renderHtml();
            $list_id = $list->identifier;
        }

        return array(
            'errors'     => $errors,
            'html'       => $html,
            'list_id'    => $list_id,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessLoadObjectListCustom()
    {
        $errors = array();
        $html = '';
        $filters_panel_html = '';

        $id_parent = BimpTools::getValue('id_parent', null);
        if (!$id_parent) {
            $id_parent = null;
        }

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $list_name = BimpTools::getValue('list_name', 'default');
        $list_id = BimpTools::getValue('list_id', null);
        $modal_format = 'large';

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            $list = new BC_ListCustom($object, $list_name, $id_parent);
            $modal_format = $list->params['modal_format'];
            if (!is_null($list_id)) {
                $list->identifier = $list_id;
            }
            $html = $list->renderListContent();
            $filters_panel_html = $list->renderFiltersPanel();
        }

        return array(
            'errors'             => $errors,
            'html'               => $html,
            'filters_panel_html' => $filters_panel_html,
            'list_id'            => $list_id,
            'modal_format'       => $modal_format,
            'request_id'         => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessLoadObjectNotes()
    {
        $errors = array();

        $html = '';

        $id_parent = BimpTools::getValue('id_parent', null);
        if (!$id_parent) {
            $id_parent = null;
        }

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $id_object = (int) BimpTools::getValue('id_object');
        $filter_by_user = (int) BimpTools::getValue('filter_by_user', 1);
        $list_model = BimpTools::getValue('list_model', 'default');

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (!$id_object) {
            $errors[] = 'ID de l\'objet absent';
        }

        if (!count($errors)) {
            $object = BimpCache::getBimpObjectInstance($module, $object_name, $id_object);
            $html = $object->renderHeader();
            $html .= $object->renderNotesList($filter_by_user, $list_model);
        }

        return array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessSetFilteredListObjectsAction()
    {
        $errors = array();
        $warnings = array();
        $success = '';
        $success_callback = '';

        $action = BimpTools::getValue('action_name', '');
        $extra_data = BimpTools::getValue('extra_data', array());

        if (!$action) {
            $errors[] = 'Nom de l\'action absent';
        } else {
            $id_parent = BimpTools::getValue('id_parent', null);
            if (!$id_parent) {
                $id_parent = null;
            }
            $module = BimpTools::getValue('module', $this->module);
            $object_name = BimpTools::getValue('object_name');
            $list_name = BimpTools::getValue('list_name', 'default');
            $list_id = BimpTools::getValue('list_id', null);

            if (is_null($object_name) || !$object_name) {
                $errors[] = 'Type d\'objet absent';
            } else {
                $object = BimpObject::getInstance($module, $object_name);
                $list = new BC_ListTable($object, $list_name, 1, $id_parent);
                if (!is_null($list_id)) {
                    $list->identifier = $list_id;
                }

                if (!$list->isOk()) {
                    $errors = array_merge($errors, $list->errors);
                } else {
                    $id_objects = array();

                    $primary = $object->getPrimary();
                    foreach ($list->getItems() as $item_data) {
                        if (isset($item_data[$primary]) && (int) $item_data[$primary]) {
                            $id_objects[] = (int) $item_data[$primary];
                        }
                    }

                    if (empty($id_objects)) {
                        $warnings[] = 'Aucun' . $object->e() . ' ' . $object->getLabel() . ' trouvé' . $object->e() . ' avec ces critères de recherches';
                    } else {
                        $extra_data['id_objects'] = $id_objects;

                        $result = $object->setObjectAction($action, 0, $extra_data, $success);

                        if (is_array($result)) {
                            if (isset($result['errors']) || isset($result['warnings']) || isset($result['success_callback'])) {
                                if (isset($result['errors']) && is_array($result['errors'])) {
                                    $errors = array_merge($errors, $result['errors']);
                                }
                                if (isset($result['warnings']) && is_array($result['warnings'])) {
                                    $warnings = array_merge($warnings, $result['warnings']);
                                }
                                if (isset($result['success_callback'])) {
                                    $success_callback = $result['success_callback'];
                                }
                            } else {
                                $errors = array_merge($errors, $result);
                            }
                        }
                    }
                }
            }
        }

        return array(
            'errors'           => $errors,
            'warnings'         => $warnings,
            'success'          => $success,
            'success_callback' => $success_callback,
            'request_id'       => BimpTools::getValue('request_id', 0)
        );
    }

    // Views Lists: 

    protected function ajaxProcessLoadObjectViewsList()
    {
        $errors = array();
        $html = '';
        $views_list_id = '';

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $views_list_name = BimpTools::getValue('views_list_name');
        $modal_format = 'large';

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (is_null($views_list_name) || !$views_list_name) {
            $errors[] = 'Type de liste de vues absent';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            $bimpViewsList = new BC_ListViews($object, $views_list_name);
            $modal_format = $bimpViewsList->params['modal_format'];
            $html = $bimpViewsList->renderItemViews();
            $views_list_id = $bimpViewsList->identifier;
        }

        return array(
            'errors'        => $errors,
            'html'          => $html,
            'views_list_id' => $views_list_id,
            'modal_format'  => $modal_format,
            'request_id'    => BimpTools::getValue('request_id', 0)
        );
    }

    // Stats Lists: 

    protected function ajaxProcessLoadObjectStatsList()
    {
        $errors = array();
        $html = '';
        $filters_panel_html = '';
        $active_filters_html = '';

        $id_parent = BimpTools::getValue('id_parent', null);
        if (!$id_parent) {
            $id_parent = null;
        }

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $list_name = BimpTools::getValue('list_name', 'default');
        $list_id = BimpTools::getValue('list_id', null);
        $modal_format = 'large';

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            $list = new BC_StatsList($object, $list_name, $id_parent);
            $modal_format = $list->params['modal_format'];
            if (!is_null($list_id)) {
                $list->identifier = $list_id;
            }
            $html = $list->renderListContent();
            $filters_panel_html = $list->renderFiltersPanel();
            $active_filters_html = $list->renderActiveFilters(true);
            if (!empty($list->errors)) {
                $errors = BimpTools::getMsgFromArray($list->errors, 'Echec du rechargement de la liste');
            }
        }

        return array(
            'errors'              => $errors,
            'html'                => $html,
            'filters_panel_html'  => $filters_panel_html,
            'active_filters_html' => $active_filters_html,
            'list_id'             => $list_id,
            'modal_format'        => $modal_format,
            'request_id'          => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessLoadObjectStatsListRows()
    {
        $errors = array();
        $rows_html = '';
        $pagination_html = '';
        $filters_panel_html = '';
        $active_filters_html = '';

        $id_parent = BimpTools::getValue('id_parent', null);
        if (!$id_parent) {
            $id_parent = null;
        }

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $list_name = BimpTools::getValue('list_name', 'default');
        $list_id = BimpTools::getValue('list_id', null);
        $modal_format = 'large';

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            $list = new BC_StatsList($object, $list_name, $id_parent);
            $modal_format = $list->params['modal_format'];
            if (!is_null($list_id)) {
                $list->identifier = $list_id;
            }

            $rows_html = $list->renderListContent(true);
            $filters_panel_html = $list->renderFiltersPanel();
            $active_filters_html = $list->renderActiveFilters(true);
            $pagination_html = $list->renderPagination();
            $errors = $list->errors;
        }

        return array(
            'errors'              => $errors,
            'rows_html'           => $rows_html,
            'filters_panel_html'  => $filters_panel_html,
            'active_filters_html' => $active_filters_html,
            'pagination_html'     => $pagination_html,
            'list_id'             => $list_id,
            'modal_format'        => $modal_format,
            'request_id'          => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessLoadObjectSubStatsList()
    {
        $errors = array();
        $html = '';
        $list_id = '';
        $pagination_html = '';

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $list_name = BimpTools::getValue('list_name', 'default');
        $sub_list_filters = BimpTools::getValue('sub_list_filters', array());
        $sub_list_joins = BimpTools::getValue('sub_list_joins', array());
        $title = BimpTools::getValue('sub_list_title', '');
        $rows_only = (int) BimpTools::getValue('rows_only', 0);
        $stat_list_id = BimpTools::getValue('stats_list_id', '');

        if ($title) {
            $title = 'Détails par ' . $title;
        } else {
            $title = 'Détails';
        }

        $group_by_idx = (int) BimpTools::getValue('group_by_index', 0);

        $id_parent = BimpTools::getValue('id_parent', null);
        if (!$id_parent) {
            $id_parent = null;
        }

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (!$stat_list_id) {
            $errors[] = 'Identifiant de la liste principale absent';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            $list = new BC_StatsList($object, $list_name, $id_parent, null, null, null, $group_by_idx, $sub_list_filters, $sub_list_joins);
            $list->base_list_id = $stat_list_id;

            if (!$rows_only) {
                $html .= '<div class="subStatsListTitle">' . $title . '</div>';
                $html .= '<div class="subStatsListContent">';
            }

            $html .= $list->renderListContent($rows_only);

            if (!$rows_only) {
                $html .= '</div>';
            }


            $list_id = $list->identifier;
            $errors = $list->errors;

            if ($rows_only) {
                $pagination_html = $list->renderPagination();
            }
        }

        return array(
            'errors'          => $errors,
            'html'            => $html,
            'list_id'         => $list_id,
            'pagination_html' => $pagination_html,
            'request_id'      => BimpTools::getValue('request_id', 0)
        );
    }

    // Traitements BimpObjects: 

    protected function ajaxProcessSetObjectNewStatus()
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name', '');
        $id_object = BimpTools::getValue('id_object', 0);

        $status = BimpTools::getValue('new_status');
        $extra_data = BimpTools::getValue('extra_data', array());

        if (!$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (!$id_object) {
            $errors[] = 'ID de l\'objet absent';
        }

        if (is_null($status)) {
            $errors[] = 'Nouveau statut non spécifié';
        }

        if (!count($errors)) {
            if (is_array($id_object)) {
                foreach ($id_object as $id) {
                    $object = BimpCache::getBimpObjectInstance($module, $object_name, (int) $id);
                    if (!BimpObject::objectLoaded($object)) {
                        if (is_a($object, 'BimpObject')) {
                            $warnings[] = BimpTools::ucfirst($object->getLabel('the') . ' d\'ID ' . $id . ' n\'existe pas');
                        } else {
                            $warnings[] = 'L\'objet d\'ID ' . $id . ' n\'existe pas';
                        }
                    } else {
                        if (!$success) {
                            $success = 'Mise à jour des statuts des ' . $object->getLabel('name_plur') . ' effectuée avec succès';
                        }
                        $obj_errors = $object->setNewStatus($status, $extra_data, $warnings);
                        if (count($obj_errors)) {
                            $warnings[] = BimpTools::getMsgFromArray($obj_errors, 'Echec de la mise à jour du statut ' . $object->getLabel('of_the') . ' d\'ID ' . $id);
                        }
                    }
                }
            } else {
                if (!(int) $id_object) {
                    $errors[] = 'ID absent';
                } else {
                    $object = BimpCache::getBimpObjectInstance($module, $object_name, (int) $id_object);
                    if (!BimpObject::objectLoaded($object)) {
                        $errors[] = 'ID de l\'objet invalide';
                    } else {
                        $errors = $object->setNewStatus($status, $extra_data, $warnings);
                        $success = 'Mise à jour du statut ' . $object->getLabel('of_the') . ' ' . $object->id . ' effectué avec succès';
                    }
                }
            }
        }

        return array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'success'    => $success,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessSetObjectAction()
    {
        $errors = array();
        $success = '';

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name', '');
        $id_object = (int) BimpTools::getValue('id_object', 0);

        $object_action = BimpTools::getValue('object_action');
        $extra_data = BimpTools::getValue('extra_data', array());

        if (!$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (is_null($object_action)) {
            $errors[] = 'Type d\'action absent';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            if (is_null($object)) {
                $errors[] = 'Type d\'objet invalide';
            } else {
                $errors = $object->setObjectAction($object_action, $id_object, $extra_data, $success);
            }
        }

        $return = array(
            'success'    => $success,
            'request_id' => BimpTools::getValue('request_id', 0)
        );

        if (is_array($errors)) {
            if (array_key_exists('errors', $errors)) {
                foreach ($errors as $key => $value) {
                    $return[$key] = $value;
                }
            } else {
                $return['errors'] = $errors;
            }
        }

        return $return;
    }

    protected function ajaxProcessSearchObjectlist()
    {
        $list = array();

        $table = BimpTools::getValue('table');
        $fields_search = explode(',', BimpTools::getValue('fields_search'));
        $fields_return_label = BimpTools::getValue('field_return_label', '');
        $label_syntaxe = html_entity_decode(BimpTools::getValue('label_syntaxe', '<label_1>'));
        $fields_return_value = BimpTools::getValue('field_return_value', '');
        $filters = BimpTools::getValue('filters', '');
        $join = BimpTools::getValue('join', '');
        $join_return_label = BimpTools::getValue('join_return_label', '');
        $join_on = BimpTools::getValue('join_on', '');
        $values = explode(' ', BimpTools::getValue('value', ''));

        if (is_string($filters)) {
            if ($filters) {
                $filters = json_decode($filters, 1);
            } else {
                $filters = array();
            }
        } elseif (!is_array($filters)) {
            $filters = array();
        }

        if (!is_null($table) && !is_null($fields_return_label) && !is_null($fields_return_value) && count($fields_search) && !empty($values)) {
            global $db;
            $bdb = new BimpDb($db);

            if (preg_match('/^.* ([a-z]+)$/', $table, $matches)) {
                $alias = $matches[1];
            } else {
                $table .= ' a';
                $alias = 'a';
            }

            $sql = 'SELECT ';
            $fields_return_label = explode(',', $fields_return_label);
            $i = 1;
            foreach ($fields_return_label as $field_label) {
                if (!preg_match('/\./', $field_label)) {
                    $field_label = $alias . '.' . $field_label;
                }
                $sql .= $field_label . ' as label_' . $i . ', ';
                $i++;
            }

            if (!preg_match('/\./', $fields_return_value)) {
                $fields_return_value = $alias . '.' . $fields_return_value;
            }

            $sql .= $fields_return_value . ' as value';

            if ($join_return_label) {
                $sql .= ', ' . $join_return_label . ' as join_label';
            }

            $sql .= ' FROM ' . MAIN_DB_PREFIX . $table;

            if ($join && $join_on) {
                $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . $join;
                $sql .= ' ON ' . $join_on;
            }

            $where = '';
            $fl = true;
            foreach ($fields_search as $field) {
                if (!$fl) {
                    $where .= ' OR ';
                } else {
                    $fl = false;
                }
                if (!preg_match('/\./', $field)) {
                    $field = $alias . '.' . $field;
                }
                $val_fl = true;
                $where .= '(';
                foreach ($values as $value) {
                    if (!$val_fl) {
                        $where .= ' AND ';
                    } else {
                        $val_fl = false;
                    }
                    $where .= 'LOWER(' . $field . ')' . ' LIKE \'%' . strtolower(addslashes($value)) . '%\'';
                }
                $where .= ')';
            }

            if ($where) {
                $sql .= ' WHERE (' . $where . ')';
            }

            if (count($filters)) {
                foreach ($filters as $field => $filter) {
                    $sql .= ' AND ' . BimpTools::getSqlFilter($field, $filter);
                }
            }

            $sql .= ' LIMIT 15';

//            echo $sql;
//            exit;

            $rows = $bdb->executeS($sql, 'array');

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $label = $label_syntaxe;
                    for ($n = 1; $n <= count($fields_return_label); $n++) {
                        $label = str_replace('<label_' . $n . '>', $r['label_' . $n], $label);
                    }
                    $list[] = array(
                        'value'      => $r['value'],
                        'label'      => $label,
                        'join_label' => (isset($r['join_label']) ? $r['join_label'] : '')
                    );
                }
            }
        }

        return array(
            'errors'     => array(),
            'list'       => $list,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessGetSearchObjectResults()
    {
        $results = array();

        $module = BimpTools::getValue('module', '');
        $object_name = BimpTools::getValue('object_name', '');
        $search_name = BimpTools::getValue('search_name', 'default');
        $search_value = BimpTools::getValue('search_value', '');
        $max_results = (int) BimpTools::getValue('max_results', 200);
        $card = BimpTools::getValue('card', '');

        if ($module && $object_name && $search_value) {
            $instance = BimpObject::getInstance($module, $object_name);

            $results = $instance->getSearchResults($search_name, $search_value, array(
                'max_results' => $max_results,
                'card'        => $card
            ));
        }

        return array(
            'results'    => $results,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    // Gestion des listes: 

    protected function ajaxProcessLoadUserListFiltersList()
    {
        $errors = array();
        $html = '';
        $list_id = '';

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $panel_name = BimpTools::getValue('panel_name', 'default');
        $id_user = (int) BimpTools::getValue('id_user', 0);

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        } else {
            $object = BimpObject::getInstance($module, $object_name);

            if (!is_a($object, $object_name)) {
                $errors[] = 'L\'objet "' . $object_name . '" n\'existe pas dans le module "' . $module . '"';
            }
        }

        if (!$id_user) {
            $errors[] = 'ID de l\'utilisateur absent';
        }

        if (!count($errors)) {
            $instance = BimpObject::getInstance('bimpcore', 'ListFilters');
            $instance->validateArray(array(
                'obj_module' => $module,
                'obj_name'   => $object_name,
                'panel_name' => $panel_name
            ));

            $list = new BC_ListTable($instance);

            $list->addFieldFilterValue('obj_module', $module);
            $list->addFieldFilterValue('obj_name', $object_name);
            $list->addFieldFilterValue('panel_name', $panel_name);

            $list->params['add_form_values']['fields']['owner_type'] = 2;
            $list->params['add_form_values']['fields']['id_owner'] = $id_user;

            $list->params['list_filters'][] = array(
                'name'   => 'owner',
                'filter' => array(
                    'custom' => ListFilters::getOwnerFilterCustomSql($id_user)
                )
            );

            $html = $list->renderHtml();
            $list_id = $list->identifier;
        }

        return array(
            'errors'     => $errors,
            'html'       => $html,
            'list_id'    => $list_id,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessLoadFiltersPanelConfig()
    {
        $errors = array();
        $html = '';

        $module = BimpTools::getValue('module', '');
        $object_name = BimpTools::getValue('object_name', '');
        $list_type = BimpTools::getValue('list_type', '');
        $list_name = BimpTools::getValue('list_name', 'default');
        $list_identifier = BimpTools::getValue('list_identifier', '');
        $panel_name = BimpTools::getValue('panel_name', 'default');
        $id_list_filters = (int) BimpTools::getValue('id_list_filters', 0);
        $filters = BimpTools::getValue('filters_panel_values', array());

        if ($module && $object_name) {
            $object = BimpObject::getInstance($module, $object_name);
            $bc_filters = new BC_FiltersPanel($object, $list_type, $list_name, $list_identifier, $panel_name);
            $bc_filters->setFilters($filters);
            $bc_filters->id_list_filters = $id_list_filters;
            $html = $bc_filters->renderHtmlContent();
        } else {
            $errors[] = 'Echec du chargement des filtres enregistrés. Certains paramètres obligatoires sont absents';
        }

        return array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessLoadSavedListFilters()
    {
        $errors = array();
        $html = '';

        $module = BimpTools::getValue('module', '');
        $object_name = BimpTools::getValue('object_name');
        $list_type = BimpTools::getValue('list_type', '');
        $list_name = BimpTools::getValue('list_name', 'default');
        $list_identifier = BimpTools::getValue('list_identifier', '');
        $panel_name = BimpTools::getValue('panel_name', 'default');
        $id_list_filters = (int) BimpTools::getValue('id_list_filters', 0);
        $id_filters_config = (int) BimpTools::getValue('id_filters_config', 0);
        $full_panel_html = (int) BimpTools::getValue('full_panel_html', 1);

        if ($module && $object_name && $list_type && $list_identifier && $id_list_filters) {
            $object = BimpObject::getInstance($module, $object_name);
            $bc_filters = new BC_FiltersPanel($object, $list_type, $list_name, $list_identifier, $panel_name, $id_filters_config);

            if ($full_panel_html) {
                $errors = $bc_filters->loadSavedValues($id_list_filters);
                $html = $bc_filters->renderHtml();
            } else {
                $bc_filters->id_list_filters = $id_list_filters;
                $html = $bc_filters->renderSavedFilters();
            }
        } else {
            $errors[] = 'Echec du chargement des filtres enregistrés. Certains paramètres obligatoires sont absents';
        }

        return array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    // Gestion des configs utilisateur: 

    protected function ajaxProcessLoadUserConfigsList()
    {
        $errors = array();
        $html = '';
        $list_id = '';

        $config_object_name = BimpTools::getValue('config_object_name', '');
        $config_filters = BimpTools::getValue('config_filters', array());
        $id_user = (int) BimpTools::getValue('id_user', 0);

        if (!$config_object_name) {
            $errors[] = 'Type de configuration absent';
        }

        if (empty($config_filters)) {
            $errors[] = 'Filtres du type de configuration absents';
        }

        if (!$id_user) {
            $errors[] = 'ID de l\'utilisateur absent';
        }

        if (!count($errors)) {
//            $object = BimpObject::getInstance($module, $object_name);
            $userConfig = BimpObject::getInstance('bimpuserconfig', $config_object_name);
            $list = new BC_ListTable($userConfig);

            foreach ($config_filters as $field_name => $value) {
                if ($userConfig->field_exists($field_name)) {
                    $list->addFieldFilterValue($field_name, $value);
                    $userConfig->set($field_name, $value);
                }
            }

            $default_values = $userConfig->getConfigDefaultValues();

            foreach ($default_values as $field_name => $value) {
                $list->params['add_form_values']['fields'][$field_name] = $value;
            }

            $list->params['list_filters'][] = array(
                'name'   => 'owner_custom',
                'filter' => array(
                    'custom' => UserConfig::getOwnerSqlFilter($id_user, 'a', true)
                )
            );

            $html = $list->renderHtml();
            $list_id = $list->identifier;
        }

        return array(
            'errors'     => $errors,
            'html'       => $html,
            'list_id'    => $list_id,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    // Divers: 

    protected function ajaxProcessLoadProductStocks()
    {
        $errors = array();
        $html = '';

        $id_product = (int) BimpTools::getValue('id_product', 0);
        $id_entrepot = (int) BimpTools::getValue('id_entrepot', 0);

        if (!$id_product) {
            $errors[] = 'ID du produit absent';
        } else {
            $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
            if (!BimpObject::objectLoaded($product)) {
                $errors[] = 'Produit d\'ID ' . $id_product . ' inexistant';
            } else {
                $html = $product->renderStocksByEntrepots($id_entrepot);
            }
        }


        return array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    protected function ajaxProcessSearchZipTown()
    {
        $errors = array();
        $html = '';

        $zip = BimpTools::getValue('zip', '');
        $town = BimpTools::getValue('town', '');

        if ($zip || $town) {
            global $db;
            $bdb = new BimpDb($db);

            $fields = array(
                'zip', 'town', 'fk_pays', 'fk_county'
            );

            $where = '`active` = 1';
            if ($zip) {
                $where .= ' AND `zip` LIKE \'' . $db->escape($zip) . '%\'';
            }
            if ($town) {
                $where .= ' AND `town` LIKE \'%' . $db->escape($town) . '%\'';
            }

            $rows = $bdb->getRows('c_ziptown', $where, 100, 'array', $fields, ($zip ? 'zip' : 'town'), 'asc');

            if (!is_null($rows)) {
                foreach ($rows as $r) {
                    $html .= '<div>';
                    $html .= '<span class="btn btn-light-default" onclick="selectZipTown($(this))"';
                    $html .= ' data-town="' . $r['town'] . '"';
                    $html .= ' data-zip="' . $r['zip'] . '"';
                    $html .= ' data-state="' . $r['fk_county'] . '"';
                    $html .= ' data-country="' . $r['fk_pays'] . '"';
                    $html .= '>';
                    $html .= $r['zip'] . ' - ' . $r['town'];
                    $html .= '</span>';
                    $html .= '</div>';
                }
            }
        }

        return array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        );
    }

    // Callbacks:

    protected function getObjectIdFromPost($object_name)
    {
        return BimpTools::getValue('id_' . $object_name, null);
    }

    protected function ajaxProcessGetNotification()
    {
        global $user;
        $errors = array();

        $notifs = BimpTools::getPostFieldValue('notificationActive');


        $notification = BimpCache::getBimpObjectInstance('bimpcore', 'BimpNotification');
        $notifs_for_user = $notification->getNotificationForUser((int) $user->id, $notifs, $errors);

        return array(
            'errors'        => $errors,
            'notifications' => $notifs_for_user,
            'request_id'    => BimpTools::getValue('request_id', 0)
        );
    }
}
