<?php
$tabSql = array("
  CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."synopsis_apple_repair` (
  `rowid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chronoId` int(11) NOT NULL,
  `repairNumber` varchar(100) DEFAULT NULL,
  `repairConfirmNumber` varchar(100) DEFAULT NULL,
  `serialUpdateConfirmNumber` varchar(100) DEFAULT NULL,
  `closed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
");

$text = "Ajout table Repair pour module Apple";
?>
