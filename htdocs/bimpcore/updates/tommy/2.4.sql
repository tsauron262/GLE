ALTER TABLE `llx_bimpcore_note` ADD `auto` INT DEFAULT 0;

UPDATE `llx_bimpcore_note` SET `auto` = 1 WHERE `content` LIKE 'Devis garantie%'; 
UPDATE `llx_bimpcore_note` SET `auto` = 1 WHERE `content` LIKE 'Devis envoy%';   
UPDATE `llx_bimpcore_note` SET `auto` = 1 WHERE `content` LIKE 'Devis mis en%';   
UPDATE `llx_bimpcore_note` SET `auto` = 1 WHERE `content` LIKE 'Mise à jour du numéro%';   
UPDATE `llx_bimpcore_note` SET `auto` = 1 WHERE `content` LIKE 'Restitué le%';   
UPDATE `llx_bimpcore_note` SET `auto` = 1 WHERE `content` LIKE 'Diagnostic commencé%';   
UPDATE `llx_bimpcore_note` SET `auto` = 1 WHERE `content` LIKE 'Attente pièce depuis le%'; 
UPDATE `llx_bimpcore_note` SET `auto` = 1 WHERE `content` LIKE 'Pièce reçue le%';   
UPDATE `llx_bimpcore_note` SET `auto` = 1 WHERE `content` LIKE 'Devis accept%';   
UPDATE `llx_bimpcore_note` SET `auto` = 1 WHERE `content` LIKE 'Réparation termin%';   
UPDATE `llx_bimpcore_note` SET `auto` = 1 WHERE `content` LIKE 'Réparation en cours depuis%';   
UPDATE `llx_bimpcore_note` SET `auto` = 1 WHERE `content` LIKE 'Devis ferm%';   
UPDATE `llx_bimpcore_note` SET `auto` = 1 WHERE `content` LIKE 'Devis refus%';   
UPDATE `llx_bimpcore_note` SET `auto` = 1 WHERE `content` LIKE 'Devis ferm%';   
UPDATE `llx_bimpcore_note` SET `auto` = 1 WHERE `content` LIKE 'Devis ferm%';     
