<?php

/**
 *  \file       htdocs/bimpequipment/manageequipment/home.php
 *  \ingroup    bimpequipment
 *  \brief      Redirect user on multiple link
 */
include_once '../../main.inc.php';

include_once DOL_DOCUMENT_ROOT.'/bimpequipment/manageequipment/lib/entrepot.lib.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpequipment/manageequipment/css/transfertStyles.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpequipment/manageequipment/js/home.js');


/*
 * 	View
 */

llxHeader('', 'Accueil boutique', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Accueil boutique', $linkback);

$entrepots = getAllEntrepots($db);

print '<div id="shopDiv" style="float:left">';
print '<strong>Boutique</strong> ';
print '<select id="warehouseSelect" class="select2 cust" style="width: 200px;">';
print '<option></option>';
foreach ($entrepots as $id => $name) {
    print '<option value="' . $id . '">' . $name . '</option>';
}
print '</select> ';
print '</div>';

print '<br/>';

print '<br/><div id="allTheFiche" class="fadeInOut">';

print '</div>';
$db->close();

llxFooter();
