<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CORRECTION PA FACTURES CAISSE', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
}

$bdb = new BimpDb($db);

$joins = array(
    'f'   => array(
        'table' => 'facture',
        'alias' => 'f',
        'on'    => 'f.rowid = fl.fk_facture'
    ),
    'fef' => array(
        'table' => 'facture_extrafields',
        'alias' => 'fef',
        'on'    => 'fef.fk_object = fl.fk_facture'
    )
);

$where = 'fef.type = \'M\'';
$where .= 'AND fl.qty < 0 AND fl.buy_price_ht = 0';

$rows = $bdb->getRows('facturedet fl', $where, null, 'array', array('fl.*', 'f.datec'), 'fl.rowid', 'desc', $joins);

if (!(int) BimPTools::getValue('exec', 0)) {
    echo 'Corrige les PA à 0 des factures pour les retours en caisse.<br/>';

    if (is_array($rows) && count($rows)) {
        echo count($rows) . ' élément(s) à traiter <br/><br/>';

        $path = pathinfo(__FILE__);
        echo ' <a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1&test=1" class="btn btn-default">';
        echo 'Test';
        echo '</a>';
        echo ' <a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1&test_one=1" class="btn btn-default">';
        echo 'Executer une entrée';
        echo '</a>';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1" class="btn btn-default">';
        echo 'Tout éxécuter';
        echo '</a>';
    }

    echo BimpRender::renderAlerts('Aucun élément à traiter', 'info');
    exit;
}

$test = (int) BimpTools::getValue('test', 0);
$test_one = (int) BimpTools::getValue('test_one', 0);

foreach ($rowd as $r) {
    $facLine = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', array(
                'id_line' => (int) $r['rowid']
    ));

    if (BimpObject::objectLoaded($facLine)) {
        $product = $facLine->getProduct();

        if (BimpObject::objectLoaded($product)) {
            $pa_ht = (float) $product->getCurrentPaHt(null, true, $r['datec']);

            if ($pa_ht !== (float) $r['buy_price_ht']) {
                if ($test || $test_one) {
                    echo 'Correction ligne #' . $facLine->id . ' - Fac #' . $r['fk_facture'] . ': ' . $pa_ht . '<br/>';
                }
                
                if (!$test) {
                    $errors = $facLine->updatePrixAchat($pa_ht);
                    
                    if (count($errors)) {
                        if (!$test_one) {
                            echo 'Correction ligne #' . $facLine->id . ' - Fac #' . $r['fk_facture'] . ': ' . $pa_ht . ': ';
                        }
                        
                        echo BimpRender::renderAlerts($errors);
                    }
                }
            }
        }
    } else {
        echo '<span class="danger">';
        echo 'BimpFactureLine non trouvée pour la ligne #' . $r['rowid'] . ' (Facture #' . $r['fk_facture'] . ')';
        echo '</span><br/>';
        continue;
    }

    if ($test_one) {
        break;
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();