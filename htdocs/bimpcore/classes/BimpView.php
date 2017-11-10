<?php

class BimpView
{

    protected $object = null;
    protected $view_name = null;
    protected $view_path = null;
    protected $view_identifier = null;
    public $errors = array();

    public function __construct(BimpObject $object, $view_name = 'default')
    {
        $this->object = $object;
        $this->view_name = $view_name;

        if ($this->object->config->isDefined('views/' . $view_name)) {
            $this->view_path = 'views/' . $view_name;
            $this->object->config->setCurrentPath($this->view_path);
        } elseif (($view_name === 'default') && $this->object->config->isDefined('view')) {
            $this->list_path = 'view';
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
        return $this->object->config->setCurrentPath($this->view_name . '/' . $path);
    }

    public function render()
    {
        $html = '';

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

        $html .= '<div id="' . $this->view_identifier . '" class="objectView ' . $this->object->object_name . '_view"';
        $html .= ' data-module_name="' . $this->object->module . '"';
        $html .= ' data-object_name="' . $this->object->object_name . '"';
        $html .= ' data-view_name="' . $this->view_name . '"';
        $html .= ' data-id_object="' . $this->object->id . '"';
        $html .= '>';

        $title = $this->object->getCurrentConf('title', 'nom');
        if ($title === 'nom') {
            $title = BimpTools::ucfirst($this->object->getInstanceName());
        }
        $edit_btn = $this->object->getCurrentConf('edit_btn');
        $delete_btn = $this->object->getCurrentConf('delete_btn');

        $content = $this->renderViewContent();

        $content .= '<div class="ajaxResultContainer" id="' . $this->view_identifier . '_result" style="display: none"></div>';

        $footer = '';
        if ($edit_btn || $delete_btn) {
            $footer .= '<div style="text-align: right">';
            if ($edit_btn) {
//                $footer .= '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#' . $this->view_identifier . '_modal">Editer</button>';
                $footer .= '<button type="button" class="btn btn-primary"';
                $footer .= ' onclick="loadModalFormFromView(\'' . $this->view_identifier . '\', \'default\', $(this));"';
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
                    $footer .= BimpRender::renderButtonFromConfig($this->object->config, $this->view_path.'/buttons/'.$idx, $this->object, $buttons_params, 'button');
                }
            }
            $footer .= '</div>';
        }


        $html .= BimpRender::renderPanel($title, $content, $footer, array(
                    'panel_id' => $this->view_identifier . '_panel',
                    'icon'     => 'file-o',
                    'type'     => 'secondary'));

        $html .= '</div>';

        $html .= BimpRender::renderAjaxModal($this->view_identifier . '_modal');
        return $html;
    }

    public function renderViewContent()
    {
        $content = $this->renderContent($this->view_path . '/content');
        if (!$content) {
            if ($this->object->config->isDefined($this->view_path . '/rows')) {
                $content = '<div class="container-fluid">';
                $content .= $this->renderRows($this->view_path . '/rows');
                $content .= '</div>';
            }
        }
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

    public function renderRows($rows_path)
    {
        $html = '';
        $rows = $this->object->getConf($rows_path, '', true, 'any');
        if (is_string($rows)) {
            if ($rows === 'common_fields') {
                $html .= $this->renderCommonFields();
            }
        } elseif (is_array($rows)) {
            foreach ($rows as $idx_row => $row) {
                $html .= '<div class="row">';
                $cols = $this->object->getConf($rows_path . '/' . $idx_row . '/cols', array(), true, 'array');
                foreach ($cols as $idx_col => $col) {
                    $html .= $this->renderCol($rows_path . '/' . $idx_row . '/cols/' . $idx_col);
                }
                $html .= '</div>';
            }
        }
        return $html;
    }

    public function renderCol($col_path)
    {
        $html = '';

        $col_lg = $this->object->getConf($col_path . '/col_lg', 12, false, 'int');
        $col_md = $this->object->getConf($col_path . '/col_md', null, false, 'int');
        $col_sm = $this->object->getConf($col_path . '/col_sm', null, false, 'int');
        $col_xs = $this->object->getConf($col_path . '/col_xs', null, false, 'int');

        if (!is_int($col_lg)) {
            $col_lg = 12;
        }
        if ($col_lg > 12) {
            $col_lg = 12;
        }

        if (is_null($col_md)) {
            $col_md = $col_lg;
        }

        if (is_null($col_sm)) {
            $col_sm = ($col_md * 2);
            if ($col_sm > 12) {
                $col_sm = 12;
            }
        }
        if (is_null($col_xs)) {
            $col_xs = $col_sm * 2;
            if ($col_xs > 12) {
                $col_xs = 12;
            }
        }
        $html .= '<div class="col-xs-' . $col_xs . ' col-sm-' . $col_sm . ' col-md-' . $col_md . ' col-lg-' . $col_lg . '">';

        $type = $this->object->getConf($col_path . '/type', 'rows');
        switch ($type) {
            case 'table':
                $html .= $this->renderTable($col_path);
                break;

            case 'list':
            case 'view':
            case 'form':
                break;

            case 'custom';
                $html .= $this->object->getConf($col_path . '/content', '', true);
                break;

            case 'rows':
                $html .= $this->renderRows($col_path . '/rows');
                break;
        }

        $html .= '</div>';

        return $html;
    }

    public function renderTable($col_path)
    {
        $html = '<table class="objectViewtable">';
        $html .= '<thead></thead>';
        $html .= '<tbody>';

        $rows = $this->object->getConf($col_path . '/rows', array(), true, 'array');
        foreach ($rows as $idx => $row) {
            $row_path = $col_path . '/rows/' . $idx;
            $field = $this->object->getConf($row_path . '/field', '');
            $html .= '<tr>';
            if ($field) {
                $html .= '<th>';
                $html .= $this->object->getConf('fields/' . $field . '/label', '');
                $html .= '</th>';
                $display = $this->object->getConf($row_path . '/display', 'default');
                $html .= '<td>';
                $html .= $this->object->displayData($field, $display);
                $html .= '</td>';
            } else {
                $label = $this->object->getConf($row_path . '/label', '', true);
                $value = $this->object->getConf($row_path . '/value', '', true);
                if ($label && $value) {
                    $html .= '<th>' . $label . '</th>';
                    $html .= '<td>' . $value . '</td>';
                }
            }
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    public function renderCommonFields()
    {
        $html = '';

        $html .= '<div class="object_common_fields">';
        $html .= '<table>';
        $html .= '<thead></thead>';
        $html .= '<tbody>';

        $html .= '<tr>';
        $html .= '<th>ID:</th>';
        $html .= '<td>' . $this->object->id . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Créé le:</th>';
        $html .= '<td>' . $this->object->displayData('date_create') . '</td>';
        $html .= '</tr>';

        $html .= '<tr>';
        $html .= '<th>Par:</th>';
        $html .= '<td>' . $this->object->displayData('user_create') . '</td>';
        $html .= '</tr>';

        $user_update = $this->object->getData('user_update');
        if (!is_null($user_update) && $user_update) {
            $html .= '<tr>';
            $html .= '<th>Dernière mise à jour le:</th>';
            $html .= '<td>' . $this->object->displayData('date_update') . '</td>';
            $html .= '</tr>';

            $html .= '<tr>';
            $html .= '<th>Par:</th>';
            $html .= '<td>' . $this->object->displayData('user_update') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }
}
