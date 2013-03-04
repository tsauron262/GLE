<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once (DOL_DOCUMENT_ROOT."/Babel_Chrono/Chrono.class.php");

// The get widget handler.
function widget_listChronoAValider() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("chrono");


      $table =  '<p>';
      $table2 = '</p>';



    if ($user->rights->Chrono->Global->Afficher)
    {
        $sql = "SELECT *
                  FROM Babel_Chrono
                 WHERE fk_statut = 999
              ORDER BY c.tms DESC";

        $result=$db->query($sql);
        $chrono = new chrono($db);
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;



            $var=true;
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $chrono->fetch($res->id);
                $chrono->getGlobalRights();
                if (!$chrono->canValidate()) continue;

                if ($i == 0)
                {
                    $table .=  '<table class="noborder" width="100%">';

                    $table .=  '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Chrono").'</td>';
                    $table .=  '<td align="center">'.$langs->trans("DateModification").'</td>';
                    $table .=  '<td align="right" width="80" >'.$langs->trans("Status").'</td>';
                    $table .=  "</tr>\n";

                    $table2 .=  '<table class="noborder" width="100%">';

                    $table2 .=  '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Chrono",50).'</td>';
                    $table2 .=  '<td align="center">'.$langs->trans("DateModification").'</td>';
                    //$table .=  '<td align="left">'.$langs->trans("Status").'</td>';
                    $table2 .=  '<td align="right" width="80" >'.$langs->trans("Status").'</td>';
                    $table2 .=  "</tr>\n";

                }


                $var=!$var;

                if ($i < 10)
                {
                    $table .=  "<tr $bc[$var]>";
                    $table .=  "<td colspan=2>";
                    $table .=  "".$chrono->getNomUrl(1);
                    $table .=  "<td>";
                    $table .=  "".date('d/m/Y',$chrono->date_modif);
                    $table .=  "<td>";
                    $table .=  "".$chrono->getLibStatut(2);
                    $table .=  "</tr>\n";
                }

                $table2 .=  "<tr $bc[$var]>";
                $table2 .=  "<td colspan=2>";
                $table2 .=  "".$chrono->getNomUrl(1);
                $table2 .=  "<td>";
                $table2 .=  "".date('d/m/Y',$chrono->date_modif);
                $table2 .=  "<td>";
                $table2 .=  "".$chrono->getLibStatut(2);
                $table2 .=  "</tr>\n";
                $i++;
                if($i >50){
                    break;
                }
            }
            $db->free($result);

            $table .=  "</table>";
            $table2 .=  "</table>";
        }

        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Chrono - Derniers chrono &agrave; valider',
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
        'title' => 'Chrono - Derniers chrono &agrave; valider',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listChronoAValider_settings() {
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
