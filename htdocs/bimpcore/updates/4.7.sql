
ALTER TABLE `llx_bs_apple_part` ADD `no_order` BOOLEAN NOT NULL DEFAULT FALSE AFTER `stock_price`;
ALTER TABLE `llx_bs_apple_part` ADD `exchange_price` FLOAT NOT NULL DEFAULT '0' AFTER `stock_price`;
ALTER TABLE `llx_bs_apple_part` ADD `out_of_warranty` BOOLEAN NOT NULL DEFAULT FALSE AFTER `stock_price`;

ALTER TABLE `llx_bimp_gsx_repair` CHANGE `total_from_order` `total_from_order` FLOAT NULL DEFAULT NULL;

ALTER TABLE `llx_bs_sav_product` ADD `out_of_warranty` BOOLEAN NOT NULL DEFAULT FALSE AFTER `qty`;