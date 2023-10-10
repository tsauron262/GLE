<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CLEAN DB', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

die('Script bloqué');

$bdb = new BimpDb($db);
$action = BimpTools::getValue('action', '');

if (!$action) {
    $actions = array();

    $path = pathinfo(__FILE__);

    $nb = (int) $bdb->getCount('bs_sav');
    if ($nb) {
        $actions['del_savs'] = 'Suppr. SAVs (' . $nb . ')';
    }

    $nb = (int) $bdb->getCount('propal', '1', 'rowid');
    if ($nb) {
        $actions['del_propales'] = 'Suppr. propales (' . $nb . ')';
    }

    $nb = (int) $bdb->getCount('commande', '1', 'rowid');
    if ($nb) {
        $actions['del_commandes'] = 'Suppr. commandes (' . $nb . ')';
    }

    $nb = (int) $bdb->getCount('facture', '1', 'rowid');
    if ($nb) {
        $actions['del_facs'] = 'Suppr. factures (' . $nb . ')';
    }

    $nb = (int) $bdb->getCount('commande_fournisseur', '1', 'rowid');
    if ($nb) {
        $actions['del_commandes_fourn'] = 'Suppr. commandes fourn (' . $nb . ')';
    }

    $nb = (int) $bdb->getCount('facture_fourn', '1', 'rowid');
    if ($nb) {
        $actions['del_facs_fourn'] = 'Suppr. factures fourn (' . $nb . ')';
    }

    $nb = (int) $bdb->getCount('be_equipment');
    if ($nb) {
        $actions['del_eqs'] = 'Suppr. équipements (' . $nb . ')';
    }

    $nb = (int) $bdb->getCount('product', '1', 'rowid');
    if ($nb) {
        $actions['del_prods'] = 'Suppr. produits (' . $nb . ')';
    }

    $nb = (int) $bdb->getCount('socpeople', '1', 'rowid');
    if ($nb) {
        $actions['del_contacts'] = 'Suppr. contacts (' . $nb . ')';
    }

    $nb = (int) $bdb->getCount('societe', '1', 'rowid');
    if ($nb) {
        $actions['del_socs'] = 'Suppr. tiers (' . $nb . ')';
    }

    foreach ($actions as $code => $label) {
        echo '<div style="margin-bottom: 10px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=' . $code . '" class="btn btn-default">';
        echo $label . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';
    }
    exit;
}

BimpCore::setMaxExecutionTime(2400);
$html = '';
$nOk = 0;
$nFails = 0;
$errors = array();

switch ($action) {
    case 'del_contacts':
        $rows = $bdb->getRows('socpeople', '1', 10000, 'array', array('rowid'));
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $obj = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Contact', (int) $r['rowid']);

                if (BimpObject::objectLoaded($obj)) {
                    $err = $obj->delete($w, true);

                    if (count($err)) {
                        $errors[] = BimpTools::getMsgFromArray($err, 'Contact #' . $r['rowid']);
                        $nFails++;
                    } else {
                        $nOk++;
                    }
                } else {
                    $errors[] = 'Contact #' . $r['rowid'] . ' non trouvé';
                    $nFails++;
                }
            }
        } else {
            $errors[] = $bdb->err();
            $nFails++;
        }
        break;

    case 'del_socs':
        $rows = $bdb->getRows('societe', '1', 10000, 'array', array('rowid'));
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $obj = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Societe', (int) $r['rowid']);

                if (BimpObject::objectLoaded($obj)) {
                    $err = $obj->delete($w, true);

                    if (count($err)) {
                        $errors[] = BimpTools::getMsgFromArray($err, 'Soc #' . $r['rowid']);
                        $nFails++;
                    } else {
                        $nOk++;
                    }
                } else {
                    $errors[] = 'Soc #' . $r['rowid'] . ' non trouvée';
                    $nFails++;
                }
            }
        } else {
            $errors[] = $bdb->err();
            $nFails++;
        }
        break;

    case 'del_prods':
        $bdb->execute('TRUNCATE TABLE `llx_br_reservation`');
        $rows = $bdb->getRows('product', '1', 10000, 'array', array('rowid'));
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $obj = BimpCache::getBimpObjectInstance('bimpcore', 'Bimp_Product', (int) $r['rowid']);

                if (BimpObject::objectLoaded($obj)) {
                    $err = $obj->delete($w, true);

                    if (count($err)) {
                        $errors[] = BimpTools::getMsgFromArray($err, 'Prod #' . $r['rowid']);
                        $nFails++;
                    } else {
                        $nOk++;
                    }
                } else {
                    $errors[] = 'Prod #' . $r['rowid'] . ' non trouvé';
                    $nFails++;
                }
            }
        } else {
            $errors[] = $bdb->err();
            $nFails++;
        }
        break;

    case 'del_eqs':
        $rows = $bdb->getRows('be_equipment', '1', 10000, 'array', array('id'));
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $obj = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $r['id']);

                if (BimpObject::objectLoaded($obj)) {
                    $err = $obj->delete($w, true);

                    if (count($err)) {
                        $errors[] = BimpTools::getMsgFromArray($err, 'Equip #' . $r['id']);
                        $nFails++;
                    } else {
                        $nOk++;
                    }
                } else {
                    $errors[] = 'Equip #' . $r['id'] . ' non trouvé';
                    $nFails++;
                }
            }
        } else {
            $errors[] = $bdb->err();
            $nFails++;
        }
        break;

    case 'del_savs':
        $rows = $bdb->getRows('bs_sav', '1', 10000, 'array', array('id'));
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $obj = BimpCache::getBimpObjectInstance('bimpsupport', 'BS_SAV', (int) $r['id']);

                if (BimpObject::objectLoaded($obj)) {
                    $err = $obj->delete($w, true);

                    if (count($err)) {
                        $errors[] = BimpTools::getMsgFromArray($err, 'SAV #' . $r['id']);
                        $nFails++;
                    } else {
                        $nOk++;
                    }
                } else {
                    $errors[] = 'SAV #' . $r['id'] . ' non trouvé';
                    $nFails++;
                }
            }
        } else {
            $errors[] = $bdb->err();
            $nFails++;
        }
        break;

    case 'del_propales':
        $rows = $bdb->getRows('propal', '1', 10000, 'array', array('rowid'));
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Propal', (int) $r['rowid']);

                if (BimpObject::objectLoaded($obj)) {
                    $err = $obj->delete($w, true);

                    if (count($err)) {
                        $errors[] = BimpTools::getMsgFromArray($err, 'Propale #' . $r['rowid']);
                        $nFails++;
                    } else {
                        $nOk++;
                    }
                } else {
                    $errors[] = 'Propale #' . $r['rowid'] . ' non trouvée';
                    $nFails++;
                }
            }
        } else {
            $errors[] = $bdb->err();
            $nFails++;
        }
        break;

    case 'del_commandes':
        $rows = $bdb->getRows('commande', '1', 10000, 'array', array('rowid'));
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Commande', (int) $r['rowid']);

                if (BimpObject::objectLoaded($obj)) {
                    $err = $obj->delete($w, true);

                    if (count($err)) {
                        $errors[] = BimpTools::getMsgFromArray($err, 'Commande #' . $r['rowid']);
                        $nFails++;
                    } else {
                        $nOk++;
                    }
                } else {
                    $errors[] = 'Commande #' . $r['rowid'] . ' non trouvée';
                    $nFails++;
                }
            }
        } else {
            $errors[] = $bdb->err();
            $nFails++;
        }
        break;

    case 'del_facs':
        $rows = $bdb->getRows('facture', '1', 10000, 'array', array('rowid'));
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_Facture', (int) $r['rowid']);

                if (BimpObject::objectLoaded($obj)) {
                    $err = $obj->delete($w, true);

                    if (count($err)) {
                        $errors[] = BimpTools::getMsgFromArray($err, 'Facture #' . $r['rowid']);
                        $nFails++;
                    } else {
                        $nOk++;
                    }
                } else {
                    $errors[] = 'Facture #' . $r['rowid'] . ' non trouvée';
                    $nFails++;
                }
            }
        } else {
            $errors[] = $bdb->err();
            $nFails++;
        }
        break;

    case 'del_commandes_fourn':
        $rows = $bdb->getRows('commande_fournisseur', '1', 10000, 'array', array('rowid'));
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_CommandeFourn', (int) $r['rowid']);

                if (BimpObject::objectLoaded($obj)) {
                    $err = $obj->delete($w, true);

                    if (count($err)) {
                        $errors[] = BimpTools::getMsgFromArray($err, 'Commande fourn #' . $r['rowid']);
                        $nFails++;
                    } else {
                        $nOk++;
                    }
                } else {
                    $errors[] = 'Commande fourn #' . $r['rowid'] . ' non trouvée';
                    $nFails++;
                }
            }
        } else {
            $errors[] = $bdb->err();
            $nFails++;
        }
        break;

    case 'del_facs_fourn':
        $rows = $bdb->getRows('facture_fourn', '1', 10000, 'array', array('rowid'));
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $obj = BimpCache::getBimpObjectInstance('bimpcommercial', 'Bimp_FactureFourn', (int) $r['rowid']);

                if (BimpObject::objectLoaded($obj)) {
                    $err = $obj->delete($w, true);

                    if (count($err)) {
                        $errors[] = BimpTools::getMsgFromArray($err, 'Facture fourn #' . $r['rowid']);
                        $nFails++;
                    } else {
                        $nOk++;
                    }
                } else {
                    $errors[] = 'Facture fourn #' . $r['rowid'] . ' non trouvée';
                    $nFails++;
                }
            }
        } else {
            $errors[] = $bdb->err();
            $nFails++;
        }
        break;
}

echo '<br/>';
echo $nOk . 'OK<br/>';
echo $nFails . ' échecs <br/>';
echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
