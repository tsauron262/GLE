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

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**     \class      modProspectBabel
        \brief      Classe de description et activation du module de Prospection Babel
*/

class modSynopsisFinanc extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modSynopsisFinanc($DB)
    {
        $this->db = $DB ;
        $this->numero = 8594;

        $this->family = "Synopsis";
        $this->name = "SynopsisFinanc";
        $this->description = utf8_decode("Gestion des Financement");
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISFINANC';
        $this->special = 0;
        $this->picto='tools@Synopsis_Tools';

        // Dir
        $this->dirs = array("synopsisFinanc");

        // Config pages
        //$this->config_page_url = "";
        
        
        $this->module_parts = array('triggers' => 1);

        // Dependences
        $this->depends = array('');
        $this->requiredby = array();

        // Constantes
        $this->const = array();



        // Permissions
        $this->rights = array();
        $this->rights_class = 'synopsisFinanc';

        $r = 1;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Lecture';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'read'; // Famille
//        $this->rights[$r][5] = 'read'; // Droit
        $r ++;

//        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
//        $this->rights[$r][1] = 'Stat Global';
//        $this->rights[$r][2] = 'r'; //useless
//        $this->rights[$r][3] = 0; // Default
//        $this->rights[$r][4] = 'stat'; // Famille
////        $this->rights[$r][5] = 'read'; // Droit
//        $r ++;
        
        
        
        
//$this->menus = array();			// List of menus to add
//		$r=0;
//		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=Process',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
//					'type'=>'left',			// This is a Left menu entry
//					'titre'=>'Apple',
//					'mainmenu'=>'Process',
//					'leftmenu'=>'apple',
//					'url'=>'/synopsisapple/test.php',
//					'langs'=>'global@Synopsis_Tools',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
//					'position'=>200,
//					'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
//					'perms'=>'synopsisapple@read    ',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
//					'target'=>'',
//					'user'=>0);						// 0=Menu for internal users,1=external users, 2=both
//		$r++;
//		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=Process,fk_leftmenu=apple',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
//					'type'=>'left',			// This is a Left menu entry
//					'titre'=>'Nouveau Sav',
//					'mainmenu'=>'apple',
//					'leftmenu'=>'apple',
//					'url'=>'/synopsisapple/FicheRapide.php',
//					'langs'=>'global@Synopsis_Tools',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
//					'position'=>201,
//					'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
//					'perms'=>'synopsisapple@read',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
//					'target'=>'',
//					'user'=>0);							// 0=Menu for internal users,1=external users, 2=both
//		$r++;
//		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=Process,fk_leftmenu=apple',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
//					'type'=>'left',			// This is a Left menu entry
//					'titre'=>'Garantie Apple',
//					'mainmenu'=>'apple',
//					'leftmenu'=>'apple',
//					'url'=>'/synopsisapple/test.php',
//					'langs'=>'global@Synopsis_Tools',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
//					'position'=>201,
//					'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
//					'perms'=>'synopsisapple@read',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
//					'target'=>'',
//					'user'=>0);			// 0=Menu for internal users,1=external users, 2=both
//		$r++;
//		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=Process,fk_leftmenu=apple',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
//					'type'=>'left',			// This is a Left menu entry
//					'titre'=>'Stat SAV',
//					'mainmenu'=>'apple',
//					'leftmenu'=>'apple',
//					'url'=>'/synopsisapple/exportSav.php',
//					'langs'=>'global@Synopsis_Tools',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
//					'position'=>201,
//					'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
//					'perms'=>'synopsisapple@read',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
//					'target'=>'',
//					'user'=>0);	
        
//        $this->tabs = array('contract:+financ:Financement:synopsisGene@Synopsis_Tools:/synopsisfinanc/financeContrat.php?id=__ID__');
        $this->tabs = array('propal:+financ:Financement:synopsisGene@Synopsis_Tools:/synopsisfinanc/financePropal.php?id=__ID__');
    }
   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees e creer pour ce module.
    */
  function init()
  {
    $sql = array();
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