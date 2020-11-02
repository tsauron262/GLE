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
        'change_prods_refs'            => 'Corriger refs produits',
//        'check_vente_paiements'        => 'Vérifier les paiements des ventes en caisse',
        'check_factures_rg'            => 'Vérification des Remmises globales factures',
        'traite_obsolete'              => 'Traitement des produit obsoléte hors stock',
        'cancel_factures'              => 'Annulation factures'
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
    case 'traite_obsolete':
        global $db;
        $sql = $db->query("SELECT DISTINCT (a.rowid) FROM llx_product a LEFT JOIN llx_product_extrafields ef ON a.rowid = ef.fk_object WHERE (a.stock = '0' || a.stock is null) AND a.tosell IN ('1') AND (ef.famille = 3097) ORDER BY a.ref DESC");
        while ($ln = $db->fetch_object($sql))
            $db->query("UPDATE `llx_product` SET `tosell` = 0, `tobuy` = 0 WHERE rowid = " . $ln->rowid);
        break;
        
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
        Bimp_Facture::checkRemainToPayAll(true);
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

    case 'check_factures_rg':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        Bimp_Facture::checkRemisesGlobalesAll(true, true);
        break;
    
    case 'cancel_factures':
        BimpObject::loadClass('bimpcommercial', 'Bimp_Facture');
        Bimp_Facture::cancelFacturesFromRefsFile(DOL_DOCUMENT_ROOT.'/bimpcore/scripts/docs/factures_to_cancel.txt', true);
        break;

    default:
        echo 'Action invalide';
        break;
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
