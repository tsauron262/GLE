<?php


//$json = json_decode(file_get_contents('php://input'), true);
//$json = array('message-id' => 'hhhh');
define("NOLOGIN", 1);
require("../main.inc.php");
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';



BimpCore::addlog('Weeb hook mail '.'ici '.print_r($json,1));

if($json['email'] == 'tommy@bimp.fr')
    mailSyn2('test', 't.sauron@bimp.fr', null, 'ici '.print_r($json,1));




echo 'ok';
