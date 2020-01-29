<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

//llxHeader();

echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
//echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/ticket.css' . '"/>';
echo '<script src="/test2/includes/jquery/js/jquery.min.js?version=6.0.4" type="text/javascript"></script>';
echo '</head>';

echo '<body>';

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmargin.class.php';

global $db;
$bdb = new BimpDb($db);
$formmargin = new FormMargin($db);

BimpTools::loadDolClass('compta/facture', 'facture');

$where = '`ref` LIKE \'FAS1901-%\' AND `datec` >= \'2019-01-17 00:00:00\' AND `datec` < \'2019-01-18 00:00:00\'';
$rows = $bdb->getRows('facture', $where, null, 'array', array(
    'rowid', 'ref', 'fk_soc', 'total'
        ));

// Afficher marge pour process. 
// Grouper par client. 

$toDelete = array(
    0 => array(
        'label' => 'Aucun client valide',
        'rows'  => array()
    )
);
$toProcess = array(
    0 => array(
        'label' => 'Aucun client valide',
        'rows'  => array()
    )
);

$nDelete = 0;
$nProcess = 0;

foreach ($rows as $r) {
    $sav_id = (int) $bdb->getValue('bs_sav', 'id', '`id_facture` = ' . (int) $r['rowid']);
    if (!$sav_id) {
        if ((float) $r['total'] >= -0.01 && (float) $r['total'] <= 0.01) {
            if (!isset($toDelete[(int) $r['fk_soc']])) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $r['fk_soc']);
                if (BimpObject::objectLoaded($soc)) {
                    $toDelete[(int) $r['fk_soc']] = array(
                        'label' => $soc->getData('code_client') . ' - ' . $soc->getData('nom')
                    );
                } else {
                    $r['fk_soc'] = 0;
                }
            }
            $toDelete[(int) $r['fk_soc']]['rows'][] = $r;
            $nDelete++;
        } else {
            if (!isset($toProcess[(int) $r['fk_soc']])) {
                $soc = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Client', (int) $r['fk_soc']);
                if (BimpObject::objectLoaded($soc)) {
                    $toProcess[(int) $r['fk_soc']] = array(
                        'label' => $soc->getData('code_client') . ' - ' . $soc->getData('nom')
                    );
                } else {
                    $r['fk_soc'] = 0;
                }
            }
            $toProcess[(int) $r['fk_soc']]['rows'][] = $r;
            $nProcess++;
        }
    }
}

$delete_txt = 'Factures supprimables automatiquement' . "\n";
$delete_txt .= $nDelete . ' factures trouvées' . "\n\n";
$delete_txt .= 'ID - Ref - Total HT';

foreach ($toDelete as $id_soc => $soc_data) {
    if (empty($soc_data['rows'])) {
        continue;
    }
    $delete_txt .= "\n\n" . 'Client: ' . $soc_data['label'] . "\n\n";
    foreach ($soc_data['rows'] as $r) {
        $facture = new Facture($db);
        $facture->fetch((int) $r['rowid']);

        $marge = 0;

        if (BimpObject::objectLoaded($facture)) {
            $margin_infos = $formmargin->getMarginInfosArray($facture);
            $marge = $margin_infos['total_margin'];
        }

        $delete_txt .= ' - ' . $r['rowid'] . ' => ' . $r['ref'] . ': ' . $r['total'] . '  -  MARGE : ' . $marge . "\n";
    }
}

$process_txt = 'Factures à traiter' . "\n";
$process_txt .= $nProcess . ' factures trouvées' . "\n\n";
$process_txt .= 'ID => Ref : Total HT';

foreach ($toProcess as $id_soc => $soc_data) {
    if (empty($soc_data['rows'])) {
        continue;
    }

    $process_txt .= "\n\n" . 'Client: ' . $soc_data['label'] . "\n\n";
    foreach ($soc_data['rows'] as $r) {
        $facture = new Facture($db);
        $facture->fetch((int) $r['rowid']);

        $marge = 0;

        if (BimpObject::objectLoaded($facture)) {
            $margin_infos = $formmargin->getMarginInfosArray($facture);
            $marge = $margin_infos['total_margin'];
        }

        $process_txt .= ' - ' . $r['rowid'] . ' => ' . $r['ref'] . ': ' . $r['total'] . '  -  MARGE : ' . $marge . "\n";
    }
}

file_put_contents(DOL_DATA_ROOT . '/facture_suppr.txt', $delete_txt);
file_put_contents(DOL_DATA_ROOT . '/facture_a_traiter.txt', $process_txt);

echo DOL_DATA_ROOT;

echo '</body></html>';

//llxFooter();
