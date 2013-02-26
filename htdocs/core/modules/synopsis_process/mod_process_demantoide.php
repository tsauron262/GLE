<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2008 Raphael Bertrand (Resultic)       <raphael.bertrand@resultic.fr>
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
  * GLE by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.synopsis-erp.com
  *
  */
/*
 * or see http://www.gnu.org/
 */

/**
    \file       htdocs/core/modules/synopsis_process/mod_process_demantoide.php
    \ingroup    process
    \brief      Fichier contenant la classe du modele de numerotation de reference de process demantoide
    \version    $Id: mod_process_demantoide.php,v 1.19 2008/07/05 14:20:10 eldy Exp $
*/

require_once(DOL_DOCUMENT_ROOT ."/core/modules/synopsis_process/modules_process.php");


/**
    \class      mod_process_demantoide
    \brief      Classe du modele de numerotation de reference de process demantoide
*/
class mod_process_demantoide extends ModeleNumRefprocess
{
    public $version='dolibarr';        // 'development', 'experimental', 'dolibarr'
    public $error = '';
    public $nom = 'D&eacute;manto&iuml;de';


    /**     \brief      Renvoi la description du modele de numerotation
     *      \return     string      Texte descripif
     */
    function info($readOnly=false,$process=false)
    {
        global $conf,$langs,$db;

        $langs->load("bills");

        $form = new Form($db);
        $texte="";
        if ($readOnly)
        {
            $texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
            $texte.= '<table class="nobordernopadding" width="100%">';

            // Parametrage du prefix des factures
            $texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
            $txt = htmlspecialchars($langs->trans("GenericMaskCodes",$langs->transnoentities("Process"),$langs->transnoentities("Process"),$langs->transnoentities("Process")));
//            $texte.= '<xmp><td align="right">'.$conf->global->PROCESS_DEMANTOIDE_MASK."<td><span onmouseout='hidetip();' onmouseover=\"showtip('".addslashes(preg_replace('/"/',"\\\"",$txt))."')\">".img_info().'</span></td></xmp>';
            $texte.= '<td align="right">'.$process->PROCESS_MASK.'<td><span onmouseout="hidetip();" onmouseover="showtip(\''.$txt.'\')">'.img_info('').'</span></td>';
            $texte.= '</tr>';

            $texte.= '</table>';
            $texte.= '</form>';
        } else {
            $texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
            $texte.= '<table class="nobordernopadding" width="100%">';

            // Parametrage du prefix des factures
            $texte.= '<tr><td>'.$langs->trans("Mask").':</td>';
            $texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat required" size="24" name="PROCESS_DEMANTOIDE_MASK" value="'.$process->PROCESS_MASK.'">',$langs->trans("GenericMaskCodes",$langs->transnoentities("Process"),$langs->transnoentities("Process"),$langs->transnoentities("Process")),1,1).'</td>';

            $texte.= '</tr>';

            $texte.= '</table>';
            $texte.= '</form>';
        }

        return $texte;
    }

    /**     \brief      Renvoi un exemple de numerotation
     *      \return     string      Example
     */
    function getExample($obj=false)
    {
        global $conf,$langs,$mysoc;

        $old_code_client=$mysoc->code_client;
        $mysoc->code_client='CCCCCCCCCC';
        $numExample = $this->getNextValue($mysoc,$obj);
        $mysoc->code_client=$old_code_client;

        if (! $numExample)
        {
            $numExample = $langs->trans('NotConfigured');
        }
        return $numExample;
    }

    /**        \brief      Return next value
    *          \param      objsoc      Object third party
    *         \param        propal        Object commercial proposal
    *          \return     string      Value if OK, 0 if KO
    */
    function getNextValue($objsoc,$process)
    {
        global $db,$conf;

        require_once(DOL_DOCUMENT_ROOT ."/core/lib/functions2.lib.php");

        // On defini critere recherche compteur
        $mask=$process->PROCESS_MASK;
        if (! $mask)
        {
            $this->error='NotConfigured';
            return 0;
        }

        $numFinal=get_next_value($db,$mask,'Synopsis_Processdet','ref',' AND process_refid='.$process->id,$objsoc->code_client);

        return  $numFinal;
    }

}
?>