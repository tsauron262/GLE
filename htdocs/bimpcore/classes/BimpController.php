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
    private $maxBouclePush = 40;

    public static function getInstance($module)
    {
        $dir = DOL_DOCUMENT_ROOT . '/' . $module . '/';
        $controller = BimpTools::getValue('fc', 'index');
        $controllerClass = $controller . 'Controller';

        if (file_exists($dir . '/controllers/' . $controllerClass . '.php')) {
            if (!class_exists($controllerClass)) {
                require_once $dir . '/controllers/' . $controllerClass . '.php';
            }
            return new $controllerClass($module, $controller);
        }
        return new BimpController($module, $controller);
    }

    public function __construct($module, $controller = 'index')
    {
        global $main_controller;

        if (is_null($main_controller)) {
            $main_controller = $this;
        }

        $this->addDebugTime('Début controller');
        $this->module = $module;
        $this->controller = $controller;

        global $user, $bimpUser;
        $bimpUser = BimpObject::getInstance('bimpcore', 'Bimp_User');
        if (BimpObject::objectLoaded($user)) {
            $bimpUser->fetch((int) $user->id);
        }

        $dir = DOL_DOCUMENT_ROOT . '/' . $module . '/controllers/';

        $this->current_tab = BimpTools::getValue('tab', 'default');

        $this->config = new BimpConfig($dir, $this->controller, $this);

        if ($this->config->errors) {
            $this->errors = array_merge($this->errors, $this->config->errors);
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

    public function addDebugTime($label)
    {
//        echo $label . '<br/>';
        $this->times[] = array(
            'label' => $label,
            'time'  => round(microtime(1), 4)
        );
    }

    // Affichages:
    public function displayHeaderFiles()
    {
        $id_object = BimpTools::getValue('id');

        echo '<script type="text/javascript">';
        echo 'ajaxRequestsUrl = \'' . DOL_URL_ROOT . '/' . $this->module . '/index.php?fc=' . $this->controller . (!is_null($id_object) ? '&id=' . $id_object : '') . '\';';
        echo '</script>';

        BimpCore::displayHeaderFiles();

        foreach ($this->cssFiles as $css_file) {
            echo '<link type="text/css" rel="stylesheet" href="' . DOL_URL_ROOT . '/' . $css_file . '"/>';
        }

        foreach ($this->jsFiles as $js_file) {
            echo '<script type="text/javascript" src="' . DOL_URL_ROOT . '/' . $js_file . '"></script>';
        }

        echo '<script type="text/javascript">';
        echo '$(document).ready(function() {$(\'body\').trigger($.Event(\'bimp_ready\'));});';
        echo '</script>';
    }

    public function display()
    {
        if (BimpTools::isSubmit('ajax')) {
            $this->ajaxProcess();
            return;
        }

        global $main_controller;

        $display_footer = false;

        if (!defined('BIMP_CONTROLLER_INIT')) {
            define('BIMP_CONTROLLER_INIT', 1);
            $this->addDebugTime('Début affichage page');
            llxHeader('', '', '', false, false, false);
            $display_footer = true;
        } else {
            $cssFiles = $this->getConf('css', array(), false, 'array');
            foreach ($cssFiles as $cssFile) {
                if (!is_null($main_controller) && is_a($main_controller, 'BimpController')) {
                    if (in_array($cssFile, $main_controller->cssFiles)) {
                        continue;
                    }
                }
                echo '<link type="text/css" rel="stylesheet" href="' . DOL_URL_ROOT . '/' . $cssFile . '"/>';
            }

            $jsFiles = $this->getConf('js', array(), false, 'array');
            foreach ($jsFiles as $jsFile) {
                if (!is_null($main_controller) && is_a($main_controller, 'BimpController')) {
                    if (in_array($jsFile, $main_controller->jsFiles)) {
                        continue;
                    }
                }
                echo '<script type="text/javascript" src="' . DOL_URL_ROOT . '/' . $jsFile . '"></script>';
            }
        }

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
            } else {
                $title = $this->getConf('title', '');
                if ($title) {
                    print load_fiche_titre($title, '', 'title_generic.png');
                }
            }

            if (count($this->msgs)) {
                foreach ($this->msgs as $msg) {
                    echo BimpRender::renderAlerts($msg['text'], $msg['type']);
                }
            }

            if (!$this->canView()) {
                echo BimpRender::renderAlerts('Vous n\'avez pas la permission de voir ce contenu');
            } elseif (BimpTools::isSubmit('search')) {
                echo $this->renderSearchResults();
            } elseif (method_exists($this, 'renderHtml')) {
                echo $this->renderHtml();
            } else {
                echo $this->renderSections('sections');
            }
        }

        if ($display_footer) {
            echo BimpRender::renderAjaxModal('page_modal');

            $this->addDebugTime('Fin affichage page');

            if (BimpDebug::isActive('bimpcore/controller/display_times')) {
                echo $this->renderDebugTime();
            }

            llxFooter();
        }
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
        $tabs = $this->config->getCompiledParams($section_path . '/tabs');

        if (!count($tabs)) {
            return $html;
        }

        if (!$this->current_tab) {
            $this->current_tab = 'default';
        }

        $h = 0;
        $head = array();

        $base_url = DOL_URL_ROOT . '/' . $this->module . '/index.php?';

        foreach ($tabs as $tab_name => $params) {
            if (isset($params['show']) && !(int) $params['show']) {
                if ($this->current_tab === $tab_name) {
                    $this->current_tab = 'default';
                }
                continue;
            }
            $url = '';
            $module = $this->module;
            $controller = '';
            if (isset($params['url'])) {
                $url = $params['url'];
            } elseif (isset($params['controller'])) {
                $controller = $params['controller'];
                if (isset($params['module'])) {
                    $url = DOL_URL_ROOT . '/' . $params['module'] . '/index.php?fc=' . $params['controller'];
                    $module = $params['module'];
                } else {
                    $url = $base_url . 'fc=' . $controller;
                }
            } else {
                $url = $base_url;
                if ($this->controller) {
                    $controller = $this->controller;
                    if ($this->controller !== 'index') {
                        $url .= 'fc=' . $this->controller;
                    }
                }
                if ($tab_name !== 'default') {
                    $url .= '&tab=' . $tab_name;
                }
                if (!is_null($this->object) && isset($this->object->id) && $this->object->id) {
                    $url .= '&id=' . $this->object->id;
                }
            }

            if (isset($params['url_params'])) {
                foreach ($params['url_params'] as $name => $value) {
                    $url .= '&' . $name . '=' . $value;
                }
            }

            if ($controller && ($controller === $this->controller) && $module === $this->module) {
                $href = $url . '#' . $tab_name;  //javascript:loadTabContent(\'' . $url . '\', \'' . $tab_name . '\')';
                $head[$h][0] = $href;
            } else {
                $head[$h][0] = $url;
            }

            $label = '';

            if (isset($params['icon']) && $params['icon']) {
                $label .= '<i class="' . BimpRender::renderIconClass($params['icon']) . ' iconLeft"></i>';
            }

            $label .= $params['label'];

            $head[$h][1] = $label;
            $head[$h][2] = $tab_name;
            $h++;
        }

        $tab_title = '';
        if (isset($tabs[$this->current_tab]['title'])) {
            $tab_title = $tabs[$this->current_tab]['title'];
        }

        if (!$tab_title) {
            if (!is_null($this->object)) {
                $tab_title = BimpTools::ucfirst($this->object->getLabel());
            }
        }

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

    protected function renderDebugTime()
    {
        $html = '';

        global $bimp_start_time;

        $html .= '<div id="bimpControllerDebugTimeInfos">';

        $html .= '<h3>Debug timers</h3>';

        if (!(float) $bimp_start_time) {
            $html .= BimpRender::renderAlerts('Variable bimp_start_time absente du fichier index.php');
        } else {
            $html .= '<table>';
            $html .= '<tbody>';

            $bimp_start_time = round($bimp_start_time, 4);
            $prev_time = $bimp_start_time;

            foreach ($this->times as $time) {
                $html .= '<tr>';
                $html .= '<td>' . $time['label'] . '</td>';
                $html .= '<td>' . round((float) ($time['time'] - $bimp_start_time), 4) . ' s</td>';
                $html .= '<td>' . round((float) ($time['time'] - $prev_time), 4) . ' s</td>';
                $html .= '</tr>';

                $prev_time = $time['time'];
            }

            $html .= '</tbody>';
            $html .= '</table>';
        }

        $html .= '</div>';

        return $html;
    }

    public function canView()
    {
        return 1;
    }

    // Traitements Ajax:

    protected function ajaxProcess()
    {
        $errors = array();
        if (BimpTools::isSubmit('action')) {
            $action = BimpTools::getvalue('action');
            $method = 'ajaxProcess' . ucfirst($action);
            if (method_exists($this, $method)) {
                $this->{$method}();
            } else {
                $errors[] = 'Requête inconnue: "' . $action . '"';
            }
        } else {
            $errors[] = 'Requête invalide: Aucune action spécifiée';
        }

        die(json_encode(array(
            'errors'     => $errors,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessLoadControllerTab()
    {
        $errors = $this->errors;
        $html = '';

        if (!count($errors)) {
            if (is_null($this->current_tab) || !$this->current_tab) {
                $errors[] = 'Impossible de charger le contenu demandé : aucun onglet spécifié';
            }
            $sections = $this->getConf('sections', array(), true, 'array');

            foreach ($sections as $idx => $section) {
                if ($this->config->isDefined('sections/' . $idx . '/tabs/' . $this->current_tab . '/sections')) {
                    $html .= $this->renderSections('sections/' . $idx . '/tabs/' . $this->current_tab . '/sections');
                }
            }

            if (!$html) {
                $errors[] = 'Aucun contenu trouvé pour cet onglet';
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessSaveObject()
    {
        $errors = array();
        $url = '';

        $id_object = BimpTools::getValue('id_object');
        $object_name = BimpTools::getValue('object_name');
        $object_module = BimpTools::getValue('module', $this->module);

        if (is_null($object_name)) {
            $errors[] = 'Type d\'objet absent';
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

        die(json_encode(array(
            'errors'           => $errors,
            'warnings'         => $result['warnings'],
            'object_view_url'  => $url,
            'success'          => $result['success'],
            'module'           => $object_module,
            'object_name'      => $object_name,
            'id_object'        => $id_object,
            'success_callback' => $result['success_callback'],
            'request_id'       => BimpTools::getValue('request_id', 0)
        )));
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
                        $success = 'Mise à jour du champ "' . $field_name . '" pour ' . $object->getLabel('the') . ' ' . $id_object . ' effectuée avec succès';
                    }
                }
            }
        }

        die(json_encode(array(
            'errors'      => $errors,
            'success'     => $success,
            'module'      => $module,
            'object_name' => $object_name,
            'id_object'   => $id_object,
            'field'       => $field,
            'request_id'  => BimpTools::getValue('request_id', 0)
        )));
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

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => $success,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
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

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => $success,
            'list_id'    => $list_id,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
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

        die(json_encode(array(
            'errors'      => $errors,
            'success'     => $success,
            'module'      => $module,
            'object_name' => $object_name,
            'id_object'   => $id_object,
            'request_id'  => BimpTools::getValue('request_id', 0)
        )));
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

        die(json_encode(array(
            'errors'      => $errors,
            'success'     => $success,
            'module'      => $module,
            'object_name' => $object_name,
            'id_object'   => $id_object,
            'request_id'  => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessDeleteObjects()
    {
        $errors = array();
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
                        $errors = array_merge($errors, $instance->delete());
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
        die(json_encode(array(
            'errors'       => $errors,
            'success'      => $success,
            'module'       => $module,
            'object_name'  => $object_name,
            'objects_list' => $objects,
            'request_id'   => BimpTools::getValue('request_id', 0)
        )));
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

        die(json_encode(array(
            'errors'     => $errors,
            'value'      => $value,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

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

        die(json_encode(array(
            'errors'      => $errors,
            'html'        => $html,
            'module'      => $module,
            'object_name' => $object_name,
            'id_object'   => $id_object,
            'id_parent'   => $id_parent,
            'form_name'   => $form_name,
            'form_id'     => $form_id,
            'request_id'  => BimpTools::getValue('request_id', 0)
        )));
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
        $value = BimpTools::getValue('value', '');
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
                if ($value && $object->field_exists($field_name)) {
                    $object->set($field_name, $value);
                }

                foreach ($fields as $field => $field_value) {
                    $object->set($field, $field_value);
                }

                if (count($object->errors)) {
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
                            $form->setValues(array(
                                'fields' => array(
                                    $field_name => $value
                                )
                            ));
                            $html = $form->renderCustomInput($form_row);
                        } else {
                            $html = BimpRender::renderAlerts('Erreur de configuration - contenu du champ personnalisé non défini');
                        }
                    } elseif ($object->config->isDefined('fields/' . $field_name)) {
                        $field = new BC_Field($object, $field_name, true);
                        $field->name_prefix = $field_prefix;
                        $field->display_card_mode = 'visible';
                        if (($field->params['type'] === 'id_object' || ($field->params['type'] === 'items_list' && $field->params['items_data_type'] === 'id_object')) &&
                                $field->params['create_form']) {
                            $html .= BC_Form::renderCreateObjectButton($object, $form_id, $field->params['object'], $field_prefix . $field_name, $field->params['create_form'], $field->params['create_form_values'], $field->params['create_form_label'], true);
                        }
                        $html .= $field->renderInput();
                        unset($field);
                    } elseif ($object->config->isDefined('associations/' . $field_name)) {
                        $bimpAsso = new BimpAssociation($object, $field_name);
                        if (count($bimpAsso->errors)) {
                            $html = BimpRender::renderAlerts($bimpAsso->errors);
                        } else {
                            $html = $bimpAsso->renderAssociatesCheckList($field_prefix);
                        }
                    }
                }
            }
        }

        die(json_encode(array(
            'errors'      => $errors,
            'html'        => $html,
            'form_id'     => $form_id,
            'module'      => $module,
            'object_name' => $object_name,
            'id_object'   => $id_object,
            'field_name'  => $field_name,
            'request_id'  => BimpTools::getValue('request_id', 0)
        )));
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

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            $list = new BC_ListTable($object, $list_name, 1, $id_parent);
            $html = $list->renderHtml();
            $list_id = $list->identifier;
        }

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'list_id'    => $list_id,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessLoadObjectList()
    {
        $errors = array();
        $rows_html = '';
        $pagination_html = '';

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
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            $list = new BC_ListTable($object, $list_name, 1, $id_parent);
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
        }

        die(json_encode(array(
            'errors'          => $errors,
            'rows_html'       => $rows_html,
            'pagination_html' => $pagination_html,
            'list_id'         => $list_id,
            'request_id'      => BimpTools::getValue('request_id', 0)
        )));
    }

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

        die(json_encode(array(
            'errors'      => $errors,
            'html'        => $html,
            'header_html' => $header_html,
            'view_id'     => $view_id,
            'request_id'  => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessLoadObjectViewsList()
    {
        $errors = array();
        $html = '';
        $views_list_id = '';

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $views_list_name = BimpTools::getValue('views_list_name');

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (is_null($views_list_name) || !$views_list_name) {
            $errors[] = 'Type de liste de vues absent';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($module, $object_name);
            $bimpViewsList = new BC_ListViews($object, $views_list_name);
            $html = $bimpViewsList->renderItemViews();
            $pagination = $bimpViewsList->renderPagination();
            $views_list_id = $bimpViewsList->identifier;
        }

        die(json_encode(array(
            'errors'        => $errors,
            'html'          => $html,
            'pagination'    => $pagination,
            'views_list_id' => $views_list_id,
            'request_id'    => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessLoadObjectCard()
    {
        $errors = array();
        $html = '';

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name');
        $id_object = BimpTools::getValue('id_object', 0);
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

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
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
        $value = BimpTools::getValue('value');

        if ($filters) {
            $filters = json_decode($filters, 1);
        } else {
            $filters = array();
        }

        if (!is_null($table) && !is_null($fields_return_label) && !is_null($fields_return_value) && count($fields_search) && !is_null($value)) {
            global $db;
            $bdb = new BimpDb($db);

            $sql = 'SELECT ';
            $fields_return_label = explode(',', $fields_return_label);
            $i = 1;
            foreach ($fields_return_label as $field_label) {
                if (!preg_match('/\./', $field_label)) {
                    $field_label = '`' . $field_label . '`';
                }
                $sql .= $field_label . ' as label_' . $i . ', ';
                $i++;
            }

            if (!preg_match('/\./', $fields_return_value)) {
                $fields_return_value = '`' . $fields_return_value . '`';
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
                    $field = '`' . $field . '`';
                }
                $where .= 'LOWER(' . $field . ')' . ' LIKE \'%' . strtolower(addslashes($value)) . '%\'';
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

        die(json_encode(array(
            'errors'     => array(),
            'list'       => $list,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
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

        die(json_encode(array(
            'errors'     => $errors,
            'success'    => 'Associations correctement enregistrées',
            'done'       => $done,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessSetObjectNewStatus()
    {
        $errors = array();
        $warnings = array();
        $success = '';

        $module = BimpTools::getValue('module', $this->module);
        $object_name = BimpTools::getValue('object_name', '');
        $id_object = (int) BimpTools::getValue('id_object', 0);

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
            $object = BimpObject::getInstance($module, $object_name, $id_object);
            if (is_null($object) || !$object->isLoaded()) {
                $errors[] = 'ID de l\'objet invalide';
            } else {
                $errors = $object->setNewStatus($status, $extra_data, $warnings);
                $success = 'Mise à jour du statut ' . $object->getLabel('of_the') . ' ' . $object->id . ' effectué avec succès';
            }
        }

        die(json_encode(array(
            'errors'     => $errors,
            'warnings'   => $warnings,
            'success'    => $success,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
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

        ini_set('display_errors', 1);

        $return = array(
            'success'    => $success,
            'request_id' => BimpTools::getValue('request_id', 0)
        );

        if (array_key_exists('errors', $errors)) {
            foreach ($errors as $key => $value) {
                $return[$key] = $value;
            }
        } else {
            $return['errors'] = $errors;
        }

        die(json_encode($return));
    }

    protected function ajaxProcessLoadProductStocks()
    {
        $errors = array();
        $html = '';

        $id_product = (int) BimpTools::getValue('id_product', 0);
        $id_entrepot = (int) BimpTools::getValue('id_entrepot', 0);

        if (!$id_product) {
            $errors[] = 'ID du produit absent';
        } else {
            $product = BimpObject::getInstance('bimpcore', 'Bimp_Product', $id_product);
            if (!BimpObject::objectLoaded($product)) {
                $errors[] = 'Produit d\'ID ' . $id_product . ' inexistant';
            } else {
                $html = $product->renderStocksByEntrepots($id_entrepot);
            }
        }


        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
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

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'request_id' => BimpTools::getValue('request_id', 0)
        )));
    }

    protected function ajaxProcessLoadFixeTabs($i = 0)
    {
        global $bimp_fixe_tabs;
        $i++;

        $bimp_fixe_tabs = new FixeTabs();
        $bimp_fixe_tabs->init();

        $html = $bimp_fixe_tabs->render(true);

        $errors = array_merge($bimp_fixe_tabs->errors, array(/* ici recup erreur global ou message genre application ferme dans 10min */));
        $returnHtml = "";
        $hashCash = 'fixeTabsHtml' . $_POST['randomId']; //Pour ne regardé que sur l'ongelt actuel
        session_start();
        if (!isset($_SESSION[$hashCash]) || !is_array($_SESSION[$hashCash]))
            $_SESSION[$hashCash] = array('nbBouclePush' => $this->nbBouclePush, 'html' => '');

        if (count($errors) > 0 || $i > $_SESSION[$hashCash]['nbBouclePush'] || $i > $this->maxBouclePush || $_SESSION[$hashCash]['html'] != $html) {
            if ($_SESSION[$hashCash]['html'] != $html)//On ne renvoie rien, pas de refeesh
                $returnHtml = $html;
            $_SESSION[$hashCash]['html'] = $html;
            $_SESSION[$hashCash]['nbBouclePush'] = $_SESSION[$hashCash]['nbBouclePush'] * 1.1; //Pour ne pas surchargé quand navigateur resté ouvert, mais ne pas avoir des boucle morte quand navigation rapide


            die(json_encode(array(
                'errors'     => $errors,
                'html'       => $returnHtml,
                'request_id' => BimpTools::getValue('request_id', 0)
            )));
        }
        else {
            session_write_close(); //Pour eviter les blockages navigateur
            usleep(930000); //un tous petit peu moins d'une seconde + temps d'execution = 1s
            return $this->ajaxProcessLoadFixeTabs($i);
        }
    }

    // Callbacks:

    protected function getObjectIdFromPost($object_name)
    {
        return BimpTools::getValue('id_' . $object_name, null);
    }
}
