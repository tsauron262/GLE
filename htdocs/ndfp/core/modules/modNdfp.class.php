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
 * 		\defgroup   modNdfp     Module Ndfp
 *      \file       htdocs/core/modules/modNdfp.class.php
 *      \ingroup    modNdfp
 *      \brief      Description and activation file for module modNdfp
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 * 		\class      modNdfp
 *      \brief      Description and activation class for module modNdfp
 */
class modNdfp extends DolibarrModules
{
	/**
	 *   \brief      Constructor. Define names, constants, directories, boxes, permissions
	 *   \param      DB      Database handler
	 */
	function modNdfp($DB)
	{
        global $langs, $conf;

        $this->db = $DB;
		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 70300;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'ndfp';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "Synopsis";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = 'ndfp';
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Gestion avancée des notes de frais et déplacements.";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.2.2';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'ndfp@ndfp';

		// Defined if the directory /mymodule/includes/triggers/ contains triggers or not
		$this->triggers = 0;

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array("/ndfp");
		$r=0;

		// Relative path to module style sheet if exists. Example: '/mymodule/css/mycss.css'.
		//$this->style_sheet = '/mymodule/mymodule.css.php';

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array('config.php@ndfp');

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->conflictwith = array('modDeplacement');
		$this->phpmin = array(5,0);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("ndfp");

		// Constants
		$this->const = array(0 => array('NDFP_ADDON','chaine','uranus','',0),
                             1 => array('NDFP_ADDON_PDF','chaine','calamar','', 0),
                             2 => array('NDFP_DIR_OUTPUT', 'chaine', DOL_DATA_ROOT.'/ndfp', '', 0),
                             3 => array('NDFP_SUBPERMCATEGORY_FOR_DOCUMENTS', 'chaine', 'myactions', '', 0));

        $this->tabs = array();

        // Dictionnaries
        $this->dictionnaries = array();

        // Boxes
		// Add here list of php file(s) stored in includes/boxes that contains class to show a box.
        $this->boxes = array();			// List of boxes
	    $r = 0;
        $this->boxes[$r][1] = "box_ndfp.php@ndfp";

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r = 0;
		$this->rights[$r][0] = 70301;
		$this->rights[$r][1] = 'Créer/Modifier les notes de frais liées à ce compte';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'myactions';
        $this->rights[$r][5] = 'create';


		$r++;
		$this->rights[$r][0] = 70302;
		$this->rights[$r][1] = 'Voir les notes de frais liées à ce compte';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'myactions';
        $this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = 70303;
		$this->rights[$r][1] = 'Supprimer les notes de frais liées à ce compte';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'myactions';
        $this->rights[$r][5] = 'delete';

		$r++;
		$this->rights[$r][0] = 70304;
		$this->rights[$r][1] = 'Envoyer par mail les notes de frais liées à ce compte';
		$this->rights[$r][2] = 's';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'myactions';
        $this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 70305;
		$this->rights[$r][1] = 'Valider les notes de frais liées à ce compte';
		$this->rights[$r][2] = 'v';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'myactions';
        $this->rights[$r][5] = 'validate';

		$r++;
		$this->rights[$r][0] = 70306;
		$this->rights[$r][1] = 'Dévalider les notes de frais liées à ce compte';
		$this->rights[$r][2] = 'u';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'myactions';
        $this->rights[$r][5] = 'unvalidate';


		$r++;
		$this->rights[$r][0] = 70307;
		$this->rights[$r][1] = 'Emmettre des paiements sur les notes de frais liées à ce compte';
		$this->rights[$r][2] = 'e';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'myactions';
        $this->rights[$r][5] = 'payment';

 		$r++;
		$this->rights[$r][0] = 70309;
		$this->rights[$r][1] = 'Créer/Modifier les notes de frais de tout le monde';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'allactions';
        $this->rights[$r][5] = 'create';


		$r++;
		$this->rights[$r][0] = 70310;
		$this->rights[$r][1] = 'Voir les notes de frais de tout le monde';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'allactions';
        $this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = 70311;
		$this->rights[$r][1] = 'Supprimer les notes de frais de tout le monde';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'allactions';
        $this->rights[$r][5] = 'delete';


		$r++;
		$this->rights[$r][0] = 70312;
		$this->rights[$r][1] = 'Envoyer par mail les notes de frais de tout le monde';
		$this->rights[$r][2] = 's';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'allactions';
        $this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 70313;
		$this->rights[$r][1] = 'Valider les notes de frais de tout le monde';
		$this->rights[$r][2] = 'v';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'allactions';
        $this->rights[$r][5] = 'validate';

		$r++;
		$this->rights[$r][0] = 70314;
		$this->rights[$r][1] = 'Dévalider les notes de frais de tout le monde';
		$this->rights[$r][2] = 'u';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'allactions';
        $this->rights[$r][5] = 'unvalidate';


		$r++;
		$this->rights[$r][0] = 70315;
		$this->rights[$r][1] = 'Emmettre des paiements sur les notes de frais de tout le monde';
		$this->rights[$r][2] = 'e';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'allactions';
        $this->rights[$r][5] = 'payment';


		// Main menu entries
		$this->menu = array();			// List of menus to add

        $r=0;
        $this->menu[$r]=array(
            'fk_menu'=>'fk_mainmenu=accountancy',			// Put 0 if this is a top menu
        	'type'=> 'left',			// This is a Top menu entry
        	'titre'=> $langs->trans('MenuTitle'),
        	'mainmenu'=> 'accountancy',
        	'leftmenu'=> 'ndfp',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
			'url'=> '/ndfp/ndfp.php',
			'langs'=> 'ndfp',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=> 100,
			'enabled'=> '1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
			'perms'=> '$user->rights->ndfp->allactions->read || $user->rights->ndfp->myactions->read',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=> '',
			'user'=> 2	// 0=Menu for internal users, 1=external users, 2=both
        );


        $r++;
        $this->menu[$r]=array(
            'fk_menu'=>'fk_mainmenu=accountancy,fk_leftmenu=ndfp',			// Put 0 if this is a top menu
        	'type'=> 'left',			// This is a Top menu entry
        	'titre'=> $langs->trans('NewNdfp'),
        	'mainmenu'=> '',
        	'leftmenu'=> '',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
			'url'=> '/ndfp/ndfp.php?action=create',
			'langs'=> 'ndfp',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=> 101,
			'enabled'=> '1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
			'perms'=> '$user->rights->ndfp->allactions->create || $user->rights->ndfp->myactions->create',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=> '',
			'user'=> 2
        );

        $r++;
        $this->menu[$r]=array(
            'fk_menu'=>'fk_mainmenu=accountancy,fk_leftmenu=ndfp',			// Put 0 if this is a top menu
        	'type'=> 'left',			// This is a Top menu entry
        	'titre'=> $langs->trans('UnpaidNdfp'),
        	'mainmenu'=> '',
        	'leftmenu'=> '',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
			'url'=> '/ndfp/ndfp.php?filter=unpaid',
			'langs'=> 'ndfp',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=> 102,
			'enabled'=> '1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
			'perms'=> '$user->rights->ndfp->allactions->read || $user->rights->ndfp->myactions->read',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=> '',
			'user'=> 2
        );

        $r++;
        $this->menu[$r]=array(
            'fk_menu'=>'fk_mainmenu=accountancy,fk_leftmenu=ndfp',			// Put 0 if this is a top menu
        	'type'=> 'left',			// This is a Top menu entry
        	'titre'=> $langs->trans('PaymentsNdfp'),
        	'mainmenu'=> '',
        	'leftmenu'=> '',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
			'url'=> '/ndfp/payment.php',
			'langs'=> 'ndfp',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=> 103,
			'enabled'=> '1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
			'perms'=> '$user->rights->ndfp->allactions->payment || $user->rights->ndfp->myactions->payment',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=> '',
			'user'=> 2
        );

	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories.
	 *      @return     int             1 if OK, 0 if KO
	 */
	function init()
	{
	   global $conf;

		$sql = array();

		$result = $this->load_tables();

		return $this->_init($sql);
	}

	/**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted.
	 *      @return     int             1 if OK, 0 if KO
	 */
	function remove()
	{
		$sql = array();

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
		return $this->_load_tables('/ndfp/sql/');
	}
}

?>
