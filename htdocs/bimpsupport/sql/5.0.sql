ALTER TABLE llx_bs_centre_sav
ADD code VARCHAR(5) NOT NULL default '' AFTER id,
ADD active tinyint(1) NOT NULL default 1,
ADD id_centre_rattachement int(11) NOT NULL default 0;
