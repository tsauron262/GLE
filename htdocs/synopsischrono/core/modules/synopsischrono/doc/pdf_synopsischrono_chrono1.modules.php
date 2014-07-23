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

class pdf_synopsischrono_chrono1 extends ModeleSynopsischrono {

    public $emetteur;    // Objet societe qui emet

    /**
      \brief      Constructeur
      \param        db        Handler acces base de donnee
     */

    function _construct($db) {

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
        $this->marge_gauche = 80;
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
        $this->marge_gauche = 55;
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
            // Definition de l'objet $chrono (pour compatibilite ascendante)
//            if (!is_object($chrono)) {
//                $id = $chrono;
//                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Contrat/class/panierMixte.class.php");
//                $chrono = getContratObj($id);
//                $chrono->fetch($id);
//                $chrono->fetch_lines(true);
////                $chrono = new ContratMixte($this->db);
////                $ret=$chrono->fetch($id);
//            } else {
//                $chrono->fetch_lines(true);
//            }
            // Definition de $dir et $file
            if (isset($chrono->specimen) && $chrono->specimen) {
                $dir = $conf->synopsischrono->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $propref = sanitize_string($chrono->ref);
                $dir = $conf->synopsischrono->dir_output . "/" . $chrono->id;
                $file = $dir . "/" . $propref . ".pdf";
            }
            $this->panier = $chrono;

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                $pdf = "";
                //$nblignes = sizeof($chrono->lines);
//                // Protection et encryption du pdf
//                if ($conf->global->PDF_SECURITY_ENCRYPTION) {
//                    $pdf = new FPDI_Protection('P', 'mm', $this->format);
//                    $pdfrights = array('print'); // Ne permet que l'impression du document
//                    $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
//                    $pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
//                    $pdf->SetProtection($pdfrights, $pdfuserpass, $pdfownerpass);
//                } else {
//
//                    $pdf = new FPDI('P', 'mm', $this->format);
//                }
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
//                $pdf1 = new FPDI('P', 'mm', $this->format);
//
//                $requete = "SELECT *
//                              FROM " . MAIN_DB_PREFIX . "Synopsis_panier_annexePdf as p,
//                                   " . MAIN_DB_PREFIX . "Synopsis_panier_annexe as a
//                             WHERE p.id = a.annexe_refid
//                               AND a.panier_refid = " . $chrono->id . " AND type = 1
//                          ORDER BY a.rang";
//                $sql = $this->db->query($requete);
//                $rang = 1;
//                $arrAnnexe = array();
//                while ($res = $this->db->fetch_object($sql)) {
//                    if ($res->afficheTitre == 1) {
//                        $arrAnnexe[$res->ref]['rang'] = $rang;
//                        $rang++;
//                    }
//                }
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


                $pdf->SetTitle("Panier");

                $pdf->SetSubject($outputlangs->transnoentities("Panier"));
                $pdf->SetCreator("GLE " . GLE_VERSION);
                $pdf->SetAuthor($user->getFullName($langs));
//
                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf1->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
//                $pdf->AddFont('VeraMoBI', 'BI', DOL_DOCUMENT_ROOT . '/Synopsis_Tools/font/VeraMoBI.php');
//                $pdf->AddFont('fq-logo', 'Roman', DOL_DOCUMENT_ROOT . '/Synopsis_Tools/font/fq-logo.php');
                // Tete de page
//                $chrono = new Object();

                $this->_pagehead($pdf, $chrono, 1, $outputlangs);
                $pdf->SetFont('', 'B', 12);

                //Titre Page 1
                $pdf->SetXY($this->marge_gauche, 42);
//                $pdf->MultiCell(157, 6, 'Panier de ' . $chrono->societe->getFullName($outputlangs), 0, 'C');
                $chrono->getValuesPlus();
//                $pdf->MultiCell(157, 6, 'Prise en charge : '.$chrono->ref, 0, 'L');
                
                foreach($chrono->valuesPlus as $id => $tabKey){
                    $pdf->MultiCell(157, 6, $tabKey->nom." : ".$tabKey->valueStr, 0, 'L');
                    $pdf->MultiCell(157, 6, '', 0, 'L');
                }
//                $pdf->MultiCell(157, 6, 'Informations de prise en charge', 0, 'L');
//                $pdf->MultiCell(157, 6, dol_print_date($chrono->date).' par '.$chrono->user_author->getFullName(), 0, 'L');
//                $pdf->MultiCell(157, 6, 'Prise en charge : '.$chrono->values["Diagnostique"], 0, 'L');
//                $pdf->MultiCell(157, 6, 'Prise en charge : '.$chrono->values["Diagnostique"], 0, 'L');
//                $pdf->MultiCell(157, 6, 'Prise en charge : '.$chrono->values["Diagnostique"], 0, 'L');
//
//                $g = 70;
//                foreach ($chrono->val as $societe) {
//                    $pdf->SetXY(50, $g);
//                    $pdf->MultiCell(120, 40, 'Nom: ' . $societe->getFullName($outputlangs) . "\n" . 'Adresse: ' . $societe->getFullAddress(), 0, '');
////                    $pdf->SetXY(50, $g+5);
////                    $pdf->MultiCell(160, 80, 'Adresse: ' . $societe->getFullAddress(), 0, '');
//                    $g += 30;
//                    if ($g > 248) {
//                        $this->_pagefoot($pdf, $chrono, $outputlangs);
//                        $pdf->AddPage();
//                        $this->_pagehead($pdf, $chrono, 1, $outputlangs);
//                $pdf->SetFont('', 'B', 12);
//                        $g = 40;
//                    }
//                }
                //Titre Page 1


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
        $pdf->SetFont('', 'B', 10);
        //Société
        $pdf->SetXY(3.5, 63);
        $pdf->MultiCell(39, 4, "Code Client : " . $object->societe->code_client, 0, "L");
        $pdf->SetXY(3.5, 54.5);
        $pdf->MultiCell(39, 4, 'Client : ' . $object->societe->getFullName($outputlangs), 0, 'L');
        $pdf->Rect(48, 39, 157, 235);
        $pdf->SetFont('', 'B', 7);
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
        $ligne .= "\n\n" . "Document généré par GLE Copyright © Synopsis & DRSI";

//        $ligne = "SA OLYS au capital de 85 372" . EURO . "    -   320 387 483 R.C.S. Lyon   -   APE 4741Z   -   TVA/CEE FR 34 320387483";
//        $ligne .= "\n" . "RIB : BPLL  -  13907. 00000.00202704667.45  -  CCP 11 158 41U Lyon";

        $pdf->SetXY(48, $Y + 50);
        $pdf->MultiCell(157, 3, $ligne, 0, "C");
        $pdf->line(48, $Y + 44, 205, $Y + 44);

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
