<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';

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
    
    public static $types = array(
        1 => 'Client',
        2 => 'En Stock',
        3 => 'Utilisateur',
        4 => 'Champ libre',
        5 => 'En Présentation',
        6 => 'Vol',
        7 => 'Matériel de prêt',
        8 => 'SAV',
        9 => 'Utilisation interne'
    );
    
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
        
        if(self::loadClass($this->module, 'InventoryWarehouse'))
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
    
    public function update(&$warnings = array(), $force_update = false) {
        $status = (int) $this->getData('status');

        // Open
        if ($status == self::STATUS_OPEN) {
            $this->updateField("date_opening", date("Y-m-d H:i:s"));
            $this->updateField("date_closing_partially", '');
            $this->updateField("date_closing", '');
        // Close partially
        } elseif ($status == self::STATUS_PARTIALLY_CLOSED) {
            $this->updateField("date_closing_partially", date("Y-m-d H:i:s"));
        } elseif($status == self::STATUS_CLOSED) {
            $this->updateField("date_closing", date("Y-m-d H:i:s"));
        } else {
            $warnings[] = "Statut non reconnu, valeur = " . $status;
        }

        if (sizeof($warnings) == 0)
            $errors = parent::update($warnings);

        return $errors;
    }
    
    public function actionSetSatus($data = array(), &$success = '') {
        $errors = array();

        if ((int) $data['status'] == self::STATUS_PARTIALLY_CLOSED) {
//            $only_scanned = BimpTools::getPostFieldValue('only_scanned');
//            $errors = array_merge($errors, $this->closePartially($only_scanned));
//            $date_mouvement = BimpTools::getPostFieldValue('date_mouvement');
//            if (!$this->setDateMouvement($date_mouvement))
//                $errors[] = "Erreur lors de la définition de la date du mouvement";
        }
        
        if ((int) $data['status'] == self::STATUS_CLOSED) {
            $errors = array_merge($errors, $this->close());
        }

        if (!count($errors)) {
            $this->updateField('status', $data['status']);
            $errors = array_merge($errors, $this->update());
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
        } else {
            $errors[] = "Erreur lors de la validation des données renseignées";
        }
        return (int) $inventory_line->db->db->last_insert_id();
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

            foreach ($equip_stock as $id_equip) { // Loop stock
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
    
    public function renderDifference() {
        $html = '';

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
            $html .= '<h3>Produits ' . $inventory_warehouse->renderName() . '</h3>';
            $product = BimpObject::getInstance($this->module, 'ProductInventory');
            $list = new BC_ListTable($product, 'inventory_stock');
            $list->addFieldFilterValue('rowid', $filter);
            $html .= $list->renderHtml();
        }
        
        /****************
         *  EQUIPEMENT  *
         ****************/

        $wt_equip_stock = $this->getDiffEquipment();
        foreach($wt_equip_stock as $fk_wt => $values) {
            $inventory_warehouse = BimpCache::getBimpObjectInstance($this->module, 'InventoryWarehouse', (int) $fk_wt);
            $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
            $html .= '<h3>Equipements ' . $inventory_warehouse->renderName() . '</h3>';
            // Manquant
            $list = new BC_ListTable($equipment, 'default', 1, null, 'Equipements manquant');
            $list->addFieldFilterValue('id', array('IN' => implode(',', $values['ids_manquant'])));
            $html .= $list->renderHtml();
            
            // En trop
            if($inventory_warehouse->getData('is_main')) {
                $list = new BC_ListTable($equipment, 'default', 1, null, 'Equipements en trop');
                $list->addFieldFilterValue('id', array('IN' => implode(',', $values['ids_en_trop'])));
                $html .= $list->renderHtml();
            }
        }
        
        return $html;
    }
    
}
    