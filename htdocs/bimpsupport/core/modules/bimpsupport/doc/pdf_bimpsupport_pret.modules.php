<?php

/*

  /*
 * or see http://www.gnu.org/
 */

/**
  \file       htdocs/core/modules/panier/pdf_panier_babel.modules.php
  \ingroup    panier
  \brief      Fichier de la classe permettant de generer les paniers au modele BIMP
  \author     Tommy SAURON
  \version    $Id: pdf_panier_bimp.modules.php,v 1.121 2011/08/07  $
 */
require_once(DOL_DOCUMENT_ROOT . "/bimpsupport/core/modules/bimpsupport/modules_bimpsupport.php");
require_once(DOL_DOCUMENT_ROOT . "/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';


/**
  \class      pdf_panier_babel
  \brief      Classe permettant de generer les paniers au modele babel
 */
if (!defined('EURO'))
    define('EURO', chr(128));

ini_set('max_execution_time', 600);

class pdf_bimpsupport_pret extends ModeleBimpSupport
{

    public $emetteur;    // Objet societe qui emet
    public $sav = null;

    /**
      \brief      Constructeur
      \param        db        Handler acces base de donnee
     */
    function __construct($db)
    {

        global $conf, $langs, $mysoc;

        $langs->load("main");
        $langs->load("bills");
        $this->debug = "";
        $this->db = $db;
        $this->name = "babel";
        $this->description = $langs->trans('PDFContratbabelDescription');

        // Dimension page pour format A4
        $this->type = 'pdf';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche = 18;
        $this->marge_droite = 7;
        $this->marge_haute = 39;
        $this->marge_basse = 22;

        $this->option_logo = 1;                    // Affiche logo
        // Recupere emmetteur
        $this->emetteur = $mysoc;
        if (!$this->emetteur->pays_code)
            $this->emetteur->pays_code = substr($langs->defaultlang, -2);    // Par defaut, si n'etait pas defini








            
// Defini position des colonnes
        $this->posxdesc = $this->marge_gauche + 1;
        $this->posxtva = 113;
        $this->posxup = 126;
        $this->posxqty = 145;
        $this->posxdiscount = 162;
        $this->postotalht = 174;
    }

    /**
      \brief      Fonction generant la panier sur le disque
      \param        panier            Objet panier a generer (ou id si ancienne methode)
      \param        outputlangs        Lang object for output language
      \return        int             1=ok, 0=ko
     */
    function write_file(BS_Pret $pret, $outputlangs = '')
    {
        if (!BimpObject::objectLoaded($pret)) {
            $this->errors[] = 'Prêt de matériel invalide';
            return 0;
        }
        
        $sav = $pret->getChildObject('sav');

        if (!BimpObject::objectLoaded($sav)) {
            $this->errors[] = 'SAV invalide';
            return 0;
        }

        global $user, $langs, $conf;
        global $tabCentre;

        if (!is_object($outputlangs))
            $outputlangs = $langs;
        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("bills");
        $outputlangs->load("panier");
        $outputlangs->load("products");
        //$outputlangs->setPhpLang();

        $ref = sanitize_string($sav->getData('ref'));
        $dir = DOL_DATA_ROOT . "/bimpcore/sav/" . $sav->id . '/';
//        }
        $this->sav = $sav;

        if (!file_exists($dir)) {
            if (dol_mkdir($dir) < 0) {
                $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        }

        // Definition de $dir et $file
//        if (isset($chrono->specimen) && $chrono->specimen) {
//            $dir = $conf->synopsischrono->dir_output;
//            $file = $dir . "/SPECIMEN.pdf";
//        } else {
        $file = $dir . "Pret-" . $ref . "-" . $pret->getData('ref') . ".pdf";
//        }

        if (file_exists($dir)) {
            $pdf = "";

            $pdf = pdf_getInstance($this->format);
            if (class_exists('TCPDF')) {
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
            }

            $pdf1 = pdf_getInstance($this->format);
            if (class_exists('TCPDF')) {
                $pdf1->setPrintHeader(false);
                $pdf1->setPrintFooter(false);
            }

//
//
            $pdf->SetAutoPageBreak(1, 0);
//                if (class_exists('TCPDF')) {
//                    $pdf->setPrintHeader(false);
//                    $pdf->setPrintFooter(false);
//                }
//
//

            $pdf->Open();
            $pdf1->Open();
            $pdf->AddPage();
            $pdf1->AddPage();
            $pdf1->AddFont(pdf_getPDFFont($outputlangs));
            $pdf->AddFont(pdf_getPDFFont($outputlangs));
            $pdf1->SetFont(pdf_getPDFFont($outputlangs), '', 8);

            // $pdf->SetDrawColor(128, 128, 128);



            $pdf->SetTitle('Prêt SAV : ' . $ref);

            $pdf->SetSubject($outputlangs->transnoentities("Panier"));
            $pdf->SetCreator("BIMP-ERP " . DOL_VERSION);
            $pdf->SetAuthor($user->getFullName($langs));

            $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
            $pdf1->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

            $pagecountTpl = $pdf->setSourceFile(DOL_DOCUMENT_ROOT . '/synopsischrono/core/modules/synopsischrono/doc/pret.pdf');
            $tplidx = $pdf->importPage(1, "/MediaBox");
            $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);

            $totalTtc = 0;
            $y = 98;

            $asso = new BimpAssociation($pret, 'equipments');

            $list = $asso->getAssociatesList();
            if (!count($list)) {
                $this->errors[] = 'Erreur: aucun équipement de prêt enregistré pour le prêt SAV d\'ID ' . $pret->id;
                return 0;
            }
            foreach ($list as $id_equipment) {
                $equipment = BimpCache::getBimpObjectInstance('bimpequipment', 'Equipment', (int) $id_equipment);
                if (!$equipment->isLoaded()) {
                    $this->errors[] = 'Equipement d\'ID ' . $id_equipment . ' non trouvé';
                    return 0;
                } else {
                    if ((int) $equipment->getData('id_product')) {
                        $product = $equipment->getChildObject('product');
                        if (!BimpObject::objectLoaded($product)) {
                            $this->errors[] = 'Erreur: aucun produit associé pour l\'équipement d\'ID ' . $equipment->id;
                            return 0;
                        } else {
                            $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
                            $pdf->SetXY(5, $y);
                            $pdf->MultiCell(28, 6, $product->ref, 0, 'C');
                            $pdf->SetXY(35, $y);
                            $pdf->MultiCell(60, 6, $product->label . " (" . $equipment->getData('serial') . ")", 0, 'C');
                            $pdf->SetXY(97, $y);
                            $pdf->MultiCell(33, 6, price($product->price), 0, 'C');
                            $pdf->SetXY(127, $y);
                            $pdf->MultiCell(15, 6, 1, 0, 'C');
                            $pdf->SetXY(143, $y);
                            $pdf->MultiCell(25, 6, price($product->price), 0, 'C');
                            $pdf->SetXY(169, $y);
                            $pdf->MultiCell(35, 6, price($product->price_ttc), 0, 'C');
                            $totalTtc += $product->price_ttc;
                            $y += 8;
                        }
                    } else {
                        $price_ttc = (float) $equipment->getData('prix_vente_except');
                        if (!$price_ttc) {
                            $this->errors[] = 'Erreur: aucun prix de vente exceptionnel enregistré pour l\'équipement "' . $equipment->getRef() . '"';
                            return 0;
                        }
                        $price_ht = $price_ttc / 1.2;

                        $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
                        $pdf->SetXY(5, $y);
                        $pdf->MultiCell(28, 6, $equipment->getData('serial'), 0, 'C');
                        $pdf->SetXY(35, $y);
                        $pdf->MultiCell(60, 6, $equipment->getData('product_label'), 0, 'C');
                        $pdf->SetXY(97, $y);
                        $pdf->MultiCell(33, 6, price($price_ht), 0, 'C');
                        $pdf->SetXY(127, $y);
                        $pdf->MultiCell(15, 6, 1, 0, 'C');
                        $pdf->SetXY(143, $y);
                        $pdf->MultiCell(25, 6, price($price_ht), 0, 'C');
                        $pdf->SetXY(169, $y);
                        $pdf->MultiCell(35, 6, price($price_ttc), 0, 'C');
                        $totalTtc += $price_ttc;
                        $y += 8;
                    }
                }
            }

            $pdf->SetXY('47', '34');
            $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 14);
            $pdf->MultiCell(100, 6, $ref, 0, 'L');

            //centre
            $code_centre = (string) $sav->getData('code_centre');

            if ($code_centre && isset($tabCentre[$code_centre])) {
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
                $pdf->SetXY('147', '39.5');
                $pdf->MultiCell(100, 6, $tabCentre[$code_centre][2], 0, 'L');
                $pdf->SetXY('147', '45.5');
                $pdf->MultiCell(100, 6, $tabCentre[$code_centre][0], 0, 'L');
                $pdf->SetXY('147', '51.3');
                $pdf->MultiCell(100, 6, $tabCentre[$code_centre][1], 0, 'L');
            }

            //client
            $contact = "";
            $addr = null;
            $tel = '';
            $mail = '';
            if ($sav->getData('id_contact')) {
                $contact_obj = $sav->getChildObject('contact');
                if (!is_null($contact_obj) && $contact_obj->isLoaded()) {
                    $addr = $contact_obj->dol_object;
                    $contact = $addr->getFullName($langs, 0, 0);
                    $tel = ($addr->phone_mobile != "") ? $addr->phone_mobile : ($addr->phone_perso != "") ? $addr->phone_perso : ($addr->phone_pro != "") ? $addr->phone_pro : "";
                    $mail = $addr->mail;
                }
            }

            $client = $sav->getChildObject('client');

            if (!is_null($client) && $client->isLoaded()) {
                if (is_null($addr)) {
                    $addr = $client->dol_object;
                    if (!$tel) {
                        $tel = $addr->phone;
                    }
                    if (!$mail) {
                        $mail = $addr->email;
                    }
                }
                $address = $client->dol_object->name;

                if ($contact != "" && $contact != $client->dol_object->name) {
                    $address .= "\n" . $contact;
                }

                $address .= "\n" . $client->dol_object->address . "\n" . $client->dol_object->zip . " " . $client->dol_object->town;
            }

            $pdf->SetXY('20', '61');
            $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
            $pdf->MultiCell(300, 6, $address . "\n" . ($tel ? $tel . ' - ' : '') . $mail, 0, 'L');

            $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
            $tabT = explode(" ", $pret->getData('date_begin'));
            $datetime1 = new DateTime($tabT[0]);
            $datetime2 = new DateTime($pret->getData('date_end'));
            $interval = $datetime1->diff($datetime2);

            $pdf->SetXY('32', '42.6');
            $pdf->MultiCell(50, 6, $interval->format('%a') . " jours", 0, 'L');

            $pdf->SetXY('32', '46.7');
            $pdf->MultiCell(50, 6, $pret->displayData('date_begin', 'default', false, true), 0, 'L');

            $pdf->SetXY('32', '50.4');
            $pdf->MultiCell(50, 6, $pret->displayData('date_end', 'default', false, true), 0, 'L');

            $user_create = $sav->getChildObject('user_create');

            if (!is_null($user_create) && (int) $user_create->id) {
                $pdf->SetXY('57', '55.7');
                $pdf->MultiCell(100, 6, $user_create->getFullName($langs), 0, 'L');
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 10);
                $pdf->SetXY('168', '64');
                $pdf->MultiCell(100, 6, $user_create->getFullName($langs), 0, 'L');
            }

//            if ((string) $sav->getData('prestataire_number')) {
//                $pdf->SetXY(12, 45);
//                $pdf->MultiCell(100, 6, "N° de dossier prestataire : " . $sav->getData('prestataire_number'), 0, 'L');
//            }

            $pdf->SetXY(69, 230);
            $pdf->MultiCell(35, 6, price($totalTtc) . "", 0, 'C');

            if (is_object($client->dol_object)) {
                $pdf->SetXY(25, 188.8);
                $pdf->MultiCell(45, 4, $client->dol_object->getFullName($outputlangs), 0, 'C');
            }


//                //le prod
//                $pdf->SetXY('121', '71.4');
//                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
//                $pdf->MultiCell(100, 6, $chrono2->description, 0, 'L');
//
//                $pdf->SetXY('137', '75.1');
//                $pdf->MultiCell(100, 6, $chrono2->valuesPlus[1011]->value, 0, 'L');
//
//                $pdf->SetXY('137', '79.4');
//                $pdf->MultiCell(100, 6, $chrono2->valuesPlus[1064]->value, 0, 'L');
//
//                $pdf->SetXY('145', '84');
//                $pdf->MultiCell(100, 6, $chrono2->valuesPlus[1015]->value, 0, 'L');
//
//                $pdf->SetXY('131.5', '88.2');
//                $pdf->MultiCell(100, 6, $chrono->valuesPlus[1040]->valueStr . "    " . $chrono->description, 0, 'L');
//
//                $pdf->SetXY('143', '104.5');
//                $pdf->MultiCell(100, 6, $chrono->valuesPlus[1041]->valueStr, 0, 'L');
//
//                $pdf->SetXY(130, 109);
//                $pdf->MultiCell(80, 6, $chrono2->valuesPlus[1067]->valueStr, 0, '');
//
//                //symptom et sauv
//                $pdf->SetXY('15', '138.5');
//                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
//                $pdf->MultiCell(170, 6, $chrono->valuesPlus[1047]->valueStr, 0, 'L');
//
//                if ($chrono->valuesPlus[1055]->value == 2)
//                    $pdf->SetTextColor(256, 0, 0);
//
//                $pdf->SetXY('28.5', '160.3');
//                $pdf->MultiCell(100, 6, $chrono->valuesPlus[1055]->valueStr, 0, 'L');
//
//                $cgv = "";
//                $cgv.= "-La société BIMP ne peut pas être tenue responsable de la perte éventuelle de données, quelque soit le support.\n\n";
//
//                if (stripos($chrono2->description, "Iphone") !== false) {
//                    $cgv .= "-Les frais de prise en charge diagnostic de 29€ TTC sont à régler à la dépose de votre materiel hors garantie. En cas d'acceptation du devis ces frais seront déduits.\n\n";
//                    $cgv.="-Les problèmes logiciels, la récupération de données ou la réparation materiel liées à une mauvaise utilisation (liquide, chute, etc...), ne sont pas couverts parla GARANTIE APPLE. Un devis sera alors établi et des frais de 29€ TTC seront alors facturés en cas de refus de celui-ci." . "\n\n";
//                    $cgv.="-Des frais de 29€ TTC seront automatiquement facturés, si lors de l'expertise il s'avère que des pièces de contre façon ont été installées.\n\n";
//                } else {
//                    $cgv .= "-Les problèmes logiciels, la récupératon de données ou la réparation matériel liée à une mauvaise utilisation (liquide, chute,etc...), ne sont pas couverts par la GARANTIE APPLE.\n\n";
//                    $cgv.="-Les frais de prise en charge diagnostic de 45€ TTC sont à régler à la dépose de votre materiel hors garantie. En cas d'acceptation du devis ces frais seront déduits.\n\n";
//                }
////                $pdf->SetX(6);
////                $pdf->MultiCell(145, 6, $cgv, 0, 'L');
////                $pdf->SetX(6);
//                $cgv.= "-Le client s'engage à venir récupérer son bien dans un délai d'un mois après mise à disposition, émission d'un devis. Après expiration de ce délai, ce dernier accepte des frais de garde de 0.75€ par jour.\n\n";
//
//                if ($chrono->valuesPlus[1068]->value == 1) {
//                    $pdf->SetXY('62', '115');
//                    $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 20);
//                    $pdf->SetTextColor(255, 102, 0);
//                    $pdf->MultiCell(100, 6, "Prise en charge urgente", 0, 'L');
//                    $cgv .= "-J'accepte les frais de 96 TTC de prise en charge urgente";
//                }
//
//
//                $pdf->SetTextColor("black");
////                $pdf->SetXY('6', '245');
//                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
//                $pdf->SetXY('7', '197');
//                $pdf->MultiCell(145, 6, $cgv, 0, 'L');
//
////                //info pour prise en charge
////                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
////                $pdf->SetTextColor(0,0,0);
////                $pdf->SetXY(25, 257);
////                $pdf->MultiCell(90, 6, "Login : ".$chrono2->valuesPlus[1063]->valueStr, 0, '');
////                $pdf->SetXY(25, 262);
////                $pdf->MultiCell(90, 6, "Mdp : ".$chrono2->valuesPlus[1057]->valueStr, 0, '');
////                $pdf->SetXY(100, 260);
////                $pdf->MultiCell(90, 6, "Systéme : ".$chrono2->valuesPlus[1067]->valueStr, 0, '');
//                //etiquette ref
//                for ($i = 0; $i < 5; $i++) {
//                    $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 11);
//                    $pdf->SetTextColor(256, 0, 0);
//                    $x = ('8' + ($i * 38.8));
//                    $pdf->SetXY($x, '269.9');
//                    $pdf->MultiCell(38, 6, $chrono->ref, 0, 'C');
//                    $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 10);
//                    $pdf->SetTextColor("black");
//                    $pdf->SetXY($x, '278');
//                    $pdf->MultiCell(38, 6, $chrono->societe->nom, 0, 'C');
//                }
//                $pdf->MultiCell(30, 6, $chrono->ref, 0, 'L');
//                for($i=0;$i<1000;$i = $i+5){
//                $pdf->SetXY($i,$i);
//                $pdf->MultiCell(155, 6, $i, 0, 'L');
//                
//                }

            if (method_exists($pdf, 'AliasNbPages'))
                $pdf->AliasNbPages();
            $pdf->Close();

            $this->file = $file;
            $pdf->Output($file, 'F');
//
////                ////$langs->setPhpLang();    // On restaure langue session

            return 1;   // Pas d'erreur
        } else {
            $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
            ////$langs->setPhpLang();    // On restaure langue session
            return 0;
        }
//            $this->error = $langs->trans("ErrorConstantNotDefined", "CONTRACT_OUTPUTDIR");
//            ////$langs->setPhpLang();    // On restaure langue session
//            return 0;

        $this->error = $langs->trans("ErrorUnknown");
        ////$langs->setPhpLang();    // On restaure langue session
        return 0;   // Erreur par defaut
    }

    function _pagehead(&$pdf, $object, $showadress = 1, $outputlangs = '', $currentPage = 0)
    {
        $sav = null;
        if (BimpObject::objectLoaded($object) && is_a($object, 'BS_Pret')) {
            $sav = $object->getChildObject('sav');
        }


        if (!BimpObject::objectLoaded($sav)) {
            $this->errors[] = 'SAV absent ou invalide';
            return;
        }


        global $conf, $langs;
        if ($currentPage > 1) {
            $showadress = 0;
        }

        $outputlangs->load("main");
        $outputlangs->load("bills");
        $outputlangs->load("propal");
        $outputlangs->load("companies");

        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 13);

        $posy = $this->marge_haute;

        $pdf->SetXY(5, 13);

        // Logo
        $logo = false;
        if (is_file($conf->mycompany->dir_output . '/logos' . '/' . $this->emetteur->logo . "noalpha.png")) {
            $logo = $conf->mycompany->dir_output . '/logos' . '/' . $this->emetteur->logo . "noalpha.png";
        } else {
            $logo = $conf->mycompany->dir_output . '/logos' . '/' . $this->emetteur->logo;
        }

//        $logo = $conf->mycompany->dir_output .'/logos' . '/' . $this->emetteur->logo;
        if ($this->emetteur->logo) {
            if (is_readable($logo)) {
                $pdf->Image($logo, 5, 13, 0, 24);
            } else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 8);
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
                $pdf->SetTextColor(0, 0, 0);
            }
        } else if (defined("FAC_PDF_INTITULE")) {
            $pdf->MultiCell(100, 4, FAC_PDF_INTITULE, 0, 'L');
        }

        $showaddress = $showadress;
        $usecontact = (int) $sav->getData('id_contact');
        $client = $sav->getChildObject('client');
        if (BimpObject::objectLoaded($client)) {
            $client = $client->dol_object;
        } else {
            $this->errors[] = 'Erreur: Client Absent';
            return;
        }

        $default_font_size = 12;
        $pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $default_font_size);

        $posx = 100;
        $posy = 10;
        $posy+=5;
        $largCadre = 206 - $this->marge_gauche;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref") . " : " . $outputlangs->convToOutputCharset($sav->getData('ref')), '', 'R');

        $posy+=1;
        $pdf->SetFont(pdf_getPDFFont($outputlangs), '', $default_font_size - 2);
        $pdf->SetTextColor(0, 0, 60);


        if (!is_null($client) && $client->code_client) {
            $posy+=5;
            $pdf->SetXY($posx, $posy);
            $pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode") . " : " . $outputlangs->transnoentities($client->code_client), '', 'R');
        }

        $posy+=5;
        $pdf->SetXY($posx, $posy);
        $pdf->MultiCell(100, 3, $outputlangs->transnoentities("Type ") . " : " . $outputlangs->transnoentities($sav->getLabel()), '', 'R');

        if ($showadress) {

            // Sender properties
            $carac_emetteur = pdf_build_address($outputlangs, $this->emetteur);

            // Show sender
            $posy = 42;
            $posx = 15;
            if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT))
                $posx = $this->page_largeur - $this->marge_droite - 80;
            $hautcadre = 45;

            // Show sender frame
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont(pdf_getPDFFont($outputlangs), '', $default_font_size - 2);
            $pdf->SetXY($posx, $posy - 5);
            $pdf->MultiCell(66, 5, $outputlangs->transnoentities("BillFrom") . ":", 0, 'L');
            $pdf->SetXY($posx, $posy);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);
            $pdf->SetTextColor(0, 0, 60);

            // Show sender name
            $pdf->SetXY($posx + 2, $posy + 3);
            $pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $default_font_size);
            $pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
            $posy = $pdf->getY();

            // Show sender information
            $pdf->SetXY($posx + 2, $posy);
            $pdf->SetFont(pdf_getPDFFont($outputlangs), '', $default_font_size - 1);
            $pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');

            // Recipient name
            if (!empty($usecontact)) {
                $contact = $sav->getChildObject('contact');
                // On peut utiliser le nom de la societe du contact
                if (!empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT))
                    $socname = $contact->dol_object->socname;
                else
                    $socname = $client->nom;
                $carac_client_name = $outputlangs->convToOutputCharset($socname);
                $carac_client = pdf_build_address($outputlangs, $this->emetteur, $client, $contact->dol_object, 1, 'target');
            }
            else {
                $carac_client_name = $outputlangs->convToOutputCharset($client->nom);
                $carac_client = pdf_build_address($outputlangs, $this->emetteur, $client, '', 0, 'target');
            }

            // Show recipient
            $widthrecbox = 100;
            if ($this->page_largeur < 210)
                $widthrecbox = 84; // To work with US executive format
            $posy = 42;
            $posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
            if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT))
                $posx = $this->marge_gauche;

            // Show recipient frame
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont(pdf_getPDFFont($outputlangs), '', $default_font_size - 2);
            $pdf->SetXY($posx + 2, $posy - 5);
            $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo") . ":", 0, 'L');
            $pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

            // Show recipient name
            $pdf->SetXY($posx + 2, $posy + 3);
            $pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $default_font_size);
            $pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, 'L');

            // Show recipient information
            $pdf->SetFont(pdf_getPDFFont($outputlangs), '', $default_font_size - 1);
            $pdf->SetXY($posx + 2, $posy + 4 + (dol_nboflines_bis($carac_client_name, 50) * 4));
            $pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');



            $pdf->Rect($this->marge_gauche - 3, 89, $largCadre, 185);
            $pdf->SetXY($this->marge_gauche, 92);
        }
        else {
            $pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 10);
            //Société
            if ($this->marge_gauche > 45) {
                $pdf->SetXY(3.5, 63);
                $pdf->MultiCell(39, 4, "Code Client : " . $client->code_client, 0, "L");
                $pdf->SetXY(3.5, 54.5);
                $pdf->MultiCell(39, 4, 'Client : ' . $client->getFullName($outputlangs), 0, 'L');
            }

            $pdf->Rect($this->marge_gauche - 3, 39, $largCadre, 235);
            $pdf->SetXY($this->marge_gauche, 42);
        }



        $pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 12);
    }
    /*
     *   \brief      Affiche le pied de page
     *   \param      pdf     objet PDF
     */

    function _pagefoot(&$pdf, $sav, $outputlangs)
    {


        $pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 9);
        $pdf->SetTextColor(255, 63, 50);
        $pdf->SetDrawColor(0, 0, 0);
        //Société
        global $mysoc;

        $Y = 235;
        if ($this->marge_gauche > 45) {
            $pdf->SetXY(3.5, $Y + 20);
            $pdf->MultiCell(39, 4, utf8_encodeRien($mysoc->address), 0, "L");
            $pdf->SetXY(3.5, $Y + 25);
            $pdf->MultiCell(39, 4, utf8_encodeRien($mysoc->zip . " " . $mysoc->town), 0, "L");
            $pdf->SetXY(3.5, $Y + 30);
            if ($mysoc->phone != "")
                $pdf->MultiCell(39, 4, utf8_encodeRien("Tél. : " . $mysoc->phone), 0, "L");
            $pdf->SetXY(3.5, $Y + 35);
            if ($mysoc->fax != "")
                $pdf->MultiCell(39, 4, "Fax  : " . $mysoc->fax, 0, "L");
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);
        $ligne = $mysoc->name;
        if (defined('MAIN_INFO_CAPITAL'))
            $ligne .= " au capital de " . MAIN_INFO_CAPITAL;
        if (defined('MAIN_INFO_RCS'))
            $ligne .= " - R.C.S. " . MAIN_INFO_RCS;
        elseif (defined('MAIN_INFO_SIREN'))
            $ligne .= " - R.C.S. " . MAIN_INFO_SIREN;
        if (defined('MAIN_INFO_APE'))
            $ligne .= " - APE " . MAIN_INFO_APE;
        if (defined('MAIN_INFO_TVAINTRA'))
            $ligne .= " - TVA/CEE " . MAIN_INFO_TVAINTRA;
        $ligne .= "\n\n" . "Document généré par BIMP-ERP Copyright © Synopsis & DRSI";

//        $ligne = "SA OLYS au capital de 85 372" . EURO . "    -   320 387 483 R.C.S. Lyon   -   APE 4741Z   -   TVA/CEE FR 34 320387483";
//        $ligne .= "\n" . "RIB : BPLL  -  13907. 00000.00202704667.45  -  CCP 11 158 41U Lyon";

        $pdf->SetXY($this->marge_gauche, $Y + 50);
        $pdf->MultiCell(200 - $this->marge_gauche, 3, $ligne, 0, "C");
        $pdf->line($this->marge_gauche - 4, $Y + 44, 203, $Y + 44);

        $pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 8);
        $pdf->SetTextColor(255, 63, 50);
        $pdf->SetXY(192, $Y + 55);
        $pdf->MultiCell(19, 3, '' . $pdf->PageNo() . '/{:ptp:}', 0, 'R', 0);

        //return pdf_pagefoot($pdf, $chrono,$outputlangs,'CONTRAT_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche + 40,$this->page_hauteur);
    }

    function hex2RGB($hexStr, $returnAsString = false, $seperator = ',')
    {
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
