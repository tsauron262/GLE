<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_rechercheProduit() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs, $bc;
    $langs->load("bills");
    $table = '<p>';
    $table .= '<form method="post" action="'.DOL_URL_ROOT.'/product/liste_orig.php">';
    $table .=  '<table class="noborder" width="100%">';
    $table .=  "<tr class=\"liste_titre\">";
    $table .=  '<td colspan="3">'.$langs->trans("Search").'</td></tr>';
    $table .=  "<tr $bc[0]><td>";
    $table .=  $langs->trans("Ref").':</td><td><input class="flat" type="text" size="18" name="sref"></td>';
    $table .=  '<td rowspan="2"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
    $table .=  "<tr $bc[0]><td>";
    $table .=  $langs->trans("Other").':</td><td><input class="flat" type="text" size="18" name="sall"></td>';
    //print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';
    $table .=  '</tr>';
    $table .=  "</table></form><br>";

    $table .="</p>";

  return array(
    'title' => 'Produits/Services - Rechercher',
    'content' => utf8_encode($table),
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
  );
}

// The widget settings handler.
function widget_rechercheProduit_settings() {
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
