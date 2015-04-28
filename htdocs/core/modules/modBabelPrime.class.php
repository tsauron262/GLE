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

class modBabelPrime extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modBabelPrime($DB)
    {
        $this->db = $DB ;
        $this->numero = 22224 ;

        $this->family = "OldGleModule";
        $this->name = "BabelPrime";
        $this->description = "Prime Babel";
        $this->version = 'experimental';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_BABELPRIME';
        $this->special = 0;
        $this->picto='PRIMEBABEL';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = "BabelPrime.php";

        // Dependences
        $this->depends = array();
        $this->requiredby = array();

        // Constantes
        $this->const = array();

        // Boites
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'Prime';

        $r = 0;
        $this->rights[$r][0] = $this->numero."1";// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage de l ecran des primes';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Primes'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;
        $this->rights[$r][0] = $this->numero."2";// this->numero ."". 2
        $this->rights[$r][1] = 'Edition de l ecran des primes';
        $this->rights[$r][2] = 'w'; //useless
        $this->rights[$r][3] = 0;// Default
        $this->rights[$r][4] = 'Primes';// Famille
        $this->rights[$r][5] = 'Ecrire';// Droit

        //Menu
        $r=0;

        $this->menu[$r]=array('fk_menu'=>0,
                              'type'=>'top',
                              'titre'=>'Prime',
                              'mainmenu'=>'Prime',
                              'leftmenu'=>'0',
                              'url'=>'/BabelPrime/index.php',
                              'langs'=>'other',
                              'position'=>100,
                              'perms'=>'',
                              'target'=>'',
                              'user'=>0 );
        $r++;
  }

   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees a creer pour ce module.
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
