SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- llx_bl_inventory_det_2
CREATE TABLE `llx_bl_inventory_det_2` (
  `id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `fk_inventory` int(11) NOT NULL,
  `fk_product` int(11) NOT NULL,
  `fk_equipment` int(11) DEFAULT NULL,
  `qty` int(11) DEFAULT 1,
  `fk_warehouse_type` int(11) NOT NULL,
  `fk_package` int(11) NOT NULL,
  `user_create` int(11) NOT NULL,
  `user_update` int(11) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT current_timestamp,
  `date_update` datetime NOT NULL DEFAULT current_timestamp
);

-- llx_bl_inventory_2

CREATE TABLE `llx_bl_inventory_2` (
  `id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `date_opening` datetime DEFAULT NULL,
  `date_closing_partially` datetime DEFAULT NULL,
  `date_closing` datetime DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `fk_warehouse` int(11) NOT NULL,
  `type` int(11) NOT NULL DEFAULT 2,
  `config` TEXT NOT NULL DEFAULT '{"cat":[],"prod":[]}',
  `user_create` int(11) NOT NULL,
  `user_update` int(11) NOT NULL,
  `date_create` datetime NOT NULL DEFAULT current_timestamp,
  `date_update` datetime NOT NULL DEFAULT current_timestamp
);


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
  `date_update` datetime NOT NULL DEFAULT current_timestamp



CREATE TABLE `llx_bl_inventory_expected` (
   `id`             INT NOT NULL AUTO_INCREMENT,
   `id_inventory`   INT NOT NULL,
   `id_wt`          INT NOT NULL,
   `id_package`     INT NOT NULL,
   `id_product`     INT NOT NULL,
   `qty`            INT NOT NULL,
   `qty_scanned`    INT NOT NULL DEFAULT '0'
   `ids_equipments` TEXT,
   `serialisable`   INT NOT NULL,
   `user_create`    INT NOT NULL,
   `user_update`    INT NOT NULL,
   `date_create`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   `date_update`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`)
) ENGINE = InnoDB;