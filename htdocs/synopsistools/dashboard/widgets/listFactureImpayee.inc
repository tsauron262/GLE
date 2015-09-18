<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

// The get widget handler.
function widget_listFactureImpayee() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $formfile = new FormFile($db);
    $langs->load("compta");
    $langs->load("bills");



      $table =  '<p>';
      $table2 = '</p>';

    if ($conf->facture->enabled && $user->rights->facture->lire)
    {
        $facturestatic=new Facture($db);

        $sql = "SELECT f.rowid, f.facnumber, f.fk_statut, f.type, f.total, f.total_ttc, ";
        $sql.= "f.date_lim_reglement as datelimite,";
        $sql.= " sum(pf.amount) as am,";
        $sql.= " s.nom, s.rowid as socid";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE s.rowid = f.fk_soc AND f.paye = 0 AND f.fk_statut = 1";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        if ($socid) $sql .= " AND f.fk_soc = ".$socid;
        $sql.= " GROUP BY f.rowid, f.facnumber, f.fk_statut, f.total, f.total_ttc, s.nom, s.rowid";
        $sql.= " ORDER BY f.datef ASC, f.facnumber ASC";
        $sql .= " LIMIT 50";

//$table .= $sql;

        $resql=$db->query($sql);
        if ($resql)
        {
            $total = 0;
            $total2 = 0;
            $ftotal = 0;
            $ftotal2 = 0;
            $num = $db->num_rows($resql);
            if ($num > 0)
            {

                $cssClass[true]='pair';
                $cssClass[false]='impair';
                $bool = false;
                $iter = 0;
                $table .= '<table class="noborder" width="100%">';
                $table .= "<tr class=\"liste_titre\">";
                $table .= "<td colspan=\"2\">".$langs->trans("BillsCustomersUnpayed")."</td>";
                $table .= "<td colspan=\"1\" align=right>".$langs->trans("AmountTTC")."</td>";
                $table .= "<td colspan=\"1\" align=right>".$langs->trans("Received")."</td>";
                $table .= "<td colspan=\"1\">&nbsp;</td></tr>";

                $table2 .= '<table class="noborder" width="100%">';
                $table2 .= "<tr class=\"liste_titre\">";
                $table2 .= "<td colspan=\"2\">".$langs->trans("BillsCustomersUnpayed")."</td>";
                $table2 .= "<td colspan=\"1\" align=right>".$langs->trans("AmountTTC")."</td>";
                $table2 .= "<td colspan=\"1\" align=right>".$langs->trans("Received")."</td>";
                $table2 .= "<td colspan=\"1\">&nbsp;</td></tr>";

                $bool = false;
                $arrCss[true]='pair';
                $arrCss[false]='impair';
                while ($res =$db->fetch_object($resql))
                {

                    $facturestatic->ref=$res->facnumber;
                    $facturestatic->id=$res->rowid;
                    $facturestatic->type=$res->type;

                    $bool = !$bool;
                    if ($iter < 10)
                    {
                            $var=!$var;
                            $table .= '<tr class='.$arrCss[$bool].'>
                                    <td  nowrap="nowrap">';
                            $table .= $facturestatic->getNomUrl(1,'');;
                            $filename=sanitize_string($res->facnumber);
                            $filedir=$conf->facture->dir_output . '/' . sanitize_string($res->facnumber);
                            $urlsource=$_SERVER['PHP_SELF'].'?facid='.$res->rowid;
                            if ($res->datelimite < (time() - $conf->facture->client->warning_delay)) $table .= img_warning($langs->trans("Late"));
                            $table .= $formfile->getDocumentsLink('facture',$filename,$filedir);

                            $table .= $formfile->html;

                            $table .=  '<td nowrap="nowrap"><a href="card.php?socid='.$res->socid.'">'.img_object($langs->trans("ShowCustomer"),"company").dol_trunc($res->nom,44).'</a></td>';
                            $table .=  '<td align="right" nowrap="nowrap">'.price($res->total_ttc).'</td>';
                            $table .=  '<td align="right" nowrap="nowrap">'.price($res->am).'</td>';
                            $table .=  '<td>'.$facturestatic->LibStatut($res->paye,$res->fk_statut,3,$res->am).'</td>';
                            $total += $res->total_ttc;
                            $ftotal += $res->am;
                    }
                    $var=!$var;
                    $table2 .= '<tr class='.$arrCss[$bool].'>
                            <td  nowrap="nowrap">';
                    $table2 .= $facturestatic->getNomUrl(1,'');;
                    $filename=sanitize_string($res->facnumber);
                    $filedir=$conf->facture->dir_output . '/' . sanitize_string($res->facnumber);
                    $urlsource=$_SERVER['PHP_SELF'].'?facid='.$res->rowid;
                    if ($res->datelimite < (time() - $conf->facture->client->warning_delay)) $table2 .= img_warning($langs->trans("Late"));
                    $table2 .= $formfile->getDocumentsLink('facture',$filename,$filedir);
                    $table2 .= $formfile->html;

                    $table2 .=  '<td nowrap="nowrap"><a href="card.php?socid='.$res->socid.'">'.img_object($langs->trans("ShowCustomer"),"company").dol_trunc($res->nom,44).'</a></td>';
                    $table2 .=  '<td align="right" nowrap="nowrap">'.price($res->total_ttc).'</td>';
                    $table2 .=  '<td align="right" nowrap="nowrap">'.price($res->am).'</td>';
                    $table2 .=  '<td>'.$facturestatic->LibStatut($res->paye,$res->fk_statut,3,$res->am).'</td>';
                    $total2 += $res->total_ttc;
                    $ftotal2 += $res->am;
                    $iter++;
                }
                if ($total>0)
                {
                    $table .=  '<tr class="liste_total"><td colspan=2>'.$langs->trans("Total").'</td>
                                    <td colspan="1" align="right">'.price($total).'</td>
                                    <td colspan="1" align="right">'.price($ftotal).'</td></tr>';
                }
                $table .=  "</table><br>";
                if ($total2>0)
                {
                    $table2 .= '<tr class="liste_total"><td colspan=1>'.$langs->trans("Total").'</td><td colspan="1" align="right">'.price($total).'</td><td colspan="1" align="right">'.price($ftotal2).'</td></tr>';
                }
                $table2 .=  "</table><br>";
            }
        }
        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Facture client - 10 derni&egrave;res factures impay&eacute;es',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => $table2,
        'fullscreenScript' => DOL_URL_ROOT.'/synopsistools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/synopsistools/dashboard/widgets/scripts/initFullscreen.js',
      );

    }else {
      return array(
        'title' => 'Facture client - 10 d&egrave;rniers factures impay&eacute;es',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listFactureImpayee_settings() {
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
