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

  $stComm = $_REQUEST['StcommClose'];
  $note = $_REQUEST['noteClose'];
  $result = $_REQUEST['resultClose'];

  $requete = "UPDATE Babel_campagne_societe
                 SET user_id = $userid  ,
                     user_reprise_refid = $userid,
                     fk_statut = 3,
                     closeStatut = $result,
                     closeNote = '$note',
                     date_cloture = now(),
                     closeStComm = $stComm
               WHERE societe_refid = $socid
                 AND campagne_refid = $campId";
  $sql = $db->query($requete);
  $xml = "Error" . $requete;
  if ($sql)
  {
      $xml = "OK";
      $requete1 = "SELECT * FROM Babel_campagne_avancement WHERE societe_refid = $socid AND campagne_refid = $campId ";
      $sql1 = $db->query($requete1);
      while($res1 = $db->fetch_object($sq1l))
      {
          $requete2 = "INSERT INTO Babel_societe_prop_history (dateNote, note, source, importance,societe_refid, source_refid)
                           VALUES ('".$res1->dateModif."', '".$res1->raison.": ".$res1->note."', 'Campagne', 2, $socid, $campId)";
          $db->query($requete2);
      }
      $rslString = "N&eacute;gatif";
      if ($result == 1){ $rslString = "Positif"; }
      $requete2 = "INSERT INTO Babel_societe_prop_history (dateNote, note, source, importance,societe_refid, source_refid)
                        VALUES (now(), 'R&eacute;sultat campagne : ".$rslString." ".$note."', 'Campagne', 2, $socid, $campId)";
      $db->query($requete2);

  }
  header("Content-Type: text/xml");
  $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
  $xmlStr .= '<ajax-response><response>'."\n";
  $xmlStr .= "<xml><![CDATA[".$xml."]]></xml>";
  $xmlStr .= '</response></ajax-response>'."\n";
  print $xmlStr;


?>
