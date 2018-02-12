<?php

include_once '../../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
include_once DOL_DOCUMENT_ROOT . '/bimpequipment/manageequipment/class/lignepanier.class.php';

class BimpFournOrder {

    private $db;
    public $orderId;
    public $statut;

    function __construct($db) {
        $this->db = $db;
    }

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

    function addInStock($products, $orderId) {
        $order = CommandeFournisseur($this->db);
        $order->fetch($orderId);
        $codeName = 'Réception commande bimp ' . $order->ref;
        
        return $orderId;
    }

}
