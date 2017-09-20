<?php
/* Copyright (C) 2007      Laurent Destailleur  <eldy@users.sourceforge.net>
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
  * Infos on http://www.finapro.fr
  *
  */
/*
 *
 * $Id: modSynopsisAffaire.class.php,v 1.5 2008/01/13 22:48:28 eldy Exp $
 */

/**
        \defgroup   SSLCert     Module SSLCert
        \brief      Module pour gerer une base de noms de SSLCertes
*/

/**
        \file       htdocs/core/modules/modSynopsisAffaire.class.php
        \ingroup    adherent
        \brief      Fichier de description et activation du module SSLCert
*/

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
        \class      modAdherent
        \brief      Classe de description et activation du module Adherent
*/

class modSynopsisAffaire extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function modSynopsisAffaire($DB)
    {
        $this->db = $DB;
        $this->numero = 226200 ;

        $this->family = "Synopsis";
        $this->name = "Affaire";
        $this->description = "Gestion par affaire";
        $this->version = '0.1';            // 'development' or 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISAFFAIRE';
        $this->special = 0;
        $this->picto='category-expanded';

        // Dir
        //----
        $this->dirs = array();

        // Config pages
        //-------------
        $this->config_page_url = array("Synopsis_Affaire.php");

        // Dependances
        //------------
        $this->depends = array('modCommercial',
                               'modCommande',
                               'modDomain',
                               'modBabelSSLCert',
                               'modFacture',
                               'modExpedition',
                               'modFournisseur');
        $this->requiredby = array();
        $this->langfiles = array("affaire");

        // Constantes
        //-----------
        $this->const = array();

        // Boites
        //-------
        $this->boxes = array();

        // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'affaire';
        $r=0;

        $r++;
        $this->rights[$r][0] = 226201;
        $this->rights[$r][1] = 'Lire les affaires';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'lire';

        $r++;
        $this->rights[$r][0] = 226202;
        $this->rights[$r][1] = 'Creer / modifier les affaires';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'creer';

        $r++;
        $this->rights[$r][0] = 226203;
        $this->rights[$r][1] = 'Effacer des affaires';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'effacer';

        $r++;
        $this->rights[$r][0] = 226204;
        $this->rights[$r][1] = 'Valider des affaires';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'valider';

        // Exports
        //--------
        $r=0;

        // $this->export_code[$r]          Code unique identifiant l'export (tous modules confondus)
        // $this->export_label[$r]         Libelle par defaut si traduction de cle "ExportXXX" non trouvee (XXX = Code)
        // $this->export_permission[$r]    Liste des codes permissions requis pour faire l'export
        // $this->export_fields_sql[$r]    Liste des champs exportables en codif sql
        // $this->export_fields_name[$r]   Liste des champs exportables en codif traduction
        // $this->export_sql[$r]           Requete sql qui offre les donnees a l'export

//         Top menu
        $r=0;
        $this->menu[$r]=array('fk_menu'=>0,
                            'type'=>'top',
                            'titre'=>'Affaire',
                            'mainmenu'=>'Affaire',
                            'leftmenu'=>'1',        // To say if we can overwrite leftmenu
                            'url'=>'/Synopsis_Affaire/index.php',
                            'langs' => 'synopsisGene@synopsistools',
                            'position'=>20,
                            'perms'=>'$user->rights->affaire->lire',
                            'target'=>'',
                            'user'=>0);
        $r++;

//
        $this->menu[$r]=array('fk_menu'=>'r=0',
                            'type'=>'left',
                            'titre'=>'Affaire',
                            'mainmenu'=>'Affaire',
                            'url'=>'/Synopsis_Affaire/index.php',
                            'langs' => 'synopsisGene@synopsistools',
                            'position'=>1,
                            'perms'=>'$user->rights->affaire->lire',
                            'target'=>'',
                            'user'=>0);
        $par = $r;
        $r++;
        $this->menu[$r]=array('fk_menu'=>'r='.$par,
                            'type'=>'left',
                            'titre'=>'Nouveau',
                            'mainmenu'=>'Affaire',
                            'url'=>'/Synopsis_Affaire/nouveau.php',
                            'langs' => 'synopsisGene@synopsistools',
                            'position'=>1,
                            'perms'=>'$user->rights->affaire->creer',
                            'target'=>'',
                            'user'=>0);
        $r++;
        $this->menu[$r]=array('fk_menu'=>'r='.$par,
                            'type'=>'left',
                            'titre'=>'List',
                            'mainmenu'=>'Affaire',
                            'url'=>'/Synopsis_Affaire/list.php',
                            'langs' => 'synopsisGene@synopsistools',
                            'position'=>1,
                            'perms'=>'$user->rights->affaire->lire',
                            'target'=>'',
                            'user'=>0);
        $r++;



    }


    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees a creer pour ce module.
     */
    function init()
    {
        global $conf;

        // Permissions
        $this->remove();
        $this->dirs[0]=$conf->synopsisaffaire->dir_output;
        $sql = array("CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_Affaire` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) DEFAULT NULL,
  `description` longtext,
  `date_creation` timestamp NULL DEFAULT NULL,
  `tms` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_user_create` int(11) DEFAULT NULL,
  `statut` int(11) NOT NULL DEFAULT '0',
  `ref` varchar(75) DEFAULT NULL,
  `entity` INT NOT NULL DEFAULT  '1',
  PRIMARY KEY (`id`),
  KEY `fk_user_create` (`fk_user_create`))",
                "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_Affaire_Element` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT NULL,
  `element_id` int(11) DEFAULT NULL,
  `tms` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datea` timestamp NULL DEFAULT NULL,
  `fk_author` int(11) DEFAULT NULL,
  `affaire_refid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniqIdx_Affaire_Element` (`type`,`element_id`,`affaire_refid`),
  KEY `fk_author` (`fk_author`),
  KEY `affaire_refid` (`affaire_refid`))",
                "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_Affaire_key` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `affaire_refid` int(11) DEFAULT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `description` longtext,
  PRIMARY KEY (`id`),
  KEY `affaire_refid` (`affaire_refid`))",
                "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_Affaire_value` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `affaire_refid` int(11) DEFAULT NULL,
  `value` longtext,
  `key_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_id` (`key_id`),
  KEY `template_refid` (`affaire_refid`))");
        return $this->_init($sql);
    }
    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove()
    {
        $sql = array();
        $requete = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE rowid = 226201";
        $res = $this->db->query($requete);

        return $this->_remove($sql);
    }

}
?>