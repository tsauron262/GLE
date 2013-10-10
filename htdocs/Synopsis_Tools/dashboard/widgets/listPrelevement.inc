<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");

// The get widget handler.
function widget_listPrelevement() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("withdrawals");


      $table =  '<p>';
      $table2 = '</p>';


    if ($user->rights->prelevement->bons->lire)
    {

        $sql = "SELECT p.rowid, p.ref, p.amount,p.datec as datec";
        $sql .= " ,p.statut ";
        $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
        $sql .= " ORDER BY datec DESC LIMIT 50";

        $result = $db->query($sql);
        if ($result)
        {
          $num = $db->num_rows($result);
          $i = 0;
          $var=True;

          $table .=  '<table class="noborder" width="100%">';
          $table .=  '<tr class="liste_titre"><td>'.$langs->trans("WithdrawalReceiptShort").'</td><td>'.$langs->trans("Date").'</td>';
          $table .=  '<td align="right">'.$langs->trans("Amount").'</td>';
          $table .=  '</tr>';

          $table2 .=  '<table class="noborder" width="100%">';
          $table2 .=  '<tr class="liste_titre"><td>'.$langs->trans("WithdrawalReceiptShort").'</td><td>'.$langs->trans("Date").'</td>';
          $table2 .=  '<td align="right">'.$langs->trans("Amount").'</td>';
          $table2 .=  '</tr>';

          while ($i < $num)
            {
              $obj = $db->fetch_object($result);
              $var=!$var;
              if ($i < 10)
              {
                  $table .=  "<tr $bc[$var]><td>";
                  $table .=  '<img border="0" src="./statut'.$obj->statut.'.png"></a>&nbsp;';
                  $table .=  '<a href="fiche.php?id='.$obj->rowid.'">'.$obj->ref."</a></td>\n";
                  $table .=  '<td>'.dol_print_date($obj->datec,"dayhour")."</td>\n";
                  $table .=  '<td align="right">'.price($obj->amount)."</td>\n";
                  $table .=  "</tr>\n";
              }
              $table2 .=  "<tr $bc[$var]><td>";
              $table2 .=  '<img border="0" src="./statut'.$obj->statut.'.png"></a>&nbsp;';
              $table2 .=  '<a href="fiche.php?id='.$obj->rowid.'">'.$obj->ref."</a></td>\n";
              $table2 .=  '<td>'.dol_print_date($obj->datec,"dayhour")."</td>\n";
              $table2 .=  '<td align="right">'.price($obj->amount)."</td>\n";
              $table2 .=  "</tr>\n";

              $i++;
            }
          $table .=  "</table>";
          $db->free($result);
        }


        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Pr&eacute;l&egrave;vement - 10 derniers pr&eacute;l&egrave;vements',
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
        'title' => 'Pr&eacute;l&egrave;vement - 10 derniers pr&eacute;l&egrave;vements',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listPrelevement_settings() {
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
