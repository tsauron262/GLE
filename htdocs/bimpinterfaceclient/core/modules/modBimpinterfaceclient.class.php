<?php

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

class modBimpinterfaceclient extends DolibarrModules
{

	public function __construct($db)
	{
        global $langs,$conf;

        $this->db = $db;

		$this->numero = 553554356;		// TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'bimpinterfaceclient';

		$this->family = "Bimp";
		// Module position in the family
		$this->module_position = 500;
		// Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '001', 'label' => $langs->trans("MyOwnFamily")));

		// Module label (no space allowed), used if translation string 'ModuleMyModuleName' not found (MyModue is name of module).
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleMyModuleDesc' not found (MyModue is name of module).
		$this->description = "Bimp Interface Client";
		// Used only if file README.md and README-LL.md not found.
		
                
                                    $this->version = '0.2';
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
		$this->module_parts = array("models"=>1, "triggers"=>0);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp","/mymodule/subdir");
		$this->dirs = array("/bimpinterfaceclient/data");

		// Config pages. Put here list of php page, stored into mymodule/admin directory, to use to setup module.
		//$this->config_page_url = array("setup.php@mymodule");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->phpmin = array(5,0);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(4,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("bimpinterfaceclient@bimpinterfaceclient"); // Fichier@Module
		$this->warnings_activation = array();                     // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array();                 // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)

		$this->const = array();
                
                
         $this->tabs = array(
            'thirdparty:+client_user:Utilisateurs:@bimpsupport:$user->rights->bimpinterfaceclient->read:/bimpinterfaceclient/admin.php?fc=user&socid=__ID__'
        );

		if (! isset($conf->mymodule) || ! isset($conf->mymodule->enabled))
        {
        	$conf->mymodule=new stdClass();
        	$conf->mymodule->enabled=0;
        }

        // Dictionaries
		$this->dictionaries=array();
        
        $this->boxes = array();
        	
		$this->cronjobs = array();
		
		$this->rights = array();		// Permission array used by this module

		$r=0;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'lire';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';				// In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
		//$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)

		$r++;
                
                
		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;

		// Add here entries to declare new menus

		// Example to declare a new Top Menu entry and its Left menu entry:
		/* BEGIN MODULEBUILDER TOPMENU */
				                // 0=Menu for internal users, 1=external users, 2=both

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

	}

	public function init($options='')
	{
		global $conf;
		$sql = array();
                $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("default_id_commercial", 62)'; //
                require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';
                $name = 'module_version_'.strtolower($this->name);
                if(BimpCore::getConf($name) == "") {
                    BimpCore::setConf($name, floatval($this->version));
                    $this->_load_tables('/'.strtolower($this->name).'/sql/');
                }
                
		return $this->_init($sql, $options);
	}

	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}

}
