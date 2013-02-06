<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");

// The get widget handler.
function widget_boxFactureFournImp() {
    global $langs;
    return boxToWidget("box_factures_fourn_imp.php", 'Fournisseur - Les 10 derni&egrave;res factures impay&eacute;es');
}

// The widget settings handler.
function widget_boxFactureFournImp_settings() {
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
