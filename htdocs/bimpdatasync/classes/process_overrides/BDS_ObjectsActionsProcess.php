<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_ObjectsActionsProcess extends BDSProcess
{

    // init opération: 

    public function initObjectAction(&$data, &$errors = array())
    {
        $action = $this->getOption('action', '');

        if (!$action) {
            $errors[] = 'Nom de l\'action absent';
        } else {
            $object = $this->getObjectInstance($errors);

            if (!count($errors)) {
                $extra_data = $this->getOption('action_extra_data', array());
                $force_action = (int) $this->getOption('force_action', 0);
                $object->initBdsAction($action, $data, $errors, $extra_data, $force_action);
            }
        }
    }

    // Exec opération: 

    public function executeObjectAction($step_name, &$errors = array(), $operation_extra_data = array())
    {
        $action = $this->getOption('action', '');

        if (!$action) {
            $errors[] = 'Nom de l\'action absent';
        } else {
            $object = $this->getObjectInstance($errors);

            if (!count($errors)) {
                $action_extra_data = $this->getOption('action_extra_data', array());
                $force_action = (int) $this->getOption('force_action', 0);
                $object->executeBdsAction($action, $step_name, $errors, $operation_extra_data, $action_extra_data, $force_action);
            }
        }
    }

    // Traitements: 

    public function getObjectInstance(&$errors = array())
    {
        $module = $this->getOption('module', '');
        $object_name = $this->getOption('object_name', '');

        if (!$module) {
            $errors[] = 'Nom du module absent';
        }

        if (!$object_name) {
            $errors[] = 'Type d\'objet absent';
        }

        $id_object = $this->getOption('id_object', 0);

        if (!count($errors)) {
            $object = null;
            if ($id_object) {
                $object = BimpCache::getBimpObjectInstance($module, $object_name, $id_object);
                if (!BimpObject::objectLoaded($object)) {
                    $object = BimpObject::getInstance($module, $object_name);
                    $errors[] = ucfirst($object->getLabel('the') . ' #' . $id_object . ' n\'existe pas');
                    return null;
                }
            } else {
                $object = BimpObject::getInstance($module, $object_name);
            }

            return $object;
        }

        return null;
    }

    // Install: 

    public static function install(&$errors = array(), &$warnings = array())
    {
        // Process:
        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'ObjectsActions',
                    'title'       => 'Traitement des actions objets',
                    'description' => '',
                    'type'        => 'other',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {
            // Options: 

            $options = array();

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Nom Action',
                        'name'          => 'action',
                        'info'          => '',
                        'type'          => 'text',
                        'default_value' => '',
                        'required'      => 1
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Module',
                        'name'          => 'module',
                        'info'          => '',
                        'type'          => 'text',
                        'default_value' => '',
                        'required'      => 1
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Nom Objet',
                        'name'          => 'object_name',
                        'info'          => '',
                        'type'          => 'text',
                        'default_value' => '',
                        'required'      => 1
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'ID Objet',
                        'name'          => 'id_object',
                        'info'          => '',
                        'type'          => 'text',
                        'default_value' => 0,
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Données supplémentaires de l\'action',
                        'name'          => 'action_extra_data',
                        'info'          => '',
                        'type'          => 'text',
                        'default_value' => '',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Forcer l\'action',
                        'name'          => 'force_action',
                        'info'          => 'Si oui: contourner les permissions utilisateur',
                        'type'          => 'toggle',
                        'default_value' => 0,
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            // opérations: 

            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'  => (int) $process->id,
                        'title'       => 'Exécuter l\'action objet',
                        'name'        => 'ObjectAction',
                        'description' => '',
                        'warning'     => '',
                        'active'      => 1,
                        'use_report'  => 0,
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op)) {
                $warnings = array_merge($warnings, $op->addAssociates('options', $options));
            }
        }
    }
}
