<?php

require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

if (!defined('EURO'))
    define('EURO', chr(128));

class pdf_fi {
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
        $this->marge_haute = 10;
        $this->marge_basse = 0;
        $this->option_logo = 1;
        $this->emetteur = $mysoc;
        if (!$this->emetteur->pays_code)
            $this->emetteur->pays_code = substr($langs->defaultlang, -2);
    }

    public static $text_head_table = Array(1 => 'Désignation (Détail en page suivante)', 2 => 'TVA', 3 => 'P.U HT', 4 => 'Qté', 5 => 'Total HT', 6 => 'Total TTC');

    public function addLogo(&$pdf, $size, $pdf1 = null) {
        global $conf;
        $logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;

        if (1){//isset($this->object->array_options['options_type']) && in_array($this->object->array_options['options_type'], array('R', 'C', 'ME', 'CO'))) {
            $testFile = str_replace(array(".jpg", ".png"), "_PRO.png", $logo);
            if (is_file($testFile))
                $logo = $testFile;
        }
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
    
    function write_file($fi, $outputlangs = '') {
        global $user, $langs, $conf, $mysoc;
        if (!is_object($outputlangs))
            $outputlangs = $langs;
        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("bills");
        $outputlangs->load("contrat");
        $outputlangs->load("products");
        //$outputlangs->setPhpLang();
        
        if ($conf->ficheinter->dir_output) {
           
            if(!is_object($fi)) {
                BimpTools::loadDolClass('fichinter');
                $fi = new Fichinter($this->db);
                $fi->fetch($fi);
            }
            
            $this->fi = $fi;
            $fiche = BimpObject::getInstance('bimptechnique', 'BT_ficheInter', $fi->id);
            
            if ($fi->specimen) {
                $dir = $conf->ficheinter->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $propref = sanitize_string($fi->ref);
                $dir = $conf->ficheinter->dir_output . "/" . $fi->ref;
                $file = $dir . '/'.$fi->ref.'.pdf';
            }
            
            
            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }
            
            if (file_exists($dir)) {                
                $pdf = "";
                $nblignes = sizeof($fi->lignes);
                $pdf = pdf_getInstance($this->format);
                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->Open();
                $pdf->AddPage();
                $pdf->SetTitle($fi->ref);
                $pdf->SetSubject($outputlangs->transnoentities("Fiche d'interventions"));
                $pdf->SetCreator("BIMP-ERP " . DOL_VERSION);
                $pdf->SetAuthor($user->getFullName($langs));
                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
                $pdf->SetAutoPageBreak(1,10);
                $pdf->SetFont('', 'B', 9);
                
                
                // Titre
                $this->addLogo($pdf, 12);
                $pdf->SetXY($this->marge_gauche, $this->marge_haute - 17); 
                $pdf->SetFont('', 'B', 14);
                $pdf->setXY(58,10);
                
                
                    $title = "Rapport d'interventions";
                    $ref = "N° " . $propref;
                
                
                

                $tech = BimpObject::getInstance('bimpcore', 'Bimp_User', $fiche->getData('fk_user_author'));
                
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $title, 0, 'L');
                $pdf->setX(58);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, $ref, 0, 'L');
                
                $pdf->SetFont('', 'B', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, "Technicien: " . $tech->getName(), 0, 'R');
                    
                $pdf->SetTextColor(0,0,0);
                $pdf->SetFont('', 'B', 11);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, "", 0, 'C');
                
                $this->titre_partie($pdf, 'Entre les parties');
                
                // Entre les parties
                $client = new Societe($this->db);
                $client->fetch($fi->socid);
                global $mysoc;
                $pdf->setColor('fill', 255, 255, 255);
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 2;
                $pdf->SetDrawColor(236, 147, 0);
                $pdf->Cell($W, 4, $mysoc->name, "R", null, 'C', true);
                if(strlen($client->nom) >= 30 && strlen($client->nom) <= 40) { 
                    $pdf->SetFont('', 'B', 9);
                } else {
                    $pdf->SetFont('', 'B', 7);
                }
                $pdf->Cell($W, 4, $client->nom . "\n", "L", null, 'C', true);
                $pdf->SetFont('', 'B', 11);
                
                
                $pdf->SetFont('', '', 9); 
                // Si il y a un contact 'Contact client suivi contrat';
                $bimp = new BimpDb($this->db);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "", "R", null, 'C', true);
                
                $pdf->SetFont('', '', 7);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, $mysoc->address, "R", null, 'C', true);
                $pdf->Cell($W, 4, $client->address, "L", null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, $mysoc->zip . ' ' . $mysoc->town, "R", null, 'C', true);
                $pdf->Cell($W, 4, $client->zip . ' ' . $client->town, "L", null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, 'Tel: ' . $mysoc->phone, "R", null, 'C', true);
                $pdf->Cell($W, 4, "", "L", null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "Email: " . $mysoc->email, "R", null, 'C', true);
                $pdf->Cell($W, 4, "Code client : " . $client->code_client, "L", null, 'C', true);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Ln();  $pdf->Ln();

                $children = $fiche->getChildrenList('inters');
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche);
                if(count($children) > 1) {
                    $text = "Détails des interventions";
                } else {
                    $text = "Détails de l'intervention";
                }
                
                $commandes = json_decode($fiche->getData('commandes'));
                $contrat = $fiche->getData('fk_contrat');
                $tickets = json_decode($fiche->getData('tickets'));
                
                
                
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 3;
                $pdf->SetFont('', 'B', 9); 
                $comm = Array();
                $tick = Array();
                if(count($commandes) > 0) {
                    foreach($commandes as $id) {
                        $commande = BimpObject::getInstance('bimpcommercial', 'Bimp_Commande', $id);
                        $comm[] = $commande->getRef();
                    }
                }
                if(count($tickets) > 0) {
                    foreach($tickets as $id) {
                        $ticket = BimpObject::getInstance('bimpsupport', "BS_Ticket", $id);
                        $tick[] = $ticket->getRef();
                    }
                }
                $pdf->ln();
                $pdf->ln();
                $pdf->ln();
                $pdf->ln();
                $pdf->ln();
                
                $dir_output = DOL_DOCUMENT_ROOT . '/bimptechnique/views/images/';
                if(count($comm) > 0) {
                    $fileName = 'commande_fi.png';
                    $pdf->Image($dir_output . $fileName, /* x */ 38, /* y */ 80, 0, 15, '', '', '', false, 250, '');
                }
                if($fiche->getData('fk_contrat')) {
                    $fileName = 'contrat_fi.png';
                    $pdf->Image($dir_output . $fileName, /* x */ 100, /* y */ 80, 0, 15, '', '', '', false, 250, '');
                }
                if(count($tickets) > 0) {
                    $fileName = 'ticket_fi.png';
                    $pdf->Image($dir_output . $fileName, /* x */ 158, /* y */ 80, 0, 15, '', '', '', false, 250, '');
                }
                $title = '';
                if(count($comm) > 0) {
                    $title = (count($commandes) > 1) ? "Références commandes" : "Référence commande";
                }
                $pdf->Cell($W, 4, $title, 0, null, 'C', true);
                $title = '';
                if($fiche->getData('fk_contrat')) 
                    $title = "Référence contrat";
                $pdf->Cell($W, 4, $title, 0, null, 'C', true);
                $title = '';
                if(count($tickets) > 0) {
                    $title = (count($tickets) > 1) ? "Références tickets" : "Référence ticket";
                }
                $pdf->Cell($W, 4, $title, 0, null, 'C', true);
                $pdf->ln();
                $pdf->SetFont('', '', 9); 
                $refY = $pdf->getY();
                
                $pdf->SetFont('', '', 7);
                $text = '';
                if(count($comm) > 0) {
                    $text = implode(',', $comm);
                } else {
//                    $pdf->Cell($W, 4, "Il n'y à pas de commandes liées à ce rapport", 0, null, 'C', 0);
                }
                $pdf->Cell($W, 4, $text, 0, null, 'C', 0);
                $text = '';
                if($fiche->getData('fk_contrat')) {
                    $contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat', $fiche->getData('fk_contrat'));
                     $text = $contrat->getRef();
                } else {
//                    $pdf->Cell($W, 4, "Il n'y à pas de contrat lié à ce rapport", 0, null, 'C', 0);
                }
                $pdf->Cell($W, 4, $text, 0, null, 'C', 0);
                $text = '';
                if(count($tickets) > 0) {
                     $text = implode(',', $tick);
                } else {
//                    $pdf->Cell($W, 4, "Il n'y à pas de tickets support liés à ce rapport", 0, null, 'C', 0);
                }
                $pdf->Cell($W, 4, $text, 0, null, 'C', 0);
                $pdf->Ln();
                
                // Tableau des conditions du contrat
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, '', 0, 'C');
                $pdf->SetFont('', 'BU', 13);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, $text, 0, 'C');
                $pdf->SetFont('', '', 9);
                $pdf->Ln();
                
                $nb = 0;
                foreach($children as $id) {
                    $nb++;
                    $child = $fiche->getChildObject("inters", $id);
                    $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 4;
                    $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "Ligne #" . $nb, 0, 'L');
                    $pdf->SetFont(''/* 'Arial' */, '', 9);
                    $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                    $pdf->setColor('fill', 236, 147, 0);
                    $pdf->SetTextColor(255, 255, 255);
                    $pdf->setDrawColor(255, 255, 255);
                    
                    $pdf->Cell($W, 8, "Type", 1, null, 'C', true);
                    $pdf->Cell($W, 8, "Durée", 1, null, 'C', true);
                    $pdf->Cell($W, 8, "Référence", 1, null, 'C', true);
                    $pdf->Cell($W, 8, "Service", 1, null, 'C', true);
                    $pdf->Ln();
                    $pdf->setColor('fill', 255, 255, 255);
                    $pdf->SetFont(''/* 'Arial' */, '', 9);
                    $pdf->setColor('fill', 255, 255, 255);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->setDrawColor(0, 0, 0);
                    
                    
                    $pdf->setColor('fill', 242, 242, 242);
                    $pdf->setDrawColor(255, 255, 255);
                    
                    switch($child->getData('type')) {
                        case 0:
                            $type = "Intervention vendue";
                            break;
                        case 1:
                            $type = "Imponderable";
                            break;
                        case 2:
                            $type = "Explications";
                            break;
                        case 3:
                            $type = "Déplacement non vendu";
                            break;
                        case 4:
                            $type = "Intervention non prévue";
                            break;
                        case 5:
                            $type = "Déplacement sous contrat";
                            break;
                    }
                    
                   
                    $pdf->Cell($W, 6, "$type" ,1, 0, 'C', 1);
                    $pdf->Cell($W, 6, $child->displayDuree() ,1, 0, 'C', 1);
                    
                    
                    $service = BimpObject::getInstance('bimpcore', "Bimp_Product");
                    
                    if($child->getData('id_line_commande')) {
                        $obj = new OrderLine($this->db);
                        $obj->fetch($child->getData('id_line_commande'));
                        $obj_parent = new Commande($this->db);
                        $obj_parent->fetch($obj->fk_commande);
                        $pdf->Cell($W, 6, $obj_parent->ref,1, 0, 'C', 1);
                        
                        $service->fetch($obj->fk_product);
                        $pdf->Cell($W, 6, $service->getData('ref'),1, 0, 'C', 1);
                    }
                    
                    if($child->getData('id_line_contrat')) {
                        $obj = BimpObject::getInstance('bimpcontract', 'BContract_contratLine', $child->getData('id_line_contrat'));
                        $obj_parent = $obj->getParentInstance();
                        $pdf->Cell($W, 6, $obj_parent->getData('ref'),1, 0, 'C', 1);
                        $service->fetch($obj->getData('fk_product'));
                        $pdf->Cell($W, 6, $service->getData('ref'),1, 0, 'C', 1);
                    }
                    $pdf->Ln();
                    $pdf->setColor('fill', 255, 255, 255);
                    $pdf->setDrawColor(255, 255, 255);
                    $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                    if($service->isLoaded()) {
                        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "1 - Description du service " . $service->getRef(), 0, 'L');
                    } else {
                        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "1 - Description du service ", 0, 'L');
                    }
                    $pdf->SetFont(''/* 'Arial' */, '', 9);
                    $pdf->setX(20);
                    if($service->isLoaded()) {
                        $pdf->MultiCell(($this->page_largeur - $this->marge_droite - ($this->marge_gauche) + 50), 4, strip_tags($service->getData('description')), 0, 'L');
                    } else {
                        $pdf->MultiCell(($this->page_largeur - $this->marge_droite - ($this->marge_gauche) + 50), 4, $type, 0, 'L');
                    }
                    
                    $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "2 - Notes de " . $tech->getName(), 0, 'L');
                    $pdf->SetFont(''/* 'Arial' */, '', 9);
                    $pdf->setX(20);                    
                    //$str = str_replace("<br>", ", ", $child->getData('description'));
                    if($str == "<br>") {
                        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "Il n'y à pas de note supplémentaire", 0, 'L');
                    } else {
                        $pdf->writeHTML($child->getData('description'));
                        //$pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, strip_tags($str), 0, 'L');
                    }

                    $pdf->Ln();
                    $pdf->Ln();

                }
                // 297 / 4 = 74.25
                if($fiche->getData('signed') && $fiche->getData('base_64_signature')) {
                    if($pdf->GetY() > 222) {
                        $pdf->addPage();
                    }
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "Nom du signataire: " . $fiche->getData('signataire'), 0, 'L');
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "Email du signataire: " . $fiche->getData('email_signature'), 0, 'L');
                    $img_base64_encoded = $fiche->getData('base_64_signature');
                    $img = '<img src="@' . preg_replace('#^data:image/[^;]+;base64,#', '', $img_base64_encoded) . '" width="300px" >';
                    $pdf->writeHTML($img, true, false, true, false, '');
                } else {
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "Nom du signataire: " . $fiche->getData('signataire'), 0, 'L');
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "Email du signataire: " . $fiche->getData('email_signature'), 0, 'L');
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "", 0, 'L');
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "SIGNATURE CLIENT (Date et signature)", 0, 'L');
                }
                
                if (method_exists($pdf, 'AliasNbPages'))
                    $pdf->AliasNbPages();
                    //$pdf1->AliasNbPages();
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
