<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 26 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : displayReport-xml_response.php
  * GLE-1.2
  */

  require_once('../../../main.inc.php');
  $id = $_REQUEST['id'];
  $requete = "SELECT webContent FROM BIMP_import_history WHERE id =  ".$id;
  $sql = $db->query($requete);
  $res = $db->fetch_object($sql);
  $xml = "<webContent><![CDATA[" . utf8_encodeRien(($res->webContent."x" != "x"?$res->webContent:"")) ."]]></webContent>";
  if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
     header("Content-type: application/xhtml+xml;charset=utf-8");
  } else {
     header("Content-type: text/xml;charset=utf-8");
  } $et = ">";
  print "<?xml version='1.0' encoding='utf-8'?$et\n";
  print "<ajax-response>";
  print $xml;
  print "</ajax-response>";


?>
