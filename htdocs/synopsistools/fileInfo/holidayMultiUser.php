<?php
$tabSql = array("ALTER TABLE `" . MAIN_DB_PREFIX . "holiday` CHANGE `fk_user` `fk_user` INT( 11 ) NULL;",
"ALTER TABLE `" . MAIN_DB_PREFIX . "holiday` ADD `fk_group` INT NULL DEFAULT NULL AFTER `fk_user`;",
"CREATE TABLE IF NOT EXISTS `" . MAIN_DB_PREFIX . "holiday_group` (
  `fk_holiday` int(11) NOT NULL,
  `fk_user` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");


$text = "Maj de holiday pour multi user";

?>