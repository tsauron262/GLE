
UPDATE `llx_bimpcore_objects_associations` SET `src_object_name` = 'BS_Pret' WHERE `src_object_name` = 'BS_SavPret';

ALTER TABLE `llx_bs_pret` CHANGE `date_begin` `date_begin` DATE NULL DEFAULT CURRENT_TIMESTAMP; 
ALTER TABLE `llx_bs_pret` CHANGE `date_end` `date_end` DATE NULL DEFAULT CURRENT_TIMESTAMP; 