--
-- Table llx_olap_c_commande_sta
-- Command status
--
SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
START TRANSACTION;
DROP TABLE IF EXISTS `llx_olap_c_commande_sta`;
CREATE TABLE `llx_olap_c_commande_sta` (
  `id` int(11) NOT NULL,
  `code` varchar(31) DEFAULT NULL,
  `label` varchar(63) DEFAULT NULL,
  `type` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_code` (`code`),
  KEY `idx_label` (`label`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `llx_olap_c_commande_sta` VALUES (-1,'CANCELED','Annulée',0),(0,'DRAFT','Brouillon',0),(1,'VALIDATED','Validée',0),(2,'SHIPMENTONPROCESS','En cours',0),(3,'CLOSED','Livrée',0);
COMMIT;
--
-- Table llx_olap_c_facture_sta
-- Facture status
--
SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
START TRANSACTION;
DROP TABLE IF EXISTS `llx_olap_c_facture_sta`;
CREATE TABLE `llx_olap_c_facture_sta` (
  `id` int(11) NOT NULL,
  `code` varchar(31) DEFAULT NULL,
  `label` varchar(63) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_code` (`code`),
  KEY `idx_label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `llx_olap_c_facture_sta` VALUES (0,'DRAFT','Brouillon'),(1,'VALIDATED','Impayée'),(2,'CLOSED','Payée'),(3,'ABANDONED','Abandonnée');
COMMIT;
--
-- Table llx_olap_month
-- Months of the year
--
SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
START TRANSACTION;
DROP TABLE IF EXISTS `llx_olap_month`;
CREATE TABLE `llx_olap_month` (
  `id` int(11) NOT NULL,
  `code` text DEFAULT NULL,
  `label` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `llx_olap_month` VALUES (1,'JAN','Janvier'),(2,'FEV','Février'),(3,'MAR','Mars'),(4,'AVR','Avril'),(5,'MAI','Mai'),(6,'JUN','Juin'),(7,'JUL','Juillet'),(8,'AOU','Août'),(9,'SEP','Septembre'),(10,'OCT','Octobre'),(11,'NOV','Novembre'),(12,'DEC','Décembre');
COMMIT;
--
-- Table llx_olap_quarter
-- Quarters of the year
--
SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
START TRANSACTION;
DROP TABLE IF EXISTS `llx_olap_quarter`;
CREATE TABLE `llx_olap_quarter` (
  `id` int(11) NOT NULL,
  `label` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `llx_olap_quarter` VALUES (1,'Q1'),(2,'Q2'),(3,'Q3'),(4,'Q4');
COMMIT;
