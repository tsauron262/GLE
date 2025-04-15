<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'INSTALL RDC', 0, 0, array(), array());

echo '<body style="padding: 30px">';

BimpCore::displayHeaderFiles();

global $db, $user;
$bdb = new BimpDb($db);

if (!BimpObject::objectLoaded($user)) {
	echo BimpRender::renderAlerts('Aucun utilisateur connecté');
	exit;
}

if (!$user->admin) {
	echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
	exit;
}

ini_set('display_errors', 1);
$errors = array();

if (!(int) $bdb->getValue('bimpcore_dictionnary', 'id', 'code = \'bimp_ticket_types\'')) {
	$dict = BimpDict::addDefaultDictionnary('bimp_ticket_types', 'Types de ticket', 1, 'values', 'id', array(), $errors);

	if (BimpObject::objectLoaded($dict)) {
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'INT',
			'label'   => 'Intégration'
		), true, $errors);
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'OFF',
			'label'   => 'Offres'
		), true, $errors);
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'CM',
			'label'   => 'Création marque'
		), true, $errors);
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'LIV',
			'label'   => 'Livraison'
		), true, $errors);
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'FDP',
			'label'   => 'Frais de port'
		), true, $errors);
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'ERRPROD',
			'label'   => 'Erreur fiche produit'
		), true, $errors);
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'API',
			'label'   => 'API'
		), true, $errors);
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'PROM',
			'label'   => 'Promotion'
		), true, $errors);
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'SOL',
			'label'   => 'Soldes'
		), true, $errors);
	}
}

if (!(int) $bdb->getValue('bimpcore_dictionnary', 'id', 'code = \'ca_categories\'')) {
	$dict = BimpDict::addDefaultDictionnary('ca_categories', 'Catégories des chiffres d\'affaire', 1, 'values', 'id', array(), $errors);

	if (BimpObject::objectLoaded($dict)) {
		echo 'OK ca_categories <br/>';
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'C1',
			'label'   => 'Catégorie 1'
		), true, $errors);
	}
	if (BimpObject::objectLoaded($dict)) {
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'C2',
			'label'   => 'Catégorie 2'
		), true, $errors);
	}
	if (BimpObject::objectLoaded($dict)) {
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'C3',
			'label'   => 'Catégorie 3'
		), true, $errors);
	}
	if (BimpObject::objectLoaded($dict)) {
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'C4',
			'label'   => 'Catégorie 4'
		), true, $errors);
	}
}

if (!(int) $bdb->getValue('bimpcore_dictionnary', 'id', 'code = \'societe_rdc_sources\'')) {
	$dict = BimpDict::addDefaultDictionnary('societe_rdc_sources', 'Sources clients', 1, 'values', 'id', array(), $errors);

	if (BimpObject::objectLoaded($dict)) {
		echo 'OK societe_rdc_sources <br/>';

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'MKL',
			'label'   => 'MIRAKL connect'
		), true, $errors);
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'INT',
			'label'   => 'Interne'
		), true, $errors);
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'LKI',
			'label'   => 'LinkedIn'
		), true, $errors);
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'CHA',
			'label'   => 'Chasse'
		), true, $errors);
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'OCT',
			'label'   => 'Octopia'
		), true, $errors);
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'OLD',
			'label'   => 'Ancienne boutique'
		), true, $errors);
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'PAR',
			'label'   => 'Partenaire'
		), true, $errors);
		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'OTH',
			'label'   => 'Autre'
		), true, $errors);
	}
}

if (!(int) $bdb->getValue('bimpcore_dictionnary', 'id', 'code = \'societe_rdc_priorities\'')) {
	$dict = BimpDict::addDefaultDictionnary('societe_rdc_priorities', 'Priorités clients', 1, 'values', 'id', array(), $errors);

	if (BimpObject::objectLoaded($dict)) {
		echo 'OK societe_rdc_priorities <br/>';

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'P1',
			'label'   => 'P1'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'P2',
			'label'   => 'P2'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'P3',
			'label'   => 'P3'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'P4',
			'label'   => 'P4'
		), true, $errors);
	}
}

echo 'Erreurs : <pre>' . print_r($errors, 1) . '</pre>';

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
