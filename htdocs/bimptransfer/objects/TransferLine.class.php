<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class TransferLine extends BimpObject {

    public function create_2($id_transfer, $id_product, $id_equipment, $quantity_input, &$id_affected, $id_warehouse_source) {
        global $user;
        $now = dol_now();

        // Create reservation
        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
        // Is equipment
        if ($id_equipment > 0) {
            $errors = array_merge($errors, $reservation->validateArray(array(
                        'id_entrepot' => $id_warehouse_source,
                        'status' => 201, // Transfert en cours
                        'type' => 2, // 2 = transfert
                        'id_equipment' => $id_equipment,
//                        'id_product' => $id_product,
                        'id_transfert' => $id_transfer,
                        'date_from' => dol_print_date($now, '%Y-%m-%d %H:%M:%S'),
            )));
            // Is product
        } else {
            $errors = array_merge($errors, $reservation->validateArray(array(
                        'id_entrepot' => $id_warehouse_source,
                        'status' => 201, // Transfert en cours
                        'type' => 2, // 2 = transfert
                        'id_product' => $id_product,
                        'id_transfert' => $id_transfer,
                        'qty' => $quantity_input,
                        'date_from' => dol_print_date($now, '%Y-%m-%d %H:%M:%S'),
            )));
        }
        $errors = array_merge($errors, $reservation->create());

        // Create transfer line
        $errors = array_merge($errors, $this->validateArray(array(
                    'user_create' => $user->id,
                    'user_update' => $user->id,
                    'id_product' => $id_product,
                    'id_equipment' => $id_equipment,
                    'id_transfer' => $id_transfer,
                    'quantity_sent' => $quantity_input,
                    'quantity_received' => 0
        )));

        if (!$errors) {
            $errors = array_merge($errors, parent::create());
            $id_affected = $this->db->db->last_insert_id();
        }
        return $errors;
    }

    public function canDelete() {
        if ($this->getData("quantity_transfered") == 0 && $this->getData("quantity_received") == 0)
            return 1;
        return 0;
    }
    
    public function cancelReservation(){
        $errors = array();
        $tabReservations = $this->getReservations();
        foreach ($tabReservations as $reservation)
            $errors = array_merge($errors, $reservation->setNewStatus(303));
        return $errors;
    }

    private function getReservations() {
        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
        $filtre = array('id_transfert' => $this->getData('id_transfer'), 'status' => array("operator" => "!=", "value" => 303));
        if ($this->getData('id_equipment'))
            $filtre['id_equipment'] = $this->getData('id_equipment');
        else
            $filtre['id_product'] = $this->getData('id_product');
        $r_lines = $reservation->getList($filtre, null, null, 'date_from', 'desc', 'array', array(
            'id',
            'qty'
        ));
        $tabs = array();
        foreach ($r_lines as $r_line) {
            $tabs[] = BimpCache::getBimpObjectInstance('bimpreservation', 'BR_Reservation', $r_line['id']);
        }
        return $tabs;
    }

    public function updateReservation($force_close = false) {
        $errors = array();
        $tabReservations = $this->getReservations();
        $i = 0;
        
        if ($force_close)
            $nb_reservation = 0;
        else
            $nb_reservation = (int) $this->getData('quantity_sent') - $this->getData('quantity_received');
        
        foreach ($tabReservations as $reservation) {
            $new_status = ($nb_reservation == 0) ? '301' : '201';
            if ($i > 0) {
                $nb_reservation = 0;
                $new_status = "303";
            }

            if ($nb_reservation > 0)
                $reservation->updateField('qty', $nb_reservation);
            elseif($force_close)
                $reservation->updateField('qty', 0);
            $i++;
            if ($new_status != $reservation->getInitData('status')) {
                $errors = array_merge($errors, $reservation->setNewStatus($new_status, $nb_reservation)); // $qty : faculatif, seulement pour les produits non sérialisés
                $errors = array_merge($errors, $reservation->update());
            }
            if (sizeof($errors) > 0)
                return $errors;
        }
    }

    public function checkInput($input, &$id_product, &$id_equipment) {
        $errors = array();
        if (!$this->isEquipment($input, $id_equipment) and ! $this->isProduct($input, $id_product))
            $errors[] = "Produit inconnu";
        else if ($this->isSerialisable($id_product))
            $errors[] = "Veuillez scanner le numéro de série au lieu de la référence.";
        return $errors;
    }

    public function isProduct($search, &$id_product) {
        $sql = 'SELECT rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product';
        $sql .= ' WHERE ref="' . $search . '"';
        $sql .= ' OR ref="' . str_replace("/", "_", $search) . '"';
        $sql .= ' OR ref LIKE "%' . $search . '"';
        $sql .= ' OR ref LIKE "%' . str_replace("/", "_", $search) . '"';
        $sql .= ' OR barcode="' . $search . '"';

        $rows = $this->db->executeS($sql, 'array');
        if (!is_null($rows)) {
            foreach ($rows as $row) {
                $id_product = $row['rowid'];
                return true;
            }
        }
        return false;
    }

    public function isEquipment($input, &$id_equipment) {
        $sql = 'SELECT id';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
        $sql .= ' WHERE serial="' . $input . '" || concat("S", serial)="' . $input . '"';

        $result = $this->db->db->query($sql);
        if ($result and $this->db->db->num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $id_equipment = $obj->id;
                return true;
            }
        }
        return false;
    }

    public function isSerialisable($id_product) {
        $sql = 'SELECT serialisable';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_extrafields';
        $sql .= ' WHERE fk_object=' . $id_product;

        $result = $this->db->db->query($sql);
        if ($result and $this->db->db->num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                if ($obj->serialisable == 1)
                    return true;
                else
                    return false;
            }
        }
        return false;
    }

    public function lineExists($id_transfer, $id_product, $id_equipment) {
        $sql = 'SELECT id, quantity_sent';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->getTable();
        $sql .= ' WHERE id_transfer=' . $id_transfer;
        $sql .= ' AND id_product=' . $id_product;
        if ($id_equipment > 0)
            $sql .= ' AND id_equipment=' . $id_equipment;

        $result = $this->db->db->query($sql);
        if ($result and $this->db->db->num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                return $obj->id;
            }
        }
        return false;
    }

    public function updateQuantity($id_transfer_line, $quantity) {
        parent::fetch($id_transfer_line);
        $this->data['quantity_sent'] += $quantity;
        parent::update();
    }

    function checkStock(&$quantity, $id_product, $id_equipment, $id_warehouse_source, $id_transfer) {
        $errors = array();
        $parent = BimpCache::getBimpObjectInstance('bimptransfer', 'Transfer', $id_transfer);
        if ($id_equipment > 0) {
            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
            $is_reserved = !$equipment->isAvailable($id_warehouse_source, $errors, array('id_transfer' => $id_transfer));
            if ($is_reserved && $parent->getData('status') == Transfer::STATUS_SENDING)
                $errors[] = "Cet équipement est déjà réservé." . $parent->getData('status');
            elseif (!$is_reserved && $parent->getData('status') == Transfer::STATUS_RECEPTING)
                $errors[] = "Cet équipement n'est pas réservé.";
            else
                $quantity = 1;
        } else {
            $quantity = $this->checkStockProd($id_product, $id_warehouse_source);
        }
        return $errors;
    }

    function checkStockEquipment($id_equipment, $id_warehouse_source) {

        $sql = 'SELECT id';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment_place';
        $sql .= ' WHERE id_equipment="' . $id_equipment . '"';
        $sql .= ' AND position=1';
        $sql .= ' AND type=2';
        $sql .= ' AND id_entrepot=' . $id_warehouse_source;

        $result = $this->db->db->query($sql);
        if ($result and $this->db->db->num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                return 1;
            }
        }
        return 0;
    }

    function delete(&$warnings = array(), $force_delete = false) {
        $errors = array();
        if (count($errors) == 0)
            $errors = array_merge($errors, $this->cancelReservation());

        if (count($errors) == 0)
            $errors = array_merge($errors, parent::delete($warnings, $force_delete));
        return $errors;
    }
    
    function isEditable($force_edit = false) {
        return $this->getParentInstance()->isEditable($force_edit);
    }

    function checkStockProd($id_product, $id_warehouse_source) {

        $sql = 'SELECT reel';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_stock';
        $sql .= ' WHERE fk_product =' . $id_product;
        $sql .= ' AND   fk_entrepot=' . $id_warehouse_source;

        $result = $this->db->db->query($sql);
        if ($result and $this->db->db->num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                return $obj->reel;
            }
        }
        return 0;
    }

    function update(&$warnings = array(), $force_update = false) {
        if ($this->getData('quantity_received') > $this->getData('quantity_sent'))
            return array("Une ligne a une quantité reçu supérieure à une quantité envoyée.");

        if ($this->getData('quantity_sent') != $this->getInitData('quantity_sent') or
                $this->getData('quantity_transfered') != $this->getInitData('quantity_transfered')) {
            $this->updateReservation();
        }
        return parent::update($warnings, $force_update);
    }

    private function getParentStatus() {
        $transfer = $this->getParentInstance();
        return $transfer->getData('status');
    }

    public function transfer() {
        global $user;
        $errors = array();
        $transfer = $this->getParentInstance();
        $codemove = 'TR-' . $transfer->getData('id');

        $new_qty = $this->getData('quantity_received') - $this->getData('quantity_transfered');
        if ($new_qty == 0) {
            return;
        }

        $id_equipment = $this->getData('id_equipment');
        // Equipment
        if ($id_equipment > 0) {
            $emplacement = BimpObject::getInstance('bimpequipment', 'BE_Place');
            if ($new_qty > 0) {
                $errors = array_merge($errors, $emplacement->validateArray(array(
                            'id_equipment' => $id_equipment,
                            'type' => 2,
                            'id_entrepot' => $transfer->getData('id_warehouse_dest'),
                            'infos' => 'Transfert de stock',
                            'code_mvt' => $codemove,
                            'date' => dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S')
                )));
            } else {
                $errors = array_merge($errors, $emplacement->validateArray(array(
                            'id_equipment' => $id_equipment,
                            'type' => 2,
                            'id_entrepot' => $transfer->getData('id_warehouse_source'),
                            'infos' => 'Annulation transfert de stock',
                            'code_mvt' => $codemove,
                            'date' => dol_print_date(dol_now(), '%Y-%m-%d %H:%M:%S')
                )));
            }
            $errors = array_merge($errors, $emplacement->create());
            // Product
        } else {
            $doli_prod = new Product($this->db->db);
            $doli_prod->fetch($this->getData('id_product'));
            $label_move = $codemove . '-qty:' . $new_qty . '-ref:' . $doli_prod->ref;
            $result1 = $doli_prod->correct_stock($user, $transfer->getData('id_warehouse_source'), $new_qty, 1, $label_move, 0, $codemove, 'entrepot', $transfer->getData('id_warehouse_dest'));
            if ($result1 == -1)
                $errors = array_merge($errors, $doli_prod->errors);
            $result2 = $doli_prod->correct_stock($user, $transfer->getData('id_warehouse_dest'), $new_qty, 0, $label_move, 0, $codemove, 'entrepot', $transfer->getData('id_warehouse_source'));
            if ($result2 == -1)
                $errors = array_merge($errors, $doli_prod->errors);
        }

        if (sizeof($errors) > 0) {
            print_r($errors);
            return $errors;
        }
        // Common
        $errors = array_merge($errors, $this->set('quantity_transfered', $this->getData('quantity_received')));
        $errors = array_merge($errors, $this->update());
        if (sizeof($errors) > 0) {
            print_r($errors);
            return $errors;
        }
        return $errors;
    }

    public function canEditField($field_name) {
        global $user;
        $transfer_status = $this->getParentStatus();
        if ($field_name == 'quantity_received' and $transfer_status != Transfer::STATUS_RECEPTING and ! $user->rights->bimptransfer->admin)
            return false;

        if ($field_name == 'quantity_sent' and $transfer_status != Transfer::STATUS_SENDING and ! $user->rights->bimptransfer->admin)
            return false;

        return parent::canEditField($field_name);
    }

}
