<?php
$tabSql = array("ALTER TABLE `llx_synopsischrono` CHANGE `revisionNext` `revisionNext` INT(11) NULL DEFAULT NULL;",
    "ALTER TABLE `llx_synopsischrono` CHANGE `note` `note` VARCHAR(10000) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;",
    "ALTER TABLE `llx_synopsischrono_chrono_105` CHANGE `Suivie` `Suivie` VARCHAR(2000) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL;",
    "ALTER TABLE `llx_synopsischrono_chrono_105` CHANGE `Pret` `Pret` INT(11) NULL DEFAULT NULL;",
    "ALTER TABLE `llx_Synopsis_contratdet_GMAO` CHANGE `telemaintenanceCur` `telemaintenanceCur` INT(11) NULL;",
    "ALTER TABLE `llx_Synopsis_contratdet_GMAO` CHANGE `nbVisiteCur` `nbVisiteCur` INT(11) NULL;",
    "");


$text = "Maj de gle 394";

?>