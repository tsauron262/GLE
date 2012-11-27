<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 7-23-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : campNav.php
  * GLE-1.1
  */

require_once('../../master.inc.php');
require_once('../Campagne.class.php');
require_once('../../societe.class.php');
$camp = new Campagne($db);
$soc=new Societe($db);
//Lock ??


$cdataStart = "<![CDATA[";
$cdataStop = "]]>";
//var_dump($_REQUEST);
$xml = "";

$action = $_REQUEST['action'];
$socid = $_REQUEST['socid'];
$campId = $_REQUEST['campid'];
$userId = $_REQUEST['userId'];

$user = new User($db);
$user->id = $userId;
$user->fetch();

$camp->id = $campId;
$camp->stats();
switch($action)
{
    case "nextAndClose":
    {
        $requete = "UPDATE Babel_campagne_societe
                       SET fk_statut = 3,
                           date_prisecharge = now()
                     WHERE societe_refid =".$socid."
                       AND campagne_refid = " . $campId;
         $db->query($requete);
         $nextSoc =$camp->getNextSoc();
        $xml .= "<nextSoc>".$nextSoc."</nextSoc>";
         $soc->fetch($nextSoc);
        $xml .= "<nextSOCNAME><![CDATA[".$soc->nom."]]></nextSOCNAME>";
        $xml .= "<nextSOCNAMELONG><![CDATA[".$soc->getNomUrl(1)."]]></nextSOCNAMELONG>";
        $xml .= "<AvgDay><![CDATA[".$camp->statCampagne['avg_day']."]]></AvgDay>";
        $xml .= "<avancement><![CDATA[".$camp->statCampagne['avancement']."]]></avancement>";
    }
    break;
    case "giveTo":
    case "nextAndPostPone":
    {
        //PostPone deja fait par postPone-xml-Response
        //GiveTo deja fait par giveTo-xml-Response

         $nextSoc =$camp->getNextSoc();
        //update status
        $xml .= "<nextSoc>".$nextSoc."</nextSoc>";
         $soc->fetch($nextSoc);
        $xml .= "<nextSOCNAME><![CDATA[".$soc->nom."]]></nextSOCNAME>";
        $xml .= "<nextSOCNAMELONG><![CDATA[".$soc->getNomUrl(1)."]]></nextSOCNAMELONG>";
        $xml .= "<AvgDay><![CDATA[".$camp->statCampagne['avg_day']."]]></AvgDay>";
        $xml .= "<avancement><![CDATA[".$camp->statCampagne['avancement']."]]></avancement>";
    }
    break;
    case "setProcessing":
    {
//        $requete = "UPDATE Babel_campagne_societe
//                       SET fk_statut = 2,
//                           date_prisecharge = now()
//                     WHERE societe_refid =".$socid."
//                       AND campagne_refid = " . $campId;
//         $db->query($requete);
         $nextSoc =$camp->getNextSoc();
         $xml .= "<nextSoc>".$nextSoc."</nextSoc>";
         $soc->fetch($nextSoc);
        $xml .= "<nextSOCNAME><![CDATA[".$soc->nom."]]></nextSOCNAME>";
        $xml .= "<nextSOCNAMELONG><![CDATA[".$soc->getNomUrl(1)."]]></nextSOCNAMELONG>";
        $xml .= "<AvgDay><![CDATA[".$camp->statCampagne['avg_day']."]]></AvgDay>";
        $xml .= "<avancement><![CDATA[".$camp->statCampagne['avancement']."]]></avancement>";
    }
    break;
    case "cancel":
    {
         $nextSoc =$camp->getNextSoc();
        $requete = "UPDATE Babel_campagne_societe
                       SET fk_statut = 1,
                           date_prisecharge = null
                     WHERE societe_refid =".$socid."
                       AND campagne_refid = " . $campId;
         $db->query($requete);
         $xml .= "<nextSoc>".$nextSoc."</nextSoc>";
         $soc->fetch($nextSoc);
        $xml .= "<nextSOCNAME><![CDATA[".$soc->nom."]]></nextSOCNAME>";
        $xml .= "<nextSOCNAMELONG><![CDATA[".$soc->getNomUrl(1)."]]></nextSOCNAMELONG>";
        $xml .= "<AvgDay><![CDATA[".$camp->statCampagne['avg_day']."]]></AvgDay>";
        $xml .= "<avancement><![CDATA[".$camp->statCampagne['avancement']."]]></avancement>";
    }
    break;

}



    header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= $xml;
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;

?>