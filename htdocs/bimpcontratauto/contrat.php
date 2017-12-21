<?php

/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/societe/agenda.php
 *  \ingroup    societe
 *  \brief      Page of third party events
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcontratauto/class/BimpContratAuto.class.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';


$arrayofcss = array('/bimpcontratauto/css/BimpContratAuto.css');
$arrayofjs = array('/bimpcontratauto/js/ajax.js');


$socid = GETPOST('socid', 'int');


/*
 * 	View
 */

llxHeader('', 'Contrat Auto', $help_url, '', 0, 0, $arrayofjs, $arrayofcss);

if ($socid > 0) {
    $langs->load("companies");
    $object = new Societe($db);
    $result = $object->fetch($socid);
    $head = societe_prepare_head($object);
    dol_fiche_head($head, 'contratauto', $langs->trans("ThirdParty"), -1, 'company');
    $linkback = '<a href="' . DOL_URL_ROOT . '/societe/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';
    dol_banner_tab($object, 'socid', $linkback, ($user->societe_id ? 0 : 1), 'rowid', 'nom');
    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';
}

print'
<h3> Contrats actifs</h3>

<div id="containerForActif" class="customContainer">
</div>

<h3> Contrats inactifs</h3>

<div id="containerForInactif" class="customContainer">
</div>

<h3>Nouveau contrat</h3>

<h5>Services</h5>
';

print '<div id="invisibleDiv">';

/* Assistance */
print '<div id=1 class="customDiv containerWithBorder">';
print '<div class="customDiv fixDiv">Assistance</div><br>';
print '<div name="assistance" class="customDiv divClikable isSelected" value=0 >Non</div>';
print '<div name="assistance" class="customDiv divClikable" value=12 >12</div>';
print '<div name="assistance" class="customDiv divClikable" value=24 ">24</div>';
print '<div name="assistance" class="customDiv divClikable" value=36 >36</div>';
print '</div>';

/* Pneumatique */
print '<div id=2 class="customDiv containerWithBorder">';
print '<div class="customDiv fixDiv">Pneumatique</div><br>';
print '<div name="pneumatique" class="customDiv divClikable isSelected" value=0 >Non</div>';
print '<div name="pneumatique" class="customDiv divClikable" value=12 >12</div>';
print '<div name="pneumatique" class="customDiv divClikable" value=24 ">24</div>';
print '<div name="pneumatique" class="customDiv divClikable" value=36 >36</div>';
print '</div>';

/* Maintenance */
print '<div id=3 class="customDiv containerWithBorder">';
print '<div class="customDiv fixDiv">Maintenance</div><br>';
print '<div name="maintenance" class="customDiv divClikable isSelected" value=0 >Non</div>';
print '<div name="maintenance" class="customDiv divClikable" value=12 >12</div>';
print '<div name="maintenance" class="customDiv divClikable" value=24 ">24</div>';
print '<div name="maintenance" class="customDiv divClikable" value=36 >36</div>';
print '</div>';

/* Extension */
print '<div id=4 class="customDiv containerWithBorder">';
print '<div name="extension" class="customDiv fixDiv">Extension</div><br>';
print '<div name="extension" class="customDiv divClikable isSelected" value=0 >Non</div>';
print '<div name="extension" class="customDiv divClikable" value=12 >12</div>';
print '<div name="extension" class="customDiv divClikable" value=24 ">24</div>';
print '<div name="extension" class="customDiv divClikable" value=36 >36</div>';
print '</div>';

/* Blyyd Connect */
print '<div id=5 class="customDiv containerWithBorder">';
print '<div name="blyyd connect" class="customDiv fixDiv">Blyyd Connect</div><br>';
print '<div name="blyyd connect" class="customDiv divClikable isSelected" value=0 >Non</div>';
print '<div name="blyyd connect" class="customDiv divClikable" value=12 >12</div>';
print '<div name="blyyd connect" class="customDiv divClikable" value=24 ">24</div>';
print '<div name="blyyd connect" class="customDiv divClikable" value=36 >36</div>';
print '</div><br>';

/* Date début */

print '<h5>Date de début</h5>';

print '<input type="text" id="datepicker"><p id="errorDate"></p><br>';

print '<div class="buttonCustom">Valider</div>';

print '</div>';

llxFooter();

$db->close();
