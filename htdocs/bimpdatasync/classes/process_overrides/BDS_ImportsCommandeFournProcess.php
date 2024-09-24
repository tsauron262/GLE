<?php

require_once DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSImportFournCatalogProcess.php';

class BDS_ImportsCommandeFournProcess extends BDSImportFournCatalogProcess
{

    public static $default_public_title = 'Imports Commande Fourn';
    public static $price_keys = array(
        'DESCRIPTION' => 'desc',
        'EAN' => 'ean',
        'TVA' => 'tva',
        'P.U. HT' => 'pu',
        'P.U TTC' => 'pu_ttc',
        'Qté' => 'qty',
        'Réduc.' => 'reduc',
        'Total HT' => 'total',
        'PRIX PUBLIC TTC' => 'public_price',
    );
    
//    public $local_dir = 'import_commande_fourn';

    // Getters array: 

    public function getFournisseursArray()
    {
        $fourns = array(
            0 => ''
        );

        $rows = $this->db->getRows('societe', 'fournisseur = 1 AND status = 1 AND is_anonymized = 0', null, 'array', array('rowid', 'code_fournisseur as ref', 'nom'));

        if (is_array($rows)) {
            foreach ($rows as $r) {
                $fourns[(int) $r['rowid']] = $r['ref'] . ' - ' . $r['nom'];
            }
        }
        return $fourns;
    }

    public function initUpdateFromFile(&$data, &$errors = array())
    {
        $data['steps'] = array();


        if (isset($this->options['process_full_file']) && (int) $this->options['process_full_file']) {
            $data['steps']['process_prices'] = array(
                'label'                  => 'Traitement des lignes',
                'on_error'               => 'continue',
                'nbElementsPerIteration' => 0
            );
        } else {
            $partsDir = $this->getFilePartsDirname($this->params['prices_file']);
            $prices_files_indexes = $this->getPartsFilesIndexes($this->local_dir . '/' . $partsDir);

            if (!empty($prices_files_indexes)) {
                $data['steps']['process_prices'] = array(
                    'label'                  => 'Import des lignes',
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

    public function executeUpdateFromFile($step_name, &$errors = array(), $extra_data = array())
    {
        $result = array();
        $this->params['id_fourn'] = $this->options['id_fourn'];

        switch ($step_name) {

            case 'process_prices':
                $file_idx = 0;

                if (!isset($this->options['process_full_file']) || !(int) $this->options['process_full_file']) {
                    if (!empty($this->references)) {
                        $file_idx = (int) $this->references[0];
                    }
                }
                $this->references = array();
                
                
                $rows = $this->getCsvFileDataByKeys($this->options['products_file'], static::$price_keys, $errors, ";", 1, 2);
                
                
                /* creation commande fourn */
                $commFourn = BimpObject::createBimpObject('bimpcommercial', 'Bimp_CommandeFourn', array(
                    'ref_supplier' => $this->options['ref'],
                    'fk_soc' => $this->options['id_fourn'],
                    'ef_type' => 'C'
                ), false, $errors);


                foreach ($rows as $idT => $datas) {
                    /* traitement des données*/
                    $tabDesc = explode('-', $datas['desc']);
                    $datas['label'] = $tabDesc[1].' '.$tabDesc[2].(isset($tabDesc[3])? ' '.$tabDesc[3] : '').(isset($tabDesc[4])? ' '.$tabDesc[4] : '');
                    $datas['ref'] = $tabDesc[0];
                    $datas['price'] = str_replace(',', '.', $datas['total']) / $datas['qty'];
                    $datas['tva'] = (float) str_replace('%', '', $datas['tva']);
                    
                    
                   /* traitement produit */
                   $product = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array('barcode' => $datas['ean']));
                   if(isset($product) && $product->isLoaded()){
                       $this->Alert('Produit loadé');
                   }
                   else{
                        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product');
                       $this->Alert('pas loadé '.get_class($product));
                   }
                    $product->validateArray(
                            array(
                                'ref' => $datas['ref'],
                                'label' => $datas['label'],
                                'barcode'    => $datas['ean'],
                                'tva_tx' => $datas['tva'],
                                'price' => $datas['public_price']  
                            )
                    );
                    if($product->isLoaded()){
                       $errors = BimpTools::merge_array($errors, $product->update($errors));
                       $this->Alert('Produit maj : '.$product->id);
                    }
                    else{
                       $errors = BimpTools::merge_array($errors, $product->create($errors));
                       $this->Alert('Produit crée : '.$product->id);
                   }
                   
                   
                   /*gestion pa fourn*/
                   
                   $fourn_product = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice', array('fk_product' => $product->id, 'fk_soc' => $this->options['id_fourn']));
                   if(isset($fourn_product) && $fourn_product->isLoaded()){
                       $this->Alert('Produit fourn loadé');
                   }
                   else{
                        $fourn_product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_ProductFournisseurPrice');
                       $this->Alert('pas loadé '.get_class($product));
                   }
                   $fourn_product->validateArray(array(
                        'fk_product' => (int) $product->id,
                        'fk_soc'     => $this->options['id_fourn'],
                        'ref_fourn'  => $datas['ref'],
                        'price'      => $datas['price'],
                        'tva_tx'     => $datas['tva']
                    ));
                   
                    if($fourn_product->isLoaded()){
                       $errors = BimpTools::merge_array($errors, $fourn_product->update($errors));
                       $this->Alert('Produit fourn maj : '.$product->id);
                    }
                    else{
                       $errors = BimpTools::merge_array($errors, $fourn_product->create($errors));
                       $this->Alert('Produit fourn crée : '.$product->id);
                   }
                   
                   /* aj de la ligne de commande */
                   $lnCommFourn = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine');
                   $lnCommFourn->id_product = $product->id;
                   $lnCommFourn->qty = $datas['qty'];
                   $lnCommFourn->validateArray(array(
                        'id_obj' => $commFourn->id
                    ));
                    $errors = BimpTools::merge_array($errors, $lnCommFourn->create($errors));
                }
                break;
        }

        return $result;
    }

    // Install: 

    public static function install(&$errors = array(), &$warnings = array(), $title = '')
    {
        // Process: 

        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'ImportsCommandeFourn',
                    'title'       => ($title ? $title : static::$default_public_title),
                    'description' => '',
                    'type'        => 'import',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {

            // Params: 

           

            // Options: 
            
            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'delimiter',
                'label'      => 'Délimiteur',
                'value'      => ';'
                    ), true, $warnings, $warnings);

           

            $opt1 = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Traiter tout le fichier en une seule étape',
                        'name'          => 'process_full_file',
                        'info'          => '',
                        'type'          => 'toggle',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            $opt2 = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Produits',
                        'name'          => 'products_file',
                        'info'          => 'Pour chaque référence, si un produit existe déjà, il sera mis à jour avec les données du fichier.',
                        'type'          => 'file',
                        'default_value' => '',
                        'required'      => 1
                            ), true, $warnings, $warnings);

            $opt3 =                 BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                    'id_process'    => $process->id,
                    'label'         => 'Fournisseur',
                    'name'          => 'id_fourn',
                    'info'          => 'Obligatoire',
                    'type'          => 'select',
                    'select_values' => 'static::fournisseurs',
                    'default_value' => 0,
                    'required'      => 1
                        ), true, $errors, $warnings);

            $opt4 =                 BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                    'id_process'    => $process->id,
                    'label'         => 'Ref Fournisseur',
                    'name'          => 'ref',
                    'info'          => 'Obligatoire',
                    'type'          => 'text',
                    'default_value' => '',
                    'required'      => 1
                        ), true, $errors, $warnings);

            // Opérations: 


            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $process->id,
                        'title'         => 'Import commande',
                        'name'          => 'updateFromFile',
                        'description'   => '',
                        'warning'       => '',
                        'active'        => 1,
                        'use_report'    => 1,
                        'reports_delay' => 30
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op)) {
                $warnings = array_merge($warnings, $op->addAssociates('options', array($opt1->id, $opt2->id, $opt3->id, $opt4->id)));

                // Crons: 

            }
        }
        
        
    }

// Opérations:

    public function initOperation($id_operation, &$errors)
    {
        // check des params: 

        if (!isset($this->options['id_fourn']) || !(int) $this->options['id_fourn']) {
            $errors[] = 'Paramètre "ID du fournisseur" absent';
            $this->params_ok = false;
        }
        
        //trucage parent 
        $this->params['id_fourn'] = $this->options['id_fourn'];
        

        return parent::initOperation($id_operation, $errors);
    }
}
