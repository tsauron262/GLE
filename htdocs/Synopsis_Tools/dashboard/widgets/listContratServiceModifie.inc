<?php

/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once (DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");

// The get widget handler.
function widget_listContratServiceModifie() {
    // A widget object as per jquery.dashboard.js.
    global $db, $conf, $langs, $user, $bc;
    $socid = 0;
    if ($user->societe_id > 0) {
        $socid = $user->societe_id;
    }
    $langs->load("contracts");

    $staticcontrat = new Contrat($db);
    $staticcompany = new Societe($db);
    $staticcontratligne = new ContratLigne($db);

    $table = '<p>';
    $table2 = '</p>';



    if ($conf->contrat->enabled && $user->rights->contrat->lire) {

        $sql = "SELECT c.ref, c.fk_soc, ";
        $sql.= " cd.rowid as cid, cd.statut, cd.label, cd.description as note, cd.fk_contrat,";
        $sql.= ' sum(' . $db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite <= sysdate())", 1, 0) . ') as nb_late,';
        $sql.= " s.nom";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", sc.fk_soc, sc.fk_user";
        $sql.= " FROM " . MAIN_DB_PREFIX . "contrat as c, " . MAIN_DB_PREFIX . "societe as s";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        $sql.= ", " . MAIN_DB_PREFIX . "contratdet as cd LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_contrat_GA c2 ON c2.`contratdet_refid` = cd.fk_contrat";
        $sql.= " WHERE is_financement IS NULL AND cd.fk_contrat = c.rowid AND c.fk_soc = s.rowid";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
        if ($socid > 0)
            $sql.= " AND s.rowid = " . $socid;
        $sql.= " GROUP BY cd.rowid ORDER BY cd.tms DESC LIMIT 50";

        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;

            $table .= '<table class="noborder" width="100%">';

            $table .= '<tr class="liste_titre"><td colspan="4">' . $langs->trans("LastModifiedServices", min($num, 10)) . '</td>';
            $table .= "</tr>\n";
            $table2 .= '<table class="noborder" width="100%">';

            $table2 .= '<tr class="liste_titre"><td colspan="4">' . $langs->trans("LastModifiedServices", min($num, 50)) . '</td>';
            $table2 .= "</tr>\n";

            $var = true;
            while ($i < min($num, 50)) {
                $obj = $db->fetch_object($resql);
                $var = !$var;
                if ($i < 10) {
                    $table .= "<tr $bc[$var]>";
                    $table .= '<td nowrap="nowrap">';
                    $staticcontrat->ref = ($obj->ref ? $obj->ref : $obj->fk_contrat);
                    $staticcontrat->id = $obj->fk_contrat;
                    $table .= $staticcontrat->getNomUrl(1, 16);
                    if ($obj->nb_late)
                        $table .= img_warning($langs->trans("Late"));
                    $table .= '</td>';
                    $table .= '<td><a href="' . DOL_URL_ROOT . '/contrat/fiche.php?id=' . $obj->fk_contrat . '">' . img_object($langs->trans("ShowService"), "service");
                    if ($obj->note)
                        $table .= ' ' . dol_trunc(html_entity_decode($obj->note), 20) . '</a></td>';
                    else
                        $table .= '</a> ' . dol_trunc($obj->label, 20) . '</td>';
                    $table .= '<td>';
                    $staticcompany->id = $obj->fk_soc;
                    $staticcompany->fetch($obj->fk_soc);
                    $staticcompany->nom = $obj->nom;
                    $table .= $staticcompany->getNomUrl(1, '', 20);
                    $table .= '</td>';
                    $table .= '<td nowrap="nowrap" align="right"><a href="' . DOL_URL_ROOT . '/contrat/fiche.php?id=' . $obj->fk_contrat . '&ligne=' . $obj->cid . '">';
                    $table .= $staticcontratligne->LibStatut($obj->statut, 5);
                    $table .= '</a></td>';
                    $table .= "</tr>\n";
                }
                $table2 .= "<tr $bc[$var]>";
                $table2 .= '<td nowrap="nowrap">';
                $staticcontrat->ref = ($obj->ref ? $obj->ref : $obj->fk_contrat);
                $staticcontrat->id = $obj->fk_contrat;
                $table2 .= $staticcontrat->getNomUrl(1, 16);
                if ($obj->nb_late)
                    $table2 .= img_warning($langs->trans("Late"));
                $table2 .= '</td>';
                $table2 .= '<td><a href="' . DOL_URL_ROOT . '/contrat/fiche.php?id=' . $obj->fk_contrat . '">' . img_object($langs->trans("ShowService"), "service");
                if ($obj->note)
                    $table2 .= ' ' . dol_trunc(html_entity_decode($obj->note), 20) . '</a></td>';
                else
                    $table2 .= '</a> ' . dol_trunc($obj->label, 20) . '</td>';
                $table2 .= '<td>';
                $staticcompany->id = $obj->fk_soc;
                $staticcompany->fetch($obj->fk_soc);
                $staticcompany->nom = $obj->nom;
                $table2 .= $staticcompany->getNomUrl(1, '', 20);
                $table2 .= '</td>';
                $table2 .= '<td nowrap="nowrap" align="right"><a href="' . DOL_URL_ROOT . '/contrat/fiche.php?id=' . $obj->fk_contrat . '&ligne=' . $obj->cid . '">';
                $table2 .= $staticcontratligne->LibStatut($obj->statut, 5);
                $table2 .= '</a></td>';
                $table2 .= "</tr>\n";
                $i++;
            }
            $db->free($resql);

            $table .= "</table>";
        }



        $table .="</p>";
        $table2 .="</p>";
        return array(
            'title' => 'Contrat - 10 derniers services modifi&eacute;s',
            'content' => $table,
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
            'fullscreen' => $table2,
            'fullscreenScript' => DOL_URL_ROOT . '/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
            'fullscreenInitScript' => DOL_URL_ROOT . '/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
        );
    } else {
        return array(
            'title' => 'Contrat - 10 derniers services modifi&eacute;s',
            'content' => 'Vous ne possedez pas les droits requis pour ce module',
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
        );
    }
}

// The widget settings handler.
function widget_listContratServiceModifie_settings() {
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
