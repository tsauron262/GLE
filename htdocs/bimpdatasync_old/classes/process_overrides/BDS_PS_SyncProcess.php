<?php

class BDS_PS_SyncProcess extends BDS_SyncProcess
{

    public static $files_dir_name = 'psBimpEducation';
    public static $ext_process_name = 'BIMP_ERP_Sync';

    public function __construct($processDefinition, $user, $params = null)
    {
        parent::__construct($processDefinition, $user, $params);

        if ($this->parameters_ok) {
            $this->authentication = array(
                'prestashopkey'     => htmlentities($this->parameters['ws_key'], ENT_COMPAT, 'UTF-8'),
                'sourceapplication' => 'BimpDataSync',
                'login'             => $this->parameters['ws_login'],
                'password'          => $this->parameters['ws_pass']
            );

            if (file_exists($this->filesDir)) {
                $dir_tree = array(
                    'product' => 'images'
                );
                $result = BDS_Tools::makeDirectories($dir_tree, $this->filesDir);
                if ($result) {
                    $this->logError($result);
                    $this->Msg($result);
                    $this->parameters_ok = false;
                }
            }
        }
    }
    
    public static function getClassName()
    {
        return 'BDS_PS_SyncProcess';
    }

    public function test()
    {
//        $errors = array();
        self::$debug_mod = true;
        self::$ext_debug_mod = true;
//
//        BDS_SyncData::resetAllStatus($this->db, $this->processDefinition->id, 'Product');
//        $objects = $this->getObjectsExportData('Product', 'Product', array(6849), $errors);
//        if (!count($errors) && count($objects['list'])) {
//            $this->soapExportObjects(array($objects), 'BIMP_ERP_Sync');
//        } else {
//            $this->debug_content .= 'Erreurs: <pre>';
//            $this->debug_content .= print_r($errors, 1);
//            $this->debug_content .= '</pre>';
//        }

//        echo $this->debug_content;
//        $objects = $this->getObjectsDeleteData('Categorie', 'Categorie', array(809));
//        if (count($objects)) {
//            $this->soapDeleteObjects(array($objects), 'BIMP_ERP_Sync');
//        }
        
        // Tests imports: 
        BDS_SyncData::resetAllStatus($this->db, $this->processDefinition->id, 'Product');
        $objects = $this->getObjectsImportData('Product', 'Product', array(6851), $errors);
        if (!count($errors) && count($objects['list'])) {
            $this->soapImportObjects(array($objects), 'BIMP_ERP_Sync');
        } else {
            $this->debug_content .= 'Erreurs: <pre>';
            $this->debug_content .= print_r($errors, 1);
            $this->debug_content .= '</pre>';
        }
        
        echo '<pre>';
        print_r($this->debug_content);
        exit;
        echo $this->debug_content;
    }

//    Opérations:

    protected function initExportsToPs(&$data, &$errors)
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

        $categories = $this->findCategoriesToExport();

        if (isset($this->options['exportCategories']) && $this->options['exportCategories']) {
            $data['steps']['process_categories_export'] = array(
                'name'                   => 'process_categories_export',
                'label'                  => 'Export des catégories',
                'elements'               => $categories,
                'nbElementsPerIteration' => 1,
                'on_error'               => 'continue'
            );
        }

        if (isset($this->options['exportProducts']) && $this->options['exportProducts']) {
            $data['steps']['process_products_export'] = array(
                'name'                   => 'process_products_export',
                'label'                  => 'Export des produits',
                'elements'               => $this->findProductsToExport($categories),
                'nbElementsPerIteration' => 1,
                'on_error'               => 'continue'
            );
        }
    }

    protected function executeExportsToPs($step, &$errors)
    {
        $object_name = '';
        $ext_object_name = '';

        switch ($step) {
            case 'process_categories_export':
                $object_name = 'Categorie';
                $ext_object_name = 'Category';
                break;

            case 'process_products_export':
                $object_name = 'Product';
                $ext_object_name = 'Product';
                break;
        }

        if ($object_name && $ext_object_name &&
                isset($this->references) && count($this->references)) {
            $objects = $this->getObjectsExportData($object_name, $ext_object_name, $this->references, $errors);
            if (!count($errors) && count($objects['list'])) {
                $this->soapExportObjects(array($objects), 'BIMP_ERP_Sync');
            }
        }

        return array();
    }

    // Triggers:

    protected function triggerActionProductCreate($object)
    {
        $this->triggerActionProductModify($object);
    }

    protected function triggerActionProductModify($object, $check_categorie = true)
    {
        if (!isset($object->id) || !$object->id) {
            return;
        }

        if ($check_categorie && !$this->isProductInSyncCategories($object->id)) {
            return;
        }

        $status = BDS_SyncData::getObjectValue($this->db, 'status', $this->processDefinition->id, 'Product', $object->id, 'loc_id_object');
        if (!is_null($status)) {
            if ($status > 0) {
                return;
            }
            if ($status < 0) {
                BDS_SyncData::updateStatusBylocIdObject($this->db, $this->processDefinition->id, 'Product', $object->id, 0);
            }
        }

        $errors = array();
        $products = $this->getObjectsExportData('Product', 'Product', array((int) $object->id), $errors);

        if (!count($errors)) {
            if (isset($products['list']) && count($products['list'])) {
                $this->soapExportObjects(array($products), 'BIMP_ERP_Sync');
            } else {
                $this->Alert('Aucun produit à exporter');
            }
        }
    }

    protected function triggerActionProductDelete($object, $check_categorie = true)
    {
        if (!isset($object->id) || !$object->id) {
            return;
        }

        if ($check_categorie && !$this->isProductInSyncCategories($object->id)) {
            return;
        }

        $status = BDS_SyncData::getObjectValue($this->db, 'status', $this->processDefinition->id, 'Product', $object->id, 'loc_id_object');
        if (!is_null($status)) {
            if ($status < 0) {
                BDS_SyncData::updateStatusBylocIdObject($this->db, $this->processDefinition->id, 'Product', $object->id, 0);
            }
        }

        $products = $this->getObjectsDeleteData('Product', 'Product', array($object->id));
        if (count($products)) {
            $this->soapDeleteObjects(array($products), 'BIMP_ERP_Sync');
        }
    }

    protected function triggerActionCategoryCreate($object)
    {
        $this->triggerActionCategoryModify($object);
    }

    protected function triggerActionCategoryModify($object)
    {
        if (!isset($object->id) || !$object->id) {
            return;
        }

        if (!$this->isCategorieInSyncCategories($object->id)) {
            return;
        }

        $status = BDS_SyncData::getObjectValue($this->db, 'status', $this->processDefinition->id, 'Categorie', $object->id, 'loc_id_object');
        if (!is_null($status)) {
            if ($status > 0) {
                return;
            }
            if ($status < 0) {
                BDS_SyncData::updateStatusBylocIdObject($this->db, $this->processDefinition->id, 'Categorie', $object->id, 0);
            }
        }

        $errors = array();

        // Hack: 
        $this->db->db->commit();

        $categories = $this->getObjectsExportData('Categorie', 'Category', array((int) $object->id), $errors);
        if (!count($errors)) {
            if (isset($categories['list']) && count($categories['list'])) {
                $this->soapExportObjects(array($categories), 'BIMP_ERP_Sync');
            } else {
                $this->Alert('Aucune catégorie à exporter');
            }
        }
    }

    protected function triggerActionCategoryDelete($object)
    {
        self::$debug_mod = true;
        if (!isset($object->id) || !$object->id) {
            return;
        }

        $status = BDS_SyncData::getObjectValue($this->db, 'status', $this->processDefinition->id, 'Categorie', $object->id, 'loc_id_object');
        if (!is_null($status)) {
            if ($status < 0) {
                BDS_SyncData::updateStatusBylocIdObject($this->db, $this->processDefinition->id, 'Categorie', $object->id, 0);
            }
        }

        $products = $this->getObjectsDeleteData('Categorie', 'Category', array($object->id));
        if (count($products)) {
            $this->soapDeleteObjects(array($products), 'BIMP_ERP_Sync');
        }
    }

    protected function triggerActionCategoryLink($object)
    {
        if (($object->element === 'category') &&
                isset($object->id) && $object->id) {
            if (isset($object->linkto)) {
                if (is_a($object->linkto, 'Product')) {
                    if ($this->isCategorieInSyncCategories((int) $object->id)) {
                        $this->triggerActionProductModify($object->linkto);
                    }
                }
            }
        }
    }

    protected function triggerActionCategoryUnlink($object)
    {
        if (($object->element === 'category') &&
                isset($object->id) && $object->id) {
            if (isset($object->unlinkoff)) {
                if (is_a($object->unlinkoff, 'Product')) {
                    if ($this->isCategorieInSyncCategories((int) $object->id)) {
                        $this->triggerActionProductModify($object->unlinkoff, false);
                    }
                }
            }
        }
    }

    protected function triggerActionStockMovement($object)
    {
        if (isset($object->product_id)) {
            if (!class_exists('Product')) {
                require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
            }
            $product = new Product($this->db->db);
            $product->fetch($object->product_id);
            if (isset($product->id) && $product->id) {
                $this->triggerActionProductModify($product);
            }
        }
    }

    // Données d'export des objets: 

    public function getCategorieExportData($id_categorie)
    {
        if (!class_exists('Categorie')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
        }

        if (!BDS_Tools::isCategorieChildOf($this->db, $id_categorie, $this->parameters['id_root_categorie'], false)) {
            $msg = 'Cette catégorie n\'est pas éligible à l\'export vers Prestashop ';
            $msg .= '(elles n\'appartient pas à la catégorie racine Prestashop.';
            $this->alert($msg, $id_categorie);
            return null;
        }

        $categorie = new Categorie($this->db->db);
        $result = $categorie->fetch($id_categorie);

        if ($result <= 0) {
            $msg = 'Echec du chargement des données de la catégorie. Export abandonné' . $this->ObjectError($categorie);
            $this->Error($msg, 'Categorie', $id_categorie);
            return;
        }

        $data = array(
            'name'        => $categorie->label,
            'description' => $categorie->description,
            'id_parent'   => $categorie->fk_parent,
            'active'      => 1,
            'type'        => (int) $categorie->type
        );

        return $data;
    }

    public function getProductExportData($id_product)
    {
        if (!class_exists('Product')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
        }

        $product = new Product($this->db->db);
        $result = $product->fetch($id_product);

        if ($result <= 0) {
            $msg = 'Echec du chargement des données du produit. Export abandonné' . $this->ObjectError($product);
            $this->Error($msg, 'Product', $id_product);
            return;
        }

        $img_dir = BDS_Tools::getProductImagesDir($product);

        $categories = $this->db->getValues('categorie_product', 'fk_categorie', '`fk_product` = ' . (int) $id_product);
        if (is_null($categories)) {
            $categories = array();
        }

        foreach ($categories as $idx => $id_categorie) {
            if (!BDS_Tools::isCategorieChildOf($this->db, $id_categorie, $this->parameters['id_root_categorie'])) {
                unset($categories[$idx]);
            }
        }

        $temp_imgs = array();
        $images_base_url = 'bimpdatasync/temp_files/images/product/' . $id_product . '/';
        $temp_images_path = DOL_DOCUMENT_ROOT . $images_base_url;

        $data = array(
            'id'              => $id_product,
            'name'            => (DOL_VERSION < '3.8.0' ? $product->libelle : $product->label),
            'reference'       => $product->ref,
            'description'     => $product->description,
            'price'           => $product->price,
            'tax_rate'        => $product->tva_tx,
            'status'          => $product->status,
            'weight'          => $product->weight,
            'length'          => $product->length,
            'stock'           => $product->stock_reel,
            'cost_price'      => (DOL_VERSION < '3.9.0' ? $product->pmp : $product->cost_price),
            'images_base_url' => $images_base_url,
            'categories'      => implode('-', $categories),
            'images'          => array()
        );



        if (is_null($img_dir)) {
            $msg = 'Impossible d\'exporter les images du produit (Répertoire non trouvé)';
            $this->Alert($msg, 'Product', $id_product);
        } else {
            if (file_exists($img_dir)) {
                $files = scandir($img_dir);
                $current_imgs = array();
                $sync_images = BDS_SyncData::getObjectObjects($this->db, 'image', $this->processDefinition->id, 'Product', $id_product);

                foreach ($sync_images as $loc_file => $ext_file) {
                    $current_imgs[] = $loc_file;
                }

                foreach ($files as $f) {
                    if (in_array($f, array('.', '..'))) {
                        continue;
                    }

                    $parts = pathinfo($f);

                    if (!in_array(strtolower($parts['extension']), array('jpg', 'jpeg', 'png', 'gif'))) {
                        continue;
                    }

                    if (is_dir($img_dir . $f)) {
                        continue;
                    }

                    if (!in_array($f, $current_imgs)) {
                        if (!file_exists($temp_images_path)) {
                            $dir_tree = array(
                                'temp_files' => array(
                                    'images' => array(
                                        'product' => '' . $id_product
                                    )
                                )
                            );
                            $error = BDS_Tools::makeDirectories($dir_tree, DOL_DOCUMENT_ROOT . '/bimpdatasync');
                            if ($error) {
                                $msg = $error . ' - Export des images impossible';
                                $this->Error($msg, 'Product', $id_product);
                                break;
                            }
                        }
                        if (!file_exists($temp_images_path . $f)) {
                            if (copy($img_dir . $f, $temp_images_path . $f)) {
                                $temp_imgs[] = $temp_images_path . $f;
                            }
                        }
                    }

                    $data['images'][] = $f;
                }
            }
        }

        switch ($product->barcode_type) {
            case 2: $data['ean13'] = $product->barcode;
                break;
            case 3: $data['upc'] = $product->barcode;
                break;
            case 4: $data['isnb'] = $product->barcode;
                break;
        }

        return $data;
    }

    // Mises à jour des objets:

    protected function updateCategorie($data, BDS_SyncData $sync_data)
    {
        $errors = array();
        if (!class_exists('Categorie')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
        }

        global $conf;

        $id_categorie = null;

        if (isset($sync_data->loc_id_object) && $sync_data->loc_id_object) {
            $id_categorie = $sync_data->loc_id_object;
        }

        $ext_id_object = 0;
        if (isset($sync_data->ext_id_object) && $sync_data->ext_id_object) {
            $ext_id_object = $sync_data->ext_id_object;
        }
        $this->setCurrentObject('Categorie', $id_categorie, $ext_id_object ? 'ID externe: ' . $ext_id_object : null);
        $this->incProcessed();

        $categorie = new Categorie($this->db->db);

        if (!is_null($id_categorie) && $id_categorie) {
            $categorie->fetch($id_categorie);
        }

        if (isset($data['name']) && $data['name']) {
            $categorie->label = $data['name'];
        }
        if (isset($data['type'])) {
            $categorie->type = (int) $data['type'];
        }
        $fk_parent = null;
        if (isset($data['id_parent'])) {
            if ($data['id_parent'] <= 1) {
                $fk_parent = $this->parameters['id_root_categorie'];
            } else {
                $fk_parent = BDS_SyncData::getObjectValue($this->db, 'loc_id_object', $this->processDefinition->id, 'Categorie', $data['id_parent'], 'ext_id_object');
            }
        }
        if (is_null($fk_parent)) {
            if (!isset($categorie->fk_parent) || !$categorie->fk_parent) {
                $categorie->fk_parent = $this->parameters['id_default_parent_categorie'];
            }
        } else {
            $categorie->fk_parent = $fk_parent;
        }

        // On vérifie que la catégorie n'existe pas déjà (même nom, même type, même parent)
        if ((is_null($id_categorie) || !$id_categorie) &&
                (isset($data['name']) && $data['name'])) {
            $where = '`type` = ' . (int) $categorie->type;
            $where .= ' AND `fk_parent` = ' . (int) $categorie->fk_parent;
            $where .= ' AND `label` = \'' . $this->db->db->escape($categorie->label) . '\'';
            $id_categorie = $this->db->getValue('categorie', 'rowid', $where);
            if (!is_null($id_categorie) && $id_categorie) {
                $categorie->fetch($id_categorie);
                $sync_data->loc_id_object = (int) $id_categorie;
                $this->current_object['id'] = $id_categorie;
            }
        }

        if (isset($data['description']) && $data['description']) {
            $categorie->description = $data['description'];
        }

        if (isset($data['active'])) {
            $categorie->visible = (int) $data['active'];
        }

        $this->saveObject($categorie, 'de la catégorie', $errors, true);

        return array(
            'object' => $categorie,
            'errors' => $errors
        );
    }

    protected function updateProduct($data, BDS_SyncData $sync_data)
    {
        $errors = array();
        if (!class_exists('Product')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
        }

        global $conf;

        $id_product = null;
        if (!is_null($sync_data->loc_id_object) && $sync_data->loc_id_object) {
            $id_product = $sync_data->loc_id_object;
        }

        // Recherche de l'existance du produit via sa référence: 
        if ((is_null($id_product) || !$id_product) &&
                (isset($data['reference']) && $data['reference'])) {
            $where = '`ref` = \'' . $data['reference'] . '\'';
            $id_product = $this->db->getValue('product', 'rowid', $where);
            if (!is_null($id_product) && $id_product) {
                $sync_data->loc_id_object = (int) $id_product;
            }
        }

        $ext_id_object = 0;
        if (isset($sync_data->ext_id_object) && $sync_data->ext_id_object) {
            $ext_id_object = $sync_data->ext_id_object;
        }
        $this->setCurrentObject('Product', $id_product, $ext_id_object ? 'ID externe: ' . $ext_id_object : null);
        $this->incProcessed();

        $product = new Product($this->db->db);

        if (!is_null($id_product) && $id_product) {
            $product->fetch($id_product);
        }

        $product->price_base_type = 'HT';
        $product->type = 0;
        $product->status_buy = 1;

        if (isset($data['price']) && ($data['price'] !== '')) {
            $product->price = $data['price'];
            if (isset($data['tax_rate']) && ($data['tax_rate'] !== '')) {
                $product->price_ttc = price2num($data['price'] * (1 + ($data['tax_rate'] / 100)), 'MU');
                $product->tva_tx = $data['tax_rate'];
            }
        }

        if (isset($data['name']) && $data['name']) {
            if (DOL_VERSION < '3.8.0')
                $product->libelle = $data['name'];
            else
                $product->label = $data['name'];
        }
        if (isset($data['description_short'])) {
            $product->description = $data['description_short'];
        }
//        if (isset($data['description']) && $data['description']) {
//            $product->array_options = array("options_longdescript" => trim($data['description']));
//        }
        if (isset($data['active'])) {
            $product->status = $data['active'];
        }
        if (isset($conf->global->MAIN_MODULE_BARCODE) && $conf->global->MAIN_MODULE_BARCODE) {//ean 2 upc 3 isbn 4
            if (isset($data['ean13']) && $data['ean13']) {
                $product->barcode = $data['ean13'];
                $product->barcode_type = 2;
            } elseif (isset($data['upc']) && $data['upc']) {
                $product->barcode = $data['upc'];
                $product->barcode_type = 3;
            } elseif (isset($data['isbn']) && $data['isbn']) {
                $product->barcode = $data['isbn'];
                $product->barcode_type = 4;
            }
        }
        if (isset($data['reference'])) {
            $product->ref = $data['reference'];
        } else {
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
            if (empty($product->ref) || !$product->ref) {
                if (isset($sync_data->ext_id_object) && $sync_data->ext_id_object) {
                    $product->ref = 'Presta' . $sync_data->ext_id_object;
                }
            }
        }

        if ($this->saveObject($product, 'du produit', $errors, true)) {
            if (!count($errors) && isset($product->id) && $product->id) {
                // Traitement des catégories:
                if (isset($data['categories'])) {
                    $categories = explode('-', $data['categories']);
                    if (!isset($categories[0]) || !$categories[0]) {
                        $categories[0] = (int) $this->parameters['id_default_products_categorie'];
                    }
                    $categories_errors = $this->processImportProductCategories($product->id, $categories, true);
                    if (count($categories_errors)) {
                        $errors = BimpTools::merge_array($errors, $categories_errors);
                    }
                }

                // Traitement des images: 
                if (isset($data['images'])) {
                    $images_errors = $this->processImportProductImages($product, $data['images'], $sync_data, $this->parameters['ps_url']);
                    if (count($images_errors)) {
                        $errors = BimpTools::merge_array($errors, $images_errors);
                    }
                }
            }
        }

        return array(
            'object' => $product,
            'errors' => $errors
        );
    }

    protected function updateSociete($data, BDS_SyncData $sync_data)
    {
        $errors = array();

        if (!class_exists('Societe')) {
            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        }

        $id_soc = null;

        if (isset($sync_data->loc_id_object) && $sync_data->loc_id_object) {
            $id_soc = $sync_data->loc_id_object;
        }

        $soc = new Societe($this->db->db);
        if (!is_null($id_soc)) {
            $soc->fetch($id_soc);
        }

        $ext_id_object = 0;
        if (isset($sync_data->ext_id_object) && $sync_data->ext_id_object) {
            $ext_id_object = $sync_data->ext_id_object;
        }
        $this->setCurrentObject('Societe', $id_soc, $ext_id_object ? 'ID externe: ' . $ext_id_object : null);
        $this->incProcessed();

        $soc->status = 1;
        $soc->fournisseur = 0;
        $soc->code_client = -1;
        $soc->commercial_id = -1;
        $soc->client = 1;

        if (isset($data['id_gender']) && $data['id_gender']) {
            //1 homme, 2 femme 9 inconnu (A priori les codes sont les mêmes) 
            $soc->civility_id = $data['id_gender'];
        } elseif (!isset($soc->civility_id) || !$soc->civility_id) {
            $soc->civility_id = 9;
        }

        if (isset($data['company']) && $data['company']) {
            $soc->name = $data['company'];
        } elseif (isset($data['lastname']) && $data['lastname']) {
            $soc->name = $data['lastname'];
            if (isset($data['firstname']) && $data['lastname']) {
                $soc->name .= ' ' . $data['firstname'];
            }
        }
        if (isset($data['email']) && $data['email']) {
            $soc->email = $data['email'];
        }
        if (isset($data['firstname']) && $data['firstname']) {
            $soc->firstname = $data['firstname'];
        }
        if (isset($data['lastname']) && $data['lastname']) {
            $soc->lastname = $data['lastname'];
        }
        if (isset($data['company']) && $data['company']) {
            $soc->company = $data['company'];
        }
        if (isset($data['addresses']['list'][0]['data'])) {
            $address = $data['addresses']['list'][0]['data'];
            if (isset($address['country_iso'])) {
                $country_table = 'c_country';
                if (DOL_VERSION < '3.7.0') {
                    $country_table = 'c_pays';
                }
                $country_id = $this->db->getValue($country_table, 'rowid', 'code LIKE "' . $address['country_iso'] . '"');
                if (!is_null($country_id) && $country_id) {
                    $soc->country_id = $country_id;
                }
                if (isset($address['vat_number']) && $address['vat_number']) {
                    $soc->tva_intra = $address['vat_number'];
                }
            }
        }

        $this->saveObject($soc, 'du client', $errors, true);

        if (isset($soc->id) && $soc->id) {
            if (isset($data['addresses']['list']) && count($data['addresses']['list'])) {
                $contacts_results = array();
                foreach ($data['addresses']['list'] as $address) {
                    if (isset($address['data'])) {
                        $a_ext_id = 0;
                        if (isset($address['ext_id_object'])) {
                            $a_ext_id = $address['ext_id_object'];
                        }
                        $a_ext_id_data_sync = 0;
                        if (isset($address['ext_id_sync_data'])) {
                            $a_ext_id_data_sync = $address['ext_id_sync_data'];
                        }
                        $address['data']['societe'] = array(
                            'socid'       => (int) $soc->id,
                            'email'       => $soc->email,
                            'civility_id' => $soc->civility_id,
                        );
                    } else {
                        $msg = 'Données absentes pour l\'addresse ';
                        $msg .= ' d\'ID externe: ' . $a_ext_id ? $a_ext_id : 'inconnu';
                        $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                    }

                    $errors = array();
                    $contacts_results[] = $this->updateObject('Contact', $address['data'], $a_ext_id_data_sync, $sync_data->ext_id_process, 'Address', $a_ext_id);
                }
            }
        }

        return array(
            'object'  => $soc,
            'errors'  => $errors,
            'objects' => array(
                'Address' => $contacts_results
            )
        );
    }

    protected function updateContact($data, BDS_SyncData $sync_data)
    {
        $errors = array();

        if (!class_exists('Contact')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        }

        $id_contact = null;
        if (isset($sync_data->loc_id_object) && $sync_data->loc_id_object) {
            $id_contact = $sync_data->loc_id_object;
        }

        $this->setCurrentObject('Contact', $id_contact);
        $this->incProcessed();

        $contact = new Contact($this->db->db);
        if (!is_null($id_contact)) {
            $contact->fetch($id_contact);
        }

        if (isset($data['societe'])) {
            $contact->socid = $data['societe']['socid']; // fk_soc
            $contact->email = $data['societe']['email'];
            $contact->civility_id = $data['societe']['civility_id'];
        }

        $contact->statut = 1;
        $contact->priv = 0;

        if (isset($data['company']) && $data['company']) {
            $contact->name = strtoupper($data['company']) . ' - ';
        } else {
            $contact->name = '';
        }
        $contact->name .= ucfirst(strtolower($data['firstname'])) . ' ' . ucfirst(strtolower($data['lastname']));
        $contact->lastname = $data['lastname'];
        $contact->firstname = $data['firstname'];
        $contact->address = $data['address1'] . ' - ' . $data['address2'];
        $contact->zip = $data['postcode'];
        $contact->town = $data['city'];
        $contact->phone_pro = $data['phone'];
        $contact->phone_mobile = $data['phone_mobile'];

        if (isset($data['country_iso'])) {
            $country_table = 'c_country';
            if (DOL_VERSION < '3.7.0') {
                $country_table = 'c_pays';
            }
            $country_id = $this->db->getValue($country_table, 'rowid', 'code LIKE "' . $data['country_iso'] . '"');
            if (!is_null($country_id)) {
                $contact->country_id = $country_id;
            }
        }
        $this->saveObject($contact, 'du contact', $errors);

        return array(
            'object' => $contact,
            'errors' => $errors
        );
    }

    protected function updateCommande($data, BDS_SyncData $sync_data)
    {
        $errors = array();
        if (!class_exists('Commande')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
            require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
        }

        $id_commande = null;

        if (isset($sync_data->loc_id_object) && $sync_data->loc_id_object) {
            $id_commande = $sync_data->loc_id_object;
        }

        $ext_id_object = 0;
        if (isset($sync_data->ext_id_object) && $sync_data->ext_id_object) {
            $ext_id_object = $sync_data->ext_id_object;
        }
        $this->setCurrentObject('Commande', $id_commande, $ext_id_object ? 'ID externe: ' . $ext_id_object : null);
        $this->incProcessed();

        $commande = new Commande($this->db->db);

        if (!is_null($id_commande) && $id_commande) {
            if ($commande->fetch($id_commande) <= 0) {
                $msg = 'Commande d\'ID ' . $id_commande . ' non trouvée';
                $errors[] = $msg;
                $this->Error($msg, $this->curName(), null, $this->curRef());
                return array(
                    'object' => null,
                    'errors' => $errors
                );
            } else {
                // La commande n'est mise à jour que si elle a le statut "Brouillon". 
                if ((int) $commande->statut !== Commande::STATUS_DRAFT) {
                    $msg = 'Cette commande ne peut pas être mise à jour';
                    $this->Info($msg, $this->curName(), $this->curId(), $this->curRef());
                    return array(
                        'object' => $commande,
                        'errors' => $errors
                    );
                }
            }
        }

        // Données de base:
        if (isset($data['date_create']) && $data['date_create']) {
            $commande->date_commande = $this->db->db->jdate($data['date_create']);
        }

        if (isset($data['reference'])) {
            $commande->ref_client = $data['reference'];
        }

        // Statut de la commande:
//        if (isset($data['id_order_state']) && $data['id_order_state']) {
//            $match = BDSProcessMatchingValues::createInstanceByName($this->processDefinition->id, 'order_states');
//            if (is_null($match)) {
//                $msg = 'Correspondance des états de commande non trouvée';
//                $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
//                $errors[] = $msg;
//            } else {
//                $match = new BDSProcessMatchingValues();
//                $statut = $match->getMatchedValue($data['id_order_state']);
//                if (!is_null($statut)) {
//                    $commande->statut = $statut;
//                } else {
//                    $msg = 'Statut de commande non trouvé pour l\'ID d\'état Prestashop: ' . $data['id_order_state'];
//                    $errors[] = $msg;
//                    $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
//                    $commande->statut = Commande::STATUS_DRAFT;
//                }
//            }
//        }
        if (!isset($commande->statut)) {
            $commande->statut = Commande::STATUS_DRAFT;
        }

        // Client:
        $id_soc = null;
        if (isset($data['id_customer']) && $data['id_customer']) {
            $id_soc = BDS_SyncData::getObjectValue($this->db, 'loc_id_object', $this->processDefinition->id, 'Societe', (int) $data['id_customer'], 'ext_id_object');
            if (is_null($id_soc) || !$id_soc) {
                $msg = 'Client non enregistré pour l\'ID Prestashop: ' . $data['id_customer'];
                $errors[] = $msg;
                $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
            }
        } elseif (isset($commande->socid)) {
            $id_soc = $commande->socid;
        }

        $soc = null;
        if (!is_null($id_soc) && $id_soc) {
            $soc = new Societe($this->db->db);
            $soc->fetch((int) $id_soc);

            if (!isset($soc->id) || !$soc->id) {
                $msg = 'Le client d\'ID ' . $id_soc . ' n\'est pas enregistré';
                $errors[] = $msg;
                $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                unset($soc);
                $soc = null;
            }
        }

        // Suppression des contacts actuels si le client a changé
        if (!is_null($soc)) {
            if (isset($commande->socid) && $commande->socid &&
                    ((int) $commande->socid !== (int) $soc->id)) {
                $current_contacts = $commande->liste_contact();
                foreach ($current_contacts as $key => $current_contact) {
                    if ($current_contact['socid'] !== $soc->id) {
                        $this->deleteCommandeContact($current_contact['socid'], $commande, $current_contact['code'], $errors);
                    }
                }
                unset($current_contacts);
            }
            $commande->socid = $soc->id;
        } else {
            $msg = 'Client non trouvé pour cette commande';
            $errors[] = $msg;
            $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
            return array(
                'object' => null,
                'errors' => $errors
            );
        }

        if (count($errors)) {
            $commande->statut = Commande::STATUS_DRAFT;
        }

        if ($this->saveObject($commande, 'de la commande', $errors, true)) {
            // Addresse de livraison:
            if (isset($data['id_address_delivery']) && $data['id_address_delivery']) {
                $id_contact = BDS_SyncData::getObjectValue($this->db, 'loc_id_object', $this->processDefinition->id, 'Contact', (int) $data['id_address_delivery'], 'ext_id_object');
                if (is_null($id_contact) || !$id_contact) {
                    $msg = 'Contact non trouvé pour l\'addresse de livraison d\'ID externe ' . $data['id_address_delivery'];
                    $errors[] = $msg;
                    $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                } else {
                    $this->processCommandeContact($commande, $id_contact, 'SHIPPING', $errors);
                }
            }

            // Addresse de facturation:
            if (isset($data['id_address_invoice']) && $data['id_address_invoice']) {
                $id_contact = BDS_SyncData::getObjectValue($this->db, 'loc_id_object', $this->processDefinition->id, 'Contact', (int) $data['id_address_invoice'], 'ext_id_object');
                if (is_null($id_contact) || !$id_contact) {
                    $msg = 'Contact non trouvé pour l\'addresse de facturation d\'ID externe ' . $data['id_address_invoice'];
                    $errors[] = $msg;
                    $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                } else {
                    $this->processCommandeContact($commande, $id_contact, 'BILLING', $errors);
                }
            }

            // Liste des produits: 
            $commande->fetch_lines(0);
            $current_lines = $commande->lines;
            $lines = $sync_data->getObjects('lines');
            $newLines = array();

            if (isset($data['products']) && count($data['products'])) {
                foreach ($data['products'] as $product_data) {
                    if ($product_data['type'] === 'product') {
                        $id_product = BDS_SyncData::getObjectValue($this->db, 'loc_id_object', $this->processDefinition->id, 'Product', $product_data['id_product'], 'ext_id_object');

                        if (is_null($id_product) || !$id_product) {
                            $msg = 'Produit non trouvé pour l\'ID externe ' . $product_data['id_product'];
                            $errors[] = $msg;
                            $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                            continue;
                        }

                        $id_line = null;
                        foreach ($current_lines as $key => $current_line) {
                            if ($current_line->fk_product === $id_product) {
                                $id_line = $current_line->id;
                                unset($current_lines[$key]);
                                if (isset($lines[$id_line])) {
                                    unset($lines[$id_line]);
                                }
                                break;
                            }
                        }

                        if ($this->processCommandeProduct($commande, $id_line, $id_product, $product_data, $errors)) {
                            $newLines[$id_line] = $id_product;
                        }
                    } elseif ($product_data['type'] === 'pack') {
                        $id_order_line = null;
                        foreach ($lines as $id_line => $ext_id_pack) {
                            if ((int) $ext_id_pack === (int) $product_data['id_product']) {
                                $id_order_line = $id_line;
                                unset($lines[$id_line]);
                                break;
                            }
                        }
                        if ($this->processCommandePack($commande, $id_order_line, $product_data, $errors)) {
                            $newLines[$id_order_line] = $product_data['id_product'];
                        }
                    }
                }
            }

            $sync_data->setObjects('lines', $newLines);

            // Suppression de tous les produis ne figurant plus dans la commande:
            foreach ($lines as $id_line => $ext_id) {
                $id_product = null;
                foreach ($current_lines as $key => $current_line) {
                    if ($current_line->id === $id_line) {
                        if (isset($current_line->fk_product) && $current_line->fk_product) {
                            $id_product = $current_line->fk_product;
                        }
                        unset($current_lines[$key]);
                    }
                }
                $this->deleteCommandeLine($commande, $id_line, $errors, $id_product);
            }
            $commande->call_trigger('ORDER_MODIFY', $this->user);
        }

        return array(
            'object' => $commande,
            'errors' => $errors
        );
    }

    // Helpers:

    protected function findCategoriesToExport()
    {
        $root_category = $this->parameters['id_root_categorie'];
        $subCats = $this->findChildrenCategories((int) $root_category);
        $categories = array();
        foreach ($subCats as $idc) {
            if (!in_array($idc, $categories)) {
                if ((int) $idc !== (int) $this->parameters['id_root_categorie']) {
                    $categories[] = (int) $idc;
                }
            }
        }
        return $this->checkOptionsForObjectsExport('Categorie', $categories);
    }

    protected function findProductsToExport($categories)
    {
        $sql = 'SELECT p.rowid as id FROM ' . MAIN_DB_PREFIX . 'product p ';
        $sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'categorie_product cp ON cp.fk_product = p.rowid ';
        $sql .= 'WHERE cp.fk_categorie IN (' . implode(',', $categories) . ')';
        $rows = $this->db->executeS($sql, 'array');

        $ids = array();
        foreach ($rows as $r) {
            $ids[] = $r['id'];
        }

        return $this->checkOptionsForObjectsExport('Product', $ids);
    }

    public function isCategorieInSyncCategories($id_categorie, $display_msg = false)
    {
        if (BDS_Tools::isCategorieChildOf($this->db, $id_categorie, $this->parameters['id_root_categorie'])) {
            return true;
        }
        if ($display_msg) {
            $msg = 'Cette catégorie n\'est pas éligible à l\'export vers Dolibarr ';
            $msg .= '(elle n\'est pas incluse dans l\'une des catégories racines de Prestashop)';
            $this->alert($msg, $id_categorie);
        }
        return false;
    }

    public function isProductInSyncCategories($id_product, $display_msg = false)
    {
        $categories = array();
        $rows = $this->db->getRows('categorie_product', '`fk_product` = ' . (int) $id_product);
        foreach ($rows as $r) {
            $categories[] = (int) $r->fk_categorie;
        }

        foreach ($categories as $id_category) {
            if (BDS_Tools::isCategorieChildOf($this->db, $id_category, (int) $this->parameters['id_root_categorie'])) {
                return true;
            }
        }

        if ($display_msg) {
            $msg = 'Ce produit n\'est pas éligible à l\'export vers Dolibarr';
            $this->alert($msg, $id_category);
        }
        return false;
    }

    protected function processImportProductCategories($id_product, $categories, $ext_ids = true)
    {
        $errors = array();

        if ($ext_ids) {
            $new_cats = array();
            foreach ($categories as $ext_id_categorie) {
                $id_categorie = BDS_SyncData::getObjectValue($this->db, 'loc_id_object', $this->processDefinition->id, 'Categorie', $ext_id_categorie, 'ext_id_object');
                if (!is_null($id_categorie) && $id_categorie) {
                    if (BDS_Tools::isCategorieChildOf($this->db, (int) $id_categorie, (int) $this->parameters['id_root_categorie'])) {
                        $new_cats[] = (int) $id_categorie;
                    }
                }
            }
            $categories = $new_cats;
        }

        $current_categories = $this->db->getValues('categorie_product', 'fk_categorie', '`fk_product` = ' . (int) $id_product);

        if (is_null($current_categories)) {
            $current_categories = array();
        }

        // Recherche des catégories à désassocier:
        foreach ($current_categories as $idx => $id_categorie) {
            if (!in_array((int) $id_categorie, $categories)) {
                if (BDS_Tools::isCategorieChildOf($this->db, $id_categorie, $this->parameters['id_root_categorie'])) {
                    $where = '`fk_categorie` = ' . (int) $id_categorie . ' AND `fk_product` = ' . (int) $id_product;
                    if ($this->db->delete('categorie_product', $where) <= 0) {
                        $msg = 'Echec de la désassociation du produit avec la catégorie d\'ID "' . $id_categorie . '"';
                        $this->SqlError($msg, 'Product', $id_product);
                        $errors[] = $msg;
                    }
                    unset($current_categories[$idx]);
                }
            }
        }

        // Recherche des nouvelles catégories à associer
        foreach ($categories as $id_categorie) {
            if (!in_array($id_categorie, $current_categories)) {
                if (!$this->db->insert('categorie_product', array(
                            'fk_categorie' => (int) $id_categorie,
                            'fk_product'   => (int) $id_product
                        ))) {
                    $msg = 'Echec de l\'association du produit avec la catégorie d\'ID "' . $id_categorie . '"';
                    $this->SqlError($msg, 'Product', $id_product);
                    $errors[] = $msg;
                }
            }
        }

        return $errors;
    }

    protected function processImportProductImages($product, $images, BDS_SyncData $sync_data, $base_url)
    {
        $errors = array();

        if (!isset($product->id) || !$product->id) {
            $msg = 'Impossible d\'importer les images du produit (ID du produit absent)';
            $this->Error($msg, 'Product');
            $errors[] = $msg;
            return $errors;
        }

        $id_product = $product->id;
        $img_dir = BDS_Tools::getProductImagesDir($product);

        if (is_null($img_dir)) {
            $msg = 'Echec de la récupération du répertoire pour l\'import des images du produit';
            $this->Error($msg, 'Product', $id_product);
            $errors[] = $msg;
            return $errors;
        }

        $sync_imgs = $sync_data->getObjects('image');

        // Recherche des images à supprimer: 
        foreach ($sync_imgs as $loc_file => $ext_file) {
            $keep = false;
            foreach ($images as $img) {
                if (isset($img['file'])) {
                    if ($img['file'] == $ext_file) {
                        $keep = true;
                        break;
                    }
                }
            }
            if (!$keep) {
                if (file_exists($img_dir . $loc_file)) {
                    if (!unlink($img_dir . $loc_file)) {
                        $msg = 'Echec de la suppression de l\'image "' . $loc_file . '"';
                        $this->Error($msg, 'Product', $id_product);
                        $errors[] = $msg;
                    }
                }
                unset($sync_imgs[$loc_file]);
            }
        }

        // Recherche de l'image de couverture actuelle:
        $dir = BDS_Tools::getProductImagesDir($product);
        $current_cover = 0;
        foreach ($sync_imgs as $loc_file => $ext_file) {
            if (preg_match('/^cover_(.+)$/', $loc_file, $matches)) {
                // On ne conserve que la première image de couverture: 
                if ($current_cover) {
                    BDS_Tools::renameFile($dir, $loc_file, $matches[1]);
                    unset($sync_imgs[$loc_file]);
                    $sync_imgs[$matches[1]] = $ext_file;
                } else {
                    $current_cover = $loc_file;
                }
            }
        }

        // Recherche des images à importer:
        $cover_done = false;
        foreach ($images as $img) {
            if (isset($img['file']) && $img['file'] &&
                    isset($img['url']) && $img['url']) {
                if (!in_array($img['file'], $sync_imgs)) {
                    $cover = false;
                    if (!$cover_done && isset($img['cover']) && $img['cover']) {
                        $cover = true;
                        if ($current_cover) {
                            // renommage de l'ancienne image de couverture: 
                            $new_name = str_replace('cover_', '', $current_cover);
                            BDS_Tools::renameFile($dir, $current_cover, $new_name);
                            $sync_imgs[$new_name] = $sync_imgs[$current_cover];
                            unset($sync_imgs[$current_cover]);
                            $current_cover = 0;
                        }
                    }
                    $loc_file = $this->importProductImageByUrl($product, $img['file'], $base_url . $img['url'], $img_dir, $cover);
                    if (!is_null($loc_file)) {
                        if ($cover) {
                            $current_cover = $loc_file;
                            $cover_done = true;
                        }
                        $sync_imgs[$loc_file] = $img['file'];
                    } else {
                        $msg = 'Echec de l\'import de l\'image "' . $img['file'] . '"';
                        $this->Error($msg, 'Product', $id_product);
                        $errors[] = $msg;
                    }
                } elseif (!$cover_done && isset($img['cover']) && $img['cover']) {
                    if ($current_cover) {
                        if ($sync_imgs[$current_cover] !== $img['file']) {
                            // renommage de l'ancienne image de couverture: 
                            $new_name = str_replace('cover_', '', $current_cover);
                            BDS_Tools::renameFile($dir, $current_cover, $new_name);
                            $sync_imgs[$new_name] = $sync_imgs[$current_cover];
                            unset($sync_imgs[$current_cover]);
                            $current_cover = 0;
                        } else {
                            $cover_done = true;
                        }
                    }

                    if (!$current_cover) {
                        // Recherche de la nouvelle image de couverture: 
                        foreach ($sync_imgs as $loc_file => $ext_file) {
                            if ($ext_file === $img['file']) {
                                if (!preg_match('/^cover_.+$/', $loc_file)) {
                                    BDS_Tools::renameFile($dir, $loc_file, 'cover_' . $loc_file);
                                    unset($sync_imgs[$loc_file]);
                                    $sync_imgs['cover_' . $loc_file] = $ext_file;
                                }
                                $current_cover = $loc_file;
                                $cover_done = true;
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!$cover_done && !$current_cover) {
            foreach ($sync_imgs as $loc_file => $ext_file) {
                if (!preg_match('/^cover_.+$/', $loc_file)) {
                    BDS_Tools::renameFile($dir, $loc_file, 'cover_' . $loc_file);
                    unset($sync_imgs[$loc_file]);
                    $sync_imgs['cover_' . $loc_file] = $ext_file;
                }
            }
        }
        $sync_data->setObjects('image', $sync_imgs);
        return $errors;
    }

    protected function processCommandeContact(Commande $commande, $id_contact, $type, &$errors)
    {
        $label = '';
        switch ($type) {
            case 'BILLING': $label = 'facturation';
                break;
            case' SHIPPING': $label = 'livraison';
                break;
        }

        $addContact = true;
        $current_contacts = $commande->liste_contact();
        foreach ($current_contacts as $current_contact) {
            if ($current_contact['code'] === $type) {
                if ((int) $current_contact['id'] !== (int) $id_contact) {
                    $this->deleteCommandeContact($id_contact, $commande, $type, $errors);
                } else {
                    $addContact = false;
                }
            }
        }
        if ($addContact) {
            $contact = new Contact($this->db->db);
            if ($contact->fetch($id_contact) <= 0) {
                $msg = 'Contact pour la ' . $label . ' d\'ID ' . $id_contact . ' non trouvé';
                $errors[] = $msg;
                $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
            } else {
                if ((int) $contact->socid !== (int) $commande->socid) {
                    $msg = 'Le contact d\'ID ' . $id_contact . ' n\'appartient pas à la société associée à cette commande';
                    $errors[] = $msg;
                    $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                } else {
                    $this->addCommandeContact($id_contact, $commande, $type, $errors);
                }
            }
        }
    }

    protected function addCommandeContact($id_contact, Commande $commande, $type, &$errors)
    {
        $label = '';
        switch ($type) {
            case 'BILLING': $label = 'facturation';
                break;
            case 'SHIPPING': $label = 'livraison';
                break;
        }

        if ($commande->add_contact($id_contact, $type) <= 0) {
            $msg = 'Echec de l\'enregistrement du contact d\'ID ' . $id_contact . ' pour l\'adresse de ' . $label . ' de cette commande';
            $msg .= $this->ObjectError($commande);
            $errors[] = $msg;
            $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
        } else {
            $msg = 'Contact pour la ' . $label . ' enregistré correctement';
            $this->Success($msg, $this->curName(), $this->curId(), $this->curRef());
            return true;
        }
        return false;
    }

    protected function deleteCommandeContact($id_contact, Commande $commande, $type, &$errors)
    {
        $label = '';
        switch ($type) {
            case 'BILLING': $label = 'facturation';
                break;
            case' SHIPPING': $label = 'livraison';
                break;
        }

        if ($commande->delete_contact($id_contact) < 0) {
            $msg = 'Echec de la suppression du contact pour la ' . $label . ' d\'ID ' . $id_contact;
            $msg .= $this->ObjectError($commande);
            $errors[] = $msg;
            $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
        } else {
            $this->Info('Contact pour la ' . $label . ' supprimé (ID ' . $id_contact . ')', $this->curName(), $this->curId(), $this->curRef());
            return true;
        }
        return false;
    }

    protected function processCommandeProduct(Commande $commande, &$id_order_line, $id_product, $product_data, &$errors)
    {
        $product = new Product($this->db->db);

        if ($product->fetch($id_product) <= 0) {
            $msg = 'Produit "' . $product_data['name'] . '" non trouvé (ID ' . $id_product . ')';
            $msg .= $this->ObjectError($product);
            $errors[] = $msg;
            $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
            return false;
        }

        $qty = $product_data['quantity'];
        $pu_ht = $product_data['base_unit_price_ht'];
        $txtva = $product_data['tax_rate'] * 100;

        $remise_percent = 0;
        if (isset($product_data['reduction_percent']) && (float) $product_data['reduction_percent']) {
            $remise_percent = $product_data['reduction_percent'];
        }

        if (isset($product_data['reduction_amount_ht']) && (float) $product_data['reduction_amount_ht']) {
            $pu_ht -= (float) $product_data['reduction_amount_ht'];
        }

        $txlocaltax1 = 0; // ??
        $txlocaltax2 = 0; // ??

        $fk_fournprice = null;
        $pa_ht = 0;

        $where = '`fk_product` = ' . (int) $product->id;
        $rows = $this->db->getRows('product_fournisseur_price', $where, null, 'object', array(
            'rowid', 'unitprice'
        ));

        if (!is_null($rows)) {
            foreach ($rows as $r) {
                if (!$pa_ht || ((float) $r->unitprice < (float) $pa_ht)) {
                    $pa_ht = (float) $r->unitprice;
                    $fk_fournprice = $r->rowid;
                }
            }
        }

        if (!$pa_ht || is_null($fk_fournprice)) {
            $msg = 'Aucun prix d\'achat fournisseur enregistré pour ce produit';
            $this->Alert($msg, $this->curName(), $this->curId(), $this->curRef());
        }

        if (is_null($id_order_line) || !$id_order_line) {
            if (($result = $commande->addline($product->label, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $product->id, $remise_percent, 0, 0, 'HT', 0, '', '', 0, -1, 0, 0, $fk_fournprice, $pa_ht)) <= 0) {
                $msg = 'Echec de l\'ajout du produit "' . $product->label . '" à la commande';
                $msg .= $this->ObjectError($commande);
                $errors[] = $msg;
                $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                return false;
            }
            $id_order_line = $result;
        } else {
            if ($commande->updateline($id_order_line, $product->label, $pu_ht, $qty, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 'HT', 0, '', '', 0, 0, 0, $fk_fournprice, $pa_ht) <= 0) {
                $msg = 'Echec de la mise à jour du produit "' . $product->label . '"';
                $msg .= $this->ObjectError($commande);
                $errors[] = $msg;
                $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                return false;
            }
        }
        return true;
    }

    protected function processCommandePack(Commande $commande, &$id_order_line, $data, &$errors)
    {
        if (!is_null($id_order_line) && $id_order_line) {
            if ($commande->updateline($id_order_line, $data['name'], 0, $data['quantity'], 0, 0) <= 0) {
                $msg = 'Echec de la mise à jour de la ligne libre "' . $data['name'] . '" à la commande';
                $msg .= $this->ObjectError($commande);
                $errors[] = $msg;
                $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                return false;
            }
        } else {
            if (($result = $commande->addline($data['name'], 0, $data['quantity'], 0)) <= 0) {
                $msg = 'Echec de l\'ajout de la ligne libre "' . $data['name'] . '" à la commande';
                $msg .= $this->ObjectError($commande);
                $errors[] = $msg;
                $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
                return false;
            }
            $id_order_line = $result;
        }
        return true;
    }

    protected function deleteCommandeLine(Commande $commande, $id_order_line, &$errors, $id_product = null)
    {
        if ($commande->deleteline($this->user, $id_order_line) <= 0) {
            if (!is_null($id_product)) {
                $msg = 'Echec de la suppression du produit d\'ID ' . $id_product;
            } else {
                $msg = 'Echec de la suppression de la ligne d\'ID ' . $id_order_line;
            }
            $msg .= $this->ObjectError($commande);
            $errors[] = $msg;
            $this->Error($msg, $this->curName(), $this->curId(), $this->curRef());
            return false;
        }
        if (!is_null($id_product)) {
            $msg = 'Produit d\'ID ' . $id_product . ' retiré de la commande';
        } else {
            $msg = 'Ligne d\'ID ' . $id_order_line . ' retirée de la commande';
        }
        $this->Info($msg, $this->curName(), $this->curId(), $this->curRef());
        return true;
    }

    // Suppression des objets: 

    protected function deleteProduct($id_product, &$errors)
    {
        if (!class_exists('Product')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
        }

        $product = new Product($this->db->db);
        $product->fetch($id_product);

        if (is_null($product->id) || !$product->id) {
            BDS_SyncData::deleteByLocObject($this->processDefinition->id, 'Product', $id_product);
            return true;
        }

        return $this->deleteObject($product, 'du produit', $errors);
    }

    protected function deleteCategorie($id_categorie, &$errors)
    {
        if (!class_exists('Product')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
        }

        $categorie = new Categorie($this->db->db);
        $categorie->fetch($id_categorie);

        if (is_null($categorie->id) || !$categorie->id) {
//            BDS_SyncData::deleteByLocObject($this->processDefinition->id, 'Categorie', $id_categorie);
            return true;
        }

        return $this->deleteObject($categorie, 'de la catégorie', $errors);
    }

    protected function deleteSociete($id_societe, &$errors)
    {
        if (!class_exists('Scociete')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
        }

        $societe = new Societe($this->db->db);
        $societe->fetch($id_societe);

        if (is_null($societe->id) || !$societe->id) {
            BDS_SyncData::deleteByLocObject($this->processDefinition->id, 'Scociete', $id_societe);
            return true;
        }

        return $this->deleteObject($societe, 'du client', $errors);
    }

    protected function deleteContact($id_contact, &$errors)
    {
        if (!class_exists('Contact')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        }

        $contact = new Contact($this->db->db);
        $contact->fetch($id_contact);

        if (is_null($contact->id) || !$contact->id) {
            BDS_SyncData::deleteByLocObject($this->processDefinition->id, 'Contact', $id_contact);
            return true;
        }

        return $this->deleteObject($contact, 'du contact', $errors);
    }

    // Installation:

    public static function install()
    {
//        $actions = array(
//            array('name' => 'COMPANY_CREATE', 'active' => 0),
//            array('name' => 'COMPANY_MODIFY', 'active' => 0),
//            array('name' => 'COMPANY_DELETE', 'active' => 0),
//            array('name' => 'CONTACT_CREATE', 'active' => 0),
//            array('name' => 'CONTACT_MODIFY', 'active' => 0),
//            array('name' => 'CONTACT_DELETE', 'active' => 0),
//            array('name' => 'CONTACT_ENABLEDISABLE', 'active' => 0),
//            array('name' => 'PRODUCT_CREATE', 'active' => 0),
//            array('name' => 'PRODUCT_MODIFY', 'active' => 0),
//            array('name' => 'PRODUCT_DELETE', 'active' => 0),
//            array('name' => 'PRODUCT_PRICE_MODIFY', 'active' => 0),
//            array('name' => 'PRODUCT_SET_MULTILANGS', 'active' => 0),
//            array('name' => 'PRODUCT_DEL_MULTILANGS', 'active' => 0),
//            array('name' => 'STOCK_MOVEMENT', 'active' => 0),
//            array('name' => 'CATEGORY_CREATE', 'active' => 0),
//            array('name' => 'CATEGORY_MODIFY', 'active' => 0),
//            array('name' => 'CATEGORY_DELETE', 'active' => 0),
//            array('name' => 'CATEGORY_SET_MULTILANGS', 'active' => 0)
//        );
//
//        $errors = self::addProcessTriggerActions($actions);
//        if (count($errors) && self::$debug_mod) {
//            $this->debug_content .= 'Erreurs lors de l\'ajout des actions sur trigger: ';
//            $this->debug_content .= '<pre>';
//            $this->debug_content .= print_r($errors, 1);
//            $this->debug_content .= '</pre>';
//        }
    }
}
