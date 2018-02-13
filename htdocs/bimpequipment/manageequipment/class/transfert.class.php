<?php

include_once '../../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

include_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

class Transfert {

    private $db;
    private $entrepotIdStart;
    private $entrepotIdEnd;
    private $ligneTransfert;    // ligne transfert
    private $user;

    public function __construct($db, $entrepotIdStart, $entrepotIdEnd, $user) {
        $this->db = $db;
        $this->entrepotIdStart = $entrepotIdStart;
        $this->entrepotIdEnd = $entrepotIdEnd;
        $this->user = $user;
    }

    public function addLignes($prodAndEquipment) {
        $this->ligneTransfert = array();
        foreach ($prodAndEquipment as $ligne) {
            if ($ligne['is_equipment'] == 'true') {
                $ligneObject = new LigneTransfert($this->db, intval($ligne['id_product']), 1, $ligne['serial']);
            } else {
                $ligneObject = new LigneTransfert($this->db, intval($ligne['id_product']), intval($ligne['qty']), '');
            }
            $this->ligneTransfert[] = $ligneObject;
        }
    }

    public function execute() {

        $now = dol_now();
        $codemove = dol_print_date($now, '%y%m%d%H%M%S');
        $label = 'Transférer stock Bimp ' . dol_print_date($now, '%Y-%m-%d %H:%M');
        $this->db->begin();
        $errors = array();
        foreach ($this->ligneTransfert as $ligne) { // Loop on each movement to do
            $errors = array_merge($errors, $ligne->transfert($this->entrepotIdStart, $this->entrepotIdEnd, $this->user, $label, $codemove));
        }
        if (sizeof($errors) == 0)
            $this->db->commit();
        return $errors;
    }

}

class LigneTransfert {

    private $db;
    public $id_product;
    public $qty;
    public $serial;

    public function __construct($db, $id_product, $qty, $serial) {
        $this->db = $db;
        $this->id_product = $id_product;
        $this->qty = $qty;
        $this->serial = $serial;
    }

    public function transfert($entrepotIdStart, $entrepotIdEnd, $user, $label, $codemove) {
        $errors = array();

        $product = new Product($this->db);
        $product->fetch($this->id_product);
        if ($this->serial != '')
            $label = 'Transférer stock Bimp ' . $this->serial;

        // Remove stock
        $result1 = $product->correct_stock($user, $entrepotIdStart, $this->qty, 1, $label, 0, $codemove);
        if ($result1 < 0) {
            $errors[] = $product->errors;
            $errors[] = $product->errorss;
        }

        // Add stock
        $result2 = $product->correct_stock($user, $entrepotIdEnd, $this->qty, 0, $label, 0, $codemove);
        if ($result2 < 0) {
            $errors[] = $product->errors;
            $errors[] = $product->errorss;
        }

        if ($this->serial != '') {  // if it is an equipment
            $id_equipment = getIdBySerial($this->db, $this->serial);

            $emplacement = BimpObject::getInstance('bimpequipment', 'BE_Place');
            $emplacement->validateArray(array(
                'id_equipment' => $id_equipment,
                'type' => 2, // type entrepot
                'id_entrepot' => $entrepotIdEnd, // si type = 2
                'infos' => '...',
                'date' => '2018-01-01 00:00:00' // date et heure d'arrivée TODO
            ));
            $errors = array_merge($errors, $emplacement->create());
//            var_dump($emplacement);
        }
        return $errors;
    }

}

function getIdBySerial($db, $serial) {
    $sql = 'SELECT id';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'be_equipment';
    $sql .= ' WHERE serial="' . $serial . '"';

    $result = $db->query($sql);
    if ($result and mysqli_num_rows($result) > 0) {
        while ($obj = $db->fetch_object($result)) {
            $id = $obj->id;
        }
        return $id;
    }
    return 'Aucun équipment correspond à ce numéro de série';
}

class BimpFournOrderReception {

    private $db;

//    public $orderId;
//    public $statut;

    function __construct($db) {
        $this->db = $db;
    }

    /* Get every line of the order */

    function getLigneOrder($orderId) {
        $lignes = array();

        $sql = 'SELECT rowid, fk_product, ref, label, qty, subprice';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'commande_fournisseurdet';
        $sql .= ' WHERE fk_commande=' . $orderId;

        $result = $this->db->query($sql);
        if ($result and $this->db->num_rows($result) > 0) {
            while ($obj = $this->db->fetch_object($result)) {
                $lignes[$obj->rowid] = array('productId' => $obj->fk_product,
                    'ref' => $obj->ref,
                    'label' => dol_trunc($obj->label, 25),
                    'qty' => $obj->qty,
                    'price_u' => price2num($obj->subprice) . ' €');
            }
        }
        return $lignes;
    }

    function addInStock($products, $orderId, $entrepotId, $user, $isTotal) {
        $errors = array();
        $lps = array();
        $now = dol_now();
        $order = new CommandeFournisseur($this->db);
        $order->fetch($orderId);
        $labelmove = 'Reception commande bimp ' . $order->ref . ' ' . dol_print_date($now, '%Y-%m-%d %H:%M');
        $codemove = dol_print_date($now, '%y%m%d%H%M%S');

        foreach ($products as $product) {
            $lp = new LignePanier($this->db);
            if ($lp->check((isset($product['ref']) ? $product['ref'] : $product['serial']), $entrepotId) != false) {
                
            } else {
                $errors[] = $lp->error;
            }
            $lps[] = $lp;
        }
// loop product
//        foreach ($products as $product) {
//            $productObject = new Product($this->db);
//            $productObject->fetch($product['id_prod']);
//
//            // Add stock
//            $result = $productObject->correct_stock($user, $entrepotId, $product['qty'], 0, $labelmove, 0, $codemove);
//            if ($result < 0) {
//                $errors[] = $productObject->errors;
//                $errors[] = $productObject->errorss;
//            }
//        }

        $type = ($isTotal == 'false') ? 'par' : 'tot';

        $order->Livraison($user, $now, $type, $labelmove); // last argument = comment, TODO add texterea ?

        return $errors;
    }

}
