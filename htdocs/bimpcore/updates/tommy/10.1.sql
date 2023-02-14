ALTER table llx_c_tva ADD use_default integer DEFAULT 0;
UPDATE `llx_extrafields` SET `size` = '24' WHERE `size` = '24,8';