<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

top_htmlhead('', 'CREA REVALS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

set_time_limit(1200);

$factures = BimpCache::getBimpObjectObjects('bimpcommercial', 'Bimp_Facture', array(
            'datec'     => array(
                'operator' => '>=',
                'value'    => '2019-07-01 00:00:00'
            ),
            'fk_statut' => array(
                'in' => array(1, 2)
            ),
            'type'      => array(
                'in' => array(0, 1, 2)
            )
        ));

foreach ($factures as $facture) {
    $lines = $facture->getLines('not_text');

    echo 'Fac ' . $facture->getRef() . '<br/>';

    foreach ($lines as $line) {
        if ((int) $line->getData('remise_crt')) {
            echo 'LINE ' . $line->getData('position') . ': ';

            $product = $line->getProduct();

            if (!BimpObject::objectLoaded($product)) {
                echo '[PAS DE PROD] <br/>';
                continue;
            }

            $remise_percent = (float) $product->getRemiseCrt();

            if (!$remise_percent) {
                echo 'PAS DE REMISE CRT SUR LE PROD <br/>';
                continue;
            }

            $remise_pa = $line->pu_ht * ($remise_percent / 100);
            echo 'REMISE: ' . $remise_pa . '<br/>';

            if ($remise_pa) {
                // Check si existe déjà: 
                $reval = BimpCache::findBimpObjectInstance('', '', array(
                            'id_facture'      => (int) $facture->id,
                            'id_facture_line' => (int) $line->id,
                            'type'            => 'crt'
                ));

                if (BimpObject::objectLoaded($reval)) {
                    echo 'UNE REVAL EXISTE DEJA <br/>';
                    continue;
                }

                $reval = BimpObject::getInstance('bimpfinanc', 'BimpRevalorisation');

                $dt = new DateTime($facture->getData('datec'));

                $err = $reval->validateArray(array(
                    'id_facture'      => (int) $facture->id,
                    'id_facture_line' => (int) $line->id,
                    'type'            => 'crt',
                    'date'            => $dt->format('Y-m-d'),
                    'amount'          => $remise_pa,
                    'qty'             => (float) $line->qty
                ));

                if (!count($err)) {
                    $w = array();
                    $err = $reval->create($w, true);
                }

                if (count($err)) {
                    echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($err, 'Echec créa reval'));
                }
            }
        }
    }
}

echo 'FIN';
echo '</body></html>';

//llxFooter();
