<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
// The get widget handler.
require_once(DOL_DOCUMENT_ROOT.'/fourn/fournisseur.commande.class.php');

function widget_statCommandeFourn() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf, $user, $langs,$bc;
    $langs->load("suppliers");
    $langs->load("orders");
    $langs->load("companies");
    if ($user->rights->fournisseur->commande->lire)
    {
        $table = '<p>';

        /*
         *
         */
        $commande = new CommandeFournisseur($db);
        $sql = "SELECT count(cf.rowid), fk_statut";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s,";
        $sql.= " ".MAIN_DB_PREFIX."commande_fournisseur as cf";
        $sql.= " WHERE cf.fk_soc = s.rowid ";
        $sql.= " GROUP BY cf.fk_statut";

        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;

            $table .= '<table class="liste" width="100%">';
            $table .= '<tr class="liste_titre"><td>'.$langs->trans("Orders").'</td><td align="center">'.$langs->trans("Nb").'</td><td>&nbsp;</td>';
            $table .= "</tr>\n";
            $var=True;

            while ($i < $num)
            {
                $row = $db->fetch_row($resql);
                $var=!$var;

                $table .= "<tr $bc[$var]>";
                $table .= '<td>'.$commande->statuts[$row[1]].'</td>';
                $table .= '<td align="center">'.$row[0].'</td>';
                $table .= '<td align="center"><a href="'.DOL_URL_ROOT.'/fourn/commande/liste.php?statut='.$row[1].'">'.$commande->LibStatut($row[1],3).'</a></td>';

                $table .= "</tr>\n";
                $i++;
            }
            $table .= "</table>";
            $db->free($resql);
        }

        $table .="</p>";

      return array(
        'title' => 'Fournisseur - Stats commandes',
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    } else {
      return array(
        'title' => 'Fournisseur - Stats commandes',
        'content' => "Ce module ne vous est pas accessible",
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    }
}

// The widget settings handler.
function widget_statCommandeFourn_settings() {
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
