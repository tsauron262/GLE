<?php

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modBimpTechnique extends DolibarrModules {

    public function __construct($db) {
        global $langs, $conf;

        $this->db = $db;
        $this->numero = 7523599;
        $this->rights_class = 'bimptechnique';
        $this->family = "BIMP";
        $this->module_position = 600;
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Bimp technique";
        $this->descriptionlong = "Bimp Technique";
        $this->editor_url = 'https://www.bimp.fr';
        $this->version = '1.0';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->picto = 'generic';
        $this->dirs = array();
        $this->tabs = array();
        $this->module_parts = array('models' => 1);
        $this->dictionaries = array();
        $this->rights = array(); 
        $r=0;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Plannifier une intervention';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'plannified';
        $r++;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Supprimer une FI';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'delete';
        $r++;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Abandoner une FI';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'abort';
        $r++;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Voir la signature sur les informations la FI';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'view_signature_infos_fi';
        $r++;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Voir les stats';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'view_stats';
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
       return $this->_init($sql, $options);
    }

   
    public function remove($options = '') {
        $sql = array();
        return $this->_remove($sql, $options);
    }

}
