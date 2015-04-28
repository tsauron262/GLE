<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_creerSAV() {
  // A widget object as per jquery.dashboard.js.
    global $db;


  return array(
    'title' => 'SAV - Nouvelle demande',
    'content' => "<p><div class='ui-widget-content'><a href='".DOL_URL_ROOT."/Babel_GMAO/SAV/create.php'>Cr&eacute;er une nouvelle fiche</a></div></p>",
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,

  );
}

// The widget settings handler.
function widget_creerSAV_settings() {
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
