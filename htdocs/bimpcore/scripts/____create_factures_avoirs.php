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

$bdb = new BimpDb($db);

//$refs = array('FACO2103-0002');

//$refs = array(
//    'FA1704-1277',
//    'FA1708-0600',
//    'FA1711-0875',
//    'FA1711-1011',
//    'FA1711-1134',
//    'FA1711-1167',
//    'FA1711-1281',
//    'FA1711-1420',
//    'FA1711-1904',
//    'FA1711-1982',
//    'FA1711-2157',
//    'FA1712-0413',
//    'FA1712-0553',
//    'FA1712-2060',
//    'FA1712-2226',
//    'FA1801-0083',
//    'FA1801-2091',
//    'FAS1802-1154',
//    'FAS1803-2408',
//    'FAS1803-2644',
//    'FAS1804-0048',
//    'FAS1804-0426',
//    'FAS1804-1749',
//    'FAS1805-0404',
//    'FAS1805-0451',
//    'FAS1805-0756',
//    'FAS1805-1361',
//    'FAS1806-0805',
//    'FAS1806-1102',
//    'FAS1806-1248',
//    'FAS1807-1083',
//    'FAS1807-2466',
//    'FAS1808-0339',
//    'FAS1808-0788',
//    'FAS1808-0993',
//    'FAS1808-1538',
//    'FAS1809-0723',
//    'FAS1809-2380',
//    'FAS1809-2400',
//    'FAS1810-0249',
//    'FAS1810-0761',
//    'FAS1810-0923',
//    'FAS1810-1186',
//    'FAS1811-0081',
//    'FAS1811-0503',
//    'FAS1812-0218',
//    'FAS1812-0700',
//    'FAS1812-1308',
//    'FAS1812-2653',
//    'FAS1901-0281',
//    'FAS1901-0755',
//    'FAS1901-1241',
//    'FAS1901-1551',
//    'FAS1902-0787',
//    'FA1609-1876',
//    'FA1603-0810',
//    'FA1601-0695',
//    'AV1801-0003',
//    'FA1607-1496',
//    'FA1603-0010',
//    'AVS1807-0006',
//    'FAS1809-0262',
//    'AVS1812-0004',
//    'FAS1901-0376',
//    'AVS1901-0006',
//    'AVS1902-0003',
//    'FAS1903-0939',
//    'ACS1907-0007',
//    'ACS1907-0002',
//    'ACS1907-0009',
//    'ACS1907-0003',
//    'ACS1907-0008',
//    'ACS1907-0028',
//    'ACS1907-0062',
//    'ACS1907-0072',
//    'ACS1907-0103',
//    'ACS1907-0121',
//    'ACS1907-0125',
//    'ACS1907-0163',
//    'ACS1907-0146',
//    'ACS1907-0180',
//    'ACS1907-0192',
//    'PAY1907-54797',
//    'ACS1907-0293',
//    'ACS1908-0096',
//    'ACS1907-0365'
//);

if (!(int) BimPTools::getValue('exec', 0)) {
    echo 'Création avoirs/factures<br/>';

    if (is_array($refs) && count($refs)) {
        echo count($refs) . ' élément(s) à traiter <br/><br/>';

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

$test = (int) BimpTools::getValue('test', 0);
$test_one = (int) BimpTools::getValue('test_one', 0);

BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');

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

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
