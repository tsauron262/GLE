<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier        <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien Di Cintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2006      Marc Barilley/Ocebo   <marc@ocebo.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2007      Patrick Raguin        <patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010      Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2011      Herve Prot            <herve.prot@symeos.com>
 * Copyright (C) 2012      Marcos García         <marcosgdf@gmail.com>
 * Copyright (C) 2013      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
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
 *	\file       htdocs/core/class/html.form.class.php
 *  \ingroup    core
 *	\brief      File of class with all html predefined components
 */


/**
 *	Class to manage generation of HTML components
 *	Only common components must be here.
 */
class Form
{
    var $db;
    var $error;
    var $num;

    // Cache arrays
    var $cache_types_paiements=array();
    var $cache_conditions_paiements=array();
    var $cache_availability=array();
    var $cache_demand_reason=array();
    var $cache_types_fees=array();
    var $cache_vatrates=array();

    var $tva_taux_value;
    var $tva_taux_libelle;


    /**
     * Constructor
     *
     * @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Output key field for an editable field
     *
     * @param   string	$text			Text of label or key to translate
     * @param   string	$htmlname		Name of select field
     * @param   string	$preselected	Name of Value to show/edit (not used in this function)
     * @param	object	$object			Object
     * @param	boolean	$perm			Permission to allow button to edit parameter
     * @param	string	$typeofdata		Type of data ('string' by default, 'email', 'numeric:99', 'text' or 'textarea:rows:cols', 'day' or 'datepicker', 'ckeditor:dolibarr_zzz:width:height:savemethod:1:rows:cols', 'select;xxx[:class]'...)
     * @param	string	$moreparam		More param to add on a href URL
     * @return	string					HTML edit field
     */
    function editfieldkey($text, $htmlname, $preselected, $object, $perm, $typeofdata='string', $moreparam='')
    {
        global $conf,$langs;

        $ret='';

        // TODO change for compatibility
        if (! empty($conf->global->MAIN_USE_JQUERY_JEDITABLE) && ! preg_match('/^select;/',$typeofdata))
        {
            if (! empty($perm))
            {
                $tmp=explode(':',$typeofdata);
                $ret.= '<div class="editkey_'.$tmp[0].(! empty($tmp[1]) ? ' '.$tmp[1] : '').'" id="'.$htmlname.'">';
                $ret.= $langs->trans($text);
                $ret.= '</div>'."\n";
            }
            else
            {
                $ret.= $langs->trans($text);
            }
        }
        else
        {
            $ret.='<table class="nobordernopadding" width="100%"><tr><td class="nowrap">';
            $ret.=$langs->trans($text);
            $ret.='</td>';
            if (GETPOST('action') != 'edit'.$htmlname && $perm) $ret.='<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=edit'.$htmlname.'&amp;id='.$object->id.$moreparam.'">'.img_edit($langs->trans('Edit'),1).'</a></td>';
            $ret.='</tr></table>';
        }

        return $ret;
    }

    /**
     * Output val field for an editable field
     *
     * @param	string	$text			Text of label (not used in this function)
     * @param	string	$htmlname		Name of select field
     * @param	string	$value			Value to show/edit
     * @param	object	$object			Object
     * @param	boolean	$perm			Permission to allow button to edit parameter
     * @param	string	$typeofdata		Type of data ('string' by default, 'email', 'numeric:99', 'text' or 'textarea:rows:cols', 'day' or 'datepicker', 'ckeditor:dolibarr_zzz:width:height:savemethod:toolbarstartexpanded:rows:cols', 'select:xxx'...)
     * @param	string	$editvalue		When in edit mode, use this value as $value instead of value
     * @param	object	$extObject		External object
     * @param	string	$success		Success message
     * @param	string	$moreparam		More param to add on a href URL
     * @return  string					HTML edit field
     */
    function editfieldval($text, $htmlname, $value, $object, $perm, $typeofdata='string', $editvalue='', $extObject=null, $success=null, $moreparam='')
    {
        global $conf,$langs,$db;

        $ret='';

        // When option to edit inline is activated
        if (! empty($conf->global->MAIN_USE_JQUERY_JEDITABLE) && ! preg_match('/^select;|datehourpicker/',$typeofdata)) // FIXME add jquery timepicker
        {
            $ret.=$this->editInPlace($object, $value, $htmlname, $perm, $typeofdata, $editvalue, $extObject, $success);
        }
        else
        {
            if (GETPOST('action') == 'edit'.$htmlname)
            {
                $ret.="\n";
                $ret.='<form method="post" action="'.$_SERVER["PHP_SELF"].($moreparam?'?'.$moreparam:'').'">';
                $ret.='<input type="hidden" name="action" value="set'.$htmlname.'">';
                $ret.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                $ret.='<input type="hidden" name="id" value="'.$object->id.'">';
                $ret.='<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
                $ret.='<tr><td>';
                if (preg_match('/^(string|email|numeric)/',$typeofdata))
                {
                    $tmp=explode(':',$typeofdata);
                    $ret.='<input type="text" id="'.$htmlname.'" name="'.$htmlname.'" value="'.($editvalue?$editvalue:$value).'"'.($tmp[1]?' size="'.$tmp[1].'"':'').'>';
                }
                else if (preg_match('/^text/',$typeofdata) || preg_match('/^note/',$typeofdata))
                {
                    $tmp=explode(':',$typeofdata);
                    $ret.='<textarea id="'.$htmlname.'" name="'.$htmlname.'" wrap="soft" rows="'.($tmp[1]?$tmp[1]:'20').'" cols="'.($tmp[2]?$tmp[2]:'100').'">'.($editvalue?$editvalue:$value).'</textarea>';
                }
                else if ($typeofdata == 'day' || $typeofdata == 'datepicker')
                {
                    $ret.=$this->form_date($_SERVER['PHP_SELF'].'?id='.$object->id,$value,$htmlname);
                }
                else if ($typeofdata == 'datehourpicker')
                {
                	$ret.=$this->form_date($_SERVER['PHP_SELF'].'?id='.$object->id,$value,$htmlname,1,1);
                }
                else if (preg_match('/^select;/',$typeofdata))
                {
                     $arraydata=explode(',',preg_replace('/^select;/','',$typeofdata));
                     foreach($arraydata as $val)
                     {
                         $tmp=explode(':',$val);
                         $arraylist[$tmp[0]]=$tmp[1];
                     }
                     $ret.=$this->selectarray($htmlname,$arraylist,$value);
                }
                else if (preg_match('/^ckeditor/',$typeofdata))
                {
                    $tmp=explode(':',$typeofdata);
                    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
                    $doleditor=new DolEditor($htmlname, ($editvalue?$editvalue:$value), ($tmp[2]?$tmp[2]:''), ($tmp[3]?$tmp[3]:'100'), ($tmp[1]?$tmp[1]:'dolibarr_notes'), 'In', ($tmp[5]?$tmp[5]:0), true, true, ($tmp[6]?$tmp[6]:'20'), ($tmp[7]?$tmp[7]:'100'));
                    $ret.=$doleditor->Create(1);
                }
                $ret.='</td>';
                if ($typeofdata != 'day' && $typeofdata != 'datepicker' && $typeofdata != 'datehourpicker') $ret.='<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
                $ret.='</tr></table>'."\n";
                $ret.='</form>'."\n";
            }
            else
            {
                if ($typeofdata == 'email')   $ret.=dol_print_email($value,0,0,0,0,1);
                elseif (preg_match('/^text/',$typeofdata) || preg_match('/^note/',$typeofdata))  $ret.=dol_htmlentitiesbr($value);
                elseif ($typeofdata == 'day' || $typeofdata == 'datepicker') $ret.=dol_print_date($value,'day');
                elseif ($typeofdata == 'datehourpicker') $ret.=dol_print_date($value,'dayhour');
                else if (preg_match('/^select;/',$typeofdata))
                {
                    $arraydata=explode(',',preg_replace('/^select;/','',$typeofdata));
                    foreach($arraydata as $val)
                    {
                        $tmp=explode(':',$val);
                        $arraylist[$tmp[0]]=$tmp[1];
                    }
                    $ret.=$arraylist[$value];
                }
                else if (preg_match('/^ckeditor/',$typeofdata))
                {
                    $tmpcontent=dol_htmlentitiesbr($value);
                    if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB))
                    {
                        $firstline=preg_replace('/<br>.*/','',$tmpcontent);
                        $firstline=preg_replace('/[\n\r].*/','',$firstline);
                        $tmpcontent=$firstline.((strlen($firstline) != strlen($tmpcontent))?'...':'');
                    }
                    $ret.=$tmpcontent;
                }
                else $ret.=$value;
            }
        }
        return $ret;
    }

    /**
     * Output edit in place form
     *
     * @param	object	$object			Object
     * @param	string	$value			Value to show/edit
     * @param	string	$htmlname		DIV ID (field name)
     * @param	int		$condition		Condition to edit
     * @param	string	$inputType		Type of input ('numeric', 'datepicker', 'textarea:rows:cols', 'ckeditor:dolibarr_zzz:width:height:?:1:rows:cols', 'select:xxx')
     * @param	string	$editvalue		When in edit mode, use this value as $value instead of value
     * @param	object	$extObject		External object
     * @param	string	$success		Success message
     * @return	string   		      	HTML edit in place
     */
    private function editInPlace($object, $value, $htmlname, $condition, $inputType='textarea', $editvalue=null, $extObject=null, $success=null)
    {
        global $conf;

        $out='';

        // Check parameters
        if ($inputType == 'textarea') $value = dol_nl2br($value);
        else if (preg_match('/^numeric/',$inputType)) $value = price($value);
        else if ($inputType == 'datepicker') $value = dol_print_date($value, 'day');

        if ($condition)
        {
            $element		= false;
            $table_element	= false;
            $fk_element		= false;
            $loadmethod		= false;
            $savemethod		= false;
            $ext_element	= false;
            $button_only	= false;

            if (is_object($object))
            {
                $element = $object->element;
                $table_element = $object->table_element;
                $fk_element = $object->id;
            }

            if (is_object($extObject))
            {
                $ext_element = $extObject->element;
            }

            if (preg_match('/^(string|email|numeric)/',$inputType))
            {
                $tmp=explode(':',$inputType);
                $inputType=$tmp[0];
                if (! empty($tmp[1])) $inputOption=$tmp[1];
                if (! empty($tmp[2])) $savemethod=$tmp[2];
            }
            else if ((preg_match('/^datepicker/',$inputType)) || (preg_match('/^datehourpicker/',$inputType)))
            {
                $tmp=explode(':',$inputType);
                $inputType=$tmp[0];
                if (! empty($tmp[1])) $inputOption=$tmp[1];
                if (! empty($tmp[2])) $savemethod=$tmp[2];

                $out.= '<input id="timestamp" type="hidden"/>'."\n"; // Use for timestamp format
            }
            else if (preg_match('/^(select|autocomplete)/',$inputType))
            {
                $tmp=explode(':',$inputType);
                $inputType=$tmp[0]; $loadmethod=$tmp[1];
                if (! empty($tmp[2])) $savemethod=$tmp[2];
                if (! empty($tmp[3])) $button_only=true;
            }
            else if (preg_match('/^textarea/',$inputType))
            {
            	$tmp=explode(':',$inputType);
            	$inputType=$tmp[0];
            	$rows=(empty($tmp[1])?'8':$tmp[1]);
            	$cols=(empty($tmp[2])?'80':$tmp[2]);
            }
            else if (preg_match('/^ckeditor/',$inputType))
            {
                $tmp=explode(':',$inputType);
                $inputType=$tmp[0]; $toolbar=$tmp[1];
                if (! empty($tmp[2])) $width=$tmp[2];
                if (! empty($tmp[3])) $heigth=$tmp[3];
                if (! empty($tmp[4])) $savemethod=$tmp[4];

                if (! empty($conf->fckeditor->enabled))
                {
                    $out.= '<input id="ckeditor_toolbar" value="'.$toolbar.'" type="hidden"/>'."\n";
                }
                else
                {
                    $inputType = 'textarea';
                }
            }

            $out.= '<input id="element_'.$htmlname.'" value="'.$element.'" type="hidden"/>'."\n";
            $out.= '<input id="table_element_'.$htmlname.'" value="'.$table_element.'" type="hidden"/>'."\n";
            $out.= '<input id="fk_element_'.$htmlname.'" value="'.$fk_element.'" type="hidden"/>'."\n";
            $out.= '<input id="loadmethod_'.$htmlname.'" value="'.$loadmethod.'" type="hidden"/>'."\n";
            if (! empty($savemethod))	$out.= '<input id="savemethod_'.$htmlname.'" value="'.$savemethod.'" type="hidden"/>'."\n";
            if (! empty($ext_element))	$out.= '<input id="ext_element_'.$htmlname.'" value="'.$ext_element.'" type="hidden"/>'."\n";
            if (! empty($success))		$out.= '<input id="success_'.$htmlname.'" value="'.$success.'" type="hidden"/>'."\n";
            if ($inputType == 'textarea') {
            	$out.= '<input id="textarea_'.$htmlname.'_rows" value="'.$rows.'" type="hidden"/>'."\n";
            	$out.= '<input id="textarea_'.$htmlname.'_cols" value="'.$cols.'" type="hidden"/>'."\n";
            }

            $out.= '<span id="viewval_'.$htmlname.'" class="viewval_'.$inputType.($button_only ? ' inactive' : ' active').'">'.$value.'</span>'."\n";
            $out.= '<span id="editval_'.$htmlname.'" class="editval_'.$inputType.($button_only ? ' inactive' : ' active').' hideobject">'.(! empty($editvalue) ? $editvalue : $value).'</span>'."\n";
        }
        else
        {
            $out = $value;
        }

        return $out;
    }

    /**
     *	Show a text and picto with tooltip on text or picto
     *
     *	@param	string		$text				Text to show
     *	@param	string		$htmltext			HTML content of tooltip. Must be HTML/UTF8 encoded.
     *	@param	int			$tooltipon			1=tooltip sur texte, 2=tooltip sur picto, 3=tooltip sur les 2
     *	@param	int			$direction			-1=Le picto est avant, 0=pas de picto, 1=le picto est apres
     *	@param	string		$img				Code img du picto (use img_xxx() function to get it)
     *	@param	string		$extracss			Add a CSS style to td tags
     *	@param	int			$notabs				0=Include table and tr tags, 1=Do not include table and tr tags, 2=use div, 3=use span
     *	@param	string		$incbefore			Include code before the text
     *	@param	int			$noencodehtmltext	Do not encode into html entity the htmltext
     *	@return	string							Code html du tooltip (texte+picto)
     *	@see	Use function textwithpicto if you can.
     */
    function textwithtooltip($text, $htmltext, $tooltipon = 1, $direction = 0, $img = '', $extracss = '', $notabs = 0, $incbefore = '', $noencodehtmltext = 0)
    {
        global $conf;

        if ($incbefore) $text = $incbefore.$text;
        if (! $htmltext) return $text;

        $tag='td';
        if ($notabs == 2) $tag='div';
        if ($notabs == 3) $tag='span';
        // Sanitize tooltip
        $htmltext=str_replace("\\","\\\\",$htmltext);
        $htmltext=str_replace("\r","",$htmltext);
        $htmltext=str_replace("\n","",$htmltext);

        $htmltext=str_replace('"',"&quot;",$htmltext);
        if ($tooltipon == 2 || $tooltipon == 3) $paramfortooltipimg=' class="classfortooltip'.($extracss?' '.$extracss:'').'" title="'.($noencodehtmltext?$htmltext:dol_escape_htmltag($htmltext,1)).'"'; // Attribut to put on td img tag to store tooltip
        else $paramfortooltipimg =($extracss?' class="'.$extracss.'"':''); // Attribut to put on td text tag
        if ($tooltipon == 1 || $tooltipon == 3) $paramfortooltiptd=' class="classfortooltip'.($extracss?' '.$extracss:'').'" title="'.($noencodehtmltext?$htmltext:dol_escape_htmltag($htmltext,1)).'"'; // Attribut to put on td tag to store tooltip
        else $paramfortooltiptd =($extracss?' class="'.$extracss.'"':''); // Attribut to put on td text tag
        $s="";
        if (empty($notabs))	$s.='<table class="nobordernopadding" summary=""><tr>';
        if ($direction < 0)	$s.='<'.$tag.$paramfortooltipimg.' valign="top" width="14">'.$img.'</'.$tag.'>';
        // Use another method to help avoid having a space in value in order to use this value with jquery
        // TODO add this in css
        //if ($text != '')	$s.='<'.$tag.$paramfortooltiptd.'>'.(($direction < 0)?'&nbsp;':'').$text.(($direction > 0)?'&nbsp;':'').'</'.$tag.'>';
        $paramfortooltiptd.= (($direction < 0)?' style="padding-left: 3px !important;"':'');
        $paramfortooltiptd.= (($direction > 0)?' style="padding-right: 3px !important;"':'');
        if ((string) $text != '')	$s.='<'.$tag.$paramfortooltiptd.'>'.$text.'</'.$tag.'>';
        if ($direction > 0)	$s.='<'.$tag.$paramfortooltipimg.' valign="top" width="14">'.$img.'</'.$tag.'>';
        if (empty($notabs))	$s.='</tr></table>';

        return $s;
    }

    /**
     *	Show a text with a picto and a tooltip on picto
     *
     *	@param	string	$text				Text to show
     *	@param  string	$htmltext	     	Content of tooltip
     *	@param	int		$direction			1=Icon is after text, -1=Icon is before text, 0=no icon
     * 	@param	string	$type				Type of picto (info, help, warning, superadmin...)
     *  @param  string	$extracss           Add a CSS style to td tags
     *  @param  int		$noencodehtmltext   Do not encode into html entity the htmltext
     *  @param	int		$notabs				0=Include table and tr tags, 1=Do not include table and tr tags, 2=use div, 3=use span
     * 	@return	string						HTML code of text, picto, tooltip
     */
    function textwithpicto($text, $htmltext, $direction = 1, $type = 'help', $extracss = '', $noencodehtmltext = 0, $notabs = 0)
    {
        global $conf;

        $alt = '';

        //For backwards compatibility
        if ($type == '0') $type = 'info';
        elseif ($type == '1') $type = 'help';

        // If info or help with no javascript, show only text
        if (empty($conf->use_javascript_ajax))
        {
            if ($type == 'info' || $type == 'help')	return $text;
            else
            {
                $alt = $htmltext;
                $htmltext = '';
            }
        }

        // If info or help with smartphone, show only text (tooltip can't works)
        if (! empty($conf->dol_no_mouse_hover))
        {
            if ($type == 'info' || $type == 'help') return $text;
        }

        if ($type == 'info') $img = img_help(0, $alt);
        elseif ($type == 'help') $img = img_help(1, $alt);
        elseif ($type == 'superadmin') $img = img_picto($alt, 'redstar');
        elseif ($type == 'admin') $img = img_picto($alt, 'star');
        elseif ($type == 'warning') $img = img_warning($alt);

        return $this->textwithtooltip($text, $htmltext, 2, $direction, $img, $extracss, $notabs, '', $noencodehtmltext);
    }

    /**
     *  Return combo list of activated countries, into language of user
     *
     *  @param	string	$selected       Id or Code or Label of preselected country
     *  @param  string	$htmlname       Name of html select object
     *  @param  string	$htmloption     Options html on select object
     *  @return	void
     */
    function select_pays($selected='',$htmlname='country_id',$htmloption='')
    {
        print $this->select_country($selected,$htmlname,$htmloption);
    }

    /**
     *  Return combo list of activated countries, into language of user
     *
     *  @param	string	$selected       Id or Code or Label of preselected country
     *  @param  string	$htmlname       Name of html select object
     *  @param  string	$htmloption     Options html on select object
     *  @param	string	$maxlength		Max length for labels (0=no limit)
     *  @return string           		HTML string with select
     */
    function select_country($selected='',$htmlname='country_id',$htmloption='',$maxlength=0)
    {
        global $conf,$langs;

        $langs->load("dict");

        $out='';
        $countryArray=array();
        $label=array();

        $sql = "SELECT rowid, code as code_iso, libelle as label";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_pays";
        $sql.= " WHERE active = 1";
        $sql.= " ORDER BY code ASC";

        dol_syslog(get_class($this)."::select_country sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $out.= '<select id="select'.$htmlname.'" class="flat selectpays" name="'.$htmlname.'" '.$htmloption.'>';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;

                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    $countryArray[$i]['rowid'] 		= $obj->rowid;
                    $countryArray[$i]['code_iso'] 	= $obj->code_iso;
                    $countryArray[$i]['label']		= ($obj->code_iso && $langs->transnoentitiesnoconv("Country".$obj->code_iso)!="Country".$obj->code_iso?$langs->transnoentitiesnoconv("Country".$obj->code_iso):($obj->label!='-'?$obj->label:''));
                    $label[$i] 	= $countryArray[$i]['label'];
                    $i++;
                }

                array_multisort($label, SORT_ASC, $countryArray);

                foreach ($countryArray as $row)
                {
                    //print 'rr'.$selected.'-'.$row['label'].'-'.$row['code_iso'].'<br>';
                    if ($selected && $selected != '-1' && ($selected == $row['rowid'] || $selected == $row['code_iso'] || $selected == $row['label']) )
                    {
                        $foundselected=true;
                        $out.= '<option value="'.$row['rowid'].'" selected="selected">';
                    }
                    else
                    {
                        $out.= '<option value="'.$row['rowid'].'">';
                    }
                    $out.= dol_trunc($row['label'],$maxlength,'middle');
                    if ($row['code_iso']) $out.= ' ('.$row['code_iso'] . ')';
                    $out.= '</option>';
                }
            }
            $out.= '</select>';
        }
        else
        {
            dol_print_error($this->db);
        }

        return $out;
    }

    /**
     *	Return list of types of lines (product or service)
     * 	Example: 0=product, 1=service, 9=other (for external module)
     *
     *	@param  string	$selected       Preselected type
     *	@param  string	$htmlname       Name of field in html form
     * 	@param	int		$showempty		Add an empty field
     * 	@param	int		$hidetext		Do not show label before combo box
     * 	@param	string	$forceall		Force to show products and services in combo list, whatever are activated modules
     *  @return	void
     */
    function select_type_of_lines($selected='',$htmlname='type',$showempty=0,$hidetext=0,$forceall=0)
    {
        global $db,$langs,$user,$conf;

        // If product & services are enabled or both disabled.
        if ($forceall || (! empty($conf->product->enabled) && ! empty($conf->service->enabled))
        || (empty($conf->product->enabled) && empty($conf->service->enabled)))
        {
            if (empty($hidetext)) print $langs->trans("Type").': ';
            print '<select class="flat" id="select_'.$htmlname.'" name="'.$htmlname.'">';
            if ($showempty)
            {
                print '<option value="-1"';
                if ($selected == -1) print ' selected="selected"';
                print '>&nbsp;</option>';
            }

            print '<option value="0"';
            if (0 == $selected) print ' selected="selected"';
            print '>'.$langs->trans("Product");

            print '<option value="1"';
            if (1 == $selected) print ' selected="selected"';
            print '>'.$langs->trans("Service");

            print '</select>';
            //if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
        }
        if (! $forceall && empty($conf->product->enabled) && ! empty($conf->service->enabled))
        {
            print '<input type="hidden" name="'.$htmlname.'" value="1">';
        }
        if (! $forceall && ! empty($conf->product->enabled) && empty($conf->service->enabled))
        {
            print '<input type="hidden" name="'.$htmlname.'" value="0">';
        }

    }

    /**
     *	Load into cache cache_types_fees, array of types of fees
     *
     *	@return     int             Nb of lines loaded, 0 if already loaded, <0 if ko
     *	TODO move in DAO class
     */
    function load_cache_types_fees()
    {
        global $langs;

        $langs->load("trips");

        if (count($this->cache_types_fees)) return 0;    // Cache already load

        $sql = "SELECT c.code, c.libelle as label";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_type_fees as c";
        $sql.= " ORDER BY lower(c.libelle) ASC";

        dol_syslog(get_class($this).'::load_cache_types_fees sql='.$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;

            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $label=($obj->code != $langs->trans($obj->code) ? $langs->trans($obj->code) : $langs->trans($obj->label));
                $this->cache_types_fees[$obj->code] = $label;
                $i++;
            }
            return $num;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *	Return list of types of notes
     *
     *	@param	string		$selected		Preselected type
     *	@param  string		$htmlname		Name of field in form
     * 	@param	int			$showempty		Add an empty field
     * 	@return	void
     */
    function select_type_fees($selected='',$htmlname='type',$showempty=0)
    {
        global $user, $langs;

        dol_syslog(get_class($this)."::select_type_fees ".$selected.", ".$htmlname, LOG_DEBUG);

        $this->load_cache_types_fees();

        print '<select class="flat" name="'.$htmlname.'">';
        if ($showempty)
        {
            print '<option value="-1"';
            if ($selected == -1) print ' selected="selected"';
            print '>&nbsp;</option>';
        }

        foreach($this->cache_types_fees as $key => $value)
        {
            print '<option value="'.$key.'"';
            if ($key == $selected) print ' selected="selected"';
            print '>';
            print $value;
            print '</option>';
        }

        print '</select>';
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
    }

    /**
     *  Output html form to select a third party
     *
     *	@param	string	$selected       Preselected type
     *	@param  string	$htmlname       Name of field in form
     *  @param  string	$filter         Optionnal filters criteras (example: 's.rowid <> x')
     *	@param	int		$showempty		Add an empty field
     * 	@param	int		$showtype		Show third party type in combolist (customer, prospect or supplier)
     * 	@param	int		$forcecombo		Force to use combo box
     *  @param	array	$event			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
     * 	@return	string					HTML string with
     */
    function select_company($selected='',$htmlname='socid',$filter='',$showempty=0, $showtype=0, $forcecombo=0, $event=array())
    {
        global $conf,$user,$langs;

        $out='';

        // On recherche les societes
        $sql = "SELECT s.rowid, s.nom, s.client, s.fournisseur, s.code_client, s.code_fournisseur";
        $sql.= " FROM ".MAIN_DB_PREFIX ."societe as s";
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE s.entity IN (".getEntity('societe', 1).")";
        if (! empty($user->societe_id)) $sql.= " AND s.rowid = ".$user->societe_id;
        if ($filter) $sql.= " AND (".$filter.")";
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        $sql.= " ORDER BY nom ASC";

        dol_syslog(get_class($this)."::select_company sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT && ! $forcecombo)
            {
                //$minLength = (is_numeric($conf->global->COMPANY_USE_SEARCH_TO_SELECT)?$conf->global->COMPANY_USE_SEARCH_TO_SELECT:2);
                $out.= ajax_combobox($htmlname, $event, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
				/*              
				if ($selected && empty($selected_input_value))
                {
                	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
                	$product = new Product($this->db);
                	$product->fetch($selected);
                	$selected_input_value=$product->ref;
                }
				if ($selected && empty($selected_input_value))
                {
                	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
                	$product = new Product($this->db);
                	$product->fetch($selected);
                	$selected_input_value=$product->ref;
                }
