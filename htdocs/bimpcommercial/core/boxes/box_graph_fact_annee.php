<?php
/* Copyright (C) 2013-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *	\file       htdocs/core/boxes/box_graph_product_distribution.php
 *	\ingroup    factures
 *	\brief      Box to show graph of invoices per month
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

/**
 * Class to manage the box to show last invoices
 */
class box_graph_fact_annee extends ModeleBoxes
{
	var $boxcode="factparan";
	var $boximg="object_bill";
	var $boxlabel="Box Fact";
	var $depends = array("product|service","facture|propal|commande");

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 * 	@param	DoliDB	$db			Database handler
	 *  @param	string	$param		More parameters
	 */
	function __construct($db,$param)
	{
		global $user, $conf;

		$this->db=$db;

		$this->hidden = ! (
		    (! empty($conf->facture->enabled) && ! empty($user->rights->facture->lire))
		 || (! empty($conf->commande->enabled) && ! empty($user->rights->commande->lire))
		 || (! empty($conf->propal->enabled) && ! empty($user->rights->propale->lire))
		);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
		global $conf, $user, $langs, $db;

		$this->max=$max;

		$refreshaction='refresh_'.$this->boxcode;

		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
		include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

		$param_year='DOLUSERCOOKIE_box_'.$this->boxcode.'_year';
		$param_showinvoicenb='DOLUSERCOOKIE_box_'.$this->boxcode.'_showinvoicenb';
		$param_showpropalnb='DOLUSERCOOKIE_box_'.$this->boxcode.'_showpropalnb';
		$param_showordernb='DOLUSERCOOKIE_box_'.$this->boxcode.'_showordernb';
		$autosetarray=preg_split("/[,;:]+/",GETPOST('DOL_AUTOSET_COOKIE'));
		if (in_array('DOLUSERCOOKIE_box_'.$this->boxcode,$autosetarray))
		{
			$year=GETPOST($param_year,'int');
			$showinvoicenb=GETPOST($param_showinvoicenb,'alpha');
			$showpropalnb=GETPOST($param_showpropalnb,'alpha');
			$showordernb=GETPOST($param_showordernb,'alpha');
		}
		else
		{
			$tmparray=json_decode($_COOKIE['DOLUSERCOOKIE_box_'.$this->boxcode],true);
			$year=$tmparray['year'];
			$showinvoicenb=$tmparray['showinvoicenb'];
			$showpropalnb=$tmparray['showpropalnb'];
			$showordernb=$tmparray['showordernb'];
		}
		if (empty($showinvoicenb) && empty($showpropalnb) && empty($showordernb)) { $showpropalnb=1; $showinvoicenb=1; $showordernb=1; }
		if (empty($conf->facture->enabled) || empty($user->rights->facture->lire)) $showinvoicenb=0;
		if (empty($conf->propal->enabled) || empty($user->rights->propale->lire)) $showpropalnb=0;
		if (empty($conf->commande->enabled) || empty($user->rights->commande->lire)) $showordernb=0;

		$nowarray=dol_getdate(dol_now(),true);
		if (empty($year)) $year=$nowarray['year'];

		$nbofgraph=2;

		$text = $langs->trans("Facture par Statut Paiement",$max).' - '.$langs->trans("Year").': '.$year;
		$this->info_box_head = array(
				'text' => $text,
				'limit'=> dol_strlen($text),
				'graph'=> 1,
				'sublink'=>'',
				'subtext'=>$langs->trans("Filter"),
				'subpicto'=>'filter.png',
				'subclass'=>'linkobject boxfilter',
				'target'=>'none'	// Set '' to get target="_blank"
		);


		$paramtitle='Nombre de piéces';

		$socid=empty($user->societe_id)?0:$user->societe_id;
		$userid=0;	// No filter on user creation

		$WIDTH=($nbofgraph >= 2 || ! empty($conf->dol_optimize_smallscreen))?'160':'320';
		$HEIGHT='192';

		if (! empty($conf->facture->enabled) && ! empty($user->rights->facture->lire))
		{

			// Build graphic number of object. $data = array(array('Lib',val1,val2,val3),...)
			if ($showinvoicenb)
			{
				include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';
                                
                                $sql = $db->query("SELECT SUM(total_ttc) as tot, SUM(`remain_to_pay`) as totIP, COUNT(*) as nb, SUM(paye) as nbP, SUM(IF(remain_to_pay != total_ttc || remain_to_pay = 0 ,1,0)) as nbPart FROM `llx_facture` WHERE YEAR( datef ) = '".$year."'");
                                $ln = $db->fetch_object($sql);
                                $data1 = array(array('Payée', $ln->nbP), array('Impayée',($ln->nb - $ln->nbP)), array('Partiellement payée',$ln->nbPart - $ln->nbP));
				$showpointvalue = 1; $nocolor = 0;

				$filenamenb = $dir."/invoiceparan-".$year.".png";
				$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=productstats&amp;file=invoiceparan-'.$year.'.png';

				$px1 = new DolGraph();
				$mesg = $px1->isGraphKo();
				if (! $mesg)
				{
					$i=0;$tot=count($data1);$legend=array();
					while ($i <= $tot)
					{
						$data1[$i][0]=dol_trunc($data1[$i][0],5);	// Required to avoid error "Could not draw pie with labels contained inside canvas"
						$legend[]=$data1[$i][0];
						$i++;
					}

					$px1->SetData($data1);
					unset($data1);

					$px1->SetDataColor(array(array(52,187,89), array(243,57,133), array(243,187,57)));
					$px1->SetPrecisionY(0);
					$px1->SetLegend($legend);
					$px1->setShowLegend(0);
					$px1->setShowPointValue($showpointvalue);
					$px1->setShowPercent(0);
					$px1->SetMaxValue($px1->GetCeilMaxValue());
					$px1->SetWidth($WIDTH);
					$px1->SetHeight($HEIGHT);
					//$px1->SetYLabel($langs->trans("NumberOfBills"));
					$px1->SetShading(3);
					$px1->SetHorizTickIncrement(1);
					$px1->SetPrecisionY(0);
					$px1->SetCssPrefix("cssboxes");
					//$px1->mode='depth';
					$px1->SetType(array('pie'));
					$px1->SetTitle('Nombre de piéces');
					$px1->combine = 0.05;

					$px1->draw($filenamenb,$fileurlnb);
				}
                                
                                $showpointvalue = 1; $nocolor = 0;
                                $unit = '€';
                                $data2 = array(array('Payée', $ln->tot - $ln->totIP), array('Impayée',$ln->totIP));
                                if($data2[0][1] > 100000){
                                    $unit = 'K€';
                                    $data2[0][1] = $data2[0][1]/1000;
                                    $data2[1][1] = $data2[1][1]/1000;
                                }
                                if($data2[0][1] > 100000){
                                    $unit = 'M€';
                                    $data2[0][1] = $data2[0][1]/1000;
                                    $data2[1][1] = $data2[1][1]/1000;
                                }
                                $data2[0][1] = round($data2[0][1]);
                                $data2[1][1] = round($data2[1][1]);
                                print_r($data2);
				if (empty($data2))
				{
					$showpointvalue = 0;
					$nocolor = 1;
					$data2=array(array(0=>$langs->trans("None"),1=>1));
				}

				$filenamenb = $dir."/invoiceparan2-".$year.".png";
				$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=productstats&amp;file=invoiceparan2-'.$year.'.png';

				$px2 = new DolGraph();
				$mesg = $px2->isGraphKo();
				if (! $mesg)
				{
					$i=0;$tot=count($data2);$legend=array();
					while ($i <= $tot)
					{
						$data2[$i][0]=dol_trunc($data2[$i][0],5);	// Required to avoid error "Could not draw pie with labels contained inside canvas"
						$legend[]=$data2[$i][0];
						$i++;
					}

					$px2->SetData($data2);
					unset($data2);

					$px2->SetDataColor(array(array(52,187,89), array(243,57,133)));
					$px2->SetPrecisionY(0);
					$px2->SetLegend($legend);
					$px2->setShowLegend(0);
					$px2->setShowPointValue($showpointvalue);
					$px2->setShowPercent(0);
					$px2->SetMaxValue($px2->GetCeilMaxValue());
					$px2->SetWidth($WIDTH);
					$px2->SetHeight($HEIGHT);
					//$px2->SetYLabel($langs->trans("AmountOfBillsHT"));
					$px2->SetShading(3);
					$px2->SetHorizTickIncrement(1);
					$px2->SetPrecisionY(0);
					$px2->SetCssPrefix("cssboxes");
					//$px2->mode='depth';
					$px2->SetType(array('pie'));
					$px2->SetTitle('En '.$unit.' TTC');
					$px2->combine = 0.05;

					$px2->draw($filenamenb,$fileurlnb);
				}
			}
		}


		if (empty($nbofgraph))
		{
		    $langs->load("errors");
		    $mesg=$langs->trans("ReadPermissionNotAllowed");
		}
		if (empty($conf->use_javascript_ajax))
		{
			$langs->load("errors");
			$mesg=$langs->trans("WarningFeatureDisabledWithDisplayOptimizedForBlindNoJs");
		}

		if (! $mesg)
		{
			$stringtoshow='';
			$stringtoshow.='<script type="text/javascript" language="javascript">
				jQuery(document).ready(function() {
					jQuery("#idsubimg'.$this->boxcode.'").click(function() {
						jQuery("#idfilter'.$this->boxcode.'").toggle();
					});
				});
			</script>';
			$stringtoshow.='<div class="center hideobject" id="idfilter'.$this->boxcode.'">';	// hideobject is to start hidden
			$stringtoshow.='<form class="flat formboxfilter" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			$stringtoshow.='<input type="hidden" name="action" value="'.$refreshaction.'">';
			$stringtoshow.='<input type="hidden" name="page_y" value="">';
			$stringtoshow.='<input type="hidden" name="DOL_AUTOSET_COOKIE" value="DOLUSERCOOKIE_box_'.$this->boxcode.':year,showinvoicenb,showpropalnb,showordernb">';
			if (! empty($conf->facture->enabled) || ! empty($user->rights->facture->lire))
			{
				$stringtoshow.='<input type="checkbox" name="'.$param_showinvoicenb.'"'.($showinvoicenb?' checked':'').'> '.$langs->trans("ForCustomersInvoices");
				$stringtoshow.=' &nbsp; ';
			}
			if (! empty($conf->propal->enabled) || ! empty($user->rights->propale->lire))
			{
				$stringtoshow.='<input type="checkbox" name="'.$param_showpropalnb.'"'.($showpropalnb?' checked':'').'> '.$langs->trans("ForProposals");
				$stringtoshow.='&nbsp;';
			}
			if (! empty($conf->commande->enabled) || ! empty($user->rights->commande->lire))
			{
				$stringtoshow.='<input type="checkbox" name="'.$param_showordernb.'"'.($showordernb?' checked':'').'> '.$langs->trans("ForCustomersOrders");
			}
			$stringtoshow.='<br>';
			$stringtoshow.=$langs->trans("Year").' <input class="flat" size="4" type="text" name="'.$param_year.'" value="'.$year.'">';
			$stringtoshow.='<input type="image" class="reposition inline-block valigntextbottom" alt="'.$langs->trans("Refresh").'" src="'.img_picto('','refresh.png','','',1).'">';
			$stringtoshow.='</form>';
			$stringtoshow.='</div>';

                        
                        $stringtoshow.='<div class="fichecenter"><div class="containercenter"><div class="fichehalfleft">';
                        $stringtoshow.=$px1->show();
                        $stringtoshow.='</div><div class="fichehalfright">';
                        $stringtoshow.=$px2->show();
                        $stringtoshow.='</div></div></div>';
                                
			$this->info_box_contents[0][0] = array('tr'=>'class="oddeven nohover"', 'td' => 'align="center" class="nohover"','textnoformat'=>$stringtoshow);
		}
		else
		{
			$this->info_box_contents[0][0] = array(
			    'td' => 'align="left" class="nohover opacitymedium"',
				'maxlength'=>500,
				'text' => $mesg
			);
		}

	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
    function showBox($head = null, $contents = null, $nooutput=0)
    {
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}

}

