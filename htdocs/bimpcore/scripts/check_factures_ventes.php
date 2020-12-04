<?php

die('Désactivé'); 

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CHECK FACTURES VENTES', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;
$bdb = new BimpDb($db);

$date = date('Y-m-d');

$sql = 'SELECT f.rowid as id,f.facnumber as ref,f.note_private  as note FROM ' . MAIN_DB_PREFIX . 'facture f';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture_extrafields fef ON f.rowid = fef.fk_object';
$sql .= ' WHERE f.datec > \'' . $date . '\' AND fef.type = \'M\'';

$rows = $bdb->executeS($sql, 'array');

$ventes = array();

foreach ($rows as $r) {
//    echo 'FAC #' . $r['id'] . ' - ' . $r['ref'] . ': ';
    if ($r['note']) {
        $note = str_replace('Vente en caisse <i class="fas fa5-money-check-alt iconLeft"></i>', '', $r['note']);
        if (preg_match('/^([0-9]+).*/', $note, $matches)) {
//            echo 'VENTE #' . $matches[1];
            $ventes[(int) $matches[1]][(int) $r['id']] = $r['ref'];
        } else {
//            echo '<span class="warning">PAS D\'ID VENTE</span>';
//            echo '<br/>NOTE: "' . $r['note'].'"';
            $ventes[0][(int) $r['id']] = $r['ref'];
        }
    } else {
//        echo '<span class="danger">PAS DE NOTE PRIVEE</span>';
        $ventes[0][(int) $r['id']] = $r['ref'];
    }

//    echo '<br/><br/>';
}

if (isset($ventes[0]) && !empty($ventes[0])) {
    echo count($ventes[0]) . ' factures sans ID vente <br/>';

    foreach ($ventes[0] as $id_facture => $ref) {
        echo ' - #' . $id_facture . ' ' . $ref . '<br/>';
    }
}

echo '<br/><br/>';
unset($ventes[0]);

$facs_to_delete = array();

foreach ($ventes as $id_vente => $facs) {
    $vente = BimpCache::getBimpObjectInstance('bimpcaisse', 'BC_Vente', (int) $id_vente);

    echo 'VENTE #' . $id_vente;

    if (BimpObject::objectLoaded($vente)) {
        echo ' ' . $vente->displayData('status');
    }

    echo ' - <span class="' . (count($facs) == 1 ? 'success' : 'danger') . '">';
    echo count($facs) . ' factures';
    echo '</span>';

    echo '<br/>';
    $id_fac_vente = 0;
    $last_id_fac = 0;
    $vente_fac_class = 'danger';

    if (BimpObject::objectLoaded($vente)) {
        $id_fac_vente = (int) $vente->getData('id_facture');
    }

    foreach ($facs as $id_fac => $ref) {
        $class = 'bold';
        if ($id_fac_vente && (int) $id_fac === $id_fac_vente) {
            $vente_fac_class = 'success';
            $class = 'success';
        }

        echo ' - ' . $ref . ' <span class="' . $class . '">#' . $id_fac . '</span><br/>';
        $last_id_fac = $id_fac;
    }


    if (!BimpObject::objectLoaded($vente)) {
        echo '<span class="danger">LA VENTE N\'EXISTE PAS</span><br/>';
    } elseif ((int) $vente->getData('status') === BC_Vente::BC_VENTE_VALIDEE) {
        if ((int) $vente->getData('id_facture')) {
            echo 'ID FACTURE DE LA VENTE <span class="' . $vente_fac_class . '">' . $vente->getData('id_facture') . '</span><br/>';
        } else {
            echo '<span class="warning">AUCUN ID FACTURE DANS LA VENTE</span><br/>';

            if (BimpTools::getValue('correct_vente_fac', 0) && $last_id_fac) {
                echo 'ATTRIBUTION FAC ' . $last_id_fac . ': ';

                $vente_errors = $vente->updateField('id_facture', $last_id_fac);
                if (count($vente_errors)) {
                    echo BimpRender::renderAlerts($vente_errors);
                } else {
                    echo '<span class="success">OK</span><br/>';
                }
            }
        }
    } elseif ((int) $vente->getData('id_facture') && (int) BimpTools::getValue('correct_vente_fac', 0)) {
        echo 'SUPPR ID FAC VENTE: ';
        $vente_errors = $vente->updateField('id_facture', 0);
        if (count($vente_errors)) {
            echo BimpRender::renderAlerts($vente_errors);
        } else {
            echo '<span class="success">OK</span><br/>';
        }
    }

    foreach ($facs as $id_fac => $ref) {
        if ((int) $vente->getData('status') === BC_Vente::BC_VENTE_VALIDEE) {
            if ((!(int) $vente->getData('id_facture') && (int) $id_fac !== $last_id_fac) || ((int) $id_fac !== (int) $vente->getData('id_facture'))) {
                $facs_to_delete[$id_fac] = $ref;
            }
        } else {
            $facs_to_delete[$id_fac] = $ref;
        }
    }

    if ((int) $vente->getData('status') === BC_Vente::BC_VENTE_VALIDEE) {
        $articles = $vente->getChildrenObjects('articles');
        $returns = $vente->getChildrenObjects('returns');
        if (count($returns)) {
            echo '<span class="warning">Retours à traiter</span><br/>';
        }
        $check = true;
        foreach ($articles as $art) {
            $code_mvt = 'VENTE' . $vente->id . '_ART' . $art->id;
            $res = (int) $bdb->getValue('stock_mouvement', 'rowid', 'inventorycode = \'' . $code_mvt . '\'');
            if (!$res) {
                $prod = $art->getChildObject('product');
                if (!BimpObject::objectLoaded($prod)) {
                    echo '<span class="danger">PAS DE PRODUIT</span><br/>';
                } elseif ($prod->isTypeProduct()) {
                    if ($prod->isSerialisable()) {
                        $id_equipment = (int) $art->getData('id_equipment');
                        if (!$id_equipment) {
                            echo '<span class="danger">PAS D\'ID EQUIPEMENT</span><br/>';
                        } else {
                            $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', $id_equipment);
                            if (!BimpObject::objectLoaded($equipment)) {
                                echo '<span class="danger">EQUIPEMENT #' . $id_equipment . ' N\'EXISTE PAS</span><br/>';
                            } else {
                                $curPlace = $equipment->getCurrentPlace();

                                if ((int) $vente->getData('id_client')) {
                                    if (!BimpObject::objectLoaded($curPlace) || (int) $curPlace->getData('type') !== BE_Place::BE_PLACE_CLIENT || (int) $curPlace->getData('id_client') !== (int) $vente->getData('id_client')) {
                                        echo '<span class="danger">';
                                        echo 'EMPLACEMENT INCORRECT POUR ARTICLE #' . $art->id;
                                        echo '</span><br/>';
                                        if ((int) BimpTools::getValue('correct_stocks', 0)) {
                                            echo 'CORRECTION: ';
                                            $place_errors = $place->validateArray(array(
                                                'id_equipment' => $id_equipment,
                                                'type'         => BE_Place::BE_PLACE_CLIENT,
                                                'id_client'    => (int) $vente->getData('id_client'),
                                                'infos'        => 'Vente #' . $vente->id,
                                                'date'         => date('Y-m-d H:i:s'),
                                                'code_mvt'     => $code_mvt
                                            ));
                                            if (!count($place_errors)) {
                                                $place_errors = $place->create($w, true);
                                            }
                                            if (count($place_errors)) {
                                                echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($place_errors, 'ECHEC CREA EMPLACEMENT'));
                                            } else {
                                                echo '<span class="success">OK</span><br/>';
                                            }
                                        }
                                    }
                                } else {
                                    if (!BimpObject::objectLoaded($curPlace) || (int) $curPlace->getData('type') !== BE_Place::BE_PLACE_FREE) {
                                        echo '<span class="danger">';
                                        echo 'EMPLACEMENT INCORRECT POUR ARTICLE #' . $art->id;
                                        echo '</span><br/>';
                                        if ((int) BimpTools::getValue('correct_stocks', 0)) {
                                            echo 'CORRECTION: ';
                                            $place_errors = $place->validateArray(array(
                                                'id_equipment' => $id_equipment,
                                                'type'         => BE_Place::BE_PLACE_FREE,
                                                'place_name'   => 'Equipement vendu (client non renseigné)',
                                                'infos'        => 'Vente #' . $vente->id,
                                                'date'         => date('Y-m-d H:i:s'),
                                                'code_mvt'     => $code_mvt
                                            ));
                                            if (!count($place_errors)) {
                                                $place_errors = $place->create($w, true);
                                            }
                                            if (count($place_errors)) {
                                                echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($place_errors, 'ECHEC CREA EMPLACEMENT'));
                                            } else {
                                                echo '<span class="success">OK</span><br/>';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        echo '<span class="danger">';
                        echo 'MVT STOCK ABSENT POUR ARTICLE #' . $art->id . ' (CODE: ' . $code_mvt . ')';
                        echo '</span><br/>';

                        if ((int) BimpTools::getValue('correct_stocks', 0)) {
                            echo 'CORRECTION: ';

                            if ($prod->dol_object->correct_stock($user, (int) $vente->getData('id_entrepot'), (int) $art->getData('qty'), 1, 'Vente #' . $vente->id, 0, $code_mvt, 'facture', (int) $vente->getData('id_facture')) <= 0) {
                                echo BimpRender::renderAlerts(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($prod->dol_object), 'ECHEC'));
                            } else {
                                echo '<span class="success">OK</span>';
                            }

                            echo '<br/>';
                        }
                    }
                }

                $check = false;
            }
        }

        if ($check) {
            echo '<span class="success">STOCKS OK</span><br/>';
        }
    }

    echo '<br/>';
}

if (count($facs_to_delete)) {
    echo 'FACS à SUPPR: <br/>';

    foreach ($facs_to_delete as $id_fac => $ref) {
        echo ' - ' . $ref . ' #' . $id_fac . '<br/>';
    }
}


//if (BimpTools::getValue('delete_paiements', 0)) {
//    foreach ($facs_to_delete as $id_fac) {
//        $facture = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $id_fac);
//
//        if (BimpObject::objectLoaded($facture)) {
//            
//        }
//    }
//}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();