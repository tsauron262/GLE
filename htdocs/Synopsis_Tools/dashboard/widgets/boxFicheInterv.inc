<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
//require_once(DOL_DOCUMENT_ROOT."/boxes.php");

// The get widget handler.
function widget_boxFicheInterv() {
    global $langs; $langs->load("boxes");
    return boxToWidget("box_ficheInterv.php", "FI - ".$langs->trans("BoxLastFicheInterv"));
}

// The widget settings handler.
function widget_boxFicheInterv_settings() {
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
