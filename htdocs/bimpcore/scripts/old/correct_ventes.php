<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CORRECTION MODE REGLEMENT FACTURES VENTES', 0, 0, array(), array());

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

$bdb->update('bc_vente_paiement', array(
    'code' => 'FIN'
        ), 'code = \'no\'');

$sql = 'SELECT f.rowid FROM llx_bc_vente_paiement p';
$sql .= ' LEFT JOIN llx_bc_vente v ON v.id = p.id_vente';
$sql .= ' LEFT JOIN llx_facture f ON f.rowid = v.id_facture';
$sql .= ' WHERE p.code = \'FIN\'';
$sql .= ' AND f.fk_mode_reglement != 13';
$sql .= ' AND f.fk_statut = 1';
$sql .= ' AND f.paye = 0';

$rows = $bdb->executeS($sql, 'array');

echo $bdb->err();

$facs = array();

foreach ($rows as $r) {
    if ((int) $r['rowid']) {
        $facs[] = (int) $r['rowid'];
    }
}

if (!empty($facs)) {
    if ($bdb->update('facture', array(
                'fk_mode_reglement' => 13
                    ), 'rowid IN (' . implode(',', $facs) . ')') <= 0) {
        echo 'FAIL ' . $bdb->err();
    } else {
        echo 'OK';
    }
} else {
    echo 'NO FACS';
}

echo '<br/>';

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
