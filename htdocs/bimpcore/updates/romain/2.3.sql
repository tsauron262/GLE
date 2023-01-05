
ALTER TABLE `llx_bl_inventory_2`
ADD `incl_categorie` TEXT NOT NULL AFTER `id_package_vol`,
ADD `excl_categorie` TEXT NOT NULL AFTER `incl_categorie`,
ADD `incl_collection` TEXT NOT NULL AFTER `excl_categorie`,
ADD `excl_collection` TEXT NOT NULL AFTER `incl_collection`,
ADD `incl_nature` TEXT NOT NULL AFTER `excl_collection`,
ADD `excl_nature` TEXT NOT NULL AFTER `incl_nature`,
ADD `incl_famille` TEXT NOT NULL AFTER `excl_nature`,
ADD `excl_famille` TEXT NOT NULL AFTER `incl_famille`,
ADD `incl_gamme` TEXT NOT NULL AFTER `excl_famille`,
ADD `excl_gamme` TEXT NOT NULL AFTER `incl_gamme`;
-- 
-- ALTER TABLE `llx_bl_inventory_2`
--   DROP `incl_categorie`,
--   DROP `excl_categorie`,
--   DROP `incl_collection`,
--   DROP `excl_collection`,
--   DROP `incl_nature`,
--   DROP `excl_nature`,
--   DROP `incl_famille`,
--   DROP `excl_famille`,
--   DROP `incl_gamme`,
--   DROP `excl_gamme`;

-- ALTER TABLE `llx_bl_inventory_2` ADD `id_filtre_product` INT NOT NULL DEFAULT '0' AFTER `id_package_vol`;
-- 
-- ALTER TABLE `llx_bl_inventory_2` CHANGE `id_filtre_product` `id_filter_product` INT(11) NOT NULL DEFAULT '0';
-- 
-- ALTER TABLE `llx_bl_inventory_2` DROP `id_filter_product`;

ALTER TABLE `llx_bl_inventory_2` ADD `incl_product` TEXT NOT NULL AFTER `excl_gamme`, ADD `excl_product` TEXT NOT NULL AFTER `incl_product`;

ALTER TABLE `llx_bl_inventory_2` ADD `filter_inventory` INT NOT NULL DEFAULT '0' AFTER `id_package_vol`;