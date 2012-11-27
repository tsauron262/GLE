<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_rechercheExpedition() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs, $bc;
    $langs->load("sendings");
    $table = '<p>';
    $var = true;
    if ($conf->expedition->enabled && $user->rights->expedition->lire)
    {
        $table .= '<form method="post" action="liste.php">';
        $table .= '<table class="noborder" width="100%">';
        $table .= '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchASending").'</td></tr>';
        $table .= '<tr '.$bc[$var].'><td>';
        $table .= $langs->trans("Ref").':</td><td><input type="text" class="flat" name="sf_ref" size="18"></td><td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
        $table .= "</table></form><br />\n";

    }

        $table .="</p>";

  return array(
    'title' => 'Exp&eacute;dition - Rechercher',
    'content' => utf8_encode($table),
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
  );
}

// The widget settings handler.
function widget_rechercheExpedition_settings() {
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
