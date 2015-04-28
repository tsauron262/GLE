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

$requete = "SELECT  Babel_financement_access.password as pass FROM Babel_financement_access, ".MAIN_DB_PREFIX."societe
             WHERE ".MAIN_DB_PREFIX."societe.rowid = Babel_financement_access.fk_soc
               AND ".MAIN_DB_PREFIX."societe.rowid = '".utf8_decode($_REQUEST['login'])."'";
$sql = $db->query($requete);
$res = $db->fetch_object($sql);
//var_dump($db);
$xml = "<ajax-response>";

//var_dump($res);
if (strlen($res->pass) > 0)
{
    $xml .= "<pass><![CDATA[".utf8_encode($res->pass)."]]></pass>";
    $db->query($requete2);
} else {
    $xml .= "<KO>KO</KO>";
}


$xml .= "</ajax-response>";
    if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
         header("Content-type: application/xhtml+xml;charset=utf-8");
     } else {
        header("Content-type: text/xml;charset=utf-8");
     } $et = ">";
    echo "<?xml version='1.0' encoding='utf-8'?$et\n";
echo $xml;


?>
