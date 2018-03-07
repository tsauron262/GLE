<?php

include_once '../../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/lignepanier.class.php';

class BimpLivraison {

    private $db;
    private $commande;
    public $orderId;
    public $statut;
    public $ref;
    public $errors = array();

    function __construct($db) {
        $this->db = $db;
    }

    function fetch($orderId) {
        $this->orderId = $orderId;
        $doliFournOrder = new CommandeFournisseur($this->db);
        $doliFournOrder->fetch($orderId);
        $this->statut = $doliFournOrder->statut;
        $this->ref = "liv" . $doliFournOrder->ref;
        $this->commande = $doliFournOrder;
    }

    /* Get every line of the order */

    function getLignesOrder() {
        $lignes = array();

        $sql = 'SELECT rowid, fk_product, ref, label, qty, subprice';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet';
        $sql .= ' WHERE fk_commande=' . $this->orderId;

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                if (isset($lignes[$obj->fk_product]))
                    $lignes[$obj->fk_product]->remainingQty += $obj->qty;
                else {
                    $doliProd = new Product($this->db);
                    $doliProd->fetch($obj->fk_product);
                    $ligne = new LigneLivraison($this->db);
                    $ligne->prodId = $obj->fk_product;
                    $ligne->label = dol_trunc($obj->label, 25);
                    $ligne->remainingQty = $obj->qty;
                    $ligne->price_unity = price($obj->subprice);
                    $ligne->isDelivered = false;
                    $ligne->isEquipment = $ligne->isSerialisable();
                    $ligne->refurl = $doliProd->getNomUrl(1);

                    $lignes[$obj->fk_product] = $ligne;
                }
            }
        } else if (!$result) {
            $this->errors[] = 'Erreur de recherche de lignes d\'une commande.';
        }
        return $lignes;
    }

    function getAllMouvement() {
        $moveQty = array();
        $sql = 'SELECT fk_product, value';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'stock_mouvement';
        $sql .= ' WHERE inventorycode="' . $this->getcodeMove() . '"';

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $moveQty[$obj->fk_product] += $obj->value;
            }
        } else if (!$result) {
            $this->errors[] = 'Erreur de recherche de mouvement de stock.';
        }
        return $moveQty;
    }

    /* Called by interface */

    function getRemainingLignes() {
        $lignes = $this->getLignesOrder();

        // StatusOrderValidated or StatusOrderApproved or StatusOrderOnProcess
        if ($this->statut == 3) {
            return array('lignes' => $lignes, 'errors' => $this->errors);
        } else { // ReceivedPartially
            $moveQty = $this->getAllMouvement();
            foreach ($lignes as $key => $ligne) {
                $qtyAlreadyDelivered = $moveQty[$ligne->prodId];
                $lignes[$key]->deliveredQty += $qtyAlreadyDelivered;
                if ($qtyAlreadyDelivered) {
                    if ($ligne->remainingQty <= $qtyAlreadyDelivered) {   // done
                        $lignes[$key]->remainingQty = 0;
                    } else {
                        $lignes[$key]->remainingQty -= $qtyAlreadyDelivered;
                    }
                }
            }
        }

        $deliveredLignes = $this->getDeliveredLignes($lignes);

        return array('init_fk_entrepot' => $this->getInitEntrepot(), 'lignes' => $lignes, 'deliveredLigne' => $deliveredLignes, 'errors' => $this->errors);
    }

    function getInitEntrepot() {

        $sql = 'SELECT e.entrepot as entrepot';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'commande_fournisseur_extrafields as e';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur as cf ON cf.rowid = e.fk_object';
        $sql .= ' WHERE e.fk_object=' . $this->orderId;

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                return $obj->entrepot;
            }
        } else if (!$result) {
            $this->errors[] = "Erreur lors de la requête de recherche de l'entrepôt d'origine" . $sql;
            return false;
        }
        return false;
    }

    function getDeliveredLignes($lignes) {
        $deliveredLignes = array();
        foreach ($lignes as $ligne) {
            $newLigne = $ligne;
            if ($ligne->isEquipment) {
                $ligne->tabSerial = $this->getDeliveredSerial($ligne);
//                if (sizeof($ligne->tabSerial) != $newLigne->deliveredQty) {
//                    $this->errors[] = "Le nombre d'équipement enregistré en base est différent du nombre de numéro de série.";
//                }
            }
        }
        return $deliveredLignes;
    }

    function getDeliveredSerial($ligne) {
        $prodSerial = array();
        $sql = 'SELECT e.serial as serial';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment as e';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place as e_place ON e.id = e_place.id_equipment';
        $sql .= ' WHERE e_place.infos="' . $this->getcodeMove() . '"';
        $sql .= ' AND e.id_product=' . $ligne->prodId;

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $prodSerial[] = $obj->serial;
            }
        }
        return $prodSerial;
    }

    /* Called by interface */

    function addInStock($products, $entrepotId, $user, $isTotal) {
        $now = dol_now();
        $labelmove = 'Reception commande bimp ' . $this->commande->ref . ' ' . dol_print_date($now, '%Y-%m-%d %H:%M');
        $codemove = $this->getcodeMove();

        $this->errors = $this->checkDuplicateSerial($products);

        if (sizeof($this->errors) == 0) {
            foreach ($products as $product) {
                $doliProduct = new Product($this->db);
                $doliProduct->fetch($product['id_prod']);
                $length = sizeof($this->errors);

                if (!isset($product['qty'])) {   // serialisable
                    $this->addEquipmentsLivraison($now, $product['id_prod'], $product['serial'], $entrepotId);
                } else {    // non serialisable
                    $result = $doliProduct->correct_stock($user, $entrepotId, $product['qty'], 0, $labelmove, 0, $codemove, 'order_supplier', $entrepotId);
                    if ($result < 0) {
                        $this->errors = array_merge($this->errors, $doliProduct->errors);
                        $this->errors = array_merge($this->errors, $doliProduct->errorss);
                    }

                    if ($length != sizeof($this->errors))
                        $this->errors[] = ' id : ' . $product['id_prod'];
                }
            }

            $type = ($isTotal == 'false') ? 'par' : 'tot';

            $this->commande->Livraison($user, $now, $type, $labelmove); // last argument = comment, TODO add texterea ?
        }

        return array('errors' => $this->errors);
    }

    function checkDuplicateSerial($products) {
        $newSerials = array();
        $newErrors = array();
        foreach ($products as $prod) {
            $newSerials[] = $prod['serial'];
        }

        $sql = 'SELECT serial';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
        $sql .= ' WHERE serial IN (\'' . implode("','", $newSerials) . '\')';

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $newErrors[] = 'Erreur, le numéro de série "' . $obj->serial . '" est déjà attribué, rien n\'a été enregistrer.';
            }
        }
        return $newErrors;
    }

    function addEquipmentsLivraison($now, $prodId, $serial, $entrepotId) {
        $length = sizeof($this->errors);
        $equipement = BimpObject::getInstance('bimpequipment', 'Equipment');

        $equipement->validateArray(array(
            'id_product' => $prodId, // ID du produit. 
            'type' => 2, // cf $types
            'serial' => $serial, // num série
            'reserved' => 0, // réservé ou non
            'date_purchase' => '2010-10-10', // date d'achat TODO remove
            'date_warranty_end' => '2010-10-10', // TODO remove
            'warranty_type' => 0, // type de garantie (liste non définie actuellement)
            'admin_login' => '',
            'admin_pword' => '',
            'note' => ''
        ));

        $this->errors = array_merge($this->errors, $equipement->create());

        $emplacement = BimpObject::getInstance('bimpequipment', 'BE_Place');

        $emplacement->validateArray(array(
            'id_equipment' => $equipement->id,
            'type' => 2, // cf $types
            'id_entrepot' => $entrepotId, // si type = 2
            'infos' => $this->getcodeMove(),
            'date' => dol_print_date($now, '%Y-%m-%d %H:%M:%S') // date et heure d'arrivée
        ));
        $this->errors = array_merge($this->errors, $emplacement->create());
        if ($length != sizeof($this->errors))
            $this->errors[] = ' id : ' . $prodId . ' numéro de série : ' . $serial;
    }

    private function getcodeMove() {
        return "BimpLivraison" . $this->id;
    }

}

class LigneLivraison extends LignePanier {

    public $label;
    public $deliveredQty;
    public $remainingQty;
    public $price_unity;
    public $isEquipment;
    public $refurl;
    public $tabSerial;

}
