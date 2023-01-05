
ALTER TABLE `llx_bl_inventory_2`
ADD `id_package_nouveau` INT NOT NULL DEFAULT '0' AFTER `config`,
ADD `id_package_vol` INT NOT NULL DEFAULT '0' AFTER `id_package_nouveau`;