<?php
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
class modSynopsisContrat extends DolibarrModules {

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function modSynopsisContrat($DB) {
        $this->db = $DB;
        $this->numero = 8000;

        $this->family = "Synopsis";
        $this->name = "Contrat +";
        $this->description = "Amélioration des contrat";
        $this->version = '0.1';    // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_SYNOPSISCONTRAT';
        $this->special = 0;
        $this->picto = 'contract';

        // Dir
        $this->dirs = array();

        // Config pages
        //$this->config_page_url = "";
        // Dependences
        $this->depends = array("modSynopsistools", "modContrat");
        $this->requiredby = array();

        // Constantes
        $this->const = array();
        
        
        $this->module_parts = array(
            'models' => 1,
            'triggers' => 1,
            'hooks' => array('contractcard')
        );
        
        $r = 0;

        $this->const[$r][0] = "SYNOPSIS_CONTRAT_ADDON_PDF";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "BIMP";

        $r++;

//        // Boites
//        $this->boxes = array();
        // Boites
//    $this->boxes = array();
//    $r=0;
//    $this->boxes[$r][1] = "box_deplacement.php";
//    $r++;
        // Permissions
        $this->rights = array();
        $this->rights_class = 'synopsiscontrat';

        $this->rights[1][0] = 161881;
        $this->rights[1][1] = 'Generer les PDF contrats';
        $this->rights[1][2] = 'r';
        $this->rights[1][3] = 1;
        $this->rights[1][4] = 'generate';

        $this->rights[2][0] = 161882;
        $this->rights[2][1] = 'Lire les PDF contrats';
        $this->rights[2][2] = 'r';
        $this->rights[2][3] = 1;
        $this->rights[2][4] = 'read';

        $this->rights[2][0] = 161883;
        $this->rights[2][1] = 'Renouveller les contrats';
        $this->rights[2][2] = 'r';
        $this->rights[2][3] = 1;
        $this->rights[2][4] = 'renouveller';

        $this->rights[3][0] = 161884;
        $this->rights[3][1] = 'Gérer les annexe';
        $this->rights[3][2] = 'r';
        $this->rights[3][3] = 1;
        $this->rights[3][4] = 'annexe';


        $r = 0;
        $this->tabs = array(
            //'contract:+details:Détails des services:@monmodule:1:/Synopsis_Contrat/contratdet.php?id=__ID__',
            'contract:+annexe:Annexe PDF:@monmodule:$user->rights->synopsiscontrat->annexe:/Synopsis_Contrat/annexes.php?id=__ID__',
            'contract:+interv:Interventions:@monmodule:$user->rights->synopsiscontrat->generate:/Synopsis_Contrat/intervByContrat.php?id=__ID__',
                /* 'contract:+tickets:Tickets:@monmodule:/Synopsis_Contrat/annexes.php?id=__ID__',
                  'contract:+sav:SAV:@monmodule:/Babel_GMAO/savByContrat.php?id=__ID__' */                );
    }

    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees e creer pour ce module.
     */
    function init() {
        global $conf;
        $sql = array(
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_contrat_annexe` (
  `annexe_refid` int(11) DEFAULT NULL,
  `contrat_refid` int(11) DEFAULT NULL,
  `rang` int(11) DEFAULT NULL,
  annexe TEXT,
  KEY `annexe_refid` (`annexe_refid`),
  KEY `contrat_refid` (`contrat_refid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modeleName` varchar(150) DEFAULT NULL,
  `ref` varchar(50) DEFAULT NULL,
  `annexe` longtext,
  `afficheTitre` tinyint(1) DEFAULT NULL,
  `type` int(10) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=39 ;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_contrat_GA` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contratdet_refid` int(11) DEFAULT NULL,
  `isTx0` tinyint(1) DEFAULT NULL,
  `montantTotHTAFinancer` float(8,3) DEFAULT NULL,
  `tauxMarge` float(8,3) DEFAULT NULL,
  `tms` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `tauxFinancement` float(8,3) DEFAULT NULL,
  `financement_period_refid` int(11) DEFAULT NULL,
  `echu` tinyint(1) DEFAULT NULL,
  `client_signataire_refid` int(11) DEFAULT NULL,
  `duree` int(11) DEFAULT NULL,
  `is_financement` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `propaldet_refid` (`contratdet_refid`),
  KEY `financement_period_refid` (`financement_period_refid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_contratdet_GMAO` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contratdet_refid` int(11) DEFAULT NULL,
  `tms` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `durValid` int(11) DEFAULT NULL,
  `DateDeb` datetime DEFAULT NULL,
  `fk_prod` int(11) DEFAULT NULL,
  `reconductionAuto` int(11) DEFAULT NULL,
  `qte` int(11) DEFAULT NULL,
  `hotline` int(11) DEFAULT NULL,
  `telemaintenance` int(11) DEFAULT NULL,
  `telemaintenanceCur` int(11) DEFAULT NULL,
  `maintenance` int(11) DEFAULT NULL,
  `SLA` varchar(150) DEFAULT NULL,
  `isSAV` int(11) DEFAULT NULL,
  `fk_contrat_prod` int(11) DEFAULT NULL,
  `type` tinyint(4) DEFAULT NULL,
  `clause` longtext,
  `prorataTemporis` tinyint(4) DEFAULT NULL,
  `prixAn1` double DEFAULT NULL,
  `prixAnDernier` double DEFAULT NULL,
  `nbVisite` int(11) DEFAULT NULL,
  `qteTempsPerDuree` int(11) NOT NULL DEFAULT '0',
  `qteTktPerDuree` int(11) NOT NULL DEFAULT '1',
  `rang` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_prod` (`fk_prod`),
  KEY `contrat_refid` (`contratdet_refid`)
)",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_contrat_GMAO` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contrat_refid` int(11) DEFAULT NULL,
  `tms` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `durValid` int(11) DEFAULT NULL,
  `DateDeb` datetime DEFAULT NULL,
  `fk_prod` int(11) DEFAULT NULL,
  `reconductionAuto` int(11) DEFAULT NULL,
  `qte` int(11) DEFAULT NULL,
  `hotline` int(11) DEFAULT NULL,
  `telemaintenance` int(11) DEFAULT NULL,
  `maintenance` int(11) DEFAULT NULL,
  `SLA` varchar(150) DEFAULT NULL,
  `isSAV` int(11) DEFAULT NULL,
  `dateAnniv` datetime DEFAULT NULL,
  `nbVisite` int(11) DEFAULT NULL,
  `condReg_refid` int(11) default NULL,
  `modeReg_refid` int(11) default NULL,
  PRIMARY KEY (`id`),
  KEY `fk_prod` (`fk_prod`),
  KEY `contrat_refid` (`contrat_refid`)
)",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_product_serial_cont` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `element_id` int(11) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `date_creation` datetime DEFAULT NULL,
  `tms` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_fin_SAV` datetime DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `element_type` varchar(10) DEFAULT 'contratGA',
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial_cont_uniq` (`element_id`,`element_type`),
  KEY `element_id` (`element_id`),
  KEY `fk_user_author` (`fk_user_author`)
)");
//    $this->dirs[0] = $conf->chrono->dir_output;
$tabModel = array(
  "contrat_BIMP" => "Modèle BIMP",
  "contrat_courrier_BIMP_renvois" => "BIMP Courrier Renvois de contrat",
  "contrat_courrierBIMPfinapro" => "BIMP Contrat - Financement", 
  "contrat_courrierBIMPavenant" => "BIMP Courrier - Avenant", 
  "contrat_courrierBIMPconfirmation" => "BIMP Courrier - Confirmation", 
  "contrat_courrierBIMPrenouvellement" => "BIMP Courrier - Renouvellement", 
  "contrat_courrierBIMPresiliation" => "BIMP Courrier - Resiliation", 
  "contrat_courrierBIMPsignature" => "BIMP Courrier - Signature", 
  "contrat_courrierBIMPresiliationAvoir" => "BIMP Courrier - Resiliation & avoir", 
  "contrat_courrierBIMPAutoPrelevement" => "BIMP Courrier - Prelevement automatique",
  //"contrat_courrierBIMPfinapro" => "BIMP contrat - Financement + Proces Verbal + Mandat Prélèvement",
  //"contrat_LDLC_lease" => "Liasse LDLC Lease",
  "contrat_BIMP_maintenance" => 'Contrat maintenance informatique'
);

        foreach($tabModel as $result => $nom){
            $sql[] = "DELETE FROM " . MAIN_DB_PREFIX . "document_model WHERE nom = '".$result."'";        
        }
        
        foreach($tabModel as $result => $nom){
            $sql[] = "INSERT INTO " . MAIN_DB_PREFIX . "document_model (nom, type, entity, libelle) VALUES('" . $result . "','contract'," . $conf->entity . ", '".$nom."')";
        }
        
        $extrafields = new ExtraFields($this->db);
        $extrafields->addExtraField('periodicity', 'Périodicité', 'select', 40, 30, 'contrat', 0, 0, "", array("options" => array(1 => "Mensuelle",3 => "Trimestrielle",6 => "Semestrielle",12 => "Annuelle")), 1, "", 1, 1, "", "", "", 1);
        $extrafields->addExtraField('tacite', 'Renouvellement tacite', 'select', 40, 30, 'contrat', 0, 0, "", array("options" => array(1 => "1 fois",3 => "2 fois",6 => "3 fois",12 => "Sur proposition")), 1, "", 1, 1, "", "", "", 1);
        $extrafields->addExtraField('syntec', 'Indice SYNTEC', 'float', 1, 10, 'contrat', 0, 0, "", NULL, 1, "", 1, 1, "", "", "", 1);
        //$extrafields->addExtraField('syntec_pdf', 'Utiliser l\'indice Syntec', 'boolean', 1, 10, 'contrat', 0, 0, "", 1, 1, "", 1, 1, "", "", "", 1);
        $extrafields->addExtraField('date_start', 'Date de début', 'date', 37, 30, 'contrat', 0, 0, "", NULL, 1, "", 1, 1, "", "", "", 1);
        $extrafields->addExtraField('duree_mois', 'Durée en mois', 'int', 38, 30, 'contrat', 0, 0, "", NULL, 1, "", 1, 1, "", "", "", 1);
        $extrafields->addExtraField('gti', 'Délais d\'intervention', 'select', 39, 30, 'contrat', 0, 0, "", array( 'options' => array(2 => '2h ouvrées',4 => '4h ouvrées',8 => '8h ouvrées',16 => '16h ouvrées')), 1, "", 1, 1, "", "", "", 1);
        $extrafields->addExtraField('denounce', 'Contrat dénoncé', 'select', 100, 30, 'contrat', 0, 0, "", array( 'options' => array(0 => 'Non',1 => 'Oui dans les temps',2 => 'Oui hors délais')), 1, "", 1, 1, "", "", "", 1);
        //$extrafields->update('tacite', 'Renouvellement tacite', 'select', 30, 'contrat', 0, 0, 40, array("options" => array(1 => "bLA BLA1 fois",3 => "Tacite 2 fois",6 => "Tacite 3 fois",12 => "Sur proposition")), 1, '', 1);
        $extrafields = null;
        $sql[] = "INSERT INTO `".MAIN_DB_PREFIX."cronjob` (`jobtype`, `label`, `command`, `classesname`, `objectname`, `methodename`, `params`, `md5params`, `module_name`, `priority`, `datelastrun`, `datenextrun`, `datestart`, `dateend`, `datelastresult`, `lastresult`, `lastoutput`, `unitfrequency`, `frequency`, `nbrun`, `status`, `fk_user_author`, `fk_user_mod`, `note`, `libname`, `entity`, `maxrun`, `autodelete`, `fk_mailing`, `test`, `processing`) VALUES
('method', 'Rappel tâche commerciaux', 'curl http://127.0.0.1/bimp-8/bimp-erp/htdocs/synopsiscontrat/testreminder.php?days=2', 'synopsiscontrat/class/remindendservice.class.php', 'RemindEndService', 'setTaskForService', '3', '', 'synopsiscontrat', 0, '2010-01-20 17:31:27', '2010-02-27 11:30:00', '2010-02-08 00:00:00', NULL, NULL, '', '', '3600', 1, 0, 1, 330, 330, 'Envoie une tâche aux commerciaux pour leurs rappeler qu\'un service arrive à terme.\r\nParamètre : nombre de jours qui séparent aujourd\'hui et la date de fin de validité des services.', NULL, 1, 0, 0, NULL, NULL, 0);";
        
        return $this->_init($sql);
    }

    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove() {
        global $conf;
//        $extrafields = new ExtraFields($this->db);
//        $extrafields->update('tacite', 'Renouvellement tacite', 'select', 30, 'contrat', 0, 0, 40, array("options" => array(1 => "Tacite 1 fois",3 => "Tacite 2 fois",6 => "Tacite 3 fois",12 => "Sur proposition")), 1, '', 1);
//        $extrafields->update('date_start', 'Date de début', 'date', 30, 'contrat', 0, 0, 37, NULL, 1, '', 1);
//        $extrafields->update('duree_mois', 'Durée en mois', 'int', 11, 'contrat', 0, 0, 38, NULL, 1, '', 1);
//         $extrafields->delete('syntec', 'contrat');
//         $extrafields->delete('tacite', 'contrat');
//         $extrafields->delete('denounce', 'contrat');
//         $extrafields->delete('date_start', 'contrat');
//         $extrafields->delete('duree_mois', 'contrat');
//         $extrafields->delete('periodicity', 'contrat');
        $sql = array("DELETE FROM " . MAIN_DB_PREFIX . "document_model WHERE nom = '" . $this->const[0][2] . "' AND entity = " . $conf->entity);
        $sql[] = "DELETE FROM `".MAIN_DB_PREFIX."cronjob` WHERE `methodename` LIKE 'setTaskForService'";
        return $this->_remove($sql);
    }

}
?>
