<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_listCommandeBrouillon() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user;
    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("orders");


      $table =  '<p>';
      $table2 = '</p>';

    if ($conf->commande->enabled && $user->rights->commande->lire)
    {
        $sql = "SELECT c.rowid, c.ref, c.total_ttc, s.nom, s.rowid as socid";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE c.fk_soc = s.rowid AND c.fk_statut = 0";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        if ($socid)
        {
            $sql .= " AND c.fk_soc = ".$socid;
        }
        $sql .= " ORDER BY c.date_creation DESC LIMIT 50";
        $resql=$db->query($sql);
        if ($resql)
        {
            $total = 0;
            $total2 = 0;
            $num = $db->num_rows($resql);
            if ($num > 0)
            {

                $cssClass[true]='pair';
                $cssClass[false]='impair';
                $bool = false;
                $iter = 0;
                $table .= '<table class="noborder" width="100%">';
                $table .= "<tr class=\"liste_titre\">";
                $table .= "<td colspan=\"3\">".$langs->trans("DraftOrders")."</td></tr>";

                $table2 .= '<table class="noborder" width="100%">';
                $table2 .= "<tr class=\"liste_titre\">";
                $table2 .= "<td colspan=\"3\">".$langs->trans("DraftOrders")."</td></tr>";

                $bool = false;
                $arrCss[true]='pair';
                $arrCss[false]='impair';
                while ($res =$db->fetch_object($resql))
                {
                    $bool = !$bool;
                    if ($iter < 10)
                    {
                            $var=!$var;
                            $table .= '<tr class='.$arrCss[$bool].'>
                                    <td  nowrap="nowrap">'.
                                    "<a href=\"".DOL_URL_ROOT."/commande/fiche.php?id=".$res->rowid."\">".img_object($langs->trans("ShowOrder"),"order")." ".$res->ref.'</a></td>';
                            $table .=  '<td nowrap="nowrap"><a href="fiche.php?socid='.$res->socid.'">'.utf8_encode(dol_trunc($res->nom,18)).'</a></td>';
                            $table .=  '<td align="right" nowrap="nowrap">'.price($res->total_ttc).'</td></tr>';
                            $total += $res->total_ttc;
                    }
                    $var=!$var;
                    $table2 .= '<tr class='.$arrCss[$bool].'>
                            <td  nowrap="nowrap">'.
                            "<a href=\"".DOL_URL_ROOT."/commande/fiche.php?id=".$res->rowid."\">".img_object($langs->trans("ShowOrder"),"order")." ".$res->ref.'</a></td>';
                    $table2 .=  '<td nowrap="nowrap"><a href="fiche.php?socid='.$res->socid.'">'.utf8_encode(dol_trunc($res->nom,18)).'</a></td>';
                    $table2 .=  '<td align="right" nowrap="nowrap">'.price($res->total_ttc).'</td></tr>';
                    $total2 += $res->total_ttc;
                    $iter++;
                }
                if ($total>0)
                {
                    $table .=  '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" align="right">'.price($total)."</td></tr>";
                }
                $table .=  "</table><br>";
                if ($total2>0)
                {
                    $table2 .=  '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" align="right">'.price($total)."</td></tr>";
                }
                $table2 .=  "</table><br>";
            }
        }
        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Commande - 10 derniers brouillons',
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => utf8_encode($table2),
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
      );

    }else {
      return array(
        'title' => 'Commande - 10 derniers brouillons',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listCommandeBrouillon_settings() {
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
