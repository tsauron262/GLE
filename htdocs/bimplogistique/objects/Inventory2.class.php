<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpequipment/objects/BE_Place.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';



/**
 * Requete test
 * SELECT * FROM `llx_bl_inventory_expected` where `qty_scanned` != IFNULL((SELECT SUM(`qty`) FROM `llx_bl_inventory_det_2` WHERE `fk_warehouse_type` = `id_wt` AND `fk_product` = `id_product`), 0) 

ORDER BY `llx_bl_inventory_expected`.`id_inventory`  DESC
 * 
 */

class Inventory2 extends BimpObject
{

    CONST STATUS_DRAFT = 0;
    CONST STATUS_OPEN = 1;
    CONST STATUS_CLOSED = 2;

    
    public static $status_list = Array(
        self::STATUS_DRAFT            => Array('label' => 'Brouillon', 'classes'           => Array('success'), 'icon' => 'fas_cogs'),
        self::STATUS_OPEN             => Array('label' => 'Ouvert', 'classes'              => Array('warning'), 'icon' => 'fas_arrow-alt-circle-down'),
        self::STATUS_CLOSED           => Array('label' => 'Fermé', 'classes'               => Array('danger'),  'icon' => 'fas_times')
    );
    
    
    
    public static $types;
    
    public function __construct($module, $object_name) {
        self::$types = BE_Place::$types;
        parent::__construct($module, $object_name);
    }
    
    public function getAllCategChild($cat, &$categs) {
        
        foreach($cat->get_filles() as $c) {
            
            if(!isset($categs[$c->id]))
                $categs[$c->id] = $c;
            
            $this->getAllCategChild($c, $categs);
            
        }

    }
    



    public function create(&$warnings = array(), $force_create = false) {
        
        $errors = array();
        $ids_prod = array();
                
        $filters = (int) BimpTools::getValue('filter_inventory');
        if($filters) {
            $ids_prod = $this->getPostedIdsProducts();
            $categories = BimpTools::getPostFieldValue('categories');
        }
        
        if($filters and empty($categories) and empty($ids_prod)) {
            $errors[] = "Vous avez renseigné \"Inventaire partiel\" sans"
                    . " renseigner ni produits ni catégories.</br>"
                    . "Merci de désactiver cette option si vous ne voulez pas utiliser de filtre";
            return $errors;
        }
        
        
        $warehouse_and_type = BimpTools::getValue('warehouse_and_type');
        unset($warehouse_and_type[0]);
        
        // Définition de l'entrepot par défault
        list($w_main, $t_main) = explode('_', $warehouse_and_type[1]);
        $this->data['fk_warehouse'] = (int) $w_main;
        $this->data['type'] = (int) $t_main;
        $this->data['config'] = json_encode(array());
        
        if(!empty($errors))
            return $errors;
        
        // Création de l'inventaire
        $errors = array_merge($errors, parent::create($warnings, $force_create));
        
        $errors = array_merge($errors, $this->createPackageVol());
        $errors = array_merge($errors, $this->createPackageNouveau());
        
        // Définition de la configuration de l'inventaire
        $this->updateField('config',json_encode(array(
            'cat'                => $categories,
            'prod'               => $ids_prod,
            'id_package_vol'     => $this->temp_package_vol,
            'id_package_nouveau' => $this->temp_package_nouveau
        )));
        
        $errors = array_merge($errors, $this->createWarehouseType($warehouse_and_type, $w_main, $t_main));
        
        if(empty($ids_prod))
            $ids_prod = false;
        $errors = array_merge($errors, $this->createExpected($filters, $ids_prod));
        
        return $errors;
    } 
    
    private function createWarehouseType($warehouse_and_type, $w_main, $t_main) {
        $errors = array();
        // Création des couple entrepôt/type
        foreach($warehouse_and_type as $wt) {
            list($w, $t) = explode('_', $wt);
            $wt_obj = BimpObject::getInstance($this->module, 'InventoryWarehouse');
            $errors = BimpTools::merge_array($errors, $wt_obj->validateArray(array(
                'fk_inventory' => (int) $this->getData('id'),
                'fk_warehouse' => (int) $w,
                'type'         => (int) $t,
                'is_main'      => ((int) $w_main == (int) $w and (int) $t_main == (int) $t)  ? 1 : 0
            )));
            $errors = BimpTools::merge_array($errors, $wt_obj->create());
        }
        
        return $errors;
    }


    /**
     * Attention, vérifier qu'il n'y ai pas un expected qui existe pour ce prod
     */
    private function createExpected($has_filter, $allowed = false) {
        
        if(!is_array($allowed))
            $allowed = $this->getAllowedProduct();
        else
            $has_filter = true; // Force dans le cas
        
        foreach($this->getWarehouseType() as $wt) {
            
            // Products
            if($has_filter)
                $prods = $wt->getProductStock($allowed);
            else
                $prods = $wt->getProductStock(0);
                        
            foreach($prods as $id_prod => $prod) {
                foreach($prod as $datas) {
               
                    $expected = BimpObject::getInstance($this->module, 'InventoryExpected');
                    $errors = array_merge($errors, $expected->validateArray(array(
                        'id_inventory'   => (int)   $this->getData('id'),
                        'id_wt'          => (int)   $wt->getData('id'),
                        'id_package'     => (int)   $datas['id_package'],
                        'id_product'     => (int)   $id_prod,
                        'qty'            => (int)   $datas['qty'],
                        'ids_equipments' => (array) array(),
                        'serialisable'   => 0
                    )));
                    $errors = array_merge($errors, $expected->create());

                }
            }
            
            // Equipments
            if($has_filter)
                $pack_prod_eq = $wt->getEquipmentStock(2, $allowed);
            else
                $pack_prod_eq = $wt->getEquipmentStock(2);
            
                  
            foreach($pack_prod_eq as $id_package => $prod_eq) {
                
                foreach($prod_eq as $id_prod => $ids_equipments) {
                    $expected = BimpObject::getInstance($this->module, 'InventoryExpected');
                    $errors = array_merge($errors, $expected->validateArray(array(
                        'id_inventory'   => (int)   $this->getData('id'),
                        'id_wt'          => (int)   $wt->getData('id'),
                        'id_package'     => (int)   $id_package,
                        'id_product'     => (int)   $id_prod,
                        'qty'            => (int)   sizeof($ids_equipments),
                        'ids_equipments' => (array) $ids_equipments,
                        'serialisable'   => 1
                    )));
                    $errors = array_merge($errors, $expected->create());
                    
                }
            }
        }
        
        return $errors;
        
    }
    
    public function getActionsButtons() {
        global $user;
        $buttons = array();
        if (!$this->isLoaded())
            return $buttons;

        if ($this->getData('status') == self::STATUS_DRAFT) {
            $buttons[] = array(
                'label'   => 'Commencer l\'inventaire',
                'icon'    => 'fas_box',
                'onclick' => $this->getJsActionOnclick('setSatus', array("status" => self::STATUS_OPEN), array(
                    'success_callback' => 'function(result) {bimp_reloadPage();}')
                )
            );
        }

        
        if ($this->getData('status') == self::STATUS_OPEN) {
            if ($user->rights->bimpequipment->inventory->close) {
                $buttons[] = array(
                    'label'   => 'Fermer l\'inventaire',
                    'icon'    => 'fas_window-close',
                    'onclick' => $this->getJsActionOnclick('setSatus', array("status" => self::STATUS_CLOSED), array(
                        'form_name'        => 'confirm_close',
                        'success_callback' => 'function(result) {bimp_reloadPage();}'
                    ))
                );
            }
        }
        
        $buttons[] = array(
            'label'   => 'Ajouter filtre',
            'icon'    => 'fas fa-filter',
            'onclick' => $this->getJsActionOnclick('addProductToConfig', array(), array(
                'form_name'        => 'add_product',
                'success_callback' => 'function(result) {bimp_reloadPage();}')
            )
        );

        return $buttons;
    }
    
    public function getPostedIdsProducts() {
        
        // Filtre par produit directement
        $ids_prod = array();

        foreach($_POST as $key => $inut) {
            
            if(preg_match('/^prod[0-9]+/', $key)) {
                $new_id = BimpTools::getPostFieldValue($key);
                if(!isset($ids_prod[$new_id]) and 0 < $new_id)
                    $ids_prod[$new_id] = $new_id;
            }
            
        }

        return $ids_prod;
    }
    
    /**
     * Indique si l'inventaire contient des filtre par categorie ou par produit
     */
    public function hasFilter() {
        
        $config = $this->getData('config');
        
        if(is_array($config['prod']) and !empty($config['prod']))
            return 1;
        
        if(is_array($config['cat']) and !empty($config['cat']))
            return 1;
        
        return 0;
    }


    public function actionAddProductToConfig($data = array(), &$success = '') {
        $errors = array();
        
        $ids_prod = $this->getPostedIdsProducts();
        echo '<pre>';
        print_r($_REQUEST);
        if(empty($ids_prod)) {
            $errors[] = "Aucun produits n'a été renseigné convenablement.";
            return $errors;
        }

        $config = $this->getData('config');
        $allowed = $this->getAllowedProduct();
        
        $cnt =0;
        
        // On enlève ceux qui étaient déjà inclus
        foreach($ids_prod as $id) {
            if(isset($allowed[$id])) {
                unset($ids_prod[(int) $id]);
                $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $id);
                $warnings .= "Le produit " . $prod->getData('ref') . " était déjà inclus"
                        . " dans les filtres (produit ou catégorie).";
            } else
                $cnt++;
        }
                
        $errors = array_merge($errors, $this->createExpected(1, $ids_prod));
        
        if(empty($errors)) {

            $new_prod = array_merge($config['prod'], $ids_prod);

            $config['prod'] = $new_prod;

            $this->set('config', $config);
            $this->update();
            
            $this->getAllowedProduct(true); // On raffraichit le cache de produits
            
        }
        
        $this->cleanScanAndExpected();
        
        if(!count($errors))
            $success .= $cnt . ' Produit(s) ajouté(s)';
        
        return $errors;
    }
    
    /**
     * Supprime toutes les ligne de scan et les ligne attendu qui ne respectent
     * pas les filtre de l'inventaire
     */
    private function cleanScanAndExpected() {
        $errors = array();
        
        $allowed_products = $this->getAllowedProduct();
        
        // Delete Scan
        $filters_scan =  array(
                'fk_product' => array(
                    'not_in' => array_keys($allowed_products)
                    ),
                'fk_inventory' => (int) $this->id
        );
        
        $line_scan = BimpCache::getBimpObjectInstance($this->module, 'InventoryLine2');
        $line_scan->deleteBy($filters_scan, $errors);
        
        
        // Delete Expected
        $filters_exp =  array(
                'id_product' => array(
                    'not_in' => array_keys($allowed_products)
                    ),
                'id_inventory' => (int) $this->id
        );
        
        $line_exp = BimpCache::getBimpObjectInstance($this->module, 'InventoryExpected');
        $line_exp->deleteBy($filters_exp, $errors);
        
        return $errors;
    }
    

    public static function renderWarehouseAndType() {
        
        if(self::loadClass('bimplogistique', 'InventoryWarehouse'))
            $options = InventoryWarehouse::getAllWarehouseAndType();
        
        $input_name = 'warehouse_and_type';
        $html = '';

        $html .= BimpInput::renderInput('select', $input_name . '_add_value', '', array('options'     => $options));

        $html .= '<p class="inputHelp">';
        $html .= 'Sélectionnez un entrepôt assoscié à son type puis cliquez sur "Ajouter"<br/>';
        $html .= '<strong>Attention ! </strong> Le premier couple entrepôt/type sera celui ';
        $html .= 'dans lequel on placera les produits en trop lors des fermeture d\'inventaire.<br/>';
        $html .= 'C\'est également celui qui sera rempli en dernier lors des mouvements de stock.';
        $html .= '</p>';

        $html .= '<div style="display: none; margin-top: 10px">';
        $html .= BimpInput::renderInput('text', $input_name . '_add_value_custom', '');
        $html .= '</div>';

        return $html;
        
    }
    
    public function renderInputs() {
        $html = '';

        if ($this->isLoaded()) {
            if ((int) $this->getData('status') != (int) self::STATUS_OPEN) {
                $html = BimpRender::renderAlerts('Le statut de l\'inventaire ne permet pas d\'ajouter des lignes', 'info');
            } else {
                $header_table = '<span style="margin-left: 100px">Ajouter</span>';
                $header_table .= BimpInput::renderInput('search_product', 'insert_line', '', array('filter_type' => 'both'));
                $header_table .= "<script>initEvents2();</script>";
                $header_table .= '<span style="margin-left: 100px">Quantité</span>';
                $header_table .= '<input name="insert_quantity" type="number" style="width: 80px; margin-left: 10px;" value="1" >';

                $html = BimpRender::renderPanel($header_table, $html, '', array(
                            'foldable' => false,
                            'type'     => 'secondary',
                            'icon'     => 'fas_plus-circle',
                ));
            }
        }
        
        $html .= '<div id="allow_sound"></div>';

        return $html;
    }
    
    public function actionSetSatus($data = array(), &$success = '') {
        $errors = array();
        $status = (int) $data['status'];
        
        $this->updateField("status", $status);

        // Open
        if ($status == self::STATUS_OPEN) {
            $this->updateField("date_opening", date("Y-m-d H:i:s"));
            
        // Close
        } elseif($status == self::STATUS_CLOSED) {
            // TODO reset date mouv
            $errors = BimpTools::merge_array($errors, $this->close());
            $date_mouvement = BimpTools::getPostFieldValue('date_mouvement');
            if (!$this->setDateMouvement($date_mouvement))
                $errors[] = "Erreur lors de la définition de la date du mouvement";
            
            $this->updateField("date_closing", date("Y-m-d H:i:s"));
            $errors = BimpTools::merge_array($errors, $this->close());
        } else {
            $errors[] = "Statut non reconnu, valeur = " . $status;
        }

        return $errors;
    }
    
    public function canCreate() {
        return $this->isAdmin();
    }
    
//    public function canEdit() {
//        return 1;
//    }
    
    public function canDelete() {
        return $this->isAdmin();
    }
    
//    public function isEditable($force_edit = false, &$errors = array()) {
//        return 1;
//    }
    
    public function isDeletable($force_delete = false, &$errors = array()) {
        return 0;
    }
    
    public function isFieldEditable($field, $force_edit = false) {
        if($field == 'status' or $field == 'date_opening' or $field == 'date_closing')
            return 1;
        return parent::isFieldEditable($field, $force_edit);
    }
        
    public function isAdmin() {
        global $user;
        
        if($user->rights->bimpequipment->inventory->create)
            return 1;
        return 0;
    }

    public function getWarehouseType() {
        $warehouse_type = array();
        
        $inventory_warehouse = BimpObject::getInstance($this->module, 'InventoryWarehouse');
        $tab_tab_ids = $inventory_warehouse->getList(array('fk_inventory' => $this->getData('id')), null, null, 'is_main', 'asc', 'array', array('id'));
        foreach($tab_tab_ids as $tab_id) {
            $warehouse_type[] = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', $tab_id['id']);
        }

        return $warehouse_type;
    }
    
    /**
     * Search the $fk_warehouse_type befor inserting it 
     */
    public function insertLineProduct($id_product, $qty_input, &$errors) {
        
        $return = array();
        
        $diff = $this->getDiffProduct();
        
        end($diff);
        $fk_main_wt = key($diff);
        
        foreach ($diff as $fk_wt => $package_prod_qty) { // Itération sur warehouse_type
            
            foreach($package_prod_qty as $id_package => $prod_qty) { // Itération sur les packages

                $values = $prod_qty[$id_product];
                if(!is_array($values))
                    continue;

                if ($values['diff'] < 0 and $qty_input == -$values['diff']) { // On en attends exactement cette quantité
                    $return[] = $this->createLine($id_product, 0, $qty_input, $fk_wt, $id_package, $errors);
                    return $return;
                } elseif ($values['diff'] < 0 and $qty_input < -$values['diff']) { // On en attends + que ça dans ce stock
                    $return[] = $this->createLine($id_product, 0, $qty_input, $fk_wt, $id_package, $errors);
                    return $return;
                } elseif ($values['diff'] < 0 and $qty_input > -$values['diff']) { // On en attends pas autant
                    $qty_input += $values['diff'];
                    $return[] = $this->createLine($id_product, 0, -$values['diff'], $fk_wt, $id_package, $errors);
                }
                
            } // fin package

            // On atteint le dernier wt, on ne l'a trouver dans aucun package
            // insertion directe et fin de boucle
            if((int) $fk_main_wt == (int) $fk_wt) {
                $return[] = $this->createLine($id_product, 0, $qty_input, $fk_wt, $this->getPackageNouveau(), $errors);
                break;
            }
            
        } // fin warehouse_type
        
        return $return;
    }
    
    /**
     * Search the $fk_warehouse_type befor inserting it 
     */
    public function insertLineEquipment($id_product, $id_equipment, &$errors) {
        
        $diff = $this->getDiffEquipment();
        
        $data = $diff[$id_equipment];
        
        return $this->createLine($id_product, $id_equipment, 1, $data['id_wt'], $data['id_package'], $errors);
    }
    
    
    public function createLine($id_product, $id_equipment, $qty, $fk_warehouse_type, $fk_package, &$errors) {
        $inventory_line = BimpObject::getInstance($this->module, 'InventoryLine2');
        
        // Si le wt n'est pas renseigné, on le met dans celui par défault
        if((int) $fk_warehouse_type == 0) {
            $wt = $this->getMainWT();
            $fk_warehouse_type = (int) $wt->id;
            if(0 == (int) $fk_package)
                $fk_package = $this->getPackageNouveau();
        }

        $errors = BimpTools::merge_array($errors, $inventory_line->validateArray(array(
            'fk_inventory'      => (int) $this->getData('id'),
            'fk_product'        => (int) $id_product,
            'fk_equipment'      => (int) $id_equipment,
            'fk_warehouse_type' => (int) $fk_warehouse_type,
            'fk_package'        => (int) $fk_package,
            'qty'               => (int) $qty,
        )));

        if (!count($errors)) {
            $errors = BimpTools::merge_array($errors, $inventory_line->create());
        } else
            $errors[] = "Erreur lors de la validation des données renseignées";


        return (int) $inventory_line->id;
    }
    
    public function getEquipmentExpectedOld() {
        
        $ids_place = array();

        $sql = 'SELECT id_equipment';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment_place p, ' . MAIN_DB_PREFIX . 'be_equipment e';
        $sql .= ' WHERE id_entrepot=' . $this->getData('fk_warehouse');
        $sql .= ' AND p.id_equipment = e.id AND p.position=1 AND p.type=2';
        if($this->getData('date_opening'))
            $sql .= ' AND p.date < "'.$this->getData('date_opening').'"';

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $ids_place[$obj->id_equipment] = $obj->id_equipment;
            }
        }
        
        return $ids_place;        
    }
    

    public function getDiffEquipment($key_wt = false) {
        
        $diff = array();
        
        if(!$key_wt) {
            foreach ($this->getWarehouseType() as $wt)
                $diff = array_replace($diff, $wt->getScanExpectedEquipment());
        } else {
            foreach ($this->getWarehouseType() as $wt)
                $diff[$wt->id] = $wt->getScanExpectedEquipment();
        }
        
        return $diff;
    }
    /**
    * Attention si $fk_product est renseigné, $fk_package et $fk_warehouse_type doivent l'être aussi
    * Attention si $fk_package est renseigné, $fk_warehouse_type doit l'être aussi
     */
    public function getDiffProduct($fk_warehouse_type = false, $fk_package = false, $fk_product = false) {
        
        $diff = array();
        
        foreach ($this->getWarehouseType() as $wt) // Pour chaque couple entrepôt/type
            $diff[(int) $wt->id] = $wt->getScanExpectedProduct();
        
        
        
        return $diff;
    }
    
    
    public function renderStock() {
//        $wt_prod_stock = $this->getDiffProduct();
        $html = '';
        
        
        foreach($this->getWarehouseType() as $key => $wt) {
            $inventory_warehouse = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', (int) $wt->id);
            $html .= '<h3>' . $inventory_warehouse->renderName() . '</h3>';
            
            $expected = BimpObject::getInstance($this->module, 'InventoryExpected');
            $list = new BC_ListTable($expected);
            $list->addFieldFilterValue('id_wt', $wt->id);
            $list->identifier .= '_' . $key;
            $html .= $list->renderHtml();
        }
        
//        
//        foreach($wt_prod_stock as $fk_wt => $values) {
//            $this->current_wt = $fk_wt;
//            $filter = array('IN' => implode(',', array_keys($values)));
//            $inventory_warehouse = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', (int) $fk_wt);
//            $html .= '<h3>' . $inventory_warehouse->renderName() . '</h3>';
//            $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
//            $list = new BC_ListTable($product, 'inventory_stock');
//            $list->addFieldFilterValue('rowid', $filter);
//            $html .= $list->renderHtml();
//        }

        return $html;
    }
    
    public function renderDifferenceTab() {
        $html = '';
        
        $expected = BimpCache::getBimpObjectInstance($this->module, 'InventoryExpected');
        
        foreach($this->getWarehouseType() as $key => $wt) {
            $inventory_warehouse = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', (int) $wt->id);
            $html .= '<h3>' . $inventory_warehouse->renderName() . '</h3>';
            
            $list = new BC_ListTable($expected);
            $list->addFieldFilterValue('qty != qty_scanned AND 1', '1');
            $list->addFieldFilterValue('id_wt', $wt->getData('id'));
            $list->addIdentifierSuffix($key . '_');
            $html .= $list->renderHtml();
        }

        return $html;
    }
    
    public function renderDifferenceProduct() {
        $html = '';

        $diff = $this->getDiffProduct();
        
        foreach ($diff as $id_wt => $package_prod_qty) {
            
            $wt_obj = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', $id_wt);
            
            $errors = '<h2>' . $wt_obj->renderName(). '</h2>' ;
            $has_diff = false;

            foreach($package_prod_qty as $id_package => $prod_qty) {
                
                if((int) $id_package > 0) {
                    $package = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $id_package);
                    $package_ref = 'package ' . $package->getData('ref');
                } else
                    $package_ref = 'stock';

                foreach($prod_qty as $id_product => $data) {
                    $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
                    if($data['diff'] != 0) {
                        $has_diff = true;
                        $errors .= 'Le produit ' . $product->getData('ref') . ' a été scanné <strong>' . (float) $data['nb_scan'] .
                                '</strong> fois et il est présent <strong>' . (float) $data['stock'] . 
                                '</strong> fois dans le ' . $package_ref .
                                ' il va être modifié de <strong>' . ((0 < (float) $data['diff']) ? '+' : '') . (float) $data['diff'] . '</strong>.<br/>';
                    }
                }
            }
            
            if(!$has_diff) {
                $errors .= '<strong>OK !</strong>';
                $html .= BimpRender::renderAlerts($errors, 'success');
            } else 
                $html .= BimpRender::renderAlerts($errors, 'warning');
            
        }

        return $html;
    }
    
    public function renderDifferenceEquipments() {
        
        $diff = $this->getDiffEquipment(true);
        
        foreach ($diff as $id_wt => $equip_data) {
            
            $wt_obj = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', $id_wt);
            
            $errors = '<h2>' . $wt_obj->renderName(). '</h2>' ;
            $has_diff = false;

            foreach($equip_data as $id_equipment => $data) {
                
                if((int) $data['code_scan'] == 1)
                    continue;
                
                if((int) $data['id_package'] > 0) {
                    $package = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $data['id_package']);
                    $package_ref = $package->getData('ref');
                    $product = BimpCache::getBimpObjectInstance('bimpequipment', 'Bimp_Product', $id_product);
                } else {
                    
                }
                
                if((int) $data['code_scan'] == 0) {
                    $has_diff = true;
                    $errors .= 'Le produit sérialisé ' . $product->getData('ref') . ' a été scanné <strong>' . (float) $data['nb_scan'] .
                            '</strong> fois et il est présent <strong>' . (float) $data['stock'] . 
                            '</strong> fois dans le package/stock ' . $package_ref .
                            ' il va être modifié de <strong>' . ((0 < (float) $data['diff']) ? '+' : '') . (float) $data['diff'] . '</strong>.<br/>';
                } elseif ((int) $data['code_scan'] == 2) {
                
                }
            }
            
            if(!$has_diff) {
                $errors .= '<strong>OK !</strong>';
                $html .= BimpRender::renderAlerts($errors, 'success');
            } else
                $html .= BimpRender::renderAlerts($errors, 'warning');
            
        }

        return $html;
        
    }
    
    
    
    public function setDateMouvement($date_mouvement) {

        $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'stock_mouvement';
        $sql .= ' SET datem="' . $date_mouvement . '"';
        $sql .= ' WHERE inventorycode="inventory-id-' . $this->getData('id') . '"';

        $result = $this->db->db->query($sql);
        if ($result) {
            $this->db->db->commit();
            return true;
        } else {
            dol_print_error($this->db->db);
            $this->db->db->rollback();
            return false;
        }
    }
    
    public function close() {
        $errors = array();
        $errors = array_merge($errors, $this->moveProducts());
        $errors = array_merge($errors, $this->moveEquipments());
       
        return $errors;
    }
    

    public function moveProducts() {
        
        

        $errors = array();
        $id_package_vol = $this->getPackageVol();
        $package_vol = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $id_package_vol);

        $id_package_nouveau = $this->getPackageNouveau();
        $package_nouveau = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $id_package_nouveau);

        
        $diff = $this->getDiffProduct();
        
        foreach ($diff as $id_wt => $package_prod_qty) {
            
            $wt = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', (int) $id_wt);
            $id_entrepot = (int) $wt->getData('fk_warehouse');
            
            foreach($package_prod_qty as $id_package => $prod_qty) {
                
                foreach($prod_qty as $id_product => $data) {
                    
                    if($data['diff'] == 0)
                        continue;
                    
                    // Package
                    if(0 < (int) $id_package) {
                        if($data['diff'] < 0) {
                            $id_dest = $id_package_vol;
                            $package_dest = $package_vol;
                            $data['diff'] *= -1;
                            $current_package = $id_package;
                        } else {
                            $id_dest = $id_package_nouveau;
                            $package_dest = $package_nouveau;
                            $current_package = $id_package_vol;
                        }

                        $errors = BimpTools::merge_array($errors, 
                                BE_Package::moveElements($current_package, $id_dest, array($id_product => $data['diff'])));
                        
                    // Stock
                    } else {
                        if($data['diff'] < 0) {
                            $data['diff'] *= -1;

                            $errors = array_merge($errors, $package_vol->addProduct($id_product, $data['diff'], $id_entrepot));
                        } else {
                            $errors = BimpTools::merge_array($errors, 
                                    BE_Package::moveElements($id_package_vol, $id_package_nouveau, array($id_product => $data['diff'])));
                        }
                        
                    }
                    
                }
            }

        }

        return $errors;
    }
    
    
    public function moveEquipments() {
        
        $errors = array();
        $id_package_vol = $this->getPackageVol();
        $id_package_nouveau = $this->getPackageNouveau();
        
        
        $diff = $this->getDiffEquipment();
        
        
        foreach ($diff as $id_equip => $data) {
            
            if($data['code_scan'] == 0) {
                
                $equip = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equip);
                $errors = BimpTools::merge_array($errors, $equip->moveToPackage($id_package_vol, 'VolInvent'.$this->id, "Manquant lors de l'inventaire " . $this->id, 1));
                
            } elseif($data['code_scan'] == 2) {
                $equip = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equip);
                $errors = BimpTools::merge_array($errors, $equip->moveToPackage($id_package_nouveau, 'DeplaceInvent'.$this->id, "Présent lors de l'inventaire " . $this->id, 1));
            }
            
        }
        
        return $errors;
    }
    
    public static function getWarehouseInventories() {
        global $db;
        
        $sql  = 'SELECT rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'entrepot';
        $sql .= ' WHERE ref="INV"';

        $result = $db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $db->fetch_object($result)) {
                return $obj->rowid;
            }
        }
        
        return -1;
    }
    
    public function getMainWT() {
        
        $wt = BimpObject::getInstance($this->module, 'InventoryWarehouse');
        $id = $wt->getList(array('fk_inventory' => (int) $this->getData('id'), 'is_main' => 1), null, null, 'is_main', 'asc', 'array', array('id'))[0]['id'];
        $wt->fetch((int) $id);
        return $wt;
    }
    
    /**
     * Utilisé si scan only = 0 lors des correct stock
     * @return tableau d'id product
     */
    public function getProductScanned() {
        $products = array();
        
        $sql  = 'SELECT fk_product';
        $sql .= ' FROM      ' . MAIN_DB_PREFIX . 'bl_inventory_det_2';
        $sql .= ' WHERE fk_inventory=' . $this->getData('id');
        $sql .= ' AND (fk_equipment=0 OR fk_equipment IS NULL)';

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $products[(int) $obj->fk_product] = (int) (int) $obj->fk_product;
            }
        }
        
        return $products;
    }
    
    public function getEntrepotRef() {
        $sql = 'SELECT ref';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'entrepot';
        $sql .= ' WHERE rowid=' . $this->getData('fk_warehouse');

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            $obj = $this->db->db->fetch_object($result);
            return $obj->ref;
        }
        
        return "Entrepôt " . $this->getData('fk_warehouse') . " non définit";
    }
    
    public function renderMouvementTrace() {
        
        if (self::STATUS_CLOSED == $this->getData('status')) {
            $url = DOL_URL_ROOT . '/product/stock/mouvement.php?search_inventorycode=Inv#' . $this->getData('id') . '.';
            return '<a href="' . $url . '">Voir</a>';
        }

        return "Disponible à la fermeture de l'inventaire";
    }
    
    public function renderPackageVol() {
        
        $id_package = $this->getData('config')['id_package_vol'];
        $package = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $id_package);
        if(!is_null($package))
            return $package->getNomUrl(true);
        
        return "Package introuvable";
    }
    
    public function renderPackageNouveau() {
        
        $id_package = $this->getData('config')['id_package_nouveau'];
        $package = BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', $id_package);
        if(!is_null($package))
            return $package->getNomUrl(true);
        
        return "Package introuvable";
    }
    
    public function getPackageVol() {
        return (int) $this->getData('config')['id_package_vol'];
    }
    
    public function getPackageNouveau() {
        return (int) $this->getData('config')['id_package_nouveau'];
    }
    
    public function createPackageVol() {
        $errors = array();
        $package = BimpObject::getInstance('bimpequipment', 'BE_Package');
        $errors = BimpTools::merge_array($errors, $package->validateArray(array(
                    'label' => 'Vol-Inventaire#' . $this->id,
                    'products'   => array(),
                    'equipments' => array()
        )));
        $errors = BimpTools::merge_array($errors, $package->create());
        
        $errors = BimpTools::merge_array($errors, $package->addPlace($this->getData('fk_warehouse'), BE_Place::BE_PLACE_VOL,
                date('Y-m-d H:i:s'), 'Vol inventaire ' . $this->id, 'VolInv#' . $this->id . '.'));
        
        $this->temp_package_vol = $package->getData('id');
        
        return $errors;
    }
    
    public function createPackageNouveau() {
        $errors = array();
        $package = BimpObject::getInstance('bimpequipment', 'BE_Package');
        $errors = BimpTools::merge_array($errors, $package->validateArray(array(
                    'label' => 'Nouveau-Inventaire#' . $this->id,
                    'products'   => array(),
                    'equipments' => array()
        )));
        $errors = BimpTools::merge_array($errors, $package->create());
        
        $errors = BimpTools::merge_array($errors, $package->addPlace($this->getData('fk_warehouse'), BE_Place::BE_PLACE_ENTREPOT,
                date('Y-m-d H:i:s'), 'Nouveau inventaire ' . $this->id, 'NouvInv#' . $this->id . '.'));
        
        $this->temp_package_nouveau = $package->getData('id');
        
        return $errors;
    }
    
    public function renderAddProduct($already_created = false) {
        // TODO remettre ça et peut être enlever l'option
//        if(!$this->hasFilter() and !is_null($this->id))
//            $html = BimpRender::renderAlerts('Attention, cet inventaire a été créer sans filtre.<br/>'
//                    . 'Si vous en ajouté, les lignes de scans qui ne répondent pas '
//                    . 'à cette nouvelle exigence seront supprimées.', 'warning');
        
        $html .= '<button type="button" class="addValueBtn btn btn-primary" '
                . 'onclick="getProduct()">'
                . '<i class="fa fa-plus-circle iconLeft"></i>Ajouter</button>';
        
        $html .= '<button type="button" class="addValueBtn btn btn-danger" '
                . 'onclick="deleteProduct()">'
                . '<i class="fas fa5-trash-alt iconLeft"></i>Tout supprimer</button>';
        
        $html .= '<div name="div_products"></div>';
        
        return $html;
    }
    
    public function renderAddCateg() {
        $form = new Form($this->db->db);
        
        $cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', 'parent', 64, 0, 1);
        return $form->multiselectarray('categories', $cate_arbo, array(), '', 0, '', 0, '100%');
    }
    
    public function renderConfigProducts() {
        
        $html = '';
        
        foreach($this->getData('config')['prod'] as $id_prod) {
            $prod_obj = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_prod);
            $html .= $prod_obj->getNomUrl() . '<br/>';
        }
        
        if($html == '')
            $html .= 'Tous';
        
        return $html;
        
    }
    
    public function renderConfigCategories() {
        
        $html = '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">';
        
        $cats = $this->getData('config')['cat'];
        
        foreach($cats as $id_cat) {
            $cat = new Categorie($this->db->db);
            $cat->fetch($id_cat);
            
            $html .= '<span style="background: red">';
            $html .= '<li class="select2-search-choice-dolibarr noborderoncategories"'.
                    ($c->color?' style="background: #'.$c->color.';"':' style="background: #aaa"').'>'
                    .img_object('','category').' '.$cat->print_all_ways(" &gt;&gt; ", '', 0)[0].'</li>';
            $html .= '</span>';
        }
        
        $html .= '</ul></div>';
        
        if(empty($cats))
            $html .= 'Toutes';
                
        return $html;
        
    }

    
    public function getAllowedProduct($refresh_cache = false) {
        
        if(isset($this->cache_prod) and !$refresh_cache)
            return $this->cache_prod;
                
        foreach($this->getData('config')['prod'] as $id_prod) 
            $this->cache_prod[$id_prod] = $id_prod;
        
        $categories = $this->getData('config')['cat'];

        // Ajout des catégories filles
        if(!is_null($categories)) {
            $categs = array();

            foreach($categories as $id_categ){
                $cat = new Categorie($this->db->db);
                $cat->fetch($id_categ);
                $categs[$cat->id] = $cat;
                $this->getAllCategChild($cat, $categs);
            }
        }
            
        // Ajout des produits présent dans les catégories
        foreach($categs as $c) {
            foreach($c->getObjectsInCateg('product', 1) as $id_prod) {
                if(!isset($this->cache_prod[$id_prod]))
                    $this->cache_prod[$id_prod] = $id_prod;
            }
        }
                        
        return $this->cache_prod;
    }
    
    public function isAllowedProduct($id_product) {
        
        $allowed_products = $this->getAllowedProduct();
                
        if(empty($allowed_products))
            return 1;
        
        if(isset($allowed_products[$id_product]))
            return 1;
        
        return 0;
    }
    
    public function renderCateg($cat) {
        
        $id_cat = $this->getIdCateg($cat);
        
        if(is_null($id_cat))
            return "Catégorie \"" . $cat . "\" non définie"
                . ", merci d'en informer l'équipe de développement";
        
        
        return $id_cat;
    }
    
    public function getIdCateg($cat) {
        
        $cats = array(
            'GAMME'      => 9288,
            'CATEGORIE'  => 9954,
            'NATURE'     => 9552, // ???
            'COLLECTION' => 55, // ???
            'FAMILLE'    => 13062
        );
        
        return $cats[$cat];
        
    }
    
    
    
}
