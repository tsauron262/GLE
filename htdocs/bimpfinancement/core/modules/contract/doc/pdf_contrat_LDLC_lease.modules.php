<?php

/*
 * BIMP-ERP by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.2
 * Created on : 30 mars 2011
 *
 * Infos on http://www.finapro.fr
 *
 */
/**
 *
 * Name : pdf_contrat_courrierBIMPresiliationAvoir.modules.php
 * BIMP-ERP-1.2
 */
require_once(DOL_DOCUMENT_ROOT . "/synopsiscontrat/core/modules/synopsiscontrat/modules_synopsiscontrat.php");
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php" );
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

//TODO  addresse livraison lié au contrat
//TODO filtre sur statuts ???

/**
  \class      pdf_contrat_babel
  \brief      Classe permettant de generer les contrats au modele babel
 */
if (!defined('EURO'))
    define('EURO', chr(128));

class pdf_contrat_LDLC_lease extends ModeleSynopsiscontrat {

    public $emetteur;
    var $contrat;
    var $pdf;
    var $margin_bottom = 2;
    public $db;
    public $forceAnnexe = false;
    private $textDroite = 'Contrat location sans service V3 du 01/05/2018';
    public static $periodicities = array(
        1 => 'Mensuelle',
        3 => 'Trimestrielle',
        6 => 'Semestrielle',
        12 => 'Annuelle'
    );

    function __construct($db) {
        global $conf, $langs, $mysoc;
        $langs->load("main");
        $langs->load("bills");
        $this->debug = "";
        $this->db = $db;
        $this->name = "babel";
        $this->description = $langs->trans('PDFContratSynopsisDescription');
        $this->type = 'pdf';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = 12;
        $this->marge_droite = 12;
        $this->marge_haute = 35;
        $this->marge_basse = 35;
        $this->option_logo = 1;
        $this->emetteur = $mysoc;
        if (!$this->emetteur->pays_code)
            $this->emetteur->pays_code = substr($langs->defaultlang, -2);
    }

    public function PrintChapter($num, $title, $file, $mode = false) {
        $this->pdf->AddPage();
        $this->pdf->resetColumns();
        $this->ChapterTitle($num, $title);
        $this->pdf->setEqualColumns(2, 100);
        $this->ChapterBody($file, $mode);
    }

    public function enTete3Cases($pdf) {
        $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->SetFont(''/* 'Arial' */, 'B', 9);
        $pdf->setColor('fill', 192, 199, 228);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($W, 8, "Quantité", 1, null, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $X = $this->marge_gauche + $W;
        $pdf->setX($X);
        $pdf->setColor('fill', 192, 199, 228);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($W * 7, 8, "Désignation du matériels", 1, null, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $X = $this->marge_gauche + $W * 8;
        $pdf->setX($X);
        $pdf->setColor('fill', 192, 199, 228);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->MultiCell($W * 2, 8, "Numéro de série", 1, null, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont(''/* 'Arial' */, '', 9);
    }

    public function enTete5Case($pdf) {
        $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
        $pdf->SetFont(''/* 'Arial' */, 'B', 9);
        $pdf->setColor('fill', 192, 199, 228);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($W * 2.1, 8, "Ordre", 1, null, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $X = $this->marge_gauche + $W;
        $pdf->setX($X * 1.5);
        $pdf->setColor('fill', 192, 199, 228);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($W * 2.1, 8, "Nombre de loyers", 1, null, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setX($X * 2.5);
        $pdf->setColor('fill', 192, 199, 228);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($W * 2.1, 8, "Montant HT", 1, null, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setX($X * 3.5);
        $pdf->setColor('fill', 192, 199, 228);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($W * 2.1, 8, "Périodicité", 1, null, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setX($X * 4.5);
        $pdf->setColor('fill', 192, 199, 228);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($W * 3.2, 8, "Montant TTC", 1, null, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
    }

    public function linesProduct($pdf, $lines) {
        foreach ($lines as $line) {
            $data = $line->getSerialDesc();
            $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
            $pdf->SetX($this->marge_gauche);
            $pdf->SetFont(''/* 'Arial' */, '', 9);
            $pdf->setColor('fill', 248, 248, 248);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell($W, 8, (int) $line->getData('qty'), 1, null, 'L', true);
            $pdf->SetTextColor(0, 0, 0);
            $X = $this->marge_gauche + $W;
            $pdf->setX($X);
            $pdf->setColor('fill', 248, 248, 248);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell($W * 7, 8, $data->label, 1, null, 'L', true);
            $pdf->SetTextColor(0, 0, 0);
            $X = $this->marge_gauche + $W * 8;
            $pdf->setX($X);
            $pdf->setColor('fill', 248, 248, 248);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->MultiCell($W * 2, 8, $data->serials, 1, null, 'L', true);
            $pdf->SetTextColor(0, 0, 0);
        }
    }

    public function linesRents($pdf, $lines) {
        $nbLine = 1;
        $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
        foreach ($lines as $rent) {
            $period = $rent->array_options['options_periodicity'];
            $pdf->SetFont(''/* 'Arial' */, '', 9);
            $pdf->setColor('fill', 248, 248, 248);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell($W * 2.1, 8, $nbLine, 1, null, 'L', true);
            $X = $this->marge_gauche + $W;
            $pdf->setX($X * 1.5);
            $pdf->setColor('fill', 248, 248, 248);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell($W * 2.1, 8, $rent->qty, 1, null, 'L', true);
            $pdf->setX($X * 2.5);
            $pdf->setColor('fill', 248, 248, 248);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell($W * 2.1, 8, $rent->price_ht . "€", 1, null, 'L', true);
            $pdf->setX($X * 3.5);
            $pdf->setColor('fill', 248, 248, 248);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell($W * 2.1, 8, self::$periodicities[$period], 1, null, 'L', true);
            $pdf->setX($X * 4.5);
            $pdf->setColor('fill', 248, 248, 248);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell($W * 3.2, 8, $rent->price_ht + $rent->price_ht * (20 / 100) . "€", 1, null, 'L', true);
            $this->jump($pdf, 3);
            $this->jump($pdf, 3);
            $nbLine++;
        }
    }

    public function greyFooter($pdf, $page = '', $array = array()) {
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->setColor('fill', 255, 255, 255);

        $pdf->SetFont(''/* 'Arial' */, '', 9);
        switch ($page) {
            case 'proces':
                $pdf->setY(270);
                $pdf->SetTextColor(200, 200, 200);
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 5.5, "", 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 5.5, "", 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 5.5, "", 0, 'L');
                $pdf->Cell($W, 8, "F-LOC - SAS au capital de 100 000€ - RCS LYON 838 651 594 - 62 Chemin du Moulin Carron, 69570 Dardilly", 1, null, 'C', true);
                break;
            case 'cgv':
                $pdf->setY(287);
                if (in_array($pdf->PageNo(), $this->paraphe_page)) {
                    $pdf->SetTextColor(0, 0, 0);
                    $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 20;
                    $pdf->Cell($W * 16, 5, "Paraphes : ", 1, null, 'R', true);
                    $pdf->setDrawColor(192, 199, 228);
                    $pdf->Cell($W * 1, 5, "", 1, null, 'R', true);
                    $pdf->Cell($W * 0.5, 5, "", 0, null, 'R', true);
                    $pdf->Cell($W * 1, 5, "", 1, null, 'R', true);
                    $pdf->Cell($W * 0.5, 5, "", 0, null, 'R', true);
                    $pdf->Cell($W * 1, 5, "", 1, null, 'R', true);
                    $pdf->setDrawColor(255, 255, 255);
                }

                break;
        }
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setY(290);
        $pdf->setX(5);
        $pdf->MultiCell(30, 3, 'Page ' . $pdf->PageNo() . '/{:ptp:}', 0, 'L', 0);
    }

    public function addLogo(&$pdf, $size) {
        global $conf;
        $logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
        $pdf->Image($logo, 0, 10, 0, 20, '', '', '', false, 250, 'C');
    }

    public function ChapterTitle($num, $title) {
        $this->pdf->SetFont('helvetica', '', 11);
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->Cell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 6, $title . $num, 0, 1, 'C', 0);
        $this->pdf->Ln(4);
    }

    public function ChapterBody($file, $mode = false) {
        $this->pdf->selectColumn();
        $content = file_get_contents($file, false);
        $tabContent = explode("\n", $content);
        $this->pdf->SetFont('', '', 7);
        $this->pdf->SetTextColor(50, 50, 50);
        if ($mode) {
            $this->pdf->writeHTML($content, true, false, true, false, 'J');
        } else {
            foreach ($tabContent as $id => $ligne) {
                if ($this->pdf->getY() < 500 && $this->pdf->getX() < 60 && (count($tabContent) - $id) < 17)
                    $this->pdf->SetAutoPageBreak(1, 55);
                $style = "";
                if (stripos($ligne, "<g>") > -1) {
                    $ligne = str_replace("<g>", "", $ligne);
                    $titre = true;
                    $style .= 'B';
                }
                if (stripos($ligne, "<i>") > -1) {
                    $ligne = str_replace("<i>", "", $ligne);
                    $style .= 'I';
                }
                if (stripos($ligne, "<s>") > -1) {
                    $ligne = str_replace("<s>", "", $ligne);
                    $style .= 'U';
                }
                $this->pdf->SetFont('', $style, 6.86);
                $this->pdf->Write(0, $ligne . "\n", '', 0, 'J', true, 0, false, true, 0);
            }
        }
        $this->pdf->Ln();
    }

    public function print_signature($pdf) {
        $titreContrat = (object) self::$textContrat['titres'];
        $texteContrat = (object) self::$textContrat['textes'];
        $autre = (object) self::$autre;
        $pdf->setY($this->page_hauteur - 65);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $autre->fait, 0, 'L');
        $W = ($this->page_largeur - ($this->marge_droite - $this->marge_gauche)) / 3;
        $pdf->setColor('fill', 255, 255, 255);
        $pdf->SetFont('', 'B', 9);
        $pdf->Cell($W, 8, $titreContrat->titre_5, 1, null, 'L', true);
        $pdf->Cell($W, 8, $titreContrat->titre_6, 1, null, 'L', true);
        $pdf->Cell($W, 8, $titreContrat->titre_7, 1, null, 'L', true);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 5.5, "", 0, 'L');
        $pdf->SetFont('', '', 7.5);
        $pdf->Cell($W, 8, $autre->nom, 1, null, 'L', true);
        $pdf->Cell($W, 8, $autre->odlc, 1, null, 'L', true);
        $pdf->Cell($W, 8, $autre->raison, 1, null, 'L', true);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 5.5, "", 0, 'L');
        $pdf->Cell($W, 8, $autre->quality, 1, null, 'L', true);
        $pdf->Cell($W, 8, $autre->president, 1, null, 'L', true);
        $pdf->Cell($W, 8, $autre->siren, 1, null, 'L', true);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 5.5, "", 0, 'L');
        $pdf->Cell($W, 8, $autre->sETc, 1, null, 'L', true);
        $pdf->Cell($W, 8, $autre->sETc, 1, null, 'L', true);
        $pdf->Cell($W, 8, $autre->nom, 1, null, 'L', true);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 5.5, "", 0, 'L');
        $pdf->Cell($W, 8, $autre->lu, 1, null, 'L', true);
        $pdf->Cell($W, 8, "", 1, null, 'L', true);
        $pdf->Cell($W, 8, $autre->quality, 1, null, 'L', true);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 5.5, "", 0, 'L');
        $pdf->Cell($W, 8, "", 1, null, 'L', true);
        $pdf->Cell($W, 8, "", 1, null, 'L', true);
        $pdf->Cell($W, 8, $autre->sETc, 1, null, 'L', true);
        $pdf->SetTextColor(200, 200, 200);
    }

    public function print_signature_matos($pdf) {
        $autre = (object) self::$autre;
        $pdf->setY($this->page_hauteur - 70);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $autre->fait, 0, 'L');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setColor('fill', 255, 255, 255);
        $pdf->SetFont('', 'B', 9);
        $W = ($this->page_largeur - ($this->marge_droite - $this->marge_gauche)) / 2;
        $pdf->Cell($W, 8, "Pour le Locataire", 1, null, 'L', true);
        $pdf->Cell($W, 8, "Pour le loueur", 1, null, 'L', true);
        $pdf->SetFont('', '', 9);
    }

    public function print_contrat($pdf, $contrat, $outputlangs, $nombre = 1) {
        global $user;
        $titreContrat = (object) self::$textContrat['titres'];
        $texteContrat = (object) self::$textContrat['textes'];
        $autre = (object) self::$autre;
        for ($i = 1; $i <= $nombre; $i++) {
            $pdf->Open();
            $pdf->AddPage();
            $pdf->SetDrawColor(128, 128, 128);
            $pdf->SetTitle($contrat->ref);
            $pdf->SetSubject($outputlangs->transnoentities("Contract"));
            $pdf->SetCreator("BIMP-ERP " . DOL_VERSION);
            $pdf->SetAuthor($user->getFullName($langs));
            $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
            $pdf->SetAutoPageBreak(1, $this->margin_bottom);
            $pdf->SetFont('', 'B', 9);

            // Titre
            $this->addLogo($pdf, 17);
            $pdf->SetXY($this->marge_gauche, $this->marge_haute - 6);
            $pdf->SetFont('', 'B', 15);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "CONTRAT DE LOCATION N° " . $this->contrat->ref, 0, 'C');
            $pdf->SetFont('', 'B', 9);
            $this->jump($pdf, 6);

            // Le locataire
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $titreContrat->titre_1, 0, 'L');
            $pdf->SetFont('', '', 9);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $this->getTextClient($client), 0, 'L');

            // Le loueur
            $pdf->SetFont('', 'B', 9);
            $this->jump($pdf, 3);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $titreContrat->titre_2, 0, 'L');
            $pdf->SetFont('', '', 9);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $texteContrat->texte_1, 0, 'L');

            // Explications
            $this->jump($pdf, 3);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $texteContrat->texte_2, 0, 'L');
            $this->jump($pdf, 3);

            // Tableau
            $pdf->SetFont('', 'B', 9);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $titreContrat->titre_3, 0, 'L');
            $pdf->SetFont('', '', 9);


            $demande = BimpObject::getInstance('bimpfinancement', 'BF_Demande');
            $demande->find(array('id_contrat' => (int) $contrat->id), true, true);
            $lines = $demande->getChildrenObjects('lines', array('in_contrat' => (int) 1));

            $new_page = (count($lines) > 5) ? true : false;
            $this->enTete3Cases($pdf);
            if ($new_page || $this->forceAnnexe) {
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche);
                $pdf->SetX($this->marge_gauche);
                $pdf->SetFont(''/* 'Arial' */, '', 9);
                $pdf->setColor('fill', 248, 248, 248);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Cell($W, 8, $texteContrat->texte_3, 1, null, 'C', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->setXY($this->marge_gauche, 120);
            } else {
                $this->linesProduct($pdf, $lines);
            }
            $pdf->SetFont('', 'B', 9);
            $this->jump($pdf, 1);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $titreContrat->titre_3, 0, 'L');
            $pdf->SetFont('', '', 9);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, $texteContrat->texte_4, 0, 'L');

            $this->enTete5Case($pdf);
            $this->jump($pdf, 3);
            $this->jump($pdf, 3);

            $this->linesRents($pdf, $contrat->lines);

            $pdf->SetFont('', 'B', 9);
            $this->jump($pdf, 3);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $titreContrat->titre_4, 0, 'L');
            $pdf->SetFont('', '', 9);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $texteContrat->texte_5, 0, 'L');
            $this->jump($pdf, 3);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $texteContrat->texte_6, 0, 'L');
            $ajoutAnnexe = ($new_page) ? "+ Liste et détails du matériel" : "";
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $this->getTextAnnexe($ajoutAnnexe), 0, 'L');


            $this->print_signature($pdf);
            $pdf->setY(265);
            $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 2;
            $this->jump($pdf, 5.5);
            $this->jump($pdf, 5.5);
            $this->jump($pdf, 5.5);
            $pdf->SetTextColor(0, 0, 0);
            if (!$new_page || $this->forceAnnexe)
                $this->paraphe_page = array(2, 3, 4);
            else
                $this->paraphe_page = array(3, 4, 5);
            $this->greyFooter($pdf);
            $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
        }
    }

    public function print_preces($pdf, $contrat, $outputlangs, $nombre = 1) {
        global $user;
        $titreContrat = (object) self::$textContrat['titres'];
        $texteContrat = (object) self::$textContrat['textes'];
        $autre = (object) self::$autre;
        for ($i = 1; $i <= $nombre; $i++) {
            $pdf->AddPage();
            $pdf->SetTitle($contrat->ref);
            $pdf->SetSubject($outputlangs->transnoentities("Contract"));
            $pdf->SetCreator("BIMP-ERP " . DOL_VERSION);
            $pdf->SetAuthor($user->getFullName($langs));
            $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
            $pdf->SetAutoPageBreak(1, $this->margin_bottom);
            $pdf->SetFont('', 'B', 9);

            // Titre
            $this->addLogo($pdf, 17);
            $pdf->SetXY($this->marge_gauche, $this->marge_haute);
            $pdf->SetFont('', 'B', 12);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "PROCES VERBAL DE RECEPTION ET MISE EN SERVICE DE MATERIEL", 0, 'C');
            $pdf->SetFont('', 'B', 9);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "CONTRAT DE LOCATION N° " . $this->contrat->ref, 0, 'C');
            $pdf->SetFont('', '', 10);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
            $pdf->SetFont('', '', 9);
            $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 2;
            $pdf->SetX($this->marge_gauche);
            $pdf->SetFont(''/* 'Arial' */, 'B', 9);
            $pdf->setColor('fill', 255, 255, 255);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell($W, 8, "Adresse du locataire", 1, null, 'C', true);
            $pdf->SetTextColor(0, 0, 0);
            $X = $this->marge_gauche + $W;
            $pdf->setX($X);
            $pdf->setColor('fill', 255, 255, 255);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell($W, 8, "Adresse du loueur", 1, null, 'C', true);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont(''/* 'Arial' */, '', 9);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
            $pdf->SetFont(''/* 'Arial' */, 'B', 9);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "Équipements objets du contrat, quantité et numéro de série", 0, 'L');
            $pdf->SetFont(''/* 'Arial' */, '', 9);
            $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
            /* DEBUT ENTE_TETE DU TABLEAU (QUANTITE, DESIGNATION DU MATERIEL, NUMERO DE SERIE) */
            $pdf->SetFont(''/* 'Arial' */, 'B', 9);
            $pdf->setColor('fill', 192, 199, 228);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell($W, 8, "Quantité", 1, null, 'L', true);
            $pdf->SetTextColor(0, 0, 0);
            $X = $this->marge_gauche + $W;
            $pdf->setX($X);
            $pdf->setColor('fill', 192, 199, 228);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell($W * 7, 8, "Désignation du matériels", 1, null, 'L', true);
            $pdf->SetTextColor(0, 0, 0);
            $X = $this->marge_gauche + $W * 8;
            $pdf->setX($X);
            $pdf->setColor('fill', 192, 199, 228);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->MultiCell($W * 2, 8, "Numéro de série", 1, null, 'L', true);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont(''/* 'Arial' */, '', 9);
            $demande = BimpObject::getInstance('bimpfinancement', 'BF_Demande');
            $demande->find(array('id_contrat' => (int) $contrat->id), true, true);
            $lines = $demande->getChildrenObjects('lines', array('in_contrat' => (int) 1));
            $new_page = (count($lines) > 5) ? true : false;
            if ($new_page || $this->forceAnnexe) {
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche);
                $pdf->SetX($this->marge_gauche);
                $pdf->SetFont(''/* 'Arial' */, '', 9);
                $pdf->setColor('fill', 248, 248, 248);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Cell($W, 8, $texteContrat->texte_3, 1, null, 'C', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->setXY($this->marge_gauche, 120);
            } else {
                $this->linesProduct($pdf, $lines);
            }
            $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 2;
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "Le locataire a choisi librement et sous sa responsabilité les équipements, objets du présent contrat, en s’assurant auprès de ses fournisseurs de leur compatibilité y compris dans le cas où ils sont incorporés dans un système préexistant.", 0, 'L');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "Le fournisseur déclare que le matériel, ci-dessus désigné, a bien été mis en service selon les normes du constructeur, et le locataire déclare avoir, ce jour, réceptionné ce matériel sans aucune réserve, en bon état de marche, sans vice ni défaut apparent et conforme à la commande passée au fournisseur. En conséquence, le locataire déclare accepter ledit matériel sans restriction, ni réserve, compte tenu du mandat qui lui a été fait par F-LOC.", 0, 'L');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "Le Loueur / Fournisseur déclare que les matériels livrés sont conformes aux normes et réglementations en vigueur notamment en ce qui concerne l’hygiène et la sécurité au travail.", 0, 'L');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "La signature du procès-verbal de réception et mise en service de matériel rend exigible le 1er loyer.", 0, 'L');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "Fait en autant d’exemplaires que de parties, un pour chacune des parties", 0, 'L');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
            $this->print_signature_matos($pdf);
            $this->greyFooter($pdf, 'proces');
            if ($new_page || $this->forceAnnexe) {
                $pdf->AddPage();
                $this->addLogo($pdf, 17);
                $pdf->SetXY($this->marge_gauche, $this->marge_haute - 6);
                $pdf->SetFont('', 'B', 15);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "CONTRAT DE LOCATION N° " . $this->contrat->ref, 0, 'C');
                $pdf->SetFont('', 'B', 9);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
                $pdf->SetFont('', '', 10);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "ANNEXE 1 : Liste et détails des produits", 0, 'C');
                $pdf->SetFont('', '', 9);
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
                $pdf->SetDrawColor(255, 255, 255);
                $this->enTete3Cases($pdf);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont(''/* 'Arial' */, '', 9);
                $this->linesProduct($pdf, $lines);
                $this->print_signature($pdf);
                $this->greyFooter($pdf, 'proces');
            }
        }
    }

    public function print_mandat($pdf, $contrat) {
        $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 2;
        $pdf->SetDrawColor(128, 128, 128);
        $this->marge_haute = 33;
        $this->marge_basse = 10;
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
        $pdf->AddPage();
        $this->addLogo($pdf, 17);
        $this->marge_droite = 20;
        $x = $this->marge_gauche;
        $y = $this->marge_haute;
        $separateur = 7;
        //titre
        $pdf->SetXY($x, $y);
        $pdf->setEqualColumns(2, 98);
        $pdf->setFont('', 'B', 10);
        $pdf->MultiCell($W, 6, "Mandat de Prélèvement SEPA", 0, 'C', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Mandat de Prélèvement SEPA", 0, 'C', false, 0);
        $y = $this->marge_haute + 8;
        $pdf->SetY($y);
        $pdf->setFont('', '', 6.5);
        $pdf->MultiCell($W, 6, "En signant ce formulaire de mandat, vous autorisez le créancier à envoyer des instructions à votre banque pour débiter votre compte, et votre banque à débiter votre compte conformément aux instructions du créancier. Vous bénéficiez du droit d’être remboursé par votre banque selon les conditions décrites dans la convention que vous avez passée avec elle. Une demande de remboursement doit être présentée dans les 8 semaines suivant la date de débit de votre compte pour un prélèvement autorisé. Vos droits concernant
le présent mandat sont expliqués dans un document que vous pouvez obtenir auprès de votre banque.
Le présent mandat est donné pour le débiteur en référence, il sera utilisable pour les contrats conclus avec celui-ci et aux termes desquels le débiteur donne autorisation de paiement en utilisant le présent mandat.  Les informations contenues dans le présent mandat, qui doit être complété, sont destinées à n'être utilisées par le créancier que pour la gestion de sa relation avec son client. Elles pourront donner lieu à l'exercice, par ce dernier, de ses droits d'opposition, d’accès et de rectification tels que prévus aux articles 38 et suivants de la Loi n° 78-17 du 6 janvier 1978 relative à l'informatique, aux fichiers et aux libertés. En signant ce mandat le débiteur, par dérogation à la règle de pré-notification de 14 jours, déclare que le délai de pré-notification des prélèvements par le créancier est fixé à 2 jours avant la date d’échéance du prélèvement
", 0, 'J', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "En signant ce formulaire de mandat, vous autorisez le créancier à envoyer des instructions à votre banque pour débiter votre compte, et votre banque à débiter votre compte conformément aux instructions du créancier. Vous bénéficiez du droit d’être remboursé par votre banque selon les conditions décrites dans la convention que vous avez passée avec elle. Une demande de remboursement doit être présentée dans les 8 semaines suivant la date de débit de votre compte pour un prélèvement autorisé. Vos droits concernant
le présent mandat sont expliqués dans un document que vous pouvez obtenir auprès de votre banque.
Le présent mandat est donné pour le débiteur en référence, il sera utilisable pour les contrats conclus avec celui-ci et aux termes desquels le débiteur donne autorisation de paiement en utilisant le présent mandat.  Les informations contenues dans le présent mandat, qui doit être complété, sont destinées à n'être utilisées par le créancier que pour la gestion de sa relation avec son client. Elles pourront donner lieu à l'exercice, par ce dernier, de ses droits d'opposition, d’accès et de rectification tels que prévus aux articles 38 et suivants de la Loi n° 78-17 du 6 janvier 1978 relative à l'informatique, aux fichiers et aux libertés. En signant ce mandat le débiteur, par dérogation à la règle de pré-notification de 14 jours, déclare que le délai de pré-notification des prélèvements par le créancier est fixé à 2 jours avant la date d’échéance du prélèvement
", 0, 'J', false, 0);
        $pdf->setY($y + 62);
        $pdf->setFont('', 'b', 8);
        $pdf->MultiCell($W, 6, "Informations Débiteur", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Informations Débiteur", 0, 'L', false, 0);
        $pdf->Line($x - 5, $y + 66, $x + 60, $y + 66);
        $pdf->Line($x + 90 + $separateur, $y + 66, $x + 163, $y + 66);
        $pdf->setY($y + 68);
        $pdf->setFont('', '', 8);
        $pdf->MultiCell($W, 6, "Raison social : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Raison social : ", 0, 'L', false, 0);
        $pdf->setY($y + 72);
        $pdf->MultiCell($W, 6, "Adresse : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Adresse : ", 0, 'L', false, 0);
        $pdf->setY($y + 80);
        $pdf->MultiCell($W, 6, "Code postal et ville : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Code postal et ville : ", 0, 'L', false, 0);
        $pdf->setY($y + 88);
        $pdf->MultiCell($W, 6, "SIREN : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "SIREN : ", 0, 'L', false, 0);
        $pdf->setY($y + 92);
        $pdf->MultiCell($W, 6, "Pays : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Pays : ", 0, 'L', false, 0);
        $pdf->setY($y + 96);
        $pdf->MultiCell($W, 6, "Email : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Email : ", 0, 'L', false, 0);
        $pdf->setY($y + 101);
        $pdf->setFont('', 'b', 8);
        $pdf->MultiCell($W, 6, "Coordonnées Bancaire débiteur : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Coordonnées Bancaire débiteur : ", 0, 'L', false, 0);
        $pdf->Line($x - 5, $y + 105, $x + 60, $y + 105);
        $pdf->Line($x + 90 + $separateur, $y + 105, $x + 163, $y + 105);
        $pdf->setY($y + 107);
        $pdf->setFont('', '', 8);
        $pdf->MultiCell($W, 6, "IBAN : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "IBAN : ", 0, 'L', false, 0);
        $pdf->setY($y + 111);
        $pdf->MultiCell($W, 6, "BIC : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "BIC : ", 0, 'L', false, 0);
        $pdf->setY($y + 116);
        $pdf->setFont('', 'b', 8);
        $pdf->MultiCell($W, 6, "Coordonnées Bancaire Créancier : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Coordonnées Bancaire Créancier : ", 0, 'L', false, 0);
        $pdf->Line($x - 5, $y + 120, $x + 60, $y + 120);
        $pdf->Line($x + 90 + $separateur, $y + 120, $x + 163, $y + 120);
        $pdf->setY($y + 122);
        $pdf->setFont('', '', 8);
        $pdf->MultiCell($W, 6, "Raison social : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Raison social : ", 0, 'L', false, 0);
        $pdf->setY($y + 126);
        $pdf->MultiCell($W, 6, "Adresse : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Adresse : ", 0, 'L', false, 0);
        $pdf->setY($y + 134);
        $pdf->MultiCell($W, 6, "Code postal et ville : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Code postal et ville : ", 0, 'L', false, 0);
        $pdf->setY($y + 138);
        $pdf->MultiCell($W, 6, "Pays : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Pays : ", 0, 'L', false, 0);
        $pdf->setY($y + 142);
        $pdf->MultiCell($W, 6, "ICS : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "ICS : ", 0, 'L', false, 0);
        $pdf->setY($y + 147);
        $pdf->setFont('', 'b', 8);
        $pdf->MultiCell($W, 6, "Référence Unique du Mandat (RUM) : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Référence Unique du Mandat (RUM) : ", 0, 'L', false, 0);
        $pdf->Line($x - 5, $y + 151, $x + 60, $y + 151);
        $pdf->Line($x + 90 + $separateur, $y + 151, $x + 163, $y + 151);

        $pdf->setY($y + 160);
        $pdf->setFont('', 'b', 8);
        $pdf->MultiCell($W, 6, "Informations Type de Paiement : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Informations type de Paiement : ", 0, 'L', false, 0);
        $pdf->Line($x - 5, $y + 164, $x + 60, $y + 164);
        $pdf->Line($x + 90 + $separateur, $y + 164, $x + 163, $y + 164);
        $pdf->setFont('', '', 8);
        $pdf->setY($y + 168);
        $pdf->MultiCell($W, 6, "Paiement: Récurent / Unique", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Paiement: Récurent / Unique", 0, 'L', false, 0);

        $pdf->setY($y + 176);
        $pdf->setFont('', 'b', 8);
        $pdf->MultiCell($W, 6, "Signature : ", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Signature : ", 0, 'L', false, 0);
        $pdf->Line($x - 5, $y + 180, $x + 60, $y + 180);
        $pdf->Line($x + 90 + $separateur, $y + 180, $x + 163, $y + 180);
        $pdf->setFont('', '', 8);
        $pdf->setY($y + 184);
        $pdf->MultiCell($W, 6, "Date :           /          /", 0, 'L', false, 0);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Date :           /          /", 0, 'L', false, 0);

        $pdf->SetAutoPageBreak(1, 0);
        $pdf->setY($y + 194);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->setColor('fill', 255, 255, 255);
        $pdf->Cell($W - 5, 40, '', 1, null, 'C', true);
        $pdf->setX($x + 90 + $separateur);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->setColor('fill', 255, 255, 255);
        $pdf->Cell($W - 5, 40, '', 1, null, 'C', true);
        $pdf->setY($y + 195);
        $pdf->setFont('', 'I', 7);
        $pdf->MultiCell($W, 6, "Signature", 0, 'L', false, 0);
        $pdf->setX($x + 90);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Signature", 0, 'L', false, 0);
        $pdf->setFont('', '', 8);

        $pdf->setY($y + 234);
        $pdf->setFont('', 'I', 7);
        $pdf->MultiCell($W, 6, "Joindre un RIB", 0, 'L', false, 0);
        $pdf->setX($x + 90);
        $pdf->MultiCell($separateur, 6, "", 0, 'C', false, 0);
        $pdf->MultiCell($W, 6, "Joindre un RIB", 0, 'L', false, 0);
        $pdf->setFont('', '', 8);
        $this->greyFooter($pdf);
    }

    function write_file($contrat, $outputlangs = '') {
        global $user, $langs, $conf;

        if (!is_object($outputlangs))
            $outputlangs = $langs;
        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("bills");
        $outputlangs->load("contrat");
        $outputlangs->load("products");
        //$outputlangs->setPhpLang();
        if ($conf->contrat->dir_output) {
            // Definition de l'objet $contrat (pour compatibilite ascendante)
            if (!is_object($contrat)) {
                $id = $contrat;
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Contrat/class/contratMixte.class.php");
                $contrat = getContratObj($id);
                $contrat->fetch($id);
                $contrat->fetch_lines(true);
            } else {
                $contrat->fetch_lines(true);
            }

            // Definition de $dir et $file
            if ($contrat->specimen) {
                $dir = $conf->contrat->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $propref = sanitize_string($contrat->ref);
                $dir = $conf->contrat->dir_output . "/" . $propref;
            }
            $this->contrat = $contrat;

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                $client = new Societe($this->db);
                $BimpDb = new BimpDb($this->db);
                $produit = new Product($this->db);
                $client->fetch($contrat->socid);
                $pdf = "";
                $nblignes = sizeof($contrat->lignes);
                $pdf = pdf_getInstance($this->format);
                $pdf1 = pdf_getInstance($this->format);
                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(true);
                    $pdf1->setPrintHeader(false);
                    $pdf1->setPrintFooter(true);
                }

                $file = $dir . "/" . $propref . "_" . date('d-m-Y') . "_sans_annexe.pdf";
                $this->print_contrat($pdf, $contrat, $outputlangs);
                $this->print_preces($pdf, $contrat, $outputlangs);
                $this->print_mandat($pdf, $contrat);
                $file1 = $dir . "/" . $propref . "_" . date('d-m-Y') . "_avec_annexe.pdf";
                $this->forceAnnexe = true;
                $this->page_largeur = 210;
                $this->page_hauteur = 297;
                $this->format = array($this->page_largeur, $this->page_hauteur);
                $this->marge_gauche = 12;
                $this->marge_droite = 12;
                $this->marge_haute = 35;
                $this->marge_basse = 35;
                $this->print_contrat($pdf1, $contrat, $outputlangs);
                $this->print_preces($pdf1, $contrat, $outputlangs);
                $this->print_mandat($pdf1, $contrat);
                $pdf->Close();
                $pdf1->Close();
                $this->file = $file;
                $this->file1 = $file1;
                $pdf->Output($file, 'f');
                $pdf1->Output($file1, 'f');
                return 1;
            } else {
                $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        } else {
            $this->error = $langs->trans("ErrorConstantNotDefined", "CONTRACT_OUTPUTDIR");
            return 0;
        }

        $this->error = $langs->trans("ErrorUnknown");
        return 0;
    }

    function _pagehead(& $pdf, $object, $showadress = 1, $outputlangs, $currentPage = 0) {
        global $conf, $langs;
        if ($currentPage > 1) {
            $showadress = 0;
        }
        $this->addLogo($pdf, 15);
    }

    function _pagefoot(&$pdf, $outputlangs) {

        $this->greyFooter($pdf, 'cgv');
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

    function headArray() {
        
    }

    public static $textContrat = array(
        'titres' => array(
            'titre_1' => 'Le locataire :',
            'titre_2' => 'Le loueur :',
            'titre_3' => 'Description de l\'équipement et quantité :',
            'titre_4' => 'Durée et Loyers :',
            'titre_4' => 'Site d\'installation :',
            'titre_5' => 'Pour le locataire :',
            'titre_6' => 'Pour le loueur :',
            'titre_7' => 'Pour le cessionnaire :'
        ),
        'textes' => array(
            'texte_1' => 'La société F-LOC, SAS au capital de 100 000 € dont le siège social est situé à Dardilly (69570), 62, chemin du Moulin Carron enregistrée sous le SIREN 838 651 594 au RCS de Lyon, représentée par Monsieur Olivier VILLEMONTE DE LA CLERGERIE , intervenant en qualité de Président .',
            'texte_2' => "Le loueur donne en location, l’équipement désigné ci-dessous (ci-après « équipement »), au locataire qui l’accepte, aux Conditions Particulières et aux Conditions Générales composées de quatre pages recto",
            'texte_3' => "Liste et détails du matériel en ANNEXE",
            'texte_4' => "Le loyer ferme et non révisable en cours de contrat, payable par terme à échoir, par prélèvements automatiques.",
            'texte_5' => "Le locataire déclare avoir été parfaitement informé de l’opération lors de la phase précontractuelle, avoir pris connaissance,
reçu et accepter toutes les conditions particulières et générales. Il atteste que le contrat est en rapport direct avec son activité
professionnelle et souscrit pour les besoins de cette dernière. Le signataire atteste être habilité à l’effet d’engager le locataire
au titre du présent contrat. Le locataire reconnait avoir une copie des Conditions Générales, les avoir acceptées sans réserve
y compris les clauses attribution de compétence et CNIL.",
            'texte_6' => "Fait en autant d'éxemplaires que de partie, un pour chacune des parties",
        ),
    );
    public static $autre = array(
        'fait' => "Fait à ........................... le          /          /",
        'nom' => 'NOM :',
        'odlc' => 'Olivier VILLEMONTE DE LA CLERGERIE',
        'quality' => 'Qualité :',
        'sETc' => 'Signature et Cachet',
        'lu' => '(Lu et approuvé)',
        'president' => 'Président',
        'raison' => 'RAISON SOCIALE :',
        'siren' => 'SIREN :'
    );
    public static $mandat = array(
        'titre' => 'Mandat de prélèvement SEPA',
    );

    public function getTextClient($client) {
        $text = "La société " . $client->nom . " ";
        $text .= ($client->capital > 0) ? "au capital de " . number_format($client->capital, 2, ',', "") . "€, " : "";
        $text .= (!is_null($client->idprof1) || !empty($client->idprof1)) ? "immatriculée sous le numéro SIREN " . $client->idprof1 . ", " : "";
        $text .= " dont le siège est situé à : " . $client->address . ", " . $client->zip . " " . $client->town;


        return $text;
    }

    public function getTextAnnexe($ajoutAnnexe) {
        return "ANNEXE : Conditions Générales composées de quatre pages recto " . $ajoutAnnexe;
    }

    public function jump($pdf, $interval) {
        return $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), $interval, "", 0, 'L');
    }

}

?>
