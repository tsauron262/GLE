<?php

class BC_View extends BC_Panel
{

    public $component_name = 'Fiche';
    public static $type = 'view';
    public $new_values = array();

    public function __construct(BimpObject $object, $name, $content_only = false, $level = 1, $title = null, $icon = null)
    {
        $this->params_def['delete_btn'] = array('data_type' => 'bool', 'default' => 0);
        $this->params_def['buttons'] = array('type' => 'definitions', 'defs_type' => 'button', 'multiple' => true);
        $this->params_def['edit_form'] = array();

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

        parent::__construct($object, $name, $path, $content_only, $level, $title, $icon);

        if (is_null($this->params['title']) || !$this->params['title']) {
            $this->params['title'] = 'nom';
        }

        if (is_null($this->params['icon']) || !$this->params['icon']) {
            $this->params['icon'] = 'far_file';
        }

        if ($this->params['title'] === 'nom') {
            if ($this->isObjectValid()) {
                $this->params['title'] = BimpTools::ucfirst($this->object->getInstanceName());
            }
        }

        if (!count($this->errors)) {
            if (!$this->object->canView()) {
                $this->errors[] = 'Vous n\'avez pas la permission de voir ' . $this->object->getLabel('this');
            }
        }
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
            $html .= '<div style="text-align: right">';
            if ($this->params['edit_form'] && $this->object->canEdit()) {
                $title = 'Edition ' . $this->object->getLabel('of_the') . ' ' . $this->object->getInstanceName();
                $html .= '<button type="button" class="btn btn-primary"';
                $html .= ' onclick="loadModalFormFromView(\'' . $this->identifier . '\', \'' . $this->params['edit_form'] . '\', $(this), \'' . addslashes($title) . '\');"';
                $html .= '><i class="fas fa5-edit iconLeft"></i>Editer</button>';
            }
            if ($this->params['delete_btn'] && $this->object->canDelete()) {
                $html .= '<button type="button" class="btn btn-danger"';
                $html .= ' onclick="deleteObject($(this), \'' . $this->object->module . '\', \'' . $this->object->object_name . '\', ' . $this->object->id . ', $(\'#' . $this->identifier . '_result\'));"';
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
}
