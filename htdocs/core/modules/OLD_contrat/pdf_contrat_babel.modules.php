<?php
/*
  * GLE by DRSI & Synopsis
  *
  * Author: SAURON Tommy <tommy@drsi.fr>
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
 \file       htdocs/includes/modules/contrat/pdf_contrat_babel.modules.php
 \ingroup    contrat
 \brief      Fichier de la classe permettant de generer les contrats au modele babel
 \author        Laurent Destailleur
 \version    $Id: pdf_contrat_babel.modules.php,v 1.121 2008/08/07 07:47:38 eldy Exp $
 */

require_once(DOL_DOCUMENT_ROOT."/includes/modules/contrat/modules_contrat.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
 \class      pdf_contrat_babel
 \brief      Classe permettant de generer les contrats au modele babel
 */

class pdf_contrat_babel extends ModeleSynopsiscontrat
{
    var $emetteur;    // Objet societe qui emet


    /**
    \brief      Constructeur
    \param        db        Handler acces base de donnee
    */
    function pdf_contrat_babel($db)
    {
        global $conf,$langs,$mysoc;

        $langs->load("main");
        $langs->load("bills");

        $this->db = $db;
        $this->name = "babel";
        $this->description = $langs->trans('PDFContratbabelDescription');

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

    }

    /**
    \brief      Fonction generant la contrat sur le disque
    \param        contrat            Objet contrat a generer (ou id si ancienne methode)
        \param        outputlangs        Lang object for output language
        \return        int             1=ok, 0=ko
        */
    function write_file($contrat,$outputlangs='')
    {
        global $user,$langs,$conf;

        if (! is_object($outputlangs)) $outputlangs=$langs;
        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("bills");
        $outputlangs->load("contrat");
        $outputlangs->load("products");

        $outputlangs->setPhpLang();

        if ($conf->contrat->dir_output)
        {
            // Definition de l'objet $contrat (pour compatibilite ascendante)
            if (! is_object($contrat))
            {
                $id = $contrat;
                $contrat = new Contrat($this->db);
                $ret=$contrat->fetch($id);
            }

            // Definition de $dir et $file
            if ($contrat->specimen)
            {
                $dir = $conf->contrat->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $propref = sanitize_string($contrat->ref);
                $dir = $conf->contrat->dir_output . "/".$contrat->fk_user . $propref;
                $file = $dir ."/" . $propref . ".pdf";
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
                $nblignes = sizeof($contrat->lignes);
                // Protection et encryption du pdf
                if ($conf->global->PDF_SECURITY_ENCRYPTION)
                {
                    $pdf=new FPDI_Protection('L','mm',$this->format);
                    $pdfrights = array('print'); // Ne permet que l'impression du document
                    $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
                    $pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
                    $pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
                } else  {
                    $pdf=new FPDI('L','mm',$this->format);
                }
                $pdf->Open();
                $pdf->AddPage();

                $pdf->SetDrawColor(128,128,128);

                $pdf->SetTitle($contrat->ref);
                $pdf->SetSubject($outputlangs->transnoentities("Contract"));
                $pdf->SetCreator("GLE ".GLE_VERSION);
                $pdf->SetAuthor($user->getFullName($langs));

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(1,0);

                $pdf->AddFont('VeraMoBI', 'BI', 'VeraMoBI.php');
                $pdf->AddFont('fq-logo', 'Roman', 'fq-logo.php');

                // Tete de page
                $this->_pagehead($pdf, $contrat, 1, $outputlangs);
                $pdf->SetFont('Arial', '', 8);


//Affiche le header avec les infos basic du contrat
                $ref = $contrat->ref;
                $client = $contrat->societe->nom;
                $date = $contrat->date_contrat;
                $pdf->setXY(50,50);
                $pdf->MultiCell(100, 3, $ref, 0, 'L');
                $pdf->setXY(150,50);
                $pdf->MultiCell(100, 3,  $client, 0, 'L');
                $pdf->setXY(250,50);
                $pdf->MultiCell(100, 3,  date("d/m/Y",$date), 0, 'L');

                $contrat->fetch_lignes();
                $pdf->setXY(50,60);
                $pdf->MultiCell(50, 3,  "Services :".$contrat->nbofservices, 0, 'L');
                $pdf->setXY(100,60);
                $pdf->MultiCell(50, 3,  "En attente :".$contrat->nbofserviceswait, 0, 'L');
                $pdf->setXY(150,60);
                $pdf->MultiCell(50, 3,  "Ouverts :".$contrat->nbofservicesopened, 0, 'L');
                $pdf->setXY(200,60);
                $pdf->MultiCell(50, 3,  "Fermés :".$contrat->nbofservicesclosed, 0, 'L');

                $baseY = 80;
                $i=0;
                foreach( $contrat->lignes as $key=>$val)
                {

                    $pdf->setXY(20,$baseY + 10*$i);
                    $pdf->MultiCell(100, 3, $val->ref, 0, 'L');
                    $pdf->setXY(120,$baseY + 10*$i);
                    $pdf->MultiCell(100, 3, $val->libelle, 0, 'L');
                    $i++;
                    $pdf->setXY(20,$baseY + 10*$i);
                    $desc = $val->product_desc;
                    if ('x'.$desc == "x")
                    {
                        $desc = $val->desc;
                    }
                    $pdf->MultiCell(100, 3,  $desc, 0, 'L');
                    $pdf->setXY(120,$baseY + 10*$i);
                    $pdf->MultiCell(20, 3,  $val->qty, 0, 'L');
                    $pdf->setXY(140,$baseY + 10*$i);
                    $pdf->MultiCell(20, 3,  $val->tva_tx, 0, 'L');
                    $pdf->setXY(160,$baseY + 10*$i);
                    $pdf->MultiCell(20, 3,  $val->subprice, 0, 'L');
                    $pdf->setXY(180,$baseY + 10*$i);
                    $pdf->MultiCell(20, 3,  $val->remise_percent, 0, 'L');
                    $pdf->setXY(200,$baseY + 10*$i);
                    $pdf->MultiCell(20, 3,  $val->price, 0, 'L');
                    $i++;
                    $pdf->setXY(20,$baseY + 10*$i);
                    $pdf->MultiCell(40, 3,  $val->date_debut_prevue, 0, 'L');
                    $pdf->setXY(60,$baseY + 10*$i);
                    $pdf->MultiCell(40, 3,  $val->date_debut_reel, 0, 'L');
                    $pdf->setXY(100,$baseY + 10*$i);
                    $pdf->MultiCell(40, 3,  $val->date_fin_prevue, 0, 'L');
                    $pdf->setXY(140,$baseY + 10*$i);
                    $pdf->MultiCell(40, 3,  $val->date_fin_reel, 0, 'L');
                    $pdf->setXY(180,$baseY + 10*$i);
                    $pdf->MultiCell(20, 3,  $val->statut, 0, 'L');
                    $i++;
                }
//Affiche les services avec les infos du services

                // Pied de page
                $this->_pagefoot($pdf,$outputlangs);
                $pdf->AliasNbPages();
                $pdf->Close();
                $pdf->Output($file);

                $langs->setPhpLang();    // On restaure langue session


                return 1;   // Pas d'erreur
            } else {
                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                $langs->setPhpLang();    // On restaure langue session
                return 0;
            }
        } else {
            $this->error=$langs->trans("ErrorConstantNotDefined","CONTRACT_OUTPUTDIR");
            $langs->setPhpLang();    // On restaure langue session
            return 0;
        }

        $this->error=$langs->trans("ErrorUnknown");
        $langs->setPhpLang();    // On restaure langue session
        return 0;   // Erreur par defaut
    }

    function FancyTable()
    {
        $ret = "";
        return($ret);

    }


        function _pagehead(& $pdf, $object, $showadress = 1, $outputlangs, $currentPage) {
        global $conf, $langs;
        if ($currentPage > 1)
        {
            $showadress=0;
        }

        $outputlangs->load("main");
        $outputlangs->load("bills");
        $outputlangs->load("propal");
        $outputlangs->load("companies");

        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('Arial', 'B', 13);

        $posy = $this->marge_haute;

        $pdf->SetXY($this->marge_gauche, $posy);

        // Logo
        $logo = false;
        if (is_file ($conf->mycompany->dir_output .'/logos'.'/'.$this->emetteur->logo."noalpha.png"))
        {
            $logo=$conf->mycompany->dir_output .'/logos'.'/'.$this->emetteur->logo."noalpha.png";
        } else {
            $logo=$conf->mycompany->dir_output .'/logos'.'/'.$this->emetteur->logo;
        }

//        $logo = $conf->mycompany->dir_output .'/logos' . '/' . $this->emetteur->logo;
        if ($this->emetteur->logo) {
            if (is_readable($logo)) {
                $pdf->Image($logo, $this->marge_gauche, $posy, 0, 24);
            } else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
            }
        } else if (defined("FAC_PDF_INTITULE")) {
                $pdf->MultiCell(100, 4, FAC_PDF_INTITULE, 0, 'L');
            }

        }

    /*
    *   \brief      Affiche le pied de page
    *   \param      pdf     objet PDF
    */
    function _pagefoot(&$pdf,$outputlangs)
    {
        return pdf_pagefoot($pdf,$outputlangs,'CONTRAT_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche + 40,$this->page_hauteur);
    }

}

?>
