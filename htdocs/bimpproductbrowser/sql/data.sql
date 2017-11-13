-- If you run that's sript manually, make sure you selected the good database before sending queries --

SET FOREIGN_KEY_CHECKS = 0;

-- Suppress all rows --
DELETE FROM llx_product;
DELETE FROM llx_product_price;
DELETE FROM llx_product_extrafields;
DELETE FROM llx_categorie;
DELETE FROM llx_categorie_product;
DELETE FROM llx_bimp_cat_cat;

-- Adding product row --
INSERT INTO llx_product (datec, entity, ref, ref_ext, price_min, price_min_ttc, label, fk_user_author, fk_product_type, price, price_ttc, price_base_type, tobuy, tosell, accountancy_code_buy, accountancy_code_sell, canvas, finished, tobatch, fk_unit) VALUES ('20171107092224', 1, '2', null, 0, 0, 'Iphone1 écran 7p basique', 1, 0, 0, 0, 'HT', 1, 1, '', '', '', null, 0, NULL);
INSERT INTO llx_product_price(price_level,date_price, fk_product, fk_user_author, price, price_ttc, price_base_type,tosell, tva_tx, default_vat_code, recuperableonly, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, price_min,price_min_ttc,price_by_qty,entity,fk_price_expression)  VALUES(1, '20171107092224',2,1,0,0,'HT',1,20, null,0, 0, 0, '0', '0', 0,0,0,1,null);
INSERT INTO llx_product_extrafields (fk_object,longdescript) VALUES (2,null);
INSERT INTO llx_product (datec, entity, ref, ref_ext, price_min, price_min_ttc, label, fk_user_author, fk_product_type, price, price_ttc, price_base_type, tobuy, tosell, accountancy_code_buy, accountancy_code_sell, canvas, finished, tobatch, fk_unit) VALUES ('20171107092301', 1, '3', null, 0, 0, 'Iphone1 écran 7p pro', 1, 0, 0, 0, 'HT', 1, 1, '', '', '', null, 0, NULL);
INSERT INTO llx_product_price(price_level,date_price, fk_product, fk_user_author, price, price_ttc, price_base_type,tosell, tva_tx, default_vat_code, recuperableonly, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, price_min,price_min_ttc,price_by_qty,entity,fk_price_expression)  VALUES(1, '20171107092301',3,1,0,0,'HT',1,20, null,0, 0, 0, '0', '0', 0,0,0,1,null);
INSERT INTO llx_product_extrafields (fk_object,longdescript) VALUES (3,null);
INSERT INTO llx_product (datec, entity, ref, ref_ext, price_min, price_min_ttc, label, fk_user_author, fk_product_type, price, price_ttc, price_base_type, tobuy, tosell, accountancy_code_buy, accountancy_code_sell, canvas, finished, tobatch, fk_unit) VALUES ('20171107092326', 1, '4', null, 0, 0, 'Iphone1 écran 8p', 1, 0, 0, 0, 'HT', 1, 1, '', '', '', null, 0, NULL);
INSERT INTO llx_product_price(price_level,date_price, fk_product, fk_user_author, price, price_ttc, price_base_type,tosell, tva_tx, default_vat_code, recuperableonly, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, price_min,price_min_ttc,price_by_qty,entity,fk_price_expression)  VALUES(1, '20171107092326',4,1,0,0,'HT',1,20, null,0, 0, 0, '0', '0', 0,0,0,1,null);
INSERT INTO llx_product_extrafields (fk_object,longdescript) VALUES (4,null);
INSERT INTO llx_product (datec, entity, ref, ref_ext, price_min, price_min_ttc, label, fk_user_author, fk_product_type, price, price_ttc, price_base_type, tobuy, tosell, accountancy_code_buy, accountancy_code_sell, canvas, finished, tobatch, fk_unit) VALUES ('20171107092358', 1, '5', null, 0, 0, 'Iphone2 écran 7p', 1, 0, 0, 0, 'HT', 1, 1, '', '', '', null, 0, NULL);
INSERT INTO llx_product_price(price_level,date_price, fk_product, fk_user_author, price, price_ttc, price_base_type,tosell, tva_tx, default_vat_code, recuperableonly, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, price_min,price_min_ttc,price_by_qty,entity,fk_price_expression)  VALUES(1, '20171107092358',5,1,0,0,'HT',1,20, null,0, 0, 0, '0', '0', 0,0,0,1,null);
INSERT INTO llx_product_extrafields (fk_object,longdescript) VALUES (5,null);
INSERT INTO llx_product (datec, entity, ref, ref_ext, price_min, price_min_ttc, label, fk_user_author, fk_product_type, price, price_ttc, price_base_type, tobuy, tosell, accountancy_code_buy, accountancy_code_sell, canvas, finished, tobatch, fk_unit) VALUES ('20171107092421', 1, '6', null, 0, 0, 'Iphone2 écran 8p', 1, 0, 0, 0, 'HT', 1, 1, '', '', '', null, 0, NULL);
INSERT INTO llx_product_price(price_level,date_price, fk_product, fk_user_author, price, price_ttc, price_base_type,tosell, tva_tx, default_vat_code, recuperableonly, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, price_min,price_min_ttc,price_by_qty,entity,fk_price_expression)  VALUES(1, '20171107092421',6,1,0,0,'HT',1,20, null,0, 0, 0, '0', '0', 0,0,0,1,null);
INSERT INTO llx_product_extrafields (fk_object,longdescript) VALUES (6,null);
INSERT INTO llx_product (datec, entity, ref, ref_ext, price_min, price_min_ttc, label, fk_user_author, fk_product_type, price, price_ttc, price_base_type, tobuy, tosell, accountancy_code_buy, accountancy_code_sell, canvas, finished, tobatch, fk_unit) VALUES ('20171107092544', 1, '1', null, 0, 0, 'Iphone1 écran 7p gamer', 1, 0, 0, 0, 'HT', 1, 1, '', '', '', null, 0, NULL);
INSERT INTO llx_product_price(price_level,date_price, fk_product, fk_user_author, price, price_ttc, price_base_type,tosell, tva_tx, default_vat_code, recuperableonly, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, price_min,price_min_ttc,price_by_qty,entity,fk_price_expression)  VALUES(1, '20171107092544',7,1,0,0,'HT',1,20, null,0, 0, 0, '0', '0', 0,0,0,1,null);
INSERT INTO llx_product_extrafields (fk_object,longdescript) VALUES (7,null);

-- Adding categorie row --
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (111, 0, 'Root', '', '', '0', 0, null, 1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (1, 0, 'Iphone', '', '', '0', 0, null, 1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (2, 1, '1', '', '', '0', 0, null, 1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (3, 2, '7p', '', '', '0', 0, null, 1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (4, 3, 'basique', '', '', '0', 0 , null, 1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (5, 3, 'gamer', '', '', '0', 0, null, 1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (6, 3, 'pro', '', '', '0', 0, null, 1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (7, 2, '8p', '', '', '0', 0, null, 1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (8, 1, '2', '', '', '0', 0, null, 1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (9, 8, '7p', '', '', '0', 0, null, 1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (10, 8, '8p', '', '', '0', 0, null, 1);

-- Editting link between categorie and product --
INSERT INTO llx_categorie_product (fk_categorie, fk_product) VALUES (18, 2);
INSERT INTO llx_categorie_product (fk_categorie, fk_product) VALUES (19, 7);
INSERT INTO llx_categorie_product (fk_categorie, fk_product) VALUES (20, 3);
INSERT INTO llx_categorie_product (fk_categorie, fk_product) VALUES (21, 4);
INSERT INTO llx_categorie_product (fk_categorie, fk_product) VALUES (23, 5);
INSERT INTO llx_categorie_product (fk_categorie, fk_product) VALUES (24, 6);

SET FOREIGN_KEY_CHECKS = 1;

-- Adding link between categories and caregories --
#INSERT INTO llx_bimp_cat_cat (rowid, fk_parent_cat, fk_child_cat) VALUES (1, 111, 1);
#INSERT INTO llx_bimp_cat_cat (rowid, fk_parent_cat, fk_child_cat) VALUES (2, 1, 2);
#INSERT INTO llx_bimp_cat_cat (rowid, fk_parent_cat, fk_child_cat) VALUES (3, 2, 3);
#INSERT INTO llx_bimp_cat_cat (rowid, fk_parent_cat, fk_child_cat) VALUES (4, 3, 4);
#INSERT INTO llx_bimp_cat_cat (rowid, fk_parent_cat, fk_child_cat) VALUES (5, 3, 5);
#INSERT INTO llx_bimp_cat_cat (rowid, fk_parent_cat, fk_child_cat) VALUES (6, 3, 6);
#INSERT INTO llx_bimp_cat_cat (rowid, fk_parent_cat, fk_child_cat) VALUES (7, 2, 7);
#INSERT INTO llx_bimp_cat_cat (rowid, fk_parent_cat, fk_child_cat) VALUES (8, 1, 8);
#INSERT INTO llx_bimp_cat_cat (rowid, fk_parent_cat, fk_child_cat) VALUES (9, 8, 9);
#INSERT INTO llx_bimp_cat_cat (rowid, fk_parent_cat, fk_child_cat) VALUES (10, 8, 10);