<?php

require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSImportFournCatalogProcess.php';

class BDS_ImportsLdlcProcess extends BDSImportFournCatalogProcess
{

    public static $price_keys = array(
        'Reference'         => 'ref_fourn',
        'EAN'               => 'ean',
        'ShortDesignation'  => 'lib',
        'Brand'             => 'brand',
        'ManufacturerRef'   => 'ref_manuf',
       /* 'IsAsleep'          => 'is_sleep',*/
        'IsDeleted'         => 'is_delete',
        'PriceVatOff'       => 'pu_ht',
        'PriceVatOn'        => 'pu_ttc',
        'BuyingPriceVatOff' => 'pa_ht',
        'Image01' => 'url'
    );
    
    public $nameFile = '';
    
    public function __construct(BDS_Process $process, $options = array(), $references = array())
    {
        parent::__construct($process, $options, $references);
        
        $file = date('Ymd') . '_catalog_ldlc_to_bimp.csv';

//        if (!file_exists($this->local_dir . $file)) {
//            $file = '';
//            if (file_exists($this->local_dir) && is_dir($this->local_dir)) {
//                $files = scandir($this->local_dir);
//                arsort($files);
//
//                foreach ($files as $f) {
//                    if (preg_match('/^[0-9]{8}_catalog_ldlc_to_bimp\.csv$/', $f)) {
//                        $this->nameFile = $f;
//                        break;
//                    }
//                }
//            } else {
//                $this->Alert('Dossier "' . $this->local_dir . '" absent');
//            }
//        }
//        else
            $this->nameFile = $file;
    }

    public function initUpdateFromFile(&$data, &$errors = array())
    {
        $data['steps'] = array();

        $this->truncTableProdFourn($errors);
        
        if (isset($this->options['update_files']) && (int) $this->options['update_files']) {
            $data['steps']['update_prices_file'] = array(
                'label'    => 'Téléchargement du fichier',
                'on_error' => 'continue'
            );
        }
        elseif (isset($this->options['process_full_file']) && (int) $this->options['process_full_file']) {
            $data['steps']['process_prices'] = array(
                'label'                  => 'Traitement des prix fourniseur',
                'on_error'               => 'continue',
                'nbElementsPerIteration' => 0
            );
        } else {
            $data['steps']['make_prices_file_parts'] = array(
                'label'    => 'Téléchargement du fichier',
                'on_error' => 'continue'
            );
        }
    }

    public function executeUpdateFromFile($step_name, &$errors = array())
    {
        $result = array();

        switch ($step_name) {
            case 'update_prices_file':
                if (isset($this->nameFile) && $this->nameFile) {
                    $fileName = $this->nameFile;
                    $this->downloadFtpFile($fileName, $errors);
//                    die($fileName."mm");
                    if (!count($errors)) {
                        if ($this->options['debug']) {
                            error_reporting(E_ALL);
                        }
                        
//                        //supression des deux premiére lignes
//                        $file = PATH_TMP."/".$this->params['local_dir']."/". $this->nameFile;
//                        
//                        $donnee = file($file);
//                        $fichier=fopen($file, "w");
//                        fputs('');
//                        $i=0;
//                        foreach($donnee as $d)
//                        {
//                                if($i!=0)
//                                {
//                                        fputs($fichier, $d);
//                                }
//                                $i++;
//                        }
//                        fclose($fichier);
//                        $donnee = array();
                        
                        if ($this->options['debug']) {
                            error_reporting(E_ERROR);
                        }
                        
                        if (isset($this->options['process_full_file']) && (int) $this->options['process_full_file']) {
                            $result['new_steps']['process_prices'] = array(
                                'label'                  => 'Traitement des prix fourniseur',
                                'on_error'               => 'continue',
                                'nbElementsPerIteration' => 0
                            );
                        } else {
                            $result['new_steps']['make_prices_file_parts'] = array(
                                'label'    => 'Téléchargement du fichier',
                                'on_error' => 'continue'
                            );
                        }
                    }
                } else {
                    $errors[] = 'Nom du fichier stock fournisseur absent';
                }
                break;
            case 'make_prices_file_parts':
                if (isset($this->nameFile) && $this->nameFile) {
                    $this->makeCsvFileParts($this->local_dir, $this->nameFile, $errors, 10000, 1);

                    if (!count($errors)) {
                        $partsDir = $this->getFilePartsDirname($this->nameFile);
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

                $this->references = array();

                $file_data = $this->getFileData($this->nameFile, static::$price_keys, $errors, 0, 1, array(
                    'part_file_idx' => $file_idx
                ));

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
                    'name'        => 'ImportsLdlc',
                    'title'       => 'Imports FTP LDLC',
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
                'value'      => 'ftp-edi.groupe-ldlc.com'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_login',
                'label'      => 'Login',
                'value'      => 'bimp-erp'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_pwd',
                'label'      => 'MDP',
                'value'      => 'MEDx33w+3u('
                    ), true, $warnings, $warnings);
            
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_dir',
                'label'      => 'Dossier FTP',
                'value'      => '/FTP-BIMP-ERP/catalogue/'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'id_fourn',
                'label'      => 'ID Fournisseur',
                'value'      => '230880'
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'local_dir',
                'label'      => 'Dossier local',
                'value'      => 'bimpdatasync/imports/ldlc/'
                    ), true, $warnings, $warnings);

//            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
//                'id_process' => (int) $process->id,
//                'name'       => 'prices_file',
//                'label'      => 'Fichier prix fournisseur',
//                'value'      => ''
//                    ), true, $warnings, $warnings);

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
                $warnings = array_merge($warnings, $op->addAssociates('options', array($opt1->id)));
                $warnings = array_merge($warnings, $op->addAssociates('options', array($opt2->id)));

                // Crons:

                BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessCron', array(
                    'id_process'   => (int) $process->id,
                    'id_operation' => (int) $op->id,
                    'title'        => 'Màj Prix/Stocks LDLC',
                    'active'       => 0,
                    'freq_val'     => '1',
                    'freq_type'    => 'week',
                    'start'        => date('Y-m-d H:i:s')
                        ), true, $warnings, $warnings);
            }
        }
    }
}
