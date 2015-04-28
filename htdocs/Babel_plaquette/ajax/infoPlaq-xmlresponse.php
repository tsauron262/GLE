<?php
/*
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 28 mai 2010
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : infoPlaq-xmlresponse.php
  * GLE-1.1
  */


  $id = $_REQUEST['id'];
  require_once('../../main.inc.php');

  $requete = "SELECT * FROM ".MAIN_DB_PREFIX."ecm_document WHERE rowid = ".$id;
  $sql = $db->query($requete);
  $res = $db->fetch_object($sql);

  $requete1 = "SELECT * FROM Babel_Plaquette_label WHERE ecm_id = ".$id;
  $sql1 = $db->query($requete1);
  $res1 = $db->fetch_object($sql1);


  $xml = "<ajax-response>";

//            var filename = jQuery(msg).find("filename");
//            var filelabel = jQuery(msg).find("filelabel");
//            var filesize = jQuery(msg).find("filesize");
//            var filemime = jQuery(msg).find("filemime");
//            var fileurl = jQuery(msg).find("fileurl");

  $filesize = $res->filesize;
  if ($filesize > 1024 * 1024)
  {
    $filesize = round(($filesize * 10)/(1024 * 1024))/10 . " Mo";
  } else if ($filesize > 1024)
  {
    $filesize = round(($filesize * 100)/(1024))/100 . " Ko";
  } else {
    $filesize = round(($filesize * 100)/(1024))/100 . " o";
  }
  $label = $res1->label;
  if ('x'.$label == 'x')
  {
    $label = 'Cliquer pour m\'&eacute;diter';
  }

  $xml .= "<plaquette>";
  $xml .= " <filename><![CDATA[".utf8_encode($res->filename)."]]></filename>";
  $xml .= " <id>".$id."</id>";
  $xml .= " <filesize><![CDATA[".utf8_encode($filesize)."]]></filesize>";
  $xml .= " <filemime><![CDATA[".utf8_encode($res->filemime)."]]></filemime>";
  $xml .= " <fileurl><![CDATA[".$res->fullpath_dol."]]></fileurl>";
  $xml .= " <filelabel><![CDATA[".$label."]]></filelabel>";
  $xml .= "</plaquette>";

  $xml .= "</ajax-response>";

    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
    echo $xml;

?>
