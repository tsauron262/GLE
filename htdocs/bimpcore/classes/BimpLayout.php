<?php

class BimpLayout
{

    protected static $instance = null;
    public $no_js = 0;
    public $no_head = 0;
    public $js_files = array();
    public $css_files = array();
    public $js_vars = array();
    public $page_title = '';
    public $extra_head = '';
    public $body_id = 'mainbody';
    public $body_classes = array();
    public $menu_target = '';
    public $menu_morequerystring = '';
    public $main_area_html = '';
    public $help_url = '';

    // Gestion Instance: 

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            global $conf, $main_controller;

            if (is_a($main_controller, 'BimpController')) {
                $layout_module = $main_controller->layout_module;
                $layout_name = $main_controller->layout_name;
            }

            if (!$layout_module || !$layout_name) {
                $layout_module = 'bimpcore';
                $layout_name = 'BimpLayout';
            }

            if (!BimpCore::isContextPublic()) {
                if ($layout_module === 'bimpcore' && $layout_name === 'BimpLayout') { // Si le controller n'a pas défini un layout différent. 
                    if ($conf->theme == "BimpTheme") {
                        $layout_module = 'bimptheme';
                        $layout_name = 'BimpThemeLayout';
                    }
                }
            }

            $layout_class = $layout_name;

            if (!class_exists($layout_class)) {
                if (file_exists(DOL_DOCUMENT_ROOT . '/' . $layout_module . '/classes/' . $layout_name . '.php')) {
                    require_once DOL_DOCUMENT_ROOT . '/' . $layout_module . '/classes/' . $layout_name . '.php';
                } else {
                    BimpCore::addlog('LAYOUT ABSENT: ' . $layout_module . '/' . $layout_name, Bimp_Log::BIMP_LOG_URGENT, 'bimpcore');
                    $layout_module = 'bimpcore';
                    $layout_name = 'BimpLayout';
                }
            }

            // Véfication des extensions: 
            if (defined('BIMP_EXTENDS_VERSION')) {
                if (file_exists(DOL_DOCUMENT_ROOT . '/' . $layout_module . '/extends/versions/' . BIMP_EXTENDS_VERSION . '/classes/' . $layout_name . '.php')) {
                    require_once DOL_DOCUMENT_ROOT . '/' . $layout_module . '/extends/versions/' . BIMP_EXTENDS_VERSION . '/classes/' . $layout_name . '.php';
                    $layout_class = $layout_name . '_ExtVersion';
                }
            }

            if (BimpCore::getExtendsEntity() != '') {
                if (file_exists(DOL_DOCUMENT_ROOT . '/' . $layout_module . '/extends/entities/' . BimpCore::getExtendsEntity() . '/classes/' . $layout_name . '.php')) {
                    require_once DOL_DOCUMENT_ROOT . '/' . $layout_module . '/extends/entities/' . BimpCore::getExtendsEntity() . '/classes/' . $layout_name . '.php';
                    $layout_class = $layout_name . '_ExtEntity';
                }
            }

            if (class_exists($layout_class)) {
                self::$instance = new $layout_class();
            } else {
                BimpCore::addlog('CLASSE LAYOUT ABSENTE: ' . $layout_class, Bimp_Log::BIMP_LOG_URGENT, 'bimpcore');
                self::$instance = new BimpLayout();
            }
        }

        return self::$instance;
    }

    public static function hasInstance()
    {
        return is_a(self::$instance, 'BimpLayout');
    }

    // Gestion du contenu: 

    public function addJsFile($file)
    {
        if ($file && !in_array($file, $this->js_files)) {
            $this->js_files[] = $file;
        }
    }

    public function addCssFile($file)
    {
        if ($file && !in_array($file, $this->css_files)) {
            $this->css_files[] = $file;
        }
    }

    public function addJsVar($name, $value)
    {
        $this->js_vars[$name] = $value;
    }

    public function addJsVars($js_vars)
    {
        if (is_array($js_vars) && !empty($js_vars)) {
            foreach ($js_vars as $var_name => $var_value) {
                $this->js_vars[$var_name] = $var_value;
            }
        }
    }

    // Traitements:

    public function initHead()
    {
        global $bimp_layout_js_vars;
        $bimp_layout_js_vars = '';

        if (!empty($this->js_vars)) {
            $bimp_layout_js_vars .= "\n" . '<!-- VARS JS -->' . "\n";
            $bimp_layout_js_vars .= '<script type="text/javascript">' . "\n";

            foreach ($this->js_vars as $var_name => $var_value) {
                $bimp_layout_js_vars .= "\t" . 'var ' . $var_name . ' = ';
                $bimp_layout_js_vars .= $var_value;
                $bimp_layout_js_vars .= ';' . "\n";
            }

            $bimp_layout_js_vars .= '</script>' . "\n";
        }
    }

    public function begin()
    {
        $this->displayHead();

        echo '<body id="' . $this->body_id . '"' . (!empty($this->body_classes) ? ' class="' . implode(' ', $this->body_classes) . '"' : '') . '>' . "\n";

        $this->displayTop();
        $this->displayLeft();
        $this->displayMainArea();
    }

    public function end()
    {
        $this->displayRight();
        $this->displayBottom();

        echo $this->renderModals();

        // Ce script doit figurer en toute fin de page (on cherche à être sûr que tout le js bimpcore est chargé): 
        echo '<script type="text/javascript">';
        echo '$(document).ready(function() {$(\'body\').trigger($.Event(\'bimp_ready\'));});';
        echo '</script>' . "\n\n";
    }

    // Affichages: 

    public function displayHead()
    {
        $this->initHead();
        top_htmlhead($this->extra_head, $this->page_title, $this->no_js, $this->no_head, $this->js_files, $this->css_files);
    }

    public function displayTop()
    {
        global $conf;
        if (empty($conf->dol_hide_topmenu) || GETPOST('dol_invisible_topmenu', 'int')) {
            top_menu($this->extra_head, $this->page_title, $this->menu_target, 0, 0, $this->js_files, $this->css_files, '', $this->help_url);
        }
    }

    public function displayLeft()
    {
        global $conf;
        if (empty($conf->dol_hide_leftmenu)) {
            left_menu('', $this->help_url, '', '', 1, $this->page_title, 1);
        }
    }

    public function displayMainArea()
    {
        if ($this->main_area_html) {
            echo $this->main_area_html;
            return;
        }

        main_area($this->page_title);
    }

    public function displayRight()
    {
        
    }

    public function displayBottom()
    {
        
    }

    // Rendus HTML: 

    public function renderModals()
    {
        $html = '';

        $html .= BimpRender::renderAjaxModal('page_modal', 'bimpModal');
        $html .= BimpRender::renderAjaxModal('docu_modal', 'docModal');
        $html .= BimpRender::renderAjaxModal('alert_modal', 'alertModal');

        $html .= '<div id="openModalBtn" onclick="bimpModal.show();" class="closed bs-popover"';
        $html .= BimpRender::renderPopoverData('Afficher la fenêtre popup', 'left');
        $html .= ' data-modal_id="page_modal">';
        $html .= BimpRender::renderIcon('far_window-restore');
        $html .= '</div>' . "\n";

        BimpDebug::addDebugTime('Fin affichage page');
        if (BimpDebug::isActive()) {

            $html .= BimpRender::renderAjaxModal('debug_modal', 'BimpDebugModal');

            $html .= '<div id="openDebugModalBtn" onclick="BimpDebugModal.show();" class="closed bs-popover"';
            $html .= BimpRender::renderPopoverData('Afficher la fenêtre debug', 'right');
            $html .= ' data-modal_id="debug_modal">';
            $html .= BimpRender::renderIcon('fas_info-circle');
            $html .= '</div>' . "\n";

            $html .= '<div id="bimp_page_debug_content" style="display: none">';
            $html .= BimpDebug::renderDebug();
            $html .= '</div>' . "\n";
        }

        $html .= "\n";
        return $html;
    }
}
