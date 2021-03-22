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

class pdf_bimpsupport_destruction extends ModeleBimpSupport {

    public $emetteur;    // Objet societe qui emet

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
    function write_file($sav, $outputlangs = '') {
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
        if ($conf->bimpcore->dir_output) {
            // Definition de $dir et $file
            if (isset($sav->specimen) && $sav->specimen) {
                $dir = $conf->bimpcore->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $propref = sanitize_string($sav->getData('ref'));
                $dir = $conf->bimpcore->dir_output . "/sav/" . $sav->id;
                $file = $dir . "/Destruction-" . $propref . ".pdf";
            }

            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

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



                $pdf->SetTitle("SAV" . ' : ' . $sav->getData('ref'));

                $pdf->SetSubject($outputlangs->transnoentities("Panier"));
                $pdf->SetCreator("BIMP-ERP " . DOL_VERSION);
                $pdf->SetAuthor($user->getFullName($langs));
//
                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf1->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
// 
//                



                $pagecountTpl = $pdf->setSourceFile(DOL_DOCUMENT_ROOT . '/bimpsupport/core/modules/bimpsupport/doc/destruction.pdf');
                $tplidx = $pdf->importPage(1, "/MediaBox");
                $pdf->useTemplate($tplidx, 0, 0, 0, 0, true);


                $equipment = $sav->getChildObject('equipment');




                $pdf->SetXY('61', '35.6');
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 14);
                $pdf->MultiCell(100, 6, $sav->getData('ref'), 0, 'L');


                
                $code_entrepot = $sav->getData('code_centre');

                //centre
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
                $pdf->SetXY('142', '40');
                $pdf->MultiCell(100, 6, $tabCentre[$code_entrepot][2], 0, 'L');
                $pdf->SetXY('142', '45.5');
                $pdf->MultiCell(100, 6, $tabCentre[$code_entrepot][0], 0, 'L');
                $pdf->SetXY('142', '51');
                $pdf->MultiCell(100, 6, $tabCentre[$code_entrepot][1], 0, 'L');
//                $tabCentre
                //client
                $contact = "";
                $client = $sav->getChildObject('client')->dol_object;
                if ($sav->getData('id_contact') > 0) {
                    $addr = $sav->getChildObject('contact')->dol_object;
                    $contact = $addr->getFullName($langs, 0, 0);
                    $tel = ($addr->phone_mobile != "") ? $addr->phone_mobile : ($addr->phone_perso != "") ? $addr->phone_perso : ($addr->phone_pro != "") ? $addr->phone_pro : "";
                    $mail = $addr->mail;
                } else {
                    $addr = $client;
                    $tel = $addr->phone;
                    $mail = $addr->email;
                }
                $address = $client->name;

                if ($contact != "" && $contact != $client->name)
                    $address .= "\n" . $contact;

                $address .= "\n" . $client->address . "\n" . $client->zip . " " . $client->town;

                $pdf->SetXY('20', '71');
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
                $pdf->MultiCell(300, 6, $address . "\n" . $tel . "\n" . $mail, 0, 'L');




                $pdf->SetXY('12', '48');
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
                $pdf->MultiCell(50, 6, dol_print_date($sav->getData('date_create')), 0, 'L');

                $user_create = $sav->getChildObject('user_create');
                if (BimpObject::objectLoaded($user_create)) {
                    $pdf->SetXY('36', '48');
                    $pdf->MultiCell(100, 6, $user_create->getName(), 0, 'L');
                }



                $product_label = $equipment->displayProduct('nom', true);
                //le prod
                $pdf->SetXY('115', '88');
                $pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
                $pdf->MultiCell(100, 6, $product_label, 0, 'L');
//
                $pdf->SetXY('125', '95');
                $pdf->MultiCell(100, 6, $equipment->getData('serial'), 0, 'L');
                
                
                
                $pdf->SetXY('32', '148.3');
                $pdf->MultiCell(100, 6, $contact, 0, 'L');
                    
        



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
        } else {
            $this->error = $langs->trans("ErrorConstantNotDefined", "CONTRACT_OUTPUTDIR");
            ////$langs->setPhpLang();    // On restaure langue session
            return 0;
        }

        $this->error = $langs->trans("ErrorUnknown");
        ////$langs->setPhpLang();    // On restaure langue session
        return 0;   // Erreur par defaut
    }

    function _pagehead(& $pdf, $sav, $showadress = 1, $outputlangs, $currentPage = 0) {
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
        $usecontact = ($object->model->hasContact && $object->contactid > 0);
        $object->client = $object->societe;
        $default_font_size = 12;



        $pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $default_font_size);

        $posx = 100;
        $posy = 10;
        $posy+=5;
        $largCadre = 206 - $this->marge_gauche;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref") . " : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

        $posy+=1;
        $pdf->SetFont(pdf_getPDFFont($outputlangs), '', $default_font_size - 2);
        $pdf->SetTextColor(0, 0, 60);


        if ($object->client->code_client) {
            $posy+=5;
            $pdf->SetXY($posx, $posy);
            $pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode") . " : " . $outputlangs->transnoentities($object->client->code_client), '', 'R');
        }

        $posy+=5;
        $pdf->SetXY($posx, $posy);
        $pdf->MultiCell(100, 3, $outputlangs->transnoentities("Type ") . " : " . $outputlangs->transnoentities($object->model->titre), '', 'R');



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
                // On peut utiliser le nom de la societe du contact
                if (!empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT))
                    $socname = $object->contact->socname;
                else
                    $socname = $object->client->nom;
                $carac_client_name = $outputlangs->convToOutputCharset($socname);
            }
            else {
                $carac_client_name = $outputlangs->convToOutputCharset($object->client->nom);
            }

            $carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->client, ($usecontact ? $object->contact : ''), $usecontact, 'target');

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
                $pdf->MultiCell(39, 4, "Code Client : " . $object->societe->code_client, 0, "L");
                $pdf->SetXY(3.5, 54.5);
                $pdf->MultiCell(39, 4, 'Client : ' . $object->societe->getFullName($outputlangs), 0, 'L');
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

    function _pagefoot(&$pdf, $sav, $outputlangs) {


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
