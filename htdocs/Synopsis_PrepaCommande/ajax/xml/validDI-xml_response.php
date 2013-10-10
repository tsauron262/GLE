<?php
/*
  * GLE by Babel-Services
  *
  * Author: Jean-Marc LE FEVRE <jm.lefevre@babel-services.com>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 16/12/11
  *
  * Infos on http://www.babel-services.com
  *
  */
 /**
  *
  * Name : validDI-xml_response.php
  * GLE-1.2
  */


    require_once('../../../main.inc.php');

    $id = $_REQUEST['id'];
    require_once(DOL_DOCUMENT_ROOT.'/Synopsis_DemandeInterv/demandeInterv.class.php');
    $di = new demandeInterv($db);
    $di->fetch($id);
    $res = $di->valid($user, $conf->synopsisdemandeinterv->dir_output);


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