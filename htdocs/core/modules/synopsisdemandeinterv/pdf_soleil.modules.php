<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)       <raphael.bertrand@resultic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
  * GLE by Synopsis et DRSI
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
/*
 * or see http://www.gnu.org/
 */

/**
 \file       htdocs/core/modules/synopsisdemandeinterv/pdf_soleil.modules.php
 \ingroup    demandeInterv
 \brief      Fichier de la classe permettant de generer les fiches d'intervention au modele Soleil
 \version    $Id: pdf_soleil.modules.php,v 1.46 2008/07/29 19:20:34 eldy Exp $
 */

require_once(DOL_DOCUMENT_ROOT."/core/modules/synopsisdemandeinterv/modules_synopsisdemandeinterv.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");

require_once(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php');

/**
 \class      pdf_soleil
 \brief      Classe permettant de generer les fiches d'intervention au modele Soleil
 */

class pdf_soleil extends ModeleSynopsisdemandeinterv
{

    /**
    \brief      Constructeur
    \param        db        Handler acces base de donnee
    */
    function pdf_soleil($db=0)
    {
        global $conf,$langs,$mysoc;

        $this->db = $db;
        $this->name = 'soleil';
        $this->description = "Mod&egrave;le de fiche d'intervention standard";

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
        $this->option_tva = 0;                     // Gere option tva FACTURE_TVAOPTION
        $this->option_modereg = 0;                 // Affiche mode reglement
        $this->option_condreg = 0;                 // Affiche conditions reglement
        $this->option_codeproduitservice = 0;      // Affiche code produit-service
        $this->option_multilang = 0;               // Dispo en plusieurs langues
        $this->option_draft_watermark = 1;           //Support add of a watermark on drafts

        // Recupere code pays de l'emmetteur
        $this->emetteur=$mysoc;
        if (! $this->emetteur->code_pays) $this->emetteur->code_pays=substr($langs->defaultlang,-2);    // Par defaut, si n'etait pas defini
    }

    /**
    \brief      Fonction generant la fiche d'intervention sur le disque
    \param        demandeInterv        Object demandeInterv
    \return        int             1=ok, 0=ko
    */
    function write_file($demandeInterv,$outputlangs='')
    {
        global $user,$langs,$conf,$mysoc;

        if (! is_object($outputlangs)) $outputlangs=$langs;
        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("interventions");
        $outputlangs->load('synopsisGene@Synopsis_Tools');

//        $outputlangs->setPhpLang();

        if ($conf->synopsisdemandeinterv->dir_output)
        {
            // If $demandeInterv is id instead of object
            if (! is_object($demandeInterv))
            {
                $id = $demandeInterv;
                $demandeInterv = new demandeInterv($this->db);
                $result=$demandeInterv->fetch($id);
                if ($result < 0)
                {
                    dol_print_error($this->db,$demandeInterv->error);
                }
            }

            $fichref = sanitize_string($demandeInterv->ref);
            $dir = $conf->synopsisdemandeinterv->dir_output;
            if (! preg_match('/specimen/i',$fichref)) $dir.= "/" . $fichref;
            $file = $dir . "/" . $fichref . ".pdf";

            if (! file_exists($dir))
            {
                if (dol_mkdir($dir) < 0)
                {
                    $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                    return 0;
                }
            }

            if (file_exists($dir))
            {
                $pdf=pdf_getInstance($this->format);
                // Protection et encryption du pdf
//                if ($conf->global->PDF_SECURITY_ENCRYPTION)
//                {
//                    $pdf=new FPDI_Protection('P','mm',$this->format);
//                    $pdfrights = array('print'); // Ne permet que l'impression du document
//                    $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
//                    $pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
//                    $pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
//                }
//                else
//                {
//                    $pdf=new FPDI('P','mm',$this->format);
//                }

                $pdf->Open();
                $pdf->AddPage();

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(1,0);

                //Affiche le filigrane brouillon - Print Draft Watermark
                if($demandeInterv->statut==0 && (! empty($conf->global->DEMANDEINTERV_DRAFT_WATERMARK)) )
                {
                    $watermark_angle=atan($this->page_hauteur/$this->page_largeur);
                    $watermark_x=5;
                    $watermark_y=$this->page_hauteur-50;
                    $watermark_width=$this->page_hauteur;
                    $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',50);
                    $pdf->SetTextColor(255,192,203);
                    //rotate
                    $pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($watermark_angle),sin($watermark_angle),-sin($watermark_angle),cos($watermark_angle),$watermark_x*$pdf->k,($pdf->h-$watermark_y)*$pdf->k,-$watermark_x*$pdf->k,-($pdf->h-$watermark_y)*$pdf->k));
                    //print watermark
                    $pdf->SetXY($watermark_x,$watermark_y);
                    $pdf->Cell($watermark_width,25,clean_html($conf->global->DEMANDEINTERV_DRAFT_WATERMARK),0,2,"C",0);
                    //antirotate
                    $pdf->_out('Q');
                }
                //Print content

                $posy=$this->marge_haute;

                $pdf->SetXY($this->marge_gauche,$posy);

                // Logo

                // Logo
        $logo = false;
        if (is_file ($conf->societe->dir_logos.'/'.$this->emetteur->logo."noalpha.png"))
        {
            $logo=$conf->societe->dir_logos.'/'.$this->emetteur->logo."noalpha.png";
        } else {
            $logo=$conf->societe->dir_logos.'/'.$this->emetteur->logo;
        }



//                $logo=$conf->societe->dir_logos.'/'.$mysoc->logo;
                if ($mysoc->logo)
                {
                    if (is_readable($logo))
                    {
                        $pdf->Image($logo, $this->marge_gauche, $posy, 0, 24);
                    }
                    else
                    {
                        $pdf->SetTextColor(200,0,0);
                        $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',8);
                        $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
                        $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
                    }
                }

                // Nom emetteur
                $posy=40;
                $hautcadre=40;
                $pdf->SetTextColor(0,0,0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs),'',8);

                $pdf->SetXY($this->marge_gauche,$posy);
                $pdf->SetFillColor(230,230,230);
                $pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);


                $pdf->SetXY($this->marge_gauche+2,$posy+3);

                $pdf->SetTextColor(0,0,60);
                $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',11);
                if (defined("FAC_PDF_SOCIETE_NOM") && FAC_PDF_SOCIETE_NOM) $pdf->MultiCell(80, 4, FAC_PDF_SOCIETE_NOM, 0, 'L');
                else $pdf->MultiCell(80, 4, $mysoc->nom, 0, 'L');

                // Caracteristiques emetteur
                $carac_emetteur = '';
                if (defined("FAC_PDF_ADRESSE") && FAC_PDF_ADRESSE) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).FAC_PDF_ADRESSE;
                else {
                    $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$mysoc->adresse;
                    $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$mysoc->cp.' '.$mysoc->ville;
                }
                $carac_emetteur .= "\n";
                // Tel
                if (defined("FAC_PDF_TEL") && FAC_PDF_TEL) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".FAC_PDF_TEL;
                elseif ($mysoc->tel) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$mysoc->tel;
                // Fax
                if (defined("FAC_PDF_FAX") && FAC_PDF_FAX) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".FAC_PDF_FAX;
                elseif ($mysoc->fax) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$mysoc->fax;
                // EMail
                if (defined("FAC_PDF_MEL") && FAC_PDF_MEL) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".FAC_PDF_MEL;
                elseif ($mysoc->email) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$mysoc->email;
                // Web
                if (defined("FAC_PDF_WWW") && FAC_PDF_WWW) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".FAC_PDF_WWW;
                elseif ($mysoc->url) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$mysoc->url;

                $pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
                $pdf->SetXY($this->marge_gauche+2,$posy+8);
                $pdf->MultiCell(80,4, $carac_emetteur);


                /*
                * Adresse Client
                */
                $pdf->SetTextColor(0,0,0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',12);
                $demandeInterv->fetch_client();
                $pdf->SetXY(102,42);
                $pdf->MultiCell(86,5, $demandeInterv->client->nom);
                $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',11);
                $pdf->SetXY(102,$pdf->GetY());
                $pdf->MultiCell(66,5, $demandeInterv->client->adresse . "\n" . $demandeInterv->client->cp . " " . $demandeInterv->client->ville);
                $pdf->rect(100, 40, 100, 40);


                $pdf->SetTextColor(200,0,0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs),'B',14);
                $pdf->Text(11, 88, "Date : " . dol_print_date($demandeInterv->date,'day'));
                $pdf->Text(11, 94, $langs->trans("Demande d'intervention")." : ".$demandeInterv->ref);

                $pdf->SetFillColor(220,220,220);
                $pdf->SetTextColor(0,0,0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs),'',12);

                $tab_top = 100;
                $tab_height = 16;

                $pdf->SetXY (10, $tab_top);
                $pdf->MultiCell(190,4,$langs->transnoentities("Description"),0,'L',0);
                $pdf->line(10, $tab_top + 8, 200, $tab_top + 8 );

                $pdf->Rect(10, $tab_top, 190, $tab_height);

                $pdf->SetFont(pdf_getPDFFont($outputlangs),'', 10);

                $pdf->SetXY (10, $tab_top + 8 );
                $pdf->writeHTMLCell(190, 4, 10, $tab_top + 8, dol_htmlentitiesbr($demandeInterv->description), 0, 'J', 0);

                //dol_syslog("desc=".dol_htmlentitiesbr($demandeInterv->description));
                $num = sizeof($demandeInterv->lignes);
                $i=0;
                $y = $pdf->GetY()+10;
                if ($num)
                {
                    while ($i < $num)
                    {
                        $demandeIntervligne = $demandeInterv->lignes[$i];

                        $valide = $demandeIntervligne->id ? $demandeIntervligne->fetch($demandeIntervligne->id) : 0;
                        if ($valide>0)
                        {
                            $pdf->SetXY (10 +$this->marge_gauche, $y);
                            $pdf->MultiCell(60, 4, preg_replace('/<br[ ]*\/?>/',"\n",$langs->transnoentities("Date")." : ".dol_print_date($demandeIntervligne->datei,'day')." - ".$langs->transnoentities("Duration")." : ".ConvertSecondToTime($demandeIntervligne->duration)), 0, 'J', 0);
                            $pdf->SetXY (70 + $this->marge_gauche, $y);
                            $pdf->MultiCell(30, 4, $demandeIntervligne->typeInterv, 0, 'L', 0);
                            $pdf->SetXY (100 + $this->marge_gauche, $y);
                            $pdf->MultiCell(90,4,preg_replace('/<br[ ]*\/?>/',"\n",$demandeIntervligne->desc,1), 0, 'L', 0);
                            $y = $pdf->GetY();
                        }
                        $i++;
                    }
                }
                $pdf->Rect(10, $tab_top, 190, $tab_height);
                $pdf->SetXY (10, $pdf->GetY() + 20 );
                $pdf->MultiCell(60, 5, '', 0, 'J', 0);

                $pdf->SetXY(20,220);
                $pdf->MultiCell(66,5, $langs->transnoentities("NameAndSignatureOfInternalContact"),0,'L',0);

                $pdf->SetXY(20,225);
                $pdf->MultiCell(80,30, '', 1);

                $pdf->SetXY(110,220);
                $pdf->MultiCell(80,5, $langs->transnoentities("NameAndSignatureOfExternalContact"),0,'L',0);

                $pdf->SetXY(110,225);
                $pdf->MultiCell(80,30, '', 1);

                $pdf->SetFont(pdf_getPDFFont($outputlangs),'', 9);   // On repositionne la police par defaut

                $this->_pagefoot($pdf,$demandeInterv,$outputlangs);
                $pdf->AliasNbPages();

                $pdf->Close();

                $this->file = $file;
                $pdf->Output($file, 'f');


//                $langs->setPhpLang();    // On restaure langue session
                return 1;
            }
            else
            {
                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                return 0;
            }
        }
        else
        {
            $this->error=$langs->trans("ErrorConstantNotDefined","DEMANDEINTERV_OUTPUTDIR");
            return 0;
        }
        $this->error=$langs->trans("ErrorUnknown");
        return 0;   // Erreur par defaut
    }

    /*
    *   \brief      Affiche $le pied de page
    *   \param      pdf     objet PDF
    */
    function _pagefoot(&$pdf,$object,$outputlangs)
    {
        return pdf_pagefoot($pdf,$outputlangs,'DEMANDEINTERV_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object);
    }

}

?>
