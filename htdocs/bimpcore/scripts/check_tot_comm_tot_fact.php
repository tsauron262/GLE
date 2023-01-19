<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'CHECK COMMANDES', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;




$sql = $db->query("SELECT `id_commande_client`, `id_facture` FROM `llx_bl_commande_shipment` 
WHERE `id_facture` NOT IN (SELECT `fk_target`  FROM `llx_element_element` WHERE `sourcetype` LIKE 'commande' AND `targettype` LIKE 'facture') AND id_facture > 0");
while ($ln = $db->fetch_object($sql)){
    echo 'mod<br/>';
    $db->query("INSERT INTO `llx_element_element`(`fk_source`, `sourcetype`, `fk_target`, `targettype`) VALUES (".$ln->id_commande_client.", 'commande', ".$ln->id_facture.", 'facture')");
}

$sql = $db->query("SELECT `id_commande_client`, `id_avoir` as id_facture FROM `llx_bl_commande_shipment` 
 WHERE `id_avoir` NOT IN (SELECT `fk_target`  FROM `llx_element_element` WHERE `sourcetype` LIKE 'commande' AND `targettype` LIKE 'facture') AND id_avoir > 0");
while ($ln = $db->fetch_object($sql)){
    echo 'mod<br/>';
    $db->query("INSERT INTO `llx_element_element`(`fk_source`, `sourcetype`, `fk_target`, `targettype`) VALUES (".$ln->id_commande_client.", 'commande', ".$ln->id_facture.", 'facture')");
}













$sql = $db->query("SELECT c.rowid as crowid, f.rowid as frowid, c.total_ht, f.total_ht FROM `llx_element_element` el, llx_commande c, llx_facture f WHERE `sourcetype` LIKE 'commande' ANd targettype LIKE 'facture'
AND c.rowid = el.fk_source AND f.rowid = el.fk_target".
" AND c.`date_creation` > '2019-10-01 00:00:00'".
//" AND c.total_ht+0.10 < f.total_ht".
//" AND f.type = 0 ".
" ORDER BY `el`.`rowid`  DESC");

$i= $tot =0;
$tabCommParFact = array();
$tabFactParComm = array();
while ($ln = $db->fetch_object($sql)){
    $tabCommParFact[$ln->frowid][] = $ln->crowid;
    $tabFactParComm[$ln->crowid][] = $ln->frowid;
}



//$sql34 = $db->query("SELECT rowid FROM `llx_facture` f1 WHERE `fk_soc` IN (SELECT `fk_soc` FROM llx_facture f2 WHERE f2.total_ht = -f1.total_ht)");
//
//$tabFactAvecAvoir = array();
//while ($ln34 = $db->fetch_object($sql34)){
//    $tabFactAvecAvoir[$ln34->rowid] = $ln34->rowid;
//}
$tabFactTraiter = array();
foreach($tabCommParFact as $idF => $tabIdC){
        /*$sql2 = $db->query("SELECT * FROM `llx_facturedet` fd WHERE total_ht > 0 AND `fk_facture` = ".$idF. ' AND fk_product > 0  '
                . ' AND fk_product IN (SELECT rowid FROM `llx_product` WHERE `fk_product_type` = 0) '
                . ' AND '
                . 'fk_product NOT IN (SELECT `fk_product` FROM `llx_commandedet` WHERE `fk_commande` IN ('. implode($tabIdC, ",").'))'
                . '');
        while ($ln2 = $db->fetch_object($sql2)){
            if($i > 200)
                die('fin anticipipé tot prov : '.$tot);
            $fact = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $idF);
            echo $fact->getLink()."<br/>";
            foreach($tabIdC as $idC){
                $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $idC);
                echo $comm->getLink()."<br/>";
            }
//            print_r($ln2);
            echo $ln2->total_ht;
            $tot += $ln2->total_ht;
            echo "<br/><br/>";
            $i++;
        }
        */
        
    
    if(!isset($tabFactTraiter[$idF])){
        $tabFTemp = array();
        foreach($tabIdC as $tempIdC){
            foreach($tabFactParComm[$tempIdC] as $tempIdF){
                $tabFTemp[$tempIdF] = $tempIdF;
                $tabFactTraiter[$tempIdF] = $tempIdF;
            }
        }
        
//        $sql2 = $db->query("SELECT SUM(qty) as qty, fk_product FROM `llx_facturedet` fd WHERE total_ht != 0 AND `fk_facture` IN (".implode($tabFTemp, ","). ') AND fk_product > 0  '
//                . ' AND fk_product IN (SELECT rowid FROM `llx_product` WHERE `fk_product_type` = 0) '
//                . ' HAVING '
//                . '(( '
//                . 'SUM(qty) > 0 AND SUM(qty) > (SELECT SUM(`qty_total`) FROM `llx_bimp_commande_line` bl, `llx_commandedet` cd WHERE `id_line` = cd.rowid AND cd.fk_product = fd.fk_product AND `fk_commande` IN ('. implode($tabIdC, ",").'))'
//                . ') || ('
//                . 'SUM(qty) < 0 AND SUM(qty) < (SELECT SUM(`qty_total`) FROM `llx_bimp_commande_line` bl, `llx_commandedet` cd WHERE `id_line` = cd.rowid AND cd.fk_product = fd.fk_product AND `fk_commande` IN ('. implode($tabIdC, ",").'))'
//                . '))'
//                . ' GROUP BY fk_product');
        
         $sql2 = $db->query("SELECT SUM(IF(fd.subprice>=0, qty, -qty)) as qtyF, SUM(fd.total_ht) as tot, fk_product FROM `llx_facturedet` fd WHERE "
                 . " `fk_facture` IN (".implode($tabFTemp, ","). ')  '
                 . ' AND fd.fk_product IN (SELECT rowid FROM `llx_product` WHERE `fk_product_type` = 0 AND price != 0) '
                 . ' GROUP BY fd.fk_product');
        while ($ln2 = $db->fetch_object($sql2)){
            if($i > 200)
                die('fin anticipipé tot prov : '.$tot);
            $sql3 = $db->query("SELECT SUM(IF(cd.subprice >= 0, bl.qty_total, -bl.qty_total)) as qtyC, SUM(IF(cd.subprice > 0 && bl.qty_total > 0, cd.subprice*bl.qty_total, -cd.subprice*bl.qty_total)) as tot, cd.fk_product FROM llx_commandedet cd, llx_bimp_commande_line bl WHERE "
                    . '  `id_line` = cd.rowid '
                    . ' AND cd.fk_product ='.$ln2->fk_product
                    . ' AND `fk_commande` IN ('. implode($tabIdC, ",").')'
                    . ' GROUP BY cd.fk_product');
            $ln3 = $db->fetch_object($sql3);
            $qtyFactNonComm = $ln2->qtyF - $ln3->qtyC;
            
            if($qtyFactNonComm > 0 && $ln2->tot != $ln3->tot && $ln2->qtyF > 0){
                foreach($tabFTemp as $tmpIdF){
                    $fact = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $tmpIdF);
                    echo $fact->getLink()."<br/>";
                }
                foreach($tabIdC as $idC){
                    $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $idC);
                    echo $comm->getLink()."<br/>";
                }
                if($ln3->qtyC == "")
                    $ln3->qtyC = 0;
                $prod = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $ln2->fk_product);
                $price = $prod->getData('price')*$qtyFactNonComm;
                echo $prod->getLink(). " ".price($price)." €";
                echo "<br/>en commande ".$ln3->qtyC;
                echo "<br/>en facture ".$ln2->qtyF;
                $tot += $price;
                echo "<br/><br/>";
                $i++;
            }
        }
    }
}


echo "fin normal. Tot : ".price($tot)." €";
