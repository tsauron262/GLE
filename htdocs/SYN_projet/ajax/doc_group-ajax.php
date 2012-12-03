<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 13 oct. 2009
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : doc_group-ajax.php
  * GLE-1.1
  */


require_once("../../main.inc.php");
$action = $_REQUEST['oper'];
$socid=$_REQUEST['socid'];
$docGroupId = $_REQUEST["id"];
$project_id = $_REQUEST["projId"];


$xml ="";

switch($action)
{
    case "add":
    {
        $nom = preg_replace('/\'/','\\\'',htmlentities(utf8_decode($_REQUEST["name"])));
        $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_projet_document_group
                                (nom, fk_projet)
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
        $nom = preg_replace('/\'/','\\\'',htmlentities(utf8_decode($_REQUEST["name"])));
        $requete = "UPDATE ".MAIN_DB_PREFIX."Synopsis_projet_document_group
                       SET nom='".$nom."'
                     WHERE id=".$docGroupId;
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

        $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_projet_document_group WHERE id=".$docGroupId;
        $sql = $db->query($requete);
        if ($sql)
        {
            $xml .= "<ok>ok</ok>";
        } else {
            $xml .= "<ko>ko</ko>";
        }
    }
    break;
    case 'getDetail':
    {
        if ('x' . $_REQUEST['groupId'] != "x")
        {
            $requete = "SELECT ifnull(count(*),0) as cnt,
                               ifnull(sum(filesize),0) as totSize
                          FROM ".MAIN_DB_PREFIX."Synopsis_ecm_document_assoc,
                               ".MAIN_DB_PREFIX."Synopsis_projet_document_li_group
                         WHERE ".MAIN_DB_PREFIX."Synopsis_projet_document_li_group.fk_document = ".MAIN_DB_PREFIX."Synopsis_ecm_document_assoc.rowid
                           AND ".MAIN_DB_PREFIX."Synopsis_projet_document_li_group.fk_group = ".$_REQUEST['groupId'];
            $sql=$db->query($requete);
            $res=$db->fetch_object($sql);
            $xml .= "<count>".$res->cnt.'</count><totSize>'.convertSize($res->totSize).'</totSize>';
        }
    }
    break;
    case 'getDocs':
    {
        $requete = "SELECT ".MAIN_DB_PREFIX."Synopsis_ecm_document_assoc.filename,
                           ".MAIN_DB_PREFIX."Synopsis_ecm_document_assoc.rowid,
                           ifnull(".MAIN_DB_PREFIX."Synopsis_projet_document_li_group.fk_group,-1) as grpId
                      FROM ".MAIN_DB_PREFIX."Synopsis_ecm_document_assoc
                 LEFT JOIN ".MAIN_DB_PREFIX."Synopsis_projet_document_li_group
                        ON ".MAIN_DB_PREFIX."Synopsis_projet_document_li_group.fk_document = ".MAIN_DB_PREFIX."Synopsis_ecm_document_assoc.rowid
                       AND ".MAIN_DB_PREFIX."Synopsis_projet_document_li_group.fk_group = ".$_REQUEST['groupId'];
        $sql = $db->query($requete);
        $arr = array();
        if ($db->num_rows($sql) > 0)
        {
            while ($res=$db->fetch_object($sql))
            {
                $arr[$res->grpId][]=array( 'filename' => $res->filename,  'rowid' => $res->rowid);
            }
        }
        $xml .= "<DocsGrp>";
//        $xml .= "<DocNotInGrp>";
//        foreach($arr as $key=>$val)
//        {
//            if ($key == -1)
//            {
//                foreach($val as $key1=>$val1)
//                {
//                    $xml .= '<file><filename><![CDATA['.$val1['filename'].']]></filename><id><![CDATA['.$val1['rowid'].']]></id></file>';
//                }
//            }
//        }
//        $xml .= "</DocNotInGrp>";
        $xml .= "<DocInGrp>";
        foreach($arr as $key=>$val)
        {
            if ($key != -1)
            {
                foreach($val as $key1=>$val1)
                {
                    $xml .= '<file><filename><![CDATA['.$val1['filename'].']]></filename><id><![CDATA['.$val1['rowid'].']]></id></file>';
                }
            }
        }
        $xml .= "</DocInGrp>";
        $xml .= "</DocsGrp>";
    }
    break;


    case 'addToGroup':
    {
        $docid = $_REQUEST['docId'];
        $groupid = $_REQUEST['groupId'];

        $sql = true;
        $error=false;
        $db->begin();
        $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_projet_document_li_group (fk_document,fk_group) VALUES ($docid,$groupid)";
        $sql = $db->query($requete);
        if (!$sql)
        {
            $error = $requete;
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
        $docid = $_REQUEST['docId'];
        $groupid = $_REQUEST['groupId'];

        $sql = true;
        $error=false;
        $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_projet_document_li_group
                          WHERE fk_document=$docid
                            AND fk_group = $groupid";
        $sql = $db->query($requete);
        if (!$sql)
        {
            $error = $requete;
        }

        if ($sql)
        {
            $xml .= "<ok>ok</ok>";
        } else {
            $xml .= "<ko>ko".$error . "</ko>";
        }

    }
    break;
    case "setRisk":
    {
        $groupid = $_REQUEST['groupId'];
        $occurence = intval($_REQUEST['occurence']);
        $importance = intval($_REQUEST['importance']);
        $description = preg_replace('/\'/','\\\'',htmlentities(utf8_decode($_REQUEST['description'])));

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


function convertSize($count)
{
    if($count > 1024 * 1024 * 1024)
    {
        $count = round($count * 10 / (1024 * 1024 * 1024))/10;
        $count .= " Go";
    } else if($count > 1024 * 1024)
    {
        $count = round($count * 10 / (1024 * 1024))/10;
        $count .= " Mo";

    } else if ($count> 1024)
    {
        $count = round($count * 10 / (1024))/10;
        $count .= " Ko";
    } else {
        $count = round($count * 10)/10;
        $count .= " o";

    }
    return($count);
}

?>
