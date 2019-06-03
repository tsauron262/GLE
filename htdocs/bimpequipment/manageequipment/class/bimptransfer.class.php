<?php

include_once '../../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

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
        $sql.= ', "' . $this->db->idate(dol_now()).'"';
        $sql.= ')';
        
        $result = $this->db->query($sql);
        if ($result) {
            $last_insert_id = $this->db->last_insert_id(MAIN_DB_PREFIX . 'be_transfer');
            $this->db->commit();
            return $last_insert_id;
        } else {
            $this->errors[] = "Impossible de créer le transfert.";
            $this->db->rollback();
            return -1;
        }
    }

    private function delete() {
        if ($this->id < 0) {
            $this->errors[] = "Identifiant de transfert non valide : " . $this->id;
            return false;
        }

        if ($this->deleteLines() == -1)
            return -2;

        $this->db->begin();
        $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'be_transfer ';
        $sql.= ' WHERE rowid=' . $this->id;

        $result = $this->db->query($sql);
        if ($result) {
            $this->db->commit();
            return 1;
        } else {
            $this->errors[] = "Impossible de supprimer le transfert.";
            $this->db->rollback();
            return -1;
        }
    }

    private function deleteLines() {
        if ($this->id < 0) {
            $this->errors[] = "Identifiant de transfert non valide : " . $this->id;
            return false;
        }

        $this->db->begin();
        $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'br_reservation ';
        $sql.= ' WHERE id_transfert=' . $this->id;

        $result = $this->db->query($sql);
        if ($result) {
            $this->db->commit();
            return 1;
        } else {
            $this->errors[] = "Impossible de supprimer les lignes de transfert.";
            $this->db->rollback();
            return -1;
        }
    }

    public function addLines($products) {

        $cnt_line_added = 0;
        $now = dol_now();
        $em = new EquipmentManager($this->db);
        foreach ($products as $product) {
            $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');

            if ($product['is_equipment'] == 'true') {
                $fk_equipment = $em->getEquipmentBySerial($product['serial']);
                $errors1 = $reservation->validateArray(array(
                    'id_entrepot' => $this->fk_warehouse_source,
                    'status' => 201, // cf status
                    'type' => 2, // 2 = transfert
//                'id_commercial', // id user du commercial (facultatif)
                    'id_equipment' => $fk_equipment, // si produit sérialisé
                    'id_product' => $product['id_product'], // sinon
                    'id_transfert' => $this->id,
//                    'qty', // quantités si produit non sérialisé
                    'date_from' => dol_print_date($now, '%Y-%m-%d %H:%M:%S'),
//                    'date_update' => '2999-01-01 00:00:00',
//                    'date_to' => '2999-01-01 00:00:00'
//                    'note' // note facultative
                ));
            } else {
                $errors1 = $reservation->validateArray(array(
                    'id_entrepot' => $this->fk_warehouse_source, // ID entrepot: obligatoire. 
                    'status' => 201, // cf status
                    'type' => 2, // 2 = transfert
//                'id_commercial', // id user du commercial (facultatif)
//                'id_equipment', // si produit sérialisé
                    'id_product' => $product['id_product'], // sinon
                    'id_transfert' => $this->id,
                    'qty' => $product['qty'], // quantités si produit non sérialiséAAAA-MM-JJ HH:MM:SS
                    'date_from' => dol_print_date($now, '%Y-%m-%d %H:%M:%S'),
//                    'date_update' => '2999-01-01 00:00:00',
//                    'date_to' => '2999-01-01 00:00:00'
//                    'note' // note facultative
                ));
            }
            if (count($errors1)) {
                $this->errors = array_merge($this->errors, $errors1);
                $this->errors[] = " numéro de série : " . $product['serial'];
                $this->delete();
                $cnt_line_added--;
                return $cnt_line_added;
            } else {
                $errors2 = $reservation->create();
                if (count($errors2)) {
                    $this->errors = array_merge($this->errors, $errors2);
                    $this->errors[] = " numéro de série : " . $product['serial'];
                    $this->delete();
                    $cnt_line_added--;
                    return $cnt_line_added;
                }
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
    public function getTransfers($fk_warehouse_dest = null, $status = null, $human_readable = false, $fk_warehouse = null) {

        $transfers = array();

        $sql = 'SELECT rowid, status, fk_warehouse_source, fk_warehouse_dest, fk_user_create, date_opening, date_closing';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_transfer WHERE 1';

        if ($fk_warehouse_dest != null) {
            $sql .= ' AND fk_warehouse_dest=' . $fk_warehouse_dest;
            if ($status != null)
                $sql .= ' AND status IN (\'' . implode("','", $status) . '\')';
        } elseif ($status != null) {
            $sql .= ' AND status IN (\'' . implode("','", $status) . '\')';
        }
        
       if($fk_warehouse != null){
            $sql .= ' AND (fk_warehouse_dest=' . $fk_warehouse.' || fk_warehouse_source=' . $fk_warehouse.")";
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
                        'ref' => 'TR'.$obj->rowid,
                        'status' => $obj->status,
                        'name_status' => $name_status,
                        'fk_warehouse_source' => $obj->fk_warehouse_source,
                        'nb_product_scanned' => $obj_transfer->getProductSent(),
                        'url_warehouse_source' => $doli_warehouse->getNomUrl(1),
                        'fk_warehouse_dest' => $obj->fk_warehouse_dest,
                        'fk_user_create' => $obj->fk_user_create,
                        'url_user' => $user->getNomUrl(-1, '', 0, 0, 24, 0, ''),
                        'date_opening' => $obj->date_opening,
                        'date_closing' => ($obj->date_closing != null) ? $obj->date_closing : '');
                } else {
                    $transfers[] = array(
                        'id' => $obj->rowid,
                        'ref' => 'TR'.$obj->rowid,
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


        $sql = 'SELECT SUM(qty) as qty_sent';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'br_reservation';
        $sql .= ' WHERE id_transfert=' . $this->id;

//        echo $sql . "\n<br/>";
        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                return $obj->qty_sent > 0 ? $obj->qty_sent : 0;
            }
        }
        return false;
    }

    public function getLines($add_prod_info = false) {

        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation'); // Pas besoin de fetcher
        $em = new EquipmentManager($this->db);

        $lines = $reservation->getList(array(
            'id_transfert' => $this->id // ID du transfert
                ), null, null, 'date_from', 'desc', 'array', array(
            'id', // Mettre ici la liste des champs à retourner
            'qty',
            'id_equipment',
            'id_product',
            'date_from',
            'status'
        ));

        foreach ($lines as $key => $line) {
            $lines[$key]['id_reservation'] = $line['id'];
            $lines[$key]['fk_product'] = $line['id_product'];
            if ($add_prod_info && $line['id_product'] > 0) {
                $doli_prod = new Product($this->db);
                $doli_prod->fetch($line['id_product']);
                $lines[$key]['ref'] = $doli_prod->ref;
                $lines[$key]['refurl'] = $doli_prod->getNomUrl(1);
                $lines[$key]['label'] = dol_trunc($doli_prod->label, 25);
                $lines[$key]['barcode'] = $doli_prod->barcode;
            }

            if ($line['id_equipment'] > 0) { // equipments
                $lines[$key]['serial'] = $em->getSerial($line['id_equipment']);
                $lines[$key]['price'] = $em->getBuyPrice($line['id_equipment']);
                $lines[$key]['fk_equipment'] = $line['id_equipment'];
//                $lines[$key]['price'] = $line['prix_achat'];
                if ($line['status'] == 201 or $line['status'] == 303)
                    $lines[$key]['quantity_received'] = 0;
                elseif ($line['status'] == 301)
                    $lines[$key]['quantity_received'] = 1;
            } else { // products
                $lines[$key]['fk_equipment'] = 0;

                if ($line['status'] == 201 or $line['status'] == 303) {
                    $lines[$key]['quantity_sent'] = $line['qty'];
                    $lines[$key]['quantity_received'] = 0;
                } elseif ($line['status'] == 301) {
                    $lines[$key]['quantity_sent'] = $line['qty'];
                    $lines[$key]['quantity_received'] = $line['qty'];
                }
            }
            unset($lines[$key][0]);
            unset($lines[$key][1]);
            unset($lines[$key][2]);
            unset($lines[$key][3]);
            unset($lines[$key][4]);
            unset($lines[$key][5]);
        }

        return array_values($lines);
    }

    public function receiveTransfert($user, $products, $equipments) {
        $nb_update = 0;
        $now = dol_now();
        $label_move = 'BimpTransfert' . $this->id . ' ' . dol_print_date($now, '%Y-%m-%d %H:%M:%S');
        $codemove = 'BimpTransfert' . $this->id;

        foreach ($products as $product) {
            $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');

            $reservation->fetch($product['id_reservation']);

            $errors1 = $reservation->setNewStatus(301, $product['added_qty']); // $qty : faculatif, seulement pour les produits non sérialisés
            if (sizeof($errors1) != 0) {
                $this->errors = array_merge($this->errors, $errors1);
                $nb_update--;
            } else {
                $errors2 = $reservation->update();
                if (sizeof($errors2) != 0) {
                    $this->errors = array_merge($this->errors, $errors2);
                    $nb_update--;
                }
                $nb_update++;
            }

            $doliProd = new Product($this->db);
            $doliProd->fetch($product['fk_product']);
            $result1 = $doliProd->correct_stock($user, $this->fk_warehouse_source, $product['added_qty'], 1, $label_move, 0, $codemove, 'entrepot', $this->fk_entrepot);
            if ($result1 == -1)
                $this->errors = array_merge($this->errors, $doliProd->errors);
            $result2 = $doliProd->correct_stock($user, $this->fk_warehouse_dest, $product['added_qty'], 0, $label_move, 0, $codemove, 'entrepot', $this->fk_entrepot);
            if ($result2 == -1)
                $this->errors = array_merge($this->errors, $doliProd->errors);
        }

        foreach ($equipments as $equipment) {
            $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');

            $reservation->fetch($equipment['id_reservation']);

            $errors1 = $reservation->setNewStatus(301); // $qty : faculatif, seulement pour les produits non sérialisés
            if (sizeof($errors1) != 0) {
                $this->errors = array_merge($this->errors, $errors1);
                $nb_update--;
            } else {
                $errors2 = $reservation->update();
                if (sizeof($errors2) != 0) {
                    $this->errors = array_merge($this->errors, $errors2);
                    $nb_update--;
                } else {
                    $nb_update++;
                }
            }

            $emplacement = BimpObject::getInstance('bimpequipment', 'BE_Place');

            $emplacement->validateArray(array(
                'id_equipment' => $equipment['fk_equipment'],
                'type' => 2,
                'id_entrepot' => $this->fk_warehouse_dest,
                'infos' => 'Transfert de stock',
                'code_mvt' => $codemove,
                'date' => dol_print_date($now, '%Y-%m-%d %H:%M:%S')
            ));
            $this->errors = array_merge($this->errors, $emplacement->create());
        }
//        $total_group_product = sizeof($products) + sizeof($products);
//        if ($nb_update == $total_group_product)
//            $this->updateStatut($this::STATUS_RECEIVED);
        if ($nb_update > 0)
            $this->updateStatut($this::STATUS_RECEIVED_PARTIALLY);
        
        return $nb_update;
    }

    public function updateStatut($code_status) {
        if ($this->id < 0) {
            $this->errors[] = "L'identifiant du transfert est inconnu.";
            return false;
        }

        if ($this->status == $this::STATUS_RECEIVED) {
            $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation'); // Pas besoin de fetcher

            $lines = $reservation->getList(array(
                'id_transfert' => $this->id // ID du transfert
                    ), null, null, 'id', 'asc', 'array', array(
                'id', // Mettre ici la liste des champs à retourner
                'qty',
                'id_equipment',
                'id_product',
                'status'
            ));

            foreach ($lines as $line) {
                if ($line['status'] == 201) {
                    
                }
            }
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

    function closeTransfer() {
        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation'); // Pas besoin de fetcher

        $lines = $reservation->getList(array(
            'id_transfert' => $this->id // ID du transfert
                ), null, null, 'status', 'asc', 'array', array(
            'id', // Mettre ici la liste des champs à retourner
            'qty',
            'id_equipment',
            'id_product',
            'status'
        ));

        foreach ($lines as $line) {
            if ($line['status'] == 201) {
                $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation', $line['id']);
                $errors = $reservation->setNewStatus(303);
                if (!count($errors)) {
                    $reservation->update();
                }
            }
        }

        return $this->updateStatut($this::STATUS_RECEIVED);
    }

    function checkClose() {
        $can_be_close = true;
        $reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation'); // Pas besoin de fetcher

        $lines = $reservation->getList(array(
            'id_transfert' => $this->id // ID du transfert
                ), null, null, 'status', 'asc', 'array', array(
            'id', // Mettre ici la liste des champs à retourner
            'qty',
            'id_equipment',
            'id_product',
            'status'
        ));

        foreach ($lines as $line) {
            if ($line['status'] == 201) {
                $can_be_close = false;
                break;
            }
        }

        if ($can_be_close) {
            $this->updateStatut($this::STATUS_RECEIVED);
        }

        return $can_be_close;
    }

}
