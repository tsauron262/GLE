<?php
ini_set('display_errors', 1);

if ($_GET['a'] == 'df') {
    require '../bimpcommercial/duplicata.php';
} elseif ($_GET['a'] == 'ss') {
    require_once('../main.inc.php');
    require_once DOL_DOCUMENT_ROOT . "/bimpcore/Bimp_Lib.php";
    require '../bimpsupport/public/page.php';
} else {
    require '../bimpinterfaceclient/client.php';
}
?>
