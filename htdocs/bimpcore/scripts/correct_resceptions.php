<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';

top_htmlhead('', 'DEL EQUIPMENTS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db;
$bdb = new BimpDb($db);

set_time_limit(1200);
ignore_user_abort(TRUE);



// Réparation liste équipements réceptions via liste serials (si équipements déjà créés)
//$line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', 25026); 
//if (BimpObject::objectLoaded($line)) {
//    $receptions = $line->getData('receptions');
//
//    foreach ($receptions as $id_reception => $reception_data) {
//        $equipments = array();
//
//        foreach ($reception_data['serials'] as $serial_data) {
//            echo 'serial ' . $serial_data['serial'] . ': ';
//
//            $eq = BimpCache::findBimpObjectInstance('bimpequipment', 'Equipment', array(
//                        'serial' => $serial_data['serial']
//            ));
//
//            if (BimpObject::objectLoaded($eq)) {
//                $equipments[(int) $eq->id] = array(
//                    'pu_ht'  => $serial_data['pu_ht'],
//                    'tva_tx' => $serial_data['tva_tx']
//                );
//            } else {
//                echo 'pas de eq';
//            }
//            echo '<br/>';
//        }
//
//        $reception_data['equipments'] = $equipments;
//        $receptions[(int) $id_reception] = $reception_data;
//    }
//    
//    $err = $line->updateField('receptions', $receptions);
//
//    if (count($err)) {
//        echo '<pre>';
//        print_r($err);
//        echo '</pre>';
//    } else {
//        echo 'OK';
//    }
//} else {
//    echo 'PAS DE LINE';
//}
// Réparation réservations en attentes pour réceptions: 
$line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFournLine', 25026);

if (BimpObject::objectLoaded($line)) {
    if ($line->getData('linked_object_name') === 'commande_line' && (int) $line->getData('linked_id_object')) {
        $comm_line = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', (int) $line->getData('linked_id_object'));

        if (BimpObject::objectLoaded($comm_line)) {
            $receptions = $line->getData('receptions');

            foreach ($receptions as $id_rec => $rec_data) {
                echo '***** RECEPTION ' . $id_rec . ' *****<br/><br/>';
                foreach ($rec_data['equipments'] as $id_eq => $eq_data) {
                    echo 'Equip. ' . $id_eq . ': ';

                    $res = BimpCache::findBimpObjectInstance('bimpreservation', 'BR_Reservation', array(
                                'type'                    => 1,
                                'id_commande_client_line' => (int) $comm_line->id,
                                'id_equipment'            => $id_eq,
                                'status'                  => 200
                    ));

                    if (BimpObject::objectLoaded($res)) {
                        echo 'RESA DEJA OK';
                    } else {
                        $res = BimpCache::findBimpObjectInstance('bimpreservation', 'BR_Reservation', array(
                                    'type'                    => 1,
                                    'id_commande_client_line' => (int) $comm_line->id,
                                    'status'                  => 100
                        ));

                        if (BimpObject::objectLoaded($res)) {
                            $err = $res->setNewStatus(200, 1, $id_eq);

                            if (count($err)) {
                                echo 'ECHEC MAJ RESA<pre>';
                                print_r($err);
                                echo '</pre>';
                            } else {
                                echo 'MAJ RESA OK';
                            }
                        } else {
                            echo 'PAS DE RESA EN ATTENTE TROUVEE';
                        }
                    }


                    echo '<br/>';
                }
            }
        } else {
            echo 'COMMANDE LINE INVALIDE';
        }
    } else {
        echo 'PAS DE COMMANDE LINE';
    }
} else {
    echo 'PA DE LINE';
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
