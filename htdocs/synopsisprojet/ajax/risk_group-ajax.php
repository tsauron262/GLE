<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 29 sept. 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : risk_group-ajax.php
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
        $nom = preg_replace('/\'/','\\\'',htmlentities(($_REQUEST["name"])));
        $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_projet_risk_group
                                (group_name, fk_projet)
                         VALUES ('".$nom."',".$project_id.")";
        $sql = $db->query($requete);
        if ($sql)
        {
            $xml .= "<ok>ok</ok>";
        } else {
            $xml .= "<ko>ko".$requete."</ko>";
        }
    }
    break;
    case "mod":
    {
        $nom = preg_replace('/\'/','\\\'',htmlentities(($_REQUEST["name"])));
        $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_projet_risk_group
                       SET group_name='".$nom."'
                     WHERE id=".$riskid;
        $sql = $db->query($requete);
        if ($sql)
        {
            $xml .= "<ok>ok</ok>";
        } else {
            $xml .= "<ko>ko\n</ko>";
        }
    }
    break;
    case 'del':
    {

        $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk_group WHERE id=".$riskid;
        $sql = $db->query($requete);
        if ($sql)
        {
            $xml .= "<ok>ok</ok>";
        } else {
            $xml .= "<ko>ko</ko>";
        }
    }
    break;
    case 'addToGroup':
    {
        $taskid = $_REQUEST['taskId'];
        $groupid = $_REQUEST['groupId'];
        //Si c'est un groupe
        $arrParent=array();

        //trouver tous les enfants => class
        require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");
        $task=new SynopsisProjectTask($db);
        $task->id = $taskid;
        $task->getChildsTree();
        $arrParent = $task->childArray;
        array_push($arrParent,$taskid);

        $sql = true;
        $error=false;
        $db->begin();
        foreach($arrParent as $key)
        {
                $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_projet_li_task_group (fk_task,fk_group_risk) VALUES ($key,$groupid)";
                $sql = $db->query($requete);
                if (!$sql)
                {
                    $error = $requete;
                }
        }
        if ($sql)
        {
            $db->commit();
            $xml .= "<ok>ok</ok>";
        } else {
            $db->rollback();
            $xml .= "<ko>ko".$error . "</ko>";
        }

    }
    break;
    case "remFromGroup":
    {
        $taskid = $_REQUEST['taskId'];
        $groupid = $_REQUEST['groupId'];

        //Si c'est un groupe
        $arrParent=array();

        //trouver tous les enfants => class
        require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");
        $task=new SynopsisProjectTask($db);
        $task->id = $taskid;
        $task->getChildsTree();
        $arrParent = $task->childArray;
        array_push($arrParent,$taskid);

        $sql = true;
        $error=false;
        $db->begin();
        foreach($arrParent as $key)
        {

            $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_projet_li_task_group
                              WHERE fk_task=$key
                                AND fk_group_risk = $groupid";
                $sql = $db->query($requete);
                if (!$sql)
                {
                    $error = $requete;
                }
        }
        if ($sql)
        {
            $db->commit();
            $xml .= "<ok>ok</ok>";
        } else {
            $db->rollback();
            $xml .= "<ko>ko".$error . "</ko>";
        }

    }
    break;
    case "setRisk":
    {
        $groupid = $_REQUEST['groupId'];
        $occurence = intval($_REQUEST['occurence']);
        $importance = intval($_REQUEST['importance']);
        $description = preg_replace('/\'/','\\\'',htmlentities(($_REQUEST['description'])));

        $requete = "SELECT *
                      FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk
                     WHERE fk_risk_group = " . $groupid;
        $sql = $db->query($requete);
        if ($db->num_rows($sql) > 0)
        {
            $requete = 'UPDATE ".MAIN_DB_PREFIX."Synopsis_projet_risk
                           SET description = "'.$description.'",
                               occurence = '.$occurence.',
                               gravite = '.$importance.'
                         WHERE fk_risk_group = '.$groupid;
            $sql = $db->query($requete);
            if ($sql)
            {
                $xml .= "<ok>ok</ok>";
            } else {
                $xml .= "<ko>ko</ko>";
            }

        } else {
            $requete1 = "SELECT * FROM ".MAIN_DB_PREFIX."Synopsis_projet_risk_group WHERE id = ".$groupid;
            $sql1 = $db->query($requete1);
            $res1 = $db->fetch_object($sql1);


            $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_projet_risk
                                    (nom, gravite, occurence, description, fk_projet, fk_risk_group)
                             VALUES ('".$res1->group_name."',".$importance.",".$groupid.",'".$description."',".$res1->fk_projet.",".$groupid.")";
            $sql = $db->query($requete);
            if ($sql)
            {
                $xml .= "<ok>ok</ok>";
            } else {
                $xml .= "<ko>ko</ko>";
            }
        }

    }
}



    header("Content-Type: text/xml");
    $xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
    $xmlStr .= '<ajax-response><response>'."\n";
    $xmlStr .= $xml;
    $xmlStr .= '</response></ajax-response>'."\n";
    print $xmlStr;
?>
