<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 8-2-2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : editProspection_json.php
  * GLE-1.1
  */
  require_once('../../main.inc.php');
  $action = $_REQUEST['oper'];
  $socid=$_REQUEST['socid'];
//  var_dump($_REQUEST);
switch($action)
{
    case "add":
    {
        $importance = $_REQUEST['importance'];
        $note = $_REQUEST['note'];
        $note = preg_replace("/'/",'\\\'',$note);
        $source = $_REQUEST['source'];
        $dateNote = $_REQUEST['dateNote'];
        if (preg_match("/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})[\W]{1}([0-9]{2})[\W]{1}([0-9]{2})/",$dateNote,$arr))
        {
            $hour = $arr[4];
            if ("x".$hour == "x") $hour = "00";
            $min = $arr[5];
            if ("x".$min == "x") $min = "00";
            $dateNote = $arr[3]."-".$arr[2]."-".$arr[1]." ".$hour.":".$min;
        } else if (preg_match("/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/",$dateNote,$arr))
        {
            $hour = "00";
            $min = "00";
            $dateNote = $arr[3]."-".$arr[2]."-".$arr[1]." ".$hour.":".$min;
        } else {
            echo json_encode(array('ko'));
            break;
        }
//        $dateNote = date('Y-m-d h:i',$dateNote);

        if ($source == 0) { $source = "Internal"; }
        if ($source == 1) { $source = "Campagne"; }
        $requete = "INSERT INTO Babel_societe_prop_history (importance, dateNote, note, source, societe_refid)
                         VALUES ($importance, '$dateNote', '$note', '$source', $socid )";
        $sql = $db->query($requete);
        print $requete;
        if ($sql)
        {
            echo json_encode(array('ok'));
        } else {
            echo json_encode(array('ko'));
        }

    }
    break;
    case "edit":
    default:
    {
        $importance = $_REQUEST['importance'];
        $note = $_REQUEST['note'];
        $note = preg_replace("/'/",'\\\'',$note);
        $source = $_REQUEST['source'];
        $dateNote = $_REQUEST['dateNote'];
        $rowid = $_REQUEST['id'];
        if (preg_match("/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})[\W]{1}([0-9]{2})[\W]{1}([0-9]{2})/",$dateNote,$arr))
        {
            $hour = $arr[4];
            if ("x".$hour == "x") $hour = "00";
            $min = $arr[5];
            if ("x".$min == "x") $min = "00";
            $dateNote = $arr[3]."-".$arr[2]."-".$arr[1]." ".$hour.":".$min;
        } else if (preg_match("/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/",$dateNote,$arr))
        {
            $hour = "00";
            $min = "00";
            $dateNote = $arr[3]."-".$arr[2]."-".$arr[1]." ".$hour.":".$min;
        } else {
            echo json_encode(array('date ko'));
            break;
        }
//        $dateNote = date('Y-m-d h:i',$dateNote);

        if ($source == 0) { $source = "Internal"; }
        if ($source == 1) { $source = "Campagne"; }
        $requete = "UPDATE Babel_societe_prop_history
                       SET importance= $importance,
                           dateNote = '$dateNote',
                           note = '$note',
                           source = '$source'
                     WHERE id = $rowid";

//                     print $requete;
        $sql = $db->query($requete);
        if ($sql)
        {
            echo json_encode(array('ok'));
        } else {
            echo json_encode(array('ko'));
        }

    }
    break;
    case 'del':
    {
        $requete = "DELETE FROM Babel_societe_prop_history WHERE id = ".$_REQUEST['id'];
        $sql = $db->query($requete);
        if ($sql)
        {
            echo json_encode(array('ok'));
        } else {
            echo json_encode(array('ko'));
        }
    }
}
?>
