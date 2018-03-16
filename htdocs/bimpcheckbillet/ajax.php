<?php


$code = $_REQUEST['code'];


if($code == "APP-661-5941")
    $valid = "OK";
else
    $valid = "KO";



echo json_encode(array("billet"=>array("valid"=>$valid, "nom" => "le nom du client")));