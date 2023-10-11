ALTER TABLE llx_bimpcore_conf ADD entity int NOT NULL default 0;
ALTER TABLE `llx_bimpcore_conf` DROP INDEX `name`;
ALTER TABLE `llx_bimpcore_conf` ADD UNIQUE INDEX `name_entity` (`name`, `entity`);


