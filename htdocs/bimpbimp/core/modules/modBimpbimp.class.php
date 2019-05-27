<?php
/*
 * BIMP-ERP by Synopsis et DRSI
 *
 * Author: Tommy SAURON <tommy@drsi.fr>
 * Licence : Artistic Licence v2.0
 *
 * Version 1.1
 * Create on : 4-1-2009
 *
 * Infos on http://www.finapro.fr
 *
 */

/**     \defgroup   ProspectBabel     Module ProspectBabel
  \brief      Module pour inclure ProspectBabel dans Dolibarr
 */
/**
  \file       htdocs/core/modules/modProspectBabel.class.php
  \ingroup    ProspectBabel
  \brief      Fichier de description et activation du module de Prospection Babel
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**     \class      modProspectBabel
  \brief      Classe de description et activation du module de Prospection Babel
 */
class modBimpbimp extends DolibarrModules {

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function modBimpbimp($DB) {
        $this->db = $DB;
        $this->numero = 8567;

        $this->family = "BIMP";
        $this->name = "Bimp module";
        $this->description = "Pour soc BIMP";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_BIMPBIMP';
        $this->special = 0;
        $this->picto = 'contract';

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
            'hooks' => array('contactcard'),
            'triggers' => 1
        );
    }

    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees e creer pour ce module.
     */
    function init() {
        global $conf;
        $sql = array();
        
        
            
        return $this->_init($sql);
    }

    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove() {
        global $conf;
        $sql = array();
        return $this->_remove($sql);
    }

}
?>