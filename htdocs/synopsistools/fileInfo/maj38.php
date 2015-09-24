<?php

$tabsql = array();

$tabSql[] = 'UPDATE `'.MAIN_DB_PREFIX.'Synopsis_Process_lien` SET `sqlFiltreSoc`= replace(`sqlFiltreSoc`, "fk_societe", "fk_soc") WHERE 1';

$tabSql[] = 'ALTER TABLE  `'.MAIN_DB_PREFIX.'synopsischrono` CHANGE  `fk_societe`  `fk_soc` INT( 11 ) NULL DEFAULT NULL';

$tabSql[] = "UPDATE `".MAIN_DB_PREFIX."Synopsis_Process_form_requete` SET `requete`= replace(`requete`, ' fk_soc', ' c.fk_soc'),`requeteValue`=  replace(`requeteValue`, ' fk_soc', ' c.fk_soc') WHERE `id` = 1007";