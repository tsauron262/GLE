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

//correctSavDiscounts($bdb);
//correctSavPropalDiscounts($bdb);
//correctAcomptesFacs($bdb, 'all');
//correctAcomptesFacs($bdb, 'not_converted');
//correctAcomptesFacs($bdb, 'paiements');
//correctAcomptesFacs($bdb, 'no_fac');
//correctAcomptesFacs($bdb, 'in_lines');
AcomptesFile($bdb);

function correctSavDiscounts($bdb)
{
    $sql = 'SELECT f.rowid as id_fac, f.facnumber, f.datec as date_fac, fl.rowid as id_line, s.id as id_sav, s.id_discount, s.date_create as date_sav, sr.amount_ttc as discount_amount, fl.total_ttc as line_amount, sr.fk_facture as disc_id_fac, sr.fk_facture_line as disc_id_fac_line';
    $sql .= ' FROM llx_facturedet fl, llx_facture f, llx_bs_sav s, llx_societe_remise_except sr';
    $sql .= ' WHERE f.rowid = fl.fk_facture AND fl.description LIKE \'Acompte%\' AND IFNULL(fl.fk_remise_except, 0) <= 0 AND f.type IN(0,2) AND f.fk_statut IN (0,1,2) AND (s.id_facture = f.rowid OR s.id_facture_avoir = f.rowid)';
    $sql .= ' AND sr.rowid = s.id_discount';
    $sql .= ' ORDER BY s.date_create DESC';

    $rows = $bdb->executeS($sql, 'array');

    BimpObject::loadClass('bimpcore', 'Bimp_Societe');

//    $factures = array();
    $fileName = 'remises_corrected.txt';
    $file = DOL_DATA_ROOT . '/bimpcore/' . $fileName;
    $hFile = fopen($file, 'a');

    foreach ($rows as $r) {
        BimpCache::$cache = array();
//        $dt_fac = new DateTime($r['date_fac']);
//        $dt_sav = new DateTime($r['date_sav']);
        // Check montant identiques: 
        if (round((float) $r['discount_amount'], 2) !== round(((float) $r['line_amount'] * -1), 2)) {
            continue;
        } else {
//        check remise consommée: 
            if ((int) $r['disc_id_fac']) {
                continue;
            } elseif ((int) $r['disc_id_fac_line']) {
                $sql = 'SELECT f.rowid as id_fac, f.facnumber, f.fk_statut FROM llx_facture f, llx_facturedet fl WHERE fl.fk_facture = f.rowid AND fl.rowid = ' . (int) $r['disc_id_fac_line'];
                $res = $bdb->executeS($sql, 'array');

                if (isset($res[0])) {
                    if ((int) $res[0]['id_fac'] !== (int) $r['id_fac'] && in_array((int) $res[0]['fk_statut'], array(0, 1, 2))) {
//                        $factures[] = 'SAV #' . $r['id_sav'] . ' (' . $dt_sav->format('d / m / Y') . ') - FAC #' . $r['id_fac'] . ' - ' . $r['facnumber'] . ' (' . $dt_fac->format('d / m / Y') . ') - LIGNE #' . $r['id_line'] . ' - REMISE #' . $r['id_discount'] . ': AJOUTEE A LA FACTURE #' . $res[0]['id_fac'] . ' ' . $res[0]['facnumber'] . ' (statut: ' . $res[0]['fk_statut'] . ')';
                        continue;
                    }
                }
            }

            $sql = 'SELECT fl.fk_facture, f.facnumber, f.fk_statut FROM llx_facturedet fl, llx_facture f WHERE fl.fk_remise_except = ' . (int) $r['id_discount'] . ' AND f.rowid = fl.fk_facture AND f.fk_statut IN (0,1,2)';
            $facs = $bdb->executeS($sql, 'array');
            if (is_array($facs) && !empty($facs)) {
                foreach ($facs as $f) {
//                    $factures[] = 'SAV #' . $r['id_sav'] . ' (' . $dt_sav->format('d / m / Y') . ') - FAC #' . $r['id_fac'] . ' - ' . $r['facnumber'] . ' (' . $dt_fac->format('d / m / Y') . ') - LIGNE #' . $r['id_line'] . ' - REMISE #' . $r['id_discount'] . ': AJOUTEE A LA FACTURE #' . $f['fk_facture'] . ' ' . $f['facnumber'] . ' (statut: ' . $f['fk_statut'] . ')';
                }
                continue;
            }

            // C'est OK on fait le transfert: 
//            echo 'SAV ' . $r['id_sav'] . ' (' . $dt_sav->format('d / m / Y') . ') ' . ' - FAC #' . $r['id_fac'] . ' - ' . $r['facnumber'] . ' (' . $dt_fac->format('d / m / Y') . ') - LIGNE #' . $r['id_line'] . '<br/>'; //. ': ';
            echo 'SAV ' . $r['id_sav'] . ' - FAC #' . $r['id_fac'] . ' - LIGNE #' . $r['id_line'] . ': ';
            if ($bdb->update('facturedet', array(
                        'fk_remise_except' => (int) $r['id_discount']
                            ), 'rowid = ' . (int) $r['id_line']) <= 0) {
                echo '<span class="danger">[ECHEC] - ' . $bdb->db->lasterror() . '</span><br/>';
            } elseif ($bdb->update('societe_remise_except', array(
                        'fk_facture'      => 0,
                        'fk_facture_line' => (int) $r['id_line']
                            ), 'rowid = ' . (int) $r['id_discount']) <= 0) {
                echo '<span class="danger">[ECHEC] - ' . $bdb->db->lasterror() . '</span><br/>';
            } else {
                echo ' OK';
//                echo '<span class="success">OK</span>';
                fwrite($hFile, $r['id_line'] . '-' . $r['id_discount'] . ';');
            }
            echo '<br/>';
        }

        //    echo 'SAV #' . $r['id_sav'] . ' - FAC #' . $r['id_fac'] . ' - LIGNE #' . $r['id_line'] . ': ';
        //    echo '<br/>';
    }

    fclose($hFile);

    if (file_exists($file)) {
        $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . urlencode($fileName);
        echo '<script>';
        echo 'window.open(\'' . $url . '\')';
        echo '</script>';
    } else {
        echo 'ECHEC DE LA CREATION DU FICHIER <br/>';
    }
//    if (!empty($factures)) {
//        echo count($factures) . ' Remises ajoutées en tant que lignes à des factures: <br/><br/>';
//
//        foreach ($factures as $fac) {
//            echo $fac . '<br/>';
//        }
//        echo '<br/><br/>';
//    }
}

function correctSavPropalDiscounts($bdb)
{
    $sql = 'SELECT p.rowid as id_propal, p.ref as ref_propal, p.datec as date_propal, pl.rowid as id_line, s.id as id_sav, s.date_create as date_sav, s.id_discount, sr.amount_ttc as discount_amount, pl.total_ttc as line_amount, sr.fk_facture as disc_id_fac, sr.fk_facture_line as disc_id_fac_line';
    $sql .= ' FROM llx_propaldet pl, llx_propal p, llx_bs_sav s, llx_societe_remise_except sr';
    $sql .= ' WHERE p.rowid = pl.fk_propal AND p.rowid = s.id_propal AND pl.description LIKE \'Acompte%\' AND IFNULL(pl.fk_remise_except, 0) <= 0 AND s.id_facture = 0 AND s.id_facture_avoir = 0 AND s.status < 999';
    $sql .= ' AND sr.rowid = s.id_discount';
    $sql .= ' ORDER BY s.date_create DESC';

    $rows = $bdb->executeS($sql, 'array');

    BimpObject::loadClass('bimpcore', 'Bimp_Societe');

    $factures = array();

    foreach ($rows as $r) {
        BimpCache::$cache = array();

        // Check montant identiques: 
        if (round((float) $r['discount_amount'], 2) !== round(((float) $r['line_amount'] * -1), 2)) {
            continue;
        } else {
//        check remise consommée: 
            if ((int) $r['disc_id_fac']) {
                continue;
            } elseif ((int) $r['disc_id_fac_line']) {
                $sql = 'SELECT f.rowid as id_fac, f.facnumber, f.fk_statut FROM llx_facture f, llx_facturedet fl WHERE fl.fk_facture = f.rowid AND fl.rowid = ' . (int) $r['disc_id_fac_line'];
                $res = $bdb->executeS($sql, 'array');

                if (isset($res[0])) {
                    if ((int) $res[0]['id_fac'] !== (int) $r['id_fac'] && in_array((int) $res[0]['fk_statut'], array(0, 1, 2))) {
                        $factures[] = 'SAV #' . $r['id_sav'] . ' - FAC #' . $r['id_fac'] . ' - LIGNE #' . $r['id_line'] . ' - REMISE #' . $r['id_discount'] . ': AJOUTEE A LA FACTURE #' . $res[0]['id_fac'] . ' ' . $res[0]['facnumber'] . ' (statut: ' . $res[0]['fk_statut'] . ')';
                        continue;
                    }
                }
            }

            $sql = 'SELECT fl.fk_facture, f.facnumber, f.fk_statut FROM llx_facturedet fl, llx_facture f WHERE fl.fk_remise_except = ' . (int) $r['id_discount'] . ' AND f.rowid = fl.fk_facture AND f.fk_statut IN (0,1,2)';
            $facs = $bdb->executeS($sql, 'array');
            if (is_array($facs) && !empty($facs)) {
                foreach ($facs as $f) {
                    $factures[] = 'SAV #' . $r['id_sav'] . ' - FAC #' . $r['id_fac'] . ' - LIGNE #' . $r['id_line'] . ' - REMISE #' . $r['id_discount'] . ': AJOUTEE A LA FACTURE #' . $f['fk_facture'] . ' ' . $f['facnumber'] . ' (statut: ' . $f['fk_statut'] . ')';
                }
                continue;
            }

            // C'est OK on fait le transfert: 

            echo 'SAV #' . $r['id_sav'] . ' - PROPAL #' . $r['id_propal'] . ' - LIGNE #' . $r['id_line'] . ': ';
            if ($bdb->update('propaldet', array(
                        'fk_remise_except' => (int) $r['id_discount']
                            ), 'rowid = ' . (int) $r['id_line']) <= 0) {
                echo '<span class="danger">[ECHEC] - ' . $bdb->db->lasterror() . '</span><br/>';
            } else {
                echo '<span class="success">OK</span>';
            }
            echo '<br/>';
        }
    }

    if (!empty($factures)) {
        echo count($factures) . ' Remises ajoutées en tant que lignes à des factures: <br/><br/>';

        foreach ($factures as $fac) {
            echo $fac . '<br/>';
        }
        echo '<br/><br/>';
    }
}

function correctAcomptesFacs(BimpDb $bdb, $type = '', $sav_only = false)
{
    global $user;

    $ref_prefixe = 'TEST';

    $select_remise = 'SELECT COUNT(r1.rowid) FROM llx_societe_remise_except r1';
    $select_remise .= ' WHERE r1.fk_facture_source = fa.rowid';

    $select_acomptes_paiements = 'SELECT COUNT(r2.rowid) FROM llx_societe_remise_except r2';
    $select_acomptes_paiements .= ' LEFT JOIN llx_facture f ON f.rowid = r2.fk_facture';
    $select_acomptes_paiements .= ' WHERE r2.fk_facture_source = fa.rowid AND IFNULL(r2.fk_facture,0) > 0';
    $select_acomptes_paiements .= ' AND f.datef >= \'2019-07-01\'';

    $select_acomptes_nofac = 'SELECT COUNT(r3.rowid) FROM llx_societe_remise_except r3';
    $select_acomptes_nofac .= ' WHERE r3.fk_facture_source = fa.rowid AND (IFNULL(r3.fk_facture, 0) <= 0)';
    $select_acomptes_nofac .= ' AND (';
    $select_acomptes_nofac .= 'IFNULL(r3.fk_facture_line,0) <= 0 OR (r3.fk_facture_line > 0 AND (SELECT lf.fk_statut FROM llx_facturedet l LEFT JOIN llx_facture lf ON lf.rowid = l.fk_facture WHERE l.rowid = r3.fk_facture_line) = 0)';
    $select_acomptes_nofac .= ')';

    $select_in_lines = 'SELECT COUNT(r4.rowid) FROM llx_societe_remise_except r4';
    $select_in_lines .= ' WHERE r4.fk_facture_source = fa.rowid';
    $select_in_lines .= ' AND (';
    $select_in_lines .= '(IFNULL(r4.fk_facture_line, 0) > 0 AND (SELECT COUNT(f.rowid) FROM llx_facture f LEFT JOIN llx_facturedet fl ON fl.fk_facture = f.rowid WHERE fl.rowid = r4.fk_facture_line AND f.fk_statut > 0 AND f.datef >= \'2019-07-01\') > 0)';
    $select_in_lines .= ' OR ';
    $select_in_lines .= '(SELECT COUNT(fl.rowid) FROM llx_facturedet fl LEFT JOIN llx_facture f ON f.rowid = fl.fk_facture WHERE fl.fk_remise_except = r4.rowid AND f.fk_statut > 0 AND f.datef >= \'2019-07-01\') > 0';
    $select_in_lines .= ')';

    $select_avoir .= 'SELECT COUNT(avoir.rowid) FROM llx_facture avoir WHERE avoir.facnumber = CONCAT(\'' . $ref_prefixe . '\', fa.rowid)';

    $sql = 'SELECT fa.rowid as id_acompte, fa.facnumber as ref_acompte, fa.datef as date_acompte, soc.code_client, soc.code_compta, fa.total as total_ht, fa.total_ttc';
    $sql .= ' FROM llx_facture fa, llx_societe soc';

    if ($sav_only) {
        $sql .= ', llx_bs_sav sav';
    }

    $sql .= ' WHERE fa.type = 3 AND fa.fk_statut IN (1,2) AND soc.rowid = fa.fk_soc';

    if ($sav_only) {
        $sql .= 'AND sav.id_facture_acompte = fa.rowid';
    }

    $sql .= ' AND (' . $select_avoir . ') = 0'; // Pour ne pas traiter 2 fois une même facture d'acompte. 

    if ($sav_only) {
        $sql .= 'AND sav.status < 999';
    }

    $sql .= ' AND (';

    // Filtres sur les acomptes créés avant le 1er Juillet 2019: 
    if (in_array($type, array('all', 'not_converted', 'paiements', 'no_fac'))) {
        $sql .= '(fa.datef < \'2019-07-01\' AND (';
        switch ($type) {
            case 'all':
                $sql .= '(' . $select_remise . ') = 0';
                $sql .= ' OR (' . $select_acomptes_paiements . ') > 0';
                $sql .= ' OR (' . $select_acomptes_nofac . ') > 0';
                break;

            case 'not_converted':
                // Factures d'acomptes non converties en remise
                $sql .= '(' . $select_remise . ') = 0';
                break;

            case 'paiements':
                // Acomptes consommés en tant que paiement: 
                $sql .= '(' . $select_acomptes_paiements . ') > 0';
                break;

            case 'no_fac':
                // Acomptes non consommés: 
                $sql .= '(' . $select_acomptes_nofac . ') > 0';
                break;
        }
        $sql .= '))';

        if ($type === 'all') {
            $sql .= ' OR ';
        }
    }

    // Filtres sur les acomptes créés après le 1er Juillet 2019: 
    if (in_array($type, array('all', 'in_lines'))) {
        $sql .= '(fa.datef > \'2019-06-30\' AND (';

        // Acomptes consommés en tant que ligne de facture:
        $sql .= '(' . $select_in_lines . ') > 0';
        $sql .= '))';
    }

    $sql .= ')';



    if (!(int) BimpTools::getValue('exec', 0)) {
        echo '<br/><br/>' . $sql . '<br/><br/>';
    }

    $rows = $bdb->executeS($sql, 'array');

//    echo '<pre>';
//    print_r($rows);
//    exit;


    if (is_array($rows)) {
        if ((int) BimpTools::getValue('exec', 0)) {
            echo 'VIRER PROTECTION';
            exit;
            foreach ($rows as $r) {
                $acompte = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['id_acompte']);

                if (!BimpObject::objectLoaded($acompte)) {
                    echo BimpRender::renderAlerts('L\'acompte #' . $r['id_acompte'] . ' n\'existe pas');
                } else {
                    echo 'Acompte #' . $r['id_acompte'] . ': ';
                    $errors = array();
                    $warnings = array();
                    $avoir = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', array(
                                'type'       => 0,
                                'fk_soc'     => (int) $acompte->getData('fk_soc'),
                                'entrepot'   => (int) $acompte->getData('entrepot'),
                                'contact_id' => (int) $acompte->getData('contact_id'),
                                'fk_account' => (int) $acompte->getData('fk_account')
                                    ), true, $errors, $warnings);

                    if (!BimpObject::objectLoaded($avoir)) {
                        echo '<span class="danger">ECHEC</span>';
                        if (count($errors)) {
                            echo BimpRender::renderAlerts($errors);
                        }
                        if (count($warnings)) {
                            echo BimpRender::renderAlerts($warnings, 'warning');
                        }
                    } else {
                        // Copie des contacts: 
                        $warnings = array();
                        $avoir->copyContactsFromOrigin($acompte, $warnings);
                        if (count($warnings)) {
                            echo BimpRender::renderAlerts($warnings, 'warning');
                        }

                        // Copie des lignes: 
                        $line_errors = $avoir->createLinesFromOrigin($acompte, array(
                            'inverse_prices' => true,
                            'pa_editable'    => false
                        ));

                        if (count($line_errors)) {
                            echo BimpRender::renderAlerts($line_errors);
                        } else {
                            echo '<span class="success">Création OK</span>';
                            // Validation de l'avoir: 
                            $ref = ''; // todo : déterminer ref... 
                            if ($avoir->dol_object->validate($user, $ref, 0, 0) <= 0) {
                                echo BimpRender::renderAlerts(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($avoir->dol_object), 'Echec de la validation de l\'avoir'), 'danger');
                            } else {
                                echo ' - <span class="success">Validation OK</span>';
                            }
                        }
                    }
                    echo '<br/>';

                    if (!(int) BimpTools::getValue('all', 0)) {
                        break;
                    }
                }
            }
        } else {
            switch ($type) {
                case 'all':
                    echo '<h3>Toutes les factures d\'acompte à corriger</h3>';
                    break;

                case 'all_before':
                    echo '<h3>Toutes les factures d\'acompte créées avant le 1er Juillet 2019 à corriger</h3>';
                    break;

                case 'not_converted':
                    echo '<h3>Factures d\'acompte créées avant le 1er Juillet 2019 non converties en remises</h3>';
                    break;

                case 'paiements':
                    echo '<h3>Factures d\'acompte créées avant le 1er Juillet 2019 dont remise consommée en paiement de facture</h3>';
                    break;

                case 'no_fac':
                    echo '<h3>Factures d\'acompte créées avant le 1er Juillet 2019 non consommées</h3>';
                    break;

                case 'in_lines':
                    echo '<h3>Factures d\'acompte créées après le 1er Juillet 2019 mais consommées en tant que ligne de facture</h3>';
                    break;
            }

            echo count($rows) . ' Factures d\'acompte à traiter <br/><br/>';
            foreach ($rows as $r) {
                echo 'Acompte ' . $r['ref_acompte'] . ' - Client: ' . $r['code_client'] . ' (Code comptable: ' . $r['code_compta'] . ') - Montants: ';
                echo BimpTools::displayMoneyValue((float) $r['total_ht'], '') . ' € HT, ';
                echo BimpTools::displayMoneyValue((float) $r['total_ttc'], '') . ' € TTC <br/>';
            }
        }
    }

    return $rows;
}

function AcomptesFile($bdb)
{
    BimpCore::loadPhpExcel();
    $excel = new PHPExcel();

    $fl = true;

    foreach (array(
'not_converted' => 'Acomptes non convertis',
 'paiements'     => 'Acomptes consommés (paiement)',
 'no_fac'        => 'Acomptes non consommés',
 'in_lines'      => 'Acomptes consommés (ligne)'
    ) as $type => $title) {
        $rows = correctAcomptesFacs($bdb, $type);

        if (!$fl) {
            $sheet = $excel->createSheet();
        } else {
            $sheet = $excel->getActiveSheet();
            $fl = false;
        }

        $sheet->setTitle($title);

        $sheet->setCellValueByColumnAndRow(0, 1, 'Réf acompte');
        $sheet->setCellValueByColumnAndRow(1, 1, 'Date acompte');
        $sheet->setCellValueByColumnAndRow(2, 1, 'Ref client');
        $sheet->setCellValueByColumnAndRow(3, 1, 'Code compta client');
        $sheet->setCellValueByColumnAndRow(4, 1, 'Montant HT');
        $sheet->setCellValueByColumnAndRow(5, 1, 'Montant TTC');

        $row = 2;

        foreach ($rows as $r) {
            $dt_acompte = new DateTime($r['date_acompte']);
            $sheet->setCellValueByColumnAndRow(0, $row, $r['ref_acompte']);
            $sheet->setCellValueByColumnAndRow(1, $row, $dt_acompte->format('d / m / Y'));
            $sheet->setCellValueByColumnAndRow(2, $row, $r['code_client']);
            $sheet->setCellValueByColumnAndRow(3, $row, $r['code_compta']);
            $sheet->setCellValueByColumnAndRow(4, $row, $r['total_ht']);
            $sheet->setCellValueByColumnAndRow(5, $row, $r['total_ttc']);
            $row++;
        }
    }

    $file_name = 'regul_compta_acomptes';
    $file_path = DOL_DATA_ROOT . '/bimpcore/lists_excel/' . $file_name . '.xlsx';

    $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $writer->save($file_path);

    $url = DOL_URL_ROOT . '/document.php?modulepart=bimpcore&file=' . htmlentities('lists_excel/' . $file_name . '.xlsx');

    echo '<script>';
    echo 'window.open(\'' . $url . '\')';
    echo '</script>';
}
echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
