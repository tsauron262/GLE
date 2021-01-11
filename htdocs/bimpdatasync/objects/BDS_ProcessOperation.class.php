<?php

class BDS_ProcessOperation extends BimpObject
{

    // Getters params: 

    public function getNameProperty()
    {
        // Nécessaire pour régler le conflit avec le champ "name"
        return 'title';
    }

    // Getters données: 

    public function getPostOptions(&$options, &$errors = array(), &$warnings = array())
    {
        if ($this->isLoaded($errors)) {
            $process = $this->getParentInstance();

            if (!BimpObject::objectLoaded($process)) {
                $errors[] = 'ID du processus absent';
                return;
            }

            $options_list = $this->getAssociatesObjects('options');

            foreach ($options_list as $option) {
                $input_name = 'operation_' . (int) $this->id . '_' . $option->getData('name');

                switch ($option->getData('type')) {
                    case 'text':
                    case 'select':
                    case 'toggle':
                    case 'date':
                    case 'datetime':
                        $value = BimpTools::getPostFieldValue($input_name, null);
                        if (is_null($value)) {
                            $value = $option->getData('default_value');

                            if (is_null($value) && (int) $option->getData('required')) {
                                $errors[] = 'Option obligatoire non spécifiée: "' . $option->getData('label') . '"';
                            }

                            if (is_null($value)) {
                                $value = '';
                            }
                        }
                        $options[$option->getData('name')] = $value;
                        break;

                    case 'file':
                        if (isset($_FILES[$input_name]['tmp_name']) && $_FILES[$input_name]['tmp_name']) {
                            if (!file_exists($_FILES[$input_name]['tmp_name'])) {
                                $errors[] = 'Echec du transfert du fichier';
                                continue;
                            }

                            $dir = 'bimpdatasync/processes/' . $process->id . '/' . $this->id . '/' . date('Y') . '/' . date('m') . '/' . date('d');

                            if (!file_exists(PATH_TMP . '/' . $dir)) {
                                $error = BimpTools::makeDirectories($dir, PATH_TMP);

                                if ($error) {
                                    $errors[] = 'Echec de la création des dossiers de destination des fichiers';
                                    continue;
                                }
                            }

                            $fileName = pathinfo($_FILES[$input_name]['name'], PATHINFO_FILENAME);
                            $ext = pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION);

                            $filePath = PATH_TMP . '/' . $dir . '/' . $fileName . '_' . date('his') . '.' . $ext;

                            if (!copy($_FILES[$input_name]['tmp_name'], $filePath)) {
                                $errors[] = 'Echec de la copie du fichier "' . $_FILES[$input_name]['name'] . '"';
                            } else {
                                $options[$option->getData('name')] = $filePath;
                            }
                        } elseif ((int) $option->getData('required')) {
                            $errors[] = 'Fichier obligatoire absent: "' . $option->getData('label') . '"';
                        }
                        break;
                }
            }
            $options['debug'] = (int) BimpTools::getPostFieldValue('operation_' . (int) $this->id . '_debug_active', 0);
        }
    }

    // Rendus HTML: 

    public function renderExecutionForm($panel = true)
    {
        if (!$this->isLoaded()) {
            return BimpRender::renderAlerts('ID de l\'opération absent');
        }

        $process = $this->getParentInstance();

        if (!BimpObject::objectLoaded($process)) {
            return BimpRender::renderAlerts('ID du processus absent');
        }

        global $user;

        $html = '';

        $title = (string) $this->getData('title');
        if (!$title) {
            $title = 'Opération #' . $this->id;
        }

        $op_html = '';

        $desc = (string) $this->getData('description');
        if ($desc) {
            $op_html .= BimpRender::renderAlerts($desc, 'info');
        }

        $warning = (string) $this->getData('warning');
        if ($warning) {
            $op_html .= BimpRender::renderAlerts($warning, 'warning');
        }

        $options = $this->getAssociatesList('options');

        $op_html .= '<form id="process_' . $process->id . '_operation_' . $this->id . '_form" class="processOperationForm" data-id_process="' . $process->id . '" data-id_operation="' . $this->id . '">';

        $op_html .= '<input type="hidden" name="id_process" value="' . $process->id . '"/>';
        $op_html .= '<input type="hidden" name="id_operation" value="' . $this->id . '"/>';

        if (!empty($options)) {
            foreach ($options as $id_option) {
                $option = BimpCache::getBimpObjectInstance('bimpdatasync', 'BDS_ProcessOption', (int) $id_option);
                $label = '';
                if (BimpObject::objectLoaded($option)) {
                    $label = $option->getData('label');
                } else {
                    $label = 'Option #' . $id_option;
                }
                $input = '<div data-id_option="' . $id_option . '">';
                if (BimpObject::objectLoaded($option)) {
                    $input .= $option->renderOptionInput($this->id);
                } else {
                    $input .= BimpRender::renderAlerts('L\'option d\'ID ' . $id_option . ' n\'existe plus');
                }
                $input .= '</div>';

                $op_html .= BimpRender::renderFormRow($label, $input);
            }
        }

        if (BimpObject::objectLoaded($user) && $user->admin) {
            $label = 'Mode débug';
            $field_name = 'operation_' . $this->id . '_debug_active';
            $input = BimpInput::renderInput('toggle', $field_name, 1);
            $input = BimpInput::renderInputContainer($field_name, 1, $input);
            $op_html .= BimpRender::renderFormRow($label, $input);
        }

        $op_html .= '</form>';

        $op_html .= '<div id="process_' . $process->id . '_operation_' . $this->id . '_ajaxResultContainer" class="ajaxResultContainer">';
        $op_html .= '</div>';

        $op_footer = '';

        $op_footer .= '<div class="buttonsContainer align-right">';
        $op_footer .= '<span class="btn btn-primary executeProcessOperationBtn" onclick="bds_initProcessOperation($(this), ' . $process->id . ', ' . $this->id . ')">';
        $op_footer .= BimpRender::renderIcon('fas_cogs', 'iconLeft') . 'Exécuter';
        $op_footer .= '</span>';
        $op_footer .= '</div>';

        $html .= '<div class="col-xs-12 col-sm-12 col-md-6">';
        if ($panel) {
            $html .= BimpRender::renderPanel($title, $op_html, $op_footer, array(
                        'type' => 'secondary'
            ));
        } else {
            $html .= '<h3>' . $title . '</h3>';
            $html .= $op_html;
            $html .= $op_footer;
        }
        $html .= '</div>';


        return $html;
    }
}
