<?php
/*
 * GLE by Synopsis & DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.synopsis-erp.com
 *
 */
    include_once ("../master.inc.php");
    include_once ("./pre.inc.php");

    //Limit

require ("./main.inc.php");

require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/html.form.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");


require_once(DOL_DOCUMENT_ROOT."/core/modules/propale/modules_propale.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/order.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/sendings.lib.php");


if ($user->rights->BabelGSM->BabelGSM_com->AfficheCommande !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}

$html = new Form($db);
$formfile = new FormFile($db);

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
        include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
        $interface=new Interfaces($db);
        $result=$interface->run_triggers('ECM_GENCOMMANDE',$commande,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
        // Fin appel triggers
        Header ('Location: '.$_SERVER["PHP_SELF"].'?commande_id='.$commande->id.'');
        exit;
    }
}


if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/project.class.php');
require_once(DOL_DOCUMENT_ROOT.'/actioncomm.class.php');


$langs->load("propal");
$langs->load("deliveries");
$langs->load('synopsisGene@synopsistools');
$langs->load('companies');
$langs->load('compta');
$langs->load('bills');
$langs->load('orders');
$langs->load('sendings');
$langs->load('products');


$commandeid=$_GET['commande_id'];
if ("x".$commandeid == "x")
{
    print "no ID provided !!";
    exit(0);
}

require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");

llxHeader("", "Dolibarr Propales", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);



$secondary =  "<SPAN id='mnubut_sec' class='mnubut_sec' onClick='MenuDisplayCSS_secondary(\"commande_detail\",\"commande_id\",$commandeid)'>D&eacute;tails</SPAN>\n";
print $gsm->MainMenu($secondary);

//print "</DIV>";


if ($_GET["commande_id"] > 0)
{
    $commande = New Commande($db);
  if ( $commande->fetch($_GET["commande_id"]) > 0)
  {
    $commande->loadExpeditions(1);

    $soc = new Societe($db);
    $soc->fetch($commande->socid);

    $author = new User($db);
    $author->fetch($commande->user_author_id);

//        $head = commande_prepare_head($commande);
//    dolibarr_fiche_head($head, 'shipping', $langs->trans("CustomerOrder"));

    // Onglet commande
    $nbrow=8;
    if ($conf->projet->enabled) $nbrow++;

    print '<table class="border" >';

    // Ref
    print '<tr><td>'.$langs->trans('Ref').'</td>';
    print '<td colspan="3">'.$commande->ref.'</td>';
    print '</tr>';

    // Ref commande client
    print '<tr><td>';
    print $langs->trans('RefCustomer').'</td>';
    print '';
    print '<td colspan="3">';
    print $commande->ref_client;
      print '</td>';
      print '</tr>';

      // Societe
      print '<tr><td>'.$langs->trans('Company').'</td>';
      print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
      print '</tr>';

      // Date
      print '<tr><td>'.$langs->trans('Date').'</td>';
      print '<td colspan="2">'.dol_print_date($commande->date,'daytext').'</td>';
      print '</tr>';

      // Date de livraison
      print '<tr><td>';
      print $langs->trans('DeliveryDate');
      print '</td>';

      print '<td colspan="2">';
        print dol_print_date($commande->date_livraison,'daytext');
      print '</td></TR>';

      print '<TR><td>'.$langs->trans('NotePublic').' :<br></td><td colspan="2">';
      print nl2br($commande->note_public);
      print '</td>';
      print '</tr>';

      // Adresse de livraison
      print '<tr><td>';
      print $langs->trans('DeliveryAddress');
      print '</td>';

      print '</td><td colspan="2">';
//        $html->form_adresse_livraison($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->adresse_livraison_id,$_GET['socid'],'none','commande',$commande->id);
      print getAdresseLivraisonComm($commande->id);
      print '</td></tr>';

      // Conditions et modes de reglement
        print '<tr><td>';
        print $langs->trans('PaymentConditionsShort');
        print '</td>';
        print '</td><td colspan="2">';
            $html->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->cond_reglement_id,'none');
        print '</td></tr>';
        print '<tr><td>';
        print $langs->trans('PaymentMode');
        print '</td>';
        print '</td><td colspan="2">';
            $html->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->mode_reglement_id,'none');
        print '</td></tr>';

        // Projet
        if ($conf->projet->enabled)
        {
            $langs->load('projects');
              print '<tr><td>';
              print $langs->trans('Project');
              print '</td>';
              print '</td><td colspan="2">';
                $html->form_project($_SERVER['PHP_SELF'].'?id='.$commande->id, $commande->socid, $commande->projet_id, 'none');
              print '</td></tr>';
        }

        // Lignes de 3 colonnes

    // Total HT
        print '<tr><td>'.$langs->trans('AmountHT').'</td>';
        print '<td align="right"><b>'.price($commande->total_ht).'</b></td>';
        print '<td>'.$langs->trans('Currency'.$conf->global->MAIN_MONNAIE).'</td></tr>';

        // Total TVA
        print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right">'.price($commande->total_tva).'</td>';
        print '<td>'.$langs->trans('Currency'.$conf->global->MAIN_MONNAIE).'</td></tr>';

        // Total TTC
        print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right">'.price($commande->total_ttc).'</td>';
        print '<td>'.$langs->trans('Currency'.$conf->global->MAIN_MONNAIE).'</td></tr>';

        // Statut
        print '<tr><td>'.$langs->trans('Status').'</td>';
        print '<td colspan="2">'.$commande->getLibStatut(4).'</td>';
        print '</tr>';

        print '</table><br>';


    /**
     *  Lignes de commandes avec quantite livrees et reste e livrer
     *  Les quantites livrees sont stockees dans $commande->expeditions[fk_product]
     */
    load_fiche_titre($langs->trans("Sending"));
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

        print '<TR '.$bc[$var].'><td align="center">'.$objp->qty.'</td>';

        print '<td align="center">';
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
    else
    {
        dol_print_error($db);
    }


    print '<br>';
    $gsm->show_list_sending_receive('commande',$commande->id);


    }
    else
    {
        /* Commande non trouvee */
        print "Commande inexistante";
    }
}



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
                    //Est ce qu'il y a des factures rattachees e� la commande
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




       /*
        * Documents generes
        */
        include_once(DOL_DOCUMENT_ROOT.'/html.formfile.class.php');
        $formfile = new FormFile($db);


        $filename=sanitize_string($commande->ref);
        $filedir=$conf->commande->dir_output . "/" . sanitize_string($commande->ref);
        $urlsource=$_SERVER["PHP_SELF"]."?commande_id=".$commandeid;
        $genallowed=$user->rights->commande->creer;
        $delallowed=$user->rights->commande->supprimer;

        $var=true;
//print $urlsource."<BR>";




        $somethingshown=show_documents($db,'commande',$filename,$filedir,$urlsource,$genallowed,$delallowed,$propal->modelpdf);



$gsm->jsCorrectSize(true);

function show_documents($db,$modulepart,$filename,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$modelliste=array(),$forcenomultilang=0,$iconPDF=0,$maxfilenamelength=28)
{
        // filedir = conf->...dir_ouput."/".get_exdir(id)
        include_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');

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

            print '<form style="width:100%; max-width:100%; min-width:100%" action="'.$urlsource.'#builddoc" method="post">';
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
        } // end function

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