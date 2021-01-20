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
                
                $dir_output = $conf->bimptechnique->dir_output . '/fi/pdf/';
                $fileName = 'commande_fi.png';
                $pdf->Image($dir_output . $fileName, /* x */ 38, /* y */ 80, 0, 15, '', '', '', false, 250, '');
                $fileName = 'contrat_fi.png';
                $pdf->Image($dir_output . $fileName, /* x */ 100, /* y */ 80, 0, 15, '', '', '', false, 250, '');
                $fileName = 'ticket_fi.png';
                $pdf->Image($dir_output . $fileName, /* x */ 158, /* y */ 80, 0, 15, '', '', '', false, 250, '');
                $title = (count($commandes) > 1) ? "Références commandes" : "Référence commande";
                $pdf->Cell($W, 4, $title, 0, null, 'C', true);
                $pdf->Cell($W, 4, "Référence contrat", 0, null, 'C', true);
                $title = (count($tickets) > 1) ? "Références tickets" : "Référence ticket";
                $pdf->Cell($W, 4, $title, 0, null, 'C', true);
                $pdf->ln();
                $pdf->SetFont('', '', 9); 
                $refY = $pdf->getY();
                
                $pdf->SetFont('', '', 7); 
                if(count($comm) > 0) {
                    $pdf->Cell($W, 4, implode(',', $comm), 0, null, 'C', 0);
                } else {
                    $pdf->Cell($W, 4, "Il n'y à pas de commandes liées à ce rapport", 0, null, 'C', 0);
                }
                if($fiche->getData('fk_contrat')) {
                    $contrat = BimpObject::getInstance('bimpcontract', 'BContract_contrat', $fiche->getData('fk_contrat'));
                    $pdf->Cell($W, 4, $contrat->getRef(), 0, null, 'C', 0);
                } else {
                    $pdf->Cell($W, 4, "Il n'y à pas de contrat lié à ce rapport", 0, null, 'C', 0);
                }
                if(count($tickets) > 0) {
                    $pdf->Cell($W, 4, implode(',', $tick), 0, null, 'C', 0);
                } else {
                    $pdf->Cell($W, 4, "Il n'y à pas de tickets support liés à ce rapport", 0, null, 'C', 0);
                }
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
                            $type = "Intervention";
                            break;
                        case 1:
                            $type = "Imponderable";
                            break;
                        case 2:
                            $type = "Explications";
                            break;
                        case 3:
                            $type = "Déplacement";
                            break;
                        case 4:
                            $type = "Temps supplémentaire";
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
                    $pdf->Ln();
                    $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "1 - Description du service " . $service->getRef(), 0, 'L');
                    $pdf->SetFont(''/* 'Arial' */, '', 9);
                    $pdf->setX(20);
                    $pdf->MultiCell(($this->page_largeur - $this->marge_droite - ($this->marge_gauche) + 50), 4, $service->getData('description'), 0, 'L');
                    $pdf->SetFont(''/* 'Arial' */, 'B', 9);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "2 - Notes de " . $tech->getName(), 0, 'L');
                    $pdf->SetFont(''/* 'Arial' */, '', 9);
                    $pdf->setX(20);
                    if($child->getData('description')) {
                        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, $child->getData('description'), 0, 'L');
                    } else {
                        $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, "Il n'y à pas de note supplémentaire", 0, 'L');
                    }
                    $pdf->Ln();
                    $pdf->Ln();
                }
                
                
                
//                $img_base64_encoded = 
//                'data:image/png;base64,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'
//                ;
//                $img = '<img src="@' . preg_replace('#^data:image/[^;]+;base64,#', '', $img_base64_encoded) . '" width="300px" >';
//                $pdf->writeHTML($img, true, false, true, false, '');

                
                
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
