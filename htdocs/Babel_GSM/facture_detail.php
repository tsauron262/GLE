<?php
/*
 ** GLE by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */
    include_once ("../master.inc.php");
    include_once ("./pre.inc.php");

    //Limit

//TODO :> Total HT et TTC trops large (entete)
//     :> Lien vers les propale, via les commandes et en direct

require ("./main.inc.php");

require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
if ($conf->expedition->enabled) require_once(DOL_DOCUMENT_ROOT."/core/lib/sendings.lib.php");
if ($conf->expedition->enabled) require_once(DOL_DOCUMENT_ROOT."/core/lib/invoice.lib.php");
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
if ($user->rights->BabelGSM->BabelGSM_ctrlGest->AfficheFacture !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}
//todo
//     liens
// modules


/*
 * Generer ou regenerer le document PDF
 */
if ($_REQUEST['action'] == 'builddoc')  // En get ou en post
{
    require_once(DOL_DOCUMENT_ROOT."/core/modules/facture/modules_facture.php");

    $fac = new Facture($db, 0, $_GET['facture_id']);
    $fac->fetch($_GET['facture_id']);

    if ($_REQUEST['model'])
    {
        $fac->setDocModel($user, $_REQUEST['model']);
    }

    $result=facture_pdf_create($db, $fac->id, '', $fac->modelpdf, $outputlangs);
    if ($result <= 0)
    {
        dol_print_error($db,$result);
        exit;
    }
    else
    {
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface=new Interfaces($db);
        $result=$interface->run_triggers('ECM_GENFACTURE',$fac,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
        // Fin appel triggers
        Header ('Location: '.$_SERVER["PHP_SELF"].'?facture_id='.$fac->id.'');
    }
}


require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");

llxHeader("", "Dolibarr Factures", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);




$langs->load("propal");
$langs->load("orders");
$langs->load("bills");
$langs->load("companies");
$langs->load("sendings");
$langs->load("synopsisGene@synopsistools");

$factureid=$_GET['facture_id'];
if ("x".$factureid == "x")
{
    print "no ID provided !!";
    exit(0);
}

$secondary =  "<SPAN id='mnubut_sec1' class='mnubut_sec' onClick='MenuDisplayCSS_secondary(\"facture_info\",\"facture_id\",$factureid)'>Info</SPAN>\n";
print $gsm->MainMenu($secondary);


$html = new Form($db);
$formfile = new FormFile($db);

$id = $_GET['facture_id'];
$fac=new Facture($db,'',$id);
$fac->fetch($id);

//Affichage par ligne de la facture


print '<TABLE style="width:98%; max-width: 98%;min-width: 98%;" class="border">'."\n";
$pair= true;
print "<THEAD>\n";
print "<TR><TH>".$langs->trans("DescriptionShort")."</TH><TH>".$langs->trans("Qty")."</TH><TH>".$langs->trans("PriceUHT")."</TH><TH>".$langs->trans("TotalHT")."</TH></TR>\n";
print "</THEAD>\n";
print "<TBODY>\n";

$price = 0;
$total_ht=0;
$total_ttc=0;
$atLeastOneDiscount=false;
//var_dump($fac->lignes);
    foreach ($fac->lignes as $key=>$val)
    {
        $desc = "";
        if (preg_match("/\[header\]([\w\W]*)/",$val->desc,$arrayMatch))
        {
                $desc = "<SPAN style='font-weight: 900; font-size: 10pt; '>".$arrayMatch[1]."</SPAN>";
                print "    <TR><TD align='center' colspan='6'>".$desc."</TD></TR>\n";
                continue;
        } elseif (preg_match("/\[header1[\w]*?\]([\w\W]*)/",$val->desc,$arrayMatch))
        {
                $desc = "<SPAN style='font-weight: 500; font-size: 9pt;'>".$arrayMatch[1]."</SPAN>";
                print "    <TR><TD align='center' colspan='6'>".$desc."</TD></TR>\n";
                continue;
        } elseif (preg_match("/\[soustot\]([\w\W]*)/",$val->desc,$arrayMatch))
        {
            continue;
        } elseif (preg_match("/\[sautpage\]([\w\W]*)/",$val->desc,$arrayMatch))
        {
            continue;
        }elseif (preg_match("/\[nota\]([\w\W]*)/",$val->desc,$arrayMatch))
        {
            continue;
        } else {
            $desc = $val->desc;
        }

        if ($pair)
        {
            $pair=false;
            print "<TR class='pair'>";
        } else {
            $pair=true;
            print "<TR class='impair'>";
        }
        //print $val->fk_product;
        if ($val->fk_product > 0)
        {
            print "<TD>";
            // Affiche ligne produit
            $text = '<a href="product_detail.php?product_id='.$val->fk_product.'">';
            if ($val->fk_product_type==1)
            {
                $text.= img_object($langs->trans('ShowService'),'service');
            } else {
                $text.= img_object($langs->trans('ShowProduct'),'product');
            }
            $text.= ' '.$val->ref.'</a>';
            $text.= ' - '.$val->product;
            $description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($val->description));
            print $html->textwithtooltip($text,$description,3,'','',$i);
            print_date_range($val->date_start,$val->date_end);
            if ($conf->global->PRODUIT_DESC_IN_FORM)
            {
                print ($val->description && $val->description!=$val->product)?'<br>'.dol_htmlentitiesbr($val->description):'';
            }
            print '</td>';
        } else {
            print "    <TD>".$desc."</TD>";
        }

        print "    <TD>".$val->qty."&nbsp;</TD>";
        print "    <TD nowrap >".price($val->subprice,0,'',1,0)."</TD>";
        $total_ht += $val->total_ht;
        $total_ttc += $val->total_ht * $val->tva_tx;
        $price += $val->subprice * $val->qty;
        print "    <TD nowrap >".price($val->total_ht,0,'',1,0)."</TD>";
        print "</TR>\n";
    }

    //Old version
    $remise_1 = $propal->remise_percent ;
//    print  "remise1" . $remise_1 ." <BR>";
    //New version
    $societe = new Societe($db);
    $societe->fetch($propal->socid,$user->id);
//    var_dump($val);
    $remise_2 = $societe->remise_client . " ".$val->socid;
    //var_dump($societe);
//    print "remise2" . $remise_2 ." <BR>";

    $remise = 0;
    if ($propal->remise_percent)
    {
        $remise = $remise_1;
    } else {
        $remise = $remise_2;
    }
print "</TBODY>\n";
print "<TFOOT>\n";
    print "<TR><TD align='center' colspan='6'>Total</TD></TR>\n ";
    if ($remise > 0)
    {

            print "<TR><TH colspan=3>".$langs->trans("HTBeforeDiscount")."</TH><TD align='right' colspan=3>".price($price,0,'',1,2)."&euro;</TD></TR>\n";
            print "<TR><TH colspan=3>".$langs->trans("RemiseGlob")."</TH><TD align='right' colspan=3>".$remise ."%</TD></TR>\n";
            print "<TR><TH colspan=3>".$langs->trans("HTAfterDiscount")."</TH><TD align='right' colspan=3>".price($total_ht * (1 - $remise/100),0,'',1,2)."&euro;</TD></TR>\n";
            print "<TR><TH colspan=3>".$langs->trans("TotalTTC")."</TH><TD  align='right'colspan=3>".price($total_ttc,0,'',1,2)."&euro;</TD></TR>\n";
    } else {
            print "<TR><TH colspan=3>".$langs->trans("TotalHT")."</TH><TD  align='right'colspan=3>".price($total_ht,0,'',1,2)."&euro;</TD></TR>\n";
            print "<TR><TH colspan=3>".$langs->trans("TotalTTC")."</TH><TD  align='right'colspan=3>".price($total_ttc,0,'',1,2)."&euro;</TD></TR>\n";
        //$langs->trans("AmountHT");
    }
print "</TFOOT>\n";
print "</TABLE>\n";


            /*
             * Liste des paiements
             */
            $sql = 'SELECT datep as dp, pf.amount,';
            $sql.= ' c.libelle as paiement_type, p.num_paiement, p.rowid';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'paiement as p, '.MAIN_DB_PREFIX.'c_paiement as c, '.MAIN_DB_PREFIX.'paiement_facture as pf';
            $sql.= ' WHERE pf.fk_facture = '.$fac->id.' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';
            $sql.= ' ORDER BY dp, tms';

            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows($result);
                $i = 0;
                //print '<table class="noborder" width="100%">';

                // Liste des paiements ou remboursements
                print '</TABLE><BR><TABLE class="border">';
                print '<tr class="liste_titre">';
                print '<td>'.($fac->type == 2 ? $langs->trans("PaymentsBack") : $langs->trans('Payments')).'</td>';
                print '<td>'.$langs->trans('Type').'</td>';
                print '<td align="right">'.$langs->trans('Amount').'</td>';
                print '</tr>';

                if ($fac->type != 2)
                {
                    $var=True;
                    while ($i < $num)
                    {
                        $objp = $db->fetch_object($result);
                        $var=!$var;
                        print '<tr '.$bc[$var].'><td>';
                        print '<a href="paiement_detail.php?paiement_id='.$objp->rowid.'">'.img_object($langs->trans('ShowPayment'),'payment').' ';
                        print dol_print_date($db->jdate($objp->dp),'day').'</a></td>';
                        print '<td>'.$objp->paiement_type.' '.$objp->num_paiement.'</td>';
                        print '<td align="right">'.price($objp->amount).'</td>';
                        print '</tr>';
                        $i++;
                    }

                    // Already payed
                    print '<tr><td colspan="2" align="right">'.$langs->trans('AlreadyPayed').' :</td><td align="right"><b>'.price($totalpaye).'</b></td></tr>';

                    // Billed
                    print '<tr><td colspan="2" align="right">'.$langs->trans("Billed").' :</td><td align="right" style="border: 1px solid;">'.price($fac->total_ttc).'</td></tr>';
                    $resteapayeraffiche=$resteapayer;

                    // Loop on each credit note applied
                    $sql = "SELECT re.rowid, " .
                            "      re.amount_ht, " .
                            "      re.amount_tva, " .
                            "      re.amount_ttc,";
                    $sql.= "       re.description, " .
                            "      re.fk_facture_source, " .
                            "      re.fk_facture_source";
                    $sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re";
                    $sql.= " WHERE fk_facture = ".$fac->id;
                    $resql=$db->query($sql);
                    if ($resql)
                    {
                        $num = $db->num_rows($resql);
                        $i = 0;
                        $invoice=new Facture($db);
                        while ($i < $num)
                        {
                            $obj = $db->fetch_object($resql);
                            $invoice->fetch($obj->fk_facture_source);
                            print '<tr><td colspan="2" align="right">'.$langs->trans("CreditNote").' ';
                            print $invoice->getNomUrl(0);
                            print ' :</td>';
                            print '<td align="right" style="border: 1px solid;">'.price($obj->amount_ttc).'</td>';
                            print '</tr>';
                            $i++;
                        }
                    }
                    else
                    {
                        dol_print_error($db);
                    }

                    // Paye partiellement 'escompte'
                    if (($fac->statut == 2 || $fac->statut == 3) && $fac->close_code == 'discount_vat')
                    {
                        print '<tr><td colspan="2" align="right" nowrap="1">';
                        print $html->textwithtooltip($langs->trans("Escompte").':',$langs->trans("HelpEscompte"),-1);
                        print '</td><td align="right">'.price($fac->total_ttc - $totalpaye).'</td></tr>';
                        $resteapayeraffiche=0;
                    }
                    // Paye partiellement ou Abandon 'badcustomer'
                    if (($fac->statut == 2 || $fac->statut == 3) && $fac->close_code == 'badcustomer')
                    {
                        print '<tr><td colspan="2" align="right" nowrap="1">';
                        print $html->textwithtooltip($langs->trans("Abandoned").':',$langs->trans("HelpAbandonBadCustomer"),-1);
                        print '</td><td align="right">'.price($fac->total_ttc - $totalpaye).'</td></tr>';
                        //$resteapayeraffiche=0;
                    }
                    // Paye partiellement ou Abandon 'product_returned'
                    if (($fac->statut == 2 || $fac->statut == 3) && $fac->close_code == 'product_returned')
                    {
                        print '<tr><td colspan="2" align="right" nowrap="1">';
                        print $html->textwithtooltip($langs->trans("ProductReturned").':',$langs->trans("HelpAbandonProductReturned"),-1);
                        print '</td><td align="right">'.price($fac->total_ttc - $totalpaye).'</td></tr>';
                        $resteapayeraffiche=0;
                    }
                    // Paye partiellement ou Abandon 'abandon'
                    if (($fac->statut == 2 || $fac->statut == 3) && $fac->close_code == 'abandon')
                    {
                        print '<tr><td colspan="2" align="right" nowrap="1">';
                        $text=$langs->trans("HelpAbandonOther");
                        if ($fac->close_note) $text.='<br><br><b>'.$langs->trans("Reason").'</b>:'.$fac->close_note;
                        print $html->textwithtooltip($langs->trans("Abandoned").':',$text,-1);
                        print '</td><td align="right">'.price($fac->total_ttc - $totalpaye).'</td></tr>';
                        $resteapayeraffiche=0;
                    }
                    print '<tr><td colspan="2" align="right">';
                    if ($resteapayeraffiche >= 0) print $langs->trans('RemainderToPay');
                    else print $langs->trans('ExcessReceived');
                    print ' :</td>';
                    print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($resteapayeraffiche).'</b></td>';
                    print '</tr>';
                }
                else
                {
                    // Solde avoir
                    print '<tr><td colspan="2" align="right">'.$langs->trans('TotalTTCToYourCredit').' :</td>';
                    print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price(abs($fac->total_ttc)).'</b></td></tr>';
                }
                //print '</table>';
                $db->free($result);
            }
            else
            {
                dol_print_error($db);
            }

            print '</td></tr>';

            print '</table><br>';



                    print '</div>';


                /*
                 *   Propales rattachees
                 */
                $sql = 'SELECT p.datep as dp, p.total_ht, p.ref, p.ref_client, p.rowid as propalid';
                $sql .= ' FROM '.MAIN_DB_PREFIX.'propal as p';
                $sql .= ", ".MAIN_DB_PREFIX."fa_pr as fp";
                $sql .= " WHERE fp.fk_propal = p.rowid AND fp.fk_facture = ".$fac->id;

                dol_syslog("facture.php: sql=".$sql);
                $resql = $db->query($sql);
                if ($resql)
                {
                    $num = $db->num_rows($resql);
                    if ($num)
                    {
                        $i = 0; $total = 0;
                        if ($somethingshown) print '<br>';
                        $somethingshown=1;
                        load_fiche_titre($langs->trans('RelatedCommercialProposals'));
                        print '<table class="noborder" width="100%">';
                        print '<tr class="liste_titre">';
                        print '<td width="150">'.$langs->trans('Ref').'</td>';
                        print '<td>'.$langs->trans('RefCustomer').'</td>';
                        print '<td align="center">'.$langs->trans('Date').'</td>';
                        print '<td align="right">'.$langs->trans('AmountHT').'</td>';
                        print '</tr>';

                        $var=True;
                        while ($i < $num)
                        {
                            $objp = $db->fetch_object($resql);
                            $var=!$var;
                            print '<tr '.$bc[$var].'>';
                            print '<td><a href="propal.php?propalid='.$objp->propalid.'">'.img_object($langs->trans('ShowPropal'),'propal').' '.$objp->ref.'</a></td>';
                            print '<td>'.$objp->ref_client.'</td>';
                            print '<td align="center">'.dol_print_date($db->jdate($objp->dp),'day').'</td>';
                            print '<td align="right">'.price($objp->total_ht).'</td>';
                            print '</tr>';
                            $total = $total + $objp->total_ht;
                            $i++;
                        }
                        print '<tr class="liste_total">';
                        print '<td align="left">'.$langs->trans('TotalHT').'</td>';
                        print '<td>&nbsp;</td>';
                        print '<td>&nbsp;</td>';
                        print '<td align="right">'.price($total).'</td></tr>';
                        print '</table>';
                    }

                }



                /*
                 * Commandes rattachees
                 */
                if($conf->commande->enabled)
                {
                    $sql = 'SELECT c.date_commande as date_commande, c.total_ht, c.ref, c.ref_client, c.rowid as id';
                    $sql .= ' FROM '.MAIN_DB_PREFIX.'commande as c, '.MAIN_DB_PREFIX.'co_fa as co_fa WHERE co_fa.fk_commande = c.rowid AND co_fa.fk_facture = '.$fac->id;
                    $resql = $db->query($sql);
                    if ($resql)
                    {
                        $num = $db->num_rows($resql);
                        if ($num)
                        {
                            $langs->load("orders");

                            $i = 0; $total = 0;
                            if ($somethingshown) print '<br>';
                            $somethingshown=1;
                            load_fiche_titre($langs->trans('RelatedOrders'));
                            print '<table class="noborder" width="100%">';
                            print '<tr class="liste_titre">';
                            print '<td width="150">'.$langs->trans('Ref').'</td>';
                            print '<td>'.$langs->trans('BabelRefCustomerOrderShort').'</td>';
                            print '<td align="center">'.$langs->trans('Date').'</td>';
                            print '<td align="right">'.$langs->trans('AmountHT').'</td>';
                            print '</tr>';
                            $var=true;
                            while ($i < $num)
                            {
                                $objp = $db->fetch_object($resql);
                                $var=!$var;
                                print '<tr '.$bc[$var].'><td>';
                                print '<a href="commande_detail.php?commande_id='.$objp->id.'">'.img_object($langs->trans('ShowOrder'), 'order').' '.$objp->ref."</a></td>\n";
                                print '<td>'.$objp->ref_client.'</td>';
                                print '<td align="center">'.dol_print_date($db->jdate($objp->date_commande),'day').'</td>';
                                print '<td align="right">'.price($objp->total_ht).'</td>';
                                print "</tr>\n";
                                $total = $total + $objp->total_ht;
                                $i++;
                            }
                            print '<tr class="liste_total">';
                            print '<td align="left">'.$langs->trans('TotalHT').'</td>';
                            print '<td>&nbsp;</td>';
                            print '<td>&nbsp;</td>';
                            print '<td align="right">'.price($total).'</td></tr>';
                            print '</table>';
                        }
                    }
                    else
                    {
                        dol_print_error($db);
                    }
                }

                print '</td><td valign="top" width="50%">';

                print '<br>';

                // List of actions on element
                include_once(DOL_DOCUMENT_ROOT.'/html.formactions.class.php');
                $formactions=new FormActions($db);
                $somethingshown=$formactions->showactions($fac,'invoice',$socid);

                print '</td></tr></table>';


                print '<table width="100%"><tr><td width="50%" valign="top">';
                print '<a name="builddoc"></a>'; // ancre

                /*
                 * Documents generes
                 */
                $filename=sanitize_string($fac->ref);
                $filedir=$conf->facture->dir_output . '/' . sanitize_string($fac->ref);
                $urlsource=$_SERVER['PHP_SELF'].'?facture_id='.$fac->id;
                $genallowed=$user->rights->facture->creer;
                $delallowed=$user->rights->facture->supprimer;

                $var=true;

                print '<br>';
                $somethingshown=$gsm->show_documents($db,'facture',$filename,$filedir,$urlsource,$genallowed,$delallowed,$fac->modelpdf);




$gsm->jsCorrectSize(true);


?>