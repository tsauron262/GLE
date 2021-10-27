<?php
/*
 * 
 *  Script Alexis
 * 
 */

require_once("../../main.inc.php");
require_once __DIR__ . '/../Bimp_Lib.php';


$export = BimpCache::getBimpObjectInstance('bimptocegid', "BTC_exportRibAndMandat");

$bdd = new BimpDb($db);

$file = '4_'."BIMP".'_(RIBS)_' . "UNIQUE" . "_" . "Y2" . ".tra";

$export_dir = PATH_TMP  ."/" . 'exportCegid' . '/' . $complementDirectory . '/';
$export_project_dir = PATH_TMP . "/" . 'exportCegid' . '/';

if(!file_exists($export_dir . $file)) {
    $create_file = fopen($export_dir . $file, 'a+');
    fwrite($create_file, $export->head_tra());
    fclose($create_file);
}


$list = $bdd->getRows("societe_rib", "exported = 1");

foreach($list as $rib) {
    $ecriture .= $export->export_rib_exported($rib->rowid);
}
echo "<pre>";
echo $ecriture;