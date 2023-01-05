<?php
/*
 * * BIMP-ERP by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 26 oct. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : index.php
 * BIMP-ERP-1.2
 */
require_once('../main.inc.php');




//$log1 = "tommy@bimp.fr";
//$command = 'git pull https://'. urlencode($log1).":".urlencode($log2).'@git2.bimp.fr/BIMP/bimp-erp.git';
//$result = array();
//$retour = exec("cd ".DOL_DOCUMENT_ROOT, $result);
//$retour .= exec($command, $result);
//foreach ($result as $line) {
//    print($line . "\n");
//}
//die($retour."fin");

llxHeader();

//categorie sans prod
$tables = array('ps_category', 'ps_category_group', 'ps_category_lang', 'ps_category_shop');

$tables = array('ps_feature', 'ps_feature_lang', 'ps_feature_shop', 'ps_feature_value', 'ps_feature_value_lang');

$tables = array('ps_product', 'ps_product_lang', 'ps_product_shop', 'ps_product_supplier', 'ps_product_attachment', 'ps_category_product', 'ps_feature_product');

$tables = array('ps_image', 'ps_image_lang', 'ps_image_shop', 'ps_image_type');

foreach ($tables as $table){
    $oldTable = str_replace("ps_", "pre2902_", $table);
    $newTable = str_replace("ps_", "pre2902_SAUV_", $table);
    echo '<br/><br/>ALTER TABLE '.$oldTable.' RENAME TO '.$newTable.';';
    
    
    $oldTable = $table;
    $newTable = str_replace("ps_", "pre2902_", $table);
    echo '<br/><br/>ALTER TABLE '.$oldTable.' RENAME TO '.$newTable.';';
}

//revert
//echo '<br/><br/>revert';
//foreach ($tables as $table){
//    $oldTable = str_replace("ps_", "pre2902_", $table);
//    $newTable = $table;
//    echo '<br/><br/>ALTER TABLE '.$oldTable.' RENAME TO '.$newTable.';';
//    
//    
//    $oldTable = str_replace("ps_", "pre2902_SAUV_", $table);;
//    $newTable = str_replace("ps_", "pre2902_", $table);
//    echo '<br/><br/>ALTER TABLE '.$oldTable.' RENAME TO '.$newTable.';';
//}


llxFooter();
?>
