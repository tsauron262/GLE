<?php

/**
 *  \file       htdocs/bimpticketrestaurant/view.php
 *  \ingroup    bimpticketrestaurant
 *  \brief      Page ticket restaurant
 */
require '../main.inc.php';
//require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

$form = new Form($db);


$arrayofcss = array('/bimpticketrestaurant/css/styles.css');
$arrayofjs = array('/bimpticketrestaurant/js/ticketrestaurant.js');

/*
 * 	View
 */

llxHeader('', 'Tickets restaurant', '', '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Tickets restaurant', $linkback);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '</tr>' . "\n";
print '</table>';

print '<table id="table_setting">';

print '<tr/><td><label for="userid">Utilisateur</label></td><td>';
$form->select_users();
print '</td></tr>';

print '</table>';

print '<input id="get_ticket" class="butAction" value="Obtenir ticket"></input>';


// TODO 
//if (!$user->rights->bimpticketrestaurant->ticketRestaurant->read )




llxFooter();

//$db->close();
