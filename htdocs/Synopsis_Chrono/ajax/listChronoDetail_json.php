<?php

/*
 *
 * Name : listContrat_json.php
 * GLE-1.1
 */

function searchtext($nom, $pref = ''){
    $searchString = $_REQUEST[$nom] ;
    $searchField=$pref.$nom;
    $oper = 'LIKE';
    return  " AND " . $searchField . " ".$oper." '%".$searchString."%'";    
}


require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Chrono/Chrono.class.php");
require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");

global $langs;
$langs->load("synopsisGene@Synopsis_Tools");
$langs->load("chrono@Synopsis_Chrono");

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
$searchOn = $_REQUEST['_search'];
if ($searchOn == 'true') {
    $oper = "";
//    $searchField = $_REQUEST['searchField'];
//    $searchString = $_REQUEST['searchString'];



    if ($_REQUEST['fk_statut'] > 0) {
        $searchString = $_REQUEST['fk_statut'];
        $searchField = 'fk_statut';
        $oper = '=';
        $wh .= " AND " . $searchField . " " . $oper . " '" . $searchString . "'";
    }


    if ($_REQUEST['ref'] > 0)
        $wh .= searchtext('ref');



    $requetePre = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_key WHERE inDetList = 1 AND model_refid =  " . $id;
    $sqlPre = $db->query($requetePre);
    while ($resPre = $db->fetch_object($sqlPre)) {
        $nom = sanitize_string($resPre->nom);
//        if ($nom == $searchField) {
        if (isset($_REQUEST[sanitize_string($nom)])) {
//            die("cool");
            $searchField = sanitize_string($nom);
            $searchString = $_REQUEST[sanitize_string($nom)];
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_key_type_valeur WHERE id = " . $resPre->type_valeur;

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
            $requetePre = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_key WHERE inDetList = 1 AND model_refid =  " . $id;
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
            while ($resPre = $db->fetch_object($sqlPre)) {
                $nom = sanitize_string($resPre->nom);
                $arrPre[$resPre->id] = $resPre->id;
                $arrKeyName[$resPre->id] = $nom;
                $arrCreateTable[$nom] = 'varchar';
                $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_key_type_valeur WHERE id = " . $resPre->type_valeur;
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
            }
            $requete = "SELECT *
                      FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono_key_value_view
                     WHERE 1=1
                       AND chrono_conf_id = " . $id;
//                       AND key_id IN (".join(",",$arrPre).")";
            if (!$withRev) {
                $requete .= " AND revision is NULL ";
            } else {
                $requete .= " AND revision is NOT NULL ";
                // chrono_refid
                $requete1 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono as c WHERE id <>" . $_REQUEST['chrono_refid'] . " AND orig_ref = (SELECT ref FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono WHERE id = " . $_REQUEST['chrono_refid'] . ")";
                //print "123456789".$requete1;
                $sql1 = $db->query($requete1);
                $arrTmp = array();
                while ($res1 = $db->fetch_object($sql1)) {
                    $arrTmp[] = $res1->id;
                }
                $requete .= " AND chrono_id IN (" . join(",", $arrTmp) . ") ";
            }
//die($requete);
//print $requete;
            $sql = $db->query($requete);
            $iter = 0;
            $arrRef = array();
            $arrValue = array();
            $arrStatut = array();
            while ($res = $db->fetch_object($sql)) {
                $nom = sanitize_string($res->nom);
                $arrRef[$res->chrono_id] = $res->ref;
                $arrStatut[$res->chrono_id] = $res->fk_statut;
                $arrKey[$res->key_id] = $nom;

                //Si from requete ou from var ou from liste, substitue la valeur "id" par la valeur "reelle"
                $val = parseValue($res->chrono_value, $arrHasSubVal[$nom], $arrSourceIsOption[$nom], $arrphpClass[$nom], $arrvalueIsSelected[$nom], $arrvalueIsChecked[$nom]);

                $arrValue[$res->chrono_id][$nom] = array('value' => $val, "id" => $res->id);
                $iter++;
            }

//temp sql table

            $requete = "CREATE TEMPORARY TABLE tempchronovalue (id int(11) NOT NULL, `chrono_id` INT(11) DEFAULT NULL, `ref` VARCHAR(150) DEFAULT NULL, `fk_statut` int(11) DEFAULT NULL,";
            $requeteArr = array();
            foreach ($arrCreateTable as $key => $val) {
                if ($val == 'datetime')
                    $requeteArr[] .= "`" . $key . "` datetime DEFAULT NULL";
                else
                    $requeteArr[] .= "`" . $key . "` VARCHAR(100) DEFAULT NULL";
            }
            $requete .= join(',', $requeteArr);
            $requete .= ")ENGINE=MyISAM DEFAULT CHARSET=utf8";
            $sql = $db->query($requete);
//Insert datas

            $insArr = array();
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
                $insArr2 = array();
                $chrono_ref = $arrRef[$chrono_id];
                $fk_statut = $arrStatut[$chrono_id];
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
                    }
                    else
                        $insArr2[] = "'" . addslashes($chrono_arr_value_by_key_name[$keyname]['value']) . "'";
                }
                $insStr2 = join(',', $insArr2);
                $requete = "INSERT INTO tempchronovalue
                            (id,chrono_id,ref,fk_statut," . $insStr . ")
                     VALUES (" . $i . "," . $chrono_id . ",'" . $chrono_ref . "','" . $fk_statut . "'," . $insStr2 . ")";
//print $requete;
                $sql = $db->query($requete);
                $i++;
            }


//Select datas
            $requete = "SELECT * FROM tempchronovalue WHERE 1=1 ";
            $requete .= $wh;


            $sql = $db->query($requete);
            if ($sql) {
                $count = $db->num_rows($sql);
                $i = 0;

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

                class general {
                    
                }

                $responce = new general();
                $responce->page = $page;
                $responce->total = $total_pages;
                $responce->records = $i;
            }
            $requete .= "      ORDER BY $sidx $sord";
            $requete .= "         LIMIT $start , $limit";

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
                        $requete1 = "SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Chrono WHERE orig_ref = '" . $chrono->ref . "' AND revision > 0";
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

                    foreach ($arrKeyName as $keyid => $keyname) {
                        if ($arrCreateTable[$keyname] == 'datetime') {
                            if ($arrhasTime[$keyname])
                                $arr[] = (strtotime($res->$keyname) > 0 ? date('Y-m-d H:i:s', strtotime($res->$keyname)) : "");
                            else
                                $arr[] = (strtotime($res->$keyname) > 0 ? date('Y-m-d', strtotime($res->$keyname)) : "");
                        } else {
                            $arr[] = htmlspecialchars($res->$keyname);
                        }
                    }
                    $arr[] = $chrono->getLibStatut(6);
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

function parseValue($val, $hasSubValeur = false, $sourceIsOption = false, $phpClass = '', $valueIsSelected = false, $valueIsChecked = false) {
    global $db;
    //Synopsis_Chrono_key_type_valeur
    if ($hasSubValeur) {
//var_dump($phpClass);
        if ($sourceIsOption) {
            require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/process.class.php");
            $tmp = $phpClass;
            $obj = new $tmp($db);
            $obj->fetch($hasSubValeur);
            $obj->getValues();
            $html = "";
            foreach ($obj->valuesArr as $key => $value) {
                if ($valueIsSelected && $val == $key) {
//            var_dump($obj->valuesArr);
                    if ($obj->OptGroup . "x" != "x") {
                        $html .= $obj->valuesGroupArrDisplay[$key]['label'] . " - " . $value;
                    } else {
                        $html = $value;
                    }
                }
            }
            return $html;
        } else {
            //Beta
            if ($phpClass == 'fct') {
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/process.class.php");
                $tmp = $phpClass;
                $obj = new $tmp($db);
                $obj->fetch($hasSubValeur);
                $obj->call_function_chronoModule($chr->model_refid, $chr->id);
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
