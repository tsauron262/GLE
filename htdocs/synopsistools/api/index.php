<?php

require("../../main.inc.php");

llxHeader();

require_once(DOL_DOCUMENT_ROOT."/synopsistools/api/curlRequestApple.class.php");
$curl = new CurlRequestAppleApi(897316, 897316);
$curl->init('authenticate/check');
$result = $curl->exec(array());
print_r($result);


$result = $curl->reqLogin('gsx@bimp.fr', '0f35f8dd-129a-4a7b-ac5f-18a5fadf255mv');
print_r($result);



            $curl->logErrorCurl('fin');
llxFooter();