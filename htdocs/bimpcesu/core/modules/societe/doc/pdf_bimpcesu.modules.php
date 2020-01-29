<?php
/* Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Raphael Bertrand     <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2013 Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/propale/doc/pdf_azur.modules.php
 *	\ingroup    propale
 *	\brief      Fichier de la classe permettant de generer les propales au modele Azur
 */
require_once DOL_DOCUMENT_ROOT.'/bimpcesu/core/modules/bimpcesu/modules_bimpcesu.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';



require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';




/**
 *	Class to generate PDF Azur bimpcesu
 */
class pdf_bimpcesu extends ModeleBimpcesu
{
	var $db;
	var $name;
	var $description;
	var $type;

	var $phpmin = array(4,3,0); // Minimum version of PHP required by module
	var $version = 'dolibarr';

	var $page_largeur;
	var $page_hauteur;
	var $format;
	var $marge_gauche;
	var	$marge_droite;
	var	$marge_haute;
	var	$marge_basse;

	var $emetteur;	// Objet societe qui emet


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");

		$this->db = $db;
		$this->name = "bimpcesu";
		$this->description = $langs->trans('DocModelBimpCesuDescription');

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;                 // Affiche mode reglement
		$this->option_condreg = 1;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_escompte = 1;                // Affiche si il y a eu escompte
		$this->option_credit_note = 1;             // Support credit notes
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts

		$this->franchise=!$mysoc->tva_assuj;

		// Get source company
		$this->emetteur=$mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default, if was not defined

		// Define position of columns
		$this->posxdesc=$this->marge_gauche+1;
		$this->posxtva=112;
		$this->posxup=126;
		$this->posxqty=145;
		$this->posxdiscount=162;
		$this->postotalht=174;
		if (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT)) $this->posxtva=$this->posxup;
		$this->posxpicture=$this->posxtva - (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH)?20:$conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH);	// width of images
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->posxpicture-=20;
			$this->posxtva-=20;
			$this->posxup-=20;
			$this->posxqty-=20;
			$this->posxdiscount-=20;
			$this->postotalht-=20;
		}

		$this->tva=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
	}

	/**
     *  Function to build pdf onto disk
     *
     *  @param		Object		$object				Object to generate
     *  @param		Translate	$outputlangs		Lang output object
     *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int			$hidedetails		Do not show line details
     *  @param		int			$hidedesc			Do not show desc
     *  @param		int			$hideref			Do not show ref
     *  @return     int             				1=OK, 0=KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
            global $user,$langs,$conf,$mysoc,$db,$hookmanager;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("propal");
		$outputlangs->load("products");

		$nblignes = count($object->lines);

		// Loop on each lines to detect if there is at least one image to show
		$realpatharray=array();
		if (! empty($conf->global->MAIN_GENERATE_PROPOSALS_WITH_PICTURE))
		{
			for ($i = 0 ; $i < $nblignes ; $i++)
			{
                            	if (empty($object->lines[$i]->fk_product)) continue;

				$objphoto = new Product($this->db);
				$objphoto->fetch($object->lines[$i]->fk_product);

				$pdir = get_exdir($object->lines[$i]->fk_product,2) . $object->lines[$i]->fk_product ."/photos/";
				$dir = $conf->product->dir_output.'/'.$pdir;

				$realpath='';
				foreach ($objphoto->liste_photos($dir,1) as $key => $obj)
				{
					$filename=$obj['photo'];
					//if ($obj['photo_vignette']) $filename='thumbs/'.$obj['photo_vignette'];
					$realpath = $dir.$filename;
					break;
				}

				if ($realpath) $realpatharray[$i]=$realpath;
			}
		}
		if (count($realpatharray) == 0) $this->posxpicture=$this->posxtva;

		if ($conf->bimpcesu->dir_output)
		{
			$object->fetch_thirdparty();
			
			// $deja_regle = 0;

			// Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->bimpcesu->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectid = dol_sanitizeFileName($object->id);
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->bimpcesu->dir_output . "/" . $objectid;
				$file = $dir . "/" ."attestation_" . $objectref. ".pdf";
			}

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
				// Create pdf instance
                $pdf=pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
                $heightforinfotot = 50;	// Height reserved to output the info and total part
		        $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
	            $heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)
                $pdf->SetAutoPageBreak(1,0);

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
                // Set path to the background PDF File
                if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
                {
                    $pagecount = $pdf->setSourceFile($conf->bimpcesu->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Attestation CESU"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("CommercialProposal"));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                                
				// Positionne $this->atleastonediscount si on a au moins une remise
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
                                    if ($object->lines[$i]->remise_percent)
					{
						$this->atleastonediscount++;
					}
				}
				if (empty($this->atleastonediscount))
				{
					$this->posxpicture+=($this->postotalht - $this->posxdiscount);
					$this->posxtva+=($this->postotalht - $this->posxdiscount);
					$this->posxup+=($this->postotalht - $this->posxdiscount);
					$this->posxqty+=($this->postotalht - $this->posxdiscount);
					$this->posxdiscount+=($this->postotalht - $this->posxdiscount);
					//$this->postotalht;
				}

				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				$tab_top = 90;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);
				$tab_height = 130;
				$tab_height_newpage = 150;

				
				// Pied de page
				$this->_pagefoot($pdf,$object,$outputlangs);
				if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file,'F');

				//Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","PROP_OUTPUTDIR");
			return 0;
		}

		$this->error=$langs->trans("ErrorUnknown");
		return 0;   // Erreur par defaut
	}




	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			&$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
        
        
        
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{               
		global $conf,$langs,$db,$dateD,$dateF;

	$facturestatic = new Facture($db);
        
        $sql = 'SELECT f.rowid as facid, f.fk_mode_reglement as mr';
        $sql .= ', fd.description';
        $sql .= ', fd.total_ttc as total_ttc, f.ref';
        $sql .= " FROM " . MAIN_DB_PREFIX . "societe s," . MAIN_DB_PREFIX . "facture f," . MAIN_DB_PREFIX . "facturedet fd";
        $sql .= " WHERE f.fk_soc = s.rowid AND s.rowid = " . $object->id;
        $sql .= " AND f.datef BETWEEN '" . $dateD . "' AND '" . $dateF ."'";
        $sql .= " AND fd.fk_facture = f.rowid ";
      //  $sql .=  "AND fd.product_type = 1";
        //$sql .= " GROUP BY f.rowid";

        $resql = $db->query($sql);
        
        if ($resql) {
          //$var = true;
          $num = $db->num_rows($resql);
          $i = 0;

          $array_total = array();   //déclaration du tableau en variable globale
          $array_total_cesu = array();
      
           while ($i < $num) {
              $objp = $db->fetch_object($resql);
              //$var = !$var;
              
              $facturestatic->id = $objp->facid;
              $facturestatic->ref = $objp->ref;
              $facturestatic->type = $objp->type;
              $facturestatic->total_ttc = $objp->total_ttc;
              $facturestatic->mr = $objp->mr;
              $facturestatic->description = $objp->description;
            $description .= "\n - ".$objp->description;
              
              if($objp->mr == 150){
                  $array_total_cesu[] = $objp->total_ttc;
                  $array_total[] = $objp->total_ttc;    //push du tableau
                  
              } else {
                  $array_total[] = $objp->total_ttc;    //push du tableau
              }
                                          
              $i++;
              
            }
            
          //$db->free($resql);
      
            $total = array_sum ($array_total); // Montant total facture     
            $totalcesu = array_sum ($array_total_cesu); // Montant total CESU       
            
            
            $npref = "SAP821320892"; // Inconnu pour le moment
            $diri = "Christian CONSTANTIN BERTIN";
            $orga = $conf->global->MAIN_INFO_SOCIETE_NOM;
            $orgaadd = $conf->global->MAIN_INFO_SOCIETE_ADDRESS;
            $orgazip = $conf->global->MAIN_INFO_SOCIETE_ZIP;
            $orgaville = $conf->global->MAIN_INFO_SOCIETE_TOWN;
            
        } elseif (empty($dateD) || empty($dateF)) {
            echo 'Pas de dates selectionnées';
            
        } else {
            echo 'Pas de fatcures';
        }
        
        
       // $object = new Societe($db);
        $bene = $object->nom;
        $beneadd = $object->address; 
        $benezip = $object->zip; 
        $beneville = $object->town; 

        // Date & Date -1
        date_default_timezone_set('UTC+1');
        $date01 = date('d/m/Y');
        $date02 = date('d/m/');
        $date03 = date('Y')-1;
        $date04 = date('Y')+1;
        
        // Remise en ordre des dates début et fin (jours/mois/annee)
        $anneeD = substr($dateD,   0,  4);
        $moisD = substr($dateD,  4, 4);
        $jourD = substr($dateD,  8,  2);
        $dateDOrdre = $jourD.$moisD.$anneeD;
        
        $anneeF = substr($dateF,  0,  4);
        $moisF = substr($dateF,  4, 4);
        $jourF = substr($dateF,  8,  2);
        $dateFOrdre = $jourF.$moisF.$anneeF;
                

        $outputlangs->load("main");
		$outputlangs->load("bills");
		$outputlangs->load("propal");
		$outputlangs->load("companies");

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		//  Show Draft Watermark
		if($object->statut==0 && (! empty($conf->global->PROPALE_DRAFT_WATERMARK)) )
		{
            pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->PROPALE_DRAFT_WATERMARK);
		}

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','B', $default_font_size + 3);

		$posy=$this->marge_haute;
		$posx=$this->page_largeur-$this->marge_droite-100;

		$pdf->SetXY($this->marge_gauche,$posy);

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
			    $height=pdf_getHeightForLogo($logo);
			    $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B',$default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		}
		else
		{
			$text=$this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		$pdf->SetFont('','B',$default_font_size + 3);
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$title=$outputlangs->transnoentities("Attestation CESU");
		$pdf->MultiCell(100, 4, $title, '', 'R');

		$pdf->SetFont('','B',$default_font_size);

		$posy+=5;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

		$posy+=1;
		$pdf->SetFont('','', $default_font_size - 1);

		if ($object->ref_client)
		{
			$posy+=5;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("RefCustomer")." : " . $outputlangs->convToOutputCharset($object->ref_client), '', 'R');
		}

		$posy+=4;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Date")." : " . $date01, '', 'R');

		$posy+=4;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("DateEndPropal")." : " . $date02.$date04, '', 'R');

		if ($object->client->code_client)
		{
			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->client->code_client), '', 'R');
		}

		$posy+=2;

                
                
                
                
                
                
                
                
		// Show list of linked objects
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);

		if ($showaddress)
		{
			// Sender properties
			$carac_emetteur='ATTESTATION CESU';
		 	// Add internal contact of proposal if defined
			$arrayidcontact=$object->getIdContact('internal','SALESREPFOLL');
		 	if (count($arrayidcontact) > 0)
		 	{
		 		$object->fetch_user($arrayidcontact[0]);
		 		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
		 	}

		 	$carac_emetteur .= pdf_build_address($outputlangs,$this->emetteur);

			// Show sender = Ou le tableau sera sur la page
			$posy=42; // Endroit sur la page et NON taille du tableau
		 	$posx=$this->marge_gauche;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-80;
			$hautcadre=10; // hauteur du tableau et NON hauteur sur la page

			// Show sender frame = tableau
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posx,$posy-5);
			//$pdf->MultiCell(66,5, $outputlangs->transnoentities("BillFrom").":", 0, 'L'); // EGALE à "emetteur :" au dessus du tableau
			$pdf->SetXY($posx,$posy);
			$pdf->SetFillColor(230,230,230);
			$pdf->MultiCell(182, $hautcadre, "", 0, 'R', 1); // Taille du tableau
			$pdf->SetTextColor(0,0,60);

			// Show sender name = Contenu tableau
			$pdf->SetXY($posx+65,$posy+2); // Position du texte dans le tableau
			$pdf->SetFont('','B', '14'); // Enable BOLD
			$pdf->MultiCell(80, 4, "ATTESTATION CESU", 0, 'L'); // ATTESTATION CESU EN GRAS ET DANS LE TABLEAU
			$posy=$pdf->getY();
                        
                        
                        


                        // LIGNES ...
                        $pdf->SetXY($posx+2,$posy+10); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
                        $pdf->SetTextColor(0,0,200); // fixe la couleur du texte
                        $pdf->SetFont('','',$default_font_size); // fixe la police, le type ( 'B' pour gras, 'I' pour italique, '' pour normal,...)
                        $pdf->MultiCell(182, 3, "$orga déclaré pour les services à la personne sous le N° $npref", 0, 'L'); // imprime du texte avec saut de ligne avec choix de la largeur du formatage du texte ( MultiCell(600, 8,) ) 600 = largeur / 8 = hauteur?
                        
                        // LIGNES ORGANISATION
//                        $pdf->SetXY($posx+2,$posy+15); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
//                        $pdf->MultiCell(100, 3, "$date01", 0, 'L'); // imprime du texte avec saut de ligne avec choix de la largeur du formatage du texte ( MultiCell(600, 8,) ) 600 = largeur / 8 = hauteur?
                        $pdf->SetXY($posx+2,$posy+20); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
                        $pdf->MultiCell(100, 3, "$orgaadd", 0, 'L'); // imprime du texte avec saut de ligne avec choix de la largeur du formatage du texte ( MultiCell(600, 8,) ) 600 = largeur / 8 = hauteur?
                        $pdf->SetXY($posx+2,$posy+25); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
                        $pdf->MultiCell(100, 3, "$orgazip - $orgaville", 0, 'L'); // imprime du texte avec saut de ligne avec choix de la largeur du formatage du texte ( MultiCell(600, 8,) ) 600 = largeur / 8 = hauteur?

                        // LIGNES BENEFICIAIRE
                        $pdf->SetXY($posx+2,$posy+40); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
                        $pdf->SetTextColor(0,0,200); // fixe la couleur du texte
                        $pdf->MultiCell(600, 3, "$bene", 0, 'L'); // imprime du texte avec saut de ligne avec choix de la largeur du formatage du texte ( MultiCell(600, 8,) ) 600 = largeur / 8 = hauteur?
                        $pdf->SetXY($posx+2,$posy+45); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
                        $pdf->MultiCell(100, 3, "$beneadd", 0, 'L'); // imprime du texte avec saut de ligne avec choix de la largeur du formatage du texte ( MultiCell(600, 8,) ) 600 = largeur / 8 = hauteur?
                        $pdf->SetXY($posx+2,$posy+50); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
                        $pdf->MultiCell(100, 3, "$benezip - $beneville", 0, 'L'); // imprime du texte
                        
                        // LIGNES DATE
                        $pdf->SetXY($posx+150,$posy+60); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
                        $pdf->SetTextColor(32,32,32); // fixe la couleur du texte
                        $pdf->MultiCell(100, 3, "$orgaville, le $date01", 0, 'L'); // imprime du texte
                        
                        // LIGNES CONTENUS 1
                        $pdf->SetXY($posx+2,$posy+70); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
                        $pdf->MultiCell(182, 3, "Je soussigné(e) M. $diri, de $orga certifie que M. $bene a bénéficié(e) de services à la personne ($description). ", 0, 'L'); // imprime du texte
                        
                        
                        if($pdf->getY() > 200){
                            $this->_pagefoot($pdf,$object,$outputlangs);
                            $pdf->AddPage();
                            $pdf->SetFont('','',$default_font_size);
                        }
                        else
                            $pdf->MultiCell(182, 3, "\n\n", 0, 'L'); // imprime du texte
                            
                        
                        
                        // LIGNES CONTENUS 2
                        //$pdf->SetXY($posx+2,$posy+85); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
                        $pdf->MultiCell(182, 3, "Du $dateDOrdre au $dateFOrdre, sa participation représente une somme totale de : $total €, dont $totalcesu € au titre du Cesu.", 0, 'L'); // imprime du texte
                        
                        // LIGNES CONTENUS 3                        
                        //$pdf->SetXY($posx+2,$posy+100); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
                        $pdf->MultiCell(182, 3,  "Montant total des factures Du $dateDOrdre au $dateFOrdre : $total €", 0, 'L'); // imprime du texte
                        
                        // LIGNES CONTENUS 4
                        //$pdf->SetXY($posx+2,$posy+105); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
                        $pdf->MultiCell(182, 3, "Montant total payé en Cesu préfinancés : $totalcesu €", 0, 'L'); // imprime du texte
                        
                        // LIGNES CONTENUS 5
                        //$pdf->SetXY($posx+2,$posy+120); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
                        $pdf->MultiCell(182, 3, "Les sommes perçues pour financer les services à la personne sont à déduire de la valeur indiquée
précédemment. La déclaration engage la responsabilité du seul contribuable.", 0, 'L'); // imprime du texte
                        
                        // LIGNES CONTENUS 6
                        //$pdf->SetXY($posx+2,$posy+145); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
                        $pdf->SetTextColor(32,32,32); // fixe la couleur du texte
                        $pdf->SetFont('','I',"8"); // fixe la police, le type ( 'B' pour gras, 'I' pour italique, '' pour normal,...)
                        $pdf->MultiCell(182, 3, "* 1. Le client doit conserver à fin de contrôle, les factures remises par le prestataire de services qui précisent les dates
d’intervention et durées des interventions.
2. La partie pré-financée par l’employeur du CESU est exonérée d’impôt. Seule la partie autofinancée par le bénéficiaire du
CESU ouvre droit à la réduction d’impôt de l’article 199 sexdecies du code général des impôts (cf. article 7231 du code
du travail). La distinction des montants sera portée sur l’attestation émise par l’employeur à son salarié bénéficiaire en vue de
la déclaration fiscale annuelle.
3. Pour toute information concernant les services à la personne, le cesu et les aides, consultez services-a-domicile.fr", 0, 'L'); // imprime du texte avec saut de ligne avec choix de la largeur du formatage du texte ( MultiCell(600, 8,) ) 600 = largeur / 8 = hauteur?
                        
                        // LIGNES CONTENUS 7
                        //$pdf->SetXY($posx+2,$posy+185); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
                        $pdf->SetTextColor(32,32,32); // fixe la couleur du texte
                        $pdf->SetFont('','',$default_font_size +2); // fixe la police, le type ( 'B' pour gras, 'I' pour italique, '' pour normal,...)
                        $pdf->MultiCell(182, 3, "Fait pour valoir ce que de droit,", 0, 'L'); // imprime du texte avec saut de ligne avec choix de la largeur du formatage du texte ( MultiCell(600, 8,) ) 600 = largeur / 8 = hauteur?
                        
                        // LIGNES CONTENUS FIN
                        //$pdf->SetXY($posx+65,$posy+200); // Position du texte sur la page //$POSX = LARGEUR // $POSY = HAUTEUR
                        $pdf->SetTextColor(32,32,32); // fixe la couleur du texte
                        $pdf->SetFont('','',$default_font_size); // fixe la police, le type ( 'B' pour gras, 'I' pour italique, '' pour normal,...)
                        $pdf->MultiCell(182, 3, "$bene, Cachet de l’entreprise", 0, 'L'); // imprime du texte avec saut de ligne avec choix de la largeur du formatage du texte ( MultiCell(600, 8,) ) 600 = largeur / 8 = hauteur?
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
			// Show sender information
//			$pdf->SetXY($posx+2,$posy);
//			$pdf->SetFont('','', $default_font_size - 1);
//			$pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');


//			// If CUSTOMER contact defined, we use it
//			$usecontact=false;
//			$arrayidcontact=$object->getIdContact('external','CUSTOMER');
//			if (count($arrayidcontact) > 0)
//			{
//				$usecontact=true;
//				$result=$object->fetch_contact($arrayidcontact[0]);
//			}

                        
                        
                  
                        
                        
                        
                    
                        
                        
                        
//              // Show list of linked objects  ==> BACKUP <==
//		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);
//
//		if ($showaddress)
//		{
//			// Sender properties
//			$carac_emetteur='';
//		 	// Add internal contact of proposal if defined
//			$arrayidcontact=$object->getIdContact('internal','SALESREPFOLL');
//		 	if (count($arrayidcontact) > 0)
//		 	{
//		 		$object->fetch_user($arrayidcontact[0]);
//		 		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
//		 	}
//
//		 	$carac_emetteur .= pdf_build_address($outputlangs,$this->emetteur);
//
//			// Show sender
//			$posy=42;
//		 	$posx=$this->marge_gauche;
//			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-80;
//			$hautcadre=40;
//
//			// Show sender frame
//			$pdf->SetTextColor(0,0,0);
//			$pdf->SetFont('','', $default_font_size - 2);
//			$pdf->SetXY($posx,$posy-5);
//			$pdf->MultiCell(66,5, $outputlangs->transnoentities("BillFrom").":", 0, 'L');
//			$pdf->SetXY($posx,$posy);
//			$pdf->SetFillColor(230,230,230);
//			$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);
//			$pdf->SetTextColor(0,0,60);
//
//			// Show sender name
//			$pdf->SetXY($posx+2,$posy+3);
//			$pdf->SetFont('','B', $default_font_size);
//			$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
//			$posy=$pdf->getY();
//
//			// Show sender information
//			$pdf->SetXY($posx+2,$posy);
//			$pdf->SetFont('','', $default_font_size - 1);
//			$pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');
//
//
//			// If CUSTOMER contact defined, we use it
//			$usecontact=false;
//			$arrayidcontact=$object->getIdContact('external','CUSTOMER');
//			if (count($arrayidcontact) > 0)
//			{
//				$usecontact=true;
//				$result=$object->fetch_contact($arrayidcontact[0]);
//			}
                        
                        
                        
//			// Recipient name
//			if (! empty($usecontact))
//			{
//				// On peut utiliser le nom de la societe du contact
//				if (! empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) $socname = $object->contact->socname;
//				else $socname = $object->client->nom;
//				$carac_client_name=$outputlangs->convToOutputCharset($socname);
//			}
//			else
//			{
//				$carac_client_name=$outputlangs->convToOutputCharset($object->client->nom);
//			}
//
//			$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->client,($usecontact?$object->contact:''),$usecontact,'target');
//
//			// Show recipient
//			$widthrecbox=100;
//			if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
//			$posy=42;
//			$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
//			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;
//
//			// Show recipient frame
//			$pdf->SetTextColor(0,0,0);
//			$pdf->SetFont('','', $default_font_size - 2);
//			$pdf->SetXY($posx+2,$posy-5);
//			$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":", 0, 'L');
//			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);
//
//			// Show recipient name
//			$pdf->SetXY($posx+2,$posy+3);
//			$pdf->SetFont('','B', $default_font_size);
//			$pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, 'L');
//
//			// Show recipient information
//			$pdf->SetFont('','', $default_font_size - 1);
//			$pdf->SetXY($posx+2,$posy+4+(dol_nboflines_bis($carac_client_name,50)*4));
//			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		}

		$pdf->SetTextColor(0,0,0);
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			&$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
	{
		return pdf_pagefoot($pdf,$outputlangs,'PROPALE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,0,$hidefreetext);
	}

}

