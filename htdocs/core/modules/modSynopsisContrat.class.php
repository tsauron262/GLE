<?php
/*
 * GLE by Synopsis et DRSI
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

include_once "DolibarrModules.class.php";

/**     \class      modProspectBabel
        \brief      Classe de description et activation du module de Prospection Babel
*/

class modSynopsisContrat extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modSynopsisContrat($DB)
    {
        $this->db = $DB ;
        $this->numero = 8000;

        $this->family = "Synopsis";
        $this->name = "Contrat +";
        $this->description = "Amélioration des contrat";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISCONTRAT';
        $this->special = 0;
        $this->picto='contrat';

        // Dir
        $this->dirs = array();

        // Config pages
        //$this->config_page_url = "";

        // Dependences
        $this->depends = array("modSynopsisTools","modContrat");
        $this->requiredby = array();

        // Constantes
        $this->const = array();

//        // Boites
//        $this->boxes = array();

    // Boites
//    $this->boxes = array();
//    $r=0;
//    $this->boxes[$r][1] = "box_deplacement.php";
//    $r++;


        // Permissions
        $this->rights = array();
        $this->rights_class = 'synopsiscontrat';

        $r = 0;
        $this->tabs = array('contract:+annexe:Annexe PDF:@monmodule:/Babel_GMAO/annexes.php?id=__ID__',
			'contract:+interv:Interventions:@monmodule:/Babel_GMAO/intervByContrat.php?id=__ID__',
			/*'contract:+tickets:Tickets:@monmodule:/Babel_GMAO/annexes.php?id=__ID__',
			'contract:+sav:SAV:@monmodule:/Babel_GMAO/savByContrat.php?id=__ID__'*/); 

    }
   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees e creer pour ce module.
    */
  function init()
  {
    $sql = array("CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_contrat_annexe` (
  `annexe_refid` int(11) DEFAULT NULL,
  `contrat_refid` int(11) DEFAULT NULL,
  `rang` int(11) DEFAULT NULL,
  KEY `annexe_refid` (`annexe_refid`),
  KEY `contrat_refid` (`contrat_refid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;",
        "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_contrat_annexePdf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modeleName` varchar(150) DEFAULT NULL,
  `ref` varchar(50) DEFAULT NULL,
  `annexe` longtext,
  `afficheTitre` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=39 ;");
//    $this->dirs[0] = $conf->chrono->dir_output;

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