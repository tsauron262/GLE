<?php

class BimpReport extends BimpObject
{

    public static $types = array();

    // Affichages: 

    public function displayBadgeNumber($field)
    {
        $class = 'default';
        $value = 0;

        if ($this->field_exists($field)) {
            $value = (int) $this->getData($field);

            if ($value > 0) {
                switch ($field) {
                    case 'nb_successes':
                        $class = 'success';
                        break;
                    case 'nb_errors':
                        $class = 'danger';
                        break;
                    case 'nb_warnings':
                        $class = 'warning';
                        break;
                    case 'nb_infos':
                        $class = 'info';
                        break;
                }
            }
        }

        return '<span class="badge badge-' . ($class ? $class : 'default') . '">' . $value . '</span>';
    }

    // Rendus HTML: 

    public function renderReportRowsView()
    {
        $html = '';

        return $html;
    }

    // Traitement: 

    public function addLine($data, &$errors = array(), &$warnings = array())
    {
        if ($this->isLoaded($errors)) {
            $data['id_report'] = (int) $this->id;
            $data['time'] = date('H:i:s');

            $line_instance = $this->getChildObject('lines');

            BimpObject::createBimpObject($line_instance->module, $line_instance->object_name, $data, true, $errors, $warnings);

            if (!count($errors)) {
                if (isset($data['type'])) {
                    switch ($data['type']) {
                        case 'success':
                            $this->incField('nb_successes');
                            break;

                        case 'danger':
                            $this->incField('nb_errors');
                            break;

                        case 'warning':
                            $this->incField('nb_warnings');
                            break;

                        case 'info':
                            $this->incField('nb_infos');
                            break;
                    }
                }
            }
        }
    }

    public function end()
    {
        $errors = array();

        if ($this->isLoaded($errors)) {
            $this->set('end', date('Y-m-d H:i:s'));

            $errors = $this->update();
        }

        return $errors;
    }

    public function incField($field)
    {
        if ($this->field_exists($field)) {
            $this->set($field, (int) $this->getData($field) + 1);
        }
    }

    // Overrides: 

    public function create(&$warnings = array(), $force_create = false)
    {
        $this->set('begin', date('Y-m-d H:i:s'));

        return parent::create($warnings, $force_create);
    }
}
