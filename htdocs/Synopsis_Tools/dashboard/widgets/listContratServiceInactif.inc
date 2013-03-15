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
function widget_listContratServiceInactif() {
    // A widget object as per jquery.dashboard.js.
    global $db, $conf, $langs, $user, $bc;
    $socid = 0;
    if ($user->societe_id > 0) {
        $socid = $user->societe_id;
    }
    $langs->load("contracts");


    $table = '<p>';
    $table2 = '</p>';



    if ($conf->contrat->enabled && $user->rights->contrat->lire) {
        $staticcontrat = new Contrat($db);
        $staticcompany = new Societe($db);
        $staticcontratligne = new ContratLigne($db);


        $sql = "SELECT cd.rowid as cid, c.ref, cd.statut, cd.label, cd.description as note, cd.fk_contrat, c.fk_soc, s.nom";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", sc.fk_soc, sc.fk_user";
        $sql.= " FROM " . MAIN_DB_PREFIX . "contrat as c, " . MAIN_DB_PREFIX . "societe as s";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        $sql.= ", " . MAIN_DB_PREFIX . "contratdet as cd LEFT JOIN " . MAIN_DB_PREFIX . "Synopsis_contrat_GA c2 ON c2.`contratdet_refid` = cd.fk_contrat";
        $sql.= " WHERE c.statut=1 AND cd.statut = 0";
        $sql.= " AND is_financement IS NULL AND cd.fk_contrat = c.rowid AND c.fk_soc = s.rowid";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
        if ($socid > 0)
            $sql.= " AND s.rowid = " . $socid;
        $sql.= " ORDER BY cd.tms DESC";

        if ($result = $db->query($sql)) {
            $num = $db->num_rows($result);
            $i = 0;

            $table .= '<table class="noborder" width="100%">';

            $table .= '<tr class="liste_titre"><td colspan="4">' . $langs->trans("NotActivatedServices") . '</td>';
            $table .= "</tr>\n";

            $table2 .= '<table class="noborder" width="100%">';

            $table2 .= '<tr class="liste_titre"><td colspan="4">' . $langs->trans("NotActivatedServices") . '</td>';
            $table2 .= "</tr>\n";

            $var = True;
            while ($i < $num) {
                $obj = $db->fetch_object($result);
                $var = !$var;

                $temp = "<tr $bc[$var]>";

                $temp .= '<td>';
                $staticcontrat->ref = ($obj->ref ? $obj->ref : $obj->fk_contrat);
                $staticcontrat->id = $obj->fk_contrat;
                $temp .= $staticcontrat->getNomUrl(1, 16);
                $temp .= '</td>';
                $temp .= '<td nowrap="1">';
                $temp .= '<a href="' . DOL_URL_ROOT . '/Synopsis_Contrat/contratDetail.php?id=' . $obj->cid . '">' . img_object($langs->trans("ShowService"), "service");
                if ($obj->note)
                    $temp .= ' ' . dol_trunc(html_entity_decode($obj->note), 20) . '</a></td>';
                else
                    $temp .= '</a> ' . dol_trunc($obj->label, 20) . '</td>';
                $temp .= '<td>';
                $staticcompany->id = $obj->fk_soc;
                $staticcompany->fetch($obj->fk_soc);
                $staticcompany->nom = $obj->nom;
                $temp .= $staticcompany->getNomUrl(1, '', 20);
                $temp .= '</td>';
                $temp .= '<td width="16" align="right"><a href="ligne.php?id=' . $obj->fk_contrat . '&ligne=' . $obj->cid . '">';
                $temp .= $staticcontratligne->LibStatut($obj->statut, 3);
                $temp .= '</a></td>';
                $temp .= "</tr>\n";

                if ($i < 10) {
                    $table .= $temp;
                }
                $table2 .= $temp;

                $i++;
            }
            $db->free($result);

            $table .= "</table>";
            $table2 .= "</table>";
        }


        $table .="</p>";
        $table2 .="</p>";
        return array(
            'title' => 'Contrat - 10 derniers services inactifs (parmi les contrats valid&eacute;s)',
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
            'title' => 'Contrat - 10 derniers services inactifs (parmi les contrats valid&eacute;s)',
            'content' => 'Vous ne possedez pas les droits requis pour ce module',
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
        );
    }
}

// The widget settings handler.
function widget_listContratServiceInactif_settings() {
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
