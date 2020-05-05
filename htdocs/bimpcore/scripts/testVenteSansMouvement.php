<?php


require_once("../../main.inc.php");


require_once __DIR__ . '/../Bimp_Lib.php';



$sql = $db->query("SELECT rowid FROM `llx_product` WHERE fk_product_type = 0");
$idProdOk[] = array();
while ($ln = $db->fetch_object($sql)){
    $idProdOk[$ln->rowid] = $ln->rowid;
}



$sql = $db->query("SELECT * FROM `llx_bc_vente_article` WHERE `id_vente` IN (SELECT id FROM llx_bc_vente v WHERE v.status = 2)");
while ($ln = $db->fetch_object($sql)){
    if(isset($idProdOk[$ln->id_product])){
        $sql2 = $db->query("SELECT * FROM `llx_stock_mouvement` WHERE inventorycode LIKE 'VENTE".$ln->id_vente."_ART".$ln->id."' AND fk_product = ".$ln->id_product. " AND value = -".$ln->qty);
        if($db->num_rows($sql2) != 1){
            echo ("<br/>probléme vente : ".$ln->id_vente." prod : ".$ln->id_product. " qty : ".$ln->qty);
        }
    }
}
