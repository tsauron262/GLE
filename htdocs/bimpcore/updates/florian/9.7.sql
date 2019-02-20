ALTER TABLE `llx_bs_apple_part` ADD `price_type` VARCHAR(128) NOT NULL DEFAULT '' AFTER `exchange_price`;
ALTER TABLE `llx_bs_apple_part` ADD `price_options` TEXT NOT NULL DEFAULT '' AFTER `exchange_price`;

UPDATE `llx_bs_apple_part` SET `price_type` = 'EXCHANGE' WHERE `no_order` = 0;
UPDATE `llx_bs_apple_part` SET `price_type` = 'STOCK' WHERE `no_order` = 1;