<?php

/**
 *  \file       htdocs/bimpstatsfacture/view.php
 *  \ingroup    bimpstatsfacture
 *  \brief      Page of stats facture
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpstatsfacture/class/BimpStatsFacture.class.php';

$langs->load("admin");

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpstatsfacture/css/styles.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpstatsfacture/js/ajax.js');

$recursif = GETPOST('recursif');

/*
 * 	View
 */

$staticSF = new BimpStatsFacture($db);

llxHeader('', 'Stats factures', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Stats factures', $linkback);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '</tr>' . "\n";
print '</table>';

print '<table class="tableforField">';

print '<tr class="top"><td rowspan=3 class="allSides">Filtres</td><td>Dates</td><td>';
print '<div><text>Date de début</text><br>';
print '<input id="dateStart" type="text" class="isDate round"></div>';

print '<div><text>Date de fin</text><br>';
print '<input id="dateEnd" type="text" class="isDate round"></div></td></tr>';


$type = $staticSF->getExtrafieldArray('facture', 'type');
print '<tr><td>Types</td><td><select id="type" class="select2" multiple style="width: 200px;">';

foreach ($type as $val => $name) {
    print '<option selected value="'.$val.'">'.$name.'</option>';
}
print '</select>';


print '<input id="selectAllTypes"   type="button" class="butAction round" value="Tout sélectionner">';
print '<input id="deselectAllTypes" type="button" class="butActionDelete round" value="Vider"></td></tr>';


$centre = $staticSF->getExtrafieldArray('facture', 'centre');

print '<tr><td>Centres</td><td><select id="centre" class="select2 round" multiple style="width: 200px;">';
foreach ($centre as $val => $name) {
    print '<option selected value="'.$val.'">'.$name.'</option>';
}
print '</select>';

print '<input id="selectAllCentres"   type="button" class="butAction round" value="Tout sélectionner">';
print '<input id="deselectAllCentres" type="button" class="butActionDelete round" value="Vider"></td></tr>';

print '<tr class="top"><td rowspan=2 class="allSides">Config</td><td>Statut (unique)</td><td>
<input id="paymentAll" name="statutPayment" type="radio" value="a" checked>
<label for="paymentAll">Toutes</label>

<input id="paymentPayed" name="statutPayment" type="radio" value="p" >
<label for="paymentPayed">Payées</label>

<input id="paymentUnpayed" name="statutPayment" type="radio" value="u" >
<label for="paymentUnpayed">Impayées</label>
</td></tr>';

print '<tr><td>Prix (unique)</td><td>
<input id="priceTTC" name="priceTaxes" type="radio" value="ttc" checked>
<label for="priceTTC">TTC</label>

<input id="priceHT" name="priceTaxes" type="radio" value="ht" >
<label for="priceHT">HT</label>
</td></tr>';

print '<tr class="top bottom" ><td class="allSides">Tri</td><td>Trier par (multiple)</td><td>

<input id="sortByType" name="sortBy" type="checkbox" value="t">
<label for="sortByType">Type</label>

<input id="sortByCentre" name="sortBy" type="checkbox" value="c" >
<label for="sortByCentre">Centre</label>
</td></tr>';

print '</table>';

print '<br><input id="go" type="button" class="butAction round" value="Valider"><br>';

print '<div id="forArray"></div>';




llxFooter();

$db->close();
