<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/Babel_Affaire/Affaire.class.php');



// The get widget handler.
function widget_listAffaire() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user;
    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
        $langs->load("bills");

  $table = '<p>';
  $table2 = '<p>';


    $sql  = "SELECT *";
    $sql .= " FROM Babel_Affaire as f";
    $sql .= " ORDER BY f.date_creation DESC LIMIT 50";
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
            $table .= "<td colspan=\"2\">".$langs->trans("Affaires")."</td></tr>";

            $table2 .= '<table class="noborder" width="100%">';
            $table2 .= "<tr class=\"liste_titre\">";
            $table2 .= "<td colspan=\"2\">".$langs->trans("Affaires")."</td></tr>";

            $bool = false;
            $arrCss[true]='pair';
            $arrCss[false]='impair';
            $facturestatic=new Affaire($db);
            while ($res =$db->fetch_object($resql))
            {
				$facturestatic->fetch($res->id);
                $bool = !$bool;
                if ($iter < 10)
                {
                    $table .= '<tr class='.$arrCss[$bool].'>
									<td  nowrap="nowrap">';
                    $table .= $facturestatic->getNomUrl(1,'');
                    $table .=  '<td align="right" nowrap="nowrap">'.$facturestatic->getLibStatut(4).'</td></tr>';
                }
                $table2 .= '<tr class='.$arrCss[$bool].'>
                        <td  nowrap="nowrap">';
                $table2 .= $facturestatic->getNomUrl(1,'');
                $table2 .=  '<td align="right" nowrap="nowrap">'.$facturestatic->getLibStatut(4).'</td></tr>';
                $iter++;
            }
            $table .=  "</table><br>";
            $table2 .=  "</table><br>";
        }
    }
    $table .="</p>";
    $table2 .="</p>";

  return array(
    'title' => 'Affaire - 10 derni&egrave;res affaires',
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
function widget_listAffaire_settings() {
	return(array('markup' => '<div>Aucune option pour ce module</div>'));
}