<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);
BimpCore::setMaxExecutionTime(9000);
BimpCore::setMemoryLimit(512);
ignore_user_abort(0);
ini_set('display_errors', 1);

top_htmlhead('', 'Créa avoirs acomptes SAV', 0, 0, array(), array());

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

$file = DOL_DOCUMENT_ROOT . '/bimpcore/scripts/docs/correct_avoirs.txt';
if (!file_exists($file)) {
    echo BimpRender::renderAlerts('Fichier KO: ' . $file);
    exit;
}

$refs = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if (!BimpTools::getValue('exec', 0)) {
    echo 'Refs: <pre>';
    print_r($refs);
    echo '</pre>';

    echo BimpRender::renderAlerts('La régul des avoirs va être lancée', 'info');
    echo '<a class="btn btn-default" href="' . DOL_URL_ROOT . '/bimpcore/scripts/regul_avoirs_tva.php?exec=1">';
    echo 'Exécuter';
    echo '</a>';
    exit;
}

$refs = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

global $db;
$bdb = new BimpDb($db);

BimpTools::loadDolClass('core', 'discount', 'DiscountAbsolute');

$result = array();

foreach ($refs as $ref) {
    $avoir = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_Facture', array(
                'ref' => $ref
    ));

    if (!BimpObject::objectLoaded($avoir)) {
        echo BimpRender::renderAlerts('Acompte "' . $ref . '" non trouvé');
    } else {
        echo 'Avoir #' . $avoir->id . ' - ' . $ref . ': ';
        $errors = array();
        $warnings = array();
        $fac = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', array(
                    'fk_facture_source' => $avoir->id,
                    'type'              => 0,
                    'fk_soc'            => (int) $avoir->getData('fk_soc'),
                    'entrepot'          => (int) $avoir->getData('entrepot'),
                    'contact_id'        => (int) $avoir->getData('contact_id'),
                    'fk_account'        => (int) $avoir->getData('fk_account'),
                    'ef_type'           => ($avoir->getData('ef_type') ? $avoir->getData('ef_type') : 'S'),
                    'datef'             => date('Y-m-d'),
                    'libelle'           => 'Annulation avoir ' . $ref,
                    'relance_active'    => 0,
                    'fk_cond_reglement' => $avoir->getData('fk_cond_reglement'),
                    'fk_mode_reglement' => $avoir->getData('fk_mode_reglement')
                        ), true, $errors, $warnings);

        if (!BimpObject::objectLoaded($fac)) {
            echo '<span class="danger">ECHEC CREA REFAC</span>';
            if (count($errors)) {
                echo BimpRender::renderAlerts($errors);
            }
            if (count($warnings)) {
                echo BimpRender::renderAlerts($warnings, 'warning');
            }
        } else {
            $warnings = array();

            // Copie des lignes: 
            $lines_errors = array();
            $lines = $avoir->getLines();
            foreach ($lines as $line) {
                $line_warnings = array();
                $line_errors = array();
                $new_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                $new_line->validateArray(array(
                    'id_obj'      => (int) $fac->id,
                    'type'        => ObjectLine::LINE_FREE,
                    'remisable'   => 0,
                    'pa_editable' => 0
                ));

                $new_line->qty = 1;
                $new_line->desc = $line->desc;
                $new_line->pu_ht = $line->pu_ht * -1;
                $new_line->tva_tx = $line->tva_tx;
                $new_line->pa_ht = $line->pa_ht * -1;

                $line_warnings = array();
                $line_errors = $new_line->create($line_warnings, true);

                if (!empty($line_errors)) {
                    $lines_errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne "' . $line->desc . '"');
                } elseif ((int) $new_line->getData('id_line')) {
                    if (preg_match('/^.+services$/', $line->desc))
                        $bdb->update('facturedet', array(
                            'product_type' => 1
                                ), 'rowid = ' . (int) $new_line->getData('id_line'));
                }
            }

            if (count($lines_errors)) {
                echo BimpRender::renderAlerts($line_errors);
            } else {
                echo '<span class="success">Création REFAC OK</span>';
                setElementElement('facture', 'facture', $avoir->id, $fac->id);
                // Validation de l'avoir: 
                if ($fac->dol_object->validate($user, '', 0, 0) <= 0) {
                    echo BimpRender::renderAlerts(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($fac->dol_object), 'Echec de la validation de la facture'), 'danger');
                } else {
                    // Création du nouvel avoir: 
                    $errors = array();
                    $warnings = array();
                    $new_avoir = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', array(
                                'fk_facture_source' => $fac->id,
                                'type'              => 0,
                                'fk_soc'            => (int) $fac->getData('fk_soc'),
                                'entrepot'          => (int) $fac->getData('entrepot'),
                                'contact_id'        => (int) $fac->getData('contact_id'),
                                'fk_account'        => (int) $fac->getData('fk_account'),
                                'ef_type'           => ($fac->getData('ef_type') ? $fac->getData('ef_type') : 'S'),
                                'datef'             => date('Y-m-d'),
                                'libelle'           => $avoir->getData('libelle') . ' (TVA corrigée)',
                                'relance_active'    => 0,
                                'fk_cond_reglement' => $fac->getData('fk_cond_reglement'),
                                'fk_mode_reglement' => $fac->getData('fk_mode_reglement')
                                    ), true, $errors, $warnings);

                    if (!BimpObject::objectLoaded($new_avoir)) {
                        echo ' - <span class="danger">ECHEC CREA NEW AVOIR</span>';
                        if (count($errors)) {
                            echo BimpRender::renderAlerts($errors);
                        }
                        if (count($warnings)) {
                            echo BimpRender::renderAlerts($warnings, 'warning');
                        }
                    } else {
                        // Créa lignes: 
                        $lines = $fac->getLines();
                        foreach ($lines as $line) {
                            $line_warnings = array();
                            $line_errors = array();
                            $new_line = BimpObject::getInstance('bimpcommercial', 'Bimp_FactureLine');
                            $new_line->validateArray(array(
                                'id_obj'      => (int) $new_avoir->id,
                                'type'        => ObjectLine::LINE_FREE,
                                'remisable'   => 0,
                                'pa_editable' => 0
                            ));

                            $new_line->qty = 1;
                            $new_line->desc = $line->desc;
                            $new_line->pu_ht = ($line->pu_ht / 1.2) * -1;
                            $new_line->tva_tx = 20;
                            $new_line->pa_ht = 0;

                            $line_warnings = array();
                            $line_errors = $new_line->create($line_warnings, true);

                            if (!empty($line_errors)) {
                                $lines_errors[] = BimpTools::getMsgFromArray($line_errors, 'Ligne "' . $line->desc . '"');
                            } elseif ((int) $new_line->getData('id_line')) {
                                if (preg_match('/^.+services$/', $line->desc))
                                    $bdb->update('facturedet', array(
                                        'product_type' => 1
                                            ), 'rowid = ' . (int) $new_line->getData('id_line'));
                            }
                        }

                        if (count($lines_errors)) {
                            echo BimpRender::renderAlerts($line_errors);
                        } else {
                            echo ' - <span class="success">Création NEW AVOIR OK</span>';
                            setElementElement('facture', 'facture', $fac->id, $new_avoir->id);
                            // Validation de l'avoir: 
                            if ($new_avoir->dol_object->validate($user, '', 0, 0) <= 0) {
                                echo BimpRender::renderAlerts(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($new_avoir->dol_object), 'Echec de la validation du nouvel avoir'), 'danger');
                            } else {
                                $new_avoir->fetch($new_avoir->id);
                                // Conversion en remise: 
                                $conv_errors = $new_avoir->convertToRemise();
                                if ($conv_errors) {
                                    echo BimpRender::renderAlerts(BimpTools::getMsgFromArray($conv_errors, 'ECHEC CONVERSION EN REMISE'));
                                } else {
                                    echo ' - <span class="success">CONV REM OK</span>';

                                    // Application de la remise à la facture: 
                                    $discount = new DiscountAbsolute($bdb->db);
                                    $discount->fetch(0, $new_avoir->id);

                                    if (BimpObject::objectLoaded($discount)) {
                                        if ($discount->link_to_invoice(0, $fac->id) <= 0) {
                                            echo BimpRender::renderAlerts(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($discount), 'ECHEC UTILISATION REMISE'));
                                        } else {
                                            echo ' - <span class="success">UTILISATION REM OK</span>';
                                        }
                                    }

                                    $fac->fetch($fac->id);
                                    $fac->checkIsPaid();

                                    $result[] = $avoir->getRef() . ' - ' . $fac->getRef() . ' - ' . $new_avoir->getRef();
                                }
                            }
                        }
                    }
                }
            }
        }
        echo '<br/>';
    }
}

echo '<br/>******************* <br/>';
echo 'RESULTATS <br/><br/>';

foreach ($result as $res) {
    echo $res . '<br/>';
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
