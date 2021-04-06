<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'TITRE', 0, 0, array(), array());

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

$sql = 'SELECT count(DISTINCT l.id) as nb, id_obj as id_propal, s.id as id_sav';
$sql .= ' FROM llx_bs_sav_propal_line l';
$sql .= ' LEFT JOIN llx_bs_sav s ON s.id_propal = l.id_obj';
$sql .= ' WHERE s.date_create > \'2018-06-01\' AND l.type IN (1,3)';
$sql .= ' GROUP BY l.id_line';
$sql .= ' HAVING nb > 1';

echo $sql .'<br/><br/>'; 


$rows = $bdb->executeS($sql, 'array');

//echo '<pre>';
//print_r($rows);
//exit;

echo 'Results: ' . count($rows) . '<br/>';

$propals = array();

foreach ($rows as $r) {
    if (!(int) $r['id_propal']) {
        continue;
    }

    if (!isset($propals[(int) $r['id_propal']])) {
        $propals[(int) $r['id_propal']] = (int) $r['id_sav'];
    } elseif ($propals[(int) $r['id_propal']] !== (int) $r['id_sav']) {
        echo '<span class="error">SAV différent (propale #' . $r['id_propal'] . ' - ligne #' . $r['id_bimp_line'] . ')</span >';
    }
}

foreach ($propals as $id_propal => $id_sav) {
    echo 'Propale #' . $id_propal . ' : ';

    if (!$id_sav) {
        echo '<span class="error">Aucun SAV</span>';
    } else {
        echo ' SAV #' . $id_sav;
        $propal = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SavPropal', $id_propal);
        $sav = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', $id_sav);

        if ((int) $sav->getData('id_facture') || (int) $sav->getData('id_facture_avoir')) {
            $total_fac = 0;
            if ((int) $sav->getData('id_facture')) {
                $total_fac += (float) $bdb->getValue('facture', 'total_ttc', 'rowid = ' . (int) $sav->getData('id_facture'));
                echo ' - <span class="warning">Facture #' . $sav->getData('id_facture') . '</span>';
            }

            if ((int) $sav->getData('id_facture_avoir')) {
                $total_fac += (float) $bdb->getValue('facture', 'total_ttc', 'rowid = ' . (int) $sav->getData('id_facture_avoir'));
                echo ' - <span class="warning">Avoir #' . $sav->getData('id_facture_avoir') . '</span>';
            }

            if ((float) $propal->getData('total_ttc') !== $total_fac) {
                echo ' - <span class="error">Montant devis (' . $propal->getData('total_ttc') . ') différent montant facturé (' . $total_fac . ')</span>';
            }
        } elseif($propal->getData('fk_statut') == 0){
            echo ' - <span class="success">Brouillon</span>';
        }
        else {
            echo ' - <span class="success">Aucune facture ni avoir. Total : '.$propal->getData('total_ht').'</span>';
        }
    }

    echo '<br/>';
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
