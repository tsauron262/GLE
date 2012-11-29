<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 5 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : rempDI-xml_response.php
  *
  * GLE-1.2
  *
  *
  */


  require_once('../../../main.inc.php');
  $id = $_REQUEST['DIid'];
  $userId = $_REQUEST['userId'];

  $xmlStr = "<ajax-response>";
  $requete = "UPDATE llx_Synopsis_demandeInterv SET fk_user_target=".$userId." WHERE rowid = ".$id;
  $sql = $db->query($requete);
  if ($sql){
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
