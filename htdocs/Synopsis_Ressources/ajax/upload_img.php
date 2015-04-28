<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 11 aout 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : upload_img.php
  * GLE-1.0
  */
require_once("../../main.inc.php");


//$debug = print_r($_REQUEST,true);
//file_put_contents("/tmp/titi.tmp",$debug .$_FILES['photo']['tmp_name'] );
$xml = '<ok>ko</ok>';
if (is_file($_FILES['photo']['tmp_name']))
{
    $xml = '<ok>ok</ok>';
    $file = AddSlashes(file_get_contents($_FILES['photo']['tmp_name']));
    $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_global_ressources ".MAIN_DB_PREFIX."Synopsis_global_ressources SET photo = '".$file."' WHERE  id = ".$_REQUEST['ressource_id'];
    $sql = $db->query($requete);
}
 header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= $xml;
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;
//file_put_contents("/tmp/titi.tmp",$debug .$_FILES['photo']['tmp_name'] );
?>