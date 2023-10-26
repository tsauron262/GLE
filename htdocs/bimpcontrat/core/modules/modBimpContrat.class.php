<?php

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modBimpContrat extends DolibarrModules
{

    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;
        $this->numero = 3505000;  // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
        $this->rights_class = 'bimpcontract';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
        // It is used to group modules by family in module setup page
        $this->family = "BIMP";
        // Module position in the family
        $this->module_position = 520;
        // Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        //$this->familyinfo = array('myownfamily' => array('position' => '001', 'label' => $langs->trans("MyOwnFamily")));
        // Module label (no space allowed), used if translation string 'ModuleMyModuleName' not found (MyModue is name of module).
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description, used if translation string 'ModuleMyModuleDesc' not found (MyModue is name of module).
        $this->description = "Contrats (V2)";
        // Used only if file README.md and README-LL.md not found.
        $this->descriptionlong = "Contrats (V2)";

        $this->editor_url = 'https://www.bimp.fr';

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '1.0';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto = 'generic';

        // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
        // for default path (eg: /mymodule/core/xxxxx) (0=disable, 1=enable)
        // for specific path of parts (eg: /mymodule/core/modules/barcode)
        // for specific css file (eg: /mymodule/css/mymodule.css.php)
        $this->module_parts = array(
            "models" => 1
        );

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/mymodule/temp","/mymodule/subdir");
        $this->dirs = array();
        $this->tabs = array(
        );
//            'categories_0:+restreindre:Restreindre:@bimpproductbrowser:$user->rights->bimpproductbrowser->read:/bimpproductbrowser/browse.php?id=__ID__',
        // Dictionaries
        $this->dictionaries = array();

        $this->rights = array();
        $r = 0;
//        $this->rights[$r][0] = $this->numero + $r;
//        $this->rights[$r][1] = 'Validation du contrat après la demande';
//        $this->rights[$r][3] = 0;
//        $this->rights[$r][4] = 'to_validate';
//        $r++;

        $this->menu = array();   // List of menus to add
        $r = 1;
    }

    public function init($options = '')
    {
        $sql = array();

        require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
        $name = 'module_version_' . strtolower($this->name);
        if (BimpCore::getConf($name, '') == "") {
            BimpCore::setConf($name, floatval($this->version));
            $this->_load_tables('/' . strtolower($this->name) . '/sql/');
        }

        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        //$extrafields = new ExtraFields($this->db);
        //$extrafields->delete('service_content', 'product');
        return $this->_remove($sql, $options);
    }
}
