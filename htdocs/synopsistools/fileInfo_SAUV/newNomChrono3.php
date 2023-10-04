<?php
$tabSql = array("UPDATE `".MAIN_DB_PREFIX."Synopsis_Process_lien` SET `table` = REPLACE(`table`,'Synopsis_Chrono','synopsischrono'), `urlObj` = REPLACE(`urlObj`,'Synopsis_Chrono','synopsischrono');");

$tabSql[] = "UPDATE `".MAIN_DB_PREFIX."Synopsis_Process_type_element` SET `classFile` = REPLACE(`classFile`,'Synopsis_Chrono','synopsischrono'), `ficheUrl` = REPLACE(`ficheUrl`,'Synopsis_Chrono','synopsischrono');";

$tabSql[] = "UPDATE `".MAIN_DB_PREFIX."synopsistools_bug` SET `text` = REPLACE(`text`,'Synopsis_Chrono','synopsischrono');";


$tabNom = array("Synopsis_Chrono", "Synopsis_Chrono_conf", "Synopsis_Chrono_form_fct_value", "Synopsis_Chrono_group_rights", "Synopsis_Chrono_key", "Synopsis_Chrono_key_type_valeur", "Synopsis_Chrono_key_value_view", "Synopsis_Chrono_Multivalidation", "Synopsis_Chrono_rights", "Synopsis_Chrono_rights_def", "Synopsis_Chrono_value");
foreach ($tabNom as $nom)
    $tabSql[] = "RENAME TABLE  ".MAIN_DB_PREFIX.$nom." TO  ".MAIN_DB_PREFIX.str_replace("Synopsis_Chrono", "synopsischrono", $nom)." ;";

$text = "change nom chrono ";

$tabSql[] = "DROP TABLE IF EXISTS `".MAIN_DB_PREFIX."synopsischrono_key_value_view`;";

$tabSql[] = "DROP VIEW IF EXISTS `".MAIN_DB_PREFIX."synopsischrono_key_value_view`;";

$tabSql[] = "CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `".MAIN_DB_PREFIX."synopsischrono_key_value_view` AS select `v`.`id` AS `id`,`k`.`nom` AS `nom`,`k`.`description` AS `description`,`k`.`model_refid` AS `model_refid`,`k`.`type_valeur` AS `type_valeur`,`k`.`type_subvaleur` AS `type_subvaleur`,`k`.`extraCss` AS `extraCss`,`k`.`inDetList` AS `inDetList`,`k`.`id` AS `key_id`,`c`.`id` AS `chrono_id`,`v`.`value` AS `chrono_value`,`c`.`date_create` AS `date_create`,`c`.`ref` AS `ref`,`c`.`description` AS `desc_chrono`,`c`.`fk_soc` AS `fk_soc`,`c`.`fk_user_author` AS `fk_user_create`,`c`.`fk_socpeople` AS `fk_socpeople`,`c`.`fk_user_modif` AS `fk_user_modif`,`c`.`fk_statut` AS `fk_statut`,`c`.`validation_number` AS `validation_number`,`c`.`revision` AS `revision`,`c`.`model_refid` AS `chrono_conf_id`,`c`.`orig_ref` AS `orig_ref` from (`".MAIN_DB_PREFIX."synopsischrono` `c` left join (`".MAIN_DB_PREFIX."synopsischrono_key` `k` left join `".MAIN_DB_PREFIX."synopsischrono_value` `v` on((`v`.`key_id` = `k`.`id`))) on((`c`.`id` = `v`.`chrono_refid`)));";