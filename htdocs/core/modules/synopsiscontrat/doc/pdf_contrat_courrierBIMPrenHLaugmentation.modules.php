<?php
/*
  *
  */
 /**
  *
  * Name : pdf_contratGMAO_courrierBIMPrenHLaugmentation.modules.php
  */

require_once(DOL_DOCUMENT_ROOT."/core/modules/synopsiscontrat/modules_synopsiscontrat.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

//TODO  addresse livraison lié au contrat
//TODO filtre sur statuts ???

/**
 \class      pdf_contrat_babel
 \brief      Classe permettant de generer les contrats au modele babel
 */

if(!defined('EURO'))
    define ('EURO', chr(128) );

class pdf_contrat_courrierBIMPrenHLaugmentation extends ModeleSynopsiscontrat
{
    public $emetteur;    // Objet societe qui emet


    /**
    \brief      Constructeur
    \param        db        Handler acces base de donnee
    */
    function pdf_contrat_courrierBIMPrenHLaugmentation($db)
    {

        global $conf,$langs,$mysoc;

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
        $this->format = array($this->page_largeur,$this->page_hauteur);
        $this->marge_gauche=25;
        $this->marge_droite=15;
        $this->marge_haute=40;
        $this->marge_basse=25;

        $this->option_logo = 0;                    // Affiche logo

        // Recupere emmetteur
        $this->emetteur=$mysoc;
        if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'etait pas defini


    }

    /**
    \brief      Fonction generant la contrat sur le disque
    \param        contrat            Objet contrat a generer (ou id si ancienne methode)
        \param        outputlangs        Lang object for output language
        \return        int             1=ok, 0=ko
        */
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
//        $outputlangs->setPhpLang();
        if ($conf->synopsiscontrat->dir_output)
        {
            // Definition de l'objet $contrat (pour compatibilite ascendante)
            if (! is_object($contrat))
            {
                $id = $contrat;
                require_once(DOL_DOCUMENT_ROOT."/Synopsis_Contrat/class/contrat.class.php");
                $contrat=getContratObj($id);
                $contrat->fetch($id);
                $contrat->fetch_lines(true);
//                $contrat = new ContratMixte($this->db);
//                $ret=$contrat->fetch($id);
            } else {
                $contrat->fetch_lignes(true);
            }

            // Definition de $dir et $file
            if ($contrat->specimen)
            {
                $dir = $conf->synopsiscontrat->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $propref = sanitize_string($contrat->ref);
                $dir = $conf->synopsiscontrat->dir_output . "/" . $propref;
                $file = $dir ."/Courrier_renHLaugmentation_".date("d_m_Y")."_" . $propref . ".pdf";
            }
            $this->contrat = $contrat;

            if (! file_exists($dir))
            {
                if (mkdir($dir) < 0)
                {
                    $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                    return 0;
                }
            }

            if (file_exists($dir))
            {
                $pdf="";
                $nblignes = sizeof($contrat->lignes);
                
                $pdf = pdf_getInstance($this->format);
                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                
                $pdf1 = pdf_getInstance($this->format);
                if (class_exists('TCPDF'))
                {
                    $pdf1->setPrintHeader(false);
                    $pdf1->setPrintFooter(false);
                }

                $pdf->Open();
                $pdf1->Open();
                $pdf->AddPage();
                $pdf1->AddPage();
                $pdf1->SetFont(/*'Arial'*/'', '', 8);

                $pdf->SetDrawColor(128,128,128);


                $pdf->SetTitle($contrat->ref);
                $pdf->SetSubject($outputlangs->transnoentities("Contract"));
                $pdf->SetCreator("GLE ".GLE_VERSION);
                $pdf->SetAuthor($user->fullname);

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf1->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(0,0);

//                $pdf->AddFont('VeraMoBI', 'BI', 'VeraMoBI.php');
//                $pdf->AddFont('fq-logo', 'Roman', 'fq-logo.php');

                // Tete de page
                $this->_pagehead($pdf, $contrat, 1, $outputlangs);
                $pdf->SetFont(/*'Arial'*/'', 'B', 12);

//Encart societe
                $pdf->SetXY($this->marge_gauche + 100,$this->marge_haute);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 100) ,6,($contrat->societe->titre."x" != "x"?$contrat->societe->titre." ":"").$contrat->societe->nom,0,'L');
                $pdf->SetFont(/*'Arial'*/'', '', 11);
                $pdf->SetX($this->marge_gauche + 100);

//representant légal : signataire contrat
                $requete = "SELECT fk_socpeople
                              FROM llx_c_type_contact as c,
                                   llx_element_contact as e
                             WHERE c.element = 'contrat'
                               AND c.source = 'external'
                               AND c.code = 'SALESREPSIGN'
                               AND e.statut = 4
                               AND e.fk_c_type_contact = c.rowid
                               AND e.element_id = ".$contrat->id;
                $sql = $this->db->query($requete);
                $contact = "";
                if ($sql && $this->db->num_rows($sql) > 0)
                {
                    $res = $this->db->fetch_object($sql);
                    require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
                    $tmpcontact = new Contact($this->db);
                    $tmpcontact->fetch($res->fk_socpeople);
                    $contact = $tmpcontact->lastname." " .$tmpcontact->firstname;
                }
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 100),6,$contact,0,'L');
                $pdf->SetX($this->marge_gauche + 100);
//addresse :> add de la société
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 100),6,$contrat->societe->address." \n ".$contrat->societe->zip.$contrat->societe->town." ",0,'L');

//Date
                $pdf->SetFont(/*'Arial'*/'', '', 10);
                $pdf->SetXY($this->marge_gauche + 100,$this->marge_haute + 44);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 50) ,6,"Lyon, le ".date("d/m/Y"),0,'L');

//Objet
                $pdf->SetFont(/*'Arial'*/'', 'U', 10);
                $pdf->SetXY($this->marge_gauche,$this->marge_haute + 60);
                $pdf->MultiCell(14 ,4,"Objet : ",0,'L');
                $pdf->SetFont(/*'Arial'*/'', '', 10);
                $pdf->SetXY($this->marge_gauche + 14,$this->marge_haute + 60);
                $pdf->MultiCell($this->page_largeur-($this->marge_droite + $this->marge_gauche + 14) ,4,utf8_decode("Renouvellement de votre contrat ".$contrat->ref),0,'L');
                $remY = $pdf->GetY();
                $pdf->SetFont(/*'Arial'*/'', 'U', 10);
                $pdf->SetXY($this->marge_gauche,$remY);
                $pdf->MultiCell(23 ,4,"Code Client : ",0,'L');
                $pdf->SetFont(/*'Arial'*/'', '', 10);
                $pdf->SetXY($this->marge_gauche + 23,$remY);
                $pdf->MultiCell($this->page_largeur-($this->marge_droite + $this->marge_gauche + 23) ,4,utf8_decode($contrat->societe->code_client),0,'L');

//Madame, Monsieur
                $pdf->SetXY($this->marge_gauche,$this->marge_haute + 90);
                $pdf->MultiCell($this->page_largeur-($this->marge_droite + $this->marge_gauche + 20) ,4,utf8_decode("Madame, Monsieur,"),0,'L');

                $pdf->SetXY($this->marge_gauche,$this->marge_haute + 100);
                $pdf->MultiCell($this->page_largeur-($this->marge_droite + $this->marge_gauche + 20) ,4,utf8_decode("Le contrat N° ".$contrat->ref." que vous avez souscrit (ou un des éléments qui le constitue) arrive à échéance").".",0,'L');
                
                $pdf->SetXY($this->marge_gauche,$this->marge_haute + 112);
                $pdf->MultiCell($this->page_largeur-($this->marge_droite + $this->marge_gauche + 20) ,4,utf8_decode("Au 1er octobre 2011, la tarification en vigueur pour la reconduction de votre contrat sera
de 200,00 EUROS HT."),0,'L');
                
                $pdf->SetXY($this->marge_gauche,$this->marge_haute + 124);
                $pdf->MultiCell($this->page_largeur-($this->marge_droite + $this->marge_gauche + 20) ,4,utf8_decode("Sans dénonciation de votre part sous dix jours, nous le renouvellerons pour
une durée d'un an."),0,'L');

                $pdf->SetXY($this->marge_gauche,$pdf->GetY()+6);
                $pdf->MultiCell($this->page_largeur-($this->marge_droite + $this->marge_gauche + 20) ,4,utf8_decode("Vous recevrez alors la facture correspondante. En cas de références particulières (bon de commande officiel, N° interne, adresse spécifique de facturation, etc.) à notifier sur celle-ci, merci de nous les transmettre avant l'échéance de votre contrat afin que celles-ci soient prises en compte."),0,'L');

                $pdf->SetXY($this->marge_gauche,$pdf->GetY()+6);
                $pdf->MultiCell($this->page_largeur-($this->marge_droite + $this->marge_gauche + 20) ,4,utf8_decode("Conformément à l'autorisation de prélèvement annexée à votre contrat, votre débit s'effectuera environ une semaine après la date de facture.
Merci de vérifier si vos coordonnées bancaires n'ont pas changé."),0,'L');

                $pdf->SetXY($this->marge_gauche,$pdf->GetY()+6);
                $pdf->MultiCell($this->page_largeur-($this->marge_droite + $this->marge_gauche + 20) ,4,utf8_decode("Ce contrat sera totalement validé lors du règlement de la facture."),0,'L');



                $pdf->SetXY($this->marge_gauche,$pdf->GetY()+6);
                $pdf->MultiCell($this->page_largeur-($this->marge_droite + $this->marge_gauche + 20) ,4,utf8_decode("Bimp et CiCenter restent à votre disposition pour tout renseignement complémentaire.
Nous vous prions d'agréer, Madame, Monsieur, l'expression de nos sincères salutations."),0,'L');

                $pdf->SetXY($this->marge_gauche,$pdf->GetY()+18);
                $pdf->MultiCell($this->page_largeur-($this->marge_droite + $this->marge_gauche + 20) ,6,utf8_decode("Mme OLAGNON
Direction Technique
"),0,'L');


                $this->_pagefoot($pdf,$outputlangs);

                if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();
                $pdf->Close();

                $pdf->Output($file, 'f');
//                $langs->setPhpLang();    // On restaure langue session


                return 1;   // Pas d'erreur
            } else {
                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
//                $langs->setPhpLang();    // On restaure langue session
                return 0;
            }
        } else {
            $this->error=$langs->trans("ErrorConstantNotDefined","SYNOPSISCONTRACT_OUTPUTDIR");
//            $langs->setPhpLang();    // On restaure langue session
            return 0;
        }

        $this->error=$langs->trans("ErrorUnknown");
//        $langs->setPhpLang();    // On restaure langue session
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


    /*
    *   \brief      Affiche le pied de page
    *   \param      pdf     objet PDF
    */
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
