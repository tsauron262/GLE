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

class modSynopsisZimbra extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modSynopsisZimbra($DB)
    {
        $this->db = $DB ;
        $this->numero = 22231;

        $this->family = "Synopsis";
        $this->name = "Zimbra";
        $this->description = "Zimbra Synopsis et DRSI";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISZIMBRA';
        $this->special = 0;
        $this->picto='ZIMBRABABEL';

        // Dir
        $this->dirs = array();

        // Config pages
        $this->config_page_url = preg_replace('/^mod/i', '', get_class($this)).".php";

        // Dependences
        $this->depends = array("modAgenda", "modBabelCalendar");
        $this->requiredby = array();

        // Constantes
        $this->const = array();

        // Boites
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'SynopsisZimbra'; //Max 12 lettres


        $r = 0;
        $this->rights[$r][0] = $this->numero."1";// this->numero ."". 1
        $this->rights[$r][1] = 'Affichage de Zimbra';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][5] = 'Affiche'; // Droit
        $r ++;
        $this->rights[$r][0] = $this->numero."2";// this->numero ."". 1
        $this->rights[$r][1] = 'Pousser des donn&eacute;es dans Zimbra';
        $this->rights[$r][2] = 'p'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][5] = 'Push'; // Droit
        $r ++;
        $this->rights[$r][0] = $this->numero."3";// this->numero ."". 1
        $this->rights[$r][1] = 'Prendre des documents depuis Zimbra';
        $this->rights[$r][2] = 'p'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][5] = 'Pull'; // Droit
        $r ++;
        $this->rights[$r][0] = $this->numero."4";// this->numero ."". 2
        $this->rights[$r][1] = 'Pousser des PJ dans ECM';
        $this->rights[$r][2] = 'e'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][5] = 'PushECM'; // Droit
        $r ++;
        $this->rights[$r][0] = $this->numero."5";// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s au calendrier Zimbra centralis&eacute;e des soci&eacute;t&eacute;s';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][5] = 'AfficheCal'; // Droit
        $r ++;
        $this->rights[$r][0] = $this->numero."6";// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s au contact Zimbra centralis&eacute;';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][5] = 'AfficheCont'; // Droit
        $r ++;
        $this->rights[$r][0] = $this->numero."7";// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s &agrave; mon calendrier Zimbra centralis&eacute;';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][5] = 'AfficheMyUserCal'; // Droit
        $r ++;
        $this->rights[$r][0] = $this->numero."8";// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s &agrave; tous les calendriers utilisateurs centralis&eacute;s';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][5] = 'AfficheAllUserCal'; // Droit
        $r ++;

        // Menus
        //------
        $r=0;

        $this->menu[$r]=array('fk_menu'=>0,'type'=>'top','titre'=>'Collab','mainmenu'=>'zimbra','leftmenu'=>'0','url'=>'/Synopsis_Zimbra/index.php','langs'=>'other','position'=>100,'perms'=>'$user->rights->SynopsisZimbra->Affiche','target'=>'','user'=>0);
        $r++;

        
        //Ajout onglet dans fiche utilisateur
        $this->tabs = array('user:+zimbraUser:Zimbra:@monmodule:/Synopsis_Zimbra/userZimbra.php?id=__ID__');
  }

   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees a creer pour ce module.
    */
  function init()
  {
    $sql = array("CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_Zimbra_Briefcase_User` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_refid` int(11) DEFAULT NULL,
  `BriefcaseName` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;",
        "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_Zimbra_li_Ressources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Ressource_refid` int(11) DEFAULT NULL,
  `ZimbraLogin` varchar(50) DEFAULT NULL,
  `ZimbraId` varchar(36) DEFAULT NULL,
  `calFolderZimId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `Ressource_refid` (`Ressource_refid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;",
        "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_Zimbra_li_User` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `User_refid` int(11) DEFAULT NULL,
  `ZimbraLogin` varchar(50) DEFAULT NULL,
  `ZimbraPass` varchar(150) DEFAULT NULL,
  `ZimbraId` varchar(36) DEFAULT NULL,
  `calFolderZimId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=87 ;",
        "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_event_refid` int(11) DEFAULT NULL,
  `event_uid` varchar(50) DEFAULT NULL,
  `event_folder` int(11) DEFAULT NULL,
  `event_table_link` varchar(50) DEFAULT NULL,
  `event_table_id` int(11) DEFAULT NULL,
  `datec` datetime DEFAULT NULL,
  `dateu` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zimbra_event_id` (`event_uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1415 ;",
        "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_folder` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `folder_type_refid` int(11) DEFAULT NULL,
  `folder_uid` int(11) DEFAULT NULL,
  `folder_name` varchar(50) DEFAULT NULL,
  `folder_parent` int(11) DEFAULT NULL,
  `datec` datetime DEFAULT NULL,
  `dateu` datetime DEFAULT NULL,
  `skeleton_part` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zimbra_id` (`folder_uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9908 ;",
        "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `val` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;",
       /* "ALTER TABLE `".MAIN_DB_PREFIX."Synopsis_Zimbra_li_Ressources`
  ADD CONSTRAINT `".MAIN_DB_PREFIX."Synopsis_Zimbra_li_ressources_ibfk_1` FOREIGN KEY (`Ressource_refid`) REFERENCES `".MAIN_DB_PREFIX."Synopsis_global_ressources` (`id`) ON DELETE CASCADE;",*/
        "INSERT IGNORE INTO `".MAIN_DB_PREFIX."Synopsis_Zimbra_trigger_type` (`id`, `val`) VALUES
(1, 'appointment'),
(2, 'contact'),
(3, 'document');");
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
