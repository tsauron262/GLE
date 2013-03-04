<?php
/**
 * @file
 *    Defines the hello_world widget.
 *
 * Released under the GNU General Public License.  See LICENSE.txt.
 */
require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");

// The get widget handler.
function widget_statOfcSoldeBanque() {
  // A widget object as per jquery.dashboard.js.
    global $db, $conf,$langs, $user,$bc;
    $socid = 0; if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }
    $langs->load("bills");

    //TODO if droit de voir le solde bancaire
    if ($user->rights->banque->lire)
    {
        $table ="<p>";
        $table2 ="<p>";

        $jsMainpath = DOL_URL_ROOT."/Synopsis_Tools/dashboard";

        $js = ' <script src="'.$jsMainpath.'/swfobject.js" type="text/javascript"></script>';



        $js .= '<script type="text/javascript">';

        $js .= 'swfobject.embedSWF(';
        $js .= '"'.DOL_URL_ROOT.'/Synopsis_Common/open-flash-chart/open-flash-chart.swf", "statOfcSoldeBanque_chart", "100%", "350px",';
        $js .= '"9.0.0", "expressInstall.swf",';
        $js .= '{"data-file":"'.DOL_URL_ROOT.'/Synopsis_Tools/dashboard/ajax/stat_statOfcSoldeBanque_json.php"} );';

        $js .= "var DOL_URL_ROOT='".DOL_URL_ROOT."'";
        $js .= '</script>';


        $table .=  $js;

        $table .=  '    <div id="statOfcSoldeBanque_chart"></div>';
        $table2 .=  '    <table width=100% cellpadding=15><tr><td class="ui-widget-content" valign=top width=400 align=center><div id="statOfcSoldeBanque_chart1"></div>';
        $table2 .= '       <tr><td valign=top class="ui-widget-content"><table width=100%><thead>';
        $table2 .= '       <tr><th style="padding: 15px; font-size: 16px;" colspan=17 class="ui-widget-header ui-state-default">Op&eacute;rations sur 18 mois</thead><tbody><tr><td colspan=2>&nbsp</tbody>';

        $sql = "    SELECT count(*) as nb
                      FROM ".MAIN_DB_PREFIX."bank as b
                 LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = b.rowid AND bu.type='company'
                 LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu.url_id = s.rowid";


        $result=$db->query($sql);
        $viewline=0;
        $page=0;
        if ($result)
        {
            $obj = $db->fetch_object($result);
            $nbline = $obj->nb;
            $total_lines = $nbline;

            if ($nbline > $viewline )
            {
                $limit = $nbline - $viewline ;
            }  else {
                $limit = $viewline;
            }

            $db->free($result);
        }

        if ($page > 0)
        {
            $limitsql = $nbline - ($page * $viewline);
            if ($limitsql < $viewline)
            {
                $limitsql = $viewline;
            }
            $nbline = $limitsql;
        } else {
            $page = 0;
            $limitsql = $nbline;
        }
        $sql = "SELECT b.rowid,
                       b.dateo as do,
                       b.datev as dv,
                       b.amount,
                       b.label,
                       b.rappro,
                       b.num_releve,
                       b.num_chq,
                       b.fk_type,
                       s.rowid as socid,
                       s.nom as thirdparty,
                       fk_account
                  FROM ".MAIN_DB_PREFIX."bank as b
             LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = b.rowid AND bu.type='company'
             LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu.url_id = s.rowid
                 WHERE b.datev > date_sub(now(), interval 18 month)
              ORDER BY b.datev ASC";

        $result = $db->query($sql);
        if ($result)
        {
            $total = 0;
            $time = time();

            $var=true;
            $num = $db->num_rows($result);
            $i = 0; $total = 0; $sep = 0;

            while ($objp = $db->fetch_object($result))
            {
                $total = $total + $objp->amount;
                $var=!$var;

                $sep = 1 ;
                $table2 .=  "<tr $bc[$var]><td align=\"right\" colspan=\"6\">&nbsp;</td>";
                $table2 .=  "<td align=\"right\" nowrap><b>".price($total - $objp->amount)."</b></td>";
                $table2 .=  "<td>&nbsp;</td>";
                $acct = new Account($db);
                if ($objp->fk_account > 0)
                {
                    $acct->id = $objp->fk_account;
                    $acct->fetch($objp->fk_account);
                }
                $societestatic = new Societe($db);
                $societestatic->fetch($objp->socid);

//                $table2 .=  "<tr $bc[$var]>";
                $table2 .=  "<td nowrap>".dol_print_date($objp->do,"day")."</td>\n";
                $table2 .=  "<td nowrap>&nbsp;".dol_print_date($objp->dv,"day")."</td>\n";
                $table2 .=  "<td nowrap>&nbsp;".$objp->fk_type." ".($objp->num_chq?$objp->num_chq:"")."</td>\n";
                $table2 .=  '<td><a href="ligne.php?rowid='.$objp->rowid.'&amp;account='.$acct->id.'">';
                if (preg_match('/^\((.*)\)$/i',$objp->label,$reg))
                {
                    // Label generique car entre parentheses. On l'affiche en le traduisant
                    $table2 .=  $langs->trans($reg[1]);
                } else {
                    $table2 .=  $objp->label;
                }
                $table2 .=  '</a>';
//
//                /*
//                * Ajout les liens autres que tiers
//                */
                $links = $acct->get_url($objp->rowid);


                //$table2 .= print_r($links,true);

                foreach($links as $key=>$val)
                {
                    if ($links[$key]['type']=='payment') {
                        $table2 .=  ' - ';
                        $table2 .=  '<a href="'.DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$links[$key]['url_id'].'">';
                        if (preg_match('/^\((.*)\)$/i',$links[$key]['label'],$reg))
                        {
                            // Label generique car entre parentheses. On l'affiche en le traduisant
                            if ($reg[1]=='paiement') $reg[1]='Payment';
                            $table2 .=  $langs->trans($reg[1]);
                        } else {
                            $table2 .=  $links[$key]['label'];
                        }
                        $table2 .=  '</a>';
                    } else if ($links[$key]['type']=='payment_supplier') {
                        $table2 .=  ' - ';
                        $table2 .=  '<a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$links[$key]['url_id'].'">';
                        if (preg_match('/^\((.*)\)$/i',$links[$key]['label'],$reg))
                        {
                            // Label generique car entre parentheses. On l'affiche en le traduisant
                            if ($reg[1]=='paiement') $reg[1]='Payment';
                            $table2 .=  $langs->trans($reg[1]);
                        } else {
                            $table2 .=  $links[$key]['label'];
                        }
                        $table2 .=  '</a>';
                    } else if ($links[$key]['type']=='company') {
                    } else if ($links[$key]['type']=='sc') {
                    } else if ($links[$key]['type']=='payment_sc') {
                        $table2 .=  ' - ';
                        $table2 .=  '<a href="'.DOL_URL_ROOT.'/compta/sociales/xxx.php?id='.$links[$key]['url_id'].'">';
                        //$table2 .=  img_object($langs->trans('ShowPayment'),'payment').' ';
                        $table2 .=  $langs->trans("SocialContributionPayment");
                        $table2 .=  '</a>';
                    } else if ($links[$key]['type']=='banktransfert') {
                        /* Do not show this link (avoid confusion). Can be accessed from transaction detail.
                        $table2 .=  ' - ';
                        $table2 .=  '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$links[$key]['url_id'].'">';
                        //$table2 .=  img_object($langs->trans('ShowPayment'),'payment').' ';
                        $table2 .=  $langs->trans("TransactionWithOtherAccount");
                        $table2 .=  '</a>';
                        */
                    } else if ($links[$key]['type']=='member') {
                    } else {
                        $table2 .=  ' - ';
                        $table2 .=  '<a href="'.$links[$key]['url'].$links[$key]['url_id'].'">';
                        if (preg_match('/^\((.*)\)$/i',$links[$key]['label'],$reg))
                        {
                            // Label generique car entre parentheses. On l'affiche en le traduisant
                            if ($reg[1]=='paiement') $reg[1]='Payment';
                            $table2 .=  $langs->trans($reg[1]);
                        } else {
                            $table2 .=  $links[$key]['label'];
                        }
                        $table2 .=  '</a>';
                    }
                }
                $table2 .=  '</td>';


                /*
                * Ajout les liens tiers
                */
                $table2 .=  '<td>';
                foreach($links as $key=>$val)
                {
                    if ($links[$key]['type']=='company')
                    {
                        $societestatic->id=$links[$key]['url_id'];
                        $societestatic->nom=$links[$key]['label'];
                        $table2 .=  $societestatic->getNomUrl(1,'',16);
                    }
                    /*else if ($links[$key]['type']=='sc') {
                        $chargestatic->id=$links[$key]['url_id'];
                        if (preg_match('/^\((.*)\)$/i',$links[$key]['label'],$reg))
                        {
                            if ($reg[1]=='socialcontribution') $reg[1]='SocialContribution';
                            $chargestatic->lib=$langs->trans($reg[1]);
                        }
                        else
                        {
                            $chargestatic->lib=$links[$key]['label'];
                        }
                        $table2 .=  $chargestatic->getNomUrl(1,'',16);
                    }*/
                    else if ($links[$key]['type']=='member')
                    {
                        $memberstatic->id=$links[$key]['url_id'];
                        $memberstatic->ref=$links[$key]['label'];
                        $table2 .=  $memberstatic->getNomUrl(1,16,'card');
                    }
                }
                $table2 .=  '</td>';

                if ($objp->amount < 0)
                {
                    $table2 .=  "<td align=\"right\" nowrap>".price($objp->amount * -1)."</td><td>&nbsp;</td>\n";
                } else {
                    $table2 .=  "<td>&nbsp;</td><td align=\"right\" nowrap>&nbsp;".price($objp->amount)."</td>\n";
                }

                if ($total >= 0)
                {
                    $table2 .=  '<td align="right" nowrap>&nbsp;'.price($total).'</td>';
                } else {
                    $table2 .=  '<td align="right" class="error" nowrap>&nbsp;'.price($total).'</td>';
                }

                // Releve rappro ou lien edition
                if ($objp->rappro && $acct->type != 2)  // Si non compte cash
                {
                    $table2 .=  "<td align=\"center\" nowrap>&nbsp; ";
                    $table2 .=  "<a href=\"releve.php?num=$objp->num_releve&amp;account=$acct->id\">$objp->num_releve</a>";
                    $table2 .=  "</td>";
                } else {
                    $table2 .=  '<td align="center">';
                    if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
                    {
                        $table2 .=  '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$objp->rowid.'&amp;account='.$acct->id.'&amp;page='.$page.'">';
                        $table2 .=  img_edit();
                        $table2 .=  '</a>';
                    }
                    $table2 .=  '&nbsp;';
                    if ($user->rights->banque->modifier)
                    {
                        $table2 .=  '<a href="'.DOL_URL_ROOT.'/compta/bank/account.php?action=delete&amp;rowid='.$objp->rowid.'&amp;account='.$acct->id.'&amp;page='.$page.'">';
                        $table2 .=  img_delete();
                        $table2 .=  '</a>';
                    }
                    $table2 .=  '</td>';
                }
                $i++;
            }
            // Affichage total
            $table2 .=  '<tr class="liste_total"><td align="left" colspan="7">'.$langs->trans("CurrentBalance").'</td>';
            $table2 .=  '<td align="right" nowrap>'.price($total).'</td>';
            $table2 .=  '<td>&nbsp;</td>';
            $table2 .=  '</tr>';
        }

        $db->free($result);
        $table2 .= "</table>";

        $table .="</p>";
        $table2 .="</p>";
          return array(
            'title' => 'Stats - Solde bancaire',
            'content' => $table,
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
            'fullscreen' => $table2,
            'fullscreenInitScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/statOfcSoldeBanque_chart.js',
            'fullscreenScript' => DOL_URL_ROOT.'/Synopsis_Tools/dashboard/widgets/scripts/statOfcSoldeBanque_chart.js',
          );
        } else {
          return array(
            'title' => 'Stats - Solde bancaire',
            'content' => utf8_encodeRien("Ce module ne vous est pas accessible"),
            'initScript' => "",
            'classes' => 'ui-state-default ui-widget-header',
            'settings' => false,
          );
    }
}

// The widget settings handler.
function widget_statOfcSoldeBanque_settings() {
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
