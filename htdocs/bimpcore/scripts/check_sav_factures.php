<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

//llxHeader();

echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
//echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/ticket.css' . '"/>';
echo '<script src="/test2/includes/jquery/js/jquery.min.js?version=6.0.4" type="text/javascript"></script>';
echo '</head>';

echo '<body>';

global $db;
$bdb = new BimpDb($db);

$where = '`facnumber` LIKE \'FAS1901-%\' AND `datec` >= 2019-01-17';
$rows = $bdb->getRows('facture', $where, null, 'array', array(
    'rowid', 'facnumber', 'total'
        ));

$delete_txt = 'Factures supprimables automatiquement' . "\n\n";
$delete_txt .= 'ID - Ref - Total HT' . "\n\n";

$process_txt = 'Factures à traiter' . "\n\n";
$process_txt .= 'N° - ID => Ref : Total HT' . "\n\n";

$nDelete = 1;
$nProcess = 1;

foreach ($rows as $r) {
    $sav_id = (int) $bdb->getValue('bs_sav', 'id', '`id_facture` = ' . (int) $r['rowid']);
    if (!$sav_id) {
        if ((float) $r['total'] >= -0.01 && (float) $r['total'] <= 0.01) {
            $delete_txt .= $nDelete . ' - ' . $r['rowid'] . ' => ' . $r['facnumber'] . ': ' . $r['total'] . "\n";
            $nDelete++;
        } else {
            $process_txt .= $nProcess . ' - ' . $r['rowid'] . ' => ' . $r['facnumber'] . ': ' . $r['total'] . "\n";
            $nProcess++;
        }
    }
}

file_put_contents(DOL_DATA_ROOT . '/facture_suppr.txt', $delete_txt);
file_put_contents(DOL_DATA_ROOT . '/facture_a_traiter.txt', $process_txt);

echo DOL_DATA_ROOT; 

echo '</body></html>';

//llxFooter();
