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

require_once(DOL_DOCUMENT_ROOT."/synopsiscontrat/core/modules/synopsiscontrat/modules_synopsiscontrat.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php" );
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

//TODO  addresse livraison lié au contrat
//TODO filtre sur statuts ???

/**
 \class      pdf_contrat_babel
 \brief      Classe permettant de generer les contrats au modele babel
 */

if(!defined('EURO'))
    define ('EURO', chr(128) );

class pdf_contrat_BIMP_maintenance extends ModeleSynopsiscontrat
{
    public $emetteur;
    var $contrat;
    var $pdf;
    var $margin_bottom = 2;

    public static $periodicities = array(
        1  => 'Mensuelle',
        3  => 'Trimestrielle',
        6  => 'Semestrielle',
        12 => 'Annuelle'
    );

    function __construct($db)
    {
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
    
    public function addLogo(&$pdf, $size){
        global $conf;
        $logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
        $pdf->Image($logo, 0, 10, 0, 20,'','','',false,250,'C');
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
        //$outputlangs->setPhpLang();
        if ($conf->contrat->dir_output)
        {
            // Definition de l'objet $contrat (pour compatibilite ascendante)
            if (! is_object($contrat))
            {
                $id = $contrat;
                require_once(DOL_DOCUMENT_ROOT."/Synopsis_Contrat/class/contratMixte.class.php");
                $contrat=getContratObj($id);
                $contrat->fetch($id);
                $contrat->fetch_lines(true);

            } else {
                $contrat->fetch_lines(true);
            }

            // Definition de $dir et $file
            if ($contrat->specimen)
            {
                $dir = $conf->contrat->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $propref = sanitize_string($contrat->ref);
                $dir = $conf->contrat->dir_output . "/" . $propref;
                $file = $dir ."/Contrat_BIMP_maintenance_".date("d_m_Y")."_" . $propref . ".pdf";
            }
            $this->contrat = $contrat;

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
                $client = new Societe($this->db);
                $BimpDb = new BimpDb($this->db);
                $produit = new Product($this->db);
                $client->fetch($contrat->socid);
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


                // FAIRE APPARAITRE TOUTES LES LIGNES


                // Titre
                $this->addLogo($pdf, 20);
                $pdf->SetXY($this->marge_gauche, $this->marge_haute - 6);
                $pdf->SetFont('', 'B', 14);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "Contrat de prestation de service et maintenance informatique", 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, $propref, 0, 'C');
                $pdf->SetFont('', 'B', 9);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "Entre les parties", 0, 'C');
                
                $client->fetch($contrat->socid);

                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 2;
                $pdf->SetDrawColor(255,255,255);
                $pdf->setColor('fill', 255, 255, 255);
                $pdf->Cell($W, 4, "BIMP GROUPE OLYS", 1, null, 'C', true);
                $pdf->Cell($W, 4, $client->nom, 1, null, 'C', true);
                $pdf->SetFont('', '', 7);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "51,ter Rue de Saint Cyr", 1, null, 'C', true);
                $pdf->Cell($W, 4, $client->address, 1, null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, '69009 LYON', 1, null, 'C', true);
                $pdf->Cell($W, 4, $client->zip . ' ' . $client->town, 1, null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "Tel: 0 812 211 211", 1, null, 'C', true);
                $pdf->Cell($W, 4, "Tel: " . $client->phone, 1, null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "Email : contact@bimp.fr", 1, null, 'C', true);
                $pdf->Cell($W, 4, "Email : " . $client->email, 1, null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "", 1, null, 'C', true);
                $pdf->Cell($W, 4, "SIRET : " . $client->siret, 1, null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "", 1, null, 'C', true);
                $pdf->Cell($W, 4, "Code client : " . $client->code_client, 1, null, 'C', true);
                $pdf->SetFont('', 'b', 11);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, 'Conditions du contrat', 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->SetFont('', '', 9);
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
                $pdf->SetDrawColor(0,0,0);
                $pdf->Cell($W * 2, 4, "Avenant au contrat N° :", 1, null, 'L', true);
                $pdf->Cell($W * 1.5, 4, "", 1, null, 'L', true);
                $pdf->Cell($W * 1.5, 4, "Date d'effet :", 1, null, 'L', true);
                $pdf->Cell($W, 4, date('d/m/Y', $contrat->date_contrat), 1, null, 'L', true);
                $pdf->Cell($W * 2.5, 4, "Périodicité de facturation :", 1, null, 'L', true);
                $pdf->Cell($W * 1.5, 4, "", 1, null, 'L', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'L');
                $pdf->Cell($W * 2, 4, "Annule et remplace :", 1, null, 'L', true);
                $pdf->Cell($W * 1.5, 4, "", 1, null, 'L', true);
                $date1 = new DateTime(date('Y-m-d',$contrat->date_contrat));
                $date2 = new DateTime(date('Y-m-d',$contrat->date_cloture));
                $interval = $date1->diff($date2);
                $pdf->Cell($W * 1.5, 4, "Durée :", 1, null, 'L', true);
                $pdf->Cell($W, 4, $interval->m . " Mois", 1, null, 'L', true);
                $pdf->Cell($W * 2.5, 4, "Coef de révision des prix :", 1, null, 'L', true);
                $pdf->Cell($W * 1.5, 4, "", 1, null, 'L', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'L');
                $pdf->Cell($W * 2, 4, "Delain d'intervention :", 1, null, 'L', true);
                $pdf->Cell($W * 1.5, 4, "8 heures", 1, null, 'L', true);
                $pdf->Cell($W * 1.5, 4, "Date de fin : ", 1, null, 'L', true);
                $pdf->Cell($W, 4, $contrat->date_cloture, 1, null, 'L', true);
                $pdf->Cell($W * 2.5, 4, "Reconduction : ", 1, null, 'L', true);
                $pdf->Cell($W * 1.5, 4, "", 1, null, 'L', true);
                $pdf->SetFont('', 'b', 11);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 8, '', 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, 'Description financière', 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->SetFont('', '', 9);
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 13;
                $pdf->Cell($W * 5, 8, "Désignation", 1, null, 'L', true);
                $pdf->Cell($W, 8, "TVA", 1, null, 'C', true);
                $pdf->Cell($W * 2, 8, "P.U HT", 1, null, 'C', true);
                $pdf->Cell($W, 8, "Qté", 1, null, 'C', true);
                $pdf->Cell($W * 2, 8, "Total HT", 1, null, 'C', true);
                $pdf->Cell($W * 2, 8, "Total TTC", 1, null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 8, '', 0, 'C');
                $colTVA = (float) 20;
                $colPU = (float) 0;
                $colQTE = (int) 0;
                $colHT = (float) 0;
                $colTTC = (float) 0;
                $pdf->SetFont('', '', 7);
                $pdf->Cell($W * 5, 8, "Assistance globale", "LR", null, 'L', true);
                $pdf->Cell($W, 8, $colTVA . "%", "LR", null, 'C', true);
                foreach ($contrat->lines as $line) {
                    $totalHT += $line->total_ht;
                    $totalTTC += $line->total_ttc;
                }
                $pdf->Cell($W * 2, 8, $totalHT . "€", "LR", null, 'C', true);
                $pdf->Cell($W, 8, "1", "LR", null, 'C', true);
                $pdf->Cell($W * 2, 8, $totalHT . "€", "LR", null, 'C', true);
                $pdf->Cell($W * 2, 8, $totalTTC . "€", "LR", null, 'C', true);
                $pdf->SetFont('', 'B', 7);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 8, '', 0, 'C');
                $pdf->Cell($W * 5, 4, "Forfait pour", "LR", null, 'L', true);
                $pdf->Cell($W, 4, "", "LR", null, 'C', true);
                $pdf->Cell($W * 2, 4, "", "LR", null, 'C', true);
                $pdf->Cell($W, 4, "", "LR", null, 'C', true);
                $pdf->Cell($W * 2, 4, "", "LR", null, 'C', true);
                $pdf->Cell($W * 2, 4, "", "LR", null, 'C', true);
                $pdf->SetFont('', '', 7);
                foreach ($contrat->lines as $line) {
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, '', 0, 'C');
                    $pdf->Cell($W * 5, 4, $line->qty . " " . $line->description, "LR", null, 'L', true);
                    $pdf->Cell($W, 4, "", "LR", null, 'C', true);
                    $pdf->Cell($W * 2, 4, "", "LR", null, 'C', true);
                    $pdf->Cell($W, 4, "", "LR", null, 'C', true);
                    $pdf->Cell($W * 2, 4, "", "LR", null, 'C', true);
                    $pdf->Cell($W * 2, 4, "", "LR", null, 'C', true);
                }
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, '', 0, 'C');
                    $pdf->Cell($W * 5, 4, "", "LRB", null, 'L', true);
                    $pdf->Cell($W, 4, "", "LRB", null, 'C', true);
                    $pdf->Cell($W * 2, 4, "", "LRB", null, 'C', true);
                    $pdf->Cell($W, 4, "", "LRB", null, 'C', true);
                    $pdf->Cell($W * 2, 4, "", "LRB", null, 'C', true);
                    $pdf->Cell($W * 2, 4, "", "LRB", null, 'C', true);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, '', 0, 'C');
                    $pdf->Cell($W * 5, 4, "", 0, null, 'L', true);
                    $pdf->Cell($W, 4, "", 0, null, 'C', true);
                    $pdf->Cell($W * 2, 4, "", 0, null, 'C', true);
                    $pdf->Cell($W, 4, "", 0, null, 'C', true);
                    $pdf->Cell($W * 2, 4, "Total HT", 1, null, 'L', true);
                    $pdf->Cell($W * 2, 4, $totalHT . "€", 1, null, 'R', true);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                    $pdf->Cell($W * 5, 4, "", 0, null, 'L', true);
                    $pdf->Cell($W, 4, "", 0, null, 'C', true);
                    $pdf->Cell($W * 2, 4, "", 0, null, 'C', true);
                    $pdf->Cell($W, 4, "", 0, null, 'C', true);
                    $pdf->Cell($W * 2, 4, "Total TVA 20%", 1, null, 'L', true);
                    $pdf->Cell($W * 2, 4, $totalTTC - $totalHT . "€", 1, null, 'R', true);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                    $pdf->Cell($W * 5, 4, "", 0, null, 'L', true);
                    $pdf->Cell($W, 4, "", 0, null, 'C', true);
                    $pdf->Cell($W * 2, 4, "", 0, null, 'C', true);
                    $pdf->Cell($W, 4, "", 0, null, 'C', true);
                    $pdf->Cell($W * 2, 4, "Total TTC", 1, null, 'L', true);
                    $pdf->Cell($W * 2, 4, $totalTTC . "€", 1, null, 'R', true);
                
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 10, '', 0, 'C');
                $pdf->SetFont('','bu',9);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, 'Pour BIMP', 0, 'L');
                $pdf->SetFont('','',9);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, 'NOM et fonction du signataire : ', 0, 'L');
            //  $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), , '', 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, 'Date :', 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 10, '', 0, 'C');
                $pdf->SetFont('','bu',9);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, 'Pour le client', 0, 'L');
                $pdf->SetFont('','',9);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, 'NOM, fonction et cahcet du signataire : ', 0, 'L');
            //  $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), , '', 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, 'précédé de la mention "Lu et approuvé"', 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '+ paraphe de toutes les pages', 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, 'Date : ', 0, 'L');
                if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();
                $pdf->Close();
                $this->file = $file;$pdf->Output($file, 'f');
                return 1;   // Pas d'erreur
            } else {
                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                //$langs->setPhpLang();    // On restaure langue session
                return 0;
            }
        } else {
            $this->error=$langs->trans("ErrorConstantNotDefined","CONTRACT_OUTPUTDIR");
            //$langs->setPhpLang();    // On restaure langue session
            return 0;
        }

        $this->error=$langs->trans("ErrorUnknown");
        //$langs->setPhpLang();    // On restaure langue session
        return 0;   // Erreur par defaut
    }

    function _pagehead(& $pdf, $object, $showadress = 1, $outputlangs, $currentPage=0)
    {
        global $conf, $langs;
        if ($currentPage > 1)
        {
            $showadress=0;
        }
    }

    function _pagefoot(&$pdf,$outputlangs)
    {

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


?>
