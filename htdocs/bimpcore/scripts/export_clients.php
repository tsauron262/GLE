<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'EXPORT CLIENT', 0, 0, array(), array());

ignore_user_abort(0);

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

//// Clients actifs depuis 4 ans: 
//$sql = 'SELECT s.rowid, s.nom, s.code_client, s.code_compta';
//$sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe s';
//$sql .= ' WHERE s.client IN (1,2,3)';
////$sql .= ' AND (';
////$sql .= '(SELECT COUNT(p.rowid) FROM llx_propal p WHERE p.fk_soc = s.rowid AND p.datec > \'2016-06-30 00:00:00\') > 0';
////$sql .= ' OR (SELECT COUNT(c.rowid) FROM llx_commande c WHERE c.fk_soc = s.rowid AND c.date_creation > \'2016-06-30 00:00:00\') > 0';
////$sql .= ' OR (SELECT COUNT(f.rowid) FROM llx_facture f WHERE f.fk_soc = s.rowid AND f.datec > \'2016-06-30 00:00:00\') > 0';
////$sql .= ' OR (SELECT COUNT(ct.rowid) FROM llx_contrat ct WHERE ct.fk_soc = s.rowid AND ct.datec > \'2016-06-30 00:00:00\') > 0';
////$sql .= ' OR (SELECT COUNT(sav.id) FROM llx_bs_sav sav WHERE sav.id_client = s.rowid AND sav.date_create > \'2016-06-30 00:00:00\') > 0';
////$sql .= ' OR (SELECT COUNT(t.id) FROM llx_bs_ticket t WHERE t.id_client = s.rowid AND t.date_create > \'2016-06-30 00:00:00\') > 0';
////$sql .= ')';
//
//$rows = $bdb->executeS($sql, 'array');
//
//if (is_null($rows)) {
//    echo $bdb->db->lasterror() . '<br/><br/>';
//} else {
//    $clients = array();
//
//    foreach ($rows as $r) {
//        if ((int) $bdb->getValue('facture', 'rowid', 'fk_soc = ' . (int) $r['rowid'] . ' AND datec > \'2016-06-30 23:59:59\'')) {
//            $clients[] = $r;
//            continue;
//        }
//        if ((int) $bdb->getValue('propal', 'rowid', 'fk_soc = ' . (int) $r['rowid'] . ' AND datec > \'2016-06-30 23:59:59\'')) {
//            $clients[] = $r;
//            continue;
//        }
//        if ((int) $bdb->getValue('commande', 'rowid', 'fk_soc = ' . (int) $r['rowid'] . ' AND date_creation > \'2016-06-30 23:59:59\'')) {
//            $clients[] = $r;
//            continue;
//        }
//        if ((int) $bdb->getValue('contrat', 'rowid', 'fk_soc = ' . (int) $r['rowid'] . ' AND datec > \'2016-06-30 23:59:59\'')) {
//            $clients[] = $r;
//            continue;
//        }
//        if ((int) $bdb->getValue('bs_sav', 'rowid', 'id_client = ' . (int) $r['rowid'] . ' AND date_create > \'2016-06-30 23:59:59\'')) {
//            $clients[] = $r;
//            continue;
//        }
//        if ((int) $bdb->getValue('bs_ticket', 'rowid', 'id_client = ' . (int) $r['rowid'] . ' AND date_create > \'2016-06-30 23:59:59\'')) {
//            $clients[] = $r;
//            continue;
//        }
//    }
//
//    if (!empty($clients)) {
//        $str = '"Code client";"Code comptable";"Nom"' . "\n";
//
//        foreach ($clients as $r) {
//            $str .= '"' . $r['code_client'] . '";"' . $r['code_compta'] . '";"' . $r['nom'] . '"' . "\n";
//        }
//
//        if (!file_put_contents(DOL_DATA_ROOT . '/bimpcore/clients_actifs_4_ans.csv', $str)) {
//            echo 'Echec de la création du fichier CSV "clients_actifs_4_ans.csv"';
//        } else {
//            $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('clients_actifs_4_ans.csv');
//            echo '<script>';
//            echo 'window.open(\'' . $url . '\')';
//            echo '</script>';
//        }
//    }
//}

//// Clients sans commercial: 
//$sql = 'SELECT s.nom, s.code_client, s.code_compta';
//$sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe s';
//$sql .= ' WHERE s.client IN (1,2,3)';
//$sql .= ' AND (SELECT COUNT(sc.rowid) FROM llx_societe_commerciaux sc WHERE sc.fk_soc = s.rowid) = 0';
//
//$rows = $bdb->executeS($sql, 'array');
//
//if (is_null($rows)) {
//    echo $bdb->db->lasterror() . '<br/><br/>';
//} else {
//    $str = '"Code client";"Code comptable";"Nom"' . "\n";
//
//    foreach ($rows as $r) {
//        $str .= '"' . $r['code_client'] . '";"' . $r['code_compta'] . '";"' . $r['nom'] . '"' . "\n";
//    }
//
//    if (!file_put_contents(DOL_DATA_ROOT . '/bimpcore/clients_sans_commercial.csv', $str)) {
//        echo 'Echec de la création du fichier CSV "clients_sans_commercial.csv"';
//    } else {
//        $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('clients_sans_commercial.csv');
//        echo '<script>';
//        echo 'window.open(\'' . $url . '\')';
//        echo '</script>';
//    }
//}

// Clients plusieurs commerciaux: 
$sql = 'SELECT s.nom, s.code_client, s.code_compta';
$sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe s';
$sql .= ' WHERE s.client IN (1,2,3)';
$sql .= ' AND (SELECT COUNT(sc.rowid) FROM llx_societe_commerciaux sc WHERE sc.fk_soc = s.rowid) > 1';

$rows = $bdb->executeS($sql, 'array');

if (is_null($rows)) {
    echo $bdb->db->lasterror() . '<br/><br/>';
} else {
    $str = '"Code client";"Code comptable";"Nom"' . "\n";

    foreach ($rows as $r) {
        $str .= '"' . $r['code_client'] . '";"' . $r['code_compta'] . '";"' . $r['nom'] . '"' . "\n";
    }

    if (!file_put_contents(DOL_DATA_ROOT . '/bimpcore/clients_plusieurs_commerciaux.csv', $str)) {
        echo 'Echec de la création du fichier CSV "clients_plusieurs_commerciaux.csv"';
    } else {
        $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('clients_plusieurs_commerciaux.csv');
        echo '<script>';
        echo 'window.open(\'' . $url . '\')';
        echo '</script>';
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();