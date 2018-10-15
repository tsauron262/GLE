<?php

class FixeTabs
{

    public $errors = array();
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

    public function init()
    {
        require_once DOL_DOCUMENT_ROOT . '/bimpsupport/chronos.php';
        runBimpSupportChrono();
    }

    public function displayHead()
    {
        echo '<link type="text/css" rel="stylesheet" href="' . DOL_URL_ROOT . '/bimpcore/views/css/fixeTabs.css"/>';
        echo '<script type="text/javascript" src="' . DOL_URL_ROOT . '/bimpcore/views/js/fixeTabs2.js"></script>';
        echo '<script type="text/javascript" src="' . DOL_URL_ROOT . '/bimpcore/views/js/BimpTimer.js"></script>';
    }

    public function render($content_only = false)
    {
        $html = '';

        if (!$content_only) {
            $html .= '<div id="bimp_fixe_tabs">';
        }

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

        if (!$content_only) {
            $html .= '</div>';
        }

        return $html;
    }
}
