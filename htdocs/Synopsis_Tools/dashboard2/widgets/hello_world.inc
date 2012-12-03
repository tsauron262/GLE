<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_hello_world() {
  // A widget object as per jquery.dashboard.js.
  return array(
    'title' => 'Votre tableau de board - Aide',
    'content' => '<p class="ui-widget-content">Faite glisser les  widgets pour les positionner o&ugrave; vous le souhaiter.</p>',
    'initScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/init.js',
    'classes' => 'search highlight',
    'settings' => false,
  );
}

// The widget settings handler.
function widget_hello_world_settings() {

  // A widget settings object as per jquery.dashboard.js.
  return array(
    'markup' => '<div>Pas de param&egrave;tres dans ce module</div>'
  );
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
