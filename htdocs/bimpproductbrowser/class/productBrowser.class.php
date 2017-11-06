<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 * 	\file       htdocs/bimpproductbrowser/class/productBrowser.class.php
 * 	\ingroup    bimpproductbrowser
 * 	\brief      File to filter and display products using categories
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobjectline.class.php';

class ProductBrowser extends CommonObject
{
	public $id='rowid';
	public $id_parent=array();
	public $id_child=array();

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db     Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * 	Add link into database
	 *
	 * 	@param	User	$user		Object user
	 * 	@return	int 				-1 : SQL error
	 *          					-2 : new ID unknown
	 *          					-3 : Invalid category
	 * 								-4 : category already exists
	 */
	function create()
	{
		global $conf,$langs,$hookmanager;
		$langs->load('categories');

		$error=0;
		print 'Class OK' ;
		dol_syslog(get_class($this).'::create', LOG_DEBUG);

	}

	/**
	 *  Load an import profil from database
	 *
	 *  @param		int		$id		Id of profil to load
	 *  @return		int				<0 if KO, >0 if OK
	 */
	function fetch($id)
	{
		$sql = 'SELECT rowid, fk_parent_cat, fk_child_cat';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'bimp_cat_cat';
		$sql.= ' WHERE rowid = '.$id;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$obj = $this->db->fetch_object($result);
			if ($obj)
			{
				$this->id			= $obj->rowid;
				$this->id_parent	= $obj->fk_parent_cat;
				$this->id_child		= $obj->fk_child_cat;
				return 1;
			}
			else
			{
				$this->error="Model not found";
				return -2;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -3;
		}
	}
}