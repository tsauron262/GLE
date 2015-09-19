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
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
if ($conf->expedition->enabled) require_once(DOL_DOCUMENT_ROOT."/core/lib/sendings.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
//todo
//     liens refaire exp + produitdans exp
//     traduire de commande a commandeFourn OK 60%

if ($user->rights->BabelGSM->BabelGSM_fourn->AfficheCommandeFourn !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}

/*
 * Generation doc (depuis lien ou depuis cartouche doc)
 */
if ($_REQUEST['action'] == 'builddoc')  // In get or post
{
    /*
     * Generate order document
     * define into /core/modules/commande/modules_commande.php
     */
    require_once(DOL_DOCUMENT_ROOT."/core/modules/commande/modules_commande.php");

    // Sauvegarde le dernier modele choisi pour generer un document
    $commande = new Commande($db, 0, $_REQUEST['commande_id']);
    $result=$commande->fetch($_REQUEST['id']);
    if ($_REQUEST['model'])
    {
        $commande->setDocModel($user, $_REQUEST['model']);
    }

    if ($_REQUEST['lang_id'])
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }

    $result=commande_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
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
        $result=$interface->run_triggers('ECM_GENCOMMANDE_FOURN',$commande,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
        // Fin appel triggers

        Header ('Location: '.$_SERVER["PHP_SELF"].'?commande_id='.$commande->id.'');
        exit;
    }
}

require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");

llxHeader("", "Dolibarr Commandes", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);




$langs->load("propal");
$langs->load("orders");
$langs->load("bills");
$langs->load("sendings");
$langs->load("synopsisGene@synopsistools");

$commandeid=$_GET['commande_id'];
if ("x".$commandeid == "x")
{
    print "no ID provided !!";
    exit(0);
}

$secondary =  "<SPAN id='mnubut_sec1' class='mnubut_sec' onClick='MenuDisplayCSS_secondary(\"commande_info\",\"commande_id\",$commandeid)'>Infos</SPAN>\n";
print $gsm->MainMenu($secondary);



$commande = new CommandeFournisseur($db);
$commande->fetch($commandeid);

$html = new Form($db);
$formfile = new FormFile($db);


print '<TABLE style="width:98%; max-width: 98%;min-width: 98%;" class="border">'."\n";
$pair= true;
print "<THEAD>\n";
print "<TR><TH>".$langs->trans("DescriptionShort")."</TH><TH>".$langs->trans("Qty")."</TH><TH>".$langs->trans("PriceUHT")."</TH><TH>".$langs->trans("DiscountShort")."</TH><TH>".$langs->trans("TotalHT")."</TH><TH>".$langs->trans("isOpt")."</TH></TR>\n";
print "</THEAD>\n";
print "<TBODY>\n";

$price = 0;
$total_ht=0;
$atLeastOneDiscount=false;
//var_dump($propal->lignes);
    foreach ($commande->lignes as $key=>$val)
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
        if ($val->remise_percent > 0) { $atLeastOneDiscount = true; }
        print "    <TD>".$val->remise_percent."%</TD>";
        print "    <TD nowrap >".price($val->total_ht,0,'',1,0)."</TD>";
        print "    <TD>";
        if ($val->special_code==3)
        {
            print img_picto("","tick");
        } else {
            print img_picto("","stcomm-1");
            $price += $val->subprice * $val->qty;
            $total_ht += $val->total_ht;
        }
        print "</TD></TR>\n";
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
        if ($atLeastOneDiscount)
        {
            print "<TR><TH colspan=3>".$langs->trans("HTBeforeDiscount")."</TH><TD align='right' colspan=3>".price($price,0,'',1,2)."&euro;</TD></TR>\n";
            print "<TR><TH colspan=3>".$langs->trans("HTAfterDiscountLine")."</TH><TD align='right' colspan=3>".price($total_ht,0,'',1,2)."&euro;</TD></TR>\n";
            print "<TR><TH colspan=3>".$langs->trans("RemiseGlob")."</TH><TD align='right' colspan=3>".$remise ."%</TD></TR>\n";
            print "<TR><TH colspan=3>".$langs->trans("HTAfterDiscount")."</TH><TD align='right' colspan=3>".price($total_ht * (1 - $remise/100),0,'',1,2)."&euro;</TD></TR>\n";
        } else {
            print "<TR><TH colspan=3>".$langs->trans("HTBeforeDiscount")."</TH><TD align='right' colspan=3>".price($price,0,'',1,2)."&euro;</TD></TR>\n";
            print "<TR><TH colspan=3>".$langs->trans("RemiseGlob")."</TH><TD align='right' colspan=3>".$remise ."%</TD></TR>\n";
            print "<TR><TH colspan=3>".$langs->trans("HTAfterDiscount")."</TH><TD align='right' colspan=3>".price($total_ht * (1 - $remise/100),0,'',1,2)."&euro;</TD></TR>\n";
        }
    } else {
        if ($atLeastOneDiscount)
        {
            print "<TR><TH colspan=3>".$langs->trans("HTBeforeDiscount")."</TH><TD align='right' colspan=3>".price($price,0,'',1,2)."&euro;</TD></TR>\n";
            print "<TR><TH colspan=3>".$langs->trans("HTAfterDiscountLine")."</TH><TD align='right' colspan=3>".price($total_ht,0,'',1,2)."&euro;</TD></TR>\n";
        } else {
            print "<TR><TH colspan=3>".$langs->trans("TotalHT")."</TH><TD  align='right'colspan=3>".price($total_ht,0,'',1,2)."&euro;</TD></TR>\n";
        }
        //$langs->trans("AmountHT");
    }
print "</TFOOT>\n";
print "</TABLE>\n";
       /*
        * Propal rattachees
        */
//exp : $cpmmande->loadExpeditions
        if($conf->propal->enabled)
        {

//            $commande->loadPropale();
//            $propals = $commande->propals;
            $propals=array();
            $requete="SELECT * " .
                    "   FROM ".MAIN_DB_PREFIX."co_pr " .
                    "  WHERE fk_commande = ".$commandeid;
            $resql=$db->query($requete);
            if ($resql)
            {
                $i=0;
                while($res=$db->fetch_object($resql))
                {
                    $tmpProp = new Propal($db);
                    $tmpProp->fetch($res->fk_propale);
                    $propals[$i]=$tmpProp;
                    $i++;
                }
            }


            if (sizeof($propals) > 0)
            {
                //if ($somethingshown) { print '<br>'; $somethingshown=1; }
                print '<BR>';
                load_fiche_titre($langs->trans('RelatedCommercialProposals'));
                print '<table class="border" style="width:98%; max-width: 98%;min-width: 98%;">';
                print '<tr class="liste_titre">';
                print '<td>'.$langs->trans("Ref").'</td>';
                print '<td align="center">'.$langs->trans("Date").'</td>';
                print '<td align="right">'.$langs->trans("AmountHT").'</td>';
                print '<td align="right">'.$langs->trans("Status").'</td>';
                print '</tr>';
                $var=true;
                $propIdArr=array();
                for ($i = 0 ; $i < sizeof($propals) ; $i++)
                {
                    $var=!$var;
                    print '<tr '.$bc[$var].'><td>';
                    print '<a href="propal_detail.php?propal_id='.$propals[$i]->id.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$propals[$i]->ref."</a></td>\n";
                    print '<td align="center">'.dol_print_date($propals[$i]->date,'day').'</td>';
                    print '<td align="right">'.price($propals[$i]->total_ht).'</td>';
                    print '<td align="right">'.$propals[$i]->getLibStatut(3).'</td>';
                    print "</tr>\n";
                    //stock les id de commandes
                    array_push($propIdArr,$propals[$i]->id);
                }
                print '</table>';
//Facture
                if ($conf->facture->enabled)
                {
                    //Est ce qu'il y a des factures rattachees a la commande
                    $requete = "SELECT count(*) as cnt " .
                            "     FROM ".MAIN_DB_PREFIX."co_fa " .
                            "    WHERE fk_commande  = ".$commandeid."";
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

                            $requete = "SELECT fk_facture " .
                                    "     FROM ".MAIN_DB_PREFIX."co_fa " .
                                    "    WHERE fk_commande = " . $commandeid;
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
                    print '</table>';
                    }
                }

            }
        }

//        print '</td><td valign="top" width="50%">';
//        print '</td></tr></table>';


    /**
     *  Lignes de commandes avec quantite livrees et reste e livrer
     *  Les quantites livrees sont stockees dans $commande->expeditions[fk_product]
     */

    load_fiche_titre($langs->trans("ProduitsCommande"));

    print '<table class="liste" width="100%">';

    $sql = "SELECT cd.fk_product, cd.description, cd.price, cd.qty, cd.rowid, cd.tva_tx, cd.subprice";
    $sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd ";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
    $sql.= " WHERE cd.fk_commande = ".$commande->id;
    $sql.= " AND p.fk_product_type <> 1";
    $sql.= " ORDER BY cd.rowid";

    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
      $i = 0;

      print '<tr class="liste_titre">';
      print '<tr class="liste_titre">';
      print '<td colspan=4>'.$langs->trans("Description").'</td></tr>';

      $var=true;
      $reste_a_livrer = array();
      while ($i < $num)
      {
        $objp = $db->fetch_object($resql);

        $var=!$var;
        print "<tr $bc[$var]>";
        if ($objp->fk_product > 0)
        {
            $product = new Product($db);
          $product->fetch($objp->fk_product);
          print '<td colspan=4>';
          print '<a href="'.DOL_URL_ROOT.'/Babel_GSM/produit_detail.php?produit_id='.$objp->fk_product.'">';
          print img_object($langs->trans("Product"),"product").' '.$product->ref.'</a>';
          print $product->libelle?' - '.$product->libelle:'';
          print '</td></TR><TR>';
        }
        else
        {
            print "<td>".nl2br($objp->description)."</td>\n";
        }
        print '<TR class="liste_titre" style="font-size: 8pt; " ><td align="center">'.$langs->trans("BabelQtyOrdered").'</td>';
      print '<td align="center" >'.$langs->trans("BabelQtyShipped").'</td>';
      print '<td align="center" >'.$langs->trans("BabelKeepToShip").'</td>';
      if ($conf->stock->enabled)
      {
        print '<td align="center">'.$langs->trans("Stock").'</td>';
      }
      else
      {
        print '<td>&nbsp;</td>';
      }
      print "</tr>\n";
        print '<td align="center">'.$objp->qty.'</td>';

        print '<TR '.$bc[$var].'><td align="center">';
        $quantite_livree = $commande->expeditions[$objp->fk_product];
        print $quantite_livree;
        print '</td>';

        $reste_a_livrer[$objp->fk_product] = $objp->qty - $quantite_livree;
        $reste_a_livrer_total = $reste_a_livrer_total + $reste_a_livrer[$objp->fk_product];
        print '<td align="center">';
        print $reste_a_livrer[$objp->fk_product];
        print '</td>';

        if ($conf->stock->enabled)
        {
            print '<td align="center">';
          print $product->stock_reel;
          if ($product->stock_reel < $reste_a_livrer[$objp->fk_product])
          {
            print ' '.img_warning($langs->trans("StockTooLow"));
          }
          print '</td>';
        }
        else
        {
            print '<td>&nbsp;</td>';
        }
        print "</tr>";

        $i++;
        $var=!$var;
      }
      $db->free($resql);

      if (! $num)
      {
        print '<tr '.$bc[false].'><td colspan="5">'.$langs->trans("NoArticleOfTypeProduct").'<br>';
      }

      print "</table>";
    }



 // Bouton expedier avec gestion des stocks

    print '<table width="100%"><tr><td width="100%" colspan="2" valign="top">';

    if ($conf->stock->enabled && $reste_a_livrer_total > 0 && $commande->statut > 0 && $commande->statut < 3 && $user->rights->expedition->creer)
    {
        load_fiche_titre($langs->trans("NewSending"));

        print '<form method="GET" action="'.DOL_URL_ROOT.'/expedition/card.php">';
        print '<input type="hidden" name="action" value="create">';
        print '<input type="hidden" name="id" value="'.$commande->id.'">';
        print '<input type="hidden" name="origin" value="commande">';
        print '<input type="hidden" name="object_id" value="'.$commande->id.'">';
        print '<table class="border" width="100%">';

        $entrepot = new Entrepot($db);
        $langs->load("stocks");

        print '<tr>';
        print '<td>'.$langs->trans("Warehouse").'</td>';
        print '<td>';

        if (sizeof($user->entrepots) === 1)
          {
            $uentrepot = array();
            $uentrepot[$user->entrepots[0]['id']] = $user->entrepots[0]['label'];
            $html->select_array("entrepot_id",$uentrepot);
          }
          else
          {
            $html->select_array("entrepot_id",$entrepot->list_array());
          }

          if (sizeof($entrepot->list_array()) <= 0)
        {
            print ' &nbsp; Aucun entrepet definit, <a href="'.DOL_URL_ROOT.'/product/stock/card.php?action=create">definissez en un</a>';
        }
        print '</td></tr>';
        /*
        print '<tr><td width="20%">Mode d\'expedition</td>';
        print '<td>';
        $html->select_array("entrepot_id",$entrepot->list_array());
        print '</td></tr>';
        */

        print '<tr><td align="center" colspan="2">';
        print '<input type="submit" class="button" named="save" value="'.$langs->trans("NewSending").'">';
        print '</td></tr>';

        print "</table>";
        print "</form>\n";

        $somethingshown=1;
    }

    print "</td></tr></table>";

    print '<br>';
    $gsm->show_list_sending_receive('commande',$commande->id);





       /*
        * Documents generes
        */
        include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php');
        $formfile = new FormFile($db);


        $filename=sanitize_string($commande->ref);
        $filedir=$conf->commande->dir_output . "/" . sanitize_string($commande->ref);
        $urlsource=$_SERVER["PHP_SELF"]."?commande_id=".$commandeid;
        $genallowed=$user->rights->commande->creer;
        $delallowed=$user->rights->commande->supprimer;

        $var=true;
//print $urlsource."<BR>";




        $somethingshown=show_documents($db,'commande',$filename,$filedir,$urlsource,$genallowed,$delallowed,$propal->modelpdf);

print "<script type='text/javascript'>";
print "correctSize();";
print "</script>";



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