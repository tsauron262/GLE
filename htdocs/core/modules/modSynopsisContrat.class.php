t<?php
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
        $this->picto = 'contrat';

        // Dir
        $this->dirs = array();

        // Config pages
        //$this->config_page_url = "";
        // Dependences
        $this->depends = array("modSynopsisTools", "modContrat");
        $this->requiredby = array();

        // Constantes
        $this->const = array();
        $r = 0;

        $this->const[$r][0] = "CONTRAT_GMAO_ADDON_PDF";
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

        $r = 0;
        $this->tabs = array('contract:+annexe:Annexe PDF:@monmodule:/Synopsis_Contrat/annexes.php?id=__ID__',
            'contract:+interv:Interventions:@monmodule:/Synopsis_Contrat/intervByContrat.php?id=__ID__',
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
            "DELETE FROM " . MAIN_DB_PREFIX . "document_model WHERE nom = '" . $this->const[0][2] . "' AND entity = " . $conf->entity,
            "INSERT INTO " . MAIN_DB_PREFIX . "document_model (nom, type, entity) VALUES('" . $this->const[0][2] . "','contratGMAO'," . $conf->entity . ")",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_contrat_annexe` (
  `annexe_refid` int(11) DEFAULT NULL,
  `contrat_refid` int(11) DEFAULT NULL,
  `rang` int(11) DEFAULT NULL,
  annexe TEXT,
  KEY `annexe_refid` (`annexe_refid`),
  KEY `contrat_refid` (`contrat_refid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;",
            "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "Synopsis_contrat_annexePdf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modeleName` varchar(150) DEFAULT NULL,
  `ref` varchar(50) DEFAULT NULL,
  `annexe` longtext,
  `afficheTitre` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=39 ;",
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;",
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
  PRIMARY KEY (`id`),
  KEY `fk_prod` (`fk_prod`),
  KEY `contrat_refid` (`contrat_refid`)
)");
//    $this->dirs[0] = $conf->chrono->dir_output;

        return $this->_init($sql);
    }

    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove() {
        global $conf;
        $sql = array("DELETE FROM " . MAIN_DB_PREFIX . "document_model WHERE nom = '" . $this->const[0][2] . "' AND entity = " . $conf->entity);
        return $this->_remove($sql);
    }

}
?>