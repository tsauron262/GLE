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
class modSynopsisHoliday extends DolibarrModules {

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function modSynopsisHoliday($DB) {
        $this->db = $DB;
        $this->numero = 8000;

        $this->family = "Synopsis";
        $this->name = "Holiday";
        $this->description = utf8_decode("Cong&eacute;s et RTT");
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISHOLIDAY';
        $this->special = 0;
        $this->picto = 'holiday';

        // Dir
        $this->dirs = array();

        // Config pages
        //$this->config_page_url = "";
        $this->config_page_url = "holiday.php@synopsisholiday";

        // Dependences
        $this->depends = array("modHoliday");

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
        $this->rights_class = 'synopsisholiday';

        $r = 0;




        $this->menus = array();            // List of menus to add
        $r = 0;
        $s = $r;
        $r++;


        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=hrm',
            'type' => 'left',
            'titre' => 'CPTitreMenu',
            'mainmenu' => 'hrm',
            'leftmenu' => 'holiday', // To say if we can overwrite leftmenu
            'url' => '/synopsisholiday/index.php?leftmenu=holiday',
            'langs' => 'holiday@synopsisholiday',
            'position' => 1,
            'perms' => '$user->rights->holiday->write',
            'target' => '',
            'user' => 0);


        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=hrm,fk_leftmenu=holiday',
            'type' => 'left',
            'titre' => 'MenuAddCP',
            'mainmenu' => 'hrm',
            'leftmenu' => '1', // To say if we can overwrite leftmenu
            'url' => '/synopsisholiday/card.php?&action=request',
            'langs' => 'holiday@synopsisholiday',
            'position' => 1,
            'perms' => '$user->rights->holiday->write',
            'target' => '',
            'user' => 0);


        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=hrm,fk_leftmenu=holiday',
            'type' => 'left',
            'titre' => 'MenuConfCP',
            'mainmenu' => 'hrm',
            'leftmenu' => '1', // To say if we can overwrite leftmenu
            'url' => '/synopsisholiday/define_holiday.php?&action=request',
            'langs' => 'holiday@synopsisholiday',
            'position' => 1,
            'perms' => '$user->rights->holiday->define_holiday',
            'target' => '',
            'user' => 0);


        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=hrm,fk_leftmenu=holiday',
            'type' => 'left',
            'titre' => 'MenuConfRTT',
            'mainmenu' => 'hrm',
            'leftmenu' => '1', // To say if we can overwrite leftmenu
            'url' => '/synopsisholiday/define_rtt.php?&action=request',
            'langs' => 'holiday@synopsisholiday',
            'position' => 1,
            'perms' => '$user->rights->holiday->define_holiday',
            'target' => '',
            'user' => 0);


        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=hrm,fk_leftmenu=holiday',
            'type' => 'left',
            'titre' => 'MenuLogCP',
            'mainmenu' => 'hrm',
            'leftmenu' => '1', // To say if we can overwrite leftmenu
            'url' => '/synopsisholiday/view_log.php?&action=request',
            'langs' => 'holiday@synopsisholiday',
            'position' => 1,
            'perms' => '$user->rights->holiday->read_all',
            'target' => '',
            'user' => 0);


        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=hrm,fk_leftmenu=holiday',
            'type' => 'left',
            'titre' => 'MenuReportMonth',
            'mainmenu' => 'hrm',
            'leftmenu' => '1', // To say if we can overwrite leftmenu
            'url' => '/synopsisholiday/month_report.php?&action=request',
            'langs' => 'holiday@synopsisholiday',
            'position' => 5,
            'perms' => '$user->rights->holiday->read_all',
            'target' => '',
            'user' => 0);


        $r++;
        $this->menu[$r] = array('fk_menu' => 'fk_mainmenu=hrm,fk_leftmenu=holiday',
            'type' => 'left',
            'titre' => 'Mes Validations à traiter',
            'mainmenu' => 'hrm',
            'leftmenu' => '1', // To say if we can overwrite leftmenu
            'url' => '/synopsisholiday/index.php?&myValid=true',
            'langs' => 'holiday@synopsisholiday',
            'position' => 5,
            'perms' => '$user->rights->holiday->read_all',
            'target' => '',
            'user' => 0);


        $this->tabs = array('user:-paidholidays',
            'user:+paidholidaysRtt:Congés RTT perso:@monmodule:/synopsisholiday/index.php?id=__ID__',
            'user:+paidholidaysRtt2:Valid Congés RTT à traiter:@monmodule:/synopsisholiday/index.php?search_valideur=__ID__');
    }

    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees e creer pour ce module.
     */
    function init() {
        $this->remove();
        $sql = array("ALTER TABLE `" . MAIN_DB_PREFIX . "holiday` ADD `type_conges` INT( 1 ) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0: ordinaires. 1 : congés exceptionnels. 2: rtt';",
            "ALTER TABLE `" . MAIN_DB_PREFIX . "holiday` ADD `date_drh_valid` DATETIME NULL DEFAULT NULL AFTER `date_valid`;",
            "ALTER TABLE `" . MAIN_DB_PREFIX . "holiday` ADD `fk_user_drh_valid` INT( 11 ) NULL DEFAULT NULL AFTER `fk_user_valid` ;",
            "ALTER TABLE `" . MAIN_DB_PREFIX . "holiday_users` ADD `nb_rtt` DOUBLE NOT NULL DEFAULT '0';",
            "ALTER TABLE `" . MAIN_DB_PREFIX . "holiday` ADD `fk_actioncomm` INT( 11 ) NULL DEFAULT NULL;",
            "ALTER TABLE `" . MAIN_DB_PREFIX . "holiday` ADD `fk_substitute` INT( 11 ) NULL DEFAULT NULL;",
            "ALTER TABLE `" . MAIN_DB_PREFIX . "holiday_users` ADD `nb_holiday_next` DOUBLE NOT NULL DEFAULT '0' COMMENT 'Année n+1';",
//            "ALTER TABLE `" . MAIN_DB_PREFIX . "holiday_users` CHANGE `nb_holiday` `nb_holiday_next` DOUBLE NOT NULL DEFAULT '0' COMMENT 'Année n+1';",
//            "ALTER TABLE `" . MAIN_DB_PREFIX . "holiday_users` ADD `nb_holiday_current` DOUBLE NOT NULL DEFAULT '0' COMMENT 'Année en cours' AFTER `nb_holiday_next`;",
            "INSERT INTO `" . MAIN_DB_PREFIX . "holiday_config` (
                `name` ,
                `value`
                ) VALUES (
                'drhUserId', '81'),(
                'nbRTTDeducted', '1'), (
                'cpNewYearDay', '31'), (
                'cpNewYearMonth', '12'), (
                'lastAnnualUpdate', '0000'), (
                'nbRTTEveryMonth', '1.0416')
            );");

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
