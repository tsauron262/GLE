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
    'bfl' => array(
        'table' => 'bimp_facture_line',
        'alias' => 'bfl',
        'on'    => 'bfl.id_line = fl.rowid'
    ),
    'f'   => array(
        'table' => 'facture',
        'alias' => 'f',
        'on'    => 'f.rowid = fl.fk_facture'
    ),
    'fef' => array(
        'table' => 'facture_extrafields',
        'alias' => 'fef',
        'on'    => 'fef.fk_object = fl.fk_facture'
    ),
    'p'   => array(
        'table' => 'product',
        'alias' => 'p',
        'on'    => 'p.rowid = fl.fk_product'
    )
);

$where = 'f.datec > \'2019-06-30 23:59:59\'';
$where .= 'AND fl.qty < 0 AND fl.buy_price_ht = 0 AND bfl.pa_editable = 1 AND fl.fk_product > 0 AND p.no_fixe_prices = 0';

$rows = $bdb->getRows('facturedet fl', $where, null, 'array', array('fl.*', 'f.datec', 'bfl.id as id_bimp_line'), 'fl.rowid', 'desc', $joins);

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

foreach ($rows as $r) {
    $facLine = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', (int) $r['id_bimp_line']);

    if (BimpObject::objectLoaded($facLine)) {
        echo 'Correction ligne #' . $facLine->id . ' - Fac #' . $r['fk_facture'] . ' ';

        $product = $facLine->getProduct();

        if (BimpObject::objectLoaded($product)) {
            $pa_ht = (float) $product->getCurrentPaHt(null, true, $r['datec']);
            $cur_pa_ht = (float) $facLine->getPaWithRevalorisations();

            if ($pa_ht !== (float) $cur_pa_ht) {
                echo ' - Nouveau PA: ' . $pa_ht;
                if (!$test) {
                    $errors = $facLine->updatePrixAchat($pa_ht);

                    if (count($errors)) {
                        echo BimpRender::renderAlerts($errors);
                    } else {
                        echo ' - <span class="success">OK</span>';
                    }
                }
            } else {
                echo '<span class="success">PA avec reval OK (' . $cur_pa_ht . ')</span>';
            }
        } else {
            echo '<span class="danger">Pas de produit</span>';
        }
        echo '<br/>';
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