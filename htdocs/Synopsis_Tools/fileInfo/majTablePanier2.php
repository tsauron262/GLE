<?php
$tabSql = array(
    "ALTER TABLE `llx_synopsis_apple_parts_cart` ADD `serialUpdateConfNum` varchar(100) DEFAULT NULL",
    "ALTER TABLE `llx_synopsis_apple_parts_cart` ADD `repairComplete` tinyint(1) NOT NULL DEFAULT '0'"
    );

$text = "Maj des table panier apple pour ajouter serialUpdateConfNum + repairComplete (0/1)";
?>
