<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 14 juil. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : login.php
  * magentoGLE
  */

require_once('pre.inc.php');


$xml = "<ajax-response>";

$requete = "UPDATE Babel_financement_access SET tmpKey='' WHERE tmpKey = '".$_REQUEST["key"]."' ";
$db->query($requete);
$xml .= "OK";
$xml .= "</ajax-response>";
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
echo $xml;


?>
