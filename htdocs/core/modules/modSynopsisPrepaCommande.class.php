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
include_once "DolibarrModules.class.php";

/**     \class      modProspectBabel
  \brief      Classe de description et activation du module de Prospection Babel
 */
class modSynopsisPrepaCommande extends DolibarrModules {

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function modSynopsisPrepaCommande($DB) {
        $this->db = $DB;
        $this->numero = 2227;

        $this->family = "Synopsis";
        $this->name = "Prepa Commande";
        $this->description = "Pr&eacute;paration de commande";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISPREPACOMMANDE';
        $this->special = 0;
        $this->picto = 'prepaCommande@Synopsis_PrepaCommande';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = "Synopsis_PrepaCommande.php";
//        $this->config_page_url = "";
        // Dependences
        $this->depends = array();
        $this->requiredby = array();

        // Constantes
        $this->const = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'SynopsisPrepaCom';

        $r = 0;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s aux menus de commande';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'all'; // Famille
        $this->rights[$r][5] = 'Afficher'; // Droit
        $r++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Voir les prix';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'all'; // Famille
        $this->rights[$r][5] = 'AfficherPrix'; // Droit
        $r++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Modifier l\'&eacute;tat';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'all'; // Famille
        $this->rights[$r][5] = 'ModifierEtat'; // Droit
        $r++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Associer un commercial';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'all'; // Famille
        $this->rights[$r][5] = 'AssocierCom'; // Droit
        $r++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Associer un technicien';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'all'; // Famille
        $this->rights[$r][5] = 'AssocierTech'; // Droit
        $r++;


        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Voir la fiche financi&egrave;re';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'financier'; // Famille
        $this->rights[$r][5] = 'Afficher'; // Droit
        $r++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Modifier la fiche financi&egrave;re';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'financier'; // Famille
        $this->rights[$r][5] = 'Modifier'; // Droit
        $r++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Afficher la fiche exp&eacute;dition';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'exped'; // Famille
        $this->rights[$r][5] = 'Afficher'; // Droit
        $r++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Modifier la fiche exp&eacute;dition';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'exped'; // Famille
        $this->rights[$r][5] = 'Modifier'; // Droit
        $r++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Afficher les interventions';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'interventions'; // Famille
        $this->rights[$r][5] = 'Afficher'; // Droit
        $r++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Cloner / Modifier les interventions';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'interventions'; // Famille
        $this->rights[$r][5] = 'Modifier'; // Droit
        $r++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Afficher les contrats';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'contrat'; // Famille
        $this->rights[$r][5] = 'Afficher'; // Droit
        $r++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Modifier les contrats';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'contrat'; // Famille
        $this->rights[$r][5] = 'Modifier'; // Droit
        $r++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Administrer les imports';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'import'; // Famille
        $this->rights[$r][5] = 'Admin'; // Droit
        $r++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Administrer la configuration';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'config'; // Famille
        $this->rights[$r][5] = 'Admin'; // Droit
        $r++;




        // Menus
        //------
        $this->menus = array();            // List of menus to add
        $r = 0;
//TODO menu
//         Top menu
        /* $this->menu[$r]=array('fk_menu'=>0,
          'type'=>'top',
          'titre'=>'Prepa.',
          'mainmenu'=>'Prepacom',
          'leftmenu'=>'1',        // To say if we can overwrite leftmenu
          'url'=>'/Synopsis_PrepaCommande/index.php',
          'langs' => 'synopsisGene@Synopsis_Tools',
          'position'=>20,
          'perms'=>'$user->rights->SynopsisPrepaCom->all->Afficher',
          'target'=>'',
          'user'=>0);
          $r++; */



        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=SynopsisTools',
            'type' => 'left',
            'titre' => 'Importation 8Sens',
            'leftmenu' => 'SynPrepaImp',
            'url' => '/Synopsis_PrepaCommande/import/index.php',
            'langs' => 'synopsisGene@Synopsis_Tools',
            'position' => 11,
            'perms' => '$user->rights->SynopsisPrepaCom->import->Admin',
            'target' => '',
            'user' => 0);
        $s1 = $r;
        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=SynopsisTools,fk_leftmenu=SynPrepaImp',
            'type' => 'left',
            'titre' => 'Historique',
            'url' => '/Synopsis_PrepaCommande/import/history.php',
            'langs' => 'synopsisGene@Synopsis_Tools',
            'position' => 111,
            'perms' => '$user->rights->SynopsisPrepaCom->import->Admin',
            'target' => '',
            'user' => 0);
        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=SynopsisTools,fk_leftmenu=SynPrepaImp',
            'type' => 'left',
            'titre' => 'Config',
            'url' => '/Synopsis_PrepaCommande/import/config.php',
            'langs' => 'synopsisGene@Synopsis_Tools',
            'position' => 112,
            'perms' => '$user->rights->SynopsisPrepaCom->import->Admin',
            'target' => '',
            'user' => 0);
        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=SynopsisTools,fk_leftmenu=SynPrepaImp',
            'type' => 'left',
            'titre' => 'Import',
            'url' => '/Synopsis_PrepaCommande/import/testImport.php',
            'langs' => 'synopsisGene@Synopsis_Tools',
            'position' => 113,
            'perms' => '$user->rights->SynopsisPrepaCom->import->Admin',
            'target' => '',
            'user' => 0);
        $r++;

        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=SynopsisTools',
            'type' => 'left',
            'titre' => 'Prepa.',
            'leftmenu' => 'SynopsisPrepa',
            'url' => '/Synopsis_PrepaCommande/index.php',
            'langs' => 'synopsisGene@Synopsis_Tools',
            'position' => 12,
            'perms' => '$user->rights->SynopsisPrepaCom->all->Afficher',
            'target' => '',
            'user' => 0);
        $s0 = $r;
        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=SynopsisTools,fk_leftmenu=SynopsisPrepa',
            'type' => 'left',
            'titre' => 'Config.',
            'url' => '/Synopsis_PrepaCommande/config.php',
            'langs' => 'synopsisGene@Synopsis_Tools',
            'position' => 121,
            'perms' => '$user->rights->SynopsisPrepaCom->config->Admin',
            'target' => '',
            'user' => 0);
        $s0 = $r;
        $r++;
        
        global $tabProductType;
        foreach ($tabProductType as $id => $type) {
            $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=products,fk_leftmenu=service',
                'type' => 'left',
                'titre' => $type,
                'url' => '/product/liste.php?type='.$id,
                'langs' => 'synopsisGene@Synopsis_Tools',
                'position' => 122,
                'perms' => '$user->rights->produit->lire',
                'target' => '',
                'user' => 0);
            $s0 = $r;
            $r++;
        }

        $this->tabs = array('order:+prepaCommande:Prepa Commande:@monmodule:/Synopsis_PrepaCommande/prepacommande.php?id=__ID__');
    }

    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees e creer pour ce module.
     */
    function init() {
        $sql = array("CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_PrepaCom_c_cat_total` (
  `catId` int(11) default NULL,
  KEY `catId` (`catId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "Synopsis_PrepaCom_c_cat_total` (`catId`) VALUES
(57),
(59),
(79),
(84),
(87);", "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_PrepaCom_c_cat_listContent` (
  `catId` int(11) default NULL,
  KEY `catId` (`catId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;", "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "Synopsis_PrepaCom_c_cat_listContent` (`catId`) VALUES
(3),
(4),
(5),
(34),
(39),
(49),
(56),
(57),
(59),
(62),
(66),
(78),
(79),
(81),
(82),
(84),
(85),
(87),
(88),
(89),
(130),
(141),
(144),
(151),
(154),
(156);",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_PrepaCom_c_commande_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;",
            "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "Synopsis_PrepaCom_c_commande_status` (`id`, `label`) VALUES
(1, 'Non examinée'),
(2, 'Problèmes Techniques'),
(3, 'Attente dispo'),
(4, 'Attente Info Com.'),
(5, 'A ventiler par tech'),
(6, 'Sous vendue'),
(7, 'Bloquée'),
(8, 'On verra bien'),
(9, 'Ventilée');",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_commande` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `logistique_ok` int(11) NOT NULL,
  `logistique_statut` int(11) NOT NULL,
  `finance_ok` int(11) NOT NULL,
  `finance_statut` int(11) NOT NULL,
  `logistique_date_dispo` datetime NOT NULL,
  `fk_entrepot` INT NOT NULL ,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_commandedet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `finance_ok` int(11) NOT NULL,
  `logistique_ok` int(11) NOT NULL,
  `logistique_date_dispo` date NOT NULL,
  `coef` int(11) NOT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_PrepaCom_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tms` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `commande_refid` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `message` longtext,
  `user_author` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_author` (`user_author`),
  KEY `commande_refid` (`commande_refid`))",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_commande_grp` (
  `id` int(11) NOT NULL auto_increment,
  `nom` varchar(150) default NULL,
  `tms` timestamp NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `datec` datetime default NULL,
  PRIMARY KEY  (`id`)
);",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_commande_grpdet` (
  `id` int(11) NOT NULL auto_increment,
  `commande_group_refid` int(11) default NULL,
  `command_refid` int(11) default NULL,
  `refCommande` varchar(50) default NULL,
  PRIMARY KEY  (`id`),
  KEY `commande_group_refid` (`commande_group_refid`),
  CONSTRAINT `" . MAIN_DB_PREFIX . "Synopsis_commande_grpdet_ibfk_1` FOREIGN KEY (`commande_group_refid`) REFERENCES `" . MAIN_DB_PREFIX . "Synopsis_commande_grp` (`id`) ON DELETE CASCADE
)");
        return $this->_init($sql);
    }

    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove() {
        $sql = array();
        $requete = "DELETE FROM " . MAIN_DB_PREFIX . "menu WHERE module='Prepacom'";
        $this->db->query($requete);
        return $this->_remove($sql);
    }

}

?>