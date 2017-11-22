<?php

class BimpView
{

    public $object = null;
    public $view_name = null;
    public $view_path = null;
    public $view_identifier = null;
    public $errors = array();

    public function __construct(BimpObject $object, $view_name = 'default')
    {
        $this->object = $object;
        $this->view_name = $view_name;

        if ($this->object->config->isDefined('views/' . $view_name)) {
            $this->view_path = 'views/' . $view_name;
            $this->object->config->setCurrentPath($this->view_path);
        } elseif (($view_name === 'default') && $this->object->config->isDefined('view')) {
            $this->view_path = 'view';
            $this->object->config->setCurrentPath($this->view_path);
        } else {
            $this->errors[] = 'Vue "' . $view_name . '" non définie dans le fichier de configuration';
        }
        if (!count($this->errors) && !is_null($this->view_path)) {
            $this->view_identifier = $this->object->object_name . '_' . $this->object->id . '_' . $this->view_name . '_view';
        }
    }

    public function setConfPath($path = '')
    {
        return $this->object->config->setCurrentPath($this->view_path . '/' . $path);
    }

    public function render($panel = false)
    {
        $html = '';

        $this->setConfPath();

        if (count($this->errors)) {
            $html .= BimpRender::renderAlerts($this->errors);
            return $html;
        }

        if (is_null($this->view_path)) {
            $this->errors[] = 'Erreur d\'initialisation de la vue';
            $html .= BimpRender::renderAlerts($this->errors);
            return $html;
        }

        $labels = $this->object->getLabels();

        $html .= '<script type="text/javascript">';
        $html .= 'object_labels[\'' . $this->object->object_name . '\'] = ' . json_encode($labels);
        $html .= '</script>';

        $no_reload = $this->object->getCurrentConf('no_reload', false, false, 'bool');

        $html .= '<div id="' . $this->view_identifier . '" class="' . ($panel ? 'section ' : '') . 'objectView ' . $this->object->object_name . '_view' . ($no_reload ? ' no_reload' : '') . '"';
        $html .= ' data-module_name="' . $this->object->module . '"';
        $html .= ' data-object_name="' . $this->object->object_name . '"';
        $html .= ' data-view_name="' . $this->view_name . '"';
        $html .= ' data-id_object="' . $this->object->id . '"';
        $html .= '>';

        $content = '';
        $content = $this->renderViewContent();
        $content .= '<div class="ajaxResultContainer" id="' . $this->view_identifier . '_result" style="display: none"></div>';


        if ($panel) {
            $this->setConfPath();
            $title = $this->object->getCurrentConf('title', 'nom');
            if ($title === 'nom') {
                $title = BimpTools::ucfirst($this->object->getInstanceName());
            }
            $edit_form = $this->object->getCurrentConf('edit_form', '');
            $delete_btn = $this->object->getCurrentConf('delete_btn', false, false, 'bool');

            $footer = '';
            if ($edit_form || $delete_btn) {
                $footer .= '<div style="text-align: right">';
                if ($edit_form) {
                    $footer .= '<button type="button" class="btn btn-primary"';
                    $footer .= ' onclick="loadModalFormFromView(\'' . $this->view_identifier . '\', \'' . $edit_form . '\', $(this));"';
                    $footer .= '><i class="fa fa-edit iconLeft"></i>Editer</button>';
                }
                if ($delete_btn) {
                    $footer .= '<button type="button" class="btn btn-danger"';
                    $footer .= ' onclick="deleteObject($(this), \'' . $this->object->module . '\', \'' . $this->object->object_name . '\', ' . $this->object->id . ', $(\'#' . $this->view_identifier . '_result\'));"';
                    $footer .= '><i class="fa fa-trash iconLeft"></i>Supprimer</button>';
                }

                $buttons = $this->object->getCurrentConf('buttons', array(), false, 'array');
                if (count($buttons)) {
                    foreach ($buttons as $idx => $button) {
                        $buttons_params = array(
                            'data' => array(
                                'view_id' => $this->view_identifier
                            )
                        );
                        $footer .= BimpRender::renderButtonFromConfig($this->object->config, $this->view_path . '/buttons/' . $idx, $buttons_params, 'button');
                    }
                }
                $footer .= '</div>';
            }
            $html .= BimpRender::renderPanel($title, $content, $footer, array(
                        'panel_id' => $this->view_identifier . '_panel',
                        'icon'     => 'file-o',
                        'type'     => 'secondary'));
        } else {
            $html .= $content;
        }

        $html .= '</div>';

        return $html;
    }

    public function renderViewContent()
    {
        $content = '<div class="container-fluid object_view_content">';
        if ($this->object->config->isDefined($this->view_path . '/rows')) {
            $content .= BimpStruct::renderRows($this->object->config, $this->view_path . '/rows');
        }
        $content .= '</div>';
        return $content;
    }

    public function renderContent($content_path)
    {
        $content = $this->object->getConf($content_path, null, true, 'string');

        if (!is_null($content)) {
            $method = 'render' . ucfirst($content) . 'Content';
            if (method_exists($this->object, $method)) {
                return $this->object->{$method}();
            }
        }
        return '';
    }
}
