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

class modSynopsisPanier extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modSynopsisPanier($DB)
    {
        $this->db = $DB ;
        $this->numero = 8589;

        $this->family = "Synopsis";
        $this->name = "Synopsis Panier";
        $this->description = utf8_decode("Gestion de Panier");
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISPANIER';
        $this->special = 0;
        $this->picto='tools@synopsistools';

        // Dir
        $this->dirs = array("synopsispanier");

        // Config pages
        //$this->config_page_url = "";

        // Dependences
        $this->depends = array('modSynopsisTools');
        $this->requiredby = array();

        // Constantes
        $this->const = array();



        // Permissions
        $this->rights = array();
        $this->rights_class = 'synopsispanier';

        $r = 0;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Gestion du Panier';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'read'; // Famille
//        $this->rights[$r][5] = 'read'; // Droit
        $r ++;
        
        
        $this->const[$r][0] = "synopsispanier_ADDON_PDF";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "PANIER";

        $this->menus = array();            // List of menus to add
        
        $this->tabs = array('thirdparty:+panier:Panier:synopsisGene@synopsistools:/synopsispanier/affichePanier.php?idReferent=__ID__&type=tiers');
    }
   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees e creer pour ce module.
    */
  function init()
  {
    $sql = array("CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsys_Panier` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `referent` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `valeur` int(11) NOT NULL,
  PRIMARY KEY (`rowid`));");

    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appelee lors de la desactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
      $sql = array();
    return $this->_remove($sql);
  }
}
?>