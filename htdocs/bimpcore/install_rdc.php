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

if (!(int) $bdb->getValue('bimpcore_dictionnary', 'id', 'code = \'societe_rdc_cat_maitre\'')) {
	$dict = BimpDict::addDefaultDictionnary('societe_rdc_cat_maitre', 'Catégories maître', 1, 'values', 'id', array(), $errors);

	if (BimpObject::objectLoaded($dict)) {
		echo 'OK societe_rdc_cat_maitre <br/>';

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'ORDI',
			'label'   => 'Ordinateur'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'APP_ELEC',
			'label'   => 'Appareils électroniques'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'COMP',
			'label'   => 'Composants'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'GEM',
			'label'   => 'GEM'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'PEM',
			'label'   => 'PEM'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'PERIPH',
			'label'   => 'Périphériques'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'MAT_JADIN',
			'label'   => 'Matériel et équipement de jardinerie'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'MEUBLE',
			'label'   => 'Meubles et rangements'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'TELEFON',
			'label'   => 'Téléphones'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'PHOTO',
			'label'   => 'Photo'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'LIVR',
			'label'   => 'Livres'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'BRICO',
			'label'   => 'Bricolage'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'GENE',
			'label'   => 'Généraliste'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'ACCE',
			'label'   => 'Accessoires et consommables'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'CULT',
			'label'   => 'Culture'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'JEUXVIDEO',
			'label'   => 'Jeux vidéo'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'PISC',
			'label'   => 'Piscine'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'LOGI',
			'label'   => 'Logiciel'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'CONS',
			'label'   => 'Console'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'LUMI',
			'label'   => 'Luminaires'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'PLOM',
			'label'   => 'Plomberie et sanitaire'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'DECO',
			'label'   => 'Décoration'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'HIGHTECH',
			'label'   => 'High Tech'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'MAISON',
			'label'   => 'Maison'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'ELECTRO',
			'label'   => 'Electroménager'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'LINGMAISON',
			'label'   => 'Linge de maison'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'MODE',
			'label'   => 'Mode'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'JARD',
			'label'   => 'Jardin'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'SPORT',
			'label'   => 'Sports'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'LOISIR',
			'label'   => 'Loisirs'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'EROTIQ',
			'label'   => 'Accessoires érotiques'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'PUER',
			'label'   => 'Puériculture'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'FOURNBURO',
			'label'   => 'Fournitures de Bureau'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'CIGA',
			'label'   => 'Cigarettes Electroniques'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'GASTRO',
			'label'   => 'Gastronomie'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'ANIM',
			'label'   => 'Animalerie'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'LING',
			'label'   => 'Lingerie'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'BEAU',
			'label'   => 'Beauté'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'SANT',
			'label'   => 'Santé'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'AUTO',
			'label'   => 'Auto'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'MOTO',
			'label'   => 'Moto'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'BIJOUX',
			'label'   => 'bijoux'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'MONTRE',
			'label'   => 'Montres'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'JEUX',
			'label'   => 'Jeux'
		), true, $errors);

		BimpObject::createBimpObject('bimpcore', 'BimpDictionnaryValue', array(
			'id_dict' => $dict->id,
			'code'    => 'JOUET',
			'label'   => 'Jouets'
		), true, $errors);
	}
}

echo 'Erreurs : <pre>' . print_r($errors, 1) . '</pre>';

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
