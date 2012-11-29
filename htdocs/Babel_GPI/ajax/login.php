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

$requete = "SELECT count(*) as cnt,
                   ".MAIN_DB_PREFIX."societe.rowid as socid
              FROM Babel_financement_access,
                   ".MAIN_DB_PREFIX."societe
             WHERE ".MAIN_DB_PREFIX."societe.rowid = Babel_financement_access.fk_soc
               AND md5(Babel_financement_access.password) =  '".$_REQUEST['pass']."'
               AND ".MAIN_DB_PREFIX."societe.nom = '".utf8_decode($_REQUEST['login'])."'";

$sql = $db->query($requete);
$res = $db->fetch_object($sql);
//var_dump($db);
$xml = "<ajax-response>";

$key = md5(date("dmyhms".$res->socid));
$requete2 = "UPDATE Babel_financement_access set tmpKey = '".$key."' WHERE Babel_financement_access.fk_soc = $res->socid";
//var_dump($res);
if ($res->cnt > 0)
{
    $xml .= "<logged>OK</logged>";
    $xml .= "<soccode>".$key."</soccode>";
    $db->query($requete2);
} else {
    $xml .= "<logged>KO</logged>";
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
