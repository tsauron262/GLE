<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");

// The get widget handler.
function widget_boxProspect() {
    global $langs;
    return boxToWidget("box_prospect.php", "Tiers - ".$langs->trans("BoxLastProspects"));
}

// The widget settings handler.
function widget_boxProspect_settings() {
    return(array('markup' => '<div>Aucune option pour ce module</div>'));
}

//// A silly little helper function for the form.
//function selected($data, $color) {
//  if ($data['color'] == $color) {
//    return ' value="' . $color . '" selected="selected"';
//  }
//  else {
//    return ' value="' . $color . '"';
//  }
//}
