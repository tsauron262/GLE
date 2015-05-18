<?php

/*
 * * GLE by Synopsis et DRSI
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

switch ($action) {
    case "list":
    default: {
            //TODO pOpen
            $xml = do_select($db, $project_id);
        }
        break;
    case "descTask": {
            $requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task.title,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid,
                           date_format(" . MAIN_DB_PREFIX . "Synopsis_projet_task_time.task_date,'%d/%m/%Y %H:%i') as task_date ,
                           date_format(DATE_ADD(" . MAIN_DB_PREFIX . "Synopsis_projet_task_time.task_date,INTERVAL " . MAIN_DB_PREFIX . "Synopsis_projet_task_time.task_duration second),'%d/%m/%Y %H:%i' )as task_end,
                           ifnull(" . MAIN_DB_PREFIX . "Synopsis_projet_task.color, '0000FF') as color,
                           ifnull(fk_task_type,3) as type,
                           ifnull(fk_task_parent,0) as fk_task_parent,
                           progress,
                           shortDesc,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.url
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task
                 LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_time ON " . MAIN_DB_PREFIX . "Synopsis_projet_task_time.fk_task = " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid
                     WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid = " . $taskId;
            $sql = $db->query($requete);
            //Open all by default
            $pOpen = 1;


            if (isset($conf->global->MAIN_MODULE_SYNOPSISHRM)) {
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Hrm/hrm.class.php");
                $hrm = new hrm($db);
                $hrm->listTeam();
            }


            //$cnt = 0;
            while ($res = $db->fetch_object($sql)) {
                $isMilestone = 0;
                $isGroup = 0;
                if ($res->type == 1) {
                    $isMilestone = 1;
                } else if ($res->type == 3) {
                    $isGroup = 1;
                }
                $xml .= "<task>\n";
                $xml .= "  <pID>" . $taskId . "</pID>\n";
                $xml .= "  <pName><![CDATA[" . utf8_decode($res->title) . "]]></pName>\n";
                $xml .= "  <pStart>" . $res->task_date . "</pStart>\n";
                $xml .= "  <pEnd>" . $res->task_end . "</pEnd>\n";
                $xml .= "  <pColor>" . $res->color . "</pColor>\n";
                $xml .= "  <pType>" . $res->type . "</pType>\n";
                $xml .= "  <pLink><![CDATA[" . $res->url . "]]></pLink>\n";
                $xml .= "  <pMile>" . $isMilestone . "</pMile>\n";
                $requete1 = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_user, " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.percent, " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.type
                           FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors
                          WHERE fk_projet_task =" . $taskId . "
                            AND role = 'admin' ";
                $sql1 = $db->query($requete1);
                $dependsArr = array();
                $xml .= "  <pRes>\n";
                $xml .= "  <admin>\n";
                while ($res1 = $db->fetch_object($sql1)) {
                    if ($res1->type == "user") {
                        $tmpUser = new User($db);
                        $tmpUser->id = $res1->fk_user;
                        $tmpUser->fetch($tmpUser->id);

                        $xml .= "<user>";
                        $xml .="<userid>" . $res1->fk_user . '</userid>';
                        $xml .="<username>" . $tmpUser->firstname . " " . $tmpUser->lastname . '</username>';
                        $xml .="<percent>" . $res1->percent . '</percent>';
                        $xml .="<type>User</type>";
                        $xml .= "</user>";
                    } else if (isset($conf->global->MAIN_MODULE_SYNOPSISHRM)) {

                        $name = preg_replace('/&[\w;]*$/', "", htmlentities($hrm->teamRessource[$res1->fk_user]['name']));

                        $xml .= "<user>";
                        $xml .="<userid>" . $res1->fk_user . '</userid>';
                        $xml .="<username>" . $name . '</username>';
                        $xml .="<percent>" . $res1->percent . '</percent>';
                        $xml .="<type>Group</type>";
                        $xml .= "</user>";
                    }
                }
                $xml .= "  </admin>\n";
                $xml .= "  <acto>\n";

                $requete1 = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_user, " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.percent, " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.type
                           FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors
                          WHERE fk_projet_task =" . $taskId . "
                            AND role = 'acto' ";
                $sql1 = $db->query($requete1);

                while ($res1 = $db->fetch_object($sql1)) {
                    if ($res1->type == "user") {
                        $tmpUser = new User($db);
                        $tmpUser->fetch($res1->fk_user);

                        $xml .= "<user>";
                        $xml .="<userid>" . $res1->fk_user . '</userid>';
                        $xml .="<username>" . $tmpUser->getFullName($langs) . '</username>';
                        $xml .="<percent>" . $res1->percent . '</percent>';
                        $xml .="<type>User</type>";
                        $xml .= "</user>";
                    } else if (isset($conf->global->MAIN_MODULE_SYNOPSISHRM)) {

                        $name = preg_replace('/&[\w;]*$/', "", htmlentities($hrm->teamRessource[$res1->fk_user]['name']));

                        $xml .= "<user>";
                        $xml .="<userid>" . $res1->fk_user . '</userid>';
                        $xml .="<username>" . $name . '</username>';
                        $xml .="<percent>" . $res1->percent . '</percent>';
                        $xml .="<type>Group</type>";
                        $xml .= "</user>";
                    }
                }
                $xml .= "  </acto>\n";
                $xml .= "  <read>\n";

                $requete1 = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_user, " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.percent, " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.type
                           FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors
                          WHERE fk_projet_task =" . $taskId . "
                            AND role = 'read'";
                $sql1 = $db->query($requete1);

                while ($res1 = $db->fetch_object($sql1)) {
                    if ($res1->type == "user") {
                        $tmpUser = new User($db);
                        $tmpUser->fetch($res1->fk_user);

                        $xml .= "<user>";
                        $xml .="<userid>" . $res1->fk_user . '</userid>';
                        $xml .="<username>" . $tmpUser->getFullName($langs) . '</username>';
                        $xml .="<percent>" . $res1->percent . '</percent>';
                        $xml .="<type>User</type>";
                        $xml .= "</user>";
                    } else if (isset($conf->global->MAIN_MODULE_SYNOPSISHRM)) {

                        $name = preg_replace('/&[\w;]*$/', "", htmlentities($hrm->teamRessource[$res1->fk_user]['name']));

                        $xml .= "<user>";
                        $xml .="<userid>" . $res1->fk_user . '</userid>';
                        $xml .="<username>" . $name . '</username>';
                        $xml .="<percent>" . $res1->percent . '</percent>';
                        $xml .="<type>Group</type>";
                        $xml .= "</user>";
                    }
                }
                $xml .= "  </read>\n";

                $xml .= "  </pRes>\n";


                $xml .= "  <pComp>" . $res->progress . "</pComp>\n";
                $xml .= "  <pGroup>" . $isGroup . "</pGroup>\n";
                //Si shrtdesc TODO
                $xml .= "  <caption><![CDATA[" . $res->shortDesc . "]]></caption>\n";
                $parent = $res->fk_task_parent;
                if ($res->fk_task_parent == 0) {
                    $parent = $res->fk_task_parent = -1;
                }
                $xml .= "  <pParent>" . $parent . "</pParent>\n";
                $xml .= "  <pOpen>" . $pOpen . "</pOpen>\n";
                $requete1 = "SELECT *
                           FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_depends,
                                " . MAIN_DB_PREFIX . "Synopsis_projet_task
                          WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid = " . MAIN_DB_PREFIX . "Synopsis_projet_task_depends.fk_depends AND fk_task =" . $taskId;
                $sql1 = $db->query($requete1);
                $dependsArr = array();
                $xml .= "<Depends>";
                while ($res1 = $db->fetch_object($sql1)) {
                    $xml.= "<depend>\n";
                    $dependsArr[] = $res1->fk_depends;
                    $xml .= "\t<depID>" . $res1->fk_depends . "</depID>";
                    $xml .= "\t<pDependId>" . $res1->fk_depends . "</pDependId>\n";
                    $xml .= "\t<pDependName><![CDATA[" . $res1->title . "]]></pDependName>\n";
                    $xml .= "\t<pDependPercent>" . $res1->percent . "</pDependPercent>\n";
                    $xml.= "</depend>\n";
                }
                $xml .= "</Depends>";

                $xml .= "  <pDepend>" . join($dependsArr, ",") . "</pDepend>\n";

                $xml .= "<trancheHoraires>";
                $requete = "SELECT ifnull(day,1) as day, fk_tranche, fk_user, type, qte
                          FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_special,
                               " . MAIN_DB_PREFIX . "Synopsis_projet_trancheHoraire
                         WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_special.fk_tranche = " . MAIN_DB_PREFIX . "Synopsis_projet_trancheHoraire.id
                           AND fk_task = " . $taskId;
                $sql1 = $db->query($requete);
                while ($res1 = $db->fetch_object($sql1)) {
                    $xml .= "<trancheHoraire>";
                    $xml .= "<idTranche>" . $res1->fk_tranche . "</idTranche>";
                    $xml .= "<idUser>" . $res1->fk_user . "</idUser>";
                    $xml .= "<type>" . $res1->type . "</type>";
                    $xml .= "<qte>" . $res1->qte . "</qte>";
                    $xml .= "<day>" . $res1->day . "</day>";
                    $xml .= "</trancheHoraire>";
                }
                $xml .= "</trancheHoraires>";



                $xml .= "</task>\n";
            }
        }
        break;
    case 'insert': {
            $name = stripslashes($_REQUEST['name']);
            $parentId = $_REQUEST['parent'];
            if ($parentId == -1) {
                $parentId = "";
            }
            $userid = $_REQUEST['userid'];
            $note = "";
            $progress = $_REQUEST['complet'];
            $progress = floatval($progress);
            $progress = preg_replace("/[,]/", ".", $progress);
            $description = stripslashes($_REQUEST['desc']);
            $description = preg_replace("/'/", "\\\'", $description);
            $shortDescription = stripslashes($_REQUEST['shortDesc']);
            $color = $_REQUEST['color'];
            $url = stripslashes($_REQUEST['url']);
            if (!preg_match('/^[http:\/\/]/', $url)) {
                $url = 'http://' . $url;
            }

            $fk_task_type = $_REQUEST['type'];

            $statut = isset($_REQUEST['statut']) ? $_REQUEST['statut'] : "open"; //closed or opened

            $datedeb = $_REQUEST['datedeb'];
            $datefin = $_REQUEST['datefin'];
            $debdateUS = "";
            $debts = "0";
            $finddateUS = "";
            $fints = "0";
            if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/", $datedeb, $arr)) {
                $debdateUS = $arr[3] . "-" . $arr[2] . "-" . $arr[1] . " " . $arr[4] . ":" . $arr[5];
                $debts = strtotime($arr[3] . "-" . $arr[2] . "-" . $arr[1]);
                $debts += $arr[5] * 60 + $arr[4] * 3600;
            }

            if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/", $datefin, $arr)) {
                $findateUS = $arr[3] . "-" . $arr[2] . "-" . $arr[1] . " " . $arr[4] . ":" . $arr[5];
                $fints = strtotime($arr[3] . "-" . $arr[2] . "-" . $arr[1]);
                $fints += $arr[5] * 60 + $arr[4] * 3600;
            }

            //2 Cases duration or date end ???
            $duration_effective = abs($fints - $debts);

            $level = 1;

            if ($parentId . "x" != "x") {
                //Get parent Level
                $requete = "SELECT level
                          FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task
                         WHERE rowid = " . $parentId;
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $level = $res->level + 1;
            } else {
                $parentId = "NULL";
            }


            $db->begin();
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_task
                                (fk_projet, fk_task_parent, title, duration_effective, fk_user_creat,statut,note,progress,description,color,url,fk_task_type , shortDesc, level,dateDeb)
                         VALUES ($project_id,$parentId, '$name', $duration_effective, $userid,'$statut','$note', $progress,'$description', '$color', '$url', $fk_task_type,'$shortDescription',$level,'$debdateUS')";
            $sql = $db->query($requete);
            if ($sql) {
                $taskId = $db->last_insert_id($sql);
                //date
                //ressource et role et dependance
                $dependArr = preg_split('/,/', $_REQUEST['depend']);
                $errBool = false;

                if (insertActor($db, $taskId, $userid, $_REQUEST['ressource'])) {
                    $errBool = false;
                } else {
                    $errBool = true;
                }

                foreach ($dependArr as $key => $val) {
                    if (strlen($val) > 2) {
                        $arr = preg_split('/:/', $val);
                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_task_depends (fk_task, fk_depends,percent)
                                     VALUES ($taskId, $arr[0],$arr[1])";
                        $sql = $db->query($requete);
                        if (!$sql && !$errBool) {
                            $errBool = true;
                        }
                    }
                }

                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_task_time
                                    (fk_task, task_date,task_duration, fk_user )
                             VALUES ($taskId, '$debdateUS', $duration_effective, $userid )";
                $sqltime = $db->query($requete);


                $trancheArr = array();
                $trancheArr = preg_split('/,/', $_REQUEST['TrancheHoraire']);
                foreach ($trancheArr as $key => $val) {
                    $trancheDetArr = preg_split('/:/', $val);
                    $type = $trancheDetArr[0];
                    $fk_user = $trancheDetArr[1];
                    $jour = $trancheDetArr[2];
                    $fk_tranche = $trancheDetArr[3];
                    $qte = $trancheDetArr[4];
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_special
                                      (fk_tranche, fk_user, type, qte, fk_task)
                               VALUES ('" . $fk_tranche . "', '" . $fk_user . "', '" . $type . "', '" . $qte . "', '" . $taskId . "')";
                    $db->query($requete);
                }



                if (!$errBool && $sqltime) {
                    $db->commit();
                    require_once(DOL_DOCUMENT_ROOT . '/synopsisprojet/class/synopsisproject.class.php');
                    $taskObj = new SynopsisProjectTask($db);
                    $taskObj->fetch($taskId);
                    //appel triggers
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface = new Interfaces($db);
                    global $user;
                    $result = $interface->run_triggers('PROJECT_CREATE_TASK', $taskObj, $user, $langs, $conf);
                    if ($result < 0) {
                        $error++;
                        $errors = $interface->errors;
                    }
                    //Fin appel triggers
                } else {
                    $xml = "<response>Error</response>";
                    $xml .= "<requete>" . $requete . "</requete>";
                    $xml .= "<error>" . $db->lastqueryerror . "</error>";
                    $xml .= "<error>" . $db->lasterror . "</error>";


                    $db->rollback();
                }

                $xml = do_select($db, $project_id);
            } else {
                $xml = "<response>Error</response>";
                $xml .= "<requete>" . $requete . "</requete>";
                $xml .= "<error>" . $db->lasterrno . "</error>";
                $db->rollback();
            }
        }
        break;
    case "update": {
            $name = $_REQUEST['name'];
            $parentId = $_REQUEST['parent'];
            if ($parentId < 0)
                $parentId = "";
            $userid = $_REQUEST['userid'];
            $note = "";
            $progress = $_REQUEST['progress'];
            $progress = floatval($progress);
            $progress = preg_replace("/[,]/", ".", $progress);
            $description = $_REQUEST['description'];
            $shortDescription = $_REQUEST['shortDescription'];
            $color = $_REQUEST['color'];
            $url = $_REQUEST['url'];
            if (!preg_match('/^[http:\/\/]/', $url)) {
                $url = 'http://' . $url;
            }
            $fk_task_type = $_REQUEST['type'];
            //$statut = isset($_REQUEST['statut']) ? $_REQUEST['statut'] : "open"; //closed or opened




            $datedeb = $_REQUEST['datedeb'];
            $datefin = $_REQUEST['datefin'];
            $debdateUS = "";
            $debts = "0";
            $finddateUS = "";
            $fints = "0";
            if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/", $datedeb, $arr)) {
                $debdateUS = $arr[3] . "-" . $arr[2] . "-" . $arr[1] . " " . $arr[4] . ":" . $arr[5];
                $debts = strtotime($arr[3] . "-" . $arr[2] . "-" . $arr[1]);
                $debts += $arr[5] * 60 + $arr[4] * 3600;
            }

            if (preg_match("/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/", $datefin, $arr)) {
                $findateUS = $arr[3] . "-" . $arr[2] . "-" . $arr[1] . " " . $arr[4] . ":" . $arr[5];
                $fints = strtotime($arr[3] . "-" . $arr[2] . "-" . $arr[1]);
                $fints += $arr[5] * 60 + $arr[4] * 3600;
            }

            //2 Cases duration or date end ???
            $duration_effective = abs($fints - $debts);
            $level = 1;
            //Get parent Level
            if ($parentId . "x" != "x") {
                $requete = "SELECT level FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task WHERE rowid = " . $parentId;
                $sql = $db->query($requete);
                $res = $db->fetch_object($sql);
                $level = $res->level + 1;
            }



            $db->begin();

            if ($parentId . 'x' == 'x') {
                $parentId = "NULL";
            }

            $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_projet_task
                       SET fk_task_parent = $parentId,
                           title = '$name',
                           duration_effective = $duration_effective,
                           fk_user_creat = $userid,
                           note = '$shortDescription',
                           progress = $progress,
                           description = '$description',
                           shortDesc = '$shortDescription',
                           color = '$color',
                           url = '$url',
                           fk_task_type = $fk_task_type,
                           level = $level,
			   dateDeb = '$debdateUS'
                     WHERE rowid = " . $taskId;

            $sql = $db->query($requete);
            if ($sql) {
                //ressource et role et dependance
                $dependArr = preg_split('/,/', $_REQUEST['depend']);
                $errBool = false;
                $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors
                              WHERE fk_projet_task = " . $taskId;
//print "del actor ".$requete."\n";

                $db->query($requete);

                if (insertActor($db, $taskId, $userid, $_REQUEST['ressource'])) {
                    $errBool = false;
                } else {
                    $errBool = true;
                }
                $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_depends
                              WHERE fk_task = " . $taskId;
//print "del task ".$requete."\n";
                $db->query($requete);


                foreach ($dependArr as $key => $val) {
                    if (strlen($val) > 2) {
                        $arr = preg_split('/:/', $val);
                        $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_task_depends (fk_task, fk_depends,percent)
                                     VALUES ($taskId, $arr[0],$arr[1])";
                        $sql = $db->query($requete);
                        if (!$sql && !$errBool) {
                            $errBool = true;
                        }
                    }
                }
                $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time
                              WHERE fk_task = " . $taskId;
//print "del tasktime ".$requete."\n";
                $db->query($requete);
                $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_task_time
                                    (fk_task, task_date,task_duration, fk_user )
                             VALUES ($taskId, '" . $debdateUS . "', $duration_effective, $userid )";
                $sqltime = $db->query($requete);

                $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_special WHERE fk_task =" . $taskId;
                $db->query($requete);

                $trancheArr = array();
                $trancheArr = preg_split('/,/', $_REQUEST['TrancheHoraire']);
                foreach ($trancheArr as $key => $val) {
                    $trancheDetArr = preg_split('/:/', $val);
                    $type = $trancheDetArr[0];
                    $fk_user = $trancheDetArr[1];
                    $jour = $trancheDetArr[2];
                    $fk_tranche = $trancheDetArr[3];
                    $qte = $trancheDetArr[4];
                    $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_special
                                      (fk_tranche, fk_user, type, qte, fk_task)
                               VALUES ('" . $fk_tranche . "', '" . $fk_user . "', '" . $type . "', '" . $qte . "', '" . $taskId . "')";
                    $db->query($requete);
                }



                if (!$errBool && $sqltime) {
                    $db->commit();
                    $xml = do_select($db, $project_id);
                    require_once(DOL_DOCUMENT_ROOT . "/synopsisprojet/class/synopsisproject.class.php");
                    $taskObj = new SynopsisProjectTask($db);
                    $taskObj->fetch($taskId);
                    //appel triggers
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface = new Interfaces($db);
                    global $user;
                    $result = $interface->run_triggers('PROJECT_UPDATE_TASK', $taskObj, $user, $langs, $conf);
                    if ($result < 0) {
                        $error++;
                        $errors = $interface->errors;
                    }
                    //Fin appel triggers
                } else {
                    $xml = "<response>Error</response>";
                    $xml .= "<requete>" . $requete . "</requete>";
                    $xml .= "<error>" . $db->lastqueryerror . "</error>";
                    $xml .= "<error>" . $db->lasterror . "</error>";
                    $xml .= "<error>" . print_r($db, true) . "</error>";
                    $db->rollback();
                }
            } else {
                $xml = "<response>Error</response>";
                $db->rollback();
            }
        }
        break;
    case "delete": {
            $requete = "DELETE FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task
                     WHERE rowid = " . $taskId;
            $sql = $db->query($requete);
            if ($sql) {
                $xml = do_select($db, $project_id);
                require_once(DOL_DOCUMENT_ROOT . "/synopsisprojet/class/synopsisproject.class.php");
                $taskObj = new SynopsisProjectTask($db);
                $taskObj->fetch($taskId);
                //appel triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface = new Interfaces($db);
                global $user;
                $result = $interface->run_triggers('PROJECT_DELETE_TASK', $taskObj, $user, $langs, $conf);
                if ($result < 0) {
                    $error++;
                    $errors = $interface->errors;
                }
                //Fin appel triggers
            } else {
                $xml = "<response>Error</response>";
            }
        }
}




header("Content-Type: text/xml");
$xmlStr = '<' . '?xml version="1.0" encoding="ISO-8859-1"?' . '>';
$result = $xmlStr . "<xml>" . $xml . "</xml>";
$result = str_replace(array("<![CDATA[", "]]>", "<br>"), "", $result);
print $result;

function do_select($db, $project_id) {

    $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet
                     WHERE rowid = " . $project_id;
    $sql = $db->query($requete);
    $res = $db->fetch_object($sql);

    $user1 = new User($db, $res->fk_user_resp);
    $user1->fetch($res->fk_user_resp);
    $xml = "<project>\n";
    $xml .= "<task>\n";
    $xml .= "  <pID>-1</pID>\n";
    $xml .= "  <pName><![CDATA[" . utf8_decode($res->title) . "]]></pName>\n";
    $xml .= "  <pStart>" . $res->dateo . "</pStart>\n";
    $xml .= "  <pEnd></pEnd>\n";
    $xml .= "  <pColor>$res->color</pColor>\n";
    $xml .= "  <pLink>" . $res->url . "</pLink>\n";
    $xml .= "  <pMile>0</pMile>\n";
    $xml .= "  <pRes><![CDATA[" . $user1->fullname . "]]></pRes>\n";
    $xml .= "  <pComp>0</pComp>\n"; //TODO
    $xml .= "  <pGroup>1</pGroup>\n";
    $xml .= "  <pParent>0</pParent>\n";
    $xml .= "  <pOpen>1</pOpen>\n";
    $xml .= "  <pDepend/>\n";
    $xml .= "</task>\n";


    $requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task.title,
                           " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid,
                           date_format(" . MAIN_DB_PREFIX . "Synopsis_projet_task_time.task_date,'%Y-%m-%d') as task_date,
                           date_format(DATE_ADD(" . MAIN_DB_PREFIX . "Synopsis_projet_task_time.task_date,INTERVAL " . MAIN_DB_PREFIX . "Synopsis_projet_task_time.task_duration second),'%Y-%m-%d') as task_end,
                           ifnull(" . MAIN_DB_PREFIX . "Synopsis_projet_task.color, '0000FF') as color,
                           ifnull(fk_task_type,3) as type,
                           ifnull(fk_task_parent,0) as fk_task_parent,
                           progress,
                           url,
                           shortDesc
                      FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task
                 LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_time ON " . MAIN_DB_PREFIX . "Synopsis_projet_task_time.fk_task = " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid
                     WHERE fk_projet = " . $project_id . " AND " . MAIN_DB_PREFIX . "Synopsis_projet_task.fk_task_parent is null";
    $sql = $db->query($requete);

    //Open all by default
    $pOpen = 1;
    //$cnt = 0;
    while ($res = $db->fetch_object($sql)) {


        $isMilestone = 0;
        $isGroup = 0;
        if ($res->type == 1) {
            $isMilestone = 1;
        } else if ($res->type == 3) {
            $isGroup = 1;
        }
        $xml .= "<task>\n";
        $xml .= "  <pID>" . $res->rowid . "</pID>\n";
        $xml .= "  <pName><![CDATA[" . utf8_decode($res->title) . "]]></pName>\n";
        $xml .= "  <pStart>" . $res->task_date . "</pStart>\n";
        $xml .= "  <pEnd>" . $res->task_end . "</pEnd>\n";
        $xml .= "  <pColor>" . $res->color . "</pColor>\n";
        $xml .= "  <pLink><![CDATA[" . $res->url . "]]></pLink>\n";
        $xml .= "  <pMile>" . $isMilestone . "</pMile>\n";
        $requete1 = "SELECT *
                           FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors
                          WHERE fk_projet_task =" . $res->rowid;
        $sql1 = $db->query($requete1);
        $dependsArr = array();
        $userStr = "";
        
    if (isset($conf->global->MAIN_MODULE_SYNOPSISHRM)) {
        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Hrm/hrm.class.php");
        $hrm = new Hrm($db);
        $hrm->listTeam();
    }
        while ($res1 = $db->fetch_object($sql1)) {
            if ($res1->type == "user") {
                $user1 = new User($db, $res1->fk_user);
                $user1->fetch($res1->fk_user);

                $userStr .= $res1->role . ": " . $user1->fullname . " (" . $res1->percent . "%)<br>\n";
            } else if (isset($conf->global->MAIN_MODULE_SYNOPSISHRM)) {
                $name = preg_replace('/&[\w;]*$/', "", htmlentities($hrm->teamRessource[$res1->fk_user]['name']));

                $userStr .= $res1->role . ": " . $name . " (" . $res1->percent . "%)<br>\n";
            }
        }
        $xml .= "  <pRes><![CDATA[" . $userStr . "]]></pRes>\n";

        $xml .= "  <pComp>" . $res->progress . "</pComp>\n";
        $xml .= "  <pGroup>" . $isGroup . "</pGroup>\n";
        $xml .= "  <caption><![CDATA[" . $res->shortDesc . "]]></caption>\n";
        $parent = $res->fk_task_parent;
        if ($res->fk_task_parent == 0) {
            $parent = $res->fk_task_parent = -1;
        }
        $xml .= "  <pParent>" . $parent . "</pParent>\n";
        $xml .= "  <pOpen>" . $pOpen . "</pOpen>\n";
        $requete1 = "SELECT *
                           FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_depends,
                                " . MAIN_DB_PREFIX . "Synopsis_projet_task
                          WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid = fk_task AND fk_task =" . $res->rowid;
        $sql1 = $db->query($requete1);
        $dependsArr = array();
        while ($res1 = $db->fetch_object($sql1)) {
            $dependsArr[] = $res1->fk_depends . ":" . $res1->percent;
        }
        $xml .= "  <pDepend>" . join($dependsArr, ",") . "</pDepend>\n";
        $xml .= "</task>\n";
        require_once(DOL_DOCUMENT_ROOT . '/synopsisprojet/class/synopsisproject.class.php');
        $taskObj = new SynopsisProjectTask($db);
        $taskObj->id = $res->rowid;
        $taskObj->getDirectChildsTree();
        foreach ($taskObj->directChildArray as $key) {
            $xml .= recursList($key, $db);
        }
    }
    $xml .= "</project>\n";
    return ($xml);
}

function recursList($taskId, $db) {
    $xml = "";
    $requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_projet_task.title,
                   " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid,
                   date_format(" . MAIN_DB_PREFIX . "Synopsis_projet_task_time.task_date,'%Y-%m-%d') as task_date,
                   date_format(DATE_ADD(" . MAIN_DB_PREFIX . "Synopsis_projet_task_time.task_date,INTERVAL " . MAIN_DB_PREFIX . "Synopsis_projet_task_time.task_duration second),'%Y-%m-%d') as task_end,
                   ifnull(" . MAIN_DB_PREFIX . "Synopsis_projet_task.color, '0000FF') as color,
                   ifnull(fk_task_type,3) as type,
                   ifnull(fk_task_parent,0) as fk_task_parent,
                   progress,
                   url,
                   shortDesc
              FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task
         LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_time ON " . MAIN_DB_PREFIX . "Synopsis_projet_task_time.fk_task = " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid
             WHERE " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid = " . $taskId;
    $sql = $db->query($requete);
    //TODO :> afficher par groupe
//recursList($taskId, $db,$xml)
    //Open all by default
    $pOpen = 1;
    //$cnt = 0;
    while ($res = $db->fetch_object($sql)) {


        $isMilestone = 0;
        $isGroup = 0;
        if ($res->type == 1) {
            $isMilestone = 1;
        } else if ($res->type == 3) {
            $isGroup = 1;
        }
        $xml .= "<task>\n";
        $xml .= "  <pID>" . $res->rowid . "</pID>\n";
        $xml .= "  <pName><![CDATA[" . utf8_decode($res->title) . "]]></pName>\n";
        $xml .= "  <pStart>" . $res->task_date . "</pStart>\n";
        $xml .= "  <pEnd>" . $res->task_end . "</pEnd>\n";
        $xml .= "  <pColor>" . $res->color . "</pColor>\n";
        $xml .= "  <pLink><![CDATA[" . $res->url . "]]></pLink>\n";
        $xml .= "  <pMile>" . $isMilestone . "</pMile>\n";
        $requete1 = "SELECT *
                           FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors
                          WHERE fk_projet_task =" . $res->rowid;
        $sql1 = $db->query($requete1);
        $dependsArr = array();
        $userStr = "";
        while ($res1 = $db->fetch_object($sql1)) {
            $user1 = new User($db, $res1->fk_user);
            $user1->fetch($res1->fk_user);

            $userStr .= $res1->role . ": " . $user1->fullname . "<br>\n";
        }
        $xml .= "  <pRes><![CDATA[" . $userStr . "]]></pRes>\n";

        $xml .= "  <pComp>" . $res->progress . "</pComp>\n";
        $xml .= "  <pGroup>" . $isGroup . "</pGroup>\n";
        $xml .= "  <caption><![CDATA[" . $res->shortDesc . "]]></caption>\n";
        $parent = $res->fk_task_parent;
        if ($res->fk_task_parent == 0) {
            $parent = $res->fk_task_parent = -1;
        }
        $xml .= "  <pParent>" . $parent . "</pParent>\n";
        $xml .= "  <pOpen>" . $pOpen . "</pOpen>\n";
        $requete1 = "SELECT *
                           FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_depends
                          WHERE fk_task =" . $res->rowid;
        $sql1 = $db->query($requete1);
        $dependsArr = array();
        while ($res1 = $db->fetch_object($sql1)) {
            $dependsArr[] = $res1->fk_depends . ":" . $res1->percent;
        }

        $xml .= "  <pDepend>" . join($dependsArr, ",") . "</pDepend>\n";
        $xml .= "</task>\n";
        $taskObj = new SynopsisProjectTask($db);
        $taskObj->id = $res->rowid;
        $taskObj->getDirectChildsTree();
        foreach ($taskObj->directChildArray as $key) {
            $xml .= recursList($key, $db);
        }
    }
    return ($xml);
}

function insertActor($db, $taskId, $userid, $ressources) {
    $ressources = preg_split('/,/', $_REQUEST['ressource']);
    $bool = true;
    foreach ($ressources as $key => $val) {
        if ($bool && strlen($val) > 3) {
            $ressourceDet = preg_split('/:/', $val);
            $type = $ressourceDet[0];
            $id = $ressourceDet[1];
            $percent = $ressourceDet[2];
            $role = $ressourceDet[3];
            if ($role == "actor") {
                $role = "acto";
            }
            $requete = "INSERT INTO " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors
                                        (fk_projet_task,fk_user,role, percent, type)
                                 VALUES ($taskId,$id, '" . $role . "', '" . $percent . "','" . $type . "')";
            $sql = $db->query($requete);
            if (!$sql) {
                $bool = false;
            }
        }
    }
    return($bool);
}

?>