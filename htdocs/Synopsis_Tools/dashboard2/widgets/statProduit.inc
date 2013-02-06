<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.

function widget_statProduit() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs,$bc;
    $langs->load("products");
    if ($user->rights->produit->lire)
    {
        $table = '<p>';

        $prodser = array();
        $prodser[0][0]=$prodser[0][1]=$prodser[1][0]=$prodser[1][1]=0;

        $sql = "SELECT COUNT(p.rowid) as total, p.fk_product_type, p.envente";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        $sql.= " GROUP BY p.fk_product_type, p.envente";
        $result = $db->query($sql);
        while ($objp = $db->fetch_object($result))
        {
            $prodser[$objp->fk_product_type][$objp->envente]=$objp->total;
        }

        $table .= '<table class="noborder" width="100%">';
        $table .= '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';
        if ($conf->produit->enabled)
        {
          $statProducts = "<tr $bc[0]>";
          $statProducts.= '<td><a href="liste_orig.php?type=0&amp;envente=0">'.$langs->trans("ProductsNotOnSell").'</a></td><td align="right">'.round($prodser[0][0]).'</td>';
          $statProducts.= "</tr>";
          $statProducts.= "<tr $bc[1]>";
          $statProducts.= '<td><a href="liste_orig.php?type=0&amp;envente=1">'.$langs->trans("ProductsOnSell").'</a></td><td align="right">'.round($prodser[0][1]).'</td>';
          $statProducts.= "</tr>";
        }
        $table .= $statProducts;

        $table .= '</table>';

        $table .="</p>";

      return array(
        'title' => 'Produits - Statistiques',
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    } else {
      return array(
        'title' => 'Produits - Statistiques',
        'content' => "Ce module ne vous est pas accessible",
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    }
}

// The widget settings handler.
function widget_statProduit_settings() {
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
