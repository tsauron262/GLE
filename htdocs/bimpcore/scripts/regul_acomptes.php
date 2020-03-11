<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
error_reporting(E_ERROR);

require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'REGUL ACOMPTES', 0, 0, array(), array());

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

$sql = 'SELECT f.rowid as id_fac, fl.rowid as id_line, s.id as id_sav, s.id_discount, sr.amount_ttc as discount_amount, fl.total_ttc as line_amount, sr.fk_facture as disc_id_fac, sr.fk_facture_line as disc_id_fac_line';
$sql .= ' FROM llx_facturedet fl, llx_facture f, llx_bs_sav s, llx_societe_remise_except sr';
$sql .= ' WHERE f.rowid = fl.fk_facture AND fl.description LIKE \'Acompte%\' AND IFNULL(fl.fk_remise_except, 0) <= 0 AND f.type IN(0,2) AND f.fk_statut IN (0,1,2) AND (s.id_facture = f.rowid OR s.id_facture_avoir = f.rowid)';
$sql .= ' AND sr.rowid = s.id_discount';
$sql .= ' ORDER BY s.date_create DESC';

$rows = $bdb->executeS($sql, 'array');

BimpObject::loadClass('bimpcore', 'Bimp_Societe');

foreach ($rows as $r) {

    // Check montant identiques: 
    if (round((float) $r['discount_amount'], 2) !== round(((float) $r['line_amount'] * -1), 2)) {
        continue;
    } else {
//        check remise consommée: 
        if ((int) $r['disc_id_fac']) {
            continue;
        } else {
            $use_label = Bimp_Societe::getDiscountUsedLabel((int) $r['id_discount'], false);

            if ($use_label) {
                echo 'SAV #' . $r['id_sav'] . ' - FAC #' . $r['id_fac'] . ' - LIGNE #' . $r['id_line'] . ' - REMISE #' . $r['id_discount'] . ': ';
                echo 'Remise ' . str_replace('Ajouté', 'ajoutée', $use_label);
                echo '<br/>';
            }
        }

        //    echo 'SAV #' . $r['id_sav'] . ' - FAC #' . $r['id_fac'] . ' - LIGNE #' . $r['id_line'] . ': ';
        //    echo '<br/>';
    }
}

exit;


//$sql = 'SELECT fl.rowid FROM llx_facturedet';
//$sql .= ' LEFT JOIN llx_facture f ON f.rowid = fl.fk_facture';
//$sql .= ' LEFT JOIN llx_sav s ON s.id_facture = fl.fk_facture';
//$sql .= ' WHERE fl.'
//$select_remise = 'SELECT COUNT(r1.rowid) FROM llx_societe_remise_except r1';
//$select_remise .= ' WHERE r1.fk_facture_source = fa.rowid';
//
//$select_acomptes_paiements = 'SELECT COUNT(r2.rowid) FROM llx_societe_remise_except r2';
//$select_acomptes_paiements .= ' LEFT JOIN llx_facture f ON f.rowid = r2.fk_facture';
//$select_acomptes_paiements .= ' WHERE r2.fk_facture_source = fa.rowid AND IFNULL(r2.fk_facture,0) > 0';
//$select_acomptes_paiements .= ' AND f.fk_statut IN (0,1,2)';
//
//$select_acomptes_nofac = 'SELECT COUNT(r3.rowid) FROM llx_societe_remise_except r3';
//$select_acomptes_nofac .= ' WHERE r3.fk_facture_source = fa.rowid AND (r3.fk_facture = 0 OR r3.fk_facture IS NULL)';
//$select_acomptes_nofac .= ' AND (';
//$select_acomptes_nofac .= '(r3.fk_facture_line <= 0 OR r3.fk_facture_line IS NULL) OR (r3.fk_facture_line > 0 AND (SELECT lf.fk_statut FROM llx_facturedet l LEFT JOIN llx_facture lf ON lf.rowid = l.fk_facture WHERE l.rowid = r3.fk_facture_line) NOT IN (1,2))';
//$select_acomptes_nofac .= ')';
//
//$sql = 'SELECT fa.rowid as id_acompte FROM llx_facture fa';
//$sql .= ' WHERE fa.datef < \'2019-07-01\' AND fa.type = 3 AND fa.fk_statut IN (1,2)';
//$sql .= ' AND (';
//$sql .= '(' . $select_remise . ') = 0';
//$sql .= ' OR (' . $select_acomptes_paiements . ') > 0';
//$sql .= ' OR (' . $select_acomptes_nofac . ') > 0';
//$sql .= ')';
//
//echo $sql;
//exit;
//
//if (!(int) BimPTools::getValue('exec', 0)) {
//    echo 'Desc <br/>';
//
//    if (is_array($rows) && count($rows)) {
//        echo count($rows) . ' élément(s) à traiter <br/><br/>';
//
//        $path = pathinfo(__FILE__);
//        echo ' <a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1&test=1" class="btn btn-default">';
//        echo 'Test';
//        echo '</a>';
//        echo ' <a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1&test_one=1" class="btn btn-default">';
//        echo 'Executer une entrée';
//        echo '</a>';
//        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1" class="btn btn-default">';
//        echo 'Tout éxécuter';
//        echo '</a>';
//    }
//
//    echo BimpRender::renderAlerts('Aucun élément à traiter', 'info');
//    exit;
//}
//
//$test = (int) BimpTools::getValue('test', 0);
//$test_one = (int) BimpTools::getValue('test_one', 0);
//
//foreach ($rowd as $r) {
//    if ($test_one) {
//        break;
//    }
//}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
