<?php

class BC_Page extends BimpComponent
{

    public static $type = 'page';
    public static $config_required = false;
    public $component_name = 'Page';
    public $identifier = '';
    public $content_only = false;
    public $current_navtab = '';

    public function __construct(BimpObject $object, $name = '', $content_only = false)
    {
        $this->params_def['object_header'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['nav_tabs'] = array('type' => 'keys');

        $this->content_only = $content_only;

        parent::__construct($object, $name);

        if ($this->isOk()) {
            if (!$this->object->isLoaded()) {
                $this->errors[] = 'ID ' . $this->object('of_the') . ' absent';
            } else {
                $this->identifier = $this->object->object_name . '_' . $this->object->id . '_page';
                if ($this->name) {
                    $this->identifier .= '_' . $this->name;
                }
            }
        }
    }

    public function renderHtml()
    {
        if (!$this->isOk()) {
            return parent::renderHtml();
        }

        $html = '';

        if (!$this->content_only) {
            $html .= '<div id="' . $this->identifier . '" class="object_page ' . $this->object->object_name . '_page"';
            $html .= ' data-identifier="' . $this->identifier . '"';
            $html .= ' data-module="' . $this->object->module . '"';
            $html .= ' data-object_name="' . $this->object->object_name . '"';
            $html .= ' data-id_object="' . $this->object->id . '"';
            $html .= '>';
        }

        if ((int) $this->params['object_header']) {
            $html .= $this->object->renderHeader();
        }
        
        if (count($this->params['nav_tabs'])) {
            
        }

        if (!$this->content_only) {
            $html .= '</div>';
        }

        return $html;
    }
}
