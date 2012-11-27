<?php
/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 27 mai 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : modelMail.php
  * GLE-1.1
  */

  $id = $_REQUEST['id'];
  require_once('../../main.inc.php');

  $requete = "SELECT * FROM Babel_plaquette WHERE id = ".$id;
  $sql = $db->query($requete);
  $res = $db->fetch_object($sql);

  $xml = "<ajax-response>";

  $xml .= "<models>";
  $xml .= " <label><![CDATA[".utf8_encode($res->label)."]]></label>";
  $xml .= " <id>".$res->id."</id>";
  $xml .= " <model><![CDATA[".htmlentities(utf8_encode($res->content))."]]></model>";
  $xml .= " <subject><![CDATA[".utf8_encode($res->subject)."]]></subject>";
  $xml .= "</models>";

  $xml .= "</ajax-response>";

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
echo $xml;

?>