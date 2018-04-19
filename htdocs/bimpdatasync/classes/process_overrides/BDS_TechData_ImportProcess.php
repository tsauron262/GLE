<?php

class BDS_TechData_ImportProcess extends BDS_ImportProcess
{

    public static $files_dir_name = 'TechData';
    public $ftp = null;
    public static $product_infos = array(
        'code_produit'             => 0,
        'designation_2'            => 2,
        'article_manufacturer_ref' => 3,
        'manufacturer_name'        => 4,
        'ean'                      => 6,
        'famille'                  => 8,
        'classe'                   => 10,
        'sous-classe'              => 12
    );
    public static $product_prices = array(
        'code_produit'             => 1,
        'designation_1'            => 2,
        'article_manufacturer_ref' => 3,
        'manufacturer_name'        => 4,
        'pv_ht'                    => 5,
        'pa_ht'                    => 7,
        'pa_reduction'             => 8
    );
    public static $product_taxes = array(
        'code_produit' => 0,
        'fee_name'     => 2,
        'fee'          => 3
    );
    public static $product_stocks = array(
        'code_produit'             => 0,
        'article_manufacturer_ref' => 1,
        'manufacturer_name'        => 2,
        'stock'                    => 3,
        'date_livraison'           => 4
    );

    public function __construct($processDefinition, $user, $params = null)
    {
        parent::__construct($processDefinition, $user, $params);

        if (file_exists($this->filesDir)) {
            $dir_tree = array(
                'ftp_files' => null
            );
            $result = BDS_Tools::makeDirectories($dir_tree, $this->filesDir);
            if ($result) {
                $this->logError($result);
                $this->Msg($result);
                $this->parameters_ok = false;
            }
        }
    }
    
    public static function getClassName()
    {
        return 'BDS_TechData_ImportProcess';
    }

    public function test()
    {
        self::$debug_mod = true;
        echo $this->debug_content;
    }

    protected function initFtpConnexionTest(&$data, &$errors)
    {
        $data['steps'] = array();
        $data['use_report'] = false;

        if (!$this->parameters_ok) {
            $errors[] = 'Certains paramètres sont invalides. Veuillez vérifier la configuration';
        }

        $html = '';

        $this->ftp = $this->ftpConnect($this->parameters['ftp_server'], $this->parameters['ftp_user'], $this->parameters['ftp_pword'], true, $errors);

        if ($this->ftp && !count($errors)) {
            $html .= '<p class="alert alert-success">La connexion au serveur FTP "' . $this->parameters['ftp_server'] . '" a été effectuée avec succès</p>';

            $files = ftp_nlist($this->ftp, '');

            if ($files === false) {
                $html .= '<p class="alert alert-danger">Echec de la récupération de la liste des fichiers présents sur le serveur</p>';
            } elseif (self::$debug_mod) {
                $this->debug_content .= 'Fichiers présents sur le serveur FTP: <pre>';
                $this->debug_content .= print_r($files, 1);
                $this->debug_content .= '</pre>';
            }

            foreach ($this->parameters as $name => $value) {
                if (preg_match('/^ftp_file_(.+)$/', $name)) {
                    if (!in_array($value, $files)) {
                        $html .= '<p alert alert-warning>Le fichier "' . $value . '" est absent du serveur FTP de Tech Data</p>';
                    }
                }
            }
        }
        
        ftp_close($this->ftp);
        $this->ftp = null;
        $data['result_html'] = $html;
    }

    protected function initFtpUpdates(&$data, &$errors)
    {
        $data['steps'] = array();
        $data['use_report'] = true;

        if (!$this->parameters_ok) {
            $errors[] = 'Certains paramètres sont invalides. Veuillez vérifier la configuration du processus';
            return;
        }

        if (!$this->options_ok) {
            $errors[] = 'Options invalides ou manquantes.';
            return;
        }

        if (isset($this->options['new_references_category']) && $this->options['new_references_category']) {
            if (!preg_match('/^\d+$/', $this->options['new_references_category'])) {
                $errors[] = 'L\'ID de la catégorie pour les nouveaux produits n\'est pas valide. Veuillez indiquer un nombre entier positif';
                return;
            } else {
                $result = $this->db->getRow('categorie', '`rowid` = ' . (int) $this->options['new_references_category']);
                if (is_null($result)) {
                    $errors[] = 'La catégorie d\'ID ' . $this->options['new_references_category'] . ' n\'existe pas';
                    return;
                }
            }
        }

        if (isset($this->options['new_references']) && $this->options['new_references']) {
            $this->options['update_infos'] = true;
        }

        if (isset($this->options['update_infos']) && (int) $this->options['update_infos']) {
            $data['steps']['download_file_infos'] = array(
                'name'     => 'download_file_infos',
                'label'    => 'Téléchargement du fichier des informations produits',
                'on_error' => 'stop'
            );
        }

        if (isset($this->options['update_prices']) && (int) $this->options['update_prices']) {
            $data['steps']['download_file_prices'] = array(
                'name'     => 'download_file_prices',
                'label'    => 'Téléchargement du fichier des prix',
                'on_error' => 'stop'
            );
        }

        if (isset($this->options['update_stocks']) && (int) $this->options['update_stocks']) {
            $data['steps']['download_file_stocks'] = array(
                'name'     => 'download_file_stocks',
                'label'    => 'Téléchargement du fichier des stocks',
                'on_error' => 'stop'
            );
        }

//        if (isset($this->options['update_taxes']) && $this->options['update_taxes']) {
//            $data['steps']['download_file_taxes'] = array(
//                'name'     => 'download_file_taxes',
//                'label'    => 'Téléchargement du fichier des taxes',
//                'on_error' => 'stop'
//            );
//        }

        if (count($data['steps'])) {
            $data['steps']['search_references'] = array(
                'name'     => 'search_references',
                'label'    => 'Recherche des références à mettre à jour',
                'on_error' => 'stop'
            );
        }
    }

    protected function executeFtpUpdates($step, &$errors)
    {
        $return = array();

        switch ($step) {
            case 'download_file_infos':
                $this->FtpDownloadFile($this->parameters['ftp_file_infos'], $errors);
                break;

            case 'download_file_prices':
                $this->FtpDownloadFile($this->parameters['ftp_file_prices'], $errors);
                break;

            case 'download_file_stocks':
                $this->FtpDownloadFile($this->parameters['ftp_file_stocks'], $errors);
                break;

//            case 'download_file_taxes':
//                $this->FtpDownloadFile($this->parameters['ftp_file_taxes'], $errors);
//                break;

            case 'search_references':
                $references = $this->findReferences();
                if (!count($references)) {
                    $this->Alert('Aucune référence à mettre à jour trouvée');
                } else {
                    $return['new_steps'] = array(
                        'update_products_process' => array(
                            'name'                   => 'update_products_process',
                            'label'                  => 'Mise à jour des produits',
                            'elements'               => $references,
                            'nbElementsPerIteration' => 1,
                            'on_error'               => 'continue'
                        )
                    );
                }
                break;

            case 'update_products_process':
                $this->updateProducts();
                break;
        }

        return $return;
    }

    protected function executeObjectImport($object_name, $id_object)
    {
        if ($object_name !== 'Product') {
            $this->Error('Opération disponible uniquement pour les produits');
            return;
        }

        $import_reference = BDS_ImportData::getObjectImportReferenceById($this->db, $this->processDefinition->id, $object_name, $id_object);
        if (!is_null($import_reference) && $import_reference) {
            $this->current_object['ref'] = $import_reference;
            $this->updateProduct($import_reference);
        } else {
            $this->Error('Référence d\'import non enregistrée', $this->curId(), $this->curName(), $this->curRef());
        }
    }

    protected function updateProducts()
    {
        if (is_null($this->references) || !$this->references) {
            $this->Alert('Références invalides');
            return;
        }

        if (is_string($this->references)) {
            $this->references = array($this->references);
        }

        if (!is_array($this->references) || !count($this->references)) {
            $this->Alert('Aucune référence indiquée');
            return;
        }

        foreach ($this->references as $reference) {
            $this->updateProduct($reference);
        }
    }

    protected function updateProduct($import_reference)
    {
        if (!class_exists('Product')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
        }

        global $conf;

        $this->incProcessed();

        $id_product = BDS_ImportData::getObjectIdByImportReference($this->db, $this->processDefinition->id, 'Product', $import_reference);
        $product = new Product($this->db->db);
        $import_data = new BDS_ImportData();
        
        if (is_null($id_product)) {
            $id_product = $this->findProductByFournisseurReference($import_reference);
        }
        if (!is_null($id_product)) {
            $product->fetch($id_product);
            $import_data->fetchByObjectId($this->processDefinition->id, 'Product', $id_product);
        } else {
            $import_data->id_process = $this->processDefinition->id;
            $import_data->object_name = 'Product';
            $import_data->import_reference = $import_reference;

            $product->price_base_type = 'HT';
            $product->type = 0;
            $product->status_buy = 1;
            $product->status = 1;
            $product->stock_reel = 0;
            $product->tva_tx = $this->parameters['tva_tx_default'];
        }
        
        $import_data->status = self::BDS_STATUS_IMPORTING;
        $import_data->update();

        $this->setCurrentObject('Product', $id_product, $import_reference);

        $data = $this->getProductUpdateData($import_reference);
        if (!count($data)) {
            $msg = 'Aucune donnée à mettre à jour trouvée';
            $this->alert($msg, $this->curName(), $this->curId(), $this->curRef());
            $import_data->status = self::BDS_STATUS_IMPORT_FAIL;
        } else {
            if (self::$debug_mod) {
                $this->debug_content .= '<h4>Mise à jour de la référence "' . $import_reference . '"</h4>';
                $this->debug_content .= 'Données: <pre>';
                $this->debug_content .= print_r($data, 1);
                $this->debug_content .= '</pre>';
            }

            if (isset($data['label']) && $data['label']) {
                if (DOL_VERSION < '3.8.0')
                    $product->libelle = $data['label'];
                else
                    $product->label = $data['label'];
            }
            if (isset($data['ean']) && $data['ean']) {
                $product->barcode = $data['ean'];
                $product->barcode_type = 2; // EAN
            }

            if (!isset($product->ref) || !$product->ref) {
                $product->ref = 'TD_' . $import_reference;
//            $this->createProductReference($product, 'TD_'.$import_reference);
            }

            $errors = array();

            if ($this->saveObject($product, 'du produit', true, $errors, true)) {
                // Mise à jour du stock: 
                if (isset($data['stock']) && ($data['stock'] !== '')) {
                    $this->updateProductStock($product, $data['stock']);
                }

                // Mise à jour du pric d'achat:
                if (isset($data['pa_ht']) && $data['pa_ht']) {
                    $this->updateProductBuyPrice($product->id, $data['pa_ht'], $import_reference);
                }

                // Mise à jour du prix de vente (désactivé pour le moment):
                if (isset($data['pv_ht']) && $data['pv_ht']) {
                    $this->updateProductPrice($product, $data['pv_ht']);
                }

                if (is_null($id_product)) {
                    $this->current_object['id'] = $product->id;
                    $import_data->id_object = $product->id;

                    // Ajout à la catégorie (nouveau produit seulement):           
                    $this->addProductToCategory($product->id);
                }

                $import_data->status = self::BDS_STATUS_IMPORTED;
                $product->call_trigger('PRODUCT_MODIFY', $this->user);
            } else {
                $import_data->status = self::BDS_STATUS_IMPORT_FAIL;
            }
        }
        $import_data->update();
    }

    protected function getProductUpdateData($import_reference)
    {
        $data = array();
        if ($this->parameters_ok && $this->options_ok) {
            if (isset($this->options['update_infos']) && $this->options['update_infos']) {
                $row = $this->getRowFromFile('ftp_file_infos', $import_reference, 0);

                if (is_null($row)) {
                    $msg = 'Echec de la récupération des données depuis le fichier "' . $this->parameters['ftp_file_infos'] . '"';
                    $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                } else {
                    $data['label'] = trim($row[self::$product_infos['designation_2']]);
                    $data['ean'] = trim($row[self::$product_infos['ean']]);
                }
            }

            if (isset($this->options['update_prices']) && $this->options['update_prices']) {
                $row = $this->getRowFromFile('ftp_file_prices', $import_reference, 1);

                if (is_null($row)) {
                    $msg = 'Echec de la récupération des données depuis le fichier "' . $this->parameters['ftp_file_prices'] . '"';
                    $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                } else {
                    $data['pv_ht'] = (float) trim($row[self::$product_prices['pv_ht']]);
                    $data['pa_ht'] = (float) trim($row[self::$product_prices['pa_ht']]) - (float) trim($row[self::$product_prices['pa_reduction']]);
                }
            }

//            if (isset($this->options['update_taxes']) && $this->options['update_taxes']) {
//                $row = $this->getRowFromFile('ftp_file_taxes', $import_reference, 0);
//
//                if (is_null($row)) {
//                    $msg = 'Echec de la récupération des données depuis le fichier "' . $this->parameters['ftp_file_taxes'] . '"';
//                    $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
//                } else {
//                    $data['fee_name'] = trim($row[self::$product_taxes['fee_name']]);
//                    $data['fee'] = trim($row[self::$product_taxes['fee']]);
//                }
//            }

            if (isset($this->options['update_stocks']) && $this->options['update_stocks']) {
                $row = $this->getRowFromFile('ftp_file_stocks', $import_reference, 0);

                if (is_null($row)) {
                    $msg = 'Echec de la récupération des données depuis le fichier "' . $this->parameters['ftp_file_stocks'] . '"';
                    $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                } else {
                    $data['stock'] = trim($row[self::$product_stocks['stock']]);
                    $data['date_livraison'] = trim($row[self::$product_stocks['date_livraison']]);
                }
            }
        }
        return $data;
    }

    protected function getRowFromFile($file_key, $import_reference, $index_reference)
    {
        $row = null;
        if ($this->parameters_ok) {
            $file_path = $this->filesDir . 'ftp_files/' . $this->parameters[$file_key];
            if (file_exists($file_path)) {
                if (is_readable($file_path)) {
                    $file = fopen($file_path, 'r');
                    while ($row = utf8_encode(fgets($file))) {
                        $row = explode("\t", $row);

                        $reference = trim($row[$index_reference]);
                        if (is_null($reference) || !$reference) {
                            continue;
                        }

                        if ($reference == $import_reference) {
                            break;
                        }
                    }
                    if (!$row) {
                        $msg = '"' . $import_reference . '": référence non trouvée dans le fichier "' . $this->parameters[$file_key] . '"';
                        $this->Alert($msg, 'Product', null, $import_reference);
                        $row = null;
                    }
                    fclose($file);
                } else {
                    $this->Error('Impossible de mettre à jour la référence "' . $import_reference . '" depuis le fichier "' . $this->parameters[$file_key] . '" - Lecture non permise');
                }
            } else {
                $this->Error('Fichier "' . $this->parameters[$file_key] . '" absent');
            }
        }

        return $row;
    }

    protected function FtpDownloadFile($file_name, &$errors = null)
    {
        //TO REMOVE:
        return true;

        if (is_null($errors)) {
            $errors = array();
        }

        $this->ftp = $this->ftpConnect($this->parameters['ftp_server'], $this->parameters['ftp_user'], $this->parameters['ftp_pword'], true, $errors);

        if (!$this->ftp || count($errors)) {
            return false;
        }

        $dir = $this->filesDir . 'ftp_files/';

        if (!ftp_get($this->ftp, $dir . $file_name, $file_name, FTP_BINARY)) {
            $msg = 'Echec du téléchargement du fichier "' . $file_name . '"';
            $this->Error($msg);
            $errors[] = $msg;
            return false;
        }

        return true;
    }

    protected function findReferences()
    {
        // Récupération des références déjà enregistrées: 
        $references = BDS_ImportData::getObjectsImportReferences($this->db, $this->processDefinition->id, 'Product');

        // Ajout des références indiquées dans les options au lancement du processus. 
        if (isset($this->options['new_references']) && $this->options['new_references']) {
            $new_references = explode(',', $this->options['new_references']);
            foreach ($new_references as $new_ref) {
                if (!in_array($new_ref, $references)) {
                    $references[] = $new_ref;
                }
            }
        }

        // Recherche des nouvelles références: 
        if (isset($this->options['find_new_references']) && $this->options['find_new_references'] &&
                isset($this->parameters['id_search_root_category']) && $this->parameters['id_search_root_category']) {
            $this->debug_content .= '<h4>Recherche de nouvelles références</h4>';
            $products_ids = BDS_ImportData::getObjectsIds($this->db, $this->processDefinition->id, 'Product');
            $categories = explode(',', $this->parameters['id_search_root_category']);

            $whereFourn = '`fk_soc` = ' . (int) $this->parameters['id_soc_fournisseur'];
            $whereFourn .= ' AND `fk_product` = ';

            foreach ($categories as $cat) {
                if (!preg_match('/^\d+$/', $cat)) {
                    $msg = 'ID de catégorie invalide pour le paramètre "';
                    $msg .= BDSProcessParameter::getParameterLabel($this->db, $this->processDefinition->id, 'id_search_root_category');
                    $msg .= '": ' . $cat . ' (doit être un nombre entier positif)';
                    $this->Alert($msg);
                } else {
                    $products = $this->findProductsInCategory((int) $cat);
                    foreach ($products as $id_product) {
                        if (in_array($id_product, $products_ids)) {
                            continue;
                        }

                        $import_data = new BDS_ImportData();
                        $import_data->fetchByObjectId($this->processDefinition->id, 'Product', $id_product);

                        // Recherche via le code fournisseur:
                        $reference = $this->db->getValue('product_fournisseur_price', 'ref_fourn', $whereFourn . (int) $id_product);
                        if (!is_null($reference) && $reference) {
                            if (!in_array($reference, $references)) {
                                $this->Msg('Nouvelle référence détectée (via code fournisseur): ' . $reference, 'info');
                                $references[] = $reference;
                                $import_data->import_reference = $reference;
                                $import_data->update();
                            }
                            continue;
                        }

                        // Recherche via le code fabricant:
                        $prod_ref = $this->db->getValue('product', 'ref', '`rowid` = ' . (int) $id_product);
                        if (preg_match('/^([A-Z]{3}\-)(.*)$/U', $prod_ref, $matches)) {
//                            $manufacturer = $this->db->getValue('manufacturer', 'name', '`ref_prefixe` = \'' . $matches[1] . '\'');
//                            if (is_null($manufacturer) || !$manufacturer) {
//                                $this->alert('Fabricant non trouvé pour le préfixe "' . $matches[1] . '"');
//                            } else {
//                                $reference = $this->findImportReferenceByManufacturerCode($manufacturer, $matches[2]);
                            $reference = $this->findImportReferenceByManufacturerCode($matches[2]);
                            if (!is_null($reference)) {
                                // Enregistrement de la référence frounisseur TechData pour ce produit:
                                $this->updateProductBuyPrice($id_product, 0.0, $reference);
                                if (!in_array($reference, $references)) {
                                    $this->Msg('Nouvelle référence détectée (via code fabricant): ' . $reference, 'info');
                                    $references[] = $reference;
                                    $import_data->import_reference = $reference;
                                    $import_data->update();
                                }
                            }
//                            }
                        }
                    }
                }
            }
        } else {
            $this->Alert('Recherche de nouvelles références désactivée');
        }


        return $references;
    }

    protected function findImportReferenceByManufacturerCode($code)//($manufacturer, $code)
    {
        $files_keys = array(
            'ftp_file_infos',
            'ftp_file_prices',
            'ftp_file_stocks'
        );

        $search_pattern = 0;
        // S'il y a un underscore dans le code fabricant, on met en place la possiblité d'effectuer une recherche avec 
        // n'impote quel cararctère pouvant remplacer l'underscore. 
        if (preg_match('/^(.+)_(.+)$/', $code, $matches)) {
            $search_pattern = '/^' . preg_quote($matches[1]) . '.' . preg_quote($matches[2]) . '$/';
        }

        foreach ($files_keys as $file_key) {
            $indexes = $this->getFileIndexesByFileKey($file_key);
            if (is_null($indexes)) {
                $this->Error('Erreur technique: type de fichier invalide: "' . $file_key . '"');
                return null;
            } else {
                if (!isset($indexes['article_manufacturer_ref'])) {
                    $this->Error('Erreur technique: index du code article fabricant absent pour le fichier "' . $this->parameters[$file_key] . '"');
                    return null;
                }
//                if (!isset($indexes['manufacturer_name'])) {
//                    $this->Error('Erreur technique: index du nom fabricant absent pour le fichier "' . $this->parameters[$file_key] . '"');
//                    return null;
//                }
            }

            $file_path = $this->filesDir . 'ftp_files/' . $this->parameters[$file_key];
            if (file_exists($file_path)) {
                if (is_readable($file_path)) {
                    $file = fopen($file_path, 'r');
                    while ($row = utf8_encode(fgets($file))) {
                        $row = explode("\t", $row);
//                        $row_manufacturer = trim($row[$indexes['manufacturer_name']]);
//                        if (!isset($row_manufacturer) || !$manufacturer) {
//                            continue;
//                        }
//                        if ($row_manufacturer !== $manufacturer) {
//                            continue;
//                        }
                        $row_article_ref = trim($row[$indexes['article_manufacturer_ref']]);
                        if (!isset($row_article_ref) || !$row_article_ref) {
                            continue;
                        }
                        if ($row_article_ref === $code) {
                            if ($row[$indexes['code_produit']]) {
                                return $row[$indexes['code_produit']];
                            }
                            break;
                        } elseif ($search_pattern) {
                            if (preg_match($search_pattern, $row_article_ref)) {
                                if ($row[$indexes['code_produit']]) {
                                    return $row[$indexes['code_produit']];
                                }
                                break;
                            }
                        }
                    }
                    $msg = 'Référence non trouvée dans le fichier "' . $this->parameters[$file_key] . '" ';
                    $msg .= 'pour le code "' . $code . '" du fabricant'; // "' . $manufacturer . '"';
                    $this->Alert($msg);
                    fclose($file);
                } else {
                    $msg = 'Impossible de rechercher la référence TechData pour le code "' . $code . '" du fabricant'; // "' . $manufacturer . '"';
                    $msg .= ' - lecture du fichier "' . $this->parameters[$file_key] . '" non permise';
                    $this->Alert($msg);
                }
            } else {
                $msg = 'Impossible de rechercher la référence TechData pour le code "' . $code . '" du fabricant'; // "' . $manufacturer . '"';
                $msg .= ' - Fichier "' . $this->parameters[$file_key] . '" absent';
                $this->Alert($msg);
            }
        }
        return null;
    }

    protected function findProductByFournisseurReference($reference)
    {
        if (!$this->checkParameter('id_soc_fournisseur', 'int')) {
            return null;
        }
        $where = '`fk_soc` = ' . (int) $this->parameters['id_soc_fournisseur'];
        $where .= ' AND `ref_fourn` = \'' . $reference . '\'';

        $id_product = $this->db->getValue('product_fournisseur_price', 'fk_product', $where);
        if (!is_null($id_product) && !$id_product) {
            return null;
        }
        return $id_product;
    }

    protected function getFileIndexesByFileKey($file_key)
    {
        switch ($file_key) {
            case 'ftp_file_infos':
                return self::$product_infos;

            case 'ftp_file_prices':
                return self::$product_prices;

            case 'ftp_file_stocks':
                return self::$product_stocks;

            case 'ftp_file_taxes':
                return self::$product_taxes;
        }
        return null;
    }
}
