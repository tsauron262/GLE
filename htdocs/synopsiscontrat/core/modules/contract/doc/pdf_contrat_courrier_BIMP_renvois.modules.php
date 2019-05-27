<?php

require_once(DOL_DOCUMENT_ROOT . "/synopsiscontrat/core/modules/synopsiscontrat/modules_synopsiscontrat.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

if (!defined('EURO'))
    define('EURO', chr(128));

class pdf_contrat_courrier_BIMP_renvois extends ModeleSynopsiscontrat {
    public $emetteur;
    var $contrat;
    var $pdf;
    public $db;
    var $margin_bottom = 2;
    
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
        $this->marge_gauche = 15;
        $this->marge_droite = 15;
        $this->marge_haute = 40;
        $this->marge_basse = 0;
        $this->option_logo = 1;
        $this->emetteur = $mysoc;
        if (!$this->emetteur->pays_code)
            $this->emetteur->pays_code = substr($langs->defaultlang, -2);
    }
    
    public function addLogo(&$pdf, $size) {
        global $conf;
        $logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
        $pdf->Image($logo, 0, 10, 0, $size, '', '', '', false, 250, 'L');
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

                $file = $dir . "/Courrier_renvois_contrat_" . $propref . "(".$user->firstname." ".$user->lastname.").pdf";
            }
            $this->contrat = $contrat;

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {   
                $pdf = "";
                $nblignes = sizeof($contrat->lignes);
                $pdf = pdf_getInstance($this->format);
                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(true);
                }

                $pdf->Open();
                $pdf->AddPage();
                $pdf->SetTitle($contrat->ref);
                $pdf->SetSubject($outputlangs->transnoentities("Contract"));
                $pdf->SetCreator("BIMP-ERP " . DOL_VERSION);
                $pdf->SetAuthor($user->getFullName($langs));
                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
                $pdf->SetAutoPageBreak(1, $this->margin_bottom);
                $pdf->SetFont('', 'B', 9);

                
                
                // Titre
                $this->addLogo($pdf, 12);
                $pdf->SetXY($this->marge_gauche, $this->marge_haute - 17);
                $pdf->SetFont('', '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 8, "", 0, 'C');
                $pdf->setColor('fill', 255, 255, 255);
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 2;
                
                BimpTools::loadDolClass('societe');
                $client = new Societe($this->db);
                $client->fetch($contrat->socid);
                
                $pdf->SetFont('', 'B', 10);
                $pdf->Cell($W, 4, "", 0, null, 'C', true);
                $pdf->Cell($W, 4, $client->nom, 0, null, 'C', true);
                $pdf->SetFont('', '', 9);
                
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "", 0, 'C');
                $bimp = new BimpDb($this->db);
                if($id_contact = $bimp->getValue('element_contact', 'fk_socpeople', 'element_id = ' . $contrat->id . ' AND fk_c_type_contact = 21')) {
                    BimpTools::loadDolClass('contact');
                    $contact = new Contact($this->db);
                    $contact->fetch($id_contact);
                    $pdf->Cell($W, 4, "", 0, null, 'C', true);
                    $pdf->Cell($W, 4, "Contact : " . $contact->lastname . " " . $contact->firstname, 0, null, 'C', true);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                }
                $pdf->Cell($W, 4, "", 0, null, 'C', true);
                $pdf->Cell($W, 4, $client->address, 0, null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "", 0, null, 'C', true);
                $pdf->Cell($W, 4, $client->zip . ' ' . $client->town, 0, null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 16, '', 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, 'Le ......./......./.......', 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 10, '', 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, 'Object : contrat N°' . $contrat->ref, 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, 'Code client : ' . $client->code_client, 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 16, '', 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 10, 'Madame, Monsieur, ', 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 10, 'Veillez trouvez ci joint le contrat citer en object.', 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 10, 'Merci de parapher chaque page et de nous retourner notre exemplaire dûment rempli et signé, afin de finaliser votre dossier', 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 10, 'Bimp reste à votre disposition pour tout renseignement complémentaire.', 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 10, 'Nous vous prions d\'agréer, Madame, Monsieur, l\'expression de nos sincères salutations.', 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 20, '', 0, 'L');
                $gender = ($user->gender == 'man') ? 'Monsieur ' : 'Madame ';
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, $gender . $user->lastname . ' ' . $user->firstname, 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, $user->job, 0, 'L');
                $pdf->SetDrawColor(255, 255, 255);
                
                
                
                $pdf->setColor('fill', 255, 255, 255);
                $pdf->SetTextColor(200, 200, 200);
                $pdf->setY(280);
                $pdf->SetFont('', '', 7);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "Bimp Groupe Olys - 2 Rue des Erables - CS 21055 - 69760 LIMONEST | 0 812 211 211", 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "SAS OLYS AU CAPITAL DE 954 352€ | R.C.S LYON 320 287 483 | CODE APE 4651Z | TVA/CEE FR 34 320 387 483", 0, 'C');
                $pdf->setDrawColor(255, 255, 255);
                $pdf->SetTextColor(200, 200, 200);
                $pdf->SetTextColor(0, 0, 0);
                
                
                
                if (method_exists($pdf, 'AliasNbPages'))
                    $pdf->AliasNbPages();
                $pdf->Close();
                $this->file = $file;
                $pdf->Output($file, 'f');
                $this->result["fullpath"] = $file;
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
    
}