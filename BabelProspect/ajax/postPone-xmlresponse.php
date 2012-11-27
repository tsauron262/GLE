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
  * Name : file.php
  * GLE-1.1
  */

  require_once('../../main.inc.php');
  $action = $_REQUEST['action'];
  $userid = $_REQUEST['userid'];
  $socid = $_REQUEST['socid'];
  $campId = $_REQUEST['campId'];
switch($action)
{
    case "history":
    {
        $requete = "SELECT * FROM Babel_campagne_avancement ORDER BY `dateModif` DESC";
        $sql = $db->query($requete);
        $xml = "<rows>";
        while ($res = $db->fetch_object($sql))
        {
            $xml .= "<row id=".$res->id.'>';
            $xml .= "<avancement>".$res->avancement."</avancement>";
            $xml .= "<note>".$res->note."</note>";
            $xml .= "<date>".$res->dateModif."</date>";
            $xml .= "<raison>".$res->raison."</raison>";
            $xml .= "<avis>".$res->avis."</avis>";
            $xml .= "<socid>".$res->societe_refid."</socid>";
            $xml .= "<campId>".$res->campagne_refid."</campId>";
            $xml .= "<userid>".$res->user_refid."</userid>";
            $xml .= '</row>';

        }
        $xml .= "</rows>";

            header("Content-Type: text/xml");
            $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
            $xmlStr .= '<ajax-response><response>'."\n";
            $xmlStr .= "<xml>".$xml."</xml>";
            $xmlStr .= '</response></ajax-response>'."\n";
            print $xmlStr;
    }
    break;
    case "update":
    {
        $avancement=$_REQUEST['avancement'];
        $avancement = preg_replace('/,/','.',$avancement);
        $raison=$_REQUEST['raison'];
        $avis=$_REQUEST['avis'];
        $date=$_REQUEST['date'];
        if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/',$date,$arr))
        {
            $date = $arr[3]."-".$arr[2].'-'.$arr[1]." ".$arr[4].':'.$arr[5];
        }
        $note=$_REQUEST['note'];
        $note = preg_replace('/,/','.',$note);

        $requete = "INSERT INTO Babel_campagne_avancement
                                (user_refid, campagne_refid,societe_refid,raison,avancement,note,avis,dateModif)
                         VALUES ($userid,$campId,$socid,'$raison',$avancement,'$note',$avis,now() )";
        $db->query($requete);

        //si c'est repousser on passe en phase 4
        $requete = "UPDATE Babel_campagne_societe
                       SET fk_statut = 4, dateRecontact = '$date', date_prisecharge = now()
                     WHERE campagne_refid = ".$campId."
                       AND societe_refid = ".$socid;
        $db->query($requete);



    }
    default:
    {
        $requete = "SELECT user_refid,
                           campagne_refid,
                           societe_refid,
                           raison,
                           avancement,
                           note,
                           avis,
                           date_format(dateModif,'%d/%m/%Y %H:%i')
                      FROM Babel_campagne_avancement
                  ORDER BY `dateModif` DESC  LIMIT 1";
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
            $xml = "<avancement>".$res->avancement."</avancement>";
            $xml .= "<note>".$res->note."</note>";
            $xml .= "<date>".$res->dateModif."</date>";
            $xml .= "<raison>".$res->raison."</raison>";
            $xml .= "<avis>".$res->avis."</avis>";
            $xml .= "<socid>".$res->societe_refid."</socid>";
            $xml .= "<campId>".$res->campagne_refid."</campId>";
            $xml .= "<userid>".$res->user_refid."</userid>";
            header("Content-Type: text/xml");
            $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
            $xmlStr .= '<ajax-response><response>'."\n";
            $xmlStr .= "<xml>".$xml."</xml>";
            $xmlStr .= '</response></ajax-response>'."\n";
            print $xmlStr;

    }
    break;
    case 'getExtra';
    {
        $requete = "SELECT avis, avancement
                      FROM Babel_campagne_avancement
                     WHERE societe_refid = $socid
                       AND campagne_refid = $campId
                       AND user_refid = $userid
                  ORDER BY dateModif DESC
                     LIMIT 1";

        $sql = $db->query($requete);
        $xml = "<rows>";
        if ($db->num_rows($sql) > 0)
        {
            while ($res = $db->fetch_object($sql))
            {
                $xml .= "<row>";
                $xml .= "<avancement>".$res->avancement."</avancement>";
                $xml .= "<avis>".$res->avis."</avis>";
                $xml .= '</row>';

            }

        } else {
                $xml .= "<row id='1'>";
                $xml .= "<avancement>0</avancement>";
                $xml .= "<avis>0</avis>";
                $xml .= '</row>';

        }
        $xml .= "</rows>";

        header("Content-Type: text/xml");
        $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
        $xmlStr .= '<ajax-response><response>'."\n";
        $xmlStr .= $xml;
        $xmlStr .= '</response></ajax-response>'."\n";
        print $xmlStr;
    }
    break;
}

?>
