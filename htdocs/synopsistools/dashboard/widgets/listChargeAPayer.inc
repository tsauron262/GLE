<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php');



// The get widget handler.
function widget_listChargeAPayer() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
        $langs->load("bills");

  $table = '<p>';
  $table2 = '<p>';
    if ($conf->tax->enabled && $user->societe_id == 0)
    {
        $sql = "SELECT c.rowid, c.amount, c.date_ech as date_ech,";
            $sql.= " cc.libelle";
            $sql.= " FROM ".MAIN_DB_PREFIX."chargesociales as c, ".MAIN_DB_PREFIX."c_chargesociales as cc";
            $sql.= " WHERE c.fk_type = cc.id AND c.paye=0";

        $sql .= " ORDER BY c.date_ech DESC LIMIT 50";
        $resql=$db->query($sql);
        $chargestatic=new ChargeSociales($db);

        if ($resql)
        {
            $total = 0;
            $total2 = 0;
            $num = $db->num_rows($resql);
            if ($num > 0)
            {

                $cssClass[true]='pair';
                $cssClass[false]='impair';
                $bool = false;
                $iter = 0;
                $table .= '<table class="noborder" width="100%">';
                $table .= "<tr class=\"liste_titre\">";
                $table .= "<td colspan=\"2\">".$langs->trans("ContributionsToPay")."</td></tr>";

                $table2 .= '<table class="noborder" width="100%">';
                $table2 .= "<tr class=\"liste_titre\">";
                $table2 .= "<td colspan=\"2\">".$langs->trans("ContributionsToPay")."</td></tr>";

                $bool = false;
                $arrCss[true]='pair';
                $arrCss[false]='impair';
                $facturestatic=new Facture($db);
                while ($res =$db->fetch_object($resql))
                {
                    $bool = !$bool;
                    if ($iter < 10)
                    {
                        $table .= '<tr class='.$arrCss[$bool].'>
                                <td  nowrap="nowrap">';
                        $chargestatic->id=$res->rowid;
                        $chargestatic->lib=$res->libelle;
                        $table .=  '<td>'.$chargestatic->getNomUrl(1).'</td>';
                        $table .=  '<td align="right" nowrap="nowrap">'.price($res->amount).'</td></tr>';
                        $total += $res->amount;
                    }
                    $table2 .= '<tr class='.$arrCss[$bool].'>
                            <td  nowrap="nowrap">';
                    $facturestatic->ref=$res->ref;
                    $facturestatic->id=$res->rowid;
                    $facturestatic->type=$res->type;
                    $table2 .= $facturestatic->getNomUrl(1,'');
                    $table2 .=  '<td nowrap="nowrap"><a href="card.php?socid='.$res->socid.'">'.dol_trunc($res->nom,18).'</a></td>';
                    $table2 .=  '<td align="right" nowrap="nowrap">'.price($res->total_ttc).'</td></tr>';
                    $total2 += $res->total_ttc;
                    $iter++;
                }
                if ($total>0)
                {
                    $table .=  '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" align="right">'.price($total)."</td></tr>";
                }
                $table .=  "</table><br>";
                if ($total2>0)
                {
                    $table2 .=  '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" align="right">'.price($total)."</td></tr>";
                }
                $table2 .=  "</table><br>";
            }
        }
        $table .="</p>";
        $table2 .="</p>";

      return array(
        'title' => 'Charges sociales - A payer',
        'content' => $table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
        'fullscreen' => $table2,
        'fullscreenScript' => DOL_URL_ROOT.'/synopsistools/dashboard/widgets/scripts/fullscreen.js',
        'fullscreenInitScript' => DOL_URL_ROOT.'/synopsistools/dashboard/widgets/scripts/initFullscreen.js',
      );
    } else {
      return array(
        'title' => 'Charges sociales - A payer',
        'content' => utf8_encodeRien("Ce module ne vous est pas accessible"),
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );
    }
}

// The widget settings handler.
function widget_listChargeAPayer_settings() {
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
