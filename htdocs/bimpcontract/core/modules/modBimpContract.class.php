<?php

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modBimpContract extends DolibarrModules {

    public function __construct($db) {
        global $langs, $conf;

        $this->db = $db;
        $this->numero = 7523423;  // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
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
        $this->description = "Contrats";
        // Used only if file README.md and README-LL.md not found.
        $this->descriptionlong = "Contrats";

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

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/mymodule/temp","/mymodule/subdir");
        $this->dirs = array();
        $this->tabs = array(
           
        );
//            'categories_0:+restreindre:Restreindre:@bimpproductbrowser:$user->rights->bimpproductbrowser->read:/bimpproductbrowser/browse.php?id=__ID__',

        if (!isset($conf->modBimpContratAuto) || !isset($conf->modBimpContratAuto->enabled)) {
            $conf->modBimpContratAuto = new stdClass();
            $conf->modBimpContratAuto->enabled = 0;
        }

        // Dictionaries
        $this->dictionaries = array();
     
        $this->rights = array(); 
        $r=0;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Validation du contrat après la demande';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'to_validate';
        $r++;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Anticiper la fermeture du contrat';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'to_anticipate';
        $r++;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Remplacer un numéro de série';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'to_replace_serial';
        $r++;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Générer les documents PDF';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'to_generate';
        $r++;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Créer un contrat à partir d\'une propal avec n\'importe quel statut sauf BROUILLON';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'to_create_from_propal_all_status'; 
        $r++;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Arrêter la facturation échéancier';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'stop_bills_timeline'; 
        $r++;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Activer la facturation automatique';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'auto_billing'; 
        $this->menu = array();   // List of menus to add
        $r++;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Changer la périodicité de facturation';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'change_periodicity'; 
        $this->menu = array();   // List of menus to add
        $r++;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Réouvrir un contrat ALL THE TIME';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'to_reopen'; 
        $this->menu = array();   // List of menus to add
        $r = 1;

    }

    public function init($options = '') {
        $sql = array();
        
        require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';
        $name = 'module_version_'.strtolower($this->name);
        if(BimpCore::getConf($name) == "") {
            BimpCore::setConf($name, floatval($this->version));
            $this->_load_tables('/'.strtolower($this->name).'/sql/');
        }
        
        $extrafields = new ExtraFields($this->db);
        //$extrafields->addExtraField('service_content', 'Services Compris', 'chkbxlst', 103, null, 'product', 0, 0, "", 'a:1:{s:7:"options";a:1:{s:44:"bcontract_productservices:titre:id::active=1";N;}}', 1, "", 1, 1, "", "", "", 1);
        $extrafields->addExtraField('objet_contrat', 'Objet du contrat', 'varchar', 104, 100, 'contrat');
        $extrafields->addExtraField('contrat_source', 'Contrat initial', 'int', 104, 11, 'contrat');
        $extrafields->addExtraField('entrepot', 'Entrepot', 'varchar', 104, 8, 'contrat');
        $extrafields->addExtraField('end_date_contrat', 'Date de fin', 'date', 104, 100, 'contrat');
        $extrafields->addExtraField('end_date_reel', 'Date réelle de fin', 'date', 104, 100, 'contrat');
        $extrafields->addExtraField('anticipate_close_note', 'Note de cloture anticipée', 'varchar', 104, 255, 'contrat');
        $extrafields->addExtraField('show_fact_line_in_pdf', 'Afficher ligne facturation PDF', 'boolean', 0, 0, 'contrat', 0, 0, 1);
        //$extrafields->addExtraField('nb_materiel', 'Nombre de machines couvertes', 'int', 105, 100, 'contratdet');
        //$extrafields->addExtraField('serials', 'Numéros de série', 'text', 106, 100, 'contratdet');
        //$extrafields->update('service_content', 'Services Compris', 'chkbxlst', null, 'product', 0, 0, 103, 'a:1:{s:7:"options";a:1:{s:44:"bcontract_productservices:titre:id::use_in_contract=1";N;}}', 1, '', 1);
        return $this->_init($sql, $options);
    }

   
    public function remove($options = '') {
        $sql = array();        
        //$extrafields = new ExtraFields($this->db);
        //$extrafields->delete('service_content', 'product');
        return $this->_remove($sql, $options);
    }

}
