<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class Inventory extends BimpDolObject {

    CONST STATUS_DRAFT = 0;
    CONST STATUS_OPEN = 1;
    CONST STATUS_CLOSED = 2;

    public static $status_list = Array(
        self::STATUS_DRAFT => Array('label' => 'Brouillon', 'classes' => Array('success'), 'icon' => 'fas_cogs'),
        self::STATUS_OPEN => Array('label' => 'Ouvert', 'classes' => Array('warning'), 'icon' => 'fas_arrow-alt-circle-down'),
        self::STATUS_CLOSED => Array('label' => 'Fermé', 'classes' => Array('danger'), 'icon' => 'fas_times')
    );

    public function update(&$warnings = array(), $force_update = false) {
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
        } elseif ($status == self::STATUS_CLOSED) {
            $this->updateField("date_closing", date("Y-m-d H:i:s"));
        } else {
            $warnings[] = "Statut non reconnu, valeur = " . $status;
        }

        if (sizeof($warnings) == 0)
            $errors = parent::update($warnings);

        return $errors;
    }

    public function getActionsButtons() {
        global $user;
        $buttons = array();
        if (!$this->isLoaded())
            return $buttons;

        if ($this->getData('status') == self::STATUS_DRAFT) {
            if ($user->admin or $user->rights->bimpequipment->inventory->open) {
                $buttons[] = array(
                    'label' => 'Commencer l\'inventaire',
                    'icon' => 'fas_box',
                    'onclick' => $this->getJsActionOnclick('setSatus', array("status" => self::STATUS_OPEN), array())
                );
            }
        }

        if ($this->getData('status') == self::STATUS_OPEN) {

            if ($user->admin or $user->rights->bimpequipment->inventory->close) { // read et create
                $buttons[] = array(
                    'label' => 'Fermer l\'inventaire',
                    'icon' => 'fas_window-close',
                    'onclick' => $this->getJsActionOnclick('setSatus', array("status" => self::STATUS_CLOSED), array('form_name' => 'confirm_close'))
                );
            }
        }

        return $buttons;
    }

    public function actionSetSatus($data = array(), &$success = '') {
        $errors = array();

        $date_mouvement =  BimpTools::getPostFieldValue('date_mouvement');

        if ((int) $data['status'] == self::STATUS_CLOSED) {
            $errors = array_merge($errors, $this->close());
        }

        if (!$this->setDateMouvement($date_mouvement))
            $errors[] = "Erreur lors de la définition de la date du mouvement";

        if (!count($errors)) {
            $this->updateField("status", $data['status']);
            $errors = array_merge($errors, $this->update());
        }

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

    public function getLines($ret_object = false) {
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

    public function create(&$warnings = array(), $force_create = false) {
        global $user;

        if (!$user->bimpequipment->inventory->create and ! $user->admin) {
            $warnings[] = "Vous n'avez pas les droits de créer un inventaire.";
            return;
        }

        $filters = array(
            'fk_warehouse' => array(
                'operator' => '=',
                'value' => $this->getData('fk_warehouse')
            ),
            'status' => array(
                'operator' => '<',
                'value' => self::STATUS_CLOSED
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

    public function close() {
        $errors = array();

        if ($this->equipmentIsOk()) {
            $errors = array_merge($errors, $this->correctProducts());
        } else {
            $errors = array_merge($errors, $this->getErrorsEquipment());
        }

        if (!count($errors))
            $errors = $this->correctProducts();

        return $errors;
    }

    private function getErrorsEquipment() {
        $errors = array();

        $diff_eq = $this->getDiffEquipment();
        // Excès
        $nb_en_trop = count($diff_eq['ids_en_trop']);
        if ($nb_en_trop == 1)
            $errors[] = 'Merci de traiter le cas du produit sérialisé en excès.';
        if ($nb_en_trop > 1)
            $errors[] = 'Merci de traiter le cas des ' . $nb_en_trop . ' produits sérialisés en excès.';
        // Manque
        $nb_manquant = count($diff_eq['ids_manquant']);
        if ($nb_manquant == 1)
            $errors[] = 'Merci de traiter le cas du produit sérialisé manquant.';
        if ($nb_manquant > 1)
            $errors[] = 'Merci de traiter le cas des ' . $nb_manquant . ' produits sérialisés manquants.';
        return $errors;
    }

    private function getErrorsProduct() {
        $errors = array();

        $diff = $this->getDiffStock();
        foreach ($diff as $id => $data) {
            $doli_prod = new Product($this->db->db);
            $doli_prod->fetch($id);
            $errors[] = 'Le produit ' . $doli_prod->ref . ' a été scanné <strong>' . (float) $data['nb_scan'] .
                    '</strong> fois et il est présent <strong>' . (float) $data['stock'] . '</strong> fois dans le stock ' .
                    ' il va être modifié de <strong>' . (float) $data['diff'] . '</strong>.';
        }

        return $errors;
    }

    private function correctProducts() {
        global $user;
        $errors = array();

        $tab_diff = $this->getDiffStock();

        $codemove = 'inventory-id-' . $this->getData('id');
        foreach ($tab_diff as $id => $data) {
            $diff = $data['diff'];
            if ($diff != 0) { // add or remove
                $doli_prod = new Product($this->db->db);
                $doli_prod->fetch($id);
                $label = 'Inventaire-' . $this->getData('id') . '-Produit"' . $doli_prod->ref . '"';

                if ($diff < 0) { // remove
                    $result = $doli_prod->correct_stock($user, $this->getData('fk_warehouse'), -$diff, 1, $label, 0, $codemove, 'entrepot', $this->getData('fk_warehouse'));
                } else { // add
                    $result = $doli_prod->correct_stock($user, $this->getData('fk_warehouse'), $diff, 0, $label, 0, $codemove, 'entrepot', $this->getData('fk_warehouse'));
                }
                if ($result == -1)
                    $errors = array_merge($errors, $doli_prod->errors);
            }
        }

        return $errors;
    }

    public function renderStock() {

        $filters = array(
            'or' => array(
                'ps.reel' => array(
                    'operator' => '!=',
                    'value' => 0
                ),
                'inv_det.qty' => array(
                    'operator' => '!=',
                    'value' => 0
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

    public function renderDiff() {

        $filters = array(
            'operator' => '!=',
            'value' => "inv_det.qty"
        );

        $sql = ' SELECT SUM(inv_det.qty) as qty FROM ' . MAIN_DB_PREFIX . 'bl_inventory_det as inv_det';
        $sql .= ' WHERE inv_det.fk_product = a.rowid AND inv_det.fk_inventory = ' . $this->getData('id') . ' AND inv_det.error = 0';
        $sql .= ' GROUP BY inv_det.fk_product';

        $product = BimpObject::getInstance('bimpcore', 'Bimp_Product');
        $list = new BC_ListTable($product, 'inventory');
        $list->addFieldFilterValue('(ps.reel != (' . $sql . ') OR (ps.reel IS NULL AND  (' . $sql . ') > 0 ) OR (ps.reel != 0 AND  (' . $sql . ') IS NULL)) AND 1', $filters);
        $list->addJoin('product_stock', 'a.rowid = ps.fk_product AND ps.fk_entrepot = ' . $this->getData('fk_warehouse'), 'ps');
        $html .= $list->renderHtml();

        $diff = $this->getDiffEquipment();

        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
        $list = new BC_ListTable($equipment, 'default', 1, null, 'Équipements manquants');
        if (!empty($diff['ids_manquant']))
            $list->addFieldFilterValue('id IN(' . implode(',', $diff['ids_manquant']) . ') AND 1', $filters);
        else
            $list->addFieldFilterValue('id = 0 AND 1', $filters);
        $html .= $list->renderHtml();

        $equipment = BimpObject::getInstance('bimpequipment', 'Equipment');
        $list = new BC_ListTable($equipment, 'default', 1, null, 'Équipements en trop');
        if (!empty($diff['ids_en_trop']))
            $list->addFieldFilterValue('id IN(' . implode(',', $diff['ids_en_trop']) . ') AND 1', $filters);
        else
            $list->addFieldFilterValue('id = 0 AND 1', $filters);
        $html .= $list->renderHtml();


        return $html;
    }

    public function displayDifference() {
        $html = '';
        if (!$this->equipmentIsOk()) {
            foreach ($this->getErrorsEquipment() as $error) {
                $html .= BimpRender::renderAlerts($error);
            }
        }


        foreach ($this->getErrorsProduct() as $error) {
            $html .= BimpRender::renderAlerts($error, 'warning');
        }

        return $html;
    }

    public function equipmentIsOk() {
        $diff_eq = $this->getDiffEquipment();
        if (count($diff_eq['ids_en_trop']) > 0 or count($diff_eq['ids_manquant']) > 0)
            return false;
        return true;
    }

    public function getDiffStock() { // non sérialisé
        $ids_stock = array();
        $ids_scanned = array();

        $sql1 = 'SELECT reel, fk_product';
        $sql1 .= ' FROM ' . MAIN_DB_PREFIX . 'product_stock';
        $sql1 .= ' WHERE fk_entrepot=' . $this->getData('fk_warehouse');

        $result1 = $this->db->db->query($sql1);
        if ($result1 and mysqli_num_rows($result1) > 0) {
            while ($obj1 = $this->db->db->fetch_object($result1)) {
                $ids_stock[$obj1->fk_product] = $obj1->reel;
            }
        }

        $sql2 = 'SELECT SUM(qty) as sum, fk_product';
        $sql2 .= ' FROM ' . MAIN_DB_PREFIX . 'bl_inventory_det';
        $sql2 .= ' WHERE fk_inventory=' . $this->getData('id');
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

    public function getDiffEquipment() {
        $ids_place = array();
        $ids_scanned = array();

        $sql = 'SELECT id_equipment';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment_place';
        $sql .= ' WHERE id_entrepot=' . $this->getData('fk_warehouse');
        $sql .= ' AND position=1 AND type=2';

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $ids_place[$obj->id_equipment] = $obj->id_equipment;
            }
        }

        $sql = 'SELECT fk_equipment';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'bl_inventory_det';
        $sql .= ' WHERE fk_inventory=' . $this->getData('id');

        $result = $this->db->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $ids_scanned[$obj->fk_equipment] = $obj->fk_equipment;
            }
        }

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

        return array('ids_en_trop' => $ids_en_trop, 'ids_manquant' => $ids_manquant);
    }

    public function renderInputs() {
        global $user; // TODO droit
        $html = '';

        if ($this->isLoaded()) {
            if ((int) $this->getData('status') != (int) self::STATUS_OPEN) {
                $html = BimpRender::renderAlerts('Le statut de l\'inventaire ne permet pas d\'ajouter des lignes', 'info');
            } else {
                $header_table = '<span style="margin-left: 100px">Ajouter</span>';
                $header_table .= BimpInput::renderInput('search_product', 'insert_line', '', array('filter_type' => 'both'));

                $header_table .= '<span style="margin-left: 100px">Quantité</span>';
                $header_table .= '<input class="search_list_input"  name="insert_quantity" type="number" min=1 style="width: 80px; margin-left: 10px;" value="1" >';

                $html = BimpRender::renderPanel($header_table, $html, '', array(
                            'foldable' => false,
                            'type' => 'secondary',
                            'icon' => 'fas_plus-circle',
                ));
            }
        }

        return $html;
    }

    public function displayMouvementTrace() {

        if ($this->getData('status') == self::STATUS_CLOSED) {
            $url = DOL_URL_ROOT . '/product/stock/mouvement.php?search_inventorycode=inventory-id-' . $this->getData('id');
            return '<a href="' . $url . '">Voir</a>';
        }

        return "Disponible à la fermeture de l'inventaire";
    }

}
