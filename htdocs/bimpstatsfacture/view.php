<?php

/**
 *  \file       htdocs/bimpstatsfacture/view.php
 *  \ingroup    bimpstatsfacture
 *  \brief      Page of stats facture
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

require_once DOL_DOCUMENT_ROOT . '/bimpstatsfacture/class/BimpStatsFacture.class.php';

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

// Dates
print '<tr class="top"><td rowspan=5 class="allSides">Filtres</td><td>Dates</td><td>';
print '<div><text>Date de début</text><br>';
print '<input id="dateStart" type="text" class="isDate round"></div>';

print '<div><text>Date de fin</text><br>';
print '<input id="dateEnd" type="text" class="isDate round"></div></td></tr>';

// Types
$type = $staticSF->getExtrafieldArray('facture', 'type');
print '<tr><td>Types</td><td>';
print '<select id="type" class="select2" multiple style="width: 200px;">';
print '<option  value="NRS">Non renseigné</option>';
foreach ($type as $val => $name) {
    print '<option value="'.$val.'">'.$name.'</option>';
}
print '</select>';


print '<input id="selectAllTypes"   type="button" class="butAction round" value="Tout sélectionner">';
print '<input id="deselectAllTypes" type="button" class="butActionDelete round" value="Vider"></td></tr>';

// Centres
$centre = $staticSF->getExtrafieldArray('facture', 'centre');
print '<tr><td>Centres</td><td>';
print '<select id="centre" class="select2 round" multiple style="width: 200px;">';
print '<option  value="NRS">Non renseigné</option>';
foreach ($centre as $val => $name) {
    print '<option value="'.$val.'">'.$name.'</option>';
}
print '</select>';

print '<input id="selectAllCentres"   type="button" class="butAction round" value="Tout sélectionner">';
print '<input id="deselectAllCentres" type="button" class="butActionDelete round" value="Vider"></td></tr>';

// Etats
$facstatic = new Facture($db);
print '<tr><td>Etat (multiple)</td><td>

<input id="etatBrouillon" name="etat" type="checkbox" value="'.$facstatic::STATUS_DRAFT.'" checked>
<label for="etatBrouillon">Brouillons</label>

<input id="etatValider" name="etat" type="checkbox" value="'.$facstatic::STATUS_VALIDATED.'" checked>
<label for="etatValider">Validées</label>

<input id="etatFermer" name="etat" type="checkbox" value="'.$facstatic::STATUS_CLOSED.'" checked>
<label for="etatFermer">Fermées</label>

<input id="etatAbandonner" name="etat" type="checkbox" value="'.$facstatic::STATUS_ABANDONED.'" >
<label for="etatAbandonner">Abandonnées</label>

</td></tr>';

// Statuts
print '<tr><td>Statut (unique)</td><td>
<input id="paymentAll" name="statutPayment" type="radio" value="a" checked>
<label for="paymentAll">Toutes</label>

<input id="paymentPayed" name="statutPayment" type="radio" value="p" >
<label for="paymentPayed">Payées</label>

<input id="paymentUnpayed" name="statutPayment" type="radio" value="u" >
<label for="paymentUnpayed">Impayées</label>
</td></tr>';

// Prix
print '<tr class="top"><td rowspan=2 class="allSides">Config</td><td>Prix (unique)</td><td>
<input id="priceHT" name="priceTaxes" type="radio" value="ht" checked>
<label for="priceHT">HT</label>

<input id="priceTTC" name="priceTaxes" type="radio" value="ttc">
<label for="priceTTC">TTC</label>
</td></tr>';

// Format
print '<tr><td>Format (unique)</td><td>
<input id="formatDetail" name="formatOutput" type="radio" value="d" checked>
<label for="formatDetail">HTML détail</label>

<input id="formatReduit" name="formatOutput" type="radio" value="r">
<label for="formatReduit">HTML réduit</label>

<input id="formatCSV" name="formatOutput" type="radio" value="c" >
<label for="formatCSV">CSV</label>
</td></tr>';

// Trier par
print '<tr class="top bottom" ><td class="allSides">Tri</td><td>Trier par (multiple)</td><td>

<input id="sortByType" name="sortBy" type="checkbox" value="t">
<label for="sortByType">Type</label>

<input id="sortByCentre" name="sortBy" type="checkbox" value="c" >
<label for="sortByCentre">Centre</label>';

//print '<input id="sortByTypeGarantie" name="sortBy" type="checkbox" value="tg" >
//<label for="sortByTypeGarantie">Type de garantie</label>
//
//<input id="sortByEquipement" name="sortBy" type="checkbox" value="e" >
//<label for="sortByEquipement">Equipement</label>';

print '</td></tr>';

print '</table>';

// Valider
print '<br><input id="go" type="button" class="butAction round" value="Valider"><br>';
print ' <div id="waiting"></div><br>';

print '<div id="forArray"></div>';




llxFooter();

$db->close();
