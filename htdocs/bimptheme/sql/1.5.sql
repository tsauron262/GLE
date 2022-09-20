ALTER TABLE `llx_menu` ADD `code_path` VARCHAR(300) NOT NULL DEFAULT '';
ALTER TABLE `llx_menu` ADD `active` tinyint(1) NOT NULL DEFAULT 1;
ALTER TABLE `llx_menu` ADD `bimp_icon` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `llx_menu` ADD `bimp_object` VARCHAR(300) NOT NULL DEFAULT '';

UPDATE llx_menu SET bimp_icon = CONCAT('fas_', icon) WHERE menu_handler != 'bimptheme';
