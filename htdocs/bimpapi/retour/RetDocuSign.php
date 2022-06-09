<?php
require_once("../../main.inc.php");


BimpObject::loadClass('bimpapi', 'API_Api');
$api = BimpAPI::getApiInstance('Docusign');
$api->saveToken('code', $_GET['code']);

echo 'OK';