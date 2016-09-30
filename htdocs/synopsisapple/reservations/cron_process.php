<?php

//define("NOLOGIN", 1);

require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/synopsisapple/class/reservations.class.php");

    llxHeader();

ini_set('display_errors', 1);
error_reporting(E_ERROR);

$reservations = new Reservations($db);
$reservations->display_debug = true;
$reservations->get_reservations();



    llxFooter();