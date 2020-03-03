<?php
/* Copyright (C) 2012      Mikael Carlavan        <contact@mika-carl.fr>
 *                                                http://www.mikael-carlavan.fr
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
 *	\file       htdocs/core/modules/ndfp/mod_ndfp_saturne.php
 *	\ingroup    ndfp
 *	\brief      File containing class for numbering module Saturne
 */
require_once(DOL_DOCUMENT_ROOT ."/core/modules/ndfp/modules_ndfp.php");


/**
 *	\class      mod_ndfp_saturne
 *	\brief      Classe du modele de numerotation de reference des notes de frais Saturne
 */
class mod_ndfp_saturne extends ModeleNumRefNdfp
{
    var $version = 'dolibarr';		// 'development', 'experimental', 'dolibarr'
    var $error = '';
    


    /**     \brief      Renvoi la description du modele de numerotation
     *      \return     string      Texte descripif
     */
    function info()
    {
        global $conf, $db, $langs;

        $langs->load("ndfp");

        $form = new Form($db);

        $texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
        $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        $texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        $texte.= '<input type="hidden" name="action" value="updateMask">';
        $texte.= '<input type="hidden" name="maskconst" value="NDFP_SATURNE_MASK">';
        $texte.= '<table class="nobordernopadding" width="100%">';

        $tooltip=$langs->trans("GenericMaskCodes",$langs->transnoentities("Ndfp2"));
        $tooltip.=$langs->trans("GenericMaskCodes2");
        $tooltip.=$langs->trans("GenericMaskCodes3");
        $tooltip.=$langs->trans("GenericMaskCodes4a",$langs->transnoentities("Ndfp2"),$langs->transnoentities("Ndfp2"));
        $tooltip.=$langs->trans("GenericMaskCodes5");

        // Parametrage du prefix
        $texte.= '<tr><td>'.$langs->trans("Mask").' :</td>';
        $texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="mask" value="'.$conf->global->NDFP_SATURNE_MASK.'">',$tooltip,1,1).'</td>';

        $texte.= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
        $texte.= '</tr>';

        $texte.= '</table>';
        $texte.= '</form>';

        return $texte;
    }

    /**     \brief      Return an example of number value
     *      \return     string      Example
     */
    function getExample()
    {
        global $conf, $langs, $mysoc;

        
        $old_code_client = $mysoc->code_client;
        $old_code_type = $mysoc->typent_code;
        $mysoc->code_client = 'CCCCCCCCCC';
        $mysoc->typent_code = 'TTTTTTTTTT';
        $numExample = $this->getNextValue($mysoc,'');
        $mysoc->code_client = $old_code_client;
        $mysoc->typent_code = $old_code_type;

        if (! $numExample)
        {
            $numExample = $langs->trans('NotConfigured');
        }
        
        return $numExample;
    }

    /**		Return next value
     *      @param      objsoc      Object third party
     *      @param      ndfp		Object ndfp
     *      @param      mode        'next' for next value or 'last' for last value
     *      @return     string      Value if OK, 0 if KO
     */
    function getNextValue($objsoc,$ndfp,$mode='next')
    {
        global $db,$conf, $langs;

        require_once(DOL_DOCUMENT_ROOT ."/core/lib/functions2.lib.php");

        // Get Mask value
        $mask = $conf->global->NDFP_SATURNE_MASK;

        if (! $mask)
        {
            $this->error = $langs->trans('NotConfigured');
            return 0;
        }

        $where = '';

        $numFinal = get_next_value($db, $mask, 'ndfp', 'ref', $where, $objsoc, $ndfp->datef, $mode);
        if (! preg_match('/([0-9])+/',$numFinal)) $this->error = $numFinal;

        return  $numFinal;
    }


    /**		Return next free value
     *      @param      objsoc      Object third party
     * 		@param		objforref	Object for number to search
     *      @param      mode        'next' for next value or 'last' for last value
     *   	@return     string      Next free value
     */
    function getNumRef($objsoc,$objforref,$mode='next')
    {
        return $this->getNextValue($objsoc,$objforref,$mode);
    }

}
?>