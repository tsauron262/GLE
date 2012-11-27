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
 * Infos on http://www.synopsis-erp.com
 *
 */

/**     \defgroup   ProspectBabel     Module ProspectBabel
  \brief      Module pour inclure ProspectBabel dans Dolibarr
 */
/**
  \file       htdocs/includes/modules/modProspectBabel.class.php
  \ingroup    ProspectBabel
  \brief      Fichier de description et activation du module de Prospection Babel
 */
include_once "DolibarrModules.class.php";

/**     \class      modProspectBabel
  \brief      Classe de description et activation du module de Prospection Babel
 */
class modSynopsisHrm extends DolibarrModules {

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function modSynopsisHrm($DB) {
        $this->db = $DB;
        $this->numero = 22230;

        $this->family = "Synopsis";
        $this->name = "SynopsisHrm";
        $this->description = "Gestion des ressources humaines";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISHRM';
        $this->special = 0;
        $this->picto = 'HRM';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = "SynopsisHrm.php";

        // Dependences
        $this->depends = array();
        $this->requiredby = array();

        // Constantes
        $this->const = array();

        // Boites
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'hrm'; //Max 12 lettres

        $r = 0;
        $this->rights[$r][0] = $this->numero."1";// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage de l\'&eacute;cran hrm';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'hrm'; // Famille
        $this->rights[$r][5] = 'Utilisateur'; // Droit
        $r ++;
        $this->rights[$r][0] = $this->numero."2";// this->numero ."". 2
        $this->rights[$r][1] = 'Affichage hrm en mode administrateur';
        $this->rights[$r][2] = 'w'; //useless
        $this->rights[$r][3] = 1;// Default
        $this->rights[$r][4] = 'hrm';// Famille
        $this->rights[$r][5] = 'Admin';// Droit
        $r ++;


        $this->menu[$r]=array('fk_menu'=>0,
                              'type'=>'top',
                              'titre'=>'RH',
                              'mainmenu'=>'RH',
                              'leftmenu'=>'0',
                              'url'=>'/Synopsis_Hrm/index.php',
                              'langs' => 'synopsisGene@Synopsis_Tools',
                              'position'=>120,
                              'perms'=>'$user->rights->hrm->hrm->Utilisateur || $user->rights->hrm->hrm->Admin',
                              'target'=>'',
                              'user'=>0);
        $r++;
    }

    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees e creer pour ce module.
     */
    function init() {
        $sql = array();
        return $this->_init($sql);
    }

    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove() {
        $sql = array();
        return $this->_remove($sql);
    }

}

?>
