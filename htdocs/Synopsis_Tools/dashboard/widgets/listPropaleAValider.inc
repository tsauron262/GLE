<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_listPropaleAValider() {
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

if ( $user->rights->propale->valider == 1)
{
    $sql = "SELECT fk_soc,
                   rowid,
                   ref,
                   unix_timestamp(tms) as uts";
    $sql .= " FROM ".MAIN_DB_PREFIX."propal";
    $sql .= " WHERE fk_statut = 5";
    $sql .= " ORDER BY ".MAIN_DB_PREFIX."propal.tms DESC";
    $sql .= $db->plimit(50, 0);
//$table .= $sql;
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
            $table .= "<td colspan=\"4\">".$langs->trans("PropalStatusValidatedShort")."</td></tr>";

            $table2 .= '<table class="noborder" width="100%">';
            $table2 .= "<tr class=\"liste_titre\">";
            $table2 .= "<td colspan=\"4\">".$langs->trans("PropalStatusValidatedShort")."</td></tr>";


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
                        $tmpsoc = new Societe($db);
                        $tmpsoc->id = $res->fk_soc;
                        $tmpsoc->fetch($res->fk_soc);
                        $table .= "<tr class='".$arrCss[$bool]."'><td><a href=\"fiche.php?id=".$res->rowid."\">".img_object("","propal")." ".$res->ref."</a></td>";
                        $table .= "<td>".$tmpsoc->getNomUrl(1)."</td>\n";
                        $table .= "<td align=\"right\">".date("d/m/Y",$res->uts)."</td></tr>\n";
                        $total += $res->total_ht;
                }

                $tmpsoc = new Societe($db);
                $tmpsoc->id = $res->fk_soc;
                $tmpsoc->fetch($res->fk_soc);
                $table2 .= "<tr class='".$arrCss[$bool]."'><td><a href=\"fiche.php?id=".$res->rowid."\">".img_object("","propal")." ".$res->ref."</a></td>";
                $table2 .= "<td>".$tmpsoc->getNomUrl(1)."</td>\n";
                $table2 .= "<td align=\"right\">".date("d/m/Y",$res->uts)."</td></tr>\n";
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
        } else {
            $table = "<p><div class='ui-widget-content'>Pas de propositions &agrave; valider</div>";
            $table2 = "<p><div  class='ui-widget-content'>Pas de propositions &agrave; valider</div>";
        }
    }
    $table .="</p>";
    $table2 .="</p>";

  return array(
    'title' => 'Proposition commerciale - 10 derni&egrave;res prop. &agrave; valider',
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
    'title' => 'Proposition commerciale - 10 derni&egrave;res prop. &agrave; valider',
    'content' => utf8_encodeRien("Ce modulenevous est pas accessible."),
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
  );

}
}

// The widget settings handler.
function widget_listPropaleAValider_settings() {

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
