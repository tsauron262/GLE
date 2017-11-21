-- If you run that's sript manually, make sure you selected the good database before sending queries --

SET FOREIGN_KEY_CHECKS = 0;

-- Suppress all rows --
DELETE FROM llx_product;
DELETE FROM llx_product_price;
DELETE FROM llx_product_extrafields;
DELETE FROM llx_categorie;
DELETE FROM llx_categorie_product;
DELETE FROM llx_bimp_cat_cat;


-- Adding categorie row --
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (183,0,'Iphone','Desc Iphone','ffaaaa','0',0,null,1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (184,183,'1','Desc 1','ffd4aa','0',0,null,1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (185,183,'2','Desc 2','c4c4c4','0',0,null,1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (186,184,'7p','Desc 7p','d4ffaa','0',0,null,1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (187,184,'8p','Desc 8p','ceffff','0',0,null,1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (188,186,'Pro','Dsc Pro','aad4ff','0',0,null,1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (189,186,'Loisir','Desc loisir','d8b8f9','0',0,null,1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (190,186,'gamer','Desc gamer','ffbfde','0',0,null,1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (191,185,'9p','Desc 9p','e5e5a9','0',0,null,1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (192,185,'10p','Là on dirait plutôt une tablette ...','d8a270','0',0,null,1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (193,0,'Mac','Desc Mac','ff5656','0',0,null,1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (194,193,'Graphiste','Desc graphiste','5f9edd','0',0,null,1);
INSERT INTO llx_categorie (rowid, fk_parent, label, description, color, visible, type, import_key, entity) VALUES (195,193,'Développeur','Desc dev','7bbf37','0',0,null,1);

-- Adding product row --
INSERT INTO llx_product (datec, entity, ref, ref_ext, price_min, price_min_ttc, label, fk_user_author, fk_product_type, price, price_ttc, price_base_type, tobuy, tosell, accountancy_code_buy, accountancy_code_sell, canvas, finished, tobatch, fk_unit) VALUES ('20171117102740', 1, 'Ref_Iphone1_7p_gamer', null, 0, 0, 'Iphone1 7p gamer', 1, 0, 0, 0, 'HT', 1, 1, '', '', '', null, 0, NULL);
INSERT INTO llx_product_price(price_level,date_price, fk_product, fk_user_author, price, price_ttc, price_base_type,tosell, tva_tx, default_vat_code, recuperableonly, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, price_min,price_min_ttc,price_by_qty,entity,fk_price_expression)  VALUES(1, '20171117102740',415,1,0,0,'HT',1,20, null,0, 0, 0, '0', '0', 0,0,0,1,null);
INSERT INTO llx_product_extrafields (fk_object,longdescript) VALUES (415,null);
INSERT INTO llx_categorie_product (fk_categorie, fk_product) VALUES (191, 415);
INSERT INTO llx_product (datec, entity, ref, ref_ext, price_min, price_min_ttc, label, fk_user_author, fk_product_type, price, price_ttc, price_base_type, tobuy, tosell, accountancy_code_buy, accountancy_code_sell, canvas, finished, tobatch, fk_unit) VALUES ('20171117102835', 1, 'Ref_Iphone1_7p_Loisir', null, 0, 0, 'Iphone1 7p Loisir', 1, 0, 0, 0, 'HT', 1, 1, '', '', '', null, 0, NULL);
INSERT INTO llx_product_price(price_level,date_price, fk_product, fk_user_author, price, price_ttc, price_base_type,tosell, tva_tx, default_vat_code, recuperableonly, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, price_min,price_min_ttc,price_by_qty,entity,fk_price_expression)  VALUES(1, '20171117102835',416,1,0,0,'HT',1,20, null,0, 0, 0, '0', '0', 0,0,0,1,null);
INSERT INTO llx_product_extrafields (fk_object,longdescript) VALUES (416,null);
INSERT INTO llx_categorie_product (fk_categorie, fk_product) VALUES (190, 416);
INSERT INTO llx_product (datec, entity, ref, ref_ext, price_min, price_min_ttc, label, fk_user_author, fk_product_type, price, price_ttc, price_base_type, tobuy, tosell, accountancy_code_buy, accountancy_code_sell, canvas, finished, tobatch, fk_unit) VALUES ('20171117102912', 1, 'Ref_Iphone1_7p_pro', null, 0, 0, 'Iphone1 7p pro', 1, 0, 0, 0, 'HT', 1, 1, '', '', '', null, 0, NULL);
INSERT INTO llx_product_price(price_level,date_price, fk_product, fk_user_author, price, price_ttc, price_base_type,tosell, tva_tx, default_vat_code, recuperableonly, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, price_min,price_min_ttc,price_by_qty,entity,fk_price_expression)  VALUES(1, '20171117102912',417,1,0,0,'HT',1,20, null,0, 0, 0, '0', '0', 0,0,0,1,null);
INSERT INTO llx_product_extrafields (fk_object,longdescript) VALUES (417,null);
INSERT INTO llx_categorie_product (fk_categorie, fk_product) VALUES (189, 417);
INSERT INTO llx_product (datec, entity, ref, ref_ext, price_min, price_min_ttc, label, fk_user_author, fk_product_type, price, price_ttc, price_base_type, tobuy, tosell, accountancy_code_buy, accountancy_code_sell, canvas, finished, tobatch, fk_unit) VALUES ('20171117102947', 1, 'Ref_Iphone1_8p', null, 0, 0, 'Iphone1 8p', 1, 0, 0, 0, 'HT', 1, 1, '', '', '', null, 0, NULL);
INSERT INTO llx_product_price(price_level,date_price, fk_product, fk_user_author, price, price_ttc, price_base_type,tosell, tva_tx, default_vat_code, recuperableonly, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, price_min,price_min_ttc,price_by_qty,entity,fk_price_expression)  VALUES(1, '20171117102947',418,1,0,0,'HT',1,20, null,0, 0, 0, '0', '0', 0,0,0,1,null);
INSERT INTO llx_product_extrafields (fk_object,longdescript) VALUES (418,null);
INSERT INTO llx_categorie_product (fk_categorie, fk_product) VALUES (188, 418);
INSERT INTO llx_product (datec, entity, ref, ref_ext, price_min, price_min_ttc, label, fk_user_author, fk_product_type, price, price_ttc, price_base_type, tobuy, tosell, accountancy_code_buy, accountancy_code_sell, canvas, finished, tobatch, fk_unit) VALUES ('20171117103034', 1, 'ref_Iphone2_9p', null, 0, 0, 'Iphone2 9p', 1, 0, 0, 0, 'HT', 1, 1, '', '', '', null, 0, NULL);
INSERT INTO llx_product_price(price_level,date_price, fk_product, fk_user_author, price, price_ttc, price_base_type,tosell, tva_tx, default_vat_code, recuperableonly, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, price_min,price_min_ttc,price_by_qty,entity,fk_price_expression)  VALUES(1, '20171117103034',419,1,0,0,'HT',1,20, null,0, 0, 0, '0', '0', 0,0,0,1,null);
INSERT INTO llx_product_extrafields (fk_object,longdescript) VALUES (419,null);
INSERT INTO llx_categorie_product (fk_categorie, fk_product) VALUES (192, 419);
INSERT INTO llx_product (datec, entity, ref, ref_ext, price_min, price_min_ttc, label, fk_user_author, fk_product_type, price, price_ttc, price_base_type, tobuy, tosell, accountancy_code_buy, accountancy_code_sell, canvas, finished, tobatch, fk_unit) VALUES ('20171117103125', 1, 'ref__Iphone2_10p', null, 0, 0, 'Iphone2 10p', 1, 0, 0, 0, 'HT', 1, 1, '', '', '', null, 0, NULL);
INSERT INTO llx_product_price(price_level,date_price, fk_product, fk_user_author, price, price_ttc, price_base_type,tosell, tva_tx, default_vat_code, recuperableonly, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, price_min,price_min_ttc,price_by_qty,entity,fk_price_expression)  VALUES(1, '20171117103125',420,1,0,0,'HT',1,20, null,0, 0, 0, '0', '0', 0,0,0,1,null);
INSERT INTO llx_product_extrafields (fk_object,longdescript) VALUES (420,null);
INSERT INTO llx_categorie_product (fk_categorie, fk_product) VALUES (193, 420);
INSERT INTO llx_product (datec, entity, ref, ref_ext, price_min, price_min_ttc, label, fk_user_author, fk_product_type, price, price_ttc, price_base_type, tobuy, tosell, accountancy_code_buy, accountancy_code_sell, canvas, finished, tobatch, fk_unit) VALUES ('20171117103204', 1, 'ref_mac_dev', null, 0, 0, 'mac dev', 1, 0, 0, 0, 'HT', 1, 1, '', '', '', null, 0, NULL);
INSERT INTO llx_product_price(price_level,date_price, fk_product, fk_user_author, price, price_ttc, price_base_type,tosell, tva_tx, default_vat_code, recuperableonly, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, price_min,price_min_ttc,price_by_qty,entity,fk_price_expression)  VALUES(1, '20171117103204',421,1,0,0,'HT',1,20, null,0, 0, 0, '0', '0', 0,0,0,1,null);
INSERT INTO llx_product_extrafields (fk_object,longdescript) VALUES (421,null);
INSERT INTO llx_categorie_product (fk_categorie, fk_product) VALUES (195, 421);
INSERT INTO llx_product (datec, entity, ref, ref_ext, price_min, price_min_ttc, label, fk_user_author, fk_product_type, price, price_ttc, price_base_type, tobuy, tosell, accountancy_code_buy, accountancy_code_sell, canvas, finished, tobatch, fk_unit) VALUES ('20171117103239', 1, 'ref_mac_graphiste', null, 0, 0, 'mac graphiste', 1, 0, 0, 0, 'HT', 1, 1, '', '', '', null, 0, NULL);
INSERT INTO llx_product_price(price_level,date_price, fk_product, fk_user_author, price, price_ttc, price_base_type,tosell, tva_tx, default_vat_code, recuperableonly, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, price_min,price_min_ttc,price_by_qty,entity,fk_price_expression)  VALUES(1, '20171117103239',422,1,0,0,'HT',1,20, null,0, 0, 0, '0', '0', 0,0,0,1,null);
INSERT INTO llx_product_extrafields (fk_object,longdescript) VALUES (422,null);
INSERT INTO llx_categorie_product (fk_categorie, fk_product) VALUES (194, 422);

-- Adding link between categories and caregories --
INSERT INTO llx_bimp_cat_cat (fk_parent_cat, fk_child_cat) VALUES (186, 189);
INSERT INTO llx_bimp_cat_cat (fk_parent_cat, fk_child_cat) VALUES (186, 188);

SET FOREIGN_KEY_CHECKS = 1;
