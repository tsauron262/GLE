<?php

require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSImportFournCatalogProcess.php';

class BDS_ImportsLdlcProcess extends BDSImportFournCatalogProcess
{

    public static $default_public_title = 'Imports FTP LDLC';
    public static $price_keys = array(
        'Reference'         => 'ref_fourn',
        'Code'         => 'code',
        'EAN'               => 'ean',
        'ShortDesignation'  => 'lib',
        'Brand'             => 'brand',
        'ManufacturerRef'   => 'ref_manuf',
        'IsAsleep'          => 'is_sleep2', 
        'IsDeleted'         => 'is_delete',
        'PriceVatOff'       => 'pu_ht',
        'PriceVatOn'        => 'pu_ttc',
        'BuyingPriceVatOff' => 'pa_ht',
        'Image01'           => 'url',
        'GrossWeight'       => 'weight'
    );
    public $nameFile = '';

    public function __construct(BDS_Process $process, $options = array(), $references = array())
    {
        parent::__construct($process, $options, $references);
        $this->nameFile = str_replace("Ymd", date('Ymd'), $this->params['file_name']);
    }

    public function initUpdateFromFile(&$data, &$errors = array())
    {
        $data['steps'] = array();

//        $this->truncTableProdFourn($errors);

        if (isset($this->options['update_files']) && (int) $this->options['update_files']) {
            $data['steps']['update_prices_file'] = array(
                'label'    => 'Téléchargement du fichier',
                'on_error' => 'continue'
            );
//        } elseif (isset($this->options['process_full_file']) && (int) $this->options['process_full_file']) {
//            $data['steps']['process_prices'] = array(
//                'label'                  => 'Traitement des prix fourniseur',
//                'on_error'               => 'continue',
//                'nbElementsPerIteration' => 0
//            );
//        } else {
//            $data['steps']['make_prices_file_parts'] = array(
//                'label'    => 'Découpage du fichier',
//                'on_error' => 'continue'
//            );
        } else {
            $data['steps']['trunc_table_prod_fourn'] = array(
                'label'    => 'Vidage de la table import',
                'on_error' => 'stop'
            );
        }
    }

    public function executeUpdateFromFile($step_name, &$errors = array(), $extra_data = array())
    {
        $result = array();

        switch ($step_name) {
            case 'trunc_table_prod_fourn':
                $this->truncTableProdFourn($errors);
                if (isset($this->options['process_full_file']) && (int) $this->options['process_full_file']) {
                    $result['new_steps']['process_prices'] = array(
                        'label'                  => 'Traitement des prix fourniseur',
                        'on_error'               => 'continue',
                        'nbElementsPerIteration' => 0
                    );
                } else {
                    $result['new_steps']['make_prices_file_parts'] = array(
                        'label'    => 'Création des fichiers intermédiaires pour les prix fourniseur',
                        'on_error' => 'continue'
                    );
                }
                break;

            case 'update_prices_file':
                if (isset($this->nameFile) && $this->nameFile) {
                    $fileName = $this->nameFile;
                    $this->downloadFtpFile($fileName, $errors);

                    if (!count($errors)) {
                        if ($this->options['debug']) {
                            error_reporting(E_ALL);
                        }

                        if ($this->options['debug']) {
                            error_reporting(E_ERROR);
                        }

                        $result['new_steps']['trunc_table_prod_fourn'] = array(
                            'label'    => 'Vidage de la table import',
                            'on_error' => 'stop'
                        );
                    }
                } else {
                    $errors[] = 'Nom du fichier stock fournisseur absent';
                }
                break;

            case 'make_prices_file_parts':
                if (isset($this->nameFile) && $this->nameFile) {
                    $this->makeCsvFileParts($this->local_dir, $this->nameFile, $errors, 3000, 1);

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

                $this->DebugData($file_data, 'Données fichier');

                if (!count($errors) && !empty($file_data)) {
                    $this->processFournPrices($file_data, $errors, true);
                    
                    $i=0;
                    $tabHtml = array();
                    foreach($file_data as $line){
                        if($line['is_sleep2'] == 'true' || !$line['is_actif'])
                            continue;
                        if($line['pa_ht'] > $line['pu_ht']){
                            $i++;
//                            echo '<pre>';
//                            print_r($line);
//                            die('fin');
                            $tabHtml[$line['code']] = '<h3>'.$line['ref_fourn'].'</h3><h4>'.$line['code'].'</h4>PV : '.round($line['pu_ht'],2).' € PA : '.round($line['pa_ht'], 2).' €';
                        }
                    }
                    if(count($tabHtml)){
                        ksort($tabHtml);
                        mailSyn2('Produit LDLC marge négative', 'tommy@bimp.fr'/*, a.pfeffer@ldlc.com, j.viales@ldlc.com'*/, null, '<h3>Bonjour, voici la liste des produits avec une marge négative ('.$i.')</h3><br/><br/>'.implode('<br/><br/><br/>------------------------- ', $tabHtml));
                    }
                }
                break;
        }

        return $result;
    }
    
    public function getFtpParams($params){
        if($params == 'ftp_host')
            return BimpCore::getConf('exports_ldlc_ftp_serv');
        if($params == 'ftp_login')//a virer quand catalogue deverssé sur actimac
            return 'bimp-erp';
        if($params == 'ftp_pwd')//a virer quand catalogue deverssé sur actimac
            return 'Yu5pTR?(3q99Aa';
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
                    'name'        => 'ImportsLdlc',
                    'title'       => ($title ? $title : static::$default_public_title),
                    'description' => '',
                    'type'        => 'import',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {

            // Params: 

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'ftp_dir',
                'label'      => 'Dossier FTP',
                'value'      => '/'.BimpCore::getConf('exports_ldlc_ftp_dir').'/catalogue/'
                    ), true, $warnings, $warnings);
            
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'file_name',
                'label'      => 'Nom fichier FTP',
                'value'      => 'Ymd_catalog_ldlc_to_bimp.csv',
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
