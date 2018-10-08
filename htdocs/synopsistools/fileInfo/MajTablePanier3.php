<?php

$tabSql = array(
    "ALTER TABLE `" . MAIN_DB_PREFIX . "synopsis_apple_parts_cart_detail` ADD `componentCode` TEXT NOT NULL ,
        ADD `partDescription` TEXT NOT NULL , ADD `stockPrice` TEXT NOT NULL"
);

$text = "Maj des table panier apple pour ajouter serialUpdateConfNum + repairComplete (0/1)";
?>
