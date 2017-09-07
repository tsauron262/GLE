<?php

class BDS_TechData_ImportProcess extends BDS_ImportProcess
{

    public static $files_dir_name = 'TechData';
    public $ftp = null;
    public static $product_infos = array(
        'code_produit'  => 0,
        'designation_2' => 2,
        'code_article'  => 3,
        'ean'           => 6,
        'famille'       => 8,
        'classe'        => 10,
        'sous-classe'   => 12
    );
    public static $product_prices = array(
        'code_produit'  => 1,
        'designation_1' => 2,
        'marque'        => 4,
        'pv_ht'         => 5,
        'pa_ht'         => 7,
        'pa_reduction'  => 8
    );
    public static $product_taxes = array(
        'code_produit' => 0,
        'fee_name'     => 2,
        'fee'          => 3
    );
    public static $product_stocks = array(
        'code_produit'   => 0,
        'stock'          => 3,
        'date_livraison' => 4
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

        if (isset($this->options['update_infos']) && $this->options['update_infos']) {
            $data['steps']['download_file_infos'] = array(
                'name'     => 'download_file_infos',
                'label'    => 'Téléchargement du fichier des informations produits',
                'on_error' => 'stop'
            );
        }

        if (isset($this->options['update_prices']) && $this->options['update_prices']) {
            $data['steps']['download_file_prices'] = array(
                'name'     => 'download_file_prices',
                'label'    => 'Téléchargement du fichier des prix',
                'on_error' => 'stop'
            );
        }

        if (isset($this->options['update_stocks']) && $this->options['update_stocks']) {
            $data['steps']['download_file_stocks'] = array(
                'name'     => 'download_file_stocks',
                'label'    => 'Téléchargement du fichier des stocks',
                'on_error' => 'stop'
            );
        }

        if (isset($this->options['update_taxes']) && $this->options['update_taxes']) {
            $data['steps']['download_file_taxes'] = array(
                'name'     => 'download_file_taxes',
                'label'    => 'Téléchargement du fichier des taxes',
                'on_error' => 'stop'
            );
        }

        $references = BDS_ImportData::getObjectsImportReferences($this->db, $this->processDefinition->id, 'Product');
        if (isset($this->options['new_references']) && $this->options['new_references']) {
            $references = array_merge($references, explode(',', $this->options['new_references']));
        }
        $data['steps']['update_products_process'] = array(
            'name'                   => 'update_products_process',
            'label'                  => 'Mise à jour des produits',
            'elements'               => $references,
            'nbElementsPerIteration' => 1,
            'on_error'               => 'continue'
        );
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

            case 'download_file_taxes':
                $this->FtpDownloadFile($this->parameters['ftp_file_taxes'], $errors);
                break;

            case 'update_products_process':
                $this->updateProducts();
                break;
        }

        return $return;
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
            $product->tva_tx = 20;
        }

        $this->setCurrentObject('Product', $id_product, $import_reference);

        $data = $this->getProductUpdateData($import_reference);
        if (!count($data)) {
            $msg = 'Aucune de donnée à mettre à jour trouvée';
            $this->alert($msg, $this->curName(), $this->curId(), $this->curRef());
            return;
        }

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
            $code_module = (!empty($conf->global->PRODUCT_CODEPRODUCT_ADDON) ? $conf->global->PRODUCT_CODEPRODUCT_ADDON : 'mod_codeproduct_leopard');
            if ($code_module != 'mod_codeproduct_leopard') {
                if (substr($code_module, 0, 16) == 'mod_codeproduct_' && substr($code_module, -3) == 'php') {
                    $code_module = substr($code_module, 0, dol_strlen($code_module) - 4);
                }
                dol_include_once('/core/modules/product/' . $code_module . '.php');
                $modCodeProduct = new $code_module;
                if (!empty($modCodeProduct->code_auto)) {
                    $product->ref = $modCodeProduct->getNextValue($product, $product->type);
                }
                unset($modCodeProduct);
            }
            if (empty($product->ref)) {
                $product->ref = 'TD_' . $import_reference;
            }
        }

        // Mise à jour du prix et de la tva:
        if (isset($data['pv_ht']) && $data['pv_ht']) {
            $price = (float) $data['pv_ht'];
            if (!is_null($id_product) && !is_null($product->id) && $product->id) {
                // On met à jour le prix maintenant pour qu'il soit pris en compte au moment du trigger
                if (!$price) {
                    $price = $product->price;
                }
                if (!$tax) {
                    
                }
                if (!$product->updatePrice($price, 'HT', $this->user, $tax)) {
                    $this->Error('Echec de la mise à jour du prix', $this->curName(), $this->curId(), $this->curRef());
                }
            } else {
                $product->price = $price;
                $product->tva_tx = $tax;
            }
        } else {
            $price = 0;
        }

        if ($this->saveObject($product, 'du produit')) {
            $call_trigger = false;
            if (isset($data['stock'])) {
                // Mise à jour du stock: 
                $nPces = (int) $data['stock'] - (isset($product->stock_reel) ? (int) $product->stock_reel : 0);
                if ($nPces !== 0) {
                    if ($nPces < 0) {
                        $mvt = 1;
                        $nPces *= -1;
                    } else {
                        $mvt = 0;
                    }
                    $product->error = '';
                    if ($product->correct_stock($this->user, $this->parameters['id_wharehouse'], (int) $nPces, $mvt, 'Mise à jour automatique')) {
                        $call_trigger = true;
                    } else {
                        $msg = 'Echec de la mise à jour des stocks';
                        if ($product->error) {
                            $msg .= ' Erreur: ' . $product->error;
                        }
                        $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                    }
                }
            }

            if (is_null($id_product)) {
                // Ajout à la catégorie (nouveau produit seulement): 
                $this->current_object['id'] = $product->id;
                if (isset($this->options['new_references_category']) && $this->options['new_references_category']) {
                    $id_categorie = (int) $this->options['new_references_category'];
                } else {
                    $id_categorie = $this->parameters['id_categorie_default'];
                }
                if (!$this->db->insert('categorie_product', array(
                            'fk_categorie' => (int) $id_categorie,
                            'fk_product'   => (int) $product->id
                        ))) {
                    $msg = 'Echec de l\'association du produit avec la catégorie d\'ID "' . $id_categorie . '"';
                    $this->SqlError($msg, $this->curName(), $this->curId(), $this->curRef());
                } else {
                    $call_trigger = true;
                }
                $import_data->id_object = $product->id;
                $import_data->create();
            } else {
                $import_data->update();
            }

            if ($call_trigger) {
                $product->call_trigger('PRODUCT_MODIFY', $this->user);
            }
        }
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

            if (isset($this->options['update_taxes']) && $this->options['update_taxes']) {
                $row = $this->getRowFromFile('ftp_file_taxes', $import_reference, 0);

                if (is_null($row)) {
                    $msg = 'Echec de la récupération des données depuis le fichier "' . $this->parameters['ftp_file_taxes'] . '"';
                    $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                } else {
                    $data['fee_name'] = trim($row[self::$product_taxes['fee_name']]);
                    $data['fee'] = trim($row[self::$product_taxes['fee']]);
                }
            }

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
}
