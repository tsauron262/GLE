DROP table llx_societe_atradius;
CREATE TABLE llx_societe_atradius (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `id_soc` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `id_atradius` INT NOT NULL DEFAULT '0',
  `date_depot_icba` DATETIME NULL DEFAULT NULL,
`outstanding_limit_icba` DECIMAL(24,8) DEFAULT 0 NOT NULL,
`status_atradius` INT NOT NULL DEFAULT '0',
`date_demande_atradius` DATETIME NULL DEFAULT NULL,
`date_rappel_atradius` DATETIME NULL DEFAULT NULL,
`date_atradius` DATETIME DEFAULT NULL,
`outstanding_limit_atradius` DECIMAL(24,8) DEFAULT 0 NOT NULL,
`outstanding_limit_manuel` DECIMAL(24,8) DEFAULT 0 NOT NULL,
`outstanding_limit_credit_check` DECIMAL(24,8) DEFAULT 0 NOT NULL,
`outstanding_limit` DECIMAL(24,8) DEFAULT 0 NOT NULL,
entity INT default 1
) ENGINE=InnoDB;

ALTER table llx_bimpcore_history ADD filters text DEFAULT '[]';



INSERT into llx_societe_atradius (SELECT null, rowid,id_atradius,date_depot_icba,outstanding_limit_icba,status_atradius,
date_demande_atradius,date_rappel_atradius,date_atradius,outstanding_limit_atradius,outstanding_limit_manuel,
outstanding_limit_credit_check,outstanding_limit,1 FROM llx_societe);


UPDATE llx_bimpcore_history SET filters = '{"entity":"1"}' WHERE filters = '[]' AND field IN ('outstanding_limit', 'outstanding_limit_credit_check', 'outstanding_limit_manuel', 'outstanding_limit_atradius', 'outstanding_limit_icba');