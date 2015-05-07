<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.

function widget_statComptaVentile() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs,$bc;
    $langs->load("compta");
    $langs->load("bills");
    $langs->load("babel");
    $table = '<p>';

    $table .= '<table class="noborder" width="100%">';
    $table .= '<tr class="liste_titre"><td>Type</td><td align="center">'.$langs->trans("NbOfLines").'</td><td align="center">'.$langs->trans("AccountNumber").'</td><td align="center">'.$langs->trans("TransID").'</td></tr>';

    $sql = "SELECT count(*), ccg.label as intitule, ccg.rowid,ccg.account_number as numero FROM ".MAIN_DB_PREFIX."facturedet as fd";
    $sql.= " ,".MAIN_DB_PREFIX."accountingaccount as ccg";
    $sql.= " WHERE fd.fk_code_ventilation = ccg.rowid";
    $sql.= " GROUP BY ccg.rowid";
    //$table .= $sql;
    $resql = $db->query($sql);
    if ($resql)
    {
        $i = 0;
        $num = $db->num_rows($resql);
        $var=true;

        while ($i < $num)
        {

            $row = $db->fetch_row($resql);
            $var=!$var;
            $table .= '<tr '.$bc[$var].'><td>'.$row[1].'</td><td align="center">'.$row[0].'</td>';
            $table .= '<td align="center">'.$row[3].'</td><td align="center">'.$row[2].'</td></tr>';
            $i++;
        }
        $db->free($resql);
    }
    $table .= "</table>\n";

    $table .="</p>";

  return array(
    'title' => 'Compta - Stats. comptes g&eacute;n&eacute;raux (client)',
    'content' => $table,
    'initScript' => "",
    'classes' => 'ui-state-default ui-widget-header',
    'settings' => false,
  );
}

// The widget settings handler.
function widget_statComptaVentile_settings() {
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
