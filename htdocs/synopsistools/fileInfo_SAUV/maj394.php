<?php
$tabSql = array("ALTER TABLE `llx_synopsischrono` CHANGE `revisionNext` `revisionNext` INT(11) NULL DEFAULT NULL;",
    "ALTER TABLE `llx_synopsischrono` CHANGE `note` `note` VARCHAR(10000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;",
    "ALTER TABLE `llx_synopsischrono_chrono_105` CHANGE `Suivie` `Suivie` VARCHAR(2000) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;",
    "ALTER TABLE `llx_synopsischrono_chrono_105` CHANGE `Pret` `Pret` INT(11) NULL DEFAULT NULL;",
    "ALTER TABLE `llx_Synopsis_contratdet_GMAO` CHANGE `telemaintenanceCur` `telemaintenanceCur` INT(11) NULL;",
    "ALTER TABLE `llx_Synopsis_contratdet_GMAO` CHANGE `nbVisiteCur` `nbVisiteCur` INT(11) NULL;",
    "ALTER TABLE `llx_projet` ENGINE=InnoDB;",
    
    "ALTER TABLE `llx_Synopsis_projet_task_time_effective` CHANGE `task_date_effective` `task_date` DATETIME NULL DEFAULT NULL;",
    "ALTER TABLE `llx_Synopsis_projet_task_time_effective` CHANGE `task_duration_effective` `task_duration` DOUBLE NULL DEFAULT NULL;",
    "ALTER TABLE `llx_Synopsis_projet_task_time_effective` ADD `task_datehour` DATETIME NULL DEFAULT NULL AFTER `note`, ADD `task_date_withhour` INT NULL DEFAULT NULL AFTER `task_datehour`, ADD `thm` DOUBLE(24,8) NOT NULL AFTER `task_date_withhour`, ADD `invoice_id` INT NOT NULL AFTER `thm`, ADD `invoice_line_id` INT NOT NULL AFTER `invoice_id`;",
    "ALTER TABLE llx_projet_task_time RENAME TO llx_synopsis_projet_task_timeP",
    "ALTER TABLE llx_Synopsis_projet_task_time_effective RENAME TO llx_projet_task_time",
    
    "INSERT IGNORE INTO `llx_element_contact`(`statut`, `element_id`, `fk_c_type_contact`, `fk_socpeople`)   SELECT 4, `fk_projet_task`, 181, `fk_user` FROM `llx_Synopsis_projet_task_actors` WHERE 1",
    "UPDATE `llx_projet` SET `fk_statut` = 2 WHERE `fk_statut` = 50",
    "UPDATE `llx_projet` SET `fk_statut` = 1 WHERE `fk_statut` = 10",
    "ALTER TABLE `llx_projet_task_time` CHANGE `task_date` `task_date` DATE NULL DEFAULT NULL;",
    "UPDATE `llx_user_param` SET `value` = 'eldy' WHERE `param` = 'MAIN_THEME';",
    "UPDATE `llx_projet_task` SET `planned_workload` = (SELECT SUM(`task_duration`) FROM `llx_synopsis_projet_task_timeP` WHERE `fk_task` = llx_projet_task.rowid)",
    "UPDATE `llx_Synopsis_Histo_User` SET  `element_type` = 'projet' WHERE  `element_type` LIKE 'synopsisprojet'");


$text = "Maj de gle 394<br/> Reactivé synopsis Chrono, synopsis projetplus, synopsisapple";

?>