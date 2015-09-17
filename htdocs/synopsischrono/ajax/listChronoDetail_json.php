<?php

/*
 *
 * Name : listContrat_json.php
 * GLE-1.1
 */

function searchtext($nom, $pref = '') {
    $searchString = $_REQUEST[$nom];
    $searchField = $pref . $nom;
    $oper = 'LIKE';
    return " AND " . $searchField . " " . $oper . " '%" . $searchString . "%'";
}

function searchint($nom, $pref = '') {
    $searchString = $_REQUEST[$nom];
    $searchField = $pref . $nom;
    $oper = '=';
    return " AND " . $searchField . " " . $oper . " '" . $searchString . "'";
}

require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/class/chrono.class.php");
require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");

global $langs;
$langs->load("synopsisGene@synopsistools");
$langs->load("chrono@synopsischrono");

$user_id = $_REQUEST['userId'];

$action = $_REQUEST['action'];
$id = $_REQUEST['id'];

$withRev = false;

if ($_REQUEST['withRev'] > 0)
    $withRev = true;

$user->fetch($user_id);
$user->getrights();
$page = $_REQUEST['page']; // get the requested page
$limit = $_REQUEST['rows']; // get how many rows we want to have into the grid
$sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
$sord = $_REQUEST['sord']; // get the direction

if (!$sidx)
    $sidx = 1; // connect to the database


$wh = "";
$wh1 = "";
$searchOn = ($_REQUEST['_search'] || $_REQUEST['_search2']);
if ($searchOn == 'true') {
    $oper = "";
//    $searchField = $_REQUEST['searchField'];
//    $searchString = $_REQUEST['searchString'];



    if ($_REQUEST['fk_statut'] > 0) {
        $searchStringT = $_REQUEST['fk_statut'];
        $searchFieldT = 'fk_statut';
        $operT = '=';
        $wh .= " AND " . $searchFieldT . " " . $operT . " '" . $searchStringT . "'";
    }

    if ($_REQUEST['fkprojet'] != "") {
        $searchStringT = "(SELECT id FROM " . MAIN_DB_PREFIX . "projet p, " . MAIN_DB_PREFIX . "synopsischrono WHERE projetid = p.rowid AND (p.ref LIKE \"%" . $_REQUEST['fkprojet'] . "%\" OR p.title LIKE \"%" . $_REQUEST['fkprojet'] . "%\"))";
        $searchFieldT = 'id';
        $operT = 'IN';
        $wh1 .= " AND " . $searchFieldT . " " . $operT . " " . $searchStringT . "";
    }
    if ($_REQUEST['fk_projet'] != "") {
        $searchStringT = $_REQUEST['fk_projet'];
        $searchFieldT = 'projetid';
        $operT = '=';
        $wh1 .= " AND " . $searchFieldT . " " . $operT . " " . $searchStringT . "";
    }

    if ($_REQUEST['propal'] != "") {
        $searchStringT = "(SELECT id FROM " . MAIN_DB_PREFIX . "propal p, " . MAIN_DB_PREFIX . "synopsischrono WHERE propalid = p.rowid AND (p.ref LIKE \"%" . $_REQUEST['propal'] . "%\"))";
        $searchFieldT = 'propalid';
        $operT = 'IN';
        $wh1 .= " AND " . $searchFieldT . " " . $operT . " " . $searchStringT . "";
    }
    if ($_REQUEST['fk_propal'] != "") {
        $searchStringT = $_REQUEST['fk_propal'];
        $searchFieldT = 'propalid';
        $operT = '=';
        $wh1 .= " AND " . $searchFieldT . " " . $operT . " " . $searchStringT . "";
    }
    if ($_REQUEST['soc'] != "") {
        $searchStringT = "(SELECT id FROM " . MAIN_DB_PREFIX . "societe p, " . MAIN_DB_PREFIX . "synopsischrono WHERE fk_soc = p.rowid AND (p.nom LIKE \"%" . $_REQUEST['soc'] . "%\"))";
        $searchFieldT = 'id';
        $operT = 'IN';
        $wh1 .= " AND " . $searchFieldT . " " . $operT . " " . $searchStringT . "";
    }


    if ($_REQUEST['ref'] > 0)
        $wh .= searchtext('ref');



    $requetePre = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_key WHERE inDetList = 1 AND model_refid =  " . $id;
    $sqlPre = $db->query($requetePre);
    while ($resPre = $db->fetch_object($sqlPre)) {
        $nom = sanitize_string($resPre->nom);
//        if ($nom == $searchField) {
        if (isset($_REQUEST[sanitize_string($nom)])) {
//            die($_REQUEST[sanitize_string($nom)]);
//            die("cool");
            $searchField = sanitize_string($nom);
            $searchString = $_REQUEST[sanitize_string($nom)];
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_key_type_valeur WHERE id = " . $resPre->type_valeur;

            $sql1 = $db->query($requete);
            $res1 = $db->fetch_object($sql1);
            if ($res1->cssClass == 'datepicker') {
                $searchField = "date_format(" . $nom . ",'%Y-%m-%d')";
                if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/', $searchString, $arr)) {
                    $searchString = $arr[3] . '-' . $arr[2] . '-' . $arr[1];
                }
            }
            if ($res1->cssClass == 'datetimepicker') {
                $searchField = "date_format(" . $nom . ",'%Y-%m-%d %H:%i')";
                if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/', $searchString, $arr)) {
                    $searchString = $arr[3] . '-' . $arr[2] . '-' . $arr[1] . " " . $arr[4] . ":" . $arr[5];
                }
            }
        }
//    }
//    if ($searchField == "c.date_create")
//    {
//        $searchField = "date_format(c.date_create,'%Y-%m-%d')";
//        if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})/',$searchString,$arr))
//        {
//            $searchString = $arr[3].'-'.$arr[2].'-'.$arr[1];
//        }
//    }.
        $searchString = addslashes($searchString);
        if (isset($searchField)) {
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
            } else {
                $oper = 'LIKE';
                $wh .= " AND " . $searchField . " " . $oper . " '%" . $searchString . "%'";
            }
        }
    }
}
//print $wh;
//
//if ($_REQUEST['c_model_refid'] > 0)
//{
//    $searchString = $_REQUEST['c_model_refid'] ;
//    $searchField='c.model_refid';
//    $oper = '=';
//    $wh .=  " AND " . $searchField . " ".$oper." '".$searchString."'";
//
//}
//$wh .= " AND revision is NULL ";

switch ($action) {
    default : {
            $requetePre = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_key WHERE inDetList = 1 AND model_refid =  " . $id;
            $sqlPre = $db->query($requetePre);
            $arrPre = array();
            $arrKeyName = array();
            $arrhasTime = array();
            $arrCreateTable = array();
            $arrHasSubVal = array();
            $arrSourceIsOption = array();
            $arrphpClass = array();
            $arrvalueIsChecked = array();
            $arrvalueIsSelected = array();
            $tabLien = array();
            $tabGlobalVar = array();
            while ($resPre = $db->fetch_object($sqlPre)) {
                $nom = sanitize_string($resPre->nom);
                $arrPre[$resPre->id] = $resPre->id;
                $arrKeyName[$resPre->id] = $nom;
                $arrCreateTable[$nom] = 'varchar';
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_key_type_valeur WHERE id = " . $resPre->type_valeur;
                $sql1 = $db->query($requete);
                $res1 = $db->fetch_object($sql1);
                if ($res1->cssClass == 'datepicker' || $res1->cssClass == 'datetimepicker')
                    $arrCreateTable[$nom] = 'datetime';
                if ($res1->cssClass == 'datetimepicker')
                    $arrhasTime[$nom] = true;
                if ($res1->hasSubValeur > 0)
                    $arrHasSubVal[$nom] = $resPre->type_subvaleur;
                if ($res1->sourceIsOption == 1)
                    $arrSourceIsOption[$nom] = true;
                if ($res1->phpClass . "x" != "x")
                    $arrphpClass[$nom] = $res1->phpClass;
                if ($res1->valueIsSelected == 1)
                    $arrvalueIsSelected[$nom] = true;
                if ($res1->valueIsChecked == 1)
                    $arrvalueIsChecked[$nom] = true;
                if ($resPre->type_valeur == 7) {
                    $tabGlobalVar[] = array("nom" => $resPre->nom, "sub_valeur" => $resPre->type_subvaleur, "extraCss" => $resPre->extraCss);
                }
                if ($resPre->type_valeur == 10) {
                    $tabLien[] = array("nom" => $resPre->nom, "sub_valeur" => $resPre->type_subvaleur, "extraCss" => $resPre->extraCss);
                }
            }
            $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "synopsischrono_key_value_view
                     WHERE 1=1
                       AND chrono_conf_id = " . $id;
//                       AND key_id IN (".join(",",$arrPre).")";
            $requete1 = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono as c WHERE model_refid = " . $id . " ";
            if ($_REQUEST['fk_soc'] > 0)
                $requete1 .= searchint('fk_soc');
            if (!$withRev) {
                $requete .= " AND revision is NULL ";
                $requete1 .= " AND revision is NULL ";
            } else {
                $requete .= " AND revision is NOT NULL ";
                $requete1 .= " AND id <>" . $_REQUEST['chrono_refid'] . " AND orig_ref = (SELECT ref FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE id = " . $_REQUEST['chrono_refid'] . ")";
                // chrono_refid
                //print "123456789".$requete1;
            }

            $requete1 .= $wh1;




            $sql1 = $db->query($requete1); //Juste pour le nb de ligne
            $count = $db->num_rows($sql1);



            $start = $limit * $page - $limit; // do not put $limit*($page - 1)
            if ($start < 0)
                $start = 0;


//            $requete1 .= " LIMIT 0,100";
//            $requete1 .= "      ORDER BY $sidx $sord";
            if ($sidx == "chrono_id" && !$searchField) {
                $requete1 .= "      ORDER BY id $sord";
                $requete1 .= "         LIMIT $start , $limit";
            }
            $sql1 = $db->query($requete1);

            if ($count > 0) {
                $total_pages = ceil($count / $limit);
            } else {
                $total_pages = 0;
            }
            if ($page > $total_pages)
                $page = $total_pages;

            $arrTmp = array();
            while ($res1 = $db->fetch_object($sql1)) {
                $arrTmp[] = $res1->id;
            }
            $iter = 0;
            $arrRef = array();
            $arrValue = array();
            $arrStatut = array();
            if (isset($arrTmp[0])) {
                $requete .= " AND chrono_id IN (" . join(",", $arrTmp) . ") ";
//            $requete .="LIMIT 0, 1000";
//die($requete);
//print $requete;
                $sql = $db->query($requete);
                while ($res = $db->fetch_object($sql)) {
                    $nom = sanitize_string($res->nom);
                    $arrRef[$res->chrono_id] = $res->ref;
                    $arrStatut[$res->chrono_id] = $res->fk_statut;
                    $arrKey[$res->key_id] = $nom;
                    $_REQUEST['chrono_id'] = $res->chrono_id;
                    //Si from requete ou from var ou from liste, substitue la valeur "id" par la valeur "reelle"
                    $val = parseValue($res->chrono_value, $res->extraCss, $arrHasSubVal[$nom], $arrSourceIsOption[$nom], $arrphpClass[$nom], $arrvalueIsSelected[$nom], $arrvalueIsChecked[$nom]);
                    $arrValue[$res->chrono_id][$nom] = array('value' => $val, "id" => $res->id);
                    $iter++;
                    if (!isset($tabGlobalVarTraiter[$res->chrono_id])) {
                        foreach ($tabGlobalVar as $lien) {
                            $lien['nom'] = str_replace(" ", "_", $lien['nom']);
                            $lien['nom'] = str_replace("/", "_", $lien['nom']);
                            $val = parseValue($res->chrono_id, $lien['extraCss'], $lien['sub_valeur'], 0, "globalvar");

                            $arrValue[$res->chrono_id][$lien['nom']] = array('value' => $val, "id" => $res->id);
                            $iter++;
                            $tabGlobalVarTraiter[$res->chrono_id] = true;
                        }
                    }
                    if (!isset($tabLienTraiter[$res->chrono_id])) {
                        foreach ($tabLien as $lien) {
                            $lien['nom'] = str_replace(" ", "_", $lien['nom']);
                            $val = parseValue("", $lien['extraCss'], $lien['sub_valeur'], 1, "Lien", 1, 0);

                            $arrValue[$res->chrono_id][$lien['nom']] = array('value' => $val, "id" => $res->id);
                            $iter++;
                            $tabLienTraiter[$res->chrono_id] = true;
                        }
                    }
//
//            }
//            foreach($tabLien as $lien){
//                $nom = sanitize_string($res->nom);
//                $arrRef[$res->chrono_id] = $res->ref;
//                $arrStatut[$res->chrono_id] = $res->fk_statut;
//                $arrKey[$res->key_id] = $nom;
//
//                //Si from requete ou from var ou from liste, substitue la valeur "id" par la valeur "reelle"
//                $val = parseValue($res->chrono_value, 1, 1, "Lien", 1, 0);
//
//                $arrValue[$res->chrono_id][$nom] = array('value' => $val, "id" => $res->id);
//                $iter++;
                }
            }

//temp sql table

            $requete = "CREATE TEMPORARY TABLE tempchronovalue (id int(11) NOT NULL, `chrono_id` INT(11) DEFAULT NULL, `ref` VARCHAR(150) DEFAULT NULL, `fk_statut` int(11) DEFAULT NULL";
            $requeteArr = array();
            foreach ($arrCreateTable as $key => $val) {
                if ($val == 'datetime')
                    $requeteArr[] .= "`" . $key . "` datetime DEFAULT NULL";
                else
                    $requeteArr[] .= "`" . $key . "` VARCHAR(1000) DEFAULT NULL";
            }
            if (count($requeteArr) > 0)
                $requete .= "," . join(',', $requeteArr);
            $requete .= ")ENGINE=MyISAM DEFAULT CHARSET=utf8";
            $sql = $db->query($requete);
//Insert datas

            $insArr = array("id", "chrono_id,ref", "fk_statut");
            $insStr = "";
            $insStr2 = "";
            foreach ($arrKeyName as $key => $val) {
                $insArr[] = $val;
            }
            $insStr = join(',', $insArr);

//Pour chaque chrono, avec ça valeur
//1)

            $i = 0;

            foreach ($arrValue as $chrono_id => $chrono_arr_value_by_key_name) {
                $chrono_ref = $arrRef[$chrono_id];
                $fk_statut = $arrStatut[$chrono_id];
                $insArr2 = array($i, $chrono_id, "'" . addslashes($chrono_ref) . "'", $fk_statut);
                foreach ($arrKeyName as $keyid => $keyname) {
                    if ($arrCreateTable[$keyname] == 'datetime') {
                        $date = $chrono_arr_value_by_key_name[$keyname]['value'];
                        $dateUS = false;
                        if (preg_match('/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})[\W]?([0-9]{0,2})[\W]{0,1}([0-9]{0,2})[\W]{0,1}([0-9]{0,2})/', $date, $arrMatch)) {
                            $year = $arrMatch[3];
                            $month = $arrMatch[2];
                            $day = $arrMatch[1];
                            $hour = ($arrMatch[4] > 0 ? $arrMatch[4] : "00");
                            $min = ($arrMatch[5] > 0 ? $arrMatch[5] : "00");
                            $seconds = ($arrMatch[6] > 0 ? $arrMatch[6] : "00");
                            $dateUS = $year . "-" . $month . "-" . $day . " " . $hour . ":" . $min . ":" . $seconds;
                        } else if (preg_match('/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{2})[\W]?([0-9]{0,2})[\W]{0,1}([0-9]{0,2})[\W]{0,1}([0-9]{0,2})/', $date, $arrMatch)) {
                            $year = "20" . $arrMatch[3];
                            $month = $arrMatch[2];
                            $day = $arrMatch[1];
                            $hour = ($arrMatch[4] > 0 ? $arrMatch[4] : "00");
                            $min = ($arrMatch[5] > 0 ? $arrMatch[5] : "00");
                            $seconds = ($arrMatch[6] > 0 ? $arrMatch[6] : "00");
                            $dateUS = $year . "-" . $month . "-" . $day . " " . $hour . ":" . $min . ":" . $seconds;
                        } else {
                            $dateUS = $date;
                        }
                        $insArr2[] = "'" . addslashes($dateUS) . "'";
                    } else
                        $insArr2[] = "'" . addslashes($chrono_arr_value_by_key_name[$keyname]['value']) . "'";
                }
                $insStr2 = join(',', $insArr2);
                $requete = "INSERT INTO tempchronovalue
                            (" . $insStr . ")
                     VALUES (" . $insStr2 . ")";
//print $requete;
                $sql = $db->query($requete);
                $i++;
            }


//Select datas
            $requete = "SELECT * FROM tempchronovalue WHERE 1=1 ";
            $requete .= $wh;


//            $sql = $db->query($requete);
//            if ($sql) {
//                $i = 0;

                class general {
                    
                }

                $responce = new general();
                $responce->page = $page;
                $responce->total = $total_pages;
//            }
            $requete .= "      ORDER BY $sidx $sord";
            if ($sidx != "chrono_id" || $searchField) {
                $responce->records = $i;
                $requete .= "         LIMIT $start , $limit";
            } else
                $responce->records = $count;

            $sql = $db->query($requete);
            if ($sql) {
                $i = 0;
                while ($res = $db->fetch_object($sql)) {
                    $arr = array();
                    $arr[] = $res->chrono_id;
                    $chrono = new Chrono($db);
                    $chrono->fetch($res->chrono_id);

                    $arr[] = $chrono->getNomUrl(1);

                    //hasRev => 1 si oui, rien sinon
                    if (!$withRev) {
                        $requete1 = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE orig_ref = '" . addslashes($chrono->ref) . "' AND revision > 0";
//                        die($requete1);
                        $sql1 = $db->query($requete1);
                        $hasRev = false;
                        if ($db->fetch_object($sql1))
                            $hasRev = true;
//            if ($res1->cnt > 0) $hasRev = true;
                        $arr[] = ($hasRev ? '<div class="hasRev">1</div>' : '<div class="hasRev">0</div>');
                    } else {
                        $arr[] = ('<div class="hasRev">0</div>');
                    }

                    if ($chrono->model->hasDescription && $chrono->model->descInList)
                        $arr[] = ($chrono->description ? $chrono->description : '');

                    foreach ($arrKeyName as $keyid => $keyname) {
                        if ($arrCreateTable[$keyname] == 'datetime') {
                            if ($arrhasTime[$keyname])
                                $arr[] = (strtotime($res->$keyname) > 0 ? date('Y-m-d H:i:s', strtotime($res->$keyname)) : "");
                            else
                                $arr[] = (strtotime($res->$keyname) > 0 ? date('Y-m-d', strtotime($res->$keyname)) : "");
                        } else {
                            $arr[] = $res->$keyname;
                        }
                    }
                    if ($chrono->model->hasSociete) {
                        $html = "";
                        if ($chrono->socid > 0) {
                            require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");
                            $obj = new Societe($db);
                            $obj->fetch($chrono->socid);
                            $html = $obj->getNomUrl(1, '', 20);
                        }
                        $arr[] = $html;
                    }
                    if ($chrono->model->hasPropal && $chrono->model->propInList) {
                        $html = "";
                        if ($chrono->propalid > 0) {
                            require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
                            $obj = new Propal($db);
                            $obj->fetch($chrono->propalid);
                            $html = $obj->getNomUrl(1, '');
                        }
                        $arr[] = $html;
                    }
                    if ($chrono->model->hasProjet) {
                        $html = "";
                        if ($chrono->projetid > 0) {
                            require_once(DOL_DOCUMENT_ROOT . "/projet/class/project.class.php");
                            $obj = new Project($db);
                            $obj->fetch($chrono->projetid);
                            $html = $obj->getNomUrl(1, '', 30);
                        }
                        $arr[] = $html;
                    }
                    if ($chrono->model->hasStatut)
                        $arr[] = $chrono->getLibStatut(6);
                    if ($chrono->model->hasFile)
                        $arr[] = count_files($conf->synopsischrono->dir_output . "/" . $chrono->id);

                    $responce->rows[$i]['cell'] = $arr;
                    $i++;
                }
            } else {
                var_dump($db);
            }

            echo json_encode($responce);
        }
        break;
}

function parseValue($val, $extraCss, $hasSubValeur = false, $sourceIsOption = false, $phpClass = '', $valueIsSelected = false, $valueIsChecked = false) {
    global $db;
    $val = stripslashes($val);
    //synopsischrono_key_type_valeur
    if ($hasSubValeur) {
//var_dump($phpClass);
        if ($sourceIsOption) {
            require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
            $tmp = $phpClass;
            $obj = new $tmp($db);
            $obj->cssClassM = $extraCss;
            $obj->idChrono = $val;
            $obj->fetch($hasSubValeur, $extraCss);
            $obj->getValue($val);
            if (isset($obj->tabVal[0])) {
                $val = $obj->tabVal[0];
                $valueIsSelected = true;
//                die("ok");
            }
            $html = "";
            foreach ($obj->valuesArr as $key => $value) {
                if ($valueIsSelected && $val == $key) {
//            var_dump($obj->valuesArr);
                    if ($obj->OptGroup . "x" != "x") {
                        $html .= $obj->valuesGroupArrDisplay[$key]['label'] . " - " . $value;
                    } else {
                        $html .= $value;
                    }
                }
            }
            return $html;
        } else {
            //Beta
            if ($phpClass == 'globalvar') {
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
                $tmp = $phpClass;
                $obj = new $tmp($db);
            $obj->cssClassM = $extraCss;
            $obj->idChrono = $val;
                $obj->fetch($hasSubValeur);
                return $obj->getValue($val);
            } elseif ($phpClass == 'fct') {
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
                $tmp = $phpClass;
                $obj = new $tmp($db);
            $obj->cssClassM = $extraCss;
            $obj->idChrono = $val;
                $obj->fetch($hasSubValeur);
                echo $obj->call_function_chronoModule($chr->model_refid, $chr->id);
            } else {
                //Construct Form
                $html = "";
                if ($valueIsChecked && $val == 1) {
                    $html .= "OUI";
                } else if ($valueIsChecked && $val != 1) {
                    $html .= "NON";
                } else {
                    $html .= $val;
                }
                return($html);
            }
        }
    } else {
        return($val);
    }
}

function count_files($dir) {
    $num = 0;
    if (is_dir($dir)) {
        $dir_handle = opendir($dir);
        while ($entry = readdir($dir_handle))
            if (is_file($dir . '/' . $entry))
                $num++;

        closedir($dir_handle);
    }
    return $num;
}

?>
