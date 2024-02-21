UPDATE llx_product act SET id_bimp = (SELECT rowid FROM ERP_PROD_BIMP.llx_product bim WHERE act.ref = bim.ref) WHERE act.id_bimp < 1 OR act.id_bimp is null;

UPDATE llx_product_extrafields SET id_bimp = (SELECT id_bimp FROM llx_product p WHERE fk_object = p.rowid);


UPDATE `llx_product_extrafields` act SET gamme = (SELECT gamme FROM ERP_PROD_BIMP.llx_product_extrafields bim WHERE act.id_bimp = bim.fk_object) WHERE (gamme = 0 || gamme is null) AND act.id_bimp > 0;

UPDATE `llx_product_extrafields` act SET categorie = (SELECT categorie FROM ERP_PROD_BIMP.llx_product_extrafields bim WHERE act.id_bimp = bim.fk_object) WHERE (categorie = 0 || categorie is null) AND act.id_bimp > 0;

UPDATE `llx_product_extrafields` act SET collection = (SELECT collection FROM ERP_PROD_BIMP.llx_product_extrafields bim WHERE act.id_bimp = bim.fk_object) WHERE (collection = 0 || collection is null) AND act.id_bimp > 0;

UPDATE `llx_product_extrafields` act SET nature = (SELECT nature FROM ERP_PROD_BIMP.llx_product_extrafields bim WHERE act.id_bimp = bim.fk_object) WHERE (nature = 0 || nature is null) AND act.id_bimp > 0;

UPDATE `llx_product_extrafields` act SET famille = (SELECT famille FROM ERP_PROD_BIMP.llx_product_extrafields bim WHERE act.id_bimp = bim.fk_object) WHERE (famille = 0 || famille is null) AND act.id_bimp > 0;