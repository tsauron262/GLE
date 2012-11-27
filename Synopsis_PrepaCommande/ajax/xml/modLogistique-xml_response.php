<?php

/*
 * * GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 5 oct. 2010
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : modLogistique-xml_response.php
 * GLE-1.2
 */
require_once('../../../main.inc.php');
$id = $_REQUEST['id'];
$xmlStr = "<ajax-response>";
$arrId = array();
$totalKO = true;
$totalOK = true;
$yesnoArr['yes'] = 1;
$yesnoArr['no'] = 0;
$nextDispo = false;
foreach ($_REQUEST as $key => $val) {
    if (preg_match('/^logistiqueOK-([0-9]*)/', $key, $arr)) {
        //Get Date
        $arrId[$arr[1]] = $yesnoArr[$val];

        $set = 'logistique_ok='.$yesnoArr[$val];

        if ($yesnoArr[$val] == 0 && preg_match('/([0-9]{2})[\W]{1}([0-9]{2})[\W]{1}([0-9]{4})/', $_REQUEST['logistiqueKODate-' . $arr[1]], $arr1)) {
            $epochDate = mktime(0, 0, 0, $arr1[2], $arr1[1], $arr1[3]);
            $dateDispo = date('Y-m-d', $epochDate);
            $set .= ", logistique_date_dispo='" . $dateDispo . "'";
            $sql = $db->query($requete);
            if ($dateDispo > $nextDispo) {
                $nextDispo = $dateDispo;
            }
        }
        $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_commandedet SET " . $set . " WHERE rowid = " . $arr[1];
        $sql = $db->query($requete);
        if ($yesnoArr[$val] == 0 && $totalOK) {
            $totalOK = false;
        }
        if ($yesnoArr[$val] == 1 && $totalKO) {
            $totalKO = false;
        }
    }
}
$totpart = 'KO'; //0
$idePart = 0;
if ($totalOK && !$totalKO) {
    $totpart = 'OK'; //1
    $idePart = 1;
} else if (!$totalOK && !$totalKO) {
    $totpart = 'Partiel'; //2
    $idePart = 2;
}
$requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_commande SET logistique_ok=" . $idePart . " WHERE rowid =" . $_REQUEST['comId'];
$db->query($requete);
if ($idePart != 1) {
    $requete = "UPDATE " . MAIN_DB_PREFIX . "Synopsis_commande SET logistique_date_dispo = '" . $nextDispo . "' WHERE rowid=" . $_REQUEST['comId'];
    $db->query($requete);
}
$xmlStr .= "<result>" . $idePart . "</result>";



if (stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml")) {
    header("Content-type: application/xhtml+xml;charset=utf-8");
} else {
    header("Content-type: text/xml;charset=utf-8");
}
$et = ">";
print "<?xml version='1.0' encoding='utf-8'?$et\n";
print $xmlStr;
print "</ajax-response>";
?>
