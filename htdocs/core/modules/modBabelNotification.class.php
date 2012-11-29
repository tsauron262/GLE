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

/**     \defgroup   JasperBabel     Module Notification
        \brief      Module pour inclure Notification dans GLE
*/

/**
        \file       htdocs/core/modules/modBabelNotification.class.php
        \ingroup    JasperBabel
        \brief      Fichier de description et activation du module Notification - Babel
*/

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**     \class      modProspectBabel
        \brief      Classe de description et activation du module de Notification
*/

class modBabelNotification extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modBabelNotification($DB)
    {
        $this->db = $DB ;
        $this->numero = 22335;

        $this->family = "OldGleModule";
        $this->name = "BabelNotification";
        $this->description = "Notification Synopsis et DRSI";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_BABELNOTIFICATION';
        $this->special = 0;
        $this->picto='NotificationBABEL';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = "Babel_Notifications.php";

        // Dependences
        $this->depends = array();
        $this->requiredby = array();

        // Constantes
        $this->const = array();

        // Boites
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'Notification'; //Max 12 lettres


        $r = 0;
        $this->rights[$r][0] = $this->numero."1";// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage de BabelNotification';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Notification'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero."4";// this->numero ."". 2
        $this->rights[$r][1] = 'Administration du module BabelNotification';
        $this->rights[$r][2] = 'e'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Notification'; // Famille
        $this->rights[$r][5] = 'Modifier'; // Droit
        $r ++;

        // Menus
        //------


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
