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
require_once(DOL_DOCUMENT_ROOT."/livraison/mods/modules_livraison.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
if ($conf->produit->enabled) require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/livraison/class/livraison.class.php");
if ($conf->expedition_bon->enabled) require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->stock->enabled) require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
if (!$user->rights->expedition->livraison->lire)
accessforbidden();
 $livraisonid=$_GET['livraison_id'];
if ("x".$livraisonid == "x")
{
    print "no ID provided !!";
    exit(0);
}
if ($user->rights->BabelGSM->BabelGSM_com->AfficheLivraison !=1)
{
   // var_dump($user->rights->JasperBabel);
    llxHeader();
    print "Ce module ne vous est pas accessible";
    exit(0);
}

/*
 * Build document
 */
if ($_REQUEST['action'] == 'builddoc')  // En get ou en post
{
    $delivery = new Livraison($db, 0, $_REQUEST['livraison_id']);
    $delivery->fetch($_REQUEST['livraison_id']);

    if ($_REQUEST['model'])
    {
        $delivery->setDocModel($user, $_REQUEST['model']);
    }

    if ($_REQUEST['lang_id'])
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    $result=delivery_order_pdf_create($db, $_REQUEST['livraison_id'],$_REQUEST['model'],$outputlangs);
    if ($result <= 0)
    {
        dol_print_error($db,$result);
        exit;
    } else {
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
        $interface=new Interfaces($db);
        $result=$interface->run_triggers('ECM_GENLIVRAISON',$delivery,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
        // Fin appel triggers
    }
}


require_once(DOL_DOCUMENT_ROOT."/Babel_GSM/gsm.class.php");

llxHeader("", "Dolibarr Factures", '',$jsFile=array(0=>"Babel_GSM/js/babel_gsm.js"));
$gsm = new gsm($db,$user);




$langs->load("propal");
$langs->load("orders");
$langs->load("bills");
$langs->load("companies");
$langs->load("deliveries");
$langs->load("sendings");
$langs->load("synopsisGene@synopsistools");

//$secondary =  "<SPAN id='mnubut_sec1' class='mnubut_sec' onClick='MenuDisplayCSS_secondary(\"facture_info\",\"livraison_id\",$livraisonid)'>Info</SPAN>\n";
print $gsm->MainMenu($secondary);


$html = new Form($db);
$formfile = new FormFile($db);

$id = $_GET['livraison_id'];

if ($_GET["livraison_id"] > 0)
{
        $livraison = new Livraison($db);
        $result = $livraison->fetch($_GET["livraison_id"]);
        $livraison->fetch_client();

        if ($livraison->origin_id)
        {
            $object = $livraison->origin;
            $livraison->fetch_object();
        }

        if ( $livraison->id > 0)
        {
            $soc = new Societe($db);
            $soc->fetch($livraison->socid);

            $h=0;
//            if ($conf->expedition_bon->enabled)
//            {
//                $head[$h][0] = "expedition_detail.php?expedition_id=".$livraison->expedition_id;
//                $head[$h][1] = $langs->trans("SendingCard");
//                $h++;
//            }

//            $head[$h][0] = DOL_URL_ROOT."/livraison/card.php?id=".$livraison->id;
//            $head[$h][1] = $langs->trans("DeliveryCard");
//            $hselected = $h;
//            $h++;

//            dol_fiche_head($head, $hselected, $langs->trans("Sending"));




            /*
             *   Livraison
             */
            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
            print '<td colspan="3">'.$livraison->ref.'</td></tr>';

            // Client
            print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
            print ''.$gsm->getGSMSocNameUrl($soc,$langs,3);
            print "</tr>";

            // Document origine
            if ($conf->commande->enabled)
            {
                print '<tr><td>'.$langs->trans("RefOrder").'</td>';
                $order=new Commande($db);
                $order->fetch($livraison->origin_id);
                print '<td colspan="3">';

                print "<a href='commande_detail.php?commande_id=".$livraison->origin_id."'>" .img_object("order","order") . "&nbsp;".$order->ref ."</a>";

                print "</td>\n";
                print '</tr>';
            }
            else
            {
                $propal=new Propal($db);
                $propal->fetch($livraison->origin_id);
                print '<tr><td>'.$langs->trans("RefProposal").'</td>';
                print '<td colspan="3">';
                print $propal->getNomUrl(1,'expedition');
                print "</td>\n";
                print '</tr>';
            }

            // Ref client
            print '<tr><td>'.$langs->trans("RefCustomer").'</td>';
            print '<td colspan="3">'.$livraison->ref_client."</a></td>\n";
            print '</tr>';

            // Date
            print '<tr><td>'.$langs->trans("Date").'</td>';
            print '<td colspan="3">'.dol_print_date($livraison->date_creation,'day')."</td>\n";
            print '</tr>';

            // Statut
            print '<tr><td>'.$langs->trans("Status").'</td>';
            print '<td colspan="3">'.$livraison->getLibStatut(4)."</td>\n";
            print '</tr>';

            if (!$conf->expedition_bon->enabled && $conf->stock->enabled)
            {
                // Entrepot
                $entrepot = new Entrepot($db);
                $entrepot->fetch($livraison->entrepot_id);
                print '<tr><td width="20%">'.$langs->trans("Warehouse").'</td>';
                print '<td colspan="3"><a href="stock_detail.php?stock_id='.$entrepot->id.'">'.$entrepot->libelle.'</a></td>';
                print '</tr>';
            }

            print "</table>\n";

            //expedition
            //expedition_id

    print '<br>';
    if ($conf->commande->enabled)
    {
        $gsm->show_list_sending_receive('commande',$livraison->origin_id);
    } else {
        $gsm->show_list_sending_receive('propal',$livraison->origin_id);
    }

            /*
             * Lignes produits
             */
            print '<br><table class="noborder" width="100%">';

            $num_prod = sizeof($livraison->lignes);

            if ($num_prod)
            {
                $i = 0;

                print '<tr class="liste_titre">';
                print '<td>'.$langs->trans("Products").'</td>';
                print '<td align="center">'.$langs->trans("QtyOrdered").'</td>';
                print '<td align="center">'.$langs->trans("QtyReceived").'</td>';
                print "</tr>\n";

                $var=true;
                while ($i < $num_prod)
                {
                    $var=!$var;
                    print "<tr $bc[$var]>";
                    if ($livraison->lignes[$i]->fk_product > 0)
                    {
                        $product = new Product($db);
                        $product->fetch($livraison->lignes[$i]->fk_product);

                        print '<td>';
                        print '<a href="product_detail.php?product_id='.$livraison->lignes[$i]->fk_product.'">'.img_object($langs->trans("ShowProduct"),"product").' '.$product->ref.'</a> - '.$product->libelle;
                        if ($livraison->lignes[$i]->description) print '<br>'.$livraison->lignes[$i]->description;
                        print '</td>';
                    }
                    else
                    {
                        print "<td>".$livraison->lignes[$i]->description."</td>\n";
                    }

                    print '<td align="center">'.$livraison->lignes[$i]->qty_asked.'</td>';
                    print '<td align="center">'.$livraison->lignes[$i]->qty_shipped.'</td>';

                    print "</tr>";

                    $i++;
                }
            }

            print "</table>\n";

            print "\n</div>\n";


                print '</div>';
            }
            print "\n";

            print "<table width=\"100%\" cellspacing=2><tr><td width=\"50%\" valign=\"top\">";

            /*
             * Documents generes
             */

            $livraisonref = sanitize_string($livraison->ref);
            $filedir = $conf->livraison_bon->dir_output . '/' . $livraisonref;
            $urlsource = $_SERVER["PHP_SELF"]."?livraison_id=".$livraison->id;

            $genallowed=$user->rights->expedition->livraison->creer;
            $delallowed=$user->rights->expedition->livraison->supprimer;

            $somethingshown=$formfile->show_documents('livraison',$livraisonref,$filedir,$urlsource,$genallowed,$delallowed,$livraison->modelpdf);

            /*
             * Deja livre
             */
            $sql = "SELECT ld.fk_product, ld.description, ld.qty as qty_shipped, ld.fk_livraison as livraison_id";
            $sql.= ", l.ref, l.date_livraison  as date_livraison";
            $sql.= ", cd.rowid, cd.qty as qty_commande";
            $sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd";
            $sql.= " , ".MAIN_DB_PREFIX."livraisondet as ld, ".MAIN_DB_PREFIX."livraison as l";
            $sql.= " WHERE l.rowid <> ".$livraison->id;
            $sql.= " AND cd.rowid = ld.fk_origin_line";
            $sql.= " AND ld.fk_livraison = l.rowid";
            $sql.= " AND l.fk_statut > 0";
            $sql.= " ORDER BY cd.fk_product";

            $resql = $db->query($sql);
            if ($resql)
            {
                $num = $db->num_rows($resql);
                $i = 0;

                if ($num)
                {
                    print '<br>';

                    load_fiche_titre($langs->trans("OtherSendingsForSameOrder"));
                    print '<table class="liste" width="100%">';
                    print '<tr class="liste_titre">';
                    print '<td align="left">'.$langs->trans("Sending").'</td>';
                    print '<td>'.$langs->trans("Description").'</td>';
                    print '<td align="center">'.$langs->trans("QtyShipped").'</td>';
                    print '<td align="center">'.$langs->trans("Date").'</td>';
                    print "</tr>\n";

                    $var=True;
                    while ($i < $num)
                    {
                        $var=!$var;
                        $objp = $db->fetch_object($resql);
                        print "<tr $bc[$var]>";
                        print '<td align="left"><a href="'.DOL_URL_ROOT.'/livraison/card.php?id='.$objp->livraison_id.'">'.img_object($langs->trans("ShowSending"),'sending').' '.$objp->ref.'<a></td>';
                        if ($objp->fk_product > 0)
                        {
                            $product = new Product($db);
                            $product->fetch($objp->fk_product);

                            print '<td>';
                            print '<a href="'.DOL_URL_ROOT.'/product/card.php?id='.$objp->fk_product.'">'.img_object($langs->trans("ShowProduct"),"product").' '.$product->ref.'</a> - '.$product->libelle;
                            if ($objp->description) print nl2br($objp->description);
                            print '</td>';
                        }
                        else
                        {
                            print "<td>".stripslashes(nl2br($objp->description))."</td>\n";
                        }
                        print '<td align="center">'.$objp->qty_shipped.'</td>';
                        print '<td align="center">'.dol_print_date($db->jdate($objp->date_livraison),"dayhour").'</td>';
                        print '</tr>';
                        $i++;
                    }

                    print '</table>';
                }
                $db->free($resql);
            }
            else
            {
                dol_print_error($db);
            }

            print '</td><td valign="top" width="50%">';

            // Rien a droite

            print '</td></tr></table>';
        }
        else
        {
            /* Expedition non trouvee */
            print "Expedition inexistante ou acc&egrave;s refus&eacute;";
        }

$gsm->jsCorrectSize(true);

$db->close();


?>
