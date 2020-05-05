<?php


require_once("../../main.inc.php");


require_once __DIR__ . '/../Bimp_Lib.php';



$sql = $db->query("SELECT rowid FROM `llx_product` WHERE fk_product_type = 0");
$idProdOk[] = array();
while ($ln = $db->fetch_object($sql)){
    $idProdOk[$ln->rowid] = $ln->rowid;
}



$sql = $db->query("SELECT va.*, v.id_entrepot FROM `llx_bc_vente_article` va, llx_bc_vente v WHERE va.id_vente = v.id AND v.status = 2 AND `date_create` > '2019-10-01 00:00:00'");
while ($ln = $db->fetch_object($sql)){
    if(isset($idProdOk[$ln->id_product])){
        $sql2 = $db->query("SELECT * FROM `llx_stock_mouvement` WHERE inventorycode LIKE 'VENTE".$ln->id_vente."_ART".$ln->id."' AND fk_entrepot = ".$ln->id_entrepot." AND fk_product = ".$ln->id_product. " AND value = -".$ln->qty);
        if($db->num_rows($sql2) != 1){
            echo ("<br/>probléme vente : ".$ln->id_vente." prod : ".$ln->id_product. " qty : ".$ln->qty);
            
            $sql3 = $db->query("SELECT * FROM `llx_stock_mouvement` WHERE inventorycode LIKE 'inventory%' AND fk_entrepot = ".$ln->id_entrepot." AND fk_product = ".$ln->id_product. " AND value = -".$ln->qty." AND `tms` > '2020-03-01 00:00:00'");
            while ($ln3 = $db->fetch_object($sql3))
                    echo "<br/>     Peut être le mouvment : ".$l3->rowid;
        }
    }
}
