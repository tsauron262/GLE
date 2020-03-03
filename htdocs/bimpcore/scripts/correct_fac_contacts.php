<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CORRECTION CONTACTS FACTURES', 0, 0, array(), array());

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

if (!(int) BimPTools::getValue('exec', 0)) {
    echo 'Corrige les contacts absents dans les avoirs SAV à partir des factures sources <br/>';

    $path = pathinfo(__FILE__);
    echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1" class="btn btn-default">';
    echo 'Lancer';
    echo '</a>';
    exit;
}

$bdb = new BimpDb($db);

$sql = 'SELECT f.rowid as id_fac, sav.id as id_sav, sav.id_facture as id_fac_src FROM ' . MAIN_DB_PREFIX . 'facture f';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bs_sav sav ON sav.id_facture_avoir = f.rowid';
$sql .= ' WHERE f.datec > \'2019-06-30\'';
$sql .= ' AND (SELECT COUNT(ec.fk_socpeople) FROM ' . MAIN_DB_PREFIX . 'element_contact ec';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_type_contact tc ON tc.rowid = ec.fk_c_type_contact';
$sql .= ' WHERE tc.element = \'facture\'';
$sql .= ' AND tc.source = \'internal\'';
$sql .= ' AND tc.code = \'SALESREPFOLL\'';
$sql .= ' AND ec.element_id = f.rowid) = 0';
$sql .= ' AND f.total_ttc != 0';
$sql .= ' AND f.type IN (0,2)';


$rows = $bdb->executeS($sql, 'array');

if (is_array($rows)) {
    foreach ($rows as $r) {

        $fac = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['id_fac']);

        if (BimpObject::objectLoaded($fac)) {
            if ($fac->getData('type') === Facture::TYPE_CREDIT_NOTE) {
                if (!(int) $fac->getData('fk_facture_source') && (int) $r['id_fac_src']) {
                    $fac->updateField('fk_facture_source', (int) $r['id_fac_src']);
                }

                if ((int) $fac->getData('fk_facture_source')) {
                    $src = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $fac->getData('fk_facture_source'));
                    if (BimpObject::objectLoaded($fac)) {
                        echo 'COPIE CONTACT POUR FAC #' . $fac->id . ': ';
                        $errors = array();
                        $fac->copyContactsFromOrigin($src, $errors);

                        if (count($errors)) {
                            echo BimpRender::renderAlerts($errors);
                        } else {
                            echo 'OK';
                        }
                        echo '<br/>';
                    }
                }
            }
        }
    }
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();