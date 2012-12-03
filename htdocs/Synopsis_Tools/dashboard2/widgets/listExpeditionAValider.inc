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
function widget_listExpeditionAValider() {
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
        /*$sql = "SELECT e.rowid, e.ref";
        $sql.= ", s.nom, s.rowid as socid";
        $sql.= ", c.ref as commande_ref, c.rowid as commande_id";
        $sql.= " FROM llx_expedition as e";
        $sql.= " LEFT JOIN llx_co_exp as ce ON e.rowid = ce.fk_expedition";
        $sql.= " LEFT JOIN llx_commande as c ON ce.fk_commande = c.rowid";
        $sql.= " LEFT JOIN llx_societe as s ON s.rowid = e.fk_soc";*/
        $sql = "SELECT *";
        $sql.= " FROM llx_commande , BIMP_site_commande";
        $sql.= $clause." llx_commande.rowid = BIMP_site_commande.fk_commande";
        $sql.= " AND fk_statut = 1";
         //$sql .= " AND fk_soc = ".$socid;
        $sql.= " ORDER BY date_valid desc";
        $sql.= " LIMIT 50";
        /*$sql.= "MINUS";
        $sql = "SELECT *";
        $sql.= " FROM llx_co_exp";*/
        /*if (!$user->rights->societe->client->voir && !$socid)
        {
            $sql.= " LEFT JOIN llx_societe_commerciaux as sc ON e.fk_soc = sc.fk_soc";
            $sql.= $clause." sc.fk_user = " .$user->id;
            $clause = " AND ";
        }
        if ($socid)
        {
            $sql .= " AND fk_soc = ".$socid;
        }*/

        $result=$db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;

            $table .=  '<table class="noborder" width="100%">';

            //$table .=  '<tr class="liste_titre"><td colspan="1">'.$langs->trans("SendingsToValidate",30).'</td>';
            //$table .=  '<tr class="liste_titre"><td align="right" width="80" colspan="">'.$langs->trans("Soci&eacute;t&eacute;").'</td>';
            $table .=  '<tr class="liste_titre"><td align="left" width="20" colspan="1">'.$langs->trans("Comm.").'</td>';
            $table .=  '<td align="left" width="20" colspan="">'.$langs->trans("date livrais.").'</td>';
            $table .=  '<td align="left" width="40" colspan="">'.$langs->trans("Logist.").'</td>';
            $table .=  '<td align="left" width="20" colspan="">'.$langs->trans("Financ.").'</td>';
            $table .=  "</tr>\n";

            $table2 .=  '<table class="noborder" width="100%">';

            //$table2 .=  '<tr class="liste_titre"><td colspan="1">'.$langs->trans("SendingsToValidate",30).'</td>';
            //$table2 .=  '<tr class="liste_titre"><td align="right" width="80" colspan="">'.$langs->trans("Soci&eacute;t&eacute;").'</td>';
            $table2 .=  '<tr class="liste_titre"><td align="left" width="20" colspan="1">'.$langs->trans("Comm.").'</td>';
            $table2 .=  '<td align="left" width="20" colspan="">'.$langs->trans("date livrais.").'</td>';
            $table2 .=  '<td align="left" width="40" colspan="">'.$langs->trans("Logist.").'</td>';
            $table2 .=  '<td align="left" width="20" colspan="">'.$langs->trans("Financ.").'</td>';
            $table2 .=  "</tr>\n";


            $var=true;
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $var=!$var;
                
                if ($obj->logistique_ok == 1)
                {
                	$log_ok="OK";
                }
                if ($obj->logistique_ok == 0)
                {
                	$log_ok="KO";
                }
                if ($obj->logistique_ok == 2)
                {
                	$log_ok="Partiel";
                }
                if ($obj->logistique_statut == 1 )
                {
                	$log_stat="Valide";
                }
                if ($obj->logistique_statut == 0 )
                {
                	$log_stat="Temporaire";
                }
                if ($obj->finance_statut == 1 )
                {
                	$fin_stat="Valide";
                }
                else
                {
                	$fin_stat="Temporaire";
                }
                if ($obj->finance_ok == 1 )
                {
                	$fin_ok="OK";
                }
                else
                {
                	$fin_ok="KO";
                }


                if ($i < 10)
                {
                    //$table .= "<tr $bc[$var]><td nowrap=\"nowrap\"><a href=\"fiche.php?id=".$obj->rowid."\">".$obj->ref."</a></td>";
                    //$table .= '<tr $bc[$var]><td nowrap=\"nowrap\"><a href="'.DOL_URL_ROOT.'/Babel_prepaCommande/prepacommande.php?id='.$obj->commande_id.'">'.$obj->ref.'</a></td>';
                    //$table .= '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.$obj->nom.'</a></td>';
                    //$table .= '<td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$obj->commande_id.'">'.$obj->commande_ref.'</a></td></tr>';
                    $table .= '<tr $bc[$var]><td><a href="'.DOL_URL_ROOT.'/Babel_prepaCommande/prepacommande.php?id='.$obj->rowid.'">'.$obj->ref.'</a></td>';
                    $table .= '<td>'.$obj->date_livraison.'</td>';
                    $table .= '<td>'.$log_ok.' , '.$log_stat.'</td>';
                    $table .= '<td>'.$fin_ok.' , '.$fin_stat.'</td></tr>';
                }
                //$table2 .= "<tr $bc[$var]><td nowrap=\"nowrap\"><a href=\"fiche.php?id=".$obj->rowid."\">".$obj->ref."</a></td>";
                //$table .= '<tr $bc[$var]><td nowrap=\"nowrap\"><a href="'.DOL_URL_ROOT.'/Babel_prepaCommande/prepacommande.php?id='.$obj->commande_id.'">'.$obj->ref.'</a></td>';
                //$table2 .= '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.$obj->nom.'</a></td>';
                //$table2 .= '<td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$obj->commande_id.'">'.$obj->commande_ref.'</a></td></tr>';
                $table2 .= '<tr $bc[$var]><td><a href="'.DOL_URL_ROOT.'/Babel_prepaCommande/prepacommande.php?id='.$obj->rowid.'">'.$obj->ref.'</a></td>';
                $table2 .= '<td>'.$obj->date_livraison.'</td>';
                $table2 .= '<td>'.$log_ok.', '.$log_stat.'</td>';
                $table2 .= '<td>'.$fin_ok.' , '.$fin_stat.'</td></tr>';

                $i++;
            }
            $db->free($result);

            $table .=  "</table>";
            $table2 .=  "</table>";
        }

        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Exp&eacute;dition - 10 derni&egrave;res exp&eacute;ditions &agrave; valider',
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
        'title' => 'Exp&eacute;dition - 10 derni&egrave;res exp&eacute;ditions &agrave; valider',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listExpeditionAValider_settings() {
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
