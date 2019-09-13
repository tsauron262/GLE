<?php

class BC_View extends BC_Panel
{

    public $component_name = 'Fiche';
    public static $type = 'view';
    public $new_values = array();
    public $default_modal_format = 'large';

    public function __construct(BimpObject $object, $name, $content_only = false, $level = 1, $title = null, $icon = null)
    {
        $this->params_def['delete_btn'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['buttons'] = array('type' => 'definitions', 'defs_type' => 'button', 'multiple' => true);
        $this->params_def['edit_form'] = array();

        global $current_bc;
        if (!is_object($current_bc)) {
            $current_bc = null;
        }
        $prev_bc = $current_bc;
        $current_bc = $this;

        $path = '';

        if (!is_null($object) && is_a($object, 'BimpObject')) {
            if (!$name || $name === 'default') {
                if ($object->config->isDefined('view')) {
                    $path = 'view';
                } elseif ($object->config->isDefined('views/default')) {
                    $path = 'views';
                    $name = 'default';
                }
            } else {
                $path = 'views';
            }
        }

        parent::__construct($object, $name, $path, $content_only, $level, $title, $icon);

        if (is_null($this->params['title']) || !$this->params['title']) {
            $this->params['title'] = 'nom';
        }

        if (is_null($this->params['icon']) || !$this->params['icon']) {
            $this->params['icon'] = 'fas_file-alt';
        }

        if ($this->params['title'] === 'nom') {
            if ($this->isObjectValid()) {
                $this->params['title'] = BimpTools::ucfirst($this->object->getInstanceName());
            }
        }

        if (!count($this->errors)) {
            if (!$this->object->can("view")) {
                $this->errors[] = 'Vous n\'avez pas la permission de voir ' . $this->object->getLabel('this');
            }
        }

        $current_bc = $prev_bc;
    }

    public function setNewValues($new_values)
    {
        foreach ($new_values as $field => $value) {
            $this->new_values[$field] = $value;
        }
    }

    public function renderHtmlContent()
    {
        $html = '';

        if (count($this->errors)) {
            $html = BimpRender::renderAlerts($this->errors);
        }

        if (!$this->isOk()) {
            return $html;
        }

        if ($this->object->config->isDefined($this->config_path . '/rows')) {
            $html .= BimpStruct::renderRows($this->object->config, $this->config_path . '/rows', $this);
        }

        return $html;
    }

    public function renderHtmlFooter()
    {
        $html = '';
        if ($this->params['edit_form'] || $this->params['delete_btn']) {
            $html .= '<div class="panelFooterButtons" style="text-align: right">';
            if ($this->params['edit_form'] && $this->object->can("edit") && $this->object->isEditable()) {
                $title = 'Edition ' . $this->object->getLabel('of_the') . ' ' . $this->object->getInstanceName();
                $html .= '<button type="button" class="btn btn-primary"';
                $html .= ' onclick="loadModalFormFromView(\'' . $this->identifier . '\', \'' . $this->params['edit_form'] . '\', $(this), \'' . addslashes($title) . '\');"';
                $html .= '><i class="fas fa5-edit iconLeft"></i>Editer</button>';
            }
            if ($this->params['delete_btn'] && $this->object->can("delete") && $this->object->isDeletable()) {
                $html .= '<button type="button" class="btn btn-danger"';
                $html .= ' onclick="' . $this->object->getJsDeleteOnClick(array(
                            'result_container' => '$(\'#' . $this->identifier . '_result\')',
                            'on_success'       => 'reload'
                        )) . '"';
                $html .= '><i class="fas fa5-trash-alt iconLeft"></i>Supprimer</button>';
            }

            $buttons = $this->object->getCurrentConf('buttons', array(), false, 'array');
            if (count($buttons)) {
                foreach ($buttons as $idx => $button) {
                    $buttons_params = array(
                        'data' => array(
                            'view_id' => $this->identifier
                        )
                    );
                    $html .= BimpRender::renderButtonFromConfig($this->object->config, $this->config_path . '/buttons/' . $idx, $buttons_params, 'button');
                }
            }

            $html .= parent::renderFooterExtraBtn();

            $html .= '</div>';
        }

        return $html;
    }

    public function getHeaderIcons()
    {
        $icons = array();
        if (isset($this->params['header_icons']) && is_array($this->params['header_icons'])) {
            $icons = $this->params['header_icons'];
        }

        $icons[] = array(
            'label'   => 'Actualiser',
            'icon'    => 'fas_redo-alt',
            'onclick' => 'reloadObjectView(\'' . $this->identifier . '\')'
        );

        return $icons;
    }
}
