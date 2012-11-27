<?php
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
global $langs;
require_once('../../master.inc.php');

$cdataStart = "<![CDATA[";
$cdataStop = "]]>";
//var_dump($_REQUEST);
$xml = "";

$action = $_REQUEST['action'];
$socid = $_REQUEST['socid'];
$campId = $_REQUEST['campid'];

switch($action)
{
    case "set":
    {
        $note = trim($_REQUEST['note']);
        //1 note par soc
        $requete = "DELETE FROM Babel_campagne_societe_notes
                           WHERE fk_soc=".$socid."
                             AND fk_camp = ".$campId;
        $db->query($requete);
        $requete = "INSERT INTO Babel_campagne_societe_notes
                                (fk_soc, note, fk_camp)
                         VALUES ($socid, '$note',$campId)";
        $db->query($requete);
    }
    case "get":
    {
        $requete = "SELECT note
                      FROM Babel_campagne_societe_notes
                     WHERE fk_soc = ".$socid."
                       AND fk_camp = ".$campId;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $xml .= "<note>".$cdataStart.$res->note.$cdataStop."</note>";
        $requete1 = "SELECT avis, avancement
                       FROM Babel_campagne_avancement
                      WHERE societe_refid = ".$socid."
                        AND campagne_refid = ".$campId ."
                   ORDER BY DateModif DESC limit 1";
        $sql1 = $db->query($requete1);
        $res1 = $db->fetch_object($sql1);
        $xml .= "<ProgressSoc>".$res1->avancement."</ProgressSoc>";
        $xml .= "<NoteSoc>".$res1->avis."</NoteSoc>";
        $requete2 = "SELECT date_format(dateModif,'%d/%m/%Y %H:%i') as dateModif,
                            user_refid,
                            raison,
                            note
                       FROM Babel_campagne_avancement
                      WHERE campagne_refid = $campId
                        AND societe_refid = ".$socid."
                   ORDER BY dateModif ASC";
//                   print $requete2;
        $sql2 = $db->query($requete2);
        $xml .= "<historique>";
        while ($res2 = $db->fetch_object($sql2))
        {
            $tmpUser = new User($db);
            $tmpUser->id = $res2->user_refid;
            $tmpUser->fetch();
            $xml.= "<histo><date><![CDATA[".$res2->dateModif."]]></date>";
            $xml.= "<userid><![CDATA[".$tmpUser->getNomUrl(1)."]]></userid>";
            $xml.= "<raison><![CDATA[".$res2->raison."]]></raison>";
            $xml.= "<noteHisto><![CDATA[".$res2->note."]]></noteHisto></histo>";
        }
        $xml .= "</historique>";


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