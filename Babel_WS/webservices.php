<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 30 juin 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : webservices.php
  * GLE-1.1
  **/

  $cookieName = "GleWSCookie";
//file_put_contents('/tmp/debugSOAP.txt',print_r($_COOKIE,1)." ".$_COOKIE[$cookieName]);
$debug=1;
if($_COOKIE[$cookieName] ."x" != "x")
{
    require_once('../master.inc.php');
    $requete = "SELECT * FROM Babel_WS_auth WHERE cookie_value = '".$_COOKIE[$cookieName]."'";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    $requete1 = "SELECT * FROM ".MAIN_DB_PREFIX."user WHERE rowid = ".$res->user_id;
    $sql1 = $db->query($requete1);
    $res1 = $db->fetch_object($sql1);
    $_POST["username"]=$res1->login;
    $_POST["password"]=$res1->pass;
} else if($debug == 1) {
    $_POST["username"]="eos";
    $_POST["password"]="redalert";
}else{
//    file_put_contents('/tmp/debugSOAP.txt',"Pas d'auth");
    print "Pas d'authentification";
    exit;
}
//file_put_contents('/tmp/debugSOAP.txt',"toto");
require_once('../main.inc.php');
require 'webservices_function.php';

ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache
ini_set('soap.wsdl_cache_ttl',0);
ini_set('soap.wsdl_cache',0);

$server = new SoapServer(GLE_FULL_ROOT."/Babel_WS/BabelGMAO.wsdl.php");
$server->addFunction("getContratList");
$server->addFunction("getItemCount");
$server->addFunction("getItemList");
$server->addFunction("setContratNbTicket");
$server->addFunction("listSocWithContract");
$server->addFunction("createTicketAction");

$server->handle();

?>
