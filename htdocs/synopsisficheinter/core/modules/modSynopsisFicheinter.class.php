<?php
 
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 * BIMP-ERP by Synopsis et DRSI
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
/*
 */
 
/**
  \defgroup   ficheinter     Module intervention cards
  \brief      Module to manage intervention cards
  \version    $Id: modFicheinter.class.php,v 1.33 2008/02/25 16:30:48 eldy Exp $
 */
/**
  \file       htdocs/core/modules/modFicheinter.class.php
  \ingroup    ficheinter
  \brief      Fichier de description et activation du module Ficheinter
 */
include_once(DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php");
 
/**
  \class      modFicheinter
  \brief      Classe de description et activation du module Ficheinter
 */
class modSynopsisFicheinter extends DolibarrModules {
 
    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function modSynopsisFicheinter($DB) {
        $this->db = $DB;
        $this->numero = 8745;
 
        $this->family = "Synopsis";
        $this->name = "Fiche inter +";
        $this->description = "Gestion des fiches d'intervention amélioré";
        $this->dir_output = "fichinter";
 
//        $this->revision = explode(" ", "$Revision: 1.33 $");
        $this->version = "1";
 
        $this->const_name = 'MAIN_MODULE_SYNOPSISFICHEINTER';
        $this->special = 0;
        $this->picto = "intervention";
 
        // Dir
        $this->dirs = array();
 
        // Config pages
        $this->config_page_url = array("synopsis_fichinter.php");
 
        // Dependances
        $this->depends = array("modSociete");
        $this->requiredby = array("modSynopsisdemandeinterv");
        
        
        $this->module_parts = array(
            'models' => '1' 
        );
        
        
        $this->tabs = array('order:+inter:Inter:bimpcore@bimpcore:1:/synopsisficheinter/tabs/order.php?id=__ID__',
            'thirdparty:+inter:Inter:bimpcore@bimpcore:1:/synopsisficheinter/tabs/client.php?id=__ID__');
 
        // Constantes
        $this->const = array();
        $r = 0;
 
        $this->const[$r][0] = "FICHEINTER_ADDON_PDF";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "BIMP";
        $r++;
//
//        $this->const[$r][0] = "FICHEINTER_ADDON";
//        $this->const[$r][1] = "chaine";
//        $this->const[$r][2] = "48pacific";
//        $r++;
 
        // Boites
        $this->boxes = array();
//        $this->boxes[0][1] = "box_ficheinter.php";
 
        // Permissions
        $this->rights = array();
        $this->rights_class = 'synopsisficheinter';
 
        $r = 0;
 
        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Lire les fiches d\'intervention';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'lire';
        $r++;
 
        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Creer/modifier les fiches d\'intervention';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'creer';
        $r++;
 
        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Supprimer les fiches d\'intervention';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'supprimer';
        $r++;
 
        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Voir les prix dans les fiches d\'intervention';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'voirPrix';
        $r++;
 
        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Rapport sur toutes les interventions';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'rapportTous';
        $r++;
 
        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Rattacher une intervention &agrave; un contrat ou une commande';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'rattacher';
        $r++;
 
        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Modifier une FI apr&egrave;s la validation';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'modifAfterValid';
        $r++;
 
        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Configurer le module';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'config';
 
 
 
        $this->menus = array();            // List of menus to add
        $r = 0;
//TODO position
        // Top menu
        $this->menu[$r] = array('fk_menu' => 0,
            'type' => 'top',
            'titre' => 'Interventions',
            'mainmenu' => 'synopsisficheinter',
            'leftmenu' => '1', // To say if we can overwrite leftmenu
            'url' => '/synopsisdemandeinterv/index.php?leftmenu=ficheinter&filtreUser=true',
            'langs' => 'synopsisGene@synopsistools',
            'position' => 110,
            'perms' => '$user->rights->synopsisficheinter->lire',
            'target' => '',
            'user' => 0);
        $r++;
 
        // Left menu linked to top menu
        //index d module deplacement
 
        /*$this->menu[$r] = array('fk_menu' => 'r=0',
            'type' => 'left',
            'titre' => 'Deplacements',
            'mainmenu' => 'synopsisficheinter',
            'url' => '/Babel_TechPeople/deplacements/index.php?leftmenu=deplacement',
            'langs' => 'synopsisGene@synopsistools',
            'position' => 1,
            'perms' => '$user->rights->TechPeople->ndf->AfficheMien || $user->rights->TechPeople->ndf->Affiche',
            'target' => '',
            'user' => 0);
        $r++;
 
//        1er lien
        $this->menu[$r] = array('fk_menu' => 'r=1',
            'type' => 'left',
            'titre' => 'Faire une NDF',
            'mainmenu' => 'synopsisficheinter',
            'url' => '/Babel_TechPeople/deplacements/card.php?action=create&leftmenu=deplacement',
            'langs' => 'synopsisGene@synopsistools',
            'position' => 1,
            'perms' => '$user->rights->TechPeople->ndf->AfficheMien',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==deplacement'));
        $r++;
        $this->menu[$r] = array('fk_menu' => 'r=1',
            'type' => 'left',
            'titre' => 'Voir les NDF',
            'mainmenu' => 'synopsisficheinter',
            'url' => '/Babel_TechPeople/deplacements/index.php?showall=1&leftmenu=deplacement',
            'langs' => 'synopsisGene@synopsistools',
            'position' => 2,
            'perms' => '$user->rights->TechPeople->ndf->Affiche',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==deplacement'));
        $r++;*/
 
 
        //index menu intervention
        // Left menu linked to top menu
        $this->menu[$r] = array('fk_menu' => 'r=0',
            'type' => 'left',
            'titre' => 'Interventions',
            'mainmenu' => 'synopsisficheinter',
            'url' => '/synopsisfichinter/liste.php?leftmenu=ficheinter',
            'langs' => 'interventions',
            'position' => 1,
            'perms' => '$user->rights->synopsisficheinter->lire',
            'target' => '',
            'user' => 0);
        $rem = $r;
        $r++;
        
        //1er lien
        $this->menu[$r] = array('fk_menu' => 'r=' . $rem,
            'type' => 'left',
            'titre' => 'ListOfInterventions',
            'mainmenu' => 'synopsisficheinter',
            'url' => '/synopsisfichinter/liste.php?leftmenu=ficheinter',
            'langs' => 'interventions',
            'position' => 2,
            'perms' => '$user->rights->synopsisficheinter->lire',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==ficheinter'));
        $rem2 = $r;
        $r++;
 
        $this->menu[$r] = array('fk_menu' => 'r=' . $rem2,
            'type' => 'left',
            'titre' => 'ListOfMyInterventions',
            'mainmenu' => 'synopsisficheinter',
            'url' => '/synopsisfichinter/liste.php?leftmenu=ficheinter&filtreUser=true',
            'langs' => 'interventions',
            'position' => 1,
            'perms' => '$user->rights->synopsisficheinter->lire',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==ficheinter'));
        $r++;
 
        $this->menu[$r] = array('fk_menu' => 'r=' . $rem2,
            'type' => 'left',
            'titre' => 'NewIntervention',
            'mainmenu' => 'synopsisficheinter',
            'url' => '/synopsisfichinter/card.php?action=create&leftmenu=ficheinter',
            'langs' => 'interventions',
            'position' => 1,
            'perms' => '$user->rights->synopsisficheinter->creer',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==ficheinter'));
        $r++;
        //2eme lien
 
 
        $this->menu[$r] = array('fk_menu' => 'r=' . $rem2,
            'type' => 'left',
            'titre' => 'Rapport FI',
            'mainmenu' => 'synopsisficheinter',
            'url' => '/synopsisfichinter/rapport.php?leftmenu=ficheinter',
            'langs' => 'interventions',
            'position' => 3,
            'perms' => '$user->rights->synopsisficheinter->lire',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==ficheinter'));
        $r++;
 
        $this->menu[$r] = array('fk_menu' => 'r=' . $rem,
            'type' => 'left',
            'titre' => 'AllDI',
            'mainmenu' => 'synopsisficheinter',
            'url' => '/synopsisdemandeinterv/index.php?leftmenu=ficheinter',
            'langs' => 'synopsisGene@synopsistools',
            'position' => 4,
            'perms' => '$user->rights->synopsisdemandeinterv->lire',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==ficheinter'));
        $rem2 = $r;
        $r++;
 
        $this->menu[$r] = array('fk_menu' => 'r=' . $rem2,
            'type' => 'left',
            'titre' => 'ListOfMyDIs',
            'mainmenu' => 'synopsisficheinter',
            'url' => '/synopsisdemandeinterv/index.php?leftmenu=ficheinter&filtreUser=true',
            'langs' => 'synopsisGene@synopsistools',
            'position' => 5,
            'perms' => '$user->rights->synopsisdemandeinterv->lire',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==ficheinter'));
        $r++;
 
        $this->menu[$r] = array('fk_menu' => 'r=' . $rem2,
            'type' => 'left',
            'titre' => 'NewDI',
            'mainmenu' => 'synopsisficheinter',
            'url' => '/synopsisdemandeinterv/card.php?action=create&leftmenu=ficheinter',
            'langs' => 'synopsisGene@synopsistools',
            'position' => 5,
            'perms' => '$user->rights->synopsisdemandeinterv->creer',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==ficheinter'));
        $r++;
        
        $this->menu[$r] = array('fk_menu' => 'r=' . $rem2,
            'type' => 'left',
            'titre' => 'Rapport DI',
            'mainmenu' => 'synopsisficheinter',
            'url' => '/synopsisdemandeinterv/rapport.php?leftmenu=ficheinter',
            'langs' => 'synopsisGene@synopsistools',
            'position' => 6,
            'perms' => '$user->rights->synopsisdemandeinterv->lire',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==ficheinter'));
        $r++;
        
        $this->menu[$r] = array('fk_menu' => 'r=' . $rem,
            'type' => 'left',
            'titre' => 'Config prix',
            'mainmenu' => 'synopsisficheinter',
            'url' => '/synopsisfichinter/config/configPrix.php?leftmenu=ficheinter',
            'langs' => 'interventions',
            'position' => 7,
            'perms' => '$user->rights->synopsisficheinter->config',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==ficheinter'));
        $r++;
        
        $this->menu[$r] = array('fk_menu' => 'r=' . $rem,
            'type' => 'left',
            'titre' => 'Config interv',
            'mainmenu' => 'synopsisficheinter',
            'url' => '/synopsisfichinter/config/configCategorie.php?leftmenu=ficheinter',
            'langs' => 'interventions',
            'position' => 8,
            'perms' => '$user->rights->synopsisficheinter->config',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==ficheinter'));
        $r++;
        
        $this->menu[$r] = array('fk_menu' => 'r=' . $rem,
            'type' => 'left',
            'titre' => 'Config type interv',
            'mainmenu' => 'synopsisficheinter',
            'url' => '/synopsisfichinter/config/configType.php?leftmenu=ficheinter',
            'langs' => 'interventions',
            'position' => 9,
            'perms' => '$user->rights->synopsisficheinter->config',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==ficheinter'));
        $r++;
        
    }
 
    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees a creer pour ce module.
     */
    function init() {
        global $conf;
 
        // Permissions
        $this->remove();
 
        // Dir
        $this->dirs[0] = $conf->facture->dir_output;
 
        $sql = array(
            "DELETE FROM " . MAIN_DB_PREFIX . "document_model WHERE nom = '" . $this->const[0][2] . "'",
            "INSERT IGNORE INTO " . MAIN_DB_PREFIX . "document_model (nom, type) VALUES('" . $this->const[0][2] . "','ficheinter')",
        );
 
//        $sql[] = "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsis_fichinter` (
//  `rowid` int(11) NOT NULL auto_increment,
//  `fk_soc` int(11) NOT NULL,
//  `fk_projet` int(11) default '0',
//  `fk_contrat` int(11) default '0',
//  `fk_commande` int(11) default NULL,
//  `ref` varchar(30) NOT NULL,
//  `tms` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
//  `datec` datetime default NULL,
//  `date_valid` datetime default NULL,
//  `datei` date default NULL,
//  `fk_user_author` int(11) default NULL,
//  `fk_user_valid` int(11) default NULL,
//  `fk_statut` smallint(6) default '0',
//  `duree` double default NULL,
//  `description` text,
//  `note_private` text,
//  `note_public` text,
//  `model_pdf` varchar(50) default NULL,
//  `total_ht` double default NULL,
//  `total_tva` double default NULL,
//  `total_ttc` double default NULL,
//  `entity` int(11) NOT NULL DEFAULT '1',
//  `natureInter` int(11) NOT NULL,
//  PRIMARY KEY  (`rowid`),
//  UNIQUE KEY `ref` (`ref`),
//  KEY `idx_fichinter_fk_soc` (`fk_soc`)
//) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;";
//
//        $sql[] = "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsis_fichinterdet` (
//  `rowid` int(11) NOT NULL auto_increment,
//  `fk_fichinter` int(11) default NULL,
//  `date` date default NULL,
//  `description` text,
//  `duree` int(11) default NULL,
//  `rang` int(11) default '0',
//  `fk_typeinterv` int(11) default NULL,
//  `fk_depProduct` int(11) default NULL,
//  `tx_tva` double default '19.6',
//  `pu_ht` double default NULL,
//  `qte` double default NULL,
//  `total_ht` double default NULL,
//  `total_tva` double default NULL,
//  `total_ttc` double default NULL,
//  `fk_contratdet` int(11) default NULL,
//  `fk_commandedet` int(11) default NULL,
//  `isForfait` tinyint(1) default NULL,
//  PRIMARY KEY  (`rowid`)
//) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;";
 
        
        $sql[] = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."synopsisfichinter` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_commande` int(11) DEFAULT NULL,
  `total_ht` double DEFAULT NULL,
  `total_tva` double DEFAULT NULL,
  `total_ttc` double DEFAULT NULL,
  `natureInter` int(11) NOT NULL,
  PRIMARY KEY (`rowid`)
)";
        $sql[] = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."synopsisfichinterdet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_typeinterv` int(11) DEFAULT NULL,
  `fk_depProduct` int(11) DEFAULT NULL,
  `tx_tva` double DEFAULT '19.6',
  `pu_ht` double DEFAULT NULL,
  `qte` double DEFAULT NULL,
  `total_ht` double DEFAULT NULL,
  `total_tva` double DEFAULT NULL,
  `total_ttc` double DEFAULT NULL,
  `fk_contratdet` int(11) DEFAULT NULL,
  `fk_commandedet` int(11) DEFAULT NULL,
  `isForfait` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
)";
        
        
        $sql[] = "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv` (
  `id` int(11) NOT NULL auto_increment,
  `label` varchar(50) default NULL,
  `active` tinyint(4) default NULL,
  `rang` int(11) default NULL,
  `default` int(11) default NULL,
  `isDeplacement` int(11) default NULL,
  `inTotalRecap` tinyint(4) default '0',
  `decountTkt` tinyint(4) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;";
 
        $sql[] = "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv` (
  `id` int(11) NOT NULL auto_increment,
  `label` varchar(50) default NULL,
  `active` tinyint(4) default NULL,
  `rang` int(11) default NULL,
  `default` int(11) default NULL,
  `isDeplacement` int(11) default NULL,
  `inTotalRecap` tinyint(4) default '0',
  `decountTkt` tinyint(4) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;";
 
 
        $sql[] = "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "synopsisfichinter_c_typeInterv` (`id`, `label`, `active`, `rang`, `default`, `isDeplacement`, `inTotalRecap`, `decountTkt`) VALUES
(1, 'Prestation', 1, 1, 1, NULL, 1, NULL),
(2, 'Impondérable', 1, 6, NULL, NULL, 1, NULL),
(3, 'Avant-vente', 1, 10, NULL, NULL, 1, NULL),
(4, 'Déplacement', 1, 3, NULL, 1, 1, NULL),
(5, 'Audit', 1, 5, NULL, NULL, 1, NULL),
(6, 'Commercial', 1, 11, NULL, NULL, 1, NULL),
(7, 'Formation Client', 1, 2, NULL, NULL, 1, NULL),
(8, 'Garantie', 1, 15, NULL, NULL, 1, NULL),
(9, 'Profil papier', 1, 18, NULL, NULL, 1, NULL),
(10, 'Profil écran', 1, 19, NULL, NULL, 1, NULL),
(11, 'Education', 1, 12, NULL, NULL, 1, NULL),
(12, 'Formation interne', 1, 14, NULL, NULL, 1, NULL),
(13, 'Télémaintenance', 1, 4, NULL, NULL, 1, 1),
(14, 'Hotline', 1, 13, NULL, NULL, NULL, 1),
(15, 'A facturer', 1, 7, NULL, NULL, 1, NULL),
(16, 'Garantie-Déplacement', 1, 16, NULL, 1, 1, NULL),
(17, '', 0, 22, NULL, 1, 1, NULL),
(18, 'Presta secours', 0, 21, NULL, NULL, NULL, NULL),
(19, 'Presta regie', 1, 20, NULL, NULL, 1, NULL),
(20, 'Visite contrat', 1, 9, NULL, NULL, 1, NULL),
(21, 'Télémaintenance contrat', 1, 8, NULL, NULL, 1, 1);";
 
        $sql[] = "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsisfichinter_extra_key` (
  `id` int(11) NOT NULL auto_increment,
  `label` varchar(50) default NULL,
  `type` varchar(50) default NULL,
  `active` int(11) default '1',
  `description` longtext,
  `isQuality` tinyint(4) default NULL,
  `rang` int(11) default '1',
  `isInMainPanel` tinyint(11) default NULL,
  `fullLine` tinyint(4) default '0',
  `inDi` tinyint(4) default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;";
 
        $sql[] = "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "synopsisfichinter_extra_key` (`id`, `label`, `type`, `active`, `description`, `isQuality`, `rang`, `isInMainPanel`, `fullLine`) VALUES
(13, 'Total bons', 'text', 1, NULL, NULL, 6, NULL, 0),
(14, 'Bon remis', 'text', 1, NULL, NULL, 1, NULL, 0),
(15, 'Total pièce', 'text', 1, NULL, NULL, 1, NULL, 0),
(16, 'Récupération de données', 'text', 1, NULL, NULL, 3, NULL, 0),
(17, 'Intervention terminée', 'checkbox', 1, NULL, NULL, 4, 1, 0),
(18, 'Date prochain RDV', 'date', 1, NULL, NULL, 5, 1, 0),
(19, 'Attentes clients', 'textarea', 1, NULL, NULL, 7, 1, 0),
(20, 'Mise en relation', 'radio', 1, 'Mise en relation avec un service particulier ?', 1, 8, NULL, 0),
(21, 'Installation', 'checkbox', 0, NULL, NULL, 9, 1, 0),
(23, 'Intervention sans matériel', 'checkbox', 1, NULL, NULL, 10, 1, 0),
(24, 'Heure arrivée AM', 'text', 1, NULL, NULL, 15, 1, 0),
(25, 'Heure arrivée PM', 'text', 1, NULL, NULL, 12, 1, 0),
(26, 'Heure départ AM', 'text', 1, NULL, NULL, 15, 1, 0),
(27, 'Heure départ PM', 'text', 1, NULL, NULL, 12, 1, 0),
(28, 'Préconisation du technicien', 'textarea', 1, NULL, NULL, 16, 1, 1),
(29, 'Remarque(s) client', 'textarea', 1, 'Remarque(s) sur l''intervention', 1, 17, NULL, 0),
(30, 'Q - Tech à l''heure', '3stars', 1, 'Le technicien est arrivé à l''heure prévu ou m''a tenu informé d''un retard ?', 1, 18, NULL, 0),
(31, 'Q - Info tâche et durée', '3stars', 1, 'Vous avez été informé(e) de la tâche en cours et de la durée de l''intervention ?', 1, 19, NULL, 0),
(32, 'Q - Satisfaction', '3stars', 1, 'Je suis très satisfait de la qualité de l''intervention ?', 1, 20, NULL, 0),
(33, 'Recontacter par un commercial', 'checkbox', 1, 'Je souhaite être recontacter par un commercial ?', 1, 21, NULL, 0),
(34, 'Proposition Contrat', 'radio', 1, NULL, NULL, 22, 1, 1),
(35, 'Forfait', 'checkbox', 1, NULL, NULL, 11, 1, 1),
(36, 'Description non imprimable', 'text', 1, NULL, NULL, 2, NULL, 0);";
 
        $sql[] = "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsisfichinter_extra_value` (
  `id` int(11) NOT NULL auto_increment,
  `interv_refid` int(11) default NULL,
  `extra_key_refid` int(11) default NULL,
  `extra_value` longtext,
  `typeI` enum('DI','FI') default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;";
 
        $sql[] = "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsisfichinter_extra_values_choice` (
  `id` int(11) NOT NULL auto_increment,
  `label` varchar(150) default NULL,
  `value` int(11) default NULL,
  `key_refid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uniq_extra_choice_interv` (`label`,`value`,`key_refid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;";
 
        $sql[] = "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "synopsisfichinter_extra_values_choice` (`id`, `label`, `value`, `key_refid`) VALUES
(8, 'Contrat 8H', 1, 34),
(9, 'Contrat Hotline', 2, 34),
(10, 'Contrat SAV+', 3, 34),
(6, 'Direction Technique', 1, 20),
(7, 'Service Commercial', 2, 20),
(11, 'Contrat de suivie', 4, 34);";
 
        $sql[] = "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsisfichinter_User_PrixDepInterv` (
  `id` int(11) NOT NULL auto_increment,
  `user_refid` int(11) default NULL,
  `fk_product` int(11) default NULL,
  `prix_ht` double default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;";
 
        $sql[] = "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsisfichinter_User_PrixTypeInterv` (
  `id` int(11) NOT NULL auto_increment,
  `user_refid` int(11) default NULL,
  `typeInterv_refid` int(11) default NULL,
  `prix_ht` double default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;";
        $sql[] = "DROP TABLE IF EXISTS ". MAIN_DB_PREFIX ."synopsis_fichinterdet;";
        $sql[] = "DROP TABLE IF EXISTS ". MAIN_DB_PREFIX ."synopsis_fichinter;";
        $sql[] = "DROP VIEW IF EXISTS ". MAIN_DB_PREFIX ."synopsis_fichinter;";
        $sql[] = "DROP VIEW IF EXISTS ". MAIN_DB_PREFIX ."synopsis_fichinterdet;";
        $sql[] = "CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW 
". MAIN_DB_PREFIX ."synopsis_fichinter as (SELECT f.`rowid`, `fk_soc`, `fk_projet`, `fk_contrat`, `fk_commande`, `ref`, `tms`, `datec`, `date_valid`, `datei`, `fk_user_author`, `fk_user_valid`, `fk_statut`, `duree`, `description`, `note_private`, `note_public`, `model_pdf`, `total_ht`, `total_tva`, `total_ttc`, `natureInter`, `entity` 
FROM ".MAIN_DB_PREFIX."fichinter f left join ".MAIN_DB_PREFIX."synopsisfichinter sf on f.rowid = sf.rowid);";
        $sql[] = "CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW ".MAIN_DB_PREFIX."synopsis_fichinterdet as (SELECT f.`rowid`, `fk_fichinter`, `date`, `description`, `duree`, `rang`, `fk_typeinterv`, `fk_depProduct`, `tx_tva`, `pu_ht`, `qte`, `total_ht`, `total_tva`, `total_ttc`, `fk_contratdet`, `fk_commandedet`, `isForfait` 
FROM ". MAIN_DB_PREFIX ."fichinterdet f  left join ".MAIN_DB_PREFIX."synopsisfichinterdet sf on f.rowid = sf.rowid);";
        
//        $sql[] = "DROP TABLE IF EXISTS ". MAIN_DB_PREFIX ."fichinterdet;";
//        
//        $sql[] = "DROP VIEW IF EXISTS ". MAIN_DB_PREFIX ."fichinterdet;";
//        
//        $sql[] = "DROP TABLE IF EXISTS ". MAIN_DB_PREFIX ."fichinter;";
//        
//        $sql[] = "DROP VIEW IF EXISTS ". MAIN_DB_PREFIX ."fichinter;";
//        
//        $sql[] = "CREATE VIEW ". MAIN_DB_PREFIX ."fichinter as (SELECT `rowid`, `fk_soc`, `fk_projet`, `fk_contrat`, `ref`, 1 as `entity`, `tms`, `datec`, `date_valid`, `datei`, `fk_user_author`, `fk_user_valid`, `fk_statut`, `duree`, `description`, `note_private`, `note_public`, `model_pdf`, '' as `extraparams` FROM `". MAIN_DB_PREFIX ."synopsis_fichinter` WHERE 1);";
 
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
