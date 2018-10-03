<?php

$text = "Que pour Capsim";


$tabSql[] = "CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "projet` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_soc` int(11) DEFAULT NULL,
  `datec` date DEFAULT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dateo` date DEFAULT NULL,
  `datee` date DEFAULT NULL,
  `ref` varchar(50) DEFAULT NULL,
  `entity` int(11) NOT NULL DEFAULT '1',
  `title` varchar(255) NOT NULL,
  `description` text,
  `fk_user_creat` int(11) NOT NULL,
  `public` int(11) DEFAULT NULL,
  `fk_statut` int(11) NOT NULL DEFAULT '0',
  `fk_opp_status` int(11) DEFAULT NULL,
  `date_close` datetime DEFAULT NULL,
  `fk_user_close` int(11) DEFAULT NULL,
  `note_private` text,
  `note_public` text,
  `opp_amount` double(24,8) DEFAULT NULL,
  `budget_amount` double(24,8) DEFAULT NULL,
  `model_pdf` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `uk_projet_ref` (`ref`,`entity`),
  KEY `idx_projet_fk_soc` (`fk_soc`)
)";

$tabSql[] = "INSERT INTO `" . MAIN_DB_PREFIX . "projet`(`rowid`, `fk_soc`, 
`datec`, `tms`, `dateo`, `datee`, `ref`, `entity`, `title`, 
`description`, `fk_user_creat`, `public`, `fk_statut`, 
 `date_close`) 
SELECT `rowid`, `fk_soc`, 
`date_create`, `tms`, `dateo`, `date_valid`, `ref`, `entity`, `title`, 
`note`, `fk_user_creat`, `fk_type_projet`, `fk_statut`, 
`date_cloture` FROM " . MAIN_DB_PREFIX . "Synopsis_projet;
";

$tabSql[] = "DROP table `" . MAIN_DB_PREFIX . "Synopsis_projet_sup`;
";

$tabSql[] = "RENAME TABLE  `" . MAIN_DB_PREFIX . "Synopsis_projet` TO  `" . MAIN_DB_PREFIX . "Synopsis_projet_sup` ;
";

$tabSql[] = "ALTER TABLE `" . MAIN_DB_PREFIX . "Synopsis_projet_sup`
  DROP `fk_soc`,
  DROP `fk_statut`,
  DROP `tms`,
  DROP `dateo`,
  DROP `date_create`,
  DROP `ref`,
  DROP `title`,
  DROP `fk_user_creat`,
  DROP `note`,
  DROP `date_cloture`,
  DROP `entity`;
";

$tabSql[] = "CREATE VIEW `" . MAIN_DB_PREFIX . "Synopsis_projet_view` AS (SELECT p1.*, p2.`fk_user_resp`,`fk_type_projet`,`date_valid`,`date_launch`,p1.note_public as note FROM " . MAIN_DB_PREFIX . "projet p1 LEFT join " . MAIN_DB_PREFIX . "Synopsis_projet_sup p2 ON p1.rowid = p2.rowid)
";

$tabSql[] = "INSERT INTO `" . MAIN_DB_PREFIX . "projet_task`(`rowid`, `ref`, `entity`, `fk_projet`, `fk_task_parent`, 
`datec`, `tms`, `dateo`, `datee`, `datev`, 
`label`, `description`, `duration_effective`, `planned_workload`, 
`progress`, `priority`, `fk_user_creat`, `fk_user_valid`, 
`fk_statut`, `note_private`, `note_public`,
`rang`)  
SELECT `rowid`, '', 1, `fk_projet`, `fk_task_parent`, 
null, `tms`,  `dateDeb`, null, null,
`title`,`description`, `duration_effective`, `duration`, 
`progress`, `fk_task_type`, `fk_user_creat`, null,
if(`statut` = 'open', 0,1), `shortDesc`, `note`,  
 `level` FROM " . MAIN_DB_PREFIX . "Synopsis_projet_task
";

$tabSql[] = "RENAME table " . MAIN_DB_PREFIX . "Synopsis_projet_task to SAUV" . MAIN_DB_PREFIX . "Synopsis_projet_task
";

$tabSql[] = "RENAME table " . MAIN_DB_PREFIX . "Synopsis_task_type to " . MAIN_DB_PREFIX . "Synopsis_projet_task_type
";

$tabSql[] = "INSERT INTO `" . MAIN_DB_PREFIX . "projet_task_time`(`rowid`, `fk_task`, `task_date`, `task_duration`, `fk_user`, `note`) SELECT `rowid`, `fk_task`, DATE(task_date), `task_duration`, `fk_user`, `note` FROM `" . MAIN_DB_PREFIX . "Synopsis_projet_task_time` WHERE 1
";

$tabSql[] = "DROP table " . MAIN_DB_PREFIX . "Synopsis_projet_task_time;";