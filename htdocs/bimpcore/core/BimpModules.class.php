<?php

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**     \class      modProspectBabel
  \brief      Classe de description et activation du module de Prospection Babel
 */
class BimpModules extends DolibarrModules {
       /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function __construct($DB) {
        $this->db = $DB;
        $this->numero = 8567;

        $this->family = "BIMP";
        $this->description = "Bimp Caisse";
        if(is_null($this->version))
            $this->version = '1';    // 'experimental' or 'dolibarr' or version
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->special = 0;

        // Dir
        $this->dirs = array();

        // Config pages
        //$this->config_page_url = "";
        // Dependences
        $this->depends = array();
        $this->requiredby = array();

        // Constantes
        $this->const = array();
        
        
        $this->module_parts = array(
            'hooks' => array(),
            'triggers' => 0
        );
    }

    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees e creer pour ce module.
     */
    function init($options = '') {
        global $conf;
        $sql = array();
        require_once DOL_DOCUMENT_ROOT.'/bimpcore/Bimp_Lib.php';
        $name = 'module_version_'.strtolower($this->name);
        // Se fais que lors de l'installation du module
        if(BimpCore::getConf($name, '') == "") {
            BimpCore::setConf($name, floatval($this->version), 0);
            $this->_load_tables('/'.strtolower($this->name).'/sql/');
        }
        
        
            
        return $this->_init($sql);
    }

    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove($options = '') {
        global $conf;
        $sql = array();
        return $this->_remove($sql, $option);
    }

}
