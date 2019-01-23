<?php 

	require_once(DOL_DOCUMENT_ROOT . "/synopsiscontrat/core/modules/synopsiscontrat/modules_synopsiscontrat.php");
	require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
	require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
	require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
    require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';

	if (!defined('EURO'))
    define('EURO', chr(128));

	ini_set('max_execution_time', 600);

	class pdf_contrat_courrierBIMPfinapro extends ModeleSynopsiscontrat {
		public $emetteur;
    	var $contrat;
    	var $pdf;
    	var $margin_bottom = 25;

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
        $this->marge_basse = 125;
        $this->option_logo = 1; 
        $this->emetteur = $mysoc;
        if (!$this->emetteur->pays_code)
            $this->emetteur->pays_code = substr($langs->defaultlang, -2);
    }

    public function PrintChapter($num, $title, $file, $mode = false) {
        $this->pdf->AddPage();
        //$this->addLogo($this->pdf, 30);
        $this->pdf->resetColumns();
        $this->ChapterTitle($num, $title);
        $this->pdf->setEqualColumns(2, 100);
        $this->ChapterBody($file, $mode);
    }

    
    public function addLogo(&$pdf, $size){
        global $conf;
        $logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
        $pdf->Image($logo, 0, 10, 0, 20,'','','',false,300,'C');
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
        if ($conf->contrat->dir_output) {
            if (!is_object($contrat)) {
                $id = $contrat;
                require_once(DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php");
                $contrat = new Contrat($this->db);
                $contrat->fetch($id);
                $contrat->fetch_lines(true);
            } else {
                $contrat->fetch_lines(true);
            }
            $BimpDb = new BimpDb($this->db);
            $contrat_bimp = BimpObject::getInstance('bimpcommercial', 'Bimp_Propal', (int) $contrat->id);
            $id_demande = $contrat_bimp->db->getValue('bf_demande', 'id', '`id_contrat` = ' . (int) $contrat->id);
            $contrat->societe = new Societe($this->db);
            $contrat->societe->fetch($contrat->socid);
/*
          ______   ______   .__   __. .___________..______          ___   .___________.
         /      | /  __  \  |  \ |  | |           ||   _  \        /   \  |           |
        |  ,----'|  |  |  | |   \|  | `---|  |----`|  |_)  |      /  ^  \ `---|  |----`
        |  |     |  |  |  | |  . `  |     |  |     |      /      /  /_\  \    |  |
        |  `----.|  `--'  | |  |\   |     |  |     |  |\  \----./  _____  \   |  |
         \______| \______/  |__| \__|     |__|     | _| `._____/__/     \__\  |__|   

 */         
            if ($contrat->specimen) {
                $dir = $conf->contrat->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $propref = sanitize_string($contrat->ref);
                $dir = $conf->contrat->dir_output . "/" . $propref;
                $file = $dir . "/Liasse_finapro_" . date("d_m_Y") . "_" . $propref . ".pdf";
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
                $this->pdf = $pdf;
                if (class_exists('TCPDF')) {
                    if (get_class($pdf) == "FPDI") {
                        if($valfinance->banque!="Grenke")
                            $logo_B="finapro";
                        else
                            $logo_B="lease";
                        $pdf = getNewPdf($this->format,$logo_B);
                        $this->pdf = $pdf;
                    }
                    $pdf->setPrintHeader(true);
                    $pdf->setPrintFooter(true);
                }

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
                $this->addLogo($pdf, 30);
                $pdf->SetXY($this->marge_gauche, $this->marge_haute - 6);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "CONTRAT DE LOCATION N° " . $this->contrat->ref, 0, 'C');
                $pdf->SetXY($this->marge_gauche, $this->marge_haute);
                $pdf->SetFont(''/* 'Arial' */, 'U, B', 9);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 100), 6, "Le locataire:", 0, 'L');
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                $pdf->SetXY($this->marge_gauche, $this->marge_haute + 6);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 100), 6, "La société: " . $contrat->societe->nom, 0, 'L');
                $pdf->SetXY($this->marge_gauche + 60, $this->marge_haute + 6);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, $contrat->societe->forme_juridique . (($contrat->societe->capital > 0) ? " au capital de " . price($contrat->societe->capital) . " €" : ""), 0, 'L');
                $pdf->setX($this->marge_gauche);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Immatriculé sous le Numéro RCS: " . $contrat->societe->idprof4, 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Dont le siège sociale est situé au " . $contrat->societe->address . " " . $contrat->societe->zip . " " . $contrat->societe->town, 0, 'L');
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
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, $nomC, 0, 'L');
                $pdf->SetXY($this->marge_gauche + 100, $this->marge_haute + 24);
                $pdf->SetFont(''/* 'Arial' */, 'U, B', 9);
                $pdf->SetX($this->marge_gauche);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Le loueur:", 0, 'L');
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "La société FINAPRO, SAS au capital de 50 000 € dont le siège social est situé à Jouques (13 490), 23 boulevard du Deffend enregistré sous le SIREN 443 247 978 au RCS d’Aix-en-Provence,", 0, 'L'); //print_r($this->emetteur);
                $contact = $contrat->Liste_Contact(-1, "internal");
                $nomC = "";
                $nomC = "Représentée par Monsieur Laurent ABOULKHEIR, intervenant en qualité de Président.";
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, $nomC, 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Le loueur donne en location, l’équipement désigné ci-dessous (ci-après « équipement »), au locataire qui l'accepte, aux Conditions Générales ci-annexées composées de deux pages recto et aux Conditions Particulières suivantes :", 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
                $pdf->SetFont(''/* 'Arial' */, 'U, B', 9);
                $pdf->SetX($this->marge_gauche);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Description de l'équipement et quantité: ", 0, 'L');
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
                $pdf->SetDrawColor(255, 255, 255);
                
                /* DEBUT ENTE_TETE DU TABLEAU (QUANTITE, DESIGNATION DU MATERIEL, NUMERO DE SERIE) */        
                $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                $pdf->setColor('fill', 237, 124, 28);
                $pdf->SetTextColor(255,255,255);
                $pdf->Cell($W, 8, "Quantité", 1, null, 'L', true);
                $pdf->SetTextColor(0,0,0);
                $X = $this->marge_gauche + $W;
                $pdf->setX($X);
                $pdf->setColor('fill', 237, 124, 28);
                $pdf->SetTextColor(255,255,255);
                $pdf->Cell($W * 7, 8, "Désignation du matériels", 1, null, 'L', true);
                $pdf->SetTextColor(0,0,0);
                $X = $this->marge_gauche + $W * 8;
                $pdf->setX($X);
                $pdf->setColor('fill', 237, 124, 28);
                $pdf->SetTextColor(255,255,255);
                $pdf->MultiCell($W * 2, 8, "Numéro de série", 1, null, 'L', true);
                $pdf->SetTextColor(0,0,0);
                $pdf->SetFont(''/* 'Arial' */, '', 9);
                /* FIN ENTE_TETE DU TABLEAU (QUANTITE, DESIGNATION DU MATERIEL, NUMERO DE SERIE) */  
                                 
                // $where = "`id_parent` = " . $id_demande . " AND `in_contrat` = 1";
                // $count = count($BimpDb->getRows("bf_demande_line", $where, null, 'object', array('*')));
                // $count = $BimpDb->getRows('bf_demande_line', $where, null, 'object', array('id'));
                // foreach ($count as $nb) {
                //     $count_++;
                // 

                // $count = $this->db->query("SELECT COUNT(*) as res FROM " . MAIN_DB_PREFIX . "bf_demande_line WHERE " . $where);
                // $count = $this->db->fetch_object($count);

                 /* DEBUT CORP DU TABLEAU (QUANTITE, DESIGNATION DU MATERIEL, NUMERO DE SERIE) */
                if($count <= 5) {
                    $new_page = false;
                    foreach ($liste_materiel as $liste) {
                        if($liste->id_product > 0) {
                            $object = new Product($this->db);
                            $object->fetch($liste->id_product);
                            $recupe_label = $object->label;
                            $serial = $liste->extra_serials;
                        } else {
                            $serial = $liste->extra_serials;
                            $recupe_label = $liste->label;
                        }
                        if($liste->equipments != ""){
                            $serial_equipment = $BimpDb->getValue("be_equipment", "serial", "`id` = " . $liste->equipements);
                            $serial .= ',' .$serial_equipment;
                        }
                        $taille_label = strlen($recupe_label);
                        $label = ($taille_label < 80) ? $recupe_label : substr($recupe_label, 0, $taille_label).' ...';
                        $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
                        $pdf->SetX($this->marge_gauche);
                        $pdf->SetFont(''/* 'Arial' */, '', 9);
                        $pdf->setColor('fill', 248, 248, 248);
                        $pdf->SetTextColor(0,0,0);
                        $pdf->Cell($W, 8, (int) $liste->qty, 1, null, 'L', true);
                        $pdf->SetTextColor(0,0,0);
                        $X = $this->marge_gauche + $W;
                        $pdf->setX($X);
                        $pdf->setColor('fill', 248, 248, 248);
                        $pdf->SetTextColor(0,0,0);
                        $pdf->Cell($W * 7, 8, $label, 1, null, 'L', true);
                        $pdf->SetTextColor(0,0,0);
                        $M_N = false;
                        $X = $this->marge_gauche + $W * 8;
                        $pdf->setX($X);
                        $pdf->setColor('fill', 248, 248, 248);
                        $pdf->SetTextColor(0,0,0);
                        $pdf->MultiCell($W * 2, 8, $serial, 1, null, 'L', true);
                        $pdf->SetTextColor(0,0,0);
                    }
                } else {
                    $new_page = true;
                    $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche);
                    $pdf->SetX($this->marge_gauche);
                    $pdf->SetFont(''/* 'Arial' */, '', 9);
                    $pdf->setColor('fill', 248, 248, 248);
                    $pdf->SetTextColor(0,0,0);
                    $pdf->Cell($W, 8, "Liste et détails du matériel en annexe", 1, null, 'C', true);
                    $pdf->SetTextColor(0,0,0);
                    $pdf->setXY($this->marge_gauche, 120);
                }
                $X = $this->marge_gauche;
                /* fin CORP DU TABLEAU (QUANTITE, DESIGNATION DU MATERIEL, NUMERO DE SERIE) */

                $pdf->SetFont(''/* 'Arial' */, 'U, B', 9);                
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Evolution de l'équipement:", 0, 'L');
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Le locataire pourra demander au bailleur, au cours de la période de validité du présent contrat la modification de l’équipement informatique remis en location. Les modifications éventuelles du contrat seront déterminées par l’accord des parties.", 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Cette modification pourra porter sur tout ou partie des équipements, par adjonction, remplacement et/ou enlèvement des matériels repris dans l’article 1 ci-dessus.", 0, 'L');
                $pdf->SetFont(''/* 'Arial' */, 'U, B', 9);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Le loyers:", 0, 'L');
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "Le loyer ferme et non révisable en cours de contrat, payable par terme à échoir, par prélèvements automatiques est fixé à :", 0, 'L');
                $X = $this->marge_gauche;
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 4;

                /* DEBUT EN_TETE DU TABLEAU (QUANTITE, DESIGNATION DU MATERIEL, NUMERO DE SERIE) */
                $pdf->SetX($X);
                $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                $pdf->SetTextColor(255,255,255);
                $pdf->setColor('fill', 237, 124, 28);
                $pdf->Cell($W, 6, "NOMBRE DE LOYERS", 1, NULL, 'C', true, NULL, NULL, null, null, 'C');
                $pdf->Cell($W, 6, "MONTANT HT", 1, NULL, 'C', true, NULL, NULL, null, null, 'C');
                $pdf->Cell($W, 6, "PERIODICITE", 1, NULL, 'C', true, NULL, NULL, null, null, 'C');
                $pdf->MultiCell($W, 6, "DUREE", 1, 'C', true, 1, NULL, null, null, null, null, null, null, 'M');
                $pdf->SetTextColor(0,0,0);
                $pdf->setColor('fill', 248, 248, 248);
                /* FIN EN_TETE DU TABLEAU (QUANTITE, DESIGNATION DU MATERIEL, NUMERO DE SERIE) */

                /* DEBUT CORP DU TABLEAU (QUANTITE, DESIGNATION DU MATERIEL, NUMERO DE SERIE) */
                foreach($contrat->lines as $line) {     
                	$periodicite = explode(' ', $line->description);
                	switch ($periodicite[1]) {
                		case 'Mensuelle':
                			$duree = 1*$line->qty;
                			break;
                		case 'Trimestrielle':
                			$duree = 3*$line->qty;
                			break;
                		case 'Semestrielle':
                			$duree = 6*$line->qty;
                			break;
                		case 'Annuelle':
                			$duree = 12*$line->qty;
                			break;
                	}
	                $pdf->SetFont('', '', 8);
	                $pdf->Cell($W, 6, $line->qty, 1, NULL, 'C', TRUE, NULL, NULL, null, null, 'C');
	                $pdf->Cell($W, 6, price($line->subprice) . " €", 1, NULL, 'C', TRUE, NULL, NULL, null, null, 'C');
	                $pdf->Cell($W, 6, $periodicite[1], 1, NULL, 'C', TRUE, NULL, NULL, null, null, 'C');
                    $pdf->MultiCell($W, 6, $duree . " Mois", 1, 'C', true, 1, NULL, null, null, null, null, null, null, 'M');
                }
                /* FIN CORP DU TABLEAU (QUANTITE, DESIGNATION DU MATERIEL, NUMERO DE SERIE) */

                $X = $this->marge_gauche;
                $pdf->SetX($X);
                $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                $pdf->Write(6, "Site d'installation: ");
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, $contrat->societe->address . " à " . $contrat->societe->town, 0, 'L', FALSE, 1, NULL, null, null, null, null, null, null, 'M');
                $pdf->SetX($X);
                $pdf->SetFont(''/* 'Arial' */, '', 7);
                $pdf->Write(2, "Le locataire déclare avoir été parfaitement informé de l’opération lors de la phase précontractuelle, avoir pris connaissance, reçu et accepter toutes les conditions particulières et générales. Il atteste que le contrat est en rapport direct avec son activité professionnelle et souscrit pour les besoins de cette dernière. Le signataire atteste être habilité à l’effet d’engager le locataire au titre du présent contrat. Le locataire reconnait avoir une copie des Conditions Générales, les avoir acceptées sans réserve y compris les clauses attribution de compétence et CNIL.");
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 6, "", 0, 'L', false, 1, NULL, null, null, null, null, null, null, 'M');
                $pdf->SetFont(''/* 'Arial' */, 'u', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 6, "Fait en autant d'exemplaires que de parties, un pour chacune des parties", 0, 'L', false, 1, NULL, null, null, null, null, null, null, 'M');
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                if($new_page) {
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 6, "CONTIENT : Liste du matériel en annexe et Conditions Générales composées de quatres pages recto", 0, 'L', false, 1, NULL, null, null, null, null, null, null, 'M');
                } else {
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 6, "CONTIENT : Conditions Générales composées de quatres pages recto", 0, 'L', false, 1, NULL, null, null, null, null, null, null, 'M');
                }
                $pdf->MultiCell($w, 6, "Fait à :" . "\n" . "Le :", 0, 'L');
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 3;
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
                $pdf->SetAutoPageBreak(1, 0);
                $pdf->SetFont(''/* 'Arial' */, 'U, B', 9);
                $pdf->MultiCell($W, 6, "Pour le locataire" . "\n" , 0, 'L', false, 0);
                $pdf->SetX($this->marge_gauche + $W + 3);
                $pdf->MultiCell($W, 6, "Pour le loueur" . "\n" , 0, 'L', false, 0);
                $pdf->MultiCell($W, 6, "Pour le cessionnaire" . "\n" , 0, 'L', false, 0);
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
                $pdf->SetFont(''/* 'Arial' */, 'U', 8);
                $pdf->MultiCell($W, 6, "Nom : ", 0, 'L', false, 0);
                $pdf->SetX($this->marge_gauche + $W + 3);
                $pdf->MultiCell($W, 6, "Nom : ", 0, 'L', false, 0);
                $pdf->MultiCell($W, 6, "Raison social : ", 0, 'L', false, 0);
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
                $pdf->SetFont(''/* 'Arial' */, 'U', 8);
                $pdf->MultiCell($W, 6, "Qualité : ", 0, 'L', false, 0);
                $pdf->SetX($this->marge_gauche + $W + 3);
                $pdf->MultiCell($W, 6, "Qualité : ", 0, 'L', false, 0);
                $pdf->MultiCell($W, 6, "SIREN : ", 0, 'L', false, 0);
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
                $pdf->SetFont(''/* 'Arial' */, '', 7);
                $pdf->MultiCell($W, 6, "Signature et cachet (lu et approuvé)", 0, 'L', false, 0);
                $pdf->SetX($this->marge_gauche + $W + 3);
                $pdf->MultiCell($W, 6, "Signature et cachet (lu et approuvé)", 0, 'L', false, 0);
                $pdf->SetFont(''/* 'Arial' */, 'U', 8);
                $pdf->MultiCell($W, 6, "Nom / Qualité : ", 0, 'L', false, 0);
                $pdf->SetFont(''/* 'Arial' */, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
                $pdf->MultiCell($W, 6, "" , 0, 'L', false, 0);
                $pdf->SetX($this->marge_gauche + $W + 3);
                $pdf->MultiCell($W, 6, "" , 0, 'L', false, 0);
                $pdf->SetFont(''/* 'Arial' */, '', 7);
                $pdf->MultiCell($W, 6, "Signature et cachet (lu et approuvé)", 0, 'L', false, 0);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
                $pdf->SetFont(''/* 'Arial' */, '', 9);
                $pdf->SetAutoPageBreak(1, $this->margin_bottom);

                if($new_page) {
                    $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche);
                    $this->PrintChapter($this->contrat->ref, 'ANNEXE: LISTE DU MATERIEL DU CONTRAT DE LOCATION N° ','', false);
                    $pdf->setFont('','b', 12);
                    $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
                    $X = $this->marge_gauche;
                    $pdf->SetX($X);
                    $pdf->SetDrawColor(255, 255, 255);
                    $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                    $pdf->setColor('fill', 237, 124, 28);
                    $pdf->SetTextColor(255,255,255);
                    $pdf->Cell($W, 8, "Quantité", 1, null, 'L', true);
                    $pdf->SetTextColor(0,0,0);
                    $X = $this->marge_gauche + $W;
                    $pdf->setX($X);
                    $pdf->setColor('fill', 237, 124, 28);
                    $pdf->SetTextColor(255,255,255);
                    $pdf->Cell($W * 7, 8, "Désignation du matériels", 1, null, 'L', true);
                    $pdf->SetTextColor(0,0,0);
                    $X = $this->marge_gauche + $W * 8;
                    $pdf->setX($X);
                    $pdf->setColor('fill', 237, 124, 28);
                    $pdf->SetTextColor(255,255,255);
                    $pdf->MultiCell($W * 2, 8, "N° de série", 1, null, 'L', true);
                    $pdf->SetTextColor(0,0,0);
                    $pdf->SetFont(''/* 'Arial' */, '', 9);
                    foreach($liste_materiel as $liste) {
                        if($liste->id_product > 0) {
                            $object = new Product($this->db);
                            $object->fetch($liste->id_product);
                            $recupe_label = $object->label;
                            $serial = $liste->extra_serials;
                        } else {
                            $serial = $liste->extra_serials;
                            $recupe_label = $liste->label;
                        }
                        if($liste->equipments != ""){
                            $serial_equipment = $BimpDb->getValue("be_equipment", "serial", "`id` = " . $liste->equipements);
                            $serial .= ',' .$serial_equipment;
                        }
                        $taille_label = strlen($recupe_label);
                        $label = ($taille_label < 80) ? $recupe_label : substr($recupe_label, 0, $taille_label).' ...';
                        $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
                        $pdf->SetX($this->marge_gauche);
                        $pdf->SetFont(''/* 'Arial' */, '', 9);
                        $pdf->setColor('fill', 248, 248, 248);
                        $pdf->SetTextColor(0,0,0);
                        $pdf->Cell($W, 8, (int) $liste->qty, 1, null, 'L', true);
                        $pdf->SetTextColor(0,0,0);
                        $X = $this->marge_gauche + $W;
                        $pdf->setX($X);
                        $pdf->setColor('fill', 248, 248, 248);
                        $pdf->SetTextColor(0,0,0);
                        $pdf->Cell($W * 7, 8, $label, 1, null, 'L', true);
                        $pdf->SetTextColor(0,0,0);
                        $X = $this->marge_gauche + $W * 8;
                        $pdf->setX($X);
                        $pdf->setColor('fill', 248, 248, 248);
                        $pdf->SetTextColor(0,0,0);
                        $pdf->MultiCell($W * 2, 8, $serial, 1, null, 'L', true);
                        $pdf->SetTextColor(0,0,0);
                    
               }
                $pdf->SetAutoPageBreak(1, $this->margin_bottom);
                $X = $this->marge_gauche;
                $pdf->SetX($X);
                $this->marge_gauche = $this->marge_gauche - 25;
                $this->marge_droite = $this->marge_droite - 5;
                $this->marge_haute = $this->marge_haute - 5;
                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
                $pdf->setFont('', '', 5);
                    $this->PrintChapter($this->contrat->ref, 'ANNEXE: CONDITION GENERALES DU CONTRAT DE LOCATION N° ', DOL_DOCUMENT_ROOT . '/synopsisfinanc/doc/contrat_finapro.txt', false);
                $pdf->SetAutoPageBreak(1, 0);
                $pdf->setFont('', '', 8);
                $X = $this->marge_gauche + 20;
                $pdf->SetXY($X, $this->page_hauteur - 50);
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 3;
                $pdf->MultiCell($W, 6, "Pour le locataire" . "\n" . "Nom : " . "\n" . "\n" . "Qualité" . "\n" . "\n" . "Signature et cachet(lu et approuver)", 0, 'L');
                $X = $X + $W;
                $pdf->SetXY($X, $this->page_hauteur - 50);
                $pdf->MultiCell($W, 6, "Pour le loueur" . "\n" . "Signature et cachet", 0, 'L');
                $X = $X + $W;
                $pdf->SetXY($X, $this->page_hauteur - 50);
                $pdf->MultiCell($W, 6, "Pour le Cessionnaire" . "\n" . "Signature et cachet", 0, 'L');
                $pdf->SetXY(95, 289);
                $pdf->SetTextColor(200, 200, 200);
                $pdf->MultiCell(100, 3, 'Conditions Générales de Location FINAPRO V3 du 10/10/2018', 0, 'R', 0);
                $pdf->SetXY(15, 289);
                $pdf->MultiCell(100, 3, 'Contrat location sans service V3 du 01/05/2018', 0, 'L', 0);
                $pdf->SetAutoPageBreak(1, 55);
                $pdf->SetTextColor(0, 0, 0);
/*
            .______   .______        ______     ______  _______     _______.
            |   _  \  |   _  \      /  __  \   /      ||   ____|   /       |
            |  |_)  | |  |_)  |    |  |  |  | |  ,----'|  |__     |   (----`
            |   ___/  |      /     |  |  |  | |  |     |   __|     \   \
            |  |      |  |\  \----.|  `--'  | |  `----.|  |____.----)   |
            | _|      | _| `._____| \______/   \______||_______|_______/

*/
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(true);
                $pdf->annulenb_page = true;
                $pdf->SetAutoPageBreak(1, 55);
                $pdf->SetDrawColor(128, 128, 128);
                $pdf->SetTitle($contrat->ref);
                $pdf->SetSubject($outputlangs->transnoentities("Contract"));
                $pdf->SetCreator("BIMP-ERP " . DOL_VERSION);
                $pdf->SetAuthor($user->getFullName($langs));
                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
                $pdf->AddPage();
                $this->marge_gauche = 20;
                $this->marge_droite = 25;
                $x = $this->marge_gauche;
                $y = $this->marge_haute;
                //titre
                $this->addLogo($pdf, 30);
                $pdf->SetXY($x, $y);
                $pdf->setFont('', 'B', 13);
                $pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 6, "PROCES VERBAL DE RECEPTION ET MISE EN SERVICE DE MATERIEL" . "\n\n" . "CONTRAT DE LOCATION N° " . $contrat->ref, 0, 'C');
                $pdf->setFont('', 'B', 11);
                $pdf->MultiCell($w, 6, '', 0, 'C');
                $pdf->MultiCell($w, 6, '', 0, 'C');
                $pdf->setFont('','',9);

                 //adresse du locataire
                $pdf->setFont('', '', 9);
                $y+=24;
                $pdf->SetXY($x, $y);
                $w = ($this->page_largeur - $this->marge_gauche - $this->marge_droite) / 2;
                $pdf->MultiCell($w, 6, "ADRESSE DU LOCATAIRE:" . "\n" . $contrat->societe->nom . "\n" . $contrat->societe->address . "\n" . $contrat->societe->zip . " " . $contrat->societe->town . "\n", 0, 'L', FALSE, 0);
                $x+=$w;
                $pdf->SetX($x);
                $pdf->MultiCell($w, 6, "ADRESSE DU FOURNISSEUR:\n\n\n\n", 0, 'C');
                $x-=$w;
                $y = $pdf->GetY();
                $y+=6;
                $pdf->SetXY($x, $y);
                $w = ($this->page_largeur - $this->marge_gauche - $this->marge_droite);
                $pdf->setFont('', '', 9);
                $y+=12;
                $pdf->SetXY($x, $y);
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
                $pdf->SetX($this->marge_gauche);
                
                /* DEBUT EN TETE DU TABLEAU (QUANTITE, DESIGNATION DU MATERIEL, NUMERO DE SERIE) */
                $pdf->SetDrawColor(255,255,255);
                $pdf->SetFont(''/* 'Arial' */, '', 9);
                $pdf->setColor('fill', 237, 124, 28);
                $pdf->SetTextColor(255,255,255);
                $pdf->Cell($W, 8, "Quantité", 1, null, 'L', true);
                $pdf->SetTextColor(0,0,0);
                $X = $this->marge_gauche + $W;
                $pdf->setX($X);
                $pdf->setColor('fill', 237, 124, 28);
                $pdf->SetTextColor(255,255,255);
                $pdf->Cell($W * 7, 8, "Désignation du matériels", 1, null, 'L', true);
                $pdf->SetTextColor(0,0,0);
                $X = $this->marge_gauche + $W * 8;
                $pdf->setX($X);
                $pdf->setColor('fill', 237, 124, 28);
                $pdf->SetTextColor(255,255,255);
                $pdf->MultiCell($W * 2, 8, "Numéro de série", 1, null, 'L', true);
                $pdf->SetTextColor(0,0,0);
                /* FIN EN TETE DU TABLEAU (QUANTITE, DESIGNATION DU MATERIEL, NUMERO DE SERIE) */

                /* DEBUT CORP DU TABLEAU (QUANTITE, DESIGNATION DU MATERIEL, NUMERO DE SERIE) */
                $liste_materiel = $BimpDb->getRows("bf_demande_line", $where, null, 'object', array('*'));
                $count = $BimpDb->getRows('bf_demande_line', $where, null, 'object', array('COUNT(*) as res'));
                //$count = $this->db->query("SELECT COUNT(*) as res FROM " . MAIN_DB_PREFIX . "bf_demande_line WHERE " . $where);
                $count = $this->db->fetch_object($count);
                if($count->res <= 5){
                    $new_page = false;
                    foreach($liste_materiel as $liste) {
                        if($liste->id_product > 0) {
                            $sql = $this->db->query("SELECT label FROM " . MAIN_DB_PREFIX . "product WHERE rowid = " .  $liste->id_product);
                            $res = $this->db->fetch_object($sql);
                            $recupe_label = $res->label;
                            $serial = $liste->extra_serials;
                        } else {
                            $serial = $liste->extra_serials;
                            $recupe_label = $liste->label;
                        }
                        if($liste->equipments != ""){
                            $serial_equipment = $BimpDb->getValue('be_equipment', 'serial', 'id = ' . $liste->equipments);
                            $serial .= ',' .$serial_equipment;
                        }
                        $taille_label = strlen($recupe_label);
                        $label = ($taille_label < 80) ? $recupe_label : substr($recupe_label, 0, $taille_label).' ...';
                        $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
                        $pdf->SetX($this->marge_gauche);
                        $pdf->SetFont(''/* 'Arial' */, '', 9);
                        $pdf->setColor('fill', 248, 248, 248);
                        $pdf->SetTextColor(0,0,0);
                        $pdf->Cell($W, 8, (int) $liste->qty, 1, null, 'L', true);
                        $pdf->SetTextColor(0,0,0);
                        $X = $this->marge_gauche + $W;
                        $pdf->setX($X);
                        $pdf->setColor('fill', 248, 248, 248);
                        $pdf->SetTextColor(0,0,0);
                        $pdf->Cell($W * 7, 8, $label, 1, null, 'L', true);
                        $pdf->SetTextColor(0,0,0);

                        $X = $this->marge_gauche + $W * 8;
                        $pdf->setX($X);
                        $pdf->setColor('fill', 248, 248, 248);
                        $pdf->SetTextColor(0,0,0);
                        $pdf->MultiCell($W * 2, 8, $serial, 1, null, 'L', true);
                        $pdf->SetTextColor(0,0,0);
                    }
                } else {
                    $new_page = true;
                    $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche);
                    $pdf->SetX($this->marge_gauche);
                    $pdf->SetFont(''/* 'Arial' */, '', 9);
                    $pdf->setColor('fill', 248, 248, 248);
                    $pdf->SetTextColor(0,0,0);
                    $pdf->Cell($W, 8, "Liste et détails du matériel en annexe", 1, null, 'C', true);
                    $pdf->SetTextColor(0,0,0);
                    $pdf->setXY($this->marge_gauche, 120);
                }
                $X = $this->marge_gauche;
                /* FIN CORP DU TABLEAU (QUANTITE, DESIGNATION DU MATERIEL, NUMERO DE SERIE) */

                $x = $this->marge_gauche;
                $y = $pdf->GetY();
                $y+=9;
                $pdf->SetXY($x, $y);
                $pdf->MultiCell($w, 6, "Le locataire a choisi librement et sous sa responsabilité les équipements, objets du présent contrat, en s’assurant auprès de ses fournisseurs de leur compatibilité y compris dans le cas où ils sont incorporés dans un système préexistant.", 0, 'L');
                
                $pdf->SetX($x);
                $pdf->MultiCell($w, 6, "Le fournisseur déclare que le matériel, ci-dessus désigné, a bien été mis en service selon les normes du constructeur, et le locataire déclare avoir, ce jour, réceptionné ce matériel sans aucune réserve, en bon état de marche, sans vice ni défaut apparent et conforme à la commande passée au fournisseur. En conséquence, le locataire déclare accepter ledit matériel sans restriction, ni réserve, compte tenu du mandat qui lui a été fait par FINAPRO.", 0, 'L');
                $pdf->SetX($x);
                $pdf->MultiCell($w, 6, "Le Loueur / Fournisseur déclare que les matériels livrés sont conformes aux normes et réglementations en vigueur notamment en ce qui concerne l’hygiène et la sécurité au travail.", 0, 'L');
                $pdf->MultiCell($w, 6, "", 0, 'L');
                $pdf->setfont('', 'u, b', 8);
                $pdf->SetX($x);
                $pdf->MultiCell($w, 6, "La signature du procès-verbal de réception et mise en service de matériel rend exigible le 1er loyer.", 0, 'L');
                $pdf->setfont('', '', 8);
                $pdf->MultiCell($w, 6, "", 0, 'L');
                $pdf->SetX($x);
                $pdf->MultiCell($w, 6, "FAIT EN DOUBLE EXEMPLAIRE, UN POUR CHACUNE DES PARTIES", 0, 'L');
                $pdf->SetX($x);
                $pdf->MultiCell($w, 6, "Fait à :" . "\n" . "Le :", 0, 'L');
                $W = $w / 2;
                $y = $pdf->GetY();
                $y+=6;
                $pdf->SetXY($x, $y);
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 2;
                //locataire
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
                $pdf->SetAutoPageBreak(1, 0);
                $pdf->SetFont(''/* 'Arial' */, '', 9);
                $pdf->SetX($x);
                $pdf->MultiCell($W, 6, "POUR LE LOCATAIRE" . "\n" . "Nom :" . "\n" . "Qualité :" . "\n" . "Signature et cachet (lu et approuvé)" , 0, 'L', false, 0);
                $pdf->MultiCell($W, 6, "POUR LE LOUEUR" . "\n" . "Signature et cachet" , 0, 'C', false, 0);
                $pdf->SetFont(''/* 'Arial' */, '', 9);
                $pdf->SetXY(95, 289);
                $pdf->SetTextColor(200, 200, 200);
                $pdf->MultiCell(100, 3, 'Contrat location sans service V3 du 01/05/2018', 0, 'R', 0);
                $pdf->SetXY(15, 289);
                $pdf->MultiCell(100, 3, 'Procès-verbal de livraison FINAPRO V3 du 01/05/2018', 0, 'L', 0);
                $pdf->Close();
                $pdf->Output($file, 'f');
/*
            .___  ___.      ___      .__   __.  _______       ___   .___________.
            |   \/   |     /   \     |  \ |  | |       \     /   \  |           |
            |  \  /  |    /  ^  \    |   \|  | |  .--.  |   /  ^  \ `---|  |----`
            |  |\/|  |   /  /_\  \   |  . `  | |  |  |  |  /  /_\  \    |  |
            |  |  |  |  /  _____  \  |  |\   | |  '--'  | /  _____  \   |  |
            |__|  |__| /__/     \__\ |__| \__| |_______/ /__/     \__\  |__|

*/

                $file = $dir . "/Mandat_prelevement_" . date("d_m_Y") . "_" . $propref . ".pdf";

                if (file_exists($dir)) {
                    $pdf = "";
                    $nblignes = sizeof($contrat->lignes);
                    // Protection et encryption du pdf
                    $pdf = pdf_getInstance($this->format);
                    $this->pdf = $pdf;
                    if (class_exists('TCPDF')) {
                        if (get_class($pdf) == "FPDI") {
                            if($valfinance->banque!="Grenke")
                            $logo_B="finapro";
                        else
                            $logo_B="lease";
                        $pdf = getNewPdf($this->format,$logo_B);
                            $this->pdf = $pdf;
                        }
                        $pdf->setPrintHeader(false);
                        $pdf->setPrintFooter(true);
                    }
                }
                $pdf->annulenb_page = true;
                $pdf->Open();
                $pdf->SetAutoPageBreak(1, 55);
                $pdf->SetDrawColor(128, 128, 128);
                $pdf->SetTitle($contrat->ref);
                $pdf->SetSubject($outputlangs->transnoentities("Contract"));
                $pdf->SetCreator("BIMP-ERP " . DOL_VERSION);
                $pdf->SetAuthor($user->getFullName($langs));
                $this->marge_haute = 10;
                $this->marge_basse = 10;
                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);


                $pdf->AddPage();
                $this->marge_gauche = 20;
                $this->marge_droite = 25;
                $x = $this->marge_gauche;
                $y = $this->marge_haute;
                //titre
                $pdf->SetXY($x, $y);
                $this->pdf->setEqualColumns(2, 98);
                $pdf->setFont('','B',10);
                $pdf->MultiCell($W, 6, "Mandat de Prélèvement SEPA", 0, 'C',false,0);
                $pdf->MultiCell($W, 6, "Mandat de Prélèvement SEPA", 0, 'C',false,0);
                $this->pdf->setEqualColumns(2, 98);
                $y = $this->marge_haute + 8;
                $pdf->setXY($x, $y);
                $pdf->setFont('','',6.5);
                $pdf->MultiCell($W, 6, "En signant ce formulaire de mandat, vous autorisez le créancier à envoyer des instructions à votre banque pour débiter votre compte, et votre banque à débiter votre compte conformément aux instructions du créancier. Vous bénéficiez du droit d’être remboursé par votre banque selon les conditions décrites dans la convention que vous avez passée avec elle. Une demande de remboursement doit être présentée dans les 8 semaines suivant la date de débit de votre compte pour un prélèvement autorisé. Vos droits concernant
le présent mandat sont expliqués dans un document que vous pouvez obtenir auprès de votre banque.
Le présent mandat est donné pour le débiteur en référence, il sera utilisable pour les contrats conclus avec celui-ci et aux termes desquels le débiteur donne autorisation de paiement en utilisant le présent mandat.  Les informations contenues dans le présent mandat, qui doit être complété, sont destinées à n'être utilisées par le créancier que pour la gestion de sa relation avec son client. Elles pourront donner lieu à l'exercice, par ce dernier, de ses droits d'opposition, d’accès et de rectification tels que prévus aux articles 38 et suivants de la Loi n° 78-17 du 6 janvier 1978 relative à l'informatique, aux fichiers et aux libertés. En signant ce mandat le débiteur, par dérogation à la règle de pré-notification de 14 jours, déclare que le délai de pré-notification des prélèvements par le créancier est fixé à 2 jours avant la date d’échéance du prélèvement
" , 0, 'J', false, 0);
                $pdf->MultiCell($W, 6, "En signant ce formulaire de mandat, vous autorisez le créancier à envoyer des instructions à votre banque pour débiter votre compte, et votre banque à débiter votre compte conformément aux instructions du créancier. Vous bénéficiez du droit d’être remboursé par votre banque selon les conditions décrites dans la convention que vous avez passée avec elle. Une demande de remboursement doit être présentée dans les 8 semaines suivant la date de débit de votre compte pour un prélèvement autorisé. Vos droits concernant
le présent mandat sont expliqués dans un document que vous pouvez obtenir auprès de votre banque.
Le présent mandat est donné pour le débiteur en référence, il sera utilisable pour les contrats conclus avec celui-ci et aux termes desquels le débiteur donne autorisation de paiement en utilisant le présent mandat.  Les informations contenues dans le présent mandat, qui doit être complété, sont destinées à n'être utilisées par le créancier que pour la gestion de sa relation avec son client. Elles pourront donner lieu à l'exercice, par ce dernier, de ses droits d'opposition, d’accès et de rectification tels que prévus aux articles 38 et suivants de la Loi n° 78-17 du 6 janvier 1978 relative à l'informatique, aux fichiers et aux libertés. En signant ce mandat le débiteur, par dérogation à la règle de pré-notification de 14 jours, déclare que le délai de pré-notification des prélèvements par le créancier est fixé à 2 jours avant la date d’échéance du prélèvement
" , 0, 'J', false, 0);
                $pdf->setXY($x, $y +62);
                $pdf->setFont('','b',8);
                $pdf->MultiCell($W, 6, "Informations Débiteur", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Informations Débiteur", 0, 'L',false,0);
                $pdf->Line($x+1, $y+66, $x+81, $y+66);
                $pdf->Line($x+83, $y+66, $x+163, $y+66);
                $pdf->setXY($x, $y +68);
                $pdf->setFont('','',8);
                $pdf->MultiCell($W, 6, "Raison social : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Raison social : ", 0, 'L',false,0);
                $pdf->setXY($x, $y +72);
                $pdf->MultiCell($W, 6, "Adresse : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Adresse : ", 0, 'L',false,0);
                $pdf->setXY($x, $y +80);
                $pdf->MultiCell($W, 6, "Code postal et ville : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Code postal et ville : ", 0, 'L',false,0);
                $pdf->setXY($x, $y +88);
                $pdf->MultiCell($W, 6, "SIREN : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "SIREN : ", 0, 'L',false,0);
                $pdf->setXY($x, $y +92);
                $pdf->MultiCell($W, 6, "Pays : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Pays : ", 0, 'L',false,0);
                $pdf->setXY($x, $y +96);
                $pdf->MultiCell($W, 6, "Email : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Email : ", 0, 'L',false,0);

                $pdf->setXY($x, $y +101);
                $pdf->setFont('','b',8);
                $pdf->MultiCell($W, 6, "Coordonnées Bancaire débiteur : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Coordonnées Bancaire débiteur : ", 0, 'L',false,0);
                $pdf->Line($x+1, $y+105, $x+81, $y+105);
                $pdf->Line($x+83, $y+105, $x+163, $y+105);
                $pdf->setXY($x, $y +107);
                $pdf->setFont('','',8);
                $pdf->MultiCell($W, 6, "IBAN : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "IBAN : ", 0, 'L',false,0);
                $pdf->setXY($x, $y +111);
                $pdf->MultiCell($W, 6, "BIC : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "BIC : ", 0, 'L',false,0);

                $pdf->setXY($x, $y +116);
                $pdf->setFont('','b',8);
                $pdf->MultiCell($W, 6, "Coordonnées Bancaire Créancier : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Coordonnées Bancaire Créancier : ", 0, 'L',false,0);
                $pdf->Line($x+1, $y+120, $x+81, $y+120);
                $pdf->Line($x+83, $y+120, $x+163, $y+120);
                $pdf->setXY($x, $y +122);
                $pdf->setFont('','',8);
                $pdf->MultiCell($W, 6, "Raison social : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Raison social : ", 0, 'L',false,0);
                $pdf->setXY($x, $y +126);
                $pdf->MultiCell($W, 6, "Adresse : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Adresse : ", 0, 'L',false,0);
                $pdf->setXY($x, $y +134);
                $pdf->MultiCell($W, 6, "Code postal et ville : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Code postal et ville : ", 0, 'L',false,0);
                $pdf->setXY($x, $y +138);
                $pdf->MultiCell($W, 6, "Pays : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Pays : ", 0, 'L',false,0);
                $pdf->setXY($x, $y +142);
                $pdf->MultiCell($W, 6, "ICS : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "ICS : ", 0, 'L',false,0);

                $pdf->setXY($x, $y +147);
                $pdf->setFont('','b',8);
                $pdf->MultiCell($W, 6, "Référence Unique du Mandat (RUM) : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Référence Unique du Mandat (RUM) : ", 0, 'L',false,0);
                $pdf->Line($x+1, $y+151, $x+81, $y+151);
                $pdf->Line($x+83, $y+151, $x+163, $y+151);

                $pdf->setXY($x, $y +160);
                $pdf->setFont('','b',8);
                $pdf->MultiCell($W, 6, "Informations Type de Paiement : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Informations type de Paiement : ", 0, 'L',false,0);
                $pdf->Line($x+1, $y+164, $x+81, $y+164);
                $pdf->Line($x+83, $y+164, $x+163, $y+164);
                $pdf->setFont('','',8);
                $pdf->setXY($x, $y +168);
                $pdf->MultiCell($W, 6, "Paiement: Récurent / Unique", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Paiement: Récurent / Unique", 0, 'L',false,0);

                $pdf->setXY($x, $y +176);
                $pdf->setFont('','b',8);
                $pdf->MultiCell($W, 6, "Signature : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Signature : ", 0, 'L',false,0);
                $pdf->Line($x+1, $y+180, $x+81, $y+180);
                $pdf->Line($x+83, $y+180, $x+163, $y+180);
                $pdf->setFont('','',8);
                $pdf->setXY($x, $y +184);
                $pdf->MultiCell($W, 6, "Date : ", 0, 'L',false,0);
                $pdf->MultiCell($W, 6, "Date : ", 0, 'L',false,0);

                $pdf->SetAutoPageBreak(1, 0);
                $pdf->setXY($x, $y +194);
                $pdf->SetDrawColor(0,0,0);
                $pdf->setColor('fill', 255, 255, 255);
                $pdf->Cell($W - 5, 40, '', 1, null, 'C', true);
                $pdf->setX($x+84);
                $pdf->SetDrawColor(0,0,0);
                $pdf->setColor('fill', 255, 255, 255);
                $pdf->Cell($W - 5, 40, '', 1, null, 'C', true);
                $pdf->setXY($x, $y +195);
                $pdf->setFont('','I',7);
                $pdf->MultiCell($W, 6, "Signature", 0, 'L',false,0);
                $pdf->setX($x+84);
                $pdf->MultiCell($W, 6, "Signature", 0, 'L',false,0);
                $pdf->setFont('','',8);

                $pdf->setXY($x, $y +234);
                $pdf->setFont('','I',7);
                $pdf->MultiCell($W, 6, "Joindre un RIB", 0, 'L',false,0);
                $pdf->setX($x+84);
                $pdf->MultiCell($W, 6, "Joindre un RIB", 0, 'L',false,0);
                $pdf->setFont('','',8);

                // Footer
                $pdf->SetXY(95, 289);
                $pdf->SetTextColor(200, 200, 200);
                $pdf->MultiCell(100, 3, 'Contrat location sans service V3 du 01/05/2018', 0, 'R', 0);
                $pdf->SetXY(15, 289);
                $pdf->MultiCell(100, 3, 'Mandat SEPA FINAPRO V3 du 01/05/2018', 0, 'L', 0);

                if (method_exists($pdf, 'AliasNbPages'))
                    $pdf->AliasNbPages();
                $pdf->Close();
                $pdf->Output($file, 'f');

                return 1;   // Pas d'erreur
            } else {
                $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        }
        } else {
            $this->error = $langs->trans("ErrorConstantNotDefined", "CONTRACT_OUTPUTDIR");
            return 0;
        }


        $this->error = $langs->trans("ErrorUnknown");
        return 0;   // Erreur par defaut

    
    } 

    // FIN GENERATION

    function ConvNumberLetter($Nombre, $Devise, $Langue) {
        $dblEnt = '';
        $byDec = '';
        $bNegatif = '';
        $strDev = '';
        $strCentimes = '';

        if ($Nombre < 0) {
            $bNegatif = true;
            $Nombre = abs($Nombre);
        }
        $dblEnt = intval($Nombre);
        $byDec = round(($Nombre - $dblEnt) * 100);
        if ($byDec == 0) {
            if ($dblEnt > 999999999999999) {
                return "#TropGrand";
            }
        } else {
            if ($dblEnt > 9999999999999.99) {
                return "#TropGrand";
            }
        }
        switch ($Devise) {
            case 0 :
                if ($byDec > 0)
                    $strDev = " virgule";
                break;
            case 1 :
                $strDev = " Euro";
                if ($byDec > 0)
                    $strCentimes = $strCentimes . " Cents";
                break;
            case 2 :
                $strDev = " Dollar";
                if ($byDec > 0)
                    $strCentimes = $strCentimes . " Cent";
                break;
        }
        if (($dblEnt > 1) && ($Devise != 0))
            $strDev = $strDev . "s";

        $NumberLetter = $this->ConvNumEnt(floatval($dblEnt), $Langue) . $strDev . " " . $this->ConvNumDizaine($byDec, $Langue) . $strCentimes;
        return $NumberLetter;
    }

    private function ConvNumEnt($Nombre, $Langue) {
        $byNum = $iTmp = $dblReste = '';
        $StrTmp = '';
        $NumEnt = '';
        $iTmp = $Nombre - (intval($Nombre / 1000) * 1000);
        $NumEnt = $this->ConvNumCent(intval($iTmp), $Langue);
        $dblReste = intval($Nombre / 1000);
        $iTmp = $dblReste - (intval($dblReste / 1000) * 1000);
        $StrTmp = $this->ConvNumCent(intval($iTmp), $Langue);
        switch ($iTmp) {
            case 0 :
                break;
            case 1 :
                $StrTmp = "mille ";
                break;
            default :
                $StrTmp = $StrTmp . " mille ";
        }
        $NumEnt = $StrTmp . $NumEnt;
        $dblReste = intval($dblReste / 1000);
        $iTmp = $dblReste - (intval($dblReste / 1000) * 1000);
        $StrTmp = $this->ConvNumCent(intval($iTmp), $Langue);
        switch ($iTmp) {
            case 0 :
                break;
            case 1 :
                $StrTmp = $StrTmp . " million ";
                break;
            default :
                $StrTmp = $StrTmp . " millions ";
        }
        $NumEnt = $StrTmp . $NumEnt;
        $dblReste = intval($dblReste / 1000);
        $iTmp = $dblReste - (intval($dblReste / 1000) * 1000);
        $StrTmp = $this->ConvNumCent(intval($iTmp), $Langue);
        switch ($iTmp) {
            case 0 :
                break;
            case 1 :
                $StrTmp = $StrTmp . " milliard ";
                break;
            default :
                $StrTmp = $StrTmp . " milliards ";
        }
        $NumEnt = $StrTmp . $NumEnt;
        $dblReste = intval($dblReste / 1000);
        $iTmp = $dblReste - (intval($dblReste / 1000) * 1000);
        $StrTmp = $this->ConvNumCent(intval($iTmp), $Langue);
        switch ($iTmp) {
            case 0 :
                break;
            case 1 :
                $StrTmp = $StrTmp . " billion ";
                break;
            default :
                $StrTmp = $StrTmp . " billions ";
        }
        $NumEnt = $StrTmp . $NumEnt;
        return $NumEnt;
    }

    private function ConvNumDizaine($Nombre, $Langue) {
        $TabUnit = $TabDiz = '';
        $byUnit = $byDiz = '';
        $strLiaison = '';

        $TabUnit = array("", "un", "deux", "trois", "quatre", "cinq", "six", "sept",
            "huit", "neuf", "dix", "onze", "douze", "treize", "quatorze", "quinze",
            "seize", "dix-sept", "dix-huit", "dix-neuf");
        $TabDiz = array("", "", "vingt", "trente", "quarante", "cinquante",
            "soixante", "soixante", "quatre-vingt", "quatre-vingt");
        if ($Langue == 1) {
            $TabDiz[7] = "septante";
            $TabDiz[9] = "nonante";
        } else if ($Langue == 2) {
            $TabDiz[7] = "septante";
            $TabDiz[8] = "huitante";
            $TabDiz[9] = "nonante";
        }
        $byDiz = intval($Nombre / 10);
        $byUnit = $Nombre - ($byDiz * 10);
        $strLiaison = "-";
        if ($byUnit == 1)
            $strLiaison = " et ";
        switch ($byDiz) {
            case 0 :
                $strLiaison = "";
                break;
            case 1 :
                $byUnit = $byUnit + 10;
                $strLiaison = "";
                break;
            case 7 :
                if ($Langue == 0)
                    $byUnit = $byUnit + 10;
                break;
            case 8 :
                if ($Langue != 2)
                    $strLiaison = "-";
                break;
            case 9 :
                if ($Langue == 0) {
                    $byUnit = $byUnit + 10;
                    $strLiaison = "-";
                }
                break;
        }
        $NumDizaine = $TabDiz[$byDiz];
        if ($byDiz == 8 && $Langue != 2 && $byUnit == 0)
            $NumDizaine = $NumDizaine . "s";
        if ($TabUnit[$byUnit] != "") {
            $NumDizaine = $NumDizaine . $strLiaison . $TabUnit[$byUnit];
        } else {
            $NumDizaine = $NumDizaine;
        }
        return $NumDizaine;
    }

    private function ConvNumCent($Nombre, $Langue) {
        $TabUnit = '';
        $byCent = $byReste = '';
        $strReste = '';
        $NumCent = '';
        $TabUnit = array("", "un", "deux", "trois", "quatre", "cinq", "six", "sept", "huit", "neuf", "dix");

        $byCent = intval($Nombre / 100);
        $byReste = $Nombre - ($byCent * 100);
        $strReste = $this->ConvNumDizaine($byReste, $Langue);
        switch ($byCent) {
            case 0 :
                $NumCent = $strReste;
                break;
            case 1 :
                if ($byReste == 0)
                    $NumCent = "cent";
                else
                    $NumCent = "cent " . $strReste;
                break;
            default :
                if ($byReste == 0)
                    $NumCent = $TabUnit[$byCent] . " cents";
                else
                    $NumCent = $TabUnit[$byCent] . " cent " . $strReste;
        }
        return $NumCent;
    }

}

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

function int2str($a) {
    $joakim = explode('.', $a);
    if (isset($joakim[1]) && $joakim[1] != '') {
        return int2str($joakim[0]) . ' virgule ' . int2str($joakim[1]);
    }
    if ($a < 0)
        return 'moins ' . int2str(-$a);
    if ($a < 17) {
        switch ($a) {
            case 0: return 'zero';
            case 1: return 'un';
            case 2: return 'deux';
            case 3: return 'trois';
            case 4: return 'quatre';
            case 5: return 'cinq';
            case 6: return 'six';
            case 7: return 'sept';
            case 8: return 'huit';
            case 9: return 'neuf';
            case 10: return 'dix';
            case 11: return 'onze';
            case 12: return 'douze';
            case 13: return 'treize';
            case 14: return 'quatorze';
            case 15: return 'quinze';
            case 16: return 'seize';
        }
    } else if ($a < 20) {
        return 'dix-' . int2str($a - 10);
    } else if ($a < 100) {
        if ($a % 10 == 0) {
            switch ($a) {
                case 20: return 'vingt';
                case 30: return 'trente';
                case 40: return 'quarante';
                case 50: return 'cinquante';
                case 60: return 'soixante';
                case 70: return 'soixante-dix';
                case 80: return 'quatre-vingt';
                case 90: return 'quatre-vingt-dix';
            }
        } elseif (substr($a, -1) == 1) {
            if (((int) ($a / 10) * 10) < 70) {
                return int2str((int) ($a / 10) * 10) . '-et-un';
            } elseif ($a == 71) {
                return 'soixante-et-onze';
            } elseif ($a == 81) {
                return 'quatre-vingt-un';
            } elseif ($a == 91) {
                return 'quatre-vingt-onze';
            }
        } elseif ($a < 70) {
            return int2str($a - $a % 10) . '-' . int2str($a % 10);
        } elseif ($a < 80) {
            return int2str(60) . '-' . int2str($a % 20);
        } else {
            return int2str(80) . '-' . int2str($a % 20);
        }
    } else if ($a == 100) {
        return 'cent';
    } else if ($a < 200) {
        return int2str(100) . ' ' . int2str($a % 100);
    } else if ($a < 1000) {
        if ($a % 100 == 0)
            return int2str((int) ($a / 100)) . ' ' . int2str(100);
        if ($a % 100 != 0)
            return int2str((int) ($a / 100)) . ' ' . int2str(100) . ' ' . int2str($a % 100);
    } else if ($a == 1000) {
        return 'mille';
    } else if ($a < 2000) {
        return int2str(1000) . ' ' . int2str($a % 1000) . ' ';
    } else if ($a < 1000000) {
        return int2str((int) ($a / 1000)) . ' ' . int2str(1000) . ' ' . int2str($a % 1000);
    }
}

function getNewPdf($format,$logo_B) {
    if (!class_exists("FPDI222")) {
        
        class FPDI222 extends FPDI {
            
            public $logoB;
            
            function setHeader() {
                global $conf, $langs, $mysoc;
                $logo = false;
                if (is_file(DOL_DOCUMENT_ROOT.'/synopsisfinanc/img/'. $this->logoB.".png")) {
                    $logo = DOL_DOCUMENT_ROOT.'/synopsisfinanc/img/'.$this->logoB . ".png";
                } else {
                    $logo = $conf->mycompany->dir_output . '/logos' . '/' . $mysoc->logo;
                }
                if (is_readable($logo)) {
                    $this->Image($logo, 0, 5, 0, 25,'','','',false,300,'C');
                }
            }

            function setFooter() {
                $this->SetAutoPageBreak(1, 0);
                $this->SetXY(190, 289);
                if (!isset($this->annulenb_page) || !$this->annulenb_page)
                    $this->MultiCell(15, 3, '' . $this->PageNo() . '/{:ptp:}', 0, 'R', 0);
                $this->SetAutoPageBreak(1, $this->margin_bottom);
            }

        }

    }
    $return =new FPDI222('P', 'mm', $format);
    $return->logoB=$logo_B;
    return $return;
	}

    
