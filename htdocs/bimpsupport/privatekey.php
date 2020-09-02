<?php
require('../main.inc.php');

if($user->id < 1)
    die('erreur');
require(DOL_DOCUMENT_ROOT."/bimpcore/Bimp_Lib.php");
require_once(DOL_DOCUMENT_ROOT."/bimpsupport/objects/BS_Remote_Token.class.php");
$res = BS_Remote_Token::getUserRsa($user);

header('Content-Type: application/pem');
header('Content-Disposition: attachment; filename="privatekey.pem"');
echo $res[0];