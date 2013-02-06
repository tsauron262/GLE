<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.

function widget_statRemiseCheque() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs,$bc;
    $langs->load("banks");
    if ($user->rights->banque->cheque)
    {

        $table = '<p>';

        $sql = "SELECT count(b.rowid)";
        $sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
        $sql.= " WHERE b.fk_type = 'CHQ' AND b.fk_bordereau = 0";
        $sql.= " AND b.amount > 0";

        $resql = $db->query($sql);

        $table .= '<table class="noborder" width="100%">';
        $table .= '<tr class="liste_titre">';
        $table .= '<td colspan="2">'.$langs->trans("BankChecks")."</td>\n";
        $table .= "</tr>\n";

        if ($resql)
        {
          $var=false;
          if ($row = $db->fetch_row($resql) )
            {
              $num = $row[0];
            }
          $table .= "<tr ".$bc[$var].">";
          $table .= '<td>'.$langs->trans("BankChecksToReceipt").'</td>';
          $table .= '<td align="right">';
          $table .= '<a href="'.DOL_URL_ROOT.'/compta/paiement/cheque/fiche.php?leftmenu=customers_bills_checks&action=new">'.$num.'</a>';
          $table .= '</td></tr>';
          $table .= "</table>\n";
        }

        $table .= '</table>';

        $table .="</p>";

      return array(
        'title' => 'Remise de ch&egrave;que - Statistiques',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    } else {
      return array(
        'title' => 'Remise de ch&egrave;que - Statistiques',
        'content' => "Ce module ne vous est pas accessible",
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_statRemiseCheque_settings() {
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
