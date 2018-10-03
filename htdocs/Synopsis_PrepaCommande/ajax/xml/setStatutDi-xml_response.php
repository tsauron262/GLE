<?php
/*
  ** BIMP-ERP by Synopsis et DRSI
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
  * Name : delDI-xml_response.php
  * BIMP-ERP-1.2
  */


    require_once('../../../main.inc.php');

    $id = $_REQUEST['id'];
    $statut = $_REQUEST['statut'];
    require_once(DOL_DOCUMENT_ROOT.'/synopsisdemandeinterv/class/synopsisdemandeinterv.class.php');
    $di = new Synopsisdemandeinterv($db);
    $di->fetch($id);
    if($statut == 1)
        $di->valid($user);
    if($statut == 2){
        $userT = new User($db);
        $userT->fetch($di->fk_user_prisencharge);
        $di->prisencharge($userT);
    }
    if($statut == 3)
        $di->cloture($user);
    
    $res = true;


    $xmlStr = "<ajax-response>";
    if ($res){
        $xmlStr .= "<OK>OK</OK>";
    } else {
        $xmlStr .= "<KO>KO</KO>";
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