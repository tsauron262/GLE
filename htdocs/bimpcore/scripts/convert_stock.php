


<?php

define('NOLOGIN', '1');

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

//llxHeader();

echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
//echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/ticket.css' . '"/>';
echo '<script src="/test2/includes/jquery/js/jquery.min.js?version=6.0.4" type="text/javascript"></script>';
echo '</head>';

echo '<body>';

global $db;
$bdb = new BimpDb($db);



$db = $bdb->db;


$query = "SELECT `rowid`, `tms`, `datem`, `fk_product`, `fk_entrepot`, `value`, label FROM `llx_stock_mouvement` WHERE `origintype` LIKE 'facture' AND tms > '2019-07-01' AND `label` NOT LIKE '%Vente #%' AND `label` NOT LIKE '%#corrig%' ORDER BY `llx_stock_mouvement`.`value` ASC";
$sql = $db->query($query);

while ($ln = $db->fetch_object($sql)){
    $prod = new Product($db);
    $prod->fetch($ln->fk_product);
    $text = $ln->label;
    
    $val = $ln->value;
    $movement = 1;
    if($val < 0){
        $val = -$val;
        $movement = 0;
    }
    
    $prod->correct_stock($user, $ln->fk_entrepot, $val, $movement, "#correction de ".$text);
    $db->query("UPDATE llx_stock_mouvement SET label = '#corrigé ".$text."' WHERE rowid = ".$ln->rowid);
    echo($text."<br/>");
}


echo '</body></html>';

//llxFooter();
