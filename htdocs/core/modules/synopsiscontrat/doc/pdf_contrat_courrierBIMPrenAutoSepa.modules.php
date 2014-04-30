<?php
/*
  * GLE by Synopsis et DRSI
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
  * Name : pdf_contrat_courrierBIMPavenant.modules.php
  * GLE-1.2
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

class pdf_contrat_courrierBIMPrenAutoSepa extends ModeleSynopsiscontrat
{
    public $emetteur;    // Objet societe qui emet


    /**
    \brief      Constructeur
    \param        db        Handler acces base de donnee
    */
    function pdf_contrat_courrierBIMPrenAutoSepa($db)
    {

        global $conf,$langs,$mysoc;

        $langs->load("main");
        $langs->load("bills");
        $this->debug = "";
        $this->db = $db;
        $this->name = "babel";
        $this->description = $langs->trans('PDFContratSynopsisDescription');

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
        //$outputlangs->setPhpLang();
        if ($conf->synopsiscontrat->dir_output)
        {
            // Definition de l'objet $contrat (pour compatibilite ascendante)
            if (! is_object($contrat))
            {
                $id = $contrat;
                require_once(DOL_DOCUMENT_ROOT."/Synopsis_Contrat/class/contratMixte.class.php");
                $contrat=getContratObj($id);
                $contrat->fetch($id);
                $contrat->fetch_lines(true);
//                $contrat = new ContratMixte($this->db);
//                $ret=$contrat->fetch($id);
            } else {
                $contrat->fetch_lines(true);
            }

            // Definition de $dir et $file
            if ($contrat->specimen)
            {
                $dir = $conf->synopsiscontrat->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $propref = sanitize_string($contrat->ref);
                $dir = $conf->synopsiscontrat->dir_output . "/" . $propref;
                $file = $dir ."/Courrier_renouv_SEPA".date("d_m_Y")."_" . $propref . ".pdf";
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
                $pdf="";
                $nblignes = sizeof($contrat->lignes);
                // Protection et encryption du pdf
//                if ($conf->global->PDF_SECURITY_ENCRYPTION)
//                {
//                    $pdf=new FPDI_Protection('P','mm',$this->format);
//                    $pdfrights = array('print'); // Ne permet que l'impression du document
//                    $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
//                    $pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
//                    $pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
//                } else  {
//
//                    $pdf=new FPDI('P','mm',$this->format);
//                }
//                $pdf1=new FPDI('P','mm',$this->format);
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
                $pdf1->SetFont('', '', 8);

                $pdf->SetDrawColor(128,128,128);


                $pdf->SetTitle($contrat->ref);
                $pdf->SetSubject($outputlangs->transnoentities("Contract"));
                $pdf->SetCreator("GLE ".GLE_VERSION);
                $pdf->SetAuthor($user->getFullName($langs));

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf1->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(0,0);

//                $pdf->AddFont('BI', 'BI', 'BI.php');
//                //$pdf->AddFont('fq-logo', 'Roman', 'fq-logo.php');

                // Tete de page
                $this->_pagehead($pdf, $contrat, 1, $outputlangs);
                $pdf->SetFont('', 'B', 12);

//Encart societe
                $pdf->SetXY($this->marge_gauche + 100,$this->marge_haute);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 100) ,6,($contrat->societe->titre."x" != "x"?$contrat->societe->titre." ":"").$contrat->societe->nom,0,'L');
                $pdf->SetFont('', '', 11);
                $pdf->SetX($this->marge_gauche + 100);

//representant légal : signataire contrat
                $requete = "SELECT fk_socpeople
                              FROM ".MAIN_DB_PREFIX."c_type_contact as c,
                                   ".MAIN_DB_PREFIX."element_contact as e
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
                    $contact = $tmpcontact->lastname." ".$tmpcontact->firstname;
                }
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 100),6,$contact,0,'L');
                $pdf->SetX($this->marge_gauche + 100);
//addresse :> add de la société
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 100),6,$contrat->societe->address."\n".$contrat->societe->zip." ".$contrat->societe->town,0,'L');

//Date
                $pdf->SetFont('', '', 10);
                $pdf->SetXY($this->marge_gauche + 100,$this->marge_haute + 44);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 50) ,6,"Lyon, le ".date("d/m/Y"),0,'L');

//Objet
//                $pdf->SetFont('', 'U', 10);
//                $pdf->SetXY($this->marge_gauche,$this->marge_haute + 60);
//                $pdf->MultiCell(14 ,4,"Objet : ",0,'L');
                $pdf->SetFont('', '', 10);
                $pdf->SetXY($this->marge_gauche,$this->marge_haute + 60);
                $pdf->MultiCell($this->page_largeur-($this->marge_droite + $this->marge_gauche + 14) ,4,utf8_encodeRien("Objet : Modification réglementaire de vos prélèvements


Cher client, cliente, 

Nous allons remplacer prochainement le service de prélèvement national que vous utilisez jusqu'à présent par le nouveau service de prélèvement européen, le prélèvement SEPA. 
Conformément à l'article 19 de l'ordonnance 2009-866, relatif à la continuité des mandats de prélèvement, le consentement donné au titre du prélèvement national que vous avez signé demeure valable pour le prélèvement SEPA; nous continuerons à envoyer des instructions à votre banque pour débiter votre compte et votre banque continuera à débiter votre compte conformément à nos instructions; vous n'aurez donc aucune démarche à accomplir auprès de votre banque. 


Vous trouverez ci-après les informations caractérisant votre prélèvement SEPA : 
Nom du créancier : 		SA OLYS
Identifiant Créancier SEPA : FR02ZZZ008801


en cas de réclamation, révocation ou modification relative à vos prélèvements SEPA, vous pourrez adresser vos demandes à : 

BIMP 
Service mandats SEPA
4 rue du commandant Dubois
69003 LYON


Veuillez agréer, Madame, Monsieur, l'expression de nos salutations distinguées. 


Le service facturation"),0,'L');


                $this->_pagefoot($pdf,$outputlangs);

                if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();
                $pdf->Close();

                $this->file = $file;$pdf->Output($file, 'f');

//                //$langs->setPhpLang();    // On restaure langue session


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
