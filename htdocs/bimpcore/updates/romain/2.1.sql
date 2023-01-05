-- Ajout champs utilisateur valide le transfert
ALTER TABLE `llx_bt_transfer` ADD `user_valid` INT NOT NULL AFTER `id_warehouse_dest`;
UPDATE `llx_bt_transfer` SET user_valid=1;