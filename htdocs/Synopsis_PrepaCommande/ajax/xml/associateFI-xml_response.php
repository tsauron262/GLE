<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.2
  * Created on : 6 oct. 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : associateFI-xml_response.php
  * GLE-1.2
  */
  require_once("../../../main.inc.php");
  $comId = $_REQUEST['id'];
  $fid = $_REQUEST['fid'];
  $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_fichinter SET fk_commande =".$comId . " WHERE rowid = ".$fid;
  $sql = $db->query($requete);
//DI ??
  $res="error";
  if ($sql)
  {
    $res='OK';
  }
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    print "<?xml version='1.0' encoding='utf-8'?$et\n";
    print "<ajax-response>";
    print $res;
    print "</ajax-response>";

?>
