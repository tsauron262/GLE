<?php

/**
 *  \file       htdocs/bimpstatsfacture/view.php
 *  \ingroup    bimpstatsfacture
 *  \brief      Page of stats facture
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpstatsfacture/class/BimpStatsFacture.class.php';
require_once DOL_DOCUMENT_ROOT . '/bimpstatsfacture/class/BimpStatsFactureFournisseur.class.php';

$object = GETPOST('object');
$is_customer = false;

$staticSF = new BimpStatsFacture($db);
$entrepots = $staticSF->getAllEntrepots();
$centres = $staticSF->getExtrafieldArray('facture', 'centre');

if (!$user->rights->bimpstatsfacture->factureCentre->read and ! $user->rights->bimpstatsfacture->facture->read)
    accessforbidden();

if ($user->rights->bimpstatsfacture->factureCentre->read and ! $user->rights->bimpstatsfacture->facture->read) {
    $centres = $staticSF->parseCenter($user, $centres);
    $centres_string = '';
    foreach ($centres as $centre)
        $centres_string .= $centre . ', ';
    $centres_string = substr($centres_string, 0, -2);
}


if ($object == 'facture_fournisseur') {
    $nb_row_filter = 4; // remove type place and sector
} else {
    $is_customer = true;
    $nb_row_filter = 7;
}

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpstatsfacture/css/styles.css');
$arrayofjs = array('/includes/jquery/plugins/select2/select2.js', '/bimpstatsfacture/js/ajax.js');

$recursif = GETPOST('recursif');

/*
 * 	View
 */

llxHeader('', 'Stats ' . str_replace('_', ' ', $object), '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Stats ' . str_replace('_', ' ', $object), $linkback);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '</tr>' . "\n";
print '</table>';

if ($user->rights->bimpstatsfacture->factureCentre->read and ! $user->rights->bimpstatsfacture->facture->read and $is_customer)
    print '<p>Vos droits vous permettent de voir les factures des centres suivants : ' . $centres_string . '</p>';

print '<table class="tableforField">';

/**
 * Filters
 */
// Dates
print '<tr class="top"><td rowspan=' . $nb_row_filter . ' class="allSides">Filtres</td><td>Dates</td><td>';
print '<div><text>Date de début</text><br>';
print '<input id="dateStart" type="text" class="isDate round"></div>';

print '<div><text>Date de fin</text><br>';
print '<input id="dateEnd" type="text" class="isDate round"></div></td></tr>';

if ($is_customer) {
// Types
    $type = $staticSF->getExtrafieldArray('facture', 'type');

    
    
    print '<tr><td>Secteurs</td><td>';
    print '<select id="type" class="select2" multiple style="width: 200px;">';
    print '<option  value="NRS">Non renseigné</option>';
    foreach ($type as $val => $name) {
        print '<option value="' . $val . '">' . $name . '</option>';
    }
    print '</select>';

    print '<input id="selectAllTypes"   type="button" class="butAction round" value="Tout sélectionner">';
    print '<input id="deselectAllTypes" type="button" class="butActionDelete round" value="Vider"></td></tr>';
}


if ($is_customer) {
    print '<tr><td>Type de lieu</td><td>
<input id="place_centre" name="place" type="radio" value="c">
<label for="place_centre">Centre</label>

<input id="place_entrepot" name="place" type="radio" value="e" checked>
<label for="place_entrepot">Entrepôt</label>
</td></tr>';
}


// Centres
print '<tr id="tr_centre" style="display:none;"><td>Centre</td><td>';
print '<select id="centre" class="select2 round" multiple style="width: 200px;">';
foreach ($centres as $val => $name) {
    print '<option value="' . $val . '">' . $name . '</option>';
}
print '</select>';

print '<input id="selectAllCentres"   type="button" class="butAction round" value="Tout sélectionner">';
print '<input id="deselectAllCentres" type="button" class="butActionDelete round" value="Vider"></td></tr>';

print '<tr id="tr_entrepot"><td>Entrepôt</td><td>';
print '<select id="entrepot" class="select2 round" multiple style="width: 200px;">';
print '<option  value="NRS">Non renseigné</option>';
foreach ($entrepots as $val => $name) {
    print '<option value="' . $val . '">' . $name . '</option>';
}
print '</select>';

print '<input id="selectAllEntrepots"   type="button" class="butAction round" value="Tout sélectionner">';
print '<input id="deselectAllEntrepots" type="button" class="butActionDelete round" value="Vider"></td></tr>';

// Etats
$facstatic = new Facture($db);
print '<tr><td>Etat (multiple)</td><td>

<input id="etatBrouillon" name="etat" type="checkbox" value="' . $facstatic::STATUS_DRAFT . '" checked>
<label for="etatBrouillon">Brouillons</label>

<input id="etatValider" name="etat" type="checkbox" value="' . $facstatic::STATUS_VALIDATED . '" checked>
<label for="etatValider">Validées</label>

<input id="etatFermer" name="etat" type="checkbox" value="' . $facstatic::STATUS_CLOSED . '" checked>
<label for="etatFermer">Fermées</label>

<input id="etatAbandonner" name="etat" type="checkbox" value="' . $facstatic::STATUS_ABANDONED . '" >
<label for="etatAbandonner">Abandonnées</label>

</td></tr>

<tr><td>Type (multiple)</td><td>

<input id="typeFacture" name="type" type="checkbox" value="' . $facstatic::TYPE_STANDARD . '" checked>
<label for="typeFacture">Facture</label>

<input id="typeAvoir" name="type" type="checkbox" value="' . $facstatic::TYPE_CREDIT_NOTE . '" checked>
<label for="typeAvoir">Avoir</label>

<input id="typeAcompte" name="type" type="checkbox" value="' . $facstatic::TYPE_DEPOSIT . '">
<label for="typeAcompte">Acompte</label>


</td></tr>';
//}
// Statuts
print '<tr><td>Statut (unique)</td><td>
<input id="paymentAll" name="statutPayment" type="radio" value="a" checked>
<label for="paymentAll">Toutes</label>

<input id="paymentPayed" name="statutPayment" type="radio" value="p" >
<label for="paymentPayed">Payées</label>

<input id="paymentUnpayed" name="statutPayment" type="radio" value="u" >
<label for="paymentUnpayed">Impayées</label>
</td></tr>';

/**
 * Config
 */
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
<label for="formatDetail">HTML détaillé</label>

<input id="formatReduit" name="formatOutput" type="radio" value="r">
<label for="formatReduit">HTML réduit</label>

<input id="formatCSV" name="formatOutput" type="radio" value="c" >
<label for="formatCSV">CSV</label>

<div id="divFichier" style="display:none">Nom du fichier de sortie <input class="round" id="nomFichier"></input><strong>.csv</strong></div>
</td></tr>';

/**
 * Tri
 */
// Trier par
print '<tr class="top bottom" ><td class="allSides">Tri</td><td>Trier par (multiple)</td><td>

<input id="sortByCentre" name="sortBy" type="checkbox" value="c" >
<label for="sortByCentre">Entrepôt</label>';

if ($is_customer) {
    print '<input id="sortByType" name="sortBy" type="checkbox" value="t">
<label for="sortByType">Secteur</label>';

    print '<input id="sortByTypeGarantie" name="sortBy" type="checkbox" value="g" >
<label for="sortByTypeGarantie">Type de garantie</label>

<input id="sortByEquipement" name="sortBy" type="checkbox" value="e" >
<label for="sortByEquipement">Equipement</label>';
}
print '</td></tr>';

print '</table>';

// Valider
print '<br><input id="go" type="button" class="butAction round" value="Valider"><br>';
print ' <div id="waiting"></div><br>';

print ' <div id="linkCsv"></div><br>';

print '<h3>Sommaire</h3>';
print '<div id="sommaire" style="display:block"></div>';

print '<div id="forArray"></div>';




llxFooter();

$db->close();
