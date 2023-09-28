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


echo 'Script désactivé';
exit;


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

foreach ($rows as $r) {
    $refs = explode(' ', (string) $r[0]);
    if (empty($refs)) {
        echo '<span class="danger">Aucune ref</span>';
    } else {
        $data = array();

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2})$/', $r[1], $matches)) {
            $data['date_start'] = '20' . $matches[3] . '-' . BimpTools::addZeros($matches[1], 2) . '-' . BimpTools::addZeros($matches[2], 2);
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2})$/', $r[2], $matches)) {
            $data['date_end'] = '20' . $matches[3] . '-' . BimpTools::addZeros($matches[1], 2) . '-' . BimpTools::addZeros($matches[2], 2);
        }

        if (!empty($data)) {
            foreach ($refs as $ref) {
                if (!$ref) {
                    continue;
                }
                
                echo ' - ' . $ref .': ';
                $comm = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_Commande', array(
                            'ref' => $ref
                ));

                if (!BimpObject::objectLoaded($comm)) {
                    echo '<span class="danger">Ref non trouvée</span>';
                } else {
                    $sql = 'SELECT rowid FROM llx_commandedet WHERE fk_commande = '. $comm->id.' AND subprice > 0';
                    $comm_lines = $bdb->executeS($sql, 'array');
                    
                    if (empty($comm_lines)) {
                        echo '<span class="warning">Aucune ligne</span>';
                    } else {
                        echo 'COMM #' . $comm->id . ' => ';
                        $ids_lines = array();

                        foreach ($comm_lines as $line) {
                            $ids_lines[] = $line['rowid'];
                        }

                        $where = 'rowid IN (' . implode(',', $ids_lines) . ')';
                        
                        if ($bdb->update('commandedet', $data, $where) <= 0) {
                            echo '<span class="danger">ECHEC - ' . $bdb->err() . '</span>';
                        } else {
                            echo '<span class="success">OK</span>';
                        }
                    }
                }
                
                echo '<br/>';
            }
        } else {
            echo '<span class="warning">Aucune date</span>';
        }
    }
    echo '<br/>';
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
