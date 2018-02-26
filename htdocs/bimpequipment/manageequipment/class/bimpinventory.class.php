<?php

include_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/lignepanier.class.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/equipmentmanager.class.php';

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
    private $prodQty;
    private $equipments;

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
        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'be_inventory (';
        $sql.= 'fk_entrepot';
        $sql.= ', fk_user_create';
        $sql.= ', date_ouverture';
        $sql.= ', statut';
        $sql.= ') ';
        $sql.= 'VALUES (' . $id_entrepot;
        $sql.= ', ' . $id_user;
        $sql.= ', ' . $this->db->idate(dol_now());
        $sql.= ', ' . $this::STATUT_DRAFT;
        $sql.= ')';


        $result = $this->db->query($sql);
        if ($result) {
            $last_insert_id = $this->db->last_insert_id(MAIN_DB_PREFIX . 'be_inventory');
            $this->db->commit();
            return $last_insert_id;
        } else {
            $this->errors[] = "Impossible de créer l'inventaire.";
            dol_print_error($this->db);
            $this->db->rollback();
            return -1;
        }
    }

    public function fetchLignes() {

        if ($this->id < 0) {
            $this->errors[] = "L'identifiant de l'inventaire est inconnu.";
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

    private function setStatut($statut_code) {
        // TODO
    }

    public function setProductQuantities() {

        if ($this->id < 0) {
            $this->errors[] = "L'identifiant de l'inventaire est inconnu.";
            return false;
        }

        $sql = 'SELECT fk_product, SUM(quantity) as qty';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_inventory_det';
        $sql .= ' WHERE fk_inventory=' . $this->id;
        $sql .= ' AND 	fk_equipment IS NULL';
        $sql .= ' GROUP BY fk_product';

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $this->prodQty[$obj->fk_product] = $obj->qty;
            }
            return true;
        } else {
            $this->errors[] = "Aucun produit pour l'inventaire dont l'identifiant est " . $this->id;
            return false;
        }
    }

    public function setEquipments() {

        if ($this->id < 0) {
            $this->errors[] = "L'identifiant de l'inventaire est inconnu.";
            return false;
        }

        $sql = 'SELECT eq.id as id, eq.serial as serial, ligne.fk_product as id_product';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_inventory_det as ligne';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment as eq ON eq.id = ligne.fk_equipment';
        $sql .= ' WHERE ligne.fk_inventory=' . $this->id;
        $sql .= ' AND 	ligne.fk_equipment IS NOT NULL';

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $equipment = array('id' => $obj->id, 'serial' => $obj->serial, 'id_product' => $obj->id_product);
                $this->equipments[] = $equipment;
            }
            return true;
        } else {
            $this->errors[] = "Aucun équipement pour l'inventaire dont l'identifiant est " . $this->id;
            return false;
        }
    }

    public function addLine($entry, $user_id) {
        $lp = new LignePanier($this->db);
        $lp->check($entry, 0);

        if ($lp->error != '')
            return array('errors' => array(0 => $lp->error));

        $line = new BimpInventoryLigne($this->db);
        if ($lp->serial != '') { // is an equipment
            $line_id = $line->create($this->id, $user_id, $lp->prodId, $lp->equipmentId, 1);
        } else { // is a product
            $line_id = $line->create($this->id, $user_id, $lp->prodId, 'NULL', 1);
        }

        if ($line->errors != '')
            return array('errors' => $line->errors);

        $line->fetch($line_id);
        $this->lignes[] = $line;

        if ($this->statut == $this::STATUT_DRAFT)
            $this->setStatut($this::STATUT_IN_PROCESS);

        return array('new_line_id' => $line_id, 'errors' => $this->errors);
    }

    public function retrieveScannedLignes() {

        if ($this->id < 0) {
            $this->errors[] = "L'identifiant de l'inventaire est inconnu.";
            return false;
        }
        
        $this->setProductQuantities();
        $this->setEquipments();
        
        $em = new EquipmentManager($this->db);
        $out = $em->getAllProducts($this->fk_entrepot);
        
        if (sizeof($em->errors) != 0)
            return $out;
        
        $allEqui = $out['equipments'];
        $allProd = $out['products'];
        
        
        
//return (array('equipments' => $equipments, 'products' => $products, 'errors' => $this->errors));

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

        $stop = false;
        if ($id_inventory < 0) {
            $this->errors[] = "Identifiant inventaire invalide : " . $id_inventory;
            $stop = true;
        }
        if ($id_user < 0) {
            $this->errors[] = "Identifiant utilisateur invalide : " . $id_user;
            $stop = true;
        }
        if ($id_product < 0) {
            $this->errors[] = "Identifiant produit invalide : " . $id_user;
            $stop = true;
        }
        if ($stop)
            return false;

// update ligne ?
//        $id = $this->checkLigneExists($id_inventory, $id_user, );
//        if (0 < $id) {
//            $this->update($id, $quantity);
//            return $id;
//        }

        $this->db->begin();
        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'be_inventory_det (';
        $sql.= 'date_creation';
        $sql.= ', quantity';
        $sql.= ', fk_inventory';
        $sql.= ', fk_user';
        $sql.= ', fk_product';
        $sql.= ', fk_equipment';
        $sql.= ') ';
        $sql.= 'VALUES (' . $this->db->idate(dol_now());
        $sql.= ', ' . $quantity;
        $sql.= ', ' . $id_inventory;
        $sql.= ', ' . $id_user;
        $sql.= ', ' . $id_product;
        $sql.= ', ' . $id_equipment;
        $sql.= ')';

        $result = $this->db->query($sql);
        if ($result) {
            $last_insert_id = $this->db->last_insert_id(MAIN_DB_PREFIX . 'be_inventory_det');
            $this->db->commit();
            return $last_insert_id;
        } else {
            $this->errors[] = "Impossible de créer l'inventaire.";
            dol_print_error($this->db);
            $this->db->rollback();
            return -1;
        }
    }

}
