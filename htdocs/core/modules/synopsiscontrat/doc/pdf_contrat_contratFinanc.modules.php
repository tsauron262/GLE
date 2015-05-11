<?php

/*
 * GLE by Synopsis et DRSI
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
 * Name : pdf_contrat_courrierBIMPsignature.modules.php
 * GLE-1.2
 */
require_once(DOL_DOCUMENT_ROOT . "/core/modules/synopsiscontrat/modules_synopsiscontrat.php");
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';






//TODO  addresse livraison lié au contrat
//TODO filtre sur statuts ???

/**
  \class      pdf_contrat_babel
  \brief      Classe permettant de generer les contrats au modele babel
 */
if (!defined('EURO'))
    define('EURO', chr(128));

class pdf_contrat_contratFinanc extends ModeleSynopsiscontrat {

    public $emetteur;    // Objet societe qui emet
    var $contrat;
    var $pdf;
    var $margin_bottom = 25;

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
        $this->description = $langs->trans('PDFContratSynopsisDescription');

        // Dimension page pour format A4
        $this->type = 'pdf';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = 25;
        $this->marge_droite = 15;
        $this->marge_haute = 40;
        $this->marge_basse = 125;

        $this->option_logo = 1;                    // Affiche logo
        // Recupere emmetteur
        $this->emetteur = $mysoc;
        if (!$this->emetteur->pays_code)
            $this->emetteur->pays_code = substr($langs->defaultlang, -2);    // Par defaut, si n'etait pas defini
    }

    /**
     * Print chapter
     * @param $num (int) chapter number
     * @param $title (string) chapter title
     * @param $file (string) name of the file containing the chapter body
     * @param $mode (boolean) if true the chapter body is in HTML, otherwise in simple text.
     * @public
     */
    public function PrintChapter($num, $title, $file, $mode = false) {
        // add a new page
        $this->pdf->AddPage();
        //$this->_pagehead($this->pdf, $this->contrat);
        // disable existing columns
        $this->pdf->resetColumns();
        // print chapter title
        $this->ChapterTitle($num, $title);
        // set columns
        $this->pdf->setEqualColumns(3, 63);
        // print chapter body
        $this->ChapterBody($file, $mode);
    }

    /**
     * Set chapter title
     * @param $num (int) chapter number
     * @param $title (string) chapter title
     * @public
     */
    public function ChapterTitle($num, $title) {
        $this->pdf->SetFont('helvetica', '', 11);
        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->Cell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 6, $title . $num, 0, 1, 'C', 0);
        $this->pdf->Ln(4);
    }

    /**
     * Print chapter body
     * @param $file (string) name of the file containing the chapter body
     * @param $mode (boolean) if true the chapter body is in HTML, otherwise in simple text.
     * @public
     */
    public function ChapterBody($file, $mode = false) {
        $this->pdf->selectColumn();
        // get esternal file content
        $content = file_get_contents($file, false);
        $tabContent = explode("\n", $content);
        // set font
        $this->pdf->SetFont('', '', 7);
        $this->pdf->SetTextColor(50, 50, 50);
        // print content
        if ($mode) {
            // ------ HTML MODE ------
            $this->pdf->writeHTML($content, true, false, true, false, 'J');
        } else {
            // ------ TEXT MODE ------
            //$this->pdf->setCellMargins(0,0,0,10);
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
                $this->pdf->SetFont('', $style, 6.5);
                $this->pdf->Write(0, $ligne . "\n", '', 0, 'J', true, 0, false, true, 0);
            }
        }
        $this->pdf->Ln();
    }

    /**
      \brief      Fonction generant la contrat sur le disque
      \param        contrat            Objet contrat a generer (ou id si ancienne methode)
      \param        outputlangs        Lang object for output language
      \return        int             1=ok, 0=ko
     */
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
        if ($conf->synopsiscontrat->dir_output) {
            // Definition de l'objet $contrat (pour compatibilite ascendante)
            if (!is_object($contrat)) {
                $id = $contrat;
                require_once(DOL_DOCUMENT_ROOT . "/Synopsis_Contrat/class/contratMixte.class.php");
                $contrat = getContratObj($id);
                $contrat->fetch($id);
                $contrat->fetch_lines(true);
//                $contrat = new ContratMixte($this->db);
//                $ret=$contrat->fetch($id);
            } else {
                $contrat->fetch_lines(true);
            }

            // Definition de $dir et $file
            if ($contrat->specimen) {
                $dir = $conf->synopsiscontrat->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $propref = sanitize_string($contrat->ref);
                $dir = $conf->synopsiscontrat->dir_output . "/" . $propref;
                $file = $dir . "/Contrat_de_financement_" . date("d_m_Y") . "_" . $propref . ".pdf";
            }
            $this->contrat = $contrat;

            require_once (DOL_DOCUMENT_ROOT . "/synopsisfinanc/class/synopsisfinancement.class.php");
            $valfinance = new Synopsisfinancement($this->db);
            $valfinance->fetch(NULL, NULL, $this->contrat->id);

            require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
            $propal = new Propal($this->db);
            $propal->fetch($valfinance->propal_id);

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                $pdf = "";
                $nblignes = sizeof($contrat->lignes);
                // Protection et encryption du pdf
                $pdf = pdf_getInstance($this->format);
                $this->pdf = $pdf;
                if (class_exists('TCPDF')) {
                    if (get_class($pdf) == "FPDI") {
                        $pdf = getNewPdf($this->format);
                        $this->pdf = $pdf;
                    }
                    $pdf->setPrintHeader(true);
                    $pdf->setPrintFooter(true);
                }

//                $pdf1 = pdf_getInstance($this->format);
//                if (class_exists('TCPDF')) {
//                    $pdf1->setPrintHeader(false);
//                    $pdf1->setPrintFooter(false);
//                }


                $pdf->Open();
                //$pdf1->Open();
                $pdf->AddPage();
                //$pdf1->AddPage();
                //$pdf1->SetFont(''/* 'Arial' */, '', 8);

                $pdf->SetDrawColor(128, 128, 128);


                $pdf->SetTitle($contrat->ref);
                $pdf->SetSubject($outputlangs->transnoentities("Contract"));
                $pdf->SetCreator("GLE " . GLE_VERSION);
                $pdf->SetAuthor($user->getFullName($langs));

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                //$pdf1->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(1, $this->margin_bottom);


                //$pdf->AddFont('VeraMoBI', 'BI', 'VeraMoBI.php');
                //$pdf->AddFont('fq-logo', 'Roman', 'fq-logo.php');
                // Tete de page
                //$this->_pagehead($pdf, $contrat, 1, $outputlangs);
                $pdf->SetFont(''/* 'Arial' */, 'B', 9);

//locataire/////////////////////////////////////////////////////////////////////
                $pdf->SetXY($this->marge_gauche, $this->marge_haute - 6);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "CONTRAT DE LOCATION N° " . $this->contrat->ref, 0, 'C');
                $pdf->SetXY($this->marge_gauche, $this->marge_haute);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 100), 6, "Le locataire:", 0, 'L');
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                $pdf->SetXY($this->marge_gauche, $this->marge_haute + 6);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 100), 6, "La société: " . $contrat->societe->nom, 0, 'L');
                $pdf->SetXY($this->marge_gauche + 60, $this->marge_haute + 6);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, $contrat->societe->forme_juridique . " au capital de " . price($contrat->societe->capital) . " €", 0, 'L');
                $pdf->setX($this->marge_gauche);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Immatriculé sous le Numéro RCS: " . $contrat->societe->idprof4, 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Dont le siège sociale est situé au " . $contrat->societe->address . " " . $contrat->societe->zip . " " . $contrat->societe->town, 0, 'L');
                /* requete pour le représentant */
                $contact = $contrat->Liste_Contact(-1, "external");
                $nomC = "";
                foreach ($contact as $key => $value) {
                    if ($value["fk_c_type_contact"] == 22) {
                        $idcontact = $value["id"];
                        $cont = new Contact($this->db);
                        $cont->fetch($idcontact);
                        $nomC = "Représentée par " . $cont->getFullName($langs);
                        $grade = $cont->poste;
                        if ($grade != "") {
                            $nomC.=" intervenant en qualité de " . $grade;
                        }
                    }
                }

                /* fin requete */
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, $nomC, 0, 'L');
                $pdf->SetXY($this->marge_gauche + 100, $this->marge_haute + 24);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "", 0, 'L');

//le loueur/////////////////////////////////////////////////////////////////////
                $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                $pdf->SetX($this->marge_gauche);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Le loueur:", 0, 'L');

                $pdf->SetFont(''/* 'Arial' */, '', 8);
                //print_r($this->emetteur);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "La Société " . $this->emetteur->name . ", " . getFormeJuridiqueLabel($this->emetteur->forme_juridique_code) . " au capital de " . price($this->emetteur->capital) . " € dont le siège social est situé à " . $this->emetteur->town . " (" . $this->emetteur->zip . "), " . $this->emetteur->address . ", enregistrée sous le numéro RCS: " . $this->emetteur->idprof4 . ",", 0, 'L'); //print_r($this->emetteur);
                $contact = $contrat->Liste_Contact(-1, "internal");
                $nomC = "";
                foreach ($contact as $key => $value) {
                    if ($value["fk_c_type_contact"] == 10) {
                        $idcontact = $value["id"];
                        $cont = new User($this->db);
                        $cont->fetch($idcontact);
                        $nomC = "Représentée par " . $cont->getFullName($langs);
                        $grade = $cont->job;
                        if ($grade != "") {
                            $nomC.=" intervenant en qualité de " . $grade . ".";
                        }
                    }
                }
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, $nomC, 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Le loueur donne en location, l’équipement désigné ci-dessous (ci-après « équipement »), au locataire qui l'accepte, aux Conditions Générales ci-annexées composées de deux pages recto et aux Conditions Particulières suivantes :", 0, 'L');

//description de l'équipement///////////////////////////////////////////////////
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
                $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                $pdf->SetX($this->marge_gauche);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Description de l'équipement:", 0, 'L');
//tableau récapitulatif/////////////////////////////////////////////////////////
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 12;

//////////////////entete du tableau/////////////////////////////////////////////
//qte
                $pdf->SetFont(''/* 'Arial' */, '', 9);
                $pdf->setColor('fill', 230, 230, 250);
                $pdf->Cell($W * 1, 6, "Quantité", 1, null, 'L', true);
//designation
                $X = $this->marge_gauche + $W;
                $pdf->setX($X);
                $pdf->setColor('fill', 230, 230, 250);
                $pdf->Cell($W * 7, 6, "Désignation du matériels", 1, null, 'L', true);
                $M_N = false;
//marque
                /* $X=$this->marge_gauche+$W*8;
                  $pdf->setX($X);
                  $pdf->setColor('fill', 230, 230, 250);
                  $pdf->Cell($W, 6, "Marque", 1, null, 'L', true);
                 * $M_N=true;
                 */
//num de série
                if ($M_N == true) {
                    $X = $this->marge_gauche + $W * 10;
                } else {
                    $X = $this->marge_gauche + $W * 8;
                }
                $pdf->setX($X);
                $pdf->setColor('fill', 230, 230, 250);
                $pdf->MultiCell($W * 2, 6, "N° de série", 1, 'L', true);

////////////////fin entete du tableau///////////////////////////////////////////
////////////////debut corps tableau/////////////////////////////////////////////
                $X = $this->marge_gauche;
                $color_id = 0;
                foreach ($propal->lines as $obj) {
                    if ($color_id == 0) {
                        $pdf->setColor('fill', 255, 255, 255);
                    } else {
                        $pdf->setColor('fill', 235, 235, 235);
                    }
                    if ($obj->fk_product) {
                        $prod = new product($this->db);
                        $prod->fetch($obj->fk_product);
                        
                        $X = $this->marge_gauche;
                        $pdf->Cell($W, 6, $obj->qty, 1, NULL, 'L', true);
                        $X = $this->marge_gauche + $W;
                        $pdf->setX($X);
                        $pdf->Cell($W * 7, 6, dol_trunc($prod->ref . " - ". $prod->libelle . " - " .$obj->desc, 40), 1, NULL, 'L', TRUE);
                        $X = $this->marge_gauche + $W * 8;
                        $pdf->setX($X);
                        if ($M_N == true) {
                            $pdf->Cell($W, 6, "marque", 1, null, 'L', true);
                            $X = $this->marge_gauche + $W * 10;
                            $pdf->MultiCell($W * 2, 6, "", 1, 'L', true);
                        } else {
                            $pdf->MultiCell($W * 2, 6, "", 1, 'L', true);
                        }
                        $color_id = ($color_id + 1) % 2;
                        
                    } else {
                        
                        $X = $this->marge_gauche;
                        $pdf->Cell($W, 6, $obj->qty, 1, NULL, 'L', true);
                        $X = $this->marge_gauche + $W;
                        $pdf->setX($X);
                        $pdf->Cell($W * 7, 6, dol_trunc($obj->desc, 40), 1, NULL, 'L', TRUE);
                        $X = $this->marge_gauche + $W * 8;
                        $pdf->setX($X);
                        if ($M_N == true) {
                            $pdf->Cell($W, 6, "marque", 1, null, 'L', true);
                            $X = $this->marge_gauche + $W * 10;
                            $pdf->MultiCell($W * 2, 6, "", 1, 'L', true);
                        } else {
                            $pdf->MultiCell($W * 2, 6, "", 1, 'L', true);
                        }
                        $color_id = ($color_id + 1) % 2;
                    }
                }
//fin corps tableau/////////////////////////////////////////////////////////////
//fin tableau///////////////////////////////////////////////////////////////////
//
//
//évolution de l'équipement/////////////////////////////////////////////////////
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
                $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                $pdf->SetX($this->marge_gauche);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Evolution de l'équipement:", 0, 'L');
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Le locataire pourra demander au bailleur, au cours de la période de validité du présent contrat la modification de l’équipement informatique remis en location. Les modifications éventuelles du contrat seront déterminées par l’accord des parties.", 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Cette modification pourra porter sur tout ou partie des équipements, par adjonction, remplacement et/ou enlèvement des matériels repris dans l’article 1 ci-dessus.", 0, 'L');

//récap du loyer////////////////////////////////////////////////////////////////
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
                $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Le loyers:", 0, 'L');
                //$pdf->SetXY($this->marge_gauche, $this->marge_haute + 126);
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "Le loyer ferme et non révisable en cours de contrat, payable par terme à échoir, par prélèvements automatiques est fixé à :", 0, 'L');

                $X = $this->marge_gauche;
                //$Y = $this->marge_haute + 132;
                if($valfinance->VR>0){
                    $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 5;
                }else{
                    $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 4;
                }

///////////////////////debut tableau////////////////////////////////////////////
                $pdf->SetX($X);
//entete////////////////////////////////////////////////////////////////////////
                $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                $pdf->Cell($W, 6, "NOMBRE DE LOYERS", 1, NULL, 'C', FALSE, NULL, NULL, null, null, 'C');
                $pdf->Cell($W, 6, "MONTANT HT", 1, NULL, 'C', FALSE, NULL, NULL, null, null, 'C');
                $pdf->Cell($W, 6, "PERIODICITE", 1, NULL, 'C', FALSE, NULL, NULL, null, null, 'C');
                if($valfinance->VR>0){
                    $pdf->Cell($W, 6, "DUREE", 1, NULL, 'C', FALSE, NULL, NULL, null, null, 'C');
                    $pdf->MultiCell($W, 6, "VR", 1, 'C', FALSE, 1, NULL, null, null, null, null, null, null, 'M');
                }else{
                    $pdf->MultiCell($W, 6, "DUREE", 1, 'C', FALSE, 1, NULL, null, null, null, null, null, null, 'M');
                }
//fin entete////////////////////////////////////////////////////////////////////
                //$pdf->SetX($X);
//debut corps///////////////////////////////////////////////////////////////////
                $pdf->setColor('fill', 230, 230, 250);
                $pdf->SetFont('', '', 8);
                $pdf->Cell($W, 6, $valfinance->nb_periode, 1, NULL, 'C', TRUE, NULL, NULL, null, null, 'C');
                //$pdf->setColor('fill', 230, 230, 250);
                $pdf->Cell($W, 6, price($valfinance->loyer1 + 0.005)." €", 1, NULL, 'C', TRUE, NULL, NULL, null, null, 'C');
                //$pdf->setColor('fill', 230, 230, 250);
                $pdf->Cell($W, 6, Synopsisfinancement::$TPeriode[$valfinance->periode], 1, NULL, 'C', TRUE, NULL, NULL, null, null, 'C');
                //$pdf->setColor('fill', 230, 230, 250);
                if($valfinance->VR>0){
                    $pdf->Cell($W, 6, $valfinance->nb_periode . " " . Synopsisfinancement::$tabM[$valfinance->periode], 1, NULL, 'C', TRUE, NULL, NULL, null, null, 'C');
                    $pdf->MultiCell($W, 6, price($valfinance->VR)." €", 1, 'C', true, 1, NULL, null, null, null, null, null, null, 'M');
                }else{
                    $pdf->MultiCell($W, 6, $valfinance->nb_periode . " " . Synopsisfinancement::$tabM[$valfinance->periode], 1, 'C', true, 1, NULL, null, null, null, null, null, null, 'M');
                }
//fin corps/////////////////////////////////////////////////////////////////////
//transition
                $pdf->MultiCell($W, 6, "suivie de:",0,'L');
//fin transition
//
//entete dégresif
//
                $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                $pdf->Cell($W, 6, "NOMBRE DE LOYERS", 1, NULL, 'C', FALSE, NULL, NULL, null, null, 'C');
                $pdf->Cell($W, 6, "MONTANT HT", 1, NULL, 'C', FALSE, NULL, NULL, null, null, 'C');
                $pdf->Cell($W, 6, "PERIODICITE", 1, NULL, 'C', FALSE, NULL, NULL, null, null, 'C');
                $pdf->MultiCell($W, 6, "DUREE", 1, 'C', FALSE, 1, NULL, null, null, null, null, null, null, 'M');
//
//fin entete degressif
//corps prix dégressif
//
                $pdf->SetFont('', '', 8);
                $pdf->Cell($W, 6, $valfinance->nb_periode2, 1, NULL, 'C', TRUE, NULL, NULL, null, null, 'C');
                $pdf->Cell($W, 6, price($valfinance->loyer2 + 0.005)." €", 1, NULL, 'C', TRUE, NULL, NULL, null, null, 'C');
                $pdf->Cell($W, 6, Synopsisfinancement::$TPeriode[$valfinance->periode], 1, NULL, 'C', TRUE, NULL, NULL, null, null, 'C');
                $pdf->MultiCell($W, 6, $valfinance->nb_periode2 . " " . Synopsisfinancement::$tabM[$valfinance->periode], 1, 'C', true, 1, NULL, null, null, null, null, null, null, 'M');
//
//fin corps prix dégressif
//////////////////////////////////fin tableau///////////////////////////////////


                $X = $this->marge_gauche;

                //$Y = $Y + 18;
                $pdf->SetX($X);
                $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                $pdf->Write(6, "Site d'installation: ");
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, $contrat->societe->address . " à " . $contrat->societe->town, 0, 'L', FALSE, 1, NULL, null, null, null, null, null, null, 'M');
                //$pdf->Write(6, $contrat->societe->address . " à " . $contrat->societe->town);
                //$Y = $Y + 6;
                //
                $pdf->SetX($X);
                $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                $pdf->Write(6, "Date d'installation: ");
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, dol_print_date($propal->date_livraison), 0, 'L', false, 1, NULL, null, null, null, null, null, null, 'M');
                //$pdf->Write(6, dol_print_date($propal->date_livraison));
                //$Y = $Y + 6;
                //$pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6,"", 0, 'L', false, 1, NULL, null, null, null, null, null, null, 'M');
                //$pdf->SetX($X);
                $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Clause spécifique: ", 0, 'L', false, 1, NULL, null, null, null, null, null, null, 'M');
                //$pdf->Write(6, "Clause spécifique: ");
                //$Y = $Y + 6;
                //$pdf->SetX($X, $Y);
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 6, "Fait en autant d'exemplaires que de parties, un pour chacune des parties", 0, 'L', false, 1, NULL, null, null, null, null, null, null, 'M');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 6, "ANNEXE : Conditions Générales composées de deux pages recto", 0, 'L', false, 1, NULL, null, null, null, null, null, null, 'M');

                //$Y = $Y + 12;
                //$pdf->Set($X);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Fait à Lyon le " . dol_print_date($contrat->date_contrat), 0, 'L', false, 1, NULL, null, null, null, null, null, null, 'M');
                //$pdf->Write(6, "Fait à Lyon le " . dol_print_date($contrat->date_contrat));
                //emplacement des signature
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 3;
                //locataire
                $pdf->SetAutoPageBreak(1, 0);
                $pdf->MultiCell($W, 6, "Pour le Locataire" . "\n" . "Signature et cachet(lu et approuvé)" . "\n" . "Qualité" . "\n" . "NOM", 0, 'L', false, 0);


                //loueur
                $X = $X + $W;
                $pdf->SetX($X);
                $pdf->MultiCell($W, 6, "Pour le Loueur" . "\n" . "Signature et cachet", 0, 'C', false, 0);

                //cessionnaire
                $X = $X + $W;
                $pdf->SetX($X);
                $pdf->MultiCell($W, 6, "Pour le Cessionnaire" . "\n" . "Signature et cachet", 0, 'C', false, 0);
                $pdf->SetAutoPageBreak(1, $this->margin_bottom);

                $X = $this->marge_gauche;
                $pdf->SetX($X);

                $this->marge_gauche = $this->marge_gauche - 25;
                $this->marge_droite = $this->marge_droite - 5; /* TODO */
                $this->marge_haute = $this->marge_haute - 5;
                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                
                if($valfinance->banque!=""){
                    if(file_exists(DOL_DATA_ROOT.'/synopsisfinanc/doc/banque_'.$valfinance->banque.'.txt')){
                        $this->PrintChapter($this->contrat->ref, 'ANNEXE: CONDITION GENERALES DU CONTRAT DE LOCATION N° ', DOL_DOCUMENT_ROOT . '/synopsisfinanc/doc/banque_'.$valfinance->banque.'.txt', false);
                    }else{
                        $this->PrintChapter($this->contrat->ref, 'ANNEXE: CONDITION GENERALES DU CONTRAT DE LOCATION N° ', DOL_DOCUMENT_ROOT . '/synopsisfinanc/doc/banque_test.txt', false);
                    }
                }else{
                    $this->PrintChapter($this->contrat->ref, 'ANNEXE: CONDITION GENERALES DU CONTRAT DE LOCATION N° ', DOL_DOCUMENT_ROOT . '/synopsisfinanc/doc/banque_test.txt', false);
                }
                
                $pdf->SetAutoPageBreak(1, 0);
                $pdf->setFont('', '', 8);
                $X = $this->marge_gauche + 10;
                $pdf->SetXY($X, $this->page_hauteur - 50);
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 3;
                $pdf->MultiCell($W, 6, "Pour le locataire" . "\n" . "Signature et cachet(lu et approuver)" . "\n" . "\n" . "Qualité" . "\n" . "\n" . "Nom", 0, 'L');
                $X = $X + $W;
                $pdf->SetXY($X, $this->page_hauteur - 50);
                $pdf->MultiCell($W, 6, "Pour le loueur", 0, 'C');
                $X = $X + $W;
                $pdf->SetXY($X, $this->page_hauteur - 50);
                $pdf->MultiCell($W, 6, "Pour le Cessionnaire", 0, 'C');
                $pdf->SetAutoPageBreak(1, 55);

                $this->_pagefoot($pdf, $outputlangs);

                if (method_exists($pdf, 'AliasNbPages'))
                    $pdf->AliasNbPages();
                $pdf->Close();

                $this->file = $file;
                $pdf->Output($file, 'f');

                //$langs->setPhpLang();    // On restaure langue session


                return 1;   // Pas d'erreur
            } else {
                $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                //$langs->setPhpLang();    // On restaure langue session
                return 0;
            }
        } else {
            $this->error = $langs->trans("ErrorConstantNotDefined", "CONTRACT_OUTPUTDIR");
            //$langs->setPhpLang();    // On restaure langue session
            return 0;
        }

        $this->error = $langs->trans("ErrorUnknown");
        //$langs->setPhpLang();    // On restaure langue session
        return 0;   // Erreur par defaut
    }

//    function header(& $pdf, $object, $showadress = 1, $outputlangs, $currentPage = 0) {
//        global $conf, $langs;
//        $logo = false;
//        if (is_file($conf->mycompany->dir_output . '/logos' . '/' . $this->emetteur->logo . "noalpha.png")) {
//            $logo = $conf->mycompany->dir_output . '/logos' . '/' . $this->emetteur->logo . "noalpha.png";
//        } else {
//            $logo = $conf->mycompany->dir_output . '/logos' . '/' . $this->emetteur->logo;
//        }
//        if (is_readable($logo)) {
//            $pdf->Image($logo, 75, 13, 0, 24);
//        }
//    }

    /*
     *   \brief      Affiche le pied de page
     *   \param      pdf     objet PDF
     */

    function _pagefoot(&$pdf, $outputlangs) {
        
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

function getNewPdf($format) {

    class FPDI222 extends FPDI {

        function setHeader() {
            global $conf, $langs, $mysoc;
            $logo = false;
            if (is_file($conf->mycompany->dir_output . '/logos' . '/' . $mysoc->logo . "noalpha.png")) {
                $logo = $conf->mycompany->dir_output . '/logos' . '/' . $mysoc->logo . "noalpha.png";
            } else {
                $logo = $conf->mycompany->dir_output . '/logos' . '/' . $mysoc->logo;
            }
            if (is_readable($logo)) {
                $this->Image($logo, 90, 5, 0, 25);
            }
        }

        function setFooter() {
            $this->SetAutoPageBreak(1, 0);
            $this->SetXY(190, 289);
            $this->MultiCell(15, 3, '' . $this->PageNo() . '/{:ptp:}', 0, 'R', 0);
            $this->SetAutoPageBreak(1, $this->margin_bottom);
        }

    }

    return new FPDI222('P', 'mm', $format);
}
