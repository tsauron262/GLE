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

if (isset($_REQUEST['userId']))
    $user->fetch($_REQUEST['userId']);
else
    global $user;

$user->getrights();
$page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : 1); // get the requested page
$limit = (isset($_REQUEST['rows']) ? $_REQUEST['rows'] : 23); // get how many rows we want to have into the grid
$sidx = traiteCarac($_REQUEST['sidx']); // get index row - i.e. user click to sort
$sord = $_REQUEST['sord']; // get the direction

if (!$sidx)
    $sidx = 1; // connect to the database

$start = $limit * $page - $limit; // do not put $limit*($page - 1)
if ($start < 0)
    $start = 0;

//die($start);

$wh = "";
$wh1 = "";






if ($_REQUEST['FiltreCentre'] != "") {
    if ($_REQUEST['FiltreCentre'] == "Tous") {

        $centre = str_replace(" ", "','", $user->array_options['options_apple_centre']);
        $wh1 .= " AND CentreVal IN ('" . $centre . "')";
    } else
        $wh1 .= " AND CentreVal LIKE '" . $_REQUEST['FiltreCentre'] . "'";
}

//die($wh1."ll".$_REQUEST['FiltreCentre']);







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
        $searchFieldT = 'chr.id';
        $operT = 'IN';
        $wh1 .= " AND " . $searchFieldT . " " . $operT . " " . $searchStringT . "";
    }
    if ($_REQUEST['fk_projet'] != "") {
        $searchStringT = $_REQUEST['fk_projet'];
        $searchFieldT = 'projetid';
        $operT = '=';
        $wh1 .= " AND " . $searchFieldT . " " . $operT . " " . $searchStringT . "";
    }


    if ($_REQUEST['fk_contrat'] != "") {
        $wh1 .= " AND Contrat = " . $_REQUEST['fk_contrat'];
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
    if ($_REQUEST['fk_societe'] != "") {
        $searchStringT = "(SELECT id FROM " . MAIN_DB_PREFIX . "societe p, " . MAIN_DB_PREFIX . "synopsischrono WHERE fk_societe = p.rowid AND (p.rowid = " . $_REQUEST['fk_societe'] . "))";
        $searchFieldT = 'chr.id';
        $operT = 'IN';
        $wh1 .= " AND " . $searchFieldT . " " . $operT . " " . $searchStringT . "";
    }
    if ($_REQUEST['soc'] != "") {
        $searchStringT = "(SELECT id FROM " . MAIN_DB_PREFIX . "societe p, " . MAIN_DB_PREFIX . "synopsischrono WHERE fk_societe = p.rowid AND (p.nom LIKE \"%" . $_REQUEST['soc'] . "%\"))";
        $searchFieldT = 'chr.id';
        $operT = 'IN';
        $wh1 .= " AND " . $searchFieldT . " " . $operT . " " . $searchStringT . "";
    }


    if (isset($_REQUEST['ref']))
        $wh .= searchtext('ref');

//die($wh.$_REQUEST['ref']);

    $requetePre = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_key WHERE inDetList > 0 AND model_refid =  " . $id . " ORDER BY inDetList";
    $sqlPre = $db->query($requetePre);
    while ($resPre = $db->fetch_object($sqlPre)) {
        $searchField = "";
        $nom = traiteCarac(sanitize_string($resPre->nom));
//        if ($nom == $searchField) {
        if (isset($_REQUEST[sanitize_string($nom)]) && $_REQUEST[sanitize_string($nom)] != "") {
//            die($_REQUEST[sanitize_string($nom)]);
//            die("cool");
            $searchField = traiteCarac(sanitize_string($nom));
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
                $searchField = "date_format(STR_TO_DATE(`" . $nom . "`, '%d/%m/%Y %H:%i'),'%d/%m/%Y') ";
                if (preg_match('/([0-9]{2})[\W]([0-9]{2})[\W]([0-9]{4})[\W]([0-9]{2})[\W]([0-9]{2})/', $searchString, $arr)) {
                    $searchString = $arr[3] . '-' . $arr[2] . '-' . $arr[1];
//                    $searchString = $arr[3] . '-' . $arr[2] . '-' . $arr[1] . " " . $arr[4] . ":" . $arr[5];
                }
            }
            if ($resPre->type_valeur == 6) {
                $result = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "Synopsis_Process_form_requete WHERE id = " . $resPre->type_subvaleur);
                $ligne = $db->fetch_object($result);
                $champs = unserialize($ligne->showFields);
                $champs2 = array();
                foreach ($champs as $champ) {
                    $champs2[] = $champ . " LIKE '%" . $searchString . "%' ";
                }
                $result2 = $db->query(str_replace("[[indexField]]", "(" . implode(" || ", $champs2) . ")", $ligne->requeteValue));
                if ($db->num_rows($result2 > 0)) {
                    $searchString = array();
                    while ($ligne2 = $db->fetch_object($result2)) {
                        $champIdNom = $ligne->indexField;
                        $searchString[] = $ligne2->$champIdNom;
                    }
                    $_REQUEST['searchOper'] = "in";
                }
            }
        }


        if ($sidx == $nom) {
            $requete = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono_key_type_valeur WHERE id = " . $resPre->type_valeur;

            $sql1 = $db->query($requete);
            $res1 = $db->fetch_object($sql1);
            if ($res1->cssClass == 'datetimepicker' || $res1->cssClass == 'datepicker')
                $sidx = "STR_TO_DATE(`" . $nom . "`, '%d/%m/%Y %H:%i')";
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
        if (!is_array($searchString))
            $searchString = addslashes($searchString);
        if (isset($searchField) && $searchField != "") {
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
            } else if ($_REQUEST['searchOper'] == 'in' && is_array($searchString)) {
                $wh .= ' AND ' . $searchField . " IN  ('" . implode("','", $searchString) . "')";
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



$requetePre = "SELECT *, k.id as key_id, k.nom as key_name FROM " . MAIN_DB_PREFIX . "synopsischrono_key k LEFT JOIN " . MAIN_DB_PREFIX . "synopsischrono_key_type_valeur tv ON tv.id = type_valeur  WHERE inDetList > 0 AND model_refid =  " . $id . " ORDER BY inDetList";

$sqlPre = $db->query($requetePre);
$arrPre = $arrKeyName = $arrKeyType = array();
while ($resPre = $db->fetch_object($sqlPre)) {
    $nom = traiteCarac($resPre->key_name);
    $arrPre[$resPre->key_id] = $resPre->key_id;
    $arrKeyName[$resPre->key_id] = $nom;
    $arrKeyType[$resPre->key_id] = $resPre;
}
//die($requetePre);


if (!$withRev) {
    $wh .= " AND revisionNext < 1"; //revision is NULL ";
} else {
    $sousReq = "(SELECT orig_ref FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE id = " . $_REQUEST['chrono_refid'] . ")";
    $wh .= " AND chr.id <>" . $_REQUEST['chrono_refid'] . " AND  (orig_ref = " . $sousReq . " || ref = " . $sousReq . ")";
}


$requete = "SELECT tview.*, chr.*, soc.nom as socname, soc.rowid as socid FROM llx_synopsischrono_chrono_" . $id . " tview, llx_synopsischrono chr LEFT JOIN llx_societe soc ON soc.rowid = fk_societe WHERE tview.id = chr.id " . $wh;

$requete .= $wh1;
if($sidx == "id")
    $sidx = "chr.id";
$requete .= " ORDER BY " . $sidx . " " . $sord . "";

//echo($requete);die;
$result = $db->query($requete);
if (!$result) {

    require(DOL_DOCUMENT_ROOT . "/synopsischrono/ajax/testCreateView.php");
    $result = $db->query($requete);
    if (!$result)
        die("Impossible de construire les vue");
}

class general {
    
}

$count = $db->num_rows($result);

$responce = new general();
$responce->page = $page;
$responce->total = round(($count / $limit) + 0.49);
//            }
//            $requete .= "      ORDER BY $sidx $sord";
//            if ($sidx != "chrono_id" || $searchField) {
//                $responce->records = $i;
$requete .= "         LIMIT $start , $limit";
//            } else
$responce->records = $count;

require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");
$socStatique = new Societe($db);


$chrono = new Chrono($db);
$chrono->loadObject = false;

//echo $requete;
$sql = $db->query($requete);
if ($sql) {
    $i = 0;
    while ($res = $db->fetch_object($sql)) {
        $arr = array();
        $arr[] = $res->id;
        $chrono->fetch($res->id);
        $model = $chrono->model;

        $arr[] = $chrono->getNomUrl(1);

        //hasRev => 1 si oui, rien sinon
        if (!$withRev) {
//            $requete1 = "SELECT * FROM " . MAIN_DB_PREFIX . "synopsischrono WHERE orig_ref = '" . addslashes($chrono->ref) . "' AND revision > 0";
////                        die($requete1);
//            $sql1 = $db->query($requete1);
//            $hasRev = false;
//            if ($db->fetch_object($sql1))
//                $hasRev = true;
//            if ($res1->cnt > 0) $hasRev = true;
            $hasRev = ($res->orig_ref && $res->orig_ref != "");
            $arr[] = ($hasRev ? '<div class="hasRev">1</div>' : '<div class="hasRev">0</div>');
        } else {
            $arr[] = ('<div class="hasRev">0</div>');
        }

        if ($chrono->model->hasSociete) {
            $html = "";
            if ($res->socid > 0) {
                $socStatique->id = $res->socid;
                $socStatique->name = $res->socname;
                $html = $socStatique->getNomUrl(1, '', 20);
            }
            $arr[] = $html;
        }

        if ($chrono->model->hasDescription && $chrono->model->descInList)
            $arr[] = ($chrono->description ? $chrono->description : '');
//echo $valeur;print_r($arrKeyName);die;
        foreach ($arrKeyName as $keyid => $keyname) {
            $model = $arrKeyType[$keyid];
            $value = stripslashes($res->$keyname);
//            if(!$value > 0)
//                die($keyname.print_r($res, true));
            if ($model->type_valeur == 3) {
//                $value = inversDate($value);
                $value = (strtotime($value) > 0 ? date('Y-m-d H:i:s', strtotime($value)) : "");
            } elseif ($model->type_valeur == 2) {
//                $value = inversDate($value);
                $value = (strtotime($value) > 0 ? date('Y-m-d', strtotime($value)) : "");
            } elseif ($model->type_subvaleur > 0 /*&& $model->type_valeur != "8"*/) {
                $value = parseValue1($res->id, $value, $model);
            }
            
            if(stripos($value, "<a") === false)
                    $value = dol_trunc($value);
            
            $arr[] = $value;
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
        if ($chrono->model->hasFile && count($arr) < $chrono->model->maxForNbDoc)
            $arr[] = count_files($conf->synopsischrono->dir_output . "/" . $chrono->id);

        $responce->rows[$i]['cell'] = $arr;
        $i++;
    }
} else {
    var_dump($db);
}

echo @json_encode($responce);

//        }
//        break;
//}

function inversDate($str) {
    $valueT1 = explode(" ", $str);
    $valueT2 = explode("/", $valueT1[0]);

    $return = $valueT2[2] . "/" . $valueT2[1] . "/" . $valueT2[0];

    if (isset($valueT1[1]))
        $return .= " " . $valueT1[1];
    return $return;
}

function parseValue1($idChrono, $value, $res) {
    return parseValue($idChrono, $value, $res->extraCss, $res->type_subvaleur, $res->sourceIsOption, $res->phpClass, $res->valueIsSelected, $res->valueIsChecked);
}

function parseValue($idChrono, $val, $extraCss, $hasSubValeur = false, $sourceIsOption = false, $phpClass = '', $valueIsSelected = false, $valueIsChecked = false) {
    global $db;
    $val = stripslashes($val);
    //synopsischrono_key_type_valeur
    if ($hasSubValeur > 0) {
//var_dump($phpClass);
        require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
        $tmp = $phpClass;
        $obj = new $tmp($db);
        $obj->cssClassM = $extraCss;
        $obj->idChrono = $idChrono;

        $obj->fetch($hasSubValeur, $extraCss);
        if ($phpClass == 'globalvar') {
            return $obj->getValue($val);
        } elseif ($phpClass == 'fct') {
            echo $obj->call_function_chronoModule($chr->model_refid, $chr->id);
        } elseif ($sourceIsOption) {
//            if($phpClass == "liste")
//        echo($phpClass."ici".$val);
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
//        } else {
//            //Beta
//             if ($phpClass == 'globalvar') {
//                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
//                $tmp = $phpClass;
//                $obj = new $tmp($db);
//                $obj->cssClassM = $extraCss;
//                $obj->idChrono = $val;
//                $obj->fetch($hasSubValeur);
//                return $obj->getValue($val);
//            } elseif ($phpClass == 'fct') {
//                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Process/class/process.class.php");
//                $tmp = $phpClass;
//                $obj = new $tmp($db);
//                $obj->cssClassM = $extraCss;
//                $obj->idChrono = $val;
//                $obj->fetch($hasSubValeur);
//                echo $obj->call_function_chronoModule($chr->model_refid, $chr->id);
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
//        }
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
