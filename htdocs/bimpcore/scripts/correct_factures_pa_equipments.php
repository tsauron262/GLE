<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'CORRECT FACTURES PA', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

echo 'DEBUT <br/><br/>';

global $db;
$bdb = new BimpDb($db);

$factures = BimpCache::getBimpObjectList('bimpcommercial', 'Bimp_Facture', array(
            'datec' => '> 2019-06-30'
        ));

foreach ($factures as $id_facture) {
    $facture = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);

    if (BimpObject::objectLoaded($facture)) {
        $lines = $facture->getLines('product');

        $has_lines = false;
        foreach ($lines as $line) {
            if ($line->isProductSerialisable()) {
                $has_lines = true;
                echo 'Facture #' . $facture->id . ' - Ligne #' . $line->getData('id_line') . ': ';
                $line_errors = $line->calcPaByEquipments();
                if (count($line_errors)) {
                    echo BimpRender::renderAlerts($line_errors);
                } else {
                    echo '<span class="success">OK</span>';
                }
                echo '<br/>';
            }
        }
        if ($has_lines) {
            echo '<br/>';
        }
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();

