<?php

class BDS_ProcessOption extends BimpObject
{

    public static $types = array(
        'text'     => 'Champ textuel',
        'select'   => 'Liste déroulante',
        'toggle'   => 'Choix OUI/NON',
        'date'     => 'Date',
        'datetime' => 'Date et heure',
        'file'     => 'Fichier'
    );

    // Getters params: 

    public function getNameProperties()
    {
        // Nécessaire pour régler le conflit avec le champ "name"
        return array('label');
    }

    // Rendus HTML: 

    public function renderOptionInput($id_operation = 0, $field_name = null, $value = null)
    {
        $html = '';

        $errors = array();

        if ($this->isLoaded($errors)) {
            $content = '';

            if (is_null($field_name)) {
                $field_name = '';

                if ($id_operation) {
                    $field_name .= 'operation_' . $id_operation . '_';
                }
                $field_name .= $this->getData('name');
            }

            if (is_null($value)) {
                $value = (string) $this->getData('default_value');
            }

            switch ($this->getData('type')) {
                case 'text':
                    $content .= BimpInput::renderInput('text', $field_name, $value);
                    break;

                case 'select':
                    $values = array();
                    foreach (explode(',', $this->getData('select_values')) as $item) {
                        if (preg_match('/^(.*)=>(.*)$/', $item, $matches)) {
                            $values[$matches[1]] = $matches[2];
                        }
                    }
                    $content .= BimpInput::renderInput('select', $field_name, $value, array(
                                'options' => $values
                    ));
                    break;

                case 'toggle':
                    $content .= BimpInput::renderInput('toggle', $field_name, (int) $value);
                    break;

                case 'date':
                    $content .= BimpInput::renderInput('date', $field_name, $value);
                    break;

                case 'datetime':
                    $content .= BimpInput::renderInput('datetime', $field_name, $value);
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

            $html .= BimpInput::renderInputContainer($field_name, $value, $content, '', (int) $this->getData('required'));
        }

        if (count($errors)) {
            $html .= BimpRender::renderAlerts($errors);
        }

        return $html;
    }

    // Overrides: 

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $id = (int) $this->id;

        $errors = parent::delete($warnings, $force_delete);

        if (!count($errors)) {
            $where = 'association = \'options\' AND dest_object_module = \'bimpdatasync\' AND dest_object_name = \'BDS_ProcessOption\' AND dest_id_object = ' . (int) $id;
            $this->db->delete('bimpcore_objects_associations', $where);

            $cronOptions = BimpCache::getBimpObjectObjects('bimpdatasync', 'BDS_ProcessCronOption', array(
                        'id_option' => $id
            ));

            foreach ($cronOptions as $cronOption) {
                $cronOption->delete($w, true);
            }
        }
    }
}
