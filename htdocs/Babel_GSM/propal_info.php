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

require ("./main.inc.php");

require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");


require_once(DOL_DOCUMENT_ROOT."/core/modules/propale/modules_propale.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/propal.lib.php");

if ($user->rights->BabelGSM->BabelGSM_com->AffichePropal !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}

/*
 * Generation doc (depuis lien ou depuis cartouche doc)
 */
if ($_REQUEST['action'] == 'builddoc' && $user->rights->propale->creer)
{
    require_once(DOL_DOCUMENT_ROOT."/core/modules/propale/modules_propale.php");

    $propal = new Propal($db);
    $propal->fetch($_GET['propal_id']);
    if ($_REQUEST['model'])
    {
        $propal->setDocModel($user, $_REQUEST['model']);
    }

    if ($_REQUEST['lang_id'])
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    $result=propale_pdf_create($db, $propal->id, $propal->modelpdf, $outputlangs);
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
        $result=$interface->run_triggers('ECM_GENPROPAL',$propal,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
        // Fin appel triggers
        Header ('Location: '.$_SERVER["PHP_SELF"].'?propal_id='.$_GET['propal_id'].'');
//print $_SERVER["PHP_SELF"].'?propal_id='.$_GET['propal_id'];
        exit;
    }
}

if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php');


require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");

llxHeader("", "Dolibarr Propales", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);


$langs->load("propal");
$langs->load("synopsisGene@synopsistools");
$langs->load('companies');
$langs->load('compta');
$langs->load('bills');
$langs->load('orders');
$langs->load('products');


$propalid=$_GET['propal_id'];
if ("x".$propalid == "x")
{
    print "no ID provided !!";
    exit(0);
}

$secondary =  "<SPAN id='mnubut_sec' class='mnubut_sec' onClick='MenuDisplayCSS_secondary(\"propal_detail\",\"propal_id\",$propalid)'>D&eacute;tails</SPAN>\n";

print $gsm->MainMenu($secondary);



print "</DIV>";

print "<DIV id='menuDiv' class='menuDiv' style=''>";
print "<UL><LI onClick='DisplayDet(\"index\")' class='menu'>Accueil";
print "    <LI onClick='DisplayDet(\"propal\")' class='menu'>Propal";
print "    <LI onClick='DisplayDet(\"commande\")' class='menu'>Commande";
print "    <LI onClick='DisplayDet(\"facture\")' class='menu'>Facture";
print "    <LI onClick='DisplayDet(\"paiement\")' class='menu'>Paiement";
print "    <LI onClick='DisplayDet(\"expedition\")' class='menu'>Expedition";
print "    <LI onClick='DisplayDet(\"stock\")' class='menu'>Stock";
print "    <LI onClick='DisplayDet(\"fournisseur\")' class='menu'>Fournisseur";
print "    <LI onClick='DisplayDet(\"client\")' class='menu'>Client";
print "    <LI onClick='DisplayDet(\"prospect\")' class='menu'>Proscpect";
print "    <LI onClick='DisplayDet(\"documents\")' class='menu'>Documents";
print "    <LI onClick='DisplayDet(\"intervention\")' class='menu'>Fiche Intervention";
print "</DIV>\n";

$propal = new Propal($db);
$propal->fetch($propalid);

    $societe = new Societe($db);
    $societe->fetch($propal->socid);

$html = new Form($db);
$formfile = new FormFile($db);

print '<TABLE name="maintable" style="width:98%; max-width: 98%;min-width: 98%;" class="border">'."\n";
$pair= true;
print "<THEAD>\n";
//print "<TR><TH>".$langs->trans("DescriptionShort")."</TH><TH>".$langs->trans("Qty")."</TH><TH>".$langs->trans("PriceUHT")."</TH><TH>".$langs->trans("DiscountShort")."</TH><TH>".$langs->trans("TotalHT")."</TH><TH>".$langs->trans("isOpt")."</TH></TR>\n";
print "</THEAD>\n";
print "<TBODY>\n";

    // Ref
    print '<tr><td>'.$langs->trans('Ref').'</td><td colspan="5">'.$propal->ref.'</td></tr>';

    // Ref client
    print '<tr><td>';
    print '<table class="nobordernopadding" width="100%"><tr><td>';
    print $langs->trans('RefCustomer').'</td><td align="left">';
    print '</td>';
    print '</tr></table>';
    print '</td><td colspan="5">';

        print $propal->ref_client;

    print '</td>';
    print '</tr>';

    $rowspan=10;

    // Societe
    print '<tr><td>'.$langs->trans('Company').'</td><td colspan="5">'.$societe->getNomUrl(1).'</td>';
    print '</tr>';

    // Ligne info remises tiers
    print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="5">';
    if ($societe->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$societe->remise_client);
    else print $langs->trans("CompanyHasNoRelativeDiscount");
    $absolute_discount=$societe->getAvailableDiscounts('','fk_facture_source IS NULL');
    $absolute_creditnote=$societe->getAvailableDiscounts('','fk_facture_source IS NOT NULL');
    print '. ';
    if ($absolute_discount)
    {
        if ($propal->statut > 0)
        {
            print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->global->MAIN_MONNAIE));
        }
        else
        {
            // Remise dispo de type non avoir
            $filter='fk_facture_source IS NULL';
            print '<br>';
//            $html->form_remise_dispo($_SERVER["PHP_SELF"].'?propalid='.$propal->id,0,'remise_id',$societe->id,$absolute_discount,$filter);
        }
    }
    if ($absolute_creditnote)
    {
        print $langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->global->MAIN_MONNAIE)).'. ';
    }
    if (! $absolute_discount && ! $absolute_creditnote) print $langs->trans("CompanyHasNoAbsoluteDiscount").'.';
    print '</td></tr>';

    // Dates
    print '<tr><td>'.$langs->trans('Date').'</td><td colspan="3">';
    print dol_print_date($propal->date,'daytext');
    print '</td>';

    if ($conf->projet->enabled) $rowspan++;
    //Remise Mod Babel
    $rowspan += 4;
    if ($conf->expedition_bon->enabled || $conf->livraison_bon->enabled)
    {
        if ($conf->global->PROPALE_ADD_SHIPPING_DATE || !$conf->commande->enabled) $rowspan++;
        if ($conf->global->PROPALE_ADD_DELIVERY_ADDRESS || !$conf->commande->enabled) $rowspan++;
    }

    // Notes
    print '</TR><TR><td valign="top" colspan="1" width="50%" >'.$langs->trans('NotePublic').' :<td colspan=5>'. nl2br($propal->note_public).'</td>';
    print '</tr>';

    // Date fin propal
    print '<tr>';
    print '<td>';
    print '<table class="nobordernopadding" width="100%"><tr><td>';
    print $langs->trans('DateEndPropal');
    print '</td>';
    print '</tr></table>';
    print '<td colspan="3">';

        if ($propal->fin_validite)
        {
            print dol_print_date($propal->fin_validite,'daytext');
            if ($propal->statut == 1 && $propal->fin_validite < (time() - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
        }
        else
        {
            print '&nbsp;';
        }
    print '</td>';
    print '</tr>';


    // date de livraison (conditionne sur PROPALE_ADD_SHIPPING_DATE car carac a
    // gerer par les commandes et non les propales
    if ($conf->expedition_bon->enabled || $conf->livraison_bon->enabled)
    {
        if ($conf->global->PROPALE_ADD_SHIPPING_DATE || !$conf->commande->enabled)
        {
            $langs->load('deliveries');
            print '<tr><td>';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('DeliveryDate');
            print '</td>';
            print '</tr></table>';
            print '</td><td colspan="3">';
                print dol_print_date($propal->date_livraison,'%a %d %B %Y');
            print '</td>';
            print '</tr>';
        }

        // adresse de livraison
        if ($conf->global->PROPALE_ADD_DELIVERY_ADDRESS || !$conf->commande->enabled)
        {
            print '<tr><td>';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('DeliveryAddress');
            print '</td>';

            print '</tr></table>';
            print '</td><td colspan="3">';


                $html->form_adresse_livraison($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->adresse_livraison_id,$_GET['socid'],'none','propal',$propal->id);
            print '</td></tr>';
        }
    }

    // Conditions et modes de reglement
    print '<tr><td>';
    print '<table class="nobordernopadding" width="100%"><tr><td>';
    print $langs->trans('PaymentConditionsShort');
    print '</td>';

    print '</tr></table>';
    print '</td><td colspan="3">';
        $html->form_conditions_reglement($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->cond_reglement_id,'none');
    print '</td>';
    print '</tr>';

    // Mode paiement
    print '<tr>';
    print '<td width="25%">';
    print '<table class="nobordernopadding" width="100%"><tr><td>';
    print $langs->trans('PaymentMode');
    print '</td>';
    print '</tr></table>';
    print '</td><td colspan="3">';
        $html->form_modes_reglement($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->mode_reglement_id,'none');
    print '</td></tr>';

    // Projet
    if ($conf->projet->enabled)
    {
        $langs->load("projects");
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('Project').'</td>';
        $numprojet = $societe->has_projects();
        if (! $numprojet)
        {
            print '</td></tr></table>';
            print '<td colspan="5">';
            print $langs->trans("NoProject").'</td>';
        }
        else
        {
                print '</td></tr></table>';
                if (!empty($propal->projetidp))
                {
                    print '<td colspan="3">';
                    $proj = new Project($db);
                    $proj->fetch($propal->projetidp);
                    print '<a href="project_detail.php?project_id='.$propal->projetidp.'" title="'.$langs->trans('ShowProject').'">';
                    print $proj->title;
                    print '</a>';
                    print '</td>';
                }
                else {
                    print '<td colspan="3">&nbsp;</td>';
                }
        }
        print '</tr>';
    }

//    // Amount HT
//    print '<tr><td height="10">'.$langs->trans('AmountHT').'</td>';
//    print '<td align="right" colspan="2" nowrap><b>'.price($propal->total_ht).'</b></td>';
//    print '<td>'.$langs->trans("Currency".$conf->global->MAIN_MONNAIE).'</td></tr>';
//
//    // Amount VAT
//    print '<tr><td height="10">'.$langs->trans('AmountVAT').'</td>';
//    print '<td align="right" colspan="2" nowrap>'.price($propal->total_tva).'</td>';
//    print '<td>'.$langs->trans("Currency".$conf->global->MAIN_MONNAIE).'</td></tr>';
//
//    // Amount TTC
//    print '<tr><td height="10">'.$langs->trans('AmountTTC').'</td>';
//    print '<td align="right" colspan="2" nowrap>'.price($propal->total_ttc).'</td>';
//    print '<td>'.$langs->trans("Currency".$conf->global->MAIN_MONNAIE).'</td></tr>';

//Amount Babel

    $requete = "SELECT * " .
            "     FROM ".MAIN_DB_PREFIX."propaldet " .
            "    WHERE special_code =0 AND  description NOT RLIKE  \"^\\[[a-zA-Z]+[0-9]?\\]\" AND fk_propal=".$propal->id;
    $resql=$societe->db->query($requete);
    $arrRem=array();
    $arrRem['TotalHTsansRemise']=0;
    $arrRem['TotalTTCsansRemise']=0;
    $arrRem['TotalHTavecRemiseSurLigne']=0;
    $arrRem['TotalTTCavecRemiseSurLigne']=0;

    if ($resql)
    {
        if ($societe->db->num_rows($resql))
        {
            $im='';
            while ($res=$societe->db->fetch_object($resql))
            {
                $arrRem['TotalHTsansRemise']+=$res->subprice * $res->qty;
                $arrRem['TotalTTCsansRemise']+=$res->subprice * $res->qty * 1.196;
                $arrRem['TotalHTavecRemiseSurLigne']+=($res->subprice * (1 - $res->remise_percent/100))*$res->qty - $res->remise;
                $arrRem['TotalTTCavecRemiseSurLigne']+=(($res->subprice * $res->qty * (1 - $res->remise_percent/100)) - $res->remise)*1.196;
            }
        }
    }
    $remise_client = $propal->remise_percent;
    if ($societe->remise_client > $propal->remise_percent)
    {
        $remise_client = $societe->remise_client;
    }
    $remise_absolue = $propal->remise_absolue;

    $requeteRemise = "SELECT * FROM ".MAIN_DB_PREFIX."societe_remise_except WHERE fk_soc =".$societe->id;
    $resqlRem=$societe->db->query($requeteRemise);
    if ($resqlRem)
    {
        $remise_absolue=0;
        if ($societe->db->num_rows($resqlRem))
        {
            $im='';
            while ($res=$societe->db->fetch_object($resqlRem))
            {
                $remise_absolue += $res->amount_ht;
            }
        }
    }

    $arrRem['TotalHTRemiseTot']=$arrRem['TotalHTavecRemiseSurLigne']* (1-$remise_percent/100) - $remise_absolue;
    $arrRem['TotalTTCRemisetot']=($arrRem['TotalTTCavecRemiseSurLigne']* (1-$remise_percent/100) - $remise_absolue)*1.196;

    // Amount HT Babel 1
    print '<tr><td height="10" class="pair">'.$langs->trans('Total HT sans remise').'</td>';
    print '<td  class="pair" align="right" colspan="1" ><b>'.price($arrRem['TotalHTsansRemise']).'</b></td>';
    print '<td class="pair">'.$langs->trans("Currency".$conf->global->MAIN_MONNAIE).'</td><td class="pair">&nbsp;</td></tr>';

    // Amount HT Babel 2 (sur lignes)
    print '<tr><td height="10" class="impair">'.$langs->trans('Total HT avec remise sur ligne').'</td>';
    print '<td  class="impair" align="right" colspan="1" ><b>'.price($arrRem['TotalHTavecRemiseSurLigne']).'</b></td>';
    print '<td class="impair">'.$langs->trans("Currency".$conf->global->MAIN_MONNAIE).'</td><td class="impair">&nbsp;</td></tr>';


    // Remises HT Babel 2 (sur lignes)
    print '<tr><td height="10" class="pair">'.$langs->trans('Remise HT sur ligne').'</td>';
    print '<td  class="pair" align="right" colspan="1" ><b>'.price($arrRem['TotalHTsansRemise']-$arrRem['TotalHTavecRemiseSurLigne']).'</b></td>';
    print '<td class="pair">'.$langs->trans("Currency".$conf->global->MAIN_MONNAIE).'</td><td class="pair">&nbsp;</td></tr>';

    // Amount HT Babel 4 (sur lignes et global)
    print '<tr><td height="10" class="impair">'.$langs->trans('Total HT avec remises sur lignes et globals').'</td>';
    print '<td  class="impair" align="right" colspan="1" ><b>'.price($arrRem['TotalHTavecRemiseSurLigne']*(1-$remise_client/100)).'</b></td>';
    print '<td class="impair">'.$langs->trans("Currency".$conf->global->MAIN_MONNAIE).'</td><td class="impair">&nbsp;</td></tr>';

    // Amount TTC Babel 4 (sur lignes et global)
    print '<tr><td height="10" class="pair">'.$langs->trans('Total TTC avec remises sur lignes et globals').'</td>';
    print '<td  class="pair" align="right" colspan="1" ><b>'.price($arrRem['TotalHTavecRemiseSurLigne']*(1-$remise_client/100)*1.196).'</b></td>';
    print '<td class="pair">'.$langs->trans("Currency".$conf->global->MAIN_MONNAIE).'</td><td class="pair">&nbsp;</td></tr>';

    // Remise HT Babel 5
    print '<tr><td height="10" class="impair">'.$langs->trans('Total remises HT globals').'</td>';
    print '<td align="right" colspan="1"  class="impair">'.price($arrRem['TotalHTavecRemiseSurLigne']*($remise_client/100)).'</td>';
    print '<td class="impair">'.$langs->trans("Currency".$conf->global->MAIN_MONNAIE).'</td><td class="impair">&nbsp;</td></tr>';

    // Total Remise HT Babel 5
    $comp=0;
    if ($arrRem['TotalHTsansRemise'] == 0)
    {
        $comp = 0;
    } else {
        $comp = round(100*($arrRem['TotalHTsansRemise'] - $arrRem['TotalHTavecRemiseSurLigne']*(1-$remise_client/100))/$arrRem['TotalHTsansRemise'],2);
    }

    print '<tr><td height="10" class="pair">'.$langs->trans('Total remises HT').'</td>';
    print '<td align="right" colspan="1"  class="pair">'.price($arrRem['TotalHTsansRemise'] - $arrRem['TotalHTavecRemiseSurLigne']*(1-$remise_client/100)).'</td>';
    print '<td class="pair">'.$langs->trans("Currency".$conf->global->MAIN_MONNAIE).'</td>';
    print '<td align="center"  class="pair">'.' ('.$comp.'%)</td></tr>';


    $requete = "SELECT * " .
            "     FROM Babel_Propal_WinPercent " .
            "    WHERE fk_propal =".$propal->id;
    $resql=$societe->db->query($requete);
    $percentCom=0;
    $percentDir=0;
    if ($resql)
    {
        if ($societe->db->num_rows($resql))
        {

            while ($res=$societe->db->fetch_object($resql))
            {
                $percentCom = $res->PercentCom;
                $percentDir = $res->PercentDir;
            }
        }
    }
    $percentCom *= 100;
    $percentDir *= 100;

    // Statut
    print '<tr><td height="10">'.$langs->trans('Status').'</td><td align="left" colspan="3">'.$propal->getLibStatut(4).'</td></tr>';

    if ($_GET['action'] != 'editEstimComm' )
    {
        print '<tr><td height="10">Estimation Commerciale</td>';
        print "<td align=\"left\" colspan=\"3\">".$percentCom."%</td></tr>";
    }
    if ($user->admin == 1|| $user->local_admin == 1)//editEstimDir2
    {
        if ($_GET['action'] != 'editEstimDir' ) {
            print '<tr><td height="10">Estimation Direction</td>';
            print '<td align="left" colspan="3">'.$percentDir.'%</td></tr>';
        }
    }
    print '</table><br>';

       /*
        * Commandes rattachees
        */

        if($conf->commande->enabled)
        {

            $propal->loadOrders();
            $coms = $propal->commandes;
            if (sizeof($coms) > 0)
            {
                //if ($somethingshown) { print '<br>'; $somethingshown=1; }
                print '<BR>';
                load_fiche_titre($langs->trans('RelatedOrders'));
                print '<table class="border" style="width:98%; max-width: 98%;min-width: 98%;">';
                print '<tr class="liste_titre">';
                print '<td>'.$langs->trans("Ref").'</td>';
                print '<td align="center">'.$langs->trans("Date").'</td>';
                print '<td align="right">'.$langs->trans("AmountHT").'</td>';
                print '<td align="right">'.$langs->trans("Status").'</td>';
                print '</tr>';
                $var=true;
                $commIdArr=array();
                for ($i = 0 ; $i < sizeof($coms) ; $i++)
                {
                    $var=!$var;
                    print '<tr '.$bc[$var].'><td>';
                    print '<a href="commande_detail.php?commande_id='.$coms[$i]->id.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$coms[$i]->ref."</a></td>\n";
                    print '<td align="center">'.dol_print_date($coms[$i]->date,'day').'</td>';
                    print '<td align="right">'.price($coms[$i]->total_ht).'</td>';
                    print '<td align="right">'.$coms[$i]->getLibStatut(3).'</td>';
                    print "</tr>\n";
                    //stock les id de commandes
                    array_push($commIdArr,$coms[$i]->id);
                }
                print '</table>';
//Facture
                if ($conf->facture->enabled)
                {
                    //Est ce qu'il y a des factures rattachees a la commande
                    $requete = "SELECT count(*) as cnt " .
                            "     FROM ".MAIN_DB_PREFIX."co_fa " .
                            "    WHERE fk_commande IN (SELECT fk_commande " .
                            "                            FROM ".MAIN_DB_PREFIX."co_pr " .
                            "                           WHERE fk_propale = ".$propalid.")";
//                     print $requete;
                     $resql = $db->query($requete);
                     $countFacture = 0;

                     if ($resql)
                     {
                        $res=$db->fetch_object($resql);
                        $countFacture = $res->cnt;
                     }

                    //si oui affichage de l'entete
                    if ($countFacture > 0)
                    {
                        //if ($somethingshown) { print '<br>'; $somethingshown=1; }
                        print '<BR>';
                        load_fiche_titre($langs->trans('RelatedBills'));
                        print '<table class="border" style="width:98%; max-width: 98%;min-width: 98%;">';
                        print '<tr class="liste_titre">';
                        print '<td>'.$langs->trans("Ref").'</td>';
                        print '<td align="center">'.$langs->trans("Date").'</td>';
                        print '<td align="right">'.$langs->trans("AmountHT").'</td>';
                        print '<td align="right">'.$langs->trans("Status").'</td>';
                        print '</tr>';
                        $var=true;
                        foreach ($commIdArr as $key=>$val)
                        {
                            $requete = "SELECT fk_facture " .
                                    "     FROM ".MAIN_DB_PREFIX."co_fa " .
                                    "    WHERE fk_commande = " . $val;
                            $resql = $db->query($requete);
                            if ($resql)
                            {
                                while($res=$db->fetch_object($resql))
                                {
                                    $fact=new Facture($db,$propal->socid,$res->fk_facture);
                                    $fact->fetch($res->fk_facture,$propal->socid);
                                    $var=!$var;
                                    print '<tr '.$bc[$var].'><td>';
                                    print '<a href="facture_detail.php?facture_id='.$res->fk_facture.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$fact->ref."</a></td>\n";
                                    print '<td align="center">'.dol_print_date($fact->date,'day').'</td>';
                                    print '<td align="right">'.price($fact->total_ht).'</td>';
                                    print '<td align="right">'.$fact->getLibStatut(3).'</td>';
                                    print "</tr>\n";
                                }
                            }

                        }
                    }
                    print '</table>';
                }

            }
        }

//        print '</td><td valign="top" width="50%">';
//        print '</td></tr></table>';

       /*
        * Documents generes
        */
        include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php');
        $formfile = new FormFile($db);


        $filename=sanitize_string($propal->ref);
        $filedir=$conf->propal->dir_output . "/" . sanitize_string($propal->ref);
        $urlsource=$_SERVER["PHP_SELF"]."?propal_id=".$propal->id;
        $genallowed=$user->rights->propale->creer;
        $delallowed=$user->rights->propale->supprimer;

        $var=true;
//trop large

        $somethingshown=show_documents($db,'propal',$filename,$filedir,$urlsource,$genallowed,$delallowed,$propal->modelpdf);

$gsm->jsCorrectSize();

function show_documents($db,$modulepart,$filename,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$modelliste=array(),$forcenomultilang=0,$iconPDF=0,$maxfilenamelength=28)
    {
        // filedir = conf->...dir_ouput."/".get_exdir(id)
        include_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');

        global $langs,$bc,$conf;
        $var=true;

        if ($iconPDF == 1)
        {
            $genallowed = '';
            $delallowed = 0;
            $modelselected = '';
            $modelliste = '';
            $forcenomultilang=0;
        }

        $filename = sanitize_string($filename);
        $headershown=0;
        $i=0;

        // Affiche en-tete tableau
        if ($genallowed)
        {
            $modellist=array();
            if ($modulepart == 'propal')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php');
                    $model=new ModelePDFPropales();
                    $modellist=$model->liste_modeles($db);
                }
            }
            else if ($modulepart == 'commande')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php');
                    $model=new ModelePDFCommandes();
                    $modellist=$model->liste_modeles($db);
                }
            }
            elseif ($modulepart == 'expedition')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/expedition/mods/pdf/ModelePdfExpedition.class.php');
                    $model=new ModelePDFExpedition();
                    $modellist=$model->liste_modeles($db);
                }
            }
            elseif ($modulepart == 'livraison')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/livraison/mods/modules_livraison.php');
                    $model=new ModelePDFDeliveryOrder();
                    $modellist=$model->liste_modeles($db);
                }
            }
            else if ($modulepart == 'ficheinter')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php');
                    $model=new ModelePDFFicheinter();
                    $modellist=$model->liste_modeles($db);
                }
            }
            elseif ($modulepart == 'facture')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php');
                    $model=new ModelePDFFactures();
                    $modellist=$model->liste_modeles($db);
                }
            }
            elseif ($modulepart == 'export')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php');
                    $model=new ModeleExports();
                    $modellist=$model->liste_modeles($db);
                }
            }
            else if ($modulepart == 'commande_fournisseur')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/fourn/commande/modules/modules_commandefournisseur.php');
                    $model=new ModelePDFSuppliersOrders();
                    $modellist=$model->liste_modeles($db);
                }
            }
            else if ($modulepart == 'facture_fournisseur')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/fourn/facture/modules/modules_facturefournisseur.php');
                    $model=new ModelePDFFacturesSuppliers();
                    $modellist=$model->liste_modeles($db);
                }
            }
            else if ($modulepart == 'remisecheque')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    // ??
                }
            }
            else
            {
                dol_print_error($db,'Bad value for modulepart');
                return -1;
            }

            $headershown=1;

            $html = new Form($db);

            print '<form style="width:100%; max-width:100%; min-width:100%" action="'.$urlsource.'" method="post">';
            print '<input type="hidden" name="action" value="builddoc"/>';
print '<BR>';
            load_fiche_titre($langs->trans("Documents"));
            print '<table class="border"  style="width:100%; max-width:100%; min-width:100%">';
            print "<tbody style='width:100%; max-width:100%; min-width:100%'>";
            print '<tr '.$bc[$var].'>';
            print '<td align="center">'.$langs->trans('Model').' ';
            $html->select_array('model',$modellist,$modelselected,0,0,1);
            $texte=$langs->trans('Generate');
            print '</td>';
            print '<td align="right" colspan="1">';
            print '<input class="button" type="submit" value="'.$texte.'">';
            print '</td>';
        }

        // Recupe liste des fichiers
        $png = '';
        $filter = '';
        if ($iconPDF==1)
        {
            $png = '|\.png$';
            $filter = $filename.'.pdf';
        }
        $file_list=dol_dir_list($filedir,'files',0,$filter,'\.meta$'.$png,'date',SORT_DESC);

        // Boucle sur chaque ligne trouvee
        foreach($file_list as $i => $file)
        {
            $var=!$var;

            // Defini chemin relatif par rapport au module pour lien download
            $relativepath=$file["name"];                                // Cas general
            if ($filename) $relativepath=$filename."/".$file["name"];   // Cas propal, facture...
            // Autre cas
            if ($modulepart == 'don')        { $relativepath = get_exdir($filename,2).$file["name"]; }
            if ($modulepart == 'export')     { $relativepath = $file["name"]; }

            if (!$iconPDF) print "<tr ".$bc[$var].">";

            // Affiche nom fichier avec lien download
            if (!$iconPDF) print '<td>';
            print '<a href="'.DOL_URL_ROOT . '/document.php?modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).'">';
            if (!$iconPDF)
            {
                print img_mime($file["name"],$langs->trans("File").': '.$file["name"]).' '.dol_trunc($file["name"],$maxfilenamelength);
            }
            else
            {
                print img_pdf($file["name"],2);
            }
            print '</a>';
            if (!$iconPDF) print '</td>';
            // Affiche taille fichier
            if (!$iconPDF) print '<td colspan=1 align="right">'.round(filesize($filedir."/".$file["name"]) / 1024,2) . ' ko';
            // Affiche date fichier
            if (!$iconPDF) print ' '.dol_print_date(filemtime($filedir."/".$file["name"]),'dayhour').'</td>';

            if (!$iconPDF) print '</tr>';

            $i++;
        }


        if ($headershown)
        {
            // Affiche pied du tableau
            print "</tbody>";
            print "</table>\n";
            if ($genallowed)
            {
                print '</form>';
            }
        }

        return ($i?$i:$headershown);
    }



?>