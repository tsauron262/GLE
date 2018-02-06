<?php

class BC_Panel extends BimpComponent
{

    public $identifier;
    public $content_only = false;
    public $level = 1;
    public $data = array(
        'component_type'       => '',
        'component_name'       => '',
        'identifier'           => '',
        'module_name'          => '',
        'object_name'          => '',
        'id_object'            => '',
        'objects_change_reload' => ''
    );

    public function __construct(BimpObject $object, $name, $path, $content_only = false, $level = 1, $title = null, $icon = null)
    {
        $this->params_def['title'] = array();
        $this->params_def['icon'] = array();
        $this->params_def['panel'] = array('data_type' => 'bool', 'default' => 1);
        $this->params_def['objects_change_reload'] = array('data_type' => 'array', 'default' => array());
        $this->params_def['no_reload'] = array('data_type' => 'bool', 'default' => 0);

        $this->content_only = $content_only;
        $this->level = $level;
        $this->identifier = $object->object_name . '_' . ($name ? $name . '_' : '') . static::$type;

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

        $this->data['identifier'] = $this->identifier;
        $this->data['type'] = static::$type;
        $this->data['name'] = $this->name;
        $this->data['module'] = $this->object->module;
        $this->data['object_name'] = $this->object->object_name;
        $this->data['id_object'] = ($this->object->isLoaded() ? $this->object->id : 0);
        $this->data['objects_change_reload'] = $this->params['objects_change_reload'];
    }

    public function renderHtml()
    {
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

            $content = '<div id="' . $this->identifier . '"';
            $content .= ' class="object_component object_' . static::$type;
            $content .= ' ' . $this->object->object_name . '_component ' . $this->object->object_name . '_' . static::$type;
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

            if (!$this->content_only && (int) $this->params['panel']) {
                $title = $this->getTitle();
                $icon = $this->getIcon();

                $footer = $this->renderHtmlFooter();

                $html .= BimpRender::renderPanel($title, $content, $footer, array(
                            'type'           => (($this->level <= 1) ? 'secondary' : 'default'),
                            'foldable'       => true,
                            'id'             => $this->identifier . '_panel',
                            'icon'           => $icon,
                            'header_buttons' => $this->getHeaderButtons()
                ));
            } else {
                $html .= $content;
            }
        }

        $html .= '</div>';

        return $html;
    }

    public function renderHtmlContent()
    {
        return '';
    }

    public function renderHtmlFooter()
    {
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
}
