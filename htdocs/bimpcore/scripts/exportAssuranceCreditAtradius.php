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

$sql = "SELECT DISTINCT f.fk_soc FROM llx_facture f, llx_facture_extrafields fe WHERE fe.fk_object = f.rowid AND fe.type = 'C' AND f.datef >= '".$moinTroisAns->format('Y-m-d')."' ORDER BY f.fk_soc ASC";

$res = $bdd->executeS($sql);
$allSociete = [];

$errors = $w = array();

$csv = "#;Type;Nom du client;Code client;Code comptable;Siren;Pays;Secteur d'activité;Encours autorisé;Note crédiSafe;Lettre crédiSafe<br />";
$nb = $nbCS = 0;
echo "<pre>";
foreach($res as $index => $array) {
    $client = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_Societe", $array->fk_soc);
    if($client->getData('lettrecreditsafe') < 1){
        $nbCS++;
        $data = array();
        echo " - ".$client->getData('code_client')."<br/>";
        $ok = false;
        if($client->getData('siret')."x" != "x")
            $errors = BimpTools::merge_array($errors, $client->checkSiren('siret', str_replace(" ", "", $client->getData('siret')), $data));
        if($client->getData('siren') != "" && !count($data))
            $errors = BimpTools::merge_array($errors, $client->checkSiren('siren', str_replace(" ", "", $client->getData('siren')), $data));
        if (count($data) > 0) {
            $ok = true;
            $client->set('lettrecreditsafe', $data['lettrecreditsafe']);
            $client->set('notecreditsafe', $data['notecreditsafe']);
            $client->set('outstanding_limit', $data['outstanding_limit']);
            $client->set('capital', $data['capital']);
            $client->set('tva_intra', $data['tva_intra']);
            $client->set('capital', $data['capital']);
            $client->set('siret', $data['siret']);
            $client->set('siren', $data['siren']);
            $errors = BimpTools::merge_array($errors, $client->update($w, true));
        }
        if(count($errors) && $ok){
            print_r($errors);
            $errors = "";
        }
        else
            echo 'Yes<br/>';
    }
    
    
    
    if($client->getData('is_subsidiary') != 1 && $client->getData('fk_typent') != 5 && $client->getData('fk_typent') != 8) {
        $nb++;
        $csv .= $client->id . ';"'.$client->displayData('fk_typent').'";"' . $client->getName() . '";"' . $client->getData('code_client') . '";"' . $client->getData('code_compta') . '";"' . $client->getData('siren') . '";"' . $client->displayData('fk_pays') . '";"' . $client->displayData('secteuractivite') . '";' . $client->getdata('outstanding_limit') . "€" . ';"' . $client->getData('notecreditsafe') . '";"'. $client->getCreditSafeLettre(true) . '"' . "<br />";
    }
}

echo print_r($errors, 1)."<br/>".print_r($w,1);
echo "</pre>";
echo $nb." lignes ".$nbCS." appel credit safe";
echo "<br/><br/><br/>";

echo $csv;

