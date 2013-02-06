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

/**     \defgroup   JasperBabel     Module JasperBabel
        \brief      Module pour inclure JasperBabel dans Dolibarr
*/

/**
        \file       htdocs/core/modules/modJasperBabel.class.php
        \ingroup    JasperBabel
        \brief      Fichier de description et activation du module d'interface JasperBI - Babel
*/

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**     \class      modProspectBabel
        \brief      Classe de description et activation du module de Prospection Babel
*/

class modBabelIM extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modBabelIM($DB)
    {
        $this->db = $DB ;
        $this->numero = 22222;

        $this->family = "OldGleModule";
        $this->name = "BabelIM";
        $this->description = "Instant Messanger Synopsis et DRSI bas&eacute;e sur sparkweb";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_BABELIM';
        $this->special = 0;
        $this->picto='jabber_chatty';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = "BabelIM.php";

        // Dependences
        $this->depends = array("");
        $this->requiredby = array();

        // Constantes
        $this->const = array();

        // Boites
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'IMBabel'; //Max 12 lettres


        $r = 0;
        $this->rights[$r][0] = $this->numero."1";// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage de l\'instant messanger';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'IMBabel'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;

        // Menus
        //------
//        $r=0;
//
//        $this->menu[$r]=array('fk_menu'=>0,'type'=>'top','titre'=>'BI','mainmenu'=>'jasperbabel','leftmenu'=>'0','url'=>'/BabelJasper/index.php','langs'=>'other','position'=>100,'perms'=>'','target'=>'','user'=>0);
//        $r++;

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
