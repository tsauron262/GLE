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
require_once(DOL_DOCUMENT_ROOT . "/synopsisapple/core/modules/synopsisapple/modules_synopsisapple.php");
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

class pdf_synopsisapple_appleretour extends ModeleSynopsisapple {

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
    function write_file($retour, $outputlangs = '') {
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
        if ($conf->synopsisapple->dir_output) {
            // Definition de $dir et $file
            if (isset($retour->specimen) && $retour->specimen) {
                $dir = $conf->synopsisapple->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $propref = sanitize_string("Retour " . $retour->ref);
                $dir = $conf->synopsisapple->dir_output . "/" . $retour->ref;
                $file = $dir . "/" . $propref . ".pdf";
            }
            $this->chrono = $retour;

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



                $pdf->SetTitle($retour->model->titre . ' : ' . $retour->ref);

                $pdf->SetSubject($outputlangs->transnoentities("Panier"));
                $pdf->SetCreator("GLE " . GLE_VERSION);
                $pdf->SetAuthor($user->getFullName($langs));
//
                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf1->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
// 
//                

                $this->_pagehead($pdf, $retour, 1, $outputlangs);

                //Titre Page 1
//                $pdf->MultiCell(155, 6, 'Panier de ' . $retour->societe->getFullName($outputlangs), 0, 'C');
//                $pdf->MultiCell(155, 6, "Doc de retour" . "\n\n", 0, 'L');


                $dirF = $dir . "";
//$dh  = opendir($dirF);
//$i = 0;
//while (false !== ($filename = readdir($dh))) {
//    if(stripos($filename, ".pdf") && $filename != "retour.pdf"){
//        $i++;
//    }
//}
                $pdf->MultiCell(190, 6, 'Retour groupé Apple ' . $retour->ref, 1, 'C');


                $file1 = $dirF . "/ups.gif";
                $file2 = $dirF . "/PackingList.pdf";
                $dirLab = $dir . "/labels";
                if (is_file($file1) && is_file($file2) && is_dir($dirLab)) {
                    $pdf->MultiCell(190, 6, "\n\n Ce document contient : \n- 1 Etiquette UPS\n- 1 Fiche de colisage Apple\n- 2 Fois la fiche label pour chaque pièce détachée", 0, 'L');
                    $pdf->MultiCell(190, 6, "\n\n Créé le : " . dol_print_date(time()), 0, 'L');

                    $pdf->Image($file1, 20, 150, 180, 105);

                    ajouteFichier($pdf, $file2);

                    for ($j = 0; $j < 2; $j++) {
                        $dh = opendir($dirLab);
                        while (false !== ($filename = readdir($dh))) {
                            if (stripos($filename, ".pdf"))
                                ajouteFichier($pdf, $dirLab . "/" . $filename);
                        }
                    }
                }
                else {
                    $pdf->MultiCell(155, 6, "\n\nGénération impossible un ou des document(s) sont manquant(s) !", 0, 'C');
                }


                $this->_pagefoot($pdf, $retour, $outputlangs);


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
        
    }

    /*
     *   \brief      Affiche le pied de page
     *   \param      pdf     objet PDF
     */

    function _pagefoot(&$pdf, $retour, $outputlangs) {


        //return pdf_pagefoot($pdf, $retour,$outputlangs,'CONTRAT_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche + 40,$this->page_hauteur);
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

function ajouteFichier($pdf, $file, $first = false) {
    $nbPage = $pdf->setSourceFile($file);
    for ($i = 1; $i <= $nbPage; $i++) {
        $tplidx = $pdf->ImportPage($i);
        $size = $pdf->getTemplatesize($tplidx);
        if (!$first)
            $pdf->AddPage('P', array($size['w'], $size['h']));
        $pdf->useTemplate($tplidx, null, null, 209, 297, 1);
        $first = false;
    }
}

?>
