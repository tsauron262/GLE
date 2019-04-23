---
--- Function get_prod_cat_flat_id
---
DELIMITER $$
CREATE DEFINER=`root`@`%` FUNCTION `get_prod_cat_flat_id`(full_path VARCHAR(255)) RETURNS int(11)
    READS SQL DATA
BEGIN
	DECLARE v_id INTEGER DEFAULT 0;
	SELECT ID INTO v_id FROM `llx_olap_prod_cat_flat` WHERE LOCATE(path,full_path)>0; 
RETURN v_id;
END$$
DELIMITER ;
---
--- Function get_prod_gamme_id
---
DELIMITER $$
CREATE DEFINER=`root`@`%` FUNCTION `get_prod_gamme_id`() RETURNS int(11)
    READS SQL DATA
BEGIN
	DECLARE v_id INTEGER DEFAULT 0;
	SELECT ID INTO v_id FROM `llx_olap_prod_cat_flat` WHERE LOCATE(path,'_Gamme')>0; 
RETURN v_id;
END$$
DELIMITER ;
---
--- Function get_prod_gamme_idcat
---
DELIMITER $$
CREATE DEFINER=`root`@`%` FUNCTION `get_prod_gamme_idcat`(curr_path VARCHAR(255)) RETURNS int(11)
    READS SQL DATA
BEGIN
	DECLARE v_id INTEGER DEFAULT 0;
    IF LOCATE('Gamme',curr_path)=0 THEN
		RETURN v_id;
	END IF;    
	SELECT id_level_1 INTO v_id FROM `llx_mat_view_categorie` WHERE LOCATE(curr_path,path)>0 LIMIT 1; 
RETURN v_id;
END$$
DELIMITER ;
---
--- Function get_prod_recurr_id
---
DELIMITER $$
CREATE DEFINER=`root`@`%` FUNCTION `get_prod_recurr_id`() RETURNS int(11)
    READS SQL DATA
BEGIN
	DECLARE v_id INTEGER DEFAULT 0;
	SELECT ID INTO v_id FROM `llx_olap_prod_cat_flat` WHERE LOCATE(path,'_Recurrence')>0; 
RETURN v_id;
END$$
DELIMITER ;
---
--- Function get_prod_recurr_idcat
---
DELIMITER $$
CREATE DEFINER=`root`@`%` FUNCTION `get_prod_recurr_idcat`(curr_path VARCHAR(255)) RETURNS int(11)
    READS SQL DATA
BEGIN
	DECLARE v_id INTEGER DEFAULT 0;
    IF LOCATE('Recurrence',curr_path)=0 THEN
		RETURN v_id;
	END IF;
	SELECT id_level_1 INTO v_id FROM `llx_mat_view_categorie` WHERE LOCATE(curr_path,path)>0 LIMIT 1; 
RETURN v_id;
END$$
DELIMITER ;
