<?php

use class\en_social;

if($_GET['hub_verify_token'] == 'kjsklfbfjfhzemhmhvckleoiho'){
	ob_clean();
	header('Content-Type: application/json');
	die($_GET['hub_challenge']);
}

require_once("../../main.inc.php");

require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';
//ini_set('display_errors', 1);error_reporting(E_ALL);

ignore_user_abort(0);

top_htmlhead('', 'QUICK SCRIPTS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
}

$action = BimpTools::getValue('action', '');

if (!$action) {
    $actions = array(
		'createHisto'                       => 'Créer histo calc',
		'testReseau'						=> 'Test social',
		'searchPage'						=> 'Rechercher page',
    );

    $path = pathinfo(__FILE__);

    foreach ($actions as $code => $label) {
        echo '<div style="margin-bottom: 10px">';
        echo '<a href="' . DOL_URL_ROOT . '/en/scripts/' . $path['basename'] . '?action=' . $code . '" class="btn btn-default">';
        echo $label . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';
    }
    exit;
}

if($action == 'testReseau'){
	require_once DOL_DOCUMENT_ROOT.'/en/class/en_social.class.php';
	$object = new en_social('fb');
	$object->getFollowers();
	echo '<br/>';
	$object = new en_social('ig');
	$object->getFollowers();
//	$object->getLongToken('IGAA5rYgdSDgZABZAE04Mi1QeXNlMno5bHZAOM2R2TnhMVEtkTGdBOVlydjFzYnFKNDQ0bm1lWGZAUUzRZAR0VaWlcwRHdlV29fS1NoajNoVXZAjeGY2dUVONkplbHQ2UDNlZA1I5UTRVbF9teEFtUGxEZA3hVM215eVpleEVOdjBjaWRXSQZDZD');
}

if($action == 'searchPage'){
	require_once DOL_DOCUMENT_ROOT.'/en/class/en_social.class.php';
	$object = new en_social('fb');
	$result = $object->getPages();
	echo '<pre>'; print_r($result);
//	$object->getLongToken('IGAA5rYgdSDgZABZAE04Mi1QeXNlMno5bHZAOM2R2TnhMVEtkTGdBOVlydjFzYnFKNDQ0bm1lWGZAUUzRZAR0VaWlcwRHdlV29fS1NoajNoVXZAjeGY2dUVONkplbHQ2UDNlZA1I5UTRVbF9teEFtUGxEZA3hVM215eVpleEVOdjBjaWRXSQZDZD');
}
//if($action == 'webHookIg'){
//	ob_clean();
//	header('Content-Type: application/json');
//	die('{
//  "success": true
//}');
//}


echo '<br/>FIN';
echo '</body></html>';

