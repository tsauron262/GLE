<?php

require_once("../../main.inc.php");

ini_set('display_errors', 1);
require_once __DIR__ . '/../Bimp_Lib.php';
set_time_limit(0);

ignore_user_abort(0);

top_htmlhead('', 'QUICK SCRIPTS', 0, 0, array(), array());

echo '<body>';

BimpCore::displayHeaderFiles();

global $db, $user;

if (!BimpObject::objectLoaded($user)) {
    echo BimpRender::renderAlerts('Aucun utilisateur connecté');
    exit;
}

if (!$user->admin) {
    echo BimpRender::renderAlerts('Seuls les admin peuvent exécuter ce script');
}

$action = BimpTools::getValue('action', '');

if (!$action) {
    $actions = array(
        'correct_prod_cur_pa'          => 'Corriger le champs "cur_pa_ht" des produits',
        'check_facs_paiement'          => 'Vérifier les statuts paiements des factures',
        'check_facs_remain_to_pay'     => 'Recalculer tous les restes à payer',
        'check_clients_solvabilite'    => 'Vérifier les statuts solvabilité des clients',
        'check_commandes_status'       => 'Vérifier les statuts des commandes client',
        'check_commandes_fourn_status' => 'Vérifier les statuts des commandes fournisseur',
        'change_prods_refs'            => 'Corriger refs produits'
    );


    $path = pathinfo(__FILE__);

    foreach ($actions as $code => $label) {
        echo '<div style="margin-bottom: 10px">';
        echo '<a href="' . DOL_URL_ROOT . '/bimpcore/scripts/' . $path['basename'] . '?action=' . $code . '" class="btn btn-default">';
        echo $label . BimpRender::renderIcon('fas_arrow-circle-right', 'iconRight');
        echo '</a>';
        echo '</div>';
    }
    exit;
}

switch ($action) {
    case 'correct_prod_cur_pa':
        BimpObject::loadClass('bimpcore', 'Bimp_Product');
        Bimp_Product::correctAllProductCurPa(true, true);
        break;

    case 'check_facs_paiement':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        Bimp_Facture::checkIsPaidAll();
        break;

    case 'check_facs_remain_to_pay':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        Bimp_Facture::checkRemainToPayAll();
        break;

    case 'check_commandes_status':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Commande');
        Bimp_Commande::checkStatusAll();
        break;

    case 'check_commandes_fourn_status':
        BimpObject::loadClass('bimpcommercial', 'Bimp_CommandeFourn');
        Bimp_CommandeFourn::checkStatusAll();
        break;

    case 'check_clients_solvabilite':
        BimpObject::loadClass('bimpcore', 'Bimp_Societe');
        Bimp_Societe::checkSolvabiliteStatusAll();
        break;

    case 'change_prods_refs':
        $bdb = new BimpDb($db);
        $lines = file(DOL_DOCUMENT_ROOT . '/bimpcore/convert_file.txt', FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

        foreach ($lines as $line) {
            $data = explode(':', $line);

            if ($data[0] === $data[1]) {
                continue;
            }

            if ($bdb->update('product', array(
                        'ref' => $data[1]
                            ), 'ref = \'' . $data[0] . '\'') < 0) {
                echo 'ECHEC ' . $data[0];
            } else {
                echo 'OK ' . $data[1];
            }

            echo '<br/>';
        }
        break;

    default:
        echo 'Action invalide';
        break;
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
