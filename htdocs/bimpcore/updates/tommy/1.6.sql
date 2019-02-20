RENAME TABLE llx_synopsis_fichinter TO llx_synopsis_fichinterSAUV;

CREATE VIEW `llx_synopsis_fichinter` AS (SELECT f.*, `fk_commande`, `total_ht`, `total_tva`, `total_ttc`, `natureInter` from (`llx_fichinter` `f` left join `llx_synopsisfichinter` `sf` on(`f`.`rowid` = `sf`.`rowid`)));

RENAME TABLE llx_synopsis_fichinterdet TO llx_synopsis_fichinterdetSAUV;

CREATE  VIEW `llx_synopsis_fichinterdet` AS (select f.*, `fk_typeinterv`, `fk_depProduct`, `tx_tva`, `pu_ht`, `qte`, `total_ht`, `total_tva`, `total_ttc`, `fk_contratdet`, `fk_commandedet`, `isForfait` from (`llx_fichinterdet` `f` left join `llx_synopsisfichinterdet` `sf` on(`f`.`rowid` = `sf`.`rowid`)));
    