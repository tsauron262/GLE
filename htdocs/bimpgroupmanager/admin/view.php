<?php

/**
 *  \file       htdocs/bimpgroupmanager/view.php
 *  \ingroup    bimpgroupmanager
 *  \brief      Page of the group manager
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bimpgroupmanager/class/BimpGroupManager.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

$langs->load("admin");

$arrayofcss = array('/bimpgroupmanager/nestable/nestable.css', '/bimpgroupmanager/css/styles.css');
$arrayofjs = array('/bimpgroupmanager/nestable/jquery.nestable.js', '/bimpgroupmanager/js/group.js');

//if (!$user->admin)
//    accessforbidden();

$gm = new BimpGroupManager($db);

$recursif = GETPOST('recursif');
if ($recursif != null) {
    $db->begin();
    if (!isset($conf->global->BIMP_AJOUT_GROUP_RECURSIF) OR $recursif != $conf->global->BIMP_AJOUT_GROUP_RECURSIF) {
        $res = dolibarr_set_const($db, "BIMP_AJOUT_GROUP_RECURSIF", $recursif, 'chaine', 0, '', $conf->entity);
        if (!$res > 0)
            $error=1;
    }
    if (!$error) {
        $db->commit();
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        $db->rollback();
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}


/*
 * 	View
 */

llxHeader('', 'Gestion des groupes', '', '', 0, 0, $arrayofjs, $arrayofcss);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . 'Retour liste des modules' . '</a>';
print load_fiche_titre('Gestion des groupes', $linkback);

print '<form>';
print '<br><table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "  <td>Souhaitez-vous activer l'ajout de groupes récursif ?</td>\n";
print "  <td align=\"right\" width=\"160\">&nbsp;</td>\n";
print '</tr>' . "\n";
print '</table>';

if (isset($conf->global->BIMP_AJOUT_GROUP_RECURSIF) && $conf->global->BIMP_AJOUT_GROUP_RECURSIF == 1) {
    print '  Oui  <input type="radio" name="recursif" value="1"checked>&nbsp;&nbsp;&nbsp;';
    print '  Non  <input type="radio" name="recursif" value="0"><br>';
} else {
    print '  Oui  <input type="radio" name="recursif" value="1">&nbsp;&nbsp;&nbsp;';
    print '  Non  <input type="radio" name="recursif" value="0" checked><br>';
}

print '<br><input type="submit" class="button butAction round" value="Valider"><br>';
print '</form><br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '</tr>';
print '</table>';

print '<p>Pour réorganiser les groupes, veuillez déplacer le carré à gauche du groupe à modifier.</p>
    <menu id="nestable-menu">
        <button type="button" class="butAction round" data-action="expand-all">Tout développer</button>
        <button type="button" class="butAction round" data-action="collapse-all">Tout réduire</button>';

if (isset($conf->global->BIMP_AJOUT_GROUP_RECURSIF) && $conf->global->BIMP_AJOUT_GROUP_RECURSIF == 1) {
    print '<button type="button" class="butAction round" data-action="set-all-users">Classer tous les utilisateurs</button>';
}
print '</menu>

    <div class="cf nestable-lists">
        <div class="dd" id="nestable">
            <ol class="dd-list">
            </ol>
        </div>
    </div>';


llxFooter();

$db->close();
