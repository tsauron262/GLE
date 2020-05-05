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
    public static $product_keys = array(
    );
    public static $stock_keys = array(
    );

    public function initUpdateFromFile(&$data, &$errors = array())
    {
        $data['steps'] = array();

        $this->truncTableProdFourn($errors);

        if (isset($this->options['update_files']) && (int) $this->options['update_files']) {
            $data['steps']['update_prices_file'] = array(
                'label'    => 'Téléchargement du fichier des prix',
                'on_error' => 'stop'
            );
//                $data['steps']['update_stocks_file'] = array(
//                    'label'    => 'Téléchargement du fichier des stocks',
//                    'on_error' => 'stop'
//                );
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
//                    $this->downloadFtpFile($this->params['prices_file'], $errors);

                    if (!count($errors)) {
                        $this->makeCsvFileParts($this->local_dir, $this->params['prices_file'], $errors, 10, 0);

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

                $file_data = $this->getFileData($this->params['prices_file'], $errors, -1, 0, array(
                    'part_file_idx' => $file_idx
                ));

                if (!count($errors) && !empty($file_data)) {
                    $this->processFournPrices($file_data, $errors);
                }
                break;

            case 'process_stocks':
//                $file_data = $this->getFileData($this->params['prices_file'], $errors);
//
//                if (!count($errors) && !empty($file_data)) {
//                    $this->processStocks($file_data, $errors);
//                }
                break;

            case 'process_elements':
                $this->executeUpdateFromFile('process_prices', $errors);
                $this->executeUpdateFromFile('process_stocks', $errors);
                break;
        }

        return $result;
    }

    // Traitements: 

    public function getStockFileData($fileName, &$errors = array())
    {
        $data = array();

        return $data;
    }

    public function processStocks($file_data, &$errors = array())
    {
        
    }
}
