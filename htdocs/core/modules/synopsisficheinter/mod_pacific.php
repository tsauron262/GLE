<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
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
    \file       htdocs/core/modules/synopsisficheinter/mod_pacific.php
    \ingroup    fiche intervention
    \brief      Fichier contenant la classe du modele de numerotation de reference de fiche intervention Pacific
    \version    $Id: mod_pacific.php,v 1.5 2008/07/08 23:02:17 eldy Exp $
*/

require_once(DOL_DOCUMENT_ROOT ."/core/modules/synopsisficheinter/modules_synopsisfichinter.php");

/**
    \class      mod_pacific
        \brief      Classe du modele de numerotation de reference de fiche intervention Pacific
*/

class mod_pacific extends ModeleNumRefFicheinter
{
    public $prefix='FI';
    public $error='';


    /**   \brief      Constructeur
    */
    function mod_pacific()
    {
        $this->nom = "pacific";
    }


    /**     \brief      Renvoi la description du modele de numerotation
     *      \return     string      Texte descripif
     */
    function info()
    {
        global $langs;

        $langs->load("bills");

        return $langs->trans('PacificNumRefModelDesc1',$this->prefix);
    }

    /**     \brief      Renvoi un exemple de numerotation
     *      \return     string      Example
     */
    function getExample()
    {
        return $this->prefix."0501-0001";
    }

    /**     \brief      Test si les numeros deja en vigueur dans la base ne provoquent pas de
     *                  de conflits qui empechera cette numerotation de fonctionner.
     *      \return     boolean     false si conflit, true si ok
     */
    function canBeActivated()
    {
        global $langs;

        $langs->load("bills");

        $fayymm='';

        $sql = "SELECT MAX(ref)";
        $sql.= " FROM ".MAIN_DB_PREFIX."Synopsis_fichinter";
        $resql=$db->query($sql);
        if ($resql)
        {
            $row = $db->fetch_row($resql);
            if ($row) $fayymm = substr($row[0],0,6);
        }
        if (! $fayymm || preg_match('/'.$this->prefix.'[0-9][0-9][0-9][0-9]/',$fayymm))
        {
            return true;
        }
        else
        {
            $this->error=$langs->trans('PacificNumRefModelError');
            return false;
        }
    }

    /**        \brief      Renvoi prochaine valeur attribuee
    *          \param      objsoc      Objet societe
    *          \param      ficheinter    Object ficheinter
    *          \return     string      Valeur
    */
    function getNextValue($objsoc=0,$ficheinter='')
    {
        global $db;

        // D'abord on recupere la valeur max (reponse immediate car champ indexe)
        $posindice=8;
        $sql = "SELECT MAX(0+SUBSTRING(ref,".$posindice.")) as max";
        $sql.= " FROM ".MAIN_DB_PREFIX."Synopsis_fichinter";
        $sql.= " WHERE ref like '".$this->prefix."%'";

        $resql=$db->query($sql);
        if ($resql)
        {
            $obj = $db->fetch_object($resql);
            if ($obj) $max = $obj->max;
            else $max=0;
        }

        //$date=time();
        $date=$ficheinter->date;
        $yymm = utf8_decode(strftime("%y%m",$date));
        $num = sprintf("%04s",$max+1);

        return $this->prefix.$yymm."-".$num;
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

}

?>
