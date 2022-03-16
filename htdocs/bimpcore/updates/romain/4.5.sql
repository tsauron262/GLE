ALTER TABLE `llx_societe` ADD `date_depot_icba` DATETIME NULL DEFAULT NULL; 
UPDATE `llx_societe` SET date_depot_icba = date_atradius;
UPDATE `llx_societe` SET date_atradius = NULL;