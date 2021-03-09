<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'DATES LIM COMMANDES', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

$bdb = new BimpDb($db);

$file = DOL_DOCUMENT_ROOT . '/bimpcore/scripts/docs/dates_lim_commandes.csv';

if (!file_exists($file)) {
    echo BimpRender::renderAlerts('Fichier absent');
    exit;
}

$lines = file($file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

$rows = array();

foreach ($lines as $line) {
    $rows[] = str_getcsv($line, ';');
}

$i = 0;
foreach ($rows as $r) {
    $i++;

    echo ' - ' . $i . ': ';
    $ref = (string) $r[0];
    if (!$ref) {
        echo '<span class="danger">Ref absente</span>';
    } else {
        $comm = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_Commande', array(
                    'ref' => $ref
        ));

        if (!BimpObject::objectLoaded($comm)) {
            echo '<span class="danger">Ref non trouvée: ' . $ref . ' </span>';
        } else {
            $lines = $comm->getLines('not_text');

            if (empty($lines)) {
                echo '<span class="warning">Aucune ligne</span>';
            } else {
                $data = array();

                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2})$/', $r[1], $matches)) {
                    $data['date_start'] = '20' . $matches[3] . '-' . BimpTools::addZeros($matches[1], 2) . '-' . BimpTools::addZeros($matches[2], 2);
                }
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2})$/', $r[2], $matches)) {
                    $data['date_end'] = '20' . $matches[3] . '-' . BimpTools::addZeros($matches[1], 2) . '-' . BimpTools::addZeros($matches[2], 2);
                }

                if (!empty($data)) {
                    echo 'COMM #' . $comm->id . ' - ' . $ref . ' => ';
                    $ids_lines = array();

                    foreach ($lines as $line) {
                        if ((int) $line->getData('id_line')) {
                            $ids_lines[] = (int) $line->getData('id_line');
                        }
                    }

                    $where = 'rowid IN (' . implode(',', $ids_lines) . ')';

                    if ($bdb->update('commandedet', $data, $where) <= 0) {
                        echo '<span class="danger">ECHEC - ' . $bdb->err() . '</span>';
                    } else {
                        echo '<span class="success">OK</span>';
                    }
                } else {
                    echo '<span class="warning">Aucune date</span>';
                }
            }
        }
    }
    echo '<br/>';
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
