<?php

/*
 *
 * Name : listContrat_json.php
 * BIMP-ERP-1.1
 */

function searchtext($nom, $pref = '') {
    $searchString = $_REQUEST[$nom];
    $searchField = $pref . $nom;
    $oper = 'LIKE';
    return " AND " . $searchField . " " . $oper . " '%" . $searchString . "%'";
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

if (isset($_REQUEST['model']) && $_REQUEST['model'] > 0) {
    $ch = new Chrono($db);
    $ch->model_refid = $_REQUEST['model'];
    if (isset($_REQUEST['socid']))
        $ch->socid = $_REQUEST['socid'];

    if (isset($_REQUEST['champSup'])) {
        $tabChamp = explode("-", $_REQUEST['champSup']);
        $tabChampVal = explode("-", $_REQUEST['champSupVal']);
        foreach ($tabChamp as $idT => $champ)
            if (is_numeric($champ) && $idT > 0)
                $champTab[$champ] = $tabChampVal[$idT];
            elseif ($champ == "fk_propal")
                $ch->propalid = $tabChampVal[$idT];
            elseif ($champ == "fk_projet")
                $ch->projetid = $tabChampVal[$idT];
    }
    $id = $ch->create();
    if (isset($champTab))
        $ch->setDatas($id, $champTab);

    echo $id;
}
//        $champ[1001] = date("d/m/Y");
//        $ch->setDatas($id, $champ);
?>
