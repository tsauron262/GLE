<?php

include_once '../../../main.inc.php';

class BimpTransfer {

    private $db;
    public $id;
    public $date_opening;
    public $date_closing;
    public $fk_warehouse;
    public $fk_user_create;
    public $status;
    public $errors;

    const STATUT_DRAFT = 0;
    const STATUT_SENT = 1;
    const STATUT_RECEIVED = 2;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
        $this->lignes = array();
    }

    public function fetch($id) {

        if ($id < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $sql = 'SELECT status, fk_warehouse, fk_user_create, date_opening, date_closing';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_transfer';
        $sql .= ' WHERE rowid=' . $id;


        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $this->id = $id;
                $this->status = $obj->status;
                $this->fk_warehouse = $obj->fk_warehouse;
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

    public function create($fk_warehouse, $fk_user) {

        if ($fk_warehouse < 0) {
            $this->errors[] = "Identifiant entrepot invalide : " . $fk_warehouse;
            return false;
        } else if ($fk_user < 0) {
            $this->errors[] = "Identifiant utilisateur invalide : " . $fk_user;
            return false;
        }

        $this->db->begin();
        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'be_transfer (';
        $sql.= 'status';
        $sql.= ', fk_warehouse';
        $sql.= ', fk_user_create';
        $sql.= ', date_opening';
        $sql.= ') ';
        $sql.= 'VALUES (' . $this::STATUT_DRAFT;
        $sql.= ', ' . $fk_warehouse;
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

    public function updateStatut($new_code_status) {

        if ($this->id < 0) {
            $this->errors[] = "L'identifiant du trasnfert est inconnu.";
            return false;
        }

        $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'be_transfer';
        $sql .= ' SET statut=' . $new_code_status;
        $sql .= ', date_fermeture=' . (($new_code_status < 2) ? ' NULL' : $this->db->idate(dol_now()));
        $sql .= ' WHERE rowid=' . $this->id;

        $result = $this->db->query($sql);
        if ($result) {
            $this->statut = $new_code_status;
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
    public $quantity;
    public $fk_transfer;
    public $fk_user;
    public $fk_product;
    public $fk_equipment;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    public function fetch($id) {

        if ($id < 0) {
            $this->errors[] = "Identifiant de ligne invalide :" . $id;
            return false;
        }

        $sql = 'SELECT date_opening, quantity, fk_transfer, fk_user, fk_product, fk_equipment';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_transfer_det';
        $sql .= ' WHERE rowid=' . $id;


        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $this->id = $id;
                $this->date_opening = $this->db->jdate($obj->date_opening);
                $this->quantity = $obj->quantity;
                $this->fk_transfer = $obj->fk_transfer;
                $this->fk_user = $obj->fk_user;
                $this->fk_product = $obj->fk_product;
                $this->fk_equipment = ($obj->fk_equipment != NULL) ? $obj->fk_equipment : '';
                return true;
            }
        } else {
            $this->errors[] = "Impossible de trouver la ligne dont l'identifiant est : $id";
            return false;
        }
    }

    public function create($fk_transfer, $fk_user_create, $fk_product, $fk_equipment, $quantity) {

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
        $sql.= 'date_creation';
        $sql.= ', quantity';
        $sql.= ', fk_transfer';
        $sql.= ', fk_user_create';
        $sql.= ', fk_product';
        $sql.= ', fk_equipment';
        $sql.= ') ';
        $sql.= 'VALUES (' . $this->db->idate(dol_now());
        $sql.= ', ' . $quantity;
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
}
    