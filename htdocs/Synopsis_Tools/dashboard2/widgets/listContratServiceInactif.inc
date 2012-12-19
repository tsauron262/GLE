<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

// The get widget handler.
function widget_listContratServiceInactif() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;
    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("contracts");


      $table =  '<p>';
      $table2 = '</p>';



    if ($conf->contrat->enabled && $user->rights->contrat->lire)
    {
        $staticcontrat= new Contrat($db);
        $staticcompany = new Societe($db);
        $staticcontratligne=new ContratLigne($db);


        $sql = "SELECT cd.rowid as cid, c.ref, cd.statut, cd.label, cd.description as note, cd.fk_contrat, c.fk_soc, s.nom";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
        $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE c.statut=1 AND cd.statut = 0";
        $sql.= " AND is_financement = 0 AND cd.fk_contrat = c.rowid AND c.fk_soc = s.rowid";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        if ($socid > 0) $sql.= " AND s.rowid = ".$socid;
        $sql.= " ORDER BY cd.tms DESC";

        if ( $db->query($sql) )
        {
            $num = $db->num_rows();
            $i = 0;

            $table .= '<table class="noborder" width="100%">';

            $table .= '<tr class="liste_titre"><td colspan="4">'.$langs->trans("NotActivatedServices").'</td>';
            $table .= "</tr>\n";

            $table2 .= '<table class="noborder" width="100%">';

            $table2 .= '<tr class="liste_titre"><td colspan="4">'.$langs->trans("NotActivatedServices").'</td>';
            $table2 .= "</tr>\n";

            $var=True;
            while ($i < $num)
            {
                $obj = $db->fetch_object();
                $var=!$var;

                if ($i<10)
                {
                    $table .= "<tr $bc[$var]>";

                    $table .= '<td>';
                    $staticcontrat->ref=($obj->ref?$obj->ref:$obj->fk_contrat);
                    $staticcontrat->id=$obj->fk_contrat;
                    $table .= $staticcontrat->getNomUrl(1,16);
                    $table .= '</td>';
                    $table .= '<td nowrap="1">';
                    $table .= '<a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"),"service");
                    if ($obj->note) $table .= ' '.dolibarr_trunc(html_entity_decode($obj->note),20).'</a></td>';
                    else $table .= '</a> '.dolibarr_trunc($obj->label,20).'</td>';
                    $table .= '<td>';
                    $staticcompany->id=$obj->fk_soc;
                    $staticcompany->fetch($obj->fk_soc);
                    $staticcompany->nom=$obj->nom;
                    $table .= $staticcompany->getNomUrl(1,'',20);
                    $table .= '</td>';
                    $table .= '<td width="16" align="right"><a href="ligne.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'">';
                    $table .= $staticcontratligne->LibStatut($obj->statut,3);
                    $table .= '</a></td>';
                    $table .= "</tr>\n";
                }
                $table2 .= "<tr $bc[$var]>";

                $table2 .= '<td>';
                $staticcontrat->ref=($obj->ref?$obj->ref:$obj->fk_contrat);
                $staticcontrat->id=$obj->fk_contrat;
                $table2 .= $staticcontrat->getNomUrl(1,16);
                $table2 .= '</td>';
                $table2 .= '<td nowrap="1">';
                $table2 .= '<a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"),"service");
                if ($obj->note) $table2 .= ' '.dolibarr_trunc(html_entity_decode($obj->note),20).'</a></td>';
                else $table2 .= '</a> '.dolibarr_trunc($obj->label,20).'</td>';
                $table2 .= '<td>';
                $staticcompany->id=$obj->fk_soc;
                $staticcompany->fetch($obj->fk_soc);
                $staticcompany->nom=$obj->nom;
                $table2 .= $staticcompany->getNomUrl(1,'',20);
                $table2 .= '</td>';
                $table2 .= '<td width="16" align="right"><a href="ligne.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'">';
                $table2 .= $staticcontratligne->LibStatut($obj->statut,3);
                $table2 .= '</a></td>';
                $table2 .= "</tr>\n";

                $i++;
            }
            $db->free();

            $table .= "</table>";

        }


        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Contrat - 10 derniers services inactifs (parmi les contrats valid&eacute;s)',
        'content' => utf8_encode($table),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => utf8_encode($table2),
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
      );

    }else {
      return array(
        'title' => 'Contrat - 10 derniers services inactifs (parmi les contrats valid&eacute;s)',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listContratServiceInactif_settings() {
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
