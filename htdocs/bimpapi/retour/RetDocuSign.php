<?php
require_once("../../main.inc.php");


//BimpObject::loadClass('bimpapi', 'API_Api');
//$api = BimpAPI::getApiInstance('Docusign');
//$api->saveToken('code', $_GET['code']);


$userAcompte = BimpCache::getBimpObjectInstance('bimpapi', 'API_UserAccount', $_SESSION['id_user_docusign']);
unset($_SESSION['id_user_docusign']);
$userAcompte->saveToken('code', $_GET['code']);

echo 'Authentification réussi, veuillez réitéré votre requête sur l\'ERP.';