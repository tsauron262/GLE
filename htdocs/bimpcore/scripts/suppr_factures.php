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

global $db;
$bdb = new BimpDb($db);

$rows = $bdb->getRows('facture', '`ref` LIKE \'(PROVACS%\'', null, 'array', array('rowid'));

$f = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
BimpTools::loadDolClass('compta/paiement', 'paiement');
BimpTools::loadDolClass('compta/bank', 'account');

if (!is_null($rows) && count($rows)) {
    foreach ($rows as $r) {
        if ($f->fetch((int) $r['rowid'])) {
            $bdb->update('facture', array(
                'fk_statut' => 0
                    ), '`rowid` = ' . (int) $r['rowid']);
            $pmts = $bdb->getRows('paiement_facture', '`fk_facture` = ' . (int) $f->id, null, 'array', array(
                'fk_paiement'
            ));

            $check = true;
            if (!is_null($pmts) && count($pmts)) {
                foreach ($pmts as $pmt) {
                    $p = new Paiement($db);
                    if ($p->fetch((int) $pmt['fk_paiement']) > 0) {
                        if ($p->delete() <= 0) {
                            echo BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($p), 'ECHEC SUPPR PAIEMENT ' . $pmt['fk_paiement']) . '<br/>';
                            $check = false;
                        }
                    }
                    unset($p);
                    $p = null;
                }
            }

            if ($check) {
                $errors = $f->delete(true);
                if (count($errors)) {
                    BimpTools::getMsgFromArray($errors, 'ECHEC SUPPR FAC ' . $r['rowid']) . '<br/>';
                } else {
                    echo 'SUPPR FAC ' . $r['rowid'] . ' OK <br/>';
                }
            }
        } else {
            echo '[FAC INVALIDE]: ' . $r['rowid'] . '<br/>';
        }
    }
} else {
    echo '[AUCUNES FACTURES A SUPPR TROUVEES]';
}

echo '</body></html>';

//llxFooter();
