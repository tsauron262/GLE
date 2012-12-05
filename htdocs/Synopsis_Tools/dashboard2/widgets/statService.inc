<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.

function widget_statService() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs,$bc;
    if ($user->rights->produit->lire)
    {

        $langs->load("products");

        $table = '<p>';

        $prodser = array();
        $prodser[0][0]=$prodser[0][1]=$prodser[1][0]=$prodser[1][1]=0;

        $sql = "SELECT COUNT(p.rowid) as total, p.fk_product_type, p.envente";
        $sql.= " FROM llx_product as p";
        $sql.= " GROUP BY p.fk_product_type, p.envente";
        $result = $db->query($sql);
        while ($objp = $db->fetch_object($result))
        {
            $prodser[$objp->fk_product_type][$objp->envente]=$objp->total;
        }

        $table .= '<table class="noborder" width="100%">';
        $table .= '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';
        if ($conf->service->enabled)
        {
          $statServices = "<tr $bc[0]>";
          $statServices.= '<td><a href="liste.php?type=1&amp;envente=0">'.$langs->trans("ServicesNotOnSell").'</a></td><td align="right">'.round($prodser[1][0]).'</td>';
          $statServices.= "</tr>";
          $statServices.= "<tr $bc[1]>";
          $statServices.= '<td><a href="liste.php?type=1&amp;envente=1">'.$langs->trans("ServicesOnSell").'</a></td><td align="right">'.round($prodser[1][1]).'</td>';
          $statServices.= "</tr>";
        }
        $table .= $statServices;

        $table .= '</table>';

        $table .="</p>";

      return array(
        'title' => 'Services - Statistiques',
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    } else {
      return array(
        'title' => 'Services - Statistiques',
        'content' => "Ce module ne vous est pas accessible",
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    }
}

// The widget settings handler.
function widget_statService_settings() {
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
