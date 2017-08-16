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
 *	\file       htdocs/core/modules/nfdp/mod_ndfp_uranus.php
 *	\ingroup    ndfp
 *	\brief      File containing class for numbering module Uranus
 */
require_once(DOL_DOCUMENT_ROOT ."/core/modules/ndfp/modules_ndfp.php");

/**	    \class      mod_ndfp_uranus
 *		\brief      Classe du modele de numerotation de reference des notes de frais Uranus
 */
class mod_ndfp_uranus extends ModeleNumRefNdfp
{
	var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	var $prefixconf='NF';
	var $error='';

	/**     \brief      Renvoi la description du modele de numerotation
	 *      \return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("ndfp");
		return $langs->trans('UranusNumRefModelDesc1',$this->prefixconf);
	}

	/**     \brief      Renvoi un exemple de numerotation
	 *      \return     string      Example
	 */
	function getExample()
	{
		return $this->prefixconf."0501-0001";
	}

	/**     \brief      Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 *                  de conflits qui empechera cette numerotation de fonctionner.
	 *      \return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		global $langs,$conf;

		$langs->load("bills");

		// Check invoice num
		$fayymm=''; $max='';

		$posindice=8;
		$sql = "SELECT MAX(SUBSTRING(ref FROM ".$posindice.")) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."ndfp";
		$sql.= " WHERE ref LIKE '".$this->prefixconf."____-%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		if ($resql)
		{
			$row = $db->fetch_row($resql);
			if ($row) { $fayymm = substr($row[0],0,6); $max=$row[0]; }
		}
		if ($fayymm && ! preg_match('/'.$this->prefixconf.'[0-9][0-9][0-9][0-9]/i',$fayymm))
		{
			$langs->load("errors");
			$this->error=$langs->trans('ErrorNumRefModel',$max);
			return false;
		}

		return true;
	}

	/**     Return next value not used or last value used
	 *      @param     objsoc		Object third party
	 *      @param     facture		Object ndfp
     *      @param     mode         'next' for next value or 'last' for last value
	 *      @return    string       Value
	 */
	function getNextValue($objsoc, $ndfp, $mode='next')
	{
		global $db, $conf;

		// D'abord on recupere la valeur max
		$posindice=8;
		$sql = "SELECT MAX(SUBSTRING(ref FROM ".$posindice.")) as max";	// This is standard SQL
		$sql.= " FROM ".MAIN_DB_PREFIX."ndfp";
		$sql.= " WHERE ref LIKE '".$this->prefixconf."____-%'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$db->query($sql);
		dol_syslog("mod_ndfp_uranus::getNextValue sql=".$sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $max = intval($obj->max);
			else $max=0;
		}
		else
		{
			dol_syslog("mod_ndfp_uranus::getNextValue sql=".$sql, LOG_ERR);
			return -1;
		}

		if ($mode == 'last')
		{
            $num = sprintf("%04s",$max);

            $ref='';
            $sql = "SELECT ref";
            $sql.= " FROM ".MAIN_DB_PREFIX."ndfp";
            $sql.= " WHERE ref LIKE '".$this->prefixconf."____-".$num."'";
            $sql.= " AND entity = ".$conf->entity;

            dol_syslog("mod_ndfp_uranus::getNextValue sql=".$sql);
            $resql=$db->query($sql);
            if ($resql)
            {
                $obj = $db->fetch_object($resql);
                if ($obj) $ref = $obj->ref;
            }
            else dol_print_error($db);

            return $ref;
		}
		else if ($mode == 'next')
		{
    		$date = $ndfp->datef;	// 
    		$yymm = strftime("%y%m",$date);
    		$num = sprintf("%04s",$max+1);

    		dol_syslog("mod_ndfp_uranus::getNextValue return ".$this->prefixconf.$yymm."-".$num);
    		return $this->prefixconf.$yymm."-".$num;
		}
		else dol_print_error('','Bad parameter for getNextValue');
	}

	/**		Return next free value
	 *     	@param      objsoc      Object third party
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
