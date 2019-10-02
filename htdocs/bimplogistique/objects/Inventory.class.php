<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class Inventory extends BimpDolObject
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

    public function getAllInventories() {
        if($this->hasChildren()) // prevent multiple parent
            return 'Non';
        
        $inventories = array(0 => '');
        
        $sql = 'SELECT i.id as id_inv, CONCAT(e.ref, " ", e.lieu) as nom';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bl_inventory AS i';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'entrepot AS e ON i.fk_warehouse=e.rowid';
        $sql .= ' WHERE i.parent=0';
        if($this->getData('id') > 0)
            $sql .= ' AND id!=' . $this->getData('id');
        $sql .= ' ORDER BY id_inv DESC';

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $inventories[$obj->id_inv] = 'Inv#' . $obj->id_inv . '  ' .$obj->nom;
            }
        }
       
        return $inventories;
    }
    
    
    public function canCreate()
    {
        global $user;
        if (!$user->rights->bimpequipment->inventory->create) {
            return 0;
        }

        return 1;
    }
    
    public function canEdit(){
        return 1;
    }
    
    
    public function canDelete()
    {
        return $this->isAdmin();
    }

    public function update(&$warnings = array(), $force_update = false)
    {
        $status = (int) $this->getData('status');

        // Draft
        if ($status == self::STATUS_DRAFT) {
            $this->updateField("date_opening", '');
            $this->updateField("date_closing", '');
            // Open
        } elseif ($status == self::STATUS_OPEN) {
            $this->updateField("date_opening", date("Y-m-d H:i:s"));
            $this->updateField("date_closing", '');
            // Close
        } elseif ($status == self::STATUS_PARTIALLY_CLOSED or $status == self::STATUS_CLOSED) {
            $this->updateField("date_closing", date("Y-m-d H:i:s"));
        } else {
            $warnings[] = "Statut non reconnu, valeur = " . $status;
        }

        if (sizeof($warnings) == 0)
            $errors = parent::update($warnings);

        return $errors;
    }

    public function getActionsButtons()
    {
        global $user;
        $buttons = array();
        if (!$this->isLoaded())
            return $buttons;

        if ($this->getData('status') == self::STATUS_DRAFT) {
            if (1/*$user->rights->bimpequipment->inventory->open*/) {
                $buttons[] = array(
                    'label'   => 'Commencer l\'inventaire',
                    'icon'    => 'fas_box',
                    'onclick' => $this->getJsActionOnclick('setSatus', array("status" => self::STATUS_OPEN), array(
                        'success_callback' => 'function(result) {bimp_reloadPage();}')
                    )
                );
            }
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

    public function actionSetSatus($data = array(), &$success = '')
    {
        $errors = array();

        if ((int) $data['status'] == self::STATUS_PARTIALLY_CLOSED) {
            $errors = array_merge($errors, $this->closePartially());
            $date_mouvement = BimpTools::getPostFieldValue('date_mouvement');
            if (!$this->setDateMouvement($date_mouvement))
                $errors[] = "Erreur lors de la définition de la date du mouvement";
        }
        
        if ((int) $data['status'] == self::STATUS_CLOSED) {
            $errors = array_merge($errors, $this->close());
        }

        if (!count($errors)) {
            $this->updateField("status", $data['status']);
            $errors = array_merge($errors, $this->update());
        }

        return $errors;
    }

    public function setDateMouvement($date_mouvement)
    {

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

    public function getLines($ret_object = false)
    {
        $return = array();
        $inventory_lines_obj = BimpObject::getInstance('bimplogistique', 'InventoryLine');
        $inventory_lines = $inventory_lines_obj->getList(
                array('fk_inventory' => $this->getData('id')), null, null, 'id', 'desc', 'array', array(
            'id',
            'fk_product',
            'fk_equipment',
            'qty'
        ));

        if ($ret_object) {
            foreach ($inventory_lines as $i_line) {
                $inventory_lines_obj = BimpCache::getBimpObjectInstance('bimplogistique', 'InventoryLine', $i_line['id']);
                $return[] = $inventory_lines_obj;
            }
            return $return;
        }
        return $inventory_lines;
    }

    public function create(&$warnings = array(), $force_create = false)
    {
        global $user;

        // Ce doit code doit obligatoirement être placé dans $this->canCreate() (c'est géré en auto). 
//        if (!$user->bimpequipment->inventory->create and ! $user->admin) {
//            $warnings[] = "Vous n'avez pas les droits de créer un inventaire.";
//            return;
//        }

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

        $inventory_obj = BimpObject::getInstance('bimplogistique', 'Inventory');
        $l_inventory_open = $inventory_obj->getList($filters, null, null, 'id', 'desc', 'array', array('id'));

        if (!empty($l_inventory_open)) {
            $links = '';
            foreach ($l_inventory_open as $data) {
                $inventory_open = BimpCache::getBimpObjectInstance('bimplogistique', 'Inventory', (int) $data['id']);
                $url = $inventory_open->getUrl();
                $links .= '<a href="' . $url . '"><span><i class="far fa5-arrow-alt-circle-right iconRight"></i>#' . $data['id'] . '</span></a>';
            }
            $warnings[] = "Il existe déjà un inventaire non fermé pour cet entrepôt ! " . $links;
            return;
        }

        return parent::create($warnings, $force_create);
    }

    
    public function closePartially()
    {
        $errors = $this->correctProducts();

        return $errors;
    }
    
    public function close()
    {
        $errors = array();

        if (!$this->equipmentIsOk())
            $errors = array_merge($errors, $this->getErrorsEquipment());

        return $errors;
    }

    private function getErrorsEquipment()
    {
        $errors = array();

        $diff_eq = $this->getDiffEquipment();
        // Excès
        $nb_en_trop = count($diff_eq['ids_en_trop']);

        if ($nb_en_trop == 1)
            $errors[] = 'Merci de traiter le cas du produit sérialisé en excès.'.print_r($diff_eq['ids_en_trop'],1);
        if ($nb_en_trop > 1)
            $errors[] = 'Merci de traiter le cas des ' . $nb_en_trop . ' produits sérialisés en excès.'.print_r($diff_eq['ids_en_trop'],1);
        // Manque
        $nb_manquant = count($diff_eq['ids_manquant']);
        if ($nb_manquant == 1)
            $errors[] = 'Merci de traiter le cas du produit sérialisé manquant.'.print_r($diff_eq['ids_manquant'],1);
        if ($nb_manquant > 1)
            $errors[] = 'Merci de traiter le cas des ' . $nb_manquant . ' produits sérialisés manquants.'.print_r($diff_eq['ids_manquant'],1);
        return $errors;
    }

    private function getErrorsProduct()
    {
        $errors = array();

        $diff = $this->getDiffStock(0);
        foreach ($diff as $id => $data) {
            $doli_prod = new Product($this->db->db);
            $doli_prod->fetch($id);
            if((float) $data['nb_scan'] != (float) $data['stock'])
                $errors[] = 'Le produit ' . $doli_prod->ref . ' a été scanné <strong>' . (float) $data['nb_scan'] .
                        '</strong> fois et il est présent <strong>' . (float) $data['stock'] . '</strong> fois dans le stock ' .
                        ' il va être modifié de <strong>' . (float) $data['diff'] . '</strong>.';
        }

        return $errors;
    }
    
    public function isAdmin() {
        global $user;
        if($user->rights->bimpequipment->inventory->close)
            return 1;
        return 0;
    }

    private function correctProducts()
    {
        global $user;
        $errors = array();

        $tab_diff = $this->getDiffStock(0);
        $id_main_warehouse = $this->getWarehouseInventories();
        if($id_main_warehouse < 1) {
            $errors[] = "L'entrepôt par défault des inventaires n'est pas définit "
                    . "(celui dans lequel on renseigne tous les mouvements de "
                    . "tou les inventaires. (il doit avoir comme ref \"INV\")";
            return $errors;
        }
        
        $codemove = 'inventory-id-' . $this->getData('id');
        foreach ($tab_diff as $id => $data) {
            $diff = $data['diff'];
            if ($diff != 0) { // add or remove
                $doli_prod = new Product($this->db->db);
                $doli_prod->fetch($id);
                $label = 'Inventaire-' . $this->getData('id') . '-Produit"' . $doli_prod->ref . '"';

                if ($diff < 0) { // remove
                    $result = $doli_prod->correct_stock($user, $this->getData('fk_warehouse'), -$diff, 1, $label, 0, $codemove, 'entrepot', $this->getData('fk_warehouse'));
                    $result = $doli_prod->correct_stock($user, $id_main_warehouse, -$diff, 0, $label, 0, $codemove, 'entrepot', $id_main_warehouse);
                } else { // add
                    $result = $doli_prod->correct_stock($user, $this->getData('fk_warehouse'), $diff, 0, $label, 0, $codemove, 'entrepot', $this->getData('fk_warehouse'));
                    $result = $doli_prod->correct_stock($user, $id_main_warehouse, $diff, 1, $label, 0, $codemove, 'entrepot', $id_main_warehouse);
                }
                if ($result == -1)
                    $errors = array_merge($errors, $doli_prod->errors);
            }
        }

        return $errors;
    }

    public function renderStock()
    {

        $filters = array(
            'or' => array(
                'ps.reel'     => array(
                    'operator' => '!=',
                    'value'    => 0
                ),
                'inv_det.qty' => array(
                    'operator' => '!=',
                    'value'    => 0
                ),
            )
        );

        $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
        $list = new BC_ListTable($product, 'inventory');
        $list->addFieldFilterValue('rien', $filters);
        $list->addJoin('bl_inventory_det', 'a.rowid = inv_det.fk_product AND inv_det.fk_inventory = ' . $this->getData('id'), 'inv_det');
        $list->addJoin('product_stock', 'a.rowid = ps.fk_product AND ps.fk_entrepot = ' . $this->getData('fk_warehouse'), 'ps');
        $html .= $list->renderHtml();


        return $html;
    }

    public function renderDiff()
    {

        $filters = array(
            'operator' => '!=',
            'value'    => "inv_det.qty"
        );

        $sql = ' SELECT SUM(inv_det.qty) as qty FROM ' . MAIN_DB_PREFIX . 'bl_inventory_det as inv_det';
        $sql .= ' WHERE inv_det.fk_product = a.rowid AND inv_det.fk_inventory = ' . $this->getData('id');
        $sql .= ' GROUP BY inv_det.fk_product';

        $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
        $list = new BC_ListTable($product, 'inventory');
        $list->addFieldFilterValue('(ps.reel != (' . $sql . ') OR (ps.reel IS NULL AND  (' . $sql . ') > 0 ) OR (ps.reel != 0 AND  (' . $sql . ') IS NULL))  AND 1', $filters);
        $list->addJoin('product_stock', 'a.rowid = ps.fk_product AND ps.fk_entrepot = ' . $this->getData('fk_warehouse'), 'ps');
        $list->addJoin('product_extrafields', 'a.rowid = pe.fk_object', 'pe');
        $html .= $list->renderHtml();

        $diff = $this->getDiffEquipment();

        $equipment = BimpObject::getInstance('bimplogistique', 'InventoryEquipment');
        $list = new BC_ListTable($equipment, 'inventaireManquant', 1, null, 'Équipements manquants');
        if (!empty($diff['ids_manquant']))
            $list->addFieldFilterValue('id IN(' . implode(',', $diff['ids_manquant']) . ') AND 1', $filters);
        else
            $list->addFieldFilterValue('id = 0 AND 1', $filters);
        $html .= $list->renderHtml();

        $equipment = BimpObject::getInstance('bimplogistique', 'InventoryEquipment');
        $list = new BC_ListTable($equipment, 'inventaireEnTrop', 1, null, 'Équipements en trop');
        if (!empty($diff['ids_en_trop']))
            $list->addFieldFilterValue('id IN(' . implode(',', $diff['ids_en_trop']) . ') AND 1', $filters);
        else
            $list->addFieldFilterValue('id = 0 AND 1', $filters);
        $html .= $list->renderHtml();
        
        
        $equipment = BimpObject::getInstance('bimplogistique', 'InventoryEquipment');
        $list = new BC_ListTable($equipment, 'inventaire', 1, null, 'Équipements deplacé dans vols');
        $list->addFieldFilterValue('id IN (SELECT id_equipment FROM ' . MAIN_DB_PREFIX . 'be_equipment_place p WHERE id_entrepot=' . $this->getData('fk_warehouse').' AND infos LIKE "%INV'.$this->id.'%" AND  p.position=1 AND p.type=6) AND 1', $filters);
        $html .= $list->renderHtml();


        return $html;
    }

    public function renderDifferenceProduct()
    {
        $html = '';

        foreach ($this->getErrorsProduct() as $error) {
            $html .= BimpRender::renderAlerts($error, 'warning');
        }

        return $html;
    }
    
    public function renderDifferenceEquipment()
    {
        $html = '';
        if (!$this->equipmentIsOk()) {
            foreach ($this->getErrorsEquipment() as $error) {
                $html .= BimpRender::renderAlerts($error);
            }
        }

        return $html;
    }

    public function equipmentIsOk()
    {
        $diff_eq = $this->getDiffEquipment();
        if (count($diff_eq['ids_en_trop']) > 0 or count($diff_eq['ids_manquant']) > 0)
            return false;
        return true;
    }

    public function getDiffStock($with_serialisable = 1)
    { // non sérialisé
        $ids_stock = array();
        $ids_scanned = array();

        if($with_serialisable) {
            $sql1 = 'SELECT reel, fk_product';
            $sql1 .= ' FROM ' . MAIN_DB_PREFIX . 'product_stock';
            $sql1 .= ' WHERE fk_entrepot=' . $this->getData('fk_warehouse');
        } else {        
            $sql1 = 'SELECT ps.reel AS reel, ps.fk_product AS fk_product';
            $sql1 .= ' FROM ' . MAIN_DB_PREFIX . 'product_stock AS ps';
            $sql1 .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product_extrafields AS pe ON pe.fk_object=ps.fk_product';
            $sql1 .= ' WHERE ps.fk_entrepot=' . $this->getData('fk_warehouse');
            $sql1 .= ' AND (serialisable=0 OR serialisable IS NULL)';
        }
        

        $result1 = $this->db->db->query($sql1);
        if ($result1 and mysqli_num_rows($result1) > 0) {
            while ($obj1 = $this->db->db->fetch_object($result1)) {
                $ids_stock[$obj1->fk_product] = $obj1->reel;
            }
        }

        $sql2 = 'SELECT SUM(qty) as sum, fk_product';
        $sql2 .= ' FROM ' . MAIN_DB_PREFIX . 'bl_inventory_det';
        $sql2 .= ' WHERE fk_inventory=' . $this->getData('id');
        if(!$with_serialisable)
            $sql2 .= ' AND fk_equipment=0';
        $sql2 .= ' GROUP BY (fk_product)';


        $result2 = $this->db->db->query($sql2);
        if ($result2 and mysqli_num_rows($result2) > 0) {
            while ($obj2 = $this->db->db->fetch_object($result2)) {
                $ids_scanned[$obj2->fk_product] = $obj2->sum;
            }
        }

        $diff = array();

        foreach ($ids_stock as $id_prod => $qty_stock) {
            if (!isset($ids_scanned[$id_prod])) {
                $diff[$id_prod] = array('stock' => $qty_stock, 'nb_scan' => 0, 'diff' => -$qty_stock);
            } else {
                $diff[$id_prod] = array('stock' => $qty_stock, 'nb_scan' => $ids_scanned[$id_prod], 'diff' => $ids_scanned[$id_prod] - $qty_stock);
            }
        }

        foreach ($ids_scanned as $id_prod => $qty_scan) {
            if (!isset($ids_stock[$id_prod])) {
                $diff[$id_prod] = array('stock' => 0, 'nb_scan' => $qty_scan, 'diff' => $qty_scan);
            }
        }
        
        return $diff;
    }
    
    /**
     * @return tous les id d'équipements qui doivent être dans l'entrepôt de l'inventaire
     */
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
    
    public function getEquipmentScanned() {
        $ids_scanned = array();

        $sql2 = 'SELECT fk_equipment';
        $sql2 .= ' FROM ' . MAIN_DB_PREFIX . 'bl_inventory_det id, ' . MAIN_DB_PREFIX . 'be_equipment e';
        $sql2 .= ' WHERE fk_inventory=' . $this->getData('id');
        $sql2 .= ' AND e.id = id.fk_equipment';
        $sql2 .= ' AND fk_equipment > 0';

        $result2 = $this->db->db->query($sql2);
        if ($result2 and mysqli_num_rows($result2) > 0) {
            while ($obj = $this->db->db->fetch_object($result2)) {
                $ids_scanned[$obj->fk_equipment] = $obj->fk_equipment;
            }
        }
        return $ids_scanned;
    }

    public function getDiffEquipment()
    {

        $ids_place = $this->getEquipmentExpected();
        $ids_scanned = $this->getEquipmentScanned();

        $ids_en_trop = array();
        $ids_manquant = array();

        foreach ($ids_place as $id_place) {
            if (!isset($ids_scanned[$id_place])) {
                $ids_manquant[] = $id_place;
            }
        }

        foreach ($ids_scanned as $id_scanned) {
            if (!isset($ids_place[$id_scanned])) {
                $ids_en_trop[] = $id_scanned;
            }
        }
        
        foreach($ids_en_trop as $i => $id_equipment) {
            $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
            $filters = array(
                'position'     => 1,
                'id_equipment' => $id_equipment
            );
            $list = $place->getList($filters, 1, 1, 'id', 'desc', 'array', array(
                'type',
                'date'
            ));
            if($list[0]['type'] == BE_Place::BE_PLACE_CLIENT and $this->getData('date_create') < $list[0]['date'])
                unset($ids_en_trop[$i]);
        }
        return array('ids_en_trop' => $ids_en_trop, 'ids_manquant' => $ids_manquant);
    }

    public function renderInputs()
    {
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

    public function displayMouvementTrace()
    {

        if (self::STATUS_PARTIALLY_CLOSED <= $this->getData('status')) {
            $url = DOL_URL_ROOT . '/product/stock/mouvement.php?search_movement=Inventaire-' . $this->getData('id') . '-&search_warehouse=' . $this->getData('fk_warehouse');
            return '<a href="' . $url . '">Voir</a>';
        }

        return "Disponible à la fermeture partielle de l'inventaire";
    }
    
    public function hasChildren() {
        if($this->isLoaded()){
            $sql = 'SELECT id';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bl_inventory';
            $sql .= ' WHERE parent=' . $this->getData('id');

            $result = $this->db->db->query($sql);
            if ($result and mysqli_num_rows($result) > 0) {
                while ($obj = $this->db->db->fetch_object($result)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public function getChildren() {
        $children = array();
        
        $sql = 'SELECT id';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bl_inventory';
        $sql .= ' WHERE parent=' . $this->getData('id');

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $children[] = BimpCache::getBimpObjectInstance($this->module, 'Inventory', (int) $obj->id);
            }
        }
        
        return $children;
    }
    
    public function qtyMissing($id_product, $id_equipment) {
        
        if($id_equipment > 0) {
            $diff = $this->getDiffEquipment();
            if(isset($diff['ids_manquant'][$id_equipment]) and $diff['ids_manquant'][$id_equipment] > 0)
                return 1;
        } else {
            $diff = $this->getDiffStock();
            if(isset($diff[$id_product]['diff']))
                return $diff[$id_product]['diff'];
        }

        return 0;
    }
    
    public function createLinesProduct($id_product, $qty_input) {
        $errors = array();
        $msg = '';
        $id_inventory_det = 0;
        
        if($qty_input < 0) {
            $out = $this->createLine($id_product, 0, $qty_input);
            $id_inventory_det = $out['id_inventory_det'];
            $errors = array_merge($errors, $out['errors']);
            $msg .= $this->getMessageAdd($qty_input);
            return array('id_inventory_det' => $id_inventory_det, 'msg' => $msg, 'errors' => $errors);
        }

        $qty_missing = -$this->qtyMissing($id_product, 0);
        
        
        $diff = $qty_missing - $qty_input;
        if($qty_missing > 0) { // On en met le plus possible dans l'entrepôt de cet inventaire
            if($diff > 0) // On en attends plus que ce qui est scanné
                $qty_insert = $qty_input;
            else // On en attends moins ou on a atteint la bonne quantité
                $qty_insert = $qty_missing;
            
            $out = $this->createLine($id_product, 0, $qty_insert);
            $id_inventory_det = $out['id_inventory_det'];
            $errors = array_merge($errors, $out['errors']);
            $msg .= $this->getMessageAdd($qty_insert);
        }

        $qty_input -= $qty_insert;
        if ($diff < 0) { // Il en reste à répartir dans les autres entrepôt
            $inv_children = $this->getChildren();
            
            foreach($inv_children as $child) { // Itération sur les enfants de l'inventaire
                
                if($qty_input < 1) // Test d'arrêt
                    break;
                
                $qty_missing = -$child->qtyMissing($id_product, 0);
                if($qty_missing > 0) { // Cet entrepôt possède ce produit
                    
                    if($qty_input > $qty_missing)
                        $qty_insert = $qty_missing;
                    else
                        $qty_insert = $qty_input;

                    $out = $child->createLine($id_product, 0, $qty_insert);
                    $errors = array_merge($errors, $out['errors']);
                    $msg .= $child->getMessageAdd($qty_insert, 1);
                    $qty_input -= $qty_insert;
                }
            }            
        }
        
        if($qty_input > 0) {
            if($id_inventory_det > 0){
                $inv_det = BimpCache::getBimpObjectInstance($this->module, 'InventoryLine', $id_inventory_det);
                $inv_det->updateField('qty', ($inv_det->getData('qty') + $qty_input));
            }
            else{
                $out = $this->createLine($id_product, 0, $qty_input);
                $id_inventory_det = $out['id_inventory_det'];
                $errors = array_merge($errors, $out['errors']);
            }
            $msg .= $this->getMessageAdd($qty_input);
        }

        return array('id_inventory_det' => $id_inventory_det, 'msg' => $msg, 'errors' => $errors);
    }
    
    public static function equipmentIsScanned($id_equipment, $id_inventory) {
        $filters = array(
            'fk_equipment' => $id_equipment,
            'fk_inventory' => $id_inventory
        );
        $inventory_lines_obj = BimpObject::getInstance('bimplogistique', 'InventoryLine');
        $list = $inventory_lines_obj->getList($filters, 1, 1, 'id', 'desc', 'array', array());
        return count($list);
    }
    
    public function createLinesEquipment($id_product, $id_equipment) {
        $id_inventory_det = 0;
        $errors = array();
        $msg = '';
        $filters = array(
            'position'     => 1,
            'id_equipment' => $id_equipment
        );
        $place = BimpObject::getInstance('bimpequipment', 'BE_Place');
        $list = $place->getList($filters, 1, 1, 'id', 'desc', 'array', array(
            'id_entrepot',
            'type'
        ));
        
        $id_entrepot = (int) $list[0]['id_entrepot'];
        $type = (int) $list[0]['type'];

        $inserted = false;
        // Si l'équipmenent n'est pas dans un entrepot ou si il est dans celui de l'inventaire
        if(!$id_entrepot > 0 or $type != 2 or $this->getData('fk_warehouse') == $id_entrepot) {
            if(self::equipmentIsScanned($id_equipment, $this->getData('id'))) {
                $errors[] = "Cet équipement a déjà été scanné";
                return array('id_inventory_det' => 0, 'msg' => '', 'errors' => $errors);
            }
            $out = $this->createLine($id_product, $id_equipment, 1);
            $errors = array_merge($errors, $out['errors']);
            $msg .= $this->getMessageAdd(1);
            $id_inventory_det = $out['id_inventory_det'];
            $inserted = true;
        } else {
            $inv_children = $this->getChildren();
            foreach($inv_children as $child) {
                if($child->getData('fk_warehouse') == $id_entrepot) {
                    if(self::equipmentIsScanned($id_equipment, $child->getData('id'))) {
                         $errors[] = "Cet équipement a déjà été scanné";
                         return array('id_inventory_det' => 0, 'msg' => '', 'errors' => $errors);
                     }
                    $out = $child->createLine($id_product, $id_equipment, 1);
                    $errors = array_merge($errors, $out['errors']);
                    $msg .= $child->getMessageAdd(1, 1);
                    $inserted = true;
                    break;
                }
            }
        }
        
        // L'équipement n'a été ajouté nulle part
        if(!$inserted) {
            if(self::equipmentIsScanned($id_equipment, $this->getData('id'))) {
                 $errors[] = "Cet équipement a déjà été scanné";
                 return array('id_inventory_det' => 0, 'msg' => '', 'errors' => $errors);
             }
            $out = $this->createLine($id_product, $id_equipment, 1);
            $errors = array_merge($errors, $out['errors']);
            $msg .= $this->getMessageAdd(1);
        }

        return array('id_inventory_det' => $id_inventory_det, 'msg' => $msg, 'errors' => $errors);
    }
    
    public function getMessageAdd($qty, $bold = false) {
        $s = ($qty > 1 ? "s" : "");
        if($bold)
            return $qty . " produit$s ajouté$s à <strong>" . $this->getEntrepotRef(). '</strong><br/>';
        return $qty . " produit$s ajouté$s à " . $this->getEntrepotRef(). '<br/>';
    }
    
    public function createLine($id_product, $id_equipment, $qty) {
        $errors = array();
        $inventory_line = BimpObject::getInstance($this->module, 'InventoryLine');

        $errors = array_merge($errors, $inventory_line->validateArray(array(
            'fk_inventory' => (int) $this->getData('id'),
            'fk_product'   => (int) $id_product,
            'fk_equipment' => (int) $id_equipment,
            'qty'          => $qty
        )));

        if (!count($errors)) {
            $errors = array_merge($errors, $inventory_line->create());
        } else {
            $errors[] = "Erreur lors de la validation des données renseignées";
        }
        return array('id_inventory_det' => $inventory_line->db->db->last_insert_id(), 'errors' => $errors);
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
    
    public function renderHeaderExtraLeft() {
        $html = '';
        $parent = 0;
        
        $sql = 'SELECT CONCAT(e.ref, " ", e.lieu) as nom, i.parent as parent';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bl_inventory AS i';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'entrepot AS e ON i.fk_warehouse=e.rowid';
        $sql .= ' WHERE i.id=' . $this->getData('id');

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $parent = (int) $obj->parent;
                $html .= 'Entrepot : <strong>' . $obj->nom . '</strong>';
            }
        }
        
        if($parent > 0) {
            $sql = 'SELECT CONCAT(e.ref, " ", e.lieu) as nom';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'entrepot AS e';
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bl_inventory AS i ON i.fk_warehouse=e.rowid';
            $sql .= ' WHERE i.id=' . $this->getData('parent');

            $result = $this->db->db->query($sql);
            if ($result and mysqli_num_rows($result) > 0) {
                while ($obj = $this->db->db->fetch_object($result)) {
                    $html .= '</br>Rattaché à : <strong>' . $obj->nom . '</strong>';
                }
            }
        }

        return $html;
    }
    
    /**
     * @return tous les id d'équipements qui doivent être dans l'entrepôt de l'inventaire
     */
    public function getEquipmentExpectedByProduct($id_product) {
        $ids_equipment = array();

        $sql = 'SELECT e.id AS id_equipment';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment AS e';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place AS epl ON e.id=epl.id_equipment';
        $sql .= ' WHERE epl.id_entrepot=' . $this->getData('fk_warehouse');
        $sql .= ' AND epl.position=1 AND epl.type=2';
        $sql .= ' AND e.id_product=' . $id_product;

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $ids_equipment[$obj->id_equipment] = $obj->id_equipment;
            }
        }
        
        return $ids_equipment;
    }
    
    public function createMultipleEquipment($id_product, $qty) {
        global $user;
        $errors = array();
        $ids_inventory_det = array();
        $msg = '';
        
        if(0 < (int) self::equipmentIsScannedByProduct($id_product, $this->getData('id'))) {
            if ($user->rights->bimpequipment->inventory->create)
                $errors[] = "Au moins un équipement de ce produit à déjà été scanné. "
                        . "Pour faire un scan d'équipement en masse, merci de supprimer"
                        . " les lignes de ce produit, puis réessayez.(quantité normale :"
                        . sizeof($this->getEquipmentExpectedByProduct($id_product)) . ')';
            else
                $errors[] = "Au moins un équipement de ce produit à déjà été scanné. "
                        . "Pour faire un scan d'équipement en masse, merci de demander au responsable"
                        . " de supprimer les lignes de ce produit, puis réessayez.";
        } else {
            $ids_equipment = $this->getEquipmentExpectedByProduct($id_product);

            if($qty == sizeof($ids_equipment)) {
                foreach($ids_equipment as $id_equipment) {
                    $tab = $this->createLine($id_product, $id_equipment, 1);
                    $errors = array_merge($errors, $tab[$errors]);
                    $ids_inventory_det = $tab['id_inventory_det'];
                }
            }

            if(!count($errors))
                $msg .= 'Ajout de <strong>' . $qty . '</strong> équipement';
        }
        
        return array('id_inventory_det' => $ids_inventory_det, 'errors' => $errors, 'msg' => $msg);
    }
    
    public static function equipmentIsScannedByProduct($id_product, $id_inventory) {
        $filters = array(
            'fk_product'   => $id_product,
            'fk_inventory' => $id_inventory
        );
        
        $inventory_lines_obj = BimpObject::getInstance('bimplogistique', 'InventoryLine');
        $list = $inventory_lines_obj->getList($filters, 1, 1, 'id', 'desc', 'array', array());
        return count($list);
    }
    
    public function getWarehouseInventories() {
        
        $sql = 'SELECT rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'entrepot';
        $sql .= ' WHERE ref="INV"';

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                return $obj->rowid;
            }
        }
        
        return -1;
    }

}
