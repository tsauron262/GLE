<?php
/* Copyright (C) 2005		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2014-2015	Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2014-2016	Charlie Benke 		<charlie@patas-monkey.Com>
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
 *	\file       htdocs/core/modules/expedition/doc/pdf_rouget.modules.php
 *	\ingroup    expedition
 *	\brief      Fichier de la classe permettant de generer les bordereaux envoi au modele Rouget
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Classe permettant de generer les borderaux envoi au modele Rouget
 */
class pdf_expedition_rouget_composition extends ModelePdfExpedition
{
	var $emetteur;	// Objet societe qui emet


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db=0)
	{
		global $conf,$langs,$mysoc;

		$this->db = $db;
		$this->name = "rouget composition";
		$this->description = $langs->trans("DocumentModelCompositionSimple");

		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->country_code) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default if not defined

		// Defini position des colonnes
		$this->posxdesc=$this->marge_gauche+1;
		$this->posxqtyordered	=$this->page_largeur - $this->marge_droite - 72;
		$this->posxqtytoship	=$this->page_largeur - $this->marge_droite - 54;
		$this->posxqtyyetship	=$this->page_largeur - $this->marge_droite - 36;
		$this->posxqtyleft		=$this->page_largeur - $this->marge_droite - 18;
		$this->posxpicture		=$this->posxqtyordered - (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH)?20:$conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH);	// width of images
		
		if ($this->page_largeur < 210) // To work with US executive format
		{
		    $this->posxpicture-=20;
		    $this->posxqtyordered-=20;
		    $this->posxqtytoship-=20;
		}
	}

	/**
	 *	Function to build pdf onto disk
	 *
	 *	@param		Object		$object			Object expedition to generate (or id if old method)
	 *	@param		Translate	$outputlangs		Lang output object
     *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int			$hidedetails		Do not show line details
     *  @param		int			$hidedesc			Do not show desc
     *  @param		int			$hideref			Do not show ref
     *  @return     int         	    			1=OK, 0=KO
	 */
	function write_file(&$object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$conf,$langs,$hookmanager;

		$object->fetch_thirdparty();
		
		$commandeinit = new Commande($this->db);
		$commandeinit->fetch($object->origin_id);
		


		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		$outputlangs->load("propal");
		$outputlangs->load("deliveries");
        $outputlangs->load("sendings");

		// on positionne les lignes de commandes à place des lignes d'expéditions
		$nblignesexpedition = count($object->lines);
		$objectlineexpedition=$object->lines;
		$object->lines=$commandeinit->lines;
		$nblignes = count($object->lines);
		
        // Loop on each lines to detect if there is at least one image to show
        $realpatharray=array();
        if (! empty($conf->global->MAIN_GENERATE_SHIPMENT_WITH_PICTURE))
        {
            $objphoto = new Product($this->db);
        
            for ($i = 0 ; $i < $nblignes ; $i++)
            {
                if (empty($object->lines[$i]->fk_product)) continue;
        
                $objphoto->fetch($object->lines[$i]->fk_product);
                //var_dump($objphoto->ref);exit;
                if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO))
                {
                    $pdir[0] = get_exdir($objphoto->id,2,0,0,$objphoto,'product') . $objphoto->id ."/photos/";
                    $pdir[1] = get_exdir(0,0,0,0,$objphoto,'product') . dol_sanitizeFileName($objphoto->ref).'/';
                }
                else
                {
                    $pdir[0] = get_exdir(0,0,0,0,$objphoto,'product') . dol_sanitizeFileName($objphoto->ref).'/';				// default
                    $pdir[1] = get_exdir($objphoto->id,2,0,0,$objphoto,'product') . $objphoto->id ."/photos/";	// alternative
                }
        
                $arephoto = false;
                foreach ($pdir as $midir)
                {
                    if (! $arephoto)
                    {
                        $dir = $conf->product->dir_output.'/'.$midir;
        
                        foreach ($objphoto->liste_photos($dir,1) as $key => $obj)
                        {
                            if (empty($conf->global->CAT_HIGH_QUALITY_IMAGES))		// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
                            {
                                if ($obj['photo_vignette'])
                                {
                                    $filename= $obj['photo_vignette'];
                                }
                                else
                                {
                                    $filename=$obj['photo'];
                                }
                            }
                            else
                            {
                                $filename=$obj['photo'];
                            }
        
                            $realpath = $dir.$filename;
                            $arephoto = true;
                        }
                    }
                }
        
                if ($realpath && $arephoto) $realpatharray[$i]=$realpath;
            }
        }
        
        if (count($realpatharray) == 0) $this->posxpicture=$this->posxqtyordered;        
        
		if ($conf->expedition->dir_output)
		{
			// Definition de $dir et $file
			if ($object->specimen)
			{
				$dir = $conf->expedition->dir_output."/sending";
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$expref = dol_sanitizeFileName($object->ref);
				$dir = $conf->expedition->dir_output."/sending/" . $expref;
				$file = $dir . "/" . $expref . ".pdf";
			}

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('beforePDFCreation',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

				$pdf=pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);
				$heightforinfotot = 0;	// Height reserved to output the info and total part
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
                    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Shipment"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Shipment"));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

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

				// Incoterm
				$height_incoterms = 0;
				if ($conf->incoterm->enabled)
				{
					$desc_incoterms = $object->getIncotermsForPDF();
					if ($desc_incoterms)
					{
						$tab_top = 88;

						$pdf->SetFont('','', $default_font_size - 1);
						$pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top-1, dol_htmlentitiesbr($desc_incoterms), 0, 1);
						$nexY = $pdf->GetY();
						$height_incoterms=$nexY-$tab_top;
	
						// Rect prend une longueur en 3eme param
						$pdf->SetDrawColor(192,192,192);
						$pdf->Rect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_incoterms+1);
	
						$tab_top = $nexY+6;
						$height_incoterms += 4;
					}
				}

				if (! empty($object->note_public) || ! empty($object->tracking_number))
				{
					$tab_top = 88 + $height_incoterms;
					$tab_top_alt = $tab_top;

					$pdf->SetFont('','B', $default_font_size - 2);
					$pdf->writeHTMLCell(60, 4, $this->posxdesc-1, $tab_top-1, $outputlangs->transnoentities("TrackingNumber")." : " . $object->tracking_number, 0, 1, false, true, 'L');

					$tab_top_alt = $pdf->GetY();
					//$tab_top_alt += 1;

					// Tracking number
					if (! empty($object->tracking_number))
					{
						$object->GetUrlTrackingStatus($object->tracking_number);
						if (! empty($object->tracking_url))
						{
							if ($object->shipping_method_id > 0)
							{
								// Get code using getLabelFromKey
								$code=$outputlangs->getLabelFromKey($this->db,$object->shipping_method_id,'c_shipment_mode','rowid','code');
								$label='';
								if ($object->tracking_url != $object->tracking_number) $label.=$outputlangs->trans("LinkToTrackYourPackage")."<br>";
								$label.=$outputlangs->trans("SendingMethod").": ".$outputlangs->trans("SendingMethod".strtoupper($code));
								//var_dump($object->tracking_url != $object->tracking_number);exit;
								if ($object->tracking_url != $object->tracking_number)
								{
									$label.=" : ";
									$label.=$object->tracking_url;
								}
								$pdf->SetFont('','B', $default_font_size - 2);
								$pdf->writeHTMLCell(60, 4, $this->posxdesc-1, $tab_top_alt, $label, 0, 1, false, true, 'L');

								$tab_top_alt = $pdf->GetY();
							}
						}
					}

					// Notes
					if (! empty($object->note_public))
					{
						$pdf->SetFont('','', $default_font_size - 1);   // Dans boucle pour gerer multi-page
						$pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top_alt, dol_htmlentitiesbr($object->note_public), 0, 1);
					}

					$nexY = $pdf->GetY();
					$height_note=$nexY-$tab_top;

					// Rect prend une longueur en 3eme param
					$pdf->SetDrawColor(192,192,192);
					$pdf->Rect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+1);

					$tab_height = $tab_height - $height_note;
					$tab_top = $nexY+6;
				}
				else
				{
					$height_note=0;
				}

				$iniY = $tab_top + 12;
				$curY = $tab_top + 12;
				$nexY = $tab_top + 12;

				// Loop on each lines
				for ($i = 0; $i < $nblignes; $i++)
				{
					$curY = $nexY;
					$pdf->SetFont('','', $default_font_size - 1);   // Into loop to work with multipage
					$pdf->SetTextColor(0,0,0);

					$lignedansexpedition=-1;
					
					// on se positionne sur la ligne de commandeinit
					for ($j = 0; $j < $nblignesexpedition; $j++)
					{
						if($objectlineexpedition[$j]->fk_product == $object->lines[$i]->fk_product)
							$lignedansexpedition=$j;
					}		

					// Define size of image if we need it
					$imglinesize=array();
					if (! empty($realpatharray[$i])) $imglinesize=pdf_getSizeForImage($realpatharray[$i]);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore=$pdf->getPage();

					$showpricebeforepagebreak=1;
					$posYAfterImage=0;
					$posYAfterDescription=0;

					// We start with Photo of product line
					if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur-($heightforfooter+$heightforfreetext+$heightforinfotot)))	// If photo too high, we moved completely on new page
					{
						$pdf->AddPage('','',true);
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
						$pdf->setPage($pageposbefore+1);

						$curY = $tab_top_newpage;
						$showpricebeforepagebreak=0;
					}

					if (isset($imglinesize['width']) && isset($imglinesize['height']))
					{
						$curX = $this->posxpicture-1;
						$pdf->Image($realpatharray[$i], $curX + (($this->posxqtyordered-$this->posxpicture-$imglinesize['width'])/2), $curY, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300);	// Use 300 dpi
						// $pdf->Image does not increase value return by getY, so we save it manually
						$posYAfterImage=$curY+$imglinesize['height'];
					}

					// Description of product line
					$curX = $this->posxdesc-1;

					$pdf->startTransaction();
					
					// Description de la ligne produit
					pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxpicture-$curX,3,$curX,$curY,$hideref,$hidedesc);

					// Ajout du numéro de série, s'il existe...
					dol_syslog("PDF_Expedition_Rouget_Equipement::write_file : searching Serial = ".$object->lines[$i]->fk_product);

					$pageposafter=$pdf->getPage();
					if ($pageposafter > $pageposbefore)	// There is a pagebreak
					{
						$pdf->rollbackTransaction(true);
						$pageposafter=$pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
						pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxpicture-$curX,3,$curX,$curY,$hideref,$hidedesc);

						// Ajout du numéro de série, s'il existe...
						dol_syslog("PDF_Expedition_Rouget_Equipement::write_file : searching Serial = ".$object->lines[$i]->fk_product);

						$pageposafter=$pdf->getPage();
						$posyafter=$pdf->GetY();
						//var_dump($posyafter); var_dump(($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot))); exit;
						if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot)))	// There is no space left for total+free text
						{
							if ($i == ($nblignes-1))	// No more lines, and no space left to show total, so we create a new page
							{
								$pdf->AddPage('','',true);
								if (! empty($tplidx)) $pdf->useTemplate($tplidx);
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
								$pdf->setPage($pageposafter+1);
							}
						}
						else
						{
							// We found a page break
							$showpricebeforepagebreak=0;
						}
					}
					else	// No pagebreak
					{
						$pdf->commitTransaction();
					}
					$posYAfterDescription=$pdf->GetY();
					
					$nexY = $pdf->GetY();
					$pageposafter=$pdf->getPage();
					
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
					}

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore) {
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
					}

					$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut

					$pdf->SetXY($this->posxqtyordered, $curY);
					$pdf->MultiCell(($this->posxqtytoship - $this->posxqtyordered), 3, $object->lines[$i]->qty,'','C');

					$qtyshipped =0;
					if ($lignedansexpedition != -1)
						$qtyshipped=$objectlineexpedition[$lignedansexpedition]->qty_shipped;

					$pdf->SetXY($this->posxqtytoship, $curY);
					$pdf->MultiCell(($this->posxqtyyetship - $this->posxqtytoship), 3, $qtyshipped,'','C');

					// détermination de la quantité déjà envoyé et restante à l'arrache
					$sql =" SELECT sum(ed.qty) as yetshipped FROM ".MAIN_DB_PREFIX."expeditiondet as ed";
					$sql.=" ,".MAIN_DB_PREFIX."expedition as e";
					$sql.=" ,".MAIN_DB_PREFIX."commandedet as cd";
					$sql.=" ,".MAIN_DB_PREFIX."element_element as ee";
					$sql.=" WHERE ee.fk_source=".$object->origin_id;
					$sql.=" AND ee.sourcetype='".$object->origin."'";
					$sql.=" AND ee.targettype='shipping'";
					$sql.=" AND ee.fk_target=e.rowid";
					$sql.=" AND ed.fk_expedition=e.rowid";
					$sql.=" AND ed.fk_origin_line=cd.rowid";
					$sql.=" AND cd.fk_product=".$object->lines[$i]->fk_product;
					$resql = $this->db->query($sql);
					if ($resql)
					{
						$row = $this->db->fetch_row($resql);
						$qty_yet_shipped = $row[0] - $qtyshipped; // on supprime la quantité de cette commande
						$qty_left_to_ship = $object->lines[$i]->qty - $qty_yet_shipped - $qtyshipped;
					}

					$pdf->SetXY($this->posxqtyyetship, $curY);
					$pdf->MultiCell(($this->posxqtyleft - $this->posxqtyyetship), 3, $qty_yet_shipped,'','C');

					$pdf->SetXY($this->posxqtyleft, $curY);
					$pdf->MultiCell(($this->page_largeur - $this->marge_droite - $this->posxqtyleft), 3, $qty_left_to_ship,'','C');


					// si le module équipement est installé, on affiche d'abord en ligne les numéros de série
					if ($conf->equipement->enabled)
					{	
						$strTemp = $this->_categcompositionserial($object->lines[$i]->fk_product, $object->id, $outputlangs);
						if ($strTemp)
						{
							// si il y avait une description on fait de l'espace
							if ($object->lines[$i]->description )
							{
								$curY+=4;
								//$nexY+=4;
							}
							$curY+=4;
							$nexY+=4;
							$pdf->writeHTMLCell(120, 3, $this->posxdesc+10, $curY, $outputlangs->convToOutputCharset($strTemp), 0, 1);
							//$nexY+=4;
							$nexY+=(4*substr_count($strTemp, "<br>"));
						}
					}
					// on ajoute ici les lignes de décomposition
					$strTemp = $this->_categcomposition($object->lines[$i]->fk_product, $object->id, $outputlangs,$object->lines[$i]->qty_shipped);
					if ($strTemp)
					{
						// si il y avait une description on fait de l'espace
						if ($object->lines[$i]->description )
						{
							$curY+=4;
							//$nexY+=4;
						}

						$curY+=4;
						$nexY+=4;
						$pdf->writeHTMLCell(120, 3, $this->posxdesc+10, $curY, $outputlangs->convToOutputCharset($strTemp), 0, 1);
						//$nexY+=4;
						$nexY+=(4*substr_count($strTemp, "<br>"));
					}

					// Add line
					if (! empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblignes - 1))
					{
						$pdf->SetLineStyle(array('dash'=>'1,1','color'=>array(210,210,210)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY+1, $this->page_largeur - $this->marge_droite, $nexY+1);
						$pdf->SetLineStyle(array('dash'=>0));
					}






					// Add line
					if ($conf->global->MAIN_PDF_DASH_BETWEEN_LINES && $i < ($nblignes - 1))
					{
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash'=>'1,1','color'=>array(80,80,80)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY+1, $this->page_largeur - $this->marge_droite, $nexY+1);
						$pdf->SetLineStyle(array('dash'=>0));
					}

					$nexY+=2;    // Passe espace entre les lignes

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter)
					{
						$pdf->setPage($pagenb);
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf,$object,$outputlangs,1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
					}
					if (isset($object->lines[$i+1]->pagebreak) && $object->lines[$i+1]->pagebreak)
					{
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf,$object,$outputlangs,1);
						// New page
						$pdf->AddPage();
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						$pagenb++;
					}
				}

				// Show square
				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext - $heightforinfotot, 0, $outputlangs, 0, 0);
					$bottomlasttab=$this->page_hauteur - $heightforfooter - $heightforfreetext - $heightforinfotot + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter - $heightforfreetext - $heightforinfotot, 0, $outputlangs, 1, 0);
					$bottomlasttab=$this->page_hauteur - $heightforfooter - $heightforfreetext - $heightforinfotot + 1;
				}

				// Pied de page
				$this->_pagefoot($pdf,$object,$outputlangs);
				if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file,'F');

				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				// on remet tout en place
				$object->lines=$objectlineexpedition;

				return 1;
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->transnoentities("ErrorConstantNotDefined","EXP_OUTPUTDIR");
			return 0;
		}
		$this->error=$langs->transnoentities("ErrorUnknown");
		return 0;   // Erreur par defaut
	}

	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop=0, $hidebottom=0)
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','',$default_font_size - 1);

		// Output Rect
		$this->printRect($pdf,$this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, $hidetop, $hidebottom);	// Rect prend une longueur en 3eme param et 4eme param

		$pdf->SetDrawColor(128,128,128);
		$pdf->SetFont('','',$default_font_size - 2);

		if (empty($hidetop))
		{
			$pdf->line($this->marge_gauche, $tab_top+10, $this->page_largeur-$this->marge_droite, $tab_top+10);

			$pdf->SetXY($this->posxdesc-1, $tab_top+1);
			$pdf->MultiCell($this->posxqtyordered - $this->posxdesc, 2, $outputlangs->transnoentities("Description"), '', 'L');
		}

		$pdf->line($this->posxqtyordered-1, $tab_top, $this->posxqtyordered-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxqtyordered, $tab_top+1);
			$pdf->MultiCell(($this->posxqtytoship - $this->posxqtyordered), 2, $outputlangs->transnoentities("QtyOrdered"),'','C');
		}

		$pdf->line($this->posxqtytoship-1, $tab_top, $this->posxqtytoship-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxqtytoship, $tab_top+1);
			$pdf->MultiCell(($this->posxqtyyetship - $this->posxqtytoship), 2, $outputlangs->transnoentities("QtyToShip"),'','C');
		}
		
		$pdf->line($this->posxqtyyetship-1, $tab_top, $this->posxqtyyetship-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxqtyyetship, $tab_top+1);
			$pdf->MultiCell(($this->posxqtyleft - $this->posxqtyyetship), 2, $outputlangs->transnoentities("QtyShipped"),'','C');
		}
		
		$pdf->line($this->posxqtyleft-1, $tab_top, $this->posxqtyleft-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxqtyleft, $tab_top+1);
			$pdf->MultiCell(($this->page_largeur - $this->marge_droite - $this->posxqtyleft), 2, $outputlangs->transnoentities("KeepToShip"),'','C');
		}

	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $conf,$langs,$mysoc;
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$langs->load("orders");

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if($object->statut==0 && (! empty($conf->global->SHIPPING_DRAFT_WATERMARK)) )
		{
            pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->SHIPPING_DRAFT_WATERMARK);
		}

		//Prepare la suite
		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','B', $default_font_size + 3);

        $posx=$this->page_largeur-$this->marge_droite-100;
		$posy=$this->marge_haute;

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
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		}
		else
		{
			$text=$this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		// Show barcode
		if (! empty($conf->barcode->enabled))
		{
			$posx=105;
		}
		else
		{
			$posx=$this->marge_gauche+3;
		}
		//$pdf->Rect($this->marge_gauche, $this->marge_haute, $this->page_largeur-$this->marge_gauche-$this->marge_droite, 30);
		if (! empty($conf->barcode->enabled))
		{
			// TODO Build code bar with function writeBarCode of barcode module for sending ref $object->ref
			//$pdf->SetXY($this->marge_gauche+3, $this->marge_haute+3);
			//$pdf->Image($logo,10, 5, 0, 24);
		}

		$pdf->SetDrawColor(128,128,128);
		if (! empty($conf->barcode->enabled))
		{
			// TODO Build code bar with function writeBarCode of barcode module for sending ref $object->ref
			//$pdf->SetXY($this->marge_gauche+3, $this->marge_haute+3);
			//$pdf->Image($logo,10, 5, 0, 24);
		}


		$posx=$this->page_largeur - 100 - $this->marge_droite;
		$posy=$this->marge_haute;

		$pdf->SetFont('','B', $default_font_size + 2);
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$title=$outputlangs->transnoentities("SendingSheet");
		$pdf->MultiCell(100, 4, $title, '', 'R');
        $posy+=1;

		$pdf->SetFont('','', $default_font_size + 1);

		$posy+=4;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("RefSending") ." : ".$object->ref, '', 'R');

		// Date planned delivery
		if (! empty($object->date_delivery))
		{
    		$posy+=4;
    		$pdf->SetXY($posx,$posy);
    		$pdf->SetTextColor(0,0,60);
    		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("DateDeliveryPlanned")." : ".dol_print_date($object->date_delivery,"day",false,$outputlangs,true), '', 'R');
		}
		
		if (! empty($object->thirdparty->code_client))
		{
			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
		}


		$pdf->SetFont('','', $default_font_size + 3);
	    $Yoff=25;

	    // Add list of linked orders
	    // TODO possibility to use with other document (business module,...)
	    //$object->load_object_linked();

	    $origin 	= $object->origin;
		$origin_id 	= $object->origin_id;

	    // TODO move to external function
		if (! empty($conf->$origin->enabled))
		{
			$outputlangs->load('orders');

			$classname = ucfirst($origin);
			$linkedobject = new $classname($this->db);
			$result=$linkedobject->fetch($origin_id);
			if ($result >= 0)
			{
				$pdf->SetFont('','', $default_font_size - 2);
				$text=$linkedobject->ref;
				if ($linkedobject->ref_client) $text.=' ('.$linkedobject->ref_client.')';
				$Yoff = $Yoff+8;
				$pdf->SetXY($this->page_largeur - $this->marge_droite - 100,$Yoff);
				$pdf->MultiCell(100, 2, $outputlangs->transnoentities("RefOrder") ." : ".$outputlangs->transnoentities($text), 0, 'R');
				$Yoff = $Yoff+3;
				$pdf->SetXY($this->page_largeur - $this->marge_droite - 60,$Yoff);
				$pdf->MultiCell(60, 2, $outputlangs->transnoentities("OrderDate")." : ".dol_print_date($linkedobject->date,"day",false,$outputlangs,true), 0, 'R');
			}
		}

		if ($showaddress)
		{
			// Sender properties
			$carac_emetteur='';
		 	// Add internal contact of origin element if defined
			$arrayidcontact=array();
			if (! empty($origin) && is_object($object->$origin)) $arrayidcontact=$object->$origin->getIdContact('internal','SALESREPFOLL');
		 	if (count($arrayidcontact) > 0)
		 	{
		 		$object->fetch_user($arrayidcontact[0]);
		 		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
		 	}

		 	$carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty);

			// Show sender
			$posx=$this->marge_gauche;
			$posy=42;
			$hautcadre=40;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-80;

			// Show sender frame
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posx,$posy-5);
			$pdf->MultiCell(66,5, $outputlangs->transnoentities("Sender").":", 0, 'L');
			$pdf->SetXY($posx,$posy);
			$pdf->SetFillColor(230,230,230);
			$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);

			// Show sender name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetTextColor(0,0,60);
			$pdf->SetFont('','B',$default_font_size);
			$pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy=$pdf->getY();

			// Show sender information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+2,$posy);
			$pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');


			// If SHIPPING contact defined, we use it
			$usecontact=false;
			$arrayidcontact=$object->$origin->getIdContact('external','SHIPPING');
			if (count($arrayidcontact) > 0)
			{
				$usecontact=true;
				$result=$object->fetch_contact($arrayidcontact[0]);
			}

			//Recipient name
			// On peut utiliser le nom de la societe du contact
			if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
				$thirdparty = $object->contact;
			} else {
				$thirdparty = $object->thirdparty;
			}

			$carac_client_name= pdfBuildThirdpartyName($thirdparty, $outputlangs);

			$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->thirdparty,(!empty($object->contact)?$object->contact:null),$usecontact,'targetwithdetails');

			// Show recipient
			$widthrecbox=100;
			if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
			$posy=42;
			$posx=$this->page_largeur - $this->marge_droite - $widthrecbox;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

			// Show recipient frame
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posx,$posy-5);
			$pdf->MultiCell($widthrecbox, 4, $outputlangs->transnoentities("Recipient").":", 0, 'L');
			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);
			$pdf->SetTextColor(0,0,0);

			// Show recipient name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, 'L');

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+2,$posy);
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		}

	}

	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	void
	 */
	function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
	{
		$showdetails=0;
		return pdf_pagefoot($pdf,$outputlangs,'SHIPPING_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,$showdetails,$hidefreetext);
	}

	//  retourne la décomposition du produit si il appartient à la catégorie de décomposition 
	function _Categcomposition($fk_product, $fk_expedition, $outputlangs, $qtypere)
	{
		global $curY;
		global $nexY;

		global $db, $conf;
		$categproduct="ProduitCompose"; 
		if( $this->productinCateg($fk_product, $categproduct)>0)
		{
			// on récupère la composition du produit
			$sql  = 'SELECT p.rowid, p.ref, p.label, pa.qty FROM '.MAIN_DB_PREFIX.'product_association AS pa, '.MAIN_DB_PREFIX.'product AS p';
			$sql .= ' WHERE p.rowid = pa.fk_product_fils ';
			$sql .= ' AND pa.fk_product_pere = '.$fk_product;

			dol_syslog("PDF_Expedition_Rouget_composition::_categcompositionserial - SQL = ".$sql);
			$result=$db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);
				if ($num>0) {
					$retStr = "";
					for ($i=0; $i< $num; $i++)
					{
						$objp = $db->fetch_object($result);
						$prodser = new Product($db);
						$prodser->fetch($objp->rowid);
						$label = $objp->label;
						if (! empty($prodser->multilangs[$outputlangs->defaultlang]["label"]) && $label == $prodser->label)
							$label=$prodser->multilangs[$outputlangs->defaultlang]["label"];
						
						$retStr .= '&nbsp;&nbsp;'.$objp->qty.'&nbsp;-&nbsp;'.$objp->ref.'&nbsp;:&nbsp;'.$label.'<br>';
						// on affiche les numéros de série des composants
						if( $this->productinCateg($objp->rowid, "ProduitSerial")>0)
						{
							$serial=$this->_Categcompositionserial($objp->rowid, $fk_expedition, $outputlangs, $fk_product);
							if ($serial)
							{	
								$retStr .=$serial.'<br>';
							}
						}
					}
					$curY = $curY+ 4*($num);
					$nexY = $nexY+ 4*($num);
					// pour virer le dernier <br>
					//$retStr=substr($retStr,0,-4);
					return $retStr;
				}
			}
		}
//		else
//			print "dfsdfs";
		// dans tous les autres cas on affiche rien
		return "";
	}

	//  retourne la décomposition du produit et les numéro de série si il appartient à la catégorie de décomposition équipement
	function _Categcompositionserial($fk_product, $fk_expedition, $outputlangs, $fk_product_pere=0)
	{

		global $conf, $object;
		$categproduct="ProduitSerial"; 
		if( $this->productinCateg($fk_product, $categproduct)>0)
		{
			
			if ($fk_product_pere > 0)
			{	// pour gérer les numéros de série des composants
				$sql  = 'SELECT eqfils.rowid, eqfils.ref FROM '.MAIN_DB_PREFIX.'equipement AS eqfils, '.MAIN_DB_PREFIX.'equipementassociation AS ea';
				$sql .= ', '.MAIN_DB_PREFIX.'equipement AS eqpere, '.MAIN_DB_PREFIX.'equipementevt AS eqv ';
				$sql .= ' WHERE eqfils.rowid = ea.fk_equipement_fils ';
				$sql .= ' AND eqpere.rowid = ea.fk_equipement_pere';
				$sql .= ' AND ea.fk_equipement_pere = eqv.fk_equipement ';
				$sql .= ' AND eqv.fk_expedition = '.$fk_expedition;
				$sql .= ' AND eqfils.fk_product = '.$fk_product;
				$sql .= ' AND eqpere.fk_product = '.$fk_product_pere;
				$decal= '&nbsp;&nbsp;&nbsp;&nbsp;';
			}
			else
			{	// on récupère les numéros de série du produit
				$sql  = 'SELECT eq.rowid, eq.ref FROM '.MAIN_DB_PREFIX.'equipement AS eq, '.MAIN_DB_PREFIX.'equipementevt AS eqv ';
				$sql .= ' WHERE eq.rowid = eqv.fk_equipement ';
				$sql .= ' AND eqv.fk_expedition = '.$fk_expedition;
				$sql .= ' AND eq.fk_product = '.$fk_product;
				$decal="";
			}
			dol_syslog("PDF_Expedition_Rouget_composition::_categcompositionserial - SQL = ".$sql);
			
			$result=$this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);
				if ($num>0) {
					$retStrcs = $decal.$outputlangs->trans("EquipRef").' : ';
					for ($i=0; $i< $num; $i++){
						$objp = $this->db->fetch_object($result);
						$retStrcs .= $objp->ref.', ';
					}

					// pour virer la derniere virgule
					$retStrcs=substr($retStrcs,0,-2);
					return $retStrcs.'<br>';
				}
			}
		}
		else
		// dans tous les autres cas on affiche rien
		return "";
	}

	// retourne 1 si le produit est dans la catégorie, sinon 0
	function productinCateg($fk_product, $categproduct)
	{
		if($fk_product)
		{
			$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'categorie as c,'.MAIN_DB_PREFIX.'categorie_product  AS cp';
			$sql.= ' WHERE c.rowid = cp.fk_categorie';
			$sql.= ' AND c.label = "'.$categproduct.'" ';
			$sql.= ' AND cp.fk_product = '.$fk_product;
			dol_syslog("PDF_Expedition_Rouget_composition::productinCateg - SQL = ".$sql);
			$result=$this->db->query($sql);
			if ($result) 
				return $this->db->num_rows($result);
		}
		return 0;
	}
}

