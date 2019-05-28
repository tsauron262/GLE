<?php

define('NOLOGIN', '1');

require_once("../../main.inc.php");
require_once __DIR__ . '/../Bimp_Lib.php';

ini_set('display_errors', 1);
set_time_limit(1200);
//llxHeader();

echo '<!DOCTYPE html>';
echo '<html lang="fr">';

echo '<head>';
echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/bimpcore/views/css/bimpcore.css' . '"/>';
echo '<script src="/test2/includes/jquery/js/jquery.min.js?version=6.0.4" type="text/javascript"></script>';
echo '</head>';

echo '<body>';

global $db;
$bdb = new BimpDb($db);
$factures_set = array();

$full = BimpTools::getValue('full', 0);
$id_commande = (int) BimpTools::getValue('id_commande', 0);

// Traitement des lignes d'expédition (réservations): 

$where = '`converted` = 0';

if ($id_commande) {
    $filters['id_commande_client'] = $id_commande;
    $where .= ' AND `id_commande_client` = ' . $id_commande;
}

$rows = $bdb->getRows('br_reservation_shipment', $where, null, 'array', null, 'id', 'asc');

foreach ($rows as $r) {
    echo 'Traitement reservation_shipment ' . $r['id'] . ': ';

    if ((int) $r['id_commande_client']) {
        $check_lines = (!BimpCache::cacheExists('bimp_object_bimpcommercial_Bimp_Commande_' . $r['id_commande_client']));

        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $r['id_commande_client']);
        $br_shipment = BimpCache::getBimpObjectInstance('bimpreservation', 'BR_CommandeShipment', (int) $r['id_shipment']);

        if (BimpObject::objectLoaded($br_shipment)) {
            if (BimpObject::objectLoaded($commande)) {
                if ($check_lines) {
                    $commande->checkLines();
                }

                $line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', array(
                            'id_obj'  => (int) $commande->id,
                            'id_line' => (int) $r['id_commande_client_line']
                ));

                if (BimpObject::objectLoaded($line)) {
                    $shipments = $line->getData('shipments');
                    if (is_null($shipments)) {
                        $shipments = array();
                    }

                    if (!isset($shipments[(int) $r['id_shipment']])) {
                        $shipments[(int) $r['id_shipment']] = array(
                            'qty'        => 0,
                            'group'      => 0,
                            'shipped'    => ((int) $br_shipment->getData('status') === 2 ? 1 : 0),
                            'equipments' => array()
                        );
                    }

                    $shipments[(int) $r['id_shipment']]['qty'] += (float) $r['qty'];

                    if ((int) $r['id_equipment']) {
                        $shipments[(int) $r['id_shipment']]['equipments'][] = (int) $r['id_equipment'];
                    }

                    if (!$full) {
                        echo '<pre>';
                        print_r($shipments);
                        echo '</pre>';
                    }

                    echo ' Maj expéditions ligne: ';
                    $up_errors = $line->updateField('shipments', $shipments);
                    if (count($up_errors)) {
                        echo '[ECHEC]<br/>';
                        echo BimpRender::renderAlerts($up_errors, 'danger');
                    } else {
                        echo '[OK]';
                    }
//                    
                    // Check facturation: 
                    if ((int) $br_shipment->getData(('id_facture'))) {
                        $id_facture = (int) $br_shipment->getData('id_facture');

                        if (!in_array($id_facture, $factures_set)) {
                            $asso = new BimpAssociation($commande, 'factures');
                            $asso_errors = $asso->addObjectAssociation($id_facture);
                            if (count($asso_errors)) {
                                echo '<br/>[ECHEC ASSO FACTURE ' . $id_facture . ']';
                                echo BimpRender::renderAlerts($asso_errors);
                            }
                        }

                        $factures = $line->getData('factures');
                        if (is_null($factures)) {
                            $factures = array();
                        }

                        if (!isset($factures[(int) $id_facture])) {
                            $factures[(int) $id_facture] = array(
                                'qty'        => 0,
                                'equipments' => array()
                            );
                        }

                        $factures[(int) $id_facture]['qty'] += (float) $r['qty'];

                        if (!$full) {
                            echo 'fac: <pre>';
                            print_r($factures[(int) $id_facture]);
                            echo '</pre>';
                        }

                        echo ' Maj facturation ligne: ';
                        $up_errors = $line->updateField('factures', $factures);
                        if (count($up_errors)) {
                            echo '[ECHEC]<br/>';
                            echo BimpRender::renderAlerts($up_errors, 'danger');
                        } else {
                            echo '[OK]';
                        }
                    }

                    if ($bdb->update('br_reservation_shipment', array(
                                'converted' => 1
                                    ), '`id` = ' . (int) $r['id']) <= 0) {
                        echo ' - [ECHEC MAJ br_reservation_shipment ' . $r['id'] . '] ' . $bdb->db->lasterror() . '<br/>';
                    }

                    echo '<br/><br/>';

                    if (!$full) {
                        break;
                    }
                } else {
                    echo '[LIGNE DE COMMANDE INVALIDE: ' . $r['id_commande_client_line'] . ']';
                }
            } else {
                echo '[COMMANDE INVALIDE: ' . $r['id_commande_client'] . ']';
            }
        } else {
            echo '[NO ID COMMANDE]';
        }
    } else {
        echo '[SHIPMENT INVALIDE ' . $r['id_shipment'] . ']';
    }
    echo '<br/>';
}

// Traitement des lignes d'expédition (services) 

$where = '`converted` = 0';

if ($id_commande) {
    $filters['id_commande_client'] = $id_commande;
    $where .= ' AND `id_commande_client` = ' . $id_commande;
}

$rows = $bdb->getRows('br_service_shipment', $where, null, 'array');

foreach ($rows as $r) {
    echo 'Traitement service_shipment ' . $r['id'] . ': ';

    if ((int) $r['id_commande_client']) {
        $check_lines = (!BimpCache::cacheExists('bimp_object_bimpcommercial_Bimp_Commande_' . $r['id_commande_client']));

        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $r['id_commande_client']);
        $br_shipment = BimpCache::getBimpObjectInstance('bimpreservation', 'BR_CommandeShipment', (int) $r['id_shipment']);

        if (BimpObject::objectLoaded($br_shipment)) {
            if (BimpObject::objectLoaded($commande)) {
                if ($check_lines) {
                    $commande->checkLines();
                }

                $line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', array(
                            'id_obj'  => (int) $commande->id,
                            'id_line' => (int) $r['id_commande_client_line']
                ));

                if (BimpObject::objectLoaded($line)) {
                    $shipments = $line->getData('shipments');
                    if (is_null($shipments)) {
                        $shipments = array();
                    }

                    if (!isset($shipments[(int) $r['id_shipment']])) {
                        $shipments[(int) $r['id_shipment']] = array(
                            'qty'        => 0,
                            'group'      => 0,
                            'shipped'    => ((int) $br_shipment->getData('status') === 2 ? 1 : 0),
                            'equipments' => array()
                        );
                    }

                    $shipments[(int) $r['id_shipment']]['qty'] += (float) $r['qty'];

                    if (!$full) {
                        echo '<pre>';
                        print_r($shipments);
                        echo '</pre>';
                    }

                    echo 'Maj expéditions ligne: ';
                    $up_errors = $line->updateField('shipments', $shipments);
                    if (count($up_errors)) {
                        echo '[ECHEC]<br/>';
                        echo BimpRender::renderAlerts($up_errors, 'danger');
                    } else {
                        echo '[OK]';
                    }

                    // Check facturation: 

                    if ((int) $br_shipment->getData(('id_facture'))) {
                        $id_facture = (int) $br_shipment->getData('id_facture');

                        if (!in_array($id_facture, $factures_set)) {
                            $asso = new BimpAssociation($commande, 'factures');
                            $asso_errors = $asso->addObjectAssociation($id_facture);
                            if (count($asso_errors)) {
                                echo '<br/>[ECHEC ASSO FACTURE ' . $id_facture . ']';
                                echo BimpRender::renderAlerts($asso_errors);
                            }
                        }

                        $factures = $line->getData('factures');
                        if (is_null($factures)) {
                            $factures = array();
                        }

                        if (!isset($factures[(int) $id_facture])) {
                            $factures[(int) $id_facture] = array(
                                'qty'        => 0,
                                'equipments' => array()
                            );
                        }

                        $factures[(int) $id_facture]['qty'] += (float) $r['qty'];

                        if (!$full) {
                            echo '<pre>';
                            print_r($factures);
                            echo '</pre>';
                        }

                        echo ' Maj facturation ligne: ';
                        $up_errors = $line->updateField('factures', $factures);
                        if (count($up_errors)) {
                            echo '[ECHEC]<br/>';
                            echo BimpRender::renderAlerts($up_errors, 'danger');
                        } else {
                            echo '[OK]';
                        }
                    }

                    if ($bdb->update('br_service_shipment', array(
                                'converted' => 1
                                    ), '`id` = ' . (int) $r['id']) <= 0) {
                        echo ' - [ECHEC MAJ br_service_shipment ' . $r['id'] . '] ' . $bdb->db->lasterror() . '<br/>';
                    }

                    echo '<br/><br/>';

                    if (!$full) {
                        break;
                    }
                } else {
                    echo '[LIGNE DE COMMANDE INVALIDE: ' . $r['id_commande_client_line'] . ']';
                }
            } else {
                echo '[COMMANDE INVALIDE: ' . $r['id_commande_client'] . ']';
            }
        } else {
            echo '[NO ID COMMANDE]';
        }
    } else {
        echo '[SHIPMENT INVALIDE ' . $r['id_shipment'] . ']';
    }
    echo '<br/>';
}

// Traitement des réservations (id_dol_line => id_bimp_line): 

$reservation = BimpObject::getInstance('bimpreservation', 'BR_Reservation');

$filters = array(
    'id_commande_client_line'   => array(
        'operator' => '>',
        'value'    => 0
    ),
    'commande_client_converted' => 0
);

if ($id_commande) {
    $filters['id_commande_client'] = $id_commande;
} else {
    $filters['id_commande_client'] = array(
        'operator' => '>',
        'value'    => 0
    );
}
$rows = $reservation->getList($filters, null, null, 'id', 'asc', 'array');

foreach ($rows as $r) {
    echo 'Traitement BR_Reservation ' . $r['id'] . ': ';

    if ((int) $r['id_commande_client']) {
        $check_lines = (!BimpCache::cacheExists('bimp_object_bimpcommercial_Bimp_Commande_' . $r['id_commande_client']));

        $commande = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $r['id_commande_client']);

        if (BimpObject::objectLoaded($commande)) {
            if ($check_lines) {
                $commande->checkLines();
            }

            $line = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_CommandeLine', array(
                        'id_obj'  => (int) $commande->id,
                        'id_line' => (int) $r['id_commande_client_line']
            ));

            if (BimpObject::objectLoaded($line)) {
                echo '<br/> Bimp_line: ' . $line->id . ', dol_line: ' . $r['id_commande_client_line'] . '<br/>';
                $result = $bdb->update('br_reservation', array(
                    'id_commande_client_line' => (int) $line->id
                        ), '`id` = ' . (int) $r['id']);

                if ($result <= 0) {
                    echo '[ECHEC MAJ] ' . $bdb->db->lasterror();
                } else {
                    if ($bdb->update('br_reservation', array(
                                'commande_client_converted' => 1
                                    ), '`id` = ' . (int) $r['id']) <= 0) {
                        echo ' - [ECHEC MAJ br_reservation ' . $r['id'] . '] ' . $bdb->db->lasterror() . '<br/>';
                    }
                }

                if (!$full) {
                    echo '<br/><br/>';
                    break;
                }
            } else {
                echo '[LIGNE DE COMMANDE INVALIDE: ' . $r['id_commande_client_line'] . ']';
            }
        } else {
            echo '[COMMANDE INVALIDE: ' . $r['id_commande_client'] . ']';
        }
    } else {
        echo '[SHIPMENT INVALIDE ' . $r['id_shipment'] . ']';
    }
    echo '<br/>';
}

// Traitement factures globales: 

$commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande');

$filters = array(
    'facture_converted' => 0
);

if ($id_commande) {
    $filters['rowid'] = $id_commande;
}

$list = $commande->getListObjects($filters);

foreach ($list as $commande) {
    $lines = $commande->getLines('not_text');

    $id_facture = (int) $commande->getData('id_facture');

    if (!$id_facture) {
        // Recherche facture liée non enregistrée dans id_facture: 
        $linked_objects = BimpTools::getDolObjectLinkedObjectsList($commande->dol_object, $bdb);

        foreach ($linked_objects as $object) {
            if ($object['type'] === 'facture') {
                $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $object['id_object']);
                if (BimpObject::objectLoaded($facture)) {
                    if ((int) $facture->getData('type') === 0) {
                        $id_facture = $object['id_object'];
                        break;
                    }
                }
            }
        }

        if ($id_facture) {
            $shipments = $commande->getChildrenObjects('old_shipments');
            foreach ($shipments as $br_shipment) {
                if ((int) $br_shipment->getData('id_facture')) {
                    continue 2;
                }
            }
        }
    }

    if (!$id_facture) {
        continue;
    }

    if (!in_array($id_facture, $factures_set)) {
        $asso = new BimpAssociation($commande, 'factures');
        $asso_errors = $asso->addObjectAssociation($id_facture);
        if (count($asso_errors)) {
            echo '<br/>[ECHEC ASSO FACTURE ' . $id_facture . ']';
            echo BimpRender::renderAlerts($asso_errors);
        }
    }

    echo 'Traitement facture globale commande ' . $commande->id . '<br/>';
    foreach ($lines as $line) {
        echo 'Ligne ' . $line->id . ': ';

        $line_qty = (float) $line->qty;
        $qty_billed = (float) $line->getBilledQty();

        if ($line_qty > $qty_billed) {
            $qty = $line_qty - $qty_billed;
            $factures = $line->getData('factures');

            if (is_null($factures)) {
                $factures = array();
            }

            if (!isset($factures[(int) $id_facture])) {
                $factures[(int) $id_facture] = array(
                    'qty'        => 0,
                    'equipments' => array()
                );
            }

            $factures[(int) $id_facture]['qty'] += $qty;

            if (!$full) {
                echo '<pre>';
                print_r($factures);
                echo '</pre>';
            }

            $up_errors = $line->updateField('factures', $factures);
            if (count($up_errors)) {
                echo '[ECHEC]<br/>';
                echo BimpRender::renderAlerts($up_errors, 'danger');
            } else {
                echo '[OK]';
            }
        }
    }

    if ($bdb->update('commande', array(
                'facture_converted' => 1
                    ), '`rowid` = ' . (int) $commande->id) <= 0) {
        echo ' - [ECHEC MAJ commande ' . $commande->id . '] ' . $bdb->db->lasterror() . '<br/>';
    }

    if (!$full) {
        echo '<br/><br/>';
        break;
    }
}

// Traitements id_user_resp / statut logistique:

$filters = array(
    'fk_statut' => array(
        'operator' => '>',
        'value'    => 0
    )
);

if ($id_commande) {
    $filters['rowid'] = $id_commande;
}

$list = $commande->getListObjects($filters);

foreach ($list as $commande) {
    if (!(int) $commande->getData('id_user_resp')) {
        $up_errors = $commande->updateField('id_user_resp', (int) $commande->getData('fk_user_valid'));
        if (count($up_errors)) {
            echo 'Commmande ' . $commande->id . ': ECHEC MAJ ID USER RESP: <br/>';
            echo BimpRender::renderAlerts($up_errors);
        }
    }

    if (!(int) $commande->getData('logistique_status')) {
        $up_errors = $commande->updateField('logistique_status', 1);
        if (count($up_errors)) {
            echo 'Commmande ' . $commande->id . ': ECHEC MAJ STATUT LOGISTIQUE: <br/>';
            echo BimpRender::renderAlerts($up_errors);
        }
    }

    if (!$full) {
        break;
    }
}

//  Cheks Statuts commandes: 

$filters = array(
    'fk_statut' => array(
        'operator' => '>',
        'value'    => 0
    )
);

if ($id_commande) {
    $filters['rowid'] = $id_commande;
}

$list = $commande->getListObjects($filters);

foreach ($list as $commande) {
    $commande->checkLogistiqueStatus();
    $commande->checkShipmentStatus();
    $commande->checkInvoiceStatus();

    if (!$full) {
        break;
    }
}

// Checks statuts commandes fourn: 

if (!$id_commande) {
    $commande_fourn = BimpObject::getInstance('bimpcommercial', 'Bimp_CommandeFourn');

    $list = $commande_fourn->getListObjects($filters);

    foreach ($list as $commande) {
        $commande->checkReceptionStatus();
        $commande->checkInvoiceStatus();

        if (!$full) {
            break;
        }
    }
}

echo '</body></html>';

//llxFooter();
