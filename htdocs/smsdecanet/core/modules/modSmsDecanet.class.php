<?php
/* Copyright (C) 2012      SARL Decanet <contact@decanet.fr>
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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


/**
 *      \file      
 *      \ingroup    smsdecanet
 *      \brief      Module d'envoi de SMS Decanet.fr
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 * 		\class      modSmsDecanet
 *      \brief      Description and activation class for module SmsDecanet
 */
class modSmsDecanet extends DolibarrModules
{
	
	/**
	 *   \brief      Constructor. Define names, constants, directories, boxes, permissions
	 *   \param      DB      Database handler
	 */
	function modSmsDecanet($DB)
	{
		$this->db = $DB;

		$this->numero = 51487;
		$this->rights_class = 'smsdecanet';

		$this->family = 'technic';
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Envoi de SMS à vos clients";
		$this->version = '3.2.2';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='email';
		$this->triggers = 0;
		$this->dirs = array();
		$r=0;
		$this->style_sheet = '';
		$this->config_page_url = array("../smsdecanet/admin/smsdecanet_conf.php");
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(4,3);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,2);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("smsdecanet@smsdecanet");
		$this->const = array();
		$r=0;

		$this->const[$r][0] = "MAIN_MODULE_SMSDECANET_SMS";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "1";
		$this->const[$r][3] = "Decanet SMS Module";
		$this->const[$r][4] = 0;
		$r++;
		
		$this->tabs = array('thirdparty:+sendsms:SendSMS:smsdecanet@smsdecanet:/smsdecanet/send.php?id=__ID__');
		$this->rights = array();		// Permission array used by this module
		$r=0;
		$this->menus = array();			// List of menus to add
		$r=0;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=tools',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
					'type'=>'left',			// This is a Left menu entry
					'titre'=>'SendSMS',
					'mainmenu'=>'tools',
					'leftmenu'=>'smsdecanet',
					'url'=>'/smsdecanet/send.php',
					'langs'=>'smsdecanet@smsdecanet',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
					'position'=>100,
					'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>0);				// 0=Menu for internal users,1=external users, 2=both
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=smsdecanet',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
					'type'=>'left',			// This is a Left menu entry
					'titre'=>'SendOneSMS',
					'mainmenu'=>'smsdecanet',
					'leftmenu'=>'smsdecanet',
					'url'=>'/smsdecanet/send.php',
					'langs'=>'smsdecanet@smsdecanet',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
					'position'=>101,
					'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>0);				// 0=Menu for internal users,1=external users, 2=both
		$r++;
		/*$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=smsdecanet',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
					'type'=>'left',			// This is a Left menu entry
					'titre'=>'SendAllSMS',
					'mainmenu'=>'smsdecanet',
					'leftmenu'=>'smsdecanet',
					'url'=>'/smsdecanet/send.php',
					'langs'=>'smsdecanet@smsdecanet',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
					'position'=>102,
					'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>0);				// 0=Menu for internal users,1=external users, 2=both
		$r++;
*/

		
	}
	
	function sendRequest($donnees) {
		global $conf;
		$url = (intval($conf->global->DECANETSMS_SSL)==1)?'https':'http';
		$url='https://www.decanet.fr/api/sms.php';	
		foreach($donnees as $key=>$value) { $donnees_ctn .= $key.'='.$value.'&'; }
		rtrim($donnees_ctn,'&');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_POST,count($donnees));
		curl_setopt($ch,CURLOPT_POSTFIELDS,$donnees_ctn);
		$data=curl_exec($ch);
		curl_close($ch);
		return json_decode($data);
	}

	/**
	 *		\brief      Function called when module is enabled.
	 *					The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *					It also creates data directories.
	 *      \return     int             1 if OK, 0 if KO
	 */
	function init()
	{
		$sql = array();
		$this->sendRequest(array('dolibarr'=>$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']));
		$result=$this->load_tables();

		return $this->_init($sql);
	}

	/**
	 *		\brief		Function called when module is disabled.
	 *              	Remove from database constants, boxes and permissions from Dolibarr database.
	 *					Data directories are not deleted.
	 *      \return     int             1 if OK, 0 if KO
	 */
	function remove()
	{
		global $dolibarr_main_db_prefix;
		$sql = array();
		$sql[] = 'DELETE FROM '.$dolibarr_main_db_prefix.'const WHERE name="MAIN_MODULE_SMSDECANET_SMS"';
		return $this->_remove($sql);
	}


	/**
	 *		\brief		Create tables, keys and data required by module
	 * 					Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 					and create data commands must be stored in directory /mymodule/sql/
	 *					This function is called by this->init.
	 * 		\return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		//return $this->_load_tables('/smsdecanet/sql/');
	}
}

?>
