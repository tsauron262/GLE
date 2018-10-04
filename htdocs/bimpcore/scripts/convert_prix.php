<?php

define('NOLOGIN', '1');

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

$where = '`tva_tx` > 0 AND `localtax1_tx` = 0 AND `localtax2_tx` = 0 AND `price_base_type` = \'HT\''; // AND `rowid` = 22492';

$rows = $bdb->getRows('product', $where, null, 'array', array(
    'rowid', 'price', 'price_ttc', 'tva_tx'
        ));

if (!is_null($rows) && count($rows)) {
    foreach ($rows as $r) {
//        echo 'Prix HT initial: ' . $r['price'] . '<br/>';
//        echo 'Prix TTC: ' . $r['price_ttc'] . '<br/>';

        $price_ht = (float) BimpTools::calculatePriceTaxEx((float) round($r['price_ttc'], 2), (float) $r['tva_tx']);
        if ($price_ht !== (float) $r['price']) {
            echo 'PRODUIT ' . $r['rowid'] . ': <br/>';
            echo 'Différentiel trouvé: ' . $price_ht;
            if ($bdb->update('product', array(
                        'price' => $price_ht
                            ), '`rowid` = ' . (int) $r['rowid']) <= 0) {
                echo '[ECHEC] - ' . $bdb->db->lasterror();
            } else {
                echo '[OK]';
            }
            echo '<br/><br/>';
        }
    }
} else {
    echo 'AUCUN PRODUIT A TRAITER TROUVE';
}

echo '</body></html>';

//llxFooter();
