<?php

/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php');

// The get widget handler.
function widget_listCommandeFournBrouillon() {
    // A widget object as per jquery.dashboard.js.
    global $db, $conf, $langs, $user;
    if ($user->societe_id > 0) {
        $socid = $user->societe_id;
    }
    $langs->load("orders");

    $commandestatic = new CommandeFournisseur($db);


    $table = '<p>';
    $table2 = '</p>';

    if ($user->rights->fournisseur->commande->lire) {
        $sql = "SELECT c.rowid, c.ref, c.total_ttc, s.nom, s.rowid as socid";
        $sql.= " FROM " . MAIN_DB_PREFIX . "commande_fournisseur as c, " . MAIN_DB_PREFIX . "societe as s";
        $sql.= " WHERE c.fk_soc = s.rowid AND c.fk_statut = 0";
        if ($socid) {
            $sql .= " AND c.fk_soc = " . $socid;
        }
        $sql .= " ORDER BY c.date_creation DESC LIMIT 50";
        $resql = $db->query($sql);
        if ($resql) {
            $total = 0;
            $total2 = 0;

            $cssClass[true] = 'pair';
            $cssClass[false] = 'impair';
            $bool = false;
            $iter = 0;
            $table .= '<table class="noborder" width="100%">';
            $table .= "<tr class=\"liste_titre\">";
            $table .= "<td colspan=\"3\">" . $langs->trans("DraftOrders") . "</td></tr>";

            $table2 .= '<table class="noborder" width="100%">';
            $table2 .= "<tr class=\"liste_titre\">";
            $table2 .= "<td colspan=\"3\">" . $langs->trans("DraftOrders") . "</td></tr>";

            $bool = false;
            $arrCss[true] = 'pair';
            $arrCss[false] = 'impair';
            while ($res = $db->fetch_object($resql)) {
                $bool = !$bool;
                if ($iter < 10) {
                    $var = !$var;
                    $table .= '<tr class=' . $arrCss[$bool] . '>
                                    <td  nowrap="nowrap">';
                    $commandestatic->id = $res->rowid;
                    $commandestatic->ref = $res->ref;
                    $table .= $commandestatic->getNomUrl(1, '', 16);
                    $table .= '<td nowrap="nowrap"><a href="fiche.php?socid=' . $res->socid . '">' . utf8_encodeRien(dol_trunc($res->nom, 18)) . '</a></td>';
                    $table .= '<td align="right" nowrap="nowrap">' . price($res->total_ttc) . '</td></tr>';
                    $total += $res->total_ttc;
                }
                $var = !$var;
                $table2 .= '<tr class=' . $arrCss[$bool] . '>
                            <td  nowrap="nowrap">';
                $commandestatic->id = $res->rowid;
                $commandestatic->ref = $res->ref;
                $table2 .= $commandestatic->getNomUrl(1, '', 16);
                $table2 .= '<td nowrap="nowrap"><a href="fiche.php?socid=' . $res->socid . '">' . utf8_encodeRien(dol_trunc($res->nom, 18)) . '</a></td>';
                $table2 .= '<td align="right" nowrap="nowrap">' . price($res->total_ttc) . '</td></tr>';
                $total2 += $res->total_ttc;
                $iter++;
            }
            if ($total > 0) {
                $table .= '<tr class="liste_total"><td>' . $langs->trans("Total") . '</td><td colspan="2" align="right">' . price($total) . "</td></tr>";
            }
            $table .= "</table><br>";
            if ($total2 > 0) {
                $table2 .= '<tr class="liste_total"><td>' . $langs->trans("Total") . '</td><td colspan="2" align="right">' . price($total) . "</td></tr>";
            }
            $table2 .= "</table><br>";
        }
        $table .="</p>";
        $table2 .="</p>";
        return array(
            'title' => 'Commande fournisseur - 10 derniers brouillons',
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
            'title' => 'Commande fournisseur - 10 derniers brouillons',
            'content' => 'Vous ne possedez pas les droits requis pour ce module',
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
        );
    }
}

// The widget settings handler.
function widget_listCommandeFournBrouillon_settings() {
    return(array('markup' => '<div>Aucune option pour ce module</div>'));
}
