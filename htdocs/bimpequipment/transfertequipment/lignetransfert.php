<?php


require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';


abstract class LigneTransfert {

    private $db;
    public $isEquipment;
    public $id_product;

    public function __construct($db, $isEquipment, $id_product) {
        $this->db = $db;
        $this->isEquipment = $isEquipment;
        $this->id_product = $id_product;
    }

}

class LigneTransfertProduct extends LigneTransfert {

    public $qty;

    public function __construct($db, $id_product, $qty) {
        parent::__construct($db, false, $id_product);
        $this->qty = $qty;
    }

}

class LigneTransfertEquipment extends LigneTransfert {

    public $serial;

    public function __construct($db, $id_product, $serial) {
        parent::__construct($db, true, $id_product);
        $this->serial = $serial;
    }

}






/**
 * @param type $entrepotIdStart entrepot de départ
 * @param type $entrepotIdEnd   entrepot d'arrivé
 * @param type $prodAndEquipment     liste de produits et équipement
 */
function makeTransfert($db, $entrepotIdStart, $entrepotIdEnd, $prodAndEquipment) {
    $products = array();
    $equipments = array();

    foreach ($prodAndEquipment as $ligne) {
        if ($ligne['is_equipment']) {
            $equipment = new LignePanierEquipment($db, $ligne['id_product'], $ligne['serial']);
            $equipments[] = $equipment;
        } else {
            $product = new LignePanierProduct($db, $ligne['id_product'], $ligne['qty']);
            $products[] = $product;
        }
    }

    $db->begin();


    $errors = array();

    foreach ($products as $ligne) { // Loop on each movement to do
        $id = $val['id']; // TODO
        $id_product = $ligne->id_product;
        $qty = (isset($ligne->qty)) ? $ligne->qty : 1 ;
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

        if (!$ligne->isEquipment) {  // If product does not need lot/serial
            // Remove stock
            $result1 = $product->correct_stock(
                    $user, $entrepotIdStart, $qty, 1, GETPOST("label"), $pricesrc, GETPOST("codemove")
            );
            if ($result1 < 0) {
                $errors[]  = $product->errors;
                $errors[]  = $product->errorss;
            }

            // Add stock
            $result2 = $product->correct_stock(
                    $user, $entrepotIdEnd, $qty, 0, GETPOST("label"), $pricedest, GETPOST("codemove")
            );
            if ($result2 < 0) {
                $errors[]  = $product->errors;
                $errors[]  = $product->errorss;
            }
        } else {
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
                    $user, $entrepotIdStart, $qty, 1, GETPOST("label"), $pricesrc, $dlc, $dluo, $batch, GETPOST("codemove")
            );
            if ($result1 < 0) {
                $errors[]  = $product->errors;
                $errors[]  = $product->errorss;
            }

            // Add stock
            $result2 = $product->correct_stock_batch(
                    $user, $entrepotIdEnd, $qty, 0, GETPOST("label"), $pricedest, $dlc, $dluo, $batch, GETPOST("codemove")
            );
            if ($result2 < 0) {
                $errors[]  = $product->errors;
                $errors[]  = $product->errorss;
            }
        }
    }
    $db->commit();
}

/*

fk_autor
code mouvement
label mouvement



 */