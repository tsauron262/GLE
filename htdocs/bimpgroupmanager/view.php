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


$arrayofcss = array('/bimpgroupmanager/css/styles.css');
$arrayofjs = array('/bimpgroupmanager/js/jquery.nestable.js', '/bimpgroupmanager/js/group.js');


/*
 * 	View
 */

llxHeader('', 'Gestion des groupes', $help_url, '', 0, 0, $arrayofjs, $arrayofcss);
print_barre_liste('Gestion des groupes', null, null, null, null, null, "", null, null, 'title_generic', 0, '', '', $limit);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';


print'
    <menu id="nestable-menu">
        <button type="button" data-action="expand-all">Développer</button>
        <button type="button" data-action="collapse-all">Réduire</button>
    </menu>

    <div class="cf nestable-lists">

        <div class="dd" id="nestable">
            <ol class="dd-list">
                <li class="dd-item" data-id="1">
                    <div class="dd-handle">Item 1</div>
                </li>
                <li class="dd-item" data-id="2">
                    <div class="dd-handle">Item 2</div>
                    <ol class="dd-list">
                        <li class="dd-item" data-id="3"><div class="dd-handle">Item 3</div></li>
                        <li class="dd-item" data-id="4"><div class="dd-handle">Item 4</div></li>
                        <li class="dd-item" data-id="5">
                            <div class="dd-handle">Item 5</div>
                            <ol class="dd-list">
                                <li class="dd-item" data-id="6"><div class="dd-handle">Item 6</div></li>
                                <li class="dd-item" data-id="7"><div class="dd-handle">Item 7</div></li>
                                <li class="dd-item" data-id="8"><div class="dd-handle">Item 8</div></li>
                            </ol>
                        </li>
                        <li class="dd-item" data-id="9"><div class="dd-handle">Item 9</div></li>
                        <li class="dd-item" data-id="10"><div class="dd-handle">Item 10</div></li>
                    </ol>
                </li>
                <li class="dd-item" data-id="11">
                    <div class="dd-handle">Item 11</div>
                </li>
                <li class="dd-item" data-id="12">
                    <div class="dd-handle">Item 12</div>
                </li>
            </ol>
        </div>

        <div class="dd" id="nestable2">
            <ol class="dd-list">
                <li class="dd-item" data-id="13">
                    <div class="dd-handle">Item 13</div>
                </li>
                <li class="dd-item" data-id="14">
                    <div class="dd-handle">Item 14</div>
                </li>
                <li class="dd-item" data-id="15">
                    <div class="dd-handle">Item 15</div>
                    <ol class="dd-list">
                        <li class="dd-item" data-id="16"><div class="dd-handle">Item 16</div></li>
                        <li class="dd-item" data-id="17"><div class="dd-handle">Item 17</div></li>
                        <li class="dd-item" data-id="18"><div class="dd-handle">Item 18</div></li>
                    </ol>
                </li>
            </ol>
        </div>

    </div>

    <p><strong>Serialised Output</strong></p>

    <textarea id="nestable-output"></textarea>

    <p>&nbsp;</p>


    </div>';



// droits
//    ->user->user->lire
//    ->user->user->creer
//    ->user->user->password
//    ->user->user->supprimer
//    ->user->self->creer
//    ->user->self->password
//    ->user->self->export 

llxFooter();

$db->close();
