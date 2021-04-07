<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'CREA AVOIRS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user, $langs;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
    exit;
}

echo 'Désactivé';
exit;

$bdb = new BimpDb($db);

$dir = DOL_DOCUMENT_ROOT . '/bimpcore/scripts/docs/';

$refs_for_facs = array();
$file = $dir . 'facs_to_create.csv';
if (!file_exists($file)) {
    echo BimpRender::renderAlerts('Le fichier "' . $file . '" n\'existe pas', 'warning');
} else {
    $lines = file($file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        $data = explode(';', $line);
        $refs_for_facs[$data[0]] = $data[1];
    }
}

$refs_for_avs = array();
$file = $dir . 'avs_to_create.csv';
if (!file_exists($file)) {
    echo BimpRender::renderAlerts('Le fichier "' . $file . '" n\'existe pas', 'warning');
} else {
    $lines = file($file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        $data = explode(';', $line);
        $refs_for_avs[$data[0]] = $data[1];
    }
}

if (!(int) BimPTools::getValue('exec', 0)) {
    echo 'Création avoirs/factures<br/>';

    if (count($refs_for_facs) || count($refs_for_avs)) {
        echo count($refs_for_facs) . ' factures à créer <br/>';
        echo count($refs_for_avs) . ' avoirs à créer <br/><br/>';

        $path = pathinfo(__FILE__);
        echo ' <a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1&test=1" class="btn btn-default">';
        echo 'Test';
        echo '</a>';
        echo ' <a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1&test_one=1" class="btn btn-default">';
        echo 'Executer une entrée';
        echo '</a>';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?exec=1" class="btn btn-default">';
        echo 'Tout éxécuter';
        echo '</a>';

        exit;
    }

    echo BimpRender::renderAlerts('Aucun élément à traiter', 'info');
    exit;
}

BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');

//if (count($refs_for_facs)) {
//    echo '<br/><h2>Factures à créer</h2><br/>';
//    createFactures($refs_for_facs, false);
//}

if (count($refs_for_avs)) {
    echo '<br/><h2>Avoirs à créer</h2><br/>';
    createFactures($refs_for_avs, true);
}

function createFactures($refs, $is_avoir = false)
{
    global $user, $db;
    $bdb = new BimpDb($db);

    $test = (int) BimpTools::getValue('test', 0);
    $test_one = (int) BimpTools::getValue('test_one', 0);

    $id_def_entrepot = 50;

    foreach ($refs as $ref => $amount) {
        BimpTools::cleanDolEventsMsgs();
        echo $ref . ' ';
        $fac = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_Facture', array(
                    'facnumber' => $ref
        ));

        if (!BimpObject::objectLoaded($fac)) {
            echo '<span class="danger">Fac. non trouvée</span>';
        } else {
            $client = $fac->getChildObject('client');

            if (!BimpObject::objectLoaded($client)) {
                echo '<span class="danger">Aucun client</span>';
            } else {

                if ($test) {
                    echo '<span class="success">OK</span><br/>';
                    continue;
                }

                echo ' - CREA ' . ($is_avoir ? 'AVOIR' : 'FAC') . ' ';
                // Créa facture: 
                $errors = array();
                $warnings = array();

                $new_fac = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');
                $errors = $new_fac->validateArray(array(
                    'fk_soc'            => (int) $client->id,
                    'ref_client'        => $fac->getData('ref_client'),
                    'type'              => Facture::TYPE_STANDARD,
                    'fk_account'        => (int) $fac->getData('fk_account'),
                    'entrepot'          => ((int) $fac->getData('entrepot') ? (int) $fac->getData('entrepot') : $id_def_entrepot),
                    'libelle'           => 'Correction ' . $fac->getRef(),
                    'centre'            => $fac->getData('centre'),
                    'ef_type'           => ($fac->getData('ef_type') ? $fac->getData('ef_type') : 'S'),
                    'fk_cond_reglement' => ($fac->getData('fk_cond_reglement') ? $fac->getData('fk_cond_reglement') : 1),
                    'fk_mode_reglement' => ($fac->getData('fk_mode_reglement') ? $fac->getData('fk_mode_reglement') : 2),
                    'datef'             => '2021-03-31'
                ));

                if (!count($errors)) {
                    $errors = $new_fac->create($warnings, true);

                    if (!count($errors) && !BimpObject::objectLoaded($new_fac)) {
                        $errors[] = 'Echec création de ' . ($is_avoir ? 'l\'avoir' : 'la facture') . ' pour une raison inconnue';
                    }
                }

                if (count($warnings)) {
                    echo BimpRender::renderAlerts($warnings, 'warning');
                }

                if (count($errors)) {
                    echo BimpRender::renderAlerts($errors);
                } else {
                    echo '<span class="success">OK ' . $new_fac->getRef() . '</span>';

                    // Créa ligne: 
                    echo ' - CREA LIGNE: ';
                    $line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                    $line->validateArray(array(
                        'id_obj' => $new_fac->id,
                        'type'   => 3
                    ));

                    $line->desc = 'Correction ' . $ref;
                    $line->product_type = 1;
                    $line->qty = 1;
                    $line->pu_ht = ($is_avoir ? (float) $amount * -1 : (float) $amount);
                    $line->tva_tx = 0;
                    $line->pa_ht = $line->pu_ht;

                    $warnings = array();
                    $errors = $line->create($warnings, true);

                    if (count($warnings)) {
                        echo BimpRender::renderAlerts($warnings, 'warning');
                    }

                    if (count($errors)) {
                        echo BimpRender::renderAlerts($errors);
                    } else {
                        echo ' - <span class="success">OK</span>';
                        
                        // Validation: 
                        echo ' - VALIDATION: ';
                        $result = $new_fac->dol_object->validate($user, '', (int) $new_fac->getData('entrepot'));
                        $warnings = BimpTools::getDolEventsMsgs(array('warnings'));

                        if (count($warnings)) {
                            echo BimpRender::renderAlerts($warnings, 'warning');
                        }

                        if ($result < 0) {
                            $errors = BimpTools::getDolEventsMsgs(array('errors'));
                            echo BimpRender::renderAlerts($errors);
                        } else {
                            $new_fac->fetch($new_fac->id);
                            echo '<span class="success">OK (' . $new_fac->getRef() . ')</span>';

                            // Classement payé: 
                            echo ' - CLASSE PAYE' . (!$is_avoir ? 'E' : '') . ': ';

                            $errors = array();
                            $warnings = array();

                            if ($new_fac->dol_object->set_paid($user, 'paid', '') <= 0) {
                                $errors[] = BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($new_fac->dol_object));
                            } else {
                                if ($bdb->update('facture', array(
                                            'paye'            => 1,
                                            'paiement_status' => 2,
                                            'remain_to_pay'   => 0
                                                ), 'rowid = ' . $new_fac->id) <= 0) {
                                    $warnings[] = 'Echec màj statuts facture - ' . $bdb->err();
                                }
                            }

                            if (count($warnings)) {
                                echo BimpRender::renderAlerts($warnings, 'warning');
                            }

                            if (count($errors)) {
                                echo BimpRender::renderAlerts($errors);
                            } else {
                                echo '<span class="success">OK</span>';

                                $rap = (float) $fac->getRemainToPay(true);

                                if (($rap * -1) == (float) round($new_fac->getData('total_ttc'), 2)) {
                                    echo ' - <span class="info">Les RAP correspondent (' . BimpTools::displayMoneyValue($rap) . ')</span>';
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($test_one) {
            break;
        }
        echo '<br/>';
    }
}

// Code à reprendre si besoin de solder factures en masse via avoirs: 
function createAvoirsFromFactures($refs)
{
    $test = (int) BimpTools::getValue('test', 0);
    $test_one = (int) BimpTools::getValue('test_one', 0);

    foreach ($refs as $ref) {
        $_POST = array();
        BimpTools::cleanDolEventsMsgs();
        echo $ref . ': ';
        $fac = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_Facture', array(
                    'facnumber' => $ref
        ));

        if (!BimpObject::objectLoaded($fac)) {
            echo '<span class="danger">Fac. non trouvée</span>';
        } else {
            if (!in_array($fac->getData('type'), array(Facture::TYPE_STANDARD, Facture::TYPE_CREDIT_NOTE, Facture::TYPE_DEPOSIT))) {
                echo '<span class="warning">Type invalide (' . $fac->getData('type') . ')</span>';
            } else {
                $ref_avoir = $bdb->getValue('facture', 'facnumber', 'fk_facture_source = ' . (int) $fac->id);

                if ($ref_avoir) {
                    echo '<span class="warning">Avoir / Facture de correction déjà créé(e) : ' . $ref_avoir . '</span>';
                } else {
                    $fac->checkIsPaid();
                    if ($fac->getData('paiement_status') > 0) {
                        echo '<span class="warning">Un paiement semble avoir été effectué</span>';
                    } else {
                        if ($test) {
                            echo '<span class="success">OK pour créa avoir</span>';
                        } else {
//                        echo 'ATTENTION ICI';
//                        continue;

                            echo '<br/>Création avoir: ';

                            $errors = array();
                            $warnings = array();
                            $avoir = BimpObject::getInstance('bimpcommercial', 'Bimp_Facture');

                            $errors = $avoir->validateArray(array(
                                'fk_soc'            => (int) $fac->getData('fk_soc'),
                                'ref_client'        => $fac->getData('ref_client'),
                                'type'              => Facture::TYPE_CREDIT_NOTE,
                                'fk_account'        => (int) $fac->getData('fk_account'),
                                'entrepot'          => (int) $fac->getData('entrepot'),
                                'libelle'           => $fac->getData('libelle') . ' - Correction',
                                'centre'            => $fac->getData('centre'),
                                'ef_type'           => $fac->getData('ef_type'),
                                'fk_cond_reglement' => $fac->getData('fk_cond_reglement'),
                                'fk_mode_reglement' => $fac->getData('fk_mode_reglement'),
                                'datef'             => '2021-03-31'
                            ));

                            if (!count($errors)) {
                                $_POST['id_facture_to_correct'] = $fac->id;
                                $_POST['avoir_same_lines'] = 1;
                                $errors = $avoir->create($warnings, true);
                            }

                            if (count($warnings)) {
                                echo BimpRender::renderAlerts($warnings, 'warnings');
                            }

                            if (count($errors)) {
                                echo BimpRender::renderAlerts($errors);
                            } else {
                                echo '<span class="success">OK</span><br/>';
                                echo 'Validation: ';

                                $result = $avoir->dol_object->validate($user, '', (int) $fac->getData('entrepot'));
                                $warnings = BimpTools::getDolEventsMsgs(array('warnings'));

                                if (count($warnings)) {
                                    echo BimpRender::renderAlerts($warnings, 'warnings');
                                }

                                if ($result < 0) {
                                    $errors = BimpTools::getDolEventsMsgs(array('errors'));
                                    echo BimpRender::renderAlerts($errors);
                                } else {
                                    echo '<span class="success">OK</span><br/>';

                                    $avoir->fetch($avoir->id);
                                    $avoir->dol_object->generateDocument($avoir->getModelPdf(), $langs);


                                    echo 'Conversion en remise: ';
                                    $true_fac = null;
                                    $true_avoir = null;

                                    if (in_array($fac->getData('type'), array(Facture::TYPE_STANDARD, Facture::TYPE_DEPOSIT)) && $avoir->getData('type') == Facture::TYPE_CREDIT_NOTE) {
                                        $true_fac = $fac;
                                        $true_avoir = $avoir;
                                    } elseif ($fac->getData('type') == Facture::TYPE_CREDIT_NOTE && in_array($avoir->getData('type'), array(Facture::TYPE_STANDARD, Facture::TYPE_DEPOSIT))) {
                                        $true_fac = $avoir;
                                        $true_avoir = $fac;
                                    } else {
                                        echo '<span class="danger">Types factures et avoirs invalides</span>';
                                    }

                                    if (!is_null($true_fac) && !is_null($true_avoir)) {
                                        $errors = $true_avoir->convertToRemise();

                                        if (count($errors)) {
                                            echo BimpRender::renderAlerts($errors);
                                        } else {
                                            echo '<span class="success">OK</span><br/>';

                                            echo 'Application de la remise: ';

                                            $errors = array();

                                            $discount = new DiscountAbsolute($db);
                                            $discount->fetch(0, $true_avoir->id);

                                            if (!BimpObject::objectLoaded($discount)) {
                                                $errors[] = 'Remise non trouvée';
                                            } else {
                                                if ($discount->link_to_invoice(0, $true_fac->id) <= 0) {
                                                    $errors = BimpTools::getErrorsFromDolObject($discount);
                                                }

                                                $true_fac->checkIsPaid();
                                                $true_avoir->checkIsPaid();
                                            }

                                            if (count($errors)) {
                                                echo BimpRender::renderAlerts($errors);
                                            } else {
                                                echo '<span class="success">OK</span>';
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        echo '<br/>';

        if ($test_one) {
            break;
        }
    }
}
echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
