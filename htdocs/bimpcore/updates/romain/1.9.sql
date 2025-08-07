

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
