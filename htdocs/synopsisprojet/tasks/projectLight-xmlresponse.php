<?php
/*
  ** GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Created on : 20 juil. 09
  *
  * Infos on http://www.finapro.fr
  *
  */
 /**
  *
  * Name : project-xmlresponse.php
  * GLE-1.1
  */

  //4 case : insert task
  //         update task
  //         remove task
  //         display task (default)

require_once ('pre.inc.php');
$project_id = $_REQUEST['id'];
$action = $_REQUEST['action'];
$taskId = $_REQUEST['taskId'];
$xml = "";

switch ($action)
{
    default:
    case "descTask":
    {
        $requete = "SELECT t.label,
                           t.rowid,
                           t.description,
                           t.dateo as task_date ,
                           ifnull(t.priority,3) as type,
                           ifnull(t.fk_task_parent,0) as fk_task_parent,
                           t.progress,
                           t.fk_statut,
                           t.note_private as shortDesc,
                           '' as url
                      FROM ".MAIN_DB_PREFIX."projet_task as t
                     WHERE t.rowid = ".$taskId;
        $sql = $db->query($requete);
        //$cnt = 0;
        while ($res = $db->fetch_object($sql))
        {
            $xml .= "<task>\n";
            $xml .= "  <pID>".$taskId."</pID>\n";
            $xml .= "  <pName><![CDATA[".(rien($res->label))."]]></pName>\n";
            $xml .= "  <pStart>".date('d/m/Y H:i',strtotime($res->task_date))."</pStart>\n";
            $xml .= "  <pType>".($res->type)."</pType>\n";
            $xml .= "  <pStatut>".($res->fk_statut == 1 ? "closed": "open")."</pStatut>\n";
            $xml .= "  <pLink><![CDATA[".($res->url)."]]></pLink>\n";
            $xml .= "  <pComp>".$res->progress."</pComp>\n";
            $xml .= "  <caption><![CDATA[".(rien($res->shortDesc))."]]></caption>\n";
            $xml .= "  <desc><![CDATA[".(rien($res->description))."]]></desc>\n";
            $parent = $res->fk_task_parent;
            if (!$res->fk_task_parent > 0)
            {
                $parent = -1;
            }
            $xml .= "  <pParent>".$parent."</pParent>\n";
            $xml .= "</task>\n";
        }
    }
    break;
    case 'import':
    {
        require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
        require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
        $form = new Form($db);
        $propalId = $_REQUEST['propalId'];
        $project_id = $_REQUEST['projId'];

        $roleStr = "<OPTION value='admin'>Admin</OPTION>";
        $roleStr .= "<OPTION value='acto' SELECTED>Intervenant</OPTION>";
        $roleStr .= "<OPTION value='info'>Info</OPTION>";
        $roleStr .= "<OPTION value='read'>Lecteur</OPTION>";

        //1 list le contenu de la propal (service uniquement)
        $requete = "SELECT p.fk_product_type,
                            p.label,
                           d.description,
                           d.fk_product,
                           d.qty
                      FROM ".MAIN_DB_PREFIX."propaldet as d
                 LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = d.fk_product AND fk_product_type = 1
                     WHERE fk_propal = ".$propalId;
        //2 trouve les groupes
        $sql = $db->query($requete);
        $taskName = "";
        $secTaskName = "";
        while($res = $db->fetch_object($sql)){
            if(preg_match('/^\[header\]/i',$res->description))
            {
                $taskName = preg_replace('/^\[header\]/i',"",$res->description);
            } else if (preg_match('/^\[header1\]/i',$res->description))
            {
                $secTaskName = preg_replace('/^\[header1\]/i',"",$res->description);
            } else if ($res->fk_product > 0 && $res->fk_product_type == 1){
                $taskDesc = html_entity_decode(preg_replace('/^\[desc\]/i',"",$res->description));
                $fullTaskName = html_entity_decode(addslashes($res->label." - ".($secTaskName."x"!="x"?$secTaskName." ":"").$taskDesc));
                $shortDescription = html_entity_decode(addslashes(('x'.$taskName != 'x' ?$taskName." ":"").($secTaskName."x"!="x"?$secTaskName." ":"").$taskDesc));
                $description = html_entity_decode(addslashes(('x'.$taskName != 'x' ?$taskName." ":"").($secTaskName."x"!="x"?$secTaskName." ":"").$taskDesc));
                $parentId="0";
                $progress = 0;
                $priority=2;
                $datedeb = date("Y-m-d H:i");
                $statut=0;
                $db->begin();
                $level=1;

                $duration = 0;

                $arrDuration['m']= 7 * 20;
                $arrDuration['d']= 7;
                $arrDuration['h'] = 1;
                $arrDuration['w'] = 35;

                $product = new Product($db);
                $product->fetch($res->fk_product);

                $duration = floatval($res->qty) * floatval($product->duration_value) * ($arrDuration[$product->duration_unit] > 0 ? intval($arrDuration[$product->duration_unit]):1);
                $duration = preg_replace('/,/','.',$duration);
                $requete = "INSERT INTO ".MAIN_DB_PREFIX."projet_task
                                        (fk_projet, fk_task_parent, label, dateo, fk_user_creat,fk_statut,note_public,progress,description,priority , note_private, rang)
                                 VALUES ($project_id,$parentId, '$fullTaskName', '$datedeb', ".$user->id.",'$statut','$note', $progress,'$description', $priority,'$shortDescription',$level)";
                $sql1 = $db->query($requete);
                if ($sql1)
                {
                    $taskId = $db->last_insert_id($sql1);

                    $xml .= "<task>";
                    $xml .= "<id>".$taskId."</id>";
                    $xml .= "<name><![CDATA[".$fullTaskName."]]></name>";
                    $xml .= "<desc><![CDATA[".$shortDescription."]]></desc>";
                    $xml .= "<dateo><![CDATA[".date('d/m/Y H:i')."]]></dateo>";
                    $xml .= "<role><![CDATA[<select name='role".$taskId."'>".($roleStr)."</select>]]></role>";
                    $userList =$form->select_dolusers('','userid'.$taskId,0,'',0,false);
                    $xml .= "<duration><![CDATA[".$duration."]]></duration>";
                    $xml .= "<userList><![CDATA[".($userList)."]]></userList>";
                    $xml .= "</task>";

                    $db->commit();

                    require_once(DOL_DOCUMENT_ROOT.'/synopsisprojet/class/synopsisproject.class.php');
                    $taskObj = new SynopsisProjectTask($db);
                    $taskObj->fetch($taskId);
                    //appel triggers
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface=new Interfaces($db);
                    global $user;
                    $result=$interface->run_triggers('PROJECT_CREATE_TASK',$taskObj,$user,$langs,$conf);
                    if ($result < 0) { $error++; $errors=$interface->errors; }
                     //Fin appel triggers
                } else {
                    $xml = "<response>Error</response>";
                    $xml .= "<requete>".$requete."</requete>";
                    $xml .= "<error>".$db->lasterrno."</error>";
                    $xml .= "<error>".$db->lastqueryerror."</error>";
                    $db->rollback();

                }

            }
        }
        //3 Importe les taches
    }
    break;
    case 'step2Import':
    {
        foreach($_REQUEST as $key=>$val)
        {
            if(preg_match('/^name([0-9]*)/',$key,$arrMatch))
            {
                $id = $arrMatch[1];
                $name = $val;

                $dateo = $_REQUEST['dateo'.$id];
                $debdateUS = "";
                if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/",$dateo,$arr))
                {
                    $debdateUS = $arr[3]."-".$arr[2]."-".$arr[1]." ".$arr[4].":".$arr[5];
                }
                $userId = $_REQUEST['userid'.$id];
                $role = $_REQUEST['role'.$id];
                $dur = $_REQUEST['dur'.$id];

                $requete = "DELETE FROM ".MAIN_DB_PREFIX."projet_task_time WHERE fk_task = ".$id;
                $sql = $db->query($requete);
                $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors WHERE fk_projet_task = ".$id;
                $sql1 = $db->query($requete);

                if ($sql && $sql1)
                {
                    $bool=true;
                    $requete= "INSERT INTO ".MAIN_DB_PREFIX."projet_task_time
                                           (fk_task,task_date,task_duration,fk_user)
                                    VALUES (".$id.",'".$debdateUS."',".intval($dur * 3600).",".$userId.")";
                    $sql = $db->query($requete);
                    if (!$sql) $bool = false;

                    $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_projet_task_actors
                                            (fk_projet_task, fk_user,role, percent,type)
                                     VALUES (".$id.",".$userId.",'".$role."',100,'user')";
                    $sql = $db->query($requete);
                    if (!$sql) $bool = false;
                }

                $requete = "SELECT sum(task_duration) as dur FROM ".MAIN_DB_PREFIX."projet_task_time WHERE fk_task = ".$id;
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $tot = $res->dur;
                $requete = "UPDATE ".MAIN_DB_PREFIX."projet_task SET planned_workload = ".$tot . ", label = '".$_REQUEST['name'.$id]."' WHERE rowid = ".$id;
                $sql = $db->query($requete);


            }
        }
    }
    break;
    case 'insert':
    {
        $name = $_REQUEST['name'];
        $parentId = $_REQUEST['parent'];
        if ($parentId == -1 )
        {
            $parentId="0";
        }
        $userid = $_REQUEST['userid'];
        $note = "";
        $progress = $_REQUEST['complet'];
        $progress = floatval($progress);
        $progress = preg_replace("/[,]/",".",$progress);
        $description = stripslashes($_REQUEST['desc']);
        $description = preg_replace("/'/","\\\'",$description);
        $shortDescription = stripslashes($_REQUEST['shortDesc']);
        $url = stripslashes($_REQUEST['url']);
        if (! preg_match('/^[http:\/\/]/',$url))
        {
            $url = 'http://'.$url;
        }

        $priority= $_REQUEST['type'];

        $statut = $_REQUEST['statut']; //closed or opened

            $datedeb = $_REQUEST['datedeb'];
            $debdateUS="";
            $debts="0";
            $finddateUS="";
            $fints="0";
        if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/",$datedeb,$arr))
        {
            $debdateUS = $arr[3]."-".$arr[2]."-".$arr[1]." ".$arr[4].":".$arr[5];
            $debts = strtotime($arr[3]."-".$arr[2]."-".$arr[1]);
            $debts += $arr[5]*60 + $arr[4] * 3600;
        }


        $level = 1;

        if ($parentId ."x" != "x")
        {
            //Get parent Level
            $requete = "SELECT rang as level
                          FROM ".MAIN_DB_PREFIX."projet_task
                         WHERE rowid = ".$parentId;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $level = $res->level + 1;
        } else {
            $parentId = "0";
        }

//        $name = ($name);
        $db->begin();
        $name = addslashes($name);
        $requete = "INSERT INTO ".MAIN_DB_PREFIX."projet_task
                                (fk_projet, fk_task_parent, label, dateo, fk_user_creat,fk_statut,note_public,progress,description,priority , note_private, rang)
                         VALUES ($project_id,$parentId, '$name', '$debdateUS', $userid,'$statut','$note', $progress,'$description', $priority,'$shortDescription',$level)";
        $sql = $db->query($requete);
        if ($sql)
        {
            $taskId = $db->last_insert_id($sql);
            //date

                $db->commit();
                require_once(DOL_DOCUMENT_ROOT.'/synopsisprojet/class/synopsisproject.class.php');
                $taskObj = new SynopsisProjectTask($db);
                $taskObj->fetch($taskId);
                //appel triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($db);
                global $user;
                $result=$interface->run_triggers('PROJECT_CREATE_TASK',$taskObj,$user,$langs,$conf);
                if ($result < 0) { $error++; $errors=$interface->errors; }
                 //Fin appel triggers



//            $xml = do_select($db,$project_id);
        } else {
            $xml = "<response>Error</response>";
            $xml .= "<requete>".$requete."</requete>";
            $xml .= "<error>".$db->lasterrno."</error>";
            $xml .= "<error>".$db->lastqueryerror."</error>";
            $db->rollback();

        }
    }
    break;
    case "update":
    {
        $name = addslashes($_REQUEST['name']);
        $parentId = $_REQUEST['parent'];
        if ($parentId <0) $parentId ="";
        $note = "";
        $progress = $_REQUEST['progress'];
        $progress = floatval($progress);
        $progress = preg_replace("/[,]/",".",$progress);
        $description = addslashes($_REQUEST['description']);
        $shortDescription = addslashes($_REQUEST['shortDescription']);
        $url =$_REQUEST['url'];
        $statut = $_REQUEST['statut'];
        if (! preg_match('/^[http:\/\/]/',$url))
        {
            $url = 'http://'.$url;
        }
        $priority = $_REQUEST['type'];

        $datedeb = $_REQUEST['datedeb'];
        $debdateUS="";
        $debts="0";
        $finddateUS="";
        $fints="0";
        if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/",$datedeb,$arr))
        {
            $debdateUS = $arr[3]."-".$arr[2]."-".$arr[1]." ".$arr[4].":".$arr[5];
            $debts = strtotime($arr[3]."-".$arr[2]."-".$arr[1]);
            $debts += $arr[5]*60 + $arr[4] * 3600;
        }

        $level = 1;
        //Get parent Level
        if ($parentId . "x" != "x")
        {
            $requete = "SELECT rang as level FROM ".MAIN_DB_PREFIX."projet_task WHERE rowid = ".$parentId;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $level = $res->level + 1;

        }



        $db->begin();

        if ($parentId . 'x' == 'x')
        {
            $parentId = "0";
        }

        $requete = "UPDATE ".MAIN_DB_PREFIX."projet_task
                       SET fk_task_parent = $parentId,
                           label = '".($name)."',
                           dateo = '$debdateUS',
                           note_public = '".($shortDescription)."',
                           progress = $progress,
                           description = '".($description)."',
                           note_private = '".($shortDescription)."',
                           fk_statut = ".($statut == "open"? 0:1) .",
                           rang = $priority
                     WHERE rowid = ".$taskId;

        $sql = $db->query($requete);
        if ($sql)
        {
            //ressource et role et dependance

                $db->commit();

                require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");
                $taskObj = new SynopsisProjectTask($db);
                $taskObj->fetch($taskId);
                //appel triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($db);
                global $user;
                $result=$interface->run_triggers('PROJECT_UPDATE_TASK',$taskObj,$user,$langs,$conf);
                if ($result < 0) { $error++; $errors=$interface->errors; }
                 //Fin appel triggers

        } else {
            $xml = "<response>Error</response>";
            $xml .= "<requete>".$requete."</requete>";
            $xml .= "<error>".$db->lastqueryerror."</error>";
            $xml .= "<error>".$db->lasterror."</error>";
            $xml .= "<error>".print_r($db,true)."</error>";
            $db->rollback();
        }
    }
    break;
    case "listUser":
    {
        $requete = "SELECT ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_user,
                           (".MAIN_DB_PREFIX."projet_task_time.task_duration / 3600) as dur,
                           ".MAIN_DB_PREFIX."projet_task_time.task_date,
                           ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.role
                      FROM ".MAIN_DB_PREFIX."projet_task_time, ".MAIN_DB_PREFIX."Synopsis_projet_task_actors
                     WHERE ".MAIN_DB_PREFIX."projet_task_time.fk_task = ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_projet_task
                       AND ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.fk_user = ".MAIN_DB_PREFIX."projet_task_time.fk_user
                       AND ".MAIN_DB_PREFIX."Synopsis_projet_task_actors.type = 'user'
                       AND ".MAIN_DB_PREFIX."projet_task_time.fk_task = ".$taskId;
        $sql = $db->query($requete);

        while($res = $db->fetch_object($sql)){
            $xml .= "<user>";
            $xml .= "  <id>".$res->fk_user."</id>";
            $xml .= "  <dur>".round(100*$res->dur)/100 ."</dur>";
            $xml .= "  <taskDateDeb>".($res->task_date ."x" != "x"? date('d/m/Y H:i',strtotime($res->task_date)) :"") ."</taskDateDeb>";
            $xml .= "  <role>".$res->role."</role>";
            $xml .= "</user>";
        }
    }
    break;
    case "ajust":
    {
        $db->begin();

        $requete = "SELECT * FROM ".MAIN_DB_PREFIX."projet_task WHERE rowid = ".$taskId;
        $sql = $db->query($requete);
        $res = $db->fetch_object($sql);
        $dateo = $res->dateo;

        $requete = "DELETE FROM ".MAIN_DB_PREFIX."projet_task_time WHERE fk_task = ".$taskId;
        $sql = $db->query($requete);
        $requete = "DELETE FROM ".MAIN_DB_PREFIX."Synopsis_projet_task_actors WHERE fk_projet_task = ".$taskId;
        $sql1 = $db->query($requete);
        if ($sql && $sql1)
        {
            $bool=true;
            foreach ( $_REQUEST as $key => $value )
            {
                if (preg_match('/^role([0-9]*)$/',$key,$arrMatch))
                {
                    $role = $value;
                    $userId = $arrMatch[1];
                    $dur = $_REQUEST['dur'.$userId];
                    $dateoFR = $_REQUEST['taskDateDeb'.$userId];
                    if($dur > 0)
                    {
                        $debdateUS = false;
                        if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/",$dateoFR,$arr))
                        {
                            $debdateUS = $arr[3]."-".$arr[2]."-".$arr[1]." ".$arr[4].":".$arr[5];
                        }
                        if ($debdateUS) $dateo = $debdateUS;
                        $requete= "INSERT INTO ".MAIN_DB_PREFIX."projet_task_time
                                               (fk_task,task_date,task_duration,fk_user)
                                        VALUES (".$taskId.",'".$dateo."',".intval($dur * 3600).",".$userId.")";
                        $sql = $db->query($requete);
                        if (!$sql) $bool = false;

                        $requete = "INSERT INTO ".MAIN_DB_PREFIX."Synopsis_projet_task_actors
                                                (fk_projet_task, fk_user,role, percent,type)
                                         VALUES (".$taskId.",".$userId.",'".$role."',100,'user')";
                        $sql = $db->query($requete);
                        if (!$sql) $bool = false;

                    }
                }
            }
            if ($bool) $db->commit();
            $requete = "SELECT sum(task_duration) as dur FROM ".MAIN_DB_PREFIX."projet_task_time WHERE fk_task = ".$taskId;
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);
            $tot = $res->dur;
            $requete = "UPDATE ".MAIN_DB_PREFIX."projet_task SET planned_workload = ".$tot . " WHERE rowid = ".$taskId;
            $sql = $db->query($requete);

        } else {
            $db->rollback();
        }
    }
    break;
    case "delete":
    {
        require_once(DOL_DOCUMENT_ROOT."/synopsisprojet/class/synopsisproject.class.php");
        $taskObj = new SynopsisProjectTask($db);
        $taskObj->fetch($taskId);
        //appel triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface=new Interfaces($db);
        global $user;
        $result=$interface->run_triggers('PROJECT_DELETE_TASK',$taskObj,$user,$langs,$conf);
        if ($result < 0) { $error++; $errors=$interface->errors; }
         //Fin appel triggers

        $requete = "DELETE FROM ".MAIN_DB_PREFIX."projet_task
                     WHERE rowid = ".$taskId;
        $sql = $db->query($requete);
        if ($sql)
        {
            $xml = "<OK>OK</OK>";
        } else {
            $xml = "<response>Error</response>";
        }

    }
}


if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") )
{
    header("Content-type: application/xhtml+xml;charset=utf-8");
} else {
    header("Content-type: text/xml;charset=utf-8");
}
$et = ">";
print "<?xml version='1.0' encoding='utf-8'?$et\n";
print "<ajax-response>";
print $xml;
print "</ajax-response>";


//header("Content-Type: text/xml");
//$xmlStr = '<'.'?xml version="1.0" encoding="ISO-8859-1"?'.'>';
//print $xmlStr . $xml;
function rien($str){ return $str; }
?>