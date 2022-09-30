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
class box_bimp_core extends ModeleBoxes
{
        static $idBox = 0;
	var $boxcode="boxbimpcore";
	var $boximg="object_bill";
	var $boxlabel="Box";

	var $db;
	var $param;

	var $info_box_head = array();
	var $info_box_contents = array();
        
        var $config = array();
        var $confUser = array();
        var $erreurs = array();
        var $camenberes = array();
        var $indicateurs = array();
        var $lists = array();
        
        var $classObj = null;
        var $methode = '';


	/**
	 *  Constructor
	 *
	 * 	@param	DoliDB	$db			Database handler
	 *  @param	string	$param		More parameters
	 */
	function __construct($db,$param)
	{
            $this->db=$db;
            $this->param=$param;                
                
            $infos = json_decode($this->param, true);
            if(is_array($infos) && isset($infos['module']) && isset($infos['object']) & isset($infos['methode'])){
                $this->classObj = BimpObject::getInstance($infos['module'], $infos['object']);
                if(method_exists($this->classObj, $infos['methode'])){
                    $this->methode = $infos['methode'];
                    $result = call_user_func(array($this->classObj, $this->methode),$this, 'init');
                    if(!$result)
                        $this->erreurs[] = 'Probléme inititalisation box';
                }
                else{
                    $this->erreurs[] = 'Methode '.$infos['methode'].' inexistante dans '.$infos['module'] .'/'. $infos['object'];
                }
            }
            else{
                $this->erreurs[] = 'Probléme de config box';
            }
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
            global $langs, $conf;
            $this->erreurs = array();
            
//            $this->boxlabel = 'Box';
            
            $stringgraph = $stringtext = '';
            
            static::$idBox++;
            $this->boxcode .= static::$idBox;
            $clef = 'boxConf'.$this->boxcode;
            foreach($_REQUEST as $name => $val){
                if(stripos($name, $clef) !== false){
                    $_SESSION['boxConf'][$this->boxcode][str_replace($clef, '', $name)] = $val;
                }
            }
            
            if(is_object($this->classObj)){
                $this->confUser = $_SESSION['boxConf'][$this->boxcode];
                $result = call_user_func(array($this->classObj, $this->methode),$this, 'load');
                if(!$result)
                    $this->erreurs[] = 'Probléme load box';
                else{
                    if(count($this->camenberes)){
                        $htmlGraph = array();
                        $WIDTH=(count(result['graphs']) >= 2 || ! empty($conf->dol_optimize_smallscreen))?'160':'320';
                        $HEIGHT='192';
                        $i = 0;

                        foreach($this->camenberes as $dataGraph){
                            $i++;
                            $htmlGraph[] = $this->getCamemberre($clef.$i, $dataGraph, $WIDTH, $HEIGHT);
                        }

                        $stringgraph.=implode('</div><div class="fichehalfright">', $htmlGraph);


                        foreach($this->indicateurs as $indicateur){
                            $stringtext .= $this->getIndicateur($indicateur);
                        }
                    }
                    
                    if(count($this->lists)){
                        foreach($this->lists as $list){
//                            echo '<pre>';   print_r($list['data']);
                            $stringtext .= BimpRender::renderBimpListTable($list['data'], $list['legend']);
                        }
                    }
                }

            }
            else{
                $this->erreurs[] = 'Class inexistante';
            }
            
            
            $this->info_box_head = array(
				'text' => $this->boxlabel,
				'limit'=> dol_strlen($this->boxlabel),
				'graph'=> 1,
				'sublink'=>'',
				'subtext'=>$langs->trans("Filter"),
				'subpicto'=>'filter.png',
				'subclass'=>'linkobject boxfilter',
				'target'=>'none'	// Set '' to get target="_blank"
		);
            
            
            
            if(count($this->erreurs)){
                $this->info_box_contents[0][0] = array('tr'=>'class="oddeven nohover"', 'td' => 'align="center" class="nohover"','textnoformat'=>$this->presenteBox(BimpRender::renderAlerts($this->erreurs), ''));
                return 0;
            }
            $this->info_box_contents[0][0] = array('tr'=>'class="oddeven nohover"', 'td' => 'align="center" class="nohover"','textnoformat'=>$this->presenteBox($stringgraph, $stringtext));
            return 1;
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
        
        
    private function presenteBox($strGraph, $strText){
        global $langs, $conf, $user;
        $stringtoshow='';
        $stringtoshow.='<script type="text/javascript" language="javascript">
                jQuery(document).ready(function() {
                        jQuery("#idsubimg'.$this->boxcode.'").click(function() {
                                jQuery("#idfilter'.$this->boxcode.'").toggle();
                        });
                });
        </script>';
        
        $refreshaction='refresh_'.$this->boxcode;
        $stringtoshow.='<div class="center hideobject" id="idfilter'.$this->boxcode.'">';	// hideobject is to start hidden
        $stringtoshow.='<form class="flat formboxfilter" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
        $stringtoshow.='<input type="hidden" name="action" value="'.$refreshaction.'">';
        $stringtoshow.='<input type="hidden" name="page_y" value="">';

        foreach($this->config as $name => $confData){
            $value = '';
            $text = '';
            $stringtoshowTmp = '';
            if(isset($confData['title']))
                $text = $confData['title'];
            if(isset($this->confUser[$name]))
                $value = $this->confUser[$name];
            elseif(isset($confData['val_default']))
                $value = $confData['val_default'];
            if($confData['type'] == 'year'){
                if($text == '')
                    $text = $langs->trans("Year");
                $stringtoshowTmp.= '<input class="flat" size="4" type="text" name="'.'boxConf'.$this->boxcode.$name.'" value="'.$value.'">';
            }
            elseif($confData['type'] == 'int'){
                $stringtoshowTmp.= '<input class="flat" size="4" type="text" name="'.'boxConf'.$this->boxcode.$name.'" value="'.$value.'">';
            }
            elseif($confData['type'] == 'bool'){
                $stringtoshowTmp.= '<input class="flat" size="4" type="checkbox" name="'.'boxConf'.$this->boxcode.$name.'"';
                if($value)
                    $stringtoshowTmp.= ' checked="checked"';
                $stringtoshowTmp.= '>';
            }
            elseif($confData['type'] == 'radio'){
                $stringtoshowTmp .= ' :';
                foreach($confData['values'] as $val => $label){
                    $stringtoshowTmp .= '<input type="radio" id="dewey" name="'.'boxConf'.$this->boxcode.$name.'" value="'.$val.'"';
                    if($value == $val)
                        $stringtoshowTmp.= ' checked="checked"';
                    $stringtoshowTmp.= '> '.$label.' ';
                }
            }
                    
            $stringtoshow .= $text.' '.$stringtoshowTmp.'<br/>';
        }
        $stringtoshow.='<input type="image" class="reposition inline-block valigntextbottom" alt="'.$langs->trans("Refresh").'" src="'.img_picto('','refresh.png','','',1).'">';
        $stringtoshow.='</form>';
        $stringtoshow.='</div>';


        if($strGraph != ''){
            $stringtoshow.='<div class="fichecenter"><div class="containercenter"><div class="fichehalfleft">';
            $stringtoshow.=$strGraph;
            $stringtoshow.='</div></div></div>';
        }
        if($strText != ''){
            $stringtoshow.='<div class="tdboxstats nohover flexcontainer centpercent">';
            $stringtoshow.=$strText;
            $stringtoshow.='</div>';
        }
        return $stringtoshow;
    }
    
    private function getCamemberre($clef, $dataGraph, $WIDTH, $HEIGHT){
        $showpointvalue = 1; 
        $nocolor = 1;
        foreach($dataGraph['data'] as $temp)
            if(isset($temp[2]))
                $nocolor = 0;

        $filenamenb = DOL_DATA_ROOT."/product/".$clef."-"/*.$year*/.".png";//ne semble servire a rien
        $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=product&amp;file='.$clef.'-'/*.$year*/.'.png';//ne semble servire a rien

        $px1 = new DolGraph();
        $mesg = $px1->isGraphKo();
        if (! $mesg)
        {
            $i=0;$tot=count($dataGraph['data'])-1;$legend=array();
            $data1 = $color = array();
            while ($i <= $tot)
            {
                    $data1[$i][0]=dol_trunc($dataGraph['data'][$i][0],5);	// Required to avoid error "Could not draw pie with labels contained inside canvas"
                    $data1[$i][1]=$dataGraph['data'][$i][1];
                    if(!$nocolor)
                        $color[$i] = $dataGraph['data'][$i][2];
                    $legend[]=$data1[$i][0];
                    $i++;
            }
            $px1->SetData($data1);
            unset($data1);

            if(!$nocolor)
            $px1->SetDataColor($color);
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
            $px1->SetTitle($dataGraph['titre']);
            $px1->combine = 0.05;

            $px1->draw($filenamenb,$fileurlnb);
            return $px1->show();
        }
        return $mesg;                  
    }
    
    function addCamenbere($titre, $data){
        if(count($data) > 0)
            $this->camenberes[] = array('titre'=>$titre, 'data'=>$data);
        else{
            BimpCore::addlog('Ajout de camenbere sans data '.$titre);
        }
    }
    
    function addList($legend, $data){
        $this->lists[] = array('legend'=>$legend, 'data'=>$data);
    }
    
    function addIndicateur($text, $value, $url = null, $title = null, $alert = null, $urlAlert = null, $titleAlert = null, $img = null){
        if(is_null($titleAlert))
            $titleAlert = $alert.' en retard';
        $this->indicateurs[] = array('text'=>$text, 'value'=>$value, 'url'=>$url, 'title'=>$title, 'img'=>$img, 'alert'=>$alert, 'urlAlert'=>$urlAlert, 'titleAlert'=>$titleAlert);
    }
    
    private function getIndicateur($indicateur){//minimum text et value
        $return = '';
        $return .= '<div class="boxstatsindicator thumbstat150 nobold nounderline""><div class="boxstatsborder"><div class="boxstatscontent">';
        $return .= '';
        $return .= '<div class="boxstats"><span class="boxstatstext"';
        if(isset($indicateur['title']))
            $return .= 'title="Commandes fournisseurs"';
        $return .= '>'.$indicateur['text'].'</span><br>';
        if(isset($indicateur['img']))
            $return .= '<img src="'.$indicateur['img'].'" alt="" class="inline-block"> ';
        if(isset($indicateur['url']))
                $return .= '<a href="'.$indicateur['url'].'">';
        $return .= '<span class="boxstatsindicator">'.$indicateur['value'].'</span>';
        if(isset($indicateur['url']))
                $return .= '</a>';
        $return .= '</div></div>';
        if(isset($indicateur['alert'])){
            $return .= '<div class="dashboardlinelatecoin nowrap">';
            if(isset($indicateur['urlAlert']))
                $return .= '<a title="'.$indicateur['titleAlert'].'" class="valignmiddle dashboardlineindicatorlate dashboardlineko" href="'.$indicateur['urlAlert'].'">';
            $return .= '<img src="'.DOL_URL_ROOT.'/theme/eldy/img/warning_white.png" alt="" title="'.$indicateur['titleAlert'].'" class="inline-block hideonsmartphone valigntextbottom"><span class="dashboardlineindicatorlate dashboardlineko">'.$indicateur['alert'].'</span>';
            if(isset($indicateur['urlAlert']))
                $return .= '</a>';
            $return .= '</div>';
        }
        $return .= '</div></div>';
        return $return;
    }

}

