<?php

/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");

// The get widget handler.
function widget_listCommandeAFacturer() {
    // A widget object as per jquery.dashboard.js.
    global $db, $conf, $langs, $user;
    $socid = 0; if ($user->societe_id > 0) {
        $socid = $user->societe_id;
    }
    $langs->load("orders");
    $formfile = new FormFile($db);
    $langs->load("compta");
    $langs->load("bills");

    $commandestatic = new Commande($db);


    $table = '<p>';
    $table2 = '</p>';

    if ($conf->commande->enabled && $user->rights->commande->lire) {
        $sql = "SELECT sum(f.total) as tot_fht, sum(f.total_ttc) as tot_fttc,";
        $sql.= " s.nom, s.rowid as socid,";
        $sql.= " p.rowid, p.ref, p.facture, p.fk_statut, p.total_ht, p.total_ttc";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", sc.fk_soc, sc.fk_user ";
        $sql.= " FROM " . MAIN_DB_PREFIX . "societe AS s";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
        $sql.= ", " . MAIN_DB_PREFIX . "commande AS p";
        $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "element_element AS co_fa ON co_fa.fk_source = p.rowid AND co_fa.sourcetype LIKE 'commande' AND co_fa.targettype LIKE 'facture'";
        $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "facture AS f ON co_fa.fk_target = f.rowid";
        $sql.= " WHERE p.fk_soc = s.rowid";
        if (!$user->rights->societe->client->voir && !$socid)
            $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
        if ($socid) {
            $sql.= " AND p.fk_soc = " . $socid;
        }
        $sql.= " AND p.fk_statut = 3 AND p.facture=0";
        $sql.= " GROUP BY p.rowid";
        $sql .= " ORDER BY p.date_creation DESC LIMIT 50";

//$table .= $sql;

        $resql = $db->query($sql);
        if ($resql) {
            $total = 0;
            $total2 = 0;
            $ftotal = 0;
            $ftotal2 = 0;

            $cssClass[true] = 'pair';
            $cssClass[false] = 'impair';
            $bool = false;
            $iter = 0;
            $table .= '<table class="noborder" width="100%">';
            $table .= "<tr class=\"liste_titre\">";
            $table .= "<td colspan=\"2\">" . $langs->trans("OrdersToBill") . "</td>";
            $table .= "<td colspan=\"1\" align=right>" . $langs->trans("AmountTTC") . "</td>";
            $table .= "<td colspan=\"1\" align=right>" . $langs->trans("ToBill") . "</td>";
            $table .= "<td colspan=\"1\">&nbsp;</td></tr>";

            $table2 .= '<table class="noborder" width="100%">';
            $table2 .= "<tr class=\"liste_titre\">";
            $table2 .= "<td colspan=\"2\">" . $langs->trans("OrdersToBill") . "</td>";
            $table2 .= "<td colspan=\"1\" align=right>" . $langs->trans("AmountTTC") . "</td>";
            $table2 .= "<td colspan=\"1\" align=right>" . $langs->trans("ToBill") . "</td>";
            $table2 .= "<td colspan=\"1\">&nbsp;</td></tr>";

            $bool = false;
            $arrCss[true] = 'pair';
            $arrCss[false] = 'impair';
            while ($res = $db->fetch_object($resql)) {

                $commandestatic->id = $res->rowid;
                $commandestatic->ref = $res->ref;

                $bool = !$bool;
                if ($iter < 10) {
                    $table .= '<tr class=' . $arrCss[$bool] . '>
                                    <td  nowrap="nowrap">';
                    $table .= $commandestatic->getNomUrl(1);
                    $filename = sanitize_string($res->ref);
                    $filedir = $conf->commande->dir_output . '/' . sanitize_string($res->ref);
                    $urlsource = $_SERVER['PHP_SELF'] . '?id=' . $res->rowid;
                    $table .= $formfile->getDocumentsLink('commande', $filename, $filedir);

//                    $table .= $formfile->html;

                    $table .= '<td nowrap="nowrap"><a href="card.php?socid=' . $res->socid . '">' . utf8_encodeRien(dol_trunc($res->nom, 18)) . '</a></td>';
                    $table .= '<td align="right" nowrap="nowrap">' . price($res->total_ttc) . '</td>';
                    $table .= '<td align="right" nowrap="nowrap">' . price($res->total_ttc - $res->tot_fttc) . '</td>';
                    $table .= '<td>' . $commandestatic->LibStatut($res->fk_statut, $res->facture, 3) . '</td>';
                    $total += $res->total_ttc;
                    $ftotal += $res->total_ttc - $res->tot_fttc;
                }
                $table2 .= '<tr class=' . $arrCss[$bool] . '>
                            <td  nowrap="nowrap">' .
                        "<a href=\"" . DOL_URL_ROOT . "/commande/card.php?id=" . $res->rowid . "\">" . img_object($langs->trans("ShowOrder"), "order") . " " . $res->ref . '</a></td>';
                $table2 .= '<td nowrap="nowrap"><a href="card.php?socid=' . $res->socid . '">' . utf8_encodeRien(dol_trunc($res->nom, 18)) . '</a></td>';
                $table2 .= '<td align="right" nowrap="nowrap">' . price($res->total_ttc) . '</td>';
                $table2 .= '<td align="right" nowrap="nowrap">' . price($res->total_ttc - $res->tot_fttc) . '</td>';
                $table2 .= '<td>' . $commandestatic->LibStatut($res->fk_statut, $res->facture, 3) . '</td>';
                $total2 += $res->total_ttc;
                $ftotal2 += $res->total_ttc - $res->tot_fttc;
                $iter++;
            }
            if ($total > 0) {
                $table .= '<tr class="liste_total"><td colspan=2>' . $langs->trans("Total") . '</td><td colspan="1" align="right">' . price($total) . '</td><td colspan="1" align="right">' . price($ftotal) . '</td></tr>';
            }
            $table .= "</table><br>";
            if ($total2 > 0) {
                $table2 .= '<tr class="liste_total"><td colspan=1>' . $langs->trans("Total") . '</td><td colspan="1" align="right">' . price($total) . '</td><td colspan="1" align="right">' . price($ftotal2) . '</td></tr>';
            }
            $table2 .= "</table><br>";
        } else {
            $table .= "Erreur sql " . $sql;
        }
        $table .="</p>";
        $table2 .="</p>";
        return array(
            'title' => 'Commande client - 10 derniers com. &agrave; facturer',
            'content' => $table,
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
            'fullscreen' => $table2,
            'fullscreenScript' => DOL_URL_ROOT . '/synopsistools/dashboard/widgets/scripts/fullscreen.js',
            'fullscreenInitScript' => DOL_URL_ROOT . '/synopsistools/dashboard/widgets/scripts/initFullscreen.js',
        );
    } else {
        return array(
            'title' => 'Commande client - 10 derniers com. &agrave; facturer',
            'content' => 'Vous ne possedez pas les droits requis pour ce module',
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
        );
    }
}

// The widget settings handler.
function widget_listCommandeAFacturer_settings() {
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
