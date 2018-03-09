<?php

include_once '../../../main.inc.php';

include_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

/**
 * @deprecated 
 */
class BimpOrderClient {

    private $db;
    public $order;
    public $order_id;
    public $status;
    public $ref;
    public $errors = array();

    /*
     * Function CRUD
     */

    function __construct($db) {
        $this->db = $db;
    }

    public function fetch($order_id, $ref) {
        $doli_order_client = new Commande($this->db);
        $doli_order_client->fetch($order_id, $ref);
        $this->order_id = $order_id;
        $this->statut = $doli_order_client->statut;
        $this->ref = "exp" . $doli_order_client->ref;
        $this->order = $doli_order_client;
    }

    /*
     * Functions triggered by the inteface
     */

    public function retrieveOrderClient() {

        $order = array();

        foreach ($this->order->lines as $line) {
            if ($line->fk_product > 0) {
                $doli_product = new Product($this->db);
                $doli_product->fetch($line->fk_product);
                $parsed_line = array();

                $parsed_line['fk_product'] = $doli_product->id;
                $parsed_line['ref'] = $doli_product->ref;
                $parsed_line['barcode'] = $doli_product->barcode;
                $parsed_line['ref_url'] = $doli_product->getNomUrl(1);
                $parsed_line['label'] = dol_trunc($doli_product->label, 70);
                $parsed_line['qty_total'] = $line->qty;
                $parsed_line['qty_previous_session'] = 1; // TODO remove

                $order['lines'][] = $parsed_line;
            }
        }

        return $order;
    }

    /*
     * Other functions
     */
}
