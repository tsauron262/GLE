<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
  * GLE by Synopsis et DRSI
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
 \file       htdocs/core/modules/propale/pdf_propale_finaproGAStd.modules.php
 \ingroup    propale
 \brief      Fichier de la classe permettant de generer les propales au modele Azur
 \author        Laurent Destailleur
 \version    $Id: pdf_propale_azur.modules.php,v 1.121 2008/08/07 07:47:38 eldy Exp $
 */

require_once(DOL_DOCUMENT_ROOT."/core/modules/propaleGA/modules_propaleGA.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");


/**
 \class      pdf_propale_azur
 \brief      Classe permettant de generer les propales au modele Azur
 */

class pdf_propaleGA_finaproGAStd extends ModelePDFPropalesGA
{
    public  $emetteur;    // Objet societe qui emet

    private $B=0;
    private $I=0;
    private $U=0;


    /**
    \brief      Constructeur
    \param        db        Handler acces base de donnee
    */
    function pdf_propaleGA_finaproGAStd($db)
    {
        global $conf,$langs,$mysoc;

        $this->B=0;
        $this->I=0;
        $this->U=0;
        $this->HREF='';

        $langs->load("main");
        $langs->load("bills");

        $this->db = $db;
        $this->name = "finaproGAStd";
        $this->libelle = "Finapro GA Evolution";
        $this->description = $langs->trans('PDFAzurDescription');

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
    \brief      Fonction generant la propale sur le disque
    \param        propale            Objet propal a generer (ou id si ancienne methode)
        \param        outputlangs        Lang object for output language
        \return        int             1=ok, 0=ko
        */
    function write_file($propale,$outputlangs='')
    {
        global $user,$langs,$conf;

        if (! is_object($outputlangs)) $outputlangs=$langs;
        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("bills");
        $outputlangs->load("propal");
        $outputlangs->load("products");

        $outputlangs->setPhpLang();

        if ($conf->PROPALEGA->dir_output)
        {
            // Definition de l'objet $propale (pour compatibilite ascendante)
            if (! is_object($propale))
            {
                $id = $propale;
                $propale = new Propal($this->db,"",$id);
                $propale->simulator = 1;
                $ret=$propale->fetch($id);
            }
            $deja_regle = "";

            // Definition de $dir et $file
            if ($propale->specimen)
            {
                $dir = $conf->PROPALEGA->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $propref = sanitize_string($propale->ref);
                $dir = $conf->PROPALEGA->dir_output . "/" . $propref;
                $file = $dir . "/" . $propref . ".pdf";
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
                $nblignes = sizeof($propale->lignes);

                // Protection et encryption du pdf
                if ($conf->global->PDF_SECURITY_ENCRYPTION)
                {
                    $pdf=new FPDI_Protection('P','mm',$this->format);
                    $pdfrights = array('print'); // Ne permet que l'impression du document
                    $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
                    $pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
                    $pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
                } else {
                    $pdf=new FPDI('P','mm',$this->format);
                }

                $pdf->Open();
                $pdf->AddPage();

                $pdf->SetDrawColor(128,128,128);

                $pdf->SetTitle($propale->ref);
                $pdf->SetSubject($outputlangs->transnoentities("CommercialProposal"));
                $pdf->SetCreator("GLE");
                $pdf->SetAuthor($user->fullname);

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(1,0);
                //Font

                $pdf->AddFont('Vera','','Vera.php');
                $pdf->AddFont('Vera','B','VeraBd.php');
                $pdf->AddFont('Vera','BI','VeraBI.php');
                $pdf->AddFont('Vera','I','VeraIt.php');


                // Tete de page
                $this->_pagehead($pdf, $propale, 1, $outputlangs);

                $pagenb = 1;
                $tab_top = 48;
                $tab_top_newpage = 7;
                $tab_height = 50;
                $tab_height_newpage = 50;
                $tab_height_middlepage = 50;
                $iniY = $tab_top + 8;
                $curY = $tab_top + 8;
                $nexY = $tab_top + 8;

                $pdf->SetFillColor(255,255,255);
                $pdf->SetDrawColor(0,0,0);
                $pdf->SetTextColor(0,0,0);
                $pdf->SetFont('Vera','',7.5);
                $pdf->Ln(4);

                $genre = "Monsieur";

                $finaHeader = "Nos Références: ".$propale->ref."<br/>";
                $finaHeader .= "<strong>Objet : Location Evolutive,</strong><br/>";
                $finaHeader .= "Monsieur,<BR><BR>Nous avons le plaisir de vous communiquer ci-après un descriptif de nos services ainsi que notre proposition de <strong>Location Evolutive</strong> concernant les produits informatiques que vous souhaitez acquérir.<BR><BR>FINAPRO a développé deux modules de services de gestion de parc associés à la Location Evolutive :<BR>".chr(149)." <strong>La Gestion Budgétaire</strong> et Technologique des contrats de location.<BR>".chr(149)." <strong>La Gestion des Evolutions</strong> et les conseils associés.<BR><BR>La <strong>Location Evolutive</strong> FINAPRO permet de faire évoluer vos produits informatiques à n".chr(146)."importe quelle étape de leur cycle de vie.<BR><BR>Le module <strong>Suivi Budgétaire et Technologique</strong> de FINAPRO vous offre la possibilité de piloter votre parc informatique : vous bénéficiez d".chr(146)." un « Reporting Dynamique » de vos contrats de location, de récapitulatifs budgétaires par contrat, par produit ".chr(133)."<BR>Nous restons bien sûr à votre disposition pour tout renseignement complémentaire et vous prions de croire, ".$genre." en  l".chr(146)."assurance de nos meilleures salutations.";
                $pdf->SetXY ($this->marge_gauche , $nexY );

                $pdf->lasth = $pdf->lasth * 0.9;
                $this->WriteHTML($finaHeader,1,0,$pdf);

                $tmpUrs = new User($propale->db);
                $tmpUrs->fetch($propale->commercial_signataire_refid);

                $pdf->MultiCell($this->page_largeur-($this->marge_gauche + $this->marge_droite), 5, strtoupper(utf8_decode(utf8_encode($tmpUrs->fullname))), 0, 'R', 0);

                $pdf->SetDrawColor(236,236,236);
                $pdf->line($this->marge_gauche, $pdf->getY()   , $this->page_largeur-$this->marge_droite, $pdf->getY() );
                $pdf->SetDrawColor(0,0,0);


                //cartouche du bas
                $x = $pdf->getX();
                $y = $pdf->getY() + 5;
                $pdf->SetX($this->marge_gauche);
                $cartoucheBasY = 170;
                if ($propale->lignes[0]->total_ht > 0)
                {
                    $cartoucheBasY += 5;
                }
                if ($propale->lignes[1]->total_ht > 0)
                {
                    $cartoucheBasY += 5;
                }
                if ($propale->lignes[2]->total_ht > 0)
                {
                    $cartoucheBasY += 5;
                }
                $pdf->SetY($cartoucheBasY);

                $pdf->SetMargins(120, $this->marge_haute, $this->marge_droite);

                $html = "<br>".chr(149)." Loyers Mensuels, Constants.<br>".chr(149)." Règlement ". ($propale->echu==1?'A Terme Echu':'Terme A Echoir')  .".<br>".chr(149)." Assurance Vol/Bris Multirisques <bi>non-incluse</bi>.<br>".chr(149)." Services de Gestion de Parc FINAPRO inclus.";
                $pdf->lasth = $pdf->lasth * 0.9;
                $this->WriteHTML($html,1,0,$pdf);

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

                $html ="<br><iu>CONDITIONS D".chr(146)."ETABLISSEMENT DE CETTE PROPOSITION :</iu>";
                $pdf->lasth = $pdf->lasth * 0.9;
                $this->WriteHTML($html,1,0,$pdf);
                $pdf->SetX($this->marge_gauche * 2);
                $pdf->SetMargins($this->marge_gauche * 2, $this->marge_haute, $this->marge_droite);
                $html = chr(149)." Durée de validité : ".utf8_encode($propale->cond_reglement).".<br>".chr(149)." Paiement des loyers par prélèvement automatique sur compte bancaire, terme à échoir.<br>Les loyers mentionnés ci-dessus sont indicatifs et révisables jusqu".chr(146)."à l".chr(146)."installation complète des produits suivant l".chr(146)."évolution des marchés financiers, selon la formule de révision [(2*EURIBOR 1 an) + TEC5] / 3.<br><uib>Cette offre de financement est soumise à l".chr(146)."accord du Comité des Engagements FINAPRO.</uib><br><br><br>";
                $pdf->lasth = $pdf->lasth * 0.9;
                $this->WriteHTML($html,1,0,$pdf);

                $pdf->SetMargins($this->marge_gauche * 2 - 5, $this->marge_haute, $this->marge_droite);
                $pdf->SetX($this->marge_gauche * 2 - 5 );

                $pdf->SetDrawColor(229,229,229);
                $pdf->Rect($this->marge_gauche * 2 - 8 , $pdf->GetY() - 3 , 100 , 25 ,'D');

                $html = "<strong>Remis en double exemplaire.<br>Mention Manuscrite : « Bon pour Accord »<br>Nom et fonction du Signataire :<br>Date et lieu :<br>Cachet et signature. </strong>";
                $pdf->lasth = $pdf->lasth * 0.9;
                $this->WriteHTML($html,1,0,$pdf);


                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);

                $pdf->SetFillColor(147,117,172);
                $pdf->SetDrawColor(255,255,255);
                $pdf->SetTextColor(0,0,0);
                $pdf->SetXY ($this->marge_gauche , $y -5);
                //On affiche le loyer HT , TTC, le nb de mensualité, la périodicité (si echu ou echoir)
                $larg = $this->page_largeur-($this->marge_gauche + $this->marge_droite);
                $larg1 = $larg / 4;
                $pdf->SetX($this->marge_gauche);
                $tmpSocFourn = new Societe($this->db);
                $tmpSocFourn->fetch($propale->fournisseur_refid);
                $html ='<br>Description des matériels loué<br><br>Voir devis  N°: '. utf8_decode($propale->ref_client).' du '.date("d/m/Y",$propale->date_devis_fourn). " de la société ".$tmpSocFourn->nom;
                $this->writeHTML($html,2,0,$pdf);

                $pdf->SetFont('',"B");
                $pdf->SetY($pdf->getY()+5);
                $pdf->SetX($larg1);
                $pdf->MultiCell($larg1, 6,"Montant total (HT):" , 0, 'R', 0);
                $pdf->SetY($pdf->getY() - 6); // Same line
                $pdf->SetX(2*$larg1+1);
                $pdf->MultiCell($larg1, 6, price($propale->total_ht) . ' ' . chr(128) , 0, 'R', 0);
                $repos = 15;
                if ($propale->lignes[0]->total_ht > 0)
                {
                    $pdf->SetX($larg1);
                    $pdf->SetFont('',"I");
                    $pdf->MultiCell($larg1, 6,utf8_decode("Matériel (HT):") , 0, 'R', 0);
                    $pdf->SetY($pdf->getY() - 6); // Same line
                    $pdf->SetX(2*$larg1+1);
                    $pdf->MultiCell($larg1, 6, price($propale->lignes[0]->total_ht) . ' ' . chr(128) , 0, 'R', 0);
                    $repos += 10;
                }
                if ($propale->lignes[1]->total_ht > 0 )
                {
                    $pdf->SetX($larg1);
                    $pdf->SetFont('',"I");
                    $pdf->MultiCell($larg1, 6,"Logiciel (HT):" , 0, 'R', 0);
                    $pdf->SetY($pdf->getY() - 6); // Same line
                    $pdf->SetX(2*$larg1+1);
                    $pdf->MultiCell($larg1, 6, price($propale->lignes[1]->total_ht) . ' ' . chr(128) , 0, 'R', 0);
                    $repos += 10;
                    if ($repos > 30) $repos -= 5;
                }
                if ($propale->lignes[2]->total_ht > 0 )
                {
                    $pdf->SetX($larg1);
                    $pdf->SetFont('',"I");
                    $pdf->MultiCell($larg1, 6,"Services (HT):" , 0, 'R', 0);
                    $pdf->SetY($pdf->getY() - 6); // Same line
                    $pdf->SetX(2*$larg1+1);
                    $pdf->MultiCell($larg1, 6, price($propale->lignes[2]->total_ht) . ' ' . chr(128) , 0, 'R', 0);
                    $repos += 10;
                    if ($repos > 30) $repos -= 5;
                }

                $larg1 = $larg / 6;
                $pdf->SetFont('',"B");
                $nexY=$y+$repos + 10;
                $pdf->SetY($nexY);
                $pdf->SetX($pdf->getX()+$larg1+$larg1/2);
                $pdf->MultiCell($larg1, 6, $outputlangs->transnoentities(utf8_decode("Loyer HT")), 0, 'C', 1);
                $pdf->SetXY($larg1+$larg1+$larg1/2+$this->marge_gauche+2,$nexY);
                $pdf->MultiCell($larg1, 6, $outputlangs->transnoentities(utf8_decode("Durée")), 0, 'C', 1);
                $pdf->SetXY($larg1+$larg1+$larg1+$larg1/2+$this->marge_gauche+3,$nexY);
                $pdf->MultiCell($larg1, 6, $outputlangs->transnoentities(utf8_decode("Echéance")), 0, 'C', 1);

                $bottomlasttab = $pdf->getY();

                $pdf->SetXY($this->marge_gauche , $bottomlasttab);

                $pdf->SetFont('Vera','B',10);

                $loyerHT = $propale->loyerHTGlobal;
                $durr = $propale->dureeGA;
                $echeance = utf8_decode($propale->echeance);


                $pdf->SetXY($larg1+$larg1/2+$this->marge_gauche+1,$bottomlasttab);
                $pdf->MultiCell($larg1, 6, price(round($loyerHT*100)/100 ) ." ". chr(128), 0, 'C', 0);
                $pdf->SetXY($larg1+$larg1/2+$larg1+$this->marge_gauche+2,$bottomlasttab);
                $pdf->MultiCell($larg1, 6, round($durr) ." Mois", 0, 'C', 0);
                $pdf->SetXY($larg1+$larg1/2+$larg1+$larg1+$this->marge_gauche+3,$bottomlasttab);
                $pdf->MultiCell($larg1, 6, $echeance, 0, 'C', 0);

                $pdf->SetDrawColor(44,44,44);
                $pdf->SetTextColor(44,44,44);
                $pdf->SetFont('Vera','',8);
                $bottomlasttab = $pdf->getY() + 10;

                // Pied de page
                $this->_pagefoot($pdf,$outputlangs);
                $pdf->AliasNbPages();

                $pdf->Close();

                $this->file = $file;$pdf->Output($file);


                $langs->setPhpLang();    // On restaure langue session
                return 1;   // Pas d'erreur
            } else {
                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                $langs->setPhpLang();    // On restaure langue session
                return 0;
            }
        } else {
            $this->error=$langs->trans("ErrorConstantNotDefined","PROP_OUTPUTDIR");
            $langs->setPhpLang();    // On restaure langue session
            return 0;
        }

        $this->error=$langs->trans("ErrorUnknown");
        $langs->setPhpLang();    // On restaure langue session
        return 0;   // Erreur par defaut
    }

    function writeHTML($html, $ln=2, $fill=0,&$pdf)
    {
        // store some variables
        $html=strip_tags($html,"<h1><h2><h3><h4><h5><h6><bi><strong><u><ui><iu><uib><ubi><bui><biu><uib><ubi><i><center><a><img><p><br><br/><strong><em><font><span><blockquote><li><ul><ol><hr><td><th><tr><table><sup><sub><small>"); //remove all unsupported tags
        //replace carriage returns, newlines and tabs
        $repTable = array("\t" => " ", "\n" => "<br>", "\r" => " ", "\0" => " ", "\x0B" => " ");
        $html = strtr($html, $repTable);
        $pattern = '/(<[^>]+>)/U';
        $a = preg_split($pattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY); //explodes the string

        if (empty($pdf->lasth)) {
            $pdf->lasth = $pdf->FontSize * K_CELL_HEIGHT_RATIO / 2;
        }
        foreach($a as $key=>$element) {
            if (!preg_match($pattern, $element)) {
                //Text
                if($this->HREF) {
                    $pdf->addHtmlLink($this->HREF, $element, $fill);
                } else {
                    $pdf->Write($pdf->lasth , stripslashes(utf8_decode($pdf->unhtmlentities($element))), '', $fill);
                }
            } else {
                $element = substr($element, 1, -1);
                //Tag
                if($element{0}=='/') {
                    $this->closedHTMLTagHandler(strtolower(substr($element, 1)),$pdf);
                } else {
                    //Extract attributes
                    // get tag name
                    preg_match('/([a-zA-Z0-9]*)/', $element, $tag);
                    $tag = strtolower($tag[0]);
                    // get attributes
                    preg_match_all('/([^=\s]*)=["\']?([^"\']*)["\']?/', $element, $attr_array, PREG_PATTERN_ORDER);
                    $attr = array(); // reset attribute array

                    while(list($id,$name)=each($attr_array[1])) {
                        $attr[strtolower($name)] = $attr_array[2][$id];
                    }

                    $this->openHTMLTagHandler($tag, $attr, $fill,$pdf);
                }
            }
        }
        if ($ln) {
            $pdf->Ln($pdf->lasth);
        }
    }

    function closedHTMLTagHandler($tag,$pdf) {
        //Closing tag
        switch($tag) {
            case 'strong': {
                $pdf->setStyle('b', false);
                $pdf->setFont('Vera',"");
                break;
            }
            case 'em': {
                $pdf->setStyle('i', false);
                $pdf->setFont('Vera',"");
                break;
            }
            case 'center': {
                $pdf->SetX($this->marge_gauche);
                break;
            }

            case 'biu':
            case 'bui':
            case 'ubi':
            case 'uib':
            case 'ibu':
            case 'iub': {
                $pdf->setStyle('u', false);
                $pdf->setStyle('b', false);
                $pdf->setStyle('i', false);
                $pdf->setFont('',"");
                break;
            }
            case 'ib':
            case 'bi': {
                $pdf->setStyle('b', false);
                $pdf->setStyle('i', false);
                $pdf->setFont('',"");
                break;
            }
            case 'iu':
            case 'ui': {
                $pdf->setStyle('i', false);
                $pdf->setStyle('u', false);
                $pdf->setFont('',"");
                break;
            }
            case 'b':
            case 'i':
            case 'u': {
                $pdf->setStyle($tag, false);
                $pdf->setFont('Vera',"");
                break;
            }
            case 'a': {
                $pdf->HREF = '';
                break;
            }
            case 'small': {
                $currentFontSize = $pdf->FontSize;
                $pdf->SetFontSize($pdf->tempfontsize);
                $pdf->tempfontsize = $pdf->FontSizePt;
                $pdf->SetXY($pdf->GetX(), $pdf->GetY() - (($pdf->FontSize - $currentFontSize)/3));
                break;
            }
            case 'span':
            case 'font': {
                if ($pdf->issetcolor == true) {
                    $pdf->SetTextColor($pdf->prevTextColor[0], $pdf->prevTextColor[1], $pdf->prevTextColor[2]);
                }
                if ($pdf->issetfont) {
                    $pdf->FontFamily = $pdf->prevFontFamily;
                    $pdf->FontStyle = $pdf->prevFontStyle;
                    $pdf->SetFont($pdf->FontFamily);
                    $pdf->issetfont = false;
                }
                $currentFontSize = $pdf->FontSize;
                $pdf->SetFontSize($pdf->tempfontsize);
                $pdf->tempfontsize = $pdf->FontSizePt;
                //$pdf->TextColor = $pdf->prevTextColor;
                $pdf->lasth = $pdf->FontSize * K_CELL_HEIGHT_RATIO;
                break;
            }
        }
    }

    function openHTMLTagHandler($tag, $attr, $fill=0,&$pdf) {
        //Opening tag
        switch($tag) {
            case 'hr': {
                $pdf->Ln();
                if ((isset($attr['width'])) AND ($attr['width'] != '')) {
                    $hrWidth = $attr['width'];
                }
                else {
                    $hrWidth = $pdf->w - $pdf->lMargin - $pdf->rMargin;
                }
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->SetLineWidth(0.2);
                $pdf->Line($x, $y, $x + $hrWidth, $y);
                $pdf->SetLineWidth(0.2);
                $pdf->Ln();
                break;
            }
            case 'strong': {
                $pdf->setStyle('B', true);
                $pdf->setFont('Vera',"B");
                //$this->write('aa');

                break;
            }
            case 'em': {
                $pdf->setStyle('i', true);
                $pdf->setFont('',"i");
                break;
            }
            case 'ib':
            case 'bi': {
                $pdf->setStyle('bi', true);
                $pdf->setFont('',"bi");
                break;
            }
            case 'iu':
            case 'ui': {
                $pdf->setStyle('i', true);
                $pdf->setStyle('u', true);
                $pdf->setFont('',"ui");
                break;
            }
            case 'biu':
            case 'bui':
            case 'ubi':
            case 'uib':
            case 'ibu':
            case 'iub': {
                $pdf->setStyle('b', true);
                $pdf->setStyle('i', true);
                $pdf->setStyle('u', true);
                $pdf->setFont('',"ubi");
                break;
            }
            case 'b':
            case 'i':
            case 'u': {
                $pdf->setStyle($tag, true);
                $pdf->setFont('',"",strtoupper($tag));
                break;
            }
            case 'a': {
                $pdf->HREF = $attr['href'];
                break;
            }
            case 'br': {
                $pdf->Ln();
                if(strlen($pdf->lispacer) > 0) {
                    $pdf->x += $pdf->GetStringWidth($pdf->lispacer);
                }
                break;
            }
            case 'center': {
                $pdf->setX($attr['centerx']);
                $pdf->x = $attr['centerx'];

                break;
            }
        }
    }


    /*
    *       \brief      Affiche en-tete propale
    *       \param      pdf             Objet PDF
    *       \param      object            Objet propale
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
        if($object->statut==0 && (! empty($conf->global->PROPALEGA_DRAFT_WATERMARK)) )
        {
            $watermark_angle=atan($this->page_hauteur/$this->page_largeur);
            $watermark_x=5;
            $watermark_y=$this->page_hauteur-25;  //Set to $this->page_hauteur-50 or less if problems
            $watermark_width=$this->page_hauteur;
            $pdf->SetFont('Vera','B',50);
            $pdf->SetTextColor(255,192,203);
            //rotate
            $pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($watermark_angle),sin($watermark_angle),-sin($watermark_angle),cos($watermark_angle),$watermark_x*$pdf->k,($pdf->h-$watermark_y)*$pdf->k,-$watermark_x*$pdf->k,-($pdf->h-$watermark_y)*$pdf->k));
            //print watermark
            $pdf->SetXY($watermark_x,$watermark_y);
            $pdf->Cell($watermark_width,25,clean_html($conf->global->PROPALEGA_DRAFT_WATERMARK),0,2,"C",0);
            //antirotate
            $pdf->_out('Q');
        }

        //Prepare la suite
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFont('Vera','B',13);

        $posy=$this->marge_haute;

        $pdf->SetXY($this->marge_gauche,$posy);

        $logo = false;
        if (is_file ($conf->societe->dir_logos.'/'.$this->emetteur->logo."noalpha.png"))
        {
            $logo=$conf->societe->dir_logos.'/'.$this->emetteur->logo."noalpha.png";
        } else {
            $logo=$conf->societe->dir_logos.'/'.$this->emetteur->logo;
        }
        if ($this->emetteur->logo)
        {
            if (is_readable($logo))
            {
                $pdf->Image($logo, ($this->page_largeur / 2) - 30 , 0, 0, 24,"","http://www.finapro.fr/");
            } else {
                $pdf->SetTextColor(200,0,0);
                $pdf->SetFont('Vera','B',8);
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        } else if (defined("FAC_PDF_INTITULE")) {
            $pdf->MultiCell(100, 4, FAC_PDF_INTITULE, 0, 'L');
        }


        if ($showadress)
        {
//            // Emetteur
            $hautcadre=40;
            // Client destinataire
            $posy=28;
            $pdf->SetTextColor(0,0,0);
            $pdf->SetFont('Vera','',7);
            $object->fetch_client();

            // If BILLING contact defined on invoice, we use it
            $usecontact=false;
            if ($conf->global->PROPALEGA_USE_CUSTOMER_CONTACT_AS_RECIPIENT != 1)
            {
                $conf->global->PROPALEGA_USE_CUSTOMER_CONTACT_AS_RECIPIENT=1;
            }
            if ($conf->global->PROPALEGA_USE_CUSTOMER_CONTACT_AS_RECIPIENT)
            {
                $arrayidcontact=$object->getIdContact('external','BILLING');

                if (count($arrayidcontact) > 0)
                {
                    $usecontact=true;
                    $result=$object->fetch_contact($arrayidcontact[0]);
                }
            }
            if (!$usecontact)
            {
                // Nom societe
                $pdf->SetXY(122,$posy+3);
                $pdf->SetFont('Vera','B',10);
                $pdf->MultiCell(96,4, $object->client->nom, 0, 'L');

                // Nom client
//                $carac_client = "\n".$object->client->getFullName($outputlangs,1,1);

                // Caracteristiques client
                $carac_client="\n".$object->client->adresse;
                $carac_client.="\n".$object->client->cp . " " . $object->contact->ville."\n";
                //Pays si different de l'emetteur
                if ($this->emetteur->pays_code != $object->contact->pays_code)
                {
                    $carac_client.=dol_entity_decode($object->contact->pays)."\n";
                }
            } else {
                // Nom client
                $pdf->SetXY(122,$posy+3);
                $pdf->SetFont('Vera','B',9);
                $pdf->MultiCell(96,4, $object->client->nom, 0, 'L');

                // Nom du contact suivi propal si c'est une societe
                $arrayidcontact = $object->getIdContact('external','BILLING');
                if (sizeof($arrayidcontact) > 0)
                {
                    $object->fetch_contact($arrayidcontact[0]);
                    // On verifie si c'est une societe ou un particulier
                    if( !preg_match('#'.$object->contact->getFullName($outputlangs,1).'#isU',$object->client->nom) )
                    {
                        $carac_client .= "\nA l'attention de ".$object->contact->getFullName($outputlangs,1,1);
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
//            // Numero TVA intracom
//            if ($object->client->tva_intra) $carac_client.="\n".$outputlangs->transnoentities("VATIntraShort").': '.$object->client->tva_intra;

            $pdf->SetFont('Vera','',8);
            $posy=$pdf->GetY()-9; //Auto Y coord readjust for multiline name
            $pdf->SetXY(122,$posy+6);
            $pdf->MultiCell(86,4, $carac_client);

            //Date et Lieu
            $pdf->SetFont('Vera','',8);
            $posy=$pdf->GetY() + 3; //Auto Y coord readjust for multiline name
            $pdf->SetXY(122,$posy);
            setlocale (LC_TIME, 'fr_FR');
            $pdf->MultiCell(86,4, "Aix En Provence, le ".strftime("%A %d %B %Y"));
            //exit();
        }

    }

    /*
    *   \brief      Affiche le pied de page
    *   \param      pdf     objet PDF
    */
    function _pagefoot(&$pdf,$outputlangs)
    {
        return pdf_pagefoot($pdf,$outputlangs,'PROPALE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur);
    }

}

?>
