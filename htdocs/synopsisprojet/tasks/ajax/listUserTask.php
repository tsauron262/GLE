<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 15 avr. 2011
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : listUserTask.php
 * GLE-1.2
 */
require_once('../../../main.inc.php');

$langs->load('projects');


if(!isset($_REQUEST['action']))
    $_REQUEST['action'] = '';
if(!isset($_REQUEST['userId']))
    $_REQUEST['userId'] = '';
if(!isset($_REQUEST['projId']))
    $_REQUEST['projId'] = '';

$action = $_REQUEST['action'];
$user_id = $_REQUEST['userId'];
$project_id = $_REQUEST['projId'];
$lightMode = $_REQUEST['lightMode'];
$extra = $_REQUEST['extra'];

$page = $_REQUEST['page']; // get the requested page
$limit = $_REQUEST['rows']; // get how many rows we want to have into the grid
$sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
$sord = $_REQUEST['sord']; // get the direction



$taskId = $_REQUEST['taskId'];


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
          FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task as t
     LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors as a ON a.fk_projet_task = t.rowid AND a.type = 'user'

     LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON a.fk_user = u.rowid
         WHERE 1 = 1
           AND t.rowid = " . $taskId;


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


$SQL = "SELECT a.fk_user,
                a.role,
                t.rowid,
               (SELECT SUM(task_duration) FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time as tt WHERE t.rowid = tt.fk_task AND tt.fk_user = a.fk_user) as task_duration,
               (SELECT min(task_date) FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time as tt WHERE t.rowid = tt.fk_task AND tt.fk_user = a.fk_user) as dateDeb,
               (SELECT max(date_add(task_date , interval task_duration second )) FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time as tt WHERE t.rowid = tt.fk_task AND tt.fk_user = a.fk_user) as dateFin,
               (SELECT SUM(task_duration_effective) FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective as te WHERE t.rowid = te.fk_task AND te.fk_user = a.fk_user) as task_duration_effective,
               (SELECT min(task_date_effective) FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective as te WHERE t.rowid = te.fk_task AND te.fk_user = a.fk_user) as dateDebEff,
               (SELECT max(date_add(task_date_effective , interval task_duration_effective second)) FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective as te WHERE t.rowid = te.fk_task AND te.fk_user = a.fk_user) as dateFinPrevEff
          FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task as t
     LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_projet_task_actors as a ON a.fk_projet_task = t.rowid  AND a.type = 'user'
     LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON a.fk_user = u.rowid
         WHERE 1 = 1
           AND t.rowid = " . $taskId;


$SQL .= $wh;
//print $SQL;
$SQL .= "      ORDER BY $sidx $sord";
$SQL .= "         LIMIT $start , $limit";
//print $SQL;
$result = $db->query($SQL) or die("Couldn t execute query." . mysql_error());
$i = 0;
require_once(DOL_DOCUMENT_ROOT . '/synopsisprojet/class/synopsisproject.class.php');
$projet = new SynopsisProject($db);
while ($row = $db->fetch_object($result)) {
    if ($row->fk_user) {
        $responce->rows[$i]['id'] = $row->fk_user;
        $tmpuser = new User($db);
        $tmpuser->id = $row->fk_user;
        $tmpuser->fetch($tmpuser->id);

        $responce->rows[$i]['cell'] = array($row->fk_user,
            ($tmpuser->id > 0 ? traite_str($tmpuser->getNomUrl(1)) : ""),
            utf8_encode($langs->trans($row->role)),
            ($row->dateDeb . "x" != "x" ? date('d/m/Y H:i', strtotime($row->dateDeb)) : ""),
            ($row->dateFin . "x" != "x" ? date('d/m/Y H:i', strtotime($row->dateFin)) : ""),
            $projet->sec2hour(abs($row->task_duration)),
            ($row->dateDebEff . "x" != "x" ? date('d/m/Y H:i', strtotime($row->dateDebEff)) : ""),
            ($row->dateFinPrevEff . "x" != "x" ? date('d/m/Y H:i', strtotime($row->dateFinPrevEff)) : ""),
            $projet->sec2hour(abs($row->task_duration_effective)),
        );
        $i++;
    }
}


$responce->page = $page;
$responce->total = $total_pages;
$responce->records = $count;


echo json_encode($responce);

function traite_str($str) {
    return $str;
}

?>