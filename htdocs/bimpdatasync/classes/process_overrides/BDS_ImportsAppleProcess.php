<?php

require_once(DOL_DOCUMENT_ROOT . '/bimpdatasync/classes/BDSImportProcess.php');

class BDS_ImportsAppleProcess extends BDSImportProcess
{

    public static $current_version = 2;
    public static $default_public_title = 'Imports produits Apple';
    public static $products_keys = array(
        'ArtCode'       => 'ref',
        'ArtLib'        => 'label',
        'ArtCodeBarre'  => 'barcode',
        'ArtStkNuf'     => 'serialisable',
        'ArtPrixBase'   => 'price',
        'ArtGTaxTaux'   => 'tva_tx',
        'ArtPrixTTC'    => 'price_ttc',
        'ArtGammeEnu'   => 'gamme',
        'ArtCategEnu'   => 'categorie',
        'ArtCollectEnu' => 'collection',
        'ArtNatureEnu'  => 'nature',
        'ArtFamilleEnu' => 'famille',
        'ArtFree1'      => 'deee',
        'ArtFree2'      => 'rpcp',
        'ArtFree3'      => 'cto',
        'ArtLastPA'     => 'cur_pa_ht',
        'ArtIsSupp'     => 'crt'
    );
    public static $prices_keys = array(
        'code produit' => 'ref_fourn',
        'prix base'    => 'price',
        'code art'     => 'fk_product'
    );

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

    // Init opérations:

    public function initImportCsv(&$data, &$errors = array())
    {
        $data['steps'] = array();

        if (isset($this->options['products_file']) && (string) $this->options['products_file']) {
            $file_errors = array();
            $file_data = $this->getFileData('products', $this->options['products_file'], $file_errors, $this->getOption('delimiteur', "\t"));

            if (count($file_errors)) {
                $errors = array_merge($errors, $file_errors);
            } else {
                $data['steps']['import_products'] = array(
                    'label'    => 'Traitement des produits à créer',
                    'on_error' => 'stop',
                    'elements' => $this->getElementsFromData($file_data, 'ref')
                );
            }
        }

        if (isset($this->options['prices_file']) && (string) $this->options['prices_file']) {
            $id_fourn = (int) $this->getOption('id_fourn', 0);
            if (!$id_fourn) {
                $errors[] = 'Aucun fournisseur sélectionné pour l\'import des prix d\'achat';
            } else {
                $fourn = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Fournisseur', $id_fourn);
                if (!BimpObject::objectLoaded($fourn)) {
                    $errors[] = 'Le fournisseur #' . $id_fourn . ' n\'existe pas';
                } else {
                    $file_errors = array();
                    $file_data = $this->getFileData('prices', $this->options['prices_file'], $file_errors, $this->getOption('delimiteur', "\t"));

                    if (count($file_errors)) {
                        $errors = array_merge($errors, $file_errors);
                    } else {
                        $data['steps']['import_fourn_prices'] = array(
                            'label'    => 'Import des prix d\'achat pour le fournisseur "' . $fourn->getName() . '"',
                            'on_error' => 'continue',
                            'elements' => $this->getElementsFromData($file_data, 'ref_fourn')
                        );
                    }
                }
            }
        }

//        foreach (array(
//    'pa_apple_file'  => array('import_apple_prices', 'Traitement des prix d\'achat Apple'),
//    'pa_td_file'     => array('import_td_prices', 'Traitement des prix d\'achat TechData'),
//    'pa_ingram_file' => array('import_ingram_prices', 'Traitement des prix d\'achat Ingram'),
//    'pa_prokov_file' => array('import_prokov_prices', 'Traitement des prix d\'achat PROKOV')
//        ) as $opt_name => $step) {
//            if (isset($this->options[$opt_name]) && (string) $this->options[$opt_name]) {
//                $file_errors = array();
//                $file_data = $this->getFileData('prices', $this->options[$opt_name], $file_errors, $this->getOption('delimiteur', "\t"));
//
//                if (count($file_errors)) {
//                    $errors = array_merge($errors, $file_errors);
//                } else {
//                    $data['steps'][$step[0]] = array(
//                        'label'    => $step[1],
//                        'on_error' => 'continue',
//                        'elements' => $this->getElementsFromData($file_data, 'ref_fourn')
//                    );
//                }
//            }
//        }

        if (isset($data['steps']['import_products'])/* && isset(array_values($file_data)[0]['crt']) */) {
            $data['steps']['crt_products'] = array(
                'label'    => 'Traitement des CRT produits',
                'on_error' => 'continue',
                'elements' => $data['steps']['import_products']['elements']
            );
        }

        if (isset($data['steps']['import_products']) && (int) $this->getOption('validate_products', 0)) {
            $data['steps']['validate_products'] = array(
                'label'    => 'Validation des produits',
                'on_error' => 'continue',
                'elements' => $data['steps']['import_products']['elements']
            );
        }

        if (empty($data['steps'])) {
            $errors[] = 'Aucune donnée à traiter';
        }
    }

    // Exec opérations:

    public function executeImportCsv($step_name, &$errors = array(), $extra_data = array())
    {
        $result = array();

        $id_fourn = 0;
        $prices_file = '';

        switch ($step_name) {
            case 'import_products':
            case 'validate_products':
            case 'crt_products':
                $file_data = $this->getFileData('products', $this->options['products_file'], $errors, $this->getOption('delimiteur', "\t"));

                $this->DebugData($file_data, 'Données fichier');

                if (!count($errors)) {
                    switch ($step_name) {
                        case 'crt_products':
                            foreach ($file_data as $idx => $prod_data) {
                                if (isset($prod_data['ref']) && (string) $prod_data['ref'] && isset($prod_data['crt']) && (float) $prod_data['crt']) {
                                    $product = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                                                'ref' => $prod_data['ref']
                                    ));
                                    if (BimpObject::objectLoaded($product)) {
                                        $file_data[$idx]['id_product'] = $product->id;
                                        $file_data[$idx]['value'] = $prod_data['crt'];
                                        continue;
                                    }
                                }
                                unset($file_data[$idx]);
                            }
                            $this->createBimpObjects('bimpcore', 'Bimp_ProductRA', $file_data, $errors, array('update_if_exists' => true, 'fields_find_exist' => array('id_product', 'type'), 'constante_fields' => array('type' => 'crt', 'nom' => 'CRT', 'active' => '1')));
                            break;

                        case 'import_products':
                            $this->createBimpObjects('bimpcore', 'Bimp_Product', $file_data, $errors, array('update_if_exists' => true));
                            break;

                        case 'validate_products':
                            foreach ($file_data as $idx => $prod_data) {
                                if (isset($prod_data['ref']) && (string) $prod_data['ref']) {
                                    $product = BimpCache::findBimpObjectInstance('bimpcore', 'Bimp_Product', array(
                                                'ref' => $prod_data['ref']
                                    ));

                                    if (BimpObject::objectLoaded($product)) {
                                        if ($product->getData('validate')) {
                                            $this->Info('Ce produit est déjà validé', $product, $prod_data['ref']);
                                        } else {
                                            $prod_wanings = array();
                                            if (isset($this->options['force_validation']) && (int) $this->options['force_validation']) {
                                                $prod_errors = $product->updateField('validate', 1, null, true, true);
                                            } else {
                                                $prod_errors = $product->validateProduct($prod_wanings);
                                            }

                                            if (count($prod_errors)) {
                                                $this->Error(BimpTools::getMsgFromArray($prod_errors, 'Echec de la validation'), $product, $prod_data['ref']);
                                            } else {
                                                $this->Success('Validation effectuée avec succès', $product, $prod_data['ref']);
                                            }

                                            if (count($prod_wanings)) {
                                                $this->Alert(BimpTools::getMsgFromArray($prod_wanings, 'Erreur(s) lors de la validation'), $product, $prod_data['ref']);
                                            }
                                        }
                                    } else {
                                        $this->Error('Validation impossible: aucun produit trouvé pour cette référence', $product, $prod_data['ref']);
                                    }
                                }
                            }
                            break;
                    }
                }
                break;

            case 'import_fourn_prices':
                $id_fourn = (int) $this->getOption('id_fourn', 0);
                if (!$id_fourn) {
                    $errors[] = 'ID fournisseur absent';
                }
                $prices_file = $this->getOption('prices_file', '');
                if (!$prices_file) {
                    $errors[] = 'Fichier des prix Apple absent';
                }
                break;

//            case 'import_apple_prices':
//                $id_fourn = (int) $this->params['id_fourn_apple'];
//                $prices_file = (isset($this->options['pa_apple_file']) ? $this->options['pa_apple_file'] : '');
//                if (!$prices_file) {
//                    $errors[] = 'Fichier des prix Apple absent';
//                }
//                break;
//
//            case 'import_td_prices':
//                $id_fourn = (int) $this->params['id_fourn_td'];
//                $prices_file = (isset($this->options['pa_td_file']) ? $this->options['pa_td_file'] : '');
//                if (!$prices_file) {
//                    $errors[] = 'Fichier des prix TechData absent';
//                }
//                break;
//
//            case 'import_ingram_prices':
//                $id_fourn = (int) $this->params['id_fourn_ingram'];
//                $prices_file = (isset($this->options['pa_ingram_file']) ? $this->options['pa_ingram_file'] : '');
//                if (!$prices_file) {
//                    $errors[] = 'Fichier des prix Ingram absent';
//                }
//                break;
//
//            case 'import_prokov_prices':
//                $id_fourn = (int) $this->params['id_fourn_prokov'];
//                $prices_file = (isset($this->options['pa_prokov_file']) ? $this->options['pa_prokov_file'] : '');
//                if (!$prices_file) {
//                    $errors[] = 'Fichier des prix PROKOV absent';
//                }
//                break;
        }

        if ($id_fourn && $prices_file) {
            $file_data = $this->getFileData('prices', $prices_file, $errors, $this->getOption('delimiteur', "\t"));

            if (!count($errors) && !empty($file_data)) {
                $instance = BimpObject::getInstance('bimpcore', 'Bimp_ProductFournisseurPrice');

                $this->setCurrentObject($instance);

                foreach ($file_data as $idx => $price_data) {
                    if (!(int) $price_data['fk_product']) {
                        if (isset($price_data['ref_prod'])) {
                            $this->Error('Aucun produit trouvé pour cette référence', $instance, $price_data['ref_prod']);
                        } else {
                            $this->Error('Ligne n° ' . ($idx + 2) . ': référence produit absente', $instance, '');
                        }

                        $this->incIgnored();
                        unset($file_data[$idx]);
                    } else {
                        $file_data[$idx]['fk_soc'] = $id_fourn;
                    }
                }

                if (!empty($file_data)) {
                    $this->createBimpObjects('bimpcore', 'Bimp_ProductFournisseurPrice', $file_data, $errors, array('check_refs' => false));
                }
            }
        }

        return $result;
    }

    // Traitements:

    public function getFileData($type, $file, &$errors = array(), $delimiter = "\t")
    {
        if (!in_array($type, array('products', 'prices'))) {
            $errors[] = 'Type de fichier invalide (' . $type . ')';
            return array();
        }

        $this->cleanTxtFile($file, $this->options['from_format']);

        $file_errors = array();

        switch ($type) {
            case 'products':
                $data = $this->getCsvFileDataByKeys($file, self::${$type . '_keys'}, $file_errors, $delimiter, 1, 2);
                break;

            case 'prices':
                $data = $this->getCsvFileDataByKeys($file, self::${$type . '_keys'}, $file_errors, $delimiter, 0, 1);
                break;
        }

        if (count($file_errors)) {
            $errors = array_merge($errors, $file_errors);
        } else {
            if (empty($data)) {
                $errors[] = 'Aucun produit à importer trouvé dans le fichier "' . pathinfo($file, PATHINFO_FILENAME) . '"';
            } else {
                switch ($type) {
                    case 'products':
                        $categories = BimpCache::getProductsTagsByTypeArray('categorie', false);
                        $collections = BimpCache::getProductsTagsByTypeArray('collection', false);
                        $natures = BimpCache::getProductsTagsByTypeArray('nature', false);
                        $familles = BimpCache::getProductsTagsByTypeArray('famille', false);
                        $gammes = BimpCache::getProductsTagsByTypeArray('gamme', false);

                        foreach ($data as $idx => $prod_data) {
                            foreach ($prod_data as $field => $value) {
                                switch ($field) {
                                    case 'serialisable':
                                        if ($value === 'NUFARTSTKSERIE') {
                                            $value = 1;
                                        } else {
                                            $value = 0;
                                        }
                                        break;

                                    case 'price':
                                    case 'tav_tx':
                                    case 'price_ttc':
                                    case 'ecotaxe':
                                    case 'cur_pa':
                                    case 'crt':
                                        $value = str_replace(' ', '', $value);
                                        $value = (float) str_replace(',', '.', $value);
                                        break;

                                    case 'gamme':
                                        $value = 0;
                                        foreach ($gammes as $id => $label) {
                                            if ((string) $prod_data[$field] == $label) {
                                                $value = $id;
                                                break;
                                            }
                                        }
                                        break;

                                    case 'categorie':
                                        $value = 0;
                                        foreach ($categories as $id => $label) {
                                            if ((string) $prod_data[$field] == $label) {
                                                $value = $id;
                                                break;
                                            }
                                        }
                                        break;

                                    case 'collection':
                                        $value = 0;
                                        foreach ($collections as $id => $label) {
                                            if ((string) $prod_data[$field] == $label) {
                                                $value = $id;
                                                break;
                                            }
                                        }
                                        break;

                                    case 'nature':
                                        $value = 0;
                                        foreach ($natures as $id => $label) {
                                            if ((string) $prod_data[$field] == $label) {
                                                $value = $id;
                                                break;
                                            }
                                        }
                                        break;

                                    case 'famille':
                                        $value = 0;
                                        foreach ($familles as $id => $label) {
                                            if ((string) $prod_data[$field] == $label) {
                                                $value = $id;
                                                break;
                                            }
                                        }
                                        break;
                                }
                                $data[$idx][$field] = $value;
                            }
                        }
                        break;

                    case 'prices':
                        foreach ($data as $idx => $price_data) {
                            $ref_prod = '';
                            if (isset($price_data['fk_product']) && (string) $price_data['fk_product']) {
                                $ref_prod = (string) $price_data['fk_product'];
                                $prod_data = $this->db->getRow('product', 'ref = \'' . $ref_prod . '\'', array('rowid', 'tva_tx'), 'array');
                            } else {
                                $prod_data = array(
                                    'rowid'  => 0,
                                    'tva_tx' => 0
                                );
                            }
                            foreach ($price_data as $field => $value) {
                                switch ($field) {
                                    case 'fk_product':
                                        $value = (int) $prod_data['rowid'];
                                        break;

                                    case 'price':
                                        $value = (float) str_replace(' ', '', $value);
                                        $value = (float) str_replace(',', '.', $value);
                                        break;
                                }
                                $data[$idx][$field] = $value;
                            }
                            $data[$idx]['tva_tx'] = $prod_data['tva_tx'];

                            if ((!isset($prod_data['fk_product']) || !(int) $prod_data['fk_product']) && $ref_prod) {
                                $data[$idx]['ref_prod'] = $ref_prod;
                            }
                        }
                        break;
                }
            }
        }
        return $data;
    }

    // Install: 

    public static function install(&$errors = array(), &$warnings = array(), $title = '')
    {
        // Process: 

        $process = BimpObject::createBimpObject('bimpdatasync', 'BDS_Process', array(
                    'name'        => 'ImportsApple',
                    'title'       => ($title ? $title : static::$default_public_title),
                    'description' => 'Import des produits Apple et/ou mise à jour des prix d\'achat',
                    'type'        => 'import',
                    'active'      => 1
                        ), true, $errors, $warnings);

        if (BimpObject::objectLoaded($process)) {

            // Params: 

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'id_fourn_apple',
                'label'      => 'ID APPLE',
                'value'      => ''
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'id_fourn_td',
                'label'      => 'ID TECHDATA',
                'value'      => ''
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'id_fourn_ingram',
                'label'      => 'ID INGRAM',
                'value'      => ''
                    ), true, $warnings, $warnings);

            BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessParam', array(
                'id_process' => (int) $process->id,
                'name'       => 'id_fourn_prokov',
                'label'      => 'ID PROKOV',
                'value'      => ''
                    ), true, $warnings, $warnings);

            // Options: 

            $options = array();

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Produits',
                        'name'          => 'products_file',
                        'info'          => 'Pour chaque référence, si un produit existe déjà, il sera mis à jour avec les données du fichier.',
                        'type'          => 'file',
                        'default_value' => '',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Prix d\'achat APPLE',
                        'name'          => 'pa_apple_file',
                        'info'          => '',
                        'type'          => 'file',
                        'default_value' => '',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Prix d\'achat TechData',
                        'name'          => 'pa_td_file',
                        'info'          => '',
                        'type'          => 'file',
                        'default_value' => '',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Prix d\'achat Ingram',
                        'name'          => 'pa_ingram_file',
                        'info'          => '',
                        'type'          => 'file',
                        'default_value' => '',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Prix d\'achat PROKOV',
                        'name'          => 'pa_prokov_file',
                        'info'          => '',
                        'type'          => 'file',
                        'default_value' => '',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Valider les produits',
                        'name'          => 'validate_products',
                        'info'          => 'Le fichier "Produits" doit être fourni.<br/><br/>A noter que les produits ne pourront pas être validés s\'ils ne disposent pas d\'au moins un prix d\'achat enregistré',
                        'type'          => 'toggle',
                        'default_value' => '1',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Forcer la validation',
                        'name'          => 'force_validation',
                        'info'          => 'Valider même si pas de prix d\'achat fournisseur',
                        'type'          => 'toggle',
                        'default_value' => '0',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            $opt = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                        'id_process'    => (int) $process->id,
                        'label'         => 'Format d\'origine des fichiers',
                        'name'          => 'from_format',
                        'info'          => '',
                        'type'          => 'select',
                        'select_values' => '=>,macintosh=>Mac OS Roman',
                        'default_value' => '',
                        'required'      => 0
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($opt)) {
                $options[] = (int) $opt->id;
            }

            // Opérations: 

            $op = BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process'    => (int) $process->id,
                        'title'         => 'Import des produits',
                        'name'          => 'importCsv',
                        'description'   => 'Import des produits APPLE via fichiers CSV',
                        'warning'       => '',
                        'active'        => 1,
                        'use_report'    => 1,
                        'reports_delay' => 365
                            ), true, $warnings, $warnings);

            if (BimpObject::objectLoaded($op)) {
                $warnings = array_merge($warnings, $op->addAssociates('options', $options));
            }
        }
    }

    public static function updateProcess($id_process, $cur_version, &$warnings = array())
    {
        $errors = array();

        if ($cur_version < 2) {
            $bdb = BimpCache::getBdb();

            $operation = BimpCache::findBimpObjectInstance('bimpdatasync', 'BDS_ProcessOperation', array(
                        'id_process' => $id_process,
                        'name'       => 'importCsv'
                            ), true);

            if (BimpObject::objectLoaded($operation)) {
                $opt = BimpCache::findBimpObjectInstance('bimpdatasync', 'BDS_ProcessOption', array(
                            'id_process' => $id_process,
                            'name'       => 'delimiteur'
                                ), true);
                if (!BimpObject::objectLoaded($opt)) {
                    BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                                'id_process'    => $id_process,
                                'label'         => 'Délimiteur',
                                'name'          => 'delimiteur',
                                'info'          => 'Délimiteur utilisé dans les fichiers csv',
                                'type'          => 'text',
                                'default_value' => 'tab',
                                'required'      => 0
                                    ), true, $errors, $warnings);
                }


                BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                    'id_process'    => $id_process,
                    'label'         => 'Fichiers des prix d\'achat fournisseur',
                    'name'          => 'prices_file',
                    'info'          => '',
                    'type'          => 'file',
                    'default_value' => '',
                    'required'      => 0
                        ), true, $errors, $warnings);

                BimpObject::createBimpObject('bimpdatasync', 'BDS_ProcessOption', array(
                    'id_process'    => $id_process,
                    'label'         => 'Fournisseur',
                    'name'          => 'id_fourn',
                    'info'          => 'Obligatoire si le fichier des prix d\'achat fournisseur est fourni',
                    'type'          => 'select',
                    'select_values' => 'static::fournisseurs',
                    'default_value' => 0,
                    'required'      => 0
                        ), true, $errors, $warnings);

                $bdb->delete('bds_process_option', 'id_process = ' . $id_process . ' AND name = \'pa_apple_file\'');
                $bdb->delete('bds_process_option', 'id_process = ' . $id_process . ' AND name = \'pa_td_file\'');
                $bdb->delete('bds_process_option', 'id_process = ' . $id_process . ' AND name = \'pa_ingram_file\'');
                $bdb->delete('bds_process_option', 'id_process = ' . $id_process . ' AND name = \'pa_prokov_file\'');

                $errors = $operation->setOptions(array('products_file', 'prices_file', 'id_fourn', 'force_validation', 'validate_products', 'from_format', 'delimiteur'));
            } else {
                $errors[] = 'Opération "Import des produits" non trouvée';
            }
        }

        return $errors;
    }
}
