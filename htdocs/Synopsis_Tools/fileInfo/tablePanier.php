<?php
$tabSql = array("CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."synopsis_apple_parts_cart` (
`rowid` int(10) unsigned NOT NULL AUTO_INCREMENT,
`serial_number` text NOT NULL,
`chrono_id` int(10) unsigned DEFAULT NULL,
PRIMARY KEY (`rowid`)
)");
$tabSql[] = "CREATE TABLE IF NOT EXISTS `".MAIN_DB_PREFIX."synopsis_apple_parts_cart_detail` (
`rowid` int(10) unsigned NOT NULL AUTO_INCREMENT,
`cart_rowid` int(10) unsigned NOT NULL,
`part_number` text NOT NULL,
`comptia_code` text,
`comptia_modifier` text,
`qty` int(10) unsigned DEFAULT NULL,
PRIMARY KEY (`rowid`),
KEY `cart_rowid` (`cart_rowid`)
)";


$text = "Aj table panier";

