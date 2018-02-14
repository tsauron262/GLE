<?php

include_once '../../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/lignepanier.class.php';

class BimpLivraison {

    private $db;
    public $orderId;
    public $statut;
    public $errors = array();

    function __construct($db) {
        $this->db = $db;
    }

    function fetch($orderId) {
        $this->orderId = $orderId;
        $doliFournOrder = new CommandeFournisseur($this->db);
        $doliFournOrder->fetch($orderId);
        $this->statut = $doliFournOrder->statut;
    }

    /* Get every line of the order */

    /**
     * remove
     * @deprecated
     */
    function getLignesOrder() {
        $lignes = array();

        $sql = 'SELECT rowid, fk_product, ref, label, qty, subprice';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet';
        $sql .= ' WHERE fk_commande=' . $this->orderId;

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $doliProd = new Product($this->db);
                $doliProd->fetch($obj->fk_product);
                $ligne = new LigneLivraison($this->db);
                $ligne->prodId = $obj->fk_product;
                $ligne->label = dol_trunc($obj->label, 25);
//                $ligne->qty = $obj->qty;
                $ligne->remainingQty = $obj->qty;
                $ligne->price_unity = price($obj->subprice);
                $ligne->isDelivered = false;
                $ligne->isEquipment = $ligne->isSerialisable();
                $ligne->refurl = $doliProd->getNomUrl(1);


                $lignes[] = $ligne;
            }
        }
        return $lignes;
    }

//            // List of language codes for status
//        $this->statuts[0] = 'StatusOrderDraft';
//        $this->statuts[1] = 'StatusOrderValidated';
//        $this->statuts[2] = 'StatusOrderApproved';
//        if (empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS)) $this->statuts[3] = 'StatusOrderOnProcess';
//        else $this->statuts[3] = 'StatusOrderOnProcessWithValidation';
//        $this->statuts[4] = 'StatusOrderReceivedPartially';
//        $this->statuts[5] = 'StatusOrderReceivedAll';
//        $this->statuts[6] = 'StatusOrderCanceled';	// Approved->Canceled
//        $this->statuts[7] = 'StatusOrderCanceled';	// Process running->canceled
//        //$this->statuts[8] = 'StatusOrderBilled';	// Everything is finished, order received totally and bill received
//        $this->statuts[9] = 'StatusOrderRefused';
//    class LignePanier {
//

    function getRemainingLignes() {
        // StatusOrderValidated or StatusOrderApproved or StatusOrderOnProcess
//        if ($this->statut == 1 or $this->statut == 2 or $this->statut == 3) {
//            return $this->getLignesOrder();
//        } else if ($this->statut == 4) { // ReceivedPartially
        $initLignes = $this->getLignesOrder();
//        }
        return $initLignes;
    }

    function addInStock($products, $orderId, $entrepotId, $user, $isTotal) {
        $now = dol_now();
        $order = new CommandeFournisseur($this->db);
        $order->fetch($orderId);
        $labelmove = 'Reception commande bimp ' . $order->ref . ' ' . dol_print_date($now, '%Y-%m-%d %H:%M');
        $codemove = $order->ref;

        foreach ($products as $product) {
            $doliProduct = new Product($this->db);
            $doliProduct->fetch($product['id_prod']);
            $length = sizeof($this->errors);

            // Add stock
            $result = $doliProduct->correct_stock($user, $entrepotId, (isset($product['qty']) ? $product['qty'] : 1), 0, $labelmove, 0, $codemove);
            if ($result < 0) {
                $this->errors = array_merge($this->errors, $doliProduct->errors);
                $this->errors = array_merge($this->errors, $doliProduct->errorss);
            }

            if ($length != sizeof($this->errors))
                $this->errors[] = ' id : ' . $product['id_prod'];

            if (!isset($product['qty'])) {   // non serialisable
                $this->addEquipmentsLivraison($now, $product['id_prod'], $product['serial'], $entrepotId);
            }
        }

        $type = ($isTotal == 'false') ? 'par' : 'tot';

        $order->Livraison($user, $now, $type, $labelmove); // last argument = comment, TODO add texterea ?

        return array('errors' => $this->errors);
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
            'infos' => '...',
            'date' => '2018-01-01 00:00:00' // date et heure d'arrivée
        ));
        $this->errors = array_merge($this->errors, $emplacement->create());
        if ($length != sizeof($this->errors))
            $this->errors[] = ' id : ' . $prodId . ' numéro de série : ' . $serial;
    }

}

class LigneLivraison extends LignePanier {

    public $label;
    public $qty;
    public $remainingQty;
    public $price_unity;
    public $isEquipment;
    public $isDelivered;
    public $refurl;

}
