<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
function widget_categorieFourn() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs,$bc;
    if ($user->rights->fournisseur->lire)
    {
        $langs->load("suppliers");
        $langs->load("orders");
        $langs->load("companies");
        $table = '<p>';
        $companystatic=new Societe($db);
        $companystatic->LoadSupplierCateg();

        if (sizeof($companystatic->SupplierCategories))
        {
                $table .= '<table class="liste" width="100%">';
                $table .= '<tr class="liste_titre"><td colspan="2">';
                $table .= $langs->trans("Category");
                $table .= "</td></tr>\n";
                $var=True;

                foreach ($companystatic->SupplierCategories as $rowid => $label)
                {
                    $var=!$var;
                    $table .= "<tr $bc[$var]>\n";
                    $table .= '<td><a href="liste.php?cat='.$rowid.'">'.stripslashes($label).'</a>';
                    $table .= '</td><td align="right">';
                    $table .= '<a href="stats.php?cat='.$rowid.'">('.$langs->trans("Stats").')</a>';
                    $table .= "</tr>\n";
                }
                $table .= "</table>\n";
                $table .= "<br>\n";
        } else {
            $table .= "Pas de cat&eacute;gorie d&eacute;finie";
        }
        $table .="</p>";

      return array(
        'title' => 'Fournisseur - Cat&eacute;gorie',
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    } else {
      return array(
        'title' => 'Fournisseur - Cat&eacute;gorie',
        'content' => "Ce module ne vous est pas accessible.",
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_categorieFourn_settings() {
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
