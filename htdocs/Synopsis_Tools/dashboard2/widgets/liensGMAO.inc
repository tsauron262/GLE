<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_liensGMAO() {
  // A widget object as per jquery.dashboard.js.
    global $db;
  $table = '<p><div class="ui-widget-content">';
  $table .= "<a href='iframe.php'>Tickets</a><br/>";
  $table .= "<a href='userAttrib.php'>Utilisateurs</a><br/>";
  $table .= "<a href='SAV.php'>SAV</a><br/>";
  $table .= "<a href='stockSAV.php'>stockSAV</a><br/>";
  $table .= "<a href='contrat.php'>Contrats</a><br/>";
  $table .= "<a href='etiquettes.php'>Etiquettes</a><br/>";
  $table .= "<a href='statsTicket.php'>Statistiques tickets</a><br/>";
  $table .="</div></p>";

  return array(
    'title' => 'Liens GMAO',
    'content' => utf8_encode($table),
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
  );
}

// The widget settings handler.
function widget_liensGMAO_settings() {
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
