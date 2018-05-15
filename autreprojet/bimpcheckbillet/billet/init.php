<?php

require_once("../config/settings.inc.php");

$fermer = true;


$conn = new mysqli(_DB_SERVER_, _DB_USER_, _DB_PASSWD_, _DB_NAME_);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$modeTest = false;
if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == "testazerty"){
	$modeTest = $_REQUEST['mode'];
}
?>
