---
--- Stored procedure refresh_llx_mat_view_categorie
---
DELIMITER $$
CREATE DEFINER=`root`@`%` PROCEDURE `refresh_llx_mat_view_categorie`()
BEGIN
	SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
	START TRANSACTION;
	TRUNCATE TABLE `llx_mat_view_categorie`;
    INSERT INTO `llx_mat_view_categorie`
		SELECT * FROM  `llx_view_categorie`;
	COMMIT;
END$$
DELIMITER ;
---
--- Stored procedure refresh_llx_mat_view_commandedet
---
DELIMITER $$
CREATE DEFINER=`root`@`%` PROCEDURE `refresh_llx_mat_view_commandedet`()
BEGIN
	SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
	START TRANSACTION;
	TRUNCATE TABLE `llx_mat_view_commandedet`;
    INSERT INTO `llx_mat_view_commandedet`
		SELECT * FROM  `llx_view_commandedet`;
	COMMIT;
END$$
DELIMITER ;
---
--- Stored procedure refresh_llx_mat_view_facturedet
---
DELIMITER $$
CREATE DEFINER=`root`@`%` PROCEDURE `refresh_llx_mat_view_facturedet`()
BEGIN
	SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
	START TRANSACTION;
	TRUNCATE TABLE `llx_mat_view_facturedet`;
    INSERT INTO `llx_mat_view_facturedet`
		SELECT * FROM  `llx_view_facturedet`;
	COMMIT;
END$$
DELIMITER ;
---
--- Stored procedure refresh_llx_mat_view_product_cat
---
DELIMITER $$
CREATE DEFINER=`root`@`%` PROCEDURE `refresh_llx_mat_view_product_cat`()
BEGIN
	SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
	START TRANSACTION;
	TRUNCATE TABLE `llx_mat_view_product_cat`;
    INSERT INTO `llx_mat_view_product_cat`
		SELECT * FROM  `llx_view_product_cat`;
	COMMIT;
END$$
DELIMITER ;
---
--- Stored procedure refresh_llx_mat_view_propaldet
---
DELIMITER $$
CREATE DEFINER=`root`@`%` PROCEDURE `refresh_llx_mat_view_propaldet`()
BEGIN
	SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
	START TRANSACTION;
	TRUNCATE TABLE `llx_mat_view_propaldet`;
    INSERT INTO `llx_mat_view_propaldet`
		SELECT * FROM  `llx_view_propaldet`;
	COMMIT;
END$$
DELIMITER ;
---
--- Stored procedure set_product_cat_flat
---
DELIMITER $$
CREATE DEFINER=`root`@`%` PROCEDURE `set_product_cat_flat`()
BEGIN
	DECLARE v_rowid int(11);
	DECLARE v_ref varchar(128);
	DECLARE v_label varchar(255);
    DECLARE v_label_esc varchar(255) DEFAULT '';
	DECLARE v_price double(24,8);
	DECLARE v_price_rand double;
	DECLARE v_path varchar(255);
    DECLARE v_path_esc varchar(255) DEFAULT '';
	DECLARE v_lvl bigint(1);
	DECLARE v_id_root bigint(1);
	DECLARE v_id_subroot bigint(1);
	DECLARE v_id_level_1 bigint(1);
	DECLARE v_id_level_2 bigint(1);
	DECLARE v_id_level_3 bigint(1);
  	DECLARE v_id_level_4 bigint(1);
	DECLARE v_id_level_5 bigint(1);
	DECLARE v_id_level bigint(1);
  	DECLARE v_id_leaf bigint(1);
    DECLARE v_id_flat int(11);
#    DECLARE v_insert_stmt varchar(512);
#    DECLARE v_update_stmt varchar(512);
	DECLARE v_id_cat_flat_field varchar(32) DEFAULT 'id_cat_flat_';
    DECLARE v_path_field varchar(32) DEFAULT 'path_';
	DECLARE v_lvl_field varchar(32) DEFAULT 'lvl_';
	DECLARE v_id_root_field varchar(32) DEFAULT 'id_root_';
	DECLARE v_id_subroot_field varchar(32) DEFAULT 'id_subroot_';
	DECLARE v_id_level_1_field varchar(32) DEFAULT 'id_level_1_';
	DECLARE v_id_level_2_field varchar(32) DEFAULT 'id_level_2_';
	DECLARE v_id_level_3_field varchar(32) DEFAULT 'id_level_3_';
  	DECLARE v_id_level_4_field varchar(32) DEFAULT 'id_level_4_';
	DECLARE v_id_level_5_field varchar(32) DEFAULT 'id_level_5_';
	DECLARE v_id_level_x_field varchar(32) DEFAULT 'id_level_';
  	DECLARE v_id_leaf_field varchar(32) DEFAULT 'id_leaf_';
    DECLARE done int(11);
    DECLARE i int(11) DEFAULT 0;
    DECLARE nmax int(11) DEFAULT 0;
    DECLARE n int(11) DEFAULT 0;
    DECLARE v_lvl_max int(3) DEFAULT 0;
    DECLARE v_insert_list VARCHAR(512);
    DECLARE v_insert_values VARCHAR(512);
    DECLARE v_update_set VARCHAR(512);
    DECLARE v_idcat_gamme int(3) DEFAULT 0;
    DECLARE v_idcat_recurr int(3) DEFAULT 0;
    DECLARE v_id_gamme int(3) DEFAULT 0;
    DECLARE v_id_recurr int(3) DEFAULT 0;
    DECLARE v_lvl_gamme int(3) DEFAULT 0;
    DECLARE v_lvl_recurr int(3) DEFAULT 0;
    DECLARE v_path_gamme varchar(255) DEFAULT '';
    DECLARE v_path_recurr varchar(255) DEFAULT '';
    
	DECLARE cur_prod CURSOR FOR 
        SELECT  `rowid`,`ref`,`label`,
				`price`,`price_rand`,`path`,`lvl`,
                `id_root`,`id_subroot`,`id_level_1`,`id_level_2`,`id_level_3`,`id_level_4`,`id_level_5`,`id_leaf`
        FROM `llx_mat_view_product_cat`;

	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done=1;

#    CREATE TEMPORARY TABLE IF NOT EXISTS t_ids(id INT primary key, val INT NOT NULL) ENGINE=MEMORY;
#    TRUNCATE TABLE t_ids;
    
    TRUNCATE TABLE `llx_mat_view_product_cat_flat`;
    TRUNCATE TABLE `llx_debug`;
    
    SET done=0;
	SELECT get_prod_gamme_id() INTO v_id_gamme;
	SELECT get_prod_recurr_id() INTO v_id_recurr;

    OPEN cur_prod;
    
	prod_loop: LOOP
        FETCH cur_prod INTO v_rowid,v_ref,v_label,
							v_price,v_price_rand,v_path,v_lvl,
							v_id_root,v_id_subroot,v_id_level_1,v_id_level_2,v_id_level_3,v_id_level_4,v_id_level_5,v_id_leaf;
        IF done=1 THEN LEAVE prod_loop; 
        END IF;
		
        SET i=i+1;
#		INSERT INTO `llx_debug` (`msg`) VALUES(CONCAT('i=',i));
        
        IF v_label IS NULL THEN ITERATE prod_loop;
        END IF;
        IF v_path IS NULL THEN ITERATE prod_loop;
        END IF;
        IF v_id_leaf IS NULL THEN ITERATE prod_loop;
        END IF;
        IF v_id_leaf=0 THEN ITERATE prod_loop;
        END IF;
        
		SELECT get_prod_cat_flat_id(v_path) INTO v_id_flat;
        IF v_id_flat=0 THEN ITERATE prod_loop;
        END IF;

        SELECT lvl_max INTO v_lvl_max FROM `llx_olap_prod_cat_flat` WHERE id=v_id_flat;
        IF v_lvl_max<1 THEN ITERATE prod_loop;
        END IF;

#		INSERT INTO `llx_debug` (`msg`) VALUES(CONCAT('v_lvl_max=',v_lvl_max));
#        TRUNCATE TABLE t_ids;
        
        IF LOCATE('\'',v_label)>0
			THEN 
				SET v_label_esc = REPLACE(v_label,"'","\\'");
			ELSE
				SET v_label_esc = v_label;
		END IF;

        IF LOCATE('\'',v_path)>0
			THEN
				SET v_path_esc = REPLACE(v_path,"'","\\'");
			ELSE
				SET v_path_esc = v_path;
		END IF;

        SELECT get_prod_gamme_idcat(v_path) INTO v_idcat_gamme;
        IF v_idcat_gamme>0 THEN
            SET v_lvl_gamme=1;
            SET v_path_gamme=v_path_esc;
		ELSE
            SET v_lvl_gamme=0;
            SET v_path_gamme='';
        END IF;
        
        SELECT get_prod_recurr_idcat(v_path) INTO v_idcat_recurr;
        IF v_idcat_recurr>0 THEN
            SET v_lvl_recurr=1;
            SET v_path_recurr=v_path_esc;
		ELSE
			SET v_lvl_recurr=0;
            SET v_path_recurr='';
        END IF;
                        
#        SELECT COUNT(rowid) INTO n FROM `llx_mat_view_product_cat_flat` WHERE `rowid`=v_rowid;
#        IF n>0
        IF NOT EXISTS(SELECT * FROM `llx_mat_view_product_cat_flat` 
                     WHERE `rowid` = v_rowid) 
			THEN
				SET v_insert_list = CONCAT('(','`rowid`',',','`ref`',',','`label`',',','`price`',',');
                SET v_insert_list = CONCAT(v_insert_list,
					'`',v_path_field,v_id_flat,'`,',
					'`',v_lvl_field,v_id_flat,'`,',
					'`',v_id_leaf_field,v_id_flat,'`,');
                SET v_insert_list = CONCAT(v_insert_list,
					'`',v_path_field,v_id_gamme,'`,',
					'`',v_lvl_field,v_id_gamme,'`,',
					'`',v_id_leaf_field,v_id_gamme,'`,');
                SET v_insert_list = CONCAT(v_insert_list,
					'`',v_path_field,v_id_recurr,'`,',
					'`',v_lvl_field,v_id_recurr,'`,',
					'`',v_id_leaf_field,v_id_recurr,'`) ');
				SET v_insert_values = CONCAT('VALUES(',v_rowid,',\'',v_ref,'\',\'',v_label_esc,'\',',v_price,',');
                SET v_insert_values = CONCAT(v_insert_values,
					'\'',v_path_esc,'\',',
                    v_lvl,',',
					v_id_leaf,',');
                SET v_insert_values = CONCAT(v_insert_values,
					'\'',v_path_gamme,'\',',
                    v_lvl_gamme,',',
					v_idcat_gamme,',');
                SET v_insert_values = CONCAT(v_insert_values,
					'\'',v_path_recurr,'\',',
                    v_lvl_recurr,',',
					v_idcat_recurr,')');
                SET @v_insert_stmt = CONCAT('INSERT INTO `llx_mat_view_product_cat_flat` ',
					v_insert_list,
                    v_insert_values);
#				INSERT INTO `llx_debug` (`msg`) VALUES(CONCAT('@v_insert_stmt=',@v_insert_stmt));
                START TRANSACTION;
				PREPARE stmt_insert FROM @v_insert_stmt;
                EXECUTE stmt_insert;
                COMMIT;
                DEALLOCATE PREPARE stmt_insert;
			ELSE
				SET v_update_set = CONCAT('`rowid`=',v_rowid,',','`ref`=\'',v_ref,'\',',
					'`label`=\'',v_label_esc,'\',','`price`=',v_price,',',
					'`',v_path_field,v_id_flat,'`=\'',v_path_esc,'\',',
					'`',v_lvl_field,v_id_flat,'`=',v_lvl,',',
					'`',v_id_leaf_field,v_id_flat,'`=',v_id_leaf);
                SET @v_update_stmt = CONCAT('UPDATE `llx_mat_view_product_cat_flat` SET ',
					v_update_set,
					' WHERE rowid=',v_rowid);
#				INSERT INTO `llx_debug` (`msg`) VALUES(CONCAT('@v_update_stmt=',@v_update_stmt));
                START TRANSACTION;
				PREPARE stmt_update FROM @v_update_stmt;
                EXECUTE stmt_update;
                COMMIT;
				DEALLOCATE PREPARE stmt_update;
				IF v_lvl_gamme=1 THEN
					SET v_update_set = CONCAT(
						'`',v_path_field,v_id_gamme,'`=\'',v_path_esc,'\',',
						'`',v_lvl_field,v_id_gamme,'`=',v_lvl_gamme,',',
						'`',v_id_leaf_field,v_id_gamme,'`=',v_idcat_gamme);
					SET @v_update_stmt = CONCAT('UPDATE `llx_mat_view_product_cat_flat` SET ',
						v_update_set,
						' WHERE rowid=',v_rowid);
#						INSERT INTO `llx_debug` (`msg`) VALUES(CONCAT('@v_update_stmt=',@v_update_stmt));
					START TRANSACTION;
					PREPARE stmt_update FROM @v_update_stmt;
					EXECUTE stmt_update;
					COMMIT;
					DEALLOCATE PREPARE stmt_update;        
				END IF;
				IF v_lvl_recurr=1 THEN
					SET v_update_set = CONCAT(
						'`',v_path_field,v_id_recurr,'`=\'',v_path_esc,'\',',
						'`',v_lvl_field,v_id_recurr,'`=',v_lvl_recurr,',',
						'`',v_id_leaf_field,v_id_recurr,'`=',v_idcat_recurr);
					SET @v_update_stmt = CONCAT('UPDATE `llx_mat_view_product_cat_flat` SET ',
						v_update_set,
						' WHERE rowid=',v_rowid);
#						INSERT INTO `llx_debug` (`msg`) VALUES(CONCAT('@v_update_stmt=',@v_update_stmt));
					START TRANSACTION;
					PREPARE stmt_update FROM @v_update_stmt;
					EXECUTE stmt_update;
					COMMIT;
					DEALLOCATE PREPARE stmt_update;        
				END IF;
        END IF;
    END LOOP prod_loop;
    CLOSE cur_prod;
#    DROP TEMPORARY TABLE t_ids;
END$$
DELIMITER ;
