<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.*//*
  * BIMP-ERP by Synopsis et DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.finapro.fr
  *
  *//*
 * or see http://www.gnu.org/
 */

/**
    \file       htdocs/core/modules/synopsiscontrat/mod_contratGA_tourmaline.php
    \ingroup    facture
    \brief      Class filte of tourmaline numbering module for invoice
    \version    $Id: tourmaline.modules.php,v 1.15 2008/07/05 14:20:08 eldy Exp $
*/

include_once("modules_contratGA.php");


/**
    \class      mod_contratGA_tourmaline
    \brief      Classe du modele de numerotation de reference de facture tourmaline
*/
class mod_contratGA_tourmaline extends ModeleNumRefcontratGA
{
    var $version='1.0';        // 'development', 'experimental', 'dolibarr'
    var $error = '';
    var $nom = 'Tourmaline';


    /**     \brief      Renvoi la description du modele de numerotation
     *      \return     string      Texte descripif
     */
    function info()
    {
        global $conf,$langs;

        $langs->load("bills");

        $form = new Form($this->db);

        $texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
        $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        $texte.= '<input type="hidden" name="action" value="updateMask">';
        $texte.= '<input type="hidden" name="maskconst" value="CONTRATGA_TOURMALINE_MASK">';
        $texte.= '<table class="nobordernopadding" width="100%">';

        // Parametrage du prefix des factures
        $texte.= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("InvoiceStandard").'):</td>';
        $texte.= '<td align="right">'.$form->textwithtooltip('<input type="text" class="flat" size="24" name="maskcontratGA" value="'.$conf->global->CONTRATGA_TOURMALINE_MASK.'">',$langs->trans("GenericMaskCodes",$langs->transnoentities("contrat"),$langs->transnoentities("contrat"),$langs->transnoentities("contrat")),1,1).'</td>';

        $texte.= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';

        $texte.= '</tr>';

        $texte.= '</table>';
        $texte.= '</form>';

        return $texte;
    }

    /**     \brief      Renvoi un exemple de numerotation
     *      \return     string      Example
     */
    function getExample()
    {
        global $conf,$langs,$mysoc;

        $old_code_client=$mysoc->code_client;
        $mysoc->code_client='CCCCCCCCCC';
        $numExample = $this->getNextValue($mysoc,'');
        $mysoc->code_client=$old_code_client;

        if (! $numExample)
        {
            $numExample = $langs->trans('NotConfigured');
        }
        return $numExample;
    }

    /**        \brief      Return next value
    *          \param      objsoc      Object third party
    *          \param      facture        Object invoice
    *          \return     string      Value if OK, 0 if KO
    */
    function getNextValue($objsoc,$contratGA)
    {
        global $db,$conf;

        require_once(DOL_DOCUMENT_ROOT ."/core/lib/functions2.lib.php");
        // On defini critere recherche compteur
        $mask=$conf->global->CONTRATGA_TOURMALINE_MASK;
        if (! $mask)
        {
            $this->error='NotConfigured';
            return 0;
        }

        $where=' AND is_financement = 1 ';

        $numFinal=get_next_value($db,$mask,'contratGA','ref',$where,$objsoc->code_client,$contratGA->date);

        return  $numFinal;
    }


    /**        \brief      Return next free value
    *          \param      objsoc      Object third party
    *         \param        objforref    Object for number to search
    *       \return     string      Next free value
    */
    function getNumRef($objsoc,$objforref)
    {
        return $this->getNextValue($objsoc,$objforref);
    }
    function getVersion()
    {
        return($this->version);
    }

}
?>