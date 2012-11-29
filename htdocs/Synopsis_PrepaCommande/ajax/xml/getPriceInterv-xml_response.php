<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 17 nov. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : getPriceInterv-xml_response.php
  * GLE-1.2
  */

    require_once('../../../main.inc.php');
    $userid = $_REQUEST['userId'];
    $xmlStr = "<ajax-response>";


    $requete= "SELECT * FROM llx_Synopsis_fichinter_User_PrixTypeInterv WHERE user_refid = " .$userid;
    $sql = $db->query($requete);
    while($res=$db->fetch_object($requete))
    {
        $xmlStr .= "<typeInterv id='".$res->typeInterv_refid."'><![CDATA[".price($res->prix_ht)." &euro;]]></typeInterv>";
    }
    $requete= "SELECT * FROM llx_Synopsis_fichinter_User_PrixDepInterv WHERE user_refid = " .$userid;
    $sql = $db->query($requete);
    while($res=$db->fetch_object($requete))
    {
        $xmlStr .= "<dep id='".$res->fk_product."'><![CDATA[".price($res->prix_ht)." &euro;]]></dep>";
    }

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
        header("Content-type: application/xhtml+xml;charset=utf-8");
    } else {
        header("Content-type: text/xml;charset=utf-8");
    }
    $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print $xmlStr;
    print "</ajax-response>";


?>