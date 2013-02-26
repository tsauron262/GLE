<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
  * Infos on http://www.finapro.fr
  *
  */
/*
 * or see http://www.gnu.org/
 */

/**
 \file       htdocs/core/modules/synopsiscontrat/mod_contratGA_malachite.php
 \ingroup    commande
 \brief      Fichier contenant la classe du modele de numerotation de reference de contratGA Malachite
 \version    $Id: mod_commande_malachite.php,v 1.9 2010 Tommy SAURON Exp $
 */

include_once("modules_contratGA.php");


/**
 \class      mod_commande_malachite
 \brief      Classe du modele de numerotation de reference de commande Malachite
 */

class mod_contratGA_malachite extends ModeleNumRefContratGA
{
    var $version='1.0';        // 'development', 'experimental', 'dolibarr'
    var $error = '';
    var $nom = 'Malachite';


    /**   \brief      Constructeur
    */
    function mod_contratGA_malachite()
    {
        $this->nom = "Malachite";
    }


    /**     \brief      Renvoi la description du modele de numerotation
    *      \return     string      Texte descripif
    */
    function info()
    {
        return "Renvoie le num&eacute;ro sous la forme num&eacute;rique CTRhexa, o&ugrave; hexa repr&eacute;sente un incr&eacute;ment global cod&eacute; en h&eacute;xad&eacute;cimal. (COM-000-001 &agrave; COM-FFF-FFF)";
    }

    /**     \brief      Renvoi un exemple de numerotation
    *      \return     string      Example
    */
    function getExample()
    {
        return "CTR-000-001";
    }


    /**        \brief      Return next value
    *          \param      objsoc      Objet third party
    *        \param        commande    Object order
    *          \return     string      Value if OK, 0 if KO
    */
    function getNextValue($objsoc,$contratGA)
    {
        global $db;

        // D'abord on recupere la valeur max (reponse immediate car champ indexe)
        $com='';
        $sql = "SELECT MAX(ref)";
        $sql.= " FROM ".MAIN_DB_PREFIX."contrat";
        $sql.= " WHERE is_financement = 1";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row)
            {
                //on extrait la valeur max et on la passe en decimale
                $max = hexdec((substr($row[0],4,3)).(substr($row[0],8,3)));
            }
        } else {
            $max=0;
        }
        //$date=time();
        $date=$contratGA->date;
        $yy = strftime("%y",$date);
        $hex = strtoupper(dechex($max+1));
        $ref = substr("000000".($hex),-6);

        return 'CTR-'.substr($ref,0,3)."-".substr($ref,3,3);
    }

    /**        \brief      Return next free value
    *          \param      objsoc      Object third party
    *         \param        objforref    Object for number to search
    *       \return     string      Next free value
    */
    function contratGA_get_num($objsoc,$objforref)
    {
        return $this->getNextValue($objsoc,$objforref);
    }
    function getVersion()
    {
        return($this->version);
    }

}
?>
