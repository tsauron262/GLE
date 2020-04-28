<?php

class BDS_ImportsAppleProcess extends BDSImportProcess
{

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
        'ArtFree3'      => 'cto',
        'ArtLastPA'     => 'cur_pa_ht',
        'ArtIsSupp'     => 'crt'
    );
    public static $prices_keys = array(
        'code produit' => 'ref_fourn',
        'prix base'    => 'price',
        'code art'     => 'fk_product'
    );

    // Init opérations: 

    public function initImportCsv(&$data, &$errors = array())
    {
        $data['steps'] = array();

        if (isset($this->options['products_file']) && (string) $this->options['products_file']) {
            $file_errors = array();
            $file_data = $this->getFileData('products', $this->options['products_file'], $file_errors);
                        
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

        foreach (array(
    'pa_apple_file'  => array('import_apple_prices', 'Traitement des prix d\'achat Apple'),
    'pa_td_file'     => array('import_td_prices', 'Traitement des prix d\'achat TechData'),
    'pa_ingram_file' => array('import_ingram_prices', 'Traitement des prix d\'achat Ingram')
        ) as $opt_name => $step) {
            if (isset($this->options[$opt_name]) && (string) $this->options[$opt_name]) {
                $file_errors = array();
                $file_data = $this->getFileData('prices', $this->options[$opt_name], $file_errors);

                if (count($file_errors)) {
                    $errors = array_merge($errors, $file_errors);
                } else {
                    $data['steps'][$step[0]] = array(
                        'label'    => $step[1],
                        'on_error' => 'continue',
                        'elements' => $this->getElementsFromData($file_data, 'ref_fourn')
                    );
                }
            }
        }

        if (isset($data['steps']['import_products']) && (int) $this->options['validate_products']) {
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

    public function executeImportCsv($step_name, &$errors = array())
    {
        $result = array();

        $id_fourn = 0;
        $prices_file = '';

        switch ($step_name) {
            case 'import_products':
            case 'validate_products':
                $file_data = $this->getFileData('products', $this->options['products_file'], $errors);

                if (!count($errors)) {
                    switch ($step_name) {
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
                                        $prod_wanings = array();
                                        $prod_errors = $product->validateProduct($prod_wanings);

                                        if (count($prod_errors)) {
                                            $this->Error(BimpTools::getMsgFromArray($prod_errors, 'Echec de la validation'), $product, $prod_data['ref']);
                                        } else {
                                            $this->Success('Validation effectuée avec succès', $product, $prod_data['ref']);
                                        }

                                        if (count($prod_wanings)) {
                                            $this->Alert(BimpTools::getMsgFromArray($prod_wanings, 'Erreur(s) lors de la validation'), $product, $prod_data['ref']);
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

            case 'import_apple_prices':
                $id_fourn = (int) $this->params['id_fourn_apple'];
                $prices_file = (isset($this->options['pa_apple_file']) ? $this->options['pa_apple_file'] : '');
                if (!$prices_file) {
                    $errors[] = 'Fichier des prix Apple absent';
                }
                break;

            case 'import_td_prices':
                $id_fourn = (int) $this->params['id_fourn_td'];
                $prices_file = (isset($this->options['pa_td_file']) ? $this->options['pa_td_file'] : '');
                if (!$prices_file) {
                    $errors[] = 'Fichier des prix TechData absent';
                }
                break;

            case 'import_ingram_prices':
                $id_fourn = (int) $this->params['id_fourn_ingram'];
                $prices_file = (isset($this->options['pa_ingram_file']) ? $this->options['pa_ingram_file'] : '');
                if (!$prices_file) {
                    $errors[] = 'Fichier des prix Ingram absent';
                }
                break;
        }

        if ($id_fourn && $prices_file) {
            $file_data = $this->getFileData('prices', $prices_file, $errors);

            if (!count($errors) && !empty($file_data)) {
                $instance = BimpObject::getInstance('bimpcore', 'Bimp_ProductFournisseurPrice');

                $this->setCurrentObject($instance);

                foreach ($file_data as $idx => $price_data) {
                    if (!(int) $price_data['fk_product']) {
                        if (isset($price_data['ref_prod'])) {
                            $this->Error('Aucun produit trouvé pour cette référence', $instance, $price_data['ref_prod']);
                        } else {
                            $this->Error('Ligne n° ' . $idx + 2 . ': référence produit absente', $instance, '');
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

    public function getFileData($type, $file, &$errors = array())
    {
        if (!in_array($type, array('products', 'prices'))) {
            $errors[] = 'Type de fichier invalide (' . $type . ')';
            return array();
        }

        $this->cleanTxtFile($file, $this->options['from_format']);

        $file_errors = array();

        switch ($type) {
            case 'products':
                $data = $this->getCsvFileDataFromHeaderCodes($file, self::${$type . '_keys'}, $file_errors, 1, "\t", 2);
                break;

            case 'prices':
                $data = $this->getCsvFileDataFromHeaderCodes($file, self::${$type . '_keys'}, $file_errors, 0, "\t", 1);
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
}
