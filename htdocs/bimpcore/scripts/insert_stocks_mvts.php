<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CORRECTION STOCKS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;
$bdb = new BimpDb($db);

exit;

$filename = DOL_DOCUMENT_ROOT . '/bimpcore/stocks_mvts.json';
$data = json_decode(file_get_contents($filename), 1);
$rows = $data['data'];

foreach ($rows as $idx => $r) {
    if ((int) $r['fk_product']) {
        $product = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $r['fk_product']);

        if (BimpObject::objectLoaded($product)) {
            if ($product->dol_object->correct_stock($user, (int) $r['fk_entrepot'], abs($r['value']), (int) $r['type_mouvement'], $r['label'], (float) $r['price'], $r['inventorycode']) <= 0) {
                echo BimpRender::renderAlerts(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($product->dol_object), 'N°' . $idx . ' (Prod #' . $r['fk_product'] . ')')) . '<br/>';
            } else {
                echo BimpRender::renderAlerts($idx . ': PROD #' . $r['fk_product'] . ' OK', 'success');
            }
        } else {
            echo $idx . ': pas de prod #' . $r['fk_product'] . '<br/>';
        }
    } else {
        echo $idx . ': pas d\'ID prod <br/>';
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();