<?php
define('NOLOGIN',1);
require('../../main.inc.php');

$serveur = GETPOST('ip');//ip du serveur
$etat = GETPOST('etat');//etat du serveur 0 surchargé 1 Ok -1 erreur

$text = 'Serveur : '.$serveur.' Etat : '.$etat;

if(!in_array($serveur, array('221', '111', '203'))){
    $text = 'Attention serveur inconnue '.$serveur;
}
elseif(!in_array($etat, array(0,1,-1))){
    $text = 'Attention etat inconnue '.$etat;
}


dol_syslog($text,3,0,'_charge_sql');
echo $text;