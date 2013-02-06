<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
// The get widget handler.

function widget_contratLegend() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs,$bc;
    $langs->load("companies");
    $langs->load("contracts");
    $staticcontratligne=new ContratLigne($db);

    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $table = '<p>';
    $var = false;

    $table .=  '<table class="noborder" width="100%">';
    $table .=  '<tr class="liste_titre"><td colspan="3">'.$langs->trans("ServicesLegend").'</td></tr>';
    $table .=  '<tr '.$bc[$var].'><td nowrap>';
    $table .=  $staticcontratligne->LibStatut(0,4).'<br />';
    $table .=  $staticcontratligne->LibStatut(4,4).'<br />';
    $table .=  $staticcontratligne->LibStatut(5,4).'<br />';
    $table .=  '</td></tr>';
    $table .=  '</table>';


    $table .="</p>";

  return array(
    'title' => 'Contrat - L&eacute;gendes',
    'content' => utf8_encode($table),
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
  );
}

// The widget settings handler.
function widget_contratLegend_settings() {
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
