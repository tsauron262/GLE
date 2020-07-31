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
        'correct_prod_cur_pa'      => 'Corriger le champs "cur_pa_ht" des produits',
        'check_facs_paiement'      => 'Vérifier les stauts paiements des factures',
        'check_facs_remain_to_pay' => 'Recalculer tous les restes à payer'
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

    default:
        echo 'Action invalide';
        break;
}

echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
