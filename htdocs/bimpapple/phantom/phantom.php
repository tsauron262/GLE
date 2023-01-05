<?php

header('Access-Control-Allow-Origin: *',true);

define("NOLOGIN", 1);

require_once("../../main.inc.php");

ini_set('display_errors', 1);

require_once __DIR__ . '/../../bimpcore/Bimp_Lib.php';

BimpObject::loadClass('bimpsupport', 'BS_SAV');


if(isset($_GET['tok'])){
    print_r(BS_SAV::setGsxActiToken($_GET['tok'], $_GET['log']));
    die('fin');
}
elseif(isset($_GET['id'])){
    $oldIdMax = $_GET['id'];
    $newIdMax = 0;

    $code = BS_SAV::getCodeApple($oldIdMax, $newIdMax);

    die($code.'|'.$newIdMax);
}
print_r($_GET);
die ('pas de requete');