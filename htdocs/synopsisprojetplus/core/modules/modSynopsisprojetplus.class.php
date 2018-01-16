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
 * BIMP-ERP by Synopsis et DRSI
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
class modSynopsisprojetplus extends DolibarrModules {

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function __construct($DB) {
        $this->db = $DB;
        $this->numero = 95002;

        $this->family = "Synopsis";
        $this->name = "Projet +++";
        $this->nameI = "synopsisprojetplus";
        $this->description = "Gestion des projets avancé ++";
        $this->version = 2;
        $this->const_name = 'MAIN_MODULE_SYNOPSISPROJETPLUS';
        $this->special = 0;
        $this->picto = 'project';
        $this->langfiles = array("synopsisprojetplus@synopsisprojetplus");

        // Dependances
        $this->depends = array();
        $this->requiredby = array();
        $this->config_page_url = preg_replace('/^mod/i', '', get_class($this)).".php";


        // Constants
        $this->const = array();
        $r = 0;
        
        $this->rights_class = 'synopsisprojet';
        $this->rights[1][0] = 46; // id de la permission
        $this->rights[1][1] = 'Voir / Modifier les imputations des autres'; // libelle de la permission
        $this->rights[1][2] = 'c'; // type de la permission (deprecie a ce jour)
        $this->rights[1][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[1][4] = 'voirImputations';

        $this->rights[6][0] = 47; // id de la permission
        $this->rights[6][1] = 'Attribution/modification de budgets d’heures associées aux tâches et attribués aux utilisateurs'; // libelle de la permission
        $this->rights[6][2] = 'c'; // type de la permission (deprecie a ce jour)
        $this->rights[6][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[6][4] = 'attribution';

        $this->rights[7][0] = 48; // id de la permission
        $this->rights[7][1] = 'Voir les CA dans les imputations'; // libelle de la permission
        $this->rights[7][2] = 'c'; // type de la permission (deprecie a ce jour)
        $this->rights[7][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[7][4] = 'caImput';

        $this->rights[8][0] = 49; // id de la permission
        $this->rights[8][1] = 'Voir les tableaux multi-utilisateurs'; // libelle de la permission
        $this->rights[8][2] = 'c'; // type de la permission (deprecie a ce jour)
        $this->rights[8][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[8][4] = 'tabMultiUser';

        $this->rights[9][0] = 50; // id de la permission
        $this->rights[9][1] = 'Voir les tableaux de bord direction'; // libelle de la permission
        $this->rights[9][2] = 'c'; // type de la permission (deprecie a ce jour)
        $this->rights[9][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[9][4] = 'tabAdmin';
        
        
        
        $r = 0;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=project',
            'type' => 'left',
            'titre' => 'Imputations',
            'mainmenu' => 'project',
            'leftmenu' => '0',
            'url' => '/synopsisprojetplus/histo_imputations.php',
            'langs' => 'synopsisproject@synopsisprojet',
            'position' => 7,
            'perms' => '$user->rights->' . $this->rights_class . '->voirImputations',
            'target' => '',
            'user' => 0);
        $s = $r;
        $s1 = $r;
        $r++;
        
        
        
        $this->module_parts = array(
        'hooks' => array('projecttaskcard')  // Set here all hooks context you want to support
        );
  
        
        
        $this->tabs = array('task:+attribution:Attribution:@monmodule:$user->rights->projet->lire:/synopsisprojetplus/task/timeP.php?&withproject=1&id=__ID__',
            'project:+imputations:Imputations:@monmodule:$user->rights->' . $this->rights_class . '->attribution:/synopsisprojetplus/histo_imputations.php?id=__ID__');
        
        $r = 0;
        $this->boxes[$r] = array(
        'file' => 'box_graph_caimput@synopsisprojetplus',
        'note' => 'CA Imputations par mois',
        'enabledbydefaulton' => 'Home'
    );
            $r++;
        $this->boxes[$r] = array(
        'file' => 'box_graph_derive@synopsisprojetplus',
        'note' => 'Graph derive',
        'enabledbydefaulton' => 'Home'
    );
            $r++;
        $this->boxes[$r] = array(
        'file' => 'box_graph_tauxH@synopsisprojetplus',
        'note' => 'Graph taux heure vendue',
        'enabledbydefaulton' => 'Home'
    );
            $r++;
    }

    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees a creer pour ce module.
     */
    function init() {
        // Permissions
        $this->remove();
        
        $sql = array("
CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."synopsis_projet_task_timeP` (
   `rowid` int(11) NOT NULL,
  `fk_task` int(11) NOT NULL,
  `task_date` date DEFAULT NULL,
  `task_datehour` datetime DEFAULT NULL,
  `task_date_withhour` int(11) DEFAULT '0',
  `task_duration` double DEFAULT NULL,
  `fk_user` int(11) DEFAULT NULL,
  `thm` double(24,8) DEFAULT NULL,
  `occupation` int(11) DEFAULT NULL,
  `note` text
)",
            
                        
  "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_projet_sup` (
  `rowid` int(11) NOT NULL,
  `fk_user_resp` int(11) DEFAULT NULL,
  `fk_type_projet` int(11) DEFAULT '1',
  `date_valid` datetime DEFAULT NULL,
  `date_launch` datetime DEFAULT NULL
);",        
            
"CREATE OR REPLACE VIEW `" . MAIN_DB_PREFIX . "Synopsis_projet_view` AS (SELECT p1.*, p2.`fk_user_resp`,`fk_type_projet`,`date_valid`,`date_launch`,p1.note_public as note FROM " . MAIN_DB_PREFIX . "projet p1 LEFT join " . MAIN_DB_PREFIX . "Synopsis_projet_sup p2 ON p1.rowid = p2.rowid)",            
  
            
 /*           "ALTER TABLE `".MAIN_DB_PREFIX."synopsis_projet_task_timeP`
  ADD KEY `idx_Synopsis_projet_task_time_task` (`fk_task`),
  ADD KEY `idx_Synopsis_projet_task_time_date` (`task_date`),
  ADD KEY `idx_Synopsis_projet_task_time_datehour` (`task_datehour`);",*/
            
            "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."synopsisprojet_stat` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `dateC` date NOT NULL,
  `type` varchar(255) NOT NULL,
  `occupation` int(11) NOT NULL,
  `valeur` decimal(10,2) NOT NULL,
  PRIMARY KEY (`rowid`));",
            
            
            "UPDATE ".MAIN_DB_PREFIX."projet_task t SET t.dateo = (SELECT p.dateo FROM ".MAIN_DB_PREFIX."projet p WHERE t.fk_projet = p.rowid) WHERE t.dateo < '1980-01-01' || t.dateo is NULL;",

"UPDATE ".MAIN_DB_PREFIX."synopsis_projet_task_timeP t SET t.`task_date` = (SELECT p.dateo FROM ".MAIN_DB_PREFIX."projet_task p WHERE t.fk_task = p.rowid) WHERE t.`task_date` < '1980-01-01';",

"UPDATE `".MAIN_DB_PREFIX."projet_task` t SET `dateo` = (SELECT MIN(ti.`task_date`) FROM `".MAIN_DB_PREFIX."projet_task_time` ti WHERE (dateo is NULL || DATE(t.`dateo`) > DATE(ti.`task_date`) ) AND ti.`fk_task` = t.rowid) WHERE t.rowid IN (SELECT ti.fk_task FROM `".MAIN_DB_PREFIX."projet_task_time` ti WHERE (dateo is NULL || DATE(t.`dateo`) > DATE(ti.`task_date`) ) AND ti.`fk_task` = t.rowid)",
"UPDATE `".MAIN_DB_PREFIX."projet_task` t SET `dateo` = (SELECT MIN(ti.`task_date`) FROM `".MAIN_DB_PREFIX."synopsis_projet_task_timeP` ti WHERE (dateo is NULL || DATE(t.`dateo`) > DATE(ti.`task_date`)) AND ti.`fk_task` = t.rowid) WHERE t.rowid IN (SELECT ti.fk_task FROM `".MAIN_DB_PREFIX."synopsis_projet_task_timeP` ti WHERE (dateo is NULL || DATE(t.`dateo`) > DATE(ti.`task_date`) ) AND ti.`fk_task` = t.rowid)",
"UPDATE `".MAIN_DB_PREFIX."projet_task` t SET `dateo` = (SELECT MIN(ti.`date`) FROM `".MAIN_DB_PREFIX."Synopsis_projet_task_AQ` ti WHERE (dateo is NULL || DATE(t.`dateo`) > DATE(ti.`date`)) AND ti.`fk_task` = t.rowid) WHERE t.rowid IN (SELECT ti.fk_task FROM `".MAIN_DB_PREFIX."Synopsis_projet_task_AQ` ti WHERE (dateo is NULL || DATE(t.`dateo`) > DATE(ti.`date`) ) AND ti.`fk_task` = t.rowid)",


"UPDATE `".MAIN_DB_PREFIX."projet` p SET `dateo` = (SELECT MIN(t.`dateo`) FROM `".MAIN_DB_PREFIX."projet_task` t WHERE (DATE(p.`dateo`) >  DATE(t.`dateo`)  )AND t.`fk_projet` = p.rowid) WHERE p.rowid IN (SELECT t.fk_projet FROM `".MAIN_DB_PREFIX."projet_task` t WHERE (DATE(p.`dateo`) >  DATE(t.`dateo`)  )AND t.`fk_projet` = p.rowid)",

"UPDATE `".MAIN_DB_PREFIX."projet` p SET `date_close` = (SELECT MAX(ti.`task_date`) FROM `".MAIN_DB_PREFIX."projet_task_time` ti, ".MAIN_DB_PREFIX."projet_task t WHERE ( DATE(p.`date_close`) < DATE(ti.`task_date`) ) AND t.`fk_projet` = p.rowid AND ti.fk_task = t.rowid) WHERE p.rowid IN (SELECT t.fk_projet FROM `".MAIN_DB_PREFIX."projet_task_time` ti, ".MAIN_DB_PREFIX."projet_task t WHERE ( DATE(p.`date_close`) < DATE(ti.`task_date`) ) AND t.`fk_projet` = p.rowid AND ti.fk_task = t.rowid)",

"UPDATE `".MAIN_DB_PREFIX."projet` p SET `date_close` = (SELECT MAX(ti.`task_date`) FROM `".MAIN_DB_PREFIX."synopsis_projet_task_timeP` ti, ".MAIN_DB_PREFIX."projet_task t WHERE ( DATE(p.`date_close`) < DATE(ti.`task_date`) ) AND t.`fk_projet` = p.rowid AND ti.fk_task = t.rowid) WHERE p.rowid IN (SELECT t.fk_projet FROM `".MAIN_DB_PREFIX."synopsis_projet_task_timeP` ti, ".MAIN_DB_PREFIX."projet_task t WHERE ( DATE(p.`date_close`) < DATE(ti.`task_date`) ) AND t.`fk_projet` = p.rowid AND ti.fk_task = t.rowid);",

"UPDATE `".MAIN_DB_PREFIX."projet` p SET `date_close` = (SELECT MAX(ti.`date`) FROM `".MAIN_DB_PREFIX."Synopsis_projet_task_AQ` ti, ".MAIN_DB_PREFIX."projet_task t WHERE ( DATE(p.`date_close`) < DATE(ti.`date`) ) AND t.`fk_projet` = p.rowid AND ti.fk_task = t.rowid) WHERE p.rowid IN (SELECT t.fk_projet FROM `".MAIN_DB_PREFIX."Synopsis_projet_task_AQ` ti, ".MAIN_DB_PREFIX."projet_task t WHERE ( DATE(p.`date_close`) < DATE(ti.`date`) ) AND t.`fk_projet` = p.rowid AND ti.fk_task = t.rowid);");

        return $this->_init($sql);
    }

    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove($option = '') {

        return $this->_remove($sql, $option);
    }

}

?>
