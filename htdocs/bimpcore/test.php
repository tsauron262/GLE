<?php

require_once("../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TESTS', 0, 0, array(), array());

echo '<body style="padding: 30px">';

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

$bdb = BimpCache::getBdb();

//$sql = 'SELECT a.rowid, a.duration, a.date_ouverture, a.date_fin_validite FROM llx_contratdet a';
//$sql .= ' LEFT JOIN llx_contrat c ON c.rowid = a.fk_contrat WHERE c.version = 2 AND a.statut > 0';
//
//$rows = $bdb->executeS($sql, 'array');

//echo '<pre>';
//print_r($rows);
//exit;

//echo $bdb->err();
//$one_day = new DateInterval('P1D');
//
//foreach ($rows as $r) {
//    if (!(int) $r['duration'] || !$r['date_ouverture']) {
//        continue;
//    }
//
//    $dt = new DateTime($r['date_ouverture']);
//    $dt->add(new DateInterval('P' . $r['duration'] . 'M'));
//    $dt->sub($one_day);
//    $date = $dt->format('Y-m-d') . ' 23:59:59';
//
//    if ($date != $r['date_fin_validite']) {
//        $interval = BimpTools::getDatesIntervalData($date, $r['date_fin_validite'], false, false);
//        echo 'MAJ <b>' . $r['date_ouverture'] . ' (' . $r['duration'] . ')</b> ==> ' . $r['date_fin_validite'] . ' => ' . $date . ' : ';
//
//        if ($interval['full_days'] > 0) {
//            echo '<span class="danger">' . $interval['full_days'] . ' jours d\'écart</span><br/>';
//        } else {
//            echo '<span class="success">OK</span><br/>';
//        }
//
//        $bdb->update('contratdet', array(
//            'date_fin_validite' => $date
//                ), 'rowid = ' . $r['rowid']);
////        break;
//    }
//}

//$sql = 'SELECT DISTINCT a.fk_contrat as id FROM llx_contratdet a LEFT JOIN llx_contrat c ON c.rowid = a.fk_contrat WHERE a.line_origin_type = \'commande_line\' AND c.fk_commercial_suivi = 270 GROUP BY a.fk_contrat';
//
//$rows = $bdb->executeS($sql, 'array');
// Emetteur : 10
// Commercial : 11
//foreach ($rows as $r) {
//    echo 'Contrat #' . $r['id'];
//    $fk_soc = (int) $bdb->getValue('contrat', 'fk_soc', 'rowid = ' . $r['id']);
//
//    if ($fk_soc) {
//        $id_comm = (int) $bdb->getValue('societe_commerciaux', 'fk_user', 'fk_soc = ' . $fk_soc, 'rowid');
//
//        if ($id_comm) {
//            echo ' OK';
//            $bdb->update('contrat', array(
//                'fk_commercial_suivi' => $id_comm
//                    ), 'rowid = ' . $r['id'] . ' AND fk_commercial_suivi = 270');
//
//            $bdb->update('element_contact', array(
//                'fk_socpeople' => $id_comm
//                    ), 'element_id = ' . $r['id'] . ' AND fk_c_type_contact = ' . 11);
//            
////            break;
//        } else {
//            echo 'Comm KO';
//        }
//    } else {
//        echo 'SOC KO';
//    }
//    
//    echo '<br/>';
//}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
