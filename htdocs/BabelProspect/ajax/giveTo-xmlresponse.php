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
  $oldUserId = $_REQUEST['userGiveTo'];




$requete = "UPDATE Babel_campagne_societe
               SET user_id = $oldUserId  ,
                   user_reprise_refid = $userid,
                   fk_statut = 4,
                   dateRecontact = date_sub(now(), interval 10 minute)
             WHERE societe_refid = $socid
               AND campagne_refid = $campId";
        $sql = $db->query($requete);
        $xml = "Error" . $requete;
        if ($sql)
        {
            $xml = "OK";
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
            header("Content-Type: text/xml");
            $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
            $xmlStr .= '<ajax-response><response>'."\n";
            $xmlStr .= "<xml><![CDATA[".$xml."]]></xml>";
            $xmlStr .= '</response></ajax-response>'."\n";
            print $xmlStr;


?>
