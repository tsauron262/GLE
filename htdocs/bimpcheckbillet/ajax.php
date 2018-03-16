<?php


$code = $_REQUEST['code'];

$name = "Nom client classique";
$valid = "KO";


    if($code == "APP-661-5941"){
    $valid = "OK";
    $name = "Biellet de test toujours valid";
}
elseif($code == "12345678910"){
    $valid = "KO";
    $name = "Billet de test jamais valid";
}



echo json_encode(array("billet"=>array("valid"=>$valid, "nom" => $name)));