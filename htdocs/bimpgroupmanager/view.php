<?php

/**
 *  \file       htdocs/societe/agenda.php
 *  \ingroup    societe
 *  \brief      Page of third party events
 */
require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/bimpgroupmanager/class/BimpGroupManager.class.php';  //TODO remove after developpment


$arrayofcss = array('/bimpgroupmanager/nestable/nestable.css', '/bimpgroupmanager/css/styles.css');
$arrayofjs = array('/bimpgroupmanager/nestable/jquery.nestable.js', '/bimpgroupmanager/js/group.js');


/*
 * 	View
 */

llxHeader('', 'Gestion des groupes', $help_url, '', 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('Gestion des groupes');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<p>Pour réorganiser les groupes, veuillez déplacer le carré à gauche du groupe à modifier.</p>
    <menu id="nestable-menu">
        <button type="button" class="butAction round" data-action="expand-all">Tout développer</button>
        <button type="button" class="butAction round" data-action="collapse-all">Tout réduire</button>
    </menu>

    <div class="cf nestable-lists">
        <div class="dd" id="nestable">
            <ol class="dd-list">
            </ol>
        </div>
    </div>';

    
llxFooter();

$db->close();
