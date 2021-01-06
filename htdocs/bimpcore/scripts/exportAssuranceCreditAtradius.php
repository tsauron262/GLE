<?php
/*
 *  Assurance Crédit Atradius
 */

require_once("../../main.inc.php");
require_once __DIR__ . '/../Bimp_Lib.php';
ini_set('display_errors', 1);
ini_set('memory_limit', "2048MB");
$can_execute = ($user->admin) ? true : false;

if($can_execute) {
    
    $bdd = new BimpDb($db);
    
    $moinTroisAns = new DateTime();
    $moinTroisAns->sub(new DateInterval("P36M"));

    $sql = "SELECT DISTINCT f.fk_soc FROM llx_facture f, llx_facture_extrafields fe WHERE fe.fk_object = f.rowid AND fe.type = 'C' AND f.datef >= '".$moinTroisAns->format('Y-m-d')."' AND f.datef < '2021-01-06'";

    $res = $bdd->executeS($sql);
    $allSociete = [];
    
    $client = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_Societe");
    $tt = 0;
    foreach($res as $index => $array) {
        $client->fetch($array->fk_soc);
        if($client->getData('is_subsidiary') != 1 && $client->getData('fk_typent') != 5 && $client->getData('fk_typent') != 8) {
            $tt++;
            echo $client->getData('nom') . " (".memory_get_usage().") <br />" ;
        }
        gc_collect_cycles();
    }
    echo "<br />" . $tt;
    
}
