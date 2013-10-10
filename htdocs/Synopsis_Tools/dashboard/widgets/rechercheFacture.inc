<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_rechercheFacture() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs;
    $langs->load("bills");
    $table = '<p>';
    if ($conf->facture->enabled && $user->rights->facture->lire)
    {
        $table .= '<form method="post" action="'.DOL_URL_ROOT.'/compta/facture/list.php">';
        $table .= '<table class="noborder" width="100%">';
        $table .= "<tr class=\"liste_titre\">";
        $table .= '<td colspan="3">'.$langs->trans("SearchACustomerInvoice").'</td></tr>';
        $table .= "<tr  class='pair'><td>".$langs->trans("Ref").':</td><td><input type="text" name="sf_ref" class="flat" size="18"></td>';
        $table .= '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
        $table .= "<tr  class='pair'><td>".$langs->trans("Other").':</td><td><input type="text" name="sall" class="flat" size="18"></td>';
        $table .= '</tr>';
        $table .= "</table></form><br>";
//TODO sidroit de lire les facture fourn
        $table .= '<form method="post" action="'.DOL_URL_ROOT.'/fourn/facture/index.php">';
        $table .= '<table class="noborder" width="100%">';
        $table .= '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchASupplierInvoice").'</td></tr>';
        $table .= "<tr class='pair'>";
        $table .= "<td>".$langs->trans("Ref").':</td><td><input type="text" name="search_ref" class="flat" size="18"></td>';
        $table .= '<td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
        //$table .= "<tr  class='pair'><td>".$langs->trans("Other").':</td><td><input type="text" name="sall" class="flat" size="18"></td>';
        $table .= '</tr>';
        $table .= "</table></form><br>";
    }

        $table .="</p>";

  return array(
    'title' => 'Facture - Rechercher',
    'content' => $table,
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
  );
}

// The widget settings handler.
function widget_rechercheFacture_settings() {
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
