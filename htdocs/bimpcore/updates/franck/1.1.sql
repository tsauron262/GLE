ALTER TABLE llx_bimpcore_cgv
	ADD COLUMN nb_pages INT(11) NOT NULL DEFAULT '1' AFTER id_centre,
	ADD COLUMN signature_y_pos INT(11) NOT NULL DEFAULT '0' AFTER nb_pages;

