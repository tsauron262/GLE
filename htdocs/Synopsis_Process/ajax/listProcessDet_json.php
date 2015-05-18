<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Created on : 10 mars 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : listContrat_json.php
 * GLE-1.1
 */
require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");

$langs->load("synopsisGene@synopsistools");
$langs->load("process@Synopsis_Process");

//$user_id = $_REQUEST['userId'];

$action = $_REQUEST['action'];

//$user->fetch($user_id);
$user->getrights();
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
    $searchField = $_REQUEST['searchField'];
    $searchString = $_REQUEST['searchString'];
    if ($searchField == 'p.name') {
        $searchField = "CONCAT(name,' ' ,firstname)";
    }
    if ($searchField == "c.date_create") {
        $searchField = "date_format(c.date_create,'%Y-%m-%d')";
        if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/', $searchString, $arr)) {
            $searchString = $arr[3] . '-' . $arr[2] . '-' . $arr[1];
        }
    }
    if ($searchField == "c.date_modify") {
        $searchField = "date_format(c.date_modify,'%Y-%m-%d')";
        if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/', $searchString, $arr)) {
            $searchString = $arr[3] . '-' . $arr[2] . '-' . $arr[1];
        }
    }

    if ($_REQUEST['searchOper'] == 'eq') {
        $oper = '=';
        $wh .= " AND " . $searchField . " " . $oper . " '" . $searchString . "'";
    } else if ($_REQUEST['searchOper'] == 'ne') {
        $oper = '<>';
        $wh .= " AND " . $searchField . " " . $oper . " '" . $searchString . "'";
    } else if ($_REQUEST['searchOper'] == 'lt') {
        $oper = '<';
        $wh .= " AND " . $searchField . " " . $oper . " '" . $searchString . "'";
    } else if ($_REQUEST['searchOper'] == 'gt') {
        $oper = '>';
        $wh .= " AND " . $searchField . " " . $oper . " '" . $searchString . "'";
    } else if ($_REQUEST['searchOper'] == 'le') {
        $oper = '<=';
        $wh .= " AND " . $searchField . " " . $oper . " '" . $searchString . "'";
    } else if ($_REQUEST['searchOper'] == 'ge') {
        $oper = '>=';
        $wh .= " AND " . $searchField . " " . $oper . " '" . $searchString . "'";
    } else if ($_REQUEST['searchOper'] == 'bw') {
        $wh .= ' AND ' . $searchField . " LIKE  '" . $searchString . "%'";
    } else if ($_REQUEST['searchOper'] == 'bn') {
        $wh .= ' AND ' . $searchField . " NOT LIKE  '" . $searchString . "%'";
    } else if ($_REQUEST['searchOper'] == 'in') {
        $wh .= ' AND ' . $searchField . " IN  ('" . $searchString . "')";
    } else if ($_REQUEST['searchOper'] == 'ni') {
        $wh .= ' AND ' . $searchField . " NOT IN  ('" . $searchString . "')";
    } else if ($_REQUEST['searchOper'] == 'ew') {
        $wh .= ' AND ' . $searchField . " LIKE  '%" . $searchString . "'";
    } else if ($_REQUEST['searchOper'] == 'en') {
        $wh .= ' AND ' . $searchField . " NOT LIKE  '%" . $searchString . "'";
    } else if ($_REQUEST['searchOper'] == 'cn') {
        $wh .= ' AND ' . $searchField . " LIKE  '%" . $searchString . "%'";
    } else if ($_REQUEST['searchOper'] == 'nc') {
        $wh .= ' AND ' . $searchField . " NOT LIKE  '%" . $searchString . "%'";
    }
}
//print $wh;

if ($_REQUEST['c_process_refid'] > 0) {
    $searchString = $_REQUEST['c_process_refid'];
    $searchField = 'process_refid';
    $oper = '=';
    $wh .= " AND " . $searchField . " " . $oper . " '" . $searchString . "'";
}

if ($_REQUEST['type'] > 0) {
    $searchString = $_REQUEST['type'];
    $searchField = 'process_refid';
    $oper = '=';
    $wh .= " AND " . $searchField . " " . $oper . " '" . $searchString . "'";
}


switch ($action) {
    default : {

            $sql = "SELECT count(*) as cnt";
            $sql .= " FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet as st";
            $sql.= " WHERE 1=1 AND revision IS NULL ";

            $sql .= "  " . $wh;
//        print $SQL;
            $result = $db->query($sql);
            $row = $db->fetch_array($result, MYSQL_ASSOC);
            $count = $row['cnt'];
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



            $sql = "SELECT c.id,  c.date_create,  c.date_modify";
            $sql .= " FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet as c ";
            $sql.= " WHERE 2 = 2 AND revision IS NULL ";

            $sql .= "  " . $wh . "
                ORDER BY $sidx $sord
                LIMIT $start , $limit";

            $result = $db->query($sql) or die("Couldn t execute query : " . $sql . "." . mysql_error());
            @$responce->page = $page;
            $responce->total = $total_pages;
            $responce->records = $count;
            $i = 0;

            $process = new processDet($db);

            while ($obj = $db->fetch_object($result)) {
                $process->fetch($obj->id);

                $tmpStatut = $process->getLibStatut(4);
                $process->fetch_process();
                require_once(DOL_DOCUMENT_ROOT . $process->process->typeElement->classFile);
                $tmpType = $process->process->typeElement->type;
                $tmpObj = new $tmpType($db);
                $tmpObj->fetch($process->element_refid);
                $hasRev = false;
                if ($process->process->revision_model_refid) {
                    $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Processdet WHERE orig_ref = '" . $process->ref . "'";
                    $sql = $db->query($requete);
                    if ($db->num_rows($sql) > 0)
                        $hasRev = true;
                }


                $responce->rows[$i]['cell'] = array($obj->id,
                    ($hasRev ? '<div class="hasRev">1</div>' : '<div class="hasRev">0</div>'),
                    $process->getNomUrl(1),
                    ($process->process ? $process->process->getNomUrl(1) : ''),
                    $tmpObj->getNomUrl(1) . $process->element_id,
                    $obj->date_create,
                    $obj->date_modify,
                    $tmpStatut,
                );
                $i++;
            }
            echo json_encode($responce);
        }
        break;
}
?>