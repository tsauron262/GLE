<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php');

// The get widget handler.
function widget_listStock() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("products");

    $table ="<p>";
    $table2 ="<p>";
    require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
    require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");

    $staticproduct=new Product($db);


    $max=50;
    $sql = "SELECT e.label, e.rowid, e.statut FROM ".MAIN_DB_PREFIX."entrepot as e";
    $sql .= " WHERE statut in (0,1) ORDER BY e.statut DESC ";
    $sql .= $db->plimit($max ,0);
    $result = $db->query($sql) ;
    $table ="";
    $table2 ="";
    if ($result)
    {
        $num = $db->num_rows($result);

        $i = 0;

        $table .= '<table class="noborder" width="100%">';
        $table .= '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Warehouses").'</td></tr>';
        $table2 .= '<table class="noborder" width="100%">';
        $table2 .= '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Warehouses").'</td></tr>';

        if ($num)
        {
            $entrepot=new Entrepot($db);

            $var=True;
            while ($i < $num)
            {
                if ($i < 10)
                {
                    $objp = $db->fetch_object($result);
                    $var=!$var;
                    $table .= "<tr $bc[$var]>";
                    $table .= "<td><a href=\"fiche.php?id=$objp->rowid\">".img_object($langs->trans("ShowStock"),"stock")." ".$objp->label."</a></td>\n";
                    $table .= '<td align="right">'.$entrepot->LibStatut($objp->statut,3).'</td>';
                    $table .= "</tr>\n";
                }
                $objp = $db->fetch_object($result);
                $var=!$var;
                $table2 .= "<tr $bc[$var]>";
                $table2 .= "<td><a href=\"fiche.php?id=$objp->rowid\">".img_object($langs->trans("ShowStock"),"stock")." ".$objp->label."</a></td>\n";
                $table2 .= '<td align="right">'.$entrepot->LibStatut($objp->statut,3).'</td>';
                $table2 .= "</tr>\n";
                $i++;
            }
            $db->free($result);

        }
        $table .= "</table>";
        $table2 .= "</table>";
    } else
    {
      dol_print_error($db);
    }


    $table .="</p>";
    $table2 .="</p>";
      return array(
        'title' => 'Stock - 10 derniers entrepots',
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
function widget_listStock_settings() {
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
