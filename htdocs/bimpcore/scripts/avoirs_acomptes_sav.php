<?php

die('Désactivé'); 

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);
ini_set('max_execution_time', 9000);
ini_set('memory_limit', '512M');
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

$file = DOL_DOCUMENT_ROOT . '/bimpcore/scripts/docs/regul_acomptes_sav.txt';
if (!file_exists($file)) {
    echo BimpRender::renderAlerts('Fichier KO: ' . $file);
    exit;
}

if (!BimpTools::getValue('exec', 0)) {
    echo BimpRender::renderAlerts('La création et validation des avoirs va être lancé', 'info');
    echo '<a class="btn btn-default" href="' . DOL_URL_ROOT . '/bimpcore/scripts/avoirs_acomptes_sav.php?exec=1">';
    echo 'Exécuter';
    echo '</a>';
    exit;
}

$refs = array();

foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $data = explode(';', $line);
    $refs[$data[0]] = $data[1];
}

global $db;
$bdb = new BimpDb($db);

foreach ($refs as $ref => $code_compta) {
    $acompte = BimpCache::findBimpObjectInstance('bimpcommercial', 'Bimp_Facture', array(
                'facnumber' => $ref
    ));

    if (!BimpObject::objectLoaded($acompte)) {
        echo BimpRender::renderAlerts('Acompte "' . $ref . '" non trouvé');
    } else {
        echo 'Acompte #' . $acompte->id . ' - ' . $ref . ': ';
        $id_soc = (int) $acompte->getData('fk_soc');
        $soc_code_compta = $bdb->getValue('societe', 'code_compta', 'rowid = ' . $id_soc);

        if ($soc_code_compta != $code_compta) {
            $id_soc = (int) $bdb->getValue('societe', 'row_id', 'code_compta = \'' . $code_compta . '\'');
            if (!$id_soc) {
                $soc = $acompte->getChildObject('client');
                if (BimpObject::objectLoaded($soc)) {
                    $soc->updateField('code_compta', $code_compta);
                    echo ' [CHANGEMENT CODE COMPTA CLIENT] ';
                } else {
                    echo ' <span class="danger">[CLIENT NON TROUVE]</span> ';
                }
            } else {
                echo ' [NOUVEAU CLIENT: ' . $id_soc . '] ';
            }
        }

        $errors = array();
        $warnings = array();
        $avoir = BimpObject::createBimpObject('bimpcommercial', 'Bimp_Facture', array(
                    'fk_facture_source' => $acompte->id,
                    'type'              => 0,
                    'fk_soc'            => (int) $acompte->getData('fk_soc'),
                    'entrepot'          => (int) $acompte->getData('entrepot'),
                    'contact_id'        => (int) $acompte->getData('contact_id'),
                    'fk_account'        => (int) $acompte->getData('fk_account'),
                    'ef_type'           => ($acompte->getData('ef_type') ? $acompte->getData('ef_type') : 'S'),
                    'datef'             => '2020-09-30',
                    'libelle'           => 'Régularisation comptable acompte ' . $acompte->getRef(),
                    'relance_active'    => 0,
                    'fk_cond_reglement' => 1,
                    'fk_mode_reglement' => 4
                        ), true, $errors, $warnings);

        if (!BimpObject::objectLoaded($avoir)) {
            echo '<span class="danger">ECHEC</span>';
            if (count($errors)) {
                echo BimpRender::renderAlerts($errors);
            }
            if (count($warnings)) {
                echo BimpRender::renderAlerts($warnings, 'warning');
            }
        } else {
            // Copie des contacts: 
            $warnings = array();
            // Probablement pas nécessaire: 
//            $avoir->copyContactsFromOrigin($acompte, $warnings);
//            if (count($warnings)) {
//                echo BimpRender::renderAlerts($warnings, 'warning');
//            }
            // Copie des lignes: 
            $line_errors = $avoir->createLinesFromOrigin($acompte, array(
                'inverse_prices' => true,
                'pa_editable'    => false
            ));

            if (count($line_errors)) {
                echo BimpRender::renderAlerts($line_errors);
            } else {
                echo '<span class="success">Création OK</span>';
                setElementElement('facture', 'facture', $avoir->id, $acompte->id);
                // Validation de l'avoir: 
//                $avoir->force_rmb_ref = true;
                if ($avoir->dol_object->validate($user, '', 0, 0) <= 0) {
                    echo BimpRender::renderAlerts(BimpTools::getMsgFromArray(BimpTools::getErrorsFromDolObject($avoir->dol_object), 'Echec de la validation de l\'avoir'), 'danger');
                } else {
                    echo ' - <span class="success">Validation OK</span>';
                    if ($avoir->dol_object->set_paid($user) <= 0) {
                        echo ' - <span class="danger">Echec classé payé</span>';
                    } else {
                        echo ' - <span class="success">Classé payé OK</span>';
                    }

                    $bdb->update('facture', array(
                        'date_valid' => '2020-09-30 00:00:00'
                            ), 'rowid = ' . $avoir->id);
                }
            }
        }
        echo '<br/>';
    }
}


echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
