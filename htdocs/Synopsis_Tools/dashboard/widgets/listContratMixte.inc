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
function widget_listContratMixte() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("contracts");


      $table =  '<p>';
      $table2 = '</p>';



    if ($conf->contrat->enabled && $user->rights->contrat->lire)
    {
        $staticcontrat= new Contrat($db);

        $sql  = "SELECT DISTINCT c.ref as ref, c.rowid, c.extraparams as type, total_ttc, ";
        $sql.= " s.nom, s.rowid as socid,  cd.date_fin_validite";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,  ".MAIN_DB_PREFIX."contrat as c LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat ";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql .= " WHERE s.rowid = c.fk_soc AND c.statut < 5 AND c.extraparams = 7 AND cd.date_fin_validite is not null ";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

        if ($socid)
        {
            $sql .= " AND f.fk_soc = ".$socid;
        }
        $sql .= " ORDER BY cd.date_fin_validite ASC";
        $sql .= " LIMIT 50";
        $resql = $db->query($sql);
        if ( $resql )
        {
            $var = false;
            $num = $db->num_rows($resql);

            $table .=  '<table class="noborder" width="100%">';
            $table .=  '<tr class="liste_titre">';
            $table .=  '<td colspan="3">'.$langs->trans("DraftContracts")." (".min(array($num, 10)).')</td>';
    //        $table .=  "<td>Type</td>";
            $table .=  '</tr>';
            
            $table2 .=  '<table class="noborder" width="100%">';
            $table2 .=  '<tr class="liste_titre">';
            $table2 .=  '<td colspan="3">'.$langs->trans("DraftContracts")." (".$num.')</td>';
    //        $table .=  "<td>Type</td>";
            $table2 .=  '</tr>';
            if ($num)
            {
                $companystatic=new Societe($db);

                $i = 0;
                $tot_ttc = 0;
                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);
                    $temp =  '<tr '.$bc[$var].'><td nowrap>';
                    $staticcontrat->ref=$obj->ref;
                    $staticcontrat->id=$obj->rowid;
                    $temp .=  $staticcontrat->getNomUrl(1,'');
                    $temp .=  '</td>';
                    $temp .=  '<td>';
                    $companystatic->id=$obj->socid;
                    $companystatic->fetch($obj->socid);
                    $companystatic->nom=$obj->nom;
                    $companystatic->client=1;
                    $temp .=  $companystatic->getNomUrl(1,'',16);
                    $temp .=  '</td>';
                    $temp .=  "<td align=right>";
                    if (strtotime($obj->date_fin_validite) - 20 * 3600 * 24 < time())
                    {
                        $temp .= img_warning('Alerte');
                    }
                    $temp .=  ''.date('d/m/Y',strtotime($obj->date_fin_validite));
                    $temp .=  '</td>';

                    $temp .=  '</tr>';
                    $tot_ttc+=$obj->total_ttc;
                    
                    if($i < 10)
                        $table .= $temp;
                    $table2 .= $temp;
                    
                    $i++;
                    $var=!$var;
                }
            }
            else
            {
                $table .=  '<tr colspan="3" '.$bc[$var].'><td>'.$langs->trans("NoContracts").'</td></tr>';
            }
            $table .=  "</table><br>";
            $db->free($resql);
        }


        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Contrat - 10 prochains contrats mixtes',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => $table2,
        'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/initFullscreen.js',
      );

    }else {
      return array(
        'title' => 'Contrat - 10 prochains contrats mixtes',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listContratMixte_settings() {
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
