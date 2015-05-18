<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
require_once('../../../main.inc.php');

if(!isset($_REQUEST['action']))
    $_REQUEST['action'] = '';
if(!isset($_REQUEST['userId']))
    $_REQUEST['userId'] = '';

$action = $_REQUEST['action'];
$user_id = $_REQUEST['userId'];
$project_id = $_REQUEST['projId'];
$lightMode = $_REQUEST['lightMode'];
$extra = $_REQUEST['extra'];

$page = $_REQUEST['page']; // get the requested page
$limit = $_REQUEST['rows']; // get how many rows we want to have into the grid
$sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
$sord = $_REQUEST['sord']; // get the direction





if (!$sidx)
    $sidx = 1; // connect to the database


$wh = "";
$searchOn = $_REQUEST['_search'];
if ($searchOn == 'true') {
    $oper = "";
    if ($_REQUEST['searchOper'] == 'eq') {
        $oper = '=';
        $wh .= " AND " . $_REQUEST['searchField'] . " " . $oper . " '" . $_REQUEST['searchString'] . "'";
    } else if ($_REQUEST['searchOper'] == 'ne') {
        $oper = '<>';
        $wh .= " AND " . $_REQUEST['searchField'] . " " . $oper . " '" . $_REQUEST['searchString'] . "'";
    } else if ($_REQUEST['searchOper'] == 'lt') {
        $oper = '<';
        $wh .= " AND " . $_REQUEST['searchField'] . " " . $oper . " '" . $_REQUEST['searchString'] . "'";
    } else if ($_REQUEST['searchOper'] == 'gt') {
        $oper = '>';
        $wh .= " AND " . $_REQUEST['searchField'] . " " . $oper . " '" . $_REQUEST['searchString'] . "'";
    } else if ($_REQUEST['searchOper'] == 'le') {
        $oper = '<=';
        $wh .= " AND " . $_REQUEST['searchField'] . " " . $oper . " '" . $_REQUEST['searchString'] . "'";
    } else if ($_REQUEST['searchOper'] == 'ge') {
        $oper = '>=';
        $wh .= " AND " . $_REQUEST['searchField'] . " " . $oper . " '" . $_REQUEST['searchString'] . "'";
    } else if ($_REQUEST['searchOper'] == 'bw') {
        $wh .= ' AND ' . $_REQUEST['searchField'] . " LIKE  '" . $_REQUEST['searchString'] . "%'";
    } else if ($_REQUEST['searchOper'] == 'bn') {
        $wh .= ' AND ' . $_REQUEST['searchField'] . " NOT LIKE  '" . $_REQUEST['searchString'] . "%'";
    } else if ($_REQUEST['searchOper'] == 'in') {
        $wh .= ' AND ' . $_REQUEST['searchField'] . " IN  ('" . $_REQUEST['searchString'] . "')";
    } else if ($_REQUEST['searchOper'] == 'ni') {
        $wh .= ' AND ' . $_REQUEST['searchField'] . " NOT IN  ('" . $_REQUEST['searchString'] . "')";
    } else if ($_REQUEST['searchOper'] == 'ew') {
        $wh .= ' AND ' . $_REQUEST['searchField'] . " LIKE  '%" . $_REQUEST['searchString'] . "'";
    } else if ($_REQUEST['searchOper'] == 'en') {
        $wh .= ' AND ' . $_REQUEST['searchField'] . " NOT LIKE  '%" . $_REQUEST['searchString'] . "'";
    } else if ($_REQUEST['searchOper'] == 'cn') {
        $wh .= ' AND ' . $_REQUEST['searchField'] . " LIKE  '%" . $_REQUEST['searchString'] . "%'";
    } else if ($_REQUEST['searchOper'] == 'nc') {
        $wh .= ' AND ' . $_REQUEST['searchField'] . " NOT LIKE  '%" . $_REQUEST['searchString'] . "%'";
    }
}


$SQL = "SELECT count(*) as count
          FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task
     LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_time ON " . MAIN_DB_PREFIX . "Synopsis_projet_task_time.fk_task = " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid
     LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors ON " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_projet_task = " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid
         WHERE 1 = 1";
if ("x" . $lightMode != "x") {
    $SQL = "SELECT count(DISTINCT " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid) as count
              FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task
         LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_time ON " . MAIN_DB_PREFIX . "Synopsis_projet_task_time.fk_task = " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid
         LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors ON " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_projet_task = " . MAIN_DB_PREFIX . "Synopsis_projet_task.rowid AND " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.type = 'user'

             WHERE 1 = 1";
}

if ("x" . $project_id != "x") {
    $SQL .= ' AND fk_projet =  ' . $project_id . ' ';
}

if ('x' . $user_id != "x") {
    $SQL .= " AND " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors.fk_user = " . $user_id;
}



$SQL .= $wh;

$result = $db->query($SQL . " " . $wh);
$row = $db->fetch_array($result, MYSQL_ASSOC);
$count = $row['count'];
if ($count > 0) {
    $total_pages = ceil($count / $limit);
} else {
    $total_pages = 0;
}
if ($page > $total_pages)
    $page = $total_pages;
$start = $limit * $page - $limit; // do not put $limit*($page - 1)
if ($start < 0)
    $start = 0;

//Si seulement mes tâches ou si toutes les taches
//Si admin du projet ou d'un groupe => affiche les tâches filles
// get role, datedebut, fin, temps prevu, temps effectif, avancement, fille enfant et dependance


$SQL = "SELECT t.rowid,
               t.title,
               ifnull(date_format(t.dateDeb,'%d/%m/%Y'),(SELECT date_format(MIN(task_date),'%d/%m/%Y') FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time as tt WHERE tt.fk_task = t.rowid )) as task_date,
               a.fk_user,
               a.role,
               (SELECT SUM(task_duration) FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time as tt WHERE t.rowid = tt.fk_task) as task_duration,
               (SELECT SUM(task_duration_effective) FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective as te WHERE t.rowid = te.fk_task) as task_duration_effective
          FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task as t
     LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors as a ON a.fk_projet_task = t.rowid
         WHERE 1 = 1";
if ("x" . $lightMode != "x") {
    $SQL = "SELECT DISTINCT t.rowid,
               t.title,
               t.progress,
               a.fk_user,
               ifnull(date_format(t.dateDeb,'%d/%m/%Y'),(SELECT date_format(MIN(task_date),'%d/%m/%Y') FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time as tt WHERE tt.fk_task = t.rowid )) as task_date,
               (SELECT SUM(task_duration) FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time as tt WHERE t.rowid = tt.fk_task) as task_duration,
               (SELECT SUM(task_duration_effective) FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective as te WHERE t.rowid = te.fk_task) as task_duration_effective
          FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task as t
     LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors as a ON a.fk_projet_task = t.rowid AND a.type = 'user'

         WHERE 1 = 1";
}

if ("x" . $project_id != "x") {
    $SQL .= ' AND fk_projet =  ' . $project_id . ' ';
}

if ('x' . $user_id != "x") {
    $SQL .= " AND a.fk_user = " . $user_id;
}
$SQL .= $wh;
//print $SQL;
$SQL .= "      GROUP BY t.rowid";
$SQL .= "      ORDER BY $sidx $sord";
$SQL .= "         LIMIT $start , $limit";
//print $SQL;
$result = $db->query($SQL) or die("Couldn t execute query." . mysql_error());
$i = 0;
require_once(DOL_DOCUMENT_ROOT . '/synopsisprojet/class/synopsisproject.class.php');

while ($row = $db->fetch_array($result, MYSQL_ASSOC)) {
    if ($lightMode . 'x' != "x") {
        $responce->rows[$i]['id'] = $row['rowid'];
//        $tmpuser = new User($db);
//        $tmpuser->fetch($row['fk_user']);
        $task = new SynopsisProjectTask($db);
        $task->fetch($row['rowid']);
        $responce->rows[$i]['cell'] = array($row['rowid'],
            traiteStr("<a href='task.php?id=" . $row['rowid'] . "'>" . $row['title'] . "</a>"),
            $row['task_date'],
            $row['progress'] . "%",
            sec2hour(abs($row['task_duration'])),
            sec2hour(abs($row['task_duration_effective'])),
            traiteStr($task->getLibStatut(5))
        );
        $i++;
    } else if ($extra == "showResp") {
        $responce->rows[$i]['id'] = $row['rowid'];
        $tmpuser = new User($db);
        $tmpuser->fetch($row['fk_user']);
        $responce->rows[$i]['cell'] = array($row['rowid'], traiteStr("<a href='task.php?id=" . $row['rowid'] . "'>" . $row['title'] . "</a>"), traiteStr($row['role']), ($tmpuser->id > 0 ? $tmpuser->getNomUrl(1) : ""), $row['task_date'], sec2hour(abs($row['task_duration'])), sec2hour(abs($row['task_duration_effective'])));
        $i++;
    } else if ('x' . $project_id != "x") {
        $responce->rows[$i]['id'] = $row['rowid'];
        $tmpuser = new User($db);
        $tmpuser->fetch($row['fk_user']);
        $responce->rows[$i]['cell'] = array($row['rowid'], traiteStr("<a href='task.php?id=" . $row['rowid'] . "'>" . $row['title'] . "</a>"), traiteStr($row['role']), $row['task_date'], sec2hour(abs($row['task_duration'])), sec2hour(abs($row['task_duration_effective'])));
        $i++;
    } else {
        $responce->rows[$i]['id'] = $row['rowid'];
        $tmpuser = new User($db);
        $tmpuser->fetch($row['fk_user']);
        $responce->rows[$i]['cell'] = array($row['rowid'], traiteStr("<a href='task.php?id=" . $row['rowid'] . "'>" . $row['title'] . "</a>"), $row['task_date'], sec2hour(abs($row['task_duration'])), sec2hour(abs($row['task_duration_effective'])), traiteStr(($tmpuser->id > 0 ? $tmpuser->getNomUrl(1) : "")));
        $i++;
    }
}



$responce->page = $page;
$responce->total = $total_pages;
$responce->records = $count;


echo json_encode($responce);

function sec2time($sec) {
    $returnstring = " ";
    $days = intval($sec / 86400);
    $hours = intval(($sec / 3600) - ($days * 24));
    $minutes = intval(($sec - (($days * 86400) + ($hours * 3600))) / 60);
    $seconds = $sec - ( ($days * 86400) + ($hours * 3600) + ($minutes * 60));

    $returnstring .= ($days) ? (($days == 1) ? "1 j" : $days . "j") : "";
    $returnstring .= ($days && $hours && !$minutes && !$seconds) ? "" : "";
    $returnstring .= ($hours) ? ( ($hours == 1) ? " 1h" : " " . $hours . "h") : "";
    $returnstring .= (($days || $hours) && ($minutes && !$seconds)) ? "  " : " ";
    $returnstring .= ($minutes) ? ( ($minutes == 1) ? " 1 min" : " " . $minutes . "min") : "";
    //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
    //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
    return ($returnstring);
}

function sec2hour($sec) {
    $days = false;
    $returnstring = " ";
    $hours = intval(($sec / 3600));
    $minutes = intval(($sec - ( ($hours * 3600))) / 60);
    $seconds = $sec - ( ($hours * 3600) + ($minutes * 60));

    $returnstring .= ($days) ? (($days == 1) ? "1 j" : $days . "j") : "";
    $returnstring .= ($days && $hours && !$minutes && !$seconds) ? "" : "";
    $returnstring .= ($hours) ? ( ($hours == 1) ? " 1h" : " " . $hours . "h") : "";
    $returnstring .= (($days || $hours) && ($minutes && !$seconds)) ? "  " : " ";
    $returnstring .= ($minutes) ? ( ($minutes == 1) ? " 1 min" : " " . $minutes . "min") : "";
    //$returnstring .= (($days || $hours || $minutes) && $seconds)?" et ":" ";
    //$returnstring .= ($seconds)?( ($seconds == 1)?"1 second":"$seconds seconds"):"";
    return ($returnstring);
}

function traiteStr($str) {
    return $str;
}

?>