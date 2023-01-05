<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'Verif ventes abandonnées', 0, 0, array(), array());

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
$where = 'status = 0 AND date_create > \'2020-05-10\'';
$rows = $bdb->getRows('bc_vente', $where, null, 'array', null, 'id', 'desc');

if (!(int) BimpTools::getValue('exec', 0)) {
    echo 'Vérifie l\'existance de ventes abondonnées avec création de facture<br/>';

    if (is_array($rows) && count($rows)) {
        echo count($rows) . ' élément(s) à traiter <br/><br/>';

        $path = pathinfo(__FILE__);
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1" class="btn btn-default">';
        echo 'Exécuter';
        echo '</a>';

        echo '<br/><br/>';
        echo 'Rés: <pre>';
        print_r($rows);
        echo '</pre>';

        exit;
    }

    echo BimpRender::renderAlerts('Aucun élément à traiter', 'info');
    exit;
}

$where = 'note_private LIKE \'%Vente #[id_vente]%\'';

foreach ($rows as $r) {
    $id_fac = $bdb->getValue('facture', 'rowid', str_replace('[id_vente]', $r['id'], $where));

    if ($id_fac) {
        echo 'Vente #' . $r['id'] . ': Fac #' . $id_fac . '<br/>';
    }
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
