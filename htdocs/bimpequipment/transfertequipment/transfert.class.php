<?php

require_once '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

abstract class LigneTransfert {

    private $db;
    public $isEquipment;
    public $id_product;
    public $entrepotIdStart;
    public $entrepotIdEnd;

    public function __construct($db, $isEquipment, $id_product, $entrepotIdStart, $entrepotIdEnd) {
        $this->db = $db;
        $this->isEquipment = $isEquipment;
        $this->id_product = $id_product;
        $this->entrepotIdStart = $entrepotIdStart;
        $this->entrepotIdEnd = $entrepotIdEnd;
    }

}

class LigneTransfertProduct extends LigneTransfert {

    public $qty;

    public function __construct($db, $id_product, $qty, $entrepotIdStart, $entrepotIdEnd) {
        parent::__construct($db, false, $id_product, $entrepotIdStart, $entrepotIdEnd);
        $this->qty = $qty;
    }

    public function transfert() {
        $id = $val['id']; // TODO
        $id_product = $ligne->id_product;
        $qty = (isset($ligne->qty)) ? $ligne->qty : 1;
        $batch = $val['batch']; // TODO
        $dlc = -1;  // They are loaded later from serial
        $dluo = -1;  // They are loaded later from serial

        $result = $product->fetch($id_product);

        $product->load_stock('novirtual'); // Load array product->stock_warehouse
        // Define value of products moved
        $pricesrc = 0;
        if (!empty($product->pmp))
            $pricesrc = $product->pmp;
        $pricedest = $pricesrc;

        //print 'price src='.$pricesrc.', price dest='.$pricedest;exit;
        // Remove stock
        $result1 = $product->correct_stock(
                $user, $this->entrepotIdStart, $qty, 1, GETPOST("label"), $pricesrc, GETPOST("codemove")
        );
        if ($result1 < 0) {
            $errors[] = $product->errors;
            $errors[] = $product->errorss;
        }

        // Add stock
        $result2 = $product->correct_stock(
                $user, $this->entrepotIdEnd, $qty, 0, GETPOST("label"), $pricedest, GETPOST("codemove")
        );
        if ($result2 < 0) {
            $errors[] = $product->errors;
            $errors[] = $product->errorss;
        }
    }

}

class LigneTransfertEquipment extends LigneTransfert {

    public $serial;

    public function __construct($db, $id_product, $serial, $entrepotIdStart, $entrepotIdEnd) {
        parent::__construct($db, true, $id_product, $entrepotIdStart, $entrepotIdEnd);
        $this->serial = $serial;
    }

    public function transfert() {
        $id = $val['id']; // TODO
        $id_product = $this->id_product;
        $qty = (isset($ligne->qty)) ? $ligne->qty : 1;
        $batch = $val['batch']; // TODO
        $dlc = -1;  // They are loaded later from serial
        $dluo = -1;  // They are loaded later from serial

        $result = $product->fetch($id_product);

        $product->load_stock('novirtual'); // Load array product->stock_warehouse
        // Define value of products moved
        $pricesrc = 0;
        if (!empty($product->pmp))
            $pricesrc = $product->pmp;
        $pricedest = $pricesrc;

        //print 'price src='.$pricesrc.', price dest='.$pricedest;exit;

        $arraybatchinfo = $product->loadBatchInfo($batch);
        if (count($arraybatchinfo) > 0) {
            $firstrecord = array_shift($arraybatchinfo);
            $dlc = $firstrecord['eatby'];
            $dluo = $firstrecord['sellby'];
            //var_dump($batch); var_dump($arraybatchinfo); var_dump($firstrecord); var_dump($dlc); var_dump($dluo); exit;
        } else {
            $dlc = '';
            $dluo = '';
        }

        // Remove stock
        $result1 = $product->correct_stock_batch(
                $user, $this->entrepotIdStart, $qty, 1, GETPOST("label"), $pricesrc, $dlc, $dluo, $batch, GETPOST("codemove")
        );
        if ($result1 < 0) {
            $errors[] = $product->errors;
            $errors[] = $product->errorss;
        }

        // Add stock
        $result2 = $product->correct_stock_batch(
                $user, $this->entrepotIdEnd, $qty, 0, GETPOST("label"), $pricedest, $dlc, $dluo, $batch, GETPOST("codemove")
        );
        if ($result2 < 0) {
            $errors[] = $product->errors;
            $errors[] = $product->errorss;
        }
    }

}

class Transfert {

    private $db;
    private $entrepotIdStart;
    private $entrepotIdEnd;
    private $lt;    // ligne transfert

    public function __construct($db, $prodAndEquipment, $entrepotIdStart, $entrepotIdEnd) {
        $this->db = $db;
        $this->entrepotIdStart = $entrepotIdStart;
        $this->entrepotIdEnd = $entrepotIdEnd;
        $products = array();
        $equipments = array();

        foreach ($prodAndEquipment as $ligne) {
            if ($ligne['is_equipment']) {
                $equipment = new LigneTransfertEquipment($this->db, $ligne['id_product'], $ligne['serial'], $this->entrepotIdStart, $this->entrepotIdEnd);
                $equipments[] = $equipment;
            } else {
                $product = new LigneTransfertProduct($this->db, $ligne['id_product'], $ligne['qty'], $this->entrepotIdStart, $this->entrepotIdEnd);
                $products[] = $product;
            }
        }
        $this->lt = array_merge($products, $equipments);
        print_r($this->lt);
    }

    /**
     * @param type $entrepotIdStart entrepot de départ
     * @param type $entrepotIdEnd   entrepot d'arrivé
     * @param type $prodAndEquipment     liste de produits et équipement
     */
    function transfertAll() {

        $this->db->begin();
        $errors = array();
        foreach ($this->lt as $ligne) { // Loop on each movement to do
            $errors = array_merge($errors, $ligne->transfert());
        }
        $this->db->commit();
    }

}
