<?php

class BimpInventory {

    private $db;
    public $errors;
    public $lignes;
    public $id;
    public $fk_entrepot;
    public $fk_user_create;
    public $date_ouverture;
    public $date_fermeture;
    public $statut;

    const STATUT_DRAFT = 0;
    const STATUT_IN_PROCESS = 1;
    const STATUT_CLOSED = 2;

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

        $sql = 'SELECT fk_entrepot, fk_user_create, date_ouverture, date_fermeture, statut';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_inventory';
        $sql .= ' WHERE rowid=' . $id;


        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $this->id = $id;
                $this->fk_entrepot = $obj->fk_entrepot;
                $this->fk_user_create = $obj->fk_user_create;
                $this->date_ouverture = $obj->date_ouverture;
                $this->date_fermeture = $this->db->jdate($obj->date_fermeture);
                $this->statut = $obj->statut;
                return true;
            }
        } else {
            $this->errors[] = "Aucun inventaire n'a l'identifiant " . $id;
            return false;
        }
    }

    public function create($id_entrepot, $id_user) {

        if ($id_entrepot < 0) {
            $this->errors[] = "Identifiant entrepot invalide : " . $id_entrepot;
            return false;
        } else if ($id_user < 0) {
            $this->errors[] = "Identifiant utilisateur invalide : " . $id_user;
            return false;
        }

        $this->db->begin();
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "be_inventory (";
        $sql.= "fk_entrepot";
        $sql.= ", fk_user_create";
        $sql.= ", date_ouverture";
        $sql.= ", statut";
        $sql.= ") ";
        $sql.= "VALUES (" . $id_entrepot;
        $sql.= ", " . $id_user;
        $sql.= ", " . $this->db->idate(dol_now());
        $sql.= ", " . $this::STATUT_DRAFT;
        $sql.= ")";

        $result = $this->db->query($sql);
        if ($result) {
            $this->db->commit();
            return 1;
        } else {
            $this->errors[] = "Impossible de créer l'inventaire.";
            dol_print_error($this->db);
            $this->db->rollback();
            return -1;
        }
    }

    public function fetchLignes() {

        if ($this->id < 0) {
            $this->errors[] = "Utiliser la fonction fetch d'inventaire avant d'utiliser la fonction fetchLignes.";
            return false;
        }

        $sql = 'SELECT rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_inventory_det';
        $sql .= ' WHERE fk_inventory=' . $this->id;


        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $ligne = new BimpInventoryLigne($this->db);
                $ligne->fetch($obj->rowid);
                $this->lignes[] = $ligne;
            }
        } else {
            $this->errors[] = "Aucune ligne pour l'inventaire dont l'identifiant est " . $this->id;
            return false;
        }
        return true;
    }

}

class BimpInventoryLigne {

    private $db;
    public $errors;
    public $id;
    public $date_creation;
    public $quantity;
    public $fk_inventory;
    public $fk_user;
    public $fk_product;
    public $fk_equipment;

    public function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    public function fetch($id) {

        if ($id < 0) {
            $this->errors[] = "Identifiant invalide :" . $id;
            return false;
        }

        $sql = 'SELECT date_creation, quantity, fk_inventory, fk_user, fk_product, fk_equipment';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_inventory_det';
        $sql .= ' WHERE rowid=' . $id;


        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $this->id = $id;
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->quantity = $obj->quantity;
                $this->fk_inventory = $obj->fk_inventory;
                $this->fk_user = $obj->fk_user;
                $this->fk_product = $obj->fk_product;
                $this->fk_equipment = $obj->fk_equipment;
                return true;
            }
        } else {
            $this->errors[] = "Aucun inventaire n'a l'identifiant " . $id;
            return false;
        }
    }

    public function create($id_inventory, $id_user, $id_product, $id_equipment, $quantity) {

        if (0 > $id_inventory or 0 > $id_user or 0 > $id_product or 0 > $id_equipment) {
            foreach (func_get_args() as $cnt => $arg) {
                if (0 > $arg && $cnt < 4) {
                    $this->errors[] = "create : Argument n°$cnt invalide : $arg";
                    return false;
                }
            }
        }

        $this->db->begin();
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "be_inventory_det (";
        $sql.= "date_creation";
        $sql.= ", quantity";
        $sql.= ", fk_inventory";
        $sql.= ", fk_user";
        $sql.= ", fk_product";
        $sql.= ", fk_equipment";
        $sql.= ") ";
        $sql.= "VALUES (" . $this->db->idate(dol_now());
        $sql.= ", " . $id_inventory;
        $sql.= ", " . $id_user;
        $sql.= ", " . $id_product;
        $sql.= ", " . $id_equipment;
        $sql.= ")";

        $result = $this->db->query($sql);
        if ($result) {
            $this->db->commit();
            return 1;
        } else {
            $this->errors[] = "Impossible de créer l'inventaire.";
            dol_print_error($this->db);
            $this->db->rollback();
            return -1;
        }
    }

}
