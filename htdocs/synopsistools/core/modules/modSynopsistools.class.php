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

class modSynopsistools extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
    function modSynopsistools($DB)
    {
        $this->db = $DB ;
        $this->numero = 8088;

        $this->family = "Synopsis";
        $this->name = "Synopsis Tools";
        $this->description = utf8_decode("Outil de gestion");
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISTOOLS';
        $this->special = 0;
        $this->picto='tools@synopsistools';

        // Dir
        $this->dirs = array();

        // Config pages
        //$this->config_page_url = "";

        // Dependences
        $this->depends = array();
        $this->requiredby = array("modSynopsisProcess");

        // Constantes
        $this->const = array();



        // Permissions
        $this->rights = array();
        $this->rights_class = 'SynopsisTools';

        $r = 0;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s au menu Tools';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Global'; // Famille
        $this->rights[$r][5] = 'read'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s a PhpMyAdmin';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Global'; // Famille
        $this->rights[$r][5] = 'phpMyAdmin'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s au import';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Global'; // Famille
        $this->rights[$r][5] = 'import'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Acc&egrave;s au fichier info de maj';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Global'; // Famille
        $this->rights[$r][5] = 'fileInfo'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Administrer les bug';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Global'; // Famille
        $this->rights[$r][5] = 'adminBug'; // Droit
        $r ++;

        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Administrer les notifications mail';
        $this->rights[$r][2] = 'r'; //useless
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Global'; // Famille
        $this->rights[$r][5] = 'adminNotifMail'; // Droit
        $r ++;
        
        
        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Voir toute les commandes';
        $this->rights[$r][3] = 1; // Default
        $this->rights[$r][4] = 'Commande'; // Famille
        $this->rights[$r][5] = 'viewAll'; // Droit
        $r ++;
        
        $this->rights[$r][0] = $this->numero.$r;// this->numero ."". 1
        $this->rights[$r][1] = 'Voir en proirité mes commandes';
        $this->rights[$r][3] = 0; // Default
        $this->rights[$r][4] = 'Commande'; // Famille
        $this->rights[$r][5] = 'defaultViewMy'; // Droit
        $r ++;



        $this->menus = array();            // List of menus to add
        $r=0;
        $this->menu[$r]=array(
                            'type'=>'top',
                            'titre'=>'Tools',
                            'mainmenu'=>'SynopsisTools',
                            'leftmenu'=>'0',        // To say if we can overwrite leftmenu
                            'url'=>'/synopsistools/index.php',
                            'langs'=>'',
                            'position'=>999,
                            'perms'=>'$user->rights->SynopsisTools->Global->read',
                            'target'=>'',
                            'user'=>0);
        $s = $r;
        $r++;
        $this->menu[$r]=array('fk_menu'=>"fk_mainmenu=SynopsisTools",
                            'type'=>'left',
                            'titre'=>'Php My Admin',
                            'mainmenu'=>'SynopsisTools',
                            'leftmenu'=>'myAdmin',        // To say if we can overwrite leftmenu
                            'url'=>'/synopsistools/myAdmin.php',
                            'langs'=>'',
                            'position'=>2,
                            'perms'=>'$user->rights->SynopsisTools->Global->phpMyAdmin',
                            'target'=>'',
                            'user'=>0);
        $s = $r;
        $r++;
        $this->menu[$r]=array('fk_menu'=>"fk_mainmenu=SynopsisTools,fk_leftmenu=myAdmin",
                            'type'=>'left',
                            'titre'=>'Php My Admin (new Onglet)',
                            'mainmenu'=>'SynopsisTools',
                            'url'=>'/synopsistools/Synopsis_myAdmin/index.php',
                            'langs'=>'blank',
                            'position'=>201,
                            'perms'=>'$user->rights->SynopsisTools->Global->phpMyAdmin',
                            'target'=>'',
                            'user'=>0);
        
        
        
        
        $r++;
        $this->menu[$r]=array('fk_menu'=>"fk_mainmenu=SynopsisTools",
                            'type'=>'left',
                            'titre'=>'Tools',
                            'mainmenu'=>'SynopsisTools',
                            'leftmenu'=>'tools',        // To say if we can overwrite leftmenu
                            'url'=>'/synopsistools/maj.php',
                            'langs'=>'',
                            'position'=>3,
                            'perms'=>'$user->rights->SynopsisTools->Global->import',
                            'target'=>'',
                            'user'=>0);
        $r++;
        $this->menu[$r]=array('fk_menu' => 'fk_mainmenu=SynopsisTools,fk_leftmenu=tools',
                            'type'=>'left',
                            'titre'=>'Impor/Export/Verif',
                            'mainmenu'=>'SynopsisTools',
                            'url'=>'/synopsistools/maj.php',
                            'langs'=>'',
                            'position'=>301,
                            'perms'=>'$user->rights->SynopsisTools->Global->import',
                            'target'=>'',
                            'user'=>0);
        $r++;
        $this->menu[$r]=array('fk_menu' => 'fk_mainmenu=SynopsisTools,fk_leftmenu=tools',
                            'type'=>'left',
                            'titre'=>'Config mail',
                            'mainmenu'=>'SynopsisTools',
                            'url'=>'/synopsistools/notificationUser/list.php',
                            'langs'=>'',
                            'position'=>302,
                            'perms'=>'$user->rights->SynopsisTools->Global->adminNotifMail',
                            'target'=>'',
                            'user'=>0);
        
        $r++;
        $this->menu[$r]=array('fk_menu' => 'fk_mainmenu=SynopsisTools,fk_leftmenu=tools',
                            'type'=>'left',
                            'titre'=>'Fichier info maj',
                            'mainmenu'=>'SynopsisTools',
                            'url'=>'/synopsistools/listFileInfo.php',
                            'langs'=>'',
                            'position'=>303,
                            'perms'=>'$user->rights->SynopsisTools->Global->fileInfo',
                            'target'=>'',
                            'user'=>0);
        
        $r++;
        $this->menu[$r]=array('fk_menu' => 'fk_mainmenu=SynopsisTools,fk_leftmenu=tools',
                            'type'=>'left',
                            'titre'=>'Fichier log',
                            'mainmenu'=>'SynopsisTools',
                            'url'=>'/synopsistools/fichierLog.php',
                            'langs'=>'',
                            'position'=>304,
                            'perms'=>'$user->rights->SynopsisTools->Global->fileInfo',
                            'target'=>'',
                            'user'=>0);
        $s = $r;
        $r++;
        
        
        
        $this->menu[$r]=array(
                            'type'=>'top',
                            'titre'=>'Signaler un bug',
                            'mainmenu'=>'SynopsisToolsBug',
                            'leftmenu'=>'0',        // To say if we can overwrite leftmenu
                            'url'=>'/synopsistools/repportBug.php',
                            'langs'=>'',
                            'position'=>1000,
                            'perms'=>'',
                            'target'=>'',
                            'user'=>0);
        $s = $r;
        $r++;
        
        
        $this->tabs = array('thirdparty:-document',
            'thirdparty:+allDoc:allDoc:synopsisGene@synopsistools:/synopsistools/allDocumentSoc.php?id=__ID__',
            'agenda:+team:Vue équipe:@synopsistools:/synopsistools/agenda/vue.php');
        
        

        $this->module_parts = array(
            'hooks' => array('printTopRightMenu', 'toprightmenu', 'printSearchForm')  // Set here all hooks context you want to support
        );
    }
   /**
    *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
    *               Definit egalement les repertoires de donnees e creer pour ce module.
    */
  function init()
  {
    $sql = array("CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."synopsistools_fileInfo` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(50) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rowid`))",
            "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."synopsistools_bug` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user` int(11) NOT NULL,
  `text` varchar(1000) NOT NULL,
  `resolu` tinyint(1) NOT NULL,
  PRIMARY KEY (`rowid`))",
        "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."Synopsis_commande_consigne` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_group` int(11) DEFAULT NULL,
  `fk_comm` int(11) DEFAULT NULL,
  `note` varchar(500) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`rowid`))",
        "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."synopsistools_notificationUser` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_trigger` int(11) NOT NULL,
  `fk_type_contact` int(11) NOT NULL,
  `mailTo` varchar(200) NOT NULL,
  `sujet` varchar(300) NOT NULL,
  `message` text NOT NULL,
  `active` tinyint(4) NOT NULL,
  PRIMARY KEY (`rowid`)
)",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_trigger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=209 ;",
            "INSERT IGNORE INTO `" . MAIN_DB_PREFIX . "Synopsis_trigger` (`id`, `code`) VALUES
(1, 'ACTION_CREATE'),
(2, 'BILL_CANCEL'),
(3, 'BILL_CANCELED'),
(4, 'BILL_CREATE'),
(5, 'BILL_DELETE'),
(6, 'BILL_MODIFY'),
(7, 'BILL_PAYED'),
(8, 'BILL_SENTBYMAIL'),
(9, 'BILL_SUPPLIER_VALIDATE'),
(10, 'BILL_VALIDATE'),
(11, 'COMPANY_CREATE'),
(12, 'COMPANY_DELETE'),
(13, 'COMPANY_MODIFY'),
(14, 'CONTACT_CREATE'),
(15, 'CONTACT_DELETE'),
(16, 'CONTACT_MODIFY'),
(17, 'CONTRACT_ACTIVATE'),
(18, 'CONTRACT_CANCEL'),
(19, 'CONTRACT_CLOSE'),
(20, 'CONTRACT_CREATE'),
(21, 'CONTRACT_DELETE'),
(22, 'CONTRACT_MODIFY'),
(23, 'CONTRACT_VALIDATE'),
(24, 'FICHEINTER_VALIDATE'),
(25, 'GROUP_CREATE'),
(26, 'GROUP_DELETE'),
(27, 'GROUP_MODIFY'),
(28, 'LINEBILL_INSERT'),
(29, 'MEMBER_CREATE'),
(30, 'MEMBER_DELETE'),
(31, 'MEMBER_MODIFY'),
(32, 'MEMBER_NEW_PASSWORD'),
(33, 'MEMBER_RESILIATE'),
(34, 'MEMBER_SUBSCRIPTION'),
(35, 'MEMBER_VALIDATE'),
(36, 'ORDER_CREATE'),
(37, 'ORDER_DELETE'),
(38, 'ORDER_SENTBYMAIL'),
(39, 'ORDER_SUPPLIER_CREATE'),
(40, 'ORDER_SUPPLIER_VALIDATE'),
(41, 'ORDER_VALIDATE'),
(42, 'PAYMENT_CUSTOMER_CREATE'),
(43, 'PAYMENT_SUPPLIER_CREATE'),
(44, 'PRODUCT_CREATE'),
(45, 'PRODUCT_DELETE'),
(46, 'PRODUCT_MODIFY'),
(47, 'PROPAL_CLOSE_REFUSED'),
(48, 'PROPAL_CLOSE_SIGNED'),
(49, 'PROPAL_CREATE'),
(50, 'PROPAL_MODIFY'),
(51, 'PROPAL_SENTBYMAIL'),
(52, 'PROPAL_VALIDATE'),
(53, 'USER_CREATE'),
(54, 'USER_DELETE'),
(55, 'USER_DISABLE'),
(56, 'USER_ENABLEDISABLE'),
(57, 'USER_LOGIN'),
(58, 'USER_LOGIN_FAILED'),
(59, 'USER_MODIFY'),
(60, 'USER_NEW_PASSWORD'),
(61, 'ACTION_DELETE'),
(62, 'ACTION_UPDATE'),
(63, 'AFFAIRE_NEW'),
(64, 'BILL_UNPAYED'),
(65, 'CONTRAT_LIGNE_MODIFY'),
(66, 'CONTRACT_SERVICE_ACTIVATE'),
(67, 'CONTRACT_SERVICE_CLOSE'),
(68, 'PROPAL_DELETE'),
(69, 'EXPEDITION_CREATE'),
(70, 'EXPEDITION_DELETE'),
(71, 'EXPEDITION_VALIDATE'),
(72, 'LIVRAISON_CREATE'),
(73, 'EXPEDITION_VALID_FROM_DELIVERY'),
(74, 'EXPEDITION_CREATE_FROM_DELIVERY'),
(75, 'LIVRAISON_VALID'),
(76, 'LIVRAISON_DELETE'),
(77, 'PROJECT_CREATE'),
(78, 'PROJECT_UPDATE'),
(79, 'PROJECT_DELETE'),
(80, 'PROJECT_CREATE_TASK_ACTORS'),
(81, 'PROJECT_CREATE_TASK'),
(82, 'PROJECT_CREATE_TASK_TIME'),
(83, 'PROJECT_CREATE_TASK_TIME_EFFECTIVE'),
(84, 'PROJECT_UPDATE_TASK'),
(85, 'PROJECT_DEL_TASK'),
(86, 'FICHEINTER_UPDATE'),
(87, 'FICHEINTER_CREATE'),
(88, 'FICHEINTER_DELETE'),
(89, 'SYNOPSISDEMANDEINTERV_CREATE'),
(90, 'SYNOPSISDEMANDEINTERV_UPDATE'),
(91, 'SYNOPSISDEMANDEINTERV_VALIDATE'),
(92, 'SYNOPSISDEMANDEINTERV_PRISENCHARGE'),
(93, 'SYNOPSISDEMANDEINTERV_CLOTURE'),
(94, 'SYNOPSISDEMANDEINTERV_DELETE'),
(95, 'SYNOPSISDEMANDEINTERV_SETDELIVERY'),
(96, 'CAMPAGNEPROSPECT_CREATE'),
(97, 'CAMPAGNEPROSPECT_UPDATE'),
(98, 'CAMPAGNEPROSPECT_VALIDATE'),
(99, 'CAMPAGNEPROSPECT_LANCER'),
(100, 'CAMPAGNEPROSPECT_CLOTURE'),
(101, 'CAMPAGNEPROSPECT_NEWACTION'),
(102, 'CAMPAGNEPROSPECT_CLOSE'),
(103, 'CAMPAGNEPROSPECT_NEWPRISECHARGE'),
(104, 'USER_ENABLE'),
(105, 'USER_CHANGERIGHT'),
(107, 'CHRONO_ASK_VALIDATE'),
(108, 'CHRONO_UNVALIDATE'),
(109, 'CHRONO_VALIDATE'),
(110, 'CONTRACTLINE_DELETE'),
(111, 'CONTRAT_LIGNE_MODIFY_TOT'),
(112, 'CONTRAT_WARNBYMAIL'),
(113, 'DELIVERY_SUPPLIER_CANCEL'),
(114, 'DELIVERY_SUPPLIER_DELETE'),
(115, 'DELIVERY_SUPPLIER_EVENT'),
(116, 'DELIVERY_SUPPLIER_ORDER_APPROVE'),
(117, 'DELIVERY_SUPPLIER_ORDER_CONFIRM'),
(118, 'DELIVERY_SUPPLIER_ORDER_REFUSE'),
(119, 'SYNOPSISDEMANDEINTERV_ATTRIB'),
(120, 'SYNOPSISDEMANDEINTERV_DELETELINE'),
(121, 'SYNOPSISDEMANDEINTERV_INSERTLINE'),
(122, 'SYNOPSISDEMANDEINTERV_INVALIDATE'),
(123, 'SYNOPSISDEMANDEINTERV_SETDESCRIPTION'),
(124, 'SYNOPSISDEMANDEINTERV_UPDATELINE'),
(125, 'SYNOPSISDEMANDEINTERV_UPDATETOTAL'),
(126, 'ECM_DEL_ADHERENT'),
(127, 'ECM_DEL_FACTURE'),
(128, 'ECM_DEL_LOGO'),
(129, 'ECM_DEL_UL_CONTRAT'),
(130, 'ECM_GENACTIONCOMRAPPORT'),
(131, 'ECM_GENCOMMANDE'),
(132, 'ECM_GENCOMMANDE_FOURN'),
(133, 'ECM_GENSYNOPSISDEMANDEINTERV'),
(134, 'ECM_GENDONS'),
(135, 'ECM_GENEXPEDITION'),
(136, 'ECM_GENFACTURE'),
(137, 'ECM_GENFICHEINTER'),
(138, 'ECM_GENLIVRAISON'),
(139, 'ECM_GENPROPAL'),
(140, 'ECM_GENPROPALE'),
(141, 'ECM_GENREMISECHEQUE'),
(142, 'ECM_GEN_COMMFOURN'),
(143, 'ECM_UL_ACTION'),
(144, 'ECM_UL_ADHERENT'),
(145, 'ECM_UL_AFFAIRE'),
(146, 'ECM_UL_BONPRELEV'),
(147, 'ECM_UL_CHRONO'),
(148, 'ECM_UL_COMMANDE'),
(149, 'ECM_UL_COMMFOURN'),
(150, 'ECM_UL_CONTRAT'),
(151, 'ECM_UL_DEL_ACTION'),
(152, 'ECM_UL_DEL_AFFAIRE'),
(153, 'ECM_UL_DEL_CHRONO'),
(154, 'ECM_UL_DEL_COMMANDE'),
(155, 'ECM_UL_DEL_COMMFOURN'),
(156, 'ECM_UL_DEL_SYNOPSISDEMANDEINTERV'),
(157, 'ECM_UL_DEL_FACTURE'),
(158, 'ECM_UL_DEL_FACTUREFOURN'),
(159, 'ECM_UL_DEL_FICHINTER'),
(160, 'ECM_UL_DEL_NDF'),
(161, 'ECM_UL_DEL_ORIJET'),
(162, 'ECM_UL_DEL_PRODUCT'),
(163, 'ECM_UL_DEL_PRODUCT_PHOTO'),
(164, 'ECM_UL_DEL_PROPAL'),
(165, 'ECM_UL_DEL_SOCIETE'),
(166, 'ECM_UL_DEL_USER_PHOTO'),
(167, 'ECM_UL_SYNOPSISDEMANDEINTERV'),
(168, 'ECM_UL_FACTURE'),
(169, 'ECM_UL_FACTUREFOURN'),
(170, 'ECM_UL_FICHINTER'),
(171, 'ECM_UL_IMPORT'),
(172, 'ECM_UL_LOGO'),
(173, 'ECM_UL_MAILLING'),
(174, 'ECM_UL_NDF'),
(175, 'ECM_UL_PRODUCT'),
(176, 'ECM_UL_PRODUCT_PHOTO'),
(177, 'ECM_UL_PROJET'),
(178, 'ECM_UL_PROPAL'),
(179, 'ECM_UL_SOCIETE'),
(180, 'ECM_UL_USER_PHOTO'),
(181, 'FICHEINTER_DELETELINE'),
(182, 'FICHEINTER_INSERTLINE'),
(183, 'FICHEINTER_SETDELIVERY'),
(184, 'FICHEINTER_SETDESCRIPTION'),
(185, 'FICHEINTER_UPDATELINE'),
(186, 'FICHEINTER_UPDATETOTAL'),
(187, 'FICHINTER_UPDATETOTAL'),
(188, 'LINEBILL_DELETE'),
(189, 'LINEORDER_DELETE'),
(190, 'LINEORDER_INSERT'),
(191, 'ORDER_SUPPLIER_APPROVE'),
(192, 'ORDER_SUPPLIER_DELETE'),
(193, 'PROJECT_CREATE_PROJADMIN'),
(194, 'PROJECT_DELETE_TASK'),
(195, 'PROJECT_MOD_PROJADMIN'),
(196, 'NOTIFY_ORDER_CHANGE_CONTRAT_MODE_REG'),
(197, 'ECM_GENCONTRAT'),
(208, 'PREPACOM_INTERNAL_EXPEDITION_SENDMAIL'),
(207, 'PREPACOM_MOD_DISPO_PROD'),
(206, 'PREPACOM_MOD_INDISPO_PROD'),
(205, 'PREPACOM_PARTIAL_FINANCE'),
(204, 'PREPACOM_KO_FINANCE'),
(203, 'PREPACOM_OK_FINANCE'),
(202, 'PREPACOM_INTERNAL_MESSAGE'),
(201, 'PREPACOM_DEVAL_FINANCE'),
(200, 'PREPACOM_DISPO_PROD'),
(199, 'PREPACOM_INDISPO_PROD'),
(198, 'PREPACOM_UPDATE_STATUT');");
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