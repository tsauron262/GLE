DROP VIEW IF EXISTS llx_view_hotline_commande;

CREATE VIEW llx_view_hotline_commande as (SELECT p.ref, c.rowid as comm_id, bcl.id as line_id, s.rowid as soc_id, p.rowid as prod_id, c.date_valid, pe.2hotline, DATE_ADD(c.date_valid, INTERVAL pe.2hotline DAY)  FROM `llx_commandedet` a LEFT JOIN llx_bimp_commande_line bcl ON bcl.id_line = a.rowid LEFT JOIN llx_product p ON p.rowid = a.fk_product LEFT JOIN llx_product_extrafields pe ON pe.fk_object = p.rowid LEFT JOIN llx_commande c ON c.rowid = a.`fk_commande` LEFT JOIN llx_societe s ON s.rowid = c.fk_soc WHERE pe.2hotline > 0 AND DATE_ADD(c.date_valid, INTERVAL pe.2hotline MONTH) > now())

ALTER TABLE `llx_bs_ticket` ADD `id_service` INT NOT NULL AFTER `id_contrat`;