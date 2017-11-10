<?php

class BimpController
{

    public $module = '';
    public $controller = '';
    public $title = '';
    public $default_tabs_title = '';
    public $tabs = array();
    public $sections = array();
    public $current_tab = '';
    protected $jsFiles = array();
    protected $cssFiles = array();

    public static function getInstance($module)
    {
        $dir = DOL_DOCUMENT_ROOT . $module . '/';
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

    public function __construct($module, $controller)
    {
        $this->module = $module;
        $this->controller = $controller;

        $dir = DOL_DOCUMENT_ROOT . $module . '/';

        $this->current_tab = BimpTools::getValue('tab', '');

        if (file_exists($dir . 'controllers/' . $controller . '.yml')) {
            $config = spyc_load_file($dir . '/controllers/' . $controller . '.yml');
            if (isset($config['title'])) {
                $this->title = $config['title'];
            }
            if (isset($config['default_tabs_title'])) {
                $this->default_tabs_title = $config['default_tabs_title'];
            }
            if (isset($config['tabs'])) {
                $this->tabs = $config['tabs'];
            }

            if ($this->current_tab) {
                if (isset($this->tabs[$this->current_tab]['sections'])) {
                    $this->sections = $this->tabs[$this->current_tab]['sections'];
                }
            } elseif (isset($config['sections'])) {
                $this->sections = $config['sections'];
            }
        }

        $this->addJsFile('/bimpcore/views/js/functions.js');
        $this->addJsFile('/bimpcore/views/js/ajax.js');
        $this->addCssFile('/bimpcore/views/css/font-awesome.css');
        $this->addCssFile('/bimpcore/views/css/styles.css');
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

        llxHeader('', '', '', false, false, false, $this->jsFiles);

        foreach ($this->cssFiles as $url) {
            echo '<link type="text/css" rel="stylesheet" href="' . DOL_URL_ROOT . '/' . $url . '"/>';
        }
        if ($this->title) {
            print load_fiche_titre($this->title, '', 'title_generic.png');
        }

        $this->displayMessages();
        $this->displayTabs();
        $this->displaySections($this->sections);

        llxFooter();
    }

    protected function displayTabs()
    {
        if (!count($this->tabs)) {
            return;
        }

        $h = 0;
        $head = array();

        $base_url = DOL_URL_ROOT . '/' . $this->module . '/index.php?';
        if ($this->controller && $this->controller !== 'index') {
            $base_url .= 'fc=' . $this->controller;
        }

        foreach ($this->tabs as $tab_name => $params) {
            $url = '';
            if (isset($params['url'])) {
                $url = $params['url'];
            } elseif (isset($params['controller'])) {
                $url = $base_url . 'fc=' . $params['controller'];
            } else {
                $url = $base_url;
                if ($this->controller && $this->controller !== 'index') {
                    $url .= 'fc=' . $this->controller;
                }
            }
            $head[$h][0] = $url;
            $head[$h][1] = $params['label'];
            $head[$h][2] = $tab_name;
            $h++;
        }
        $title = '';
        if ($this->current_tab) {
            if (isset($this->tabs[$this->current_tab]['title'])) {
                $title = $this->tabs[$this->current_tab['title']];
            }
        }

        if (!$title && isset($this->default_tabs_title)) {
            $title = $this->default_tabs_title;
        }

        dol_fiche_head($head, $this->current_tab, $title);
    }

    protected function displayMessages()
    {
        
    }

    protected function displaySections($sections, $object = null)
    {
        if (count($sections)) {
            foreach ($sections as $section) {
                $this->displaySection($section, $object);
            }
        }
    }

    protected function displaySection($section, $object = null)
    {
        if (isset($section['object'])) {
            $object = null;
            $module = isset($section['object']['module']) ? $section['object']['module'] : $this->module;
            $object = BimpObject::getInstance($module, $section['object']['name']);

            if (BimpTools::isSubmit('id_' . $section['object']['name'])) {
                $object->fetch(BimpTools::getValue('id_' . $section['object']['name']));
            }
        }

        $params = isset($section['params']) ? $section['params'] : null;

        switch ($section['type']) {
            case 'list':
                $this->displayObjectList($object, $params);
                break;

            case 'view':
                $this->displayObjectView($object, $params);
                break;

            case 'form':
                $this->displayObjectForm($object, $params);
                break;

            case 'formAndList':
                $this->displayObjectFormAndList($object, $params);
                break;
        }
    }

    protected function displayObjectList(BimpObject $object = null, $params = null)
    {
        if (is_null($object)) {
            return;
        }

        if (isset($params['list'])) {
            $list_name = $params['list'];
        } else {
            $list_name = 'default';
        }

        $bimplist = new BimpList($object);
        echo $bimplist->render($list_name);
    }

    protected function displayObjectFormAndList(BimpObject $object = null, $params = null)
    {
        if (is_null($object)) {
            return;
        }

        if (isset($params['list'])) {
            $list_name = $params['list'];
        } else {
            $list_name = 'default';
        }

        $html = '';
        $object_name = $object->objectName;
        $html .= '<div id="' . $object_name . '_card">';
        $html .= '<div class="objectToolbar">';
        $html .= '<span id="' . $object_name . '_openFormButton" class="butAction"';
        $html .= 'onclick="openObjectForm(\'' . $object_name . '\', null)">';
        $html .= 'Ajouter ' . $object->getLabel('a') . '</span>';
        $html .= '<span id="' . $object_name . '_closeFormButton" class="butActionDelete"';
        $html .= 'onclick="closeObjectForm(\'' . $object_name . '\')"';
        $html .= ' style="display: none">';
        $html .= 'Annuler</span>';
        $html .= '</div>';

        $html .= '<div id="' . $object_name . '_formContainer" style="display: none"></div>';
        $bimplist = new BimpList($object);
        $html .= $bimplist->render($list_name);
        $html .= '</div>';

        echo $html;
    }

    // Traitements Ajax :

    protected function ajaxProcess()
    {
        if (BimpTools::isSubmit('action')) {
            $method = 'ajaxProcess' . ucfirst(BimpTools::getvalue('action'));
            if (method_exists($this, $method)) {
                $this->{$method}();
            }
        }
    }

    protected function ajaxProcessSaveObject()
    {
        $id_object = BimpTools::getValue('id_object');
        $object_name = BimpTools::getValue('object_name');
        $object_module = BimpTools::getValue('object_module', $this->module);

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
                        $success = 'Création ' . $object->getLabel('of_the') . ' effectuée avec succès';
                    }
                }
            }
        }
    }

    protected function ajaxProcessSaveObjectAssociations()
    {
        $id_object = BimpTools::getValue('id_object');
        $object_name = BimpTools::getValue('object_name');
        $object_module = BimpTools::getValue('object_module', $this->module);
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
            $object = BimpObject::getInstance($object_module, $object_name);

            if (!is_null($id_object)) {
                $object->fetch($id_object);
            }
            
            if (!isset($object->config['associations'][$association])) {
                $errors[] = 'Type d\'association invalide';
            } else {
                $errors = $object->saveAssociations($association, $list);
            if (!count($errors)) {
                
                $label = $class_name::getLabel('name_plur');
                $success = 'Enregistrement de la liste des ' . $class_name::getLabel('name_plur') . ' associé';
                if ($class_name::isLabelFemale()) {
                    $success .= 'e';
                }
                $success .= 's effectué avec succès';
            }
            }
        }
    }

    protected function ajaxProcessDeleteObjects()
    {
        $objects = BimpTools::getValue('objects', array());
        $object_name = BimpTools::getValue('object_name', null);
        if (!count($objects)) {
            $errors[] = 'Liste des objets à supprimer vide ou absente';
        }
        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (!count($errors)) {
            if (!class_exists($object_name)) {
                if (file_exists(__DIR__ . '/classes/' . $object_name . '.class.php')) {
                    require_once __DIR__ . '/classes/' . $object_name . '.class.php';
                }
            }

            if (!class_exists($object_name)) {
                $errors[] = 'Classe "' . $object_name . '" inexistante';
            } else {
                foreach ($objects as $id_object) {
                    $object = new $object_name();
                    if ($object->fetch($id_object)) {
                        $errors = array_merge($errors, $object->delete());
                    }
                    unset($object);
                }
                if (!count($errors)) {
                    if ($object_name::isLabelFemale()) {
                        $success = 'Toutes les ' . $object_name::getLabel('name_plur') . ' ont été supprimées';
                    } else {
                        $success = 'Tous les ' . $object_name::getLabel('name_plur') . ' ont été supprimés';
                    }
                    $success .= ' avec succès';
                }
            }
        }
    }

    protected function ajaxProcessLoadObjectForm()
    {
        $object_name = BimpTools::getValue('object_name');
        $id_object = BimpTools::getValue('id_object', 0);
        $id_parent = BimpTools::getValue('id_parent', 0);

        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type de l\'objet absent';
        }

        if (!count($errors)) {
            if (!class_exists($object_name)) {
                if (file_exists(__DIR__ . '/classes/' . $object_name . '.class.php')) {
                    require_once __DIR__ . '/classes/' . $object_name . '.class.php';
                }
            }

            if (!class_exists($object_name)) {
                $errors[] = 'Classe "' . $object_name . '" inexistante';
            } else {
                if ($id_object) {
                    $object = new $object_name();
                    if (!$object->fetch($id_object)) {
                        $errors[] = ucfirst($object->getLabel('')) . ' d\'ID ' . $id_object . ' non trouvé';
                    } else {
                        $html = $object->renderEditForm((int) $id_parent ? $id_parent : null);
                    }
                } else {
                    $html = $object_name::renderCreateForm((int) $id_parent ? $id_parent : null);
                }
            }
        }
    }

    protected function loadObjectList()
    {
        $id_parent = BimpTools::getValue('id_parent', null);
        if (!$id_parent) {
            $id_parent = null;
        }
        $object_name = BimpTools::getValue('object_name');
        if (is_null($object_name) || !$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        if (!count($errors)) {
            if (!class_exists($object_name)) {
                if (file_exists(__DIR__ . '/classes/' . $object_name . '.class.php')) {
                    require_once __DIR__ . '/classes/' . $object_name . '.class.php';
                }
            }

            if (!class_exists($object_name)) {
                $errors[] = 'Classe "' . $object_name . '" inexistante';
            } else {
                $html = $object_name::renderListRows($id_parent);
            }
        }
    }
}
