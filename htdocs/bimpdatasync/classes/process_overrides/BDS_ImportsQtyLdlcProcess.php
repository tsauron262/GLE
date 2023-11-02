<?php

require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSImportFournCatalogProcess.php';

class BDS_ImportsQtyLdlcProcess extends BDSImportFournCatalogProcess
{

    public static $default_public_title = 'Imports Qty FTP LDLC';
    public static $stock_keys = array(
        'id'           => 'ref_fourn',
        'availability' => 'stock'
    );

    public function initUpdateFromFile(&$data, &$errors = array())
    {
        $data['steps'] = array();

        if (isset($this->options['update_files']) && (int) $this->options['update_files']) {
            $data['steps']['update_prices_file'] = array(
                'label'    => 'Téléchargement du fichier',
                'on_error' => 'continue'
            );
        } else {
            $data['steps']['process_qty'] = array(
                'label'                  => 'Traitement des prix fourniseur',
                'on_error'               => 'continue',
                'nbElementsPerIteration' => 0
            );
        }
    }

    public function executeUpdateFromFile($step_name, &$errors = array(), $extra_data = array())
    {
        $result = array();

        if (!defined('PATH_TMP')) {
            $errors[] = 'Chemin du dossier racine absent (PATH_TMP non défini)';
        } else {
            switch ($step_name) {
                case 'update_prices_file':
                    if (isset($this->params['qty_file']) && $this->params['qty_file']) {
                        $fileName = $this->params['qty_file'];
                        $this->downloadFtpFile($fileName, $errors);

                        if (!count($errors)) {
                            if ($this->options['debug']) {
                                error_reporting(E_ALL);
                            }

                            //supression des deux premiére lignes
                            $file = PATH_TMP . "/" . $this->params['local_dir'] . "/" . $this->params['qty_file'];

                            $donnee = file($file);
                            $fichier = fopen($file, "w");
                            fputs($fichier, '');
                            $i = 0;

                            foreach ($donnee as $d) {
                                if ($i != 0) {
                                    fputs($fichier, $d);
                                }
                                $i++;
                            }
                            fclose($fichier);
                            $donnee = array();

                            if ($this->options['debug']) {
                                error_reporting(E_ERROR);
                            }

                            $result['new_steps'] = array(
                                'process_qty' => array(
                                    'label'                  => 'Traitement des qty fourniseur',
                                    'on_error'               => 'continue',
                                    'nbElementsPerIteration' => 0
                                )
                            );
                        }
                    } else {
                        $errors[] = 'Nom du fichier stock fournisseur absent';
                    }
                    break;

                case 'process_qty':
                    $this->references = array();

                    if (!file_exists(PATH_TMP . "/" . $this->params['local_dir'] . '/' . $this->params['qty_file'])) {
                        $errors[] = 'Fichier "' . PATH_TMP . "/" . $this->params['local_dir'] . '/' . $this->params['qty_file'] . '" absent';
                    } else {
                        $file_data = $this->openXML(PATH_TMP . "/" . $this->params['local_dir'], $this->params['qty_file']);

                        if (is_null($file_data)) {
                            $errors[] = 'Echec de l\'obtention des données depuis le fichier "' . $this->params['qty_file'] . '"';
                        } elseif (!isset($file_data->Products)) {
                            $errors[] = 'Aucun produit dans le fichier "' . $this->params['qty_file'] . '"';
                        } else {
                            $newFileData = array();
                            foreach ($file_data->Products->children() as $line) {
                                $newFileData[] = array('ref_fourn' => strval($line->attributes()['id'][0]), 'stock' => intval($line->attributes()['availability'][0]));
                            }

                            $this->DebugData($newFileData, 'Données fichier');

                            if (!count($errors) && !empty($newFileData)) {
                                $this->processFournStocks($newFileData, $errors);
                            }
                        }
                    }
                    break;
            }
        }

        return $result;
    }
    
    public function getFtpParams($params){
        if($params == 'ftp_host')
            return BimpCore::getConf('exports_ldlc_ftp_serv');
        if($params == 'ftp_login')
            return BimpCore::getConf('exports_ldlc_ftp_user');
        if($params == 'ftp_pwd')
            return BimpCore::getConf('exports_ldlc_ftp_mdp');
    }

    // Install: 

    public static function install(&$errors = array(), &$warnings = array(), $title = '')
    {
        // Process: 

        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'ImportsQtyLdlc',
                    'title'       => ($title ? $title : static::$default_public_title),
                    'description' => '',
                    'type'        => 'import',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {

            // Params: 

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'id_fourn',
                'label'      => 'ID Fournisseur',
                'value'      => '230880'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_dir',
                'label'      => 'Dossier FTP',
                'value'      => '/'.BimpCore::getConf('exports_ldlc_ftp_dir').'/products/'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'local_dir',
                'label'      => 'Dossier local',
                'value'      => 'bimpdatasync/imports/ldlcqty/'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'qty_file',
                'label'      => 'Fichier prix fournisseur',
                'value'      => 'products.xml'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'delimiter',
                'label'      => 'Délimiteur',
                'value'      => '|;|'
                    ), true, $warnings, $warnings);

            // Options: 


            $opt1 = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Mettre à jour le fichier',
                        'name'          => 'update_files',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            // Opérations: 

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                'id_process'  => (int) $process->id,
                'title'       => 'Test de connection FTP',
                'name'        => 'ftp_test',
                'description' => '',
                'warning'     => '',
                'active'      => 1,
                'use_report'  => 0
                    ), true, $warnings, $warnings);

            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $process->id,
                        'title'         => 'Mise à jour des stocks',
                        'name'          => 'updateFromFile',
                        'description'   => '',
                        'warning'       => '',
                        'active'        => 1,
                        'use_report'    => 1,
                        'reports_delay' => 30
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op)) {
                $warnings = array_merge($warnings, $op->addAssociates('options', array($opt1->id)));

                // Crons:

                BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessCron', array(
                    'id_process'   => (int) $process->id,
                    'id_operation' => (int) $op->id,
                    'title'        => 'Màj Qty LDLC',
                    'active'       => 0,
                    'freq_val'     => '1',
                    'freq_type'    => 'week',
                    'start'        => date('Y-m-d H:i:s')
                        ), true, $warnings, $warnings);
            }
        }
    }
}
