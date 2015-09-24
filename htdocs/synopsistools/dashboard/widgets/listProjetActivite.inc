<?php

/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . '/projet/class/project.class.php');

// The get widget handler.
function widget_listProjetActivite() {
    // A widget object as per jquery.dashboard.js.
    global $db, $user, $langs, $bc;

    // Defini les bornes date debut et fin de semaines, mois et annee pour le jour courant
    $now = time();
    $info = dol_getdate($now);
    $daystartw = $now - (($info['wday'] - 1) * 24 * 3600);
    $dayendw = $now + ((7 - $info['wday']) * 24 * 3600);
    $infostartw = dol_getdate($daystartw);
    $infoendw = dol_getdate($dayendw);
    $datestartw = dol_mktime(0, 0, 0, $infostartw["mon"], $infostartw["mday"], $infostartw["year"]);
    $dateendw = dol_mktime(23, 59, 59, $infoendw["mon"], $infoendw["mday"], $infoendw["year"]);
    $datestartm = dol_mktime(0, 0, 0, $info["mon"], 1, $info["year"]);
    $dateendm = dol_mktime(23, 59, 59, $info["mon"], 30, $info["year"]);
    $datestarty = dol_mktime(0, 0, 0, 1, 1, $info["year"]);
    $dateendy = dol_mktime(23, 59, 59, 12, 31, $info["year"]);

    $socid = 0;
    if ($user->societe_id > 0) {
        $socid = $user->societe_id;
    }
    $langs->load('projects');
    $table = '
<p>
<table width="100%" class="noborder"><tbody>
    <tr class="liste_titre">
        <td width="50%">Activit&eacute; sur les projets cette semaine</td>
        <td width="50%" align="right">' . $langs->trans("Hours") . '</td>
    </tr>
</tr>';

    if ($user->rights->projet->lire) {
        /* Affichage de la liste des projets de la semaine */
        $sql = "SELECT p.title, p.rowid, sum(tt.task_duration) as total";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", sc.fk_soc, sc.fk_user";
        $sql .= " FROM " . MAIN_DB_PREFIX . "Synopsis_projet_view as p";
        $sql .= " , " . MAIN_DB_PREFIX . "projet_task as t";
        $sql .= " , " . MAIN_DB_PREFIX . "projet_task_time as tt";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        $sql .= " WHERE t.fk_projet = p.rowid";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
        $sql .= " AND tt.fk_task = t.rowid";
        $sql .= " AND task_date >= '" . $db->idate($datestartw) . "' AND task_date <= '" . $db->idate($dateendw) . "'";
        $sql .= " GROUP BY p.rowid";
        dol_syslog("Index: sql=" . $sql);
        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;

            while ($i < $num) {
                $obj = $db->fetch_object($resql);
                $projet = new Project($db);
                $projet->fetch($obj->rowid);

                $var = !$var;
                $table .= "<tr $bc[$var]>";
                $table .= '<td><a href="' . DOL_URL_ROOT . '/projet/tasks/card.php?id=' . $obj->rowid . '">' . $obj->title . '</a></td>';
                $table .= '<td align="right">' . $projet->sec2hour($obj->total) . '</td>';
                $table .= "</tr>\n";
                $table .= "</table><br />";
                $i++;
            }
        }
        /* Affichage de la liste des projets du mois */
        $table .= '<table class="noborder" width="100%">';
        $table .= '<tr class="liste_titre">';
        $table .= '<td width="50%">' . $langs->trans("Project") . ' ce mois : ' . utf8_decode(strftime("%B %Y", $now)) . '</td>';
        $table .= '<td width="50%" align="right">' . $langs->trans("Hours") . '</td>';
        $table .= "</tr>\n";

        $sql = "SELECT p.title, p.rowid, sum(tt.task_duration) as total";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", sc.fk_soc, sc.fk_user";
        $sql .= " FROM " . MAIN_DB_PREFIX . "Synopsis_projet_view as p";
        $sql .= " , " . MAIN_DB_PREFIX . "projet_task as t";
        $sql .= " , " . MAIN_DB_PREFIX . "projet_task_time as tt";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        $sql .= " WHERE t.fk_projet = p.rowid";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
        $sql .= " AND tt.fk_task = t.rowid";
        $sql .= " AND task_date >= '" . $db->idate($datestartm) . "' AND task_date <= '" . $db->idate($dateendm) . "'";
        $sql .= " GROUP BY p.rowid";

        $var = true;
        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;

            while ($i < $num) {
                $obj = $db->fetch_object($resql);
                $projet = new Project($db);
                $projet->fetch($obj->rowid);
                $var = !$var;
                $table .= "<tr $bc[$var]>";
                $table .= '<td><a href="' . DOL_URL_ROOT . '/projet/tasks/card.php?id=' . $obj->rowid . '">' . $obj->title . '</a></td>';
                $table .= '<td align="right">' . $projet->sec2hour($obj->total) . '</td>';
                $table .= "</tr>\n";
                $i++;
            }

            $db->free($resql);
        } else {
            dol_print_error($db);
        }
        $table .= "</table>";

        /* Affichage de la liste des projets du mois */
        $table .= '<br /><table class="noborder" width="100%">';
        $table .= '<tr class="liste_titre">';
        $table .= '<td width="50%">' . $langs->trans("Project") . ' cette ann&eacute;e : ' . strftime("%Y", $now) . '</td>';
        $table .= '<td width="50%" align="right">' . $langs->trans("Hours") . '</td>';
        $table .= "</tr>\n";

        $sql = "SELECT p.title, p.rowid, sum(tt.task_duration) as total";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", sc.fk_soc, sc.fk_user";
        $sql .= " FROM " . MAIN_DB_PREFIX . "Synopsis_projet_view as p";
        $sql .= " , " . MAIN_DB_PREFIX . "projet_task as t";
        $sql .= " , " . MAIN_DB_PREFIX . "projet_task_time as tt";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        $sql .= " WHERE t.fk_projet = p.rowid";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
        $sql .= " AND tt.fk_task = t.rowid";
        $sql .= " AND task_date >= '" . $db->idate($datestarty) . "' AND task_date <= '" . $db->idate($dateendy) . "'";
        $sql .= " GROUP BY p.rowid";

        $var = true;
        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;

            while ($i < $num) {
                $obj = $db->fetch_object($resql);
                $projet = new Project($db);
                $projet->fetch($obj->rowid);
                $var = !$var;
                $table .= "<tr $bc[$var]>";
                $table .= '<td><a href="' . DOL_URL_ROOT . '/projet/tasks/card.php?id=' . $obj->rowid . '">' . $obj->title . '</a></td>';
                $table .= '<td align="right">' . $projet->sec2hour($obj->total) . '</td>';
                $table .= "</tr>\n";
                $i++;
            }

            $db->free($resql);
        } else {
            dol_print_error($db);
        }
        $table .= "</table>";
        $table .= '</tbody></table></p>';

        return array(
            'title' => 'Projets - Activit&eacute;',
            'content' => $table,
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
        );
    } else {
        return array(
            'title' => 'Projets - Activit&eacute;',
            'content' => "Ce module ne vous est pas accessible",
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
        );
    }
}

// The widget settings handler.
function widget_listProjetActivite_settings() {
    return(array('markup' => '<div>Aucune option pour ce module</div>'));
}

// A silly little helper function for the form.
//function selected($data, $color) {
//  if ($data['color'] == $color) {
//    return ' value="' . $color . '" selected="selected"';
//  }
//  else {
//    return ' value="' . $color . '"';
//  }
//}
