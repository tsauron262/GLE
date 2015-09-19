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
class gsm
{

    private $db;

    function gsm($db,$user)
    {
        $this->db=$db;
        $this->user=$user;
    }
    function MainInit($print=true,$secondary=false)
    {
        if ($print)
        {
            print $this->MainMenu($secondary);
        }
    }
    function MainMenu($secondary=false)
    {
        global $langs;
        $styleTmenu = "display: block;
                        width:100%;
                        min-width:100%;
                        border:1px Solid #121212;
                        min-height: 22px;
                        height: 22px;
                        max-height: 22px;
                        white-space: nowrap;
                        border-left: 0px;
                        padding: 0px 0px 0px 0px;
                        margin: 0px 0px 0px 0px;
                        font-size: 12px;
                        background-image : url(\"".DOL_URL_ROOT."/theme/auguria/img/nav.jpg\") ;
                        background-repeat : repeat-x ;
                        background-position : top left ;
                     ";
        $html =      "<table name='forceresize' style='padding:0px 0px 0px 0px; margin:0px 3px 0px 0px; border-collapse: collapse;'><tr><td><div class='tmenugsm' style='".$styleTmenu."'>
                      <table name='forceresize' style='padding:0px 0px 0px 0px; margin:0px 3px 0px 0px; border-collapse: collapse;'>
                        <tr style='height: 100%;'><td width=40><div  class='butActionGsm'  onMouseOver='MenuDisplayCSS()'>Menu</div></td>";
        if ($secondary)
        {
            $html = $html . " " . $secondary;
        }
        $html .= "<td>&nbsp;</td>";
        $dec = "<td align='right' width=40px;><div id='logoutGSM' class='butActionGsm' style='position: relative; border :0px;' ><a href='".DOL_URL_ROOT."/user/logout.php'>".$langs->Trans('Logout')."</a></div></td></tr></table>";
        $html .= $dec;
        $user = $this->user;
        $html .= "</DIV></td></tr></table>\n";
        $html .= "   <DIV id='menuDiv' class='menuDiv' style='min-height: 515px;height: 515px;'>";
        $html .= "   <UL><LI onClick='DisplayDet(\"index\")' class='menu'>".img_picto("Accueil","info")."Accueil";
        if ($user->rights->BabelGSM->BabelGSM_com->AfficheClient || $user->rights->BabelGSM->BabelGSM_fourn->AfficheFourn  || $user->rights->BabelGSM->BabelGSM_com->AfficheProspect  || $user->rights->BabelGSM->BabelGSM_com->AfficheContact )
        {
            $html .= "       <LI class='menuSep'>Tiers"; // BabelGSM_com.AfficheClient + BabelGSM_fourn.AfficheFourn + BabelGSM_com.AfficheProspect + BabelGSM_com.AfficheContact
            if ($user->rights->BabelGSM->BabelGSM_com->AfficheClient )
            {
                $html .= "       <LI onClick='DisplayDet(\"client\")' class='menu'>".img_object("company","company")."Client";
            }
            if ($user->rights->BabelGSM->BabelGSM_fourn->AfficheFourn )
            {
                $html .= "       <LI onClick='DisplayDet(\"fournisseur\")' class='menu'>".img_object("company","company")."Fournisseur";
            }
            if ($user->rights->BabelGSM->BabelGSM_com->AfficheProspect )
            {
                $html .= "       <LI onClick='DisplayDet(\"prospect\")' class='menu'>".img_object("company","company")."Proscpect";
            }
            if ($user->rights->BabelGSM->BabelGSM_com->AfficheContact )
            {
                $html .= "       <LI onClick='DisplayDet(\"contact\")' class='menu'>".img_object("contact","contact")."Contact";
            }

        }
        if ($user->rights->BabelGSM->BabelGSM_com->AffichePropal ||
            $user->rights->BabelGSM->BabelGSM_com->AfficheCommande ||
            $user->rights->BabelGSM->BabelGSM_com->AfficheIntervention ||
            $user->rights->BabelGSM->BabelGSM_com->AfficheContrat ||
            $user->rights->BabelGSM->BabelGSM_fourn->AfficheCommandeFourn)
        {
            $html .= "       <LI class='menuSep'>Commercial";// BabelGSM_com.AffichePropal BabelGSM_com.AfficheCommande BabelGSM_com.AfficheIntervention BabelGSM_com.AfficheContrat BabelGSM_fourn.AfficheCommandeFourn
            if ($user->rights->BabelGSM->BabelGSM_com->AffichePropal )
            {
                $html .= "       <LI onClick='DisplayDet(\"propal\")' class='menu'>".img_object("propal","propal")."Propal";
            }
            if ($user->rights->BabelGSM->BabelGSM_com->AfficheCommande )
            {
                $html .= "       <LI onClick='DisplayDet(\"commande\")' class='menu'>".img_object("order","order")."Commande";
            }
            if ($user->rights->BabelGSM->BabelGSM_tech->AfficheIntervention )
            {
                $html .= "       <LI onClick='DisplayDet(\"intervention\")' class='menu'>".img_object("intervention","intervention")."Fiche intervention";
                $html .= "       <LI onClick='DisplayDet(\"synopsisdemandeintervention\")' class='menu'>".img_object("intervention","intervention")."Demande intervention";
            }
            if ($user->rights->BabelGSM->BabelGSM_com->AfficheContrat )
            {
                $html .= "       <LI onClick='DisplayDet(\"contrat\")' class='menu'>".img_object("contract","contract")."Contrats";
            }
            if ($user->rights->BabelGSM->BabelGSM_fourn->AfficheCommandeFourn )
            {
                $html .= "       <LI onClick='DisplayDet(\"commandeFourn\")' class='menu'>".img_object("order","order")."Commande fournisseur";
            }
        }
        if ($user->rights->BabelGSM->BabelGSM_com->AfficheExpedition ||
            $user->rights->BabelGSM->BabelGSM_com->AfficheLivraison)
        {
            $html .= "       <LI class='menuSep'>Livraison"; // BabelGSM_com.AfficheLivraison + BabelGSM_com.AfficheExpedition
            if ($user->rights->BabelGSM->BabelGSM_com->AfficheExpedition )
            {
                $html .= "       <LI onClick='DisplayDet(\"expedition\")' class='menu'>".img_object("sending","sending")."Expedition";
            }
            if ($user->rights->BabelGSM->BabelGSM_com->AfficheLivraison)
            {
                $html .= "       <LI onClick='DisplayDet(\"livraison\")' class='menu'>".img_object("trip","trip")."Bon de livraison";
            }
        }
        if ($user->rights->BabelGSM->BabelGSM_com->AfficheProduit ||
            $user->rights->BabelGSM->BabelGSM_com->AfficheServices ||
            $user->rights->BabelGSM->BabelGSM_com->AfficheStock)
        {
            $html .= "       <LI class='menuSep'>Produits"; // BabelGSM_com.AfficheStock +   BabelGSM_com.AfficheServices + BabelGSM_com.AfficheProduit
            if ($user->rights->BabelGSM->BabelGSM_com->AfficheStock)
            {
                $html .= "       <LI onClick='DisplayDet(\"stock\")' class='menu'>".img_object("stock","stock")."Stock";
            }
            if ($user->rights->BabelGSM->BabelGSM_com->AfficheProduit)
            {
                $html .= "       <LI onClick='DisplayDet(\"produit\")' class='menu'>".img_object("product","product")."Produits";
            }
            if ($user->rights->BabelGSM->BabelGSM_com->AfficheServices)
            {
                $html .= "       <LI onClick='DisplayDet(\"service\")' class='menu'>".img_object("service","service")."Services";
            }
        }
        if ($user->rights->BabelGSM->BabelGSM->AfficheDocuments ||
            $user->rights->BabelGSM->BabelGSM_com->AfficheProjet)
        {
            $html .= "       <LI class='menuSep'>Documents"; //BabelGSM.AfficheDocuments + BabelGSM_com.AfficheProjet
            if ($user->rights->BabelGSM->BabelGSM->AfficheDocuments)
            {
                $html .= "       <LI onClick='DisplayDet(\"documents\")' class='menu'>".img_object("book","book")."Documents";
            }
            if ($user->rights->BabelGSM->BabelGSM_com->AfficheProjet)
            {
                $html .= "       <LI onClick='DisplayDet(\"project\")' class='menu'>".img_object("project","project")."Project";
            }
        }

        if ($user->rights->BabelGSM->BabelGSM_ctrlGest->AfficheFacture ||
            $user->rights->BabelGSM->BabelGSM_ctrlGest->AffichePaiement)
        {
            $html .= "       <LI class='menuSep'>Compta"; // BabelGSM_ctrlGest.AffichePaiement + BabelGSM_ctrlGest.AfficheFacture
            if ($user->rights->BabelGSM->BabelGSM_ctrlGest->AfficheFacture)
            {
                $html .= "       <LI onClick='DisplayDet(\"facture\")' class='menu'>".img_object("bill","bill")."Facture";
            }
            if ($user->rights->BabelGSM->BabelGSM_ctrlGest->AffichePaiement)
            {
                $html .= "       <LI onClick='DisplayDet(\"paiement\")' class='menu'>".img_object("payment","payment")."Paiement";
            }
        }

        if ($user->rights->BabelGSM->BabelGSM_com->AfficheBI){
            $html .= "       <LI class='menuSep'>BI"; // BabelGSM_ctrlGest.AfficheBI
            $html .= "       <LI onClick='DisplayDet(\"bi\")' class='menu'>".Babel_img_crystal("bi","apps/kchart")."BI";
        }

        $html .= "   </DIV></div>\n";
        return($html);
    }
    function jsCorrectSize($print =false)
    {
        $html = "<script type='text/javascript'>";
        $html .= "correctSize();";
        $html .=  "</script>";
        if ($print)
        {
            print $html;
        }
        return($html);


    }
    function show_list_sending_receive($origin='commande',$origin_id,$filter='')
    {
        global $db, $conf, $langs, $bc;

        $sql = "SELECT obj.rowid, obj.fk_product, obj.description, obj.qty as qty_asked";
        $sql.= ", ed.qty as qty_shipped, ed.fk_expedition as expedition_id";
        $sql.= ", e.ref, e.date_expedition as date_expedition";
        if ($conf->livraison_bon->enabled) $sql .= ", l.rowid as livraison_id, l.ref as livraison_ref";
        $sql.= " FROM ".MAIN_DB_PREFIX.$origin."det as obj";
        $sql.= " , ".MAIN_DB_PREFIX."expeditiondet as ed, ".MAIN_DB_PREFIX."expedition as e";
        if ($conf->livraison_bon->enabled) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."livraison as l ON l.fk_expedition = e.rowid";
        $sql.= " WHERE obj.fk_".$origin." = ".$origin_id;
        if ($filter) $sql.=$filter;
        $sql.= " AND obj.rowid = ed.fk_origin_line";
        $sql.= " AND ed.fk_expedition = e.rowid";
        $sql.= " ORDER BY obj.fk_product";

        dol_syslog("show_list_sending_receive sql=".$sql, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;

            if ($num)
            {
                print '<br>';

                if ($filter) load_fiche_titre($langs->trans("OtherSendingsForSameOrder"));
                else load_fiche_titre($langs->trans("SendingsAndReceivingForSameOrder"));

                print '<table class="liste" width="100%">';
                print '<tr class="liste_titre">';
                print '<td align="left">'.$langs->trans("Ref").'</td>';
                print '<td>'.$langs->trans("Description").'</td>';
                print '<td align="center">'.$langs->trans("Qty").'</td>';
                print '<td align="center">'.$langs->trans("BabelDateSending").'</td>';
                print "</tr>\n";

                $var=True;
                while ($i < $num)
                {
                    $var=!$var;
                    $objp = $db->fetch_object($resql);
                    print "<tr $bc[$var]>";
                    print '<td align="left" ><a href="expedition_detail.php?expedition_id='.$objp->expedition_id.'">'.img_object($langs->trans("ShowSending"),'sending').' '.$objp->ref.'<a></td>';
                    if ($objp->fk_product > 0)
                    {
                        $product = new Product($db);
                        $product->fetch($objp->fk_product);

                        print '<td>';
                        print '<a href="product_detail.php?product_id='.$objp->fk_product.'">'.img_object($langs->trans("ShowProduct"),"product").' '.$product->ref.'</a> - '.dol_trunc($product->libelle,20);
                        if ($objp->description) print dol_htmlentitiesbr(dol_trunc($objp->description,24));
                        print '</td>';
                    }
                    else
                    {
                        print "<td>".dol_htmlentitiesbr(dol_trunc($objp->description,24))."</td>\n";
                    }
                    print '<td align="center">'.$objp->qty_shipped.'</td>';
                    print '<td align="center" >'.dol_print_date($db->jdate($objp->date_expedition),'day').'</td></tr>';
                    if ($conf->expedition_bon->enabled)
                    {
                        if ($conf->livraison_bon->enabled)
                        {
                            print '<tr  class="liste_titre" style="font-size: 8pt;"><td colspan=2>'.$langs->trans("SendingSheet").'</td>';
                        } else {
                            print '<tr  class="liste_titre" style="font-size: 8pt;"><td colspan=4>'.$langs->trans("SendingSheet").'</td></tr>';
                        }

                    }
                    if ($conf->livraison_bon->enabled)
                    {
                        if ($conf->expedition_bon->enabled)
                        {
                            print '<td colspan=2>'.$langs->trans("DeliveryOrder").'</td></tr>';
                        } else {
                            print '<tr  class="liste_titre" style="font-size: 8pt;"><td colspan=4>'.$langs->trans("DeliveryOrder").'</td></tr>';
                        }

                    }

                    if ($conf->expedition_bon->enabled)
                    {
                        if ($conf->livraison_bon->enabled)
                        {
                            print "<tr  $bc[$var]>";
                            print '<td align="left" colspan=2><a href="expedition_detail.php?expedition_id='.$objp->expedition_id.'">'.img_object($langs->trans("ShowSending"),'sending').' '.$objp->ref.'<a></td>';
                        } else {
                            print "<tr  $bc[$var]>";
                            print '<td align="left" colspan=4 ><a href="expedition_detail.php?expedition_id='.$objp->expedition_id.'">'.img_object($langs->trans("ShowSending"),'sending').' '.$objp->ref.'<a></td></tr>';
                        }
                    }
                    if ($conf->livraison_bon->enabled)
                    {
                        if ($conf->expedition_bon->enabled)
                        {
                            if ($objp->livraison_id)
                            {
                                print '<td colspan=2><a href="/livraison_detail.php?livraison_id='.$objp->livraison_id.'">'.img_object($langs->trans("ShowSending"),'generic').' '.$objp->livraison_ref.'<a></td>';
                            }
                            else
                            {
                                print '<td colspan=2>&nbsp;</td>';
                            }
                        } else {
                            print "<tr  $bc[$var]>";
                            if ($objp->livraison_id)
                            {
                                print '<td colspan=4><a href="livraison_detail.php?livraison_id='.$objp->livraison_id.'">'.img_object($langs->trans("ShowSending"),'generic').' '.$objp->livraison_ref.'<a></td>';
                            }
                            else
                            {
                                print '<td colspan=4>&nbsp;</td>';
                            }
                        }
                    }
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

        return 1;
    }

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

    function getContactUrl($id,$withpicto=0,$option='',$maxlen=0)
    {
        global $langs;

        $result='';

        $lien = '<a href="contact_detail.php?contact_id='.$id.'">';
        $lienfin='</a>';

        if ($option == 'xxx')
        {
            $lien = '<a href="contact_detail.php?contact_id='.$id.'">';
            $lienfin='</a>';
        }

        if ($withpicto) $result.=($lien.img_object($langs->trans("ShowContact").': '.$this->getContactFullName($langs,$id),'contact').$lienfin.' ');
        $result.=$lien.($maxlen?dol_trunc($this->getFullName($langs,$id),$maxlen):$this->getContactFullName($langs,$id)).$lienfin;
        return $result;
    }

    function getContactFullName($langs,$id,$option=0,$nameorder=0)
    {
        require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
        $cont = new Contact($this->db);
        $cont->fetch($id);
        $ret='';
        if ($option && $cont->civility_id)
        {
            if ($langs->transnoentities("Civility".$cont->civility_id)!="Civility".$cont->civility_id) $ret.=$langs->transnoentities("Civility".$cont->civility_id).' ';
            else $ret.=$cont->civility_id.' ';
        }

        if ($nameorder)
        {
            if ($cont->firstname) $ret.=$cont->firstname.' ';
            if ($cont->name)      $ret.=$cont->name.' ';
        }
        else
        {
            if ($cont->name)      $ret.=$cont->name.' ';
            if ($cont->firstname) $ret.=$cont->firstname.' ';
        }
        return trim($ret);
    }

    function show_contacts($conf,$langs,$db,$objsoc)
    {
        $user=$this->user;
    global $user;
    global $bc;

    $contactstatic = new Contact($db);


    load_fiche_titre($langs->trans("ContactsForCompany"));
    print '<table class="noborder" width="100%">';

    print '<tr class="liste_titre"><td>'.$langs->trans("Name").'</td>';
    print '<td>'.$langs->trans("Tel").'</td>';
    print '<td>'.$langs->trans("EMail").'</td>';

    print "</tr>";

    $sql = "SELECT p.rowid, p.name, p.firstname, p.poste, p.phone, p.fax, p.email, p.note ";
    $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
    $sql .= " WHERE p.fk_soc = ".$objsoc->id;
    $sql .= " ORDER by p.datec";

    $result = $db->query($sql);
    $i = 0;
    $num = $db->num_rows($result);
    $var=true;

    if ($num)
    {
        while ($i < $num)
        {
            $obj = $db->fetch_object($result);
            $var = !$var;

            print "<tr ".$bc[$var].">";

            print '<td>';
            $contactstatic->id = $obj->rowid;
            $contactstatic->name = $obj->name;
            $contactstatic->firstname = $obj->firstname;
            //TODO pas bon
            print $this->getContactUrl($obj->rowid,1);
            print '</td>';

            print '<td><a href="tel:'.$obj->phone.'">';

            print dol_print_phone($obj->phone);
//            print dol_phone_link($obj->phone);
            print '</a></td>';
            print '<td>';
            print '<a href="mailto:'.$obj->email.'">';
            print $obj->email;
            print '</a>';


            if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
            {
                print '<td align="center"><a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&backtopage=1&actioncode=AC_RDV&contactid='.$obj->rowid.'&socid='.$objsoc->id.'">';
                print img_object($langs->trans("Rendez-Vous"),"action");
                print '</a></td>';
            }

            print "</tr>\n";
            $i++;
        }
    }
    else
    {
        //print "<tr ".$bc[$var].">";
        //print '<td>'.$langs->trans("NoContactsYetDefined").'</td>';
        //print "</tr>\n";
    }
    print "</table>\n";

    print "<br>\n";
}
    function getGSMSocNameUrl($soc,$langs,$colspan=1)
    {
        if ($soc->client == 1)
        {
            print "    <TD align='left' colspan='".$colspan."'><A href='client_detail.php?client_id=".$soc->id."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$soc->nom."</A>";
        } else if ($soc->client == 2 ) {
            print "    <TD align='left' colspan='".$colspan."'><A href='prospect_detail.php?prospect_id=".$soc->id."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$soc->nom."</A>";
        } else if ($soc->client == 0 && $soc->fournisseur == 1 )
        {
            print "    <TD align='left' colspan='".$colspan."'><A href='fournisseur_detail.php?fournisseur_id=".$soc->id."'>".img_object($langs->trans("showCompany"),"company")."&nbsp;" .$soc->nom."</A>";
        }
    }

/**
 \brief      Formatage des num�ros de telephone en fonction du format d'un pays
 \param        phone            Num�ro de telephone a formater
 \param        country            Pays selon lequel formatter
 \return     string            Num�ro de t�l�phone format�
 */
function dol_print_phone($phone,$country="FR")
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php");
    $phone=trim($phone);
    if (! $phone) { return $phone; }

    if (strtoupper($country) == "FR")
    {
        // France
        if (strlen($phone) == 10) {
            return img_picto("","call") . substr($phone,0,2)."&nbsp;".substr($phone,2,2)."&nbsp;".substr($phone,4,2)."&nbsp;".substr($phone,6,2)."&nbsp;".substr($phone,8,2);
        }
        elseif (strlen($phone) == 7)
        {

            return img_picto("","call") .substr($phone,0,3)."&nbsp;".substr($phone,3,2)."&nbsp;".substr($phone,5,2);
        }
        elseif (strlen($phone) == 9)
        {
            return img_picto("","call") .substr($phone,0,2)."&nbsp;".substr($phone,2,3)."&nbsp;".substr($phone,5,2)."&nbsp;".substr($phone,7,2);
        }
        elseif (strlen($phone) == 11)
        {
            return img_picto("","call") .substr($phone,0,3)."&nbsp;".substr($phone,3,2)."&nbsp;".substr($phone,5,2)."&nbsp;".substr($phone,7,2)."&nbsp;".substr($phone,9,2);
        }
        elseif (strlen($phone) == 12)
        {
            return img_picto("","call") .substr($phone,0,4)."&nbsp;".substr($phone,4,2)."&nbsp;".substr($phone,6,2)."&nbsp;".substr($phone,8,2)."&nbsp;".substr($phone,10,2);
        }
    }

    return img_picto("","call") .$phone;
}
}



?>