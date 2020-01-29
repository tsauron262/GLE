<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');



// The get widget handler.
function widget_listFactureCess() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
        $langs->load("bills");

  $table = '<p>';
  $table2 = '<p>';


    $sql  = "SELECT f.ref, f.rowid, f.total as total_ht, f.type, f.fk_statut, ";
    $sql.= " s.nom, s.rowid as socid";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
    $sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
//    $sql .= " WHERE s.rowid = f.fk_soc AND s.cessionnaire = 1 ";
    $sql .= " WHERE s.rowid = f.fk_soc AND 0 = 1 ";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

    if ($socid)
    {
        $sql .= " AND f.fk_soc = $socid";
    }

    $sql .= " ORDER BY f.datec DESC LIMIT 50";
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
            $table .= "<td colspan=\"4\">".$langs->trans("Factures cessionaires (HT)")."</td></tr>";

            $table2 .= '<table class="noborder" width="100%">';
            $table2 .= "<tr class=\"liste_titre\">";
            $table2 .= "<td colspan=\"4\">".$langs->trans("Factures cessionaires (HT)")."</td></tr>";

            $bool = false;
            $arrCss[true]='pair';
            $arrCss[false]='impair';
            $facturestatic=new Facture($db);
            while ($res =$db->fetch_object($resql))
            {
                $bool = !$bool;
                if ($iter < 10)
                {
                    $table .= '<tr class='.$arrCss[$bool].'>
                            <td  nowrap="nowrap">';
                    $facturestatic->ref=$res->ref;
                    $facturestatic->id=$res->rowid;
                    $facturestatic->type=$res->type;
                    $facturestatic->statut = $res->fk_statut;
                    $table .= $facturestatic->getNomUrl(1,'');
                    $table .=  '<td nowrap="nowrap"><a href="card.php?socid='.$res->socid.'">'.dol_trunc($res->nom,18).'</a></td>';
                    $table .=  '<td align="right" nowrap="nowrap">'.price($res->total_ht).'</td>';
                    $table .=  '<td align="right" nowrap="nowrap">'.$facturestatic->getLibStatut(5).'</td></tr>';
                    $total += $res->total_ht;
                }
                $table2 .= '<tr class='.$arrCss[$bool].'>
                        <td  nowrap="nowrap">';
                $facturestatic->ref=$res->ref;
                $facturestatic->id=$res->rowid;
                $facturestatic->type=$res->type;
                $facturestatic->statut = $res->fk_statut;
                $table2 .= $facturestatic->getNomUrl(1,'');
                $table2 .=  '<td nowrap="nowrap"><a href="card.php?socid='.$res->socid.'">'.dol_trunc($res->nom,24).'</a></td>';
                $table2 .=  '<td align="right" nowrap="nowrap">'.price($res->total_ht).'</td>';
                $table2 .=  '<td align="right" nowrap="nowrap">'.$facturestatic->getLibStatut(5).'</td></tr>';
                $total2 += $res->total_ht;
                $iter++;
            }
            if ($total>0)
            {
                $table .=  '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="3" align="right">'.price($total)."</td><td>&nbsp;</tr>";
            }
            $table .=  "</table><br>";
            if ($total2>0)
            {
                $table2 .=  '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="3" align="right">'.price($total)."</td><td>&nbsp;</tr>";
            }
            $table2 .=  "</table><br>";
        }
    }
    $table .="</p>";
    $table2 .="</p>";

  return array(
    'title' => 'GA - 10 derni&egrave;res factures',
    'content' => $table,
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
    'fullscreen' => $table2,
    'fullscreenScript' => DOL_URL_ROOT.'/synopsistools/dashboard/widgets/scripts/fullscreen.js',
    'fullscreenInitScript' => DOL_URL_ROOT.'/synopsistools/dashboard/widgets/scripts/initFullscreen.js',
  );
}

// The widget settings handler.
function widget_listFactureCess_settings() {
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
