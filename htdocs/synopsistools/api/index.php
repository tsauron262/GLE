<?php

require("../../main.inc.php");

llxHeader();

require_once(DOL_DOCUMENT_ROOT."/synopsistools/api/curlRequestApple.class.php");
$curl = new CurlRequestAppleApi(897316, 897316);
if($curl->checkConnexion())
    echo 'okokokok';
else
    echo 'probléme de connexion GSX';


echo '<br/><br/><br/>';

$result = $curl->reqLogin('olys_tech_aprvlreqrd@olys.com');
if($result){
    echo 'autentification OK';
}
else{
    echo 'autentification BAD';
}


$curl->printErrors();

echo '<br/><br/>Token utilisé : '.$curl->tokenApple.' new token '.$_SESSION['apple_token'];

llxFooter();