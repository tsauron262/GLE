<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 13 aout 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : cat_ajax.php
  * GLE-1.0
  */

  require_once('../../main.inc.php');
  $action = $_REQUEST['action'];

$xml = "";
  switch($action)
  {
    case "add":
    {
        $parentID = $_REQUEST['parentID'];
        $level = 0;
        if (!($parentID > 0 ))
        {
            $parentID = "NULL";
            $level=0;
        } else {
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE id = ".$parentID;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $level = $res->level + 1;
            if ('x'.$level == "x")
            {
                $level = 0;
            }
        }
        $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_global_ressources (nom, isGroup, fk_parent_ressource, fk_user_resp,level) VALUES ('".$_REQUEST['nomCat']."',1,$parentID,NULL,$level)";
        $sql = $db->query($requete);
        if ($sql)
        {
            $xml = "<OK>".$db->last_insert_id("".MAIN_DB_PREFIX."Synopsis_global_ressources")."</OK>";
        } else {
            $xml = "<KO>".$db->lasterror."</KO>";
        }

    }
    break;
    case "mod":
    {
        $parentID = $_REQUEST['parentID'];
        $id = $_REQUEST['id'];
        $level = 0;
        if (!($parentID > 0 ))
        {
            $parentID = "NULL";
            $level=0;
        } else {
            $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE id = ".$parentID;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $level = $res->level + 1;
            if ('x'.$level == "x")
            {
                $level = 0;
            }
        }
        $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_global_ressources SET nom='".$_REQUEST['nomCat']."', isGroup=1, fk_parent_ressource=$parentID, level=$level WHERE id = ".$id;
        $sql = $db->query($requete);
        if ($sql)
        {
            modLevel($id,$db,$level);
            $xml = "<OK>".$id."</OK>";
        } else {
            $xml = "<KO>".$db->lasterror."</KO>";
        }
    }
    break;
    case "del":
    {

        $id = $_REQUEST['id'];
        $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE id=".$id;
        $sql = $db->query($requete);
        if ($sql)
        {
            $xml = "<OK>OK</OK>";
        } else {
            $xml = "<KO>".print_r($db,true)."</KO>";
        }

    }
    break;

  }
            header("Content-Type: text/xml");
            $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
            $xmlStr .= '<ajax-response><response>'."\n";
            $xmlStr .= "<xml>".$xml."</xml>";
            $xmlStr .= '</response></ajax-response>'."\n";
            print $xmlStr;


function modLevel($id,$db,$parentLevel)
{
    $newLevel = $parentLevel+1;
    $requete = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_global_ressources WHERE fk_parent_ressource = ".$id;
    $sql = $db->query($requete);
    while ($res = $db->fetch_object($sql))
    {
        $requete1 = "UPDATE ".MAIN_DB_PREFIX."Synopsis_global_ressources SET level=".$newLevel." WHERE id = ".$res->id;
        $db->query($requete1);
        modLevel($res->id,$db,$newLevel);
    }


}
?>
