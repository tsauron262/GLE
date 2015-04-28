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

include_once("../master.inc.php");
include_once ("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
llxHeader('',$langs->trans('Fiche PN'),'Fiche PN');
//TODO lie fiche PN a la propal / commande ?/ facture ?

 //Fiche PN

 // 1) Selectionne une propale

 // 2) Affiche le materiel, les software  , les prestation et la maintenance ou autres
 // 3) Affiche le Prix de vente
 // 4) Affiche le prix d'achat
 // 5) Affiche la PN

    $propalId=13;

    $propal = new Propal($db);
    $propal->fetch($propalId);
        /*
    * Lignes de propale
    */
    print '<table class="noborder" width="100%">';

    $sql = 'SELECT pt.rowid, pt.description, pt.fk_product, pt.fk_remise_except,';
    $sql.= ' pt.qty, pt.tva_tx, pt.remise_percent, pt.subprice, pt.info_bits,';
    $sql.= ' pt.total_ht, pt.total_tva, pt.total_ttc, pt.marge_tx, pt.marque_tx, pt.pa_ht, pt.special_code,';
    $sql.= ' p.label as product, p.ref, p.fk_product_type, p.rowid as prodid,';
    $sql.= ' p.description as product_desc';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'propaldet as pt';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pt.fk_product=p.rowid';
    $sql.= ' WHERE pt.fk_propal = '.$propal->id;
    $sql.= '   AND pt.description NOT LIKE "[header]%"';
    $sql.= '   AND pt.description NOT LIKE "[header1]%"';
    $sql.= '   AND pt.description NOT LIKE "[header1InitsTot]%"';
    $sql.= '   AND pt.description NOT LIKE "[nota]%"';
    $sql.= '   AND pt.description NOT LIKE "[sautpage]%"';
    $sql.= '   AND pt.description NOT LIKE "[soustot]%"';
    $sql.= '   AND pt.special_code <> "3"';
    $sql.= ' ORDER BY pt.rang ASC, pt.rowid';
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;

        if ($num)
        {
            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans('Description').'</td>';
            if ($conf->global->PRODUIT_USE_MARKUP)
            {
                print '<td align="right" width="80">'.$langs->trans('Markup').'</td>';
            }
            print '<td align="right" width="80">'.$langs->trans('PriceUHT').'</td>';
            print '<td align="right" width="50">'.$langs->trans('Qty').'</td>';
            print '<td align="right" width="50">'.$langs->trans('AmountHT').'</td>';
            print "</tr>\n";
        }
        $var=true;
        while ($i < $num)
        {
            $objp = $db->fetch_object($resql);
            $var=!$var;

            // Ligne en mode visu
            if ($_GET['action'] != 'editline' || $_GET['lineid'] != $objp->rowid)
            {
                print '<tr '.$bc[$var].'>';

                // Produit
                if ($objp->fk_product > 0)
                {
                    print '<td>';
                    print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne;

                    // Affiche ligne produit
                    $text = '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
                    if ($objp->fk_product_type==1) $text.= img_object($langs->trans('ShowService'),'service');
                    else $text.= img_object($langs->trans('ShowProduct'),'product');
                    $text.= ' '.$objp->ref.'</a>';
                    $text.= ' - '.$objp->product;
                    $description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($objp->description));
                    print $html->textwithtooltip($text,$description,3,'','',$i);
                    print_date_range($objp->date_start,$objp->date_end);
                    if ($conf->global->PRODUIT_DESC_IN_FORM)
                    {
                        print ($objp->description && $objp->description!=$objp->product)?'<br>'.dol_htmlentitiesbr($objp->description):'';
                    }

                    print '</td>';
                }
                else
                {
                    print '<td>';
                    print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
                    if (($objp->info_bits & 2) == 2)
                    {
                        print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$propal->socid.'">';
                        print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
                        print '</a>';
                        if ($objp->description)
                        {
                            if ($objp->description == '(CREDIT_NOTE)')
                            {
                                $discount=new DiscountAbsolute($db);
                                $discount->fetch($objp->fk_remise_except);
                                print ' - '.$langs->transnoentities("DiscountFromCreditNote",$discount->getNomUrl(0));
                            }
                            else
                            {
                                print ' - '.nl2br($objp->description);
                            }
                        }
                    }
                    else
                    {
                        print nl2br(preg_replace('/^\[[\w]*\]/','',$objp->description));
                        print_date_range($objp->date_start,$objp->date_end);
                    }
                    print "</td>\n";
                }
                $isHeader=false;
                $isHeader1=false;
                $isHeader1stot=false;
                $isSautPage =false;
                $isNota = false;
                $isSousTot=false;
                $isDesc=false;
                $isDesc=true;


                if ($conf->global->PRODUIT_USE_MARKUP && $conf->use_javascript_ajax)
                {
                    $formMarkup = '<form id="formMarkup" action="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'" method="post">'."\n";
                    $formMarkup.= '<table class="border" width="100%">'."\n";
                    if ($objp->fk_product > 0)
                    {
                        $formMarkup.= '<tr><td align="left" colspan="2">&nbsp;</td></tr>'."\n";
                        $formMarkup.= '<tr><td align="left" width="25%" height="19">&nbsp;'.$langs->trans('SupplierPrice').'</td>'."\n";
                        $formMarkup.= '<td align="left">'.$html->select_product_fourn_price($objp->fk_product,'productfournpriceid').'</td></tr>'."\n";
                    }
                    $formMarkup.= '<tr><td align="left" colspan="2">&nbsp;</td></tr>'."\n";
                    $formMarkup.= '<tr><td align="left" width="25%" height="19">&nbsp;'.$langs->trans('PurchasePrice').' '.$langs->trans('HT').'</td>'."\n";
                    $formMarkup.= '<td align="left"><input size="10" type="text" class="flat" name="purchaseprice_ht" value=""></td></tr>'."\n";
                    $formMarkup.= '<tr><td align="left" width="25%" height="19">&nbsp;'.$langs->trans('MarkupRate').'</td>'."\n";
                    $formMarkup.= '<td><input size="10" type="text" class="flat" id="markuprate'.$i.'" name="markuprate'.$i.'" value=""></td></tr>'."\n";
                    $formMarkup.= '<tr><td align="left" width="25%" height="19">&nbsp;'.$langs->trans('SellingPrice').' '.$langs->trans('HT').'</td>'."\n";
                    //$formMarkup.= '<td><div id="sellingprice_ht'.$i.'"><input size="10" type="text" class="flat" id="sellingdata_ht'.$i.'" name="sellingdata_ht'.$i.'" value=""></div></td></tr>'."\n";
                    $formMarkup.= '<td nowrap="nowrap"><div id="sellingprice_ht'.$i.'"><div></td></tr>'."\n";
                    $formMarkup.= '<tr><td align="left" width="25%" height="19">&nbsp;'.$langs->trans('CashFlow').' '.$langs->trans('HT').'</td>'."\n";
                    $formMarkup.= '<td nowrap="nowrap"><div id="cashflow'.$i.'"></div></td></tr>'."\n";
                    $formMarkup.= '<tr><td align="center" colspan="2">'."\n";
                    $formMarkup.= '<input type="submit" class="button" name="validate" value="'.$langs->trans('Validate').'">'."\n";
                    //$formMarkup.= ' &nbsp; <input onClick="Dialog.closeInfo()" type="button" class="button" name="cancel" value="'.$langs->trans('Cancel').'">'."\n";
                    $formMarkup.= '</td></tr></table></form>'."\n";
                    $formMarkup.= ajax_updaterWithID("rate".$i,"markup","sellingprice_ht".$i,"/product/ajaxproducts.php","&count=".$i,"working")."\n";


                    print '<td align="right">'."\n";

                    print '<div id="calc_markup'.$i.'" style="display:none">'."\n";
                    print $formMarkup."\n";
                    print '</div>'."\n";


                    print '<table class="nobordernopadding" width="100%"><tr class="nocellnopadd">';
                    print '<td class="nobordernopadding" nowrap="nowrap" align="left">';
                    if (($objp->info_bits & 2) == 2)
                    {
                        // Ligne remise predefinie, on ne permet pas modif
                    }
                    else
                    {
                        $picto = '<a href="#" onClick="dialogWindow($(\'calc_markup'.$i.'\').innerHTML,\''.$langs->trans('ToCalculateMarkup').'\')">';
                        $picto.= img_calc();
                        $picto.= '</a>';
                        print $html->textwithtooltip($picto,$langs->trans("ToCalculateMarkup"),3,'','',$i);
                    }
                    print '</td>';
                    print '<td class="nobordernopadding" nowrap="nowrap" align="right">'.vatrate($objp->marge_tx).'% </td>';
                    print '</tr></table>';
                    print '</td>';
                }


                // U.P HT
                print '<td align="right" nowrap="nowrap">'.price($objp->subprice)."</td>\n";

                // Qty
                print '<td align="right" nowrap="nowrap">';
#                if ((($objp->info_bits & 2) != 2) && $objp->special_code != 3)
                if ((($objp->info_bits & 2) != 2))
                {
                    print $objp->qty;
                }
                else print '&nbsp;';
                print '</td>';


                // Montant total HT
//                if ($objp->special_code == 3)
//                {
//                    // Si ligne en option
//                    print '<td align="right" nowrap="nowrap">'.$langs->trans('Option').'</td>';
//                }
//                else
//                {

                    print '<td align="right" nowrap="nowrap">'.price($objp->total_ht)."</td>\n";
//                }



                print '</tr>';
            }

            // Ligne en mode update
            if ($propal->statut == 0 && $_GET["action"] == 'editline' && $user->rights->propale->creer && $_GET["lineid"] == $objp->rowid)
            {
                print '<form action="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'#'.$objp->rowid.'" method="post">';
                print '<input type="hidden" name="action" value="updateligne">';
                print '<input type="hidden" name="propalid" value="'.$propal->id.'">';
                print '<input type="hidden" name="lineid" value="'.$_GET["lineid"].'">';
                print '<tr '.$bc[$var].'>';
                print '<td>';
                print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
                if ($objp->fk_product > 0)
                {
                    print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
                    if ($objp->fk_product_type==1) print img_object($langs->trans('ShowService'),'service');
                    else print img_object($langs->trans('ShowProduct'),'product');
                    print ' '.$objp->ref.'</a>';
                    print ' - '.nl2br($objp->product);
                    print '<br>';
                }
                if ($_GET["action"] == 'editline')
                {
                    // editeur wysiwyg
                    if (isset($conf->fckeditor->enabled) && $conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
                    {
                        require_once(DOL_DOCUMENT_ROOT."/core/lib/doleditor.class.php");
                        $doleditor=new DolEditor('desc',$objp->description,164,'dolibarr_details');
                        $doleditor->Create();
                    }
                    else
                    {
                        print '<textarea name="desc" cols="70" class="flat" rows="'.ROWS_2.'">'.dol_htmlentitiesbr_decode(preg_replace('/^\[[\w]*\]/','',$objp->description)).'</textarea>';
                    }
                }
                print '</td>';
                if ($conf->global->PRODUIT_USE_MARKUP)
                {
                    print '<td align="right">'.vatrate($objp->marge_tx).'%</td>';
                }

                $isHeader=false;
                $isHeader1=false;
                $isHeader1stot=false;
                $isSautPage =false;
                $isNota = false;
                $isSousTot=false;
                $isDesc=false;

                if (preg_match('/^\[header\]/',$objp->description))
                {
                    $isHeader = true;
                } else if (preg_match('/^\[header1\]/',$objp->description))
                {
                    $isHeader1 = true;
                } else if (preg_match('/^\[header1stot\]/',$objp->description))
                {
                    $isHeader1stot=true;
                }else if (preg_match('/^\[sautpage\]/',$objp->description))
                {
                    $isSautPage=true;
                }else if (preg_match('/^\[nota\]/',$objp->description))
                {
                    $isNota=true;
                }else if (preg_match('/^\[soustot\]/',$objp->description))
                {
                    $isSousTot=true;
                }else {
                    $isDesc=true;
                }

                print '<td width="50">';
                    print '<SELECT name="type_ligne">';
                    if ($isHeader)
                    {
                        print '<OPTION SELECTED value="header">header</OPTION>';
                    } else {
                        print '<OPTION value="header">header</OPTION>';
                    }
                    if ($isHeader1)
                    {
                        print '<OPTION SELECTED value="header1">header1</OPTION>';
                    } else {
                        print '<OPTION value="header1">header1</OPTION>';
                    }
                    if ($isHeader1stot)
                    {
                        print '<OPTION SELECTED value="header1stot">header1stot</OPTION>';
                    } else {
                        print '<OPTION value="header1stot">header1stot</OPTION>';
                    }
                    if ($isNota)
                    {
                        print '<OPTION SELECTED value="nota">Note</OPTION>';
                    } else {
                        print '<OPTION value="nota">Note</OPTION>';
                    }
                    if ($isSautPage)
                    {
                        print '<OPTION SELECTED value="sautpage">Saut de page</OPTION>';
                    } else {
                        print '<OPTION value="sautpage">Saut de page</OPTION>';
                    }
                    if ($isSousTot)
                    {
                        print '<OPTION SELECTED value="soustot">Sous-total</OPTION>';
                    } else {
                        print '<OPTION value="soustot">Sous-total</OPTION>';
                    }
                    if ($isDesc)
                    {
                        print '<OPTION SELECTED value="desc">description</OPTION>';
                    } else {
                        print '<OPTION value="desc">description</OPTION>';
                    }
                    print '</SELECT>';
                    print '</td>';
                print '<td align="right"><input size="6" type="text" class="flat" name="subprice" value="'.price($objp->subprice,0,'',0).'"></td>';
                print '<td align="right">';
                if (($objp->info_bits & 2) != 2)
                {
                    print '<input size="2" type="text" class="flat" name="qty" value="'.$objp->qty.'">';
                }
                else print '&nbsp;';
                print '</td>';
                print '<td align="right" nowrap>';
                if (($objp->info_bits & 2) != 2)
                {
                    print '<input size="1" type="text" class="flat" name="remise_percent" value="'.$objp->remise_percent.'">%';
                }
                else print '&nbsp;';
                print '</td>';
                //option
                if  ($objp->special_code == 3){
                    $checked = "CHECKED";
                }
                print '<td align="center"><input  name="isOption" '.$checked.' type="checkbox"></input></td>';
                print '<td align="center" colspan="5" valign="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
                print '<br /><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td>';
                print '</tr>' . "\n";
                /*
                if ($conf->service->enabled)
                {
                print "<tr $bc[$var]>";
                print '<td colspan="5">Si produit de type service a duree limitee: Du ';
                print $html->select_date($objp->date_start,"date_start",0,0,$objp->date_start?0:1);
                print ' au ';
                print $html->select_date($objp->date_end,"date_end",0,0,$objp->date_end?0:1);
                print '</td>';
                print '</tr>' . "\n";
                }
                */
                print "</form>\n";
            }

            $i++;
        }
    }

 // 6) lie avec le fournisseur pour les produits
 // 7) Etape de validation
 // 8) Ligne globale
?>
