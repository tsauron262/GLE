<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_listPropaleFerme() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
        $langs->load("propal");
        $langs->load("commercial");

  $table = '<p>';
  $table2 = '<p>';


    if ($conf->propal->enabled)  require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
    $propalstatic=new Propal($db);
    require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

    $html = new Form($db);
    $formfile = new FormFile($db);

    $sql = "SELECT s.nom, s.rowid, p.rowid as propalid, p.total_ht, p.ref, p.fk_statut, p.datep as dp";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p";
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= " WHERE p.fk_soc = s.rowid AND p.fk_statut > 1";
    if ($socid)
    {
    $sql .= " AND s.rowid = ".$socid;
    }
    if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    $sql .= " ORDER BY p.rowid DESC";
    $sql .= $db->plimit(5, 0);

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
            $table .= "<td colspan=\"4\">".$langs->trans("PropalStatusClosed")."</td></tr>";

            $table2 .= '<table class="noborder" width="100%">';
            $table2 .= "<tr class=\"liste_titre\">";
            $table2 .= "<td colspan=\"4\">".$langs->trans("PropalStatusClosed")."</td></tr>";


            $bool = false;
            $arrCss[true]='pair';
            $arrCss[false]='impair';
            while ($res =$db->fetch_object($resql))
            {
                $propalstatic->id=$res->propalid;
                $propalstatic->ref=$res->ref;

                $bool = !$bool;
                if ($iter < 10)
                {
                        $var=!$var;
                        $table .= '<tr class='.$arrCss[$bool].'>
                                <td  nowrap="nowrap">'.$propalstatic->getNomUrl(1).'</td>';
                          $table .= '<td width="16" align="center" class="nobordernopadding">';
                          $filename=sanitize_string($res->ref);
                          $filedir=$conf->propal->dir_output . '/' . sanitize_string($res->ref);
                          $urlsource=$_SERVER['PHP_SELF'].'?propalid='.$res->propalid;
                          $table .= $formfile->getDocumentsLink('propal',$filename,$filedir);
                          $table .= $formfile->html;

                        $table .=  '<td nowrap="nowrap"><a href="card.php?socid='.$res->rowid.'">'.dol_trunc($res->nom,18).'</a></td>';
                        $table .=  '<td align="right" nowrap="nowrap">'.price($res->total_ht).'</td></tr>';
                        $total += $res->total_ht;
                }
                $var=!$var;
                $table2 .= '<tr class='.$arrCss[$bool].'>
                        <td  nowrap="nowrap">'.$propalstatic->getNomUrl(1).'</td>';
                  $table2 .= '<td width="16" align="center" class="nobordernopadding">';
                  $filename=sanitize_string($res->ref);
                  $filedir=$conf->propal->dir_output . '/' . sanitize_string($res->ref);
                  $urlsource=$_SERVER['PHP_SELF'].'?propalid='.$res->propalid;
                  $table2 .= $formfile->getDocumentsLink('propal',$filename,$filedir);
                  $table2 .= $formfile->html;

                $table2 .=  '<td nowrap="nowrap"><a href="card.php?socid='.$res->rowid.'">'.dol_trunc($res->nom,18).'</a></td>';
                $table2 .=  '<td align="right" nowrap="nowrap">'.price($res->total_ht).'</td></tr>';
                $total2 += $res->total_ht;
                $iter++;
            }
            if ($total>0)
            {
                $table .=  '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="3" align="right">'.price($total)."</td></tr>";
            }
            $table .=  "</table><br>";
            if ($total2>0)
            {
                $table2 .=  '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="3" align="right">'.price($total)."</td></tr>";
            }
            $table2 .=  "</table><br>";
        }
    }
    $table .="</p>";
    $table2 .="</p>";

  return array(
    'title' => 'Proposition commerciale - 10 derni&egrave;res ferm&eacute;es',
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
function widget_listPropaleFerme_settings() {
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
