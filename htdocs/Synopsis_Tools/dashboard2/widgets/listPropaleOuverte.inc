<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_listPropaleOuverte() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user;
    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
        $langs->load("propal");

  $table = '<p>';
  $table2 = '<p>';


    $sql = "SELECT p.rowid, p.ref, p.total_ht, s.rowid as socid, s.nom";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql.= " FROM ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."societe as s";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= " WHERE p.fk_statut = 1 and p.fk_soc = s.rowid";
    if ($socid)
    {
       $sql .= " AND s.rowid = ".$socid;
    }
    if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    $sql .= " ORDER BY p.datec DESC LIMIT 50";

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
            $table .= "<td colspan=\"3\">".$langs->trans("ProposalsOpened")."</td></tr>";

            $table2 .= '<table class="noborder" width="100%">';
            $table2 .= "<tr class=\"liste_titre\">";
            $table2 .= "<td colspan=\"3\">".$langs->trans("ProposalsOpened")."</td></tr>";

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
                                "<a href=\"".DOL_URL_ROOT."/comm/propal.php?propalid=".$res->rowid."\">".img_object($langs->trans("ShowPropal"),"propal")." ".$res->ref.'</a></td>';
                        $table .=  '<td nowrap="nowrap"><a href="fiche.php?socid='.$res->socid.'">'.utf8_encode(dolibarr_trunc($res->nom,18)).'</a></td>';
                        $table .=  '<td align="right" nowrap="nowrap">'.price($res->total_ht).'</td></tr>';
                        $total += $res->total_ht;
                }
                $var=!$var;
                $table2 .= '<tr class='.$arrCss[$bool].'>
                        <td  nowrap="nowrap">'.
                        "<a href=\"".DOL_URL_ROOT."/comm/propal.php?propalid=".$res->rowid."\">".img_object($langs->trans("ShowPropal"),"propal")." ".$res->ref.'</a></td>';
                $table2 .=  '<td nowrap="nowrap"><a href="fiche.php?socid='.$res->socid.'">'.utf8_encode(dolibarr_trunc($res->nom,18)).'</a></td>';
                $table2 .=  '<td align="right" nowrap="nowrap">'.price($res->total_ht).'</td></tr>';
                $total2 += $res->total_ht;
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
    'title' => 'Proposition com. - 10 derniers prop. ouvertes',
    'content' => $table,
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
    'fullscreen' => $table2,
    'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
    'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
  );
}

// The widget settings handler.
function widget_listPropaleOuverte_settings() {
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
