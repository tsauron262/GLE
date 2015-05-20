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
include_once(DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php");

/**     \class      modProspectBabel
  \brief      Classe de description et activation du module de Prospection Babel
 */
class modSynopsisChrono extends DolibarrModules {

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function modSynopsisChrono($DB) {
        $this->db = $DB;
        $this->numero = 8000;

        $this->family = "Synopsis";
        $this->name = "Chrono";
        $this->description = utf8_decode("R&eacute;f&eacute;renciel Chrono");
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISCHRONO';
        $this->special = 0;
        $this->picto = 'chrono@synopsischrono';

        // Dir
        $this->dirs = array();

        // Config pages
        //$this->config_page_url = "";
        $this->config_page_url = "synopsischrono.php@synopsischrono";

        // Dependences
        $this->depends = array("modSynopsisProcess");
        $this->requiredby = array("modSynopsisHotline");

        // Constantes
        $this->const = array();

//        // Boites
//        $this->boxes = array();
        // Boites
//    $this->boxes = array();
//    $r=0;
//    $this->boxes[$r][1] = "box_deplacement.php";
//    $r++;
        // Permissions
        $this->rights = array();
        $this->rights_class = 'synopsischrono';

        $r = 0;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s au module chrono';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'read'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'G&eacute;n&eacute;rer un num&eacute;ro chrono';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Generer'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Modifier un chrono';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Modifier'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Valider un chrono';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Valider'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Modifier un chrono apr&egrave;s validation';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'ModifierApresValide'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero . $r; // this->numero ."". 1
        $this->rights[$r][1] = 'Supprimer un chrono brouillon';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Supprimer'; // Droit
        $r ++;

        /*
          $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
          $this->rights[$r][1] = 'Afficher le module 2';
          $this->rights[$r][2] = 'r'; //useless
          $this->rights[$r][3] = 1; // Default
          $this->rights[$r][4] = 'Global'; // Famille
          $this->rights[$r][5] = 'read'; // Droit
          $r ++;
          $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
          $this->rights[$r][1] = 'Afficher le module3';
          $this->rights[$r][2] = 'r'; //useless
          $this->rights[$r][3] = 1; // Default
          $this->rights[$r][4] = 'Global'; // Famille
          $this->rights[$r][5] = 'run'; // Droit
          $r ++; */

//
//        $this->menu[$r]=array('fk_menu'=>0,
//                              'type'=>'top',
//                              'titre'=>'Prospection',
//                              'mainmenu'=>'prospection',
//                              'leftmenu'=>'0',
//                              'url'=>'/BabelProspect/nouvelleProspection.php',
//                              'langs'=>'other',
//                              'position'=>100,
//                              'perms'=>'',
//                              'target'=>'',
//                              'user'=>0);
//        $r++;
//        $this->menu[$r]=array('fk_menu'=>'r=0',
//                            'type'=>'left',
//                            'titre'=>'Campagne de prospection',
//                            'mainmenu'=>'prospection',
//                            'url'=>'/BabelProspect/nouvelleProspection.php',
//                            'langs' => 'synopsisGene@synopsistools',
//                            'position'=>100,
//                            'perms'=>'$user->rights->prospectbabe->Prospection->Affiche',
//                            'target'=>'',
//                            'user'=>0);
//        $r++;


        $this->menus = array();            // List of menus to add
        $r = 0;
//        $this->menu[$r]=array(
//                            'type'=>'top',
//                            'titre'=>'Chrono',
//                            'mainmenu'=>'Process',
//                            'leftmenu'=>'0',        // To say if we can overwrite leftmenu
//                            'url'=>'/synopsischrono/index.php',
//                            'langs' => 'synopsisGene@synopsistools',
//                            'position'=>130,
//                            'perms'=>'$user->rights->synopsischrono->read',
//                            'target'=>'',
//                            'user'=>0);
        $s = $r;
        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=Process',
            'type' => 'left',
            'titre' => 'Chrono',
            'mainmenu' => 'Process',
            'leftmenu' => 'Chrono', // To say if we can overwrite leftmenu
            'url' => '/synopsischrono/index.php?leftmenu=chrono',
            'langs' => 'synopsisGene@synopsistools',
            'position' => 1,
            'perms' => '$user->rights->synopsischrono->read',
            'target' => '',
            'user' => 0);
        $s = $r;
        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=Process,fk_leftmenu=Chrono',
            'type' => 'left',
            'titre' => 'Liste',
            'mainmenu' => 'Process',
            'leftmenu' => '1', // To say if we can overwrite leftmenu
            'url' => '/synopsischrono/liste.php?leftmenu=chrono',
            'langs' => 'synopsisGene@synopsistools',
            'position' => 1,
            'perms' => '$user->rights->synopsischrono->read',
            'target' => '',
            'user' => 0);
        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=Process,fk_leftmenu=Chrono',
            'type' => 'left',
            'titre' => 'Liste d&eacute;tails',
            'mainmenu' => 'Process',
            'leftmenu' => '1', // To say if we can overwrite leftmenu
            'url' => '/synopsischrono/listDetail.php?leftmenu=chrono',
            'langs' => 'synopsisGene@synopsistools',
            'position' => 1,
            'perms' => '$user->rights->synopsischrono->read',
            'target' => '',
            'user' => 0);

        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=Process,fk_leftmenu=Chrono',
            'type' => 'left',
            'titre' => 'Nouveau',
            'mainmenu' => 'Process',
            'leftmenu' => '1', // To say if we can overwrite leftmenu
            'url' => '/synopsischrono/nouveau.php?leftmenu=chrono',
            'langs' => 'synopsisGene@synopsistools',
            'position' => 2,
            'perms' => '$user->rights->synopsischrono->Generer',
            'target' => '',
            'user' => 0);
        $r++;


        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=SynopsisTools',
            'type' => 'left',
            'titre' => 'Chrono',
            'mainmenu' => 'SynopsisTools',
            'leftmenu' => 'SynopsisChrono',
            'url' => '/synopsischrono/liste.php',
            'langs' => 'synopsisGene@synopsistools',
            'perms' => '$user->rights->synopsischrono->read',
            'position' => 15,
            'target' => '',
            'user' => 0);
        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=SynopsisTools,fk_leftmenu=SynopsisChrono',
            'type' => 'left',
            'titre' => 'Config',
            'url' => '/synopsischrono/admin/synopsischrono.php',
            'langs' => 'synopsisGene@synopsistools',
            'perms' => '$user->rights->synopsischrono->Generer',
            'position' => 151,
            'target' => '',
            'user' => 0);
        $r++;

        $this->module_parts = array(
            'hooks' => array('printTopRightMenu', 'toprightmenu', 'printSearchForm')  // Set here all hooks context you want to support
        );
    }

    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees e creer pour ce module.
     */
    function init() {
        $this->remove();
        $sql = array("UPDATE " . MAIN_DB_PREFIX . "menu set Titre = 'Chrono/Process', url = '/synopsischrono/index.php' WHERE Titre = 'Process' AND type ='top'",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsischrono` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_create` datetime DEFAULT NULL,
  `tms` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ref` varchar(500) DEFAULT NULL,
  `model_refid` int(11) DEFAULT NULL,
  `description` longtext,
  `fk_societe` int(11) DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_socpeople` int(11) DEFAULT NULL,
  `fk_user_modif` int(11) DEFAULT NULL,
  `fk_statut` int(11) DEFAULT '0',
  `validation_number` int(11) DEFAULT NULL,
  `revision` int(11) DEFAULT NULL,
  `orig_ref` varchar(500) DEFAULT NULL,
  `propalid` int(11) DEFAULT NULL,
  `projetid` int(11) DEFAULT NULL,
  `entity` int(11) DEFAULT 1,
  `note` varchar(10000),
  PRIMARY KEY (`id`),
  KEY `model_refid` (`model_refid`),
  KEY `fk_user_author` (`fk_user_author`),
  KEY `fk_socpeople` (`fk_socpeople`),
  KEY `fk_societe` (`fk_societe`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsischrono_conf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(50) DEFAULT NULL,
  `description` text,
  `hasFile` tinyint(4) DEFAULT '0',
  `hasContact` tinyint(4) DEFAULT '0',
  `hasSociete` tinyint(4) DEFAULT '0',
  `hasRevision` tinyint(4) DEFAULT '0',
  `hasPropal` tinyint(4) DEFAULT '0',
  `hasProjet` tinyint(4) DEFAULT '0',
  `revision_model_refid` int(11) DEFAULT NULL,
  `modele` varchar(50) DEFAULT NULL,
  `date_create` date DEFAULT NULL,
  `tms` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(1) DEFAULT '1',
  `hasSuivie` tinyint(4) NOT NULL DEFAULT '1',
  `hasDescription` tinyint(4) NOT NULL DEFAULT '1',
  `hasStatut` tinyint(4) NOT NULL DEFAULT '1',
  `nomDescription` varchar(50) NOT NULL,
  `typeDescription` int(11) NOT NULL,
  PRIMARY KEY (`id`));",
//        "INSERT IGNORE INTO `".MAIN_DB_PREFIX."synopsischrono_conf` (`id`, `titre`, `description`, `hasFile`, `hasContact`, `hasSociete`, `hasRevision`, `hasPropal`, `hasProjet`, `revision_model_refid`, `modele`, `date_create`, `tms`, `active`, `hasSuivie`, `hasDescription`, `hasStatut`, `nomDescription`, `typeDescription`) VALUES
//(1, 'Document indiçable', 'Document indiçable', 1, 0, 0, 1, 0, 0, 2, '{yy}I{0000@1}-A', '2011-03-17', '2013-11-18 03:46:10', 0, 1, 1, 1, 'Description', 2),
//(2, 'Document départ', 'Courrier, mail, documents ...', 1, 0, 0, 0, 0, 0, NULL, '{yy}D{0000@1} ', '2011-03-17', '2013-11-18 03:46:06', 0, 1, 1, 1, 'Description', 2),
//(3, 'Procédures', 'Documents de procédures AQ', 1, 0, 0, 1, 0, 0, 2, 'PR{0000}-A', '2011-04-26', '2013-11-18 03:46:01', 0, 1, 1, 1, 'Description', 2),
//(4, 'Instructions', 'Documents d instructions AQ', 1, 0, 0, 1, 0, 0, 2, 'IN{0000}-A', '2011-04-26', '2013-11-18 03:45:56', 0, 1, 1, 1, 'Description', 2),
//(5, 'Trames', 'Trames de fichiers', 1, 0, 0, 1, 0, 0, 2, 'TR{0000}-A', '2011-04-27', '2013-11-18 03:45:49', 0, 1, 1, 1, 'Description', 2),
//(6, 'FNC', 'Fiches de non conformité', 1, 0, 0, 0, 0, 0, NULL, '{yy}FNC{0000@1}', '2011-04-27', '2013-11-18 03:45:42', 0, 1, 1, 1, 'Description', 2),
//(7, 'ACP', 'Actions correctives et préventives', 1, 0, 0, 0, 0, 0, NULL, '{yy}ACP{0000@1}', '2011-05-06', '2013-11-18 03:45:38', 0, 1, 1, 1, 'Description', 2),
//(8, 'Synthèses affaires', '', 1, 0, 0, 0, 0, 0, NULL, '{yy}SA{0000@1}', '2012-06-20', '2013-11-18 03:45:32', 0, 1, 1, 1, 'Description', 2),
//(9, 'Audit', 'Chrono des audits', 0, 0, 0, 0, 0, 0, NULL, '{yy}AU{0000@1}', '2012-08-16', '2013-11-18 03:45:27', 0, 1, 1, 1, 'Description', 2),
//(10, 'Fiches d''anomalie', 'Anomalies modèles et outils de simulation', 1, 0, 0, 0, 0, 0, NULL, '{yy}FA{0000@1}', '2012-10-04', '2013-11-18 03:45:22', 0, 1, 1, 1, 'Description', 2),
//(100, 'Appel', 'Fiche Hotline', 0, 1, 1, 0, 0, 0, NULL, 'Hot{yy}{mm}{000}', '2013-10-15', '2013-11-18 00:57:45', 1, 0, 1, 0, 'Objet', 1),
//(101, 'Produit', 'Info sur un produit sous contrat ou garentie', 0, 0, 1, 0, 0, 0, NULL, 'PROD{000000}', '2013-10-28', '2013-11-18 03:06:59', 1, 0, 1, 0, 'NomB', 1),
//(102, 'Licence', '', 0, 0, 0, 0, 0, 0, NULL, 'LIC-{0000}', '2013-11-03', '2013-11-18 01:04:56', 1, 0, 1, 0, 'Nom', 1),
//(103, 'Compte Utilisateur', '', 0, 0, 1, 0, 0, 0, NULL, 'USER{00000}', '2013-11-03', '2013-11-18 00:57:45', 1, 0, 1, 0, 'Description', 2),
//(104, 'Site', '', 0, 0, 1, 0, 0, 0, NULL, 'Site{0000}', '2013-11-18', '2013-11-18 01:03:21', 1, 0, 1, 0, 'Nom', 1);",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsischrono_form_fct_value` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fct_refid` int(11) DEFAULT NULL,
  `label` varchar(50) DEFAULT NULL,
  `chrono_conf_refid` int(11) DEFAULT NULL,
  `valeur` longtext,
  PRIMARY KEY (`id`),
  KEY `model_refid` (`chrono_conf_refid`),
  KEY `fct_refid` (`fct_refid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsischrono_group_rights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chrono_refid` int(11) DEFAULT NULL,
  `group_refid` int(11) DEFAULT NULL,
  `right_refid` int(11) DEFAULT NULL,
  `valeur` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsischrono_key` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `description` longtext,
  `model_refid` int(11) DEFAULT NULL,
  `type_valeur` int(11) DEFAULT NULL,
  `type_subvaleur` int(11) DEFAULT NULL,
  `extraCss` longtext,
  `inDetList` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `model_refid` (`model_refid`),
  KEY `type_valeur` (`type_valeur`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=57 ;",
//        "INSERT IGNORE INTO `".MAIN_DB_PREFIX."synopsischrono_key` (`id`, `nom`, `description`, `model_refid`, `type_valeur`, `type_subvaleur`, `extraCss`, `inDetList`) VALUES
//(1, 'Classification', 'Classification', 1, 1, NULL, '', 1),
//(2, 'Titre', 'Titre', 1, 9, NULL, '', 1),
//(41, 'Date', '', 8, 2, NULL, 'required', 1),
//(4, 'Rédacteur', 'Rédacteur', 1, 6, 9, '', 1),
//(10, 'Date', 'Date d''envoi', 2, 2, NULL, 'required', 1),
//(6, 'Courrier', 'Type de courrier', 2, 8, 1, '', 1),
//(7, 'Envoi renseignements', 'Envoi renseignements', 2, 9, NULL, '', 1),
//(8, 'Destinataire', 'Destinataire', 2, 1, NULL, '', 1),
//(9, 'Rédacteur', 'Rédacteur', 2, 6, 9, '', 1),
//(13, 'Identifiant', 'Identifiant', 4, 1, NULL, 'required', 1),
//(14, 'Date', 'Date d''introduction de l''instruction', 4, 2, NULL, 'required', 1),
//(15, 'Identifiant', 'Identifiant de la procédure', 3, 1, NULL, 'required', 1),
//(16, 'Date', 'Date d''introduction de la procédure', 3, 2, NULL, 'required', 1),
//(17, 'Identifiant', 'Identifiant de la trame', 5, 1, NULL, 'required', 1),
//(18, 'Date', 'Date d''introduction de la trame', 5, 2, NULL, 'required', 1),
//(19, 'Titre', ' Titre de la FNC', 6, 1, NULL, 'required', 1),
//(20, 'Origine', 'Origine de la FNC', 6, 8, 3, '', 1),
//(24, 'Auteur', 'Auteur', 6, 6, 9, 'required', 1),
//(25, 'Date d''émission', 'Date d''émission', 6, 2, NULL, 'required', 1),
//(26, 'Responsable traitement', 'Responsable traitement', 6, 6, 9, 'required', 1),
//(27, 'Objectif de date de clôture', 'Date de clôture visée', 6, 2, NULL, '', 1),
//(31, 'Date de clôture', 'Date de clôture', 6, 2, NULL, '', 1),
//(29, 'Estimation coût FNC', 'Estimation coût FNC', 6, 9, NULL, '', 1),
//(30, 'Efficacité des actions', 'Efficacité des actions', 6, 9, NULL, '', 1),
//(32, 'Titre', 'Titre', 7, 1, NULL, 'required', 1),
//(33, 'Auteur', 'Auteur', 7, 6, 9, 'required', 1),
//(34, 'Origine', 'Origine de l''ACP', 7, 8, 4, 'required', 1),
//(35, 'Responsable traitement', 'Responsable traitement', 7, 6, 9, 'required', 1),
//(37, 'Date d''émission', 'Date d''émission', 7, 2, NULL, 'required', 1),
//(38, 'Objectif de date de clôture', 'Objectif de date de clôture', 7, 2, NULL, 'required', 1),
//(39, 'Date de clôture', 'Date de clôture', 7, 2, NULL, '', 1),
//(46, 'Rédacteur', 'rédacteur de la synthèse d''affaire', 8, 6, 9, 'required', 1),
//(45, 'Projet', '', 8, 6, 15, 'required', 1),
//(48, 'Titre', 'Titre de l''audit', 9, 1, NULL, 'required', 1),
//(49, 'Référence', 'Référence du document associé à l''audit (doc départ ou doc indiçable)', 9, 1, NULL, '', 1),
//(50, 'Auditeur', 'nom de l''auditeur', 9, 6, 9, 'required', 1),
//(51, 'Date d''émission', '', 9, 2, NULL, '', 1),
//(52, 'modèle', '', 10, 1, NULL, 'required', 1),
//(53, 'Auteur', '', 10, 6, 9, 'required', 1),
//(54, 'Date de détection', '', 10, 2, NULL, 'required', 1),
//(55, 'Date de solution', '', 10, 2, NULL, '', 1),
//(56, 'Etat', '', 10, 8, 5, 'required', 1),
//(1000, 'Note', '', 100, 9, NULL, 'required', 1),
//(1001, 'Date / Heure', '', 100, 3, NULL, 'required', 1),
//(1004, 'Contrat', '', 100, 6, 1000, '', 1),
//(1010, 'Produit', '', 101, 6, 1002, '', 1),
//(1011, 'N° Serie', '', 101, 1, NULL, '', 1),
//(1013, 'Note', '', 101, 9, NULL, '', 1),
//(1014, 'Date Achat', '', 101, 2, NULL, '', 1),
//(1015, 'Date fin SAV', '', 101, 2, NULL, '', 1),
//(1016, 'Ligne contrat', '', 101, 10, 1, 'type:productCli', 1),
//(1019, 'Appels', '', 101, 10, 2, 'type:productCli', 1),
//(1020, 'Produit Client', '', 100, 10, 3, 'type:appel', 1),
//(1021, 'Login', '', 103, 1, NULL, '', 1),
//(1022, 'Password', '', 103, 1, NULL, '', 1),
//(1023, 'Produit', '', 103, 10, 3, 'type:compteUser', 1),
//(1024, 'Poste', '', 102, 10, 3, 'type:licence', 1),
//(1025, 'Licence', '', 101, 10, 4, 'type:productCli', 1),
//(1026, 'Compte Utilisateur', '', 101, 10, 5, 'type:productCli', 1),
//(1027, 'Site', '', 101, 10, 6, 'type:productCli', 1),
//(1028, 'Technicien', '', 104, 6, 9, '', 1),
//(1029, 'Durée déplacement (en h)', '', 104, 1, NULL, '', 1),
//(1030, 'Produits', '', 104, 10, 3, 'type:site', 1);",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsischrono_key_type_valeur` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `hasSubValeur` tinyint(4) DEFAULT '0',
  `subValeur_table` varchar(150) DEFAULT NULL,
  `subValeur_idx` varchar(150) DEFAULT NULL,
  `subValeur_text` varchar(150) DEFAULT NULL,
  `phpClass` varchar(150) DEFAULT NULL,
  `htmlTag` varchar(150) DEFAULT NULL,
  `htmlEndTag` varchar(150) DEFAULT NULL,
  `endNeeded` tinyint(1) DEFAULT NULL,
  `cssClass` varchar(150) DEFAULT NULL,
  `cssScript` varchar(150) DEFAULT NULL,
  `jsCode` varchar(150) DEFAULT NULL,
  `valueIsChecked` tinyint(1) DEFAULT NULL,
  `valueIsSelected` tinyint(1) DEFAULT NULL,
  `valueInTag` tinyint(1) DEFAULT NULL,
  `valueInValueField` tinyint(1) DEFAULT NULL,
  `sourceIsOption` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;",
            "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "synopsischrono_key_type_valeur` (`id`, `nom`, `hasSubValeur`, `subValeur_table`, `subValeur_idx`, `subValeur_text`, `phpClass`, `htmlTag`, `htmlEndTag`, `endNeeded`, `cssClass`, `cssScript`, `jsCode`, `valueIsChecked`, `valueIsSelected`, `valueInTag`, `valueInValueField`, `sourceIsOption`) VALUES
(1, 'Texte', 0, NULL, NULL, NULL, NULL, '<input>', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL),
(2, 'Date', 0, NULL, NULL, NULL, NULL, '<input>', NULL, NULL, 'datepicker', NULL, NULL, NULL, NULL, NULL, 1, NULL),
(3, 'Date/Heure', 0, NULL, NULL, NULL, NULL, '<input>', NULL, NULL, 'datetimepicker', NULL, NULL, NULL, NULL, NULL, 1, NULL),
(4, 'Case à cocher', 0, NULL, NULL, NULL, NULL, '<input type=''checkbox''>', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL),
(6, 'Requête', 1, '" . MAIN_DB_PREFIX . "Synopsis_Process_form_requete', 'id', 'label', 'requete', '<SELECT>', '</SELECT>', 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, 1),
(7, 'Constante', 1, '" . MAIN_DB_PREFIX . "Synopsis_Process_form_global', 'id', 'label', 'globalvar', '<input type=''hidden''>', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'Liste', 1, '" . MAIN_DB_PREFIX . "Synopsis_Process_form_list', 'id', 'label', 'listform', '<SELECT>', '</SELECT>', 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, 1),
(9, 'Description', 0, NULL, NULL, NULL, NULL, '<textarea>', '</textarea>', 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL),
(10, 'Lien', 1, 'llx_Synopsis_Process_lien', 'rowid', 'label', 'lien', '<SELECT>', '</SELECT>', 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, 1);",
            /* "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."synopsischrono_key_value_view` (
              `id` int(11)
              ,`nom` varchar(50)
              ,`description` longtext
              ,`model_refid` int(11)
              ,`type_valeur` int(11)
              ,`type_subvaleur` int(11)
              ,`extraCss` longtext
              ,`inDetList` tinyint(1)
              ,`key_id` int(11)
              ,`chrono_id` int(11)
              ,`chrono_value` longtext
              ,`date_create` datetime
              ,`ref` varchar(500)
              ,`desc_chrono` longtext
              ,`fk_soc` int(11)
              ,`fk_user_create` int(11)
              ,`fk_socpeople` int(11)
              ,`fk_user_modif` int(11)
              ,`fk_statut` int(11)
              ,`validation_number` int(11)
              ,`revision` int(11)
              ,`chrono_conf_id` int(11)
              ,`orig_ref` varchar(500)
              );", */
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsischrono_Multivalidation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_refid` int(11) DEFAULT NULL,
  `tms` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `chrono_refid` int(11) DEFAULT NULL,
  `validation` tinyint(4) DEFAULT NULL,
  `right_refid` int(11) DEFAULT NULL,
  `validation_number` int(11) DEFAULT NULL,
  `note` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `multivalidation_chrono_uniq_key` (`right_refid`,`validation_number`,`chrono_refid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsischrono_rights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chrono_refid` int(11) DEFAULT NULL,
  `user_refid` int(11) DEFAULT NULL,
  `right_refid` int(11) DEFAULT NULL,
  `valeur` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsischrono_rights_def` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(50) DEFAULT NULL,
  `description` longtext,
  `dflt` int(11) DEFAULT '0',
  `code` varchar(50) DEFAULT NULL,
  `rang` int(11) DEFAULT NULL,
  `active` int(11) DEFAULT '0',
  `isValidationRight` tinyint(4) DEFAULT NULL,
  `isValidationForAll` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;",
            "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "synopsischrono_rights_def` (`id`, `label`, `description`, `dflt`, `code`, `rang`, `active`, `isValidationRight`, `isValidationForAll`) VALUES
(1, 'Voir', 'Voir le chrono', 1, 'voir', 1, 1, NULL, 0),
(2, 'Modifer', 'Modifier le chrono', 0, 'modifier', 2, 1, NULL, 0),
(3, 'Valider', 'Valider le chrono', 0, 'valider', 3, 1, 1, 1),
(4, 'Valider(Tech)', 'Valider le chrono (Technicien)', 0, 'validerTech', 4, 1, 1, 0),
(5, 'Valider(Dir)', 'Valider le chrono (Direction)', 0, 'validerDir', 5, 1, 1, 0),
(6, 'Valider(Com)', 'Valider le chrono (Commercial)', 0, 'validerCom', 6, 1, 1, 0),
(7, 'Supprimer', 'Supprimer le chrono', 0, 'supprimer', 7, 1, NULL, 0);"/*,
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "synopsischrono_value` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chrono_refid` int(11) DEFAULT NULL,
  `value` longtext,
  `key_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `key_id` (`key_id`),
  KEY `chrono_refid` (`chrono_refid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;",
            "DROP TABLE IF EXISTS `" . MAIN_DB_PREFIX . "synopsischrono_key_value_view`;",
            "DROP VIEW IF EXISTS `" . MAIN_DB_PREFIX . "synopsischrono_key_value_view`;",
            "CREATE VIEW `" . MAIN_DB_PREFIX . "synopsischrono_key_value_view` AS select `v`.`id` AS `id`,`k`.`nom` AS `nom`,`k`.`description` AS `description`,`k`.`model_refid` AS `model_refid`,`k`.`type_valeur` AS `type_valeur`,`k`.`type_subvaleur` AS `type_subvaleur`,`k`.`extraCss` AS `extraCss`,`k`.`inDetList` AS `inDetList`,`k`.`id` AS `key_id`,`c`.`id` AS `chrono_id`,`v`.`value` AS `chrono_value`,`c`.`date_create` AS `date_create`,`c`.`ref` AS `ref`,`c`.`description` AS `desc_chrono`,`c`.`fk_societe` AS `fk_soc`,`c`.`fk_user_author` AS `fk_user_create`,`c`.`fk_socpeople` AS `fk_socpeople`,`c`.`fk_user_modif` AS `fk_user_modif`,`c`.`fk_statut` AS `fk_statut`,`c`.`validation_number` AS `validation_number`,`c`.`revision` AS `revision`,`c`.`model_refid` AS `chrono_conf_id`,`c`.`orig_ref` AS `orig_ref` from (`" . MAIN_DB_PREFIX . "synopsischrono` `c` left join (`" . MAIN_DB_PREFIX . "synopsischrono_key` `k` left join `" . MAIN_DB_PREFIX . "synopsischrono_value` `v` on((`v`.`key_id` = `k`.`id`))) on((`c`.`id` = `v`.`chrono_refid`)));
"*/);
//    $this->dirs[0] = $conf->chrono->dir_output;

        return $this->_init($sql);
    }

    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove() {
//    $requete = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE module = 'Chrono' ";
//    $sql = $this->db->query($requete);
//    if (!$sql) print $this->db->error;
//    $sql = array("DROP TABLE ".MAIN_DB_PREFIX."synopsischrono_conf, ".MAIN_DB_PREFIX."synopsischrono_key, ".MAIN_DB_PREFIX."synopsischrono_key_type_valeur, ".MAIN_DB_PREFIX."synopsischrono_rights_def");
        $sql = array();
        return $this->_remove($sql);
    }

}

?>