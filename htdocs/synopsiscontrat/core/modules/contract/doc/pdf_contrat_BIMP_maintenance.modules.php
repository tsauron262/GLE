<?php

require_once(DOL_DOCUMENT_ROOT . "/synopsiscontrat/core/modules/synopsiscontrat/modules_synopsiscontrat.php");
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php" );
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

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
    public static $text_head_table = Array(1 => 'Désignation (Détail en page suivante)', 2 => 'TVA', 3 => 'P.U HT', 4 => 'Qté', 5 => 'Total HT', 6 => 'Total TTC');

    public function addLogo(&$pdf, $size, $pdf1 = null) {
        global $conf;
        $logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
        if(is_file($logo)) {
             if(is_object($pdf1)){
                $pdf1->Image($logo, 0, 10, 0, $size, '', '', '', false, 250, 'L');
            } else {
                $pdf->Image($logo, 0, 10, 0, $size, '', '', '', false, 250, 'L');
            }
        }
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
        global $db;
        $pdf->SetFont(''/* 'Arial' */, '', 7);
        $pdf->setColor('fill', 242, 242, 242);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setDrawColor(255, 255, 255);
        $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 13;
        foreach ($lines as $line) {
            if ($line->id > $dernier_id) {
                BimpTools::loadDolClass('product');
                $p = new Product($db);
                $p->fetch($line->fk_product);
                //echo '<pre>';print_r($p);
                $pdf->Cell($W * 5, 6, (strlen($p->label) > 60) ? substr($p->label, 0, 60) . " ..." : $p->label, 1, null, 'L', true);
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

    public function display_total($pdf, $lines, $contrat = null) {
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
                $pdf->Cell($W * 2, 7, number_format($total->$designation, 2, '.', "") . "€", 1, null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 7, "", 0, 'C');
            }
        }
        $pdf->setColor('fill', 255, 255, 255);
        $pdf->Cell($W * 5, 7, "", 1, null, 'L', true);
        $pdf->Cell($W, 7, "", 1, null, 'C', true);
        $pdf->Cell($W * 2, 7, "", 1, null, 'C', true);
        $pdf->Cell($W, 7, "", 1, null, 'C', true);
        $pdf->setColor('fill', 235, 235, 235);
        $pdf->setFont('', 'B', 6);
        $liste_mode_reglement = BimpObject::getModeReglementsArray();
       
        $pdf->Cell($W * 4, 6, "Mode de règlement : " . $liste_mode_reglement[$contrat->array_options['options_moderegl']], 1, null, 'L', true);
        
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 7, "", 0, 'C');
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
        $titre = "Indissociable des Conditions Générales du Contrat";
        $parag1 = "Les présentes Conditions Particulières sont signées en application et exécution des Conditions Générales du Contrat, avec lesquelles elles forment un tout indivisible. Le Client reconnaît avoir pris connaissance desdites Conditions Générales et s'engage à les respecter.";
        $parag2 = "Il est expressément convenu entre les Parties qu'en cas de contradiction entre une ou plusieurs dispositions des Conditions Générales du Contrat et une ou plusieurs dispositions des présentes Conditions Particulières, ces dernières prévalent.";
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
        $pdf->SetFont('', 'B', 9);
        $pdf->setDrawColor(236, 147, 0);
        
        if(!empty($contrat->note_public) || !is_null($contrat->note_public)) {
            $pdf->Line(15, 80, 195, 80);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, 'Note particulière au contrat', 0, 'C');
            $pdf->Line(15, 88, 195, 88);
            $pdf->setY(90);
            $pdf->setFont('','', 8);
            $chaine_description = $contrat->note_public;
            
            $chaine_description = str_replace(":&nbsp;", ' ', $chaine_description);  
            $chaine_description = str_replace("<li>", '', $chaine_description);
            $chaine_description = str_replace("</li>", "\n", $chaine_description);
            $chaine_description = str_replace("<br>", "\n", $chaine_description);
            $chaine_description = str_replace("<br/>", "\n", $chaine_description);
            $chaine_description = str_replace("<br />", "\n", $chaine_description);
            $chaine_description = str_replace("<ul>", '', $chaine_description);
            $chaine_description = str_replace("</ul>", '', $chaine_description);
            $chaine_description = str_replace("<p>", '', $chaine_description);
            $chaine_description = str_replace("</p>", '', $chaine_description);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, $chaine_description, 0, 'L');
            $pdf->setDrawColor(240, 240, 240);
            $pdf->Line(15, $pdf->getY() + 3, 195, $pdf->getY() + 3);
            $pdf->setY($pdf->getY() + 4);
            $pdf->SetFont('', 'B', 9);
        }
        $pdf->setDrawColor(236, 147, 0);
        $pdf->Line(15, $pdf->getY() + 3, 195, $pdf->getY() + 3);
        $pdf->setY($pdf->getY() + 5);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, 'Liste des sites d\'intervention', 0, 'C');
        $pdf->Line(15, $pdf->getY() + 3, 195, $pdf->getY() + 3);
        $pdf->setY($pdf->getY() + 2);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 2, "", 0, 'C');
        $pdf->setDrawColor(240, 240, 240);
        $pdf->SetFont('', '', 8);
        global $db;
        $bimp = new BimpDb($db);
        $id_contact_type = $bimp->getValue('c_type_contact', 'rowid', 'code = "SITE" and element = "contrat"');
        $contacts = $bimp->getRows('element_contact', 'element_id = ' . $contrat->id . ' and fk_c_type_contact = ' . $id_contact_type);
        foreach ($contacts as $key => $infos) {
            $inf = $bimp->getRow("socpeople", "rowid = " . $infos->fk_socpeople);
            
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '- ' . $inf->address . ', ' . $inf->zip . ' ' . $inf->town , 0, 'L');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 2, "", 0, 'C');
        }
        $pdf->SetFont('', '', 9);
        $pdf->SetFont('', 'BU', 12);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "Ce contrat comprend", 0, 'C');
        $pdf->SetFont('', '', 9);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "", 0, 'C');
        $this->display_content_contrat($pdf, $contrat);
    }

    public function display_content_contrat($pdf, $contrat) {
        global $db;
        $services = BimpObject::getInstance('bimpcontract', 'BContract_Productservices'); // Appel de l'objet
        $list_services = (object) $services->getList(array('use_in_contract' => 1)); // Filtre des services activés
        // Remise en forme de l'array pour traitement
        $array_services = Array();
        foreach ($list_services as $service) {
            $array_services[$service['id']] = array('titre' => $service['titre'], 'description' => $service['content']);
        }

        $nombre_lignes = (int) count($contrat->lines);
        foreach ($contrat->lines as $line) {
            BimpTools::loadDolClass('product');
            $p = new Product($db);
            $p->fetch($line->fk_product);
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
            $affichage = str_replace("\n", ' ', $p->label);
            $pdf->Cell($W * 9, 7, $affichage, 1, null, 'L', true);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 7, "", 0, 'L');
            $pdf->setDrawColor(255, 255, 255);
            $pdf->setColor('fill', 236, 147, 0);
            $pdf->setTextColor(255, 255, 255);
            $pdf->Cell($W, 5, 'N° Série', 1, null, 'C', true);
            $pdf->setDrawColor(255, 255, 255);
            $pdf->setColor('fill', 255, 255, 255);
            $pdf->setTextColor(0, 0, 0);
            
                        
//            $serials = BimpObject::getInstance('bimpcontract', 'BContract_Serials_Imei');
//            $list = $serials->getList(['id_contrat' => $contrat->id]);
//            
//            $chaine_serials = '';
//            $last_current = $line->id;
//            $start_serial = 1;
//            foreach ($list as $l => $infos) {
//                $current = $line->id;
//                if($current != $last_current) {
//                    $chaine_serials = '';
//                }
//                if(in_array($line->id, explode(',', $infos['id_line']))) {
//                    $chaine_serials .= $infos['serial'] . ', ';
//                    if(strlen($chaine_serials) >= 80) {
//                        $pdf->Cell($W * 9, 5, $chaine_serials, 1, null, 'L', true);
//                        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 5, "", 0, 'L'); 
//                        $chaine_serials = '';
//                    }
//                }
//                $last_current = $current;
//            }
//            
//            if(strlen($chaine_serials) > 0){
//                $pdf->Cell($W * 9, 5, $chaine_serials, 1, null, 'L', true);
//                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 5, "", 0, 'L');
//            }
            $chaine_serial = '';
            
            $L = BimpObject::getInstance('bimpcontract', 'BContract_contratLine', $line->id);
            
            $serials_tab = json_decode($L->getData('serials'));
            if(count($serials_tab) > 0) {
                foreach ($serials_tab as $serial) {
                    $chaine_serial .= ", " . $serial;
                }
            }
            
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche - 25, 7, $chaine_serial, 0, 'L');
            $pdf->SetFont('', '', 7);
            $pdf->setDrawColor(255, 255, 255);
            $pdf->setColor('fill', 236, 147, 0);
            $pdf->setTextColor(255, 255, 255);
            $pdf->Cell($W, 7, 'Description', 1, null, 'C', true);
            $pdf->setDrawColor(255, 255, 255);
            $pdf->setColor('fill', 255, 255, 255);
            $pdf->SetFont('', 'B', 9);
            $associate_product = new Product($this->db);
            $associate_product->fetch($line->fk_product);
            $pdf->SetFont('', '', 7);
            $pdf->setTextColor(0, 0, 0);
            $nb_char_desc = strlen($associate_product->description);
            $nb_line = ceil($nb_char_desc / 136);
            $last_char = 136;
            $start_char = 0;
            
            
            $chaine_description = $line->description;
            //$chaine_description = strip_tags($chaine_description,"<b><u><i><a><img><p><strong><em><font><tr><blockquote>");
            $chaine_description = str_replace(":&nbsp;", ' ', $chaine_description);  
            $chaine_description = str_replace("<li>", '', $chaine_description);
            $chaine_description = str_replace("</li>", "\n", $chaine_description);
            $chaine_description = str_replace("<br>", "\n", $chaine_description);
            $chaine_description = str_replace("<br/>", "\n", $chaine_description);
            $chaine_description = str_replace("<br />", "\n", $chaine_description);
            $chaine_description = str_replace("<ul>", '', $chaine_description);
            $chaine_description = str_replace("</ul>", '', $chaine_description);
            $chaine_description = str_replace("<p>", '', $chaine_description);
            $chaine_description = str_replace("</p>", '', $chaine_description);
            
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche - 25, 7, $chaine_description, 0, 'L'); 
            
            $first_passage = false;
            
            
            $pdf->setDrawColor(220, 220, 220);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, "", "B", 'L');
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, "", "T", 'L');
            $pdf->setDrawColor(255, 255, 255);
        }
    }

    function write_file($contrat, $outputlangs = '') {
        global $user, $langs, $conf, $mysoc;
        if (!is_object($outputlangs))
            $outputlangs = $langs;
        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("bills");
        $outputlangs->load("contrat");
        $outputlangs->load("products");
        
        
        $bimp_contract = BimpObject::getInstance('bimpcontract', 'BContract_contrat', $contrat->id);
                    
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
                $dir = $conf->contrat->dir_output . "/" . $contrat->ref;
                $file = $dir . "/Contrat_" . $contrat->ref . '_Ex_Client.pdf';
                $file1= $dir . "/Contrat_" . $contrat->ref . '_Ex_'.$mysoc->name.'.pdf';
            }
            $this->contrat = $contrat;

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                $nb_exemplaire = 1;
                $current_exemplaire = 1;
                
                
                $client = new Societe($this->db);
                $BimpDb = new BimpDb($this->db);
                $produit = new Product($this->db);
                $client->fetch($contrat->socid);
                $pdf = "";
                $pdf1 = "";
                $nblignes = sizeof($contrat->lignes);
                $pdf = pdf_getInstance($this->format);
                $pdf1 = pdf_getInstance($this->format);
                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf1->setPrintHeader(false);
                    $pdf->setPrintFooter(true);
                    $pdf1->setPrintFooter(true);
                }
                
                $pdf->Open();
                $pdf1->Open();
                while($current_exemplaire <= $nb_exemplaire){
                    
                    $pdf->AddPage();
                    $pdf1->AddPage();
                    $pdf->SetTitle($contrat->ref);
                    $pdf1->SetTitle($contrat->ref);
                    $pdf->SetSubject($outputlangs->transnoentities("Contract"));
                    $pdf1->SetSubject($outputlangs->transnoentities("Contract"));
                    $pdf->SetCreator("BIMP-ERP " . DOL_VERSION);
                    $pdf1->SetCreator("BIMP-ERP " . DOL_VERSION);
                    $pdf->SetAuthor($user->getFullName($langs));
                    $pdf1->SetAuthor($user->getFullName($langs));
                    $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
                    $pdf1->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
                    $pdf->SetAutoPageBreak(1, $this->margin_bottom);
                    $pdf1->SetAutoPageBreak(1, $this->margin_bottom);
                    $pdf->SetFont('', 'B', 9);
                    $pdf1->SetFont('', 'B', 9);

                // Titre
                $this->addLogo($pdf, 12);
                $this->addLogo($pdf, 12, $pdf1);
                $pdf->SetXY($this->marge_gauche, $this->marge_haute - 17); 
                $pdf1->SetXY($this->marge_gauche, $this->marge_haute - 17);
                $pdf->SetFont('', 'B', 14);
                $pdf1->SetFont('', 'B', 14);
                $pdf->setXY(58,10);
                $pdf1->setXY(58,10);
                
                if($contrat->statut == 0 || $contrat->statut == 10) {
                    $title = "BROUILLON n’ayant aucune valeur commerciale";
                    $ref = "";
                    $pdf->SetTextColor(255,0,0);
                    $pdf1->SetTextColor(255,0,0);
                } else {
                    $title = BimpCore::getConf('bimpcontract_pdf_title');
                    $ref = "N° " . $propref;
                }

                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $title, 0, 'L');
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $title, 0, 'L');
                $pdf->setX(58);
                $pdf1->setX(58);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $ref, 0, 'L');
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $ref, 0, 'L');    
                
                $pdf->SetFont('', 'B', 8);
                $pdf1->SetFont('', 'B', 8);
                $pdf->SetTextColor(0,50,255);
                $pdf1->SetTextColor(255,140,115);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, "Exemplaire à conserver par le client", 0, 'R');
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, "Exemplaire à retourner signé à " . $mysoc->name, 0, 'R');
                
                
//                $pdf->SetFont('', 'B', 8);
//                $pdf1->SetFont('', 'B', 8);
//                $pdf->SetTextColor(0,50,255);
//                $pdf1->SetTextColor(255,140,115);
//                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, "Exemplaire à conserver par le client", 0, 'R');
//                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, "Exemplaire à retourner signé à " . $mysoc->name, 0, 'R');
                    
                $pdf->SetTextColor(0,0,0);
                $pdf1->SetTextColor(0,0,0);
                $pdf->SetFont('', 'B', 11);
                $pdf1->SetFont('', 'B', 11);
//                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, "", 0, 'C');
//                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, "", 0, 'C');
                
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, $bimp_contract->getData('label'), 0, 'L');
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, $bimp_contract->getData('label'), 0, 'L');
                
                $current_exemplaire++;
                // Titre partie
                $this->titre_partie($pdf, 'Entre les parties');
                $this->titre_partie($pdf1, 'Entre les parties');

                // Entre les parties
                $client->fetch($contrat->socid);
                global $mysoc;
                $pdf->setColor('fill', 255, 255, 255); $pdf1->setColor('fill', 255, 255, 255);
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 2;
                $pdf->SetDrawColor(236, 147, 0); $pdf1->SetDrawColor(236, 147, 0);
                $pdf->Cell($W, 4, $mysoc->name, "R", null, 'C', true);
                $pdf1->Cell($W, 4, $mysoc->name, "R", null, 'C', true);
                if(strlen($client->nom) >= 30 && strlen($client->nom) <= 40) { 
                    $pdf->SetFont('', 'B', 9);
                    $pdf1->SetFont('', 'B', 9);
                } else {
                    $pdf->SetFont('', 'B', 7);
                    $pdf1->SetFont('', 'B', 7);
                }
                $pdf->Cell($W, 4, $client->nom . "\n", "L", null, 'C', true);
                $pdf1->Cell($W, 4, $client->nom . "\n", "L", null, 'C', true);
                $pdf->SetFont('', 'B', 11);
                $pdf1->SetFont('', 'B', 11);
                
                
                $pdf->SetFont('', '', 9); $pdf1->SetFont('', '', 9);
                // Si il y a un contact 'Contact client suivi contrat';
                $bimp = new BimpDb($this->db);
                
                $id_type_contact = $bimp->getValue('c_type_contact', 'rowid', 'code = "CUSTOMER" AND element = "contrat"');
                $id_contact = $bimp->getValue('element_contact', 'fk_socpeople', 'element_id = ' . $contrat->id . ' AND fk_c_type_contact = ' . $id_type_contact);
                $contact = new Contact($this->db);
                $contact->fetch($id_contact);
                
                $instance_contact = BimpObject::getInstance('bimpcore', 'Bimp_Contact', $id_contact);
                
                $phone_contact = "";
                if($instance_contact->getData('phone')) $phone_contact = $instance_contact->getData('phone');
                else $phone_contact = $instance_contact->getData('phone_mobile');
                
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "", "R", null, 'C', true);
                $pdf1->Cell($W, 4, "", "R", null, 'C', true);
                $pdf->Cell($W, 4, "Contact : " . $contact->lastname . " " . $contact->firstname, "L", null, 'C', true);
                $pdf1->Cell($W, 4, "Contact : " . $contact->lastname . " " . $contact->firstname, "L", null, 'C', true);
                
                $pdf->SetFont('', '', 7);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, $mysoc->address, "R", null, 'C', true);
                $pdf->Cell($W, 4, $client->address, "L", null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, $mysoc->zip . ' ' . $mysoc->town, "R", null, 'C', true);
                $pdf->Cell($W, 4, $client->zip . ' ' . $client->town, "L", null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, 'Tel: ' . $mysoc->phone, "R", null, 'C', true);
                $pdf->Cell($W, 4, "Tel contact: " . $phone_contact, "L", null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "Email: " . $mysoc->email, "R", null, 'C', true);
                $pdf->Cell($W, 4, "Email contact: " . $instance_contact->getData('email'), "L", null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                
                $commercial = BimpObject::getInstance('bimpcore', 'Bimp_User', $contrat->commercial_suivi_id);
                
                $pdf->Cell($W, 4, "Commercial : " . $commercial->getData('lastname') . ' ' . $commercial->getData('firstname'), "R", null, 'C', true);
                $pdf->Cell($W, 4, "SIREN : " . $client->idprof1, "L", null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "Contact commercial : " . $commercial->getData('email'), "R", null, 'C', true);
                $pdf->Cell($W, 4, "Code client : " . $client->code_client, "L", null, 'C', true);
                $pdf1->SetFont('', '', 7);
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf1->Cell($W, 4, $mysoc->address, "R", null, 'C', true);
                $pdf1->Cell($W, 4, $client->address, "L", null, 'C', true);
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf1->Cell($W, 4, $mysoc->zip . ' ' . $mysoc->town, "R", null, 'C', true);
                $pdf1->Cell($W, 4, $client->zip . ' ' . $client->town, "L", null, 'C', true);
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf1->Cell($W, 4, 'Tel: ' . $mysoc->phone, "R", null, 'C', true);
                $pdf1->Cell($W, 4, "Tel contact: " . $phone_contact, "L", null, 'C', true);
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf1->Cell($W, 4, "Email : " . $mysoc->email, "R", null, 'C', true);
                $pdf1->Cell($W, 4, "Email contact: " . $instance_contact->getData('email'), "L", null, 'C', true);
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf1->Cell($W, 4, "Commercial : " . $commercial->getData('lastname') . ' ' . $commercial->getData('firstname'), "R", null, 'C', true);
                $pdf1->Cell($W, 4, "SIREN : " . $client->idprof1, "L", null, 'C', true);
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf1->Cell($W, 4, "Contact commercial : " . $commercial->getData('email'), "R", null, 'C', true);
                $pdf1->Cell($W, 4, "Code client : " . $client->code_client, "L", null, 'C', true);

                // Tableau des conditions du contrat
                $pdf->SetFont('', 'BU', 13);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, '', 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, 'Descriptif et conditions du contrat', 0, 'C');
                $pdf->SetFont('', '', 9);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf1->SetFont('', 'BU', 13);
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, '', 0, 'C');
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, 'Descriptif et conditions du contrat', 0, 'C');
                $pdf1->SetFont('', '', 9);
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');

                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
                $pdf->setColor('fill', 242, 242, 242);
                $pdf->setDrawColor(255, 255, 255);
                $pdf1->setColor('fill', 242, 242, 242);
                $pdf1->setDrawColor(255, 255, 255);

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
                $pdf1->SetFont('', 'B', 7);
                $pdf1->Cell($W * 2, 8, "Avenant au contrat N° :", 1, null, 'L', true);
                $pdf1->SetFont('', '', 7);
                $pdf1->Cell($W * 1.5, 8, "", 1, null, 'L', true);
                $pdf1->SetFont('', 'B', 7);
                $pdf1->Cell($W * 1.5, 8, "Date d'effet :", 1, null, 'L', true);
                $pdf1->SetFont('', '', 7);
                $pdf1->Cell($W, 8, date('d/m/Y', $extra->options_date_start), 1, null, 'L', true);
                $pdf1->SetFont('', 'B', 7);
                $pdf1->Cell($W * 2.5, 8, "Périodicité de facturation :", 1, null, 'L', true);
                $pdf1->SetFont('', '', 7);
                $pdf1->Cell($W * 1.5, 8, self::$periode[$extra->options_periodicity], 1, null, 'L', true);
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 8, '', 0, 'L');
                
                // Ligne 2
                $pdf->SetFont('', 'B', 7);
                $pdf->Cell($W * 2, 8, "Annule et remplace :", 1, null, 'L', true);
                $pdf->SetFont('', '', 6);
                $pdf->Cell($W * 1.5, 8, $bimp_contract->getData('replaced_ref'), 1, null, 'L', true);
                $pdf->SetFont('', 'B', 7);
                $pdf->Cell($W * 1.5, 8, "Durée :", 1, null, 'L', true);
                $pdf->SetFont('', '', 7);
                $pdf->Cell($W, 8, $extra->options_duree_mois . " Mois", 1, null, 'L', true);
                $pdf->SetFont('', 'B', 7);
                $pdf->Cell($W * 2.5, 8, "Coef de révision des prix : (Syntec)", 1, null, 'L', true);
                $pdf->SetFont('', '', 7);
                $syntec = ($extra->options_syntec > 0) ? $extra->options_syntec : "";
                $pdf->Cell($W * 1.5, 8, $syntec, 1, null, 'L', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 8, '', 0, 'L');
                $pdf1->SetFont('', 'B', 7);
                $pdf1->Cell($W * 2, 8, "Annule et remplace :", 1, null, 'L', true);
                $pdf1->SetFont('', '', 7);
                $pdf1->Cell($W * 1.5, 8, $bimp_contract->getData('replaced_ref'), 1, null, 'L', true);
                $pdf1->SetFont('', 'B', 7);
                $pdf1->Cell($W * 1.5, 8, "Durée :", 1, null, 'L', true);
                $pdf1->SetFont('', '', 7);
                $pdf1->Cell($W, 8, $extra->options_duree_mois . " Mois", 1, null, 'L', true);
                $pdf1->SetFont('', 'B', 7);
                $pdf1->Cell($W * 2.5, 8, "Coef de révision des prix : (Syntec)", 1, null, 'L', true);
                $pdf1->SetFont('', '', 7);
                $pdf1->Cell($W * 1.5, 8, $syntec, 1, null, 'L', true);
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 8, '', 0, 'L');
                
                // Ligne 3
                $pdf->SetFont('', 'B', 7);
                $pdf->Cell($W * 2, 8, "Délai d'intervention :", 1, null, 'L', true);
                $pdf->SetFont('', '', 7);
                $pdf->Cell($W * 1.5, 8, self::$gti[$extra->options_gti], 1, null, 'L', true);
                $pdf->SetFont('', 'B', 7);
                $pdf->Cell($W * 1.5, 8, "Date de fin : ", 1, null, 'L', true);
                $pdf->SetFont('', '', 7);
                $date = new DateTime();
                $date->setTimestamp((int) $extra->options_date_start);
                if($extra->options_duree_mois > 0)
                    $date->add(new DateInterval("P" . $extra->options_duree_mois . "M"));
                $date->sub(new DateInterval("P1D"));
                $pdf->Cell($W, 8, $date->format('d/m/Y'), 1, null, 'L', true);
                $pdf->SetFont('', 'B', 7);
                $pdf->Cell($W * 2.5, 8, "Reconduction : ", 1, null, 'L', true);
                $pdf->SetFont('', '', 7);
                $pdf->Cell($W * 1.5, 8, (is_null($extra->options_tacite)) ? "Non" : self::$tacite[$extra->options_tacite], 1, null, 'L', true);
                $pdf1->SetFont('', 'B', 7);
                $pdf1->Cell($W * 2, 8, "Délai d'intervention :", 1, null, 'L', true);
                $pdf1->SetFont('', '', 7);
                $pdf1->Cell($W * 1.5, 8, self::$gti[$extra->options_gti], 1, null, 'L', true);
                $pdf1->SetFont('', 'B', 7);
                $pdf1->Cell($W * 1.5, 8, "Date de fin : ", 1, null, 'L', true);
                $pdf1->SetFont('', '', 7);
                $pdf1->Cell($W, 8, $date->format('d/m/Y'), 1, null, 'L', true);
                $pdf1->SetFont('', 'B', 7);
                $pdf1->Cell($W * 2.5, 8, "Reconduction : ", 1, null, 'L', true);
                $pdf1->SetFont('', '', 7);
                $pdf1->Cell($W * 1.5, 8, (is_null($extra->options_tacite)) ? "Non" : self::$tacite[$extra->options_tacite], 1, null, 'L', true);
                
                $pdf->SetFont('', 'BU', 13);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 10, '', 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, 'Tarification', 0, 'C');
                $pdf->SetFont('', '', 9);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf1->SetFont('', 'BU', 13);
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 10, '', 0, 'C');
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, 'Tarification', 0, 'C');
                $pdf1->SetFont('', '', 9);
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');

                $pdf->SetDrawColor(255, 255, 255);
                $pdf->setColor('fill', 255, 255, 255);
                $this->headOfArray($pdf);
                $pdf1->SetDrawColor(255, 255, 255);
                $pdf1->setColor('fill', 255, 255, 255);
                $this->headOfArray($pdf1);
                $pdf1->setColor('fill', 255, 255, 255);
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
                    $pdf1->SetX($this->marge_gauche);
                    $pdf1->SetFont(''/* 'Arial' */, '', 9);
                    $pdf1->setDrawColor(255, 255, 255);
                    $pdf1->setColor('fill', 242, 242, 242);
                    $pdf1->SetTextColor(0, 0, 0);
                    $pdf1->Cell($W, 8, "Liste des descriptions financière en ANNEXE 1", 1, null, 'C', true);
                    $pdf1->SetTextColor(0, 0, 0);
                    $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 50, '', 0, 'C');
                } else {
                    $this->display_lines($pdf, $contrat->lines);
                    $this->display_total($pdf, $contrat->lines, $contrat);
                    $this->display_lines($pdf1, $contrat->lines);
                    $this->display_total($pdf1, $contrat->lines, $contrat);
                }

                $pdf->setY(225); $pdf1->setY(225);
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 2;
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 2, '', 0, 'L');
                $pdf->SetFont('', 'BU', 8);
                $pdf->setColor('fill', 255, 255, 255);
                $pdf->Cell($W, 8, "POUR " . $mysoc->name, 1, null, 'L', true);
                $pdf->Cell($W, 8, "POUR LE CLIENT", 1, null, 'L', true);
                $pdf->MultiCell($W, 6, '', 0, 'L');
                $pdf->SetFont('', '', 7);
                $pdf->Cell($W, 8, "Nom du signataire : " . BimpCore::getConf('bimpcontract_pdf_signataire'), 1, null, 'L', true);
                $pdf->Cell($W, 8, "Nom, fonction et cachet du signataire :", 1, null, 'L', true);
                $pdf->MultiCell($W, 6, '', 0, 'L');
                $pdf->Cell($W, 8, "Fonction du signataire: " . BimpCore::getConf('bimpcontract_pdf_signataire_function'), 1, null, 'L', true);
                $pdf->Cell($W, 8, "Précédé de la mention 'Lu et approuvé' + Paraphe de toutes les pages", 1, null, 'L', true);
                $pdf->MultiCell($W, 6, '', 0, 'L');
                $pdf->Cell($W, 8, "Date : " . date('d / m / Y'), 1, null, 'L', true);
                $pdf->Cell($W, 8, "+ Signature des conditions générales de contrat", 1, null, 'L', true);
                $pdf->MultiCell($W, 6, '', 0, 'L');
                $pdf->Cell($W, 8, "Signature", 1, null, 'L', true);
                $pdf->Cell($W, 8, "Date :          /          /", 1, null, 'L', true);
                $pdf->MultiCell($W, 6, '', 0, 'L');
                $pdf->Cell($W, 8, "", 1, null, 'L', true);
                $pdf->Cell($W, 8, "Signature", 1, null, 'L', true);
                $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 2, '', 0, 'L');
                $pdf1->SetFont('', 'BU', 8);
                $pdf1->setColor('fill', 255, 255, 255);
                $pdf1->Cell($W, 8, "POUR " . $mysoc->name, 1, null, 'L', true);
                $pdf1->Cell($W, 8, "POUR LE CLIENT", 1, null, 'L', true);
                $pdf1->MultiCell($W, 6, '', 0, 'L');
                $pdf1->SetFont('', '', 7);
                $pdf1->Cell($W, 8, "Nom et fonction du signataire : " . BimpCore::getConf('bimpcontract_pdf_signataire'), 1, null, 'L', true);
                $pdf1->Cell($W, 8, "Nom, fonction et cachet du signataire :", 1, null, 'L', true);
                $pdf1->MultiCell($W, 6, '', 0, 'L');
                $pdf1->Cell($W, 8, "Date : " . date('d / m / Y'), 1, null, 'L', true);
                $pdf1->Cell($W, 8, "Précédé de la mention 'Lu et approuvé' + Paraphe de toutes les pages", 1, null, 'L', true);
                $pdf1->MultiCell($W, 6, '', 0, 'L');
                $pdf1->Cell($W, 8, "", 1, null, 'L', true);
                $pdf1->Cell($W, 8, "+ Signature des conditions générales de contrat", 1, null, 'L', true);
                $pdf1->MultiCell($W, 6, '', 0, 'L');
                $pdf1->Cell($W, 8, "Signature", 1, null, 'L', true);
                $pdf1->Cell($W, 8, "Date :          /          /", 1, null, 'L', true);
                $pdf1->MultiCell($W, 6, '', 0, 'L');
                $pdf1->Cell($W, 8, "", 1, null, 'L', true);
                $pdf1->Cell($W, 8, "Signature", 1, null, 'L', true);
                
                $signed = (($contrat->statut == 1 || $contrat->statut == 11) && BimpCore::getConf('bimpcontract_pdf_use_signature')) ? true : false;
                
                if($signed) {
                    $choosed_signature = ($bimp_contract->getData('secteur') == "CTE") ? "signed_education.png" : "signed_contrat.png";
                    
                    $logo = $conf->mycompany->dir_output . '/' . $choosed_signature;
                    $pdf1->Image($logo, 30, 255, 50);
                    $pdf->Image($logo, 30, 255, 50);
                }
                
                $this->_pagefoot($pdf, $outputlangs);
                $this->_pagefoot($pdf1, $outputlangs);
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
                    $pdf1->AddPage();
                    $this->addLogo($pdf, 20);
                    $pdf1->SetXY($this->marge_gauche, $this->marge_haute - 6);
                    $pdf1->SetFont('', 'B', 14);
                    $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "ANNEXE 1 : Description financière", 0, 'C');
                    $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "Contrat N° " . $propref, 0, 'C');
                    $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
                    $pdf1->SetFont('', 'B', 11);
                    $pdf1->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
                    $this->headOfArray($pdf1);
                    $pdf->setColor('fill', 255, 255, 255);
                    $this->display_lines($pdf1, $contrat->lines);
                    $pdf->setColor('fill', 255, 255, 255);
                    $this->display_total($pdf1, $contrat->lines);
                    $pdf->setColor('fill', 255, 255, 255);
                }

                $this->display_cp($pdf, $contrat, $user, $outputlangs);
                $this->display_cp($pdf1, $contrat, $user, $outputlangs);
                $this->_pagefoot($pdf, $outputlangs);
                $this->_pagefoot($pdf1, $outputlangs);
                require_once DOL_DOCUMENT_ROOT . '/synopsiscontrat/core/modules/contract/doc/annexe.class.php';
                $classAnnexe = new annexe($pdf, $this, $outputlangs, ($new_page ? 1 : 0));
                $classAnnexe->getAnnexeContrat($contrat);
                }

                if(BimpCore::getConf('bimpcontract_pdf_use_cgc')) {
                    $this->display_cgv($pdf);
                    $this->display_cgv($pdf1);
                }

                if (method_exists($pdf, 'AliasNbPages'))
                    $pdf->AliasNbPages();
                    //$pdf1->AliasNbPages();
                    $pdf->Close();
                    $pdf1->Close();
                    $this->file = $file;
                    $pdf->Output($file, 'f');
                    $pdf1->Output($file1, 'f');
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
    
    public function display_cgv($pdf, $nb = 9) {
        $current = 1;
        for($i=1; $i <= $nb; $i++) {
            $affiche_paraphe = true;
            $pdf->AddPage();
            $pagecountTpl = $pdf->setSourceFile(DOL_DOCUMENT_ROOT . '/bimpcontract/core/doc/cgv.pdf');

            $tplidx = $pdf->importPage($i, "/MediaBox");
            $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);
            if($current == $nb) {
                $affiche_paraphe = false;
            }
            $this->_pagefoot($pdf, $outputlangs, $affiche_paraphe);
            $current++;
        }
    }

    function _pagehead(& $pdf, $object, $showadress = 1, $outputlangs, $currentPage = 0) {
        global $conf, $langs;
        if ($currentPage > 1) {
            $showadress = 0;
        }
    }

    function _pagefoot(&$pdf, $outputlangs, $paraphe = true) {
        global $mysoc, $conf;
        $pdf->SetDrawColor(255, 255, 255);
        $pdf->setColor('fill', 255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setY(280);
        $pdf->SetFont('', '', 9);
        $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 20;
        $pdf->Cell($W * 4, 3, 'Page ' . $pdf->PageNo() . '/{:ptp:}', 1, null, 'L', true);
        if($paraphe){
            $pdf->Cell($W * 15, 3, 'Paraphe :', 1, null, 'R', true);
            $pdf->setDrawColor(236, 147, 0);
            $pdf->Cell($W, 3, '', 1, null, 'R', true);
            $pdf->setDrawColor(255, 255, 255);
            $pdf->SetTextColor(200, 200, 200);
            $pdf->SetTextColor(0, 0, 0);
        }
        $pdf->setY(285);
        $pdf->SetFont('', '', 8);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $mysoc->name . " - SAS au capital de " . $mysoc->capital . ' - ' . $mysoc->address . ' - ' . $mysoc->zip . ' ' . $mysoc->town . ' - Tél ' . $mysoc->phone . ' - SIRET: ' . $conf->global->MAIN_INFO_SIRET  , 0, 'C');
        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, 'APE : '.$conf->global->MAIN_INFO_APE.' - RCS/RM : '.$conf->global->MAIN_INFO_RCS.' - Num. TVA : FR 34 320387483'  , 0, 'C');
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
