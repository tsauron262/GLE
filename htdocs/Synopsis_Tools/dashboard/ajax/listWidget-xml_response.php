<?php

/*
 * GLE by Babel-Services
 *
 * Author: Jean-Marc LE FEVRE <jm.lefevre@babel-services.com>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.0
 * Created on : 6 juil. 2010
 *
 * Infos on http://www.babel-services.com
 *
 */
/**
 *
 * Name : listWidget-xml_response.php
 * GLE-1.1
 */
require_once("../../../main.inc.php");

if (!isset($_REQUEST['action']))
    $_REQUEST['action'] = '';

$action = $_REQUEST['action'];
$xml = "<ajax-response>";

$activeArr = array();
$disableArr = array();

$userid = $_REQUEST['userid'];
$type = $_REQUEST['type'];
$requete = "SELECT " . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget.id,
                        " . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget.nom,
                        " . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget.module
                   FROM " . MAIN_DB_PREFIX . "Synopsis_Dashboard_module,
                        " . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget
                  WHERE " . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget.id = " . MAIN_DB_PREFIX . "Synopsis_Dashboard_module.module_refid
                    AND type_refid = '" . $type . " AND " . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget.active = 1 '
               ORDER BY " . MAIN_DB_PREFIX . "Synopsis_Dashboard_widget.nom ";
$sql = $db->query($requete);
$arr = array();
while ($res = $db->fetch_object($sql)) {
    $arr[$res->module]['id'] = $res->id;
    $arr[$res->module]['nom'] = $res->nom;
    $arr[$res->module]['module'] = $res->module;
    $disableArr[$res->id] = $res->nom;
}
$requete = "";
if ($userid > 0) {
    $requete = "SELECT params
                       FROM " . MAIN_DB_PREFIX . "Synopsis_Dashboard
                      WHERE dash_type_refid = '" . $type . "'
                        AND user_refid =" . $userid;
} else {
    $requete = "SELECT params
                       FROM " . MAIN_DB_PREFIX . "Synopsis_Dashboard
                      WHERE dash_type_refid = '" . $type . "'
                        AND user_refid is null";
}
$sql = $db->query($requete);
while ($res = $db->fetch_object($sql)) {
    $arrTmp = unserialize($res->params);
    foreach ($arrTmp as $key => $val) {
        foreach ($val as $key1 => $val1) {
            //print $key1." ".$val1."\n";
            if ($key1 == $arr[$key1]['module']) {
                $activeArr[$arr[$key1]['id']] = $arr[$key1]['nom'];
                $disableArr[$arr[$key1]['id']] = false;
            }
        }
    }
}

$xml .= "<list>";
foreach ($activeArr as $key => $val) {
    $xml .= "<active id='" . $key . "'><![CDATA[" . html_entity_decode($val) . "]]></active>";
}
foreach ($disableArr as $key => $val) {
    if ($val) {
        $xml .= "<disabled id='" . $key . "'><![CDATA[" . html_entity_decode($val) . "]]></disabled>";
    }
}
$xml .= "</list>";



if (stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml")) {
    header("Content-type: application/xhtml+xml;charset=utf-8");
} else {
    header("Content-type: text/xml;charset=utf-8");
} $et = ">";
echo "<?xml version='1.0' encoding='utf-8'?$et\n";
echo $xml;
echo "</ajax-response>";
?>
