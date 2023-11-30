<?php

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modBimptocegid extends DolibarrModules {

    public function __construct($db) {
        global $langs, $conf;

        $this->db = $db;
        $this->numero = 51458653;
        $this->rights_class = 'bimptocegid';
        $this->family = "Bimp";
        $this->module_position = 500;
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Interface pour les exports vers CEGID";
        $this->version = '1.0';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->special = 0;
        $this->picto = 'generic';
        $this->module_parts = array("models" => 1, "triggers" => 0);
        $this->dirs = array("/bimptocegid/data");
        $this->hidden = false;
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->phpmin = array(5, 0);
        $this->need_dolibarr_version = array(4, 0);
        $this->langfiles = array("bimptocegid@bimptocegid");
        $this->warnings_activation = array();
        $this->warnings_activation_ext = array();
        $this->const = array();
        $this->tabs = array();

        if (!isset($conf->mymodule) || !isset($conf->mymodule->enabled)) {
            $conf->mymodule = new stdClass();
            $conf->mymodule->enabled = 0;
        }

        $this->dictionaries = array();
        $this->boxes = array();
        $this->cronjobs = array();
        $this->rights = array();

        $r = 0;
        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'lire';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'read';
        $r++;


        $this->menu = array();
    }

    public function init($options = '') {
        global $conf;

        $sql = array();

        
        
        $extrafields = new ExtraFields($this->db);
        $extrafields->addExtrafield('type_compta', 'Classement du produit en comptabilitée', 'select', 200, null, 'product', 0, 0, 0, 'a:1:{s:7:"options";a:3:{i:0;s:11:"Pas de type";i:1;s:7:"Produit";i:2;s:7:"Service";}}', 1, null, 1);
        
        require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
        $name = 'module_version_' . strtolower($this->name);
        if (BimpCore::getConf($name) == "") { // C'est la première fois qu'on install le module
            
            // DEEE
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("vente_dee_fr", 70882000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("vente_dee_ue", 70882000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_dee_fr", 61111000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_dee_ue", 61113000, "bimptocegid")';
            // France
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("vente_produit_fr", 70700000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("vente_service_fr", 70600000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_produit_fr", 60700000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_service_fr", 60410000, "bimptocegid")';
            // Europe
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("vente_produit_ue", 70792000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("vente_service_ue", 70692100, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_produit_ue", 60794000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_service_ue", 60492100, "bimptocegid")';
            // Export
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("vente_produit_ex", 70790000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("vente_service_ex", 70691100, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_produit_ex", 60790000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_service_ex", 60491100, "bimptocegid")';
            //TVA
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("autoliquidation_tva_666", 44566600, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("autoliquidation_tva_711", 44571100, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("vente_tva_fr", 44571000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("vente_tva_ue", 44571200, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("vente_tva_null", 70750100, "bimptocegid")';
            //$sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("vente_tva_null_service", 70650100)';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_tva_fr", 44566100, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_tva_null", 60780000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_tva_null_service", 60480000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_tva_france_01", 44560100, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_tva_france_02", 44560200, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_tva_france_03", 44560300, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_tva_france_04", 44560400, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_tva_france_05", 44560500, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_tva_france_06", 44560600, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_tva_france_07", 44560700, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_tva_france_08", 44560800, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_tva_france_09", 44560900, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_tva_france_10", 44561000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_tva_france_11", 44561100, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_tva_france_12", 44561200, "bimptocegid")';
            // Remise fournisseur
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("avoir_fournisseur_fr", 60700000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("avoir_fournisseur_ue", 60794000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("avoir_fournisseur_ex", 60790000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("rfa_fournisseur_fr", 60970000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("rfa_fournisseur_ue", 60973000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("rfa_fournisseur_ex", 60974000, "bimptocegid")';
            // Ca   s particulier Apple
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("achat_fournisseur_apple", 60793000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("avoir_fournisseur_apple", 60793000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("rfa_fournisseur_apple", 60973000, "bimptocegid")';
            // Frais de port achat
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("frais_de_port_achat_fr", 62410000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("frais_de_port_achat_ue", 62413000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("frais_de_port_achat_ex", 62419000, "bimptocegid")';
            // Frais de port vente
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("frais_de_port_vente_fr", 70850000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("frais_de_port_vente_ue", 70859300, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("frais_de_port_vente_ex", 70859000, "bimptocegid")';
            // Commissions
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("comissions_fr", 70820000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("comissions_ue", 70829100, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("comissions_ex", 70829000, "bimptocegid")';
            //refacturation filliales
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("refacturation_ht", 79100000, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("refacturation_ttc", 79119000, "bimptocegid")';

            //Config
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("code_fournisseur_apple", "0000001000000000", "bimptocegid")';
            $sql[] = 'INSERT INTO `'. MAIN_DB_PREFIX . 'bimpcore_conf` (`name`, `value`, module) VALUES ("default_entrepot", 50, "bimptocegid")';
            $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value, module) VALUES ("start_current_trimestre", "2019-07-01", "bimptocegid")';
            
            
            
            
            $extrafields = new ExtraFields($this->db);
            $extrafields->addExtraField('is_subsidiary', 'Filiale', 'boolean', 100, 1, 'societe');
            $extrafields->addExtraField('is_salarie', 'Client salarié', 'boolean', 100, 1, 'societe');
            $extrafields->addExtraField('accounting_account', 'Compte comptable interco client', 'int', 101, 8, 'societe');
            $extrafields->addExtraField('accounting_account_fournisseur', 'Compte comptable interco fournisseur', 'int', 102, 8, 'societe');
            BimpCore::setConf($name, floatval($this->version));
            $this->_load_tables('/bimptocegid/sql/');
        }
        return $this->_init($sql, $options);
    }

    public function remove($options = '') {
        $sql = array();
        
        $sql[] = "DELETE FROM " . MAIN_DB_PREFIX . "bimpcore_conf WHERE name LIKE 'BIMPTOCEGID_%'";
        
        return $this->_remove($sql, $options);
    }
    
    
    

}
