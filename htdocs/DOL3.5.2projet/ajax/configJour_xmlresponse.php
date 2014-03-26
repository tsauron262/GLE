<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  *  Created on : 28 sept. 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : configJour_xmlresponse.php
  * GLE-1.1
  */
  require_once('../../master.inc.php');

    $xml = "";

    $action = $_REQUEST['action'];
    switch($action)
    {
        case "add":
            $type = $_REQUEST['type'];
            $debut = $_REQUEST['debut'];
            $fin = $_REQUEST['fin'];
            $facteur = intval($_REQUEST['facteur']);
            if ($type=="Semaine"){ $type = "null"; }
            if ($type=="Dimanche"){ $type= "7"; }
            if ($type=="Samedi"){ $type= "6"; }
            if ($type=="Ferie"){ $type= "8"; }
            $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_projet_trancheHoraire (debut, fin, facteur, day) VALUES ('".$debut."', '".$fin."', '".$facteur."', ".$type.") ";
            $sql = $db->query($requete);
            if ($sql)
            {
                $xml .= "<ok>ok</ok>";
            } else {
                $xml .= "<ko>ko</ko>";
            }
        break;
        case "del":
            $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_projet_trancheHoraire WHERE id =".$_REQUEST['id'];
            $db->query($requete);
            if ($sql)
            {
                $xml .= "<ok>ok</ok>";
            } else {
                $xml .= "<ko>ko</ko>";
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