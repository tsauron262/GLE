<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'INSERT PRODUCTS CUR PA', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

$idCatMereMarqueExt = 9564;
$idCatMereMarqueExt = 9693;
$ok = 0;
//pas de collection mais une marque
$sql = $db->query("SELECT fk_object FROM `llx_product_extrafields` WHERE `collection` is null AND fk_object IN (SELECT `fk_product` FROM `llx_categorie_product` WHERE `fk_categorie` IN(SELECT `rowid` FROM `llx_categorie` WHERE `fk_parent` = ".$idCatMereMarqueExt."))");
while($ln = $db->fetch_object($sql)){
    $idProd = $ln->fk_object;
    $sql2 = $db->query("SELECT c.label FROM `llx_categorie_product` cp, `llx_categorie` c WHERE `fk_parent` = ".$idCatMereMarqueExt." AND cp.`fk_categorie` = c.rowid AND `fk_product` = ".$idProd);
    $ln2 = $db->fetch_object($sql2);
    $nomCat = $ln2->label;
    if($nomCat != "A catégoriser"){
        $ok++;
        echo 'prod '.$idProd.' ajout Collection '.$nomCat."<br/><br/>";
       // $db->query('UPDATE llx_product_extrafields SET collection ="'.$nomCat.'" WHERE fk_object = '.$idProd);
    }
}



//toute les lignes restant
$sql = $db->query("SELECT p.* FROM `llx_product_extrafields` pe, llx_product p WHERE `categorie` IS NULL AND `nature` is null AND `collection` is null AND famille is null AND pe.fk_object = p.rowid AND validate = 1 ORDER BY `p`.`rowid` DESC");
echo $db->num_rows($sql)." probléme restant.<br/>";

echo $ok." probléme resolu.";