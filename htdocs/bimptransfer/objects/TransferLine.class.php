<?php

require_once DOL_DOCUMENT_ROOT . '/bimpcore/objects/BimpDolObject.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class TransferLine extends BimpObject
{
    public function canEditField($field_name)
    {
        global $user;
        $transfer_status = $this->getParentStatus();
        if ($field_name == 'quantity_received' and $transfer_status != Transfer::STATUS_RECEPTING and ! $user->rights->bimptransfer->admin)
            return false;

        if ($field_name == 'quantity_sent' and $transfer_status != Transfer::STATUS_SENDING and ! $user->rights->bimptransfer->admin)
            return false;

        return parent::canEditField($field_name);
    }
    
    public function canDelete()
    {
        if ($this->getData("quantity_transfered") == 0 && $this->getData("quantity_received") == 0)
            return 1;
        return 0;
    }

    public function cancelReservation()
    {
        $errors = array();
        $tabReservations = $this->getReservations();
        foreach ($tabReservations as $reservation)
            $errors = array_merge($errors, $reservation->setNewStatus(303));
        return $errors;
    }

    private function getReservations()
    {
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

    public function updateReservation($force_close = false)
    {
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
            elseif ($force_close)
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

    public function checkInput($input, &$id_product, &$id_equipment)
    {
        $errors = array();
        $is_product = $this->isProduct($input, $id_product);
        $is_equipment = $this->isEquipment($input, $id_equipment, $id_product);
        if (!$is_equipment and ! $is_product)
            $errors[] = "Produit inconnu";
        else if (/* rajout de ici */!$is_equipment and /* a la */$this->isSerialisable($id_product))
            $errors[] = "Veuillez scanner le numéro de série au lieu de la référence.";
        return $errors;
    }

    public function isProduct($search, &$id_product)
    {
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

    public function isEquipment($input, &$id_equipment, &$id_product)
    {
        $sql = 'SELECT id, id_product';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
        $sql .= ' WHERE serial="' . $input . '" || concat("S", serial)="' . $input . '"';

        $result = $this->db->db->query($sql);
        if ($result and $this->db->db->num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                $id_product = $obj->id_product;
                $id_equipment = $obj->id;
                return true;
            }
        }
        return false;
    }

    public function isSerialisable($id_product)
    {
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

    public function lineExists($id_transfer, $id_product, $id_equipment)
    {
        $sql = 'SELECT id, quantity_sent';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . $this->getTable();
        $sql .= ' WHERE id_transfer=' . $id_transfer;
        if ($id_equipment > 0)
            $sql .= ' AND id_equipment=' . $id_equipment;
        else
            $sql .= ' AND id_product=' . $id_product;

        $result = $this->db->db->query($sql);
        if ($result and $this->db->db->num_rows($result) > 0) {
            while ($obj = $this->db->db->fetch_object($result)) {
                return $obj->id;
            }
        }
        return false;
    }

    public function updateQuantity($id_transfer_line, $quantity)
    {
        parent::fetch($id_transfer_line);
        $this->data['quantity_sent'] += $quantity;
        parent::update();
    }

    public function checkStock(&$quantity, $id_product, $id_equipment, $id_warehouse_source, $id_transfer)
    {
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

    public function checkStockEquipment($id_equipment, $id_warehouse_source)
    {

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

    public function delete(&$warnings = array(), $force_delete = false)
    {
        $errors = array();
        if (count($errors) == 0)
            $errors = array_merge($errors, $this->cancelReservation());

        if (count($errors) == 0)
            $errors = array_merge($errors, parent::delete($warnings, $force_delete));
        return $errors;
    }

    public function isEditable($force_edit = false)
    {
        return $this->getParentInstance()->isEditable($force_edit);
    }

    public function checkStockProd($id_product, $id_warehouse_source)
    {

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

    public function update(&$warnings = array(), $force_update = false)
    {
        if ($this->getData('quantity_received') > $this->getData('quantity_sent'))
            return array("Une ligne a une quantité reçu supérieure à une quantité envoyée.");

        if ($this->getData('quantity_sent') != $this->getInitData('quantity_sent') or
                $this->getData('quantity_transfered') != $this->getInitData('quantity_transfered')) {
            $this->updateReservation();
        }
        return parent::update($warnings, $force_update);
    }

    private function getParentStatus()
    {
        $transfer = $this->getParentInstance();
        return $transfer->getData('status');
    }

    public function transfer()
    {
        $errors = array();
        $transfer = $this->getParentInstance();

        if (!BimpObject::objectLoaded($transfer)) {
            $errors[] = 'ID du transfert absent';
            return $errors;
        }

        $codemove = 'TR' . $transfer->id . '_LN' . $this->id;

        $new_qty = (float) $this->getData('quantity_received') - (float) $this->getData('quantity_transfered');
        if ($new_qty == 0) {
            return;
        }

        $id_equipment = $this->getData('id_equipment');

        if ($id_equipment > 0) {
            // Equipment: 
            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);

            $place_errors = array();
            if ($new_qty > 0) {
                $place_errors = $equipment->moveToPlace(BE_Place::BE_PLACE_ENTREPOT, $transfer->getData('id_warehouse_dest'), $codemove . '_EQ' . $id_equipment, 'Transfert #' . $transfer->id, 1, null, 'transfert', (int) $transfer->id);
            } else {
                $place_errors = $equipment->moveToPlace(BE_Place::BE_PLACE_ENTREPOT, $transfer->getData('id_warehouse_source'), $codemove . '_EQ' . $id_equipment . '_ANNUL', 'Annulation transfert #' . $transfer->id, 1, null, 'transfert', (int) $transfer->id);
            }
        } else {
            // Produit: 
            $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $this->getData('id_product'));

            $label_move = 'Transfert #' . $transfer->id . ' - Produit ' . $product->getRef();
            $stock_errors = $product->correctStocks((int) $transfer->getData('id_warehouse_source'), $new_qty, Bimp_Product::STOCK_OUT, $codemove, $label_move, 'transfert', (int) $transfer->id);
            if (count($stock_errors)) {
                $errors[] = BimpTools::getMsgFromArray($stock_errors, 'Ligne #' . $this->id);
            }
            $stock_errors = $product->correctStocks((int) $transfer->getData('id_warehouse_dest'), $new_qty, Bimp_Product::STOCK_IN, $codemove, $label_move, 'transfert', (int) $transfer->id);
            if (count($stock_errors)) {
                $errors[] = BimpTools::getMsgFromArray($stock_errors, 'Ligne #' . $this->id);
            }
        }

        if (!count($errors)) {
            $this->set('quantity_transfered', $this->getData('quantity_received'));
            $up_errors = $this->update($w, true);
            if (count($up_errors)) {
                $errors[] = BimpTools::getMsgFromArray($up_errors, 'Ligne #' . $this->id . ': échec de l\'enregistrement des quantités transférées');
            }
        }

        return $errors;
    }
    
    public function create_2($id_transfer, $id_product, $id_equipment, $quantity_input, &$id_affected, $id_warehouse_source)
    {
        global $user;
        $now = dol_now();

        // Create reservation
        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');
        // Is equipment
        if ($id_equipment > 0) {
            $errors = array_merge($errors, $reservation->validateArray(array(
                        'id_entrepot'  => $id_warehouse_source,
                        'status'       => 201, // Transfert en cours
                        'type'         => 2, // 2 = transfert
                        'id_equipment' => $id_equipment,
                        'id_product'   => $id_product,
                        'id_transfert' => $id_transfer,
                        'date_from'    => dol_print_date($now, '%Y-%m-%d %H:%M:%S'),
            )));
            // Is product
        } else {
            $errors = array_merge($errors, $reservation->validateArray(array(
                        'id_entrepot'  => $id_warehouse_source,
                        'status'       => 201, // Transfert en cours
                        'type'         => 2, // 2 = transfert
                        'id_product'   => $id_product,
                        'id_transfert' => $id_transfer,
                        'qty'          => $quantity_input,
                        'date_from'    => dol_print_date($now, '%Y-%m-%d %H:%M:%S'),
            )));
        }
        $errors = array_merge($errors, $reservation->create());

        // Create transfer line
        $errors = array_merge($errors, $this->validateArray(array(
                    'user_create'       => $user->id,
                    'user_update'       => $user->id,
                    'id_product'        => $id_product,
                    'id_equipment'      => $id_equipment,
                    'id_transfer'       => $id_transfer,
                    'quantity_sent'     => $quantity_input,
                    'quantity_received' => 0
        )));

        if (!$errors) {
            $errors = array_merge($errors, parent::create());
            $id_affected = $this->db->db->last_insert_id($this->getTable());
        }
        return $errors;
    }
}
