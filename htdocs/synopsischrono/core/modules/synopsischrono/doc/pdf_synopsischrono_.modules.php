<?php

/*

  /*
 * or see http://www.gnu.org/
 */

/**
  \file       htdocs/core/modules/panier/pdf_panier_babel.modules.php
  \ingroup    panier
  \brief      Fichier de la classe permettant de generer les paniers au modele BIMP
  \author     Tommy SAURON
  \version    $Id: pdf_panier_bimp.modules.php,v 1.121 2011/08/07  $
 */
require_once(DOL_DOCUMENT_ROOT . "/synopsischrono/core/modules/synopsischrono/modules_synopsischrono.php");
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';


/**
  \class      pdf_panier_babel
  \brief      Classe permettant de generer les paniers au modele babel
 */
if (!defined('EURO'))
    define('EURO', chr(128));

ini_set('max_execution_time', 600);

class pdf_synopsischrono_ extends ModeleSynopsischrono {

    public $emetteur;    // Objet societe qui emet

    /**
      \brief      Constructeur
      \param        db        Handler acces base de donnee
     */

    function __construct($db) {

        global $conf, $langs, $mysoc;

        $langs->load("main");
        $langs->load("bills");
        $this->debug = "";
        $this->db = $db;
        $this->name = "babel";
        $this->description = $langs->trans('PDFContratbabelDescription');

        // Dimension page pour format A4
        $this->type = 'pdf';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = 18;
        $this->marge_droite = 7;
        $this->marge_haute = 39;
        $this->marge_basse = 22;

        $this->option_logo = 1;                    // Affiche logo
        // Recupere emmetteur
        $this->emetteur = $mysoc;
        if (!$this->emetteur->pays_code)
            $this->emetteur->pays_code = substr($langs->defaultlang, -2);    // Par defaut, si n'etait pas defini
























            
// Defini position des colonnes
        $this->posxdesc = $this->marge_gauche + 1;
        $this->posxtva = 113;
        $this->posxup = 126;
        $this->posxqty = 145;
        $this->posxdiscount = 162;
        $this->postotalht = 174;
    }

    /**
      \brief      Fonction generant la panier sur le disque
      \param        panier            Objet panier a generer (ou id si ancienne methode)
      \param        outputlangs        Lang object for output language
      \return        int             1=ok, 0=ko
     */
    function write_file($chrono, $outputlangs = '') {
        global $user, $langs, $conf;
//        $this->marge_gauche = 55;
        $afficherPrix = false;

        if (!is_object($outputlangs))
            $outputlangs = $langs;
        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("bills");
        $outputlangs->load("panier");
        $outputlangs->load("products");
        //$outputlangs->setPhpLang();
        if ($conf->synopsischrono->dir_output) {
            // Definition de $dir et $file
            if (isset($chrono->specimen) && $chrono->specimen) {
                $dir = $conf->synopsischrono->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $propref = sanitize_string($chrono->ref);
                $dir = $conf->synopsischrono->dir_output . "/" . $chrono->id;
                $file = $dir . "/" . $propref . ".pdf";
            }
            $this->chrono = $chrono;

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                $pdf = "";

                $pdf = pdf_getInstance($this->format);
                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }

                $pdf1 = pdf_getInstance($this->format);
                if (class_exists('TCPDF')) {
                    $pdf1->setPrintHeader(false);
                    $pdf1->setPrintFooter(false);
                }

//
//
                $pdf->SetAutoPageBreak(1, 0);
//                if (class_exists('TCPDF')) {
//                    $pdf->setPrintHeader(false);
//                    $pdf->setPrintFooter(false);
//                }
//
//
                $pdf->Open();
                $pdf1->Open();
                $pdf->AddPage();
                $pdf1->AddPage();
                $pdf1->SetFont('', '', 8);

                // $pdf->SetDrawColor(128, 128, 128);



                $pdf->SetTitle($chrono->model->titre . ' : ' . $chrono->ref);

                $pdf->SetSubject($outputlangs->transnoentities("Panier"));
                $pdf->SetCreator("BIMP-ERP " . DOL_VERSION);
                $pdf->SetAuthor($user->getFullName($langs));
//
                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf1->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
// 
//                

                $this->_pagehead($pdf, $chrono, 1, $outputlangs);

                //Titre Page 1
//                $pdf->MultiCell(155, 6, 'Panier de ' . $chrono->societe->getFullName($outputlangs), 0, 'C');

                $pdf->MultiCell(155, 6, $chrono->model->nomDescription . ' : ' . $chrono->description . "\n\n", 0, 'L');

                if ($chrono->model->hasPropal) {
                    $pdf->MultiCell(155, 6, 'Propal : ' . $chrono->propal->ref . "\n\n", 0, 'L');
                }

                if ($chrono->model->hasProjet) {
                    $pdf->MultiCell(155, 6, 'Projet : ' . $chrono->projet->ref . " - " . $chrono->projet->titre . "\n\n", 0, 'L');
                }

                $chrono->getValuesPlus();
                foreach ($chrono->valuesPlus as $id => $tabKey) {
                    if (intval($pdf->GetY()) > 250) {
                        $this->_pagefoot($pdf, $chrono, $outputlangs);
                        $pdf->AddPage();
                        $this->_pagehead($pdf, $chrono, 0, $outputlangs);
                    }
                    $pdf->MultiCell(155, 6, $tabKey->nom . " : " . $tabKey->valueStr . "\n\n", 0, 'L');
                }




                $this->_pagefoot($pdf, $chrono, $outputlangs);


                if (method_exists($pdf, 'AliasNbPages'))
                    $pdf->AliasNbPages();
                $pdf->Close();

                $this->file = $file;
                $pdf->Output($file, 'F');
//
////                ////$langs->setPhpLang();    // On restaure langue session


                return 1;   // Pas d'erreur
            } else {
                $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                ////$langs->setPhpLang();    // On restaure langue session
                return 0;
            }
        } else {
            $this->error = $langs->trans("ErrorConstantNotDefined", "CONTRACT_OUTPUTDIR");
            ////$langs->setPhpLang();    // On restaure langue session
            return 0;
        }

        $this->error = $langs->trans("ErrorUnknown");
        ////$langs->setPhpLang();    // On restaure langue session
        return 0;   // Erreur par defaut
    }

    function _pagehead(& $pdf, $object, $showadress = 1, $outputlangs, $currentPage = 0) {
        global $conf, $langs;
        if ($currentPage > 1) {
            $showadress = 0;
        }

        $outputlangs->load("main");
        $outputlangs->load("bills");
        $outputlangs->load("propal");
        $outputlangs->load("companies");

        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', 13);

        $posy = $this->marge_haute;

        $pdf->SetXY(5, 13);

        // Logo
        $logo = false;
        if (is_file($conf->mycompany->dir_output . '/logos' . '/' . $this->emetteur->logo . "noalpha.png")) {
            $logo = $conf->mycompany->dir_output . '/logos' . '/' . $this->emetteur->logo . "noalpha.png";
        } else {
            $logo = $conf->mycompany->dir_output . '/logos' . '/' . $this->emetteur->logo;
        }

//        $logo = $conf->mycompany->dir_output .'/logos' . '/' . $this->emetteur->logo;
        if ($this->emetteur->logo) {
            if (is_readable($logo)) {
                $pdf->Image($logo, 5, 13, 0, 24);
            } else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->SetFont('', 'B', 8);
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
                $pdf->SetTextColor(0, 0, 0);
            }
        } else if (defined("FAC_PDF_INTITULE")) {
            $pdf->MultiCell(100, 4, FAC_PDF_INTITULE, 0, 'L');
        }

        $showaddress = $showadress;
        $usecontact = ($object->model->hasContact && $object->contactid >0);
        $object->client = $object->societe;
        $default_font_size = 12;



        $pdf->SetFont('', 'B', $default_font_size);

        $posx = 100;
        $posy = 10;
        $posy+=5;
        $largCadre = 206 - $this->marge_gauche;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref") . " : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

        $posy+=1;
        $pdf->SetFont('', '', $default_font_size - 2);
        $pdf->SetTextColor(0, 0, 60);


        if ($object->client->code_client) {
            $posy+=5;
            $pdf->SetXY($posx, $posy);
            $pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode") . " : " . $outputlangs->transnoentities($object->client->code_client), '', 'R');
        }

        $posy+=5;
        $pdf->SetXY($posx, $posy);
        $pdf->MultiCell(100, 3, $outputlangs->transnoentities("Type ") . " : " . $outputlangs->transnoentities($object->model->titre), '', 'R');



        if ($showadress) {

            // Sender properties
            $carac_emetteur = pdf_build_address($outputlangs, $this->emetteur);

            // Show sender
            $posy = 42;
            $posx = 15;
            if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT))
                $posx = $this->page_largeur - $this->marge_droite - 80;
            $hautcadre = 45;

            // Show sender frame
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($posx, $posy - 5);
            $pdf->MultiCell(66, 5, $outputlangs->transnoentities("BillFrom") . ":", 0, 'L');
            $pdf->SetXY($posx, $posy);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);
            $pdf->SetTextColor(0, 0, 60);

            // Show sender name
            $pdf->SetXY($posx + 2, $posy + 3);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
            $posy = $pdf->getY();

            // Show sender information
            $pdf->SetXY($posx + 2, $posy);
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');




            // Recipient name
            if (!empty($usecontact)) {
                // On peut utiliser le nom de la societe du contact
                if (!empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT))
                    $socname = $object->contact->socname;
                else
                    $socname = $object->client->nom;
                $carac_client_name = $outputlangs->convToOutputCharset($socname);
            }
            else {
                $carac_client_name = $outputlangs->convToOutputCharset($object->client->nom);
            }

            $carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->client, ($usecontact ? $object->contact : ''), $usecontact, 'target');

            // Show recipient
            $widthrecbox = 100;
            if ($this->page_largeur < 210)
                $widthrecbox = 84; // To work with US executive format
            $posy = 42;
            $posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
            if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT))
                $posx = $this->marge_gauche;

            // Show recipient frame
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($posx + 2, $posy - 5);
            $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo") . ":", 0, 'L');
            $pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

            // Show recipient name
            $pdf->SetXY($posx + 2, $posy + 3);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, 'L');

            // Show recipient information
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->SetXY($posx + 2, $posy + 4 + (dol_nboflines_bis($carac_client_name, 50) * 4));
            $pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');



            $pdf->Rect($this->marge_gauche - 3, 89, $largCadre, 185);
            $pdf->SetXY($this->marge_gauche, 92);
        }
        else {
            $pdf->SetFont('', 'B', 10);
            //Société
            if ($this->marge_gauche > 45) {
                $pdf->SetXY(3.5, 63);
                $pdf->MultiCell(39, 4, "Code Client : " . $object->societe->code_client, 0, "L");
                $pdf->SetXY(3.5, 54.5);
                $pdf->MultiCell(39, 4, 'Client : ' . $object->societe->getFullName($outputlangs), 0, 'L');
            }

            $pdf->Rect($this->marge_gauche - 3, 39, $largCadre, 235);
            $pdf->SetXY($this->marge_gauche, 42);
        }



        $pdf->SetFont('', 'B', 12);
    }

    /*
     *   \brief      Affiche le pied de page
     *   \param      pdf     objet PDF
     */

    function _pagefoot(&$pdf, $chrono, $outputlangs) {


        $pdf->SetFont('', 'B', 9);
        $pdf->SetTextColor(255, 63, 50);
        $pdf->SetDrawColor(0, 0, 0);
        //Société
        global $mysoc;

        $Y = 235;
        if ($this->marge_gauche > 45) {
            $pdf->SetXY(3.5, $Y + 20);
            $pdf->MultiCell(39, 4, utf8_encodeRien($mysoc->address), 0, "L");
            $pdf->SetXY(3.5, $Y + 25);
            $pdf->MultiCell(39, 4, utf8_encodeRien($mysoc->zip . " " . $mysoc->town), 0, "L");
            $pdf->SetXY(3.5, $Y + 30);
            if ($mysoc->phone != "")
                $pdf->MultiCell(39, 4, utf8_encodeRien("Tél. : " . $mysoc->phone), 0, "L");
            $pdf->SetXY(3.5, $Y + 35);
            if ($mysoc->fax != "")
                $pdf->MultiCell(39, 4, "Fax  : " . $mysoc->fax, 0, "L");
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', '', 7);
        $ligne = $mysoc->name;
        if (defined('MAIN_INFO_CAPITAL'))
            $ligne .= " au capital de " . MAIN_INFO_CAPITAL;
        if (defined('MAIN_INFO_RCS'))
            $ligne .= " - R.C.S. " . MAIN_INFO_RCS;
        elseif (defined('MAIN_INFO_SIREN'))
            $ligne .= " - R.C.S. " . MAIN_INFO_SIREN;
        if (defined('MAIN_INFO_APE'))
            $ligne .= " - APE " . MAIN_INFO_APE;
        if (defined('MAIN_INFO_TVAINTRA'))
            $ligne .= " - TVA/CEE " . MAIN_INFO_TVAINTRA;
        $ligne .= "\n\n" . "Document généré par BIMP-ERP Copyright © Synopsis & DRSI";

//        $ligne = "SA OLYS au capital de 85 372" . EURO . "    -   320 387 483 R.C.S. Lyon   -   APE 4741Z   -   TVA/CEE FR 34 320387483";
//        $ligne .= "\n" . "RIB : BPLL  -  13907. 00000.00202704667.45  -  CCP 11 158 41U Lyon";

        $pdf->SetXY($this->marge_gauche, $Y + 50);
        $pdf->MultiCell(200 - $this->marge_gauche, 3, $ligne, 0, "C");
        $pdf->line($this->marge_gauche - 4, $Y + 44, 203, $Y + 44);

        $pdf->SetFont('', 'B', 8);
        $pdf->SetTextColor(255, 63, 50);
        $pdf->SetXY(192, $Y + 55);
        $pdf->MultiCell(19, 3, '' . $pdf->PageNo() . '/{:ptp:}', 0, 'R', 0);

        //return pdf_pagefoot($pdf, $chrono,$outputlangs,'CONTRAT_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche + 40,$this->page_hauteur);
    }

    function hex2RGB($hexStr, $returnAsString = false, $seperator = ',') {
        $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
        $rgbArray = array();
        if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
            $colorVal = hexdec($hexStr);
            $rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
            $rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
            $rgbArray['blue'] = 0xFF & $colorVal;
        } elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
            $rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
            $rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
            $rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
        } else {
            return false; //Invalid hex color code
        }
        return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; // returns the rgb string or the associative array
    }

}

function couperChaine($chaine, $nb) {
    if (strlen($chaine) > $nb)
        $chaine = substr($chaine, 0, $nb) . "...";
    return $chaine;
}

function traiteStr($str) {
    return utf8_encodeRien(utf8_encodeRien(htmlspecialchars($str)));
}

function max_size($chaine, $lg_max) {
    if (strlen($chaine) > $lg_max) {
        $chaine = substr($chaine, 0, $lg_max);
        $last_space = strrpos($chaine, " ");
        $chaine = substr($chaine, 0, $last_space) . "...";
    }

    return $chaine;
}

?>
