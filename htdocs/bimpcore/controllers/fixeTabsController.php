<?php

class fixeTabsController extends BimpController
{

    protected $tabs = array();

    public function addTab($id, $caption, $content, $classes = array())
    {
        $this->tabs[] = array(
            'id'      => $id,
            'caption' => $caption,
            'content' => $content,
            'classes' => $classes
        );
    }

    public function display()
    {
        if (BimpTools::isSubmit('ajax')) {
            $this->ajaxProcess();
            return;
        }

        if (!count($this->tabs)) {
            return;
        }

        if (!defined('BIMP_CONTROLLER_INIT')) {
            define('BIMP_CONTROLLER_INIT', 1);
            global $main_controller;
            $main_controller = $this;

            BimpCore::displayHeaderFiles(true);

            foreach ($this->cssFiles as $cssFile) {
                echo '<link type="text/css" rel="stylesheet" href="' . BimpCore::getFileUrl($cssFile) . '"/>';
            }

            foreach ($this->jsFiles as $jsFile) {
                echo '<script type="text/javascript" src="' . BimpCore::getFileUrl($jsFile) . '"></script>';
            }

            echo '<script type="text/javascript">';
            echo 'ajaxRequestsUrl = \'' . DOL_URL_ROOT . '/' . $this->module . '/index.php?fc=' . $this->controller . '\';';
            echo '</script>';

            echo $this->render();
        } else {
            global $main_controller;
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
            echo $this->render();
        }
    }

    public function render()
    {
        if (!count($this->tabs)) {
            return '';
        }
        $html = '';

        $html .= '<div id="bimp_fixe_tabs">';

        $html .= '<div id="bimp_fixe_tabs_captions">';
        foreach ($this->tabs as $tab) {
            $html .= '<div class="fixe_tab_caption';
            if (count($tab['classes'])) {
                foreach ($tab['classes'] as $class) {
                    $html .= ' ' . $class;
                }
            }
            $html .= '" id="fixe_tab_caption_' . $tab['id'] . '" data-id_tab="' . $tab['id'] . '">';

            $html .= '<span class="title">' . $tab['caption'] . '</span>';
            $html .= '</div>';
        }
        $html .= '</div>';

        $html .= '<div id="bimp_fixe_tabs_contents">';
        foreach ($this->tabs as $tab) {
            $html .= '<div class="fixe_tab_content" id="fixe_tab_content_' . $tab['id'] . '" data-id_tab="' . $tab['id'] . '">';
            $html .= $tab['content'];
            $html .= '</div>';
        }
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }
}
