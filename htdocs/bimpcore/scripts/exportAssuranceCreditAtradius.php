<?php
/*
 *  Assurance Crédit Atradius
 */

require_once("../../main.inc.php");
require_once __DIR__ . '/../Bimp_Lib.php';
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', "2000M");
$bdd = new BimpDb($db);

$moinTroisAns = new DateTime();
$moinTroisAns->sub(new DateInterval("P36M"));

$sql = "SELECT DISTINCT f.fk_soc FROM llx_facture f, llx_facture_extrafields fe WHERE fe.fk_object = f.rowid AND fe.type = 'C' AND f.datef >= '".$moinTroisAns->format('Y-m-d')."'";

$res = $bdd->executeS($sql);
$allSociete = [];

$csv = "#;Type;Nom du client;Code client;Code comptable;Siren;Pays;Secteur d'activité;Encours autorisé;Note crédiSafe;Lettre crédiSafe;<br />";
$nb = 0;
foreach($res as $index => $array) {
    $client = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_Societe", $array->fk_soc);
    if($client->getData('lettrecreditsafe')."x" == "x"){
        $data = array();
        $errors = BimpTools::merge_array($errors, $client->checkSiren('siret', $client->getData('siret'), $data));
        if (count($data) > 0) {
            $client->set('lettrecreditsafe', $data['lettrecreditsafe']);
            $client->set('notecreditsafe', $data['notecreditsafe']);
            $client->set('outstanding_limit', $data['outstanding_limit']);
            $client->set('capital', $data['capital']);
            $client->set('tva_intra', $data['tva_intra']);
            $client->set('capital', $data['capital']);
            $errors = BimpTools::merge_array($errors, $client->update($w, true));
        }
    }
    
    
    
    if($client->getData('is_subsidiary') != 1 && $client->getData('fk_typent') != 5 && $client->getData('fk_typent') != 8) {
        $nb++;
        $csv .= $client->id . ';"'.$client->displayData('fk_typent').'";"' . $client->getName() . '";"' . $client->getData('code_client') . '";"' . $client->getData('code_compta') . '";"' . $client->getData('siren') . '";"' . $client->displayData('fk_pays') . '";"' . $client->displayData('secteuractivite') . '";' . $client->getdata('outstanding_limit') . "€" . ';"' . $client->getData('notecreditsafe') . '";'. $client->getCreditSafeLettre(true) . '";' . "<br />";
    }
}

echo $csv;

