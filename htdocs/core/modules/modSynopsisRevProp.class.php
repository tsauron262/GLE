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

class modSynopsisRevProp extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modSynopsisRevProp($DB)
    {
        $this->db = $DB ;
        $this->numero = 23000;

        $this->family = "Synopsis";
        $this->name = "Revision prop";
        $this->description = "Permet de reviser les proposition commerciale et d'afficher le doc indicable";
        $this->version = '1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISREVPROP';
        $this->special = 0;
        $this->picto='propal';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = preg_replace('/^mod/i', '', get_class($this)).".php";

        // Dependences
        $this->depends = array("modPropale");
        $this->requiredby = array();
        // Constantes
        $this->const = array();

        // Boites
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'SynopsisRevProp'; //Max 12 lettres


        $r = 0;
        $this->rights[$r][0] = $this->numero."1";// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage des Revision';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'SynopsisRevProp'; // Famille
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;
        $this->rights[$r][0] = $this->numero."2";// this->numero ."". 1
        $this->rights[$r][1] = 'Reviser des proposition';
        $this->rights[$r][2] = 'p'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'SynopsisRevProp'; // Famille
        $this->rights[$r][5] = 'Push'; // Droit
        $r ++;
        
        // Menus
        //------

        
        //Ajout onglet dans fiche utilisateur
        $this->tabs = array('propal:+revision:Revision/Doc.Indicable:@monmodule:/Synopsis_Revision/revisionProp.php?id=__ID__');
  }

   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees a creer pour ce module.
    */
  function init()
  {
        $this->remove();
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
