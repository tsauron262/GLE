DROP TABLE IF EXISTS `llx_olap_view_facture`;
DROP VIEW IF EXISTS `llx_olap_view_facture`;
CREATE ALGORITHM=UNDEFINED
DEFINER=`root`@`%` SQL SECURITY DEFINER
VIEW `llx_olap_view_facture` AS 
(select `llx_facture`.`rowid` AS `rowid`,
	`llx_facture`.`ref` AS `ref`,
	`llx_facture`.`date_valid` AS `date_valid`,
	`llx_facture`.`date_valid_year` AS `date_valid_year`,
	`llx_facture`.`date_valid_quarter` AS `date_valid_quarter`,
	`llx_facture`.`date_valid_month` AS `date_valid_month`,
	`llx_facture`.`date_valid_day` AS `date_valid_day`,
	`llx_facture`.`total_ht` AS `total_ht`,
	`llx_facture`.`type` AS `type`,
	cast(`llx_facture_extrafields`.`entrepot` as unsigned) AS `id_entrepot`,
	`llx_bs_sav`.`id_entrepot` AS `sav_entrepot`,
	`llx_facture`.`fk_user_author` AS `id_user`,
	`llx_facture`.`fk_soc` AS `id_societe`,
	sum(`llx_facturedet`.`total_ht` - `llx_facturedet`.`buy_price_ht` * `llx_facturedet`.`qty`) AS `total_marge` 
from (((`llx_facture` left join `llx_facture_extrafields` on(`llx_facture_extrafields`.`fk_object` = `llx_facture`.`rowid`)) 
		left join `llx_facturedet` on(`llx_facturedet`.`fk_facture` = `llx_facture`.`rowid`)) 
		left join `llx_bs_sav` on(`llx_bs_sav`.`id_facture` = `llx_facture`.`rowid`)) 
group by `llx_facture`.`ref`);