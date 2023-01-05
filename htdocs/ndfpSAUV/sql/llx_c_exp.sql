-- ============================================================================
-- Copyright (C) 2012 Mikael Carlavan  <mcarlavan@qis-network.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ============================================================================

CREATE TABLE IF NOT EXISTS `llx_c_exp`(
  `rowid`          int(11)  AUTO_INCREMENT,
  `code`         varchar(12) UNIQUE NOT NULL,
  `label`      varchar(48) NOT NULL,
  `fk_tva`       int(11) DEFAULT 15 NOT NULL, 
  `active`		int(11) DEFAULT 1 NOT NULL,	  	   
  PRIMARY KEY (`rowid`)    
)ENGINE=innodb DEFAULT CHARSET=utf8;

TRUNCATE TABLE `llx_c_exp`;

INSERT INTO llx_c_exp (`code`, `label`, `fk_tva`, `active`) values ('EX_FLI', 'Flight', 14, 1);
INSERT INTO llx_c_exp (`code`, `label`, `fk_tva`, `active`) values ('EX_TRA', 'Train', 14, 1);
INSERT INTO llx_c_exp (`code`, `label`, `fk_tva`, `active`) values ('EX_TAX', 'Taxi', 14, 1);
INSERT INTO llx_c_exp (`code`, `label`, `fk_tva`, `active`) values ('EX_RES', 'Restaurant', 17, 1);
INSERT INTO llx_c_exp (`code`, `label`, `fk_tva`, `active`) values ('EX_OTH', 'Other', 15, 1);
INSERT INTO llx_c_exp (`code`, `label`, `fk_tva`, `active`) values ('EX_FUE', 'Fuel', 11, 1);
INSERT INTO llx_c_exp (`code`, `label`, `fk_tva`, `active`) values ('EX_KME', 'Km', 15, 1);
INSERT INTO llx_c_exp (`code`, `label`, `fk_tva`, `active`) values ('EX_HOT', 'Hotel', 17, 1);
INSERT INTO llx_c_exp (`code`, `label`, `fk_tva`, `active`) values ('EX_PAR', 'Parking', 11, 1);
INSERT INTO llx_c_exp (`code`, `label`, `fk_tva`, `active`) values ('EX_TOL', 'Toll', 11, 1);



