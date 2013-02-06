<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_rechercheAction() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs, $bc;
    $langs->load("bills");
    $langs->load("companies");
    $langs->load("commercial");
    $langs->load("agenda");

    $var=true;
    $table = '<p>';
    $table .=  '<form method="post" action="'.DOL_URL_ROOT.'/comm/action/listactions.php">';
    $table .= '<table class="noborder" width="100%">';
    $table .=  '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAnAction").'</td></tr>';
    $table .=  '<tr '.$bc[$var].'><td>';
    $table .=  $langs->trans("Keyword").':</td><td><input type="text" class="flat" name="sf_ref" size="18"></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
    $table .=  '</tr>';
    $table .=  "</table></form><br>\n";

    $table .="</p>";

  return array(
    'title' => 'Actions - Rechercher',
    'content' => utf8_encode($table),
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
  );
}

// The widget settings handler.
function widget_rechercheAction_settings() {
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
