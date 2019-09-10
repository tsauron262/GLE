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
 *  \file       htdocs/bimpequipment/core/modules/modBimpEquipment.class.php
 *  \ingroup    bimpequipment
 *  \brief      Descriptor for the module bimpequipment
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

// The class name should start with a lower case mod for Dolibarr to pick it up
// so we ignore the Squiz.Classes.ValidClassName.NotCamelCaps rule.
// @codingStandardsIgnoreStart
/**
 *  Description and activation class for module modBimpEquipment
 */
class modBimpEquipment extends DolibarrModules {

    // @codingStandardsIgnoreEnd
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db) {
        global $langs, $conf;

        $this->db = $db;

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 758442;  // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'bimpequipment';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
        // It is used to group modules by family in module setup page
        $this->family = "products";
        // Module position in the family
        $this->module_position = 500;
        // Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        //$this->familyinfo = array('myownfamily' => array('position' => '001', 'label' => $langs->trans("MyOwnFamily")));
        // Module label (no space allowed), used if translation string 'ModuleMyModuleName' not found (MyModue is name of module).
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description, used if translation string 'ModuleMyModuleDesc' not found (MyModue is name of module).
        $this->description = "Gestion des équipements";
        // Used only if file README.md and README-LL.md not found.
        $this->descriptionlong = "Gestion des équipements";

        $this->editor_url = 'https://www.example.com';

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '1.0';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto = 'generic';

        $this->rights = array();  // Permission array used by this module

        $r = 0;
        $this->rights[$r][0] = 88151;
        $this->rights[$r][1] = 'Créer inventaire';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'inventory';
        $this->rights[$r][5] = 'create';
        $r++;
       
        $this->rights[$r][0] = 88152;
        $this->rights[$r][1] = 'Voir le détail des inventaire';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'inventory';
        $this->rights[$r][5] = 'read';
        $r++;

        $this->rights[$r][0] = 88153;
        $this->rights[$r][1] = 'Accès admin caisse';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'caisse_admin';
        $this->rights[$r][5] = 'read';
        $r++;

        $this->rights[$r][0] = 88154;
        $this->rights[$r][1] = 'Accès caisse';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'caisse';
        $this->rights[$r][5] = 'read';
        $r++;

        $this->rights[$r][0] = 88155;
        $this->rights[$r][1] = 'Fermer transfert';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'transfer';
        $this->rights[$r][5] = 'close';
        $r++;
        
        $this->rights[$r][0] = 88156;
        $this->rights[$r][1] = 'Ouvrir inventaire';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'inventory';
        $this->rights[$r][5] = 'open';
        $r++;
        
        $this->rights[$r][0] = 88157;
        $this->rights[$r][1] = 'Fermer inventaire';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'inventory';
        $this->rights[$r][5] = 'close';
        $r++;
        // Array to add new pages in new tabs
        // Example: $this->tabs = array('objecttype:+tabname1:Title1:mylangfile@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__',  					// To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@mymodule:$user->rights->othermodule->read:/mymodule/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
        //                              'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
        $this->tabs = array(
            //'order:+bimporderclient:BimpExpédition:@bimpequipment:$user->rights->commande->lire:/bimpequipment/manageequipment/viewOrderClient.php?id=__ID__',
            'order:+bimplogisitquecommande:Logistique:@bimpequipment:$user->rights->commande->lire:/bimplogistique/index.php?fc=commande&id=__ID__',
            'supplier_order:+bimpordersupplier:Livrer:@bimpequipment:$user->rights->fournisseur->facture->lire:/bimpequipment/manageequipment/viewOrderSupplier.php?id=__ID__',
            'stock:+bimpstock:Stock à date:@bimpequipment:$user->rights->stock->lire:/bimpequipment/tabs/stock/card.php?id=__ID__'
        );
//            'categories_0:+restreindre1:Impliquant:@bimpproductbrowser:$user->rights->bimpproductbrowser->read:/bimpproductbrowser/browse.php?id=__ID__&mode=1',

        if (!isset($conf->modBimpEquipment) || !isset($conf->modBimpEquipment->enabled)) {
            $conf->modBimpEquipment = new stdClass();
            $conf->modBimpEquipment->enabled = 0;
        }
        
        
        
        
        $this->menus = array();            // List of menus to add
        $r=0;
        $this->menu[$r]=array(
                            'type'=>'top',
                            'titre'=>'Logistique',
                            'mainmenu'=>'bimpequipment',
                            'leftmenu'=>'0',        // To say if we can overwrite leftmenu
                            'url'=>'/bimptransfer/?fc=dashBoard',
                            'langs'=>'',
                            'position'=>1099,
                            'perms'=>'1',
                            'target'=>'',
                            'user'=>0);
    }

    /**
     * 		Function called when module is enabled.
     * 		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * 		It also creates data directories
     *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
     *      @return     int             	1 if OK, 0 if KO
     */
    public function init($options = '') {
        $sql = array();

        $this->_load_tables('/bimpequipment/sql/');

        // Add restrictions to all categories son of root
        // Create extrafields
//        include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
//        $extrafields = new ExtraFields($this->db);
        //$result1=$extrafields->addExtraField('myattr1', "New Attr 1 label", 'boolean', 1, 3, 'thirdparty');
        //$result2=$extrafields->addExtraField('myattr2', "New Attr 2 label", 'string', 1, 10, 'project');

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
    public function remove($options = '') {
        $sql = array();

        return $this->_remove($sql, $options);
    }

}
