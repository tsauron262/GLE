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
  * Name : pdf_contrat_courrierBIMPsignature.modules.php
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

class pdf_contrat_contratFinanc extends ModeleSynopsiscontrat
{
    public $emetteur;    // Objet societe qui emet
    var $object;

    /**
    \brief      Constructeur
    \param        db        Handler acces base de donnee
    */
    function __construct($db)
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

        $this->option_logo = 1;                    // Affiche logo

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
                $file = $dir ."/Contrat_de_financement_".date("d_m_Y")."_" . $propref . ".pdf";
            }
            $this->contrat = $contrat;
            
            require_once (DOL_DOCUMENT_ROOT . "/synopsisfinanc/class/synopsisfinancement.class.php");
            $valfinance=new Synopsisfinancement($this->db);
            $valfinance->fetch(NULL,NULL,$this->contrat->id);
            
            require_once(DOL_DOCUMENT_ROOT . "/comm/propal/class/propal.class.php");
            $propal = new Propal($this->db);
            $propal->fetch($valfinance->propal_id);

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
                $pdf1->SetFont(''/*'Arial'*/, '', 8);

                $pdf->SetDrawColor(128,128,128);


                $pdf->SetTitle($contrat->ref);
                $pdf->SetSubject($outputlangs->transnoentities("Contract"));
                $pdf->SetCreator("GLE ".GLE_VERSION);
                $pdf->SetAuthor($user->getFullName($langs));

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf1->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(1,0);

                //$pdf->AddFont('VeraMoBI', 'BI', 'VeraMoBI.php');
                //$pdf->AddFont('fq-logo', 'Roman', 'fq-logo.php');

                // Tete de page
                $this->_pagehead($pdf, $contrat, 1, $outputlangs);
                $pdf->SetFont(''/*'Arial'*/, 'B', 9);

//locataire/////////////////////////////////////////////////////////////////////
                $pdf->SetXY($this->marge_gauche,$this->marge_haute);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 100) ,6,"Le locataire:",0,'L');
                $pdf->SetFont(''/*'Arial'*/, '', 8);
                $pdf->SetXY($this->marge_gauche, $this->marge_haute+6);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 100) ,6, "La société: ".$contrat->societe->nom,0,'L');
                $pdf->SetXY($this->marge_gauche + 60,$this->marge_haute+6);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200) ,6, $contrat->societe->forme_juridique." au capital de ".$contrat->societe->capital,0,'L');
                $pdf->setX($this->marge_gauche);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Immatriculé sous le N°: ".$contrat->societe->idprof4." auprès du RCS de Lyon", 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Dont le siège sociale est situé au ".$contrat->societe->address." ".$contrat->societe->zip." ".$contrat->societe->town, 0, 'L');
                /* faire la requete pour le représentant */
                
                $nom_provisoir="Monsieur Yves Machin-Truc";//variable temporaire à modifier par la vrai valeur
                $grade_provisoir="gerant";//variable temporaire à modifier par la vrai valeur
                
                
                /* fin requete */
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "représenter par: ".$nom_provisoir, 0, 'L');
                $pdf->SetXY($this->marge_gauche+100, $this->marge_haute+24);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "intervenant en qualité de: ".$grade_provisoir, 0, 'L');
                
//le loueur/////////////////////////////////////////////////////////////////////
                $pdf->SetFont(''/*'Arial'*/, 'B', 9);
                $pdf->SetX($this->marge_gauche);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Le loueur:", 0, 'L');
                
                $pdf->SetFont(''/*'Arial'*/, '', 8);
                if($this->emetteur->name=="Aegis"){
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "La Société FINAPRO, SARL au capital de 50 000 € dont le siège social est situé à Jouques (13490), Parc du Deffend - 23 boulevard du Deffend,
enregistrée sous le n° 443 247 978 au RCS d'Aix en Provence,", 0, 'L');//print_r($this->emetteur);
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Représentée par Madame Patricia RODDIER, intervenant en qualité de Gérante", 0, 'L');
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Le loueur donne en location, l’équipement désigné ci-dessous (ci-après « équipement »), au locataire qui l'accepte, aux Conditions Générales ciannexées
composées de deux pages recto : Feuillet A et Feuillet B et aux Conditions Particulières suivantes :", 0, 'L');
                }else{
                    $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "!!! erreur nom entreprise !!!", 0, 'L');
                }
                
//dezcription de l'équipement///////////////////////////////////////////////////
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
                $pdf->SetFont(''/*'Arial'*/, 'B', 9);
                $pdf->SetX($this->marge_gauche);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Description de l'équipement:", 0, 'L');
                $pdf->SetFont(''/*'Arial'*/, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "!!!Comming soon!!!", 1, 'C');//penser à implenter le tableau des équipements
                
//évolution de l'équipement/////////////////////////////////////////////////////
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
                $pdf->SetFont(''/*'Arial'*/, 'B', 9);
                $pdf->SetX($this->marge_gauche);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Evolution de l'équipement:", 0, 'L');
                $pdf->SetFont(''/*'Arial'*/, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Le locataire pourra demander au bailleur, au cours de la période de validité du présent contrat la modification de l’équipement informatique remis en
location. Les modifications éventuelles du contrat seront déterminées par l’accord des parties.", 0, 'L');
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Cette modification pourra porter sur tout ou partie des équipements, par adjonction, remplacement et/ou enlèvement des matériels repris dans
l’article 1 ci-dessus.", 0, 'L');
                
//récap du loyer////////////////////////////////////////////////////////////////
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "", 0, 'L');
                $pdf->SetFont(''/*'Arial'*/, 'B', 9);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 200), 6, "Le loyers:", 0, 'L');
                $pdf->SetXY($this->marge_gauche, $this->marge_haute+126);
                $pdf->SetFont(''/*'Arial'*/, '', 8);
                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche), 6, "Le loyer ferme et non révisable en cours de contrat, payable par terme à échoir, par prélèvements automatiques est fixé à :", 0, 'L');
                
                $X=$this->marge_gauche;
                $Y=$this->marge_haute+132;
                $W=($this->page_largeur-$this->marge_droite - $this->marge_gauche)/4;
                for($i=1;$i<=4;$i++){
                    $pdf->SetXY($X, $Y);
                    if($i==1){
                        $pdf->SetFont(''/*'Arial'*/, 'B', 9);
                        $pdf->MultiCell($W, 6, "NOMBRE DE LOYERS", 1, 'C',FALSE,NULL,NULL,null,null,null,null,null,null,'M');
                        $pdf->SetXY($X, $Y+6);
                        $pdf->setColor('fill', 230, 230, 250);
                        $pdf->SetFont(''/*'Arial'*/, '', 8);
                        $pdf->MultiCell($W, 6, $valfinance->nb_periode, 1, 'C',true,NULL,NULL,null,null,null,null,null,null,'M');
                    }
                    if($i==2){
                        $pdf->SetFont(''/*'Arial'*/, 'B', 9);
                        $pdf->MultiCell($W, 6, "MONTANT HT", 1, 'C',FALSE,NULL,NULL,null,null,null,null,null,null,'M');
                        $pdf->SetXY($X, $Y+6);
                        $pdf->setColor('fill', 230, 230, 250);
                        $pdf->SetFont(''/*'Arial'*/, '', 8);
                        $pdf->MultiCell($W, 6, price($valfinance->loyer+0.005), 1, 'C',true,NULL,NULL,null,null,null,null,null,null,'M');
                    }
                    if($i==3){
                        $pdf->SetFont(''/*'Arial'*/, 'B', 9);
                        $pdf->MultiCell($W, 6, "PERIODICITE", 1, 'C',FALSE,NULL,NULL,null,null,null,null,null,null,'M');
                        $pdf->SetXY($X, $Y+6);
                        $pdf->setColor('fill', 230, 230, 250);
                        $pdf->SetFont(''/*'Arial'*/, '', 8);
                        $pdf->MultiCell($W, 6, Synopsisfinancement::$TPeriode[$valfinance->periode], 1, 'C',true,NULL,NULL,null,null,null,null,null,null,'M');
                    }
                    if($i==4){
                        $pdf->SetFont(''/*'Arial'*/, 'B', 9);
                        $pdf->MultiCell($W, 6, "DUREE", 1, 'C',FALSE,NULL,NULL,null,null,null,null,null,null,'M');
                        $pdf->SetXY($X, $Y+6);
                        $pdf->setColor('fill', 230, 230, 250);
                        $pdf->SetFont(''/*'Arial'*/, '', 8);
                        $pdf->MultiCell($W, 6, $valfinance->nb_periode." ".  Synopsisfinancement::$tabM[$valfinance->periode], 1, 'C',true,NULL,NULL,null,null,null,null,null,null,'M');
                    }
                    $X=$X+$W;
                }
                $X=$this->marge_gauche;
                
                $Y=$Y+18;
                $pdf->SetXY($X, $Y);
                $pdf->SetFont(''/*'Arial'*/, 'B', 9);
                $pdf->Write(6, "Site d'installation: ");
                $pdf->SetFont(''/*'Arial'*/, '', 8);
                $pdf->Write(6, $contrat->societe->address." à ".$contrat->societe->town);
                
                $Y=$Y+6;
                $pdf->SetXY($X, $Y);
                $pdf->SetFont(''/*'Arial'*/, 'B', 9);
                $pdf->Write(6, "Date d'installation: ");
                $pdf->SetFont(''/*'Arial'*/, '', 8);
                $pdf->Write(6, dol_print_date($propal->date_livraison));
                
                $Y=$Y+6;
                $pdf->SetXY($X, $Y);
                $pdf->Write(6, "Clause spécifique: ");
                
                $Y=$Y+6;
                $pdf->SetXY($X, $Y);
                $pdf->MultiCell($this->page_largeur-$this->marge_droite - $this->marge_gauche, 6, "Fait en autant d'exemplaires que de parties, un pour chacune des parties", 0, 'L');
                $pdf->MultiCell($this->page_largeur-$this->marge_droite - $this->marge_gauche, 6, "ANNEXE : Conditions Générales composées de deux pages recto : Feuillet A et Feuillet B", 0, 'L');
                
                $Y=$Y+12;
                $pdf->SetXY($X, $Y);
                $pdf->Write(6, "Fait à Lyon le ".$contrat->date_contrat);

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
                //$pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 100),6,$contact,0,'L');
                //$pdf->SetX($this->marge_gauche + 100);
//addresse :> add de la société
                //$pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 100),6,$contrat->societe->address."\n".$contrat->societe->zip." ".$contrat->societe->town,0,'L');

//Date
//                $pdf->SetFont(''/*'Arial'*/, '', 10);
//                $pdf->SetXY($this->marge_gauche + 100,$this->marge_haute + 44);
//                $pdf->MultiCell($this->page_largeur - $this->marge_droite - ($this->marge_gauche + 50) ,6,"Lyon, le ".date("d/m/Y"),0,'L');


                $this->_pagefoot($pdf,$outputlangs);

                if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();
                $pdf->Close();

                $this->file = $file;$pdf->Output($file, 'f');

                //$langs->setPhpLang();    // On restaure langue session


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
