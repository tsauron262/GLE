<?php
//        $pulls = file_get_contents(PATH_TMP.'/git_logs_commit/logs.logs');
require_once("../main.inc.php");
echo '<pre>' . print_r($_SERVER, true) . '</pre>';
 // exit();
//	$sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV');  // TR2TQV4M9P
//	$sav->setData('serial','2063238');
	require_once("../bimpapple/classes/GSX_v2.php");
$gsx = new GSX_v2();

echo '<pre>' . print_r($gsx->productDetailsBySerial(2063238), true) . '</pre>';

echo '<pre>' . print_r($gsx->serialEligibility(2063238), true) . '</pre>';

echo '<pre>' . print_r($gsx->partsSummaryBySerial(2063238), true) . '</pre>';

echo '<pre>' . print_r($gsx->partsSummaryBySerialAndIssue(2063238), true) . '</pre>';

echo '<pre>' . print_r($gsx->getIssueCodesBySerial(2063238), true) . '</pre>';


die('llllicicicicicicic');
