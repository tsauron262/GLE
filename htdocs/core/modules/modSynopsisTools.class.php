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

class modSynopsisTools extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modSynopsisTools($DB)
    {
        $this->db = $DB ;
        $this->numero = 8088;

        $this->family = "Synopsis";
        $this->name = "SynopsisTools";
        $this->description = utf8_decode("Outil de gestion");
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISTOOLS';
        $this->special = 0;
        $this->picto='tools@Synopsis_Tools';

        // Dir
        $this->dirs = array();

        // Config pages
        //$this->config_page_url = "";

        // Dependences
        $this->depends = array();
        $this->requiredby = array();

        // Constantes
        $this->const = array();



        // Permissions
        $this->rights = array();
        $this->rights_class = 'SynopsisTools';

        $r = 0;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s au menu Tools';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Global'; // Famille
        $this->rights[$r][5] = 'read'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s a PhpMyAdmin';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Global'; // Famille
        $this->rights[$r][5] = 'phpMyAdmin'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s au import';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Global'; // Famille
        $this->rights[$r][5] = 'import'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s au fichier info de maj';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Global'; // Famille
        $this->rights[$r][5] = 'fileInfo'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Administrer les bug';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Global'; // Famille
        $this->rights[$r][5] = 'adminBug'; // Droit
        $r ++;



        $this->menus = array();            // List of menus to add
        $r=0;
        $this->menu[$r]=array(
                            'type'=>'top',
                            'titre'=>'Tools',
                            'mainmenu'=>'SynopsisTools',
                            'leftmenu'=>'0',        // To say if we can overwrite leftmenu
                            'url'=>'/Synopsis_Tools/myAdmin.php',
                            'langs'=>'',
                            'position'=>1,
                            'perms'=>'$user->rights->SynopsisTools->Global->read',
                            'target'=>'',
                            'user'=>0);
        $s = $r;
        $r++;
        $this->menu[$r]=array('fk_menu'=>"r=".$s,
                            'type'=>'left',
                            'titre'=>'Php My Admin',
                            'mainmenu'=>'SynopsisTools',
                            'leftmenu'=>'1',        // To say if we can overwrite leftmenu
                            'url'=>'/Synopsis_Tools/myAdmin.php',
                            'langs'=>'',
                            'position'=>1,
                            'perms'=>'$user->rights->SynopsisTools->Global->phpMyAdmin',
                            'target'=>'',
                            'user'=>0);
        $r++;
        $this->menu[$r]=array('fk_menu'=>"r=".$s,
                            'type'=>'left',
                            'titre'=>'Importation',
                            'mainmenu'=>'SynopsisTools',
                            'leftmenu'=>'1',        // To say if we can overwrite leftmenu
                            'url'=>'/Synopsis_Tools/maj.php',
                            'langs'=>'',
                            'position'=>1,
                            'perms'=>'$user->rights->SynopsisTools->Global->import',
                            'target'=>'',
                            'user'=>0);
        
        $r++;
        $this->menu[$r]=array('fk_menu'=>"r=".$s,
                            'type'=>'left',
                            'titre'=>'Fichier info maj',
                            'mainmenu'=>'SynopsisTools',
                            'leftmenu'=>'1',        // To say if we can overwrite leftmenu
                            'url'=>'/Synopsis_Tools/listFileInfo.php',
                            'langs'=>'',
                            'position'=>1,
                            'perms'=>'$user->rights->SynopsisTools->Global->fileInfo',
                            'target'=>'',
                            'user'=>0);
        $s = $r;
        $r++;
        
        
        
        $this->menu[$r]=array(
                            'type'=>'top',
                            'titre'=>'Signaler un bug',
                            'mainmenu'=>'SynopsisToolsBug',
                            'leftmenu'=>'0',        // To say if we can overwrite leftmenu
                            'url'=>'/Synopsis_Tools/repportBug.php',
                            'langs'=>'',
                            'position'=>1,
                            'perms'=>'',
                            'target'=>'',
                            'user'=>0);
        $s = $r;
        $r++;
    }
   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees e creer pour ce module.
    */
  function init()
  {
    $sql = array("CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_Tools_fileInfo` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(50) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rowid`)
)",
            "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_Tools_bug` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL,
  `text` varchar(1000) NOT NULL,
  `resolu` tinyint(1) NOT NULL,
  PRIMARY KEY (`rowid`))");
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