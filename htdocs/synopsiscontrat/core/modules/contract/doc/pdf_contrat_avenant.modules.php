<?php

require_once(DOL_DOCUMENT_ROOT . "/synopsiscontrat/core/modules/synopsiscontrat/modules_synopsiscontrat.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
require_once(DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php" );
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

if (!defined('EURO'))
    define('EURO', chr(128));

class pdf_contrat_avenant extends ModeleSynopsiscontrat {
    public $emetteur;
    var $contrat;
    var $avenant;
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
        if(is_file($logo)) {
            $pdf->Image($logo, 0, 10, 0, $size, '', '', '', false, 250, 'L');
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
    function write_file($contrat, $outputlangs = '', $srctemplatepath = "", $membre = "member", $osef = 1, $params = null) {
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
                
                $this->avenant = BimpObject::getInstance('bimpcontract', 'BContract_avenant', $contrat->pdf_avenant);
                
                $this->avenant->ref = $contrat->ref."-AV".$this->avenant->getData('number_in_contrat');
                
                $file = $dir . "/".$this->avenant->ref.".pdf";
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

                
                $client = new Societe($this->db);
                // Titre
                $this->addLogo($pdf, 12);
                
                
                
                $pdf->SetXY($this->marge_gauche, $this->marge_haute - 17);
                $pdf->SetFont('', 'B', 14);
                $pdf->setXY(58,10);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, 'Avenant N°' . $this->avenant->ref, 0, 'L');
                $pdf->setX(58);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 3, 'du contrat N°' . $contrat->ref, 0, 'L');
                $pdf->SetFont('', 'B', 8);
                $pdf->SetTextColor(0,50,255);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 1, "Exemplaire à conserver par le client", 0, 'R');
                
                $pdf->SetFont('', '', 8);
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche);
                $pdf->setTextColor(255, 255, 255);
                $pdf->setDrawColor(255, 255, 255);
                $pdf->setColor('fill', 236, 147, 0);
                $pdf->Cell($W, 8, 'Entre les parties', 1, null, 'C', true);
                $pdf->setColor('fill', 255, 255, 255);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'C');
                $pdf->setTextColor(0, 0, 0);
                $pdf->setDrawColor(0, 0, 0);
                $pdf->setColor('fill', 255, 255, 255);
                $client->fetch($contrat->socid);
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
                
                $id_type_contact = $bimp->getValue('c_type_contact', 'rowid', 'code = "CUSTOMER" AND element = "contrat"');
                $id_contact = $bimp->getValue('element_contact', 'fk_socpeople', 'element_id = ' . $contrat->id . ' AND fk_c_type_contact = ' . $id_type_contact);
                $contact = new Contact($this->db);
                $contact->fetch($id_contact);
                
                $instance_contact = BimpObject::getInstance('bimpcore', 'Bimp_Contact', $id_contact);
                
                $phone_contact = "";
                if($instance_contact->getData('phone')) $phone_contact = $instance_contact->getData('phone');
                else $phone_contact = $instance_contact->getData('phone_mobile');
                
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, '', 0, 'C');
                $pdf->Cell($W, 4, "", "R", null, 'C', true);
                $pdf->Cell($W, 4, "Contact : " . $contact->lastname . " " . $contact->firstname, "L", null, 'C', true);
                
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
                
                $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 2;
                
                BimpTools::loadDolClass('societe');
                $client = new Societe($this->db);
                $client->fetch($contrat->socid);
                $pdf->setY(75);
                $pdf->Cell($W, 4, "Article " . $num_article, "L", null, 'C', true);
                $pdf->setY(80);
                
                $lignes_avenant = $this->avenant->getChildrenListArray('avenantdet');
                $num_article = 0;
                foreach($lignes_avenant as $id => $infos) {
                    $num_article++;
                    $line = $this->avenant->getChildObject('avenantdet', $id);
                    $current_ligne++;
                    $need = 10 + 60 + ((int) count($content_service)); // En tete + Marge du bas + nombre de ligne contenu dans le service

                    $currentY = (int) $pdf->getY();
                    $hauteur = (int) $this->page_hauteur;
                    $reste = $hauteur - $currentY;

                    if ($reste < $need) {
                        //$this->_pagefoot($pdf, $outputlangs);
                        $pdf->AddPage();
                        $this->addLogo($pdf, 12);
                        $pdf->SetXY($this->marge_gauche, $this->marge_haute - 6);
                        $pdf->Line(15, 32, 195, 32);
                    }
                    
                    $W = ($this->page_largeur - $this->marge_droite - $this->marge_gauche) / 10;
                    $pdf->SetFont('', '', 10);
                    $pdf->Cell($W, 4, "Article " . $num_article, "L", null, 'C', true);
                    $pdf->Ln();
                    $pdf->SetX(20);
                    $pdf->SetFont('', '', 8);
                    
                    if($line->getData('id_line_contrat')) {
                        $contrat_line = BimpObject::getInstance('bimpcontract', 'BContract_contratLine', $line->getData('id_line_contrat'));
                        $p = BimpObject::getInstance('bimpcore', 'Bimp_Product', $contrat_line->getData('fk_product'));
                    } else {
                        $p = BimpObject::getInstance('bimpcore', 'Bimp_Product', $contrat_line->getData('id_serv'));
                    }
                    
                    $pdf->Cell($W * 2, 4, "- Service: " . $p->getData('ref'), 0, null, 'L', false);
                    $pdf->Ln();$pdf->SetX(20);
                    if($line->getData('description')) {
                        $pdf->Cell($W, 4, "- Nouvelle description du service", 0, null, 'L', false);
                        $pdf->Ln();$pdf->SetX(24);
                        $chaine_description = $line->getData('description');
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
                        $chaine_description = str_replace("<em>", '', $chaine_description);
                        $chaine_description = str_replace("</em>", '', $chaine_description);
                        $pdf->MultiCell($W * 10, 4, $chaine_description, 0, null, 'L', false);
                        $pdf->Ln();$pdf->SetX(20);
                    }
                    $old_serials = json_decode($line->getData('serials_out'));
                    if(count($old_serials)) {
                        $pdf->Cell($W*5, 4, "- Numéros de série désormais couvert par ce contrat", 0, null, 'L', false);
                        $pdf->Ln();$pdf->SetX(24);
                        $pdf->MultiCell($W * 10, 4, implode(',', json_decode($line->getData('serials_in'))) , 0, null, 'L', false);
                        $pdf->Ln();$pdf->SetX(20);
                        $pdf->Cell($W*5, 4, "- Numéros de série désormais NON couvert par ce contrat", 0, null, 'L', false);
                        $pdf->Ln();$pdf->SetX(24);
                        $pdf->MultiCell($W * 10, 4, implode(',', json_decode($line->getData('serials_out'))) , 0, null, 'L', false);
                    }
                }
                
          
                $pdf->setColor('fill', 255, 255, 255);
                $pdf->SetTextColor(200, 200, 200);
                $pdf->setY(280);
                $pdf->SetFont('', '', 7);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 4, $mysoc->name . " - ".$mysoc->address." - CS 21055 - 69760 LIMONEST | 0 812 211 211", 0, 'C');
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