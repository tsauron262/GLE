<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_listClientProspect() {
  // A widget object as per jquery.dashboard.js.
    global $db,$user,$langs;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load('commercial');
  $table = '
<p>
<table width="100%" class="noborder"><tbody>
    <tr class="liste_titre">
        <td colspan="3">Les 5 derniers clients ou prospects</td>
    </tr>
</tr>';
  $table2 = '
<p>
<table width="100%" class="noborder"><tbody>
    <tr class="liste_titre">
        <td colspan="3">Les 50 derniers clients ou prospects</td>
    </tr>
</tr>';

    if ($user->rights->societe->lire)
    {
        $requete = "SELECT s.rowid,s.nom,s.client,datec as datec";
        if (!$user->rights->societe->client->voir && !$socid) $requete .= ", sc.fk_soc, sc.fk_user";
        $requete.= " FROM ".MAIN_DB_PREFIX."societe as s";
        if (!$user->rights->societe->client->voir && !$socid) $requete .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $requete.= " WHERE s.client in (1,2)";
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
                if ($res->client == 1) $table .= "<a href=\"".DOL_URL_ROOT."/comm/fiche.php?socid=".$res->rowid."\">".img_object($langs->trans("ShowCustomer"),"company")." ".$res->nom."</a></td>";
                if ($res->client == 2) $table .= "<a href=\"".DOL_URL_ROOT."/comm/prospect/fiche.php?socid=".$res->rowid."\">".img_object($langs->trans("ShowCustomer"),"company")." ".$res->nom."</a></td>";
                $table .= '<td align="right" nowrap>';
                if ($res->client == 1) $table .= $langs->trans("Customer");
                if ($res->client == 2) $table .= $langs->trans("Prospect");
                $table .= "</td>";
                $table .= '<td align="right" nowrap>'.dol_print_date($res->datec,'day')."</td>";
            }
            $table2 .= '<tr class="'.$cssClass[$bool].'">';
            $table2 .= '   <td style="overflow: hidden; white-space: nowrap;">';
            if ($res->client == 1) $table2 .= "<a href=\"".DOL_URL_ROOT."/comm/fiche.php?socid=".$res->rowid."\">".img_object($langs->trans("ShowCustomer"),"company")." ".$res->nom."</a></td>";
            if ($res->client == 2) $table2 .= "<a href=\"".DOL_URL_ROOT."/comm/prospect/fiche.php?socid=".$res->rowid."\">".img_object($langs->trans("ShowCustomer"),"company")." ".$res->nom."</a></td>";
            $table2 .= '<td align="right" nowrap>';
            if ($res->client == 1) $table2 .= $langs->trans("Customer");
            if ($res->client == 2) $table2 .= $langs->trans("Prospect");
            $table2 .= "</td>";
            $table2 .= '<td align="right" nowrap>'.dol_print_date($res->datec,'day')."</td>";
            $iter ++;
        }
        $table .= '</tbody></table></p>';
        $table2 .= '</tbody></table></p>';

      return array(
        'title' => 'Tiers -5 derniers clients/prospects',
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
        'title' => 'Tiers -5 derniers clients/prospects',
        'content' => "Ce module ne vous est pas accessible",
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listClientProspect_settings() {
  // Useful to test what happens if the user clicks in and out of settings-edit mode before settings have finished initializing.
  // sleep(5);

  // Save &/or load user's settings here!
//  if ($GLOBALS['_POST']['settings']) {
//    $data = $GLOBALS['_POST']['settings'];
//  }
//  else {
//    $data = array(
//      'color' => 'red',
//      'title' => 'Mello Yellow',
//    );
//  }
//
//  // A widget settings object as per jquery.dashboard.js.
//  return array(
//    'markup' => '<div>' . ($GLOBALS['_POST']['settings'] ? 'Reloaded Title' : 'Title') . '</div>
//                 <div><input name="title" type="text" value="' . $data['title'] . '" /></div>
//                 <div><select name="color">
//                   <option' . selected($data, 'yellow') . '>Yellow</option>
//                   <option' . selected($data, 'red') . '>Red</option>
//                   <option' . selected($data, 'blue') . '>Blue</option>
//                 </select></div>
//                 <div><label><input name="on" type="checkbox" value="1" ' . ($data['on'] ? 'checked="checked"' : '') . ' />Feature on</label></div>',
//  );
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
