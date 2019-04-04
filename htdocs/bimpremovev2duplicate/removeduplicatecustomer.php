<?php

/**
 *  \file       htdocs/bimpremovev2duplicate/removeduplicatecustomer.php
 *  \ingroup    bimpremovev2duplicate
 *  \brief      Page to remove duplicate in companies
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

$arrayofcss = array('/includes/jquery/plugins/select2/select2.css', '/bimpremovev2duplicate/css/styles.css');
$arrayofjs = array('/bimpremovev2duplicate/js/removeduplicatecustomer.js');


$commerciaux = array();
$sql = 'SELECT rowid, lastname, firstname';
$sql .= ' FROM ' . MAIN_DB_PREFIX . 'user';

$result = $db->query($sql);
while ($obj = $db->fetch_object($result)) {
    $commerciaux[$obj->rowid] = $obj->firstname . ' ' . $obj->lastname;
}

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


// Separator
print '<table class="noborder" width="100%"><tr/></table>';
if (!$user->rights->societe->creer or ! $user->rights->societe->supprimer) {
    print "<strong>Vous n'avez pas les droits d'écriture pour les tiers.</strong>";
    return;
}
print '<table class="filter"><tbody>';
print '<tr class="liste_titre_filter">';
print '<td><label>Score minimum</label><br/>';
print '<input id="s_min" type="number" min=1 class="flat" value=200></td>';
print '<td><label>Score nom</label><br/>';
print '<input id="s_name" type="number" min=1 class="flat" value=180></td>';
print '<td><label>Score email</label><br/>';
print '<input id="s_email" type="number" min=1 class="flat" value=200></td>';
print '<td><label>Score adresse</label><br/>';
print '<input id="s_address" type="number" min=1 class="flat" value=60></td>';
print '<td><label>Score code postal</label><br/>';
print '<input id="s_zip" type="number" min=1 class="flat" value=20></td>';
print '<td><label>Score ville</label><br/>';
print '<input id="s_town" type="number" min=1 class="flat" value=20></td>';
print '<td><label>Score téléphone</label><br/>';
print '<input id="s_phone" type="number" min=1 class="flat" value=200></td>';
print '<td><label>Score siret</label><br/>';
print '<input id="s_siret" type="number" min=1 class="flat" value=200></td>';
print '</tr></tbody</table>';

print '<div class="alert alert-info" role="alert" style="display: inline-block;">';
print "<strong>Remarque :</strong> Les champs ci-dessous sont utilisés pour savoir si un tier est le doublon d'un autre.<br/>";
print "Si un champ d'un tier match avec le même champ d'un autre tier, on ajoute le score de ce champ.<br/>";
print "Si le score total est égal ou supérieur au score minimum renseigné, alors les tiers sont affichés dans un tableau.";

print '<table class="filter"><tbody>';
print '<tr class="liste_titre_filter">';
print '<td><label>Nombre de tier à vérifier</label><br/>';
print '<input id="limit" type="number" min=1 class="flat" value=50></td>';

print '<td><label>Commerciaux</label><br/>';
print '<select id="commercial" class="select2" multiple style="width: 200px;">';
foreach ($commerciaux as $id => $name) {
    print '<option value="' . $id . '">' . $name . '</option>';
}
print '</select>';


print '<td><input id="display_duplicates" type="submit" class="butAction" value="Afficher doublons">';
print '<i id="spinner" style="display: none; font-size: 30px;" class="fa fa-spinner fa-spin"></i></td>';
print '<td><label>Nombre de tier non vérifiés</label><br/>';
print '<div id="db_duplicate"></td>';
print '<td><label>Progression</label><br/>';
print '<div id="progress"></td>';
print '<td><label>Temps d\'exécution</label><br/>';
print '<div id="time_exec"></td>';
print '<td><input id="init_duplicate" type="submit" class="butActionDelete" value="Réinitialiser les tiers">';
print '<i id="spinner_init" style="display: none; font-size: 30px;" class="fa fa-spinner fa-spin"></i></td>';
print '</tr></tbody</table>';


print '</div>';

print '<table id="customer" class="liste_titre_filter">';
print '<tr id="tr_header" class="liste_titre last">';
$arrayofheader = array(
    '', 'Garder', 'Fusionner', 'Ignorer', 'Nom', 'Email', 'Adresse', 'Code Postal', 'Ville', 'Téléphone', 'Siret', 'Création', 'Commerciaux', 'Lien', 'Retour fusion'
);
displayHeader($arrayofheader);
print '</tr>';
print '</table>';


llxFooter();

$db->close();
