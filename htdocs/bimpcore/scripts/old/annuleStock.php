<?php



require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';



$sql = $db->query('SELECT a.*
FROM llx_product_stock a
WHERE (a.fk_entrepot IN ("52","54","56","58","62","66","68","72","74","178","180","248","278","294","296","298")) AND reel != 0');

$nbEqui = $nbProd = 0;
while($ln = $db->fetch_object($sql)){
    $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $ln->fk_product);
//    $prod = new Bimp_Product();
    if($prod->getData('serialisable') == 1){
        $sql2 = $db->query("SELECT a.id
FROM llx_be_equipment a
LEFT JOIN llx_be_equipment_place a___places ON a___places.id_equipment = a.id
WHERE a___places.type IN ('2') AND (a___places.position <= '1') AND (a___places.id_entrepot = '".$ln->fk_entrepot."') AND (a.id_product = '".$ln->fk_product."')");
        if($db->num_rows($sql2) != $ln->reel)
            die('Probleme stock'.print_r($ln,true));
        while($ln2 = $db->fetch_object($sql2)){
            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $ln2->id);
//            $equipment = new Equipment();
            $equipment->moveToPlace(4, 'C2BO', 'ToC2BO', 'Transfert C2BO');
            $nbEqui ++;
            
        }
    }
    else{
        $prod->correctStocks($ln->fk_entrepot, $ln->reel, true, 'ToC2BO', 'Transfert C2BO');
        $nbProd += $ln->reel;
    }
}

echo 'fin : '.$nbProd.' prod + '.$nbEqui.' equipement';