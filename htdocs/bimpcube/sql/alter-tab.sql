---
--- llx_commande
---
ALTER TABLE `llx_commande`
ADD COLUMN `date_valid_year` int(4) GENERATED ALWAYS AS (year(`date_valid`)) STORED,
ADD COLUMN `date_valid_quarter` int(1) GENERATED ALWAYS AS (quarter(`date_valid`)) STORED AFTER `date_valid_year`,
ADD COLUMN `date_valid_month` int(2) GENERATED ALWAYS AS (month(`date_valid`)) STORED AFTER `date_valid_quarter`,
ADD COLUMN `date_valid_day` int(2) GENERATED ALWAYS AS (dayofmonth(`date_valid`)) STORED AFTER `date_valid_month`,
ADD COLUMN `fk_user_comm` int(11) DEFAULT 0 AFTER `marge`,
ADD KEY `idx_date_valid_year` (`date_valid_year`),
ADD KEY `idx_date_valid_quarter` (`date_valid_quarter`),
ADD KEY `idx_date_valid_month` (`date_valid_month`),
ADD KEY `idx_date_valid_day` (`date_valid_day`),
ADD KEY `idx_user_comm` (`fk_user_comm`);
---
--- llx_facture
---
ALTER TABLE `llx_facture`
ADD COLUMN `date_valid_year` int(4) GENERATED ALWAYS AS (year(`date_valid`)) STORED,
ADD COLUMN `date_valid_quarter` int(1) GENERATED ALWAYS AS (quarter(`date_valid`)) STORED AFTER `date_valid_year`,
ADD COLUMN `date_valid_month` int(2) GENERATED ALWAYS AS (month(`date_valid`)) STORED AFTER `date_valid_quarter`,
ADD COLUMN `date_valid_day` int(2) GENERATED ALWAYS AS (dayofmonth(`date_valid`)) STORED AFTER `date_valid_month`,
ADD COLUMN `fk_user_comm` int(11) DEFAULT 0 AFTER `marge`,
ADD KEY `idx_date_valid_year` (`date_valid_year`),
ADD KEY `idx_date_valid_quarter` (`date_valid_quarter`),
ADD KEY `idx_date_valid_month` (`date_valid_month`),
ADD KEY `idx_date_valid_day` (`date_valid_day`),
ADD KEY `idx_user_comm` (`fk_user_comm`);
---
--- llx_propal
---
ALTER TABLE `llx_propal`
ADD COLUMN `date_valid_year` int(4) GENERATED ALWAYS AS (year(`date_valid`)) STORED,
ADD COLUMN `date_valid_quarter` int(1) GENERATED ALWAYS AS (quarter(`date_valid`)) STORED AFTER `date_valid_year`,
ADD COLUMN `date_valid_month` int(2) GENERATED ALWAYS AS (month(`date_valid`)) STORED AFTER `date_valid_quarter`,
ADD COLUMN `date_valid_day` int(2) GENERATED ALWAYS AS (dayofmonth(`date_valid`)) STORED AFTER `date_valid_month`,
ADD KEY `idx_date_valid_year` (`date_valid_year`),
ADD KEY `idx_date_valid_quarter` (`date_valid_quarter`),
ADD KEY `idx_date_valid_month` (`date_valid_month`),
ADD KEY `idx_date_valid_day` (`date_valid_day`);
---
--- llx_bs_sav
---
ALTER TABLE `llx_bs_sav`
ADD COLUMN `date_valid_year` int(4) GENERATED ALWAYS AS (year(`date_valid`)) STORED,
ADD COLUMN `date_valid_quarter` int(1) GENERATED ALWAYS AS (quarter(`date_valid`)) STORED AFTER `date_valid_year`,
ADD COLUMN `date_valid_month` int(2) GENERATED ALWAYS AS (month(`date_valid`)) STORED AFTER `date_valid_quarter`,
ADD COLUMN `date_valid_day` int(2) GENERATED ALWAYS AS (dayofmonth(`date_valid`)) STORED AFTER `date_valid_month`,
ADD KEY `idx_date_valid_year` (`date_valid_year`),
ADD KEY `idx_date_valid_quarter` (`date_valid_quarter`),
ADD KEY `idx_date_valid_month` (`date_valid_month`),
ADD KEY `idx_date_valid_day` (`date_valid_day`);

