<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

top_htmlhead('', 'CHECK FACTURES', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

$factures = BimpCache::getBimpObjectList('bimpcommercial', 'Bimp_Facture', array(
            'datec' => array(
                'operator' => '>',
                'value'    => '2019-06-30'
            )
        ));

$comm_instance = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');
$asso = new BimpAssociation($comm_instance, 'factures');

foreach ($factures as $id_facture) {
    $fac = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);

    if (BimpObject::objectLoaded($fac)) {
        $items = BimpTools::getDolObjectLinkedObjectsListByTypes($fac->dol_object, $bdb);
        $new_items = array();

        if (isset($items['facture'])) {
            foreach ($items['facture'] as $id_fac2) {
                $fac2 = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture', (int) $id_facture);

                if (BimpObject::objectLoaded($fac2)) {
                    $items2 = BimpTools::getDolObjectLinkedObjectsListByTypes($fac2->dol_object, $bdb);
                    foreach ($items2 as $type => $ids) {
                        foreach ($ids as $id) {
                            if ((!isset($items[$type]) || !in_array($id, $items[$type])) &&
                                    (!isset($new_items[$type]) || !in_array((int) $id, $new_items[$type]))) {
                                if (!isset($new_items[$type])) {
                                    $new_items[$type] = array();
                                }

                                $new_items[$type][] = (int) $id;
                            }
                        }
                    }
                }
            }
        }

        if (!empty($new_items)) {
            echo 'FAC ' . $id_facture . ': <br/>';
            foreach ($new_items as $type => $ids) {
                echo $type . '<br/>';
                foreach ($ids as $id) {
                    echo $id . '<br/>';
                    addElementElement('facture', $type, $fac->id, $id);

                    if ($type === 'commande') {
                        $asso->addObjectAssociation((int) $fac->id, (int) $id);
                    }
                }
            }
            echo '<br/>';
        }
    }

    unset($fac);
    BimpCache::$cache = array();
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();


