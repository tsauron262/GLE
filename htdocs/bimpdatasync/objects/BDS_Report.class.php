<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpReport.class.php';

class BDS_Report extends BimpReport
{

    public $objects_data = null;
    public static $types = array(
        'ajax'     => 'Opération manuelle',
        'trigger'  => 'Opération automatique',
        'cron'     => 'Tâche planifiée',
        'requests' => 'Requête entrante'
    );

    // Gestion des données d'objets: 

    public function loadObjetsData()
    {
        if ($this->isLoaded()) {
            if (is_null($this->objects_data)) {
                $this->objects_data = array();

                foreach ($this->getChildrenObjects('objects_data') as $od) {
                    $module = (string) $od->getData('obj_module');
                    $object_name = (string) $od->getData('obj_name');

                    if ($module && $object_name) {
                        $this->objects_data[$module . '/' . $object_name] = $od;
                    }
                }
            }
        }
    }

    public function increaseObjectData($module, $object_name, $field_name)
    {
        if ($this->isLoaded()) {
            $this->loadObjetsData();

            $key = $module . '/' . $object_name;

            if (!isset($this->objects_data[$key])) {
                $this->objects_data[$key] = BimpObject::createBimpObject('bimpdatasync', 'BDS_ReportObjectData', array(
                            'id_report'  => (int) $this->id,
                            'obj_module' => $module,
                            'obj_name'   => $object_name
                ));
            }

            if (isset($this->objects_data[$key]) && BimpObject::objectLoaded($this->objects_data[$key])) {
                if ($this->objects_data[$key]->field_exists($field_name)) {
                    $this->objects_data[$key]->set($field_name, (int) $this->objects_data[$key]->getData($field_name) + 1);
                }
            }
        }
    }

    public function saveObjectsData()
    {
        if (!is_null($this->objects_data)) {
            foreach ($this->objects_data as $od) {
                $od->update();
            }
        }
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            $this->saveObjectsData();
        }

        return $errors;
    }
}
