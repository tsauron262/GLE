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
    
    public function delete(&$warnings = array(), $force_delete = false) {
        if (!$this->isLoaded()) {
            $errors[] = 'ID ' . $this->getLabel('of_the') . ' absent';
        } elseif (!$force_delete && !$this->can("delete")) {
            $errors[] = 'Vous n\'avez pas la permission de supprimer ' . $this->getLabel('this');
        } elseif (!$this->isDeletable($force_delete, $errors)) {
            if (empty($errors)) {
                $errors[] = 'Il n\'est pas possible de supprimer ' . $this->getLabel('this');
            }
        }
        if(!count($errors)){
            $this->db->db->query('DELETE FROM '.MAIN_DB_PREFIX.'bds_report_line WHERE id_report = '.$this->id);
            return parent::delete($warnings, $force_delete);
        }
        
        return $errors;
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

    // Overrides: 

    public function update(&$warnings = array(), $force_update = false)
    {
        $errors = parent::update($warnings, $force_update);

        if (!count($errors)) {
            $this->saveObjectsData();
        }

        return $errors;
    }

    // Méthodes statiques: 

    public static function cleanReports($errors = array())
    {
        $n = 0;

        $operations = BimpCache::getBimpObjectObjects('bimpdatasync', 'BDS_ProcessOperation', array(
                    'use_report' => 1
        ));

        foreach ($operations as $operation) {
            $delay = (int) $operation->getData('reports_delay');
            if (!$delay) {
                continue;
            }

            $id_process = (int) $operation->getData('id_process');
            $dt = new DateTime();
            $dt->sub(new DateInterval('P' . $delay . 'D'));
            $date = $dt->format('Y-m-d H:i:s');

            $reports_list = BimpCache::getBimpObjectList('bimpdatasync', 'BDS_Report', array(
                        'id_process'   => $id_process,
                        'id_operation' => $operation->id,
                        'begin'        => array(
                            'operator' => '<',
                            'value'    => $date
                        )
            ));

            foreach ($reports_list as $id_report) {
                $report = BimpObject::getInstance('bimpdatasync', 'BDS_Report', (int) $id_report);

                if (BimpObject::objectLoaded($report)) {
                    $err = $report->delete($w, true);

                    if (count($err)) {
                        $errors[] = BimpTools::getMsgFromArray($err, 'Echec de la suppression du Rapport #' . $id_report);
                    } else {
                        $n++;
                    }
                }

                unset($report);
            }
        }
        
        return $n;
    }
}
