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
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

// The class name should start with a lower case mod for Dolibarr to pick it up
// so we ignore the Squiz.Classes.ValidClassName.NotCamelCaps rule.
// @codingStandardsIgnoreStart
/**
 *  Description and activation class for module MyModule
 */
class modBimpusertools extends DolibarrModules
{

    // @codingStandardsIgnoreEnd
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 3509000;  // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'bimpusertools';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
        // It is used to group modules by family in module setup page
        $this->family = "Bimp";
        // Module position in the family
        $this->module_position = 500;
        // Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        //$this->familyinfo = array('myownfamily' => array('position' => '001', 'label' => $langs->trans("MyOwnFamily")));
        // Module label (no space allowed), used if translation string 'ModuleMyModuleName' not found (MyModue is name of module).
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description, used if translation string 'ModuleMyModuleDesc' not found (MyModue is name of module).
        $this->description = "BimpUserTools";
        // Used only if file README.md and README-LL.md not found.


        $this->version = '1.0';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        // Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
        $this->special = 0;
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto = 'generic';


        // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
        // for default path (eg: /mymodule/core/xxxxx) (0=disable, 1=enable)
        // for specific path of parts (eg: /mymodule/core/modules/barcode)
        // for specific css file (eg: /mymodule/css/mymodule.css.php)
        $this->module_parts = array("triggers" => 1);

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/mymodule/temp","/mymodule/subdir");
        $this->dirs = array("/bimpusertools/data");

        // Config pages. Put here list of php page, stored into mymodule/admin directory, to use to setup module.
        //$this->config_page_url = array("setup.php@mymodule");
        // Dependencies
        $this->hidden = false;   // A condition to hide module
        $this->depends = array();  // List of module class names as string that must be enabled if this module is enabled
        $this->requiredby = array(); // List of module ids to disable if this one is disabled
        $this->conflictwith = array(); // List of module class names as string this module is in conflict with
        $this->phpmin = array(5, 0);     // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(4, 0); // Minimum version of Dolibarr required by module
        $this->langfiles = array("bimpusertools@bimpusertools");
        $this->warnings_activation = array();                     // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        $this->warnings_activation_ext = array();                 // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
        $this->const = array();
        $this->tabs = array();

        if (!isset($conf->mymodule) || !isset($conf->mymodule->enabled)) {
            $conf->mymodule = new stdClass();
            $conf->mymodule->enabled = 0;
        }

        // Dictionaries
        $this->dictionaries = array();
        $this->boxes = array();
        $this->cronjobs = array();
        $this->rights = array();  // Permission array used by this module
        $this->menu = array();   // List of menus to add
        $r = 0;
    }

    /**
     * 		Function called when module is enabled.
     * 		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * 		It also creates data directories
     *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
     *      @return     int             	1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        $sql = array();

        require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
        $name = 'module_version_' . strtolower($this->name);

        // Se fait que lors de l'installation du module
        if (BimpCore::getConf($name, '') == "") {
            BimpCore::setConf($name, floatval($this->version));
            $this->_load_tables('/' . strtolower($this->name) . '/sql/');
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
        return $this->_remove($sql, $options);
    }
}
