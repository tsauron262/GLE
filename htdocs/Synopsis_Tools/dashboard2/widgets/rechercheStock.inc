<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
    require_once(DOL_DOCUMENT_ROOT."/product/stock/entrepot.class.php");

// The get widget handler.
function widget_rechercheStock() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs, $bc;
    $langs->load("bills");
    $table = '<p>';
    $table .= '<form method="post" action="'.DOL_URL_ROOT.'/product/stock/liste.php">';
    $table .= '<table class="noborder" width="100%">';
    $table .= "<tr class=\"liste_titre\">";
    $table .= '<td colspan="3">'.$langs->trans("Search").'</td></tr>';
    $table .= "<tr $bc[0]><td>";
    $table .= $langs->trans("Ref").':</td><td><input class="flat" type="text" size="18" name="sf_ref"></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
    $table .= "<tr $bc[0]><td>".$langs->trans("Other").':</td><td><input type="text" name="sall" class="flat" size="18"></td>';
    $table .= "</table></form><br>";

    $table .="</p>";

  return array(
    'title' => 'Stock - Rechercher',
    'content' => utf8_encode($table),
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
  );
}

// The widget settings handler.
function widget_rechercheStock_settings() {
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
