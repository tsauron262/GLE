ALTER TABLE `llx_facture_extrafields` CHANGE `pdf_hide_reduc` `save_pdf_hide_reduc` INT(1) NULL DEFAULT '0'; 
ALTER TABLE `llx_facture_extrafields` CHANGE `pdf_hide_total` `save_pdf_hide_total` INT(1) NULL DEFAULT '0';
ALTER TABLE `llx_facture_extrafields` CHANGE `pdf_hide_ttc` `save_pdf_hide_ttc` INT(1) NULL DEFAULT '0';

DELETE FROM `llx_extrafields` WHERE `name` IN ('pdf_hide_reduc','pdf_hide_ttc','pdf_hide_total') AND `elementtype` = 'facture';