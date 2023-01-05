CREATE VIEW llx_view_facture_contrat AS (
SELECT CONCAT(cd.rowid, f.rowid) as id, cd.fk_contrat, cd.fk_product, f.rowid as fk_facture, cd.rowid as fk_contratdet, f.total as totalFact, cd.total_ht as totalLn, datef as datef, renouvellement
FROM `llx_contratdet` cd LEFT JOIN llx_element_element e ON e.sourcetype= 'contrat' AND e.targettype='facture' AND e.fk_source = cd.fk_contrat
LEFT JOIN llx_facture f ON f.rowid = e.fk_target WHERE f.rowid > 0
);