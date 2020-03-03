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

$idCatMereMarqueExt = array(9564, 9693);
$ok = 0;
//pas de collection mais une marque
$sql = $db->query("SELECT fk_object FROM `llx_product_extrafields` WHERE `collection` is null AND fk_object IN (SELECT `fk_product` FROM `llx_categorie_product` WHERE `fk_categorie` IN(SELECT `rowid` FROM `llx_categorie` WHERE `fk_parent` IN ( ".implode(",",$idCatMereMarqueExt).")))");
while($ln = $db->fetch_object($sql)){
    $idProd = $ln->fk_object;
    $sql2 = $db->query("SELECT c.label FROM `llx_categorie_product` cp, `llx_categorie` c WHERE `fk_parent` IN ( ".implode(",",$idCatMereMarqueExt).") AND cp.`fk_categorie` = c.rowid AND `fk_product` = ".$idProd);
    $ln2 = $db->fetch_object($sql2);
    $nomCat = $ln2->label;
    if($nomCat != "A catégoriser"){
        $ok++;
        echo 'prod '.$idProd.' ajout Collection '.$nomCat."<br/><br/>";
        $db->query('UPDATE llx_product_extrafields SET collection ="'.$nomCat.'" WHERE fk_object = '.$idProd);
    }
}



//toute les lignes restant
$sql = $db->query("SELECT p.* FROM `llx_product_extrafields` pe, llx_product p WHERE (`categorie` IS NULL && `nature` is null && `collection` is null && famille is null) AND pe.fk_object = p.rowid AND validate = 1 AND tosell = 1 ORDER BY `p`.`rowid` DESC");
echo $db->num_rows($sql)." problémes restant en tout. (aucune infos)<br/>";
$sql = $db->query("SELECT p.* FROM `llx_product_extrafields` pe, llx_product p WHERE (`categorie` IS NULL || `nature` is null || `collection` is null || famille is null) AND pe.fk_object = p.rowid AND validate = 1 AND tosell = 1 ORDER BY `p`.`rowid` DESC");
echo $db->num_rows($sql)." problémes restant en tout. (manque 1 infos)<br/>";

echo $ok." probléme resolu.";



foreach(array('collection', 'categorie', 'nature', 'famille', 'gamme') as $type){
    $sql = $db->query('SELECT DISTINCT(`'.$type.'`) as label FROM `llx_product_extrafields` WHERE 1');
    while($ln = $db->fetch_object($sql)){
        $label = $ln->label;
        if($label != '' && filter_var($label, FILTER_VALIDATE_INT) === false){
            $sql2 = $db->query('SELECT * FROM `llx_bimp_c_values8sens` WHERE type="'.$type.'" AND label LIKE "'.$label.'"');
            $idC = null;
            while($ln2 = $db->fetch_object($sql2)){
                $idC = $ln2->id;
            }
            if(is_null($idC)){
                $db->query("INSERT INTO llx_bimp_c_values8sens( `label`, `type`) VALUES ('".addslashes($label)."', '".$type."')");
                $idC = $db->last_insert_id('llx_bimp_c_values8sens');
                echo "<br/>creation de ".$label." new id : ".$idC."<br/>";
            }
            
            if(!is_null($idC)){
                $db->query('UPDATE `llx_product_extrafields` SET '.$type.'='.$idC.' WHERE '.$type.' LIKE "'.$label.'"');
            }
        }
    }
}






