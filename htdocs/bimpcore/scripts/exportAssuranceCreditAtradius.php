<?php
/*
 *  Assurance Crédit Atradius
 */

require_once("../../main.inc.php");
require_once __DIR__ . '/../Bimp_Lib.php';
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', "20000MB");
$bdd = new BimpDb($db);

$moinTroisAns = new DateTime();
$moinTroisAns->sub(new DateInterval("P36M"));

$sql = "SELECT DISTINCT f.fk_soc FROM llx_facture f, llx_facture_extrafields fe WHERE fe.fk_object = f.rowid AND fe.type = 'C' AND f.datef >= '".$moinTroisAns->format('Y-m-d')."' AND f.datef < '2021-01-06'";

$res = $bdd->executeS($sql);
$allSociete = [];

$client = BimpCache::getBimpObjectInstance("bimpcore", "Bimp_Societe");
$tt = 0;

BimpCore::loadPhpExcel();
$excel = new PHPExcel();
if (!class_exists('ZipArchive')) {
            echo 'La classe "ZipArchive" n\'existe pas';
            return 0;
        }
        
$firstLoop = true;

foreach($res as $index => $array) {
    
    if ($firstLoop) { 
        $sheet = $excel->createSheet(); $sheet->setTitle("Export");
        $sheet->setCellValueByColumnAndRow(0, 1, 'Nom du client');
        $sheet->setCellValueByColumnAndRow(1, 1, 'Référence client');
    } else {$sheet = $excel->getActiveSheet();$firstLoop = false;}
    
    $client->fetch($array->fk_soc);
    if($client->getData('is_subsidiary') != 1 && $client->getData('fk_typent') != 5 && $client->getData('fk_typent') != 8) {
        $tt++;
        echo $client->getData('nom') . " <br />" ;
    }
}

$file_name = 'exportAssurance';
$file_path = DOL_DATA_ROOT . '/bimpcore/lists_excel/' . $file_name . '.xlsx';

$writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
$writer->save($file_path);

$url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('lists_excel/' . $file_name . '.xlsx');

echo '<script>';
echo 'window.open(\'' . $url . '\')';
echo '</script>';

echo "<br />" . $tt;
