<?php


require '../../main.inc.php';


llxHeader();




$sql=$db->query("SELECT p.rowid FROM `llx_product` p, `llx_product_extrafields` pe WHERE p.rowid = pe.fk_object AND pe.`serialisable` = 1;");

echo "Début<br/>";
while ($ligne = $db->fetch_object($sql)){
    $idProd = $ligne->rowid;
    echo "<br/>Prod Id : ".$idProd."<br/>";
    $sql2 = $db->query("SELECT rowid, label FROM `llx_entrepot` WHERE 1");
    while($ligne2 = $db->fetch_object($sql2)){
        $idEntrepot = $ligne2->rowid;
        $nomEntrepot = $ligne2->label;
        $nbStock = 0;
        $nbEq = 0;
        echo "Entrepot ".$nomEntrepot."<br/>";
        $sql3 = $db->query("SELECT `reel` FROM `llx_product_stock` WHERE `fk_entrepot` = ".$idEntrepot." AND `fk_product` = ".$idProd);
        while($ligne3 = $db->fetch_object($sql3)){
            $nbStock = $ligne3->reel;
        }
        $sql4 = $db->query("SELECT COUNT(*) as nbEq FROM `llx_be_equipment` e, `llx_be_equipment_place` ep WHERE e.id = ep.`id_equipment` AND `id_entrepot` = ".$idEntrepot." AND `id_product` = ".$idProd." AND `position` = 1");
        while($ligne4 = $db->fetch_object($sql4)){
            $nbEq += $ligne4->nbEq;
        }
        
        
        echo "<div class='red'>";
        if($nbStock > $nbEq)
            echo "Attention plus de Stock que d'équipement<br/>";
        if($nbStock < $nbEq)
            echo "Attention plus d'équipement que de Stock<br/>";
        
        if($nbStock != $nbEq)
            echo $nbStock." en Stock ".$nbEq." équipement dans l'entrepot ".$nomEntrepot."<br/>";
        echo "</div>";
    }
}


llxFooter();