<?php
/*
  ** GLE by Synopsis et DRSI
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
  * Name : saveImport-xmlresponse.php
  * GLE-1.1
  */

require_once('../../main.inc.php');
//lat="+lat+"&lng="+lng+'&socid='+socid+"&name="+Curname+'&countryCode='+Curcountry_name
$lat = $_REQUEST['lat'];
$lng = $_REQUEST['lng'];
$socid = $_REQUEST['socid'];
$name = $_REQUEST['name'];
$country = $_REQUEST['countryCode'];
$id = $_REQUEST['id'];
$xml="";
if ($id."x" == "x")
{
    $requete = "INSERT INTO `Babel_GeoBI` (`lat`,`lng`,`socid`,`countryCode`,`label`)
                VALUES
               ('".$lat."','".$lng."',".$socid.", '".$country."', '".$name."')
               ";
    $sql =$db->query($requete);
    if ($sql)
    {
        $xml = '<OK>OK</OK>';
    } else {
        $xml = '<KO>KO</KO>';
    }
} else {
    $requete = "UPDATE `Babel_GeoBI`
                   SET `lat`='".$lat."',
                       `lng`='".$lng."',
                       `socid`='".$socid."',
                       `label`='".$name."',
                       `countryCode`='".$country."'
                 WHERE id = ".$id;
                 //print $requete;
    $sql =$db->query($requete);
    if ($sql)
    {
        $xml = '<OK>OK</OK>';
    } else {
        $xml = '<KO>KO</KO>';
    }
}
   header("Content-Type: text/xml");
   $xmlStr = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
   $xmlStr .= "<ajax-response>";
   $xmlStr .= $xml;
   $xmlStr .= "</ajax-response>";
   print $xmlStr;


?>
