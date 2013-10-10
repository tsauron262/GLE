<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_listFourn() {
  // A widget object as per jquery.dashboard.js.
    global $db,$user,$langs;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load('suppliers');
  $table = '
<p>
<table width="100%" class="noborder"><tbody>
    <tr class="liste_titre">
        <td colspan="3">Les 5 derniers fournisseurs</td>
    </tr>
</tr>';
  $table2 = '
<p>
<table width="100%" class="noborder"><tbody>
    <tr class="liste_titre">
        <td colspan="3">Les 50 derniers fournisseurs</td>
    </tr>
</tr>';

    if ($user->rights->societe->lire)
    {
        $requete = "SELECT s.rowid,s.nom,s.fournisseur,datec as datec";
        if (!$user->rights->societe->client->voir && !$socid) $requete .= ", sc.fk_soc, sc.fk_user";
        $requete.= " FROM ".MAIN_DB_PREFIX."societe as s";
        if (!$user->rights->societe->client->voir && !$socid) $requete .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $requete.= " WHERE s.fournisseur = 1";
        if ($socid)
        {
            $requete .= " AND s.rowid = $socid";
        }
        if (!$user->rights->societe->client->voir && !$socid) //restriction
        {
            $requete .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        }
        $requete .= " ORDER BY s.datec DESC";
        $requete .= $db->plimit(50, 0);
        $sql = $db->query($requete);
        $cssClass[true]='pair';
        $cssClass[false]='impair';
        $bool = false;
        $iter = 0;
        while ($res =$db->fetch_object($sql))
        {
            $bool = !$bool;

            if ($iter < 5)
            {
                $table .= '<tr class="'.$cssClass[$bool].'">';
                $table .= '   <td style="overflow: hidden; white-space: nowrap;">';
                if ($res->fournisseur == 1) $table .= "<a href=\"".DOL_URL_ROOT."/comm/fiche.php?socid=".$res->rowid."\">".img_object($langs->trans("ShowSupplier"),"company")." ".$res->nom."</a></td>";
                $table .= '<td align="right" nowrap>';
                if ($res->fournisseur == 1) $table .= $langs->trans("Supplier");
                $table .= "</td>";
                $table .= '<td align="right" nowrap>'.dol_print_date($res->datec,'day')."</td>";
            }
            $table2 .= '<tr class="'.$cssClass[$bool].'">';
            $table2 .= '   <td style="overflow: hidden; white-space: nowrap;">';
            if ($res->fournisseur == 1) $table2 .= "<a href=\"".DOL_URL_ROOT."/comm/fiche.php?socid=".$res->rowid."\">".img_object($langs->trans("ShowSupplier"),"company")." ".$res->nom."</a></td>";
            $table2 .= '<td align="right" nowrap>';
            if ($res->fournisseur == 1) $table2 .= $langs->trans("Supplier");
            $table2 .= "</td>";
            $table2 .= '<td align="right" nowrap>'.dol_print_date($res->datec,'day')."</td>";
            $iter ++;
        }
        $table .= '</tbody></table></p>';
        $table2 .= '</tbody></table></p>';

      return array(
        'title' => 'Tiers - 5 derniers fournisseurs',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => $table2,
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
      );
    } else {
      return array(
        'title' => 'Tiers - 5 derniers fournisseurs',
        'content' => "Ce module ne vous est pas accessible",
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listFourn_settings() {
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
?>