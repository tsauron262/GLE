<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 5 juil. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : loginWS.php
  * GLE-1.1
  */

    require_once('../master.inc.php');
    //require_once('webservices_function.php');
    $cookieName = "GleWSCookie";

    ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache
    $server = new SoapServer(DOL_URL_ROOT."/Babel_WS/connect.wsdl.php");
    $server->addFunction("WSconnect");
    $server->handle();



function WSconnect($login,$passMd5)
{
    global $db;
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."user WHERE login ='".$login."'";
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);
    if (md5($res->pass) == $passMd5)
    {
        $secret= md5(time()*rand(0,1000)/1000);
        $requete = "DELETE FROM `Babel_WS_auth` WHERE user_id = ".$res->rowid;
        $sql=$db->query($requete);

        $requete = "INSERT INTO `Babel_WS_auth`
                                (`tms`,`cookie_value`,`user_id`)
                         VALUES (now(), '".$secret."', ".$res->rowid.")";
        $sql=$db->query($requete);
        return($secret);
    } else {
        return(-1);
    }
}

?>
