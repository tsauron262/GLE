<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_rechercheContrat() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs;
    $langs->load("contracts");
    $table = '<p>';
    if ($conf->contrat->enabled && $user->rights->contrat->lire)
    {
      $var=false;
      $table .= '<form method="post" action="'.DOL_URL_ROOT.'/contrat/list.php">';
      $table .=  '<table class="noborder" width="100%">';
      $table .=  '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAContract").'</td></tr>';
      $table .=  '<tr class="impair">';
      $table .=  '<td nowrap>'.$langs->trans("Ref").':</td><td><input type="text" class="flat" name="search_contract" size="18"></td>';
      $table .=  '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
      $table .=  '<tr class="impair"><td nowrap>'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="sall" size="18"></td>';
      $table .=  '</tr>';
      $table .=  "</table></form>\n";
      $table .=  "<br>";
    }

        $table .="</p>";

  return array(
    'title' => 'Contrat - Rechercher',
    'content' => $table,
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
  );
}

// The widget settings handler.
function widget_rechercheContrat_settings() {
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
