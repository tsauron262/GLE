<?php

include_once '../../../main.inc.php';

class BimpTransfer {

    private $db;
    public $lines;
    public $id;
    public $date_opening;
    public $date_closing;
    public $fk_warehouse_source;
    public $fk_warehouse_dest;
    public $fk_user_create;
    public $status;
    public $errors;

    const STATUS_DRAFT = 0;
    const STATUS_SENT = 1;
    const STATUS_RECEIVED_PARTIALLY = 2;
    const STATUS_RECEIVED = 3;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
        $this->lines = array();
    }

    public function fetch($id) {

        if ($id < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $sql = 'SELECT status, fk_warehouse_source, fk_warehouse_dest, fk_user_create, date_opening, date_closing';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_transfer';
        $sql .= ' WHERE rowid=' . $id;


        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $this->id = $id;
                $this->status = $obj->status;
                $this->fk_warehouse_source = $obj->fk_warehouse_source;
                $this->fk_warehouse_dest = $obj->fk_warehouse_dest;
                $this->fk_user_create = $obj->fk_user_create;
                $this->date_opening = $obj->date_opening;
                $this->date_closing = $obj->date_closing;

                return true;
            }
        } else {
            $this->errors[] = "Aucun transfert n'a l'identifiant " . $id;
            return false;
        }
    }

    public function create($fk_warehouse_source, $fk_warehouse_dest, $fk_user, $status = STATUS_DRAFT) {

        if ($fk_warehouse_source < 0) {
            $this->errors[] = "Identifiant entrepot de départ invalide : " . $fk_warehouse_source;
            return false;
        } elseif ($fk_warehouse_dest < 0) {
            $this->errors[] = "Identifiant entrepot d'arrivée invalide : " . $fk_warehouse_dest;
            return false;
        } elseif ($fk_user < 0) {
            $this->errors[] = "Identifiant utilisateur invalide : " . $fk_user;
            return false;
        }

        $this->db->begin();
        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'be_transfer (';
        $sql.= 'status';
        $sql.= ', fk_warehouse_source';
        $sql.= ', fk_warehouse_dest';
        $sql.= ', fk_user_create';
        $sql.= ', date_opening';
        $sql.= ') ';
        $sql.= 'VALUES (' . $status;
        $sql.= ', ' . $fk_warehouse_source;
        $sql.= ', ' . $fk_warehouse_dest;
        $sql.= ', ' . $fk_user;
        $sql.= ', ' . $this->db->idate(dol_now());
        $sql.= ')';

        $result = $this->db->query($sql);
        if ($result) {
            $last_insert_id = $this->db->last_insert_id(MAIN_DB_PREFIX . 'be_transfer');
            $this->db->commit();
            return $last_insert_id;
        } else {
            $this->errors[] = "Impossible de créer le transfert.";
            dol_print_error($this->db);
            $this->db->rollback();
            return -1;
        }
    }

    public function addLines($products) {

        $cnt_line_added = 0;
        $em = new EquipmentManager($this->db);
        foreach ($products as $product) {
            $new_line = new BimpTransferLine($this->db);
            if ($product['is_equipment'] == 'true') {
                $fk_equipment = $em->getEquipmentBySerial($product['serial']);
                $id_line = $new_line->create($this->id, $this->fk_user_create, $product['id_product'], $fk_equipment, 1);
            } else {
                $id_line = $new_line->create($this->id, $this->fk_user_create, $product['id_product'], 'NULL', $product['qty']);
            }
            if ($id_line < 0) {
                $this->errors = array_merge($this->errors, $new_line->errors);
            }
            $cnt_line_added++;
        }
        return $cnt_line_added;
    }

    /**
     * 
     * @param int $fk_warehouse_dest
     * @param array $status accept all transfert whith status in that array
     * @param type $human_readable url name and explicit status
     * @return array of transfer
     */
    public function getTransfers($fk_warehouse_dest = null, $status = null, $human_readable = false) {

        $transfers = array();

        $sql = 'SELECT rowid, status, fk_warehouse_source, fk_warehouse_dest, fk_user_create, date_opening, date_closing';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_transfer';

        if ($fk_warehouse_dest != null) {
            $sql .= ' WHERE fk_warehouse_dest=' . $fk_warehouse_dest;
            if ($status != null)
                $sql .= ' AND status IN (\'' . implode("','", $status) . '\')';
        } elseif ($status != null) {
            $sql .= ' WHERE status IN (\'' . implode("','", $status) . '\')';
        }

//        echo $sql;

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                if ($human_readable) {
                    $user = new User($this->db);
                    $user->fetch($obj->fk_user_create);
                    $obj_transfer = new BimpTransfer($this->db);
                    $obj_transfer->id = $obj->rowid;
                    $doli_warehouse = new Entrepot($this->db);
                    $doli_warehouse->fetch($obj->fk_warehouse_source);

                    if ($obj->status == $obj_transfer::STATUS_DRAFT) {
                        $name_status = 'Bouillon';
                    } elseif ($obj->status == $obj_transfer::STATUS_SENT) {
                        $name_status = 'Envoyé';
                    } elseif ($obj->status == $obj_transfer::STATUS_RECEIVED_PARTIALLY) {
                        $name_status = 'Reçu partiellement';
                    } elseif ($obj->status == $obj_transfer::STATUS_RECEIVED) {
                        $name_status = 'Reçu';
                    }
                    $transfers[] = array(
                        'id' => $obj->rowid,
                        'status' => $obj->status,
                        'name_status' => $name_status,
                        'fk_warehouse_source' => $obj->fk_warehouse_source,
                        'nb_product_scanned' => $obj_transfer->getProductSent(),
                        'url_warehouse_source' => $doli_warehouse->getNomUrl(),
                        'fk_warehouse_dest' => $obj->fk_warehouse_dest,
                        'fk_user_create' => $obj->fk_user_create,
                        'url_user' => $user->getNomUrl(-1, '', 0, 0, 24, 0, ''),
                        'date_opening' => $obj->date_opening,
                        'date_closing' => ($obj->date_closing != null) ? $obj->date_closing : '');
                } else {
                    $transfers[] = array(
                        'id' => $obj->rowid,
                        'status' => $obj->status,
                        'fk_warehouse_source' => $obj->fk_warehouse_source,
                        'fk_warehouse_dest' => $obj->fk_warehouse_dest,
                        'fk_user_create' => $obj->fk_user_create,
                        'date_opening' => $obj->date_opening,
                        'date_closing' => $obj->date_closing);
                }
            }
        }
        return $transfers;
    }

    public function getProductSent() {

        if ($this->id < 0) {
            $this->errors[] = "L'identifiant du transfert est inconnu.";
            return false;
        }


        $sql = 'SELECT SUM(quantity_sent) as scanned_product';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_transfer_det';
        $sql .= ' WHERE fk_transfer=' . $this->id;

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                return $obj->scanned_product;
            }
        }
        return false;
    }

    public function getLines($add_prod_info = false) {

        if ($this->id < 0) {
            $this->errors[] = "L'identifiant du transfert est inconnu.";
            return false;
        }


        $sql = 'SELECT rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_transfer_det';
        $sql .= ' WHERE fk_transfer=' . $this->id;


        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $line = new BimpTransferLine($this->db);
                $line->fetch($obj->rowid);
                if ($add_prod_info) {
                    $doli_prod = new Product($this->db);
                    $doli_prod->fetch($line->fk_product);
                    $line->ref = $doli_prod->ref;
                    $line->refurl = $doli_prod->getNomUrl(1);
                    $line->label = dol_trunc($doli_prod->label, 25);
                    $line->barcode = $doli_prod->barcode;
                    if ($line->fk_equipment > 0) {
                        $em = new EquipmentManager($this->db);
                        $line->serial = $em->getSerial($line->fk_equipment);
                    }
                }

                $this->lines[] = $line;
                $this->errors = array_merge($this->errors, $line->errors);
            }
        } if (!$result) {
            $this->errors[] = "Erreur lors de la requête de recherche de ligne du transfert : $this->id";
            return false;
        }
        return $this->lines;
    }

    public function receiveTransfert($user, $products, $equipments) {
        $nb_update = 0;
        $now = dol_now();
        $labelmove = 'Reception transfert bimp ' . dol_print_date($now, '%Y-%m-%d %H:%M');
        $codemove_source = "ExepeditionBimpTransfer" . $this->id;
        $codemove_dest = "ReceptionBimpTransfer" . $this->id;

        foreach ($products as $product) {
            $line = new BimpTransferLine($this->db);
            $line->updateQty($user, $this->id, $product['previous_qty'], $product['new_qty'], $product['fk_product'], 0, $labelmove, $codemove_source, $codemove_dest, $this->fk_warehouse_source, $this->fk_warehouse_dest, $now);
            $this->errors = array_merge($this->errors, $line->errors);
            $nb_update++;
        }

        foreach ($equipments as $fk_equipment) {
            $line = new BimpTransferLine($this->db);
            $line->updateQty($user, $this->id, 0, 1, 0, $fk_equipment, $labelmove, $codemove_source, $codemove_dest, $this->fk_warehouse_source, $this->fk_warehouse_dest, $now);
            $this->errors = array_merge($this->errors, $line->errors);
            $nb_update++;
        }

        $this->updateStatut($this::STATUS_RECEIVED_PARTIALLY);

        return $nb_update;
    }

    public function updateStatut($code_status) {
        if ($this->id < 0) {
            $this->errors[] = "L'identifiant du transfert est inconnu.";
            return false;
        }

        $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'be_transfer';
        $sql .= ' SET status=' . $code_status;
        $sql .= ', date_closing=' . (($code_status < $this::STATUS_RECEIVED) ? ' NULL' : $this->db->idate(dol_now()));
        $sql .= ' WHERE rowid=' . $this->id;

        $result = $this->db->query($sql);
        if ($result) {
            $this->status = $code_status;
            $this->db->commit();
            return true;
        } else {
            $this->errors[] = "Impossible de mettre à jour le statut du transfert.";
            dol_print_error($this->db);
            $this->db->rollback();
            return -1;
        }
    }

}

class BimpTransferLine {

    private $db;
    public $errors;
    public $id;
    public $date_opening;
    public $quantity_sent;
    public $quantity_received;
    public $fk_transfer;
    public $fk_user_create;
    public $fk_product;
    public $fk_equipment;
    public $serial;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    public function fetch($id) {

        if ($id < 0) {
            $this->errors[] = "Identifiant de ligne invalide :" . $id;
            return false;
        }

        $sql = 'SELECT date_opening, quantity_sent, quantity_received, fk_transfer, fk_user_create, fk_product, fk_equipment';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_transfer_det';
        $sql .= ' WHERE rowid=' . $id;


        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $this->id = $id;
                $this->date_opening = $this->db->jdate($obj->date_opening);
                $this->quantity_sent = $obj->quantity_sent;
                $this->quantity_received = $obj->quantity_received;
                $this->fk_transfer = $obj->fk_transfer;
                $this->fk_user_create = $obj->fk_user_create;
                $this->fk_product = $obj->fk_product;
                $this->fk_equipment = ($obj->fk_equipment != NULL) ? $obj->fk_equipment : '';
                return true;
            }
        } else {
            $this->errors[] = "Impossible de trouver la ligne dont l'identifiant est : $id";
            return false;
        }
    }

    public function create($fk_transfer, $fk_user_create, $fk_product, $fk_equipment, $quantity_sent) {

        $stop = false;
        if ($fk_transfer < 0) {
            $this->errors[] = "Identifiant tranfert invalide : " . $fk_transfer;
            $stop = true;
        }
        if ($fk_user_create < 0) {
            $this->errors[] = "Identifiant utilisateur invalide : " . $fk_user_create;
            $stop = true;
        }
        if ($fk_product < 0) {
            $this->errors[] = "Identifiant produit invalide : " . $fk_product;
            $stop = true;
        }
        if ($stop)
            return false;

        $this->db->begin();
        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'be_transfer_det (';
        $sql.= 'date_opening';
        $sql.= ', quantity_sent';
        $sql.= ', fk_transfer';
        $sql.= ', fk_user_create';
        $sql.= ', fk_product';
        $sql.= ', fk_equipment';
        $sql.= ') ';
        $sql.= 'VALUES (' . $this->db->idate(dol_now());
        $sql.= ', ' . $quantity_sent;
        $sql.= ', ' . $fk_transfer;
        $sql.= ', ' . $fk_user_create;
        $sql.= ', ' . $fk_product;
        $sql.= ', ' . $fk_equipment;
        $sql.= ')';

        $result = $this->db->query($sql);
        if ($result) {
            $last_insert_id = $this->db->last_insert_id(MAIN_DB_PREFIX . 'be_transfer_det');
            $this->db->commit();
            return $last_insert_id;
        } else {
            $this->errors[] = "Impossible de créer la ligne de transfert avec fk_product=$fk_product";
            dol_print_error($this->db);
            $this->db->rollback();
            return -1;
        }
    }

    function updateQty($user, $fk_transfert, $previous_qty, $new_qty, $fk_product, $fk_equipment, $labelmove, $codemove_source, $codemove_dest, $fk_warehouse_source, $fk_warehouse_dest, $now) {

        if ($fk_transfert < 0) {
            $this->errors[] = "L'identifiant du transfert est inconnu.";
            return false;
        }
        if ($fk_product < 0 and $fk_equipment < 0) {
            $this->errors[] = "Impossible de mettre à jours les quantités de produits sans définir de id_product ni de id_equipment";
            return false;
        }

        $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'be_transfer_det';
        $sql .= ' SET quantity_received=' . $new_qty;
        $sql .= ' WHERE fk_transfer=' . $fk_transfert;
        if (0 < $fk_product)
            $sql .= ' AND fk_product=' . $fk_product;
        if (0 < $fk_equipment)
            $sql .= ' AND fk_equipment=' . $fk_equipment;

        $result = $this->db->query($sql);
        if ($result) {
            $this->db->commit();
            // products
            if (0 < $fk_product) {
                $doli_product = new Product($this->db);
                $doli_product->fetch($fk_product);
                // remove from source
                $result = $doli_product->correct_stock($user, $fk_warehouse_source, ($new_qty - $previous_qty), 1, $labelmove, 0, $codemove_source); //, 'order_supplier', $entrepotId);
                if ($result < 0) {
                    $this->errors = array_merge($this->errors, $doli_product->errors);
                    $this->errors = array_merge($this->errors, $doli_product->errorss);
                }
                // add to destination
                $result2 = $doli_product->correct_stock($user, $fk_warehouse_dest, ($new_qty - $previous_qty), 0, $labelmove, 0, $codemove_dest); //, 'order_supplier', $entrepotId);
                if ($result2 < 0) {
                    $this->errors = array_merge($this->errors, $doli_product->errors);
                    $this->errors = array_merge($this->errors, $doli_product->errorss);
                }
            } else if (0 < $fk_equipment) {
                $emplacement = BimpObject::getInstance('bimpequipment', 'BE_Place');

                $emplacement->validateArray(array(
                    'id_equipment' => $fk_equipment,
                    'type' => 2,
                    'id_entrepot' => $fk_warehouse_dest,
                    'code_mvt' => $codemove_dest,
//                    'infos' => $codemove,
                    'date' => dol_print_date($now, '%Y-%m-%d %H:%M:%S') // date et heure d'arrivée
                ));
                $this->errors = array_merge($this->errors, $emplacement->create());
            }
            return true;
        } else {
            $this->errors[] = "Impossible de changer la quantité de produit avec fk_product = $fk_product et fk_equipment = $fk_equipment";
            dol_print_error($this->db);
            $this->db->rollback();
            return -1;
        }
    }

}
