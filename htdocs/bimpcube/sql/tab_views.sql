SET character_set_client      = utf8;
SET character_set_results     = utf8;
SET collation_connection      = utf8_general_ci;
--
-- Temporary table structure for view llx_olap_view_categorie_leaf
--
DROP TABLE IF EXISTS `llx_olap_view_categorie_leaf`;
DROP VIEW IF EXISTS `llx_olap_view_categorie_leaf`;
CREATE TABLE `llx_olap_view_categorie_leaf` (
  `rowid` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_commande
--
DROP TABLE IF EXISTS `llx_olap_view_commande`;
DROP VIEW IF EXISTS `llx_olap_view_commande`;
CREATE TABLE `llx_olap_view_commande` (
  `rowid` tinyint NOT NULL,
  `ref` tinyint NOT NULL,
  `date_valid` tinyint NOT NULL,
  `date_valid_year` tinyint NOT NULL,
  `date_valid_quarter` tinyint NOT NULL,
  `date_valid_month` tinyint NOT NULL,
  `date_valid_day` tinyint NOT NULL,
  `total_ht` tinyint NOT NULL,
  `id_entrepot` tinyint NOT NULL,
  `id_user` tinyint NOT NULL,
  `id_societe` tinyint NOT NULL,
  `total_marge` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_consommable
--
DROP TABLE IF EXISTS `llx_olap_view_consommable`;
DROP VIEW IF EXISTS `llx_olap_view_consommable`;
CREATE TABLE `llx_olap_view_consommable` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_facture
--
DROP TABLE IF EXISTS `llx_olap_view_facture`;
DROP VIEW IF EXISTS `llx_olap_view_facture`;
CREATE TABLE `llx_olap_view_facture` (
  `rowid` tinyint NOT NULL,
  `ref` tinyint NOT NULL,
  `date_valid` tinyint NOT NULL,
  `date_valid_year` tinyint NOT NULL,
  `date_valid_quarter` tinyint NOT NULL,
  `date_valid_month` tinyint NOT NULL,
  `date_valid_day` tinyint NOT NULL,
  `total_ht` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `id_entrepot` tinyint NOT NULL,
  `sav_entrepot` tinyint NOT NULL,
  `id_user` tinyint NOT NULL,
  `id_societe` tinyint NOT NULL,
  `total_marge` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_logiciel_nature
--
DROP TABLE IF EXISTS `llx_olap_view_logiciel_nature`;
DROP VIEW IF EXISTS `llx_olap_view_logiciel_nature`;
CREATE TABLE `llx_olap_view_logiciel_nature` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_logiciel_type
--
DROP TABLE IF EXISTS `llx_olap_view_logiciel_type`;
DROP VIEW IF EXISTS `llx_olap_view_logiciel_type`;
CREATE TABLE `llx_olap_view_logiciel_type` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_marque
--
DROP TABLE IF EXISTS `llx_olap_view_marque`;
DROP VIEW IF EXISTS `llx_olap_view_marque`;
CREATE TABLE `llx_olap_view_marque` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_materiel_nature
--
DROP TABLE IF EXISTS `llx_olap_view_materiel_nature`;
DROP VIEW IF EXISTS `llx_olap_view_materiel_nature`;
CREATE TABLE `llx_olap_view_materiel_nature` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_materiel_type
--
DROP TABLE IF EXISTS `llx_olap_view_materiel_type`;
DROP VIEW IF EXISTS `llx_olap_view_materiel_type`;
CREATE TABLE `llx_olap_view_materiel_type` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_modele
--
DROP TABLE IF EXISTS `llx_olap_view_modele`;
DROP VIEW IF EXISTS `llx_olap_view_modele`;
CREATE TABLE `llx_olap_view_modele` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_obsolescence
--
DROP TABLE IF EXISTS `llx_olap_view_obsolescence`;
DROP VIEW IF EXISTS `llx_olap_view_obsolescence`;
CREATE TABLE `llx_olap_view_obsolescence` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_produit_autre
--
DROP TABLE IF EXISTS `llx_olap_view_produit_autre`;
DROP VIEW IF EXISTS `llx_olap_view_produit_autre`;
CREATE TABLE `llx_olap_view_produit_autre` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_propal
--
DROP TABLE IF EXISTS `llx_olap_view_propal`;
DROP VIEW IF EXISTS `llx_olap_view_propal`;
CREATE TABLE `llx_olap_view_propal` (
  `rowid` tinyint NOT NULL,
  `ref` tinyint NOT NULL,
  `date_valid` tinyint NOT NULL,
  `date_valid_year` tinyint NOT NULL,
  `date_valid_quarter` tinyint NOT NULL,
  `date_valid_month` tinyint NOT NULL,
  `date_valid_day` tinyint NOT NULL,
  `total_ht` tinyint NOT NULL,
  `propal_entrepot` tinyint NOT NULL,
  `sav_entrepot` tinyint NOT NULL,
  `id_user` tinyint NOT NULL,
  `id_societe` tinyint NOT NULL,
  `total_marge` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_recurr_period_nature
--
DROP TABLE IF EXISTS `llx_olap_view_recurr_period_nature`;
DROP VIEW IF EXISTS `llx_olap_view_recurr_period_nature`;
CREATE TABLE `llx_olap_view_recurr_period_nature` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_recurr_period_type
--
DROP TABLE IF EXISTS `llx_olap_view_recurr_period_type`;
DROP VIEW IF EXISTS `llx_olap_view_recurr_period_type`;
CREATE TABLE `llx_olap_view_recurr_period_type` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_recurr_unique
--
DROP TABLE IF EXISTS `llx_olap_view_recurr_unique`;
DROP VIEW IF EXISTS `llx_olap_view_recurr_unique`;
CREATE TABLE `llx_olap_view_recurr_unique` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_sav
--
DROP TABLE IF EXISTS `llx_olap_view_sav`;
DROP VIEW IF EXISTS `llx_olap_view_sav`;
CREATE TABLE `llx_olap_view_sav` (
  `id` tinyint NOT NULL,
  `ref` tinyint NOT NULL,
  `status` tinyint NOT NULL,
  `code_centre` tinyint NOT NULL,
  `id_entrepot` tinyint NOT NULL,
  `id_user` tinyint NOT NULL,
  `id_client` tinyint NOT NULL,
  `date_create_year` tinyint NOT NULL,
  `date_create_quarter` tinyint NOT NULL,
  `date_create_month` tinyint NOT NULL,
  `date_create_day` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_service_competence
--
DROP TABLE IF EXISTS `llx_olap_view_service_competence`;
DROP VIEW IF EXISTS `llx_olap_view_service_competence`;
CREATE TABLE `llx_olap_view_service_competence` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_service_nature
--
DROP TABLE IF EXISTS `llx_olap_view_service_nature`;
DROP VIEW IF EXISTS `llx_olap_view_service_nature`;
CREATE TABLE `llx_olap_view_service_nature` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_service_type
--
DROP TABLE IF EXISTS `llx_olap_view_service_type`;
DROP VIEW IF EXISTS `llx_olap_view_service_type`;
CREATE TABLE `llx_olap_view_service_type` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_societe
--
DROP TABLE IF EXISTS `llx_olap_view_societe`;
DROP VIEW IF EXISTS `llx_olap_view_societe`;
CREATE TABLE `llx_olap_view_societe` (
  `rowid` tinyint NOT NULL,
  `nom` tinyint NOT NULL,
  `code` tinyint NOT NULL,
  `statut` tinyint NOT NULL,
  `status` tinyint NOT NULL,
  `zip` tinyint NOT NULL,
  `id_dept` tinyint NOT NULL,
  `code_dept` tinyint NOT NULL,
  `town` tinyint NOT NULL,
  `fk_dept` tinyint NOT NULL,
  `fk_pays` tinyint NOT NULL,
  `country_code` tinyint NOT NULL,
  `country_label` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_textile_type
--
DROP TABLE IF EXISTS `llx_olap_view_textile_type`;
DROP VIEW IF EXISTS `llx_olap_view_textile_type`;
CREATE TABLE `llx_olap_view_textile_type` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_time_commande
--
DROP TABLE IF EXISTS `llx_olap_view_time_commande`;
DROP VIEW IF EXISTS `llx_olap_view_time_commande`;
CREATE TABLE `llx_olap_view_time_commande` (
  `rowid` tinyint NOT NULL,
  `date_valid_year` tinyint NOT NULL,
  `date_valid_quarter` tinyint NOT NULL,
  `date_valid_month` tinyint NOT NULL,
  `date_valid_day` tinyint NOT NULL,
  `label_quarter` tinyint NOT NULL,
  `code_month` tinyint NOT NULL,
  `label_month` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_time_facture
--
DROP TABLE IF EXISTS `llx_olap_view_time_facture`;
DROP VIEW IF EXISTS `llx_olap_view_time_facture`;
CREATE TABLE `llx_olap_view_time_facture` (
  `rowid` tinyint NOT NULL,
  `date_valid_year` tinyint NOT NULL,
  `date_valid_quarter` tinyint NOT NULL,
  `date_valid_month` tinyint NOT NULL,
  `date_valid_day` tinyint NOT NULL,
  `label_quarter` tinyint NOT NULL,
  `code_month` tinyint NOT NULL,
  `label_month` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_time_propal
--
DROP TABLE IF EXISTS `llx_olap_view_time_propal`;
DROP VIEW IF EXISTS `llx_olap_view_time_propal`;
CREATE TABLE `llx_olap_view_time_propal` (
  `rowid` tinyint NOT NULL,
  `date_valid_year` tinyint NOT NULL,
  `date_valid_quarter` tinyint NOT NULL,
  `date_valid_month` tinyint NOT NULL,
  `date_valid_day` tinyint NOT NULL,
  `label_quarter` tinyint NOT NULL,
  `code_month` tinyint NOT NULL,
  `label_month` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_time_sav
--
DROP TABLE IF EXISTS `llx_olap_view_time_sav`;
DROP VIEW IF EXISTS `llx_olap_view_time_sav`;
CREATE TABLE `llx_olap_view_time_sav` (
  `id` tinyint NOT NULL,
  `date_create_year` tinyint NOT NULL,
  `date_create_quarter` tinyint NOT NULL,
  `date_create_month` tinyint NOT NULL,
  `date_create_day` tinyint NOT NULL,
  `label_quarter` tinyint NOT NULL,
  `code_month` tinyint NOT NULL,
  `label_month` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_transport
--
DROP TABLE IF EXISTS `llx_olap_view_transport`;
DROP VIEW IF EXISTS `llx_olap_view_transport`;
CREATE TABLE `llx_olap_view_transport` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_olap_view_user
--
DROP TABLE IF EXISTS `llx_olap_view_user`;
DROP VIEW IF EXISTS `llx_olap_view_user`;
CREATE TABLE `llx_olap_view_user` (
  `rowid` tinyint NOT NULL,
  `fk_user` tinyint NOT NULL,
  `gender` tinyint NOT NULL,
  `login` tinyint NOT NULL,
  `statut` tinyint NOT NULL,
  `user` tinyint NOT NULL,
  `zip` tinyint NOT NULL,
  `id_dept` tinyint NOT NULL,
  `code_dept` tinyint NOT NULL,
  `town` tinyint NOT NULL,
  `fk_country` tinyint NOT NULL,
  `country_code` tinyint NOT NULL,
  `country_label` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_view_categorie
--
DROP TABLE IF EXISTS `llx_view_categorie`;
DROP VIEW IF EXISTS `llx_view_categorie`;
CREATE TABLE `llx_view_categorie` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_view_categorie_all
--
DROP TABLE IF EXISTS `llx_view_categorie_all`;
DROP VIEW IF EXISTS `llx_view_categorie_all`;
CREATE TABLE `llx_view_categorie_all` (
  `rowid` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `fk_parent` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `root` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `subroot` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `rootsubroot` tinyint NOT NULL,
  `level_1` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `level_2` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `level_3` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `level_4` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `level_5` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `leaf` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL,
  `childs` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_view_commandedet
--
DROP TABLE IF EXISTS `llx_view_commandedet`;
DROP VIEW IF EXISTS `llx_view_commandedet`;
CREATE TABLE `llx_view_commandedet` (
  `commandedet_rowid` tinyint NOT NULL,
  `commande_rowid` tinyint NOT NULL,
  `date_valid` tinyint NOT NULL,
  `date_valid_year` tinyint NOT NULL,
  `date_valid_quarter` tinyint NOT NULL,
  `date_valid_month` tinyint NOT NULL,
  `date_valid_day` tinyint NOT NULL,
  `id_user` tinyint NOT NULL,
  `id_societe` tinyint NOT NULL,
  `id_statut` tinyint NOT NULL,
  `facture` tinyint NOT NULL,
  `marge` tinyint NOT NULL,
  `marge_rand` tinyint NOT NULL,
  `price_ht` tinyint NOT NULL,
  `price_rand` tinyint NOT NULL,
  `buy_price_ht` tinyint NOT NULL,
  `prod_rowid` tinyint NOT NULL,
  `prod_ref` tinyint NOT NULL,
  `prod_label` tinyint NOT NULL,
  `id_prod_root` tinyint NOT NULL,
  `id_prod_subroot` tinyint NOT NULL,
  `id_prod_level_1` tinyint NOT NULL,
  `id_prod_level_2` tinyint NOT NULL,
  `id_prod_level_3` tinyint NOT NULL,
  `id_prod_level_4` tinyint NOT NULL,
  `id_prod_level_5` tinyint NOT NULL,
  `id_prod_leaf` tinyint NOT NULL,
  `id_entrepot` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_view_commandedet_flat
--
DROP TABLE IF EXISTS `llx_view_commandedet_flat`;
DROP VIEW IF EXISTS `llx_view_commandedet_flat`;
CREATE TABLE `llx_view_commandedet_flat` (
  `commandedet_rowid` tinyint NOT NULL,
  `commandedet_prodid` tinyint NOT NULL,
  `commandedet_prodqty` tinyint NOT NULL,
  `commande_rowid` tinyint NOT NULL,
  `commande_ref` tinyint NOT NULL,
  `date_valid` tinyint NOT NULL,
  `date_valid_year` tinyint NOT NULL,
  `date_valid_quarter` tinyint NOT NULL,
  `date_valid_month` tinyint NOT NULL,
  `date_valid_day` tinyint NOT NULL,
  `id_user` tinyint NOT NULL,
  `id_societe` tinyint NOT NULL,
  `id_statut` tinyint NOT NULL,
  `prod_rowid` tinyint NOT NULL,
  `prod_ref` tinyint NOT NULL,
  `id_leaf_1` tinyint NOT NULL,
  `id_leaf_2` tinyint NOT NULL,
  `id_leaf_3` tinyint NOT NULL,
  `id_leaf_4` tinyint NOT NULL,
  `id_leaf_5` tinyint NOT NULL,
  `id_leaf_6` tinyint NOT NULL,
  `id_leaf_7` tinyint NOT NULL,
  `id_leaf_8` tinyint NOT NULL,
  `id_leaf_9` tinyint NOT NULL,
  `id_leaf_10` tinyint NOT NULL,
  `id_leaf_11` tinyint NOT NULL,
  `id_leaf_12` tinyint NOT NULL,
  `id_leaf_13` tinyint NOT NULL,
  `id_leaf_14` tinyint NOT NULL,
  `id_leaf_15` tinyint NOT NULL,
  `id_leaf_16` tinyint NOT NULL,
  `id_leaf_17` tinyint NOT NULL,
  `facture` tinyint NOT NULL,
  `marge` tinyint NOT NULL,
  `total_ht` tinyint NOT NULL,
  `buy_price_ht` tinyint NOT NULL,
  `id_entrepot` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_view_facturedet
--
DROP TABLE IF EXISTS `llx_view_facturedet`;
DROP VIEW IF EXISTS `llx_view_facturedet`;
CREATE TABLE `llx_view_facturedet` (
  `facturedet_rowid` tinyint NOT NULL,
  `facture_rowid` tinyint NOT NULL,
  `date_valid` tinyint NOT NULL,
  `date_valid_year` tinyint NOT NULL,
  `date_valid_quarter` tinyint NOT NULL,
  `date_valid_month` tinyint NOT NULL,
  `date_valid_day` tinyint NOT NULL,
  `id_user` tinyint NOT NULL,
  `id_societe` tinyint NOT NULL,
  `id_statut` tinyint NOT NULL,
  `marge` tinyint NOT NULL,
  `marge_rand` tinyint NOT NULL,
  `price_ht` tinyint NOT NULL,
  `price_rand` tinyint NOT NULL,
  `buy_price_ht` tinyint NOT NULL,
  `prod_rowid` tinyint NOT NULL,
  `prod_ref` tinyint NOT NULL,
  `prod_label` tinyint NOT NULL,
  `id_prod_root` tinyint NOT NULL,
  `id_prod_subroot` tinyint NOT NULL,
  `id_prod_level_1` tinyint NOT NULL,
  `id_prod_level_2` tinyint NOT NULL,
  `id_prod_level_3` tinyint NOT NULL,
  `id_prod_level_4` tinyint NOT NULL,
  `id_prod_level_5` tinyint NOT NULL,
  `id_prod_leaf` tinyint NOT NULL,
  `id_entrepot` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_view_facturedet_flat
--
DROP TABLE IF EXISTS `llx_view_facturedet_flat`;
DROP VIEW IF EXISTS `llx_view_facturedet_flat`;
CREATE TABLE `llx_view_facturedet_flat` (
  `facturedet_rowid` tinyint NOT NULL,
  `facturedet_prodid` tinyint NOT NULL,
  `facturedet_prodqty` tinyint NOT NULL,
  `facture_rowid` tinyint NOT NULL,
  `facture_ref` tinyint NOT NULL,
  `date_valid` tinyint NOT NULL,
  `date_valid_year` tinyint NOT NULL,
  `date_valid_quarter` tinyint NOT NULL,
  `date_valid_month` tinyint NOT NULL,
  `date_valid_day` tinyint NOT NULL,
  `id_user` tinyint NOT NULL,
  `id_societe` tinyint NOT NULL,
  `id_statut` tinyint NOT NULL,
  `prod_rowid` tinyint NOT NULL,
  `prod_ref` tinyint NOT NULL,
  `id_leaf_1` tinyint NOT NULL,
  `id_leaf_2` tinyint NOT NULL,
  `id_leaf_3` tinyint NOT NULL,
  `id_leaf_4` tinyint NOT NULL,
  `id_leaf_5` tinyint NOT NULL,
  `id_leaf_6` tinyint NOT NULL,
  `id_leaf_7` tinyint NOT NULL,
  `id_leaf_8` tinyint NOT NULL,
  `id_leaf_9` tinyint NOT NULL,
  `id_leaf_10` tinyint NOT NULL,
  `id_leaf_11` tinyint NOT NULL,
  `id_leaf_12` tinyint NOT NULL,
  `id_leaf_13` tinyint NOT NULL,
  `id_leaf_14` tinyint NOT NULL,
  `id_leaf_15` tinyint NOT NULL,
  `id_leaf_16` tinyint NOT NULL,
  `id_leaf_17` tinyint NOT NULL,
  `marge` tinyint NOT NULL,
  `total_ht` tinyint NOT NULL,
  `buy_price_ht` tinyint NOT NULL,
  `id_entrepot` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_view_product_cat
--
DROP TABLE IF EXISTS `llx_view_product_cat`;
DROP VIEW IF EXISTS `llx_view_product_cat`;
CREATE TABLE `llx_view_product_cat` (
  `rowid` tinyint NOT NULL,
  `ref` tinyint NOT NULL,
  `label` tinyint NOT NULL,
  `price` tinyint NOT NULL,
  `price_rand` tinyint NOT NULL,
  `path` tinyint NOT NULL,
  `lvl` tinyint NOT NULL,
  `id_root` tinyint NOT NULL,
  `id_subroot` tinyint NOT NULL,
  `id_level_1` tinyint NOT NULL,
  `id_level_2` tinyint NOT NULL,
  `id_level_3` tinyint NOT NULL,
  `id_level_4` tinyint NOT NULL,
  `id_level_5` tinyint NOT NULL,
  `id_leaf` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_view_propaldet
--
DROP TABLE IF EXISTS `llx_view_propaldet`;
DROP VIEW IF EXISTS `llx_view_propaldet`;
CREATE TABLE `llx_view_propaldet` (
  `propaldet_rowid` tinyint NOT NULL,
  `propal_rowid` tinyint NOT NULL,
  `date_valid` tinyint NOT NULL,
  `date_valid_year` tinyint NOT NULL,
  `date_valid_quarter` tinyint NOT NULL,
  `date_valid_month` tinyint NOT NULL,
  `date_valid_day` tinyint NOT NULL,
  `id_user` tinyint NOT NULL,
  `id_societe` tinyint NOT NULL,
  `id_statut` tinyint NOT NULL,
  `marge` tinyint NOT NULL,
  `marge_rand` tinyint NOT NULL,
  `price_ht` tinyint NOT NULL,
  `price_rand` tinyint NOT NULL,
  `buy_price_ht` tinyint NOT NULL,
  `prod_rowid` tinyint NOT NULL,
  `prod_ref` tinyint NOT NULL,
  `prod_label` tinyint NOT NULL,
  `id_prod_root` tinyint NOT NULL,
  `id_prod_subroot` tinyint NOT NULL,
  `id_prod_level_1` tinyint NOT NULL,
  `id_prod_level_2` tinyint NOT NULL,
  `id_prod_level_3` tinyint NOT NULL,
  `id_prod_level_4` tinyint NOT NULL,
  `id_prod_level_5` tinyint NOT NULL,
  `id_prod_leaf` tinyint NOT NULL,
  `id_entrepot` tinyint NOT NULL
) ENGINE=MyISAM;
--
-- Temporary table structure for view llx_view_propaldet_flat
--
DROP TABLE IF EXISTS `llx_view_propaldet_flat`;
DROP VIEW IF EXISTS `llx_view_propaldet_flat`;
CREATE TABLE `llx_view_propaldet_flat` (
  `propaldet_rowid` tinyint NOT NULL,
  `propaldet_prodid` tinyint NOT NULL,
  `propaldet_prodqty` tinyint NOT NULL,
  `propal_rowid` tinyint NOT NULL,
  `propal_ref` tinyint NOT NULL,
  `date_valid` tinyint NOT NULL,
  `date_valid_year` tinyint NOT NULL,
  `date_valid_quarter` tinyint NOT NULL,
  `date_valid_month` tinyint NOT NULL,
  `date_valid_day` tinyint NOT NULL,
  `id_user` tinyint NOT NULL,
  `id_societe` tinyint NOT NULL,
  `id_statut` tinyint NOT NULL,
  `prod_rowid` tinyint NOT NULL,
  `prod_ref` tinyint NOT NULL,
  `id_leaf_1` tinyint NOT NULL,
  `id_leaf_2` tinyint NOT NULL,
  `id_leaf_3` tinyint NOT NULL,
  `id_leaf_4` tinyint NOT NULL,
  `id_leaf_5` tinyint NOT NULL,
  `id_leaf_6` tinyint NOT NULL,
  `id_leaf_7` tinyint NOT NULL,
  `id_leaf_8` tinyint NOT NULL,
  `id_leaf_9` tinyint NOT NULL,
  `id_leaf_10` tinyint NOT NULL,
  `id_leaf_11` tinyint NOT NULL,
  `id_leaf_12` tinyint NOT NULL,
  `id_leaf_13` tinyint NOT NULL,
  `id_leaf_14` tinyint NOT NULL,
  `id_leaf_15` tinyint NOT NULL,
  `id_leaf_16` tinyint NOT NULL,
  `id_leaf_17` tinyint NOT NULL,
  `marge` tinyint NOT NULL,
  `total_ht` tinyint NOT NULL,
  `buy_price_ht` tinyint NOT NULL,
  `id_entrepot` tinyint NOT NULL
) ENGINE=MyISAM;


--*********************************************

--
-- Final view structure for view llx_olap_view_categorie_leaf
--
DROP TABLE IF EXISTS `llx_olap_view_categorie_leaf`;
DROP VIEW IF EXISTS `llx_olap_view_categorie_leaf`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_categorie_leaf` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`lvl` > 1 
order by `llx_mat_view_categorie`.`root`,`llx_mat_view_categorie`.`leaf`);
--
-- Final view structure for view llx_olap_view_commande
--
DROP TABLE IF EXISTS `llx_olap_view_commande`;
DROP VIEW IF EXISTS `llx_olap_view_commande`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_commande` AS 
(select `llx_commande`.`rowid` AS `rowid`,
	`llx_commande`.`ref` AS `ref`,
	`llx_commande`.`date_valid` AS `date_valid`,
	`llx_commande`.`date_valid_year` AS `date_valid_year`,
	`llx_commande`.`date_valid_quarter` AS `date_valid_quarter`,
	`llx_commande`.`date_valid_month` AS `date_valid_month`,
	`llx_commande`.`date_valid_day` AS `date_valid_day`,
	`llx_commande`.`total_ht` AS `total_ht`,
	cast(`llx_commande_extrafields`.`entrepot` as unsigned) AS `id_entrepot`,
	`llx_commande`.`fk_user_author` AS `id_user`,
	`llx_commande`.`fk_soc` AS `id_societe`,
	sum(`llx_commandedet`.`total_ht` - `llx_commandedet`.`buy_price_ht` * `llx_commandedet`.`qty`) AS `total_marge` 
from ((`llx_commande` left join `llx_commande_extrafields` on(`llx_commande_extrafields`.`fk_object` = `llx_commande`.`rowid`)) 
		left join `llx_commandedet` on(`llx_commandedet`.`fk_commande` = `llx_commande`.`rowid`)) 
group by `llx_commande`.`ref`);
--
-- Final view structure for view llx_olap_view_consommable
--
DROP TABLE IF EXISTS `llx_olap_view_consommable`;
DROP VIEW IF EXISTS `llx_olap_view_consommable`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_consommable` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf`
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Gamme > Consommable%');
--
-- Final view structure for view llx_olap_view_facture
--
DROP TABLE IF EXISTS `llx_olap_view_facture`;
DROP VIEW IF EXISTS `llx_olap_view_facture`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_facture` AS 
(select `llx_facture`.`rowid` AS `rowid`,
	`llx_facture`.`facnumber` AS `ref`,
	`llx_facture`.`date_valid` AS `date_valid`,
	`llx_facture`.`date_valid_year` AS `date_valid_year`,
	`llx_facture`.`date_valid_quarter` AS `date_valid_quarter`,
	`llx_facture`.`date_valid_month` AS `date_valid_month`,
	`llx_facture`.`date_valid_day` AS `date_valid_day`,
	`llx_facture`.`total` AS `total_ht`,
	`llx_facture`.`type` AS `type`,
	cast(`llx_facture_extrafields`.`entrepot` as unsigned) AS `id_entrepot`,
	`llx_bs_sav`.`id_entrepot` AS `sav_entrepot`,
	`llx_facture`.`fk_user_author` AS `id_user`,
	`llx_facture`.`fk_soc` AS `id_societe`,
	sum(`llx_facturedet`.`total_ht` - `llx_facturedet`.`buy_price_ht` * `llx_facturedet`.`qty`) AS `total_marge` 
from (((`llx_facture` left join `llx_facture_extrafields` on(`llx_facture_extrafields`.`fk_object` = `llx_facture`.`rowid`)) 
		left join `llx_facturedet` on(`llx_facturedet`.`fk_facture` = `llx_facture`.`rowid`)) 
		left join `llx_bs_sav` on(`llx_bs_sav`.`id_facture` = `llx_facture`.`rowid`)) 
group by `llx_facture`.`facnumber`);
--
-- Final view structure for view llx_olap_view_logiciel_nature
--
DROP TABLE IF EXISTS `llx_olap_view_logiciel_nature`;
DROP VIEW IF EXISTS `llx_olap_view_logiciel_nature`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_logiciel_nature` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf`
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Gamme > Logiciel > Nature%');
--
-- Final view structure for view llx_olap_view_logiciel_type
--
DROP TABLE IF EXISTS `llx_olap_view_logiciel_type`;
DROP VIEW IF EXISTS `llx_olap_view_logiciel_type`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_logiciel_type` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Gamme > Logiciel > Type%');
--
-- Final view structure for view llx_olap_view_marque
--
DROP TABLE IF EXISTS `llx_olap_view_marque`;
DROP VIEW IF EXISTS `llx_olap_view_marque`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_marque` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Marque > Nature%');
--
-- Final view structure for view llx_olap_view_materiel_nature
--
DROP TABLE IF EXISTS `llx_olap_view_materiel_nature`;
DROP VIEW IF EXISTS `llx_olap_view_materiel_nature`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_materiel_nature` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Gamme > Matériel > Nature%');
--
-- Final view structure for view llx_olap_view_materiel_type
--
DROP TABLE IF EXISTS `llx_olap_view_materiel_type`;
DROP VIEW IF EXISTS `llx_olap_view_materiel_type`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_materiel_type` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Gamme > Matériel > Type%');
--
-- Final view structure for view llx_olap_view_modele
--
DROP TABLE IF EXISTS `llx_olap_view_modele`;
DROP VIEW IF EXISTS `llx_olap_view_modele`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_modele` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Modèle%');
--
-- Final view structure for view llx_olap_view_obsolescence
--
DROP TABLE IF EXISTS `llx_olap_view_obsolescence`;
DROP VIEW IF EXISTS `llx_olap_view_obsolescence`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_obsolescence` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Obsolescence > Nature%');
--
-- Final view structure for view llx_olap_view_produit_autre
--
DROP TABLE IF EXISTS `llx_olap_view_produit_autre`;
DROP VIEW IF EXISTS `llx_olap_view_produit_autre`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_produit_autre` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Gamme > Autre%');
--
-- Final view structure for view llx_olap_view_propal
--
DROP TABLE IF EXISTS `llx_olap_view_propal`;
DROP VIEW IF EXISTS `llx_olap_view_propal`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_propal` AS 
(select `llx_propal`.`rowid` AS `rowid`,
	`llx_propal`.`ref` AS `ref`,
	`llx_propal`.`date_valid` AS `date_valid`,
	`llx_propal`.`date_valid_year` AS `date_valid_year`,
	`llx_propal`.`date_valid_quarter` AS `date_valid_quarter`,
	`llx_propal`.`date_valid_month` AS `date_valid_month`,
	`llx_propal`.`date_valid_day` AS `date_valid_day`,
	`llx_propal`.`total_ht` AS `total_ht`,
	cast(`llx_propal_extrafields`.`entrepot` as unsigned) AS `propal_entrepot`,
	`llx_bs_sav`.`id_entrepot` AS `sav_entrepot`,
	`llx_propal`.`fk_user_author` AS `id_user`,
	`llx_propal`.`fk_soc` AS `id_societe`,
	sum(`llx_propaldet`.`total_ht` - `llx_propaldet`.`buy_price_ht` * `llx_propaldet`.`qty`) AS `total_marge` 
from (((`llx_propal` left join `llx_propal_extrafields` on(`llx_propal_extrafields`.`fk_object` = `llx_propal`.`rowid`)) 
		left join `llx_propaldet` on(`llx_propaldet`.`fk_propal` = `llx_propal`.`rowid`)) 
		left join `llx_bs_sav` on(`llx_bs_sav`.`id_propal` = `llx_propal`.`rowid`)) 
group by `llx_propal`.`ref`);
--
-- Final view structure for view llx_olap_view_recurr_period_nature
--
DROP TABLE IF EXISTS `llx_olap_view_recurr_period_nature`;
DROP VIEW IF EXISTS `llx_olap_view_recurr_period_nature`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_recurr_period_nature` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Recurrence > Périodique > Nature%');
--
-- Final view structure for view llx_olap_view_recurr_period_type
--
DROP TABLE IF EXISTS `llx_olap_view_recurr_period_type`;
DROP VIEW IF EXISTS `llx_olap_view_recurr_period_type`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_recurr_period_type` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Recurrence > Périodique > Type%');
--
-- Final view structure for view llx_olap_view_recurr_unique
--
DROP TABLE IF EXISTS `llx_olap_view_recurr_unique`;
DROP VIEW IF EXISTS `llx_olap_view_recurr_unique`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_recurr_unique` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Recurrence > Unique%');
--
-- Final view structure for view llx_olap_view_sav
--
DROP TABLE IF EXISTS `llx_olap_view_sav`;
DROP VIEW IF EXISTS `llx_olap_view_sav`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_sav` AS 
(select `llx_bs_sav`.`id` AS `id`,
	`llx_bs_sav`.`ref` AS `ref`,
	`llx_bs_sav`.`status` AS `status`,
	`llx_bs_sav`.`code_centre` AS `code_centre`,
	`llx_bs_sav`.`id_entrepot` AS `id_entrepot`,
	`llx_bs_sav`.`id_user_tech` AS `id_user`,
	`llx_bs_sav`.`id_client` AS `id_client`,
	`llx_bs_sav`.`date_create_year` AS `date_create_year`,
	`llx_bs_sav`.`date_create_quarter` AS `date_create_quarter`,
	`llx_bs_sav`.`date_create_month` AS `date_create_month`,
	`llx_bs_sav`.`date_create_day` AS `date_create_day` 
from `llx_bs_sav`);
--
-- Final view structure for view llx_olap_view_service_competence
--
DROP TABLE IF EXISTS `llx_olap_view_service_competence`;
DROP VIEW IF EXISTS `llx_olap_view_service_competence`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_service_competence` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Gamme > Service > Compétence%');
--
-- Final view structure for view llx_olap_view_service_nature
--
DROP TABLE IF EXISTS `llx_olap_view_service_nature`;
DROP VIEW IF EXISTS `llx_olap_view_service_nature`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_service_nature` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Gamme > Service > Nature%');
--
-- Final view structure for view llx_olap_view_service_type
--
DROP TABLE IF EXISTS `llx_olap_view_service_type`;
DROP VIEW IF EXISTS `llx_olap_view_service_type`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_service_type` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf`
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Gamme > Service > Type%');
--
-- Final view structure for view llx_olap_view_societe
--
DROP TABLE IF EXISTS `llx_olap_view_societe`;
DROP VIEW IF EXISTS `llx_olap_view_societe`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_societe` AS 
(select `llx_societe`.`rowid` AS `rowid`,
	`llx_societe`.`nom` AS `nom`,
	`llx_societe`.`code_client` AS `code`,
	`llx_societe`.`statut` AS `statut`,
	`llx_societe`.`status` AS `status`,
	`llx_societe`.`zip` AS `zip`,
	`llx_societe`.`id_dept` AS `id_dept`,
	case 
		when `llx_societe`.`id_dept` < 10 
			then concat('0',cast(`llx_societe`.`id_dept` as char charset utf8)) 
		else cast(`llx_societe`.`id_dept` as char charset utf8)
	end AS `code_dept`,
	`llx_societe`.`town` AS `town`,
	`llx_societe`.`fk_departement` AS `fk_dept`,
	`llx_societe`.`fk_pays` AS `fk_pays`,
	`llx_c_country`.`code` AS `country_code`,
	`llx_c_country`.`label` AS `country_label` 
from (`llx_societe` left join `llx_c_country` on(`llx_societe`.`fk_pays` = `llx_c_country`.`rowid`))) ;
--
-- Final view structure for view llx_olap_view_textile_type
--
DROP TABLE IF EXISTS `llx_olap_view_textile_type`;
DROP VIEW IF EXISTS `llx_olap_view_textile_type`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_textile_type` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from `llx_mat_view_categorie` 
where `llx_mat_view_categorie`.`path` like '%Gamme > Textile > Type%');
--
-- Final view structure for view llx_olap_view_time_commande
--
DROP TABLE IF EXISTS `llx_olap_view_time_commande`;
DROP VIEW IF EXISTS `llx_olap_view_time_commande`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_time_commande` AS 
(select `llx_commande`.`rowid` AS `rowid`,
	`llx_commande`.`date_valid_year` AS `date_valid_year`,
	`llx_commande`.`date_valid_quarter` AS `date_valid_quarter`,
	`llx_commande`.`date_valid_month` AS `date_valid_month`,
	`llx_commande`.`date_valid_day` AS `date_valid_day`,
	concat(`llx_commande`.`date_valid_year`,'-',`llx_olap_quarter`.`label`) AS `label_quarter`,
	concat(`llx_commande`.`date_valid_year`,'-',`llx_olap_month`.`code`) AS `code_month`,
	concat(`llx_olap_month`.`label`,' ',`llx_commande`.`date_valid_year`) AS `label_month` 
from ((`llx_commande` left join `llx_olap_quarter` on(`llx_commande`.`date_valid_quarter` = `llx_olap_quarter`.`id`)) 
		left join `llx_olap_month` on(`llx_commande`.`date_valid_month` = `llx_olap_month`.`id`)));
--
-- Final view structure for view llx_olap_view_time_facture
--
DROP TABLE IF EXISTS `llx_olap_view_time_facture`;
DROP VIEW IF EXISTS `llx_olap_view_time_facture`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_time_facture` AS 
(select `llx_facture`.`rowid` AS `rowid`,
	`llx_facture`.`date_valid_year` AS `date_valid_year`,
	`llx_facture`.`date_valid_quarter` AS `date_valid_quarter`,
	`llx_facture`.`date_valid_month` AS `date_valid_month`,
	`llx_facture`.`date_valid_day` AS `date_valid_day`,
	concat(`llx_facture`.`date_valid_year`,'-',`llx_olap_quarter`.`label`) AS `label_quarter`,
	concat(`llx_facture`.`date_valid_year`,'-',`llx_olap_month`.`code`) AS `code_month`,
	concat(`llx_olap_month`.`label`,' ',`llx_facture`.`date_valid_year`) AS `label_month` 
from ((`llx_facture` left join `llx_olap_quarter` on(`llx_facture`.`date_valid_quarter` = `llx_olap_quarter`.`id`)) 
		left join `llx_olap_month` on(`llx_facture`.`date_valid_month` = `llx_olap_month`.`id`)));
--
-- Final view structure for view llx_olap_view_time_propal
--
DROP TABLE IF EXISTS `llx_olap_view_time_propal`;
DROP VIEW IF EXISTS `llx_olap_view_time_propal`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_time_propal` AS 
(select `llx_propal`.`rowid` AS `rowid`,
	`llx_propal`.`date_valid_year` AS `date_valid_year`,
	`llx_propal`.`date_valid_quarter` AS `date_valid_quarter`,
	`llx_propal`.`date_valid_month` AS `date_valid_month`,
	`llx_propal`.`date_valid_day` AS `date_valid_day`,
	concat(`llx_propal`.`date_valid_year`,'-',`llx_olap_quarter`.`label`) AS `label_quarter`,
	concat(`llx_propal`.`date_valid_year`,'-',`llx_olap_month`.`code`) AS `code_month`,
	concat(`llx_olap_month`.`label`,' ',`llx_propal`.`date_valid_year`) AS `label_month` 
from ((`llx_propal` left join `llx_olap_quarter` on(`llx_propal`.`date_valid_quarter` = `llx_olap_quarter`.`id`)) 
		left join `llx_olap_month` on(`llx_propal`.`date_valid_month` = `llx_olap_month`.`id`)));
--
-- Final view structure for view llx_olap_view_time_sav
--
DROP TABLE IF EXISTS `llx_olap_view_time_sav`;
DROP VIEW IF EXISTS `llx_olap_view_time_sav`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_time_sav` AS 
(select `llx_bs_sav`.`id` AS `id`,
	`llx_bs_sav`.`date_create_year` AS `date_create_year`,
	`llx_bs_sav`.`date_create_quarter` AS `date_create_quarter`,
	`llx_bs_sav`.`date_create_month` AS `date_create_month`,
	`llx_bs_sav`.`date_create_day` AS `date_create_day`,
	`llx_olap_quarter`.`label` AS `label_quarter`,
	`llx_olap_month`.`code` AS `code_month`,
	`llx_olap_month`.`label` AS `label_month` 
from ((`llx_bs_sav` left join `llx_olap_quarter` on(`llx_bs_sav`.`date_create_quarter` = `llx_olap_quarter`.`id`)) 
		left join `llx_olap_month` on(`llx_bs_sav`.`date_create_month` = `llx_olap_month`.`id`)));
--
-- Final view structure for view llx_olap_view_transport
--
DROP TABLE IF EXISTS `llx_olap_view_transport`;
DROP VIEW IF EXISTS `llx_olap_view_transport`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_transport` AS 
(select `llx_mat_view_categorie`.`rowid` AS `rowid`,
	`llx_mat_view_categorie`.`type` AS `type`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`root` AS `root`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`subroot` AS `subroot`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
	`llx_mat_view_categorie`.`level_1` AS `level_1`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`level_2` AS `level_2`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`level_3` AS `level_3`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`level_4` AS `level_4`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`level_5` AS `level_5`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`leaf` AS `leaf`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from `llx_mat_view_categorie` where `llx_mat_view_categorie`.`path` like '%Gamme > Transport%');
--
-- Final view structure for view llx_olap_view_gamme
--
DROP TABLE IF EXISTS `llx_olap_view_gamme`;
DROP VIEW IF EXISTS `llx_olap_view_gamme`;
CREATE 
    ALGORITHM = UNDEFINED 
    DEFINER = `root`@`%` 
    SQL SECURITY DEFINER
VIEW `llx_olap_view_gamme` AS
    SELECT 
        `llx_mat_view_categorie`.`rowid` AS `rowid`,
        `llx_mat_view_categorie`.`type` AS `type`,
        `llx_mat_view_categorie`.`path` AS `path`,
        `llx_mat_view_categorie`.`lvl` AS `lvl`,
        `llx_mat_view_categorie`.`root` AS `root`,
        `llx_mat_view_categorie`.`id_root` AS `id_root`,
        `llx_mat_view_categorie`.`subroot` AS `subroot`,
        `llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
        `llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
        `llx_mat_view_categorie`.`level_1` AS `level_1`,
        `llx_mat_view_categorie`.`id_level_1` AS `id_level_1`
    FROM
        `llx_mat_view_categorie`
    WHERE
        `llx_mat_view_categorie`.`path` LIKE '%Gamme >%'
    GROUP BY `llx_mat_view_categorie`.`id_level_1`;
--
-- Final view structure for view llx_olap_view_recurr
--
DROP TABLE IF EXISTS `llx_olap_view_recurr`;
DROP VIEW IF EXISTS `llx_olap_view_recurr`;
CREATE 
    ALGORITHM = UNDEFINED 
    DEFINER = `root`@`%` 
    SQL SECURITY DEFINER
VIEW `llx_olap_view_recurr` AS
    SELECT 
        `llx_mat_view_categorie`.`rowid` AS `rowid`,
        `llx_mat_view_categorie`.`type` AS `type`,
        `llx_mat_view_categorie`.`path` AS `path`,
        `llx_mat_view_categorie`.`lvl` AS `lvl`,
        `llx_mat_view_categorie`.`root` AS `root`,
        `llx_mat_view_categorie`.`id_root` AS `id_root`,
        `llx_mat_view_categorie`.`subroot` AS `subroot`,
        `llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
        `llx_mat_view_categorie`.`rootsubroot` AS `rootsubroot`,
        `llx_mat_view_categorie`.`level_1` AS `level_1`,
        `llx_mat_view_categorie`.`id_level_1` AS `id_level_1`
    FROM
        `llx_mat_view_categorie`
    WHERE
        `llx_mat_view_categorie`.`path` LIKE '%Recurrence >%'
    GROUP BY `llx_mat_view_categorie`.`id_level_1`;
--
-- Final view structure for view llx_olap_view_user
--
DROP TABLE IF EXISTS `llx_olap_view_user`;
DROP VIEW IF EXISTS `llx_olap_view_user`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_user` AS 
(select `llx_user`.`rowid` AS `rowid`,
	`llx_user`.`fk_user` AS `fk_user`,
	case 
		when `llx_user`.`gender` is null 
			then 'undefined' 
		else `llx_user`.`gender` 
		end AS `gender`,
	`llx_user`.`login` AS `login`,
	case 
		when `llx_user`.`statut` = 0 
			then 'Desactive' 
		else 'Actif' 
		end AS `statut`,
	concat(`llx_user`.`firstname`,' ',`llx_user`.`lastname`) AS `user`,
	`llx_user`.`zip` AS `zip`,
	`llx_user`.`id_dept` AS `id_dept`,
	case 
		when `llx_user`.`id_dept` < 10 
			then concat('0',cast(`llx_user`.`id_dept` as char charset utf8)) 
		else cast(`llx_user`.`id_dept` as char charset utf8) 
		end AS `code_dept`,
	`llx_user`.`town` AS `town`,
	`llx_user`.`fk_country` AS `fk_country`,
	`llx_c_country`.`code` AS `country_code`,
	`llx_c_country`.`label` AS `country_label` 
from (`llx_user` left join `llx_c_country` on(`llx_user`.`fk_country` = `llx_c_country`.`rowid`)));
--
-- Final view structure for view llx_view_categorie
--
DROP TABLE IF EXISTS `llx_view_categorie`;
DROP VIEW IF EXISTS `llx_view_categorie`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_view_categorie` AS 
(select `vc1`.`rowid` AS `rowid`,
	`vc1`.`type` AS `type`,
	`vc1`.`path` AS `path`,
	`vc1`.`lvl` AS `lvl`,
	`vc1`.`root` AS `root`,
	`vc1`.`id_root` AS `id_root`,
	`vc1`.`subroot` AS `subroot`,
	`vc1`.`id_subroot` AS `id_subroot`,
	`vc1`.`rootsubroot` AS `rootsubroot`,
	`vc1`.`level_1` AS `level_1`,
	`vc1`.`id_level_1` AS `id_level_1`,
	`vc1`.`level_2` AS `level_2`,
	`vc1`.`id_level_2` AS `id_level_2`,
	`vc1`.`level_3` AS `level_3`,
	`vc1`.`id_level_3` AS `id_level_3`,
	`vc1`.`level_4` AS `level_4`,
	`vc1`.`id_level_4` AS `id_level_4`,
	`vc1`.`level_5` AS `level_5`,
	`vc1`.`id_level_5` AS `id_level_5`,
	`vc1`.`leaf` AS `leaf`,
	`vc1`.`id_leaf` AS `id_leaf` 
from `llx_view_categorie_all` `vc1` 
where `vc1`.`childs` = 0 order by `vc1`.`path`);
--
-- Final view structure for view llx_view_categorie_all
--
DROP TABLE IF EXISTS `llx_view_categorie_all`;
DROP VIEW IF EXISTS `llx_view_categorie_all`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_view_categorie_all` AS 
with recursive category_path as 
(select `llx_categorie`.`rowid` AS `rowid`,
	`llx_categorie`.`type` AS `type`,
	`llx_categorie`.`rowid` AS `id_root`,
	`llx_categorie`.`fk_parent` AS `fk_parent`,
	`llx_categorie`.`label` AS `path`,
	0 AS `lvl`,
	`llx_categorie`.`label` AS `root`,
	space(254) AS `subroot`,
	0 AS `id_subroot`,
	space(254) AS `rootsubroot`,
	space(254) AS `level_1`,
	0 AS `id_level_1`,
	space(254) AS `level_2`,
	0 AS `id_level_2`,
	space(254) AS `level_3`,
	0 AS `id_level_3`,
	space(254) AS `level_4`,
	0 AS `id_level_4`,
	space(254) AS `level_5`,
	0 AS `id_level_5`,
	`llx_categorie`.`label` AS `leaf`,
	`llx_categorie`.`rowid` AS `id_leaf` 
from `llx_categorie` 
where `llx_categorie`.`fk_parent` = 0 union all 
	select `llx_categorie`.`rowid` AS `rowid`,
		`llx_categorie`.`type` AS `type`,
		`category_path`.`id_root` AS `id_root`,
		`llx_categorie`.`fk_parent` AS `fk_parent`,
		concat(`category_path`.`path`,' > ',`llx_categorie`.`label`) AS `path`,`
		category_path`.`lvl` + 1 AS `lvl`,`category_path`.`root` AS `root`,
		case 
			when `category_path`.`lvl` = 0 
				then `llx_categorie`.`label` 
			else `category_path`.`subroot` 
			end AS `subroot`,
		case 
			when `category_path`.`lvl` = 0 
				then `llx_categorie`.`rowid` 
			else `category_path`.`id_subroot` 
			end AS `id_subroot`,
		case 
			when `category_path`.`lvl` = 0 
				then concat(`category_path`.`path`,' > ',`llx_categorie`.`label`) 
			else `category_path`.`rootsubroot` 
			end AS `rootsubroot`,
		case 
			when `category_path`.`lvl` = 1 
				then `llx_categorie`.`label` 
			else `category_path`.`level_1` 
			end AS `level_1`,
		case 
			when `category_path`.`lvl` = 1 
				then `llx_categorie`.`rowid` 
			else `category_path`.`id_level_1` 
		end AS `id_level_1`,
		case 
			when `category_path`.`lvl` = 2 
				then `llx_categorie`.`label` 
			else `category_path`.`level_2` 
		end AS `level_2`,
		case 
			when `category_path`.`lvl` = 2 
				then `llx_categorie`.`rowid` 
			else `category_path`.`id_level_2` 
			end AS `id_level_2`,
		case 
			when `category_path`.`lvl` = 3 
				then `llx_categorie`.`label` 
			else `category_path`.`level_3` 
			end AS `level_3`,
		case 
			when `category_path`.`lvl` = 3 
				then `llx_categorie`.`rowid`
			else `category_path`.`id_level_3` 
			end AS `id_level_3`,
		case 
			when `category_path`.`lvl` = 4 
				then `llx_categorie`.`label` 
			else `category_path`.`level_4` 
			end AS `level_4`,
		case 
			when `category_path`.`lvl` = 4 
				then `llx_categorie`.`rowid` 
			else `category_path`.`id_level_4` 
			end AS `id_level_4`,
		case 
			when `category_path`.`lvl` = 5 
				then `llx_categorie`.`label` 
			else `category_path`.`level_5` 
			end AS `level_5`,
		case 
			when `category_path`.`lvl` = 5 
				then `llx_categorie`.`rowid` 
			else `category_path`.`id_level_5` 
			end AS `id_level_5`,
		`llx_categorie`.`label` AS `leaf`,
		`llx_categorie`.`rowid` AS `id_leaf` 
	from (`category_path` join `llx_categorie` on(`category_path`.`rowid` = `llx_categorie`.`fk_parent`)))
	select `category_path`.`rowid` AS `rowid`,
		`category_path`.`type` AS `type`,
		`category_path`.`fk_parent` AS `fk_parent`,
		`category_path`.`path` AS `path`,
		`category_path`.`lvl` AS `lvl`,
		`category_path`.`root` AS `root`,
		`category_path`.`id_root` AS `id_root`,
		ltrim(`category_path`.`subroot`) AS `subroot`,
		`category_path`.`id_subroot` AS `id_subroot`,
		ltrim(`category_path`.`rootsubroot`) AS `rootsubroot`,
		ltrim(`category_path`.`level_1`) AS `level_1`,
		`category_path`.`id_level_1` AS `id_level_1`,
		ltrim(`category_path`.`level_2`) AS `level_2`,
		`category_path`.`id_level_2` AS `id_level_2`,
		ltrim(`category_path`.`level_3`) AS `level_3`,
		`category_path`.`id_level_3` AS `id_level_3`,
		ltrim(`category_path`.`level_4`) AS `level_4`,
		`category_path`.`id_level_4` AS `id_level_4`,
		ltrim(`category_path`.`level_5`) AS `level_5`,
		`category_path`.`id_level_5` AS `id_level_5`,
		`category_path`.`leaf` AS `leaf`,
		`category_path`.`id_leaf` AS `id_leaf`,
		count(`cp`.`rowid`) AS `childs` 
	from (`category_path` left join `category_path` `cp` on(`cp`.`fk_parent` = `category_path`.`rowid`)) 
	group by `category_path`.`rowid` order by `category_path`.`path`;
--
-- Final view structure for view llx_view_commandedet
--
DROP TABLE IF EXISTS `llx_view_commandedet`;
DROP VIEW IF EXISTS `llx_view_commandedet`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_view_commandedet` AS 
(select `llx_commandedet`.`rowid` AS `commandedet_rowid`,
	`llx_commande`.`rowid` AS `commande_rowid`,
	`llx_commande`.`date_valid` AS `date_valid`,
	`llx_commande`.`date_valid_year` AS `date_valid_year`,
	`llx_commande`.`date_valid_quarter` AS `date_valid_quarter`,
	`llx_commande`.`date_valid_month` AS `date_valid_month`,
	`llx_commande`.`date_valid_day` AS `date_valid_day`,
	`llx_commande`.`fk_user_author` AS `id_user`,`llx_commande`.
	`fk_soc` AS `id_societe`,
	`llx_commande`.`fk_statut` AS `id_statut`,
	case 
		when `llx_commande`.`facture` = 0 
			then 'NON' 
		when `llx_commande`.`facture` = 1 
			then 'OUI' 
		else 'PART' 
		end AS `facture`,
	`llx_commandedet`.`total_ht` - `llx_commandedet`.`buy_price_ht` * `llx_commandedet`.`qty` AS `marge`,
	case 
		when `llx_commandedet`.`total_ht` = 0 
			then 0 
		else `llx_commandedet`.`total_ht`+floor(1+rand(`llx_commandedet`.`rowid`)*1E+6)/1E+11-`llx_commandedet`.`buy_price_ht`*`llx_commandedet`.`qty`
		end AS `marge_rand`,
	`llx_commandedet`.`total_ht` AS `price_ht`,
	case 
		when `llx_commandedet`.`total_ht` = 0 
			then 0 
		else `llx_commandedet`.`total_ht`+floor(1+rand(`llx_commandedet`.`rowid`)*1E+6)/1E+11 
		end AS `price_rand`,
	`llx_commandedet`.`buy_price_ht` AS `buy_price_ht`,
	`llx_view_product_cat`.`rowid` AS `prod_rowid`,
	`llx_view_product_cat`.`ref` AS `prod_ref`,
	`llx_view_product_cat`.`label` AS `prod_label`,
	case 
		when `llx_view_product_cat`.`id_root` is null 
			then 0 
		else `llx_view_product_cat`.`id_root` 
		end AS `id_prod_root`,
	case 
		when `llx_view_product_cat`.`id_subroot` is null 
			then 0
		else `llx_view_product_cat`.`id_subroot` 
		end AS `id_prod_subroot`,
	case 
		when `llx_view_product_cat`.`id_level_1` is null 
			then 0 
		else `llx_view_product_cat`.`id_level_1` 
		end AS `id_prod_level_1`,
	case 
		when `llx_view_product_cat`.`id_level_2` is null 
			then 0 
		else `llx_view_product_cat`.`id_level_2` 
		end AS `id_prod_level_2`,
	case 
		when `llx_view_product_cat`.`id_level_3` is null 
			then 0 
		else `llx_view_product_cat`.`id_level_3` 
		end AS `id_prod_level_3`,
	case 
		when `llx_view_product_cat`.`id_level_4` is null 
			then 0 
		else `llx_view_product_cat`.`id_level_4` 
		end AS `id_prod_level_4`,
	case 
		when `llx_view_product_cat`.`id_level_5` is null 
			then 0 
		else `llx_view_product_cat`.`id_level_5` 
		end AS `id_prod_level_5`,
	case 
		when `llx_view_product_cat`.`id_leaf` is null 
			then 0
		else `llx_view_product_cat`.`id_leaf` 
		end AS `id_prod_leaf`,
	case 
		when `llx_commande_extrafields`.`entrepot` is not null 
			then cast(`llx_commande_extrafields`.`entrepot` as unsigned) 
		else 0 
		end AS `id_entrepot` 
from (((`llx_commandedet` left join `llx_view_product_cat` on(`llx_commandedet`.`fk_product` = `llx_view_product_cat`.`rowid`)) 
		left join `llx_commande` on(`llx_commandedet`.`fk_commande` = `llx_commande`.`rowid`)) 
		left join `llx_commande_extrafields` on(`llx_commande_extrafields`.`fk_object` = `llx_commandedet`.`fk_commande`)) 
where `llx_commandedet`.`total_ht` is not null 
	and `llx_commandedet`.`total_ht` <> 0 
	and `llx_commandedet`.`buy_price_ht` is not null 
	and `llx_commande`.`fk_statut` > 0);
--
-- Final view structure for view llx_view_commandedet_flat
--
DROP TABLE IF EXISTS `llx_view_commandedet_flat`;
DROP VIEW IF EXISTS `llx_view_commandedet_flat`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_view_commandedet_flat` AS 
(select `llx_commandedet`.`rowid` AS `commandedet_rowid`,
	`llx_commandedet`.`fk_product` AS `commandedet_prodid`,
	`llx_commandedet`.`qty` AS `commandedet_prodqty`,
	`llx_commande`.`rowid` AS `commande_rowid`,
	`llx_commande`.`ref` AS `commande_ref`,
	`llx_commande`.`date_valid` AS `date_valid`,
	`llx_commande`.`date_valid_year` AS `date_valid_year`,
	`llx_commande`.`date_valid_quarter` AS `date_valid_quarter`,
	`llx_commande`.`date_valid_month` AS `date_valid_month`,
	`llx_commande`.`date_valid_day` AS `date_valid_day`,
	`llx_commande`.`fk_user_author` AS `id_user`,
	`llx_commande`.`fk_soc` AS `id_societe`,
	`llx_commande`.`fk_statut` AS `id_statut`,
	`llx_mat_view_product_cat_flat`.`rowid` AS `prod_rowid`,
	`llx_mat_view_product_cat_flat`.`ref` AS `prod_ref`,
	`llx_mat_view_product_cat_flat`.`id_leaf_1` AS `id_leaf_1`,
	`llx_mat_view_product_cat_flat`.`id_leaf_2` AS `id_leaf_2`,
	`llx_mat_view_product_cat_flat`.`id_leaf_3` AS `id_leaf_3`,
	`llx_mat_view_product_cat_flat`.`id_leaf_4` AS `id_leaf_4`,
	`llx_mat_view_product_cat_flat`.`id_leaf_5` AS `id_leaf_5`,
	`llx_mat_view_product_cat_flat`.`id_leaf_6` AS `id_leaf_6`,
	`llx_mat_view_product_cat_flat`.`id_leaf_7` AS `id_leaf_7`,
	`llx_mat_view_product_cat_flat`.`id_leaf_8` AS `id_leaf_8`,
	`llx_mat_view_product_cat_flat`.`id_leaf_9` AS `id_leaf_9`,
	`llx_mat_view_product_cat_flat`.`id_leaf_10` AS `id_leaf_10`,
	`llx_mat_view_product_cat_flat`.`id_leaf_11` AS `id_leaf_11`,
	`llx_mat_view_product_cat_flat`.`id_leaf_12` AS `id_leaf_12`,
	`llx_mat_view_product_cat_flat`.`id_leaf_13` AS `id_leaf_13`,
	`llx_mat_view_product_cat_flat`.`id_leaf_14` AS `id_leaf_14`,
	`llx_mat_view_product_cat_flat`.`id_leaf_15` AS `id_leaf_15`,
	`llx_mat_view_product_cat_flat`.`id_leaf_16` AS `id_leaf_16`,
	`llx_mat_view_product_cat_flat`.`id_leaf_17` AS `id_leaf_17`,
	case 
		when `llx_commande`.`facture` = 0 
			then 'NON' 
		when `llx_commande`.`facture` = 1 
			then 'OUI' 
		else 'PART' 
		end AS `facture`,
	`llx_commandedet`.`total_ht` - `llx_commandedet`.`buy_price_ht` * `llx_commandedet`.`qty` AS `marge`,
	`llx_commandedet`.`total_ht` AS `total_ht`,
	`llx_commandedet`.`buy_price_ht` AS `buy_price_ht`,
	case 
		when `llx_commande_extrafields`.`entrepot` is not null 
			then cast(`llx_commande_extrafields`.`entrepot` as unsigned) 
		else 0 
		end AS `id_entrepot` 
from (((`llx_commandedet` left join `llx_mat_view_product_cat_flat` on(`llx_commandedet`.`fk_product` = `llx_mat_view_product_cat_flat`.`rowid`)) 
		left join `llx_commande` on(`llx_commandedet`.`fk_commande` = `llx_commande`.`rowid`)) 
		left join `llx_commande_extrafields` on(`llx_commande_extrafields`.`fk_object` = `llx_commandedet`.`fk_commande`)) 
where `llx_commandedet`.`total_ht` is not null 
	and `llx_commandedet`.`fk_product` is not null 
	and `llx_mat_view_product_cat_flat`.`ref` is not null 
	and `llx_commandedet`.`total_ht` <> 0 
	and `llx_commandedet`.`buy_price_ht` is not null 
	and `llx_commande`.`fk_statut` > 0);
--
-- Final view structure for view llx_view_facturedet
--
DROP TABLE IF EXISTS `llx_view_facturedet`;
DROP VIEW IF EXISTS `llx_view_facturedet`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_view_facturedet` AS 
(select `llx_facturedet`.`rowid` AS `facturedet_rowid`,
	`llx_facture`.`rowid` AS `facture_rowid`,
	`llx_facture`.`date_valid` AS `date_valid`,
	`llx_facture`.`date_valid_year` AS `date_valid_year`,
	`llx_facture`.`date_valid_quarter` AS `date_valid_quarter`,
	`llx_facture`.`date_valid_month` AS `date_valid_month`,
	`llx_facture`.`date_valid_day` AS `date_valid_day`,
	`llx_facture`.`fk_user_author` AS `id_user`,
	`llx_facture`.`fk_soc` AS `id_societe`,
	`llx_facture`.`fk_statut` AS `id_statut`,
	`llx_facturedet`.`total_ht` - `llx_facturedet`.`buy_price_ht` * `llx_facturedet`.`qty` AS `marge`,
	case 
		when `llx_facturedet`.`total_ht` = 0 
			then 0 
		else `llx_facturedet`.`total_ht`+floor(1+rand(`llx_facturedet`.`rowid`)*1E+6)/1E+11-`llx_facturedet`.`buy_price_ht`* llx_facturedet`.`qty` 
		end AS `marge_rand`,
	`llx_facturedet`.`total_ht` AS `price_ht`,
	case 
		when `llx_facturedet`.`total_ht` = 0 
			then 0 
		else `llx_facturedet`.`total_ht`+floor(1+rand(`llx_facturedet`.`rowid`)*1E+6)/1E+11 
		end AS `price_rand`,
	`llx_facturedet`.`buy_price_ht` AS `buy_price_ht`,
	`llx_view_product_cat`.`rowid` AS `prod_rowid`,
	`llx_view_product_cat`.`ref` AS `prod_ref`,
	`llx_view_product_cat`.`label` AS `prod_label`,
	case 
		when `llx_view_product_cat`.`id_root` is null 
			then 0 
		else `llx_view_product_cat`.`id_root` 
		end AS `id_prod_root`,
	case 
		when `llx_view_product_cat`.`id_subroot` is null 
			then 0 
		else `llx_view_product_cat`.`id_subroot` 
		end AS `id_prod_subroot`,
	case 
		when `llx_view_product_cat`.`id_level_1` is null 
			then 0 
		else `llx_view_product_cat`.`id_level_1` 
		end AS `id_prod_level_1`,
	case 
		when `llx_view_product_cat`.`id_level_2` is null 
			then 0 
		else `llx_view_product_cat`.`id_level_2` 
		end AS `id_prod_level_2`,
	case 
		when `llx_view_product_cat`.`id_level_3` is null 
			then 0 
		else `llx_view_product_cat`.`id_level_3` 
		end AS `id_prod_level_3`,
	case 
		when `llx_view_product_cat`.`id_level_4` is null 
			then 0 
		else `llx_view_product_cat`.`id_level_4` 
		end AS `id_prod_level_4`,
	case 
		when `llx_view_product_cat`.`id_level_5` is null 
			then 0 
		else `llx_view_product_cat`.`id_level_5` 
		end AS `id_prod_level_5`,
	case 
		when `llx_view_product_cat`.`id_leaf` is null 
			then 0 
		else `llx_view_product_cat`.`id_leaf` 
		end AS `id_prod_leaf`,
	cast(`llx_facture_extrafields`.`entrepot` as unsigned) AS `id_entrepot` 
from (((`llx_facturedet` left join `llx_view_product_cat` on(`llx_facturedet`.`fk_product` = `llx_view_product_cat`.`rowid`)) 
		left join `llx_facture` on(`llx_facturedet`.`fk_facture` = `llx_facture`.`rowid`)) 
		left join `llx_facture_extrafields` on(`llx_facture_extrafields`.`fk_object` = `llx_facturedet`.`fk_facture`)) 
where `llx_facturedet`.`total_ht` is not null 
	and `llx_facturedet`.`total_ht` <> 0 
	and `llx_facturedet`.`buy_price_ht` is not null 
	and `llx_facture`.`fk_statut` > 0);
--
-- Final view structure for view llx_view_facturedet_flat
--
DROP TABLE IF EXISTS `llx_view_facturedet_flat`;
DROP VIEW IF EXISTS `llx_view_facturedet_flat`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_view_facturedet_flat` AS 
(select `llx_facturedet`.`rowid` AS `facturedet_rowid`,
	`llx_facturedet`.`fk_product` AS `facturedet_prodid`,
	`llx_facturedet`.`qty` AS `facturedet_prodqty`,
	`llx_facture`.`rowid` AS `facture_rowid`,
	`llx_facture`.`facnumber` AS `facture_ref`,
	`llx_facture`.`date_valid` AS `date_valid`,
	`llx_facture`.`date_valid_year` AS `date_valid_year`,
	`llx_facture`.`date_valid_quarter` AS `date_valid_quarter`,
	`llx_facture`.`date_valid_month` AS `date_valid_month`,
	`llx_facture`.`date_valid_day` AS `date_valid_day`,
	`llx_facture`.`fk_user_author` AS `id_user`,
	`llx_facture`.`fk_soc` AS `id_societe`,
	`llx_facture`.`fk_statut` AS `id_statut`,
	`llx_mat_view_product_cat_flat`.`rowid` AS `prod_rowid`,
	`llx_mat_view_product_cat_flat`.`ref` AS `prod_ref`,
	`llx_mat_view_product_cat_flat`.`id_leaf_1` AS `id_leaf_1`,
	`llx_mat_view_product_cat_flat`.`id_leaf_2` AS `id_leaf_2`,
	`llx_mat_view_product_cat_flat`.`id_leaf_3` AS `id_leaf_3`,
	`llx_mat_view_product_cat_flat`.`id_leaf_4` AS `id_leaf_4`,
	`llx_mat_view_product_cat_flat`.`id_leaf_5` AS `id_leaf_5`,
	`llx_mat_view_product_cat_flat`.`id_leaf_6` AS `id_leaf_6`,
	`llx_mat_view_product_cat_flat`.`id_leaf_7` AS `id_leaf_7`,
	`llx_mat_view_product_cat_flat`.`id_leaf_8` AS `id_leaf_8`,
	`llx_mat_view_product_cat_flat`.`id_leaf_9` AS `id_leaf_9`,
	`llx_mat_view_product_cat_flat`.`id_leaf_10` AS `id_leaf_10`,
	`llx_mat_view_product_cat_flat`.`id_leaf_11` AS `id_leaf_11`,
	`llx_mat_view_product_cat_flat`.`id_leaf_12` AS `id_leaf_12`,
	`llx_mat_view_product_cat_flat`.`id_leaf_13` AS `id_leaf_13`,
	`llx_mat_view_product_cat_flat`.`id_leaf_14` AS `id_leaf_14`,
	`llx_mat_view_product_cat_flat`.`id_leaf_15` AS `id_leaf_15`,
	`llx_mat_view_product_cat_flat`.`id_leaf_16` AS `id_leaf_16`,
	`llx_mat_view_product_cat_flat`.`id_leaf_17` AS `id_leaf_17`,
	`llx_facturedet`.`total_ht` - `llx_facturedet`.`buy_price_ht` * `llx_facturedet`.`qty` AS `marge`,
	`llx_facturedet`.`total_ht` AS `total_ht`,
	`llx_facturedet`.`buy_price_ht` AS `buy_price_ht`,
	case 
		when `llx_facture_extrafields`.`entrepot` is not null 
			then cast(`llx_facture_extrafields`.`entrepot` as unsigned) 
			else 0 
			end AS `id_entrepot` 
from (((`llx_facturedet` left join `llx_mat_view_product_cat_flat` on(`llx_facturedet`.`fk_product` = `llx_mat_view_product_cat_flat`.`rowid`)) 
		left join `llx_facture` on(`llx_facturedet`.`fk_facture` = `llx_facture`.`rowid`)) 
		left join `llx_facture_extrafields` on(`llx_facture_extrafields`.`fk_object` = `llx_facturedet`.`fk_facture`)) 
where `llx_facturedet`.`total_ht` is not null 
	and `llx_facturedet`.`total_ht` <> 0 
	and `llx_facturedet`.`buy_price_ht` is not null 
	and `llx_facture`.`fk_statut` > 0);
--
-- Final view structure for view llx_view_product_cat
--
DROP TABLE IF EXISTS `llx_view_product_cat`;
DROP VIEW IF EXISTS `llx_view_product_cat`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_view_product_cat` AS 
(select `llx_product`.`rowid` AS `rowid`,
	`llx_product`.`ref` AS `ref`,
	`llx_product`.`label` AS `label`,
	`llx_product`.`price` AS `price`,
	case 
		when `llx_product`.`price` = 0 
			then 0 
		else `llx_product`.`price`+floor(1+rand(`llx_product`.`rowid`)*1E+6)/1E+11 
		end AS `price_rand`,
	`llx_mat_view_categorie`.`path` AS `path`,
	`llx_mat_view_categorie`.`lvl` AS `lvl`,
	`llx_mat_view_categorie`.`id_root` AS `id_root`,
	`llx_mat_view_categorie`.`id_subroot` AS `id_subroot`,
	`llx_mat_view_categorie`.`id_level_1` AS `id_level_1`,
	`llx_mat_view_categorie`.`id_level_2` AS `id_level_2`,
	`llx_mat_view_categorie`.`id_level_3` AS `id_level_3`,
	`llx_mat_view_categorie`.`id_level_4` AS `id_level_4`,
	`llx_mat_view_categorie`.`id_level_5` AS `id_level_5`,
	`llx_mat_view_categorie`.`id_leaf` AS `id_leaf` 
from ((`llx_product` join `llx_categorie_product` on(`llx_product`.`rowid` = `llx_categorie_product`.`fk_product`)) 
		join `llx_mat_view_categorie` on(`llx_mat_view_categorie`.`rowid` = `llx_categorie_product`.`fk_categorie`)));
--
-- Final view structure for view llx_view_propaldet
--
DROP TABLE IF EXISTS `llx_view_propaldet`;
DROP VIEW IF EXISTS `llx_view_propaldet`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_view_propaldet` AS 
(select `llx_propaldet`.`rowid` AS `propaldet_rowid`,
	`llx_propal`.`rowid` AS `propal_rowid`,
	`llx_propal`.`date_valid` AS `date_valid`,
	`llx_propal`.`date_valid_year` AS `date_valid_year`,
	`llx_propal`.`date_valid_quarter` AS `date_valid_quarter`,
	`llx_propal`.`date_valid_month` AS `date_valid_month`,
	`llx_propal`.`date_valid_day` AS `date_valid_day`,
	`llx_propal`.`fk_user_author` AS `id_user`,
	`llx_propal`.`fk_soc` AS `id_societe`,
	`llx_propal`.`fk_statut` AS `id_statut`,
	`llx_propaldet`.`total_ht` - `llx_propaldet`.`buy_price_ht` * `llx_propaldet`.`qty` AS `marge`,
	case 
		when `llx_propaldet`.`total_ht` = 0 
			then 0 
		else `llx_propaldet`.`total_ht`+floor(1+rand(`llx_propaldet`.`rowid`)*1E+6)/1E+11-`llx_propaldet`.`buy_price_ht`*`llx_propaldet`.`qty` 
		end AS `marge_rand`,
	`llx_propaldet`.`total_ht` AS `price_ht`,
	case 
		when `llx_propaldet`.`total_ht` = 0 
			then 0 
		else `llx_propaldet`.`total_ht`+floor(1+rand(`llx_propaldet`.`rowid`)*1E+6)/1E+11 
		end AS `price_rand`,
	`llx_propaldet`.`buy_price_ht` AS `buy_price_ht`,
	`llx_view_product_cat`.`rowid` AS `prod_rowid`,
	`llx_view_product_cat`.`ref` AS `prod_ref`,
	`llx_view_product_cat`.`label` AS `prod_label`,
	case 
		when `llx_view_product_cat`.`id_root` is null 
			then 0 
		else `llx_view_product_cat`.`id_root` 
		end AS `id_prod_root`,
	case 
		when `llx_view_product_cat`.`id_subroot` is null 
			then 0 
		else `llx_view_product_cat`.`id_subroot` 
		end AS `id_prod_subroot`,
	case 
		when `llx_view_product_cat`.`id_level_1` is null 
			then 0 
		else `llx_view_product_cat`.`id_level_1` 
		end AS `id_prod_level_1`,
	case 
		when `llx_view_product_cat`.`id_level_2` is null 
			then 0 
		else `llx_view_product_cat`.`id_level_2` 
		end AS `id_prod_level_2`,
	case 
		when `llx_view_product_cat`.`id_level_3` is null 
			then 0 
		else `llx_view_product_cat`.`id_level_3` 
		end AS `id_prod_level_3`,
	case 
		when `llx_view_product_cat`.`id_level_4` is null 
			then 0 
		else `llx_view_product_cat`.`id_level_4` 
		end AS `id_prod_level_4`,
	case 
		when `llx_view_product_cat`.`id_level_5` is null 
			then 0 
		else `llx_view_product_cat`.`id_level_5` 
		end AS `id_prod_level_5`,
	case 
		when `llx_view_product_cat`.`id_leaf` is null 
			then 0 
		else `llx_view_product_cat`.`id_leaf` 
		end AS `id_prod_leaf`,
	case 
		when `llx_propal_extrafields`.`entrepot` is not null 
			then cast(`llx_propal_extrafields`.`entrepot` as unsigned) 
		else 0 
		end AS `id_entrepot` 
from (((`llx_propaldet` left join `llx_view_product_cat` on(`llx_propaldet`.`fk_product` = `llx_view_product_cat`.`rowid`)) 
		left join `llx_propal` on(`llx_propaldet`.`fk_propal` = `llx_propal`.`rowid`)) 
		left join `llx_propal_extrafields` on(`llx_propal_extrafields`.`fk_object` = `llx_propaldet`.`fk_propal`)) 
where `llx_propaldet`.`total_ht` is not null 
	and `llx_propaldet`.`total_ht` <> 0 
	and `llx_propaldet`.`buy_price_ht` is not null 
	and `llx_propal`.`fk_statut` > 0);
--
-- Final view structure for view llx_view_propaldet_flat
--
DROP TABLE IF EXISTS `llx_view_propaldet_flat`;
DROP VIEW IF EXISTS `llx_view_propaldet_flat`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_view_propaldet_flat` AS 
(select `llx_propaldet`.`rowid` AS `propaldet_rowid`,
	`llx_propaldet`.`fk_product` AS `propaldet_prodid`,
	`llx_propaldet`.`qty` AS `propaldet_prodqty`,
	`llx_propal`.`rowid` AS `propal_rowid`,
	`llx_propal`.`ref` AS `propal_ref`,
	`llx_propal`.`date_valid` AS `date_valid`,
	`llx_propal`.`date_valid_year` AS `date_valid_year`,
	`llx_propal`.`date_valid_quarter` AS `date_valid_quarter`,
	`llx_propal`.`date_valid_month` AS `date_valid_month`,
	`llx_propal`.`date_valid_day` AS `date_valid_day`,
	`llx_propal`.`fk_user_author` AS `id_user`,
	`llx_propal`.`fk_soc` AS `id_societe`,
	`llx_propal`.`fk_statut` AS `id_statut`,
	`llx_mat_view_product_cat_flat`.`rowid` AS `prod_rowid`,
	`llx_mat_view_product_cat_flat`.`ref` AS `prod_ref`,
	`llx_mat_view_product_cat_flat`.`id_leaf_1` AS `id_leaf_1`,
	`llx_mat_view_product_cat_flat`.`id_leaf_2` AS `id_leaf_2`,
	`llx_mat_view_product_cat_flat`.`id_leaf_3` AS `id_leaf_3`,
	`llx_mat_view_product_cat_flat`.`id_leaf_4` AS `id_leaf_4`,
	`llx_mat_view_product_cat_flat`.`id_leaf_5` AS `id_leaf_5`,
	`llx_mat_view_product_cat_flat`.`id_leaf_6` AS `id_leaf_6`,
	`llx_mat_view_product_cat_flat`.`id_leaf_7` AS `id_leaf_7`,
	`llx_mat_view_product_cat_flat`.`id_leaf_8` AS `id_leaf_8`,
	`llx_mat_view_product_cat_flat`.`id_leaf_9` AS `id_leaf_9`,
	`llx_mat_view_product_cat_flat`.`id_leaf_10` AS `id_leaf_10`,
	`llx_mat_view_product_cat_flat`.`id_leaf_11` AS `id_leaf_11`,
	`llx_mat_view_product_cat_flat`.`id_leaf_12` AS `id_leaf_12`,
	`llx_mat_view_product_cat_flat`.`id_leaf_13` AS `id_leaf_13`,
	`llx_mat_view_product_cat_flat`.`id_leaf_14` AS `id_leaf_14`,
	`llx_mat_view_product_cat_flat`.`id_leaf_15` AS `id_leaf_15`,
	`llx_mat_view_product_cat_flat`.`id_leaf_16` AS `id_leaf_16`,
	`llx_mat_view_product_cat_flat`.`id_leaf_17` AS `id_leaf_17`,
	`llx_propaldet`.`total_ht` - `llx_propaldet`.`buy_price_ht` * `llx_propaldet`.`qty` AS `marge`,
	`llx_propaldet`.`total_ht` AS `total_ht`,
	`llx_propaldet`.`buy_price_ht` AS `buy_price_ht`,
	case 
		when `llx_propal_extrafields`.`entrepot` is not null 
			then cast(`llx_propal_extrafields`.`entrepot` as unsigned) 
		else 0 
		end AS `id_entrepot` 
from (((`llx_propaldet` left join `llx_mat_view_product_cat_flat` on(`llx_propaldet`.`fk_product` = `llx_mat_view_product_cat_flat`.`rowid`)) 
		left join `llx_propal` on(`llx_propaldet`.`fk_propal` = `llx_propal`.`rowid`)) 
		left join `llx_propal_extrafields` on(`llx_propal_extrafields`.`fk_object` = `llx_propaldet`.`fk_propal`)) 
where `llx_propaldet`.`total_ht` is not null 
	and `llx_propaldet`.`total_ht` <> 0 
	and `llx_propaldet`.`buy_price_ht` is not null 
	and `llx_propal`.`fk_statut` > 0) ;
