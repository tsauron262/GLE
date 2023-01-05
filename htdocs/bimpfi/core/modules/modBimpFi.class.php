<?php

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modBimpFi extends DolibarrModules {

    public function __construct($db) {
        global $langs, $conf;

        $this->db = $db;
        $this->numero = 9990099;
        $this->rights_class = 'bimpfi';
        $this->family = "BIMP";
        $this->module_position = 520;
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Fiches d'interventions";
        $this->descriptionlong = "Bimp Fiches d'interventions";
        $this->editor_url = 'https://www.bimp.fr';
        $this->version = '1.0';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->picto = 'generic';
        $this->dirs = array();
        $this->tabs = array();
        $this->dictionaries = array();
        $this->rights = array(); 
        $r=0;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Créer une FI';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'fi_create';
        $r++;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Supprimer une FI';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'fi_delete';
        $r++;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Valider une FI';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'fi_validate';
        $r++;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Plannifier une FI';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'fi_planning';
        $this->menu = array();
        $r++;

    }

    public function init($options = '') {
        $sql = array();
        
        require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';
        $name = 'module_version_'.strtolower($this->name);
        if(BimpCore::getConf($name) == "") {
            BimpCore::setConf($name, floatval($this->version));
            $this->_load_tables('/'.strtolower($this->name).'/sql/');
        }
        
        return $this->_init($sql, $options);
    }

   
    public function remove($options = '') {
        $sql = array();        
       
        return $this->_remove($sql, $options);
    }

}
