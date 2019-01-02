<?php

class BC_Page extends BimpComponent
{

    public static $type = 'page';
    public static $config_required = false;
    public $component_name = 'Page';
    public $identifier = '';
    public $content_only = false;
    public $header_locked = false;
    public $current_navtab = '';
    public $tabs = array();

    public function __construct(BimpObject $object, $name = '', $content_only = false)
    {
        $this->params_def['object_header'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['nav_tabs'] = array('type' => 'keys');

        $this->content_only = $content_only;

        parent::__construct($object, $name);

        if ($this->isOk()) {
            if (!$this->object->isLoaded()) {
                $this->errors[] = 'ID ' . $this->object->getLabel('of_the') . ' absent';
            } else {
                $this->identifier = $this->object->object_name . '_' . $this->object->id . '_page';
                if ($this->name) {
                    $this->identifier .= '_' . $this->name;
                }
            }

            $this->fetchTabs();
        }
        
        global $bimpUser;
        if (BimpObject::objectLoaded($bimpUser)) {
            $this->header_locked = (int) $bimpUser->getData('object_header_locked');
        } else {
            echo 'KO'; exit;
        }
    }

    public function fetchTabs()
    {
        $this->tabs = array();
        if (count($this->params['nav_tabs'])) {
            $path = $this->config_path . '/nav_tabs';
            foreach ($this->params['nav_tabs'] as $idx) {
                if (!(int) $this->object->config->get($path . '/' . $idx . '/show', 1, false, 'bool')) {
                    continue;
                }
                $content = '';
                if ($this->object->config->isDefined($path . '/' . $idx . '/struct')) {
                    $content = BimpStruct::renderStruct($this->object->config, $path . '/' . $idx . '/struct');
                } elseif ($this->object->config->isDefined($path . '/' . $idx . '/content')) {
                    $content = $this->object->config->get($path . '/' . $idx . '/content');
                }
                $this->tabs[] = array(
                    'id'      => $this->object->config->get($path . '/' . $idx . '/id', '', true),
                    'title'   => $this->object->config->get($path . '/' . $idx . '/title', '', true),
                    'content' => $content
                );
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

        $html .= '<div class="object_page_header' . ($this->header_locked ? ' locked' : '') . '">';

        if ((int) $this->params['object_header']) {
            $html .= $this->object->renderHeader(false, array(
                'allow_lock' => 1,
                'locked'     => (int) $this->header_locked
            ));
        }

        if (count($this->tabs)) {
            $html .= BimpRender::renderNavTabs($this->tabs, 'maintabs', array(
                        'nav_only' => 1
            ));
        }

        $html .= '</div>';

        $html .= '<div class="object_page_content">';

        if (count($this->tabs)) {
            $html .= BimpRender::renderNavTabs($this->tabs, 'maintabs', array(
                        'content_only' => 1
            ));
        } elseif ($this->object->config->isDefined($this->config_path . '/content')) {
            $html .= $this->object->getConf($this->config_path . '/content', '');
        }

        $html .= '</div>';

        if (!$this->content_only) {
            $html .= '</div>';
        }

        return $html;
    }
}
