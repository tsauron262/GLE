<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSProcess.php');

class BDS_ImportsDiversProcess extends BDSProcess
{

    public static $current_version = 2;
    public static $default_public_title = 'Imports Divers';

    // Imports AppleCare: 

    public function initImportProductsAppleCare(&$data, &$errors = array())
    {
        $file = $this->getOption('csv_file', '');

        if (!$file) {
            $errors[] = 'Fichier absent';
        } else {
            $rows = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if (empty($rows)) {
                $errors[] = 'Fichier vide';
            } else {
                $data['steps'] = array(
                    'import' => array(
                        'label'                  => 'Création des remises AppleCare',
                        'on_error'               => 'continue',
                        'elements'               => $rows,
                        'nbElementsPerIteration' => 100
                    )
                );
            }
        }
    }

    public function executeImportProductsAppleCare($step_name, &$errors = array(), $extra_data = array())
    {
        $this->setCurrentObjectData('bimpcore', 'Bimp_ProductRA');
        $prod = BimpObject::getInstance('bimpcore', 'Bimp_Product');
        foreach ($this->references as $line) {
            $this->incProcessed();

            $data = explode(';', $line);
            $ref = $data[0];

            if (isset($data[1])) {
                $value = (float) $data[1];
            } else {
                $value = 20;
            }

            if ($ref && $value) {
                $id_product = (int) $this->db->getValue('product', 'rowid', 'ref = \'APP-' . $ref . '\'');

                if (!$id_product) {
                    $this->Error('Produit non trouvé', null, $ref);
                } else {
                    $prod->id = $id_product;
                    $id_ra = (int) $this->db->getValue('product_remise_arriere', 'id', 'id_product = ' . $id_product . ' AND type = \'applecare\'');
                    if ($id_ra) {
                        $this->Alert('Remise AppleCare déjà créée pour ce produit', $prod, $ref);
                    } else {
                        $id_ra = (int) $this->db->insert('product_remise_arriere', array(
                                    'id_product' => $id_product,
                                    'type'       => 'applecare',
                                    'nom'        => 'AppleCare',
                                    'value'      => $value,
                                    'active'     => 1
                                        ), true);

                        if ($id_ra <= 0) {
                            $this->Error('Echec ajout AppleCare (' . $value . ' %) - ' . $this->db->err(), $prod, $ref);
                        } else {
                            $this->Success('Ajout AppleCare OK (' . $value . ' %)', $prod, $ref);
                            $this->incCreated();
                            continue;
                        }
                    }
                }
            } else {
                $this->Error('Ligne invalide: ' . $line);
            }
            $this->incIgnored();
        }
    }

    // Install / updates: 

    public static function install(&$errors = array(), &$warnings = array(), $title = '')
    {
        // Process:
        BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
            'name'        => 'ImportsDivers',
            'title'       => ($title ? $title : static::$default_public_title),
            'description' => '',
            'type'        => 'import',
            'active'      => 1
                ), true, $errors, $warnings);
    }

    public static function updateProcess($id_process, $cur_version, &$warnings = array())
    {
        $errors = array();

        if ($cur_version < 2) {
            // Options "Fichier CSV": 
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                'id_process'    => (int) $id_process,
                'label'         => 'Fichier CSV',
                'name'          => 'csv_file',
                'info'          => '',
                'type'          => 'file',
                'default_value' => '',
                'required'      => 1
                    ), true, $errors, $warnings);

            // Opération "Import Remises AppleCare produits": 
            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $id_process,
                        'title'         => 'Import Remises AppleCare produits',
                        'name'          => 'importProductsAppleCare',
                        'description'   => '',
                        'warning'       => 'Fichier CSV : ref_produit;pourcentage_remise',
                        'active'        => 1,
                        'use_report'    => 1,
                        'reports_delay' => 15
                            ), true, $errors, $warnings);

            if (BimpObject::objectLoaded($op)) {
                $errors = array_merge($errors, $op->addOptions(array('csv_file')));
            }
        }

        return $errors;
    }
}
