<?php

if (!defined('BIMP_LIB')) {
	require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
}
if ((int) BimpCore::getConf('use_centres_sav', null, 'bimpsupport')) {
	BimpCore::addlog('Fichier centre.inc require', 4);
}

//global $tabCentre;
$tabCentre = array(
    "CH" => array("04 27 46 60 09", "sav_ch@ldlc.com", "Champagne-au-Mont-d'Or", 0, "1745442", "69410", "Champagne-au-Mont-d'Or", "2 avenue Charles de Gaulle", 40, 1, null, '310065b3-815e-4043-a221-8ae34d463dc7'),
//    "LI" => array("04 27 46 60 09", "sav_ch@ldlc.com", "Limonest", 0, "1442050", "69760", "Limonest", "2 Rue des Érables", 40, 1, null, '310065b3-815e-4043-a221-8ae34d463dc7'),
);
