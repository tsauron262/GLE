<?php

if (!defined('BIMP_LIB')) {
	require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
}
if ((int) BimpCore::getConf('use_centres_sav', null, 'bimpsupport')) {
	BimpCore::addlog('Fichier centre.inc require', 4);
}

global $tabCentre;
$tabCentre = array();
