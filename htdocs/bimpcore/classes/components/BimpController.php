<?php

class BimpController
{

    public $module = '';
    public $controller = '';
    public $current_tab = '';
    public $config = null;
    public $errors = array();
    protected $jsFiles = array();
    protected $cssFiles = array();

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
        $this->module = $module;
        $this->controller = $controller;

        $dir = DOL_DOCUMENT_ROOT . '/' . $module . '/controllers/';

        $this->current_tab = BimpTools::getValue('tab', 'default');

        $this->config = new BimpConfig($dir, $this->controller, $this);

        if ($this->config->errors) {
            $this->errors = array_merge($this->errors, $this->config->errors);
        }

        $this->addJsFile('/bimpcore/views/js/new/controller.js');

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

    // Affichages :

    public function display()
    {
        if (BimpTools::isSubmit('ajax')) {
            $this->ajaxProcess();
            return;
        }

        if (!defined('BIMP_CONTROLLER_INIT')) {
            define('BIMP_CONTROLLER_INIT', 1);
            global $main_controller;
            $main_controller = $this;
            llxHeader('', '', '', false, false, false);

            BimpCore::displayHeaderFiles();

            $id_object = BimpTools::getValue('id');

            echo '<script type="text/javascript">';
            echo ' var dol_url_root = \'' . DOL_URL_ROOT . '\';';
            echo ' ajaxRequestsUrl = \'' . DOL_URL_ROOT . '/' . $this->module . '/index.php?fc=' . $this->controller . (!is_null($id_object) ? '&id=' . $id_object : '') . '\';';
            echo '</script>';

            foreach ($this->cssFiles as $css_file) {
                echo '<link type="text/css" rel="stylesheet" href="' . DOL_URL_ROOT . '/' . $css_file . '"/>';
            }

            foreach ($this->jsFiles as $js_file) {
                echo '<script type="text/javascript" src="' . DOL_URL_ROOT . '/' . $js_file . '"></script>';
            }

            if (count($this->errors)) {
                echo BimpRender::renderAlerts($this->errors);
            } else {
                $title = $this->getConf('title', '');
                if ($title) {
                    print load_fiche_titre($title, '', 'title_generic.png');
                }

                if (BimpTools::isSubmit('search')) {
                    echo $this->renderSearchResults();
                } else {
                    echo $this->renderSections('sections');
                }
            }

            echo BimpRender::renderAjaxModal('page_modal');
            llxFooter();
        } else {
            global $main_controller;
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
            if (BimpTools::isSubmit('search')) {
                echo $this->renderSearchResults();
            } else {
                echo $this->renderSections('sections');
            }
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
        $tabs = $this->getCurrentConf('tabs', array(), true, 'array');

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
            $this->config->setCurrentPath($section_path . '/tabs/' . $tab_name);
            $show = (int) $this->getCurrentConf('show', 1, false, 'bool');
            if (!$show) {
                if ($this->current_tab === $tab_name) {
                    $this->current_tab = 'default';
                }
                continue;
            }
            $url = '';
            $controller = '';
            if (isset($params['url'])) {
                $url = $this->getCurrentConf('url', '', true);
            } elseif (isset($params['controller'])) {
                $controller = $this->getCurrentConf('controller', '', true);
                $url = $base_url . 'fc=' . $controller;
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
                $url_params = $this->getCurrentConf('url_params', array(), true, 'array');
                foreach ($url_params as $name => $value) {
                    $value = $this->getCurrentConf('url_params/' . $name, '', true);
                    $url .= '&' . $name . '=' . $value;
                }
            }

            if ($controller && ($controller === $this->controller)) {
                $href = $url . '#' . $tab_name;  //javascript:loadTabContent(\'' . $url . '\', \'' . $tab_name . '\')';
                $head[$h][0] = $href;
            } else {
                $head[$h][0] = $url;
            }

            $head[$h][1] = $params['label'];
            $head[$h][2] = $tab_name;
            $h++;
        }
        $this->config->setCurrentPath($section_path);

        $tab_title = '';
        if (isset($tabs[$this->current_tab]['title'])) {
            $tab_title = $this->getConf($section_path . '/tabs/' . $this->current_tab . '/title', '');
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
                $params['attr']['href'] = $this->makeUrlFromConfig($section_path . '/buttons/' . $idx . '/url');
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

    // Traitements Ajax :

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
        $success = '';
        $url = '';

        $id_object = BimpTools::getValue('id_object');
        $object_name = BimpTools::getValue('object_name');
        $object_module = BimpTools::getValue('module', $this->module);

        if (is_null($object_name)) {
            $errors[] = 'Type d\'objet absent';
        }

        if (!count($errors)) {
            $object = BimpObject::getInstance($object_module, $object_name);

            if (!is_null($id_object)) {
                $object->fetch($id_object);
            }

            $errors = $object->validatePost();

            if (!count($errors)) {
                if (isset($object->id) && $object->id) {
                    $errors = $object->update();
                    if (!count($errors)) {
                        $success = 'Mise à jour ' . $object->getLabel('of_the') . ' effectuée avec succès';
                    }
                } else {
                    $errors = $object->create();
                    if (!count($errors)) {
                        $id_object = $object->id;
                        $success = 'Création ' . $object->getLabel('of_the') . ' effectuée avec succès';
                    }
                }
            }

            if (!count($errors)) {
                $url = BimpObject::getInstanceUrl($object);

                if (BimpTools::isSubmit('associations_params')) {
                    $assos = json_decode(BimpTools::getValue('associations_params'));
                    foreach ($assos as $params) {
                        if (isset($params->association)) {
                            if (isset($params->object_name) && isset($params->object_module) && isset($params->id_object)) {
                                $obj = BimpObject::getInstance($params->object_module, $params->object_name);
                                $bimpAsso = new BimpAssociation($obj, $params->association);
                                $assos_errors = $bimpAsso->addObjectAssociation($id_object, $params->id_object);
                                if ($assos_errors) {
                                    $errors[] = 'Echec de l\'association ' . $object->getLabel('of_the') . ' avec ' . $obj->getLabel('the') . ' ' . $params->id_object;
                                    $errors = array_merge($errors, $assos_errors);
                                }
                                unset($bimpAsso);
                            } elseif (isset($params->id_associate)) {
                                $bimpAsso = new BimpAssociation($object, $params->association);
                                $assos_errors = $bimpAsso->addObjectAssociation($params->id_associate, $id_object);
                                if ($assos_errors) {
                                    $errors[] = 'Echec de l\'association ' . $object->getLabel('of_the') . ' avec ' . BimpObject::getInstanceLabel($bimpAsso->associate, 'the') . ' ' . $params->id_associate;
                                    $errors = array_merge($errors, $assos_errors);
                                }
                                unset($bimpAsso);
                            }
                        }
                    }
                }
            }
        }

        die(json_encode(array(
            'errors'          => $errors,
            'object_view_url' => $url,
            'success'         => $success,
            'module'          => $object_module,
            'object_name'     => $object_name,
            'id_object'       => $id_object,
            'request_id'      => BimpTools::getValue('request_id', 0)
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
                        $success = 'Mise à jour du champ "' . $field . '" pour ' . $object->getLabel('the') . ' n° ' . $id_object . ' effectuée avec succès';
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
                    if ($instance->isLabelFemale()) {
                        $success = 'Toutes les ' . $instance->getLabel('name_plur') . ' ont été supprimées';
                    } else {
                        $success = 'Tous les ' . $instance->getLabel('name_plur') . ' ont été supprimés';
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
                foreach ($fields as $field => $value) {
                    $object->set($field, $value);
                }

                if (count($object->errors)) {
                    $errors = $object->errors;
                } else {
                    if ($custom_field) {
                        $form_row = BimpTools::getValue('form_row', '');
                        if ($form_row) {
                            $form = new BC_Form($object, $id_parent, $form_name, 1, true);
                            $html = $form->renderCustomInput($form_row);
                        } else {
                            $html = BimpRender::renderAlerts('Erreur de configuration - contenu du champ personnalisé non défini');
                        }
                    } elseif ($object->config->isDefined('fields/' . $field_name)) {
                        $field = new BC_Field($object, $field_name, true);
                        $html = $field->renderInput();
                        unset($field);
                    } elseif ($object->config->isDefined('associations/' . $field_name)) {
                        $bimpAsso = new BimpAssociation($object, $field_name);
                        if (count($bimpAsso->errors)) {
                            $html = BimpRender::renderAlerts($bimpAsso->errors);
                        } else {
                            $html = $bimpAsso->renderAssociatesCheckList();
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
            $view->setNewValues($new_values);
            if ($content_only) {
                $html = $view->renderHtmlContent();
            } else {
                $html = $view->renderHtml();
            }
            $view_id = $view->identifier;
        }

        die(json_encode(array(
            'errors'     => $errors,
            'html'       => $html,
            'view_id'    => $view_id,
            'request_id' => BimpTools::getValue('request_id', 0)
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
            $bimpViewsList = new BimpViewsList($object, $views_list_name);
            $html = $bimpViewsList->renderItemViews();
            $pagination = $bimpViewsList->renderPagination();
            $views_list_id = $bimpViewsList->views_list_identifier;
        }

        die(json_encode(array(
            'errors'        => $errors,
            'html'          => $html,
            'pagination'    => $pagination,
            'views_list_id' => $views_list_id,
            'request_id'    => BimpTools::getValue('request_id', 0)
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
                $where .= 'LOWER(' . $field . ')' . ' LIKE \'%' . strtolower($value) . '%\'';
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

    // Callbacks: 

    protected function getObjectIdFromPost($object_name)
    {
        return BimpTools::getValue('id_' . $object_name, null);
    }

    // Outils:
    public function makeUrlFromConfig($path)
    {
        $url = DOL_URL_ROOT . '/';

        $params = $this->getConf($path, null, true, 'array');
        if (is_null($params)) {
            return '';
        }

        if (isset($params['url'])) {
            $url .= $this->getConf($path . '/url', '');
        } else {
            $module = $this->getConf($path . '/module', $this->module);
            $controller = $this->getConf($path . '/controller', $this->controller);
            $url .= $module . '/index.php?fc=' . $controller;
        }

        if (isset($params['url_params'])) {
            $url_params = $this->getConf($path . '/url_params', array(), false, 'array');
            foreach ($url_params as $name => $value) {
                if (!preg_match('/\?/', $url)) {
                    $url .= '?';
                } else {
                    $url .= '&';
                }
                $url .= $name . '=' . $value;
            }
        }

        return $url;
    }
}
