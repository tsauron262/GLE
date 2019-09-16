<?php

class BC_Panel extends BimpComponent
{

    public $component_name = 'Panel';
    public $identifier;
    public $content_only = false;
    public $level = 1;
    public $default_modal_format = 'medium';
    public $data = array(
        'identifier'            => '',
        'type'                  => '',
        'name'                  => '',
        'module'                => '',
        'object_name'           => '',
        'id_object'             => '',
        'objects_change_reload' => ''
    );

    public function __construct(BimpObject $object, $name, $path, $content_only = false, $level = 1, $title = null, $icon = null)
    {
        $this->params_def['title'] = array();
        $this->params_def['icon'] = array();
        $this->params_def['panel'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['panel_header'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['panel_footer'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['objects_change_reload'] = array('data_type' => 'array', 'default' => array());
        $this->params_def['no_reload'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['header_buttons'] = array('data_type' => 'array', 'compile' => true);
        $this->params_def['header_icons'] = array('type' => 'definitions', 'defs_type' => 'icon_button', 'multiple' => true);
        $this->params_def['footer_extra_btn'] = array('data_type' => 'array', 'default' => array(), 'compile' => true);
        $this->params_def['footer_extra_content'] = array('default' => '');
        $this->params_def['msgs'] = array('data_type' => 'array', 'default' => null, 'compile' => true);
        $this->params_def['before_content'] = array('default' => '');
        $this->params_def['after_content'] = array('default' => '');
        $this->params_def['modal_format'] = array('default' => $this->default_modal_format);

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $this->content_only = (int) $content_only;
        $this->level = $level;
        $this->identifier = $object->object_name . '_' . ($name ? $name . '_' : '') . static::$type;

        if (BimpObject::objectLoaded($object)) {
            $this->identifier .= '_' . $object->id;
        }

        if (isset($this->id_parent) && !is_null($this->id_parent) && $this->id_parent) {
            if (!is_null($object)) {
                $parent_object_name = $object->getParentObjectName();
                if (!is_null($parent_object_name) && $parent_object_name) {
                    $this->identifier .= '_' . $parent_object_name . '_' . $this->id_parent;
                }
            }
        }

        parent::__construct($object, $name, $path);

        if (!is_null($title)) {
            $this->params['title'] = $title;
        }

        if (!is_null($icon)) {
            $this->params['icon'] = $icon;
        }

        if (is_string($this->params['objects_change_reload'])) {
            $this->params['objects_change_reload'] = array($this->params['objects_change_reload']);
        }

        $this->data['identifier'] = $this->identifier;
        $this->data['type'] = static::$type;
        $this->data['name'] = $this->name;
        $this->data['module'] = $this->object->module;
        $this->data['object_name'] = $this->object->object_name;
        $this->data['id_object'] = ($this->object->isLoaded() ? $this->object->id : 0);
        $this->data['objects_change_reload'] = implode(',', $this->params['objects_change_reload']);

        $current_bc = $prev_bc;
    }

    public function renderHtml()
    {
        if ((int) !$this->params['show']) {
            return '';
        }
        
        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $html = '';
        $this->setConfPath();

        if (is_null($this->identifier)) {
            $this->identifier = '';
        }

        $labels = $this->object->getLabels();
        $html = '<script type="text/javascript">';
        $html .= 'object_labels[\'' . $this->object->object_name . '\'] = ' . json_encode($labels);
        $html .= '</script>';

        $html .= '<div id="' . $this->identifier . '_container"';
        $html .= ' class="objectComponentContainer object_' . static::$type . '_container ';
        $html .= $this->object->object_name . '_' . static::$type . '_container"';
        $html .= '>';


        if (count($this->errors)) {
            $html .= BimpRender::renderAlerts($this->errors);
        } else {
            $content = '';

            if ($this->params['before_content']) {
                $content .= '<div class="beforePanelContainer">';
                $content .= $this->params['before_content'];
                $content .= '</div>';
            }

            $content .= '<div id="' . $this->identifier . '"';
            $content .= ' class="object_component object_' . static::$type;
            $content .= ' ' . $this->object->object_name . '_component ' . $this->object->object_name . '_' . static::$type;
            $content .= ' ' . $this->object->object_name . '_' . static::$type . '_' . $this->name;
            if ((int) $this->params['no_reload']) {
                $content .= ' no_reload';
            }
            $content .= '"';
            foreach ($this->data as $data_name => $data_value) {
                $content .= ' data-' . $data_name . '="' . $data_value . '"';
            }
            $content .= '>';

            $content .= '<div id="' . $this->identifier . '_params" class="object_component_params">';
            foreach ($this->params_def as $param_name => $defs) {
                if (isset($defs['request']) && $defs['request']) {
                    $value = $this->params[$param_name];
                    if (is_null($value)) {
                        $value = '';
                    }
                    $content .= '<input type="hidden" class="object_component_param" name="param_' . $param_name . '" value="';
                    if (isset($defs['json']) && $defs['json']) {
                        $content .= htmlentities(json_encode($value));
                    } else {
                        $content .= $value;
                    }
                    $content .= '"/>';
                }
            }
            $content .= '</div>';

            $content .= '<div class="container-fluid object_component_content object_' . static::$type . '_content">';
            $content .= $this->renderHtmlContent();
            $content .= '</div>';

            $content .= '<div class="ajaxResultContainer" id="' . $this->identifier . '_result" style="display: none"></div>';
            $content .= '</div>';

            if ($this->params['after_content']) {
                $content .= '<div class="afterPanelContainer">';
                $content .= $this->params['after_content'];
                $content .= '</div>';
            }

            $html .= $this->renderBeforePanelHtml();

            if (!$this->content_only && (int) $this->params['panel']) {
                $title = $this->getTitle();
                $icon = $this->getIcon();

                $footer = $this->renderHtmlFooter();

                $html .= BimpRender::renderPanel($title, $content, $footer, array(
                            'type'           => (($this->level <= 1) ? 'secondary' : 'default'),
                            'foldable'       => true,
                            'id'             => $this->identifier . '_panel',
                            'icon'           => $icon,
                            'header_buttons' => $this->getHeaderButtons(),
                            'header_icons'   => $this->getHeaderIcons(),
                            'no_header'      => (int) !$this->params['panel_header'],
                            'no_footer'      => (int) !$this->params['panel_footer']
                ));
            } else {
                $html .= $content;
            }
        }

        $html .= '</div>';

        $current_bc = $prev_bc;
        return $html;
    }

    public function renderBeforePanelHtml()
    {
        return '';
    }

    public function renderHtmlContent()
    {
        $html = '';

        if (!is_null($this->params['msgs']) && count($this->params['msgs'])) {
            foreach ($this->params['msgs'] as $msg) {
                $html .= BimpRender::renderAlerts($msg['content'], $msg['type']);
            }
        }

        return $html;
    }

    public function renderHtmlFooter()
    {
        $html = '<div class="panelFooterButtons" style="text-align: right">';
        $html .= $this->renderFooterExtraBtn();
        if ($this->params['footer_extra_content']) {
            $html .= $this->params['footer_extra_content'];
        }
        $html .= '</div>';

        return $html;
    }

    public function renderFooterExtraBtn()
    {
        if (count($this->params['footer_extra_btn'])) {
            $items = array();

            foreach ($this->params['footer_extra_btn'] as $action_params) {
                $button = null;
                $label = isset($action_params['label']) ? $action_params['label'] : '';
                $onclick = isset($action_params['onclick']) ? $action_params['onclick'] : '';
                $icon = isset($action_params['icon']) ? $action_params['icon'] : '';
                $onclick = str_replace('component_id', $this->identifier, $onclick);
                $disabled = isset($action_params['disabled']) ? (int) $action_params['disabled'] : 0;
                $popover = isset($action_params['popover']) ? (string) $action_params['popover'] : '';
                $classes = array('btn', 'btn-light-default');
                if ($disabled) {
                    $classes[] = 'disabled';
                }
                if ($popover) {
                    $classes[] = 'bs-popover';
                }
                if ($label) {
                    $button = array(
                        'classes' => $classes,
                        'label'   => $label,
                        'attr'    => array(
                            'type'    => 'button',
                            'onclick' => $onclick
                        )
                    );
                    if ($icon) {
                        $button['icon_before'] = $icon;
                    }
                    if ($popover) {
                        $button['data']['toggle'] = 'popover';
                        $button['data']['trigger'] = 'hover';
                        $button['data']['container'] = 'body';
                        $button['data']['placement'] = 'top';
                        $button['data']['html'] = 'false';
                        $button['data']['content'] = $popover;
                    }
                }

                if (!is_null($button)) {
                    $items[] = BimpRender::renderButton($button, 'button');
                }
            }

            if (count($items)) {
                if (count($items) > 1) {
                    return BimpRender::renderDropDownButton('Actions', $items, array(
                                'icon'       => 'fas_cogs',
                                'menu_right' => 1
                    ));
                }
                return str_replace('btn-light-default', 'btn-default', $items[0]);
            }
        }

        return '';
    }

    public function getTitle()
    {
        if (isset($this->params['title'])) {
            return $this->params['title'];
        }

        if ($this->isObjectValid()) {
            return $this->object->getInstanceName();
        }

        return '';
    }

    public function getIcon()
    {
        if (isset($this->params['icon'])) {
            return $this->params['icon'];
        }
        return '';
    }

    public function getHeaderButtons()
    {
        if (isset($this->params['header_buttons'])) {
            return $this->params['header_buttons'];
        }
        return array();
    }

    public function getHeaderIcons()
    {
        if (isset($this->params['header_icons'])) {
            return $this->params['header_icons'];
        }
        return array();
    }

    public function addObjectChangeReload($object_name)
    {
        if (!in_array($object_name, $this->params['objects_change_reload'])) {
            $this->params['objects_change_reload'][] = $object_name;
            $this->data['objects_change_reload'] = implode(',', $this->params['objects_change_reload']);
        }
    }

    public function addIdentifierSuffix($suffix)
    {
        $this->identifier .= '_' . $suffix;
        $this->data['identifier'] = $this->identifier;
    }
}
