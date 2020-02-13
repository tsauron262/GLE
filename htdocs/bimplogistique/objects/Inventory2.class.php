<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpequipment/objects/BE_Place.class.php';

class Inventory2 extends BimpDolObject
{

    CONST STATUS_DRAFT = 0;
    CONST STATUS_OPEN = 1;
    CONST STATUS_PARTIALLY_CLOSED = 2;
    CONST STATUS_CLOSED = 3;

    public static $status_list = Array(
        self::STATUS_DRAFT            => Array('label' => 'Brouillon', 'classes'           => Array('success'), 'icon' => 'fas_cogs'),
        self::STATUS_OPEN             => Array('label' => 'Ouvert', 'classes'              => Array('warning'), 'icon' => 'fas_arrow-alt-circle-down'),
        self::STATUS_PARTIALLY_CLOSED => Array('label' => 'Partiellement fermé', 'classes' => Array('warning'), 'icon' => 'fas_arrow-alt-circle-down'),
        self::STATUS_CLOSED           => Array('label' => 'Fermé', 'classes'               => Array('danger'),  'icon' => 'fas_times')
    );
    
    public static $types;
    
    public function __construct($module, $object_name) {
        self::$types = BE_Place::$types;
        parent::__construct($module, $object_name);
    }
    
    public function create(&$warnings = array(), $force_create = false) {
        
        $errors = array();
        
        $warehouse_and_type = BimpTools::getValue('warehouse_and_type');
        unset($warehouse_and_type[0]);

        // Vérification des autres inventaires
        if($this->checkBeforeCreate($warnings) != 1)
            return $errors;
        
        // Définition de l'entrepot par défault
        list($w_main, $t_main) = explode('_', $warehouse_and_type[1]);
        $this->data['fk_warehouse'] = (int) $w_main;
        $this->data['type'] = (int) $t_main;
        
        $errors = array_merge($errors, parent::create($warnings, $force_create));
        
        // Création des couple entrepôt/type
        foreach($warehouse_and_type as $key => $wt) {
            list($w, $t) = explode('_', $wt);
            $wt_obj = BimpObject::getInstance($this->module, 'InventoryWarehouse');
            $errors = array_merge($errors, $wt_obj->validateArray(array(
                'fk_inventory' => (int) $this->getData('id'),
                'fk_warehouse' => (int) $w,
                'type'         => (int) $t,
                'is_main'      => ((int) $w_main == (int) $w and (int) $t_main == (int) $t)  ? 1 : 0
            )));
            $errors = array_merge($errors, $wt_obj->create());
        }
        return $errors;
    }
    
    public function checkBeforeCreate(&$warnings) {

        $filters = array(
            'fk_warehouse' => array(
                'operator' => '=',
                'value'    => $this->getData('fk_warehouse')
            ),
            'status'       => array(
                'operator' => '<',
                'value'    => self::STATUS_PARTIALLY_CLOSED
            ),
        );

        $inventory_obj = BimpObject::getInstance($this->module, 'Inventory');
        $l_inventory_open = $inventory_obj->getList($filters, null, null, 'id', 'desc', 'array', array('id'));

        if (!empty($l_inventory_open)) {
            $links = '';
            foreach ($l_inventory_open as $data) {
                $inventory_open = BimpCache::getBimpObjectInstance($this->module, 'Inventory', (int) $data['id']);
                $url = $inventory_open->getUrl();
                $links .= '<a href="' . $url . '"><span><i class="far fa5-arrow-alt-circle-right iconRight"></i>#' . $data['id'] . '</span></a>';
            }
            $warnings[] = "Il existe déjà un inventaire non fermé pour cet entrepôt ! " . $links;
            return -1;
        }
        
        return 1;
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
                    'label'   => 'Fermer partiellement l\'inventaire',
                    'icon'    => 'fas_window-close',
                    'onclick' => $this->getJsActionOnclick('setSatus', array("status" => self::STATUS_PARTIALLY_CLOSED), array(
                        'form_name'        => 'confirm_close_partially',
                        'success_callback' => 'function(result) {bimp_reloadPage();}'
                    ))
                );
            }
        }
        
        if ($this->getData('status') == self::STATUS_PARTIALLY_CLOSED) {
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

        return $buttons;
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
        $html .= 'dans lequel on placera les produits en trop lors des fermeture d\'inventaire.';
        $html .= '</p>';

        $html .= '<div style="display: none; margin-top: 10px">';
        $html .= BimpInput::renderInput('text', $input_name . '_add_value_custom', '');
        $html .= '</div>';

        return $html;
        
    }
    
    public function renderInputs() {
        $html = '';

        if ($this->isLoaded()) {
            if ((int) $this->getData('status') != (int) self::STATUS_OPEN and (int) $this->getData('status') != (int) self::STATUS_PARTIALLY_CLOSED) {
                $html = BimpRender::renderAlerts('Le statut de l\'inventaire ne permet pas d\'ajouter des lignes', 'info');
            } else {
                $header_table = '<span style="margin-left: 100px">Ajouter</span>';
                $header_table .= BimpInput::renderInput('search_product', 'insert_line', '', array('filter_type' => 'both'));
                $header_table .= "<script>initEvents2();</script>";
                $header_table .= '<span style="margin-left: 100px">Quantité</span>';
                $header_table .= '<input class="search_list_input"  name="insert_quantity" type="number" style="width: 80px; margin-left: 10px;" value="1" >';

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
        // Close partially
        } elseif ($status == self::STATUS_PARTIALLY_CLOSED) {
            $this->updateField("date_closing_partially", date("Y-m-d H:i:s"));
            $only_scanned = BimpTools::getPostFieldValue('only_scanned');
            $errors = array_merge($errors, $this->closePartially($only_scanned));
            $date_mouvement = BimpTools::getPostFieldValue('date_mouvement');
            if (!$this->setDateMouvement($date_mouvement))
                $errors[] = "Erreur lors de la définition de la date du mouvement";
        } elseif($status == self::STATUS_CLOSED) {
            $this->updateField("date_closing", date("Y-m-d H:i:s"));
            $errors = array_merge($errors, $this->close());
        } else {
            $errors[] = "Statut non reconnu, valeur = " . $status;
        }

        return $errors;
    }
    
    public function canCreate() {
        return $this->isAdmin();
    }
    
    public function canEdit() {
        return 1;
    }
    
    public function canDelete() {
        return $this->isAdmin();
    }
    
    public function isEditable($force_edit = false, &$errors = array()) {
        return 0;
    }
    
    public function isFieldEditable($field, $force_edit = false) {
        if($field == 'status' or $field == 'date_opening'
                or $field == 'date_closing_partially' or $field == 'date_closing')
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
        
        $diff = $this->getDiffProduct();
        
        end($diff);
        $fk_main_wt = key($diff);
        
        foreach ($diff as $fk_wt => $prod_qty) { // Itération sur warehouse_type

            // On atteint le dernier wt, insertion directe et fin de boucle
            if((int) $fk_main_wt == (int) $fk_wt) {
                $this->createLine($id_product, 0, $qty_input, $fk_wt, $errors);
                break;
            }
            
            $values = $prod_qty[$id_product];
            if(!is_array($values))
                continue;
            
            if       ($values['diff'] < 0 and $qty_input == -$values['diff']) { // On en attends exactement cette quantité
                $this->createLine($id_product, 0, $qty_input, $fk_wt, $errors);
                break;
            } elseif ($values['diff'] < 0 and $qty_input < -$values['diff']) { // On en attends + que ça dans ce stock
                $this->createLine($id_product, 0, $qty_input, $fk_wt, $errors);
            } elseif ($values['diff'] < 0 and $qty_input > -$values['diff']) { // On en attends pas autant
                $qty_input += $values['diff'];
                $this->createLine($id_product, 0, -$values['diff'], $fk_wt, $errors);
            }
        }
    }
    
    /**
     * Search the $fk_warehouse_type befor inserting it 
     */
    public function insertLineEquipment($id_product, $id_equipment, $errors) {
        
        $diff = $this->getDiffEquipment();
        
        end($diff);
        $fk_main_wt = key($diff);
        
        foreach ($diff as $fk_wt => $eq_eq) { // Itération sur warehouse_type
            // On atteint le dernier wt, insertion directe et fin de boucle
            if((int) $fk_main_wt == (int) $fk_wt)
                return $this->createLine($id_product, $id_equipment, 1, $fk_wt, $errors);

            // Attendu dans cet entrepot
            if (in_array($id_equipment, $eq_eq['ids_manquant']))
                return $this->createLine($id_product, $id_equipment, 1, $fk_wt, $errors);
        }
    }
    
    public function createLine($id_product, $id_equipment, $qty, $fk_warehouse_type, &$errors) {
        $inventory_line = BimpObject::getInstance($this->module, 'InventoryLine2');

        $errors = array_merge($errors, $inventory_line->validateArray(array(
            'fk_inventory'      => (int) $this->getData('id'),
            'fk_product'        => (int) $id_product,
            'fk_equipment'      => (int) $id_equipment,
            'fk_warehouse_type' => (int) $fk_warehouse_type,
            'qty'               => (int) $qty
        )));

        if (!count($errors)) {
            $errors = array_merge($errors, $inventory_line->create());
        } else
            $errors[] = "Erreur lors de la validation des données renseignées";

        return (int) $inventory_line->id;
    }
    
    public function getEquipmentExpected() {
        
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
    
    public function getDiffEquipment($fk_warehouse_type = false) {
        
        if(is_array($this->wt_eq_diff)) { // variable global existe
            if($fk_warehouse_type != false) { // un wt particulier
                return $this->wt_eq_diff[$fk_warehouse_type];
            }
            return $this->wt_eq_diff;
        } else // Création variable global
            $this->wt_eq_diff = array();
        
        foreach ($this->getWarehouseType() as $wt) { // Pour chaque couple entrepôt/type
            $fk_wt = $wt->getData('id');
            $equip_scanned = $wt->getEquipmentScanned();
            $equip_stock = $wt->getEquipmentStock();
            $this->wt_eq_diff[$fk_wt] = array('ids_manquant' => array(), 'ids_en_trop' => array());
            
            foreach ($equip_scanned as $id_equip) { // Loop stock
                if (!isset($equip_stock[$id_equip]))
                    $this->wt_eq_diff[$fk_wt]['ids_en_trop'][$id_equip] = $id_equip;
            }

            foreach ($equip_stock as $id_equip => $inut) { // Loop stock
                if (!isset($equip_scanned[$id_equip]))
                    $this->wt_eq_diff[$fk_wt]['ids_manquant'][$id_equip] = $id_equip;
            }
        }

        if($fk_warehouse_type != false) { // un wt particulier
            return $this->wt_eq_diff[$fk_warehouse_type];
        }
        return $this->wt_eq_diff;
    }
    
    public function getDiffProduct($fk_warehouse_type = false, $fk_product = false) {
        if(is_array($this->wt_prod_diff)) { // variable global existe
            if($fk_warehouse_type != false) { // un wt particulier
                if($fk_product != false)
                    return $this->wt_prod_diff[$fk_warehouse_type][$fk_product];
                return $this->wt_prod_diff[$fk_warehouse_type];
            }
            return $this->wt_prod_diff;
        } else // Création variable global
            $this->wt_prod_diff = array();
        
        foreach ($this->getWarehouseType() as $wt) { // Pour chaque couple entrepôt/type
            $prod_scanned = $wt->getProductScanned();
            $prod_stock = $wt->getProductStock();
            $this->wt_prod_diff[$wt->getData('id')] = array();
            
            foreach ($prod_stock as $id_prod => $qty_stock) { // Loop stock
                if (!isset($prod_scanned[$id_prod])) {
                    $this->wt_prod_diff[$wt->getData('id')][$id_prod] = 
                            array('stock'   => $qty_stock,
                                  'nb_scan' => 0,
                                  'diff'    => -$qty_stock);
                } else {
                    $this->wt_prod_diff[$wt->getData('id')][$id_prod] = 
                            array('stock'   => $qty_stock,
                                  'nb_scan' => $prod_scanned[$id_prod],
                                  'diff'    => $prod_scanned[$id_prod] - $qty_stock);
                }
            }

            foreach ($prod_scanned as $id_prod => $qty_scan) { // Loop scan
                if (!isset($prod_stock[$id_prod]))
                    $this->wt_prod_diff[$wt->getData('id')][$id_prod] = 
                        array('stock'   => 0,
                              'nb_scan' => $qty_scan,
                              'diff'    => $qty_scan);
            }
        }
        
        if($fk_warehouse_type != false) {
            if($fk_product != false)
                return $this->wt_prod_diff[$fk_warehouse_type][$fk_product];
            return $this->wt_prod_diff[$fk_warehouse_type];
        }
        return $this->wt_prod_diff;
    }
    
    public function renderStock() {
        $wt_prod_stock = $this->getDiffProduct();
        $html = '';

        foreach($wt_prod_stock as $fk_wt => $values) {
            $this->current_wt = $fk_wt;
            $filter = array('IN' => implode(',', array_keys($values)));
            $inventory_warehouse = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', (int) $fk_wt);
            $html .= '<h3>' . $inventory_warehouse->renderName() . '</h3>';
            $product = BimpObject::getInstance($this->module, 'ProductInventory');
            $list = new BC_ListTable($product, 'inventory_stock');
            $list->addFieldFilterValue('rowid', $filter);
            $html .= $list->renderHtml();
        }

        return $html;
    }
    
    public function renderDifferenceTab() {
        $html = '';
        self::loadClass('bimpequipment', 'BE_Place');

        /*************
         *  PRODUIT  *
         *************/
        $wt_prod_stock = $this->getDiffProduct();

        foreach($wt_prod_stock as $fk_wt => $values) {
            $ids_with_diff = array();
            $this->current_wt = $fk_wt;
            foreach ($values as $id_product => $tab) {
                if($tab['diff'] != 0)
                    $ids_with_diff[] = $id_product;
            }
            $filter = array('IN' => $ids_with_diff);
            $inventory_warehouse = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', (int) $fk_wt);
            $product = BimpObject::getInstance($this->module, 'ProductInventory');
            $list = new BC_ListTable($product, 'inventory_stock', 1, null, 'Produits: ' . $inventory_warehouse->renderName());
            $list->identifier .= $fk_wt;
            $list->addFieldFilterValue('rowid', $filter);
            $html .= $list->renderHtml();
        }
        
        /****************
         *  EQUIPEMENT  *
         ****************/

        $wt_equip_stock = $this->getDiffEquipment();
        $manquant = array();
        $en_trop = array();
        foreach($wt_equip_stock as $fk_wt => $values) {
            $manquant = array_merge($manquant, $values['ids_manquant']);
            $en_trop = array_merge($en_trop, $values['ids_en_trop']);
        }
        
        $inventory_warehouse = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', (int) $fk_wt);
        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
        
        // Manquant
        $list_m = new BC_ListTable($equipment, 'inventaireManquant', 1, null, 'Equipements manquant');
        if(!empty($manquant))
            $list_m->addFieldFilterValue('id', array('IN' => implode(',', $manquant)));
        else
                $list_m->addFieldFilterValue('id = 0 AND 1', '0');
        $html .= $list_m->renderHtml();

        // En trop
        if($inventory_warehouse->getData('is_main')) {
            $list_t = new BC_ListTable($equipment, 'inventaireEnTrop', 1, null, 'Equipements en trop');
            if(!empty($en_trop))
                $list_t->addFieldFilterValue('id', array('IN' => implode(',', $en_trop)));
            else
                $list_t->addFieldFilterValue('id = 0 AND 1', '0');
            $html .= $list_t->renderHtml();
        }
        
        $filters = array( // Inutile mais important pour que la requête fonctionne
            'operator' => '!=',
            'value'    => "inv_det.qty"
        );
        
        $sql_vol  = 'id IN (SELECT id_equipment FROM ' . MAIN_DB_PREFIX . 'be_equipment_place p';
        $sql_vol .= ' WHERE infos LIKE "%Inventaire-'.$this->id.'%" AND  p.position=1 AND p.type=' . BE_Place::BE_PLACE_VOL . ') AND 1';

        $list_v = new BC_ListTable($equipment, 'inventaire', 1, null, 'Équipements deplacé dans vols');
        $list_v->addFieldFilterValue($sql_vol, $filters);
        $html .= $list_v->renderHtml();
        
        $sql_e  = 'id IN (SELECT id_equipment FROM ' . MAIN_DB_PREFIX . 'be_equipment_place p';
        $sql_e .= ' WHERE infos LIKE "%Inventaire-'.$this->id.'%" AND  p.position=1 AND p.type=' . BE_Place::BE_PLACE_ENQUETE . ') AND 1';

        $list_e = new BC_ListTable($equipment, 'inventaire', 1, null, 'Équipements deplacé dans enquête');
        $list_e->addFieldFilterValue($sql_e, $filters);
        $html .= $list_e->renderHtml();
        
        return $html;
    }
    
    public function renderDifferenceProduct() {
        $html = '';

        $diff = $this->getDiffProduct();
        foreach ($diff as $fk_wt => $prods) {
            foreach($prods as $id_product => $data) {
                $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_product);
                if($data['diff'] != 0) {
                    $error = 'Le produit ' . $product->getData('ref') . ' a été scanné <strong>' . (float) $data['nb_scan'] .
                            '</strong> fois et il est présent <strong>' . (float) $data['stock'] . '</strong> fois dans le stock ' .
                            ' il va être modifié de <strong>' . (float) $data['diff'] . '</strong>.';
                    $html .= BimpRender::renderAlerts($error, 'warning');
                }
            }
        }

        return $html;
    }
    
    public function renderDifferenceEquipment() {
        $html = '';

        foreach ($this->getErrorsEquipment() as $error) {
            $html .= BimpRender::renderAlerts($error);
        }

        return $html;
    }
    
    private function getErrorsEquipment() {
        $errors = array();
        $equip_en_trop = array();
        $equip_manquant = array();
        $urls_en_trop = '';
        $urls_manquant = '';
        

        $diff_eq = $this->getDiffEquipment();
        foreach ($diff_eq as $fk_wt => $data) {
            $equip_en_trop = array_merge($equip_en_trop, $data['ids_en_trop']);
            $equip_manquant = array_merge($equip_en_trop, $data['ids_manquant']);
        }
        
        foreach ($equip_en_trop as $key => $id_equipment) {
            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
            $urls_en_trop .= '<br/>' . $key . ' => ' . $equipment->getNomUrl();
        }
        
        foreach ($equip_manquant as $key => $id_equipment) {
            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
            $urls_manquant .= '<br/>' . $key . ' => ' . $equipment->getNomUrl();
        }
        
        // Excès
        $nb_en_trop = count($equip_en_trop);

        if ($nb_en_trop == 1)
            $errors[] = 'Merci de traiter le cas du produit sérialisé en excès.' . $urls_en_trop;
        if ($nb_en_trop > 1)
            $errors[] = 'Merci de traiter le cas des ' . $nb_en_trop . ' produits sérialisés en excès.' . $urls_en_trop;
        // Manque
        $nb_manquant = count($equip_manquant);
        if ($nb_manquant == 1)
            $errors[] = 'Merci de traiter le cas du produit sérialisé manquant.' . $urls_manquant;
        if ($nb_manquant > 1)
            $errors[] = 'Merci de traiter le cas des ' . $nb_manquant . ' produits sérialisés manquants.' . $urls_manquant;
        return $errors;
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
        return $this->getErrorsEquipment();
    }
    
    public function closePartially($only_scanned = 0) {

        $errors = array();
        
        $package = $this->getPackageInventory();
        
//        if($package == 0)
            $errors = array_merge($errors, $this->createPackageInventory());
        
        if (!count($errors)) {
            $errors = array_merge($errors, $this->moveProducts($only_scanned));
            $errors = array_merge($errors, $this->moveEquipments());
        }
       
        return $errors;
    }
    
    public function moveProducts($only_scanned) {

        $errors = array();
        $move_product_package = array();
        $move_product_stock = array();
        $package_dest = $this->getPackageInventory();
        
        if(!BimpObject::objectLoaded($package_dest)) {
            $errors[] = "Problème lors du chargement du package de l'inventaire.";
            return $errors;
        }

        foreach ($this->getWarehouseType() as $wt) { // Pour chaque couple entrepôt/type

            $product_scanned = $wt->getProductScanned($only_scanned);
            
            // Dans les package
            if((int) $wt->getData('type') != (int) BE_Place::BE_PLACE_ENTREPOT) {
                $product_stock = $wt->getProductStock($only_scanned, 1);

                foreach($product_stock as $id_product => $datas) {
                
                    // Calcul de la diff
                    if(isset($product_scanned[$id_product]))
                        $reste_scan = $product_scanned[$id_product] - $datas['qty'];
                    else
                        $reste_scan = -$datas['qty'];

                    // On en a scanné au moins autant
                    if(0 <= $reste_scan) {
                        $product_scanned[$id_product] -= $datas['qty'];
    //                    if($reste_scan == 0)
    //                        unset($product_scanned[$id_product]);

                    // On en a scanné moins
                    } else {
                        if(!isset($move_product_package[$datas['id_package']]))
                            $move_product_package[$datas['id_package']] = array();
                        $move_product_package[$datas['id_package']][$id_product] = $datas['qty'];
                    }

                } // Fin boucle sur product stock
                
            } else { // Ce n'est pas dans un package (donc dans stock)
                
                $product_stock = $wt->getProductStock($only_scanned);

                foreach($product_stock as $id_product => $qty) {

//                        $product_stock => $products[(int) $obj->id_product] = (int) $obj->qty;
//                        
                    // Calcul de la diff
                    if(isset($product_scanned[$id_product]))
                        $reste_scan = $product_scanned[$id_product] - $qty;
                    else
                        $reste_scan = -$qty;

                    // On en a scanné au moins autant
                    if(0 <= $reste_scan) {
                        $product_scanned[$id_product] -= $qty;
    //                    if($reste_scan == 0)
    //                        unset($product_scanned[$id_product]);

                    // On en a scanné moins
                    } else {
                        if(!isset($move_product_stock[$id_product]))
                            $move_product_stock[$id_product] = 0;
                        $move_product_stock[$id_product] += $qty;
                    }
                }
            }
            
        } // Fin boucle sur WT
        
        self::loadClass('bimpequipment', 'BE_Package');/*) {
            $errors[] = 'Erreur de chargement de la class package';
            return $errors;
        }*/

        // Package
        foreach ($move_product_package as $id_package => $products) {
            if(is_array($products) and !empty($products))
                $errors = array_merge($errors, BE_Package::moveElements($id_package, (int) $package_dest->id, $products, array()));
        }
        
        // Stock
        foreach($move_product_stock as $id_product => $qty)
            $errors = array_merge($errors, $package_dest->addProduct($id_product, $qty, $this->getData('fk_warehouse')));
        
        return $errors;
    }
    
    public function moveEquipments() {
        
        $errors = array();
        $move_equipment_package = array();
        $move_equipment_stock = array();
        $package_dest = $this->getPackageInventory();
        
        if(!BimpObject::objectLoaded($package_dest)) {
            $errors[] = "Problème lors du chargement du package de l'inventaire.";
            return $errors;
        }
        
        $code_mouv = 'Inv#' . $this->id . '.';
        $label_mouv = 'Déplacement inventaire#' . $this->id;

        foreach ($this->getWarehouseType() as $wt) { // Pour chaque couple entrepôt/type
            $equip_scanned = $wt->getEquipmentScanned();
            
            // Dans les package
            if((int) $wt->getData('type') != (int) BE_Place::BE_PLACE_ENTREPOT) {
                $equip_stock = $wt->getEquipmentStock(1);
                
                foreach($equip_stock as $id_equipment => $id_package) {

                    // Calcul de la diff
                    if(!isset($equip_scanned[$id_equipment])) { // Cet équipement n'a pas été scanné

                        if(!isset($move_equipment_package[$id_package]))
                            $move_equipment_package[$id_package] = array();

                        $move_equipment_package[$id_package][$id_equipment] = $id_equipment;
                    }

                    
                }
                
            // Dans les stock
            } else {
                $equip_stock = $wt->getEquipmentStock();
                
                foreach($equip_stock as $id_equipment => $id_package) {

                    if(!isset($equip_scanned[$id_equipment]))  // Cet équipement n'a pas été scanné
                        $move_equipment_stock[$id_equipment] = $id_equipment;

                }
                
            }

        } // Fin boucle sur WT

        
        // Package
        foreach ($move_equipment_package as $id_package => $equipments)
            $errors = array_merge($errors, BE_Package::moveElements($id_package, (int) $package_dest->id, array(), $equipments));
        
        // Stock
        foreach ($move_equipment_stock as $id_equipment) {
            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
            $errors = array_merge($errors, $equipment->moveToPackage((int) $package_dest->id, $code_mouv, $label_mouv, 1));
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
        
        if (self::STATUS_PARTIALLY_CLOSED <= $this->getData('status')) {
            $url = DOL_URL_ROOT . '/product/stock/mouvement.php?search_inventorycode=Inv#' . $this->getData('id') . '.';
            return '<a href="' . $url . '">Voir</a>';
        }

        return "Disponible à la fermeture partielle de l'inventaire";
    }
    
    public function renderPackageInventory() {
        
        if (self::STATUS_PARTIALLY_CLOSED <= $this->getData('status')) {
            $package = $this->getPackageInventory();
            if(!is_null($package))
                return $package->getNomUrl(true);
            return "Il n'a pas été nécessaire de créer un package";
        }
        
        return "Disponible à la fermeture partielle de l'inventaire";
    }
    
    public function getPackageInventory() {
        
        BimpObject::loadClass('bimpequipment', 'BE_Package');

        $sql = 'SELECT id';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_package';
        $sql .= ' WHERE label="Inventaire#' . $this->getData('id') . '"';

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            $obj = $this->db->db->fetch_object($result);
            return BimpCache::getBimpObjectInstance('bimpequipment', 'BE_Package', (int) $obj->id);
        }
        
        return 0;
    }
    
    public function createPackageInventory() {
        $errors = array();
        $package = BimpObject::getInstance('bimpequipment', 'BE_Package');
        $errors = array_merge($errors, $package->validateArray(array(
                    'label' => 'Inventaire#' . $this->id
        )));
        $errors = array_merge($errors, $package->create());
        
        $errors = array_merge($errors, $package->addPlace($this->getData('fk_warehouse'), BE_Place::BE_PLACE_VOL, date('Y-m-d H:i:s'), 'Vol inventaire ' . $this->id, 'VolInv#' . $this->id . '.'));
        
        return $errors;
    }
}