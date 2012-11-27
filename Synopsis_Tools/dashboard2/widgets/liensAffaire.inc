<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_liensAffaire() {
  // A widget object as per jquery.dashboard.js.
    global $db;
  $table = '<p><div class="ui-widget-content">';
  $table .= "<a href='nouveau.php'>Nouvelle affaire</a><br/>";
  $table .= "<a href='nouveau.php'>Liste</a><br/>";
  $table .="</div></p>";

  return array(
    'title' => 'Liens Affaires',
    'content' => utf8_encode($table),
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
  );
}

// The widget settings handler.
function widget_liensAffaire_settings() {
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
