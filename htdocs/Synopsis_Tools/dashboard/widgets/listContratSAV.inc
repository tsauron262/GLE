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
function widget_listContratSAV() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("contracts");

    $staticcontrat=new Contrat($db);
    $staticcompany = new Societe($db);
    $staticcontratligne=new ContratLigne($db);

      $table =  '<p>';
      $table2 = '</p>';



    if ($conf->contrat->enabled && $user->rights->contrat->lire)
    {

        $sql = 'SELECT ';
        $sql.= ' sum('.$db->ifsql("cd.statut=0",1,0).') as nb_initial,';
        $sql.= ' sum('.$db->ifsql("cd.statut=4 AND cd.date_fin_validite > sysdate()",1,0).') as nb_running,';
        $sql.= ' sum('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite <= sysdate())",1,0).') as nb_late,';
        $sql.= ' sum('.$db->ifsql("cd.statut=5",1,0).') as nb_closed,';
        $sql.= " c.rowid as cid, c.ref, c.datec, c.tms, c.statut, s.nom, s.rowid as socid";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s,";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
        $sql.= " ".MAIN_DB_PREFIX."contrat as c";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
        $sql.= " WHERE c.fk_soc = s.rowid AND c.statut > 0  AND c.extraparams=4";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        if ($socid > 0) $sql .= " AND s.rowid = ".$socid;
        $sql.= " GROUP BY c.rowid, c.datec, c.statut, s.nom, s.rowid";
        $sql.= " ORDER BY c.tms DESC";
        $sql.= " LIMIT 50";

        $result=$db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;

            $table .=  '<table class="noborder" width="100%">';

            $table .=  '<tr class="liste_titre"><td colspan="2">'.$langs->trans("LastContracts",10).'</td>';
            $table .=  '<td align="center">'.$langs->trans("Date Mod.").'</td>';
            //$table .=  '<td align="left">'.$langs->trans("Status").'</td>';
            $table .=  '<td align="right" width="80" colspan="3">'.$langs->trans("Nb prod.").'</td>';
            $table .=  "</tr>\n";

            $table2 .=  '<table class="noborder" width="100%">';

            $table2 .=  '<tr class="liste_titre"><td colspan="2">'.$langs->trans("LastContracts",50).'</td>';
            $table2 .=  '<td align="center">'.$langs->trans("Date Mod.").'</td>';
            //$table .=  '<td align="left">'.$langs->trans("Status").'</td>';
            $table2 .=  '<td align="right" width="80" colspan="3">'.$langs->trans("Nb prod.").'</td>';
            $table2 .=  "</tr>\n";


            $var=true;
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $var=!$var;

                if ($i < 10)
                {
                    $table .=  "<tr $bc[$var]>";
                    $table .=  "<td>";
                    $staticcontrat->ref=($obj->ref?$obj->ref:$obj->cid);
                    $staticcontrat->id=$obj->cid;
                    $table .=  $staticcontrat->getNomUrl(1,16);
                    if ($obj->nb_late) $table .=  img_warning($langs->trans("Late"),false);
                    $table .=  '</td>';
                    $table .=  '<td>';
                    $staticcompany->id=$obj->socid;
                    $staticcompany->fetch($obj->socid);
                    $staticcompany->nom=$obj->nom;
                    $table .=  $staticcompany->getNomUrl(1,'',20);
                    $table .=  '</td>';
                    $table .=  '<td align="center">'.dol_print_date($obj->tms,'day').'</td>';
                    //$table .=  '<td align="left">'.$staticcontrat->LibStatut($obj->statut,2).'</td>';
                    $table .=  '<td align="right" width="32">'.($obj->nb_initial>0 ? $obj->nb_initial.$staticcontratligne->LibStatut(0,3):'').'</td>';
                    $table .=  '<td align="right" width="32">'.($obj->nb_running+$obj->nb_late>0 ? ($obj->nb_running+$obj->nb_late).$staticcontratligne->LibStatut(4,3):'').'</td>';
                    $table .=  '<td align="right" width="32">'.($obj->nb_closed>0 ? $obj->nb_closed.$staticcontratligne->LibStatut(5,3):'').'</td>';
                    $table .=  "</tr>\n";
                }

                $table2 .=  "<tr $bc[$var]>";
                $table2 .=  "<td>";
                $staticcontrat->ref=($obj->ref?$obj->ref:$obj->cid);
                $staticcontrat->id=$obj->cid;
                $table2 .=  $staticcontrat->getNomUrl(1,16);
                if ($obj->nb_late) $table2 .=  img_warning($langs->trans("Late"));
                $table2 .=  '</td>';
                $table2 .=  '<td>';
                $staticcompany->id=$obj->socid;
                $staticcompany->fetch($obj->socid);
                $staticcompany->nom=$obj->nom;
//                $table2 .=  $staticcompany->getNomUrl(1,'',20);
                $table2 .=  '</td>';
                $table2 .=  '<td align="center">'.dol_print_date($obj->tms,'day').'</td>';
                //$table .=  '<td align="left">'.$staticcontrat->LibStatut($obj->statut,2).'</td>';
                $table2 .=  '<td align="right" width="32">'.($obj->nb_initial>0 ? $obj->nb_initial.$staticcontratligne->LibStatut(0,3):'').'</td>';
                $table2 .=  '<td align="right" width="32">'.($obj->nb_running+$obj->nb_late>0 ? ($obj->nb_running+$obj->nb_late).$staticcontratligne->LibStatut(4,3):'').'</td>';
                $table2 .=  '<td align="right" width="32">'.($obj->nb_closed>0 ? $obj->nb_closed.$staticcontratligne->LibStatut(5,3):'').'</td>';
                $table2 .=  "</tr>\n";
                $i++;
            }
            $db->free($result);

            $table .=  "</table>";
            $table2 .=  "</table>";
        }

        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'GMAO - 10 derniers contrats SAV modifi&eacute;s',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => $table2,
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
      );

    } else {
      return array(
        'title' => 'GMAO - 10 derniers contrats SAV modifi&eacute;s',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listContratSAV_settings() {
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
