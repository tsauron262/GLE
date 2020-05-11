<?php

require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSImportFournCatalogProcess.php';

class BDS_ImportsTechDataProcess extends BDSImportFournCatalogProcess
{

    public static $price_keys = array(
        1 => 'ref_fourn',
        2 => 'lib',
        3 => 'ref_manuf',
        4 => 'brand',
        5 => 'pu_ht',
        7 => 'pa_ht'
    );
    public static $stock_keys = array(
        0 => 'ref_fourn',
        3 => 'stock'
    );

    public function initUpdateFromFile(&$data, &$errors = array())
    {
        $data['steps'] = array();

        $this->truncTableProdFourn($errors);

        if (isset($this->options['update_files']) && (int) $this->options['update_files']) {
            $data['steps']['update_prices_file'] = array(
                'label'    => 'Téléchargement du fichier des prix',
                'on_error' => 'continue'
            );
            $data['steps']['update_stocks_file'] = array(
                'label'    => 'Téléchargement du fichier des stocks',
                'on_error' => 'continue'
            );
        } else {
            if (isset($this->options['process_full_file']) && (int) $this->options['process_full_file']) {
                $data['steps']['process_prices'] = array(
                    'label'                  => 'Traitement des prix fourniseur',
                    'on_error'               => 'continue',
                    'nbElementsPerIteration' => 0
                );

                $data['steps']['process_stocks'] = array(
                    'label'                  => 'Traitement des stocks fourniseur',
                    'on_error'               => 'continue',
                    'nbElementsPerIteration' => 0
                );
            } else {
                $partsDir = $this->getFilePartsDirname($this->params['prices_file']);
                $prices_files_indexes = $this->getPartsFilesIndexes($this->local_dir . '/' . $partsDir);

                if (!empty($prices_files_indexes)) {
                    $data['steps']['process_prices'] = array(
                        'label'                  => 'Import des prix fourniseur',
                        'on_error'               => 'continue',
                        'elements'               => $prices_files_indexes,
                        'nbElementsPerIteration' => 1
                    );
                }

                $partsDir = $this->getFilePartsDirname($this->params['stocks_file']);
                $stocks_files_indexes = $this->getPartsFilesIndexes($this->local_dir . '/' . $partsDir);

                if (!empty($stocks_files_indexes)) {
                    $data['steps']['process_stocks'] = array(
                        'label'                  => 'Import des stocks fourniseur',
                        'on_error'               => 'continue',
                        'elements'               => $stocks_files_indexes,
                        'nbElementsPerIteration' => 1
                    );
                }

                if (empty($prices_files_indexes) && empty($stocks_files_indexes)) {
                    $errors[] = 'Aucune donnée à traiter trouvée';
                }
            }
        }
    }

    public function executeUpdateFromFile($step_name, &$errors = array())
    {
        $result = array();

        switch ($step_name) {
            case 'update_prices_file':
                if (isset($this->params['prices_file']) && $this->params['prices_file']) {
                    $this->downloadFtpFile($this->params['prices_file'], $errors);

                    if (!count($errors)) {
                        $this->makeCsvFileParts($this->local_dir, $this->params['prices_file'], $errors, 10000, 0);

                        if (!count($errors)) {
                            if (isset($this->options['process_full_file']) && (int) $this->options['process_full_file']) {
                                $result['new_steps'] = array(
                                    'process_prices' => array(
                                        'label'                  => 'Traitement des prix fourniseur',
                                        'on_error'               => 'continue',
                                        'nbElementsPerIteration' => 0
                                    )
                                );
                            } else {
                                $partsDir = $this->getFilePartsDirname($this->params['prices_file']);
                                $prices_files_indexes = $this->getPartsFilesIndexes($this->local_dir . '/' . $partsDir);

                                $result['new_steps'] = array(
                                    'process_prices' => array(
                                        'label'                  => 'Import des prix fourniseur',
                                        'on_error'               => 'continue',
                                        'elements'               => $prices_files_indexes,
                                        'nbElementsPerIteration' => 1
                                    )
                                );
                            }
                        }
                    }
                } else {
                    $errors[] = 'Nom du fichier des prix fournisseur absent';
                }
                break;

            case 'update_stocks_file':
                if (isset($this->params['stocks_file']) && $this->params['stocks_file']) {
                    $this->downloadFtpFile($this->params['stocks_file'], $errors);

                    if (!count($errors)) {
                        $this->makeCsvFileParts($this->local_dir, $this->params['stocks_file'], $errors, 10000, 0);

                        if (!count($errors)) {
                            if (isset($this->options['process_full_file']) && (int) $this->options['process_full_file']) {
                                $result['new_steps'] = array(
                                    'process_stocks' => array(
                                        'label'                  => 'Traitement des stocks fourniseur',
                                        'on_error'               => 'continue',
                                        'nbElementsPerIteration' => 0
                                    )
                                );
                            } else {
                                $partsDir = $this->getFilePartsDirname($this->params['stocks_file']);
                                $stocks_files_indexes = $this->getPartsFilesIndexes($this->local_dir . '/' . $partsDir);

                                $result['new_steps'] = array(
                                    'process_stocks' => array(
                                        'label'                  => 'Import des stocks fourniseur',
                                        'on_error'               => 'continue',
                                        'elements'               => $stocks_files_indexes,
                                        'nbElementsPerIteration' => 1
                                    )
                                );
                            }
                        }
                    }
                } else {
                    $errors[] = 'Nom du fichier des stocks absent';
                }
                break;

            case 'process_prices':
                $file_idx = 0;

                if (!isset($this->options['process_full_file']) || !(int) $this->options['process_full_file']) {
                    if (!empty($this->references)) {
                        $file_idx = (int) $this->references[0];
                    }
                }

                $this->references = array();

                $file_data = $this->getFileData($this->params['prices_file'], static::$price_keys, $errors, -1, 0, array(
                    'part_file_idx' => $file_idx
                ));

//                $this->DebugData($file_data, 'Données fichier');

                if (!count($errors) && !empty($file_data)) {
                    $this->processFournPrices($file_data, $errors);
                }
                break;

            case 'process_stocks':
                $file_idx = 0;

                if (!isset($this->options['process_full_file']) || !(int) $this->options['process_full_file']) {
                    if (!empty($this->references)) {
                        $file_idx = (int) $this->references[0];
                    }
                }

                $this->references = array();

                $file_data = $this->getFileData($this->params['stocks_file'], static::$stock_keys, $errors, -1, 0, array(
                    'part_file_idx' => $file_idx
                ));

//                $this->DebugData($file_data, 'Données fichier');

                if (!count($errors) && !empty($file_data)) {
                    $this->processFournStocks($file_data, $errors);
                }
                break;
        }

        return $result;
    }

    // Install: 

    public static function install(&$errors = array(), &$warnings = array())
    {
        // Process: 

        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'ImportsTechData',
                    'title'       => 'Imports FTP TechData',
                    'description' => '',
                    'type'        => 'import',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {

            // Params: 

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_host',
                'label'      => 'Hôte',
                'value'      => 'exportftp.techdata.fr'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_login',
                'label'      => 'Login',
                'value'      => 'bimp'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_pwd',
                'label'      => 'MDP',
                'value'      => '=bo#lys$2003'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'auth_code',
                'label'      => 'Code d\'authentification',
                'value'      => '770OrBQ6-vv5w-knLM-jMJ9-UelkTxS2HIKB'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'id_fourn',
                'label'      => 'ID Fournisseur',
                'value'      => '229890'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'local_dir',
                'label'      => 'Dossier local',
                'value'      => 'bimpdatasync/imports/techdata/'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'prices_file',
                'label'      => 'Fichier prix fournisseur',
                'value'      => 'CustSpecific.txt'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'stocks_file',
                'label'      => 'Fichier Stocks',
                'value'      => 'StockFile.txt'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'delimiter',
                'label'      => 'Délimiteur',
                'value'      => '\t'
                    ), true, $warnings, $warnings);

            // Options: 

            $opt1 = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Mettre à jour les fichiers',
                        'name'          => 'update_files',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            $opt2 = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Traiter tout le fichier en une seule étape',
                        'name'          => 'process_full_file',
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
                        'title'         => 'Mise à jour des prix d\'achat',
                        'name'          => 'updateFromFile',
                        'description'   => '',
                        'warning'       => '',
                        'active'        => 1,
                        'use_report'    => 1,
                        'reports_delay' => 30
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op)) {
                $warnings = array_merge($warnings, $op->addAssociates('options', array($opt1->id, $opt2->id)));

                // Crons:

                BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessCron', array(
                    'id_process'   => (int) $process->id,
                    'id_operation' => (int) $op->id,
                    'title'        => 'Màj Prix/Stocks TechData',
                    'active'       => 0,
                    'freq_val'     => '1',
                    'freq_type'    => 'week',
                    'start'        => date('Y-m-d H:i:s')
                        ), true, $warnings, $warnings);
            }
        }
    }
}
