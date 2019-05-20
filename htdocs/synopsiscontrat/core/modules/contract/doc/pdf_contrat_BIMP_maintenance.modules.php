<?php

require_once(DOL_DOCUMENT_ROOT . "/synopsiscontrat/core/modules/synopsiscontrat/modules_synopsiscontrat.php");
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php" );
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

if (!defined('EURO'))
    define('EURO', chr(128));

class pdf_contrat_BIMP_maintenance extends ModeleSynopsiscontrat {
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

    public static $gti = Array(2 => '2h ouvrées', 4 => '4h ouvrées', 8 => '8h ouvrées', 16 => '16h ouvrées');
    public static $tacite = Array(1 => 'Tacite 1 fois', 3 => 'Tacite 2 fois', 6 => 'Tacite 3 fois', 12 => 'Sur proposition');
    public static $denounce = Array(0 => 'Non', 1 => 'Oui, dans les temps', 2 => 'Oui, hors délais');
    public static $periode = Array(1 => 'Mensuelle', 3 => 'Trimestrielle', 6 => 'Semestrielle', 12 => 'Annuelle');
    public static $text_head_table = Array(1 => 'Désignation', 2 => 'TVA', 3 => 'P.U HT', 4 => 'Qté', 5 => 'Total HT', 6 => 'Total TTC');

    public function addLogo(&$pdf, $size) {
        global $conf;
        $logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
        $pdf->Image($logo, 0, 10, 0, $size, '', '', '', false, 250, 'C');
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

    public function headOfArray($pdf) {
        $pdf->SetFont(''/* 'Arial' */, 'B', 9);
        $pdf->setColor('fill', 236, 147, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->setDrawColor(255, 255, 255);
        $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 13;
        $pdf->Cell($W * 5, 8, self::$text_head_table[1], 1, null, 'L', true);
        $pdf->Cell($W, 8, self::$text_head_table[2], 1, null, 'C', true);
        $pdf->Cell($W * 2, 8, self::$text_head_table[3], 1, null, 'C', true);
        $pdf->Cell($W, 8, self::$text_head_table[4], 1, null, 'C', true);
        $pdf->Cell($W * 2, 8, self::$text_head_table[5], 1, null, 'C', true);
        $pdf->Cell($W * 2, 8, self::$text_head_table[6], 1, null, 'C', true);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 8, '', 0, 'C');
        $pdf->setColor('fill', 255, 255, 255);
        $pdf->SetFont(''/* 'Arial' */, '', 9);
        $pdf->setColor('fill', 255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setDrawColor(0, 0, 0);
    }

    public function titre_partie($pdf, $titre) {
        $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche);
        $pdf->setTextColor(255, 255, 255);
        $pdf->setDrawColor(255, 255, 255);
        $pdf->setColor('fill', 236, 147, 0);
        $pdf->Cell($W, 8, $titre, 1, null, 'C', true);
        $pdf->setColor('fill', 255, 255, 255);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
        $pdf->setTextColor(0, 0, 0);
        $pdf->setDrawColor(0, 0, 0);
    }

    public function display_lines($pdf, $lines, $dernier_id = 0) {
        $pdf->SetFont(''/* 'Arial' */, '', 7);
        $pdf->setColor('fill', 242, 242, 242);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setDrawColor(255, 255, 255);
        $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 13;
        foreach ($lines as $line) {
            if ($line->id > $dernier_id) {
                $pdf->Cell($W * 5, 6, (strlen($line->description) > 40) ? substr($line->description, 0, 40) . " ..." : $line->description, 1, null, 'L', true);
                $pdf->Cell($W, 6, number_format($line->tva_tx, 0, '', '') . "%", 1, null, 'C', true);
                $pdf->Cell($W * 2, 6, number_format($line->price_ht, 2, '.', '') . "€", 1, null, 'C', true);
                $pdf->Cell($W, 6, $line->qty, 1, null, 'C', true);
                $pdf->Cell($W * 2, 6, number_format($line->total_ht, 2, '.', '') . "€", 1, null, 'C', true);
                $pdf->Cell($W * 2, 6, number_format($line->total_ttc, 2, '.', '') . '€', 1, null, 'C', true);
                $dernier_id = $line->id;
            }
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
        }
        $pdf->SetTextColor(0, 0, 0);
        return $dernier_id;
    }

    public function display_total($pdf, $lines) {
        $pdf->SetFont(''/* 'Arial' */, '', 7);
        $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 13;
        $total = $this->get_totaux($lines);
        $taux_tva_text = "TOTAL TVA ";
        foreach ($total as $designation => $valeur) {
            if ($designation == 'TVA') {
                foreach ($total->TVA as $taux => $montant) {
                    $pdf->setColor('fill', 255, 255, 255);
                    $pdf->Cell($W * 5, 6, "", 1, null, 'L', true);
                    $pdf->Cell($W, 6, "", 1, null, 'C', true);
                    $pdf->Cell($W * 2, 6, "", 1, null, 'C', true);
                    $pdf->Cell($W, 6, "", 1, null, 'C', true);
                    $pdf->setColor('fill', 230, 230, 230);
                    $pdf->Cell($W * 2, 6, (!is_float($taux)) ? $taux_tva_text . number_format($taux, 0, '', '') . "%" : $taux_tva_text . number_format($taux, 2, '.', '') . "%", 1, null, 'L', true);
                    $pdf->Cell($W * 2, 6, number_format($montant, 2, '.', "") . "€", 1, null, 'C', true);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
                }
            } else {
                $pdf->setColor('fill', 255, 255, 255);
                $pdf->Cell($W * 5, 7, "", 1, null, 'L', true);
                $pdf->Cell($W, 7, "", 1, null, 'C', true);
                $pdf->Cell($W * 2, 7, "", 1, null, 'C', true);
                $pdf->Cell($W, 7, "", 1, null, 'C', true);
                $pdf->setColor('fill', 235, 235, 235);
                $pdf->setFont('', 'B', 7);
                $pdf->Cell($W * 2, 7, "TOTAL $designation", 1, null, 'L', true);
                $pdf->Cell($W * 2, 7, $total->$designation . "€", 1, null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 7, "", 0, 'C');
            }
        }
    }

    public function get_totaux($lines) {
        $total_ttc = 0;
        $total_ht = 0;
        $tva = Array();

        foreach ($lines as $line) {
            $total_ht += $line->total_ht;
            $total_ttc += $line->total_ttc;
            $tva[$line->tva_tx] += $line->total_tva;
        }
        return (object) Array('HT' => $total_ht, 'TVA' => $tva, 'TTC' => $total_ttc);
    }

    public function display_cp($pdf, $contrat, $user, $outputlangs) {
        $titre = "Applicables aux conditions générales du Contrat de prestation de services et de maintenance informatique";
        $parag1 = "Les présentes Conditions Particulières sont signées en application et exécution des Conditions Générales du Contrat de Prestation de Services et Maintenance informatique, avec lesquelles elles forment un tout indivisible. Le Client reconnaît avoir pris connaissance des dites Conditions Générales et s'engage à les respecter.";
        $parag2 = "Il est expressément convenu entre les Parties qu'en cas de contradiction entre une ou plusieurs dispositions des Conditions Générales du Contrat de Prestation de Services et Maintenance informatique et une ou plusieurs dispositions des présentes Conditions Particulières, ces dernières prévalent.";
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
        $pdf->SetXY($this->marge_gauche, $this->marge_haute - 6);
        $pdf->SetFont('', 'B', 14);
        $pdf->setTextColor(0, 0, 0);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "Conditions particulières du Contrat N°" . $contrat->ref, 0, 'C');
        $pdf->SetFont('', 'B', 9);
        $pdf->setDrawColor(236, 147, 0);
        $pdf->Line(15, 32, 195, 32);
        $pdf->Line(15, 42, 195, 42);
        $pdf->setDrawColor(255, 255, 255);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, $titre, 0, 'C');
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 2, "", 0, 'C');
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, $parag1, 0, 'L');
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 2, "", 0, 'C');
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, $parag2, 0, 'L');
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 2, "", 0, 'C');
        $pdf->SetFont('', 'BU', 12);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "Ce contrat comprend", 0, 'C');
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "", 0, 'C');
        $this->display_content_contrat($pdf, $contrat);
    }

    public function display_content_contrat($pdf, $contrat) {
        $services = BimpObject::getInstance('bimpcontract', 'BContract_Productservices'); // Appel de l'objet
        $list_services = (object) $services->getList(array('use_in_contract' => 1)); // Filtre des services activés
        // Remise en forme de l'array pour traitement
        $array_services = Array();
        foreach ($list_services as $service) {
            $array_services[$service['id']] = array('titre' => $service['titre'], 'description' => $service['content']);
        }

        $nombre_lignes = (int) count($contrat->lines);
        foreach ($contrat->lines as $line) {
            $current_ligne++;
            $need = 10 + 60 + ((int) count($content_service)); // En tete + Marge du bas + nombre de ligne contenu dans le service

            $currentY = (int) $pdf->getY();
            $hauteur = (int) $this->page_hauteur;
            $reste = $hauteur - $currentY;

            if ($reste < $need) {
                $this->_pagefoot($pdf, $outputlangs);
                $pdf->AddPage();
                $this->addLogo($pdf, 12);
                $pdf->SetXY($this->marge_gauche, $this->marge_haute - 6);
                $pdf->Line(15, 32, 195, 32);
            }
            $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
            $pdf->SetFont('', '', 7);
            $pdf->setDrawColor(255, 255, 255);
            $pdf->setColor('fill', 236, 147, 0);
            $pdf->setTextColor(255, 255, 255);
            $pdf->Cell($W, 7, 'Service', 1, null, 'C', true);
            $pdf->setDrawColor(255, 255, 255);
            $pdf->setColor('fill', 255, 255, 255);
            $pdf->setTextColor(0, 0, 0);
            $affichage = str_replace("\n", ' ', $line->description);
            $pdf->Cell($W * 9, 7, $affichage, 1, null, 'L', true);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 7, "", 0, 'L');
            $pdf->setDrawColor(255, 255, 255);
            $pdf->setColor('fill', 236, 147, 0);
            $pdf->setTextColor(255, 255, 255);
            $pdf->Cell($W, 7, 'N° Série', 1, null, 'C', true);
            $pdf->setDrawColor(255, 255, 255);
            $pdf->setColor('fill', 255, 255, 255);
            $pdf->setTextColor(0, 0, 0);
            $pdf->SetFont('', '', 9);
            $pdf->Cell($W * 9, 7, "", 1, null, 'L', true);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 7, "", 0, 'L');
            $pdf->SetFont('', 'B', 9);

            $associate_product = new Product($this->db);
            $associate_product->fetch($line->fk_product);
            $content_service = explode(',', $associate_product->array_options['options_service_content']);
            $pdf->SetFont('', '', 8);
            $first_passage = false;

            foreach ($content_service as $row => $id) {

                if ($array_services[$id]) {
                    if (!$first_passage) {
                        $pdf->Cell($W, 7, '', 0, null, 'C', true);
                        $pdf->setColor('fill', 225, 225, 225);
                        $pdf->Cell($W * 2, 7, 'Service inclu', 1, null, 'L', true);
                        $pdf->Cell($W * 7, 7, 'Commentaire sur le service', 1, null, 'L', true);
                        $pdf->setColor('fill', 255, 255, 255);
                        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 7, "", 0, 'L');
                        $first_passage = true;
                    }
                    $pdf->Cell($W, 7, '', 0, null, 'C', true);
                    $pdf->setColor('fill', 242, 242, 242);
                    $pdf->Cell($W * 2, 7, $array_services[$id]['titre'], 1, null, 'L', true);
                    $pdf->Cell($W * 7, 7, (!empty($array_services[$id]['description'])) ? $array_services[$id]['description'] : "Pas de commentaire sur ce service compris", 1, null, 'L', true);
                    $pdf->setColor('fill', 255, 255, 255);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 7, "", 0, 'L');
                }
            }
            $pdf->setDrawColor(220, 220, 220);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, "", "B", 'L');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, "", "T", 'L');
            $pdf->setDrawColor(255, 255, 255);
        }
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
                $file = $dir . "/Contrat_BIMP_maintenance_" . date("d_m_Y") . "_" . $propref . ".pdf";
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
                $pdf->SetFont('', 'B', 14);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, "Contrat de prestation de service et maintenance informatique", 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, "N° " . $propref, 0, 'C');
                $pdf->SetFont('', 'B', 11);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, "", 0, 'C');

                // Titre partie
                $this->titre_partie($pdf, 'Entre les parties');

                // Entre les parties
                $client->fetch($contrat->socid);
                $pdf->setColor('fill', 255, 255, 255);
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 2;
                $pdf->SetDrawColor(236, 147, 0);
                $pdf->Cell($W, 4, "BIMP GROUPE OLYS", "R", null, 'C', true);
                $pdf->Cell($W, 4, $client->nom, "L", null, 'C', true);
                $pdf->SetFont('', '', 7);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "51,ter Rue de Saint Cyr", "R", null, 'C', true);
                $pdf->Cell($W, 4, $client->address, "L", null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, '69009 LYON', "R", null, 'C', true);
                $pdf->Cell($W, 4, $client->zip . ' ' . $client->town, "L", null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "Tel: 0 812 211 211", "R", null, 'C', true);
                $pdf->Cell($W, 4, "Tel: " . $client->phone, "L", null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "Email : contact@bimp.fr", "R", null, 'C', true);
                $pdf->Cell($W, 4, "Email : " . $client->email, "L", null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "", "R", null, 'C', true);
                $pdf->Cell($W, 4, "SIRET : " . $client->siret, "L", null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "", "R", null, 'C', true);
                $pdf->Cell($W, 4, "Code client : " . $client->code_client, "L", null, 'C', true);

                // Tableau des conditions du contrat
                $pdf->SetFont('', 'BU', 13);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, '', 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, 'Conditions du contrat', 0, 'C');
                $pdf->SetFont('', '', 9);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');

                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
                $pdf->setColor('fill', 242, 242, 242);
                $pdf->setDrawColor(255, 255, 255);

                $extra = (object) $contrat->array_options;
                // Ligne 1
                $pdf->SetFont('', 'B', 7);
                $pdf->Cell($W * 2, 8, "Avenant au contrat N° :", 1, null, 'L', true);
                $pdf->SetFont('', '', 7);
                $pdf->Cell($W * 1.5, 8, "", 1, null, 'L', true);
                $pdf->SetFont('', 'B', 7);
                $pdf->Cell($W * 1.5, 8, "Date d'effet :", 1, null, 'L', true);
                $pdf->SetFont('', '', 7);
                $pdf->Cell($W, 8, date('d/m/Y', $extra->options_date_start), 1, null, 'L', true);
                $pdf->SetFont('', 'B', 7);
                $pdf->Cell($W * 2.5, 8, "Périodicité de facturation :", 1, null, 'L', true);
                $pdf->SetFont('', '', 7);
                $pdf->Cell($W * 1.5, 8, self::$periode[$extra->options_periodicity], 1, null, 'L', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 8, '', 0, 'L');

                // Ligne 2
                $pdf->SetFont('', 'B', 7);
                $pdf->Cell($W * 2, 8, "Annule et remplace contrat :", 1, null, 'L', true);
                $pdf->SetFont('', '', 7);
                $pdf->Cell($W * 1.5, 8, "", 1, null, 'L', true);
                $pdf->SetFont('', 'B', 7);
                $pdf->Cell($W * 1.5, 8, "Durée :", 1, null, 'L', true);
                $pdf->SetFont('', '', 7);
                $pdf->Cell($W, 8, $extra->options_duree_mois . " Mois", 1, null, 'L', true);
                $pdf->SetFont('', 'B', 7);
                $pdf->Cell($W * 2.5, 8, "Coef de révision des prix :", 1, null, 'L', true);
                $pdf->SetFont('', '', 7);
                $pdf->Cell($W * 1.5, 8, ($extra->options_syntec_pdf == 1) ? $extra->options_syntec : "", 1, null, 'L', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 8, '', 0, 'L');

                // Ligne 3
                $pdf->SetFont('', 'B', 7);
                $pdf->Cell($W * 2, 8, "Délais d'intervention :", 1, null, 'L', true);
                $pdf->SetFont('', '', 7);
                $pdf->Cell($W * 1.5, 8, self::$gti[$extra->options_gti], 1, null, 'L', true);
                $pdf->SetFont('', 'B', 7);
                $pdf->Cell($W * 1.5, 8, "Date de fin : ", 1, null, 'L', true);
                $pdf->SetFont('', '', 7);
                $date = new DateTime();
                $date->setTimestamp((int) $extra->options_date_start);
                $date->add(new DateInterval("P" . $extra->options_duree_mois . "M"));
                $date->sub(new DateInterval("P1D"));
                $pdf->Cell($W, 8, $date->format('d/m/Y'), 1, null, 'L', true);
                $pdf->SetFont('', 'B', 7);
                $pdf->Cell($W * 2.5, 8, "Reconduction : ", 1, null, 'L', true);
                $pdf->SetFont('', '', 7);
                $pdf->Cell($W * 1.5, 8, (is_null($extra->options_tacite)) ? "Non" : self::$tacite[$extra->options_tacite], 1, null, 'L', true);

                $pdf->SetFont('', 'BU', 13);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 10, '', 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, 'Description financière', 0, 'C');
                $pdf->SetFont('', '', 9);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');

                $pdf->SetDrawColor(255, 255, 255);
                $pdf->setColor('fill', 255, 255, 255);
                $this->headOfArray($pdf);

                $count = count($contrat->lines);
                $new_page = false;
                if ($count > 12) {
                    $new_page = true;
                    $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche);
                    $pdf->SetX($this->marge_gauche);
                    $pdf->SetFont(''/* 'Arial' */, '', 9);
                    $pdf->setDrawColor(255, 255, 255);
                    $pdf->setColor('fill', 242, 242, 242);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->Cell($W, 8, "Liste des descriptions financière en ANNEXE 1", 1, null, 'C', true);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 50, '', 0, 'C');
                } else {
                    $this->display_lines($pdf, $contrat->lines);
                    $this->display_total($pdf, $contrat->lines);
                }

                $pdf->setY(225);
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 2;
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 2, '', 0, 'L');
                $pdf->SetFont('', 'BU', 8);
                $pdf->setColor('fill', 255, 255, 255);
                $pdf->Cell($W, 8, "POUR BIMP", 1, null, 'L', true);
                $pdf->Cell($W, 8, "POUR LE CLIENT", 1, null, 'L', true);
                $pdf->MultiCell($W, 6, '', 0, 'L');
                $pdf->SetFont('', '', 7);
                $pdf->Cell($W, 8, "Nom et fonction du signataire :", 1, null, 'L', true);
                $pdf->Cell($W, 8, "Nom, fonction et cachet du signataire :", 1, null, 'L', true);
                $pdf->MultiCell($W, 6, '', 0, 'L');
                $pdf->Cell($W, 8, "Date :          /          /", 1, null, 'L', true);
                $pdf->Cell($W, 8, "Précédé de la mention 'Lu et approuvé' + Paraphe de toutes les pages", 1, null, 'L', true);
                $pdf->MultiCell($W, 6, '', 0, 'L');
                $pdf->Cell($W, 8, "Signature", 1, null, 'L', true);
                $pdf->Cell($W, 8, "Date :          /          /", 1, null, 'L', true);
                $pdf->MultiCell($W, 6, '', 0, 'L');
                $pdf->Cell($W, 8, "", 1, null, 'L', true);
                $pdf->Cell($W, 8, "Signature", 1, null, 'L', true);

                $this->_pagefoot($pdf, $outputlangs);
                if ($new_page) {
                    $pdf->AddPage();
                    $this->addLogo($pdf, 20);
                    $pdf->SetXY($this->marge_gauche, $this->marge_haute - 6);
                    $pdf->SetFont('', 'B', 14);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "ANNEXE 1 : Description financière", 0, 'C');
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "Contrat N° " . $propref, 0, 'C');
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
                    $pdf->SetFont('', 'B', 11);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
                    $this->headOfArray($pdf);
                    $this->display_lines($pdf, $contrat->lines);
                    $this->display_total($pdf, $contrat->lines);
                }

                $this->display_cp($pdf, $contrat, $user, $outputlangs);

                $this->_pagefoot($pdf, $outputlangs);
                require_once DOL_DOCUMENT_ROOT . '/synopsiscontrat/core/modules/contract/doc/annexe.class.php';
                $classAnnexe = new annexe($pdf, $this, $outputlangs, ($new_page ? 1 : 0));
                $classAnnexe->getAnnexeContrat($contrat);

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

    function _pagehead(& $pdf, $object, $showadress = 1, $outputlangs, $currentPage = 0) {
        global $conf, $langs;
        if ($currentPage > 1) {
            $showadress = 0;
        }
    }

    function _pagefoot(&$pdf, $outputlangs) {
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->setColor('fill', 255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setY(280);
        $pdf->SetFont('', '', 9);
        $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 20;
        $pdf->Cell($W * 4, 3, 'Page ' . $pdf->PageNo() . '/{:ptp:}', 1, null, 'L', true);
        $pdf->Cell($W * 15, 3, 'Paraphes :', 1, null, 'R', true);
        $pdf->setDrawColor(236, 147, 0);
        $pdf->Cell($W, 3, '', 1, null, 'R', true);
        $pdf->setDrawColor(255, 255, 255);
        $pdf->SetTextColor(200, 200, 200);
        $pdf->SetTextColor(0, 0, 0);
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
