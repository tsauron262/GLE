<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CORR RELANCES 5', 0, 0, array(), array());

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
$where = 'id_relance = 1981 AND relance_idx = 1';
$rows = $bdb->getRows('bimp_relance_clients_line', $where, null, 'array', null, 'id', 'desc');

foreach ($rows as $r) {
    $rl = BimpCache::getBimpObjectInstance('bimpcommercial', 'BimpRelanceClientsLine', (int) $r['id']);

    if (BimpObject::objectLoaded($rl)) {
        $factures = $rl->getData('factures');

        if (!empty($factures)) {
            foreach ($factures as $id_fac) {
                $where = 'id != ' . $r['id'] . ' AND factures LIKE \'%[' . $id_fac . ']%\'';
                $prev_relance_idx = (int) $bdb->getValue('bimp_relance_clients_line', 'relance_idx', $where, 'relance_idx', 'desc');

                if ($prev_relance_idx) {
                    switch ($prev_relance_idx) {
                        case 1:
                        case 2:
                            $where = 'id != ' . $r['id'] . ' AND factures LIKE \'%[' . $id_fac . ']%\' AND relance_idx = ' . (int) $prev_relance_idx;
                            $prev_relance_date = (string) $bdb->getValue('bimp_relance_clients_line', 'date_send', $where, 'relance_idx', 'desc');
                            $dt = new DateTime($prev_relance_date);
                            $bdb->update('facture', array(
                                'nb_relance'   => ($prev_relance_idx),
                                'date_relance' => $dt->format('Y-m-d'),
                                    ), 'rowid = ' . (int) $id_fac);

                            $bdb->update('bimp_relance_clients_line', array(
                                'relance_idx'  => ($prev_relance_idx + 1),
                                'status'       => 0,
                                'date_send'    => null,
                                'id_user_send' => 0
                                    ), 'id = ' . (int) $r['id']);
                            echo $prev_relance_idx + 1 . ' => Fac #' . $id_fac . ' - ' . $bdb->getValue('facture', 'ref', 'rowid = ' . $id_fac) . ' - date: ' . $prev_relance_date . ' <br/>';
                            break;

                        case 3:
                            $bdb->update('facture', array(
                                'nb_relance' => 4
                                    ), 'rowid = ' . (int) $id_fac);

                            $bdb->update('bimp_relance_clients_line', array(
                                'relance_idx' => 4,
                                'status'      => 1
                                    ), 'id = ' . (int) $r['id']);
                            echo '4 => ' . $r['id'] . ' - ' . $id_fac . '<br/>';
                            break;

                        case 4:
                            $bdb->update('facture', array(
                                'nb_relance' => 5
                                    ), 'rowid = ' . (int) $id_fac);

                            $bdb->update('bimp_relance_clients_line', array(
                                'relance_idx' => 5,
                                'status'      => 12
                                    ), 'id = ' . (int) $r['id']);

                            echo '5 => ' . $r['id'] . ' - ' . $id_fac . '<br/>';
                    }
                }
            }
        }
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
