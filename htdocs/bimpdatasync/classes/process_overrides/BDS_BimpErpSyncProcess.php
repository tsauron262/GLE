<?php

require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSSyncProcess.php';

class BDS_BimpErpSyncProcess extends BDSSyncProcess
{

    public static $default_public_title = 'Synchronisation Bimp ERP';
    public static $allow_multiple_instances = true;

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

    public function processExportObject($id_sync)
    {
        
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
                'name'       => 'id_api_export',
                'label'      => 'ID API Exports',
                'value'      => ''
                    ), true, $warnings, $warnings);
        }
    }
}
