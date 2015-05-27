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
        $this->numero = 8598;

        $this->family = "Synopsis";
        $this->name = "SynopsisFinanc";
        $this->description = utf8_decode("Gestion des Financement");
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISFINANC';
        $this->special = 0;
        $this->picto='tools@synopsistools';

        // Dir
        //$this->dirs = array("synopsisFinanc");

        // Config pages
        //$this->config_page_url = "";
        
        
        $this->module_parts = array('triggers' => 0);

        // Dependences
        $this->depends = array('modPropale','modContrat','modFacture');
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
        
        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Ecriture partielle';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'write'; // Famille
        $r ++;
        
        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Ecriture totale';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'super_write'; // Famille
        $r ++;
        
//        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
//        $this->rights[$r][1] = 'Taux';
//        $this->rights[$r][2] = 'r'; //useless
//        $this->rights[$r][3] = 0; // Default
//        $this->rights[$r][4] = 'Taux'; // Famille
//        $r ++;
//        
//        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
//        $this->rights[$r][1] = 'banque';
//        $this->rights[$r][2] = 'r'; //useless
//        $this->rights[$r][3] = 0; // Default
//        $this->rights[$r][4] = 'bank'; // Famille
//        $r ++;

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
//					'langs'=>'global@synopsistools',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
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
//					'langs'=>'global@synopsistools',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
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
//					'langs'=>'global@synopsistools',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
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
//					'langs'=>'global@synopsistools',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
//					'position'=>201,
//					'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
//					'perms'=>'synopsisapple@read',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
//					'target'=>'',
//					'user'=>0);	
        
//        $this->tabs = array('contract:+financ:Financement:synopsisGene@synopsistools:/synopsisfinanc/financeContrat.php?id=__ID__');
        $this->tabs = array('propal:+financ:Financement:synopsisGene@synopsistools:/synopsisfinanc/financePropal.php?id=__ID__');
    }
   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees e creer pour ce module.
    */
  function init()
  {
    $sql = array();
//    $sql[]="CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."synopsisfinancement ("
//            . "rowid        integer not null auto_increment,"
//            . "user_create  integer not null,"
//            . "user_modify  integer,"
//            . "fk_propal    integer not null,"
//            . "fk_contrat   integer,"
//            . "fk_facture   integer,"
//            . "montantAF    float,"
//            . "periode      integer,"
//            . "duree        integer,"
//            . "commC        float,"
//            . "commF        float,"
//            . "taux         float,"
//            . "banque       varchar(25),"
//            . "PRIMARY KEY (rowid),"
//            . "CONSTRAINT Finance_FK_Propal FOREIGN KEY (fk_propal) REFERENCES ".MAIN_DB_PREFIX."propal (rowid),"
//            . "CONSTRAINT Finance_FK_Contrat FOREIGN KEY (fk_contrat) REFERENCES ".MAIN_DB_PREFIX."contrat (rowid),"
//            . "CONSTRAINT Finance_FK_Facture FOREIGN KEY (fk_facture) REFERENCES ".MAIN_DB_PREFIX."facture (rowid),"
//            . "CONSTRAINT Finance_FK_create FOREIGN KEY (user_create) REFERENCES ".MAIN_DB_PREFIX."user (rowid),"
//            . "CONSTRAINT Finance_FK_modify FOREIGN KEY (user_modify) REFERENCES ".MAIN_DB_PREFIX."user (rowid)"
//        . ");";
    $sql[] = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."synopsisfinancement` (
`rowid` int(11) NOT NULL,
  `user_create` int(11) NOT NULL,
  `user_modify` int(11) DEFAULT NULL,
  `fk_propal` int(11) NOT NULL,
  `fk_contrat` int(11) DEFAULT NULL,
  `fk_facture` int(11) DEFAULT NULL,
  `type_location` varchar(25) NOT NULL,
  `preter` float DEFAULT NULL,
  `VR` float DEFAULT NULL,
  `montantAF` float DEFAULT NULL,
  `periode` int(11) DEFAULT NULL,
  `duree` int(11) DEFAULT NULL,
  `commC` float DEFAULT NULL,
  `commF` float DEFAULT NULL,
  `taux` float DEFAULT NULL,
  `banque` varchar(25) DEFAULT NULL,
  `duree_degr` int(11) NOT NULL,
  `pourcent_degr` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  KEY `Finance_FK_Propal` (`fk_propal`),
  KEY `Finance_FK_Contrat` (`fk_contrat`),
  KEY `Finance_FK_Facture` (`fk_facture`),
  KEY `Finance_FK_create` (`user_create`),
  KEY `Finance_FK_modify` (`user_modify`)
)";
    
    $sql[] = "INSERT INTO `".MAIN_DB_PREFIX."document_model` (`rowid`, `nom`, `entity`, `type`, `libelle`, `description`) VALUES
(null, 'azurFinanc', 1, 'propal', 'Proposotion de financement', NULL);";
    
    $sql[] = "INSERT INTO `".MAIN_DB_PREFIX."c_type_contact` (`rowid`, `element`, `source`, `code`, `libelle`, `active`, `module`) VALUES ('780', 'propal', 'external', 'rapp', 'Rapporteur', '1', 'synopsisFinanc');";
    
    $sql[]="INSERT INTO  ".MAIN_DB_PREFIX."document_model (`rowid` ,`nom` ,`entity` ,`type` ,`libelle` ,`description`) VALUES (NULL ,  'contratFinanc',  '1',  'synopsiscontrat',  'Financement', NULL);";
    
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