---
--- Trigger llx_element_contact_AFTER_INSERT
---
DROP TRIGGER IF EXISTS `llx_element_contact_AFTER_INSERT`;

DELIMITER $$
CREATE TRIGGER `llx_element_contact_AFTER_INSERT` AFTER INSERT ON `llx_element_contact` FOR EACH ROW
BEGIN
	DECLARE type_id_facture INT default 0;
	DECLARE type_id_commande INT default 0;
	SELECT get_type_contact_id('facture') INTO type_id_facture;
	SELECT get_type_contact_id('commande') INTO type_id_commande;
	IF NEW.fk_c_type_contact = type_id_facture THEN
		UPDATE llx_facture SET fk_user_comm=NEW.fk_socpeople WHERE rowid=NEW.element_id;
	ELSEIF NEW.fk_c_type_contact = type_id_commande THEN
		UPDATE llx_commande SET fk_user_comm=NEW.fk_socpeople WHERE rowid=NEW.element_id;
	END IF;
END$$
DELIMITER ;

---
--- Trigger llx_element_contact_AFTER_UPDATE
---
DROP TRIGGER IF EXISTS `llx_element_contact_AFTER_UPDATE`;

DELIMITER $$
CREATE TRIGGER `llx_element_contact_AFTER_UPDATE` AFTER UPDATE ON `llx_element_contact` FOR EACH ROW
BEGIN
	DECLARE type_id_facture INT default 0;
	DECLARE type_id_commande INT default 0;
	SELECT get_type_contact_id('facture') INTO type_id_facture;
	SELECT get_type_contact_id('commande') INTO type_id_commande;
	IF NEW.fk_c_type_contact = type_id_facture THEN
		UPDATE llx_facture SET fk_user_comm=NEW.fk_socpeople WHERE rowid=NEW.element_id;
	ELSEIF NEW.fk_c_type_contact = type_id_commande THEN
		UPDATE llx_commande SET fk_user_comm=NEW.fk_socpeople WHERE rowid=NEW.element_id;
	END IF;
END$$
DELIMITER ;

---
--- Trigger llx_element_contact_AFTER_DELETE
---
DROP TRIGGER IF EXISTS `llx_element_contact_AFTER_DELETE`;

DELIMITER $$
CREATE TRIGGER `llx_element_contact_AFTER_DELETE` AFTER DELETE ON `llx_element_contact` FOR EACH ROW
BEGIN
	DECLARE num_cont_comm INT DEFAULT 0;
	DECLARE new_user_comm INT DEFAULT 0;
	DECLARE type_id_facture INT default 0;
	DECLARE type_id_commande INT default 0;
	SELECT get_type_contact_id('facture') INTO type_id_facture;
	SELECT get_type_contact_id('commande') INTO type_id_commande;
	IF OLD.fk_c_type_contact = type_id_facture THEN
		SELECT COUNT(fk_socpeople) FROM llx_element_contact 
			WHERE element_id = OLD.element_id AND fk_c_type_contact = type_id_facture INTO num_cont_comm;
		IF num_cont_comm>0 THEN
			SELECT MIN(fk_socpeople) FROM llx_element_contact 
				WHERE element_id = OLD.element_id AND fk_c_type_contact = type_id_facture INTO new_user_comm;
		END IF;
		UPDATE llx_facture SET fk_user_comm=new_user_comm WHERE rowid=OLD.element_id;
	ELSEIF OLD.fk_c_type_contact = type_id_commande THEN
		SELECT COUNT(fk_socpeople) FROM llx_element_contact 
			WHERE element_id = OLD.element_id AND fk_c_type_contact = type_id_commande INTO num_cont_comm;
		IF num_cont_comm>0 THEN
			SELECT MIN(fk_socpeople) FROM llx_element_contact 
				WHERE element_id = OLD.element_id AND fk_c_type_contact = type_id_commande INTO new_user_comm;
		END IF;
		UPDATE llx_commande SET fk_user_comm=new_user_comm WHERE rowid=OLD.element_id;
	END IF;
END$$
DELIMITER ;
