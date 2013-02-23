<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
/*
  * GLE by Babel-Services
  *
  * Author: Jean-Marc LE FEVRE <jm.lefevre@babel-services.com>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.babel-services.com
  *
  */
/*
 * or see http://www.gnu.org/
 */

/**
        \file       htdocs/core/boxes/modules_boxes.php
        \ingroup    facture
        \brief      Fichier contenant la classe mere des boites
        \version    $Id: modules_boxes.php,v 1.35 2008/06/19 08:48:17 eldy Exp $
*/


/**
        \class      ModeleBoxes
        \brief      Classe mere des boites
*/

class ModeleBoxes
{
    public $MAXLENGTHBOX=60;   // Mettre 0 pour pas de limite

    public $db;
    public $error='';
    public $textnohtmlencoded=false;

    /*
    *    \brief        Constructeur
    */
    function ModeleBoxes($DB)
    {
        $this->db=$DB;
    }


   /**
        \brief      Renvoi le dernier message d'erreur de creation de facture
    */
    function error()
    {
        return $this->error;
    }


   /**
        \brief      Charge une ligne boxe depuis son rowid
    */
    function fetch($rowid)
    {
        // Recupere liste des boites d'un user si ce dernier a sa propre liste
        $sql = "SELECT b.rowid, b.box_id, b.position, b.box_order, b.fk_user";
        $sql.= " FROM ".MAIN_DB_PREFIX."boxes as b";
        $sql.= " WHERE b.rowid = ".$rowid;
        dolibarr_syslog("ModeleBoxes::fetch rowid=".$rowid);

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($obj)
            {
                $this->rowid=$obj->rowid;
                $this->box_id=$obj->box_id;
                $this->position=$obj->position;
                $this->box_order=$obj->box_order;
                $this->fk_user=$obj->fk_user;
                return 1;
            }
            else
            {
                return -1;
            }
        }
        else
        {
            return -1;
        }
    }


   /**
        \brief      Methode standard d'affichage des boites
        \param      $head       tableau des caracteristiques du titre
        \param      $contents   tableau des lignes de contenu
    */
    function showBox($head, $contents, $display=true)
    {
        global $langs,$conf;

        $bcx[0] = 'class="box_pair"';
        $bcx[1] = 'class="box_impair"';
        $var = true;

        $html = "";

        // Define nbcol and nblines
        $nbcol=0;
        if (isset($contents[0])) $nbcol=sizeof($contents[0])+1;
        $nblines=sizeof($contents);

        $html .=  "\n\n<!-- Box start -->\n";
        $html .=  '<div style="padding-right: 2px; padding-left: 2px; padding-bottom: 4px;" id="boxto_'.$this->box_id.'">'."\n";

        // Affiche titre de la boite
        if (! empty($head['text']) || ! empty($head['sublink']))
        {
            $html .=  '<div id="boxto_'.$this->box_id.'_title">'."\n";
            $html .=  '<table width="100%" class="noborder">'."\n";
            $html .=  '<tr class="box_titre">';
            $html .=  '<td';
            if ($nbcol > 0) { $html .=  ' colspan="'.$nbcol.'"'; }
            $html .=  '>';
            if ($conf->use_javascript_ajax)
            {
                $html .=  '<table class="nobordernopadding" width="100%"><tr><td align="left">';
            }
            if (! empty($head['text']))
            {
                $th = $head['text'];
                $s=dol_trunc(html_entity_decode($th),isset($head['limit'])?$head['limit']:$this->MAXLENGTHBOX);
                $so = $s;
//                if ($this->textnohtmlencoded) $html .=  htmlentities($s);
//                else $html .=  $s;
$html .=  $so;
            }
            if (! empty($head['sublink']))
            {
                $html .=  ' <a href="'.$head['sublink'].'" target="_blank">'.img_picto($head['subtext'],$head['subpicto']).'</a>';
            }
            if ($conf->use_javascript_ajax)
            {
                $html .=  '</td><td class="nocellnopadd" width="14">';
                // The image must have the class 'boxhandle' beause it's value used in DOM draggable objects to define the area used to catch the full object
                $html .=  img_picto($langs->trans("MoveBox",$this->box_id),'uparrow','class="boxhandle" style="cursor:move;"');
                $html .=  '</td></tr></table>';
            }
            $html .=  '</td>';
            $html .=  "</tr>\n";
            $html .=  "</table>\n";
            $html .=  "</div>\n";
        }

        // Affiche chaque ligne de la boite
        if ($nblines)
        {
            $html .=  '<table width="100%" class="noborder">'."\n";
            for ($i=0, $n=$nblines; $i < $n; $i++)
            {
                if (isset($contents[$i]))
                {
                    $var=!$var;
                    if (sizeof($contents[$i]))
                    {
                        if (isset($contents[$i][-1]['class'])) $html .=  '<tr valign="top" class="'.$contents[$i][-1]['class'].'">';
                        else $html .=  '<tr valign="top" '.$bcx[$var].'>';
                    }

                    // Affiche chaque cellule
                    for ($j=0, $m=isset($contents[$i][-1])?sizeof($contents[$i])-1:sizeof($contents[$i]); $j < $m; $j++)
                    {
                        $tdparam="";
                        if (isset($contents[$i][$j]['align'])) $tdparam.=' align="'. $contents[$i][$j]['align'].'"';
                        if (isset($contents[$i][$j]['nowrap'])) $tdparam.=' nowrap="'. $contents[$i][$j]['align'].'"';
                        if (isset($contents[$i][$j]['width'])) $tdparam.=' width="'. $contents[$i][$j]['width'].'"';
                        if (isset($contents[$i][$j]['colspan'])) $tdparam.=' colspan="'. $contents[$i][$j]['colspan'].'"';
                        if (isset($contents[$i][$j]['class'])) $tdparam.=' class="'. $contents[$i][$j]['class'].'"';
                        if (isset($contents[$i][$j]['td'])) $tdparam.=' '.$contents[$i][$j]['td'];

                        if (!$contents[$i][$j]['text']) $contents[$i][$j]['text']="";
                        $texte=isset($contents[$i][$j]['text'])?$contents[$i][$j]['text']:'';
                        $textewithnotags=preg_replace('/<[^>]+>/i','',$texte);
                        $texte2=isset($contents[$i][$j]['text2'])?$contents[$i][$j]['text2']:'';
                        $texte2withnotags=preg_replace('/<[^>]+>/i','',$texte2);
                        //$html .=  "xxx $textewithnotags y";

                        if (isset($contents[$i][$j]['logo']) && $contents[$i][$j]['logo']) $html .=  '<td width="16">';
                        else $html .=  '<td '.$tdparam.'>';

                        // Picto
                        if (isset($contents[$i][$j]['url'])) {
                            $html .=  '<a href="'.$contents[$i][$j]['url'].'" title="'.$textewithnotags.'"';
                        //$html .=  ' alt="'.$textewithnotags.'"';      // Pas de alt sur un "<a href>"
                            $html .=  isset($contents[$i][$j]['target'])?' target="'.$contents[$i][$j]['target'].'"':'';
                            $html .=  '>';
                        }

                        // Texte
                        if (isset($contents[$i][$j]['logo']) && $contents[$i][$j]['logo'])
                        {
                            $logo=preg_replace("/^object_/i","",$contents[$i][$j]['logo']);
                            $html .=  img_object($langs->trans("Show"),$logo);
                            if (isset($contents[$i][$j]['url'])) $html .=  '</a>';
                            $html .=  '</td><td '.$tdparam.'>';
                            if (isset($contents[$i][$j]['url']))
                            {
                                $html .=  '<a href="'.$contents[$i][$j]['url'].'" title="'.$textewithnotags.'"';
                                //$html .=  ' alt="'.$textewithnotags.'"';      // Pas de alt sur un "<a href>"
                                $html .=  isset($contents[$i][$j]['target'])?' target="'.$contents[$i][$j]['target'].'"':'';
                                $html .=  '>';
                            }
                        }
                        $maxlength=$this->MAXLENGTHBOX;
                        if (isset($contents[$i][$j]['maxlength'])) $maxlength=$contents[$i][$j]['maxlength'];

                        if ($maxlength && strlen($textewithnotags) > $maxlength)
                        {
                            $texte=substr($texte,0,$maxlength)."...";
                        }
                        if ($maxlength && strlen($texte2withnotags) > $maxlength)
                        {
                            $texte2=substr($texte2,0,$maxlength)."...";
                        }
                        $html .=  $texte;
                        if (isset($contents[$i][$j]['url'])) $html .=  '</a>';
                        $html .=  $texte2;
                        $html .=  "</td>";
                    }

                    if (sizeof($contents[$i])) $html .=  "</tr>\n";
                }
            }
            $html .=  "</table>\n";
        }

        // If invisible box with no contents
        if (empty($head['text']) && empty($head['sublink']) && ! $nblines) $html .=  "<br><br>\n";

        $html .=  "</div>\n";
        $html .=  "<!-- Box end -->\n\n";

        if ($display){print $html;}
        return ($html);
    }

}


?>
