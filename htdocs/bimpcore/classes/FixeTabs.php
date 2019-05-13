<?php
class FixeTabs
{

    public $errors = array();
    protected $tabs = array();
    public $modules = array("bimptask", "bimpsupport", "bimpmsg");
    public $objs = array();

    public function __construct() {
        global $user;
        foreach($this->modules as $module){
            $file = DOL_DOCUMENT_ROOT."/".$module."/fixeTabs.php";
            if(is_file($file)){
                require_once($file);
                $class = "FixeTabs_".$module;
                $obj = new $class($this, $user);
                if($obj->can("view"))
                    $this->objs[] = $obj;
            }
            else
                dol_syslog ("Fichier introuvable ".$file,3);
        }
    }
    
    public function addTab($id, $caption, $content, $classes = array())
    {
        $this->tabs[] = array(
            'id'      => $id,
            'caption' => $caption,
            'content' => $content,
            'classes' => $classes
        );
    }

    public function can($right)
    {
        foreach($this->objs as $obj)
            if($obj->can($right))
                return 1;
        
        return 0;
    }

    public function init()
    {
        foreach($this->objs as $obj){
            $obj->init();
        }
    }

    public function displayHead($echo = true)
    {
        $html =  '<link type="text/css" rel="stylesheet" href="' . DOL_URL_ROOT . '/bimpcore/views/css/fixeTabs.css"/>';
        $html .= '<script type="text/javascript" src="' . DOL_URL_ROOT . '/bimpcore/views/js/fixeTabs.js"></script>';
        foreach($this->objs as $obj)
            $html .= $obj->displayHead();
        
        if($echo)
            echo $html;
        return $html;
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

        $html .= '<div id="bimp_fixe_tabs_contents">'; // Ne pas mettre de styles dans cette balise (conflit js). 
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
