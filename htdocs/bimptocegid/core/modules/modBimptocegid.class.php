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

        // DEEE
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_vente_dee_fr", 70882000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_vente_dee_ue", 70882000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_dee_fr", 61111000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_dee_ue", 61113000)';
        // France
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_vente_produit_fr", 70700000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_vente_service_fr", 70600000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_produit_fr", 60700000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_service_fr", 60410000)';
        // Europe
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_vente_produit_ue", 70792000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_vente_service_ue", 70692100)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_produit_ue", 60794000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_service_ue", 60492100)';
        // Export
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_vente_produit_ex", 70790000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_vente_service_ex", 70691100)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_produit_ex", 60790000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_service_ex", 60491100)';
        //TVA
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_autoliquidation_tva_666", 44566600)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_autoliquidation_tva_711", 44571100)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_vente_tva_fr", 44571000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_vente_tva_ue", 44571200)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_vente_tva_null", 70750100)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_tva_fr", 44566100)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_tva_null", 60780000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_tva_null_service", 60480000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_tva_france_01", 44560100)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_tva_france_02", 44560200)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_tva_france_03", 44560300)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_tva_france_04", 44560400)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_tva_france_05", 44560500)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_tva_france_06", 44560600)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_tva_france_07", 44560700)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_tva_france_08", 44560800)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_tva_france_09", 44560900)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_tva_france_10", 44561000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_tva_france_11", 44561100)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_tva_france_12", 44561200)';
        // Remise fournisseur
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_avoir_fournisseur_fr", 60700000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_avoir_fournisseur_ue", 60794000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_avoir_fournisseur_ex", 60790000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_rfa_fournisseur_fr", 60970000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_rfa_fournisseur_ue", 60973000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_rfa_fournisseur_ex", 60974000)';
        // Ca   s particulier Apple
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_achat_fournisseur_apple", 60793000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_avoir_fournisseur_apple", 60793000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_rfa_fournisseur_apple", 60973000)';
        // Frais de port achat
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_frais_de_port_achat_fr", 62410000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_frais_de_port_achat_ue", 62413000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_frais_de_port_achat_ex", 62419000)';
        // Frais de port vente
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_frais_de_port_vente_fr", 70850000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_frais_de_port_vente_ue", 70859300)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_frais_de_port_vente_ex", 70859000)';
        // Commissions
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_comissions_fr", 70820000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_comissions_ue", 70829100)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_comissions_ex", 70829000)';
        //refacturation filliales
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_refacturation_ht", 79100000)';
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_refacturation_ttc", 79119000)';
        
        //Config
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPTOCEGID_code_fournisseur_apple", "0000001000000000")';
        
        // Conf
        $sql[] = 'INSERT INTO ' . MAIN_DB_PREFIX . 'bimpcore_conf (name, value) VALUES ("BIMPtoCEGID_start_current_trimestre", "2019-07-01")';
        
        $extrafields = new ExtraFields($this->db);
        $extrafields->addExtrafield('type_compta', 'Classement du produit en comptabilitée', 'select', 200, null, 'product', 0, 0, 0, 'a:1:{s:7:"options";a:3:{i:0;s:11:"Pas de type";i:1;s:7:"Produit";i:2;s:7:"Service";}}', 1, null, 1);
        
        require_once DOL_DOCUMENT_ROOT . '/bimpcore/Bimp_Lib.php';
        $name = 'module_version_' . strtolower($this->name);
        if (BimpCore::getConf($name) == "") { // C'est la première fois qu'on install le module
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
