<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

top_htmlhead('', 'MAJ PA AVOIRS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

set_time_limit(1200);

$rows = file(DOL_DOCUMENT_ROOT . '/bimpcore/equipments_list.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$id_comm_line = 127620;

foreach ($rows as $r) {
    echo ' - ' . $r . ' ';

    $equipement = BimpCache::findBimpObjectInstance('bimpequipment', 'Equipment', array(
                'serial' => $r
    ));

    if (BimpObject::objectLoaded($equipement)) {

        $err = array();

        $reservation = BimpCache::findBimpObjectInstance('bimpreservation', 'BR_Reservation', array(
                    'id_equipment' => (int) $equipement->id,
                    'type'         => 1
        ));

        if (BimpObject::objectLoaded($reservation)) {
            if ((int) $reservation->getData('id_commande_client_line') === (int) $id_comm_line) {
                $reservation->updateField('id_equipment', 0);
                $err = $reservation->setNewStatus(100, 1, 0);
                if (count($err)) {
                    echo 'Echec maj résa<pre>';
                    print_r($err);
                    echo '</pre>';
                } else {
                    echo 'MAJ RESA OK - ';
                }
            }
        } else {
            echo 'AUCUNE RESA TROUVEE - ';
        }

//        if (!count($err)) {
//            $warnings = array();
//            $err = $equipement->delete($warnings, true);
//
//            if (count($err)) {
//                echo 'Echec del eq<pre>';
//                print_r($err);
//                echo '</pre>';
//            } else {
//                echo 'DEL EQ OK';
//            }
//        }
    } else {
        echo 'EQ PAS TROUVE';
    }

    echo '<br/>';
}

echo 'FIN';

echo '</body></html>';

//llxFooter();
