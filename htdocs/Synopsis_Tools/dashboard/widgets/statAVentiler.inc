<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.

function widget_statAVentiler() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs,$bc;
    $langs->load("compta");
    $langs->load("bills");
    $langs->load("babel");
    $table = '<p>';



    $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."facturedet";
    $sql .= " WHERE fk_export_compta = 0";
    $result = $db->query($sql);
    if ($result)
    {
      $row = $db->fetch_row($result);
      $nbfac = $row[0];

      $db->free($result);
    }

    $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."paiement";
    $sql .= " WHERE fk_export_compta = 0";

    $result = $db->query($sql);
    if ($result)
    {
      $row = $db->fetch_row($result);
      $nbp = $row[0];

      $db->free($result);
    }

    $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."facture_fourn_det";
    $sql .= " WHERE fk_export_compta = 0";

    $result = $db->query($sql);
    if ($result)
    {
      $row = $db->fetch_row($result);
      $nbfacfourn = $row[0];

      $db->free($result);
    }

    $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."paiementfourn";
    $sql .= " WHERE fk_export_compta = 0";

    $result = $db->query($sql);
    if ($result)
    {
      $row = $db->fetch_row($result);
      $nbpfourn = $row[0];

      $db->free($result);
    }

    $table .= '<table class="noborder" width="100%">';
    $table .= '<tr class="liste_titre"><td colspan="2">Lignes a ventiler</tr>';
    $table .= '<tr class="liste_titre"><td>Type</td><td align="center">Nb</td></tr>';
    $table .= '<tr class="pair"><td>Factures clients</td><td align="center">'.$nbfac.'</td></tr>';
    $table .= '<tr class="pair"><td>Paiements clients</td><td align="center">'.$nbp.'</td></tr>';
    $table .= '<tr class="impair"><td>Factures fournisseurs</td><td align="center">'.$nbfacfourn.'</td></tr>';
    $table .= '<tr class="impair"><td>Paiements fournisseurs</td><td align="center">'.$nbpfourn.'</td></tr>';
    $table .= "</table>\n";

    $table .="</p>";

  return array(
    'title' => 'Compta - Lignes &agrave; ventiller',
    'content' => $table,
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
  );
}

// The widget settings handler.
function widget_statAVentiler_settings() {
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
