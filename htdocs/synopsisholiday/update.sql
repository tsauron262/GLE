ALTER TABLE `llx_holiday` CHANGE `fk_user` `fk_user` INT( 11 ) NULL;
ALTER TABLE `llx_holiday` ADD `fk_group` INT NULL DEFAULT NULL AFTER `fk_user`;
CREATE TABLE IF NOT EXISTS `llx_holiday_group` (
  `fk_holiday` int(11) NOT NULL,
  `fk_user` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;