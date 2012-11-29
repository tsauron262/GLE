<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
//Affiche le calendrier zimbra de la ressource
require_once('pre.inc.php');
require_once('ressource.class.php');
require_once(DOL_DOCUMENT_ROOT."/Synopsis_Zimbra/ZimbraSoap.class.php");

$ressourceId = $_REQUEST['ressource_id'];


    $ressource = new Ressource($db);
    $ressource->fetch($ressourceId);
//var_dump($ressource);
    $url1="/zimbra/";
    $zimuser="";
//    print $ressource->nom;
    $zim = new Zimbra($ressource->nom."-".$ressource->id);
//    $zim->debug=true;
    $zim->connect();

    $url1 = "/home/".rawurlencode($ressource->nom."-".$ressource->id)."/calendar.html?view=week";
//print $url1;
    $authTok = $zim->auth_token;
//    var_dump($authTok);
    $preauthURL1 = $zim->_protocol."".$zim->_server."/service/preauth?isredirect=1&authtoken=".$authTok."&redirectURL=".urlencode($url1);
    header("Location: $preauthURL1");

?>
