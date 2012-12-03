<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/
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
/*
 */

/**
        \defgroup   demandeInterv     Module intervention cards
        \brief      Module to manage intervention cards
        \version    $Id: moddemandeInterv.class.php,v 1.33 2008/02/25 16:30:48 eldy Exp $
*/

/**
        \file       htdocs/core/modules/moddemandeInterv.class.php
        \ingroup    demandeInterv
        \brief      Fichier de description et activation du module demandeInterv
*/

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
        \class      moddemandeInterv
        \brief      Classe de description et activation du module demandeInterv
*/

class modSynopsisDemandeInterv  extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
  function modSynopsisDemandeInterv($DB)
  {
    $this->db = $DB ;
    $this->numero = 22229 ;

    $this->family = "Synopsis";
    $this->name = "Demande d'intervention";
    $this->description = "Gestion des demandes d'intervention";

    $this->revision = explode(" ","$Revision: 0.1 $");
    $this->version = $this->revision[1];

    $this->const_name = 'MAIN_MODULE_SYNOPSISDEMANDEINTERV';
    $this->special = 0;
    $this->picto = "demandeInterv";

    // Dir
    $this->dirs = array();

    // Config pages
    $this->config_page_url = array("demandeInterv.php");

    // Dependances
    $this->depends = array("modSociete","modCommercial","modFicheinter");
    $this->requiredby = array();

    // Constantes
    $this->const = array();
    $r=0;

    $this->const[$r][0] = "SYNOPSISDEMANDEINTERV_ADDON_PDF";
    $this->const[$r][1] = "chaine";
    $this->const[$r][2] = "soleil";
    $r++;

    $this->const[$r][0] = "SYNOPSISDEMANDEINTERV_ADDON";
    $this->const[$r][1] = "chaine";
    $this->const[$r][2] = "atlantic";
    $r++;

    // Boites
    $this->boxes = array();
    $r=0;
    $this->boxes[$r][1] = "box_demandeInterv.php";
    $r++;

    // Permissions
    $this->rights = array();
    $this->rights_class = 'synopsisdemandeinterv';

    $this->rights[1][0] = $this->numero."61";
    $this->rights[1][1] = 'Lire les demandes d\'intervention';
    $this->rights[1][2] = 'r';
    $this->rights[1][3] = 1;
    $this->rights[1][4] = 'lire';

    $this->rights[2][0] = $this->numero."62";
    $this->rights[2][1] = 'Cr&eacute;er/modifier les demandes d\'intervention';
    $this->rights[2][2] = 'w';
    $this->rights[2][3] = 0;
    $this->rights[2][4] = 'creer';

    $this->rights[3][0] = $this->numero."64";
    $this->rights[3][1] = 'Supprimer les demandes d\'intervention';
    $this->rights[3][2] = 'd';
    $this->rights[3][3] = 0;
    $this->rights[3][4] = 'supprimer';

    $this->rights[4][0] = $this->numero."65";
    $this->rights[4][1] = 'Prise en charge des demandes d\'intervention';
    $this->rights[4][2] = 'w';
    $this->rights[4][3] = 0;
    $this->rights[4][4] = 'prisencharge';

    $this->rights[5][0] = $this->numero."66";
    $this->rights[5][1] = 'Cl&ocirc;ture des demandes d\'intervention';
    $this->rights[5][2] = 'w';
    $this->rights[5][3] = 0;
    $this->rights[5][4] = 'cloture';

    $this->rights[6][0] = $this->numero."67";
    $this->rights[6][1] = 'Configurer les prix des interventions';
    $this->rights[6][2] = 'w';
    $this->rights[6][3] = 0;
    $this->rights[6][4] = 'config';

    $this->rights[7][0] = $this->numero."68";
    $this->rights[7][1] = 'Rapport sur toutes les interventions';
    $this->rights[7][2] = 'w';
    $this->rights[7][3] = 0;
    $this->rights[7][4] = 'rapportTous';

    $this->rights[7][0] = $this->numero."69";
    $this->rights[7][1] = 'Editer une DI apr&egrave;s la validation';
    $this->rights[7][2] = 'w';
    $this->rights[7][3] = 0;
    $this->rights[7][4] = 'edit_after_validation';


  }


   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees e creer pour ce module.
    */
  function init()
  {
       global $conf;

        // Permissions
        $this->remove();

        // Dir
        //$this->dirs[0] = $conf->facture->dir_output;

        $sql = array(
            "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[0][2]."'",
            "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type) VALUES('".$this->const[0][2]."','synopsisdemandeinterv')",
            );
        
        $sql[] = "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_demandeInterv` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_soc` int(11) NOT NULL,
  `fk_projet` int(11) default '0',
  `fk_contrat` int(11) default '0',
  `fk_commande` int(11) default NULL,
  `ref` varchar(30) NOT NULL,
  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datec` datetime default NULL,
  `date_valid` datetime default NULL,
  `datei` date default NULL,
  `fk_user_author` int(11) default NULL,
  `fk_user_valid` int(11) default NULL,
  `fk_statut` smallint(6) default '0',
  `duree` double default NULL,
  `description` text,
  `note_private` text,
  `note_public` text,
  `model_pdf` varchar(50) default NULL,
  `fk_user_target` int(11) default NULL,
  `date_cloture` datetime default NULL,
  `date_prisencharge` datetime default NULL,
  `fk_user_prisencharge` int(11) default NULL,
  `fk_user_cloture` int(11) default NULL,
  `total_ht` double default NULL,
  `total_tva` double default NULL,
  `total_ttc` double default NULL,
  `dateStat` int(11) NOT NULL,
  PRIMARY KEY  (`rowid`),
  UNIQUE KEY `ref` (`ref`),
  KEY `idx_demandeInterv_fk_soc` (`fk_soc`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2280 ;";
        
        $sql[] = "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_demandeIntervdet` (
  `rowid` int(11) NOT NULL auto_increment,
  `fk_demandeInterv` int(11) default NULL,
  `date` date default NULL,
  `description` text,
  `duree` int(11) default NULL,
  `rang` int(11) default '0',
  `fk_typeinterv` int(11) default NULL,
  `tx_tva` double default '19.6',
  `pu_ht` double default NULL,
  `qte` double default NULL,
  `total_ht` double default NULL,
  `total_tva` double default NULL,
  `total_ttc` double default NULL,
  `fk_contratdet` int(11) default NULL,
  `fk_commandedet` int(11) default NULL,
  `isForfait` tinyint(1) default '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4325 ;";

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