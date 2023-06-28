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

$bdb = new BimpDb($db);
$action = BimpTools::getValue('action', '');

if (!$action) {
    $actions = array();

    $path = pathinfo(__FILE__);

    $nb = (int) $bdb->getCount('product', '1', 'rowid');
    if ($nb) {
        $action['del_prods'] = 'Suppr. produits (' . $nb . ')';
    }

    $nb = (int) $bdb->getCount('be_equipment');
    if ($nb) {
        $action['del_eqs'] = 'Suppr. équipements (' . $nb . ')';
    }
    
    $nb = (int) $bdb->getCount('bs_sav');
    if ($nb) {
        $action['del_savs'] = 'Suppr. SAVs (' . $nb . ')';
    }
    
    $nb = (int) $bdb->getCount('propal', '1', 'rowid');
    if ($nb) {
        $action['del_propales'] = 'Suppr. propales (' . $nb . ')';
    }
    
    $nb = (int) $bdb->getCount('commande', '1', 'rowid');
    if ($nb) {
        $action['del_commandes'] = 'Suppr. commandes (' . $nb . ')';
    }
    
    $nb = (int) $bdb->getCount('facture', '1', 'rowid');
    if ($nb) {
        $action['del_facs'] = 'Suppr. factures (' . $nb . ')';
    }
    
    $nb = (int) $bdb->getCount('commande_fournisseur', '1', 'rowid');
    if ($nb) {
        $action['del_commandes_fourn'] = 'Suppr. commandes fourn (' . $nb . ')';
    }
    
    $nb = (int) $bdb->getCount('facture_fourn', '1', 'rowid');
    if ($nb) {
        $action['del_facs_fourn'] = 'Suppr. factures fourn (' . $nb . ')';
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

echo '<br/>';
echo $nOk . 'OK<br/>';
echo $nFails . ' échecs <br/>';
echo '<br/>FIN';

echo '</body></html>';

//llxFooter();
