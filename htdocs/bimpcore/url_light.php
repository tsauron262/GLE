<?php
ini_set('display_errors', 0);

if ($_GET['a'] == 'df') {
    require '../bimpcommercial/duplicata.php';
} elseif ($_GET['a'] == 'ss') {
    require '../bimpsupport/public/page.php';
} else {
    require '../bimpinterfaceclient/client.php';
}
?>
