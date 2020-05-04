<?php

require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSImportFournCatalogProcess.php';

class BDS_ImportsLdlcProcess extends BDSImportFournCatalogProcess
{
    public static $keys = array(
        1 => 'ref',
        2 => 'lib',
        3 => 'ref_fourn',
        4 => 'brand',
        5 => 'pu_ht',
        7 => 'prix_base'
    );

    public function initUpdateFromFile(&$data, &$errors = array())
    {
        $data['steps'] = array();

        if (parent::initImports($data, $errors)) {
            if (isset($this->options['update_files']) && (int) $this->options['update_files']) {
                $data['steps']['update_prices_file'] = array(
                    'label'    => 'Téléchargement du fichier des prix',
                    'on_error' => 'stop'
                );
            }

            if (isset($this->options['nb_elements']) && (int) $this->options['nb_elements'] > 0) {
                $file_data = $this->getFileData($this->params['prices_file'], $errors);

                if (!empty($file_data)) {
                    $data['steps']['process_prices'] = array(
                        'label'                  => 'Import des prix fourniseur',
                        'on_error'               => 'continue',
                        'elements'               => $this->getElementsFromData($file_data, 'ref'),
                        'nbElementsPerIteration' => (int) $this->options['nb_elements']
                    );
                }
            } else {
                $file_data = $this->getFileData($this->params['prices_file'], $errors);
                if (!empty($file_data)) {
                    $data['steps']['process_prices'] = array(
                        'label'    => 'Traitement des imports',
                        'on_error' => 'continue'
                    );
                }
            }
        }
    }

    public function executeUpdateFromFile($step_name, &$errors = array())
    {
        $result = array();

        switch ($step_name) {
            case 'update_prices_file':
                break;

            case 'process_prices':
                break;
        }

        return $result;
    }
}
