<?php
/*
 * * BIMP-ERP by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 19 oct. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : intervByContrat.php
 * BIMP-ERP-1.2
 */
require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");

$langs->load("contracts");

restrictedArea($user, 'contrat', $contratid, '');

llxHeader($js, 'Prélèvement Automatique');


echo "<table class='espace'><tr><th>Type</th><th>Objet</th><th>Client</th><th>Date prévue</th></tr>";

$dateRef = date('Y-m-d H:i:s', strtotime('+1 month',strtotime(date('Y-m-d'))));

$sql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."synopsisprelevauto WHERE dateProch is not null and dateProch < '".$dateRef."';");
while($result = $db->fetch_object($sql)){
    $objet = $objet2 = "n/c";
    if($result->type == "contrat"){
        $ctr = new Contrat($db);
        $ctr->fetch($result->referent);
        $objet = str_replace("contrat/fiche", "synopsisprelevauto/prelev", $ctr->getNomUrl(1));
        if($ctr->socid > 0){
            $soc = new Societe($db);
            $soc->fetch($ctr->socid);
            $objet2 = $soc->getNomUrl(1);
        }
    }
    echo "<tr><td>".ucfirst($result->type)."</td><td>".$objet."</td><td>".$objet2."</td><td>".dol_print_date($result->dateProch)."</td></tr>";
}
echo "</table>";