<?php

include_once '../../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/lignepanier.class.php';
include_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';

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
        $staticproduct = new Product($this->db);

        $sql = 'SELECT cf.rowid as rowid, cf.fk_product as fk_product,'
                . ' cf.ref as ref, cf.label as label, cf.qty as qty,'
                . ' cf.subprice as subprice, cf.total_ht as total_ht';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet as cf';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product as p ON p.rowid=cf.fk_product';
        $sql .= ' WHERE cf.fk_commande=' . $this->orderId;
        $sql .= ' AND p.fk_product_type=' . $staticproduct::TYPE_PRODUCT;

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                /* if (isset($lignes[$obj->fk_product])) {
                  $lignes[$obj->fk_product]->remainingQty += $obj->qty;
                  $lignes[$obj->fk_product]->total_ht += $obj->total_ht;
                  $lignes[$obj->fk_product]->price_unity = price($lignes[$obj->fk_product]->total_ht / $lignes[$obj->fk_product]->remainingQty);
                  } else */if ($obj->fk_product > 0) {
                    $doliProd = new Product($this->db);
                    $doliProd->fetch($obj->fk_product);
                    $ligne = new LigneLivraison($this->db);
                    $ligne->prodId = $obj->fk_product;
                    $ligne->label = dol_trunc($obj->label, 25);
                    $ligne->remainingQty = $obj->qty;
                    $ligne->qty = $obj->qty;
                    $ligne->price_unity = str_replace(" ", "", price($obj->total_ht / $obj->qty));
                    $ligne->total_ht = $obj->total_ht;
                    $ligne->isEquipment = $ligne->isSerialisable();
                    $ligne->refurl = $doliProd->getNomUrl(1);

                    $lignes[] = $ligne;
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
        $sql = 'SELECT e.serial as serial, prix_achat';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment as e';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'be_equipment_place as e_place ON e.id = e_place.id_equipment';
        $sql .= ' WHERE e_place.infos="' . $this->getcodeMove() . '"';
        $sql .= ' AND e.id_product=' . $ligne->prodId;
        $sql .= ' AND ROUND(e.prix_achat,2)  = ROUND(' . str_replace(',', '.', $ligne->price_unity) . ',2)';

        //echo $sql."\n";

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
                    $this->addEquipmentsLivraison($now, $product['id_prod'], $product['serial'], $entrepotId, $product['price']);
                } else {    // non serialisable
                    $result = $doliProduct->correct_stock($user, $entrepotId, $product['qty'], 0, $labelmove, 0, $codemove, 'order_supplier', $this->commande->id);
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

    function addEquipmentsLivraison($now, $prodId, $serial, $entrepotId, $price) {
        $length = sizeof($this->errors);
        $equipement = BimpObject::getInstance('bimpequipment', 'Equipment');

        $equipement->validateArray(array(
            'id_product' => $prodId, // ID du produit. 
            'type' => 2, // cf $types
            'serial' => $serial, // num série
            'reserved' => 0, // réservé ou non
            'warranty_type' => 0, // type de garantie (liste non définie actuellement)
            'admin_login' => '',
            'admin_pword' => '',
            'prix_achat' => $price,
            'note' => '',
            'origin_element' => 3,
            'origin_id_element' => $this->commande->id
        ));

        $this->errors = array_merge($this->errors, $equipement->create());

        $emplacement = BimpObject::getInstance('bimpequipment', 'BE_Place');

        $emplacement->validateArray(array(
            'id_equipment' => $equipement->id,
            'type' => 2, // cf $types
            'id_entrepot' => $entrepotId, // si type = 2
            'infos' => $this->getcodeMove(),
            'date' => dol_print_date($now, '%Y-%m-%d %H:%M:%S'), // date et heure d'arrivée
            'code_mvt' => $this->getcodeMove()
        ));
        $this->errors = array_merge($this->errors, $emplacement->create());
        if ($length != sizeof($this->errors))
            $this->errors[] = ' id : ' . $prodId . ' numéro de série : ' . $serial;
    }

    private function getcodeMove() {
        return "BimpLivraison" . $this->orderId;
    }

    public function getNomUrl($withpicto = 0) {
        $link = DOL_URL_ROOT . '/bimplogistique/index.php?fc=commandeFourn&id=' . $this->orderId;
        if ($withpicto == 0)
            $name = $this->ref;
        else
            $name = '<img src="' . DOL_URL_ROOT . '/bimpequipment/manageequipment/css/livraison.png"> ' . $this->ref;
        return '<a href="' . $link . '">' . $name . '</a>';
    }

    public function getOrders($fk_warehouse, $status_min, $status_max) {

        $orders = array();

        $sql = 'SELECT cf.rowid as id';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'commande_fournisseur_extrafields as e';
        $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur as cf ON cf.rowid = e.fk_object';
        $sql .= ' WHERE e.entrepot=' . $fk_warehouse;
        $sql .= ' AND fk_statut >= ' . $status_min . " AND fk_statut <= " . $status_max;

        $result = $this->db->query($sql);
        if ($result and mysqli_num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $bl = new BimpLivraison($this->db);
                $bl->fetch($obj->id);
                $fourn = new Societe($this->db);
                $fourn->fetch($bl->commande->socid);

                $status = $bl->commande->statut;
                if ($status == 0)
                    $name_status = 'Brouillon';
                elseif ($status == 1)
                    $name_status = 'Validée';
                elseif ($status == 2)
                    $name_status = 'Approuvée';
                elseif ($status == 3)
                    $name_status = 'En cours';
                elseif ($status == 4)
                    $name_status = 'Reçu partiellement';
                elseif ($status == 5)
                    $name_status = 'Reçu';
                else
                    $name_status = 'Inconnue';

                $orders[] = array(
                    'id' => $bl->orderId,
                    'url_fourn' => $fourn->getNomUrl(1),
                    'name_status' => $name_status,
                    'url_ref' => $bl->commande->getNomUrl(1),
                    'date_opening' => dol_print_date($bl->commande->date_commande),
                    'url_livraison' => $bl->getNomUrl(1)
                );
            }
        } elseif (!$result) {
            $this->errors[] = "Erreur lors de la requête de recherche de commande";
            return false;
        }

        return $orders;
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
