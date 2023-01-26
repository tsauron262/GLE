<?php
ini_set('display_errors', 0);

if (isset($_GET['a']) && $_GET['a'] == 'df') {
    require '../bimpcommercial/duplicata.php';
} elseif (isset($_GET['a']) && $_GET['a'] == 'ss') {
    require '../bimpsupport/public/page.php';
} else {
    require '../bimpinterfaceclient/client.php';
}
?>
