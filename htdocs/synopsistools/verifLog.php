<?php

define("NOREQUIREHTML", true);
if (isset($_REQUEST['username']))
    include_once("../../main.inc.php");
$temp = $_SERVER["QUERY_STRING"];
$_SERVER["QUERY_STRING"] = "";
$temp2 = $_POST;
$_POST = array();
$temp3 = $_GET;
$_GET = array();
$temp4 = $_REQUEST;
$_REQUEST = array();

include_once("../../main.inc.php");
if ($user->rights->SynopsisTools->Global->phpMyAdmin != 1) {
//    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}

$_SERVER["QUERY_STRING"] = $temp;
$_REQUEST = $temp4;
$_POST = $temp2;
$_GET = $temp3;
