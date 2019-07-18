<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * 	\defgroup   mymodule     Module MyModule
 *  \brief      MyModule module descriptor.
 *
 *  \file       htdocs/mymodule/core/modules/modBimpCommercial.class.php
 *  \ingroup    mymodule
 *  \brief      Description and activation file for module MyModule
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


// The class name should start with a lower case mod for Dolibarr to pick it up
// so we ignore the Squiz.Classes.ValidClassName.NotCamelCaps rule.
// @codingStandardsIgnoreStart
/**
 *  Description and activation class for module MyModule
 */
class modBimpCommercial extends DolibarrModules
{
	// @codingStandardsIgnoreEnd
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
        global $langs,$conf;

        $this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 514566;		// TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'bimpcommercial';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
		// It is used to group modules by family in module setup page
		$this->family = "Bimp";
		// Module position in the family
		$this->module_position = 500;
		// Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '001', 'label' => $langs->trans("MyOwnFamily")));

		// Module label (no space allowed), used if translation string 'ModuleMyModuleName' not found (MyModue is name of module).
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleMyModuleDesc' not found (MyModue is name of module).
		$this->description = "BimpCommercial";
		// Used only if file README.md and README-LL.md not found.
		
                
                                    $this->version = '1.0';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='generic';
               

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /mymodule/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /mymodule/core/modules/barcode)
		// for specific css file (eg: /mymodule/css/mymodule.css.php)
		$this->module_parts = array("models"=>1, "triggers"=>1);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp","/mymodule/subdir");
		$this->dirs = array("/bimpcommercial/data");

		// Config pages. Put here list of php page, stored into mymodule/admin directory, to use to setup module.
		//$this->config_page_url = array("setup.php@mymodule");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->phpmin = array(5,0);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(4,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("bimpcommercial@bimpcommercial");
		$this->warnings_activation = array();                     // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array();                 // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array();
                
                
                
        $this->tabs = array(
//                    'thirdparty:+bimpcommercial:bimpcommercial:bimpcommercial@bimpcommercial:$user->rights->bimpcommercial->read:/bimpcommercial/tabs/bimpcommercial.php?socid=__ID__',
            'thirdparty:+commercial:Commercial:bimpcommercial:$user->rights->bimpcommercial->read:/bimpcommercial/index.php?fc=tabCommercial&id=__ID__'
        );

		// Array to add new pages in new tabs
		// Example: $this->tabs = array('objecttype:+tabname1:Title1:mylangfile@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__',  					// To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@mymodule:$user->rights->othermodule->read:/mymodule/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
        //                              'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
        // Can also be:	$this->tabs = array('data'=>'...', 'entity'=>0);
        //
		// where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in fundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in customer order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view
//        $this->tabs = array();

		if (! isset($conf->mymodule) || ! isset($conf->mymodule->enabled))
        {
        	$conf->mymodule=new stdClass();
        	$conf->mymodule->enabled=0;
        }

        // Dictionaries
		$this->dictionaries=array();
        /* Example:
        $this->dictionaries=array(
            'langs'=>'mylangfile@mymodule',
            'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),		// List of tables we want to see into dictonnary editor
            'tablib'=>array("Table1","Table2","Table3"),													// Label of tables
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),	// Request to select fields
            'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
            'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionary)
            'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
            'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array($conf->mymodule->enabled,$conf->mymodule->enabled,$conf->mymodule->enabled)												// Condition to show each dictionary
        );
        */


        // Boxes/Widgets
		// Add here list of php file(s) stored in mymodule/core/boxes that contains class to show a widget.
        $this->boxes = array();
        	//0=>array('file'=>'mymodulewidget1.php@mymodule','note'=>'Widget provided by MyModule','enabledbydefaulton'=>'Home'),
        	//1=>array('file'=>'mymodulewidget2.php@mymodule','note'=>'Widget provided by MyModule'),
        	//2=>array('file'=>'mymodulewidget3.php@mymodule','note'=>'Widget provided by MyModule')
        


		// Cronjobs (List of cron jobs entries to add when module is enabled)
		$this->cronjobs = array();
			//0=>array('label'=>'MyJob label', 'jobtype'=>'method', 'class'=>'/mymodule/class/mymodulemyjob.class.php', 'objectname'=>'MyModuleMyJob', 'method'=>'myMethod', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true)
		
		// Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true),
		//                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>true)
		// );


		// Permissions
		$this->rights = array();		// Permission array used by this module

		$r=0;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'lire';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';				// In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
		//$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)

		$r++;
                
                
                $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Modifié prix vente';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'priceVente';				// In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
		//$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)

		$r++;
                
                $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Modifié prix achat';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'priceAchat';				// In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
		//$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)

		$r++;
                
                $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Attribué les commerciaux';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'commerciauxToSoc';				// In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
		//$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)

		$r++;
                
                $this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Facturation d\'avance';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'factureAnticipe';				// In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
		//$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)

		$r++;

		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;

		// Add here entries to declare new menus

		// Example to declare a new Top Menu entry and its Left menu entry:
		/* BEGIN MODULEBUILDER TOPMENU */
		$this->menu[$r++]=array('fk_menu'=>'fk_mainmenu=commercial,fk_leftmenu=propals',			                // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',			                // This is a Top menu entry
								'titre'=>'Mes Propals',
								'mainmenu'=>'',
								'leftmenu'=>'',
								'url'=>'/bimpcommercial/index.php?fc=userPropals',
								'langs'=>'bimpcomm@bimpcommercial',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1000+$r,
								'enabled'=>'1',	// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
								'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both

		/* END MODULEBUILDER TOPMENU */

		// Example to declare a Left Menu entry into an existing Top menu entry:
		/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT
		$this->menu[$r++]=array(	'fk_menu'=>'fk_mainmenu=mymodule',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',			                // This is a Left menu entry
								'titre'=>'List MyObject',
								'mainmenu'=>'mymodule',
								'leftmenu'=>'mymodule',
								'url'=>'/mymodule/myobject_list.php',
								'langs'=>'mymodule@mymodule',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1000+$r,
								'enabled'=>'$conf->mymodule->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
		$this->menu[$r++]=array(	'fk_menu'=>'fk_mainmenu=mymodule,fk_leftmenu=mymodule',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',			                // This is a Left menu entry
								'titre'=>'New MyObject',
								'mainmenu'=>'mymodule',
								'leftmenu'=>'mymodule',
								'url'=>'/mymodule/myobject_page.php?action=create',
								'langs'=>'mymodule@mymodule',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1000+$r,
								'enabled'=>'$conf->mymodule->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
		END MODULEBUILDER LEFTMENU MYOBJECT */


		// Exports
		$r=1;

		// Example:
		/* BEGIN MODULEBUILDER EXPORT MYOBJECT
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='MyModule';	                         // Translation key (used only if key ExportDataset_xxx_z not found)
        $this->export_enabled[$r]='1';                               // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
        $this->export_icon[$r]='generic:MyModule';					 // Put here code of icon then string for translation key of module name
		//$this->export_permission[$r]=array(array("mymodule","level1","level2"));
        $this->export_fields_array[$r]=array('t.rowid'=>"Id",'t.ref'=>'Ref','t.label'=>'Label','t.datec'=>"DateCreation",'t.tms'=>"DateUpdate");
		$this->export_TypeFields_array[$r]=array('t.rowid'=>'Numeric', 't.ref'=>'Text', 't.label'=>'Label', 't.datec'=>"Date", 't.tms'=>"Date");
		// $this->export_entities_array[$r]=array('t.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company','s.phone'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
		// $this->export_dependencies_array[$r]=array('invoice_line'=>'fd.rowid','product'=>'fd.rowid');   // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		// $this->export_sql_start[$r]='SELECT DISTINCT ';
		// $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'myobject as t';
		// $this->export_sql_order[$r] .=' ORDER BY t.ref';
		// $r++;
		END MODULEBUILDER EXPORT MYOBJECT */

	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options='')
	{
		$sql = array();
                
                require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';
                $name = 'module_version_'.strtolower($this->name);
                if(BimpCore::getConf($name) == "") {
                    BimpCore::setConf($name, floatval($this->version));
                    $this->_load_tables('/'.strtolower($this->name).'/sql/');
                }
                

                //$sql[]="DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom like 'einstein';";
                $sql[]="DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom like 'azur';";
                $sql[] = "INSERT INTO `".MAIN_DB_PREFIX."document_model` ( `nom`, `entity`, `type`, `libelle`, `description`) VALUES( 'bimpdevis', 1, 'propal', 'Devis BIMP', NULL);";
                $sql[] = "INSERT INTO `".MAIN_DB_PREFIX."document_model` ( `nom`, `entity`, `type`, `libelle`, `description`) VALUES( 'bimployerdevis', 1, 'propal', 'Proposition loyer', NULL);";
                
                //Facture
                $sql[]="DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom like 'crabe';";
                $sql[] = "INSERT INTO `".MAIN_DB_PREFIX."document_model` ( `nom`, `entity`, `type`, `libelle`, `description`) VALUES( 'bimpfact', 1, 'invoice', 'Facture', NULL);";
                
                $sql[]="DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom like 'einstein';";
                $sql[] = "INSERT INTO `".MAIN_DB_PREFIX."document_model` ( `nom`, `entity`, `type`, `libelle`, `description`) VALUES( 'bimpcommande', 1, 'order', 'Commande', NULL);";
                
                // Dash board commercial
                $sql[] = "UPDATE " . MAIN_DB_PREFIX . "menu SET `url`='/bimpcommercial/index.php?fc=tabCommercial&amp;mainmenu=commercial&amp;leftmenu=' WHERE `type` LIKE 'top' AND `mainmenu` LIKE 'commercial'";

		//$this->_load_tables('/bimpcommercial/sql/');
                
                $extrafields = new ExtraFields($this->db);
                $extrafields->addExtraField('crt', 'Remise CRT', 'varchar', 1, 10, 'product');
                $extrafields->addExtraField('ref_constructeur', 'Réf. constructeur', 'int', 1, 255, 'product');
                $extrafields->addExtraField('pa_prevu', 'Prix d\'achat HT prévu', 'decimal', 1, '24,8', 'product', 0, 0, 0);
                $extrafields->addExtraField('infos_pa', 'Informations prix d\'achat', 'text', 1, 2000, 'product');
                
                $extrafields->addExtraField('zone_vente', 'Zone de vente', 'int', 1, 255, 'propal', 0, 0, 1);
                $extrafields->addExtraField('zone_vente', 'Zone de vente', 'int', 1, 255, 'commande', 0, 0, 1);
                $extrafields->addExtraField('zone_vente', 'Zone de vente', 'int', 1, 255, 'facture', 0, 0, 1);
                
                $extrafields->addExtraField('zone_vente', 'Zone de vente', 'int', 1, 255, 'commande_fournisseur', 0, 0, 1);
                $extrafields->addExtraField('zone_vente', 'Zone de vente', 'int', 1, 255, 'facture_fourn', 0, 0, 1);
                
                // Nécessaire pour valider tous les produits actuels seulement si le champ validate n\'existe pas déjà (Etant donné qu'on défini la valeur par défaut à 0): 
                if (!$this->db->num_rows($this->db->query('SELECT `rowid` FROM '.MAIN_DB_PREFIX.'extrafields WHERE elementtype = \'product\' AND `name` = \'validate\''))) {
                    $extrafields->addExtraField('validate', 'Validé', 'boolean', 1, 1, 'product', 0, 0, 0);
                    
                    $this->db->query('UPDATE ' . MAIN_DB_PREFIX . 'product_extrafields SET `validate` = 1 WHERE 1');
                }

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param      string	$options    Options when enabling module ('', 'noboxes')
	 * @return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
                
                $sql[]="DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom like 'bimpcommercial';";
                $sql[]="DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom like 'bimployerdevis';";
                $sql[]="DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom like 'bimpdevis';";
                $sql[]="DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom like 'bimpcommande';";

//                $sql[] = "INSERT INTO `".MAIN_DB_PREFIX."document_model` ( `nom`, `entity`, `type`, `libelle`, `description`) VALUES( 'einstein', 1, 'propal', 'einstein', NULL);";
                $sql[] = "INSERT INTO `".MAIN_DB_PREFIX."document_model` ( `nom`, `entity`, `type`, `libelle`, `description`) VALUES( 'azur', 1, 'propal', 'azur', NULL);";
                
                $sql[] = "INSERT INTO `".MAIN_DB_PREFIX."document_model` ( `nom`, `entity`, `type`, `libelle`, `description`) VALUES( 'einstein', 1, 'order', 'einstein', NULL);";
                
                
                //Facture
                $sql[]="DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom like 'bimpfact';";
                $sql[] = "INSERT INTO `".MAIN_DB_PREFIX."document_model` ( `nom`, `entity`, `type`, `libelle`, `description`) VALUES( 'crabe', 1, 'invoice', 'crabe', NULL);";
                
                // Dash board commercial
                $sql[] = "UPDATE " . MAIN_DB_PREFIX . "menu SET `url`='/comm/index.php?mainmenu=commercial&amp;leftmenu=' WHERE `type` LIKE 'top' AND `mainmenu` LIKE 'commercial'";

		return $this->_remove($sql, $options);
	}

}
