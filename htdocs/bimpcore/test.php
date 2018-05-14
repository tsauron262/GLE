<?php

define('NOLOGIN', '1');

require_once("../main.inc.php");

//
//$modulepart = 'bimpcore';
//$modulepart2 = 'bimpfinancement/demande';
//
//$object = new stdClass();
//$object->ref = 5;
//
//$permission = $user->rights->societe->creer;
//$permtoedit = $user->rights->societe->creer;
//
//$object->id = 5;
//
//$action = GETPOST("action");
//$confirm = GETPOST("confirm");
//$upload_dir = DOL_DATA_ROOT."/".$modulepart."/".$modulepart2."/".$object->ref;
//require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
//require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
//include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';
//
////ATTENTION pas de données envoyé avant cela
//llxHeader();
//
//$relativepathwithnofile = $modulepart2."/".$object->ref."/";
//$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
//include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
//
//
//
//
//

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';

//llxHeader();

echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
//echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/ticket.css' . '"/>';
echo '<script src="/test2/includes/jquery/js/jquery.min.js?version=6.0.4" type="text/javascript"></script>';
echo '</head>';

echo '<body>';
//BimpTools::loadDolClass('compta/facture', 'facture');
//
//global $db;
//
//$facture = new Facture($db);
//$facture->fetch(86073);
//
//$ticket = new BimpTicket($db, 370, $facture);
//
//echo $ticket->renderHtml();
//
//echo '<script>';
//echo '$(document).ready(function() {';
//echo 'window.print();';
//echo '});';
//echo '</script>';
//$sav = BimpObject::getInstance('bimpsupport', 'BS_SAV');
//$errors = $sav->validateArray(array(
//    'status'       => 3,
//    'id_equipment' => 1,
//    'id_client'    => 1
//        ));
//
//if (!count($errors)) {
//    $errors = $sav->create();
//}
//
//echo '<pre>';
//print_r($errors);
//exit;

$repair = BimpObject::getInstance('bimpapple', 'GSX_Repair', 11);
if (BimpObject::objectLoaded($repair)) {
    $repair->updateTotalOrder();
} else {
    echo 'KO';
}

echo '</body></html>';


//llxFooter();
