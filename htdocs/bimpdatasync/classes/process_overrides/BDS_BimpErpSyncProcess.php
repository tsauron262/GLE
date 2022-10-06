<?php

require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSSyncProcess.php';

class BDS_BimpErpSyncProcess extends BDSSyncProcess
{

    public static $default_public_title = 'Synchronisation Bimp ERP';
    public static $allow_multiple_instances = true;
    protected $api = null;

    // Init opérations: 

    public function initExportObjects(&$data, &$errors = array())
    {
        $ids_synchros = explode(',', $this->getOption('ids_sync_objects', ''));

        if (!empty($ids_synchros)) {
            $data['steps']['export'] = array(
                'label'                  => 'Export des objets',
                'on_error'               => 'continue',
                'elements'               => $ids_synchros,
                'nbElementsPerIteration' => 10
            );
        } else {
            $errors[] = 'Aucun objet à exporter spécifié';
        }
    }

    // Exec opérations:

    public function executeExportObjects($step_name, &$errors = array(), $extra_data = array())
    {
        $result = array();

        switch ($step_name) {
            case 'export':
                if (!empty($this->references)) {
                    foreach ($this->references as $id_signature) {
                        $this->processRelance($id_signature, $errors);
                    }
                }
                break;
        }

        return $result;
    }

    // Traitements: 

    public function getApi(&$errors = array())
    {
        if (is_null($this->api)) {
            $id_api = $this->getParam('id_api', 0);

            if (!$id_api) {
                $errors[] = 'ID API Non configurée';
            } else {
                $api_obj = BimpCache::getBimpObjectInstance('bimpapi', 'API_Api', $id_api);
                if (!BimpObject::objectLoaded($api_obj)) {
                    $errors[] = 'Erreur de configuration : l\'API #' . $id_api . ' n\'existe pas';
                } else {
                    $this->api = $api_obj->getApiInstance();

                    if (!empty($this->api->errors)) {
                        $errors = BimpTools::merge_array($errors, $this->api->errors);
                    }

                    if (!is_a($this->api, 'ErpAPI')) {
                        $errors[] = 'Erreur de configuration : API Liée invalide';
                        $this->api = null;
                    }
                }
            }
        }

        return $this->api;
    }

    protected function checkObjectSynchronization($object = null, $module = '', $object_name = '', $id_object = 0)
    {
        $errors = array();

        if (is_null($object)) {
            if ($module && $object_name && $id_object) {
                $object = BimpCache::getBimpObjectInstance($module, $object_name, $id_object);
            }
        }

        if (!BimpObject::objectLoaded($errors)) {
            $errors[] = 'Objet invalide';
            return $errors;
        }

        BimpObject::loadClass('bimpdatasync', 'BDS_SyncObject');
        $id_sync = BDS_SyncObject::syncExists($object->module, $object->object_name, $object->id);

        if ($id_sync) {
            $errors = $this->processSynchronization(array($id_sync));
        } else {
            $errors = $this->exportObject($object);
        }

        return $errors;
    }

    protected function processSynchronization($ids_sync)
    {
        $errors = array();

        return $errors;
    }

    protected function exportObject($object)
    {
        $errors = array();

        if (!is_a($object, 'BimpObject') || !BimpObject::objectLoaded($object)) {
            $errors[] = 'Objet à exporter invalide';
        }

        $id_process_ext = (int) $this->getParam('id_process_ext', 0);
        if (!$id_process_ext) {
            $errors[] = 'Paramètre absent: ID Processus externe';
        }

        if (!count($errors)) {
            $api = $this->getApi($errors);

            if (!count($errors)) {
                // Vérification de l'existence de l'objet dans le système externe: 
                $req_errors = array();
                $list = $api->getObjectsList('bimpdatasync', 'BDS_SyncObject', array(
                    'obj_module' => $object->module,
                    'obj_name'   => $object->name,
                    'id_ext'     => $object->id,
                    'id_process' => $id_process_ext
                        ), null, null, $req_errors);

                if (is_null($list) ||  count($req_errors)) {
                    $title = 'Echec de la vérication de l\'existence ' . $object->getLabel('of_the') . ' ' . $object->getRef(true);
                    $title .= ' dans le système "' . $api->getParam('external_erp_name', '(Nom du système externe non configuré)') . '"';
                    $errors[] = BimpTools::getMsgFromArray($req_errors, $title);
                } elseif (!empty($list)) {
                    $id_ext_sync = 0;
                    foreach ($list as $id_sync) {
                        $id_ext_sync = $id_sync;
                        break;
                    }
                }
            }
        }

        return $errors;
    }

    // Install: 

    public static function install(&$errors = array(), &$warnings = array(), $title = '')
    {
        // Process: 
        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'BimpErpSync',
                    'title'       => ($title ? $title : static::$default_public_title),
                    'description' => 'Gestion des opérations de synchronisation des données entre deux Bimp-ERP',
                    'type'        => 'sync',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {
            // Params: 
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'id_api',
                'label'      => 'ID API',
                'value'      => ''
                    ), true, $warnings, $warnings);
        }
    }
}
