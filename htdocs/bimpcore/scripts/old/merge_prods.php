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
}

$bdb = new BimpDb($db);

$lines = file(DOL_DOCUMENT_ROOT . '/bimpcore/prods_list.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$prod = BimpObject::getInstance('bimpcore', 'Bimp_Product');
foreach ($lines as $line) {
    $data = explode(':', $line);

    $rows = $bdb->getRows('product', 'ref = \'' . $data[1] . '\' OR ref = \'' . $data[1] . "\n" . '\'', null, 'array', array('rowid', 'ref'));

    if (is_array($rows) && !empty($rows)) {
        $id_keep = 0;
        $id_suppr = 0;

        foreach ($rows as $r) {
            if ($r['ref'] === $data[1]) {
                $id_keep = $r['rowid'];
            } elseif ($r['ref'] === $data[1] . "\n") {
                $id_suppr = $r['rowid'];
            }
        }

        if ($id_keep && $id_suppr) {
            echo $data[1] . ': <br/>';
            echo 'KEEP: ' . $id_keep . '<br/>';
            echo 'SUPPR: ' . $id_suppr . '<br/>';

//            $pDel = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_keep);
//
//            $err = $pDel->delete($w, true);
//
//            if (count($err)) {
//                echo BimpRender::renderAlerts($err, 'FAIL SUPPR');
//            } else {
//                $pUp = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', $id_suppr);
//                $err = $pUp->updateField('ref', $data[1]);
//
//                if (count($err)) {
//                    echo BimpRender::renderAlerts($err, 'FAIL MAJ REF');
//                } else {
//                    echo 'OK';
//                }
//            }

//            $success = '';
//            $res = $prod->setObjectAction('merge', $id_keep, array(
//                'id_kept_product'   => $id_keep,
//                'id_merged_product' => $id_suppr
//                    ), $success);
//            if (isset($res['errors']) && !empty($res['errors'])) {
//                echo BimpRender::renderAlerts($res['errors']);
//            } elseif (!isset($res['errors']) && !empty($res)) {
//                echo BimpRender::renderAlerts($res);
//            } else {
//                echo $success;
//            }

            echo '<br/>';

            continue;
        }
    }

//    echo 'FAIL ' . $data[1] . '<br/>';
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
