<?php
/*
 * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
 require_once ('../main.inc.php');
$url1="/zimbra/";
require_once("./ZimbraSoap.class.php");
$zimuser="";
if ($conf->global->ZIMBRA_ZIMBRA_USE_LDAP=="true")
{
    $zimuser=$user->login;
//    var_dump($user);
} else {
    $zimuser = Zimbra::getZimbraCred($db, $user->id);
}
$zim = new Zimbra($zimuser);
//$zim->debug=true;
$zim->connect();

$authTok = $zim->auth_token;
$preauthURL1 = $zim->_protocol."".$zim->_server."/service/preauth?isredirect=1&authtoken=".$authTok."&redirectURL=".urlencode($url1);
header("Location: $preauthURL1");
?>
