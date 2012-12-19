<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once (DOL_DOCUMENT_ROOT."/expedition/expedition.class.php");

// The get widget handler.
function widget_listExpedition() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;
    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("sendings");

      $table =  '<p>';
      $table2 = '</p>';

    if ($conf->expedition->enabled && $user->rights->expedition->lire)
    {
        $clause = " WHERE ";
        $sql = "SELECT e.rowid, e.ref";
        $sql.= ", s.nom, s.rowid as socid";
        $sql.= ", c.ref as commande_ref, c.rowid as commande_id";
        $sql.= " FROM ".MAIN_DB_PREFIX."expedition as e";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."co_exp as ce ON e.rowid = ce.fk_expedition";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON ce.fk_commande = c.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
        if (!$user->rights->societe->client->voir && !$socid)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON e.fk_soc = sc.fk_soc";
            $sql.= $clause." sc.fk_user = " .$user->id;
            $clause = " AND ";
        }
        if ($socid)
        {
            $sql .= $clause . " c.fk_soc = ".$socid;
        }

        $sql.= " LIMIT 50";

        $result=$db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;

            $table .=  '<table class="noborder" width="100%">';

            $table .=  '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Sendings",30).'</td>';
            $table .=  '<td align="center" width="80" colspan="">'.$langs->trans("Comm.").'</td>';
            $table .=  '<td align="right" width="80" colspan="">'.$langs->trans("Statut").'</td>';
            $table .=  "</tr>\n";

            $table2 .=  '<table class="noborder" width="100%">';

            $table2 .=  '<tr class="liste_titre"><td colspan="1">'.$langs->trans("Sendings",30).'</td>';
            $table2 .=  '<td align="center" width="80" colspan="">'.$langs->trans("Soci&eacute;t&eacute;").'</td>';
            $table2 .=  '<td align="center" width="80" colspan="">'.$langs->trans("Comm.").'</td>';
            $table2 .=  '<td align="right" width="80" colspan="">'.$langs->trans("Statut").'</td>';
            $table2 .=  "</tr>\n";


            $var=true;
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $var=!$var;
                $exp = new Expedition($db);
                $exp->fetch($obj->rowid);

                if ($i < 10)
                {
                    $table .= "<tr $bc[$var]><td nowrap=\"nowrap\">".$exp->getNomUrl(1);
                    $table .= '<td>'.$exp->societe->getNomUrl(1);
                    $table .= '<td>'.$exp->commande->getNomUrl(1);
                    $table .= '<td align=right>'.$exp->getLibStatut(5).'</td></tr>';
                }
                $table2 .= "<tr $bc[$var]><td nowrap=\"nowrap\">".$exp->getNomUrl(1);
                $table2 .= '<td>'.$exp->societe->getNomUrl(1);
                $table2 .= '<td>'.$exp->commande->getNomUrl(1);
                $table2 .= '<td align=right>'.$exp->getLibStatut(5).'</td></tr>';

                $i++;
            }
            $db->free($result);

            $table .=  "</table>";
            $table2 .=  "</table>";
        }

        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Exp&eacute;dition - 10 derni&egrave;res exp&eacute;ditions',
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => utf8_encode($table2),
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
      );

    } else {
      return array(
        'title' => 'Exp&eacute;dition - 10 derni&egrave;res exp&eacute;ditions',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listExpeditionAValider() {
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
