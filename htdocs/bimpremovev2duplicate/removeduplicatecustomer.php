<?php

/**
 *  \file       htdocs/bimpremovev2duplicate/removeduplicatecustomer.php
 *  \ingroup    bimpremovev2duplicate
 *  \brief      Page to remove duplicate in companies
 */
require '../main.inc.php';

$arrayofcss = array('/bimpremovev2duplicate/css/styles.css');
$arrayofjs = array('/bimpremovev2duplicate/js/removeduplicatecustomer.js');

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

print load_fiche_titre('Enlever doublon avancé', $linkback);

if (!$user->rights->societe->creer or ! $user->rights->societe->supprimer) {
    print "<strong>Vous n'avez pas les droits d'écriture pour les tiers.</strong>";
    return;
}

//if ($user->rights->BimpStatsFacture->factureCentre->read and ! $user->rights->BimpStatsFacture->facture->read and $is_customer)
//    print '<p>Vos droits vous permettent de voir les factures des centres suivants : ' . $centres_string . '</p>';
// Separator
print '<table class="noborder" width="100%"><tr/></table>';

print '<table class="filter"><tbody>';
print '<tr class="liste_titre_filter">';
print '<td><label>Nombre de tier à vérifier</label><br/>';
print '<input id="limit" type="number" min=1 class="flat" value=1000></td>';
print '<td><input id="display_duplicates" type="submit" class="butAction" value="Afficher doublons">';
print '<i id="spinner" style="display: none; font-size: 30px;" class="fa fa-spinner fa-spin"></i></td>';
print '<td><label>Nombre de tier non vérifiés</label><br/>';
print '<div id="db_duplicate"></td>';
print '<td><label>Temps d\'exécution (en seconde)</label><br/>';
print '<div id="time_exec"></td>';
print '<td><input id="init_duplicate" type="submit" class="butActionDelete" value="Réinitialiser les tiers">';
print '<i id="spinner_init" style="display: none; font-size: 30px;" class="fa fa-spinner fa-spin"></i></td>';
print '</tr></tbody</table>';

print '<table class="filter"><tbody>';
print '<tr class="liste_titre_filter">';
print '<td><label>Score minimum</label><br/>';
print '<input id="s_min" type="number" min=1 class="flat" value=200></td>';
print '<td><label>Score nom</label><br/>';
print '<input id="s_name" type="number" min=1 class="flat" value=150></td>';
print '<td><label>Score email</label><br/>';
print '<input id="s_email" type="number" min=1 class="flat" value=80></td>';
print '<td><label>Score adresse</label><br/>';
print '<input id="s_address" type="number" min=1 class="flat" value=60></td>';
print '<td><label>Score code postal</label><br/>';
print '<input id="s_zip" type="number" min=1 class="flat" value=40></td>';
print '<td><label>Score ville</label><br/>';
print '<input id="s_town" type="number" min=1 class="flat" value=40></td>';
print '<td><label>Score téléphone</label><br/>';
print '<input id="s_phone" type="number" min=1 class="flat" value=80></td>';
print '</tr></tbody</table>';

print '<table id="customer" class="liste_titre_filter">';
print '<tr id="tr_header" class="liste_titre last">';
$arrayofheader = array(
    '', 'Garder', 'Fusionner', 'Nom', 'Email', 'Adresse', 'Code Postal', 'Ville', 'Téléphone', 'Création', 'Commerciaux', 'Lien', 'Retour fusion'
);
displayHeader($arrayofheader);
print '</tr>';
print '</table>';

//print '<div class="alert alert-info" role="alert" style="display: inline-block;">';
//print '<strong>Remarque :</strong> Actuellement les tiers considérés comme étant<br/> des doublons sont les homonymes avec le même code postale.';
//print '</div>';


llxFooter();

$db->close();
