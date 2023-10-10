<?php

$tabTableSuppr = array("synopsischrono_key", "synopsischrono_conf", "Synopsis_Dashboard", "Synopsis_Dashboard_module", "Synopsis_Dashboard_page", "Synopsis_Dashboard_settings", "Synopsis_Dashboard_widget",
    "Synopsis_Process_type_element", "Synopsis_Process_type_element_trigger",
    "Synopsis_fichinter", "Synopsis_fichinterdet", "Synopsis_fichinter_c_typeInterv", "Synopsis_fichinter_extra_key", "Synopsis_fichinter_extra_value", "Synopsis_fichinter_extra_values_choice", "Synopsis_fichinter_User_PrixDepInterv", "Synopsis_fichinter_User_PrixTypeInterv");


$modulesInit = array("SynopsisFicheinter", "Ficheinter", "Synopsisdemandeinterv", "SynopsisDashboard", "SynopsisProcess", "SynopsisChrono", "SynopsisHotline");




$tabsql = array();
foreach ($tabTableSuppr as $table)
    $tabSql[] = "DROP table " . MAIN_DB_PREFIX . $table;

$tabSql[] = "DROP view " . MAIN_DB_PREFIX . "fichinter";

$tabSql[] = "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "fichinter` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) NOT NULL,
  `fk_projet` int(11) DEFAULT '0',
  `fk_contrat` int(11) DEFAULT '0',
  `ref` varchar(30) NOT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `datec` datetime DEFAULT NULL,
  `date_valid` datetime DEFAULT NULL,
  `datei` date DEFAULT NULL,
  `fk_user_author` int(11) DEFAULT NULL,
  `fk_user_valid` int(11) DEFAULT NULL,
  `fk_statut` smallint(6) DEFAULT '0',
  `duree` double DEFAULT NULL,
  `description` text,
  `note_private` text,
  `note_public` text,
  `model_pdf` varchar(255) DEFAULT NULL,
  `extraparams` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_fichinter_ref` (`ref`,`entity`),
  KEY `idx_fichinter_fk_soc` (`fk_soc`)
)";


$tabSql[] = "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "fichinterdet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_fichinter` int(11) DEFAULT NULL,
  `fk_parent_line` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `description` text,
  `duree` int(11) DEFAULT NULL,
  `rang` int(11) DEFAULT '0',
  PRIMARY KEY (`rowid`),
  KEY `idx_fichinterdet_fk_fichinter` (`fk_fichinter`)
)";




$text = "Suivre lien ".DOL_URL_ROOT."/htdocs//synopsischrono/admin/synopsischrono.php?action=modify&id=7 enregistrer pour actualiser les onglet chronolist ds objet.";
$php = '';

foreach ($modulesInit as $module)
    $php .= 'require_once(DOL_DOCUMENT_ROOT."/core/modules/mod' . $module . '.class.php");
$module = new mod' . $module . '($db);
echo $module->init()."<br/>";';


//        $tabSql = array("UPDATE ".MAIN_DB_PREFIX."synopsischrono_conf set active = 2 where active = 1",
//        "UPDATE ".MAIN_DB_PREFIX."synopsischrono_conf set active = 1 where active = 0",
//        "UPDATE ".MAIN_DB_PREFIX."synopsischrono_conf set active = 0 where active = 2");
?>
