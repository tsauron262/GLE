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
function widget_listExpeditionPrepaCommande() {
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
        $sql = "  SELECT c.rowid as comId, c.date_livraison, bs.label, e.rowid as expId
                    FROM llx_commande AS c
               LEFT JOIN BIMP_site_commande AS b ON c.rowid = b.fk_commande
               LEFT JOIN BIMP_site AS bs ON b.site_id = bs.id
               LEFT JOIN llx_societe AS s ON s.rowid  = c.fk_soc
               LEFT JOIN llx_co_exp AS ce ON c.rowid = ce.fk_commande
               LEFT JOIN llx_expedition AS e ON e.rowid = ce.fk_expedition ";
    if (!$user->rights->societe->client->voir && !$socid)
    {
        $sql.= " LEFT JOIN llx_societe_commerciaux as sc ON e.fk_soc = sc.fk_soc";
        $sql.= $clause." sc.fk_user = " .$user->id;
        $clause = " AND ";
    }
         $sql .= " WHERE c.date_livraison IS NOT NULL
                     AND b.site_id IS NOT NULL
                   LIMIT 50";
        $result=$db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;
            $table .=  '<table class="noborder" width="100%">';
            $table .=  '<tr class="liste_titre"><td colspan="1">'.$langs->trans("Commande",10).'</td>';
            $table .=  '<td colspan="1">'.$langs->trans("Expedition",10).'</td>';
            $table .=  '<td colspan="1" width=16></td>';
            $table .=  '<td  width="80" colspan="1">'.$langs->trans("Date liv.").'</td>';
            $table .=  '<td  width="80" colspan="1">'.$langs->trans("Dest.").'</td>';
            $table .=  "</tr>\n";

            $table2 .=  '<table class="noborder" width="100%">';

            $table2 .=  '<tr class="liste_titre"><td colspan="1">'.$langs->trans("Commande",50).'</td>';
            $table2 .=  '<td colspan="1">'.$langs->trans("Expedition",10).'</td>';
            $table2 .=  '<td colspan="1" width=16></td>';
            $table2 .=  '<td width="80" colspan="1">'.$langs->trans("Date liv.").'</td>';
            $table2 .=  '<td width="80" colspan="1">'.$langs->trans("Dest.").'</td>';
            $table2 .=  "</tr>\n";


            $var=true;
            require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
            require_once(DOL_DOCUMENT_ROOT."/expedition/expedition.class.php");
            $com = new Commande($db);
            $exp = new Expedition($db);

            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $var=!$var;
                $com->fetch($obj->comId);
                $exp->fetch($obj->expId);

                if ($i < 10)
                {
                    $table .= "<tr $bc[$var]><td nowrap=\"nowrap\">".$com->getNomUrl(1,6)."</td>";
                    if ($exp->id > 0)
                        $table .= '<td>'.$exp->getNomUrl(1)."<td>".$exp->getLibStatut(2).'</td>';
                    else
                        $table .= '<td colspan=2></td>';
                    $table .= '<td>'.date('d/m/Y',$com->date_livraison).'</td>';
                    $table .= '<td>'.$obj->label.'</td>';
                }
                $table2 .= "<tr $bc[$var]><td nowrap=\"nowrap\">".$com->getNomUrl(1,6)."</td>";
                if ($exp->id > 0)
                    $table2 .= '<td>'.$exp->getNomUrl(1)."<td>".$exp->getLibStatut(2).'</td>';
                else
                    $table2 .= '<td colspan=2></td>';
                $table2 .= '<td>'.date('d/m/Y',$com->date_livraison).'</td>';
                $table2 .= '<td>'.$obj->label.'</td>';

                $i++;
            }
            $db->free($result);

            $table .=  "</table>";
            $table2 .=  "</table>";
        }

        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Pr&eacute;paration commande - Exp&eacute;dition - Derni&egrave;res exp&eacute;ditions',
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
        'title' => 'Pr&eacute;paration commande - Exp&eacute;dition - Derni&egrave;res exp&eacute;ditions',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listExpeditionPrepaCommande_settings() {
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
