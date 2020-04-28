<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'EXPORT STOCKS MVT', 0, 0, array(), array());

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

$sql = 'SELECT a.rowid, a.datem as date, a.value as qty, p.ref as ref_prod, e.ref as ref_ent';
$sql .= ' FROM llx_stock_mouvement a';
$sql .= ' LEFT JOIN llx_product p ON p.rowid = a.fk_product';
$sql .= ' LEFT JOIN llx_entrepot e ON e.rowid = a.fk_entrepot';

$sql .= ' ORDER BY a.rowid DESC';

$rows = $bdb->executeS($sql, 'array');

$str = '"ID";"Date";"Entrepôt";"Produit";"Raison";"Quantité"' . "\n";

foreach ($rows as $r) {
    $obj = BimpCache::getBimpObjectInstance('bimpcore', 'BimpProductMouvement', (int) $r['rowid']);

    if (BimpObject::objectLoaded($obj)) {
        $raison = $obj->displayReasonMvt();
    }

    $dt = new DateTime($r['date']);

    $str .= '"' . $r['rowid'] . '";"' . $dt->format('d / m / Y H:i') . '";"' . $r['ref_ent'] . '";"' . $r['ref_prod'] . '";"' . $raison . '";"' . $r['qty'] . '"' . "\n";

    BimpCache::$cache = array();
}


if (!file_put_contents(DOL_DATA_ROOT . '/bimpcore/stock_mvts_full.csv', $str)) {
    echo 'Echec de la création du fichier CSV';
} else {
    $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('stock_mvts_full.csv');
    echo '<script>';
    echo 'window.open(\'' . $url . '\')';
    echo '</script>';
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
