<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT."/compta/prelevement/class/bonprelevement.class.php";
// The get widget handler.

function widget_statPrelevement() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs,$bc;

    $langs->load("withdrawals");
    if ($user->rights->prelevement->bons->lire)
    {

        $table = '<p>';

        require_once DOL_DOCUMENT_ROOT."/core/modules/modPrelevement.class.php";

        $bprev = new BonPrelevement($db);
        $var=true;

        $table .= '<table class="noborder" width="100%">';
        $table .= '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';
        $var=!$var;
        $table .= '<tr '.$bc[$var].'><td>Nb de facture &agrave; pr&eacute;lever</td>';
        $table .= '<td align="right">';
        $table .= $bprev->NbFactureAPrelever();
        $table .= '</td></tr>';
        $var=!$var;
        $table .= '<tr '.$bc[$var].'><td>Somme &agrave; pr&eacute;lever</td>';
        $table .= '<td align="right">';
        $table .= price($bprev->SommeAPrelever());
        $table .= '</td></tr></table><br>';


        $table .="</p>";

      return array(
        'title' => 'Pr&eacute;l&egrave;vement - Statistiques',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    } else {
      return array(
        'title' => 'Pr&eacute;l&egrave;vement - Statistiques',
        'content' => "Ce module ne vous est pas accessible.",
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    }
}

// The widget settings handler.
function widget_statPrelevement_settings() {
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
