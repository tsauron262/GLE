<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/remisecheque.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/bank/account.class.php');

// The get widget handler.
function widget_listBordereauRemiseCheque() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;
    if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("banks");
    $langs->load("bills");
    $langs->load("compta");

    $checkdepositstatic=new RemiseCheque($db);
    $accountstatic=new Account($db);



      $table =  '<p>';
      $table2 = '</p>';

    if ($user->rights->banque->cheque)
    {

        $sql = "SELECT bc.rowid,".$db->pdate("bc.date_bordereau")." as db, bc.amount, bc.number,";
        $sql.= " bc.statut, bc.nbcheque,";
        $sql.= " ba.label, ba.rowid as bid";
        $sql.= " FROM ".MAIN_DB_PREFIX."bordereau_cheque as bc,";
        $sql.= " ".MAIN_DB_PREFIX."bank_account as ba";
        $sql.= " WHERE ba.rowid=bc.fk_bank_account";
        $sql.= " ORDER BY bc.rowid";
        $sql.= " DESC LIMIT 50";

        $resql = $db->query($sql);
        $i=0;
        if ($resql)
        {
            $table .= '<table class="noborder" width="100%">';
            $table .= '<tr class="liste_titre">';
            $table .= '<td title="'.$langs->trans("CheckReceiptShort").'">'.dolibarr_trunc($langs->trans("CheckReceiptShort"),5).'</td>';
            $table .= '<td>'.$langs->trans("Date")."</td>";
            $table .= '<td>'.$langs->trans("Account").'</td>';
            $table .= '<td align="right" title="'.$langs->trans("NbOfCheques").'">'.dolibarr_trunc($langs->trans("NbOfCheques"),6).'</td>';
            $table .= '<td align="right">'.$langs->trans("Amount").'</td>';
            $table .= '<td align="right">'.$langs->trans("Status").'</td>';
            $table .= "</tr>\n";

            $table2 .= '<table class="noborder" width="100%">';
            $table2 .= '<tr class="liste_titre">';
            $table2 .= '<td>'.$langs->trans("CheckReceiptShort").'</td>';
            $table2 .= '<td>'.$langs->trans("Date")."</td>";
            $table2 .= '<td>'.$langs->trans("Account").'</td>';
            $table2 .= '<td align="right">'.$langs->trans("NbOfCheques").'</td>';
            $table2 .= '<td align="right">'.$langs->trans("Amount").'</td>';
            $table2 .= '<td align="right">'.$langs->trans("Status").'</td>';
            $table2 .= "</tr>\n";

            $var=true;
            while ( $objp = $db->fetch_object($resql) )
            {
                $checkdepositstatic->statut=$objp->statut;
                $checkdepositstatic->rowid=$objp->rowid;
                $checkdepositstatic->number=$objp->number;

                $accountstatic->id=$objp->bid;
                $accountstatic->label=$objp->label;

                $var=!$var;
                if ($i<10)
                {
                    $table .= "<tr $bc[$var]>\n";

                    $table .= '<td>'.$checkdepositstatic->getNomUrl(1).'</td>';
                    $table .= '<td>'.dolibarr_print_date($objp->db,'day').'</td>';
                    $table .= '<td>'.$accountstatic->getNomUrl(1).'</td>';
                    $table .= '<td align="right">'.$objp->nbcheque.'</td>';
                    $table .= '<td align="right">'.price($objp->amount).'</td>';
                    $table .= '<td align="right">'.$checkdepositstatic->LibStatut($objp->statut,3).'</td>';

                    $table .= '</tr>';
                }
                $table2 .= "<tr $bc[$var]>\n";

                $table2 .= '<td>'.$checkdepositstatic->getNomUrl(1).'</td>';
                $table2 .= '<td>'.dolibarr_print_date($objp->db,'day').'</td>';
                $table2 .= '<td>'.$accountstatic->getNomUrl(1).'</td>';
                $table2 .= '<td align="right">'.$objp->nbcheque.'</td>';
                $table2 .= '<td align="right">'.price($objp->amount).'</td>';
                $table2 .= '<td align="right">'.$checkdepositstatic->LibStatut($objp->statut,3).'</td>';

                $table2 .= '</tr>';
                $i++;
            }
            $table .= "</table>";
            $table2 .= "</table>";
        }
            $db->free($resql);


        $table .="</p>";
        $table2 .="</p>";
      return array(
        'title' => 'Remise de ch&egrave;que - 10 derni&egrave;res remises',
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
        'title' => 'Remise de ch&egrave;que - 10 derni&egrave;res remises',
        'content' => 'Vous ne possedez pas les droits requis pour ce module'.$table,
        'initScript' => "",
        'classes' => 'ui-state-default ui-widget-header',
        'settings' => false,
      );

    }
}

// The widget settings handler.
function widget_listBordereauRemiseCheque_settings() {
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
