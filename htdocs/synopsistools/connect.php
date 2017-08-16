<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 26 oct. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : index.php
 * GLE-1.2
 */
$_GET['optioncss'] = 'print';
require_once('../main.inc.php');
$url = DOL_URL_ROOT . "/index.php";
if ($_GET['url'] != '')
    $url = $_GET['url'];

if (isset($_REQUEST['off'])) {
    $_SESSION['pagePrinc'] = "0";
    header("Location:" . $url);
    die;
}

$_SESSION['pagePrinc'] = $user->id;
$sql = $db->query("SELECT MAX(id) as id FROM " . MAIN_DB_PREFIX . "actioncomm");
$result = $db->fetch_object($sql);
$js = "<script>$(window).on('load', function() {"
 . "             initSynchServ(". $result->id .");"
 . "$(window).bind('beforeunload', function(){"
 . " return 'Ceci va quiter le mode Connect';"
 . "});"
 . "});</script>";
llxHeader($js, "Mode Connect");
echo '<iframe class="fullScreen" src="' . $url . '" style="display: block;" name="iframePrinc"></iframe>';
echo '<div class="deconnect"><a href="?off=true">Deconnect</a></div>';
llxFooter();
?>
