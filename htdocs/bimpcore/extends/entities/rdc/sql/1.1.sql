ALTER TABLE llx_c_actioncomm
    ADD COLUMN maj_dercontact_rdc TINYINT(1) DEFAULT 1
	AFTER user_notif;

-- UPDATE llx_actioncomm SET maj_dercontact_rdc = 0 WHERE id = 40;
