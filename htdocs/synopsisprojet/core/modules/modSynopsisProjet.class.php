<?php

/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * MERCmodHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
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
 *
 * $Id: modProjet.class.php,v 1.23 2008/01/13 22:48:26 eldy Exp $
 */

/**  \defgroup   projet     Module projet
  \brief      Module pour inclure le detail par projets dans les autres modules
 */
/** \file       htdocs/core/modules/modProjet.class.php
  \ingroup    projet
  \brief      Fichier de description et activation du module Projet
 */
include_once(DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php");

/**
  \class      modProjet
  \brief      Classe de description et activation du module Projet
 */
class modSynopsisProjet extends DolibarrModules {

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function modSynopsisProjet($DB) {
        $this->db = $DB;
        $this->numero = 4000;

        $this->family = "Synopsis";
        $this->name = "Projet +";
        $this->nameI = "synopsisprojet";
        $this->description = "Gestion des projets avancé";
        $this->version = 2;
        $this->const_name = 'MAIN_MODULE_SYNOPSISPROJET';
        $this->special = 0;
        $this->picto = 'project';
        $this->langfiles = array("synopsisproject@synopsisprojet");

        // Dependances
        $this->depends = array("modSociete");
        $this->requiredby = array();
        $this->config_page_url = preg_replace('/^mod/i', '', get_class($this)).".php";


        // Constants
        $this->const = array();
        $r = 0;
        
        $this->const[$r][0] = "PROJET_ADDON";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "mod_projet_tourmaline";
        $this->const[$r][3] = 'Nom du gestionnaire de numerotation des projets';
        $this->const[$r][4] = 0;
        $r++;
        
        $this->const[$r][0] = "PROJET_TOURMALINE_MASK";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "{tt}{000}";
        $this->const[$r][3] = 'Masque de numerotation des projets';
        $this->const[$r][4] = 0;
        $r++;


        $this->dirs = array();
        $this->data_directory = "/imputations/";


        // Boites
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'synopsisprojet';

        $this->rights[1][0] = 41; // id de la permission
        $this->rights[1][1] = 'Lire les projets/t&acirc;ches'; // libelle de la permission
        $this->rights[1][2] = 'r'; // type de la permission (deprecie a ce jour)
        $this->rights[1][3] = 1; // La permission est-elle une permission par defaut
        $this->rights[1][4] = 'lire';

        $this->rights[2][0] = 42; // id de la permission
        $this->rights[2][1] = 'Cr&eacute;er modifier les projets/t&acirc;ches'; // libelle de la permission
        $this->rights[2][2] = 'w'; // type de la permission (deprecie a ce jour)
        $this->rights[2][3] = 1; // La permission est-elle une permission par defaut
        $this->rights[2][4] = 'creer';

        $this->rights[3][0] = 44; // id de la permission
        $this->rights[3][1] = 'Supprimer un projets/t&acirc;ches'; // libelle de la permission
        $this->rights[3][2] = 'd'; // type de la permission (deprecie a ce jour)
        $this->rights[3][3] = 1; // La permission est-elle une permission par defaut
        $this->rights[3][4] = 'supprimer';

        $this->rights[4][0] = 45; // id de la permission
        $this->rights[4][1] = 'Configurer les param&egrave;tres globaux de tous les projets'; // libelle de la permission
        $this->rights[4][2] = 'c'; // type de la permission (deprecie a ce jour)
        $this->rights[4][3] = 1; // La permission est-elle une permission par defaut
        $this->rights[4][4] = 'configure';

        $this->rights[5][0] = 46; // id de la permission
        $this->rights[5][1] = 'Voir / Modifier les imputations des autres'; // libelle de la permission
        $this->rights[5][2] = 'c'; // type de la permission (deprecie a ce jour)
        $this->rights[5][3] = 1; // La permission est-elle une permission par defaut
        $this->rights[5][4] = 'voirImputations';

        $this->rights[6][0] = 47; // id de la permission
        $this->rights[6][1] = 'Attribution/modification de budgets d’heures associées aux tâches et attribués aux utilisateurs'; // libelle de la permission
        $this->rights[6][2] = 'c'; // type de la permission (deprecie a ce jour)
        $this->rights[6][3] = 1; // La permission est-elle une permission par defaut
        $this->rights[6][4] = 'attribution';

        $this->rights[7][0] = 48; // id de la permission
        $this->rights[7][1] = 'Voir les CA dans les imputations'; // libelle de la permission
        $this->rights[7][2] = 'c'; // type de la permission (deprecie a ce jour)
        $this->rights[7][3] = 1; // La permission est-elle une permission par defaut
        $this->rights[7][4] = 'caImput';

        $this->rights[8][0] = 49; // id de la permission
        $this->rights[8][1] = 'Modifier les projets/taches des autres'; // libelle de la permission
        $this->rights[8][2] = 'c'; // type de la permission (deprecie a ce jour)
        $this->rights[8][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[8][4] = 'modAll';




        $r = 0;
        $this->menu[$r] = array('fk_menu' => 0,
            'type' => 'top',
            'titre' => 'Projet',
            'mainmenu' => $this->nameI,
            'leftmenu' => '0',
            'url' => '/synopsisprojet/liste.php',
            'langs' => 'synopsisproject@synopsisprojet',
            'position' => 7,
            'perms' => '$user->rights->' . $this->nameI . '->lire',
            'target' => '',
            'user' => 0);
        $s = $r;
        $s1 = $r;
        $r++;

        $this->menu[$r] = array('fk_menu' => 'r=' . $s,
            'type' => 'left',
            'titre' => $this->nameI,
            'mainmenu' => $this->nameI,
            'url' => '/synopsisprojet/index.php?leftmenu=projects',
            'langs' => 'synopsisproject@synopsisprojet',
            'position' => 0,
            'perms' => '$user->rights->' . $this->nameI . '->lire',
            'target' => '',
            'user' => 0);
        $s = $r;
        $r++;
        $this->menu[$r] = array('fk_menu' => 'r=' . $s,
            'type' => 'left',
            'titre' => 'Imputations',
            'mainmenu' => $this->nameI,
            'url' => '/synopsisprojet/histo_imputations.php?leftmenu=projects',
            'langs' => 'synopsisproject@synopsisprojet',
            'position' => 0,
            'perms' => '$user->rights->' . $this->nameI . '->lire',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==projects'));
        $r++;
        
       /* $this->menu[$r] = array('fk_menu' => 'r=' . $s,
            'type' => 'left',
            'titre' => 'Test NEW Imputations',
            'mainmenu' => $this->nameI,
            'url' => '/synopsisprojet/histo_imputations.php?leftmenu=projects',
            'langs' => 'synopsisproject@synopsisprojet',
            'position' => 0,
            'perms' => '$user->rights->' . $this->nameI . '->voirImputations',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==projects'));
        $r++;*/


        $this->menu[$r] = array('fk_menu' => 'r=' . $s,
            'type' => 'left',
            'titre' => 'NewProject',
            'mainmenu' => $this->nameI,
            'url' => '/synopsisprojet/nouveau.php?leftmenu=projects',
            'langs' => 'synopsisproject@synopsisprojet',
            'position' => 100,
            'perms' => '$user->rights->' . $this->nameI . '->creer',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==projects'));
        $r++;

        $this->menu[$r] = array('fk_menu' => 'r=' . $s,
            'type' => 'left',
            'titre' => 'List',
            'mainmenu' => $this->nameI,
            'url' => '/synopsisprojet/liste.php?leftmenu=projects',
            'langs' => 'synopsisproject@synopsisprojet',
            'position' => 101,
            'perms' => '$user->rights->' . $this->nameI . '->lire',
            'target' => '',
            'user' => 0,
            'constraints' => array(0 => '$leftmenu==projects'));
        $r++;

        $this->menu[$r] = array('fk_menu' => 'r=' . $s,
            'type' => 'left',
            'titre' => 'MenuConfig',
            'mainmenu' => $this->nameI,
            'url' => '/synopsisprojet/config.php?leftmenu=projects',
            'langs' => 'synopsisproject@synopsisprojet',
            'position' => 101,
            'perms' => '$user->rights->' . $this->nameI . '->configure',
            'target' => '',
            'user' => 0, 'constraints' => array(0 => '$leftmenu==projects'));
        $s = $r;
        $r++;

        $s = $s1;

        $this->menu[$r] = array('fk_menu' => 'r=' . $s,
            'type' => 'left',
            'titre' => 'Tasks',
            'mainmenu' => 'projects',
            'url' => '/synopsisprojet/tasks/index.php?leftmenu=projects_task',
            'langs' => 'synopsisproject@synopsisprojet',
            'position' => 1,
            'perms' => '$user->rights->' . $this->nameI . '->lire',
            'target' => '',
            'user' => 0);
        $s = $r;
        $r++;



        $this->menu[$r] = array('fk_menu' => 'r=' . $s,
            'type' => 'left',
            'titre' => 'Mytasks',
            'mainmenu' => 'projects',
            'url' => '/synopsisprojet/tasks/mytasks.php?leftmenu=projects_task',
            'langs' => 'synopsisproject@synopsisprojet',
            'position' => 100,
            'perms' => '$user->rights->' . $this->nameI . '->lire',
            'target' => '',
            'user' => 0, 'constraints' => array(0 => '$leftmenu==projects_task'));
        $r++;
        $s = $s1;

        $this->menu[$r] = array('fk_menu' => 'r=' . $s1,
            'type' => 'left',
            'titre' => 'Activity',
            'mainmenu' => 'projects',
            'url' => '/synopsisprojet/activity/index.php?leftmenu=projects_activity',
            'langs' => 'synopsisproject@synopsisprojet',
            'position' => 2,
            'perms' => '$user->rights->' . $this->nameI . '->lire',
            'target' => '',
            'user' => 0);
        $s = $r;
        $r++;

        $this->menu[$r] = array('fk_menu' => 'r=' . $s,
            'type' => 'left',
            'titre' => 'MyActivity',
            'mainmenu' => 'projects',
            'url' => '/synopsisprojet/activity/myactivity.php?leftmenu=projects_activity',
            'langs' => 'synopsisproject@synopsisprojet',
            'position' => 100,
            'perms' => '$user->rights->' . $this->nameI . '->lire',
            'target' => '',
            'user' => 0, 'constraints' => array(0 => '$leftmenu==projects_activity'));
        $r++;

        $this->tabs = array('user:+coutUser:Coût horaire:@monmodule:/synopsisprojet/userPrix.php?id=__ID__');
    }

    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees a creer pour ce module.
     */
    function init() {
        // Permissions
        $this->remove();
        dolibarr_set_const($this->db, "PROJECT_HOUR_PER_DAY", 7);
        dolibarr_set_const($this->db, "PROJECT_DAY_PER_WEEK", 5);
        $conf->projet->dir_output = "cool";
        $this->dirs[0] = "/imputations/";
        $this->dirs[1] = "/imputations/temp";

//
//    require_once(DOL_DOCUMENT_ROOT.'core/menubase.class.php');
//    $ModuleMenu = new MenuBase($$this->db)
//    global $user;
//    $this->menu_handler=trim($this->menu_handler);
//
//    $ModuleMenu->module="project";
//    $ModuleMenu->type="left";
//    $ModuleMenu->mainmenu="project";
//    $ModuleMenu->fk_menu="5050";
//    $ModuleMenu->position=0;
//    $ModuleMenu->url=trim($ModuleMenu->url);
//    $ModuleMenu->target=trim($ModuleMenu->target);
//    $ModuleMenu->titre=trim($ModuleMenu->titre);
//    $ModuleMenu->langs=trim($ModuleMenu->langs);
//    $ModuleMenu->level=trim($ModuleMenu->level);
//    $ModuleMenu->leftmenu=1;
//    $ModuleMenu->perms=trim($ModuleMenu->perms);
//    $ModuleMenu->user=trim($ModuleMenu->user);
//    if (! $ModuleMenu->level) $ModuleMenu->level=0;
//    $ModuleMenu->create($user);

        $sql = array(
            "CREATE TABLE IF NOT EXISTS `llx_Synopsis_projet_sup` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user_resp` int(11) DEFAULT NULL,
  `fk_type_projet` int(11) DEFAULT '1',
  `date_valid` datetime DEFAULT NULL,
  `date_launch` datetime DEFAULT NULL,
  PRIMARY KEY (`rowid`)
)",
            
            
            
             "CREATE VIEW IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_view` AS (SELECT p1.*, p2.`fk_user_resp`,`fk_type_projet`,`date_valid`,`date_launch`,p1.note_public as note FROM " . MAIN_DB_PREFIX . "projet p1 LEFT join " . MAIN_DB_PREFIX . "Synopsis_projet_sup p2 ON p1.rowid = p2.rowid)",
            
            
            
            
            
            
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_global_ressources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `nom` varchar(50) DEFAULT NULL,
  `fk_user_resp` int(11) DEFAULT NULL,
  `description` longtext,
  `fk_parent_ressource` int(11) DEFAULT NULL,
  `photo` longblob,
  `date_achat` datetime DEFAULT NULL,
  `isGroup` tinyint(1) NOT NULL DEFAULT '0',
  `valeur` double(24,8) DEFAULT NULL,
  `cout` double(24,8) DEFAULT NULL,
  `zimbra_id` varchar(40) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `fk_resa_type` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_nom` (`nom`),
  KEY `fk_ressource_resp` (`fk_user_resp`),
  KEY `fk_parent_ressource_key` (`fk_parent_ressource`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;", "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_ressource` int(11) DEFAULT NULL,
  `datedeb` datetime DEFAULT NULL,
  `datefin` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_imputation` int(11) DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `zimbraId` varchar(50) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `fk_projet_task` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_author_resa_ressource_key` (`fk_user_author`),
  KEY `user_imputation_resa_ressource_key` (`fk_user_imputation`),
  KEY `resa_ressource_key` (`fk_ressource`),
  KEY `fk_projet` (`fk_projet`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=18 ;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_c_projet_statut` (
  `id` smallint(6) NOT NULL,
  `code` varchar(12) NOT NULL,
  `label` varchar(30) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;", 
            "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "Synopsis_c_projet_statut` (`id`, `code`, `label`, `active`) VALUES
(0, 'PROJDRAFT', 'Brouillon', 1),
(5, 'PROJPLANNING', 'Planification', 1),
(10, 'PROJRUNNING', 'En cours', 1),
(50, 'PROJCLOSE', 'Cloturé', 1),
(999, 'PROJWAITVAL', 'Attente de validation', 1),
(9999, 'PROJABANDON', 'Abandonné', 1);", 
            
//            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_view` (
//  `rowid` int(11) NOT NULL AUTO_INCREMENT,
//  `fk_soc` int(11) DEFAULT NULL,
//  `fk_statut` smallint(6) NOT NULL,
//  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//  `dateo` date DEFAULT NULL,
//  `datec` datetime DEFAULT NULL,
//  `ref` varchar(50) DEFAULT NULL,
//  `title` varchar(255) DEFAULT NULL,
//  `fk_user_resp` int(11) DEFAULT NULL,
//  `fk_user_creat` int(11) DEFAULT NULL,
//  `note` text,
//  `fk_type_projet` int(11) DEFAULT '1',
//  `date_valid` datetime DEFAULT NULL,
//  `date_launch` datetime DEFAULT NULL,
//  `date_close` datetime DEFAULT NULL,
//  `entity` int(11) NOT NULL DEFAULT '1',
//  PRIMARY KEY (`rowid`),
//  UNIQUE KEY `ref` (`ref`),
//  KEY `fk_statut` (`fk_statut`)
//) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=545 ;", 
            
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_document_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;", "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_document_li_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_group` int(11) DEFAULT NULL,
  `fk_document` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;", "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_frais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `montantHT` float(11,3) DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `dateAchat` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `acheteur_id` int(11) DEFAULT NULL,
  `fk_task` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `fk_facture_fourn` int(11) DEFAULT NULL,
  `fk_commande_fourn` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;", "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_Hressources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_debut_resa` datetime DEFAULT NULL,
  `date_fin_resa` datetime DEFAULT NULL,
  `fk_user_resa` int(11) DEFAULT NULL,
  `fk_global_ressource` int(11) NOT NULL,
  `valeur` double(24,8) DEFAULT NULL,
  `cout` double(24,8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_user_resa_key` (`fk_user_resa`),
  KEY `fk_global_ressource_key` (`fk_global_ressource`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;", "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_li_task_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_task` int(11) DEFAULT NULL,
  `fk_group_risk` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;", "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_ressources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `nom` varchar(50) DEFAULT NULL,
  `fk_user_resp` int(11) DEFAULT NULL,
  `description` longtext,
  `fk_parent_ressource` int(11) DEFAULT NULL,
  `photo` longblob,
  `date_achat` datetime DEFAULT NULL,
  `isGroup` tinyint(1) NOT NULL DEFAULT '0',
  `valeur` double(24,8) DEFAULT NULL,
  `cout` double(24,8) DEFAULT NULL,
  `zimbra_id` varchar(40) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL,
  `fk_global_ressource` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_nom` (`nom`),
  KEY `fk_projressource_resp` (`fk_user_resp`),
  KEY `fk_projparent_ressource_key` (`fk_parent_ressource`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;", "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_risk` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `occurence` int(3) DEFAULT NULL,
  `gravite` int(3) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `description` longtext,
  `cout` float(11,5) DEFAULT NULL,
  `fk_projet` int(11) NOT NULL,
  `fk_task` int(11) DEFAULT NULL,
  `fk_risk_group` int(11) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  KEY `fk_projet` (`fk_projet`),
  KEY `fk_task` (`fk_task`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;", "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_risk_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(150) NOT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;", "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "projet_task` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_projet` int(11) NOT NULL,
  `fk_task_parent` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `dateo` datetime DEFAULT NULL,
  `duration_effective` double NOT NULL,
  `duration` double DEFAULT NULL,
  `fk_user_creat` int(11) DEFAULT NULL,
  `statut` enum('open','closed') DEFAULT 'open',
  `note` text,
  `progress` float(5,2) DEFAULT NULL,
  `description` longtext,
  `color` varchar(7) DEFAULT NULL,
  `url` longtext,
  `priority` tinyint(1) DEFAULT NULL,
  `shortDesc` text,
  `level` int(11) DEFAULT NULL,
  `tms` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rowid`),
  KEY `fk_projet` (`fk_projet`),
  KEY `statut` (`statut`),
  KEY `fk_user_creat` (`fk_user_creat`),
  KEY `fk_parent_task_sql` (`fk_task_parent`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=201 ;", "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_task_actors` (
  `fk_projet_task` int(11) NOT NULL,
  `fk_user` int(11) NOT NULL,
  `role` enum('admin','read','acto','info') DEFAULT 'admin',
  `percent` int(11) DEFAULT '100',
  `type` enum('user','group') NOT NULL DEFAULT 'user',
  UNIQUE KEY `fk_projet_task` (`fk_projet_task`,`fk_user`),
  KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_task_depends` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_task` int(11) DEFAULT NULL,
  `fk_depends` int(11) DEFAULT NULL,
  `percent` int(11) DEFAULT '100',
  PRIMARY KEY (`id`),
  UNIQUE KEY `" . MAIN_DB_PREFIX . "Synopsis_projet_task_depends` (`fk_task`,`fk_depends`),
  KEY `fk_parent_task_depends_2_sql` (`fk_depends`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;", 
//            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "projet_task_time` (
//  `rowid` int(11) NOT NULL AUTO_INCREMENT,
//  `fk_task` int(11) NOT NULL,
//  `task_date` datetime DEFAULT NULL,
//  `task_duration` double DEFAULT NULL,
//  `fk_user` int(11) DEFAULT NULL,
//  `note` text,
//  PRIMARY KEY (`rowid`),
//  KEY `fk_task` (`fk_task`),
//  KEY `fk_user` (`fk_user`)
//) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=653 ;", 
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_task_time_effective` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_task` int(11) NOT NULL,
  `task_date_effective` datetime DEFAULT NULL,
  `task_duration_effective` double DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`rowid`),
  KEY `fk_task` (`fk_task`),
  KEY `fk_user` (`fk_user`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2347 ;", "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_task_time_special` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_tranche` int(11) DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `type` enum('User','Group') DEFAULT 'User',
  `qte` int(11) DEFAULT NULL,
  `fk_task` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=38 ;", "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_trancheHoraire` (
  `debut` varchar(5) DEFAULT NULL,
  `fin` varchar(5) DEFAULT NULL,
  `facteur` int(11) DEFAULT NULL,
  `day` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT NULL,
  `active` int(11) DEFAULT '1',
  `defaut` tinyint(4) DEFAULT NULL,
  `refAddOn` varchar(50) DEFAULT NULL,
  `hasTache` tinyint(4) DEFAULT NULL,
  `hasTacheLight` tinyint(4) DEFAULT NULL,
  `hasGantt` tinyint(4) DEFAULT NULL,
  `hasCout` tinyint(11) DEFAULT NULL,
  `hasRH` tinyint(4) DEFAULT NULL,
  `hasRessources` tinyint(4) DEFAULT NULL,
  `hasRisque` tinyint(4) DEFAULT NULL,
  `hasImputation` tinyint(4) DEFAULT NULL,
  `hasPointage` tinyint(4) DEFAULT NULL,
  `hasAgenda` tinyint(4) DEFAULT NULL,
  `hasDocuments` tinyint(4) DEFAULT NULL,
  `hasStats` tinyint(4) DEFAULT NULL,
  `hasReferent` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;", 
            "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "Synopsis_projet_type` (`id`, `type`, `active`, `defaut`, `refAddOn`, `hasTache`, `hasTacheLight`, `hasGantt`, `hasCout`, `hasRH`, `hasRessources`, `hasRisque`, `hasImputation`, `hasPointage`, `hasAgenda`, `hasDocuments`, `hasStats`, `hasReferent`) VALUES
(1, 'Gantt', NULL, NULL, '1', 1, NULL, 1, 1, 1, 1, NULL, NULL, NULL, NULL, 1, 1, 1),
(2, 'Imputations', 1, 1, '6', 1, 1, 1, 1, 1, 1, NULL, 1, NULL, NULL, 1, 1, 1),
(3, 'R&D', 1, NULL, '5', 1, 1, 1, 1, 1, 1, NULL, NULL, NULL, NULL, 1, 1, 1),
(4, 'Interne', 1, NULL, '1', 1, 1, 1, 1, 1, 1, NULL, NULL, NULL, NULL, 1, 1, 1);",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_task_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;",
            "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "Synopsis_projet_task_type` (`id`, `label`) VALUES
(1, 'milestone'),
(2, 'task'),
(3, 'group');",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_hrm_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `hrm_id` int(11) DEFAULT NULL,
  `couthoraire` float(11,3) DEFAULT NULL,
  `startDate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_global_resatype` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;",
            "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "Synopsis_global_resatype` (`id`, `name`) VALUES
(1, 'Par heure'),
(2, 'Par demi journ&eacute;e'),
(3, 'Par journ&eacute;e');",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `montantHT` float(11,3) DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `dateAchat` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `acheteur_id` int(11) DEFAULT NULL,
  `fk_task` int(11) DEFAULT NULL,
  `fk_projet` int(11) DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `fk_facture_fourn` int(11) DEFAULT NULL,
  `fk_commande_fourn` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_ecm_document_assoc` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(16) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filesize` int(11) NOT NULL,
  `filemime` varchar(32) NOT NULL,
  `fullpath_dol` varchar(255) NOT NULL,
  `fullpath_orig` varchar(255) NOT NULL,
  `description` text,
  `manualkeyword` text,
  `fk_create` int(11) NOT NULL,
  `fk_update` int(11) DEFAULT NULL,
  `date_c` datetime NOT NULL,
  `date_u` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_directory` int(11) DEFAULT NULL,
  `fk_status` smallint(6) DEFAULT '0',
  `private` smallint(6) DEFAULT '0',
  `categorie_refid` int(11) DEFAULT NULL,
  `rev` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_ecm_document_auto` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `ref` varchar(16) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filesize` int(11) NOT NULL,
  `filemime` varchar(32) NOT NULL,
  `fullpath_dol` varchar(255) NOT NULL,
  `fullpath_orig` varchar(255) NOT NULL,
  `description` text,
  `manualkeyword` text,
  `fk_create` int(11) NOT NULL,
  `fk_update` int(11) DEFAULT NULL,
  `date_c` datetime NOT NULL,
  `date_u` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_directory` int(11) DEFAULT NULL,
  `fk_status` smallint(6) DEFAULT '0',
  `private` smallint(6) DEFAULT '0',
  `categorie_refid` int(11) DEFAULT NULL,
  `rev` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=101 ;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_ecm_document_auto_categorie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL DEFAULT '',
  `displayOrder` tinyint(2) NOT NULL DEFAULT '100',
  `idStr` varchar(20) DEFAULT NULL,
  `sqlTable` varchar(50) NOT NULL DEFAULT '" . MAIN_DB_PREFIX . "',
  `dirName` varchar(50) DEFAULT ' ',
  `disabled` tinyint(1) DEFAULT '0',
  `conf` varchar(200) DEFAULT NULL,
  `droits` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=23 ;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_ecm_indexer_pool` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) DEFAULT NULL,
  `sphinx_id` int(11) DEFAULT NULL,
  `object_id` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;", "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_li_ecm_element` (
  `category_refid` int(11) NOT NULL DEFAULT '0',
  `ecm_refid` int(11) NOT NULL DEFAULT '0',
  `element_refid` int(11) NOT NULL DEFAULT '0',
  KEY `liaison_fk` (`ecm_refid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_li_ecm_element_assoc` (
  `category_refid` int(11) NOT NULL DEFAULT '0',
  `ecm_assoc_refid` int(11) NOT NULL DEFAULT '0',
  `element_refid` int(11) NOT NULL DEFAULT '0',
  KEY `liaison_ecm_assoc` (`ecm_assoc_refid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;","SET foreign_key_checks = 0;",
            "DROP TABLE IF EXISTS ". MAIN_DB_PREFIX ."projet;",
            "DROP VIEW IF EXISTS ". MAIN_DB_PREFIX ."projet;",
            "CREATE VIEW ". MAIN_DB_PREFIX ."projet as (SELECT rowid, fk_soc, datec as datec, tms, dateo, date_close as datee, ref, entity, title, note as description, fk_user_creat, '' as public, fk_statut, '' as note_private, '' as note_public, '' as model_pdf FROM ". MAIN_DB_PREFIX ."Synopsis_projet_view);",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_projet_task_AQ` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_task` int(11) NOT NULL,
  `fk_user` int(11) NOT NULL,
  `date` date NOT NULL,
  `val` int(11) NOT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;",
            
            
            "ALTER IGNORE TABLE `" . MAIN_DB_PREFIX . "Synopsis_li_ecm_element`
  ADD CONSTRAINT `liaison_fk` FOREIGN KEY (`ecm_refid`) REFERENCES `" . MAIN_DB_PREFIX . "Synopsis_ecm_document_auto` (`rowid`) ON DELETE CASCADE;",
            "ALTER IGNORE TABLE `" . MAIN_DB_PREFIX . "Synopsis_li_ecm_element_assoc`
  ADD CONSTRAINT `liaison_ecm_assoc` FOREIGN KEY (`ecm_assoc_refid`) REFERENCES `" . MAIN_DB_PREFIX . "Synopsis_ecm_document_assoc` (`rowid`) ON DELETE CASCADE;",
            "ALTER IGNORE TABLE `" . MAIN_DB_PREFIX . "Synopsis_global_ressources`
  ADD CONSTRAINT `fk_parent_ressource_key` FOREIGN KEY (`fk_parent_ressource`) REFERENCES `" . MAIN_DB_PREFIX . "Synopsis_global_ressources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ressource_resp` FOREIGN KEY (`fk_user_resp`) REFERENCES `" . MAIN_DB_PREFIX . "user` (`rowid`) ON DELETE NO ACTION;", "ALTER IGNORE TABLE `" . MAIN_DB_PREFIX . "Synopsis_global_ressources_resa`
  ADD CONSTRAINT `resa_ressource_key ` FOREIGN KEY (`fk_ressource`) REFERENCES `" . MAIN_DB_PREFIX . "Synopsis_global_ressources` (`id`) ON DELETE NO ACTION,
  ADD CONSTRAINT `user_author_resa_ressource_key` FOREIGN KEY (`fk_user_author`) REFERENCES `" . MAIN_DB_PREFIX . "user` (`rowid`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_imputation_resa_ressource_key ` FOREIGN KEY (`fk_user_imputation`) REFERENCES `" . MAIN_DB_PREFIX . "user` (`rowid`) ON DELETE NO ACTION;", 
            "ALTER IGNORE TABLE `" . MAIN_DB_PREFIX . "Synopsis_projet_view`
  ADD CONSTRAINT `" . MAIN_DB_PREFIX . "Synopsis_projet_ibfk_1` FOREIGN KEY (`fk_statut`) REFERENCES `" . MAIN_DB_PREFIX . "Synopsis_c_projet_statut` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;",
            "ALTER IGNORE TABLE `" . MAIN_DB_PREFIX . "Synopsis_projet_Hressources`
  ADD CONSTRAINT `fk_global_ressource_key` FOREIGN KEY (`fk_global_ressource`) REFERENCES `" . MAIN_DB_PREFIX . "Synopsis_global_ressources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_resa_key` FOREIGN KEY (`fk_user_resa`) REFERENCES `" . MAIN_DB_PREFIX . "user` (`rowid`) ON DELETE NO ACTION;",
            "ALTER IGNORE TABLE `" . MAIN_DB_PREFIX . "Synopsis_projet_ressources`
  ADD CONSTRAINT `fk_projessource_resp` FOREIGN KEY (`fk_user_resp`) REFERENCES `" . MAIN_DB_PREFIX . "user` (`rowid`) ON DELETE NO ACTION,
  ADD CONSTRAINT `fk_projparent_ressource_key` FOREIGN KEY (`fk_parent_ressource`) REFERENCES `" . MAIN_DB_PREFIX . "Synopsis_global_ressources` (`id`) ON DELETE CASCADE;",
            "ALTER IGNORE TABLE `" . MAIN_DB_PREFIX . "Synopsis_projet_risk`
  ADD CONSTRAINT `fk_projet_risk` FOREIGN KEY (`fk_projet`) REFERENCES `" . MAIN_DB_PREFIX . "Synopsis_projet_view` (`rowid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_Synopsis_projet_task_risk` FOREIGN KEY (`fk_task`) REFERENCES `" . MAIN_DB_PREFIX . "projet_task` (`rowid`) ON DELETE CASCADE;",
            "ALTER IGNORE TABLE `" . MAIN_DB_PREFIX . "projet_task`
  ADD CONSTRAINT `fk_parent_task_sql` FOREIGN KEY (`fk_task_parent`) REFERENCES `" . MAIN_DB_PREFIX . "projet_task` (`rowid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_projet_task` FOREIGN KEY (`fk_projet`) REFERENCES `" . MAIN_DB_PREFIX . "Synopsis_projet_view` (`rowid`) ON DELETE CASCADE;",
            "ALTER IGNORE TABLE `" . MAIN_DB_PREFIX . "Synopsis_projet_task_actors`
  ADD CONSTRAINT `fk_parent_task_actor_sql` FOREIGN KEY (`fk_projet_task`) REFERENCES `" . MAIN_DB_PREFIX . "projet_task` (`rowid`) ON DELETE CASCADE;",
            "ALTER IGNORE TABLE `" . MAIN_DB_PREFIX . "Synopsis_projet_task_depends`
  ADD CONSTRAINT `fk_parent_task_depends_1_sql` FOREIGN KEY (`fk_task`) REFERENCES `" . MAIN_DB_PREFIX . "projet_task` (`rowid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_parent_task_depends_2_sql` FOREIGN KEY (`fk_depends`) REFERENCES `" . MAIN_DB_PREFIX . "projet_task` (`rowid`) ON DELETE CASCADE;",
            "ALTER IGNORE TABLE `" . MAIN_DB_PREFIX . "projet_task_time`
  ADD CONSTRAINT `fk_parent_task_time_sql` FOREIGN KEY (`fk_task`) REFERENCES `" . MAIN_DB_PREFIX . "projet_task` (`rowid`) ON DELETE CASCADE;"
           
                
            
            
            );

        return $this->_init($sql);
    }

    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove($option = '') {
        $sql = array("DROP VIEW IF EXISTS ". MAIN_DB_PREFIX ."projet;");
//    $requete = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE mainmenu='".$this->nameI."'";
//    $sql = $this->db->query($requete);

        return $this->_remove($sql, $option);
    }

}

?>
