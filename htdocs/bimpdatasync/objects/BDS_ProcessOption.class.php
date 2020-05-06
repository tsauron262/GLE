<?php

class BDS_ProcessOption extends BimpObject
{

    public static $types = array(
        'text'   => 'Champ textuel',
        'select' => 'Liste déroulante',
        'toggle' => 'Choix OUI/NON',
        'file'   => 'Fichier'
    );

    // Getters params: 

    public function getNameProperty()
    {
        // Nécessaire pour régler le conflit avec le champ "name"
        return 'label';
    }

    // Rendus HTML: 

    public function renderOptionInput($id_operation)
    {
        $html = '';

        $errors = array();

        if ($this->isLoaded($errors)) {
            $content = '';
            $field_name = 'operation_' . $id_operation . '_' . $this->getData('name');
            $default_value = (string) $this->getData('default_value');
            switch ($this->getData('type')) {
                case 'text':
                    $content .= BimpInput::renderInput('text', $field_name, $default_value);
                    break;

                case 'select':
                    $values = array();
                    foreach (explode(',', $this->getData('select_values')) as $item) {
                        if (preg_match('/^(.*)=>(.*)$/', $item, $matches)) {
                            $values[$matches[1]] = $matches[2];
                        }
                    }
                    $content .= BimpInput::renderInput('select', $field_name, $default_value, array(
                                'options' => $values
                    ));
                    break;

                case 'toggle':
                    $content .= BimpInput::renderInput('toggle', $field_name, (int) $default_value);
                    break;

                case 'file':
                    $content .= BimpInput::renderInput('file_upload', $field_name);
                    break;

                default:
                    $content .= BimpRender::renderAlerts('Type d\'option invalide: "' . $this->getData('type') . '"');
                    break;
            }

            if ($this->getData('info')) {
                $content .= '<p class="inputHelp">' . $this->getData('info') . '</p>';
            }

            $html .= BimpInput::renderInputContainer($field_name, $default_value, $content, '', (int) $this->getData('required'));
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        return $html;
    }
}
