<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/commande/commande.class.php');
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");

// The get widget handler.
function widget_listPrepaCommande() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user;
    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("orders");
    $formfile = new FormFile($db);
    $langs->load("compta");
    $langs->load("bills");

    $commandestatic=new Commande($db);


      $table =  '<p>';
      $table2 = '</p>';

    if ($conf->commande->enabled && $user->rights->commande->lire)
    {
        $sql = "SELECT sum(f.total) as tot_fht, sum(f.total_ttc) as tot_fttc,";
        $sql.= " s.nom, s.rowid as socid,";
        $sql.= " p.rowid, p.ref, p.facture, p.fk_statut, p.total_ht, p.total_ttc, b.statut_refid, bs.label as Blabel";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
        $sql.= " FROM llx_societe AS s";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= ", llx_societe_commerciaux as sc";
        $sql.= ", llx_commande AS p";
        $sql.= " LEFT JOIN llx_co_fa AS co_fa ON co_fa.fk_commande = p.rowid";
        $sql.= " LEFT JOIN llx_facture AS f ON co_fa.fk_facture = f.rowid";
        $sql.= " LEFT JOIN BIMP_commande_status as b ON b.commande_refid = p.rowid";
        $sql.= " LEFT JOIN BIMP_status as bs ON b.statut_refid = bs.id";

        $sql.= " WHERE p.fk_soc = s.rowid";
        if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        if ($socid)
        {
            $sql.= " AND p.fk_soc = ".$socid;
        }
        $sql.= " AND (b.statut_refid = 1 OR b.statut_refid is null)";
        $sql.= " GROUP BY p.rowid";
        $sql .= " ORDER BY p.date_creation DESC LIMIT 50";

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
                $table .= "<td colspan=\"2\">".$langs->trans("Orders")."</td>";
                $table .= "<td colspan=\"1\">&nbsp;</td></tr>";

                $table2 .= '<table class="noborder" width="100%">';
                $table2 .= "<tr class=\"liste_titre\">";
                $table2 .= "<td colspan=\"2\">".$langs->trans("Orders")."</td>";
                $table2 .= "<td colspan=\"1\">&nbsp;</td></tr>";

                $bool = false;
                $arrCss[true]='pair';
                $arrCss[false]='impair';
                while ($res =$db->fetch_object($resql))
                {

                    $commandestatic->id=$res->rowid;
                    $commandestatic->ref=$res->ref;

                    $bool = !$bool;
                    if ($iter < 10)
                    {
                            $var=!$var;
                            $table .= '<tr class='.$arrCss[$bool].'>
                                    <td  nowrap="nowrap">';
                            $table .= $commandestatic->getNomUrl(1,5);
                            $table .= $formfile->html;

                            $table .=  '<td nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$res->socid.'">'.img_object('','societe')." ".dolibarr_trunc($res->nom,18).'</a></td>';
                            $table .=  '<td>'.$res->Blabel.'</td>';
                    }
                    $var=!$var;
                    $table2 .= '<tr class='.$arrCss[$bool].'>
                            <td  nowrap="nowrap">';
                    $table2 .= $commandestatic->getNomUrl(1,5);

                    $table2 .=  '<td nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$res->socid.'">'.img_object('','societe')." ".dolibarr_trunc($res->nom,18).'</a></td>';
                    $table2 .=  '<td>'.$res->Blabel.'</td>';
                    $iter++;
                }
                $table2 .=  "</table><br>";
            }
        }
        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Pr&eacute;paration commande - 10 derniers com. non examin&eacute;',
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
        'title' => 'Pr&eacute;paration commande - 10 derniers com. non examin&eacute;',
        'content' => 'Vous ne possedez pas les droits requis pour ce module',
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listPrepaCommande_settings() {
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
