<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2008 Raphael Bertrand (Resultic)       <raphael.bertrand@resultic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
/*
  * BIMP-ERP by Synopsis et DRSI
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
/*
 * or see http://www.gnu.org/
 */

/**
 \file       htdocs/core/modules/commande/pdf_pholos.modules.php
 \ingroup    commande
 \brief      Fichier de la classe permettant de generer les commandes au modele Einstein
 \author        Laurent Destailleur
 \version    $Id: pdf_pholos.modules.php,v 1.64 2008/08/07 07:50:04 eldy Exp $
 */

//require_once(DOL_DOCUMENT_ROOT ."/core/modules/commande/modules_commande.php");
//require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");


/**
 \class      pdf_pholos
 \brief      Classe permettant de generer les commandes au modele Einstein
 */

class pdf_process_pholos extends ModeleNumRefProcess
{
    public $emetteur;    // Objet societe qui emet


    /**
    \brief      Constructeur
    \param        db        Handler acces base de donnee
    */
    function pdf_process_pholos($db)
    {
        global $conf,$langs,$mysoc;

        $langs->load("main");
        $langs->load("bills");

        $this->db = $db;
        $this->name = "Pholos";
        $this->description = $langs->trans('PDFPholosDescription');

        // Dimension page pour format A4
        $this->type = 'pdf';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur,$this->page_hauteur);
        $this->marge_gauche=10;
        $this->marge_droite=10;
        $this->marge_haute=10;
        $this->marge_basse=10;

        $this->option_logo = 1;                    // Affiche logo
        $this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
        $this->option_modereg = 1;                 // Affiche mode reglement
        $this->option_condreg = 1;                 // Affiche conditions reglement
        $this->option_codeproduitservice = 1;      // Affiche code produit-service
        $this->option_multilang = 1;               // Dispo en plusieurs langues
        $this->option_escompte = 1;                // Affiche si il y a eu escompte
        $this->option_credit_note = 1;             // Gere les avoirs
        $this->option_freetext = 1;                    // Support add of a personalised text
        $this->option_draft_watermark = 1;           //Support add of a watermark on drafts

        if (defined("FACTURE_TVAOPTION") && FACTURE_TVAOPTION == 'franchise')
        $this->franchise=1;

        // Recupere emmetteur
        $this->emetteur=$mysoc;
        if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'etait pas defini

        // Defini position des colonnes
        $this->posxdesc=$this->marge_gauche+1;
        $this->posxtva=113;
        $this->posxup=126;
        $this->posxqty=145;
        $this->posxdiscount=162;
        $this->postotalht=174;

        $this->tva=array();
        $this->atleastoneratenotnull=0;
        $this->atleastonediscount=0;
    }

    /**
    *        \brief      Fonction generant la commande sur le disque
    *        \param        com                Objet commande a generer
    *        \param        outputlangs        Lang object for output language
    *        \return        int             1=ok, 0=ko
    */
    function write_file($com,$outputlangs='')
    {
        global $user,$langs,$conf;

        if (! is_object($outputlangs)) $outputlangs=$langs;
        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("bills");
        $outputlangs->load("products");
        $outputlangs->load("orders");

        //$outputlangs->setPhpLang();

        if ($conf->commande->dir_output)
        {
            // Definition de l'objet $com (pour compatibilite ascendante)
            if (! is_object($com))
            {
                $id = $com;
                $com = new Commande($this->db,"",$id);
                $ret=$com->fetch($id);
            }
            $deja_regle = "";

            // Definition de $dir et $file
            if ($com->specimen)
            {
                $dir = $conf->commande->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            }
            else
            {
                $comref = sanitize_string($com->ref);
                $dir = $conf->commande->dir_output . "/" . $comref;
                $file = $dir . "/" . $comref . ".pdf";
            }

            if (! file_exists($dir))
            {
                if (create_exdir($dir) < 0)
                {
                    $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                    return 0;
                }
            }

            if (file_exists($dir))
            {
                $nblignes = sizeof($com->lines);

                // Protection et encryption du pdf
                if ($conf->global->PDF_SECURITY_ENCRYPTION)
                {
                    $pdf=new FPDI_Protection('P','mm',$this->format);
                    $pdfrights = array('print'); // Ne permet que l'impression du document
                    $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
                    $pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
                    $pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
                }
                else
                {
                    $pdf=new FPDI('P','mm',$this->format);
                }

                $pdf->Open();
                $pdf->AddPage();

                $pdf->SetDrawColor(128,128,128);

                $pdf->SetTitle($com->ref);
                $pdf->SetSubject($outputlangs->transnoentities("Order"));
                $pdf->SetCreator("BIMP-ERP ".DOL_VERSION);
                $pdf->SetAuthor($user->getFullName($langs));

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(1,0);

                // Positionne $this->atleastonediscount si on a au moins une remise
                for ($i = 0 ; $i < $nblignes ; $i++)
                {
                    if ($com->lines[$i]->remise_percent)
                    {
                        $this->atleastonediscount++;
                    }
                }

                // Tete de page
                $this->_pagehead($pdf, $com, 1, $outputlangs);

                $pagenb = 1;
                $tab_top = 90;
                $tab_top_newpage = 50;
                $tab_height = 110;
                $tab_height_newpage = 180;

                // Affiche notes
                if ($com->note_public)
                {
                    $tab_top = 88;

                    $pdf->SetFont(pdf_getPDFFont($outputlangs),'', 9);   // Dans boucle pour gerer multi-page
                    $pdf->SetXY ($this->posxdesc-1, $tab_top);
                    $pdf->MultiCell(190, 3, $com->note_public, 0, 'J');
                    $nexY = $pdf->GetY();
                    $height_note=$nexY-$tab_top;

                    // Rect prend une longueur en 3eme param
                    $pdf->SetDrawColor(192,192,192);
                    $pdf->Rect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+1);

                    $tab_height = $tab_height - $height_note;
                    $tab_top = $nexY+6;
                }
                else
                {
                    $height_note=0;
                }

                $iniY = $tab_top + 8;
                $curY = $tab_top + 8;
                $nexY = $tab_top + 8;

                // Boucle sur les lignes
                for ($i = 0 ; $i < $nblignes ; $i++)
                {
                    $curY = $nexY;

                    // Description de la ligne produit
                    $libelleproduitservice=dol_htmlentitiesbr($com->lines[$i]->libelle,1);
                    if ($com->lines[$i]->desc && $com->lines[$i]->desc!=$com->lines[$i]->libelle)
                    {
                        if ($libelleproduitservice) $libelleproduitservice.="<br>";
                        if ($com->lines[$i]->desc == '(CREDIT_NOTE)' && $com->lines[$i]->fk_remise_except)
                        {
                            $discount=new DiscountAbsolute($this->db);
                            $discount->fetch($com->lines[$i]->fk_remise_except);
                            $libelleproduitservice=dol_htmlentitiesbr($langs->trans("DiscountFromCreditNote",$discount->ref_facture_source),1);
                        }
                        else
                        {
                            $libelleproduitservice.=dol_htmlentitiesbr($com->lines[$i]->desc,1);
                        }
                    }
                    // Si ligne associee a un code produit
                    if ($com->lines[$i]->fk_product)
                    {
                        $prodser = new Product($this->db);
                        $prodser->fetch($com->lines[$i]->fk_product);

                        // On ajoute la ref
                        if ($prodser->ref)
                        {
                            $prefix_prodserv = "";
                            if($prodser->isservice())
                            $prefix_prodserv = $outputlangs->transnoentities("Service")." ";
                            else
                            $prefix_prodserv = $outputlangs->transnoentities("Product")." ";

                            $libelleproduitservice=$prefix_prodserv.$prodser->ref." - ".$libelleproduitservice;
                        }

                    }

                    if ($com->lines[$i]->date_start && $com->lines[$i]->date_end)
                    {
                        // Affichage duree si il y en a une
                        $libelleproduitservice.="<br>".dol_htmlentitiesbr("(".$outputlangs->transnoentities("From")." ".dol_print_date($com->lines[$i]->date_start)." ".$outputlangs->transnoentities("to")." ".dol_print_date($com->lines[$i]->date_end).")",1);
                    }

                    $pdf->SetFont(pdf_getPDFFont($outputlangs),'', 9);   // Dans boucle pour gerer multi-page

                    // Description
                    $pdf->writeHTMLCell($this->posxtva-$this->posxdesc-1, 3, $this->posxdesc-1, $curY, $libelleproduitservice, 0, 1);

                    $pdf->SetFont(pdf_getPDFFont($outputlangs),'', 9);   // On repositionne la police par defaut

                    $nexY = $pdf->GetY();

                    // TVA
                    $pdf->SetXY ($this->posxtva, $curY);
                    $pdf->MultiCell($this->posxup-$this->posxtva-1, 3, vatrate($com->lines[$i]->tva_tx,1,$com->lines[$i]->info_bits), 0, 'R');

                    // Prix unitaire HT avant remise
                    $pdf->SetXY ($this->posxup, $curY);
                    $pdf->MultiCell($this->posxqty-$this->posxup-1, 3, price($com->lines[$i]->subprice), 0, 'R', 0);

                    // Quantity
                    $pdf->SetXY ($this->posxqty, $curY);
                    $pdf->MultiCell($this->posxdiscount-$this->posxqty-1, 3, $com->lines[$i]->qty, 0, 'R');

                    // Remise sur ligne
                    $pdf->SetXY ($this->posxdiscount, $curY);
                    if ($com->lines[$i]->remise_percent)
                    {
                        $pdf->MultiCell($this->postotalht-$this->posxdiscount-1, 3, dolibarr_print_reduction($com->lines[$i]->remise_percent), 0, 'R');
                    }

                    // Total HT ligne
                    $pdf->SetXY ($this->postotalht, $curY);
                    $total = price($com->lines[$i]->total_ht);
                    $pdf->MultiCell(26, 4, $total, 0, 'R', 0);

                    // Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
                    $tvaligne=$com->lines[$i]->total_tva;
                    if ($com->remise_percent) $tvaligne-=($tvaligne*$com->remise_percent)/100;
                    $vatrate=(string) $com->lines[$i]->tva_tx;
                    if ($com->lines[$i]->info_bits & 0x01 == 0x01) $vatrate.='*';
                    $this->tva[$vatrate] += $tvaligne;

                    $nexY+=2;    // Passe espace entre les lignes

                    // cherche nombre de lignes a venir pour savoir si place suffisante
                    if ($i < ($nblignes - 1))    // If it's not last line
                    {
                        //on recupere la description du produit suivant
                        $follow_descproduitservice = $com->lines[$i+1]->desc;
                        //on compte le nombre de ligne afin de verifier la place disponible (largeur de ligne 52 caracteres)
                        $nblineFollowDesc = (num_lines($follow_descproduitservice,52)*4);
                    }
                    else    // If it's last line
                    {
                        $nblineFollowDesc = 0;
                    }

                    // test si besoin nouvelle page
                    if (($nexY+$nblineFollowDesc) > ($tab_top+$tab_height) && $i < ($nblignes - 1))
                    {
                        if ($pagenb == 1)
                        {
                            $this->_tableau($pdf, $tab_top, $tab_height + 20, $nexY, $outputlangs);
                        }
                        else
                        {
                            $this->_tableau($pdf, $tab_top_newpage, $tab_height_newpage, $nexY, $outputlangs);
                        }

                        $this->_pagefoot($pdf,$outputlangs);

                        // Nouvelle page
                        $pdf->AddPage();
                        $pagenb++;
                        $this->_pagehead($pdf, $com, 0, $outputlangs);

                        $nexY = $tab_top_newpage + 8;
                        $pdf->SetTextColor(0,0,0);
                        $pdf->SetFont(pdf_getPDFFont($outputlangs),'', 10);
                    }

                }

                // Affiche cadre tableau
                if ($pagenb == 1)
                {
                    $this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);
                    $bottomlasttab=$tab_top + $tab_height + 1;
                }
                else
                {
                    $this->_tableau($pdf, $tab_top_newpage, $tab_height, $nexY, $outputlangs);
                    $bottomlasttab=$tab_top_newpage + $tab_height + 1;
                }

                // Affiche zone infos
                $posy=$this->_tableau_info($pdf, $com, $bottomlasttab, $outputlangs);
                // Affiche zone totaux
                $posy=$this->_tableau_tot($pdf, $com, $deja_regle, $bottomlasttab, $outputlangs);

                // Affiche zone versements
                if ($deja_regle)
                {
                    $posy=$this->_tableau_versements($pdf, $com, $posy, $outputlangs);
                }

                // Pied de page
                $this->_pagefoot($pdf,$outputlangs);
                if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

                $pdf->Close();

                $this->file = $file;$pdf->Output($file);


                $langs->setPhpLang();    // On restaure langue session
                return 1;   // Pas d'erreur
            }
            else
            {
                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                $langs->setPhpLang();    // On restaure langue session
                return 0;
            }
        }
        else
        {
            $this->error=$langs->trans("ErrorConstantNotDefined","COMMANDE_OUTPUTDIR");
            $langs->setPhpLang();    // On restaure langue session
            return 0;
        }
        $this->error=$langs->trans("ErrorUnknown");
        $langs->setPhpLang();    // On restaure langue session
        return 0;   // Erreur par defaut
    }

    /*
    *   \brief      Affiche tableau des versement
    *   \param      pdf         Objet PDF
    *   \param      object        Objet commande
    *    \param        posy            Position y in PDF
    *    \param        outputlangs        Object langs for output
    *    \return     int                <0 if KO, >0 if OK
    */
    function _tableau_versements(&$pdf, $object, $posy, $outputlangs)
    {

    }


    /*
    *    \brief      Affiche infos divers
    *    \param      pdf             Objet PDF
    *    \param      object          Objet commande
    *    \param        posy            Position depart
    *    \param        outputlangs        Objet langs
    *    \return     y               Position pour suite
    */
    function _tableau_info(&$pdf, $object, $posy, $outputlangs)
    {
        global $conf;

        $pdf->SetFont(pdf_getPDFFont($outputlangs),'', 9);

        /*
        *    If France, show VAT mention if not applicable
        */
        if ($this->emetteur->pays_code == 'FR' && $this->franchise == 1)
        {
            $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',8);
            $pdf->SetXY($this->marge_gauche, $posy);
            $pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

            $posy=$pdf->GetY()+4;
        }

        /*
        *    Conditions de reglements
        */
        if ($object->cond_reglement_code || $object->cond_reglement)
        {
            $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',8);
            $pdf->SetXY($this->marge_gauche, $posy);
            $titre = $outputlangs->transnoentities("PaymentConditions").':';
            $pdf->MultiCell(80, 5, $titre, 0, 'L');

            $pdf->SetFont(pdf_getPDFFont($outputlangs),'',8);
            $pdf->SetXY(50, $posy);
            $lib_condition_paiement=$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code)!=('PaymentCondition'.$object->cond_reglement_code)?$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code):$object->cond_reglement;
            $pdf->MultiCell(80, 5, $lib_condition_paiement,0,'L');

            $posy=$pdf->GetY()+3;
        }

        /*
        *    Check si absence mode reglement
        */
        if (! $conf->global->FACTURE_CHQ_NUMBER && ! $conf->global->FACTURE_RIB_NUMBER)
        {
            $pdf->SetXY($this->marge_gauche, $posy);
            $pdf->SetTextColor(200,0,0);
            $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',8);
            $pdf->MultiCell(90, 3, $outputlangs->transnoentities("ErrorNoPaiementModeConfigured"),0,'L',0);
            $pdf->SetTextColor(0,0,0);

            $posy=$pdf->GetY()+1;
        }

        /*
        * Propose mode reglement par CHQ
        */
        if (! $object->mode_reglement_code || $object->mode_reglement_code == 'CHQ')
        {
            // Si mode reglement non force ou si force a CHQ
            if ($conf->global->FACTURE_CHQ_NUMBER)
            {
                if ($conf->global->FACTURE_CHQ_NUMBER > 0)
                {
                    $account = new Account($this->db);
                    $account->fetch($conf->global->FACTURE_CHQ_NUMBER);

                    $pdf->SetXY($this->marge_gauche, $posy);
                    $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',8);
                    $pdf->MultiCell(90, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$account->proprio).':',0,'L',0);
                    $posy=$pdf->GetY()+1;

                    $pdf->SetXY($this->marge_gauche, $posy);
                    $pdf->SetFont(pdf_getPDFFont($outputlangs),'',8);
                    $pdf->MultiCell(80, 3, $account->adresse_proprio, 0, 'L', 0);

                    $posy=$pdf->GetY()+2;
                }
                if ($conf->global->FACTURE_CHQ_NUMBER == -1)
                {
                    $pdf->SetXY($this->marge_gauche, $posy);
                    $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',8);
                    $pdf->MultiCell(90, 3, $outputlangs->transnoentities('PaymentByChequeOrderedToShort').' '.$this->emetteur->nom.' '.$outputlangs->transnoentities('SendTo').':',0,'L',0);
                    $posy=$pdf->GetY()+1;

                    $pdf->SetXY($this->marge_gauche, $posy);
                    $pdf->SetFont(pdf_getPDFFont($outputlangs),'',8);
                    $pdf->MultiCell(80, 6, $this->emetteur->adresse_full, 0, 'L', 0);

                    $posy=$pdf->GetY()+2;
                }
            }
        }

        /*
        * Propose mode reglement par RIB
        */
        if (! $object->mode_reglement_code || $object->mode_reglement_code == 'VIR')
        {
            // Si mode reglement non force ou si force a VIR
            if ($conf->global->FACTURE_RIB_NUMBER)
            {
                if ($conf->global->FACTURE_RIB_NUMBER)
                {
                    $account = new Account($this->db);
                    $account->fetch($conf->global->FACTURE_RIB_NUMBER);

                    $this->marges['g']=$this->marge_gauche;

                    $cury=$posy;
                    $pdf->SetXY ($this->marges['g'], $cury);
                    $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',8);
                    $pdf->MultiCell(90, 3, $outputlangs->transnoentities('PaymentByTransferOnThisBankAccount').':', 0, 'L', 0);
                    $cury+=4;
                    $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',6);
                    $pdf->line($this->marges['g']+1, $cury, $this->marges['g']+1, $cury+10 );
                    $pdf->SetXY ($this->marges['g'], $cury);
                    $pdf->MultiCell(18, 3, $outputlangs->transnoentities("BankCode"), 0, 'C', 0);
                    $pdf->line($this->marges['g']+18, $cury, $this->marges['g']+18, $cury+10 );
                    $pdf->SetXY ($this->marges['g']+18, $cury);
                    $pdf->MultiCell(18, 3, $outputlangs->transnoentities("DeskCode"), 0, 'C', 0);
                    $pdf->line($this->marges['g']+36, $cury, $this->marges['g']+36, $cury+10 );
                    $pdf->SetXY ($this->marges['g']+36, $cury);
                    $pdf->MultiCell(24, 3, $outputlangs->transnoentities("BankAccountNumber"), 0, 'C', 0);
                    $pdf->line($this->marges['g']+60, $cury, $this->marges['g']+60, $cury+10 );
                    $pdf->SetXY ($this->marges['g']+60, $cury);
                    $pdf->MultiCell(13, 3, $outputlangs->transnoentities("BankAccountNumberKey"), 0, 'C', 0);
                    $pdf->line($this->marges['g']+73, $cury, $this->marges['g']+73, $cury+10 );

                    $pdf->SetFont(pdf_getPDFFont($outputlangs),'',8);
                    $pdf->SetXY ($this->marges['g'], $cury+5);
                    $pdf->MultiCell(18, 3, $account->code_banque, 0, 'C', 0);
                    $pdf->SetXY ($this->marges['g']+18, $cury+5);
                    $pdf->MultiCell(18, 3, $account->code_guichet, 0, 'C', 0);
                    $pdf->SetXY ($this->marges['g']+36, $cury+5);
                    $pdf->MultiCell(24, 3, $account->number, 0, 'C', 0);
                    $pdf->SetXY ($this->marges['g']+60, $cury+5);
                    $pdf->MultiCell(13, 3, $account->cle_rib, 0, 'C', 0);

                    $pdf->SetXY ($this->marges['g'], $cury+12);
                    $pdf->MultiCell(90, 3, $outputlangs->transnoentities("Residence").' : ' . $account->domiciliation, 0, 'L', 0);
                    $pdf->SetXY ($this->marges['g'], $cury+22);
                    $pdf->MultiCell(90, 3, $outputlangs->transnoentities("IbanPrefix").' : ' . $account->iban_prefix, 0, 'L', 0);
                    $pdf->SetXY ($this->marges['g'], $cury+25);
                    $pdf->MultiCell(90, 3, $outputlangs->transnoentities("BIC").' : ' . $account->bic, 0, 'L', 0);

                    $posy=$pdf->GetY()+2;
                }
            }
        }

        return $posy;
    }


    /*
    *    \brief      Affiche le total a payer
    *    \param      pdf             Objet PDF
    *    \param      object          Objet commande
    *    \param      deja_regle      Montant deja regle
    *    \param        posy            Position depart
    *    \param        outputlangs        Objet langs
    *    \return     y                Position pour suite
    */
    function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
    {
        $tab2_top = $posy;
        $tab2_hl = 5;
        $tab2_height = $tab2_hl * 4;
        $pdf->SetFont(pdf_getPDFFont($outputlangs),'', 9);

        // Tableau total
        $lltot = 200; $col1x = 120; $col2x = 182; $largcol2 = $lltot - $col2x;

        // Total HT
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY ($col1x, $tab2_top + 0);
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);

        $pdf->SetXY ($col2x, $tab2_top + 0);
        $pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ht + $object->remise), 0, 'R', 1);

        $index = 0;

        // Affichage des totaux de TVA par taux (conformement a reglementation)
        $pdf->SetFillColor(248,248,248);

        foreach( $this->tva as $tvakey => $tvaval )
        {
            if ($tvakey)    // On affiche pas taux 0
            {
                $this->atleastoneratenotnull++;

                $index++;
                $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);

                $tvacompl='';
                if (preg_match('/\*/i',$tvakey))
                {
                    $tvakey=preg_replace('/\*/i','',$tvakey);
                    $tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
                }
                $totalvat =$outputlangs->transnoentities("TotalVAT").' ';
                $totalvat.=vatrate($tvakey,1).$tvacompl;
                $pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

                $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
                $pdf->MultiCell($largcol2, $tab2_hl, price($tvaval), 0, 'R', 1);
            }
        }
        if (! $this->atleastoneratenotnull) // If not vat at all
        {
            $index++;
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalVAT"), 0, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($object->total_tva), 0, 'R', 1);
        }

        $useborder=0;

        $index++;
        $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFillColor(224,224,224);
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

        $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc), $useborder, 'R', 1);
        $pdf->SetTextColor(0,0,0);

        if ($deja_regle > 0)
        {
            $index++;

            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("AlreadyPayed"), 0, 'L', 0);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle), 0, 'R', 0);

            $resteapayer = $object->total_ttc - $deja_regle;


            $index++;
            $pdf->SetTextColor(0,0,60);
            $pdf->SetFillColor(224,224,224);
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer), $useborder, 'R', 1);

            // Fin
            $pdf->SetFont(pdf_getPDFFont($outputlangs),'', 9);
            $pdf->SetTextColor(0,0,0);
        }

        $index++;
        return ($tab2_top + ($tab2_hl * $index));
    }

    /**
    *   \brief      Affiche la grille des lignes de commandes
    *   \param      pdf     objet PDF
    */
    function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
    {
        global $conf;

        // Montants exprimes en     (en tab_top - 1)
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont(pdf_getPDFFont($outputlangs),'',8);
        $titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentities("Currency".$conf->global->MAIN_MONNAIE));
        $pdf->Text($this->page_largeur - $this->marge_droite - $pdf->GetStringWidth($titre), $tab_top-1, $titre);

        $pdf->SetDrawColor(128,128,128);

        // Rect prend une longueur en 3eme param
        $pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height);
        // line prend une position y en 3eme param
        $pdf->line($this->marge_gauche, $tab_top+5, $this->page_largeur-$this->marge_droite, $tab_top+5);

        $pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);

        $pdf->SetXY ($this->posxdesc-1, $tab_top+2);
        $pdf->MultiCell(108,2, $outputlangs->transnoentities("Designation"),'','L');

        $pdf->line($this->posxtva-1, $tab_top, $this->posxtva-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxtva-1, $tab_top+2);
        $pdf->MultiCell($this->posxup-$this->posxtva-1,2, $outputlangs->transnoentities("VAT"),'','C');

        $pdf->line($this->posxup-1, $tab_top, $this->posxup-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxup-1, $tab_top+2);
        $pdf->MultiCell(18,2, $outputlangs->transnoentities("PriceUHT"),'','C');

        $pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxqty-1, $tab_top+2);
        $pdf->MultiCell($this->posxdiscount-$this->posxqty-1,2, $outputlangs->transnoentities("Qty"),'','C');

        $pdf->line($this->posxdiscount-1, $tab_top, $this->posxdiscount-1, $tab_top + $tab_height);
        if ($this->atleastonediscount)
        {
            $pdf->SetXY ($this->posxdiscount-1, $tab_top+2);
            $pdf->MultiCell(14,2, $outputlangs->transnoentities("ReductionShort"),'','C');
        }

        if ($this->atleastonediscount)
        {
            $pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
        }
        $pdf->SetXY ($this->postotalht-1, $tab_top+2);
        $pdf->MultiCell(28,2, $outputlangs->transnoentities("TotalHT"),'','C');

    }

    /*
    *       \brief      Affiche en-tete commande
    *       \param      pdf             Objet PDF
    *       \param      com             Objet commande
    *      \param      showadress      0=non, 1=oui
    *      \param      outputlang        Objet lang cible
    */
    function _pagehead(&$pdf, $object, $showadress=1, $outputlangs)
    {
        global $conf,$langs;

        $outputlangs->load("main");
        $outputlangs->load("bills");
        $outputlangs->load("propal");
        $outputlangs->load("companies");

        //Affiche le filigrane brouillon - Print Draft Watermark
        if($object->statut==0 && (! empty($conf->global->COMMANDE_DRAFT_WATERMARK)) )
        {
            $watermark_angle=atan($this->page_hauteur/$this->page_largeur);
            $watermark_x=5;
            $watermark_y=$this->page_hauteur-25; //Set to $this->page_hauteur-50 or less if problems
            $watermark_width=$this->page_hauteur;
            $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',50);
            $pdf->SetTextColor(255,192,203);
            //rotate
            $pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($watermark_angle),sin($watermark_angle),-sin($watermark_angle),cos($watermark_angle),$watermark_x*$pdf->k,($pdf->h-$watermark_y)*$pdf->k,-$watermark_x*$pdf->k,-($pdf->h-$watermark_y)*$pdf->k));
            //print watermark
            $pdf->SetXY($watermark_x,$watermark_y);
            $pdf->Cell($watermark_width,25,clean_html($conf->global->COMMANDE_DRAFT_WATERMARK),0,2,"C",0);
            //antirotate
            $pdf->_out('Q');
        }
        //Print content

        $pdf->SetTextColor(0,0,60);
        $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',13);

        $posy=$this->marge_haute;

        $pdf->SetXY($this->marge_gauche,$posy);

        // Logo
        $logo = false;
        if (is_file ($conf->mycompany->dir_output .'/logos'.'/'.$this->emetteur->logo."noalpha.png"))
        {
            $logo=$conf->mycompany->dir_output .'/logos'.'/'.$this->emetteur->logo."noalpha.png";
        } else {
            $logo=$conf->mycompany->dir_output .'/logos'.'/'.$this->emetteur->logo;
        }
        if ($this->emetteur->logo)
        {
            if (is_readable($logo))
            {
                $pdf->Image($logo, $this->marge_gauche, $posy, 0, 24);
            } else {
                $pdf->SetTextColor(200,0,0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',8);
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        }
        else if (defined("FAC_PDF_INTITULE"))
        {
            $pdf->MultiCell(100, 4, FAC_PDF_INTITULE, 0, 'L');
        }

        $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',13);
        $pdf->SetXY(100,$posy);
        $pdf->SetTextColor(0,0,60);
        $title=$outputlangs->transnoentities("Order");
        $pdf->MultiCell(100, 4, $title, '' , 'R');

        $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',12);

        $posy+=6;
        $pdf->SetXY(100,$posy);
        $pdf->SetTextColor(0,0,60);
        $pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : " . $object->ref, '', 'R');

        $posy+=1;
        $pdf->SetFont(pdf_getPDFFont($outputlangs),'',10);

        $posy+=5;
        $pdf->SetXY(100,$posy);
        $pdf->SetTextColor(0,0,60);
        $pdf->MultiCell(100, 3, $outputlangs->transnoentities("OrderDate")." : " . dol_print_date($object->date,"%d %b %Y"), '', 'R');

        if ($showadress)
        {
            // Emetteur
            $posy=42;
            $hautcadre=40;
            $pdf->SetTextColor(0,0,0);
            $pdf->SetFont(pdf_getPDFFont($outputlangs),'',8);
            $pdf->SetXY($this->marge_gauche,$posy-5);
            $pdf->MultiCell(66,5, $outputlangs->transnoentities("BillFrom").":");


            $pdf->SetXY($this->marge_gauche,$posy);
            $pdf->SetFillColor(230,230,230);
            $pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);


            $pdf->SetXY($this->marge_gauche+2,$posy+3);

            // Nom emetteur
            $pdf->SetTextColor(0,0,60);
            $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',11);
            if (defined("FAC_PDF_SOCIETE_NOM") && FAC_PDF_SOCIETE_NOM) $pdf->MultiCell(80, 4, FAC_PDF_SOCIETE_NOM, 0, 'L');
            else $pdf->MultiCell(80, 4, $this->emetteur->nom, 0, 'L');

            // Caracteristiques emetteur
            $carac_emetteur = '';
            if (defined("FAC_PDF_ADRESSE") && FAC_PDF_ADRESSE) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).FAC_PDF_ADRESSE;
            else {
                $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$this->emetteur->adresse;
                $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$this->emetteur->cp.' '.$this->emetteur->ville;
            }
            $carac_emetteur .= "\n";

            // Tel
            if (defined("FAC_PDF_TEL") && FAC_PDF_TEL) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".FAC_PDF_TEL;
            elseif ($this->emetteur->tel) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$this->emetteur->tel;

            // Fax
            if (defined("FAC_PDF_FAX") && FAC_PDF_FAX) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".FAC_PDF_FAX;
            elseif ($this->emetteur->fax) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$this->emetteur->fax;

            // EMail
            if (defined("FAC_PDF_MEL") && FAC_PDF_MEL) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".FAC_PDF_MEL;
            elseif ($this->emetteur->email) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$this->emetteur->email;

            // Web
            if (defined("FAC_PDF_WWW") && FAC_PDF_WWW) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".FAC_PDF_WWW;
            elseif ($this->emetteur->url) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$this->emetteur->url;

            $pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
            $pdf->SetXY($this->marge_gauche+2,$posy+8);
            $pdf->MultiCell(80,4, $carac_emetteur);

            // Client destinataire
            $posy=42;
            $pdf->SetTextColor(0,0,0);
            $pdf->SetFont(pdf_getPDFFont($outputlangs),'',8);
            $pdf->SetXY(102,$posy-5);
            $pdf->MultiCell(80,5, $outputlangs->transnoentities("BillTo").":");
            $object->fetch_client();

            // Cadre client destinataire
            $pdf->rect(100, $posy, 100, $hautcadre);

            // If CUSTOMER contact defined on invoice, we use it
            $usecontact=false;
            if ($conf->global->COMMANDE_USE_CUSTOMER_CONTACT_AS_RECIPIENT)
            {
                $arrayidcontact=$object->getIdContact('external','CUSTOMER');
                if (sizeof($arrayidcontact) > 0)
                {
                    $usecontact=true;
                    $result=$object->fetch_contact($arrayidcontact[0]);
                }
            }

            if ($usecontact)
            {
                // Nom societe
                $pdf->SetXY(102,$posy+3);
                $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',11);
                $pdf->MultiCell(96,4, $object->client->nom, 0, 'L');

                // Nom client
                $carac_client = "\n".$object->contact->getFullName($outputlangs,1,1);

                // Caracteristiques client
                $carac_client.="\n".$object->contact->adresse;
                $carac_client.="\n".$object->contact->cp . " " . $object->contact->ville."\n";
                //Pays si different de l'emetteur
                if ($this->emetteur->pays_code != $object->contact->pays_code)
                {
                    dol_entity_decode($carac_client.=$object->contact->pays)."\n";
                }
            }
            else
            {
                // Nom client
                $pdf->SetXY(102,$posy+3);
                $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',11);
                $pdf->MultiCell(96,4, $object->client->nom, 0, 'L');

                // Nom du contact suivi commande si c'est une societe
                $arrayidcontact = $object->getIdContact('external','CUSTOMER');
                if (sizeof($arrayidcontact) > 0)
                {
                    $object->fetch_contact($arrayidcontact[0]);
                    // On verifie si c'est une societe ou un particulier
                    if( !preg_match('#'.$object->contact->getFullName($outputlangs,1).'#isU',$object->client->nom) )
                    {
                        $carac_client .= "\n".$object->contact->getFullName($outputlangs,1,1);
                    }
                }

                // Caracteristiques client
                $carac_client.="\n".$object->client->adresse;
                $carac_client.="\n".$object->client->cp . " " . $object->client->ville."\n";

                //Pays si different de l'emetteur
                if ($this->emetteur->pays_code != $object->client->pays_code)
                {
                    $carac_client.=dol_entity_decode($object->client->pays)."\n";
                }
            }
            // Numero TVA intracom
            if ($object->client->tva_intra) $carac_client.="\n".$outputlangs->transnoentities("VATIntraShort").': '.$object->client->tva_intra;
            $pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
            $posy=$pdf->GetY()-9; //Auto Y coord readjust for multiline name
            $pdf->SetXY(102,$posy+6);
            $pdf->MultiCell(86,4, $carac_client);
        }
    }

    /*
    *   \brief      Affiche le pied de page
    *   \param      pdf     objet PDF
    */
    function _pagefoot(&$pdf,$outputlangs)
    {
        return pdf_pagefoot($pdf,$outputlangs,'COMMANDE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur);
    }

}

?>
