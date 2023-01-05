-- llx_bl_inventory_warehouse
CREATE TABLE `llx_bl_inventory_warehouse` (
  `id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `fk_inventory` int(11) NOT NULL,
  `fk_warehouse` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `is_main` int(11) DEFAULT NULL COMMENT 'Couple par default pour cet inventaire',
  `user_create` int(11) NOT NULL,
  `user_update` int(11) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT current_timestamp,
  `date_update` datetime NOT NULL DEFAULT current_timestamp);



CREATE TABLE `llx_bl_inventory_expected` (
   `id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
   `id_inventory`   INT NOT NULL,
   `id_wt`          INT NOT NULL,
   `id_package`     INT NOT NULL,
   `id_product`     INT NOT NULL,
   `qty`            INT NOT NULL,
   `qty_scanned`    INT NOT NULL DEFAULT '0',
   `ids_equipments` TEXT,
   `serialisable`   INT NOT NULL,
   `user_create`    INT NOT NULL,
   `user_update`    INT NOT NULL,
   `date_create`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   `date_update`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;