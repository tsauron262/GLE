<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 15 juil. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : soccode.php
  * magentoGLE
  */

require_once('pre.inc.php');

$requete = "SELECT fk_soc FROM Babel_financement_access
             WHERE Babel_financement_access.tmpKey= '".$_REQUEST['soccode']."'";
$xml="<ajax-response>";
$sql = $db->query($requete);
$res = $db->fetch_object($sql);

    $xml .= "<socid>".$res->fk_soc."</socid>";



$xml .= "</ajax-response>";
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
echo $xml;



?>
