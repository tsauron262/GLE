<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.0
  * Created on : 26 sept. 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : riskDetailEdit_ajax.php
  * GLE-1.1
  */


require_once("../../main.inc.php");
$action = $_REQUEST['oper'];
$socid=$_REQUEST['socid'];
$riskid = $_REQUEST["id"];
$project_id = $_REQUEST["projId"];


$xml ="";

switch($action)
{
    case "add":
    {
        $description = preg_replace('/\'/','\\\'',$_REQUEST["description"]);
        $nom = preg_replace('/\'/','\\\'',$_REQUEST["nom"]);
        $occurence=intval($_REQUEST["occurence"]);
        $gravite=intval($_REQUEST["gravite"]);
        $cout = preg_replace("/,/",".",floatval($_REQUEST["cout"]));
        $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_projet_risk
                                (nom,description,occurence,gravite,cout,fk_projet)
                         VALUES ('".$nom."','".$description."','".$occurence."','".$gravite."','".$cout."',$project_id)";
        $sql = $db->query($requete);
        if ($sql)
        {
            $xml .= "<ok>ok</ok>";
        } else {
            $xml .= "<ko>ko</ko>";
        }
    }
    break;
    case "edit":
    {
        $description = preg_replace('/\'/','\\\'',$_REQUEST["description"]);
        $nom = preg_replace('/\'/','\\\'',$_REQUEST["nom"]);
        $occurence=intval($_REQUEST["occurence"]);
        $gravite=intval($_REQUEST["gravite"]);
        $cout = preg_replace("/,/",".",floatval($_REQUEST["cout"]));
        $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_projet_risk
                       SET nom='".$nom."',
                           description='".$description."',
                           occurence=".$occurence.",
                           gravite=".$gravite.",
                           cout=".$cout."
                     WHERE rowid=".$riskid;
        $sql = $db->query($requete);
        if ($sql)
        {
            $xml .= "<ok>ok</ok>";
        } else {
            $xml .= "<ko>ko\n$requete</ko>";
        }
    }
    break;
    case 'del':
    {
        $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk WHERE rowid=".$riskid;
        $sql = $db->query($requete);
        if ($sql)
        {
            $xml .= "<ok>ok</ok>";
        } else {
            $xml .= "<ko>ko</ko>";
        }
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
