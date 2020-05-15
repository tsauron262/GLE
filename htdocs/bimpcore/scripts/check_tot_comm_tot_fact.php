<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'CHECK COMMANDES', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;



$sql = $db->query("SELECT c.rowid as crowid, f.rowid as frowid, c.total_ht, f.total FROM `llx_element_element` el, llx_commande c, llx_facture f WHERE `sourcetype` LIKE 'commande' ANd targettype LIKE 'facture'
AND c.rowid = el.fk_source AND f.rowid = el.fk_target
AND f.`datec` > '2019-10-01 00:00:00'
".//"AND c.total_ht+0.10 < f.total".
"AND f.type = 0 
ORDER BY `el`.`rowid`  DESC");

$i= $tot =0;
while ($ln = $db->fetch_object($sql)){
        $sql2 = $db->query("SELECT * FROM `llx_facturedet` WHERE total_ht > 0 AND `fk_facture` = ".$ln->frowid. ' AND fk_product > 0 AND fk_product NOT IN (SELECT `fk_product` FROM `llx_commandedet` WHERE `fk_commande` = '.$ln->crowid.')');
        while ($ln2 = $db->fetch_object($sql2)){
            if($i > 200)
                die('fin anticipipé tot prov : '.$tot);
            $fact = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', $ln->frowid);
            echo $fact->getLink()."<br/>";
            $comm = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', $ln->crowid);
            echo $comm->getLink()."<br/>";
//            print_r($ln2);
            echo $ln2->total_ht;
            $tot += $ln2->total_ht;
            echo "<br/><br/>";
            $i++;
        }
//        die("SELECT * FROM `llx_commandedet` WHERE `fk_commande` = ".$ln->crowid. ' AND fk_product > 0 AND fk_product NOT IN (SELECT `fk_product` FROM `llx_facturedet` WHERE `fk_facture` = '.$ln->frowid.')');
}


echo "fin normal. Tot :".$tot;
