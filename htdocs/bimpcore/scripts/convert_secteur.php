


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

$nomChamp = "type";


foreach(array("HIED" => "E","ENS" => "E", "EBTS" => "E","F" => "C","R" => "ME","HIED" => "X") as $oldType => $newtype)
    foreach(array("propal_extrafields", "commande_extrafields", "facture_extrafields", "facture_fourn_extrafields", "commande_fournisseur_extrafields") as $table){
        $query = "UPDATE llx_".$table." SET ".$nomChamp." = '".$newtype."' WHERE ".$nomChamp." = '".$oldType."';";
        $db->query($query);
        echo "<br/>".$query."<br/>";
}

echo '</body></html>';

//llxFooter();
