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
  * Name : ws_notifications.php
  * GLE-1.1
  **/

  $cookieName = "GleWSCookie";

  $debug=1;

//file_put_contents('/tmp/debugSOAP.txt',print_r($_COOKIE,1)." ".$_COOKIE[$cookieName]);
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
} else if($debug == 1){
//    file_put_contents('/tmp/debugSOAP.txt',"Pas d'auth");
    $_POST["username"]="eos";
    $_POST["password"]="redalert";
} else {
//    file_put_contents('/tmp/debugSOAP.txt',"Pas d'auth");
    print "Pas d'authentification";
    exit;
}
//file_put_contents('/tmp/debugSOAP.txt',"toto");
require_once('../main.inc.php');
require 'webservices_notification_function.php';

ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache
ini_set('soap.wsdl_cache_ttl',0);
ini_set('soap.wsdl_cache',0);

$server = new SoapServer("http://127.0.0.1/GLE-1.2/main/htdocs/Babel_WS/BabelNotification.wsdl");
$server->addFunction("getNotifications");

$server->handle();

?>
