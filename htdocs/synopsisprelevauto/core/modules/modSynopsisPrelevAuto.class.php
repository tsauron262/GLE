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

class modSynopsisPrelevAuto extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modSynopsisPrelevAuto($DB)
    {
        $this->db = $DB ;
        $this->numero = 8597;

        $this->family = "Synopsis";
        $this->name = "Synopsis Prelevement Auto";
        $this->description = utf8_decode("Gestion des Pr&eacute;l&egrave;vement Automatique");
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISPRELEVAUTO';
        $this->special = 0;
        $this->picto='tools@synopsistools';

        // Dir
        $this->dirs = array("synopsisprelevauto");

        // Config pages
        //$this->config_page_url = "";

        // Dependences
        $this->depends = array('');
        $this->requiredby = array();

        // Constantes
        $this->const = array();



        // Permissions
        $this->rights = array();
        $this->rights_class = 'synopsisprelevauto';

        $r = 0;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Gestion des Prélèvement';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'read'; // Famille
//        $this->rights[$r][5] = 'read'; // Droit
        $r ++;
        
        
        $this->const[$r][0] = "synopsispanier_ADDON_PDF";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "PANIER";
        
        
$this->menus = array();			// List of menus to add
		$r=0;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=tools',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
					'type'=>'left',			// This is a Left menu entry
					'titre'=>'Prélèvements',
					'mainmenu'=>'tools',
					'leftmenu'=>'prelevAuto',
					'url'=>'/synopsisprelevauto/list.php',
					'langs'=>'global@synopsistools',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
					'position'=>200,
					'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>0);				// 0=Menu for internal users,1=external users, 2=both
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=prelevAuto',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
					'type'=>'left',			// This is a Left menu entry
					'titre'=>'Prélèvements',
					'mainmenu'=>'prelevAuto',
					'leftmenu'=>'prelevAuto',
					'url'=>'/synopsisprelevauto/list.php',
					'langs'=>'global@synopsistools',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
					'position'=>201,
					'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>0);	
        
        $this->tabs = array('contract:+prelevAuto:Prélèvement:synopsisGene@synopsistools:$user->rights->synopsisprelevauto->read:/synopsisprelevauto/prelev.php?id=__ID__&type=contrat');
    }
   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees e creer pour ce module.
    */
  function init()
  {
    $sql = array("CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."synopsisprelevauto` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `referent` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `dateDeb` datetime,
  `dateDern` datetime,
  `dateProch` datetime,
  `nb` int(11),
  `periode` int(11),
  `note` TEXT,
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