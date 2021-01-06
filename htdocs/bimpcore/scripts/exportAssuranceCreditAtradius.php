<?php
/*
 *  Assurance Crédit Atradius
 */

require_once("../../main.inc.php");
require_once __DIR__ . '/../Bimp_Lib.php';
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', "20000MB");
$bdd = new BimpDb($db);

$moinTroisAns = new DateTime();
$moinTroisAns->sub(new DateInterval("P36M"));

$sql = "SELECT DISTINCT f.fk_soc FROM llx_facture f, llx_facture_extrafields fe WHERE fe.fk_object = f.rowid AND fe.type = 'C' AND f.datef >= '".$moinTroisAns->format('Y-m-d')."'";

$res = $bdd->executeS($sql);
$allSociete = [];

$client = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_Societe");
$csv = "#;Type;Nom du client;Code client;Code comptable;Siren;Pays;Secteur d'activité;Encours autorisé;Note crédiSafe;<br />";
$nb = 0;
foreach($res as $index => $array) {
    
    $client->fetch($array->fk_soc);
    if($client->getData('is_subsidiary') != 1 && $client->getData('fk_typent') != 5 && $client->getData('fk_typent') != 8) {
        $nb++;
        $csv .= $client->id . ';"'.$client->displayData('fk_typent').'";"' . $client->getName() . '";"' . $client->getData('code_client') . '";"' . $client->getData('code_compta') . '";"' . $client->getData('siren') . '";"' . $client->displayData('fk_pays') . '";"' . $client->displayData('secteuractivite') . '";' . $client->getdata('outstanding_limit') . "€" . ';"' . $client->getData('notecreditsafe') . '";' . "<br />";
    }
}

echo $csv;

