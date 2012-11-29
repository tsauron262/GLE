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

class modBabelsCal extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modBabelsCal($DB)
    {
        $this->db = $DB ;
        $this->numero = 22227;

        $this->family = "OldGleModule";
        $this->name = "BabelsCal";
        $this->description = "Calculette Synopsis et DRSI bas&eacute;e sur sCal";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_BABELsCALC';
        $this->special = 0;
        $this->picto='BabelScal';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = "BabelsCal.php";

        // Dependences
        $this->depends = array("");
        $this->requiredby = array();

        // Constantes
        $this->const = array();

        // Boites
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'sCalBabel'; //Max 12 lettres


        $r = 0;
        $this->rights[$r][0] = $this->numero."1";// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage de la calculette';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'sCalBabel'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."2";// this->numero ."". 2
        $this->rights[$r][1] = 'Modification des tables de conversion';
        $this->rights[$r][2] = 'g'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'sCalBabel'; // Famille
        $this->rights[$r][5] = 'Modification'; // Droit
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
