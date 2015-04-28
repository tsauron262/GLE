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

CREATE TABLE IF NOT EXISTS `llx_c_exp_tax_cat`(
  `rowid`          int(11)  AUTO_INCREMENT,
  `label`           varchar(48) NOT NULL, 
  `fk_parent`		int(11) DEFAULT 1 NOT NULL,   
  `active`		int(11) DEFAULT 1 NOT NULL,	          
  PRIMARY KEY (`rowid`)    
)ENGINE=innodb DEFAULT CHARSET=utf8;

TRUNCATE TABLE `llx_c_exp_tax_cat`;

INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('AutoCat', 0, 1);
INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('CycloCat', 0, 1);
INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('MotoCat', 0, 1);

INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('Auto3CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('Auto4CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('Auto5CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('Auto6CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('Auto7CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('Auto8CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('Auto9CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('Auto10CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('Auto11CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('Auto12CV', 1, 1);
INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('Auto13PCV', 1, 1);

INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('Cyclo', 2, 1);

INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('Moto12CV', 3, 1);
INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('Moto345CV', 3, 1);
INSERT INTO llx_c_exp_tax_cat (`label`, `fk_parent`, `active`) values ('Moto5PCV', 3, 1);
