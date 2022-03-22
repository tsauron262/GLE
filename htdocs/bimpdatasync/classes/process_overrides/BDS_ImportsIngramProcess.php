<?php

require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSImportFournCatalogProcess.php';

class BDS_ImportsIngramProcess extends BDSImportFournCatalogProcess
{

    public static $price_keys = array(
        1 => 'brand',
        3 => 'ref_fourn',
        4 => 'lib',
        7 => 'ref_manuf',
        8 => 'pa_ht',
        9 => 'pu_ht'
    );
    public static $taxe_keys = array(
        'IM PART #' => 'ref_fourn',
        'CP FEE D' => 'taxe1',
        'RECYCFEE' => 'taxe2',
        'CP FEE N' => 'taxe3'
    );

    public function initUpdateFromFile(&$data, &$errors = array())
    {
        $data['steps'] = array();

        $this->truncTableProdFourn($errors);

        if (isset($this->options['update_files']) && (int) $this->options['update_files']) {
            $data['steps']['update_prices_file'] = array(
                'label'    => 'Téléchargement du fichier',
                'on_error' => 'continue'
            );
            $data['steps']['update_taxe_file'] = array(
                'label'    => 'Téléchargement du fichier de taxe',
                'on_error' => 'continue'
            );
        } else {
            if (isset($this->options['process_full_file']) && (int) $this->options['process_full_file']) {
                $data['steps']['process_prices'] = array(
                    'label'                  => 'Traitement des prix fourniseur',
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

                if (empty($prices_files_indexes)) {
                    $errors[] = 'Aucune donnée à traiter trouvée';
                }
            }
        }
    }

    public function executeUpdateFromFile($step_name, &$errors = array(), $extra_data = array())
    {
        $result = array();

        switch ($step_name) {
            case 'update_taxe_file':
                if(isset($this->params['taxes_file']) && $this->params['taxes_file'] && isset($this->params['ftp_dir2']) && $this->params['ftp_dir2']){
                    $fileNameTaxe = $this->params['taxes_file'];;
                    $this->ftp_dir = $this->params['ftp_dir2']."/";
                    $this->downloadFtpFile($fileNameTaxe, $errors);
                }
                break;
            
            case 'update_prices_file':
                if (isset($this->params['prices_file']) && $this->params['prices_file']) {
                    $fileName = pathinfo($this->params['prices_file'], PATHINFO_FILENAME) . '.ZIP';
                    $this->downloadFtpFile($fileName, $errors);
                    
                    if (!count($errors)) {
                        if ($this->options['debug']) {
                            error_reporting(E_ALL);
                        }

                        if (BimpTools::unZip($this->local_dir . $fileName, $this->local_dir, $errors)) {
                            $this->Msg('UNZIP OK', 'success');
                        } elseif (!count($errors)) {
                            $errors[] = 'Echec inconnue de l\'extraction de l\'archive "' . $fileName . '"';
                        }

                        if ($this->options['debug']) {
                            error_reporting(E_ERROR);
                        }

                        if (!count($errors)) {
                            $this->makeCsvFileParts($this->local_dir, $this->params['prices_file'], $errors, 10000, 1);

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
                    }
                } else {
                    $errors[] = 'Nom du fichier des prix fournisseur absent';
                }
                break;

            case 'process_prices':
                $file_idx = 0;

                if (!isset($this->options['process_full_file']) || !(int) $this->options['process_full_file']) {
                    if (!empty($this->references)) {
                        $file_idx = (int) $this->references[0];
                    }
                }
                
                
                $dataTaxe = $this->getCsvFileDataByKeys(PATH_TMP."/".$this->params['local_dir'].$this->params['taxes_file'], static::$taxe_keys, $errors, $this->params['delimiter']);
                $tmp =array();
                foreach($dataTaxe as $datas)
                    $tmp[$datas['ref_fourn']] = $datas;
                $dataTaxe = $tmp;
                $this->references = array();

                $file_data = $this->getFileData($this->params['prices_file'], static::$price_keys, $errors, -1, 0, array(
                    'part_file_idx' => $file_idx,
                    'clean_value'   => true
                ));
                
                foreach($file_data as $idT =>$datas){
                    if(!isset($dataTaxe[$datas['ref_fourn']])){
                        unset($file_data[$idT]);
                        $this->Alert('Pas d\'infos taxe trouvé pour la ref fournisseur :"'.$datas['ref_fourn'].'"', NULL, $datas['ref_fourn']);
                    }
                    else{
                        $infoTaxe = $dataTaxe[$datas['ref_fourn']];
                        if($infoTaxe['taxe1'] > 0){
                            $file_data[$idT]['pa_ht'] += $infoTaxe['taxe1'];
                        }
                        if($infoTaxe['taxe2'] > 0)
                            $file_data[$idT]['pa_ht'] += $infoTaxe['taxe2'];
                        if($infoTaxe['taxe3'] > 0)
                            $file_data[$idT]['pa_ht'] += $infoTaxe['taxe3'];
                    }

                }
                
//                $this->DebugData($file_data, 'Données fichier');

                if (!count($errors) && !empty($file_data)) {
                    $this->processFournPrices($file_data, $errors);
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
                    'name'        => 'ImportsIngram',
                    'title'       => 'Imports FTP Ingram',
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
                'value'      => 'ftpsecure.ingrammicro.com'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_login',
                'label'      => 'Login',
                'value'      => '220380'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_pwd',
                'label'      => 'MDP',
                'value'      => 'BIMP380'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'id_fourn',
                'label'      => 'ID Fournisseur',
                'value'      => '230496'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_dir',
                'label'      => 'Dossier FTP',
                'value'      => 'fusion/FR/B0380/'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'local_dir',
                'label'      => 'Dossier local',
                'value'      => 'bimpdatasync/imports/ingram/'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'prices_file',
                'label'      => 'Fichier prix fournisseur',
                'value'      => 'PRICE.TXT'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'delimiter',
                'label'      => 'Délimiteur',
                'value'      => ','
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'taxes_file',
                'label'      => 'Fichier de taxe',
                'value'      => 'FRSRVFEE.TXT'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_dir2',
                'label'      => 'Dossier FTP2',
                'value'      => '/FUSION/FR/AVAIL'
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
                    'title'        => 'Màj Prix/Stocks Ingram',
                    'active'       => 0,
                    'freq_val'     => '1',
                    'freq_type'    => 'week',
                    'start'        => date('Y-m-d H:i:s')
                        ), true, $warnings, $warnings);
            }
        }
    }
}
