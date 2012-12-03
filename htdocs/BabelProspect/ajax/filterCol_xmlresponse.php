<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 10-4-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : filterCol_xmlresponse.php
  * dolibarr-24dev
  */
require_once('../../main.inc.php');

//  POST
//  distinct  8

//id   data_grid1

//colnum  8

$grid = $_REQUEST['id'];
$colnum = $_REQUEST['colnum'];
$distinct = $_REQUEST['distinct'];

$offset=0;
$xml= '<'.'?xml version="1.0" encoding="UTF-8"?'.'>';
$xml.= "<ajax-response>\n";
$xml.= "\t<response type='object' id='".$grid."_updater'>\n";
$xml.= "\t\t<rows update_ui='true' offset='0' distinct='".$distinct."'>\n";

switch($_SESSION[$grid][$colnum])
{
    case "Client":
        $xml .= "<tr><td>Client</td></tr>";
        $xml .= "<tr><td>Prospect</td></tr>";

    break;

    case  "Effectif":
        $requete = "SELECT libelle FROM ".MAIN_DB_PREFIX."c_effectif where active=1 order by id ";
        if ($resql=$db->query($requete))
        {
            while($res=$db->fetch_object($resql))
            {
                $xml .= "<tr><td>".utf8_encode($res->libelle)."</td></tr>";
            }
        }
    break;
    case  "Secteur":
        $requete = "SELECT libelle FROM ".MAIN_DB_PREFIX."c_secteur where active=1 order by id ";
        if ($resql=$db->query($requete))
        {
            while($res=$db->fetch_object($resql))
            {
                $xml .= "<tr><td>".utf8_encode($res->libelle)."</td></tr>";
            }
        }
    break;
}
        $xml .= "\t\t</rows>\n";
        $xml .= "\t</response>\n";
        $xml .= "</ajax-response>\n";
header("Content-Type: text/xml");
print $xml;


?>
