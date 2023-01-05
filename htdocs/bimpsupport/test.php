<?php

require_once("../main.inc.php");



llxHeader();



$sql = $db->query('SELECT email FROM `'.MAIN_DB_PREFIX.'societe`  WHERE rowid IN (SELECT id_client FROM `'.MAIN_DB_PREFIX.'bs_sav` WHERE status != 999 AND code_centre = "P")');

$tabMail = array();
while($ln = $db->fetch_object($sql)){
    $mail = $ln->email;
    if(stripos($mail, "@")){
        $tabMail[] = $mail;
    }
}


print_r($tabMail);


echo "Total ".count($tabMail)." mails";

$msg = "Madamme, Monsieur,

Tout d'abords l'équipe BIMP vous souhaite leurs meilleurs voeux pour cette année 2019.

Nous vous envoyons ce mail pour vous prévenir du déménnagement de notre centre SAV de perpipgnan qui ce trouve désormais au 12 Avenue du Maréchal Leclerc.

Nos horaires on aussi changées, et nous sommes désormais ouvert le Lundi après-midi de 14h à 18h et du Mardi au Vendredi, le matin de 10h à 12h30 et l'après-midi de 14h à 18h.

C'est avec plaisir que nous vous acceuilllons désormais dans ce nouveau centre SAV, la boutique restant ouverte et à votre disposition à l'adresse actuelle.

Cordialement.";

$to = implode(",", $tabMail);

$to = "Tommy@bimp.fr";

$msg .= "<br/><br/>Mails : ".implode("<br/>", $tabMail);


mailSyn2("Déménagement SAV BIMP", $to, 'SAV66@bimp.fr', $msg);



llxFooter();
