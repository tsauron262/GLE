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
$bdb = new BimpDb($db);

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

//$sql = 'SELECT fdet.fk_product as id_product, fdet.qty, fl.id as id_line, fl.linked_object_name, fl.linked_id_object, fl.id_obj as id_facture, av.rowid as id_avoir FROM llx_bimp_facture_line fl
//LEFT JOIN llx_facture f ON f.rowid = fl.id_obj
//LEFT JOIN llx_facturedet fdet ON fdet.rowid = fl.id_line
//LEFT JOIN llx_facture av ON av.fk_facture_source = f.rowid
//WHERE f.type = 0 
//AND fdet.fk_product > 0
//AND av.rowid > 0
//AND fl.linked_object_name = \'contrat_line\' AND fl.linked_id_object > 0
//AND (SELECT COUNT(avl.id) FROM llx_bimp_facture_line avl WHERE avl.linked_id_object = fl.linked_id_object AND avl.id_obj = av.rowid) = 0
//AND fl.id NOT IN (5331348);';
//
//$rows = $bdb->executeS($sql, 'array');
//
//foreach ($rows as $r) {
//    $av_lines = $bdb->getRows('facturedet', 'fk_facture = ' . $r['id_avoir'] . ' AND fk_product = ' . $r['id_product'] . ' AND qty = ' . $r['qty'], null, 'array', array('rowid'));
//
//    if (is_array($av_lines)) {
//        echo $r['id_line'] . ' - ';
//
//        if (count($av_lines) == 1) {
//            if ($bdb->update('bimp_facture_line', array(
//                        'linked_object_name' => $r['linked_object_name'],
//                        'linked_id_object'   => $r['linked_id_object']
//                            ), 'id_line = ' . $av_lines[0]['rowid']) <= 0) {
//                echo 'FAIL : ' . $bdb->err();
//            } else {
//                echo 'OK';
//            }
//        } elseif (count($av_lines) > 1) {
//            echo 'PLUSIEURS AV LINES';
//        } else {
//            echo 'NO AV LINES';
//        }
//        echo '<br/>';
//    } else {
//        echo 'FAIL - ' . $bdb->err();
//    }
//
//
//    $av_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', $id_object);
//}



$sql = 'SELECT avdet.fk_product as id_product, avdet.qty, avl.id as id_line, avl.linked_object_name, avl.linked_id_object, avl.id_obj as id_avoir, nf.rowid as id_new_fac FROM llx_bimp_facture_line avl
LEFT JOIN llx_facture av ON av.rowid = avl.id_obj
LEFT JOIN llx_facturedet avdet ON avdet.rowid = avl.id_line
LEFT JOIN llx_facture nf ON nf.fk_facture_source = av.rowid
WHERE av.type = 2
AND avdet.fk_product > 0
AND nf.rowid > 0
AND avl.linked_object_name = \'contrat_line\' AND avl.linked_id_object > 0
AND (SELECT COUNT(nfl.id) FROM llx_bimp_facture_line nfl WHERE nfl.linked_id_object = avl.linked_id_object AND nfl.id_obj = nf.rowid) = 0;';

$rows = $bdb->executeS($sql, 'array');

echo '<pre>';
print_r($rows);
//exit;

foreach ($rows as $r) {
    $new_fac_lines = $bdb->getRows('facturedet', 'fk_facture = ' . $r['id_new_fac'] . ' AND fk_product = ' . $r['id_product'], null, 'array', array('rowid'));

    if (is_array($new_fac_lines)) {
        echo $r['id_line'] . ' - ';
        
        echo '<pre>';
        print_r($new_fac_lines);
        echo '</pre>';

        if (count($new_fac_lines) == 1) {
            if ($bdb->update('bimp_facture_line', array(
                        'linked_object_name' => $r['linked_object_name'],
                        'linked_id_object'   => $r['linked_id_object']
                            ), 'id_line = ' . $new_fac_lines[0]['rowid']) <= 0) {
                echo 'FAIL : ' . $bdb->err();
            } else {
                echo 'OK';
            }
        } elseif (count($new_fac_lines) > 1) {
            echo 'PLUSIEURS NEW FAC LINES';
        } else {
            echo 'NO NEW FAC LINES';
        }
        echo '<br/>';
    } else {
        echo 'FAIL - ' . $bdb->err();
    }


//    $av_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureLine', $id_object);
}

echo '<br/>FIN';
echo '</body></html>';

//llxFooter();
