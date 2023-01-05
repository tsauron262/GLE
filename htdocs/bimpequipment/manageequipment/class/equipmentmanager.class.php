<?php

include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/lignepanier.class.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/bimpinventory.class.php';

class EquipmentManager {

    private $db;
    public $errors;

    function __construct($db) {
        $this->db = $db;
        $this->errors = array();
    }

    function getProductFromEntrepot($entrepotId, $idProd = null) {
        $sql = 'SELECT reel';
        $sql.= ' FROM ' . MAIN_DB_PREFIX . 'product_stock';
        $sql.= ' WHERE fk_entrepot=' . $entrepotId;
        $sql.= (isset($idProd)) ? ' AND fk_product=' . $idProd : '';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $prodQty = $obj->reel;
            }
        }
        if (!$result) {
            $this->errors[] = "La requête SQL pour la recherche des produits a échouée";
        }
        return $prodQty;
    }

    function getProductSerialFromEntrepot($entrepotId, $idProd = null) {
        $prodSerial = array();
        $sql = 'SELECT e.serial as serial, e.id_product as id_product';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment as e';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place as e_place ON e.id = e_place.id_equipment';
        $sql .= ' WHERE e_place.id_entrepot=' . $entrepotId;
        $sql .= ' AND e_place.position=1';
        $sql.= (isset($idProd)) ? ' AND id_product=' . $idProd : '';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $prodSerial[] = $obj->serial;
            }
        }
        if (!$result) {
            $this->errors[] = "La requête SQL pour la recherche des équipements a échouée";
        }

        return $prodSerial;
    }

    function getPositionEquipmentForEntrepot($serial, $entrepotId) {
        $sql = 'SELECT e_place.position as position';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment as e';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place as e_place ON e.id = e_place.id_equipment';
        $sql .= ' WHERE e_place.id_entrepot=' . $entrepotId;
        $sql .= ' AND e.serial="' . $serial . '"';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                return $obj->position;
            }
        }
        return false; // no row found
    }

    /* Called by the interface */

    function getStockAndSerial($entrepotId, $idProd, $serial) {
        $doliProd = new Product($this->db);
        $doliProd->fetch($idProd);
        $this->errors = BimpTools::merge_array($this->errors, $doliProd->errors);
        $position = $this->getPositionEquipmentForEntrepot($serial, $entrepotId);
        if ($position == false && $serial != '') { // équipement connu mais jamais dans cet entrepôt
            $this->errors[] = "Cet équipement n'a jamais été dans cet entrepôt.";
        }
        if ($position == 1 or $position == false) { // equipement existe ou est un produit
            $equipments = $this->getProductSerialFromEntrepot($entrepotId, $idProd);
            $stocks = $this->getProductFromEntrepot($entrepotId, $idProd);
        } else {
            $this->errors[] = "Cet équipement n'est plus dans cet entrepôt.";
        }
        return array(
            'id' => $idProd,
            'stocks' => $stocks,
            'equipments' => $equipments,
            'serial' => $serial,
            'ref' => $doliProd->getNomUrl(1),
            'label' => dol_trunc($doliProd->label, 25),
            'errors' => $this->errors);
    }

    /**
     *  Called by the interface 
     * @deprecated
     */
    function correctStock($entrepotId, $products, $user) {
        var_dump($products);
        $now = dol_now();
        $codemove = dol_print_date($now, '%y%m%d%H%M%S');
        $label = 'Inventaire Bimp ' . dol_print_date($now, '%Y-%m-%d %H:%M');
        foreach ($products as $id => $prod) {
            if ($prod['qtyMissing'] == 0) {
                
            } else {
                $doliProd = new Product($this->db);
                $doliProd->fetch($id);
                if (0 < $prod['qtyMissing']) {
                    $result = $doliProd->correct_stock($user, $entrepotId, $prod['qtyMissing'], 0, $label, 0, $codemove, 'entrepot', $entrepotId);
//                    if ($result == 1)
//                        $change
                } elseif ($prod['qtyMissing'] < 0) {
                    $result = $doliProd->correct_stock($user, $entrepotId, -$prod['qtyMissing'], 1, $label, 0, $codemove, 'entrepot', $entrepotId);
                }
                if ($result == -1)
                    $this->errors = BimpTools::merge_array($this->errors, $doliProd->errors);
            }
        }
        return array('OK' => 'en dèv', 'errors' => $this->errors);
    }

    /* Function on inventories */

    public function getInventories($id_entrepot = null, $getName = false) {

        $inventories = array();

        $sql = 'SELECT rowid';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_inventory';
        $sql .= ($id_entrepot) ? ' WHERE fk_entrepot=' . $id_entrepot : '';
        $sql .= ' ORDER BY date_ouverture DESC';

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $inventory = new BimpInventory($this->db);
                $inventory->fetch($obj->rowid);
                $inventory->setScannedProduct();
                if ($getName) {
                    $user = new User($this->db);
                    $user->fetch($inventory->fk_user_create);
                    $inventory->url_user = $user->getNomUrl(-1, '', 0, 0, 24, 0, '');
                }
                $inventories[] = $inventory;
            }
        } else {
            $this->errors[] = ($id_entrepot) ? "Aucun inventaire pour l'entrepôt dont l'identifiant est : " . $id_entrepot : "Il n'y a aucun inventaire.";
            return false;
        }
        return $inventories;
    }

    /* Called by the interface */

    function getAllProducts($id_entrepot) {
        $cacheProducts = array();
        $products = $this->getOnlyProductsForEntrepot($id_entrepot);
        $equipments = $this->getOnlyEquipmentsForEntrepot($id_entrepot);

        foreach ($products as $id => $prod) {
            $doli_prod = new Product($this->db);
            $doli_prod->fetch($id);
            $products[$id]['ref'] = $doli_prod->getNomUrl(1);
            $products[$id]['label'] = dol_trunc($doli_prod->label, 25);
            $products[$id]['prixH'] = "999";
        }

        foreach ($equipments as $id => $equipment) {
            $id_product = $equipment['id_product'];

            if($id_product > 0){
                if ($cacheProducts[$id_product]) {
                    $equipments[$id]['ref'] = $cacheProducts[$id_product]['ref'];
                    $equipments[$id]['label'] = $cacheProducts[$id_product]['label'];
                } else {
                    // fill the cache
                    $doli_prod = new Product($this->db);
                    $doli_prod->fetch($id_product);
                    $cacheProducts[$id_product]['ref'] = $doli_prod->getNomUrl(1);
                    $cacheProducts[$id_product]['label'] = dol_trunc($doli_prod->label, 25);
                    // then the equipment array
                    $equipments[$id]['ref'] = $cacheProducts[$id_product]['ref'];
                    $equipments[$id]['label'] = $cacheProducts[$id_product]['label'];
                }
            }
            else
                unset($equipments[$id]);
        }
        return (array('equipments' => $equipments, 'products' => $products, 'errors' => $this->errors));
    }

    /* Used by getAllProducts() */

    function getOnlyProductsForEntrepot($id_entrepot) {
        $products = array();
        $sql = 'SELECT ps.fk_product as id, ps.reel as qty';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_stock as ps';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product_extrafields as pe ON pe.fk_object = ps.fk_product';
        $sql .= ' WHERE fk_entrepot=' . $id_entrepot;
        $sql .= ' AND (pe.serialisable != 1';
        $sql .= ' OR pe.serialisable IS NULL)';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $product = array('qty' => $obj->qty);
                $products[$obj->id] = $product;
            }
        } else if (!$result) {
            $this->errors[] = "La requête SQL pour la recherche des produits a échouée";
        }

        return $products;
    }

    /* Used by getAllProducts() */

    function getOnlyEquipmentsForEntrepot($id_entrepot) {
        $equipments = array();
        $sql = 'SELECT e.id as id, e.serial as serial, e.id_product as id_product';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment as e';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place as e_place ON e.id = e_place.id_equipment';
        $sql .= ' WHERE e_place.id_entrepot=' . $id_entrepot;
        $sql .= ' AND e_place.position=1';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $equipment = array('serial' => $obj->serial, 'id_product' => $obj->id_product);
                $equipments[$obj->id] = $equipment;
            }
        } else if (!$result) {
            $this->errors[] = "La requête SQL pour la recherche des équipements a échouée";
        }
        return $equipments;
    }

    public function getEntrepotNameForEquipment($equipment_id) {

        $sql = 'SELECT id_entrepot';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment_place';
        $sql .= ' WHERE id_equipment=' . $equipment_id;
        $sql .= ' AND position=1';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $entrepot_id = $obj->id_entrepot;
            }
            $doliEntrepot = new Entrepot($this->db);
            $doliEntrepot->fetch($entrepot_id);
            return $doliEntrepot->lieu;
        } else {
            $this->errors[] = "L'équipement $equipment_id n'est pas dans la table des entrepôts d'équipement";
            return false;
        }
    }

    public function getEntrepotForEquipment($equipment_id) {

        $sql = 'SELECT id_entrepot';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment_place';
        $sql .= ' WHERE id_equipment=' . $equipment_id;
        $sql .= ' AND position=1';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $entrepot_id = $obj->id_entrepot;
            }
            return $entrepot_id;
        } else {
            $this->errors[] = "L'équipement $equipment_id n'est pas dans la table des entrepôts d'équipement";
            return false;
        }
    }

    public function getEquipmentBySerial($serial) {

        $sql = 'SELECT id';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
        $sql .= ' WHERE serial="' . $serial . '"';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                return $obj->id;
            }
        } else {
            $this->errors[] = "Le numéro de série $serial n'est pas dans la table des équipements";
            return false;
        }
    }

    public function getSerial($fk_equipment) {

        $sql = 'SELECT serial';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
        $sql .= ' WHERE id=' . $fk_equipment;

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                return $obj->serial;
            }
        } else {
            $this->errors[] = "Le l'identifiant $fk_equipment n'est pas dans la table des équipements";
            return false;
        }
    }

    public function getBuyPrice($fk_equipment) {
        $sql = 'SELECT prix_achat';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
        $sql .= ' WHERE id=' . $fk_equipment;

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                return $obj->prix_achat;
            }
        } else {
            $this->errors[] = "Le l'identifiant $fk_equipment n'est pas dans la table des équipements";
            return false;
        }
    }

}
