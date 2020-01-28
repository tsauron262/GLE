<?php

/**
 *  \file       htdocs/bimpremoveduplicate/removeduplicatecustomer.php
 *  \ingroup    bimpremoveduplicate
 *  \brief      Page to remove duplicate in companies
 */
require '../main.inc.php';

$arrayofcss = array('/bimpremoveduplicate/css/styles.css');
$arrayofjs = array('/bimpremoveduplicate/js/removeduplicatecustomer.js');

/**
 * Functions
 */
function displayHeader($arrayofheader) {
    foreach ($arrayofheader as $title) {
        print '<th>' . $title . '</th>';
    }
}

/*
 * 	View
 */

llxHeader('', 'Enlever doublon', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Enlever doublon', $linkback);

if (!$user->rights->societe->creer or ! $user->rights->societe->supprimer) {
    print "<strong>Vous n'avez pas les droits d'écriture pour les tiers.</strong>";
    return;
}

//if ($user->rights->BimpStatsFacture->factureCentre->read and ! $user->rights->BimpStatsFacture->facture->read and $is_customer)
//    print '<p>Vos droits vous permettent de voir les factures des centres suivants : ' . $centres_string . '</p>';
// Separator
print '<table class="noborder" width="100%"><tr/></table>';

print '<table><tbody>';
print '<tr class="liste_titre_filter">';
print '<td style="padding:10px;"><label>Nombre de groupes de doublons à afficher</label><br/>';
print '<input id="limit" type="number" min=1 max=100 class="flat" value=30></td>';
print '<td style="padding:10px;"><input id="display_duplicates" type="submit" class="butAction" value="Afficher doublons">';
print '<i id="spinner" style="display: none; font-size: 30px;" class="fa fa-spinner fa-spin"></i></td>';
print '<td style="padding:10px;"><label>Nombre de groupes de doublons total</label><br/>';
print '<div id="db_duplicate"></td>';
print '</tr></tbody</table>';

print '<table id="customer" class="liste_titre_filter">';
print '<tr id="tr_header" class="liste_titre last">';
$arrayofheader = array(
    '', 'Garder', 'Fusionner', 'Nom', 'Email', 'Adresse', 'Code Postal', 'Ville', 'Téléphone', 'Commerciaux', 'Lien', 'Retour fusion'
);
displayHeader($arrayofheader);
print '</tr>';
print '</table>';

print '<div class="alert alert-info" role="alert" style="display: inline-block;">';
print '<strong>Remarque :</strong> Actuellement les tiers considérés comme étant<br/> des doublons sont les homonymes avec le même code postal.';
print '</div>';


llxFooter();

$db->close();
